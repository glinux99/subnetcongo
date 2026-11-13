<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ref, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import debounce from 'lodash/debounce';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

// PrimeVue
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import Divider from 'primevue/divider';
import SncConfirmDialog from '@/Components/SncConfirmDialog.vue';

const props = defineProps({
    tickets: { type: Object, default: null },
    ticket: { type: Object, default: null },
    messages: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    agents: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    auth: { type: Object, default: () => ({ user: null }) },
    view: { type: String, default: 'list' },
});

const toast = useToast();
const confirm = useConfirm();

const isDetail = computed(() => props.view === 'detail' && props.ticket);

// ─── STATUTS & PRIORITÉS ───
const statuses = computed(() => [
    { label: t('pages.support.status_new'),        value: 'new',         severity: 'info' },
    { label: t('pages.support.status_open'),       value: 'open',        severity: 'warning' },
    { label: t('pages.support.status_in_progress'), value: 'in_progress', severity: 'help' },
    { label: t('pages.support.status_pending'),    value: 'pending',     severity: 'secondary' },
    { label: t('pages.support.status_resolved'),   value: 'resolved',    severity: 'success' },
    { label: t('pages.support.status_closed'),     value: 'closed',      severity: 'secondary' },
]);

const priorities = computed(() => [
    { label: t('pages.support.priority_urgent'), value: 'urgent', color: 'rose' },
    { label: t('pages.support.priority_high'),   value: 'high',   color: 'orange' },
    { label: t('pages.support.priority_medium'), value: 'medium', color: 'amber' },
    { label: t('pages.support.priority_low'),    value: 'low',    color: 'emerald' },
]);

const statusLabel = (v) => statuses.value.find(s => s.value === v)?.label || v;
const statusSeverity = (v) => statuses.value.find(s => s.value === v)?.severity || 'info';
const priorityLabel = (v) => priorities.value.find(p => p.value === v)?.label || v;
const prioritySeverity = (v) => ({ urgent: 'danger', high: 'warning', medium: 'info', low: 'success' }[v] || 'secondary');
const categoryColor = (slug) => props.categories.find(c => c.slug === slug)?.color || '#64748b';

const timeAgo = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return t('pages.support.time_now');
    if (diff < 3600) return t('pages.support.time_minutes', { count: Math.floor(diff / 60) });
    if (diff < 86400) return t('pages.support.time_hours', { count: Math.floor(diff / 3600) });
    return t('pages.support.time_days', { count: Math.floor(diff / 86400) });
};

// ─── FILTRES (serveur) ───
const filterState = ref({
    search: props.filters.search || '',
    status: props.filters.status || null,
    priority: props.filters.priority || null,
    category_id: props.filters.category_id || null,
    assigned_to_user_id: props.filters.assigned_to_user_id || null,
});

const applyFilters = debounce(() => {
    router.get(route('social.support.index'), {
        ...filterState.value,
        unassigned: filterState.value.assigned_to_user_id === 'unassigned' ? 1 : undefined,
    }, { preserveState: true, preserveScroll: true });
}, 400);

watch(filterState, applyFilters, { deep: true });

const onPage = (e) => {
    router.get(route('social.support.index'), {
        ...filterState.value,
        page: e.page + 1,
        per_page: e.rows,
    }, { preserveState: true, preserveScroll: true });
};

const openTicket = (ticket) => {
    router.get(route('social.support.show', ticket.ticket_number));
};

const exportCsv = () => {
    window.location.href = route('social.support.export', filterState.value);
};

// ─── NOUVEAU TICKET ───
const showCreateDialog = ref(false);
const createForm = useForm({
    subject: '',
    ticket_category_id: null,
    priority: 'medium',
    description: '',
});

const submitTicket = () => {
    createForm.post(route('social.support.store'), {
        onSuccess: () => {
            showCreateDialog.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
};

// ─── DÉTAIL : réponse / assignation / statut ───
const replyForm = useForm({ message: '', is_internal: false });
const assignForm = useForm({ assigned_to_user_id: null });
const statusForm = useForm({ status: '' });

const submitReply = () => {
    replyForm.post(route('social.support.reply', props.ticket.ticket_number), {
        preserveScroll: true,
        onSuccess: () => replyForm.reset('message', 'is_internal'),
    });
};

const submitAssign = () => {
    assignForm.post(route('social.support.assign', props.ticket.ticket_number), { preserveScroll: true });
};

const submitStatus = () => {
    statusForm.post(route('social.support.change-status', props.ticket.ticket_number), { preserveScroll: true });
};

const deleteTicket = () => {
    confirm.require({
        message: t('pages.support.delete_confirm_message', { number: props.ticket.ticket_number }),
        header: t('pages.support.delete_confirm_header'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => router.delete(route('social.support.destroy', props.ticket.ticket_number)),
    });
};

const categoryOptions = computed(() => props.categories.map(c => ({ label: c.name, value: c.id })));
const agentOptions = computed(() => props.agents.map(a => ({ label: a.name, value: a.id })));
const priorityOptions = computed(() => priorities.value.map(p => ({ label: p.label, value: p.value })));
const statusOptions = computed(() => statuses.value.map(s => ({ label: s.label, value: s.value })));

const ticketStatus = ref(props.ticket?.status || '');
const ticketAssignee = ref(props.ticket?.assigned_to_user_id || null);

watch(() => props.ticket, (t) => {
    if (t) {
        ticketStatus.value = t.status;
        ticketAssignee.value = t.assigned_to_user_id;
        statusForm.status = t.status;
        assignForm.assigned_to_user_id = t.assigned_to_user_id;
    }
}, { immediate: true });

const saveConfig = () => {
    router.put(route('social.support.update', props.ticket.ticket_number), {
        subject: props.ticket.subject,
        ticket_category_id: props.ticket.ticket_category_id,
        priority: props.ticket.priority,
        status: ticketStatus.value,
        assigned_to_user_id: ticketAssignee.value,
    }, { preserveScroll: true });
};

const kpis = computed(() => [
    { label: t('pages.support.kpi_total'), value: props.stats.total ?? 0, icon: 'pi-ticket', color: 'blue' },
    { label: t('pages.support.kpi_open'), value: props.stats.open ?? 0, icon: 'pi-envelope', color: 'amber' },
    { label: t('pages.support.kpi_pending'), value: props.stats.pending ?? 0, icon: 'pi-clock', color: 'slate' },
    { label: t('pages.support.kpi_resolved'), value: props.stats.resolved ?? 0, icon: 'pi-check-circle', color: 'emerald' },
    { label: t('pages.support.kpi_unassigned'), value: props.stats.unassigned ?? 0, icon: 'pi-user-plus', color: 'red' },
]);
</script>

<template>
    <AppLayout>
        <Head :title="isDetail ? t('pages.support.ticket_title', { number: ticket.ticket_number }) : t('pages.support.center_title')" />
        <SncConfirmDialog />

        <div class="page-bg min-h-screen pb-20">

            <!-- ═══ VUE DÉTAIL D'UN TICKET ═══ -->
            <template v-if="isDetail">
                <PageHeader
                    :title="t('pages.support.ticket_title', { number: ticket.ticket_number })"
                    :subtitle="ticket.subject"
                    icon="pi-ticket"
                    :badge="statusLabel(ticket.status)"
                    accent="blue"
                    :actions="[
                        { label: t('pages.support.back_to_list'), icon: 'pi-arrow-left', onClick: () => router.get(route('social.support.index')), variant: 'secondary' },
                        ...(canManage ? [{ label: t('pages.support.btn_delete'), icon: 'pi-trash', onClick: deleteTicket, variant: 'primary' }] : []),
                    ]"
                />

                <div class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Colonne gauche : demandeur + configuration -->
                    <div class="lg:col-span-4 space-y-6">
                        <Card class="border-none shadow-sm rounded-xl">
                            <template #content>
                                <div class="text-center pb-4">
                                    <Avatar
                                        :image="ticket.user?.profile_photo_url"
                                        :label="(ticket.user?.name || '?').charAt(0)"
                                        size="xlarge" shape="circle"
                                        class="!w-20 !h-20 !text-2xl !mb-3"
                                    />
                                    <h3 class="font-black text-slate-800">{{ ticket.user?.name }}</h3>
                                    <p class="text-sm text-slate-500">{{ ticket.user?.email }}</p>
                                    <div class="flex justify-center gap-2 mt-3">
                                        <Tag :value="statusLabel(ticket.status)" :severity="statusSeverity(ticket.status)" class="!text-[10px] !font-bold uppercase" />
                                        <Tag :value="priorityLabel(ticket.priority)" :severity="prioritySeverity(ticket.priority)" class="!text-[10px] !font-bold uppercase" />
                                    </div>
                                </div>
                                <Divider />
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold text-xs uppercase">{{ t('pages.support.col_category') }}</span>
                                        <span class="font-semibold text-slate-700">{{ ticket.category?.name || '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold text-xs uppercase">{{ t('pages.support.col_created') }}</span>
                                        <span class="font-semibold text-slate-700">{{ ticket.created_at ? new Date(ticket.created_at).toLocaleString(locale) : '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold text-xs uppercase">{{ t('pages.support.col_assigned_to') }}</span>
                                        <span class="font-semibold text-slate-700">{{ ticket.assigned_agent?.name || t('pages.support.unassigned') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400 font-bold text-xs uppercase">{{ t('pages.support.col_messages') }}</span>
                                        <span class="font-semibold text-slate-700">{{ messages?.total ?? 0 }}</span>
                                    </div>
                                </div>

                                <!-- Description initiale -->
                                <Divider />
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ t('pages.support.initial_description') }}</p>
                                    <p class="text-sm text-slate-600 whitespace-pre-line bg-slate-50 rounded-xl p-4 border border-slate-100">{{ ticket.description }}</p>
                                </div>
                            </template>
                        </Card>

                        <!-- Configuration (staff) -->
                        <Card v-if="canManage" class="border-none shadow-sm rounded-xl">
                            <template #title><span class="text-sm font-black text-slate-700 px-1">{{ t('pages.support.configuration') }}</span></template>
                            <template #content>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 block mb-1">{{ t('pages.support.field_status') }}</label>
                                        <Select v-model="ticketStatus" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" @change="saveConfig" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 block mb-1">{{ t('pages.support.field_assign_to') }}</label>
                                        <Select v-model="ticketAssignee" :options="agentOptions" optionLabel="label" optionValue="value" class="w-full" :placeholder="t('pages.support.choose_agent')" @change="saveConfig" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-slate-500 block mb-1">{{ t('pages.support.field_priority') }}</label>
                                        <Select
                                            v-model="ticket.priority"
                                            :options="priorityOptions" optionLabel="label" optionValue="value"
                                            class="w-full"
                                            @change="router.put(route('social.support.update', ticket.ticket_number), {
                                                subject: ticket.subject,
                                                ticket_category_id: ticket.ticket_category_id,
                                                priority: ticket.priority,
                                                status: ticketStatus,
                                                assigned_to_user_id: ticketAssignee,
                                            }, { preserveScroll: true })"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- Colonne droite : conversation -->
                    <div class="lg:col-span-8">
                        <Card class="border-none shadow-sm rounded-xl h-full">
                            <template #title>
                                <div class="flex items-center justify-between px-1">
                                    <span class="text-sm font-black text-slate-700">{{ t('pages.support.conversation') }}</span>
                                    <span class="text-xs text-slate-400">{{ t('pages.support.messages_count', { count: messages?.total ?? 0 }) }}</span>
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4 max-h-[55vh] overflow-y-auto pr-2 mb-4">
                                    <div v-if="!messages?.data?.length" class="text-center py-10 text-slate-400 text-sm">
                                        {{ t('pages.support.no_messages') }}
                                    </div>
                                    <div
                                        v-for="msg in messages?.data || []"
                                        :key="msg.id"
                                        :class="['flex flex-col', msg.user_id === auth?.user?.id ? 'items-end' : 'items-start']"
                                    >
                                        <div :class="[
                                            'max-w-[85%] p-4 rounded-2xl text-sm shadow-sm',
                                            msg.is_internal
                                                ? 'bg-amber-50 border border-amber-200 text-amber-900'
                                                : msg.user_id === auth?.user?.id
                                                    ? 'bg-blue-600 text-white rounded-tr-sm'
                                                    : 'bg-slate-100 text-slate-700 rounded-tl-sm'
                                        ]">
                                            <div v-if="msg.is_internal" class="flex items-center gap-1 text-[10px] font-black uppercase mb-1">
                                                <i class="pi pi-lock"></i> {{ t('pages.support.internal_note') }}
                                            </div>
                                            <p class="whitespace-pre-line">{{ msg.message }}</p>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 mt-1.5 uppercase tracking-tight">
                                            {{ msg.user?.name || t('pages.support.default_user') }}
                                            <span v-if="msg.user?.id === ticket.user_id">{{ t('pages.support.author_suffix') }}</span>
                                            • {{ msg.created_at ? new Date(msg.created_at).toLocaleString(locale) : '' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Répondre -->
                                <Divider />
                                <form @submit.prevent="submitReply" class="space-y-3">
                                    <Textarea
                                        v-model="replyForm.message"
                                        rows="3" autoResize
                                        :placeholder="t('pages.support.reply_placeholder')"
                                        class="w-full !rounded-xl"
                                    />
                                    <div v-if="canManage" class="flex items-center gap-2">
                                        <InputSwitch v-model="replyForm.is_internal" />
                                        <label class="text-xs font-bold text-slate-500">{{ t('pages.support.internal_note_hint') }}</label>
                                    </div>
                                    <div class="flex justify-end">
                                        <Button
                                            type="submit"
                                            :label="t('pages.support.btn_send')"
                                            icon="pi pi-send"
                                            :loading="replyForm.processing"
                                            :disabled="!replyForm.message?.trim()"
                                            class="!rounded-xl"
                                        />
                                    </div>
                                </form>
                            </template>
                        </Card>
                    </div>
                </div>
            </template>

            <!-- ═══ VUE LISTE ═══ -->
            <template v-else>
                <PageHeader
                    :title="t('pages.support.center_title')"
                    :subtitle="t('pages.support.center_subtitle')"
                    icon="pi-headphones"
                    :badge="t('pages.support.tickets_badge', { count: stats.total ?? 0 })"
                    accent="blue"
                    :actions="[
                        ...(canManage ? [{ label: t('pages.support.btn_export_csv'), icon: 'pi-download', onClick: exportCsv, variant: 'secondary' }] : []),
                        { label: t('pages.support.btn_new_ticket'), icon: 'pi-plus', onClick: () => showCreateDialog = true, variant: 'primary' },
                    ]"
                />

                <!-- KPIs -->
                <div class="max-w-[1500px] mx-auto px-4 lg:px-6 mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div v-for="kpi in kpis" :key="kpi.label" class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                        <div :class="`p-3 rounded-xl bg-${kpi.color}-50 text-${kpi.color}-500`">
                            <i :class="`pi ${kpi.icon} text-xl`"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</p>
                            <p class="text-2xl font-black text-slate-800">{{ kpi.value }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="max-w-[1500px] mx-auto px-4 lg:px-6 mt-6">
                    <Card class="border-none shadow-sm rounded-xl">
                        <template #content>
                            <div class="flex flex-col md:flex-row gap-3 items-center">
                                <IconField iconPosition="left" class="flex-1 w-full">
                                    <InputIcon class="pi pi-search text-slate-400" />
                                    <InputText v-model="filterState.search" :placeholder="t('pages.support.search_placeholder')" class="w-full" />
                                </IconField>
                                <Select v-model="filterState.status" :options="statuses" optionLabel="label" optionValue="value" :placeholder="t('pages.support.filter_status')" class="w-full md:w-44" showClear />
                                <Select v-model="filterState.priority" :options="priorities" optionLabel="label" optionValue="value" :placeholder="t('pages.support.filter_priority')" class="w-full md:w-40" showClear />
                                <Select v-model="filterState.category_id" :options="categoryOptions" optionLabel="label" optionValue="value" :placeholder="t('pages.support.filter_category')" class="w-full md:w-52" showClear />
                                <Select v-model="filterState.assigned_to_user_id" :options="[{label:t('pages.support.unassigned_plural'),value:'unassigned'}, ...agentOptions]" optionLabel="label" optionValue="value" :placeholder="t('pages.support.filter_assignment')" class="w-full md:w-48" showClear />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Table -->
                <div class="max-w-[1500px] mx-auto px-4 lg:px-6 mt-6">
                    <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                        <template #content>
                            <DataTable
                                :value="tickets?.data || []"
                                :loading="!tickets"
                                :paginator="true"
                                :rows="tickets?.per_page || 15"
                                :totalRecords="tickets?.total || 0"
                                :first="((tickets?.current_page || 1) - 1) * (tickets?.per_page || 15)"
                                :lazy="true"
                                @page="onPage"
                                dataKey="id"
                                stripedRows
                                class="p-datatable-lg"
                            >
                                <Column field="ticket_number" :header="t('pages.support.col_number')" style="width: 8rem">
                                    <template #body="{ data }">
                                        <span class="font-mono font-bold text-slate-500 text-xs">{{ data.ticket_number }}</span>
                                    </template>
                                </Column>

                                <Column :header="t('pages.support.col_requester')" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <Avatar :image="data.user?.profile_photo_url" :label="(data.user?.name || '?').charAt(0)" shape="circle" size="normal" />
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 text-sm">{{ data.user?.name || '—' }}</span>
                                                <span class="text-[10px] text-slate-400">{{ data.user?.email }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="subject" :header="t('pages.support.col_subject')" style="min-width: 18rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <button class="font-black text-slate-700 text-sm text-left hover:text-blue-600 transition-colors" @click="openTicket(data)">
                                                {{ data.subject }}
                                            </button>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-black uppercase px-2 py-0.5 rounded"
                                                    :style="{ backgroundColor: categoryColor(data.category?.slug) + '18', color: categoryColor(data.category?.slug) }"
                                                >{{ data.category?.name || '—' }}</span>
                                                <span class="text-[10px] text-slate-400">{{ timeAgo(data.created_at) }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="priority" :header="t('pages.support.col_priority')" style="width: 7rem">
                                    <template #body="{ data }">
                                        <Tag :value="priorityLabel(data.priority)" :severity="prioritySeverity(data.priority)" class="!text-[9px] !font-black uppercase" />
                                    </template>
                                </Column>

                                <Column field="status" :header="t('pages.support.col_status')" style="width: 9rem">
                                    <template #body="{ data }">
                                        <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[9px] !font-black uppercase" />
                                    </template>
                                </Column>

                                <Column :header="t('pages.support.col_agent')" style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span class="text-sm font-semibold" :class="data.assigned_agent ? 'text-slate-700' : 'text-slate-300 italic'">
                                            {{ data.assigned_agent?.name || t('pages.support.unassigned') }}
                                        </span>
                                    </template>
                                </Column>

                                <Column header="" style="width: 4rem" alignFrozen="right" frozen>
                                    <template #body="{ data }">
                                        <Button icon="pi pi-chevron-right" text rounded severity="secondary" @click="openTicket(data)" />
                                    </template>
                                </Column>

                                <template #empty>
                                    <div class="text-center py-16">
                                        <i class="pi pi-inbox text-4xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-500 font-semibold">{{ t('pages.support.empty_tickets') }}</p>
                                    </div>
                                </template>
                            </DataTable>
                        </template>
                    </Card>
                </div>
            </template>

            <!-- ═══ DIALOGUE : NOUVEAU TICKET ═══ -->
            <Dialog v-model:visible="showCreateDialog" modal :header="t('pages.support.dialog_title')" :style="{ width: '560px' }" class="p-fluid">
                <form @submit.prevent="submitTicket" class="space-y-5 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ t('pages.support.field_subject') }}</label>
                        <InputText v-model="createForm.subject" class="w-full" :placeholder="t('pages.support.subject_placeholder')" />
                        <p v-if="createForm.errors.subject" class="text-red-500 text-xs mt-1">{{ createForm.errors.subject }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ t('pages.support.field_category') }}</label>
                            <Select v-model="createForm.ticket_category_id" :options="categoryOptions" optionLabel="label" optionValue="value" :placeholder="t('pages.support.choose_placeholder')" class="w-full" />
                            <p v-if="createForm.errors.ticket_category_id" class="text-red-500 text-xs mt-1">{{ createForm.errors.ticket_category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ t('pages.support.field_priority') }}</label>
                            <Select v-model="createForm.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ t('pages.support.field_description') }}</label>
                        <Textarea v-model="createForm.description" rows="6" class="w-full" :placeholder="t('pages.support.description_placeholder')" />
                        <p v-if="createForm.errors.description" class="text-red-500 text-xs mt-1">{{ createForm.errors.description }}</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <Button type="button" :label="t('pages.support.btn_cancel')" text severity="secondary" @click="showCreateDialog = false" />
                        <Button type="submit" :label="t('pages.support.btn_create_ticket')" icon="pi pi-send" :loading="createForm.processing" />
                    </div>
                </form>
            </Dialog>
        </div>
    </AppLayout>
</template>
