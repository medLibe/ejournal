<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <DataTable
                :value="generalLedgerImports"
                :lazy="true"
                :total-records="totalRecords"
                :rows="10"
                :loading="isLoading"
                :paginator="true"
                :rows-per-page-options="[10, 25, 50]"
                :sort-order="sortOrder"
                :sort-field="sortField"
                :responsive="true"
                v-model:selection="selectedGeneralLedgerImports"
                @sort="onSortChange"
                @page="onPageChange"
            >
                <!-- header search and buttons -->
                <template #header>
                    <div class="flex justify-end items-center">
                        <InputText 
                            v-model="filters['global'].value" 
                            @input="searchGeneralLedgerImport"
                            placeholder="Search keywords..." class="w-60" />
                    </div>
                </template>

                <Column 
                    field="import_date" 
                    header="Tanggal Upload">
                    <template #body="slotProps">
                        {{ slotProps.data.import_date ? formatDate(slotProps.data.import_date) : 'Tanggal tidak tersedia' }}
                    </template>
                </Column>
                <Column 
                    field="import_no" 
                    header="No. Upload">
                    <template #body="slotProps">
                        <a 
                            target="_blank"
                            :href="`/jurnal-umum/detail/${slotProps.data.import_no}`" 
                            class="text-blue-500 hover:underline">
                            {{ slotProps.data.import_no }}
                        </a>
                    </template>
                </Column>
                <Column 
                    field="created_by" 
                    header="Diupload oleh" 
                    sortable />
            </DataTable>
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import ButtonGroup from 'primevue/buttongroup'

export default {
    name: 'GeneralLedgerEntry',
    components: {
        Breadcrumb,
        DataTable,
        Column,
        Button,
        ButtonGroup,
        InputText,
        Dialog,
        Checkbox,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            generalLedgerImports: [],
            totalRecords: 0,
            isLoading: false,
            selectedGeneralLedgerImports: [],
            filters: {
                global: { value: null, matchMode: 'contains' },
            },
            sortField: 'import_date',
            sortOrder: 1,
        }
    },
    methods: {
        async fetchGeneralLedgerImport({ first, rows, sortField, sortOrder, search }) {
            this.isLoading = true

            this.$api.get(`${import.meta.env.VITE_API_URL}/general-ledger`, {
                params: {
                    page: Math.floor(first / rows) + 1,
                    limit: rows,
                    search: search || '',
                    sort: sortField || 'import_date',
                    order: sortOrder === 1 ? 'asc' : 'desc'
                },
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { generalLedgerImports, total } = response.data
                this.generalLedgerImports = generalLedgerImports
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
            this.fetchGeneralLedgerImport(event)
        },
        onSortChange(event) {
            this.sortField = event.sortField,
            this.sortOrder = event.sortOrder

            this.fetchGeneralLedgerImport({
                first: 0,
                rows: 10,
                sortField: event.sortField,
                sortOrder: event.sortOrder
            })
        },
        searchGeneralLedgerImport() {
            this.fetchGeneralLedgerImport({
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
    },
    mounted() {
        this.fetchGeneralLedgerImport({ 
            first: 0, 
            rows: 10, 
            sortField: 'import_date',
            sortOrder: 1
        })
    }
}
</script>