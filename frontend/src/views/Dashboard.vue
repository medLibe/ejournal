<template>
    <Breadcrumb />

    <main class="mt-8 px-4">
        <!-- TOP SECTION -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="bg-sky-700 text-white p-8 min-h-[160px] rounded-lg flex items-center gap-4">
                <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'file-invoice-dollar']" />
                <div class="flex flex-col">
                    <h5 class="text-lg sm:text-xl lg:text-2xl">
                        <vue3-autocounter 
                            ref="counter" 
                            :startAmount="0" 
                            :endAmount="dashboardData.revenue" 
                            :duration="3" 
                            separator="."
                            :autoinit="true" />
                    </h5>
                    <p class="text-sm md:text-base">Pendapatan (Juli 2025)</p>
                </div>
            </div>

            <div class="bg-emerald-600 text-white p-8 min-h-[160px] rounded-lg flex items-center gap-4">
                <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'exchange-alt']" />
                <div class="flex flex-col">
                    <h5 class="text-lg sm:text-xl lg:text-2xl">
                        <vue3-autocounter 
                            ref="counter" 
                            :startAmount="0" 
                            :endAmount="dashboardData.expense" 
                            :duration="3" 
                            separator="."
                            :autoinit="true" />
                    </h5>
                    <p class="text-sm md:text-base">Beban (Juli 2025)</p>
                </div>
            </div>

            <div class="bg-rose-600 text-white p-8 min-h-[160px] rounded-lg flex items-center gap-4">
                <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'book']" />
                <div class="flex flex-col">
                    <h5 class="text-lg sm:text-xl lg:text-2xl">
                        <vue3-autocounter 
                            ref="counter" 
                            :startAmount="0" 
                            :endAmount="dashboardData.cash" 
                            :duration="3" 
                            separator="."
                            :autoinit="true" />
                    </h5>
                    <p class="text-sm md:text-base">Kas</p>
                </div>
            </div>

            <div class="bg-cyan-700 text-white p-8 min-h-[160px] rounded-lg flex items-center gap-4">
                <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'coins']" />
                <div class="flex flex-col">
                    <h5 class="text-lg sm:text-xl lg:text-2xl">
                        <vue3-autocounter 
                            ref="counter" 
                            :startAmount="0" 
                            :endAmount="dashboardData.bank" 
                            :duration="3" 
                            separator="."
                            :autoinit="true" />
                    </h5>
                    <p class="text-sm md:text-base">Bank</p>
                </div>
            </div>
        </div>

        <!-- BOTTOM SECTION -->
        <div class="grid grid-cols-1 xl:grid-cols-[2fr_1fr] gap-4">
            <!-- LEFT: Chart -->
            <div class="bg-white shadow-md rounded-lg p-6 h-[36rem] flex flex-col">
                <h4 class="text-2xl font-semibold mb-4">Grafik Arus Kas</h4>
                <Chart 
                    type="bar" 
                    :data="chartData" 
                    :options="chartOptions" 
                    class="flex-1" 
                />
            </div>

            <!-- RIGHT: Info Cards -->
            <div class="flex flex-col gap-4">
                <div class="bg-indigo-600 text-white p-8 flex-1 rounded-lg flex items-center gap-4">
                    <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'file']" />
                    <div class="flex flex-col">
                        <h5 class="text-lg sm:text-xl lg:text-2xl">
                            <vue3-autocounter 
                                ref="counter" 
                                :startAmount="0" 
                                :endAmount="dashboardData.general_ledger" 
                                :duration="3"
                                separator="." 
                                :autoinit="true" />
                        </h5>
                        <p class="text-sm md:text-base">Transaksi Jurnal (Juli 2025)</p>
                    </div>
                </div>

                <div class="bg-yellow-600 text-white p-8 flex-1 rounded-lg flex items-center gap-4">
                    <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'file-archive']" />
                    <div class="flex flex-col">
                        <h5 class="text-lg sm:text-xl lg:text-2xl">
                            <vue3-autocounter 
                                ref="counter" 
                                :startAmount="0" 
                                :endAmount="dashboardData.active_account" 
                                :duration="3"
                                separator="." 
                                :autoinit="true" />
                        </h5>
                        <p class="text-sm md:text-base">Akun Aktif</p>
                    </div>
                </div>

                <div class="bg-green-700 text-white p-8 flex-1 rounded-lg flex items-center gap-4">
                    <font-awesome-icon class="text-7xl md:text-5xl" :icon="['fas', 'building']" />
                    <div class="flex flex-col">
                        <h5 class="text-lg sm:text-xl lg:text-2xl">
                            {{ dashboardData.department }}
                        </h5>
                        <p class="text-sm md:text-base">Divisi/Departemen</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Vue3AutoCounter from 'vue3-autocounter'
import Chart from 'primevue/chart'

export default {
    name: 'Dashboard',
    components: {
        Breadcrumb,
        Chart,
        'vue3-autocounter': Vue3AutoCounter
    },
    data() {
        return {
            chartData: null,
            chartOptions: null,
            dashboardData: {
                revenue: 0,
                expense: 0,
                cash: 0,
                bank: 0,
                general_ledger: 0,
                active_account: 0,
                department: 'Unknown'
            }
        }
    },
    mounted() {
        this.chartData = this.setChartData()
        this.chartOptions = this.setChartOptions()
        this.getDashboard()
    },
    methods: {
        setChartData() {
            const style = getComputedStyle(document.documentElement)
            return {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                datasets: [
                    {
                        type: 'line',
                        label: 'Dataset 1',
                        borderColor: style.getPropertyValue('--p-orange-500'),
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        data: [50, 25, 12, 48, 56, 76, 42]
                    },
                    {
                        type: 'bar',
                        label: 'Dataset 2',
                        backgroundColor: style.getPropertyValue('--p-gray-500'),
                        data: [21, 84, 24, 75, 37, 65, 34],
                        borderColor: 'white',
                        borderWidth: 2
                    },
                    {
                        type: 'bar',
                        label: 'Dataset 3',
                        backgroundColor: style.getPropertyValue('--p-cyan-500'),
                        data: [41, 52, 24, 74, 23, 21, 32]
                    }
                ]
            }
        },
        setChartOptions() {
            const style = getComputedStyle(document.documentElement)
            const textColor = style.getPropertyValue('--p-text-color')
            const textColorSecondary = style.getPropertyValue('--p-text-muted-color')
            const surfaceBorder = style.getPropertyValue('--p-content-border-color')

            return {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: textColorSecondary
                        },
                        grid: {
                            color: surfaceBorder
                        }
                    },
                    y: {
                        ticks: {
                            color: textColorSecondary
                        },
                        grid: {
                            color: surfaceBorder
                        }
                    }
                }
            }
        },
        async getDashboard() {
            try {
                const response = await this.$api.get(
                    `${import.meta.env.VITE_API_URL}/dashboard`,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                        }
                    }
                )

                this.dashboardData = response.data
            } catch (error) {

                this.$toast.add({
                    severity: 'error',
                    summary: 'Print Data Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during prints.',
                    life: 3000
                })
            }
        }
    }
}
</script>