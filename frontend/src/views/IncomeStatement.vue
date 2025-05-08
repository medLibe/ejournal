<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-3 max-w-4xl mx-auto shadow max-h-[80vh] overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterIncomeStatementDialog = true" 
                    label="Filter" 
                    icon="pi pi-filter" 
                />
                <Button 
                    label="Print" 
                    icon="pi pi-print" 
                />
           </div>
            
            <!-- table -->
            <template v-if="Array.isArray(groupedData) && groupedData.length > 0">
                <table class="table-auto text-sm w-full border-collapse border border-gray-200 mt-5">
                    <thead>
                        <tr>
                            <th class="border border-gray-200 px-4 py-2 text-left">Deskripsi</th>
                            <th class="border border-gray-200 px-4 py-2 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- loop account group (Aset, Liabilitas, Ekuitas) -->
                        <template v-for="(group, groupIndex) in groupedData" :key="groupIndex">
                            <tr class="bg-gray-100">
                                <td class="pl-6 font-bold px-4 py-2 border border-gray-200">
                                    {{ group.account_group_name || 'No Group Name' }}
                                </td>
                                <td class="px-4 py-2 border border-gray-200 text-right"></td>
                            </tr>

                            <!-- loop account type -->
                            <template v-for="(type, typeIndex) in group.account_types" :key="typeIndex">
                                <tr>
                                    <td class="pl-10 px-4 py-2 border border-gray-200">
                                        {{ type.account_type_name || 'No Type Name' }}
                                    </td>
                                    <td class="px-4 py-2 border border-gray-200 text-right"
                                        :class="{ 'text-rose-600': isNegative(type.total_balance) }">
                                        {{ formatBalance(type.total_balance) }}
                                    </td>
                                </tr>

                                <!-- loop account -->
                                <template v-for="(account, accIndex) in type.accounts" :key="accIndex">
                                    <IncomeStatementRow :account="account" :depth="2" />
                                </template>
                            </template>

                            <!-- total group account -->
                            <tr class="bg-gray-200 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Total {{ group.account_group_name }}</td>
                                <td class="px-4 py-2 border border-gray-200 text-right"
                                    :class="{ 'text-rose-600': isNegative(group.total_balance) }">
                                    {{ formatBalance(group.total_balance) }}
                                </td>
                            </tr>
                        </template>

                        <template v-if="viewTotal">
                            <tr class="bg-blue-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Laba Sebelum Pajak</td>
                                <td class="px-4 py-2 border border-gray-200 text-right">
                                    {{ formatBalance(summary.profit_before_tax) }}
                                </td>
                            </tr>
                            <tr class="bg-yellow-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Pajak (11%)</td>
                                <td class="px-4 py-2 border border-gray-200 text-right text-red-500">
                                    {{ formatBalance(summary.tax) }}
                                </td>
                            </tr>
                            <tr class="bg-green-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Laba Setelah Pajak</td>
                                <td class="px-4 py-2 border border-gray-200 text-right text-green-600">
                                    {{ formatBalance(summary.profit_after_tax) }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>

            <template
                v-else>
                <p class="text-center font-bold mt-5 text-gray-400">
                    {{ Object.keys(rawData).length === 0
                        ? 'Tidak ada data yang tersedia.' 
                        : 'Data tidak valid. Silakan periksa kembali.' }}
                </p>
            </template>
        </div>

         <!-- modal filter -->
         <ModalIncomeStatement
            :isVisible="filterIncomeStatementDialog" 
            @update:isVisible="filterIncomeStatementDialog = $event"
            @fetchIncomeStatements="fechIncomeStatement" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalIncomeStatement from '../components/modal/ModalIncomeStatement.vue'
import IncomeStatementRow from '../components/others/IncomeStatementRow.vue'

export default {
    name: 'IncomeStatement',
    components: {
        Breadcrumb,
        Button,
        ModalIncomeStatement,
        IncomeStatementRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterIncomeStatementDialog: false,
            rawData: {},
            summary: {},
            viewTotal: false,
            viewParent: false,
            viewChildren: false,
        }
    },
    computed: {
        groupedData() {
            if (this.viewTotal && this.rawData?.data) {
                return Object.entries(this.rawData.data).map(([groupName, accountTypes]) => {
                    const accountTypeMapped = accountTypes.map(accountType => {
                        return {
                            account_type_name: accountType.account_type_name,
                            total_balance: parseFloat(accountType.total_balance) || 0,
                            accounts: accountType.accounts || []
                        }
                    })

                    const groupTotalBalance = accountTypeMapped.reduce(
                        (sum, type) => sum + type.total_balance, 0)

                    return {
                        account_group_name: groupName,
                        account_types: accountTypeMapped,
                        total_balance: groupTotalBalance
                    }
                })
            }
            return []
        }
    },
    methods: {
        isNegative(value) {
            return parseFloat(value) < 0
        },
        fechIncomeStatement({ data, summary, viewTotal, viewParent, viewChildren }) {
            this.viewTotal = !!viewTotal
            this.viewParent = !!viewParent
            this.viewChildren = !!viewChildren

            if (viewTotal && data) {
                this.rawData = {
                    data,
                }
                this.summary = summary || {}
            } else {
                this.rawData = {}
                this.summary = {}
            }

            console.log('Summary:', summary)
            console.log('Assigned summary:', this.summary)

        },
        formatBalance(value) {

            if (value === null || value === undefined) return '-'
            return new Intl.NumberFormat('id-ID', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(parseFloat(value))
        },
    },
}
</script>