<template>
  <Dialog 
    v-bind:visible="isVisible" 
    header="Filter Tanggal" 
    :modal="true" 
    class="max-w-sm w-full">
    
    <div class="p-4">
      <FloatLabel variant="on" class="mb-4">
        <DatePicker 
            v-model="form.startDate" 
            inputId="startDate" 
            showIcon 
            iconDisplay="input" 
            class="w-full"/>
            <label for="startDate">Tanggal Awal</label>
      </FloatLabel>

      <FloatLabel variant="on">
        <DatePicker 
            v-model="form.endDate" 
            inputId="endDate" 
            showIcon 
            iconDisplay="input" 
            class="w-full"/>
            <label for="endDate">Tanggal Akhir</label>
      </FloatLabel>
    </div>

    <template #footer>
      <Button label="Batal" icon="pi pi-times" text @click="cancel"/>
      <Button label="Terapkan" icon="pi pi-filter" @click="applyFilter"/>
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog'
import FloatLabel from 'primevue/floatlabel'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'

export default {
    name: 'ModalFilterGeneralLedgerVoucherDetail',
    components: { 
        Dialog, 
        FloatLabel, 
        DatePicker, 
        Button 
    },
    props: {
        isVisible: {
          type: Boolean,
          required: true,
        }
    },
    emits: ['update:isVisible', 'submitFilter'],
    data() {
        return {
        form: {
            startDate: null,
            endDate: null
        }
        }
    },
    methods: {
        cancel() {
            this.$emit('update:isVisible', false)
        },
        applyFilter() {
            if (!this.form.startDate || !this.form.endDate) return
            const payload = {
                startDate: this.formatDate(this.form.startDate),
                endDate: this.formatDate(this.form.endDate)
            }

            this.$emit('submitFilter', payload)
            this.$emit('update:isVisible', false)
        },
        formatDate(date) {
            if (!date) return null
            const d = new Date(date)
            const year = d.getFullYear()
            const month = (d.getMonth() + 1).toString().padStart(2, '0')
            const day = d.getDate().toString().padStart(2, '0')
            return `${year}-${month}-${day}` // YYYY-MM-DD
        }
    }
}
</script>
