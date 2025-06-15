<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <!-- filter modal -->
                <div class="grid grid-cols-2 gap-2">
                    <Button 
                        severity="primary"
                        icon="pi pi-filter" 
                        label="Filter" 
                        @click="filterGeneralLedgerVoucherDetailDialog = true"
                    />
                    <Button 
                        severity="primary"
                        icon="pi pi-print" 
                        label="Print"
                        @click="handlePrint"
                    />
                </div>

                <!-- search -->
                <input
                    v-model="searchQuery" 
                    placeholder="Cari No Sumber..." 
                    type="text"
                    class="w-60 px-4 py-2 border rounded-md">
            </div>
            
            <DataTable 
                :value="generalLedgers" 
                :paginator="true"
                :lazy="true"
                :rows="pagination.perPage"
                :first="(pagination.page - 1) * pagination.perPage"
                :totalRecords="totalRecords"
                @page="onPageChange"
                :loading="isLoading">
                <Column field="transaction_date" header="Tanggal">
                    <template #body="slotProps">
                        {{ formatDate(slotProps.data.transaction_date) }}
                    </template>
                </Column>
                 <Column header="Nomor Sumber">
                    <template #body="slotProps">
                        <a
                            :href="`/jurnal/koreksi/${encodeURIComponent(slotProps.data.reference_no)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sky-600 hover:text-sky-800 cursor-pointer"
                        >
                            {{ slotProps.data.reference_no }}
                        </a>
                    </template>
                </Column>
                <Column field="reference" header="Tipe Sumber" />
                <Column field="total_amount" header="Jumlah">
                    <template #body="slotProps">
                        {{ formatCurrency(slotProps.data.total_amount) }}
                    </template>
                    <template #footer>
                        <div class="font-semibold">
                            {{ formatCurrency(totalAmount) }}
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-gray-500">
                        Entri data tidak ditemukan
                    </div>
                </template>
            </DataTable>

            <!-- modal filter -->
            <ModalFilterGeneralLedgerVoucherDetail
                :isVisible="filterGeneralLedgerVoucherDetailDialog"
                @update:isVisible="filterGeneralLedgerVoucherDetailDialog = $event"
                @submitFilter="onFilterSubmit"
            />
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import DataTable from 'primevue/datatable'
import Row from 'primevue/row'
import Column from 'primevue/column'
import ColumnGroup from 'primevue/columngroup'
import Button from 'primevue/button'
import ModalFilterGeneralLedgerVoucherDetail from '../components/modal/ModalFilterGeneralLedgerVoucherDetail.vue'

export default {
    name: 'GeneralLedgerVoucherDetail',
    components: {
        Breadcrumb,
        DataTable,
        Row,
        Column,
        ColumnGroup,
        Button,
        ModalFilterGeneralLedgerVoucherDetail,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            importNo: this.$route.params.importNo,
            generalLedgers: [],
            totalRecords: 0,
            totalAmount: 0,
            isLoading: false,
            filterGeneralLedgerVoucherDetailDialog: false,
            searchQuery: '',
            pagination: {
                page: 1,
                perPage: 50
            },
            filters: {
                startDate: null,
                endDate: null,
            },
        }
    },
    methods: {
        onPageChange(event) {
            this.pagination.page = event.page + 1 // PrimeVue 0-based page
            this.fetchGeneralLedger()
        },
        onFilterSubmit(payload) {
            this.filters.startDate = payload.startDate
            this.filters.endDate = payload.endDate
            this.fetchGeneralLedger()
        },
        async fetchGeneralLedger() {
            if (!this.filters.startDate || !this.filters.endDate) return

            this.isLoading = true

            const params = {
                startDate: this.filters.startDate,
                endDate: this.filters.endDate,
                page: this.pagination.page,
                perPage: this.pagination.perPage,
                search: this.searchQuery.trim() || undefined
            }

            await this.$api.get(`${import.meta.env.VITE_API_URL}/general-ledger/voucher-detail`, {
                params,
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { generalLedgers, total, total_amount } = response.data
                this.generalLedgers = generalLedgers
                this.totalRecords = total
                this.totalAmount = total_amount
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
        async handlePrint() {
            if (!this.filters.startDate || !this.filters.endDate) {
                this.$toast.add({
                    severity: 'warn',
                    summary: 'Filter belum lengkap',
                    detail: 'Silakan pilih tanggal mulai dan akhir terlebih dahulu.',
                    life: 3000
                })
                return
            }

            const params = {
                startDate: this.filters.startDate,
                endDate: this.filters.endDate
            }

            try {
                this.showLoader()
                const response = await this.$api.get(
                    `${import.meta.env.VITE_API_URL}/general-ledger/voucher-detail/print`,
                    {
                        params,
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                        },
                        responseType: 'blob'
                    }
                )

                const blob = new Blob([response.data], { type: response.headers['content-type'] })
                const fileUrl = window.URL.createObjectURL(blob)
                window.open(fileUrl, '_blank')

            } catch (error) {
                console.error(error)
                this.$toast.add({
                    severity: 'error',
                    summary: 'Print Gagal',
                    detail: 'Terjadi kesalahan saat mencetak data.',
                    life: 3000
                })
            } finally {
                this.hideLoader()
            }
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
        formatCurrency(value) {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(value || 0)
        },
    },
    watch: {
        searchQuery: {
            handler() {
                this.pagination.page = 1
                this.fetchGeneralLedger()
            },
            immediate: false
        }
    }
}
</script>