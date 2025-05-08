<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-[#e9ecef]">
        <h5 class="text-3xl mt-12 mb-8 uppercase font-semibold text-center">Pilih Divisi</h5>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-6">
            <div 
                v-for="(division, index) in divisionList" 
                :key="index"
                class="border border-gray-200 bg-white p-6 w-40 rounded-lg shadow-lg text-center cursor-pointer hover:bg-gray-100 transition"
                @click="goToLogin(division)">
                <font-awesome-icon 
                    class="text-5xl text-gray-700" 
                    :icon="['fas', 'building']" />
                <p class="mt-2 text-2xl text-gray-700 font-medium">
                    {{ division.name }}</p>
            </div>
        </div>
    </div>
</template>
<script>
import md5 from 'md5'

export default {
    name: 'LandingPage',
    data() {
        return {
            divisionList: [
                { id: 1, name: 'MAC' },
                { id: 2, name: 'MS' },
                { id: 3, name: 'TKP' },
                { id: 4, name: 'EPS' },
                { id: 5, name: 'DIKO' },
                { id: 6, name: 'KIS' },
                { id: 7, name: 'CPP' },
                { id: 8, name: 'TMC' },
            ]
        }
    },
    methods: {
        goToLogin(division) {
            const encodedDb = btoa(division.id)
            this.$router.push({ path: '/login', query: { db: encodedDb } })
        }
    }
}
</script>