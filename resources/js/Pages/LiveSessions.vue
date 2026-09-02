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

const showCreate = ref(false);
const form = ref({
    title: '',
    course_id: null,
    instructor_id: null,
    start_at: '',
    duration: 60,
    provider: 'zoom',
    max_participants: 100,
    is_recorded: true,
    passcode: '',
});
const submitting = ref(false);

const providers = props.meta?.providers ?? [];
const courses = props.meta?.courses ?? [];
const instructors = props.meta?.instructors ?? [];

const statusSeverity = (s) => ({
    'Scheduled': 'info', 'Live': 'success', 'Ended': 'secondary', 'Cancelled': 'danger'
}[s] ?? 'secondary');

const providerColor = (p) => ({
    zoom: 'bg-blue-500', meet: 'bg-emerald-500', bbb: 'bg-indigo-600', jitsi: 'bg-slate-700'
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
        onSuccess: () => { showCreate.value = false; submitting.value = false; form.value = { title: '', course_id: null, instructor_id: null, start_at: '', duration: 60, provider: 'zoom', max_participants: 100, is_recorded: true, passcode: '' }; },
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
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-video text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Sessions en direct</h1>
                            <p class="text-slate-400 font-medium text-sm">Planifiez et gérez vos classes virtuelles Zoom / Meet / BBB / Jitsi</p>
                        </div>
                    </div>
                    <Button label="Planifier une session" icon="pi pi-plus" @click="showCreate = true" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 !px-5" />
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'En direct', value: kpis?.live_now ?? 0, icon: 'pi-bolt', color: 'from-rose-500 to-pink-500' },
                        { label: 'Planifiées aujourd\'hui', value: kpis?.scheduled_today ?? 0, icon: 'pi-calendar', color: 'from-indigo-500 to-blue-500' },
                        { label: 'Audience moyenne', value: kpis?.avg_attendance ?? 0, icon: 'pi-users', color: 'from-emerald-500 to-teal-500' },
                        { label: 'Stockage replays', value: kpis?.storage_used ?? '0 B', icon: 'pi-server', color: 'from-amber-500 to-orange-500' },
                    ]" :key="kpi.label" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white shadow-lg mb-3', kpi.color]">
                            <i :class="['pi', kpi.icon, 'text-xl']"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-800 tracking-tight">{{ kpi.value }}</div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="p-input-icon-left flex-1 min-w-[200px]">
                            <i class="pi pi-search"></i>
                            <InputText v-model="search" placeholder="Rechercher..." class="w-full !rounded-xl" @keyup.enter="search_" />
                        </span>
                        <Select v-model="provider" :options="providers" optionLabel="label" optionValue="value" placeholder="Fournisseur" class="!rounded-xl" showClear />
                        <Select v-model="status" :options="['Scheduled','Live','Ended','Cancelled']" placeholder="Statut" class="!rounded-xl" showClear />
                        <Button label="Filtrer" icon="pi pi-search" @click="search_" outlined class="!rounded-xl" />
                    </div>

                    <DataTable :value="sessions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="sessions?.total ?? 0" :lazy="true" responsiveLayout="scroll" class="text-sm">
                        <Column field="title" header="Titre" sortable>
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs', providerColor(data.provider)]">
                                        <i :class="data.provider === 'zoom' ? 'pi pi-video' : data.provider === 'meet' ? 'pi pi-google' : data.provider === 'bbb' ? 'pi pi-users' : 'pi pi-comments'"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ data.title }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ data.meeting_id ?? '—' }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="course.title" header="Cours">
                            <template #body="{ data }">{{ data.course?.title ?? '—' }}</template>
                        </Column>
                        <Column field="instructor.name" header="Instructeur">
                            <template #body="{ data }">{{ data.instructor?.name ?? '—' }}</template>
                        </Column>
                        <Column field="start_at" header="Démarrage" sortable>
                            <template #body="{ data }">{{ formatDate(data.start_at) }}</template>
                        </Column>
                        <Column field="duration" header="Durée">
                            <template #body="{ data }">{{ data.duration ?? '—' }} min</template>
                        </Column>
                        <Column field="status" header="Statut">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column header="Actions" :exportable="false">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1">
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-play" rounded text severity="success" @click="startSession(data.id)" v-tooltip.top="'Démarrer'" />
                                    <Button icon="pi pi-sync" rounded text severity="info" @click="syncRecordings(data.id)" v-tooltip.top="'Sync replays'" />
                                    <Link :href="route('live-sessions.report', data.id)">
                                        <Button icon="pi pi-chart-bar" rounded text severity="secondary" v-tooltip.top="'Rapport'" />
                                    </Link>
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-times" rounded text severity="warn" @click="cancelSession(data.id)" v-tooltip.top="'Annuler'" />
                                    <Button icon="pi pi-trash" rounded text severity="danger" @click="destroySession(data.id)" v-tooltip.top="'Supprimer'" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-video text-3xl mb-2 block"></i>
                                Aucune session planifiée. Cliquez sur "Planifier une session" pour commencer.
                            </div>
                        </template>
                    </DataTable>
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
                        <Select v-model="form.provider" :options="providers" optionLabel="label" optionValue="value" class="w-full" />
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
