<template>
     <div class="min-h-screen flex flex-col bg-[#e9ecef]">
        
         <div class="flex h-screen flex-col items-center justify-center">
            <h5 class="text-3xl mb-4 uppercase font-extralight">Masuk</h5>

            <FormLogin/>
         </div>
    </div>
</template>

<script>
import FormLogin from '../components/form/FormLogin.vue'

export default {
    name: 'Login',
    components: {
        FormLogin
    },
}
</script>