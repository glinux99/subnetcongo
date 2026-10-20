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
import Breadcrumb from 'primevue/breadcrumb';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    sessions: Object,
    kpis: Object,
    filters: Object,
    meta: Object,
});

const toast = useToast();
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
        <Toast position="top-right" />
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1600px] mx-auto">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Sessions en direct' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-[#ff4d4d] text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                                Live Classroom
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                                <span class="text-white">SESSIONS</span>
                                <span class="text-[#FF3B3B]">EN DIRECT</span>
                            </h1>

                            <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                                Planifiez et gérez vos classes virtuelles Zoom, Meet, BBB ou Jitsi. Diffusez vos cours en temps réel et centralisez l'ensemble de vos replays.
                            </p>
                        </div>

                        <!-- Partie Droite (Boutons) -->
                        <div class="flex items-center gap-3 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto">
                            <Button 
                                label="Actualiser" 
                                icon="pi pi-sync" 
                                @click="search_"
                                class="!bg-transparent hover:!bg-white/10 !border !border-slate-600 !text-white w-full sm:w-auto text-sm font-medium transition-colors" 
                            />
                            <Button 
                                label="Planifier une session" 
                                icon="pi pi-plus" 
                                @click="showCreate = true"
                                class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !text-white w-full sm:w-auto text-sm font-bold shadow-lg shadow-red-900/50 transition-colors" 
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES KPI (Chevauchement) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- KPI 1 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-rose-50 flex items-center justify-center">
                            <i class="pi pi-bolt text-rose-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">En direct</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.live_now ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center">
                            <i class="pi pi-calendar text-indigo-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Planifiées aujourd'hui</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.scheduled_today ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center">
                            <i class="pi pi-users text-emerald-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Audience Moyenne</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.avg_attendance ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-amber-50 flex items-center justify-center">
                            <i class="pi pi-server text-amber-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Stockage Replays</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.storage_used ?? '0 B' }}</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU TABLEAU (Filtres et DataTable)                                         -->
                <!-- ============================================================================== -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative z-20 mt-6">
                    
                    <!-- Zone des filtres -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <span class="p-input-icon-left flex-1 min-w-[200px]">
                            <i class="pi pi-search text-slate-400"></i>
                            <InputText v-model="search" placeholder="Rechercher une session..." class="w-full !rounded-xl border-slate-200" @keyup.enter="search_" />
                        </span>
                        <Select v-model="provider" :options="providers" optionLabel="label" optionValue="value" placeholder="Fournisseur" class="!rounded-xl border-slate-200 min-w-[150px]" showClear />
                        <Select v-model="status" :options="['Scheduled','Live','Ended','Cancelled']" placeholder="Statut" class="!rounded-xl border-slate-200 min-w-[150px]" showClear />
                        <Button label="Filtrer" icon="pi pi-filter" @click="search_" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold" />
                    </div>

                    <!-- DataTable -->
                    <DataTable :value="sessions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="sessions?.total ?? 0" :lazy="true" responsiveLayout="scroll" class="text-sm p-datatable-custom border-t border-slate-100 pt-4">
                        <Column field="title" header="Titre" sortable>
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-9 h-9 rounded-lg flex items-center justify-center text-white text-sm shadow-sm', providerColor(data.provider)]">
                                        <i :class="data.provider === 'zoom' ? 'pi pi-video' : data.provider === 'meet' ? 'pi pi-google' : data.provider === 'bbb' ? 'pi pi-users' : data.provider === 'livekit' ? 'pi pi-camera' : 'pi pi-comments'"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ data.title }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono flex items-center gap-2 mt-0.5">
                                            <span>ID: {{ data.meeting_id ?? '—' }}</span>
                                            <Tag v-if="!data.meeting_url && data.provider !== 'livekit'" value="Demo" severity="warning" class="!text-[8px] !py-0 !px-1" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="course.title" header="Cours lié">
                            <template #body="{ data }">
                                <span class="font-medium text-slate-600">{{ data.course?.title ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="instructor.name" header="Instructeur">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-700">{{ data.instructor?.name ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="start_at" header="Démarrage" sortable>
                            <template #body="{ data }">
                                <span class="text-slate-500 font-medium">{{ formatDate(data.start_at) }}</span>
                            </template>
                        </Column>
                        <Column field="duration" header="Durée">
                            <template #body="{ data }">
                                <span class="font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2 py-1 rounded">{{ data.duration ?? '—' }} min</span>
                            </template>
                        </Column>
                        <Column field="status" header="Statut">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="statusSeverity(data.status)" class="text-[10px] font-bold uppercase tracking-wider px-2" />
                            </template>
                        </Column>
                        <Column header="Actions" :exportable="false" alignFrozen="right">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 justify-end">
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-play" rounded text severity="success" @click="startSession(data.id)" v-tooltip.top="'Démarrer la session'" />
                                    <Button icon="pi pi-sync" rounded text severity="info" @click="syncRecordings(data.id)" v-tooltip.top="'Sync replays'" />
                                    <Link :href="route('live-sessions.report', data.id)">
                                        <Button icon="pi pi-chart-bar" rounded text severity="secondary" v-tooltip.top="'Rapport de présence'" />
                                    </Link>
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-times" rounded text severity="warn" @click="cancelSession(data.id)" v-tooltip.top="'Annuler'" />
                                    <Button icon="pi pi-trash" rounded text severity="danger" @click="destroySession(data.id)" v-tooltip.top="'Supprimer'" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-16 text-slate-400 flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center border border-slate-100 mb-4 shadow-inner">
                                    <i class="pi pi-video text-2xl text-slate-300"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 mb-1">Aucune session planifiée</h3>
                                <p class="text-sm text-slate-500">Cliquez sur "Planifier une session" en haut à droite pour commencer.</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- MODAL : PLANIFIER UNE SESSION                                                  -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="showCreate" modal header="Planifier une classe virtuelle" :style="{ width: '600px' }" class="!rounded-2xl" :pt="{ header: { class: 'border-b border-slate-100 pb-4' }, content: { class: 'pt-4' } }">
            
            <div class="space-y-5">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Titre de la session <span class="text-red-500">*</span></label>
                    <InputText v-model="form.title" class="w-full !rounded-xl border-slate-300" placeholder="Ex: Masterclass Cybersécurité - Semaine 4" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Cours rattaché</label>
                        <Select v-model="form.course_id" :options="courses" optionLabel="title" optionValue="id" placeholder="Sélectionner..." class="w-full !rounded-xl border-slate-300" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Instructeur</label>
                        <Select v-model="form.instructor_id" :options="instructors" optionLabel="name" optionValue="id" placeholder="Sélectionner..." class="w-full !rounded-xl border-slate-300" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Date et Heure de début <span class="text-red-500">*</span></label>
                        <!-- Utilisation de input HTML5 natif stylisé Tailwind pour le datetime -->
                        <input v-model="form.start_at" type="datetime-local" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm text-slate-700 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-shadow" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Durée prévue (min)</label>
                        <InputText v-model="form.duration" type="number" class="w-full !rounded-xl border-slate-300" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Plateforme Visio</label>
                        <Select v-model="form.provider" :options="providers" optionLabel="label" optionValue="value" class="w-full !rounded-xl border-slate-300 bg-white" :optionDisabled="p => !isProviderConfigured(p.value)">
                            <template #option="slotProps">
                                <div class="flex items-center justify-between gap-2 w-full">
                                    <span :class="{'text-slate-400': !isProviderConfigured(slotProps.option.value)}" class="font-bold">{{ slotProps.option.label }}</span>
                                    <Tag v-if="!isProviderConfigured(slotProps.option.value)" value="Inactif" severity="warning" class="!text-[8px] !py-0 !px-1 uppercase" />
                                </div>
                            </template>
                        </Select>
                        <p v-if="!configuredProviders.length" class="text-[10px] text-red-500 mt-1.5 font-bold">Aucun fournisseur vidéo n'est configuré.</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Max Participants</label>
                        <InputText v-model="form.max_participants" type="number" class="w-full !rounded-xl border-slate-300 bg-white" />
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Code d'accès (Optionnel)</label>
                    <InputText v-model="form.passcode" class="w-full !rounded-xl border-slate-300" placeholder="Laissez vide pour générer automatiquement" />
                </div>
            </div>

            <template #footer>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 w-full">
                    <Button label="Annuler" text @click="showCreate = false" class="!text-slate-500 font-bold" />
                    <Button :label="submitting ? 'Création en cours...' : 'Générer la session'" icon="pi pi-check" :loading="submitting" @click="submit" class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 shadow-md shadow-red-500/20" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Customisation PrimeVue DataTable B2B */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Overrides UI Forms */
:deep(.p-inputtext), :deep(.p-select) {
    border-radius: 0.75rem;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-select:not(.p-disabled).p-focus) {
    border-color: #FF3B3B;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
}
</style>