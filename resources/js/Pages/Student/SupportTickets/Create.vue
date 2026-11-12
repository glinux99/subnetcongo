<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    subject: '',
    message: '',
    category_id: null,
    priority: 'medium',
});

const categoryOptions = computed(() => [{ label: t('pages.support_ticket_create.noCategory'), value: null }, ...(props.categories?.map(c => ({ label: c.name, value: c.id })) || [])]);
const priorityOptions = computed(() => [
    { label: t('pages.support_ticket_create.prioLow'), value: 'low' },
    { label: t('pages.support_ticket_create.prioMedium'), value: 'medium' },
    { label: t('pages.support_ticket_create.prioHigh'), value: 'high' },
    { label: t('pages.support_ticket_create.prioUrgent'), value: 'urgent' },
]);

const submit = () => form.post(route('student.support.store'));
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.support_ticket_create.headTitle')" />
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[900px] mx-auto relative z-10">
                    <Link :href="route('student.support.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4">
                        <i class="pi pi-arrow-left"></i> {{ t('pages.support_ticket_create.backLink') }}
                    </Link>
                    <h1 class="text-3xl font-black text-white tracking-tight">{{ t('pages.support_ticket_create.pageTitle') }}</h1>
                    <p class="text-slate-400 text-sm mt-1">{{ t('pages.support_ticket_create.pageSubtitle') }}</p>
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <form @submit.prevent="submit" class="bg-white rounded-2xl shadow border border-slate-100 p-8 space-y-5">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">{{ t('pages.support_ticket_create.fieldSubject') }}</label>
                        <InputText v-model="form.subject" class="w-full !rounded-xl" :placeholder="t('pages.support_ticket_create.subjectPlaceholder')" />
                        <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1">{{ form.errors.subject }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">{{ t('pages.support_ticket_create.fieldCategory') }}</label>
                            <Select v-model="form.category_id" :options="categoryOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">{{ t('pages.support_ticket_create.fieldPriority') }}</label>
                            <Select v-model="form.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">{{ t('pages.support_ticket_create.fieldDescription') }}</label>
                        <Textarea v-model="form.message" rows="6" class="w-full !rounded-xl" :placeholder="t('pages.support_ticket_create.descPlaceholder')" />
                        <p v-if="form.errors.message" class="text-red-500 text-xs mt-1">{{ form.errors.message }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('student.support.index')">
                            <Button :label="t('pages.support_ticket_create.cancel')" outlined class="!rounded-xl" />
                        </Link>
                        <Button type="submit" :label="t('pages.support_ticket_create.submit')" icon="pi pi-send" :loading="form.processing" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl" />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
