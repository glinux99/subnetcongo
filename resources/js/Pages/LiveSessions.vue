<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import SncDialog from '@/Components/SncDialog.vue';
import Select from 'primevue/select';
import Menu from 'primevue/menu';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

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
const timezone = props.meta?.timezone ?? 'Africa/Lubumbashi';
const timezones = props.meta?.timezones ?? [];
const timezoneLabel = computed(() => timezones.find((tz) => tz.value === timezone)?.label ?? timezone);

const isProviderConfigured = (providerValue) => configuredProviders.includes(providerValue);

const showCreate = ref(false);
const editingSession = ref(null);
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
    timezone,
    remind_before_minutes: null,
});
const submitting = ref(false);

const reminderOptions = computed(() => [
    { label: t('pages.live_sessions.reminder_none'), value: null },
    { label: t('pages.live_sessions.reminder_15'), value: 15 },
    { label: t('pages.live_sessions.reminder_30'), value: 30 },
    { label: t('pages.live_sessions.reminder_60'), value: 60 },
    { label: t('pages.live_sessions.reminder_1day'), value: 1440 },
]);

const emptyForm = () => ({
    title: '', course_id: null, instructor_id: null, start_at: '', duration: 60,
    provider: configuredProviders[0] || 'livekit', max_participants: 100, is_recorded: true,
    passcode: '', visibility: 'private', invited_emails: '', timezone, remind_before_minutes: null,
});

const isoToLocalInput = (iso, tz = timezone) => {
    const dt = new Date(iso);
    if (Number.isNaN(dt.getTime())) return '';
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: tz, year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hour12: false,
    }).formatToParts(dt);
    const p = Object.fromEntries(parts.map(({ type, value }) => [type, value]));
    return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}`;
};

const openCreate = () => {
    editingSession.value = null;
    form.value = emptyForm();
    showCreate.value = true;
};

const openEdit = (session) => {
    editingSession.value = session;
    form.value = {
        title: session.title ?? '',
        course_id: session.course_id ?? session.course?.id ?? null,
        instructor_id: session.instructor_id ?? session.instructor?.id ?? null,
        start_at: isoToLocalInput(session.start_at, timezone),
        duration: session.duration ?? 60,
        provider: session.provider ?? (configuredProviders[0] || 'livekit'),
        max_participants: session.max_participants ?? 100,
        is_recorded: session.is_recorded ?? true,
        passcode: session.passcode ?? '',
        visibility: session.settings?.visibility ?? 'private',
        invited_emails: (session.invited_emails ?? []).join(', '),
        timezone: timezone,
        remind_before_minutes: session.remind_before_minutes ?? null,
    };
    showCreate.value = true;
};

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
    router.post(route(`live-sessions.join-requests.${action}`, session.uuid), { user_id: userId }, {
        preserveScroll: true,
        onSuccess: () => {
            const list = (requestsTarget.value?.join_requests ?? []).map((r) =>
                Number(r.user_id) === Number(userId) ? { ...r, status: action === 'approve' ? 'approved' : 'rejected' } : r);
            if (requestsTarget.value) requestsTarget.value.join_requests = list;
        },
    });
};

const copyShareLink = async (uuid) => {
    try {
        const { data } = await window.axios.post(route('live-sessions.share-link', uuid));
        await navigator.clipboard.writeText(data.url);
        toast.add({ severity: 'success', summary: t('pages.live_sessions.link_copied'), detail: data.url, life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.live_sessions.error'), detail: t('pages.live_sessions.link_error'), life: 3000 });
    }
};

const rowMenu = ref(null);
const rowMenuTarget = ref(null);
const expandedRows = ref([]);
const rowMenuItems = computed(() => {
    const s = rowMenuTarget.value;
    if (!s) return [];
    const items = [];
    if (s.status === 'Scheduled') {
        items.push({ label: t('pages.live_sessions.menu_start'), icon: 'pi pi-play', command: () => startSession(s.uuid) });
    }
    items.push(
        { label: t('pages.live_sessions.tip_invite'), icon: 'pi pi-envelope', command: () => openInvite(s) },
        { label: t('pages.live_sessions.tip_copy_link'), icon: 'pi pi-link', command: () => copyShareLink(s.uuid) },
        { label: t('pages.live_sessions.tip_sync'), icon: 'pi pi-sync', command: () => syncRecordings(s.uuid) },
        { label: t('pages.live_sessions.tip_report'), icon: 'pi pi-chart-bar', command: () => router.visit(route('live-sessions.report', s.uuid)) },
    );
    if (s.status === 'Scheduled') {
        items.push({ separator: true });
        items.push({ label: t('pages.live_sessions.out_cancel'), icon: 'pi pi-times', command: () => cancelSession(s.uuid), class: 'text-amber-600' });
    }
    items.push({ separator: true });
    items.push({ label: t('pages.live_sessions.tip_delete'), icon: 'pi pi-trash', command: () => destroySession(s.uuid), class: 'text-red-600' });
    return items;
});
const toggleRowMenu = (event, session) => {
    rowMenuTarget.value = session;
    rowMenu.value?.toggle(event);
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

const formatDate = (d) => d ? new Date(d).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short', timeZone: timezone }) : '—';

const search_ = () => {
    router.get(route('live-sessions.index'), {
        search: search.value, provider: provider.value, status: status.value,
    }, { preserveState: true, replace: true });
};

const submit = () => {
    submitting.value = true;
    const payload = { ...form.value };
    const editing = editingSession.value;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            submitting.value = false;
            editingSession.value = null;
            form.value = emptyForm();
        },
        onError: () => { submitting.value = false; },
    };

    if (editing) {
        router.put(route('live-sessions.update', editing.uuid), payload, options);
    } else {
        router.post(route('live-sessions.store'), payload, options);
    }
};

const startSession = async (uuid) => {
    try {
        await window.axios.post(route('live-sessions.start', uuid));
    } catch (e) {
        /* la session peut déjà être en direct */
    }
    window.location.href = route('live-sessions.join', uuid);
};
const confirmAction = useConfirmAction();
const cancelSession = (uuid) => {
    confirmAction({
        header: t('pages.live_sessions.cancel_header'),
        message: t('pages.live_sessions.cancel_message'),
        acceptLabel: t('pages.live_sessions.cancel_accept'),
        rejectLabel: t('pages.live_sessions.keep'),
        danger: true,
        accept: () => router.post(route('live-sessions.cancel', uuid)),
    });
};
const destroySession = (uuid) => {
    confirmAction({
        header: t('pages.live_sessions.delete_header'),
        message: t('pages.live_sessions.delete_message'),
        acceptLabel: t('pages.live_sessions.delete_accept'),
        danger: true,
        accept: () => router.delete(route('live-sessions.destroy', uuid)),
    });
};
const syncRecordings = (uuid) => router.post(route('live-sessions.sync-recordings', uuid), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.live_sessions.head_title')" />
        
        <div class="page-bg min-h-screen font-sans pb-20">

            <PageHeader
                :title="`${t('pages.live_sessions.title_part1')} ${t('pages.live_sessions.title_part2')}`"
                :subtitle="t('pages.live_sessions.subtitle')"
                badge="Live Classroom"
                icon="pi-video"
                accent="red"
            >
                <template #actions>
                    <Button
                        :label="t('pages.live_sessions.btn_refresh')"
                        icon="pi pi-sync"
                        @click="search_"
                        class="!bg-white/10 hover:!bg-white/20 !border !border-white/15 !text-white !rounded-xl !text-xs !font-bold"
                    />
                    <Button
                        :label="t('pages.live_sessions.btn_schedule')"
                        icon="pi pi-plus"
                        @click="openCreate"
                        class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !text-white !rounded-xl !text-xs !font-bold shadow-lg shadow-red-900/40"
                    />
                </template>
            </PageHeader>

            <div class="page-container page-section">

                <!-- ── CARTES KPI ── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <PageKpi :label="t('pages.live_sessions.kpi_live')" :value="kpis?.live_now ?? 0" icon="pi-bolt" color="red" />
                    <PageKpi :label="t('pages.live_sessions.kpi_scheduled_today')" :value="kpis?.scheduled_today ?? 0" icon="pi-calendar" color="sky" />
                    <PageKpi :label="t('pages.live_sessions.kpi_avg_attendance')" :value="kpis?.avg_attendance ?? 0" icon="pi-users" color="emerald" />
                    <PageKpi :label="t('pages.live_sessions.kpi_storage')" :value="kpis?.storage_used ?? '0 B'" icon="pi-server" color="amber" />
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU TABLEAU (Filtres et DataTable)                                         -->
                <!-- ============================================================================== -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative z-20">
                    
                    <!-- Zone des filtres -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-6">
                        <IconField iconPosition="left" class="flex-1 min-w-0 basis-full sm:basis-auto sm:min-w-[200px]">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="search" :placeholder="t('pages.live_sessions.search_placeholder')" class="w-full h-11" @keyup.enter="search_" />
                        </IconField>
                        <Select v-model="provider" :options="providers" optionLabel="label" optionValue="value" :placeholder="t('pages.live_sessions.filter_provider')" class="!rounded-xl border-slate-200 h-11 w-[calc(50%-0.25rem)] sm:w-auto sm:min-w-[150px]" showClear />
                        <Select v-model="status" :options="['Scheduled','Live','Ended','Cancelled']" :placeholder="t('pages.live_sessions.filter_status')" class="!rounded-xl border-slate-200 h-11 w-[calc(50%-0.25rem)] sm:w-auto sm:min-w-[150px]" showClear />
                        <Button :label="t('pages.live_sessions.btn_filter')" icon="pi pi-filter" @click="search_" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold h-11 w-full sm:w-auto justify-center" />
                        <span class="flex items-center gap-1.5 text-[11px] text-slate-500 w-full sm:w-auto sm:ml-auto font-medium justify-end" v-tooltip.top="t('pages.live_sessions.timezone_note')">
                            <i class="pi pi-globe text-slate-400" aria-hidden="true"></i>{{ timezoneLabel }}
                        </span>
                    </div>

                    <!-- DataTable -->
                    <DataTable :value="sessions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="sessions?.total ?? 0" :lazy="true" responsiveLayout="scroll" v-model:expandedRows="expandedRows" dataKey="uuid" class="text-sm p-datatable-custom border-t border-slate-100 pt-4 snc-fluid-table">
                        <Column expander style="width: 3rem" headerClass="md:hidden" bodyClass="md:hidden" />
                        <Column field="title" :header="t('pages.live_sessions.col_title')" sortable style="min-width: 0">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div :class="['w-9 h-9 rounded-lg flex-shrink-0 flex items-center justify-center text-white text-sm shadow-sm', providerColor(data.provider)]">
                                        <i :class="data.provider === 'zoom' ? 'pi pi-video' : data.provider === 'meet' ? 'pi pi-google' : data.provider === 'bbb' ? 'pi pi-users' : data.provider === 'livekit' ? 'pi pi-camera' : 'pi pi-comments'"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 dark:text-slate-100 truncate">{{ data.title }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono flex items-center gap-2 mt-0.5">
                                            <span class="truncate">ID: {{ data.meeting_id ?? '—' }}</span>
                                            <Tag v-if="!data.meeting_url && data.provider !== 'livekit'" value="Demo" severity="warning" class="!text-[8px] !py-0 !px-1 flex-shrink-0" />
                                        </div>
                                        <div class="xl:hidden flex flex-wrap items-center gap-1.5 text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-1">
                                            <span class="flex items-center gap-1.5">
                                                <i class="pi pi-calendar text-[10px] text-slate-400" aria-hidden="true"></i>
                                                <span>{{ formatDate(data.start_at) }}</span>
                                                <i v-if="data.remind_before_minutes" class="pi pi-bell text-amber-500" aria-hidden="true"></i>
                                            </span>
                                            <Tag :value="data.status" :severity="statusSeverity(data.status)" class="md:hidden text-[8px] font-bold uppercase tracking-wider !py-0.5 !px-1.5" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="course.title" :header="t('pages.live_sessions.col_course')" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 0">
                            <template #body="{ data }">
                                <span class="font-medium text-slate-600 dark:text-slate-300 line-clamp-2">{{ data.course?.title ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="instructor.name" :header="t('pages.live_sessions.col_instructor')" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 0">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-700 dark:text-slate-200 line-clamp-2">{{ data.instructor?.name ?? '—' }}</span>
                            </template>
                        </Column>
                        <Column field="start_at" :header="t('pages.live_sessions.col_start')" sortable headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell">
                            <template #body="{ data }">
                                <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                    {{ formatDate(data.start_at) }}
                                    <i
                                        v-if="data.remind_before_minutes"
                                        class="pi pi-bell text-amber-500"
                                        v-tooltip.top="t('pages.live_sessions.reminder_set', { minutes: data.remind_before_minutes })"
                                        aria-hidden="true"
                                    ></i>
                                </span>
                            </template>
                        </Column>
                        <Column headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" field="duration" :header="t('pages.live_sessions.col_duration')">
                            <template #body="{ data }">
                                <span class="font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2 py-1 rounded">{{ data.duration ?? '—' }} min</span>
                            </template>
                        </Column>
                        <Column :header="t('pages.live_sessions.col_participants')" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-bold text-slate-700"><i class="pi pi-users text-[10px] mr-1"></i>{{ t('pages.live_sessions.attendees_count', { count: data.attendances_count ?? 0 }) }}</span>
                                    <span class="text-[10px] text-slate-400">{{ t('pages.live_sessions.guests_count', { count: data.invited_count ?? 0 }) }}</span>
                                    <Tag v-if="data.pending_requests" :value="t('pages.live_sessions.requests_count', { count: data.pending_requests })" severity="warn" class="text-[9px] w-fit" />
                                </div>
                            </template>
                        </Column>
                        <Column field="status" :header="t('pages.live_sessions.col_status')" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <Tag :value="data.status" :severity="statusSeverity(data.status)" class="text-[10px] font-bold uppercase tracking-wider px-2" />
                                    <Tag v-if="data.is_public_session" :value="t('pages.live_sessions.tag_public')" severity="info" class="text-[9px] w-fit" />
                                </div>
                            </template>
                        </Column>
                        <Column :header="t('pages.live_sessions.col_actions')" :exportable="false" style="min-width: 6rem; text-align: right;">
                            <template #body="{ data }">
                                <div class="hidden md:flex items-center gap-1 justify-end whitespace-nowrap">
                                    <Button
                                        v-if="data.status !== 'Ended' && data.status !== 'Cancelled'"
                                        icon="pi pi-video"
                                        rounded
                                        text
                                        severity="danger"
                                        :aria-label="t('pages.live_sessions.tip_join')"
                                        v-tooltip.top="t('pages.live_sessions.tip_join')"
                                        @click="window.location.href = route('live-sessions.join', data.uuid)"
                                    />
                                    <Button
                                        v-if="data.status === 'Scheduled'"
                                        icon="pi pi-pencil"
                                        rounded
                                        text
                                        severity="info"
                                        :aria-label="t('pages.live_sessions.tip_edit')"
                                        v-tooltip.top="t('pages.live_sessions.tip_edit')"
                                        @click="openEdit(data)"
                                    />
                                    <Button
                                        icon="pi pi-ellipsis-v"
                                        rounded
                                        text
                                        severity="secondary"
                                        :aria-label="t('pages.live_sessions.menu_more')"
                                        v-tooltip.top="t('pages.live_sessions.menu_more')"
                                        @click="toggleRowMenu($event, data)"
                                        aria-haspopup="true"
                                        aria-controls="live-session-row-menu"
                                    />
                                </div>
                                <Button
                                    icon="pi pi-ellipsis-v"
                                    rounded
                                    text
                                    severity="secondary"
                                    class="md:hidden w-11 h-11"
                                    :aria-label="t('pages.live_sessions.menu_more')"
                                    v-tooltip.top="t('pages.live_sessions.menu_more')"
                                    @click="toggleRowMenu($event, data)"
                                    aria-haspopup="true"
                                    aria-controls="live-session-row-menu"
                                />
                            </template>
                        </Column>
                        <template #expansion="{ data }">
                            <div class="px-4 py-4 sm:px-6 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-700">
                                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-xs min-w-0">
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.filter_provider') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200 break-anywhere">{{ providerLabel(data.provider) }}</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.field_start') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200 break-anywhere">{{ formatDate(data.start_at) }} ({{ timezoneLabel }})</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.col_duration') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200">{{ data.duration ?? '—' }} min</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.field_max_participants') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200">{{ data.max_participants ?? '—' }} · {{ t('pages.live_sessions.attendees_count', { count: data.attendances_count ?? 0 }) }}</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.col_status') }}</dt>
                                        <dd class="mt-0.5 flex flex-wrap items-center gap-1">
                                            <Tag :value="data.status" :severity="statusSeverity(data.status)" class="text-[9px] font-bold uppercase tracking-wider" />
                                            <Tag v-if="data.is_public_session" :value="t('pages.live_sessions.tag_public')" severity="info" class="text-[9px]" />
                                        </dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.field_reminder') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200 break-anywhere">{{ data.remind_before_minutes ? t('pages.live_sessions.reminder_minutes_short', { minutes: data.remind_before_minutes }) : t('pages.live_sessions.reminder_none') }}</dd>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">ID</dt>
                                        <dd class="mt-0.5 font-mono text-[10px] text-slate-600 dark:text-slate-300 break-anywhere">{{ data.meeting_id ?? '—' }}</dd>
                                    </div>
                                    <div v-if="data.course" class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.col_course') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200 break-anywhere">{{ data.course?.title }}</dd>
                                    </div>
                                    <div v-if="data.instructor" class="min-w-0">
                                        <dt class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ t('pages.live_sessions.col_instructor') }}</dt>
                                        <dd class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200 break-anywhere">{{ data.instructor?.name }}</dd>
                                    </div>
                                </dl>
                                <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-slate-200/70 dark:border-slate-700">
                                    <Button v-if="data.status !== 'Ended' && data.status !== 'Cancelled'" :label="t('pages.live_sessions.tip_join')" icon="pi pi-video" class="p-button-outlined p-button-danger p-button-sm" @click="window.location.href = route('live-sessions.join', data.uuid)" />
                                    <Button v-if="data.status === 'Scheduled'" :label="t('pages.live_sessions.tip_edit')" icon="pi pi-pencil" class="p-button-outlined p-button-info p-button-sm" @click="openEdit(data)" />
                                </div>
                            </div>
                        </template>
                        <template #empty>
                            <div class="text-center py-16 px-4 text-slate-400 flex flex-col items-center break-words">
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
        <SncDialog v-model:visible="showCreate" :title="editingSession ? t('pages.live_sessions.modal_edit_title') : t('pages.live_sessions.modal_create_title')" icon="pi-video" icon-class="from-red-500 to-red-600" width="600px">
            
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

                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1.5" for="live-session-timezone">
                        <i class="pi pi-globe text-slate-400" aria-hidden="true"></i>{{ t('pages.live_sessions.field_timezone') }}
                    </label>
                    <Select
                        id="live-session-timezone"
                        v-model="form.timezone"
                        :options="timezones"
                        optionLabel="label"
                        optionValue="value"
                        class="w-full !rounded-xl border-slate-300"
                    />
                    <p class="text-[10px] text-slate-500 mt-1.5 flex items-start gap-1.5">
                        <i class="pi pi-info-circle text-slate-400 mt-0.5" aria-hidden="true"></i>
                        <span>{{ t('pages.live_sessions.timezone_hint') }}</span>
                    </p>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1.5" for="live-session-reminder">
                        <i class="pi pi-bell text-slate-400" aria-hidden="true"></i>{{ t('pages.live_sessions.field_reminder') }}
                    </label>
                    <Select
                        id="live-session-reminder"
                        v-model="form.remind_before_minutes"
                        :options="reminderOptions"
                        optionLabel="label"
                        optionValue="value"
                        :placeholder="t('pages.live_sessions.reminder_none')"
                        class="w-full !rounded-xl border-slate-300"
                    />
                    <p class="text-[10px] text-slate-500 mt-1.5 flex items-start gap-1.5">
                        <i class="pi pi-info-circle text-slate-400 mt-0.5" aria-hidden="true"></i>
                        <span>{{ t('pages.live_sessions.reminder_hint') }}</span>
                    </p>
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
                <div class="flex justify-end gap-2 w-full">
                    <Button :label="t('pages.live_sessions.btn_cancel')" text @click="showCreate = false" class="!text-slate-500 font-bold" />
                    <Button :label="submitting ? t('pages.live_sessions.btn_creating') : (editingSession ? t('pages.live_sessions.btn_save') : t('pages.live_sessions.btn_create'))" icon="pi pi-check" :loading="submitting" @click="submit" class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 shadow-md shadow-red-500/20" />
                </div>
            </template>
        </SncDialog>

        <!-- MODAL : INVITER / VISIBILITÉ -->
        <SncDialog v-model:visible="showInvite" :title="t('pages.live_sessions.modal_invite_title')" icon="pi-envelope" width="520px">
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
                <div class="flex justify-end gap-2 w-full">
                    <Button :label="t('pages.live_sessions.btn_cancel')" text @click="showInvite = false" />
                    <Button :label="t('pages.live_sessions.btn_save')" icon="pi pi-check" :loading="inviteSubmitting" @click="submitInvite" />
                </div>
            </template>
        </SncDialog>

        <!-- MODAL : DEMANDES D'ACCÈS -->
        <SncDialog v-model:visible="showRequests" :title="t('pages.live_sessions.modal_requests_title')" icon="pi-user-plus" width="560px">
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
        </SncDialog>

        <Menu
            id="live-session-row-menu"
            ref="rowMenu"
            :model="rowMenuItems"
            popup
            class="!rounded-xl !shadow-xl !border-slate-200 !overflow-hidden !py-1"
            aria-label="live-session-menu"
        />
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