<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <DataTable 
                :value="generalLedgers"
                :lazy="true"
                :total-records="totalRecords"
                :rows="10"
                :loading="isLoading"
                :paginator="true"
                :rows-per-page-options="[10, 25, 50]"
                :sort-order="sortOrder"
                :sort-field="sortField"
                :responsive="true"
                v-model:selection="selectedGeneralLedgers"
                @sort="onSortChange"
                @page="onPageChange"
            >
                <!-- header search and buttons -->
                <template #header>
                    <div class="flex justify-between items-center">
                        <ButtonGroup>
                            <!-- import -->
                            <Button 
                                severity="contrast"
                                size="small"
                                label="Import" 
                                icon="pi pi-plus"
                                @click="importGeneralLedgerDialog = true" />

                            <!-- print -->
                            <!-- <Button 
                                severity="contrast"
                                size="small"
                                label="Print" 
                                icon="pi pi-print"
                                @click="printToPDF" /> -->
                            
                            <!-- export -->
                            <!-- <Button 
                                severity="contrast"
                                size="small"
                                label="Export" 
                                icon="pi pi-upload"
                                @click="exportToExcel" /> -->

                            <!-- delete -->
                            <!-- <Button 
                                severity="contrast"
                                size="small"
                                label="Delete" 
                                icon="pi pi-trash"
                                @click="confirmDeleteSelected" 
                                :disabled="!selectedGeneralLedgers.length" /> -->
                        </ButtonGroup>

                        <InputText 
                            v-model="filters['global'].value" 
                            @input="searchGeneralLedger"
                            placeholder="Search keywords..." class="w-60" />
                    </div>
                </template>

                <Column selectionMode="multiple" :style="{ width: '50px' }" />
                <Column
                    v-for="col in columns"
                    :key="col.field"
                    :field="col.field"
                    :header="col.header"
                    :sortable="true"
                    :filters="filters"
                    :filterPlaceholder="'Search ' + col.header"
                    :filterMatchMode="'contains'"
                    :filterValue="filters[col.field]?.value"
                    :style="{ width: '200px' }"
                />
            </DataTable>
        </div>
    </main>
</template>

<script>
import axios from 'axios'
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
            generalLedgers: [],
            totalRecords: 0,
            isLoading: false,
            selectedGeneralLedgers: [],
            filters: {
                global: { value: null, matchMode: 'contains' },
            },
            columns: [
                { field: 'transaction_date', header: 'Tanggal' },
                { field: 'account_code', header: 'Kode Akun' },
                { field: 'account_name', header: 'Nama Akun' },
                { field: 'reference_no', header: 'Referensi' },
                { field: 'description', header: 'Keterangan' },
                { field: 'debit_transaction', header: 'Debit' },
                { field: 'credit_transaction', header: 'Kredit' },
            ],
            sortField: 'transaction_date',
            sortOrder: 1,
            newGeneralLedger: {},
        }
    },
    methods: {
        async fetchGeneralLedger({ first, rows, sortField, sortOrder, search }) {
            this.isLoading = true

            axios.get(`${import.meta.env.VITE_API_URL}/general-ledger`, {
                params: {
                    page: Math.floor(first / rows) + 1,
                    limit: rows,
                    search: search || '',
                    sort: sortField || 'transaction_date',
                    order: sortOrder === 1 ? 'asc' : 'desc'
                },
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { generalLedgers, total } = response.data
                this.generalLedgers = generalLedgers.map(entry => {
                        entry.debit_transaction = entry.transaction_type === 1 ? entry.amount : 0
                        entry.credit_transaction = entry.transaction_type === 2 ? entry.amount : 0
                        
                        
                        entry.debit_transaction = this.formatCurrency(entry.debit_transaction)
                        entry.credit_transaction = this.formatCurrency(entry.credit_transaction)

                        return entry
                    })

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
        onSortChange(event) {
            this.sortField = event.sortField,
            this.sortOrder = event.sortOrder

            this.fetchGeneralLedger({
                first: 0,
                rows: 10,
                sortField: event.sortField,
                sortOrder: event.sortOrder
            })
        },
        searchGeneralLedger() {
            this.fetchGeneralLedger({
                first: 0, 
                rows: 10, 
                sortField: this.sortField,
                sortOrder: this.sortOrder,
                search: this.filters['global'].value
            });
        },
        // async printToPDF() {
        //     this.showLoader()

        //     axios.get(`${import.meta.env.VITE_API_URL}/account/pdf`, {
        //         headers: {
        //             Authorization: `Bearer ${localStorage.getItem('accessToken')}`
        //         },
        //         responseType: 'blob'
        //     })
        //     .then(response => {
        //         this.hideLoader()
                
        //         const blob = new Blob([response.data], { type: 'application/pdf' })
        //         const url = window.URL.createObjectURL(blob)
                
        //         // new tab (streaming)
        //         window.open(url, '_blank')
        //     })
        //     .catch(error => {
        //         console.error(error)
        //         this.hideLoader()

        //         this.$toast.add({
        //             severity: 'error',
        //             summary: 'Print Data Gagal',
        //             detail: error.response ? error.response.data.message : 'An error occurred during prints.',
        //             life: 3000
        //         })

        //         this.$nextTick(() => {
        //             document.activeElement.blur()
        //         })
        //     })
        // },
        // async exportToExcel() {
        //     this.showLoader()

        //     axios.get(`${import.meta.env.VITE_API_URL}/account/excel`, {
        //         headers: {
        //             Authorization: `Bearer ${localStorage.getItem('accessToken')}`
        //         },
        //         responseType: 'blob'
        //     })
        //     .then(response => {
        //         this.hideLoader()
        //         const url = window.URL.createObjectURL(new Blob([response.data]))
        //         const link = document.createElement('a')

        //         link.href = url
        //         link.setAttribute('download', 'Master Data Akun.xlsx')
        //         document.body.appendChild(link)
        //         link.click()

        //         document.body.removeChild(link)
        //     })
        //     .catch(error => {
        //         console.error(error)
        //         this.hideLoader()

        //         this.$toast.add({
        //             severity: 'error',
        //             summary: 'Export Data Gagal',
        //             detail: error.response ? error.response.data.message : 'An error occurred during export.',
        //             life: 3000
        //         })

        //         this.$nextTick(() => {
        //             document.activeElement.blur()
        //         })
        //     })
        // },
        formatCurrency(value) {
            if (value === 0 || value === null) return '-';
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(value);
        }
    },
    mounted() {
        this.fetchGeneralLedger({ 
            first: 0, 
            rows: 10, 
            sortField: 'transaction_date',
            sortOrder: 1
        })
    }
}
</script>