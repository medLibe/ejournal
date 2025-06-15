<template>
    <DataTable 
        :editingRows="editingRows"
        @update:editingRows="newVal => $emit('update:editingRows', newVal)"
        :value="generalLedgers" 
        editMode="row" 
        dataKey="id" 
        @row-edit-save="editRow"
        class="p-datatable-sm"
    >
        <!-- Tombol Hapus -->
        <Column style="width: 60px">
            <template #body="{ data }">
                <Button 
                    icon="pi pi-trash" 
                    @click="$emit('delete-row', data.id)"
                    variant="text"
                    severity="secondary"
                />
            </template>
        </Column>

        <!-- Akun -->
        <Column field="account_name" header="Akun">
            <template #body="{ data }">
                <div @dblclick="activateCellEdit(data.id, 'account')">
                <template v-if="editingCell.rowId === data.id && editingCell.field === 'account'">
                    <Select
                        filter
                        filterMatchMode="contains"
                        v-model="data.account_id"
                        :options="filteredAccounts"
                        optionLabel="combined_label"
                        optionValue="id"
                        placeholder="Pilih akun"
                        class="w-full"
                        @update:modelValue="(val) => { onAccountSelected(val, data); deactivateCellEdit(); }"
                    />
                </template>
                <template v-else>
                    {{ data.account_code }} - {{ data.account_name }}
                </template>
                </div>
            </template>
        </Column>

        <!-- Debit -->
        <Column header="Debit">
            <template #body="{ data }">
                <div @dblclick="activateCellEdit(data.id, 'debit')">
                <template v-if="editingCell.rowId === data.id && editingCell.field === 'debit'">
                    <InputText
                        v-model="data.amount"
                        class="w-full"
                        placeholder="Debit"
                        @blur="() => handleBlurCell(data, 'debit')"
                        @keydown.enter="() => handleBlurCell(data, 'debit')"
                    />
                </template>
                <template v-else>
                    <span v-if="data.transaction_type === 1">
                    Rp {{ Number(data.amount).toLocaleString('id-ID') }}
                    </span>
                    <span v-else>-</span>
                </template>
                </div>
            </template>
        </Column>

        <!-- Kredit -->
        <Column header="Kredit">
            <template #body="{ data }">
                <div @dblclick="activateCellEdit(data.id, 'kredit')">
                <template v-if="editingCell.rowId === data.id && editingCell.field === 'kredit'">
                    <InputText
                        v-model="data.amount"
                        class="w-full"
                        placeholder="Kredit"
                        @blur="() => handleBlurCell(data, 'kredit')"
                        @keydown.enter="() => handleBlurCell(data, 'kredit')"
                    />
                </template>
                <template v-else>
                    <span v-if="data.transaction_type === 2">
                    Rp {{ Number(data.amount).toLocaleString('id-ID') }}
                    </span>
                    <span v-else>-</span>
                </template>
                </div>
            </template>
        </Column>

        <!-- Tombol Simpan/Batal -->
        <!-- <Column :rowEditor="true" style="width: 60px" /> -->

        <template #empty>
            <div class="text-center py-2 text-gray-500">
                Belum ada data
            </div>
        </template>
    </DataTable>
</template>

<script>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'

export default {
    name: 'GeneralLedgerAdjustmentRow',
    components: {
        DataTable,
        Column,
        InputText,
        Select,
        Button
    },
    props: {
        generalLedgers: Array,
        editingRows: Array,
        filteredAccounts: Array,
        voucherInfo: Object,
        totalDebit: Number,
        totalCredit: Number,
    },
    emits: ['update:editingRows', 'update:generalLedgers', 'add-new-row', 'update-row', 'delete-row'],
    data() {
        return {
            formData: [],
            editingCell: { rowId: null, field: null }
        }
    },
    methods: {
        activateCellEdit(rowId, field) {
            const row = this.generalLedgers.find(r => r.id == rowId)
              if (row) {
                if (field === 'debit' && row.transaction_type !== 1) {
                    row.amount = 0
                    row.transaction_type = 1
                }
                if (field === 'kredit' && row.transaction_type !== 2) {
                    row.amount = 0
                    row.transaction_type = 2
                }
            }

            this.editingCell = { rowId, field }
        },
        deactivateCellEdit() {
            this.editingCell = { rowId: null, field: null }
        },
        handleBlurCell(row, field) {
            let amount = Number(row.amount) || 0

            // if field debit & amount > 0, set to debit
            if (field === 'debit') {
                if (amount > 0) {
                    row.transaction_type = 1
                } else {
                    row.amount = 0
                }
            }

            // if field kredit & amount > 0, set to kredit
            if (field === 'kredit') {
                if (amount > 0) {
                    row.transaction_type = 2
                } else {
                    row.amount = 0
                }
            }

            // close edit cell
            this.deactivateCellEdit()
        },
        handleDebitCreditInput(val, row, type) {
            const parsedVal = parseFloat(val)
            
            // change transaction type if not clear
            if (row.transaction_type !== type && Number(row.amount) !== 0) {
                this.$toast.add({
                    severity: 'warn',
                    summary: 'Perubahan Tidak Diizinkan',
                    detail: 'Kosongkan nilai terlebih dahulu sebelum memindahkan dari Debit ke Kredit atau sebaliknya.',
                    life: 3000
                })
                return
            }

            row.transaction_type = type
            row.amount = isNaN(parsedVal) ? 0 : parsedVal
        },
        editRow(event) {
            const { newData } = event

            // patch acocunt name & account code based on account id
            const matchedAccount = this.filteredAccounts.find(acc => acc.id === newData.account_id)

            if (matchedAccount) {
                newData.account_code = matchedAccount.account_code
                newData.account_name = matchedAccount.account_name
            }

            this.$emit('update-row', newData)

            // balance check
            const isBalanced = this.checkBalance()

            this.$toast.add({
                severity: isBalanced ? 'success' : 'warn',
                summary: isBalanced ? 'Berhasil' : 'Saldo Belum Balance',
                detail: isBalanced 
                    ? 'Data berhasil diperbarui secara lokal.' 
                    : 'Saldo belum balance. Pastikan balance sebelum disimpan.',
                life: 3000
            })
        },
        deleteRow(id) {
            this.generalLedgers = this.generalLedgers.filter(row => row.id !== id)
            this.formData = this.formData.filter(row => row.id !== id)

            // also delete from editingRows if is editing
            this.$emit('update:editingRows', this.editingRows.filter(editId => editId !== id))

            this.$toast.add({
                severity: 'info',
                summary: 'Baris Dihapus',
                detail: 'Baris berhasil dihapus secara lokal.',
                life: 2000
            })
        },
        checkBalance() {
            const debit = this.generalLedgers
                .filter(row => row.transaction_type === 1)
                .reduce((sum, row) => sum + Number(row.amount || 0), 0)

            const credit = this.generalLedgers
                .filter(row => row.transaction_type === 2)
                .reduce((sum, row) => sum + Number(row.amount || 0), 0)

            return debit > 0 && credit > 0 && debit === credit
        },
        onAccountSelected(accountId, rowData) {
            const selected = this.filteredAccounts.find(acc => acc.id === accountId)

            if (selected) {
                rowData.account_id = selected.id
                rowData.account_code = selected.account_code
                rowData.account_name = selected.account_name
            }
        },
    },
}
</script>