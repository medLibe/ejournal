<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data company -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <!-- create modal -->
                <div class="grid grid-cols-2 gap-2">
                    <Button 
                        severity="primary"
                        icon="pi pi-plus" 
                        label="Tambah" 
                        @click="createCompanyDialog = true"
                    />
                </div>

                <!-- search -->
                <input
                    v-model="searchQuery" 
                    placeholder="Cari Perusahaan..." 
                    type="text"
                    class="w-60 px-4 py-2 border rounded-md">
            </div>
            
            <DataTable 
                :value="companies" 
                :paginator="true"
                :lazy="true"
                :rows="pagination.perPage"
                :first="(pagination.page - 1) * pagination.perPage"
                :totalRecords="totalRecords"
                @page="onPageChange"
                :loading="isLoading">
                <Column field="company_code" header="Kode Perusahaan" />
                <Column field="company_name" header="Nama Perusahaan" />
                <Column 
                    field="company_type" 
                    header="Tipe">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.company_type === 1">Supplier</span>
                        <span v-else-if="slotProps.data.company_type === 2">Customer</span>
                        <span v-else>N/A</span>
                    </template>
                </Column>
                <Column
                    header="Aksi">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-trash" 
                            severity="danger" 
                            text
                            @click="openDeleteModal(slotProps.data)" 
                        />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-gray-500">
                        Entri data tidak ditemukan
                    </div>
                </template>
            </DataTable>

            <!-- modal filter -->
            <ModalCreateCompany
                :isVisible="createCompanyDialog"
                @update:isVisible="createCompanyDialog = $event"
                @createCompany="fetchCompany"
            />

            <ModalDeleteCompany
                :isVisible="deleteCompanyDialog"
                :company="selectedCompany"
                @update:isVisible="deleteCompanyDialog = $event"
                @deleteSuccess="fetchCompany"
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
import ModalCreateCompany from '../components/modal/ModalCreateCompany.vue'
import ModalDeleteCompany from '../components/modal/ModalDeleteCompany.vue'

export default {
    name: 'Company',
    components: {
        Breadcrumb,
        DataTable,
        Row,
        Column,
        ColumnGroup,
        Button,
        ModalCreateCompany,
        ModalDeleteCompany,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            importNo: this.$route.params.importNo,
            companies: [],
            totalRecords: 0,
            totalAmount: 0,
            isLoading: false,
            createCompanyDialog: false,
            deleteCompanyDialog: false,
            selectedCompany: null,
            searchQuery: '',
            pagination: {
                page: 1,
                perPage: 50
            },
            formData: {
                companyCode: null,
                companyName: null,
                companyType: null,
            },
        }
    },
    mounted() {
        this.fetchCompany()
    },
    methods: {
        onPageChange(event) {
            this.pagination.page = event.page + 1 // PrimeVue 0-based page
            this.fetchCompany()
        },
        async fetchCompany() {
            this.isLoading = true

            const params = {
                page: this.pagination.page,
                perPage: this.pagination.perPage,
                search: this.searchQuery
            }

            await this.$api.get(`${import.meta.env.VITE_API_URL}/company`, {
                params,
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { companies, total } = response.data

                this.companies = companies
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
        openDeleteModal(company) {
            this.selectedCompany = company
            this.deleteCompanyDialog = true
        }
    },
    watch: {
        searchQuery: {
            handler() {
                this.pagination.page = 1
                this.fetchCompany()
            },
            immediate: false
        }
    }
}
</script>