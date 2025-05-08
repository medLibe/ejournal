<template>
    <header>
        <div class="p-3 bg-slate-800 text-gray-100 flex items-center justify-between">
            <!-- bar button -->
            <div class="flex items-center">
                <button 
                    @click="toggleSidebar"
                    :class="['text-white', 'text-xl', 
                    { 'ml-64': isSidebarOpen, 'ml-16': !isSidebarOpen },
                    'lg:hidden', 'transition-all', 'duration-300', 'ease-in-out']">
                    <font-awesome-icon :icon="['fas', 'bars']" />
                </button>
            </div>

            <!-- right side navbar -->
            <div class="flex items-center space-x-4">
                <button 
                    @click.stop="toggleDropdown"
                    class="hover:text-gray-300 flex items-center gap-4">
                    <font-awesome-icon class="text-2xl" :icon="['fas', 'user-circle']" />
                    <font-awesome-icon :icon="['fas', 'caret-down']" />
                </button>
            </div>
        </div>

        <!-- pop up menu -->
        <div v-show="isDropdownVisible" ref="dropdownMenu" class="absolute right-2 bg-white border border-gray-200 shadow-lg rounded-md">
            <ul class="w-44 p-2 space-y-1">
                <li>
                    <button 
                        @click="handleLogout"
                        class="block text-sm text-rose-500 hover:bg-gray-200 hover:text-rose-600 p-2 w-full text-start">
                        <font-awesome-icon :icon="['fas', 'sign-out-alt']" />
                        Keluar
                    </button>
                </li>
            </ul>
        </div>
    </header>
</template>

<script>
import axios from 'axios'

export default {
    name: 'GlobalLayoutHeader',
    props: {
        isSidebarOpen: Boolean,
        isDropdownVisible: Boolean
    },
    inject: ['showLoader', 'hideLoader'],
    methods: {
        toggleSidebar() {
            this.$emit('toggle-sidebar')
        },
        toggleDropdown() {
            this.$emit('toggle-dropdown', !this.isDropdownVisible)
        },
        handleClickOutside(event) {
            if (this.$refs.dropdownMenu && !this.$refs.dropdownMenu.contains(event.target)) {
                this.$emit('toggle-dropdown', false)
            }
        },
        async handleLogout() {
            this.showLoader()

            await this.$api.get(`${import.meta.env.VITE_API_URL}/logout`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                },
            })
                .then(response => {
                    localStorage.removeItem('accessToken')

                    setTimeout(() => {
                        this.$router.push('/')
                        this.hideLoader()
                    }, 3000)
                })
                .catch(error => {
                    this.hideLoader()

                    this.$toast.add({
                        severity: 'error',
                        summary: 'Logout Gagal',
                        detail: error.response ? error.response.data.message : 'Terjadi kesalahan saat logout.',
                        life: 3000
                    })

                    this.$nextTick(() => {
                        document.activeElement.blur()
                    })
                })
        }
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside)
    },
    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside)
    }
}
</script>
