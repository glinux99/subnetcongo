<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageCard from '@/Components/PageCard.vue';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

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
    legalForm.post(route('admin.legal-content.update', 'legal'), {
        data,
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Mentions légales enregistrées.', life: 3000 }),
    });
};

const submitPrivacy = () => {
    const data = Object.entries(privacyForm.privacy).map(([key, value]) => ({ key, value })).filter(i => i.key);
    privacyForm.post(route('admin.legal-content.update', 'privacy'), {
        data,
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Politique de confidentialité enregistrée.', life: 3000 }),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Éditeur de contenu légal" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ -->
            <PageHeader
                title="Contenu Légal & Conformité"
                subtitle="Modifiez les mentions légales et la politique de confidentialité du site public."
                icon="pi-file-edit"
                badge="Conformité"
                accent="blue"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <div class="page-card-flush p-6">
                    <TabView class="custom-tabview">
                        <TabPanel header="Mentions Légales">
                            <form @submit.prevent="submitLegal" class="mt-6 space-y-6">
                                <div v-for="item in legal" :key="item.key" class="space-y-2 p-4 bg-slate-50/70 border border-slate-200 rounded-xl">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-700">
                                        {{ item.key.replace('legal_', '').replace('_html', '').replace('_', ' ') }}
                                    </label>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="legalForm.legal[item.key]" class="w-full !rounded-xl" />
                                    <Textarea v-else v-model="legalForm.legal[item.key]" rows="6" class="w-full !rounded-xl" autoResize />
                                </div>
                                <div class="pt-2">
                                    <Button type="submit" label="Enregistrer les mentions légales" icon="pi pi-check" :loading="legalForm.processing" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                                </div>
                            </form>
                        </TabPanel>

                        <TabPanel header="Politique de Confidentialité">
                            <form @submit.prevent="submitPrivacy" class="mt-6 space-y-6">
                                <div v-for="item in privacy" :key="item.key" class="space-y-2 p-4 bg-slate-50/70 border border-slate-200 rounded-xl">
                                    <label class="block text-xs font-black uppercase tracking-widest text-slate-700">
                                        {{ item.key.replace('privacy_', '').replace('_html', '').replace('_', ' ') }}
                                    </label>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="privacyForm.privacy[item.key]" class="w-full !rounded-xl" />
                                    <Textarea v-else v-model="privacyForm.privacy[item.key]" rows="6" class="w-full !rounded-xl" autoResize />
                                </div>
                                <div class="pt-2">
                                    <Button type="submit" label="Enregistrer la politique de confidentialité" icon="pi pi-check" :loading="privacyForm.processing" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                                </div>
                            </form>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
