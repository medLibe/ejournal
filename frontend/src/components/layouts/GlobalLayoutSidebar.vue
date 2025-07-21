<template>
    <aside 
        :class="{
            'lg:w-64 w-64': isSidebarOpen, 
            'w-16': !isSidebarOpen && !isHoveringSidebar,
            'lg:w-16': !isSidebarOpen && !isHoveringSidebar && !isMobile,
            'w-64': isHoveringSidebar && !isSidebarOpen && isMobile,
        }"
        class="fixed z-50 lg:w-64 h-screen bg-slate-800 text-white py-5 px-3 overflow-auto scrollbar-hidden transition-all duration-300 ease-in-out"
        @mouseenter="expandSidebarOnHover"
        @mouseleave="shrinkSidebarOnLeave">
        <div class="flex items-center mb-4">
            <img src="/accounting.png" alt="Ejournal Logo" class="w-8 h-8">
            <span 
                v-if="isSidebarOpen || isHoveringSidebar" 
                class="ml-2 font-extrabold text-lg">e-journal</span>
        </div>

        <!-- menu -->
        <nav>
            <ul>
                <li 
                    v-for="(menu, index) in menuItems" 
                    :key="index" 
                    class="mb-3">
                    <div
                        class="flex items-center justify-between hover:bg-slate-600 p-3 text-sm rounded cursor-pointer"
                        @click="menu.submenu ? toggleSubmenu(index) : null"
                        :class="{
                            'bg-slate-700': isActive(menu.link) || isActiveSubmenu(menu.submenu),
                        }"
                    >
                        <a v-if="!menu.submenu" :href="menu.link" class="flex items-center gap-2">
                            <font-awesome-icon :icon="menu.icon" />
                            <span v-if="isSidebarOpen || isHoveringSidebar">{{ menu.label }}</span>
                        </a>
                        <div v-else class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2">
                                <font-awesome-icon :icon="menu.icon" />
                                <span v-if="isSidebarOpen || isHoveringSidebar">{{ menu.label }}</span>
                            </div>
                            <font-awesome-icon
                                v-if="isSidebarOpen || isHoveringSidebar"
                                :icon="['fas', menu.isOpen ? 'caret-down' : 'caret-right']"
                            />
                        </div>
                    </div>

                    <!-- submenu -->
                    <ul 
                        v-if="menu.submenu && menu.isOpen" 
                        class="ml-2 mt-3" 
                        :class="{'block': isSidebarOpen || isHoveringSidebar, 'hidden': !(isSidebarOpen || isHoveringSidebar)}">
                        <li 
                            v-for="(sub, subIndex) in menu.submenu" 
                            :key="subIndex" 
                            class="hover:bg-slate-600 p-3 my-2 text-sm rounded" 
                            :class="{'bg-slate-700': isActive(sub.link)}">
                            <a class="flex items-center gap-2" :href="sub.link">
                                <font-awesome-icon :icon="['fas', 'folder']"/>
                                <span>{{ sub.label }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </aside>
</template>

<script>
export default {
    name: 'GlobalLayoutSidebar',
    props: {
        isSidebarOpen: Boolean,
        isHoveringSidebar: Boolean,
        menuItems: Array
    },
    data() {
        return {
            isMobile: false,
        }    
    },
    methods: {
        toggleSubmenu(index) {
            this.$emit('toggle-submenu', index)
        },
        expandSidebarOnHover() {
            this.$emit('expand-sidebar')
        },
        shrinkSidebarOnLeave() {
            this.$emit('shrink-sidebar')
        },
        isActive(link) {
            if (!link) return false
            return this.$route.path === link
        },
        isActiveSubmenu(submenu) {
            if (!submenu) return false
            return submenu.some(sub => this.$route.path.startsWith(sub.link))
        },
    },
    mounted() {
        this.isMobile = window.innerWidth < 768
    },
    watch: {
        isSidebarOpen(newVal) {
            if (this.isMobile && !newVal) {
                this.isMobile = true
            }
        }
    },
}
</script>
