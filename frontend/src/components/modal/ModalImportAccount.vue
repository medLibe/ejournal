<template>
    <Dialog 
        v-bind:visible="isVisible"
        header="Import Akun" 
        :modal="true"
        :closable="false"
        class="max-w-4xl w-full"
    >
            <Message 
                :icon="responseMessage.severity === 'success' ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                closable
                v-if="responseMessage" 
                :severity="responseMessage.severity" 
                class="mt-4"
            >
                {{ responseMessage.detail }}
            </Message>
        <div class="pt-4 flex flex-col gap-6 justify-center items-center">
            <FileUpload
                ref="fileUpload"
                mode="basic" 
                accept=".csv, .xlsx, .xls" 
                :maxFileSize="100000" 
                @select="onFileSelect"
            />
        </div>

        <!-- preview data -->
        <template 
            v-if="previewData.length > 0">
            <div class="p-2 border border-gray-300 rounded mt-3">
                <h3 class="italic font-semibold text-center">Klik tombol Import untuk konfirmasi</h3>
                <DataTable 
                    :value="previewData" 
                    paginator 
                    :rows="rowsPerPage"
                    :rowClass="rowClass"
                >
                    <Column field="tipe_akun" header="Tipe Akun" />
                    <Column field="kode_akun" header="Kode Akun" />
                    <Column field="nama_akun" header="Nama Akun" />
                    <Column field="sub_akun_dari" header="Sub Akun Dari">
                        <template #body="slotProps">
                            <span v-if="slotProps.data.sub_akun_dari">
                                {{ slotProps.data.sub_akun_dari }}
                            </span>
                            <span v-else>-</span>
                        </template>
                    </Column>
                    <Column header="Saldo Awal">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.saldo_awal) }}
                            <!-- <span v-if="slotProps.data.sub_akun_dari">
                            </span> -->
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>

        <template #footer>
            <Button 
                label="Batal" 
                icon="pi pi-times" 
                text 
                @click="onCancel" />
            <Button 
                label="Import" 
                severity="primary" 
                icon="pi pi-check" 
                @click="saveAccount" />
        </template>
    </Dialog>
</template>

<script>
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Message from 'primevue/message'

export default {
    name: 'ModalImportAccount',
    components: {
        Dialog,
        Button,
        FileUpload,
        DataTable,
        Column,
        Message
    },
    props: {
        isVisible: {
            type: Boolean,
            required: true
        },
    },
    inject: ['showLoader' , 'hideLoader'],
    emits: ['fetchAccounts', 'update:isVisible'],
    data() {
        return {
            previewData: [],
            backendData: [],
            rowsPerPage: 20,
            responseMessage: null,
        }
    },
    methods: {
        rowClass(rowData) {
            if (!rowData.sub_akun_dari) {
                // parent account
                return 'font-bold';
            }

            const isParent = this.previewData.some((data) => data.sub_akun_dari === rowData.kode_akun);
            if (isParent && rowData.sub_akun_dari) {
                // sub sub account
                return 'font-bold';
            }
        },
        onCancel() {
            this.$emit('update:isVisible', false)
            this.previewData = []
        },
        // recursive to count balance of parent account based on children
        calculateBalance(accounts) {
            const accountMap = new Map()

            // create map account based on account code
            accounts.forEach(account => {
                account.children = []
                accountMap.set(account.kode_akun, account)
            })

            accounts.forEach(account => {
                if (account.sub_akun_dari) {
                    const parentAccount = accountMap.get(account.sub_akun_dari)
                    if (parentAccount) {
                        parentAccount.children.push(account)
                    }
                }
            })

            function calculate(account) {
                if (account.saldo_awal !== null && account.saldo_awal !== undefined) {
                    return account.saldo_awal
                }

                // if there is no children return balance account or 0 if balance not available
                if (!account.children || account.children.length === 0) {
                    return 0
                }

                // total parent balance = children balance
                let totalBalance = 0
                account.children.forEach(child => {
                    totalBalance += calculate(child)
                });

                account.saldo_awal = totalBalance
                return totalBalance
            }

            accounts.forEach(account => {
                if (!account.sub_akun_dari) {
                    calculate(account)
                }
            })

            return accounts
        
        },
        // to handle file selection and preview it to modal
        async onFileSelect(event) {
            const file = event.files[0]
            this.showLoader()
            if (file) {
                const formData = new FormData()
                formData.append('file_account', file)
                

                this.$api.post(`${import.meta.env.VITE_API_URL}/account/import`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
                    }
                })
                .then(response => {
                    if (response.data.status) {
                        this.previewData = response.data.preview
                        this.backendData = response.data.raw

                        this.calculateBalance(this.previewData)

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

                        this.$nextTick(() => {
                            document.activeElement.blur()
                        })
                    }
                })
                .catch(error => {
                    console.error(error)
                    this.hideLoader()

                    // remove file input if import fails
                    if (this.$refs.fileUpload) {
                        this.$refs.fileUpload.clear()
                    }

                    this.responseMessage = {
                        severity: 'error',
                        detail: error.response ? error.response.data.message : 'An error occurred during import.',
                    }
                    this.$toast.add({
                        severity: 'error',
                        summary: 'Import Data Gagal',
                        detail: error.response ? error.response.data.message : 'An error occurred during import',
                        life: 3000
                    })

                    this.$nextTick(() => {
                        document.activeElement.blur()
                    })
                })
                .finally(() => {
                    this.hideLoader()
                })
            }
        },
        // send the preview to server
        async saveAccount() {
            this.showLoader()
            if (this.backendData.length > 0) {
                // in json data
                const data = {
                    account: this.backendData
                }

                this.$api.post(`${import.meta.env.VITE_API_URL}/account`, data, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`,
                    }
                })
                .then(response => {
                    if (response.data.status) {
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

                        // remove file input if import fails
                        if (this.$refs.fileUpload) {
                            this.$refs.fileUpload.clear()
                        }

                        this.previewData = []
                        this.$emit('fetchAccounts')
                        
                        this.$nextTick(() => {
                            document.activeElement.blur()
                        })
                    }
                })
                .catch(error => {
                    console.error(error)
                    this.responseMessage = {
                        severity: 'error',
                        detail: error.response ? error.response.data.message : 'An error occurred during import.',
                    }
                    this.$toast.add({
                        severity: 'error',
                        summary: 'Save Data Gagal',
                        detail: error.response ? error.response.data.message : 'An error occurred during import.',
                        life: 3000
                    })

                    this.$nextTick(() => {
                        document.activeElement.blur()
                    })
                })
                .finally(() => {
                    this.hideLoader()
                })
            }
        },
        // format currency
        formatCurrency(rowData) {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(rowData)
        }
    }
}
</script>