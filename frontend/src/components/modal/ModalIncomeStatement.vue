<template>
    <Dialog 
        v-bind:visible="isVisible" 
        header="Filter Laba Rugi" 
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
                        v-model="formData.startDate"
                        inputId="startDate" 
                        showIcon 
                        iconDisplay="input"
                        class="w-full"
                        :maxDate="today" />
                    <label for="startDate">Tanggal Mulai</label>
                </FloatLabel>
                <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message>
            </div>

            <div class="mb-4">
                <FloatLabel variant="on">
                    <DatePicker 
                        v-model="formData.endDate"
                        inputId="endDate" 
                        showIcon 
                        iconDisplay="input"
                        class="w-full"
                        :maxDate="today" />
                    <label for="endDate">Tanggal Akhir</label>
                </FloatLabel>
                <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message>
            </div>

            <div class="mb-4" v-if="showMacOptions">
                <FloatLabel variant="on">
                    <Select 
                        v-model="formData.division" 
                        :options="[
                            { label: 'MAC Product', value: 'MAC' },
                            { label: 'MAC Flushing', value: 'FLH' }
                        ]"
                        optionLabel="label" 
                        class="w-full"
                    />
                    <label for="over_label">Divisi</label>
                </FloatLabel>
                <Message v-if="$form.division?.invalid" severity="error" size="small" variant="simple">{{
                    $form.division.error?.message }}</Message>
            </div>

            <!-- <div class="card flex flex-col gap-4">
                <label for="">Parameter:</label>
                <div class="flex items-center gap-2">
                    <Checkbox 
                        binary 
                        v-model="formData.viewTotal" 
                        inputId="view_total"
                        @change="handleViewTotalChange"
                        disabled />
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
            </div> -->
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
import Select from 'primevue/select'

export default {
    name: 'ModalBalanceSheetStandard',
    components: {
        Dialog,
        Button,
        Message,
        Form,
        FloatLabel,
        DatePicker,
        Checkbox,
        Select,
    },
    props: {
        isVisible: {
            type: Boolean,
            required: true
        },
    },
    inject: ['showLoader' , 'hideLoader'],
    emits: ['fetchIncomeStatements', 'update:isVisible'],
    data() {
        return {
            responseMessage: null,
            today: new Date(),
            division: null,
            showMacOptions: false,
            // data model form
            formData: {
                startDate: null,
                endDate: null,
                viewTotal: true,
                viewParent: false,
                viewChildren: false,
                division: null,
            }
        }
    },
    mounted()  {
        try {
            const encodedDb = localStorage.getItem('db')
            if (encodedDb) {
                const decodeDb = atob(encodedDb)
                if (decodeDb === '1') {
                    this.showMacOptions = true
                }
            }
        } catch (error) {
            console.error(error)
            this.$toast.add({
                severity: severity,
                summary: 'Decode DB Gagal.',
                detail: error.response ? error.response.data.message : 'An error occurred during filter.',
                life: 3000
            })
        }
    },
    methods: {
        onCancel() {
            this.$emit('update:isVisible', false)
            // this.previewData = []
        },
        resolver({ values }) {
            const errors = { 
                startDate: [],
                endDate: [] 
            }

            if (!values.startDate || !values.endDate) {
                errors.startDate.push({ type: 'required', message: 'Tanggal mulai wajib diisi.' })
                errors.endDate.push({ type: 'required', message: 'Tanggal akhir wajib diisi.' })
            }

            return { errors }
        },
        // on submit action
        async onSubmitFilter() {
            const formatDate = (date) => {
                if (!date) return null;
                const d = new Date(date);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            }

            const startDate = formatDate(this.formData.startDate)
            const endDate = formatDate(this.formData.endDate)

            const params = {
                startDate: startDate,
                endDate: endDate,
                viewTotal: this.formData.viewTotal,
                viewParent: this.formData.viewParent,
                viewChildren: this.formData.viewChildren,
                division: this.formData.division?.value ?? null,
            }

            this.showLoader()
            
            this.$api.get(`${import.meta.env.VITE_API_URL}/report/income-statement`, {
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
                        this.$emit('fetchIncomeStatements', {
                            data: response.data.data.data,
                            summary: response.data.data.summary,
                            viewTotal: this.formData.viewTotal,
                            viewParent: this.formData.viewParent,
                            viewChildren: this.formData.viewChildren
                        })

                        // reset form
                        const resetForm = {
                            startDate: null,
                            endDate: null,
                        }

                        if ('division' in this.formData) {
                            resetForm.division = null
                        }

                        this.formData = resetForm

                        this.onCancel()
                        
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