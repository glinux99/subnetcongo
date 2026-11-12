<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
    provider: configuredProviders[0] || 'livekit',
    max_participants: 100,
    is_recorded: true,
    passcode: '',
    visibility: 'private',
    invited_emails: '',
});
const submitting = ref(false);

const emptyForm = () => ({
    title: '', course_id: null, instructor_id: null, start_at: '', duration: 60,
    provider: configuredProviders[0] || 'livekit', max_participants: 100, is_recorded: true,
    passcode: '', visibility: 'private', invited_emails: '',
});

const showInvite = ref(false);
const inviteTarget = ref(null);
const inviteEmails = ref('');
const inviteVisibility = ref('private');
const inviteSubmitting = ref(false);

const showRequests = ref(false);
const requestsTarget = ref(null);

const openInvite = (session) => {
    inviteTarget.value = session;
    inviteEmails.value = (session.invited_emails ?? []).join(', ');
    inviteVisibility.value = session.is_public_session ? 'public' : 'private';
    showInvite.value = true;
};

const submitInvite = () => {
    inviteSubmitting.value = true;
    router.post(route('live-sessions.invitations', inviteTarget.value.uuid), {
        emails: inviteEmails.value,
        visibility: inviteVisibility.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { showInvite.value = false; inviteSubmitting.value = false; },
        onError: () => { inviteSubmitting.value = false; },
    });
};

const openRequests = (session) => {
    requestsTarget.value = session;
    showRequests.value = true;
};

const decideRequest = (session, userId, action) => {
    router.post(route(`live-sessions.join-requests.${action}`, session.id), { user_id: userId }, {
        preserveScroll: true,
        onSuccess: () => {
            const list = (requestsTarget.value?.join_requests ?? []).map((r) =>
                Number(r.user_id) === Number(userId) ? { ...r, status: action === 'approve' ? 'approved' : 'rejected' } : r);
            if (requestsTarget.value) requestsTarget.value.join_requests = list;
        },
    });
};

const copyShareLink = async (id) => {
    try {
        const { data } = await window.axios.post(route('live-sessions.share-link', id));
        await navigator.clipboard.writeText(data.url);
        toast.add({ severity: 'success', summary: t('pages.live_sessions.link_copied'), detail: data.url, life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.live_sessions.error'), detail: t('pages.live_sessions.link_error'), life: 3000 });
    }
};

const providerLabel = (p) => {
    const found = providers.find(pr => pr.value === p);
    return found ? found.label : p;
};

const statusSeverity = (s) => ({
    'Scheduled': 'info', 'Live': 'success', 'Ended': 'secondary', 'Cancelled': 'danger'
}[s] ?? 'secondary');

const providerColor = (p) => ({
    zoom: 'bg-blue-500', meet: 'bg-emerald-500', bbb: 'bg-blue-600', jitsi: 'bg-slate-700', livekit: 'bg-red-500'
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
        onSuccess: () => { showCreate.value = false; submitting.value = false; form.value = emptyForm(); },
        onError: () => { submitting.value = false; },
    });
};

const startSession = async (id) => {
    try {
        await window.axios.post(route('live-sessions.start', id));
    } catch (e) {
        /* la session peut déjà être en direct */
    }
    window.location.href = route('live-sessions.join', id);
};
const confirmAction = useConfirmAction();
const cancelSession = (id) => {
    confirmAction({
        header: t('pages.live_sessions.cancel_header'),
        message: t('pages.live_sessions.cancel_message'),
        acceptLabel: t('pages.live_sessions.cancel_accept'),
        rejectLabel: t('pages.live_sessions.keep'),
        danger: true,
        accept: () => router.post(route('live-sessions.cancel', id)),
    });
};
const destroySession = (id) => {
    confirmAction({
        header: t('pages.live_sessions.delete_header'),
        message: t('pages.live_sessions.delete_message'),
        acceptLabel: t('pages.live_sessions.delete_accept'),
        danger: true,
        accept: () => router.delete(route('live-sessions.destroy', id)),
    });
};
const syncRecordings = (id) => router.post(route('live-sessions.sync-recordings', id), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.live_sessions.head_title')" />
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1600px] mx-auto">
                

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-[#ff4d4d] text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                                Live Classroom
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                                <span class="text-white">{{ t('pages.live_sessions.title_part1') }}</span>
                                <span class="text-[#FF3B3B]">{{ t('pages.live_sessions.title_part2') }}</span>
                            </h1>

                            <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                                {{ t('pages.live_sessions.subtitle') }}
                            </p>
                        </div>

                        <!-- Partie Droite (Boutons) -->
                        <div class="flex items-center gap-3 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto">
                            <Button 
                                :label="t('pages.live_sessions.btn_refresh')" 
                                icon="pi pi-sync" 
                                @click="search_"
                                class="!bg-transparent hover:!bg-white/10 !border !border-slate-600 !text-white w-full sm:w-auto text-sm font-medium transition-colors" 
                            />
                            <Button 
                                :label="t('pages.live_sessions.btn_schedule')" 
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
                        <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center">
                            <i class="pi pi-bolt text-red-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_sessions.kpi_live') }}</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.live_now ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center">
                            <i class="pi pi-calendar text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_sessions.kpi_scheduled_today') }}</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.scheduled_today ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center">
                            <i class="pi pi-users text-emerald-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_sessions.kpi_avg_attendance') }}</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ kpis?.avg_attendance ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-amber-50 flex items-center justify-center">
                            <i class="pi pi-server text-amber-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_sessions.kpi_storage') }}</div>
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
                        <IconField iconPosition="left" class="flex-1 min-w-[200px]">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="search" :placeholder="t('pages.live_sessions.search_placeholder')" class="w-full" @keyup.enter="search_" />
                        </IconField>
                        <Select v-model="provider" :options="providers" optionLabel="label" optionValue="value" :placeholder="t('pages.live_sessions.filter_provider')" class="!rounded-xl border-slate-200 min-w-[150px]" showClear />
                        <Select v-model="status" :options="['Scheduled','Live','Ended','Cancelled']" :placeholder="t('pages.live_sessions.filter_status')" class="!rounded-xl border-slate-200 min-w-[150px]" showClear />
                        <Button :label="t('pages.live_sessions.btn_filter')" icon="pi pi-filter" @click="search_" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold" />
                    </div>

                    <!-- DataTable -->
                    <DataTable :value="sessions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="sessions?.total ?? 0" :lazy="true" responsiveLayout="scroll" class="text-sm p-datatable-custom border-t border-slate-100 pt-4">
                        <Column field="title" :header="t('pages.live_sessions.col_title')" sortable>
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
                        <Column field="course.title" :header="t('pages.live_sessions.col_course')">
                            <template #body="{ data }">
                                <span class="font-medium text-slate-600">{{ data.course?.title ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="instructor.name" :header="t('pages.live_sessions.col_instructor')">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-700">{{ data.instructor?.name ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="start_at" :header="t('pages.live_sessions.col_start')" sortable>
                            <template #body="{ data }">
                                <span class="text-slate-500 font-medium">{{ formatDate(data.start_at) }}</span>
                            </template>
                        </Column>
                        <Column field="duration" :header="t('pages.live_sessions.col_duration')">
                            <template #body="{ data }">
                                <span class="font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2 py-1 rounded">{{ data.duration ?? '—' }} min</span>
                            </template>
                        </Column>
                        <Column :header="t('pages.live_sessions.col_participants')">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-bold text-slate-700"><i class="pi pi-users text-[10px] mr-1"></i>{{ t('pages.live_sessions.attendees_count', { count: data.attendances_count ?? 0 }) }}</span>
                                    <span class="text-[10px] text-slate-400">{{ t('pages.live_sessions.guests_count', { count: data.invited_count ?? 0 }) }}</span>
                                    <Tag v-if="data.pending_requests" :value="t('pages.live_sessions.requests_count', { count: data.pending_requests })" severity="warn" class="text-[9px] w-fit" />
                                </div>
                            </template>
                        </Column>
                        <Column field="status" :header="t('pages.live_sessions.col_status')">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <Tag :value="data.status" :severity="statusSeverity(data.status)" class="text-[10px] font-bold uppercase tracking-wider px-2" />
                                    <Tag v-if="data.is_public_session" :value="t('pages.live_sessions.tag_public')" severity="info" class="text-[9px] w-fit" />
                                </div>
                            </template>
                        </Column>
                        <Column :header="t('pages.live_sessions.col_actions')" :exportable="false" alignFrozen="right">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 justify-end">
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-play" rounded text severity="success" @click="startSession(data.id)" v-tooltip.top="t('pages.live_sessions.tip_start')" />
                                    <Link v-if="data.status !== 'Ended' && data.status !== 'Cancelled'" :href="route('live-sessions.join', data.uuid)">
                                        <Button icon="pi pi-video" rounded text severity="danger" v-tooltip.top="t('pages.live_sessions.tip_join')" />
                                    </Link>
                                    <Button icon="pi pi-envelope" rounded text severity="help" @click="openInvite(data)" v-tooltip.top="t('pages.live_sessions.tip_invite')" />
                                    <Button v-if="data.pending_requests" icon="pi pi-user-plus" rounded text severity="warn" @click="openRequests(data)" v-tooltip.top="t('pages.live_sessions.tip_requests')" />
                                    <Button icon="pi pi-link" rounded text severity="secondary" @click="copyShareLink(data.id)" v-tooltip.top="t('pages.live_sessions.tip_copy_link')" />
                                    <Button icon="pi pi-sync" rounded text severity="info" @click="syncRecordings(data.id)" v-tooltip.top="t('pages.live_sessions.tip_sync')" />
                                    <Link :href="route('live-sessions.report', data.uuid)">
                                        <Button icon="pi pi-chart-bar" rounded text severity="secondary" v-tooltip.top="t('pages.live_sessions.tip_report')" />
                                    </Link>
                                    <Button v-if="data.status === 'Scheduled'" icon="pi pi-times" rounded text severity="warn" @click="cancelSession(data.id)" v-tooltip.top="t('pages.live_sessions.tip_cancel')" />
                                    <Button icon="pi pi-trash" rounded text severity="danger" @click="destroySession(data.id)" v-tooltip.top="t('pages.live_sessions.tip_delete')" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-16 text-slate-400 flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center border border-slate-100 mb-4 shadow-inner">
                                    <i class="pi pi-video text-2xl text-slate-300"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 mb-1">{{ t('pages.live_sessions.empty_title') }}</h3>
                                <p class="text-sm text-slate-500">{{ t('pages.live_sessions.empty_hint') }}</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- MODAL : PLANIFIER UNE SESSION                                                  -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="showCreate" modal :header="t('pages.live_sessions.modal_create_title')" :style="{ width: '600px' }" class="!rounded-2xl" :pt="{ header: { class: 'border-b border-slate-100 pb-4' }, content: { class: 'pt-4' } }">
            
            <div class="space-y-5">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_title') }} <span class="text-red-500">*</span></label>
                    <InputText v-model="form.title" class="w-full !rounded-xl border-slate-300" :placeholder="t('pages.live_sessions.field_title_placeholder')" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_course') }}</label>
                        <Select v-model="form.course_id" :options="courses" optionLabel="title" optionValue="id" :placeholder="t('pages.live_sessions.select_placeholder')" class="w-full !rounded-xl border-slate-300" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_instructor') }}</label>
                        <Select v-model="form.instructor_id" :options="instructors" optionLabel="name" optionValue="id" :placeholder="t('pages.live_sessions.select_placeholder')" class="w-full !rounded-xl border-slate-300" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_start') }} <span class="text-red-500">*</span></label>
                        <!-- Utilisation de input HTML5 natif stylisé Tailwind pour le datetime -->
                        <input v-model="form.start_at" type="datetime-local" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-shadow" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_duration') }}</label>
                        <InputText v-model="form.duration" type="number" class="w-full !rounded-xl border-slate-300" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_provider') }}</label>
                        <Select v-model="form.provider" :options="providers" optionLabel="label" optionValue="value" class="w-full !rounded-xl border-slate-300 bg-white" :optionDisabled="p => !isProviderConfigured(p.value)">
                            <template #option="slotProps">
                                <div class="flex items-center justify-between gap-2 w-full">
                                    <span :class="{'text-slate-400': !isProviderConfigured(slotProps.option.value)}" class="font-bold">{{ slotProps.option.label }}</span>
                                    <Tag v-if="!isProviderConfigured(slotProps.option.value)" :value="t('pages.live_sessions.provider_inactive')" severity="warning" class="!text-[8px] !py-0 !px-1 uppercase" />
                                </div>
                            </template>
                        </Select>
                        <p v-if="!configuredProviders.length" class="text-[10px] text-red-500 mt-1.5 font-bold">{{ t('pages.live_sessions.no_provider') }}</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_max_participants') }}</label>
                        <InputText v-model="form.max_participants" type="number" class="w-full !rounded-xl border-slate-300 bg-white" />
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_passcode') }}</label>
                    <InputText v-model="form.passcode" class="w-full !rounded-xl border-slate-300" :placeholder="t('pages.live_sessions.field_passcode_placeholder')" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_visibility') }}</label>
                        <Select v-model="form.visibility" :options="[{ label: t('pages.live_sessions.vis_private'), value: 'private' }, { label: t('pages.live_sessions.vis_public'), value: 'public' }]"
                                optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">{{ t('pages.live_sessions.field_invited_emails') }}</label>
                        <InputText v-model="form.invited_emails" class="w-full !rounded-xl border-slate-300" placeholder="a@b.com, c@d.com" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 w-full">
                    <Button :label="t('pages.live_sessions.btn_cancel')" text @click="showCreate = false" class="!text-slate-500 font-bold" />
                    <Button :label="submitting ? t('pages.live_sessions.btn_creating') : t('pages.live_sessions.btn_create')" icon="pi pi-check" :loading="submitting" @click="submit" class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 shadow-md shadow-red-500/20" />
                </div>
            </template>
        </Dialog>

        <!-- MODAL : INVITER / VISIBILITÉ -->
        <Dialog v-model:visible="showInvite" modal :header="t('pages.live_sessions.modal_invite_title')" :style="{ width: '520px', maxWidth: '95vw' }" class="!rounded-2xl">
            <div class="space-y-4 pt-2">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ t('pages.live_sessions.field_visibility') }}</label>
                    <Select v-model="inviteVisibility" :options="[{ label: t('pages.live_sessions.vis_private_long'), value: 'private' }, { label: t('pages.live_sessions.vis_public'), value: 'public' }]"
                            optionLabel="label" optionValue="value" class="w-full" />
                    <p class="text-[11px] text-slate-500 mt-1">{{ t('pages.live_sessions.vis_public_hint') }}</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ t('pages.live_sessions.field_invited_emails') }}</label>
                    <textarea v-model="inviteEmails" rows="4" :placeholder="t('pages.live_sessions.invite_placeholder')"
                              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700"></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">{{ t('pages.live_sessions.invite_hint') }}</p>
                </div>
            </div>
            <template #footer>
                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 w-full">
                    <Button :label="t('pages.live_sessions.btn_cancel')" text @click="showInvite = false" />
                    <Button :label="t('pages.live_sessions.btn_save')" icon="pi pi-check" :loading="inviteSubmitting" @click="submitInvite" />
                </div>
            </template>
        </Dialog>

        <!-- MODAL : DEMANDES D'ACCÈS -->
        <Dialog v-model:visible="showRequests" modal :header="t('pages.live_sessions.modal_requests_title')" :style="{ width: '560px', maxWidth: '95vw' }" class="!rounded-2xl">
            <div class="pt-2">
                <p class="text-sm text-slate-600 mb-3">{{ requestsTarget?.title }}</p>
                <div v-if="(requestsTarget?.join_requests ?? []).length" class="space-y-2 max-h-[360px] overflow-auto">
                    <div v-for="r in requestsTarget.join_requests" :key="r.user_id" class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl">
                        <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 font-black text-sm flex items-center justify-center">
                            {{ (r.name || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-800 truncate">{{ r.name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ r.email }}</p>
                        </div>
                        <Tag v-if="r.status === 'pending'" :value="t('pages.live_sessions.req_pending')" severity="warn" class="text-[9px]" />
                        <Tag v-else-if="r.status === 'approved'" :value="t('pages.live_sessions.req_approved')" severity="success" class="text-[9px]" />
                        <Tag v-else :value="t('pages.live_sessions.req_rejected')" severity="danger" class="text-[9px]" />
                        <div v-if="r.status === 'pending'" class="flex gap-1">
                            <Button icon="pi pi-check" rounded text severity="success" v-tooltip.top="t('pages.live_sessions.tip_approve')" @click="decideRequest(requestsTarget, r.user_id, 'approve')" />
                            <Button icon="pi pi-times" rounded text severity="danger" v-tooltip.top="t('pages.live_sessions.tip_reject')" @click="decideRequest(requestsTarget, r.user_id, 'reject')" />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400 text-center py-8">{{ t('pages.live_sessions.no_requests') }}</p>
            </div>
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