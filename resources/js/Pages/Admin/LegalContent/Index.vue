<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    legal: { type: Array, default: () => [] },
    privacy: { type: Array, default: () => [] },
});

const toast = useToast();

const legalForm = useForm({
    legal: Object.fromEntries(props.legal.map(r => [r.key, r.value || ''])),
});

const privacyForm = useForm({
    privacy: Object.fromEntries(props.privacy.map(r => [r.key, r.value || ''])),
});

const submitLegal = () => {
    const data = Object.entries(legalForm.legal).map(([key, value]) => ({ key, value })).filter(i => i.key);
    legalForm.transform(() => data).put(route('admin.legal-content.update', 'legal'), {
        preserveScroll: true,
        onError: () => toast.add({ severity: 'error', summary: t('pages.admin_legal_content.toast_error_summary'), detail: t('pages.admin_legal_content.toast_error_detail'), life: 4000 }),
    });
};

const submitPrivacy = () => {
    const data = Object.entries(privacyForm.privacy).map(([key, value]) => ({ key, value })).filter(i => i.key);
    privacyForm.transform(() => data).put(route('admin.legal-content.update', 'privacy'), {
        preserveScroll: true,
        onError: () => toast.add({ severity: 'error', summary: t('pages.admin_legal_content.toast_error_summary'), detail: t('pages.admin_legal_content.toast_error_detail'), life: 4000 }),
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_legal_content.page_title')" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ -->
            <PageHeader
                :title="t('pages.admin_legal_content.header_title')"
                :subtitle="t('pages.admin_legal_content.header_subtitle')"
                icon="pi-file-edit"
                :badge="t('pages.admin_legal_content.header_badge')"
                accent="blue"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <div class="page-card-flush p-6">
                    <TabView class="custom-tabview">
                        <TabPanel :header="t('pages.admin_legal_content.tab_legal')">
                            <form @submit.prevent="submitLegal" class="mt-6 space-y-6">
                                <div v-for="item in legal" :key="item.key" class="space-y-2 p-4 bg-slate-50/70 border border-slate-200 rounded-xl">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-700">
                                        {{ item.key.replace('legal_', '').replace('_html', '').replace('_', ' ') }}
                                    </label>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="legalForm.legal[item.key]" class="w-full !rounded-xl" />
                                    <Textarea v-else v-model="legalForm.legal[item.key]" rows="6" class="w-full !rounded-xl" autoResize />
                                </div>
                                <div class="pt-2">
                                    <Button type="submit" :label="t('pages.admin_legal_content.btn_save_legal')" icon="pi pi-check" :loading="legalForm.processing" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                                </div>
                            </form>
                        </TabPanel>

                        <TabPanel :header="t('pages.admin_legal_content.tab_privacy')">
                            <form @submit.prevent="submitPrivacy" class="mt-6 space-y-6">
                                <div v-for="item in privacy" :key="item.key" class="space-y-2 p-4 bg-slate-50/70 border border-slate-200 rounded-xl">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-700">
                                        {{ item.key.replace('privacy_', '').replace('_html', '').replace('_', ' ') }}
                                    </label>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="privacyForm.privacy[item.key]" class="w-full !rounded-xl" />
                                    <Textarea v-else v-model="privacyForm.privacy[item.key]" rows="6" class="w-full !rounded-xl" autoResize />
                                </div>
                                <div class="pt-2">
                                    <Button type="submit" :label="t('pages.admin_legal_content.btn_save_privacy')" icon="pi pi-check" :loading="privacyForm.processing" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                                </div>
                            </form>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
