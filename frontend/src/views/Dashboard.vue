<template>
    <Breadcrumb/>

    <main class="mt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="bg-sky-700 text-white p-6 py-8 w-full md:max-w-md xl:max-w-sm rounded-lg flex items-center gap-4">
                <font-awesome-icon 
                    class="text-5xl"
                    :icon="['fas', 'book']"/>
                <div class="flex flex-col">
                    <h5 class="text-2xl">
                        <vue3-autocounter 
                            ref='counter' 
                            :startAmount='0' 
                            :endAmount='332' 
                            :duration='3'
                            separator='.' 
                            :autoinit='true'
                        />
                    </h5>
                    <p class="text-sm">Transaksi Desember 2024</p>
                </div>
            </div>
            <div class="bg-emerald-600 text-white p-6 py-8 w-full md:max-w-md xl:max-w-sm rounded-lg flex items-center gap-4">
                <font-awesome-icon 
                    class="text-5xl"
                    :icon="['fas', 'layer-group']"/>
                <div class="flex flex-col">
                    <h5 class="text-2xl">
                        <vue3-autocounter 
                            ref='counter' 
                            :startAmount='0' 
                            :endAmount='1245' 
                            :duration='3'
                            separator='.' 
                            :autoinit='true'
                        />
                    </h5>
                    <p class="text-sm">Tipe Akun Perkiraan</p>
                </div>
            </div>
            <div class="bg-rose-600 text-white p-6 py-8 w-full md:max-w-md xl:max-w-sm rounded-lg flex items-center gap-4">
                <font-awesome-icon 
                    class="text-5xl"
                    :icon="['fas', 'book']"/>
                <div class="flex flex-col">
                    <h5 class="text-2xl">
                        <vue3-autocounter 
                            ref='counter' 
                            :startAmount='0' 
                            :endAmount='3342' 
                            :duration='3'
                            separator='.' 
                            :autoinit='true'
                        />
                    </h5>
                    <p class="text-sm">Akun Perkiraan</p>
                </div>
            </div>
            <div class="bg-cyan-700 text-white p-6 py-8 w-full md:max-w-md xl:max-w-sm rounded-lg flex items-center gap-4">
                <font-awesome-icon 
                    class="text-5xl"
                    :icon="['fas', 'book']"/>
                <div class="flex flex-col">
                    <h5 class="text-2xl">
                      <vue3-autocounter 
                            ref='counter' 
                            :startAmount='0' 
                            :endAmount='444' 
                            :duration='3'
                            separator='.' 
                            :autoinit='true'
                        />
                    </h5>
                    <p class="text-sm">Perusahaan</p>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import Vue3AutoCounter from 'vue3-autocounter'

export default {
    name: 'Dashboard',
    components: {
        Breadcrumb,
        'vue3-autocounter': Vue3AutoCounter
    }
}
</script>