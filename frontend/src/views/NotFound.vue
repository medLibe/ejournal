<template>
    <Breadcrumb/>

    <p class="bg-neutral-600 text-white p-4 rounded-lg mt-8">
        <font-awesome-icon :icon="['fas', 'circle-exclamation']"/>
        Halaman yang anda cari tidak ditemukan.</p>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'

export default {
    name: 'NotFound',
    components: {
        Breadcrumb
    }
}
</script>