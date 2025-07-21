<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterBalanceSheetDialog = true" 
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
                <thead>
                    <tr>
                        <th class="border border-gray-200 px-4 py-2 text-left">Deskripsi</th>
                        <th class="border border-gray-200 px-4 py-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="Array.isArray(groupedData) && groupedData.length > 0">
                        <!-- loop accounts -->
                        <template v-for="(group, groupIndex) in groupedData" :key="groupIndex">
                            <tr class="bg-gray-100">
                                <td class="pl-6 font-bold px-4 py-2 border border-gray-200">
                                    {{ group.account_group_name || 'No Group Name' }}
                                </td>
                                <td class="px-4 py-2 border border-gray-200 text-right"></td>
                            </tr>

                            <template v-for="(type, typeIndex) in group.account_types" :key="typeIndex">
                                <tr>
                                    <td class="pl-10 font-bold px-4 py-2 border border-gray-200">
                                        {{ type.account_type_name || 'No Type Name' }}
                                    </td>
                                    <td class="px-4 py-2 border border-gray-200 text-right">
                                        {{ formatBalance(type.total_balance) }}
                                    </td>
                                </tr>

                                <template v-for="(account, accIndex) in type.accounts" :key="accIndex">
                                    <BalanceSheetRow :account="account" :depth="2" />
                                </template>
                            </template>

                            <tr class="bg-gray-200 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Total {{ group.account_group_name }}</td>
                                <td class="px-4 py-2 border border-gray-200 text-right">
                                    {{ formatBalance(group.total_balance) }}
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- show empty row if no data -->
                    <template v-else>
                        <tr>
                            <td colspan="2" class="text-center text-gray-400 font-semibold py-6">
                                {{ rawData.length === 0 ? 'Tidak ada data yang tersedia.' : 'Data tidak valid. Silakan periksa kembali.' }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

         <!-- modal filter -->
         <ModalBalanceSheet
            :isVisible="filterBalanceSheetDialog" 
            @update:isVisible="filterBalanceSheetDialog = $event"
            @fetchBalanceSheets="fetchBalanceSheet" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalBalanceSheet from '../components/modal/ModalBalanceSheet.vue'
import BalanceSheetRow from '../components/others/BalanceSheetRow.vue'

export default {
    name: 'BalanceSheet',
    components: {
        Breadcrumb,
        Button,
        ModalBalanceSheet,
        BalanceSheetRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterBalanceSheetDialog: false,
            rawData: [],
            viewTotal: false,
            viewParent: false,
            viewChildren: false,
        }
    },
    computed: {
        groupedData() {
            if (this.viewTotal) {
                return this.rawData && typeof this.rawData === 'object' && Object.keys(this.rawData).length > 0
                    ? Object.entries(this.rawData).map(([groupName, accounts]) => {
                        const accountTypes = accounts.map(account => ({
                            account_type_name: account.account_type_name,
                            total_balance: parseFloat(account.total_balance) || 0
                        }));
                        
                        // sum total balance for the group
                        const groupTotalBalance = accountTypes.reduce((sum, account) => sum + account.total_balance, 0);

                        return {
                            account_group_name: groupName,
                            account_types: accountTypes,
                            total_balance: groupTotalBalance // store group total balance
                        };
                    })
                    : []
            } else if (this.viewParent || this.viewChildren) {
                return Array.isArray(this.rawData) ? this.rawData : []
            }
            return []
        },
    },
    methods: {
        isNegative(value) {
            return parseFloat(value) < 0
        },
        fetchBalanceSheet({ data, viewTotal, viewParent, viewChildren }) {
            this.viewTotal = !!viewTotal
            this.viewParent = !!viewParent
            this.viewChildren = !!viewChildren

            if (viewTotal) {
                this.rawData = data && typeof data === 'object' && Object.keys(data).length > 0 ? data : {}
            } else {
                this.rawData = Array.isArray(data) ? data : []
            }
        },
        formatBalance(value) {
            if (value === null || value === undefined) return ' '
            
            const number = parseFloat(value)
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Math.abs(number))

            return number < 0 ? `(${formatted})` : formatted
        }
    },
}
</script>