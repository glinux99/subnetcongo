<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    settings: Object,
    has_secret: Object,
    configured: Object,
});

const toast = useToast();
const testing = ref(null);

const form = useForm({
    default_provider: props.settings.default_provider ?? 'livekit',
    livekit_url: props.settings.livekit_url ?? '',
    livekit_ws_url: props.settings.livekit_ws_url ?? '',
    livekit_api_key: props.settings.livekit_api_key ?? '',
    livekit_api_secret: '',
    zoom_account_id: props.settings.zoom_account_id ?? '',
    zoom_client_id: props.settings.zoom_client_id ?? '',
    zoom_client_secret: '',
    google_client_id: props.settings.google_client_id ?? '',
    google_client_secret: '',
    google_refresh_token: '',
    google_calendar_id: props.settings.google_calendar_id ?? 'primary',
    bbb_base_url: props.settings.bbb_base_url ?? '',
    bbb_secret: '',
    jitsi_base_url: props.settings.jitsi_base_url ?? 'https://meet.jit.si',
});

const providerOptions = [
    { label: 'LiveKit (intégré à la plateforme)', value: 'livekit' },
    { label: 'Zoom', value: 'zoom' },
    { label: 'Google Meet', value: 'meet' },
    { label: 'BigBlueButton', value: 'bbb' },
    { label: 'Jitsi Meet', value: 'jitsi' },
];

const submit = () => {
    form.post(route('system.settings.video.update'), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Configuration enregistrée', detail: 'Les fournisseurs sont mis à jour.', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les champs en surbrillance.', life: 4000 }),
    });
};

const testProvider = async (provider) => {
    testing.value = provider;
    try {
        const { data } = await window.axios.post(route('system.settings.video.test', provider));
        toast.add({
            severity: data.success ? 'success' : 'error',
            summary: data.success ? 'Connexion réussie' : 'Échec de connexion',
            detail: data.message,
            life: 5000,
        });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Test impossible : ' + (e?.response?.data?.message ?? e.message), life: 5000 });
    } finally {
        testing.value = null;
    }
};

const badge = (provider) => (props.configured?.[provider] ? 'Configuré' : 'Non configuré');
const severity = (provider) => (props.configured?.[provider] ? 'success' : 'warn');
</script>

<template>
    <AppLayout>
        <Head title="Fournisseurs de visioconférence" />
        <Toast />

        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="max-w-[1400px] mx-auto relative z-10 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Configuration Système</p>
                        <h1 class="text-3xl font-black text-white tracking-tight">Visioconférence</h1>
                        <p class="text-slate-400 text-sm mt-1">Clés API et serveurs utilisés pour les classes virtuelles.</p>
                    </div>
                    <Link :href="route('system.settings')">
                        <Button label="Paramètres généraux" icon="pi pi-arrow-left" outlined class="!text-white !border-white/30" />
                    </Link>
                </div>
            </div>

            <div class="max-w-[1400px] mx-auto px-4 lg:px-6 -mt-6 relative z-20 space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Fournisseur par défaut</label>
                    <Select v-model="form.default_provider" :options="providerOptions" optionLabel="label" optionValue="value" class="w-full md:w-96" />
                    <p class="text-xs text-slate-500 mt-2">LiveKit est le seul fournisseur entièrement intégré : la salle s'affiche directement dans la plateforme.</p>
                    <p v-if="form.errors.default_provider" class="text-xs text-red-500 mt-1">{{ form.errors.default_provider }}</p>
                </div>

                <!-- LIVEKIT -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center"><i class="pi pi-camera"></i></div>
                            <div>
                                <h2 class="font-black text-slate-800">LiveKit (recommandé)</h2>
                                <p class="text-xs text-slate-500">Visioconférence native, salon intégré à la plateforme.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="badge('livekit')" :severity="severity('livekit')" class="text-[9px] uppercase" />
                            <Button label="Tester" icon="pi pi-bolt" size="small" outlined :loading="testing === 'livekit'" @click="testProvider('livekit')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">URL API (https)</label>
                            <InputText v-model="form.livekit_url" class="w-full" placeholder="https://votre-projet.livekit.cloud" />
                            <p v-if="form.errors.livekit_url" class="text-xs text-red-500 mt-1">{{ form.errors.livekit_url }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">URL WebSocket (wss)</label>
                            <InputText v-model="form.livekit_ws_url" class="w-full" placeholder="wss://votre-projet.livekit.cloud" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">API Key</label>
                            <InputText v-model="form.livekit_api_key" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">API Secret</label>
                            <Password v-model="form.livekit_api_secret" :feedback="false" toggleMask class="w-full" inputClass="w-full"
                                      :placeholder="has_secret?.livekit_api_secret ? '•••••••• (déjà enregistré)' : 'Secret LiveKit'" />
                        </div>
                    </div>
                </div>

                <!-- ZOOM -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center"><i class="pi pi-video"></i></div>
                            <div>
                                <h2 class="font-black text-slate-800">Zoom</h2>
                                <p class="text-xs text-slate-500">Application Server-to-Server OAuth.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="badge('zoom')" :severity="severity('zoom')" class="text-[9px] uppercase" />
                            <Button label="Tester" icon="pi pi-bolt" size="small" outlined :loading="testing === 'zoom'" @click="testProvider('zoom')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Account ID</label>
                            <InputText v-model="form.zoom_account_id" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Client ID</label>
                            <InputText v-model="form.zoom_client_id" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Client Secret</label>
                            <Password v-model="form.zoom_client_secret" :feedback="false" toggleMask class="w-full" inputClass="w-full"
                                      :placeholder="has_secret?.zoom_client_secret ? '•••••••• (déjà enregistré)' : 'Client Secret'" />
                        </div>
                    </div>
                </div>

                <!-- GOOGLE MEET -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center"><i class="pi pi-google"></i></div>
                            <div>
                                <h2 class="font-black text-slate-800">Google Meet</h2>
                                <p class="text-xs text-slate-500">Compte de service / OAuth avec refresh token.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="badge('meet')" :severity="severity('meet')" class="text-[9px] uppercase" />
                            <Button label="Tester" icon="pi pi-bolt" size="small" outlined :loading="testing === 'meet'" @click="testProvider('meet')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Client ID</label>
                            <InputText v-model="form.google_client_id" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Client Secret</label>
                            <Password v-model="form.google_client_secret" :feedback="false" toggleMask class="w-full" inputClass="w-full"
                                      :placeholder="has_secret?.google_client_secret ? '•••••••• (déjà enregistré)' : 'Client Secret'" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Refresh Token</label>
                            <Password v-model="form.google_refresh_token" :feedback="false" toggleMask class="w-full" inputClass="w-full"
                                      :placeholder="has_secret?.google_refresh_token ? '•••••••• (déjà enregistré)' : 'Refresh token OAuth'" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Calendrier</label>
                            <InputText v-model="form.google_calendar_id" class="w-full" placeholder="primary" />
                        </div>
                    </div>
                </div>

                <!-- BBB -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center"><i class="pi pi-users"></i></div>
                            <div>
                                <h2 class="font-black text-slate-800">BigBlueButton</h2>
                                <p class="text-xs text-slate-500">Serveur auto-hébergé (recommandé pour la souveraineté des données).</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="badge('bbb')" :severity="severity('bbb')" class="text-[9px] uppercase" />
                            <Button label="Tester" icon="pi pi-bolt" size="small" outlined :loading="testing === 'bbb'" @click="testProvider('bbb')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">URL du serveur</label>
                            <InputText v-model="form.bbb_base_url" class="w-full" placeholder="https://bbb.votre-domaine.com" />
                            <p v-if="form.errors.bbb_base_url" class="text-xs text-red-500 mt-1">{{ form.errors.bbb_base_url }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">Secret partagé</label>
                            <Password v-model="form.bbb_secret" :feedback="false" toggleMask class="w-full" inputClass="w-full"
                                      :placeholder="has_secret?.bbb_secret ? '•••••••• (déjà enregistré)' : 'Secret BBB'" />
                        </div>
                    </div>
                </div>

                <!-- JITSI -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-700 text-white flex items-center justify-center"><i class="pi pi-comments"></i></div>
                            <div>
                                <h2 class="font-black text-slate-800">Jitsi Meet</h2>
                                <p class="text-xs text-slate-500">Instance publique ou auto-hébergée.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag :value="badge('jitsi')" :severity="severity('jitsi')" class="text-[9px] uppercase" />
                            <Button label="Tester" icon="pi pi-bolt" size="small" outlined :loading="testing === 'jitsi'" @click="testProvider('jitsi')" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1 block">URL du serveur</label>
                        <InputText v-model="form.jitsi_base_url" class="w-full" placeholder="https://meet.jit.si" />
                        <p v-if="form.errors.jitsi_base_url" class="text-xs text-red-500 mt-1">{{ form.errors.jitsi_base_url }}</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button label="Enregistrer la configuration" icon="pi pi-check" :loading="form.processing" @click="submit"
                            class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 py-3" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
