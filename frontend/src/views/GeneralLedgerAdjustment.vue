<template>
    <main class="mt-8">    
        <Breadcrumb/>

        <!-- data general ledger -->
        <div class="bg-white p-8 mt-5 rounded-lg shadow">

            <!-- <Message 
                v-if="responseMessage" 
                :severity="responseMessage.severity" 
                :icon="responseMessage.severity === 'success' ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                closable 
                @close="responseMessage = null"
                class="mt-3 mb-5"
            >
                <ul v-if="Array.isArray(responseMessage.detail)">
                    <li v-for="(msg, idx) in responseMessage.detail" :key="idx">{{ msg }}</li>
                </ul>
                <span v-else>{{ responseMessage.detail }}</span>
            </Message> -->

            <!-- voucher info -->
            <div class="flex flex-wrap gap-8 mb-8">
                <div class="w-full md:w-1/2">
                    <div class="flex flex-col gap-5">
                    <FloatLabel variant="on">
                        <DatePicker 
                        v-model="voucherInfo.transactionDate"
                        inputId="transactionDate"
                        showIcon
                        iconDisplay="input"
                        class="w-full" />
                        <label for="transactionDate">Tanggal Transaksi</label>
                    </FloatLabel>
                    <Message 
                        v-if="getFieldError('transaction_date')" severity="error" size="small" variant="simple">
                        {{ getFieldError('transaction_date') }}
                    </Message>

                    <FloatLabel variant="on">
                        <InputText 
                        v-model="voucherInfo.referenceNo" 
                        class="w-full" />
                        <label for="referenceNo">Nomor Sumber</label>
                    </FloatLabel>
                    <Message 
                        v-if="getFieldError('reference_no')" severity="error" size="small" variant="simple">
                        {{ getFieldError('reference_no') }}
                    </Message>

                    <FloatLabel variant="on">
                        <InputText 
                        v-model="voucherInfo.department" 
                        class="w-full" />
                        <label for="department">Departemen</label>
                    </FloatLabel>
                    <Message 
                        v-if="getFieldError('department')" severity="error" size="small" variant="simple">
                        {{ getFieldError('department') }}
                    </Message>
                    </div>
                </div>

                <div class="w-full md:w-1/3">
                    <FloatLabel variant="on">
                    <Textarea 
                        v-model="voucherInfo.description"
                        rows="6"
                        cols="10"
                        class="w-full" />
                    <label for="description">Deskripsi</label>
                    </FloatLabel>
                    <Message 
                        v-if="getFieldError('description')" severity="error" size="small" variant="simple">
                        {{ getFieldError('description') }}
                    </Message>
                </div>
            </div>

            <GeneralLedgerAdjustmentRow
                :generalLedgers="generalLedgers"
                :filteredAccounts="filteredAccounts"
                :voucherInfo="voucherInfo"
                :editingRows="editingRows"
                :totalDebit="totalDebit"
                :totalCredit="totalCredit"
                @add-new-row="handleAddNewRow"
                @update-row="handleUpdateRow"
                @delete-row="handleDeleteRow"
            />
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <Button
                label="Tambah"
                icon="pi pi-plus"
                severity="success"
                @click="handleAddNewRow"/>
            <Button
                label="Simpan"
                icon="pi pi-save"
                @click="handleSaveAll"/>
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Dialog from 'primevue/dialog'
import ButtonGroup from 'primevue/buttongroup'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Message from 'primevue/message'
import GeneralLedgerAdjustmentRow from '../components/others/GeneralLedgerAdjustmentRow.vue'
import DatePicker from 'primevue/datepicker'
import FloatLabel from 'primevue/floatlabel'

export default {
    name: 'GeneralLedgerAdjustment',
    components: {
        Breadcrumb,
        ButtonGroup,
        Dialog,
        InputText,
        Textarea,
        DatePicker,
        FloatLabel,
        Button,
        Message,
        GeneralLedgerAdjustmentRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            generalLedgers: [],
            editingRows: [],
            referenceNo: '',
            isLoading: false,
            responseMessage: null,
            fieldErrors: {},
            accounts: [],
            filteredAccounts: [],
            formData : [],
            voucherInfo: {
                referenceNo: '',
                transactionDate: '',
                description: '',
                department: ''
            },
            currentAmount: 0,
        }
    },
    methods: {
        async fetchAccounts() {
            this.showLoader()
            try {
                const response = await this.$api.get(`${import.meta.env.VITE_API_URL}/report/accounts`, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                    }
                })
                this.accounts = response.data.data

                this.filteredAccounts = this.filterSelectableAccounts(this.accounts).map(acc => ({
                    ...acc,
                    combined_label: acc.account_code 
                    ? `${acc.account_code} - ${acc.account_name}` 
                    : acc.account_name
                }))
            } catch (error) {
                console.error(error)
            } finally {
                this.hideLoader()
            }
        },
        filterSelectableAccounts(accounts) {
            return accounts.filter(account => {
                if (!account.children || account.children.length === 0) {
                    return true
                }
                return account.children.every(child => !child.children)
            })
        },
        async fetchGeneralLedgerByReferenceNo() {
            const referenceNo = decodeURIComponent(this.$route.params.referenceNo)
            this.referenceNo = referenceNo

            this.$api.get(`${import.meta.env.VITE_API_URL}/general-ledger/adjustment/?ref=${encodeURIComponent(referenceNo)}`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                if (response.data.status) {
                    if (response.data.generalLedgers.length > 0) {
                        const firstRow = response.data.generalLedgers[0]
                        this.voucherInfo = {
                            referenceNo: firstRow.reference_no,
                            transactionDate: firstRow.transaction_date,
                            description: firstRow.description,
                            department: firstRow.department,
                        }
                    }

                    this.generalLedgers = response.data.generalLedgers

                    this.formData = response.data.generalLedgers.map(item => ({
                        id: item.id,
                        periode: this.getPeriodeFromDate(this.voucherInfo.transactionDate),
                        department: item.department,
                        account: this.filteredAccounts.find(acc => acc.id === item.account_id) || null,
                        amount: item.amount,
                        transaction_date: this.voucherInfo.transactionDate,
                        reference_no: this.voucherInfo.referenceNo,
                        description: this.voucherInfo.description,
                    }))
                }
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
        handleAddNewRow() {
            const newId = Date.now()
            const newRow = {
                id: newId,
                account_id: null,
                account_code: '',
                account_name: '',
                amount: 0,
                transaction_type: null, // default to unset/null
                transaction_date: this.voucherInfo.transactionDate || '',
                department: this.voucherInfo.department || '',
                reference_no: this.voucherInfo.referenceNo || '',
                description: this.voucherInfo.description || ''
            }

            this.generalLedgers.push(newRow)
            this.$nextTick(() => {
                this.editingRows = [newId]
            })
        },
        handleUpdateRow(updateRow) {
            const index = this.generalLedgers.findIndex(row => row.id === updateRow.id)
            if (index !== -1) {
                const original = this.generalLedgers[index]

                // mix old data with new one
                this.generalLedgers.splice(index, 1, {
                    ...original,
                    ...updateRow,
                })
            }
        },
        handleDeleteRow(id) {
            this.generalLedgers = this.generalLedgers.filter(row => row.id !== id)
            this.editingRows = this.editingRows.filter(editId => editId !== id)

            this.$toast.add({
                severity: 'info',
                summary: 'Baris Dihapus',
                detail: 'Baris berhasil dihapus.',
                life: 2000
            })
        },
        handleSaveAll() {
            // validate empty row
            const hasEmptyRow = this.generalLedgers.some(row => {
                return (
                    !row.account_id ||
                    (!row.amount || row.amount === 0)
                )
            })

            if (hasEmptyRow) {
                this.$toast.add({
                    severity: 'error',
                    summary: 'Validasi Gagal',
                    detail: 'Terdapat baris atau jumlah yang kosong, mohon diisi atau dihapus.',
                    life: 3000
                })
                return
            }

            if (this.editingRows.length > 0) {
                this.$toast.add({
                    severity: 'warn',
                    summary: 'Baris Belum Disimpan',
                    detail: 'Ada baris yang belum disimpan, mohon diselesaikan terlebih dahulu.',
                    life: 3000
                })
                return
            }

            // validate balance amount
            if (this.totalDebit !== this.totalCredit) {
                this.$toast.add({
                    severity: 'error',
                    summary: 'Saldo Tidak Balance',
                    detail: 'Total debit dan kredit harus sama.',
                    life: 3000
                })
                return
            }

            // if success validation
            const periode = this.getPeriodeFromDate(this.voucherInfo.transactionDate)

            this.formData = this.generalLedgers.map(row => ({
                id: row.id,
                import_id: row.import_id,
                periode: periode,
                account_id: row.account_id,
                amount: Number(row.amount),
                reference: row.reference,
                transaction_type: Number(row.transaction_type),
                transaction_date: this.voucherInfo.transactionDate,
                reference_no: this.voucherInfo.referenceNo,
                department: this.voucherInfo.department,
                description: this.voucherInfo.description,
            }))

            this.$api.post(`${import.meta.env.VITE_API_URL}/general-ledger/adjustment`, {

                data: this.formData
            }, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                this.$toast.add({
                    severity: 'success',
                    summary: 'Sukses',
                    detail: response.data.message,
                    life: 3000
                })
            })
            .catch(error => {
                const res = error.response?.data;

                this.fieldErrors = res?.errors || {}

                this.$toast.add({
                    severity: 'error',
                    summary: 'Gagal',
                    detail: res?.message || 'Terjadi kesalahan.',
                    life: 3000
                })
            })
        },
        getPeriodeFromDate(dateStr) {
            const date = new Date(dateStr)
            const year = date.getFullYear()
            const month = String(date.getMonth() + 1).padStart(2, '0')
            return `${year}${month}`
        },
        getFieldError(field) {
            return this.fieldErrors?.[field]?.[0] || null
        }
    },
    mounted() {
        this.fetchAccounts()
        this.fetchGeneralLedgerByReferenceNo()
    },
    computed: {
        totalDebit() {
            return this.generalLedgers
                .filter(item => item.transaction_type === 1)
                .reduce((sum, item) => sum + Number(item.amount || 0), 0)
        },
        totalCredit() {
            return this.generalLedgers
                .filter(item => item.transaction_type === 2)
                .reduce((sum, item) => sum + Number(item.amount || 0), 0)
        }
    },
    watch: {
        generalLedgers: {
            handler(val) {
                val.forEach(row => {
                    if (Number(row.amount) === 0) {

                    }
                })
            },
            deep: true
        }
    }
}
</script>