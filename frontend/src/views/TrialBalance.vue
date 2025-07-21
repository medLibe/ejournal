<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterTrialBalanceDialog = true" 
                    label="Filter" 
                    icon="pi pi-filter" 
                />
                <Button 
                    label="Print" 
                    icon="pi pi-print" 
                />
           </div>
            
            <!-- table -->
            <table class="table-auto text-sm w-full border-collapse border border-gray-200 mt-5">
                <thead class="bg-gray-100">
                    <tr>
                    <th class="border border-gray-200 px-4 py-2 text-left">No. Akun</th>
                    <th class="border border-gray-200 px-4 py-2 text-left">Nama Akun</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Saldo Awal Debet</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Saldo Awal Kredit</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Perubahan Debet</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Perubahan Kredit</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Saldo Akhir Debet</th>
                    <th class="border border-gray-200 px-4 py-2 text-right">Saldo Akhir Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="Array.isArray(flattenedData) && flattenedData.length > 0">
                        <tr
                            v-for="(account, index) in flattenedData"
                            :key="index"
                            :class="{ 'font-semibold bg-gray-50': account.isGroup }"
                        >
                            <td class="px-4 py-2 border border-gray-200">
                            {{ account.account_code }}
                            </td>
                            <td
                                class="py-2 border border-gray-200"
                                :style="{
                                    paddingLeft: account.isGroup 
                                    ? `${account.level * 20 + 12}px`  // extra padding for group
                                    : `${account.level * 20}px`
                                }"
                                >
                                {{ account.account_name }}
                            </td>

                            <template v-if="!account.isGroup">
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.opening_debit) }}
                            </td>
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.opening_credit) }}
                            </td>
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.mutation_debit) }}
                            </td>
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.mutation_credit) }}
                            </td>
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.closing_debit) }}
                            </td>
                            <td class="px-4 py-2 border border-gray-200 text-right">
                                {{ formatBalance(account.closing_credit) }}
                            </td>
                            </template>

                            <template v-else>
                            <td colspan="6" class="border border-gray-200"></td>
                            </template>
                        </tr>
                    </template>

                    <!-- add row if empty data -->
                    <template v-else>
                    <tr>
                        <td
                        colspan="8"
                        class="text-center text-gray-400 font-semibold py-4 border border-gray-200"
                        >
                        Tidak ada data yang tersedia.
                        </td>
                    </tr>
                    </template>
                </tbody>

                <!-- footer can still exist -->
                <tfoot>
                    <tr class="font-bold bg-gray-100">
                    <td colspan="2" class="px-4 py-2 text-right border border-gray-200">
                        Total
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.opening_debit) }}
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.opening_credit) }}
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.mutation_debit) }}
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.mutation_credit) }}
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.closing_debit) }}
                    </td>
                    <td class="px-4 py-2 border border-gray-200 text-right">
                        {{ formatBalance(totalSummary.closing_credit) }}
                    </td>
                    </tr>
                </tfoot>
                </table>
        </div>

         <!-- modal filter -->
         <ModalTrialBalance
            :isVisible="filterTrialBalanceDialog" 
            @update:isVisible="filterTrialBalanceDialog = $event"
            @fetchTrialBalances="fetchTrialBalance" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalTrialBalance from '../components/modal/ModalTrialBalance.vue'

export default {
    name: 'TrialBalance',
    components: {
        Breadcrumb,
        Button,
        ModalTrialBalance,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterTrialBalanceDialog: false,
            rawData: [],
                totalSummary: {
                opening_debit: 0,
                opening_credit: 0,
                mutation_debit: 0,
                mutation_credit: 0,
                closing_debit: 0,
                closing_credit: 0,
            }
        }
    },
    computed: {
        flattenedData() {
            const flatten = (accounts, level = 0) => {
                return accounts.flatMap(account => {
                    const isGroup = account.group_code !== undefined
                    const label = isGroup ? account.group_name : account.account_name
                    const code = isGroup ? account.group_code : account.account_code

                    const row = {
                        ...account,
                        account_name: label,
                        account_code: code,
                        level,
                        isGroup
                    }

                    const children = account.children ? flatten(account.children, level + 1) : []
                    return [row, ...children]
                })
            }

            return Array.isArray(this.rawData) ? flatten(this.rawData) : []
        }
    },
    methods: {
        fetchTrialBalance({ data, totals }) {
            console.log(data)
            console.log(totals)
            this.rawData = Array.isArray(data) ? data : []
            this.totalSummary = totals || {
                opening_debit: 0,
                opening_credit: 0,
                mutation_debit: 0,
                mutation_credit: 0,
                closing_debit: 0,
                closing_credit: 0,
            }
        },
        formatBalance(value) {
            if (value === null || value === undefined) return ' '
            return new Intl.NumberFormat('id-ID', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(parseFloat(value))
        },
    },
}
</script>