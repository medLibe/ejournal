<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="bg-white p-12 w-full shadow max-h-full overflow-auto mt-5 border border-gray-200 rounded-lg">
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
                    @click="handlePrint"
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
                        <template v-for="(group, groupIndex) in groupedData" :key="groupIndex">
                            <tr class="bg-gray-100">
                                <td class="pl-6 font-bold px-4 py-2 border border-gray-200">
                                    {{ group.account_group_name || 'No Group Name' }}
                                </td>
                                <td class="px-4 py-2 border border-gray-200 text-right"></td>
                            </tr>

                            <template v-for="(type, typeIndex) in group.account_types" :key="typeIndex">
                                <tr>
                                    <td class="pl-10 font-semibold px-4 py-2 border border-gray-200">
                                        {{ type.account_type_name || 'No Type Name' }}
                                    </td>
                                    <td class="px-4 py-2 font-semibold border border-gray-200 text-right">
                                        {{ type.total_balance }}
                                    </td>
                                </tr>

                                <template v-for="(account, accIndex) in type.accounts" :key="accIndex">
                                    <IncomeStatementRow :account="account" :depth="2" />
                                </template>
                            </template>

                            <tr class="bg-gray-200 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Total {{ group.account_group_name }}</td>
                                <td class="px-4 py-2 border border-gray-200 text-right">
                                    {{ group.total_balance }}
                                </td>
                            </tr>
                        </template>

                        <template v-if="viewTotal">
                            <tr class="bg-blue-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Laba Sebelum Pajak</td>
                                <td class="px-4 py-2 border border-gray-200 text-right">
                                    {{ summary.profit_before_tax }}
                                </td>
                            </tr>
                            <tr class="bg-yellow-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Pajak (11%)</td>
                                <td class="px-4 py-2 border border-gray-200 text-right text-red-500">
                                    {{ summary.tax }}
                                </td>
                            </tr>
                            <tr class="bg-green-100 font-bold">
                                <td class="pl-6 px-4 py-2 border border-gray-200">Laba Setelah Pajak</td>
                                <td class="px-4 py-2 border border-gray-200 text-right text-green-600">
                                    {{ summary.profit_after_tax }}
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- if no data exists -->
                    <template v-else>
                        <tr>
                            <td colspan="2" class="text-center text-gray-400 font-semibold py-6">
                                {{ Object.keys(rawData).length === 0
                                    ? 'Tidak ada data yang tersedia.'
                                    : 'Data tidak valid. Silakan periksa kembali.' }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

         <!-- modal filter -->
         <ModalIncomeStatement
            :isVisible="filterIncomeStatementDialog" 
            @update:isVisible="filterIncomeStatementDialog = $event"
            @fetchIncomeStatements="fetchIncomeStatement" />
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
            filterParams: {}
        }
    },
    computed: {
        groupedData() {
            const toNumber = s => {
                if (!s) return 0
                return parseFloat(String(s).replace(/\./g, '').replace(',', '.'))
            }

            if (this.viewTotal && this.rawData?.data) {
                return Object.entries(this.rawData.data).map(([groupName, accountTypes]) => {
                    const accountTypeMapped = accountTypes.map(accountType => {
                        return {
                            account_type_name: accountType.account_type_name,
                            total_balance: accountType.total_balance,
                            accounts: accountType.accounts || []
                        }
                    })

                    const groupTotal = accountTypeMapped.reduce((sum, type) => {
                        return sum + toNumber(type.total_balance)
                    }, 0)

                    return {
                        account_group_name: groupName,
                        account_types: accountTypeMapped,
                        total_balance: groupTotal.toLocaleString('id-ID')
                    }
                })
            }
            return []
        }
    },
    methods: {
        fetchIncomeStatement({ data, summary, viewTotal, viewParent, viewChildren, filters }) {
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

            this.filterParams = filters || {}
        },
        async handlePrint() {
            try {
                this.showLoader()

                const formatDate = (date) => {
                    if (!date) return null;
                    const d = new Date(date)
                    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
                }

                const params = {
                    startDate: formatDate(this.filterParams.startDate) || null,
                    endDate: formatDate(this.filterParams.endDate) || null,
                    division: this.filterParams.division || null,
                    viewTotal: this.filterParams.viewTotal || false,
                    viewParent: this.filterParams.viewParent || false,
                    viewChildren: this.filterParams.viewChildren || false,
                }

                const response = await this.$api.get(
                    `${import.meta.env.VITE_API_URL}/print/income-statement`,  // sesuaikan endpoint print
                    {
                        params,
                        responseType: 'blob',
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                        }
                    }
                )

                const blob = new Blob([response.data], { type: 'application/pdf' })
                const url = window.URL.createObjectURL(blob)
                window.open(url, '_blank')
            } catch (error) {
                console.error('Print error:', error)
                this.$toast.add({
                    severity: 'error',
                    summary: 'Print Gagal',
                    detail: error.response?.data.message || 'Terjadi kesalahan saat mencetak.',
                    life: 3000
                })
            } finally {
                this.hideLoader()
            }
        }
    },
}
</script>