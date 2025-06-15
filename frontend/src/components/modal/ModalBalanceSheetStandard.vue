<template>
    <Dialog 
        v-bind:visible="isVisible" 
        header="Filter Neraca" 
        :modal="true" 
        :closable="false"
        class="max-w-sm w-full">
        <Message 
            :icon="responseMessage.severity === 'success' ? 'pi pi-check-circle' : 'pi pi-times-circle'"closable
            v-if="responseMessage" :severity="responseMessage.severity" class="mt-4">
            {{ responseMessage.detail }}
        </Message>
        <Form 
            v-slot="$form" 
            :initialValues="formData" 
            :resolver="resolver"
            @submit="onSubmitFilter"
            class="flex flex-col justify-center gap-4 p-4">

            <div class="mb-4">

                <FloatLabel variant="on">
                    <DatePicker 
                        v-model="formData.datePeriode"
                        inputId="datePeriode" 
                        showIcon 
                        iconDisplay="input"
                        class="w-full"
                        :maxDate="today" />
                    <label for="datePeriode">Per Tanggal</label>
                </FloatLabel>
                <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message>
            </div>

            <div class="card flex flex-col gap-4">
                <label for="">Parameter:</label>
                <div class="flex items-center gap-2">
                    <Checkbox 
                        binary 
                        v-model="formData.viewTotal" 
                        inputId="view_total"
                        @change="handleViewTotalChange"
                        :disabled="formData.viewParent || formData.viewChildren" />
                    <label for="view_total">Tampilkan Total</label>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox 
                        binary 
                        v-model="formData.viewParent" 
                        inputId="view_parent"
                        @change="handleParentChange"
                        :disabled="formData.viewTotal" />
                    <label for="view_parent">Tampilkan Induk</label>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox 
                        binary 
                        v-model="formData.viewChildren" 
                        inputId="view_children"
                        @change="handleChildrenChange"
                        :disabled="formData.viewTotal" />
                    <label for="view_children">Tampilkan Anak</label>
                </div>
            </div>
        </Form>

        <template #footer>
            <Button 
                label="Batal" 
                icon="pi pi-times" 
                text 
                @click="onCancel" />
            <Button 
                label="Terapkan" 
                severity="primary" 
                icon="pi pi-filter"
                @click="onSubmitFilter" />
        </template>
    </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { Form } from '@primevue/forms'
import FloatLabel from 'primevue/floatlabel'
import DatePicker from 'primevue/datepicker'
import Checkbox from 'primevue/checkbox'

export default {
    name: 'ModalBalanceSheetStandard',
    components: {
        Dialog,
        Button,
        Message,
        Form,
        FloatLabel,
        DatePicker,
        Checkbox
    },
    props: {
        isVisible: {
            type: Boolean,
            required: true
        },
    },
    inject: ['showLoader' , 'hideLoader'],
    emits: ['fetchBalanceSheets', 'update:isVisible'],
    data() {
        return {
            responseMessage: null,
            today: new Date(),
            // data model form
            formData: {
                datePeriode: null,
                viewTotal: false,
                viewParent: false,
                viewChildren: false,
            }
        }
    },
    methods: {
        onCancel() {
            this.$emit('update:isVisible', false)
            // this.previewData = []
        },
        resolver({ values }) {
            const errors = { datePeriode: [] }

            if (!values.datePeriode) {
                errors.datePeriode.push({ type: 'required', message: 'Tanggal periode wajib diisi.' })
            }

            return { errors }
        },
        // on submit action
        async onSubmitFilter() {
            const datePeriode = this.formatDate(this.formData.datePeriode)
            
            const params = {
                datePeriode: datePeriode,
                viewTotal: this.formData.viewTotal,
                viewParent: this.formData.viewParent,
                viewChildren: this.formData.viewChildren,
            }

            this.showLoader()

            
            this.$api.get(`${import.meta.env.VITE_API_URL}/report/balance-sheet`, {
                    params,
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`,
                    }
                })
                .then(response => {
                    if (response.data.status) {

                        this.responseMessage = {
                            severity: 'success',
                            detail: 'Filter berhasil, data ditampilkan.',
                        }
                        this.$toast.add({
                            severity: 'success',
                            summary: 'Filter Data Berhasil.',
                            detail: response.data.message,
                            life: 3000
                        })

                        // emit data
                        this.$emit('fetchBalanceSheets', {
                            data: response.data.data,
                            viewTotal: this.formData.viewTotal,
                            viewParent: this.formData.viewParent,
                            viewChildren: this.formData.viewChildren
                        })
                        
                        this.$nextTick(() => {
                            document.activeElement.blur()
                        })
                    }
                })
                .catch(error => {
                    console.error(error)

                    let severity;
                    if (error.response.status === 400)
                    {
                        severity = 'warn'
                    }
                    this.responseMessage = {
                        severity: severity,
                        detail: error.response ? error.response.data.message : 'An error occurred during filter.',
                    }
                    this.$toast.add({
                        severity: severity,
                        summary: 'Filter Data Gagal',
                        detail: error.response ? error.response.data.message : 'An error occurred during filter.',
                        life: 3000
                    })

                    this.$nextTick(() => {
                        document.activeElement.blur()
                    })
                })
                .finally(() => {
                    this.hideLoader()
                })
        },
        // handle change on ViewTotal
        handleViewTotalChange() {
            if (this.formData.viewTotal) {
                this.formData.viewParent = false
                this.formData.viewChildren = false
            }
        },
        // automatically check Tampilkan Induk when Tampilkan Anak is checked
        handleChildrenChange() {
            if (this.formData.viewChildren) {
                this.formData.viewParent = true
            }
        },
        // handle Tamppilkan Induk change
        handleParentChange() {
            if (!this.formData.viewParent) {
                this.formData.viewChildren = false
            }
        },
        formatDate(date) {
            if (!date) return null
            const d = new Date(date)
            const year = d.getFullYear()
            const month = (d.getMonth() + 1).toString().padStart(2, '0')
            const day = d.getDate().toString().padStart(2, '0')
            return `${year}-${month}-${day}` // YYYY-MM-DD
        }
    },
    watch: {
        'formData.viewTotal': function(newValue) {
            if (newValue) {
                this.formData.viewParent = false
                this.formData.viewChildren = false
            }
        }
    }
}
</script>