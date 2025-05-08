<template>
    <main class="mt-8">
        <Breadcrumb />

        <!-- data account -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <!-- header with buttons and search -->
            <div class="flex justify-between items-center mb-4">
                <!-- button group -->
                <ButtonGroup>
                    <Button @click="importAccountDialog = true" label="Import" icon="pi pi-upload" />
                    <Button @click="printToPDF" label="Print" icon="pi pi-print" />
                    <Button @click="exportToExcel" label="Export" icon="pi pi-download" />
                </ButtonGroup>

                <!-- search -->
                <!-- <input 
                    @input="applySearch"
                    v-model="searchQuery" 
                    placeholder="Cari kode/nama akun..." 
                    type="text"
                    class="w-60 px-4 py-2 border rounded-md"> -->
            </div>

            <!-- account table -->
             <h5 class="my-3 font-bold text-lg">Total Akun: {{ totalRecords }}</h5>
            <div class="overflow-x-auto max-h-screen overflow-y-auto">
                <table class="min-w-full table-auto bg-white border border-collapse border-gray-200 rounded-md">
                    <thead>
                        <tr class="text-left bg-gray-100">
                            <th class="px-4 py-2">Kode Akun</th>
                            <th class="px-4 py-2">Nama Akun</th>
                            <th class="px-4 py-2">Tipe Akun</th>
                            <th class="px-4 py-2">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- root accounts -->
                        <template
                            v-for="(account, index) in filteredAccounts"
                            :key="account.account_code">
                            <tr 
                                :class="{'font-bold': account.children && account.children.length > 0}"
                                v-bind:ref="'highlightedRow' + index"
                                @click="scrollToHighlighted(index)">
                                <td class="px-4">{{ account.account_code ?? 'Data tidak tersedia' }}</td>
                                <td class="px-4">{{ account.account_name ?? 'Data tidak tersedia' }}</td>
                                <td>{{ account.account_type_name ?? 'Data tidak tersedia' }}</td>
                                <td class="text-right">
                                    {{ formatCurrency(account.totalBalance) ?? 'Data tidak tersedia' }}</td>
                            </tr>
                            
                            <!-- recursive rendering for children -->
                            <template 
                                v-if="account.children && account.children.length > 0">
                                <template 
                                    v-for="(child, childIndex) in account.children" 
                                    :key="child.account_code">
                                    <tr 
                                        :class="{'font-bold': child.children && child.children.length > 0}"
                                        @click="scrollToHighlighted(index)">
                                        <td class="pl-8">{{ child.account_code }}</td>
                                        <td class="pl-8">{{ child.account_name }}</td>
                                        <td>{{ child.account_type_name ?? '-' }}</td>
                                        <td class="text-right">{{ formatCurrency(child.totalBalance) ?? '-' }}</td>
                                    </tr>
                                    
                                    <template v-if="child.children && child.children.length > 0">
                                        <template v-for="(subChild, subChildIndex) in child.children" :key="subChild.account_code">
                                            <tr
                                                :class="{'font-bold': subChild.children && subChild.children.length > 0}"
                                                @click="scrollToHighlighted(index)">
                                                <td class="pl-12">{{ subChild.account_code }}</td>
                                                <td class="pl-12">{{ subChild.account_name }}</td>
                                                <td>{{ subChild.account_type_name ?? '-' }}</td>
                                                <td class="text-right">{{ formatCurrency(subChild.totalBalance) ?? '-' }}</td>
                                            </tr>
        
                                            <!-- Render recursively for subChild children -->
                                            <template v-if="subChild.children && subChild.children.length > 0">
                                                <template v-for="(subSubChild, subSubChildIndex) in subChild.children" :key="subSubChild.account_code">
                                                    <tr>
                                                        <td class="pl-16">{{ subSubChild.account_code }}</td>
                                                        <td class="pl-16">{{ subSubChild.account_name }}</td>
                                                        <td>{{ subSubChild.account_type_name ?? '-' }}</td>
                                                        <td class="text-right">{{ formatCurrency(subSubChild.totalBalance) ?? '-' }}</td>
                                                    </tr>
                                                </template>
                                            </template>
                                        </template>
                                    </template>

                                </template>
                            </template>
                        </template>

                    </tbody>
                </table>
            </div>
        </div>

        <!-- modal import -->
        <ModalImportAccount 
            :isVisible="importAccountDialog" 
            @update:isVisible="importAccountDialog = $event"
            @fetchAccounts="fetchAccount()" />
    </main>
</template>

<script>
import { debounce } from 'lodash'
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import ButtonGroup from 'primevue/buttongroup'
import ModalImportAccount from '../components/modal/ModalImportAccount.vue'

export default {
    name: 'Account',
    components: {
        Breadcrumb,
        Button,
        ButtonGroup,
        InputText,
        Dialog,
        Checkbox,
        ModalImportAccount,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            accounts: [],
            totalRecords: 0,
            isLoading: false,
            searchQuery: '',
            filteredAccounts: [],
            importAccountDialog: false,
        }
    },
    methods: {
        async fetchAccount() {
            this.showLoader()

            this.$api.get(`${import.meta.env.VITE_API_URL}/account`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { accounts, total } = response.data
                
                this.accounts = this.formatAccounts(accounts)
                this.totalRecords = total
                this.filteredAccounts = [...this.accounts]
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
                this.hideLoader()
            })
        },
        formatCurrency(amount) {
            amount = parseFloat(amount)

            if (isNaN(amount)) {
                return '-'
            }

            const numberFormat = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })

            let formattedAmount = numberFormat.format(amount)

            // if negative/minus
            if (amount < 0) {
                formattedAmount = `(${formattedAmount.replace('IDR ', '').replace('-', '')})`
            }

            return formattedAmount
        },
        formatAccounts(accounts) {
            let accountMap = new Map()
            let rootAccounts = []

            // create a map of accounts by account_code
            accounts.forEach(account => {
                account.children = []
                account.totalBalance = account.opening_balance || 0
                accountMap.set(account.account_code, account)
            })

            // build hierarchy by finding children
            accounts.forEach(account => {
                if (account.parent_id) {
                    let parentAccount = accountMap.get(account.parent_id)
                    if (parentAccount) {
                        parentAccount.children.push(account)
                        parentAccount.totalBalance += account.totalBalance
                    }
                } else {
                    rootAccounts.push(account) // root accounts (without sub_account_code)
                }
            })

            function calculateTotalBalance(account) {
                if (account.children && account.children.length > 0) {
                    let totalChildBalance = 0
                    
                    account.children.forEach(child => {
                        calculateTotalBalance(child)
                        const childBalance = parseFloat(child.totalBalance) || 0
                        totalChildBalance += childBalance
                    })

                    // add total balance children to parent
                    account.totalBalance = parseFloat(account.totalBalance) + totalChildBalance
                }
            }

            rootAccounts.forEach(account => calculateTotalBalance(account))

            return rootAccounts
        },
        // method to handle search filtering
        applySearch : debounce(function() {
            const query = this.searchQuery.toLowerCase().trim()

            if (!query) {
                this.filteredAccounts = this.formatAccounts(this.accounts)
            } else {
                this.filteredAccounts = this.filterAccounts(this.accounts, query)
            }

            if (this.filteredAccounts.length > 0) {
                this.scrollToHighlighted(0)
            }
        }, 500),
        // recursive method to filter accounts by account_code or account_name
        filterAccounts(accounts, query) {
            return accounts.filter(account => {
                const matches = account.account_code.toLowerCase().includes(query) ||
                                account.account_name.toLowerCase().includes(query)
                
                if (account.children && account.children.length > 0) {
                    account.children = this.filterAccounts(account.children, query)
                }

                return matches || account.children.length > 0
            })
        },
        // to check if account matches the search query and highlight it
        isHiglighted(account) {
            return account.account_code.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
            account.account_name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
            (account.children && account.children.some(child => 
                child.account_code.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                child.account_name.toLowerCase().includes(this.searchQuery.toLowerCase())
            ))
        },
        scrollToHighlighted(index) {
            this.$nextTick(() => {
                const row = this.$refs['highlightedRow' + index]
                if (row && row.scrollIntoView) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
            })
        },
        async printToPDF() {
            this.showLoader()

            this.$api.get(`${import.meta.env.VITE_API_URL}/account/pdf`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                },
                responseType: 'blob'
            })
            .then(response => {
                this.hideLoader()
                
                const blob = new Blob([response.data], { type: 'application/pdf' })
                const url = window.URL.createObjectURL(blob)
                
                // new tab (streaming)
                window.open(url, '_blank')
            })
            .catch(error => {
                console.error(error)
                this.hideLoader()

                this.$toast.add({
                    severity: 'error',
                    summary: 'Print Data Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during prints.',
                    life: 3000
                })

                this.$nextTick(() => {
                    document.activeElement.blur()
                })
            })
        },
        async exportToExcel() {
            this.showLoader()

            this.$api.get(`${import.meta.env.VITE_API_URL}/account/excel`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                },
                responseType: 'blob'
            })
            .then(response => {
                this.hideLoader()
                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')

                link.href = url
                link.setAttribute('download', 'Master Data Akun.xlsx')
                document.body.appendChild(link)
                link.click()

                document.body.removeChild(link)
            })
            .catch(error => {
                console.error(error)
                this.hideLoader()

                this.$toast.add({
                    severity: 'error',
                    summary: 'Export Data Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during export.',
                    life: 3000
                })

                this.$nextTick(() => {
                    document.activeElement.blur()
                })
            })
        },
    },
    mounted() {
        this.fetchAccount()
    },
}
</script>