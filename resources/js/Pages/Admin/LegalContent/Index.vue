<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    legal: { type: Array, default: () => [] },
    privacy: { type: Array, default: () => [] },
});

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
    });
};

const submitPrivacy = () => {
    const data = Object.entries(privacyForm.privacy).map(([key, value]) => ({ key, value })).filter(i => i.key);
    privacyForm.post(route('admin.legal-content.update', 'privacy'), {
        data,
    });
};
</script>

<template>
    <Head title="Éditeur de contenu légal" />
    <AppLayout>
        <div class="py-10 px-6 lg:px-12">
            <div class="max-w-[1600px] mx-auto space-y-6">
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter">Éditeur de contenu légal</h1>
                <p class="text-slate-500">Modifiez les mentions légales et la politique de confidentialité. Les modifications sont appliquées immédiatement sur le site public.</p>

                <TabView>
                    <TabPanel header="Mentions Légales">
                        <form @submit.prevent="submitLegal" class="mt-6 space-y-6">
                            <Card v-for="item in legal" :key="item.key" class="mb-4">
                                <template #title>{{ item.key.replace('legal_', '').replace('_html', '').replace('_', ' ') }}</template>
                                <template #content>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="legalForm.legal[item.key]" class="w-full" />
                                    <Textarea v-else v-model="legalForm.legal[item.key]" rows="6" class="w-full" autoResize />
                                </template>
                            </Card>
                            <Button type="submit" label="Enregistrer les mentions légales" icon="pi pi-check" :loading="legalForm.processing" />
                        </form>
                    </TabPanel>

                    <TabPanel header="Politique de Confidentialité">
                        <form @submit.prevent="submitPrivacy" class="mt-6 space-y-6">
                            <Card v-for="item in privacy" :key="item.key" class="mb-4">
                                <template #title>{{ item.key.replace('privacy_', '').replace('_html', '').replace('_', ' ') }}</template>
                                <template #content>
                                    <InputText v-if="!item.key.endsWith('_html')" v-model="privacyForm.privacy[item.key]" class="w-full" />
                                    <Textarea v-else v-model="privacyForm.privacy[item.key]" rows="6" class="w-full" autoResize />
                                </template>
                            </Card>
                            <Button type="submit" label="Enregistrer la confidentialité" icon="pi pi-check" :loading="privacyForm.processing" />
                        </form>
                    </TabPanel>
                </TabView>
            </div>
        </div>
    </AppLayout>
</template>
