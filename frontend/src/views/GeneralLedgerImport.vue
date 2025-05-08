<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <!-- success or error message -->
            <Message 
                :icon="responseMessage.severity === 'success' ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                closable
                v-if="responseMessage" 
                :severity="responseMessage.severity" 
                class="mt-4"
            >
                {{ responseMessage.detail }}
            </Message>
            <!-- file upload and preview -->
            <div class="pt-4 flex flex-col gap-6">
                <FileUpload
                    ref="fileUpload"
                    mode="basic" 
                    accept=".csv, .xlsx, .xls" 
                    :maxFileSize="100000" 
                    @select="onFileSelect"
                />
            </div>

            <!-- preview -->
            <template v-if="previewData.length > 0">
                <div class="p-2 border border-gray-300 rounded mt-3">
                    <h3 class="italic font-semibold text-center">Klik tombol Import untuk konfirmasi</h3>
                    <DataTable 
                        :value="previewData" 
                        paginator 
                        :rows="rowsPerPage"
                    >
                        <Column field="tanggal" header="Tanggal" />
                        <Column field="nomor_akun" header="Nomor Akun" />
                        <Column field="nama_akun" header="Nama Akun" />
                        <Column field="department" header="Department" />
                        <Column field="nomor_sumber" header="Nomor Sumber" />
                        <Column field="tipe_sumber" header="Tipe Sumber" />
                        <Column header="Nominal">
                            <template #body="slotProps">
                                <span v-if="slotProps.data.tipe_transaksi === 1">Debit</span>
                                <span v-else>Kredit</span>
                            </template>
                        </Column>
                        <Column field="keterangan" header="Keterangan" />
                        <Column header="Nominal">
                            <template #body="slotProps">
                                {{ formatCurrency(slotProps.data.nominal) }}
                            </template>
                        </Column>
                        <ColumnGroup type="footer">
                            <Row>
                                <Column footer="Total Transaksi:" :colspan="6" footerStyle="text-align:right" />
                                <Column :footer="formatCurrency(totalNominal)" />
                            </Row>
                        </ColumnGroup>
                    </DataTable>
                </div>
            </template>

            <!-- action buttons (cancel & import) -->
            <div class="flex justify-end mt-4">
                <Button 
                    label="Batal" 
                    icon="pi pi-times" 
                    text 
                    @click="onCancel" />
                <Button 
                    label="Import" 
                    severity="primary" 
                    icon="pi pi-check" 
                    @click="saveGeneralLedger" />
            </div>
        </div>
    </main>
</template>

<script>
import axios from 'axios'
import Breadcrumb from '../components/Breadcrumb.vue'
import Column from 'primevue/column'
import Button from 'primevue/button'
import ButtonGroup from 'primevue/buttongroup'
import DataTable from 'primevue/datatable'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import FileUpload from 'primevue/fileupload'
import Message from 'primevue/message'

export default {
    name: 'GeneralLedgerImport',
    components: {
        Breadcrumb,
        FileUpload,
        DataTable,
        Column,
        ColumnGroup,
        Row,
        Breadcrumb,
        Button,
        ButtonGroup,
        Message,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            previewData: [],
            rowsPerPage: 20,
            date: null,
            value: '',
            responseMessage: null,
        }
    },
    methods: {
        onCancel() {
            this.previewData = []
            this.$nextTick(() => {
                document.activeElement.blur()
            })
        },
        async onFileSelect(event) {
            const file = event.files[0]
            if (file) {
                const formData = new FormData()
                formData.append('file_general_ledger', file)

                this.showLoader()

                this.$api.post('/general-ledger/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
                    }
                })
                .then(response => {
                    if (response.data.status) {
                        this.previewData = response.data.preview
                        this.responseMessage = {
                            severity: 'success',
                            detail: 'Import Data Berhasil, Preview Ditampilkan.',
                        }
                        this.$toast.add({
                            severity: 'success',
                            summary: 'Import Data Berhasil',
                            detail: response.data.message,
                            life: 3000
                        })
                    }
                })
                .catch(error => {
                    this.hideLoader()

                    if (this.$refs.fileUpload) {
                        this.$refs.fileUpload.clear()
                    }
                    this.responseMessage = {
                        severity: 'error',
                        detail: error.response ? error.response.data.message : 'An error occurred during import',
                    }
                    this.$toast.add({
                        severity: 'error',
                        summary: 'Import Data Gagal',
                        detail: error.response ? error.response.data.message : 'An error occurred during import',
                        life: 3000
                    })
                })
                .finally(() => {
                    this.hideLoader()
                })
            }
        },
        async saveGeneralLedger() {
            this.showLoader()
            if (this.previewData.length > 0) {
                const data = {
                    generalLedger: this.previewData
                }

                this.$api.post(`${import.meta.env.VITE_API_URL}/general-ledger`, data, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`,
                    }
                })
                .then(response => {
                    if (response.data.status) {
                        if (this.$refs.fileUpload) {
                            this.$refs.fileUpload.clear()
                        }
                        this.responseMessage = {
                            severity: 'success',
                            detail: response.data.message,
                        }
                        this.$toast.add({
                            severity: 'success',
                            summary: 'Save Data Berhasil',
                            detail: response.data.message,
                            life: 3000
                        })

                        this.previewData = []
                        this.$emit('fetchGeneralLedgers')
                    }
                })
                .catch(error => {
                    console.error(error)

                    if (error.response && error.response.status === 422 && error.response.data.errors) {
                        const validationErrors = error.response.data.errors
                        const errorMessages = Object.values(validationErrors).flat().join(', ')


                        this.responseMessage = {
                           severity: 'error',
                           detail: errorMessages || 'Beberapa data gagal divalidasi.',
                       }
    
                       this.$toast.add({
                           severity: 'error',
                           summary: 'Save Data Gagal',
                           detail: errorMessages || 'Beberapa data gagal divalidasi.',
                           life: 3000
                       })
                    } else {
                        this.responseMessage = {
                            severity: 'error',
                            detail: error.response ? error.response.data.message : 'Terjadi kesalahan saat menyimpan data.',
                        }
                        this.$toast.add({
                            severity: 'error',
                            summary: 'Save Data Gagal',
                            detail: error.response ? error.response.data.message : 'Terjadi kesalahan saat menyimpan data.',
                            life: 3000
                        })
                    }

                })
                .finally(() => {
                    this.hideLoader()
                })
            }
        },
        formatCurrency(rowData) {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(rowData)
        },
    },
    computed: {
        totalNominal() {
            return this.previewData.reduce((total, item) => {
                const nominal = parseFloat(item.nominal)
                return isNaN(nominal) ? total : total + nominal
            }, 0)
        }
    }
}
</script>