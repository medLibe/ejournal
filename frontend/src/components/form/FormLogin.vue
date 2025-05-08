<template>
<div class="p-8 bg-white rounded shadow-lg">
    <form @submit.prevent="handleLogin">
        <div class="mb-4 flex flex-col">
            <label for="username">Username</label>
            <input 
                v-model="username"
                type="text"
                placeholder="Username"
                class="border border-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-300 mt-2 pl-4 py-1 px-3 rounded">
            <p v-if="errors.username" class="text-red-500 text-sm mt-1">{{ errors.username[0] }}</p>
        </div>
        <div class="mb-4 flex flex-col">
            <label for="username">Password</label>
            <input 
                v-model="password"
                type="password"
                placeholder="Password"
                class="border border-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-300 mt-2 pl-4 py-1 px-3 rounded">
            <p v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password[0] }}</p>
        </div>

        <button 
            type="submit"
            class="p-2 w-full bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 rounded text-white">
            Masuk
        </button>
    </form>
</div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'FormLogin',
    inject: ['showLoader', 'hideLoader'],
    data() {
        return {
            username: '',
            password: '',
            errors: {
                username: null,
                password: null
            }
        }
    },
    methods: {
        async handleLogin() {
            this.showLoader()
            this.errors = { username: null, password: null }

            const db = new URLSearchParams(window.location.search).get("db")

           
            await axios.post(`${import.meta.env.VITE_API_URL}/login${db ? `?db=${db}` : ''}`, { 
                username: this.username,
                password: this.password
            })
            .then(response => {
                if (response.data.status) {
                    localStorage.setItem('accessToken', response.data.accessToken)
                    if (db) {
                        localStorage.setItem('db', db)
                    }

                    const authError = localStorage.getItem('authError')
                    if (authError) {
                        localStorage.removeItem('authError')
                    }
                    this.$toast.add({
                        severity: 'success',
                        summary: 'Login Berhasil',
                        detail: response.data.message,
                        life: 3000
                    })

                    this.$nextTick(() => {
                        document.activeElement.blur()
                    })

                    setTimeout(() => {
                        this.$router.push('/dashboard')
                        this.hideLoader()
                    }, 3000)
                }
            })
            .catch(error => {
                this.hideLoader()

                this.$toast.add({
                    severity: 'error',
                    summary: 'Login Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during import.',
                    life: 3000
                })

                this.$nextTick(() => {
                    document.activeElement.blur()
                })
                
                if (error.response && error.response.data.errors) {
                    this.errors = error.response.data.errors
                }
            })
        }
    }
}
</script>