<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            
            <DataTable 
                :value="generalLedgers" 
                :loading="isLoading"
            >
                
                <Column header="Tanggal">
                    <template #body="slotProps">
                        {{ slotProps.data.transaction_date ? formatDate(slotProps.data.transaction_date) : 'Tanggal tidak tersedia' }}
                    </template>
                </Column>
                <Column field="account_code" header="Nomor Akun" />
                <Column field="account_name" header="Nama Akun" />
                <Column field="department" header="Department" />
                <Column field="reference_no" header="Nomor Sumber" />
                <Column field="reference" header="Tipe Sumber" />
                <Column header="Debit">
                    <template #body="slotProps">
                        {{ slotProps.data.transaction_type === 1 ? formatCurrency(slotProps.data.amount) : 0 }}
                    </template>
                </Column>
                <Column header="Kredit">
                    <template #body="slotProps">
                        {{ slotProps.data.transaction_type === 2 ? formatCurrency(slotProps.data.amount) : 0 }}
                    </template>
                </Column>
                <Column field="description" header="Keterangan" />
                <ColumnGroup type="footer">
                    <Row>
                        <Column footer="Total Transaksi:" :colspan="6" footerStyle="text-align:right" />
                        <Column :footer="formatCurrency(totalNominal)" />
                    </Row>
                </ColumnGroup>
            </DataTable>
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import DataTable from 'primevue/datatable'
import Row from 'primevue/row'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'

export default {
    name: 'GeneralLedgerDetail',
    components: {
        Breadcrumb,
        DataTable,
        Row,
        Column,
        ColumnGroup
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            importNo: this.$route.params.importNo,
            generalLedgers: [],
            totalRecords: 0,
            isLoading: false,
        }
    },
    methods: {
        async fetchGeneralLedger() {
            this.isLoading = true

            const response = await this.$api.get(`${import.meta.env.VITE_API_URL}/general-ledger/detail/${this.importNo}`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { generalLedgers, total } = response.data
                this.generalLedgers = generalLedgers
                this.totalRecords = total
            })
            .catch(error => {
                console.error(error)

                this.$toast.add({
                    severity: 'error',
                    summary: 'Fetching Data Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during fetch data.',
                    life: 3000
                })

                this.$nextTick(() => {
                    document.activeElement.blur()
                })
            })
            .finally(() => {
                this.isLoading = false
            })
        },
        onPageChange(event) {
            this.fetchGeneralLedger(event)
        },
        getImportNoFromUrl() {
            const url = new URL(window.location.href)
            return url.pathname.split('/').pop()
        },
        searchGeneralLedgerImport() {
            this.fetchGeneralLedger({
                first: 0, 
                rows: 10, 
                sortField: this.sortField,
                sortOrder: this.sortOrder,
                search: this.filters['global'].value
            });
        },
        formatDate(rowData) {
            if (!rowData) return ''
            try {
                const [year, month, day] = rowData.split('-')
                const monthNames = [
                    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
                ]
                return `${day} ${monthNames[parseInt(month) - 1]} ${year}`
            } catch (error) {
                console.error('Invalid date format:', rowData)
                return rowData
            }
        },
        formatDateForStorage(date) {
            return this.formatDate(date)
        },
        formatCurrency(value) {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(value || 0)
        },
    },
    mounted() {
        this.fetchGeneralLedger()
    },
    computed: {
        totalNominal() {
            if (!this.generalLedgers || !Array.isArray(this.generalLedgers)) return 0
            return this.generalLedgers.reduce((total, item) => {
                const amount = parseFloat(item.amount)
                return isNaN(amount) ? total : total + amount
            }, 0)
        }
    }
}
</script>