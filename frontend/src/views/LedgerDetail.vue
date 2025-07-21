<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterLedgerDetailDialog = true" 
                    label="Filter" 
                    icon="pi pi-filter" 
                />
                <Button 
                    label="Print" 
                    icon="pi pi-print" 
                />
           </div>
            
           <!-- table ledger -->
           <table class="w-full text-sm border-collapse border border-gray-300 mt-5">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="border border-gray-300 p-2">Tanggal</th>
                        <th class="border border-gray-300 p-2">Tipe Sumber</th>
                        <th class="border border-gray-300 p-2">Nomor Sumber</th>
                        <th class="border border-gray-300 p-2">Keterangan</th>
                        <th class="border border-gray-300 p-2 text-right">Debit</th>
                        <th class="border border-gray-300 p-2 text-right">Kredit</th>
                        <th class="border border-gray-300 p-2 text-right">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(account, aIndex) in filteredData" :key="aIndex">
                        <!-- header account -->
                        <tr class="font-semibold">
                        <td colspan="7" class="border border-gray-300 p-2">
                            {{ account.account_code }} - {{ account.account_name }}
                        </td>
                        </tr>

                        <!-- opening balance -->
                        <tr v-if="account.ledger.some(l => l.description === 'Saldo Awal')" class="font-semibold">
                        <td colspan="5" class="border border-gray-300 p-2 text-right">Saldo Awal</td>
                        <td class="border border-gray-300 p-2 text-right font-semibold">
                            {{ formatCurrency(getOpeningBalance(account.ledger)) }}
                        </td>
                        </tr>

                        <!-- transactions -->
                        <tr
                        v-for="(item, index) in computeBalance(account.ledger)"
                        :key="index"
                        class="border-b"
                        >
                        <td class="border border-gray-300 p-2">{{ formatDate(item.transaction_date) }}</td>
                        <td class="border border-gray-300 p-2">{{ item.reference }}</td>
                        <td class="border border-gray-300 p-2">{{ item.reference_no }}</td>
                        <td class="border border-gray-300 p-2">{{ item.description }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(item.debit) }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(item.credit) }}</td>
                        <td class="border border-gray-300 p-2 text-right font-semibold">{{ formatCurrency(item.balance) }}</td>
                        </tr>

                        <!-- total per account -->
                        <tr class="bg-gray-200 font-semibold">
                        <td colspan="4" class="border border-gray-300 p-2 text-right">Total</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(getTotalDebit(account.ledger)) }}</td>
                        <td class="border border-gray-300 p-2 text-right">{{ formatCurrency(getTotalCredit(account.ledger)) }}</td>
                        <td class="border border-gray-300 p-2"></td>
                        </tr>
                    </template>
                </tbody>

                <tfoot>
                    <tr class="bg-gray-300 font-bold text-right">
                        <td colspan="4" class="border border-gray-300 p-2 text-right">Grand Total</td>
                        <td class="border border-gray-300 p-2 text-right">
                            {{ formatCurrency(totals.debit) }}
                        </td>
                        <td class="border border-gray-300 p-2 text-right">
                            {{ formatCurrency(totals.credit) }}
                        </td>
                        <td class="border border-gray-300 p-2"></td>
                    </tr>
                </tfoot>
            </table>
           
        </div>

         <!-- modal filter -->
         <ModalLedgerDetail
            :isVisible="filterLedgerDetailDialog" 
            @update:isVisible="filterLedgerDetailDialog = $event"
            @fetchLedgerDetails="fetchLedgerDetail" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalLedgerDetail from '../components/modal/ModalLedgerDetail.vue'
import BalanceSheetRow from '../components/others/BalanceSheetRow.vue'

export default {
    name: 'LedgerDetail',
    components: {
        Breadcrumb,
        Button,
        ModalLedgerDetail,
        BalanceSheetRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterLedgerDetailDialog: false,
            rawData: [],
            filteredData: null,
            totals: {
                debit: 0,
                credit: 0,
            },
            openingBalance: null,
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
            return isNaN(date) ? '-' : date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' })
        },
        getOpeningBalance(ledger) {
            const opening = ledger.find(item => item.description === 'Saldo Awal' && item.id === null)
            return opening ? parseFloat(opening.balance) || 0 : 0
        },
        computeBalance(ledger) {
            let balance = this.getOpeningBalance(ledger)
            return ledger.map(item => {
            let debit = parseFloat(item.debit) || 0
            let credit = parseFloat(item.credit) || 0
            if (!(item.description === 'Saldo Awal' && item.id === null)) {
                balance += credit - debit
            }
            return { ...item, debit, credit, balance }
            })
        },  
        getTotalDebit(ledger) {
            return ledger.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0)
        },
        getTotalCredit(ledger) {
            return ledger.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0)
        },
        fetchLedgerDetail(ledgers, totals) {
            this.filteredData = ledgers
            this.totals = totals

            const firstLedgerWithOpening = ledgers.find(account => account.ledger && account.ledger.some(item => item.description === 'Saldo Awal' && item.id === null))
            if (firstLedgerWithOpening) {
                const openingItem = firstLedgerWithOpening.ledger.find(item => item.description === 'Saldo Awal' && item.id === null)
                this.openingBalance = openingItem ? parseFloat(openingItem.balance) : 0
            } else {
                this.openingBalance = 0
            }
        }
    },
}
</script>