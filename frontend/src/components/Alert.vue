<template>
    <div class="relative">
        <div
            v-if="message"
            :class="{
            'p-4 mb-4 text-sm font-medium rounded-lg': true,
            'bg-green-50 text-green-800': status === true,
            'bg-red-50 text-red-800': status === false
            }"
            class="absolute z-50 max-w-80 top-2 right-2"
            role="alert"
        >
            <div class="flex gap-3 items-center">
                <font-awesome-icon
                    :icon="status ? ['fas', 'check-circle'] : ['fas', 'exclamation-circle']"
                    class="text-xl"
                />
                <p>{{ message }}</p>

                <!-- close -->
                 <button
                    @click="closeAlert"
                    class="ml-4 text-gray-600 hover:text-gray-800 cursor-pointer">
                    <font-awesome-icon :icon="['fas', 'times']"/>
                 </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Alert',
    props: {
        status: {
            type: Boolean,
            required: true,
        },
        message: {
            type: String, 
            required: true,
        }
    },
    methods: {
        closeAlert() {
            this.$emit('close-alert')
        }
    },
}
</script>