<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';

const props = defineProps({
    sessions: Object,
    kpis: Object,
    filters: Object,
    meta: Object,
});

const search = ref(props.filters?.search ?? '');
const provider = ref(props.filters?.provider ?? null);
const status = ref(props.filters?.status ?? null);

const providers = props.meta?.providers ?? [];
const configuredProviders = props.meta?.configured_providers ?? [];
const courses = props.meta?.courses ?? [];
const instructors = props.meta?.instructors ?? [];

const isProviderConfigured = (providerValue) => configuredProviders.includes(providerValue);

const showCreate = ref(false);
const form = ref({
    title: '',
    course_id: null,
    instructor_id: null,
    start_at: '',
    duration: 60,
    provider: configuredProviders[0] || 'zoom',
    max_participants: 100,
    is_recorded: true,
    passcode: '',
});
const submitting = ref(false);

const providerLabel = (p) => {
    const found = providers.find(pr => pr.value === p);
    return found ? found.label : p;
};

const statusSeverity = (s) => ({
    'Scheduled': 'info', 'Live': 'success', 'Ended': 'secondary', 'Cancelled': 'danger'
}[s] ?? 'secondary');

const providerColor = (p) => ({
    zoom: 'bg-blue-500', meet: 'bg-emerald-500', bbb: 'bg-indigo-600', jitsi: 'bg-slate-700', livekit: 'bg-rose-500'
}[p] ?? 'bg-slate-500');

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const search_ = () => {
    router.get(route('live-sessions.index'), {
        search: search.value, provider: provider.value, status: status.value,
    }, { preserveState: true, replace: true });
};

const submit = () => {
    submitting.value = true;
    router.post(route('live-sessions.store'), form.value, {
        onSuccess: () => { showCreate.value = false; submitting.value = false; form.value = { title: '', course_id: null, instructor_id: null, start_at: '', duration: 60, provider: configuredProviders[0] || 'zoom', max_participants: 100, is_recorded: true, passcode: '' }; },
        onError: () => { submitting.value = false; },
    });
};

const startSession = (id) => router.post(route('live-sessions.start', id), {}, { preserveScroll: true });
const cancelSession = (id) => {
    if (confirm('Annuler cette session ?')) router.post(route('live-sessions.cancel', id));
};
const destroySession = (id) => {
    if (confirm('Supprimer définitivement ?')) router.delete(route('live-sessions.destroy', id));
};
const syncRecordings = (id) => router.post(route('live-sessions.sync-recordings', id), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head title="Sessions en direct" />
       <div class="flex flex-col gap-2 mb-12">
        
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm px-1 mb-2">
            <Breadcrumb 
                :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Sessions en direct' }]" 
                class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
            />
        </div>

        <!-- Bannière Sombre avec padding bas (pb-28) pour le chevauchement -->
        <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
            
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                
                <!-- Partie Gauche (Titres) -->
                <div class="space-y-4 max-w-3xl">
                    <!-- Badge Certifications -->
                    <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                        Live Classroom
                    </span>

                    <!-- Titre Principal (Italique, gras, 2 couleurs) -->
                    <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                        <span class="text-white">Sessions</span>
                        <span class="text-[#E92B2B]">en direct</span>
                    </h1>

                    <!-- Sous-titre -->
                    <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                        Planifiez et gérez vos classes virtuelles Zoom, Meet, BBB ou Jitsi. Diffusez vos cours en temps réel et centralisez l'ensemble de vos replays.
                    </p>
                </div>

                <!-- Partie Droite (Boutons) -->
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <Button 
                        label="Actualiser" 
                        icon="pi pi-sync" 
                        class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                        @click="search_" 
                    />
                    <Button 
                        label="Planifier une session" 
                        icon="pi pi-plus" 
                        class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white w-full sm:w-auto font-bold shadow-lg shadow-red-900/50 transition-colors px-6" 
                        @click="showCreate = true" 
                    />
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- KPI CARDS (Overlapping / Chevauchement)                                        -->
        <!-- ============================================================================== -->
        <!-- Marge négative (-mt-16) pour faire remonter les cartes sur la bannière sombre -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-4 md:px-6 -mt-16 relative z-20">
            
            <!-- KPI 1 : En direct -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-rose-50 flex items-center justify-center mb-4">
                    <i class="pi pi-bolt text-rose-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">En direct</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ kpis?.live_now ?? 0 }}</p>
                </div>
            </div>

            <!-- KPI 2 : Planifiées aujourd'hui -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center mb-4">
                    <i class="pi pi-calendar text-indigo-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Planifiées aujourd'hui</h3>
                    <div class="flex items-center gap-2">
                        <p class="text-3xl font-extrabold text-slate-900">{{ kpis?.scheduled_today ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- KPI 3 : Audience Moyenne -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-emerald-50 flex items-center justify-center mb-4">
                    <i class="pi pi-users text-emerald-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Audience Moyenne</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ kpis?.avg_attendance ?? 0 }}</p>
                </div>
            </div>

            <!-- KPI 4 : Stockage Replays -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-amber-50 flex items-center justify-center mb-4">
                    <i class="pi pi-server text-amber-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Stockage Replays</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ kpis?.storage_used ?? '0 B' }}</p>
                </div>
            </div>
        </div>
    </div>

        <Dialog v-model:visible="showCreate" modal header="Planifier une session live" :style="{ width: '600px' }" class="!rounded-3xl">
            <div class="space-y-4 p-2">
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Titre</label>
                    <InputText v-model="form.title" class="w-full !rounded-xl" placeholder="Ex: Cours de networking - Semaine 4" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Cours</label>
                        <Select v-model="form.course_id" :options="courses" optionLabel="title" optionValue="id" placeholder="Choisir" class="w-full" />
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Instructeur</label>
                        <Select v-model="form.instructor_id" :options="instructors" optionLabel="name" optionValue="id" placeholder="Choisir" class="w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Date / heure</label>
                        <input v-model="form.start_at" type="datetime-local" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm" />
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Durée (min)</label>
                        <InputText v-model="form.duration" type="number" class="w-full !rounded-xl" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Fournisseur</label>
                        <Select v-model="form.provider" :options="providers" optionLabel="label" optionValue="value" class="w-full" :optionDisabled="p => !isProviderConfigured(p.value)">
                            <template #option="slotProps">
                                <div class="flex items-center justify-between gap-2">
                                    <span :class="{'text-slate-400': !isProviderConfigured(slotProps.option.value)}">{{ slotProps.option.label }}</span>
                                    <Tag v-if="!isProviderConfigured(slotProps.option.value)" value="Non configuré" severity="warning" class="!text-[8px] !py-0 !px-1" />
                                </div>
                            </template>
                        </Select>
                        <p v-if="!configuredProviders.length" class="text-xs text-rose-500 mt-1 font-medium">Aucun fournisseur vidéo n'est configuré. Vérifiez les paramètres système.</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Max participants</label>
                        <InputText v-model="form.max_participants" type="number" class="w-full !rounded-xl" />
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Code (optionnel)</label>
                    <InputText v-model="form.passcode" class="w-full !rounded-xl" placeholder="Ex: 123456" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined @click="showCreate = false" class="!rounded-xl" />
                <Button :label="submitting ? 'Création...' : 'Créer la session'" :loading="submitting" @click="submit" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
