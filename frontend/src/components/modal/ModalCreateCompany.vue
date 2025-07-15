<template>
  <Dialog 
    v-bind:visible="isVisible" 
    header="Tambah Perusahaan" 
    :modal="true" 
    class="max-w-sm w-full">
    
    <div class="p-4">
      <div class="mb-4">
        <FloatLabel variant="on">
          <InputText 
              v-model="formData.companyCode" 
              inputId="companyCode" 
              class="w-full"/>
              <label for="companyCode">Kode Perusahaan</label>
        </FloatLabel>
        <Message 
            v-if="errorMessages.companyCode" 
            :severity="'error'"
            variant="simple" 
            class="mb-4">
            {{ errorMessages.companyCode }}
        </Message>
      </div>

      <div class="mb-4">
        <FloatLabel variant="on">
          <InputText 
              v-model="formData.companyName" 
              inputId="companyName" 
              class="w-full"/>
              <label for="companyName">Nama Perusahaan</label>
        </FloatLabel>
        <Message 
            v-if="errorMessages.companyName" 
            :severity="'error'"
            variant="simple" 
            class="mb-4">
            {{ errorMessages.companyName }}
        </Message>
      </div>

      <div class="mb-4">
        <FloatLabel variant="on">
        <Select
              v-model="formData.companyType"
              :options="companyTypes" 
              optionLabel="company_type"
              fluid
              class="w-full" />
            <label for="companyType">Tipe</label>
      </FloatLabel>
      <Message 
            v-if="errorMessages.companyType" 
            :severity="'error'"
            variant="simple" 
            class="mb-4">
            {{ errorMessages.companyType }}
        </Message>
      </div>
    </div>

    <template #footer>
      <Button label="Batal" icon="pi pi-times" text @click="cancel"/>
      <Button label="Simpan" icon="pi pi-plus" @click="createCompany"/>
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Message from 'primevue/message'

export default {
    name: 'ModalCreateCompany',
    components: { 
        Dialog, 
        FloatLabel, 
        InputText, 
        Button,
        Select,
        Message,
    },
    props: {
        isVisible: {
          type: Boolean,
          required: true,
        }
    },
    emits: ['update:isVisible', 'createCompany'],
    data() {
        return {
          formData: {
              companyCode: null,
              companyName: null,
              companyType: null,
          },
          companyTypes: [
            {"id": 1, "company_type": "Supplier"},
            {"id": 2, "company_type": "Customer"},
          ],
          errorMessages: {
            companyCode: null,
            companyName: null,
            companyType: null,
          },
        }
    },
    methods: {
        cancel() {
            this.$emit('update:isVisible', false)
        },
        async createCompany() {
            this.isLoading = true

            const payload = {
                company_code: this.formData.companyCode,
                company_name: this.formData.companyName,
                company_type: this.formData.companyType?.id ?? null,
            }

            await this.$api.post(`${import.meta.env.VITE_API_URL}/company`, payload, {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('accessToken')}`
                }
            })
            .then(response => {
                this.$toast.add({
                    severity: 'success',
                    summary: 'Tambah Data Berhasil',
                    detail: response.data.message,
                    life: 3000
                })

                this.$emit('createCompany')
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

                if (error.response && error.response.data?.error) {
                  const errors = error.response.data.error

                  this.errorMessages.companyCode = errors.company_code?.[0] ?? null
                  this.errorMessages.companyName = errors.company_name?.[0] ?? null
                  this.errorMessages.companyType = errors.company_type?.[0] ?? null
                }

                this.$toast.add({
                    severity: 'error',
                    summary: 'Tambah Data Gagal',
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
    watch: {
      isVisible(val) {
        if (val) {
          this.errorMessages = {
            companyCode: null,
            companyName: null,
            companyType: null,
          }
        }
      }
    }
}
</script>
