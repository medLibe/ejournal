<template>
    <Dialog 
        v-bind:visible="isVisible" 
        header="Filter Buku Besar" 
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
            class="flex flex-col justify-center gap-4 p-4">

            <div class="mb-4">
                <FloatLabel variant="on">
                    <DatePicker 
                        v-model="formData.startDate"
                        inputId="datePeriode" 
                        showIcon 
                        iconDisplay="input"
                        class="w-full"
                        :maxDate="today" />
                    <label for="datePeriode">Tanggal Awal</label>
                </FloatLabel>
                <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message>
            </div>

            <div class="mb-4">
                <FloatLabel variant="on">
                    <DatePicker 
                        v-model="formData.endDate"
                        inputId="datePeriode" 
                        showIcon 
                        iconDisplay="input"
                        class="w-full"
                        :maxDate="today" />
                    <label for="datePeriode">Tanggal Akhir</label>
                </FloatLabel>
                <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message>
            </div>

            <div class="mb-4">
                <FloatLabel variant="on">
                    <Select 
                        filter
                        v-model="formData.selectedAccount"
                        :options="filteredAccounts" 
                        optionLabel="account_name" fluid
                        class="w-full" />
                    <label for="selectedAccount">Pilih Akun</label>
                </FloatLabel>
                <!-- <Message v-if="$form.date?.invalid" severity="error" size="small" variant="simple">{{
                    $form.date.error?.message }}</Message> -->
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
import Select from 'primevue/select'

export default {
    name: 'ModalLedger',
    components: {
        Dialog,
        Button,
        Message,
        Form,
        FloatLabel,
        DatePicker,
        Select,
    },
    props: {
        isVisible: {
            type: Boolean,
            required: true
        },
    },
    inject: ['showLoader' , 'hideLoader'],
    emits: ['fetchLedgers', 'update:isVisible'],
    data() {
        return {
            responseMessage: null,
            showMacOptions: false,
            today: new Date(),
            allAccounts: [],
            filteredAccounts: [],
            division: null,
            formData: {
                startDate: null,
                endDate: null,
                division: null,
            },
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
        async fetchAccounts() {
            this.showLoader()
            try {
                const response = await this.$api.get(`${import.meta.env.VITE_API_URL}/report/accounts`, {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem('accessToken')}`,
                    }
                })
                this.allAccounts = response.data.data
                this.filteredAccounts = this.filterSelectableAccounts(this.allAccounts)
            } catch (error) {
                console.error(error)
            } finally {
                this.hideLoader()
            }
        },
        filterSelectableAccounts(accounts) {
            return accounts.filter(account => {
                if (!account.children || account.children.length === 0) {
                    return true
                }
                return account.children.every(child => !child.children)
            })
        },
        // on submit action
        async onSubmitFilter() {
            const formatDate = (date) => {
                const localDate = new Date(date)
                return new Date(localDate.getTime() - localDate.getTimezoneOffset() * 60000).toISOString().split("T")[0]
            }
            const params = {
                accountId: this.formData.selectedAccount?.id || null,
                startDate: formatDate(this.formData.startDate),
                endDate: formatDate(this.formData.endDate),
                division: this.formData.division?.value ?? null,
            }

            this.showLoader()

            this.$api.get(`${import.meta.env.VITE_API_URL}/report/ledger`, {
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
                        this.$emit('fetchLedgers', response.data.data.ledgers, response.data.data.totals)

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
                        detail: error.response ? error.response.data.message : 'An error occurred during import.',
                    }
                    this.$toast.add({
                        severity: severity,
                        summary: 'Filter Data Gagal',
                        detail: error.response ? error.response.data.message : 'An error occurred during import.',
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
    },
    mounted() {
        this.formData.startDate = new Date(new Date().getFullYear(), new Date().getMonth(), 1)
        this.formData.endDate = this.today
        this.fetchAccounts()

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
    }
}
</script>