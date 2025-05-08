<template>
    <Breadcrumb/>

    <main class="mt-8">        
        <!-- data account types -->
        <div class="bg-white p-3 mt-5 rounded-lg shadow">
            <!-- table account types -->
            <table class="min-w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-start">Tipe Akun</th>
                        <th class="px-4 py-2 text-start">Kelompok</th>
                        <th class="px-4 py-2">Saldo Normal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="accountType in accountTypes" :key="accountType.id">
                        <td class="px-4 py-2">{{ accountType.account_type_name }}</td>
                        <td class="px-4 py-2">{{ accountType.account_group.account_group_name }}</td>
                        <td class="px-4 py-2 text-center">
                            <span v-if="accountType.account_group.normal_balance === 1">
                                Debit
                            </span>
                            <span v-else>
                                Kredit
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</template>


<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Button from 'primevue/button'

export default {
    name: 'AccountType',
    components: {
        Breadcrumb,
        Button,
    },
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            accountTypes: [],
            totalRecords: 0,
            isLoading: false,
        }
    },
    methods: {
        async fetchAccountType() {
            this.isLoading = true

            await this.$api.get(`/account-type`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                const { accountTypes, total } = response.data
                this.accountTypes = accountTypes
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
    },
    mounted() {
        this.fetchAccountType()
    }
}
</script>