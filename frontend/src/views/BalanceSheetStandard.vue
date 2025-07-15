<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
            <!-- button -->
           <div class="flex gap-1">
                <Button 
                    @click="filterBalanceSheetStandardDialog = true" 
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
                                    <td class="pl-10 font-bold px-4 py-2 border border-gray-200">
                                        {{ type.account_type_name || 'No Type Name' }}
                                    </td>
                                    <td class="px-4 py-2 border border-gray-200 text-right"
                                        :class="{ 'text-rose-600': isNegative(type.total_balance) }">
                                        {{ formatBalance(type.total_balance) }}
                                    </td>
                                </tr>

                                <!-- loop account -->
                                <template v-for="(account, accIndex) in type.accounts" :key="accIndex">
                                    <BalanceSheetRow :account="account" :depth="2" />
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
                    </tbody>
                </table>
            </template>

            <template
                v-else>
                <p class="text-center font-bold mt-5 text-gray-400">
                    {{ rawData.length === 0 
                        ? 'Tidak ada data yang tersedia.' 
                        : 'Data tidak valid. Silakan periksa kembali.' }}
                </p>
            </template>
        </div>

         <!-- modal filter -->
         <ModalBalanceSheetStandard
            :isVisible="filterBalanceSheetStandardDialog" 
            @update:isVisible="filterBalanceSheetStandardDialog = $event"
            @fetchBalanceSheets="fetchBalanceSheet" />
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import ModalBalanceSheetStandard from '../components/modal/ModalBalanceSheetStandard.vue'
import BalanceSheetRow from '../components/others/BalanceSheetRow.vue'

export default {
    name: 'BalanceSheetStandard',
    components: {
        Breadcrumb,
        Button,
        ModalBalanceSheetStandard,
        BalanceSheetRow
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            filterBalanceSheetStandardDialog: false,
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
            return new Intl.NumberFormat('id-ID', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(parseFloat(value))
        },
    },
}
</script>