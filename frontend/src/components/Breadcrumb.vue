<template>
    <nav class="flex flex-col gap-2 justify-between lg:flex-row lg:gap-0">
        <h3 class="font-semibold text-2xl">{{ breadcrumbTitle }}</h3>
        <ol class="flex gap-2 text-sm">
            <li class="text-sky-700">
                <router-link to="/dashboard">Dashboard</router-link>
            </li>
            <span class="text-gray-300">/</span>
            <li class="underline active" aria-current="page">
                {{ breadcrumbTitle }}
            </li>
        </ol>
    </nav>
</template>

<script>
export default {
    name: 'Breadcrumb',
    computed: {
        breadcrumbTitle() {
            const routeMeta = this.$route.meta.title
            return routeMeta ? routeMeta : 'No Title'
        }
    }
}
</script>