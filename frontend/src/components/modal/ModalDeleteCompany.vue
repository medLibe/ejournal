<template>
  <Dialog 
    v-bind:visible="isVisible" 
    header="Hapus Perusahaan" 
    :modal="true" 
    :closable="false"
    class="max-w-md w-full">

    <p>Apakah kamu ingin menghapus {{ company?.company_name }}?</p>

    <template #footer>
      <Button label="Batal" icon="pi pi-times" text @click="cancel"/>
      <Button label="Hapus" severity="danger" icon="pi pi-trash" @click="deleteCompany"/>
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

export default {
    name: 'ModalDeleteCompany',
    components: { 
        Dialog,
        Button,
    },
    props: {
        company: {
            type: Object,
            default: null,
        },
        isVisible: {
          type: Boolean,
          required: true,
        }
    },
    emits: ['update:isVisible', 'deleteSuccess'],
    methods: {
        cancel() {
            this.$emit('update:isVisible', false)
        },
        async deleteCompany() {
            if (!this.company?.id) return
            this.isLoading = true

            await this.$api.delete(`${import.meta.env.VITE_API_URL}/company/${this.company.id}`, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                this.$toast.add({
                    severity: 'success',
                    summary: 'Hapus Data Berhasil',
                    detail: response.data.message,
                    life: 3000
                })

                this.$emit('deleteSuccess')
                this.cancel()
            })
            .catch(error => {
                console.error(error)

                // reset
                this.errorMessages = {
                  companyCode: null,
                  companyName: null,
                  companyType: null,
                }

                this.$toast.add({
                    severity: 'error',
                    summary: 'Hapus Data Gagal',
                    detail: error.response ? error.response.data.message : 'An error occurred during fetch data.',
                    life: 3000
                })

                this.$nextTick(() => {
                    document.activeElement.blur()
                })
            })
            .finally(() => {
                this.isLoading = false
            })
        },
    },
}
</script>
