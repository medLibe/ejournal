<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterLedgerDialog = true" 
                    label="Filter" 
                    icon="pi pi-filter" 
                />
                <Button 
                    label="Print" 
                    icon="pi pi-print" 
                    @click="handlePrint"
                />
           </div>
            
           <!-- table ledger -->
           <table class="w-full text-sm border-collapse border border-gray-300 mt-5">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="border border-gray-300 p-2">Tanggal</th>
                        <th class="border border-gray-300 p-2">No. Akun</th>
                        <th class="border border-gray-300 p-2">Nama Akun</th>
                        <th class="border border-gray-300 p-2">Keterangan</th>
                        <th class="border border-gray-300 p-2">Ref</th>
                        <th class="border border-gray-300 p-2 text-right">Debit</th>
                        <th class="border border-gray-300 p-2 text-right">Kredit</th>
                        <th class="border border-gray-300 p-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in formattedData" :key="item.id" class="border-b">
                        <td class="border border-gray-300 p-2">{{ formatDate(item.transaction_date) }}</td>
                        <td class="border border-gray-300 p-2">{{ item.account_code }}</td>
                        <td class="border border-gray-300 p-2">{{ item.account_name }}</td>
                        <td class="border border-gray-300 p-2">{{ item.description }}</td>
                        <td class="border border-gray-300 p-2">{{ item.reference_no }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(item.debit) }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(item.credit) }}</td>
                        <td class="border border-gray-300 p-2 text-right font-semibold">{{ formatCurrency(item.balance) }}</td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr class="bg-gray-200 font-semibold">
                        <td colspan="5" class="border border-gray-300 p-2 text-right">Total</td>
                        <td class="border border-gray-300 p-2 text-right">
                            {{ formatCurrency(grandTotalDebit) }}
                        </td>
                        <td class="border border-gray-300 p-2 text-right">
                            {{ formatCurrency(grandTotalCredit) }}
                        </td>
                        <td class="border border-gray-300 p-2"></td>
                    </tr>
                </tfoot>
            </table>
           
        </div>

         <!-- modal filter -->
         <ModalLedger
            :isVisible="filterLedgerDialog" 
            @update:isVisible="filterLedgerDialog = $event"
            @fetchLedgers="fetchLedger"
            @sendFormData="saveFormData" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalLedger from '../components/modal/ModalLedger.vue'
import BalanceSheetRow from '../components/others/BalanceSheetRow.vue'

export default {
    name: 'Ledger',
    components: {
        Breadcrumb,
        Button,
        ModalLedger,
        BalanceSheetRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterLedgerDialog: false,
            rawData: [],
            filteredData: null,
            openingBalance: null,
            grandTotalDebit: 0,
            grandTotalCredit: 0,
            formData: {}
        }
    },
    computed: {
        formattedData() {
            let balance = this.openingBalance || 0

            const data = this.filteredData || this.rawData

            return data.map(item => {
                let debit = 0
                let credit = 0

                if (item.description === 'Saldo Awal' && item.id === null) {
                    balance = parseFloat(item.balance) || 0
                } else {
                    debit = parseFloat(item.debit) || 0
                    credit = parseFloat(item.credit) || 0
                    balance += credit - debit
                }
                
                return { 
                    ...item,
                    debit,
                    credit,
                    balance 
                }
            })
        },
    },
    methods: {
        saveFormData(data) {
            this.formData = data
        },
        formatCurrency(value) {
            if (value == null || value === undefined || isNaN(value)) return '-'
            const absFormatted =  new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(Math.abs(value))

            return value < 0 ? `(${absFormatted})` : absFormatted
        },
        formatDate(dateStr) {
            const date = new Date(dateStr)
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' })
        },
        fetchLedger(ledgers, totals) {
            this.filteredData = ledgers

            const opening = ledgers.find(item => item.description === 'Saldo Awal' && item.id === null)
            this.openingBalance = opening ? parseFloat(opening.balance) || 0 : 0

            this.grandTotalDebit = totals.debit
            this.grandTotalCredit = totals.credit
        },
        async handlePrint() {
            try {
                this.showLoader()

                const formatDate = (date) => {
                    const localDate = new Date(date)
                    return new Date(localDate.getTime() - localDate.getTimezoneOffset() * 60000)
                        .toISOString().split("T")[0]
                }

                const params = {
                    accountId: this.formData.selectedAccount?.id || null,
                    startDate: formatDate(this.formData?.startDate),
                    endDate: formatDate(this.formData?.endDate),
                    division: this.formData?.division || null
                }

                const response = await this.$api.get(`${import.meta.env.VITE_API_URL}/print/ledger`, {
                    params,
                    responseType: 'blob',
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                    }
                })

                const blob = new Blob([response.data], { type: 'application/pdf' })
                const url = window.URL.createObjectURL(blob)
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('stream', 'Ledger Report.pdf')
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)

            } catch (error) {
                console.error('Print error:', error)
                this.$toast.add({
                    severity: 'error',
                    summary: 'Print Gagal',
                    detail: error.response?.data?.message || 'Terjadi kesalahan saat mencetak.',
                    life: 3000
                })
            } finally {
                this.hideLoader()
            }
        }
    },
}
</script>