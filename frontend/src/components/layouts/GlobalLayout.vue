<template>
    <div class="min-h-screen flex flex-col bg-[#e9ecef]">
        <!-- content -->
         <div class="flex-1 flex flex-col relative">
            <!-- header -->
            <GlobalLayoutHeader 
                @toggle-sidebar="toggleSidebar" 
                @toggle-dropdown="toggleDropdown"
                :is-sidebar-open="isSidebarOpen"
                :is-dropdown-visible="isDropdownVisible"
            />

            <GlobalLayoutSidebar 
                :is-sidebar-open="isSidebarOpen" 
                :is-hovering-sidebar="isHoveringSidebar" 
                :menu-items="menuItems"
                @toggle-submenu="toggleSubmenu"
                @expand-sidebar="expandSidebarOnHover"
                @shrink-sidebar="shrinkSidebarOnLeave"
            />
             
            <!-- main content -->
            <main 
                :class="[
                    'transition-all', 'duration-300', 'ease-in-out',
                    {'ml-64': isSidebarOpen, 'ml-16': !isSidebarOpen

                    }]" 
                class="p-2 lg:p-5 flex-1">
                <slot></slot>
            </main>
         </div>
    </div>
</template>


<script>
import GlobalLayoutHeader from '../layouts/GlobalLayoutHeader.vue'
import GlobalLayoutSidebar from '../layouts/GlobalLayoutSidebar.vue'
export default {
    name: 'GlobalLayout',
    components: {
        GlobalLayoutHeader,
        GlobalLayoutSidebar
    },
    data() {
        return {
            isDropdownVisible: false,
            isSidebarOpen: true,
            isHoveringSidebar: false,
            menuItems: [
                {
                    label: "Dashboard",
                    link: "/dashboard",
                    icon: ['fas', 'dashboard'],
                    
                },
                {
                    label: "Jurnal Umum",
                    icon: ['fas', 'book'],
                    isOpen: true,
                    submenu: [
                        { label: "Daftar Jurnal Umum", link: "/jurnal-umum/daftar" },
                        // { label: "Buat Jurnal Umum", link: "/jurnal-umum/buat" },
                        { label: "Import Jurnal Umum", link: "/jurnal-umum/import" },
                    ]
                },
                {
                    label: "Master Data",
                    icon: ['fas', 'user-cog'],
                    isOpen: true,
                    submenu: [
                        { label: "Daftar Akun", link: "/master-data/daftar-akun" },
                        { label: "Tipe Akun", link: "/master-data/tipe-akun" },
                    ]
                },
                {
                    label: "Laporan",
                    icon: ['fas', 'file-invoice-dollar'],
                    isOpen: true,
                    submenu: [
                        { label: "Neraca", link: "/laporan-keuangan/neraca" },
                        { label: "Buku Besar", link: "/laporan-keuangan/buku-besar" },
                        { label: "Laba Rugi", link: "/laporan-keuangan/laba-rugi" },
                    ]
                },
            ]
        }
    },
    methods: {
        toggleSidebar() {
            this.isSidebarOpen = !this.isSidebarOpen
        },
        toggleDropdown(isVisible) {
            this.isDropdownVisible = isVisible
        },
        toggleSubmenu(index) {
            this.menuItems[index].isOpen = !this.menuItems[index].isOpen
        },
        expandSidebarOnHover() {
            if (!this.isSidebarOpen) {
                this.isHoveringSidebar = true
            }
        },
        shrinkSidebarOnLeave() {
            if (!this.isSidebarOpen) {
                this.isHoveringSidebar = false
            }
        },
        openActiveSubmenu() {
            this.menuItems.forEach(menu => {
                if (menu.submenu) {
                    menu.isOpen = menu.isOpen ?? this.isActiveSubmenu(menu.submenu)
                } else {
                    menu.isOpen = false
                }
            })
        },
        isActiveSubmenu(submenu) {
            if (!submenu) return false
            return submenu.some(sub => this.$route.path.startsWith(sub.link))
        },
        handleResize() {
            this.isMobile = window.innerWidth < 768
            if (this.isMobile) {
                this.isSidebarOpen = false
            } else {
                this.isSidebarOpen = true
            }
        }
    },
    watch: {
        '$route': function() {
            this.openActiveSubmenu()
        }
    },
    created() {
        this.openActiveSubmenu()
    },
    mounted() {
        this.handleResize()
        window.addEventListener('resize', this.handleResize)
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.handleResize)
    },
}
</script>

