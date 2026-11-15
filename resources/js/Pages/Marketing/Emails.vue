<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';
import Editor from 'primevue/editor';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Menu from 'primevue/menu';
import { useI18n } from 'vue-i18n';
import SncEmailChips from '@/Components/EmailChipsInput.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';

const props = defineProps({
    campaigns: {
        type: Object,
        default: () => ({ data: [], total: 0, per_page: 15, current_page: 1, last_page: 1 }),
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, active: 0, sent: 0, draft: 0, sent_total: 0 }),
    },
    filters: { type: Object, default: () => ({ search: '', status: '' }) },
    mode: { type: String, default: 'index' },
    campaign: { type: Object, default: null },
    recipientsCount: { type: Number, default: 0 },
    recipientsPreview: { type: Array, default: () => [] },
    audienceGroups: { type: Array, default: () => [] },
});

const confirmAction = useConfirmAction();
const { t } = useI18n();

// --- Filtres (recherche + statut) via route Inertia ---
const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');

const statusOptions = [
    { label: 'Tous les statuts', value: '' },
    { label: 'Brouillon', value: 'draft' },
    { label: 'Programmée', value: 'scheduled' },
    { label: "En file d'envoi", value: 'queued' },
    { label: 'Envoyée', value: 'sent' },
];

const audienceOptions = computed(() => [
    { label: 'Tous les utilisateurs', value: 'all' },
    { label: 'Étudiants', value: 'students' },
    { label: 'Formateurs', value: 'instructors' },
    { label: 'Groupe d\'audience personnalisé', value: 'group' },
]);

const groupOptions = computed(() =>
    (props.audienceGroups || []).map((g) => ({
        label: `${g.name} (${g.members?.length ?? 0} contact${(g.members?.length ?? 0) > 1 ? 's' : ''})`,
        value: g.id,
    }))
);

const selectedGroup = computed(() =>
    (props.audienceGroups || []).find((g) => g.id === form.audience_group_id) ?? null
);

const applyFilters = () => {
    router.get(route('marketing.emails.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const onPage = (event) => {
    router.get(route('marketing.emails.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        page: event.page + 1,
    }, { preserveState: true, preserveScroll: true });
};

// --- Formulaire création / édition (même page, mode create/edit) ---
const emptyForm = () => ({
    name: '',
    subject: '',
    from_name: '',
    from_email: '',
    body: '',
    target_audience: 'all',
    audience_group_id: null,
    extra_emails: [],
    excluded_emails: [],
    status: 'draft',
    scheduled_at: null,
});

const form = useForm(emptyForm());
const formOpen = ref(props.mode === 'create' || props.mode === 'edit');
const editingId = ref(props.mode === 'edit' ? props.campaign?.id ?? null : null);

const fillFromCampaign = (c) => {
    if (!c) return;
    form.name = c.name ?? '';
    form.subject = c.subject ?? '';
    form.from_name = c.from_name ?? '';
    form.from_email = c.from_email ?? '';
    form.body = c.body ?? '';
    form.target_audience = c.target_audience ?? 'all';
    form.audience_group_id = c.audience_group_id ?? null;
    form.extra_emails = Array.isArray(c.extra_emails) ? [...c.extra_emails] : [];
    form.excluded_emails = Array.isArray(c.excluded_emails) ? [...c.excluded_emails] : [];
    form.status = c.status === 'sent' ? 'draft' : (c.status ?? 'draft');
    form.scheduled_at = c.scheduled_at ?? null;
};

watch(() => props.mode, (mode) => {
    if (mode === 'create') {
        editingId.value = null;
        form.clearErrors();
        form.defaults(emptyForm()).reset();
        formOpen.value = true;
    } else if (mode === 'edit') {
        editingId.value = props.campaign?.id ?? null;
        form.clearErrors();
        fillFromCampaign(props.campaign);
        formOpen.value = true;
    }
}, { immediate: true });

const openCreate = () => {
    editingId.value = null;
    form.clearErrors();
    form.defaults(emptyForm()).reset();
    formOpen.value = true;
};

const openEdit = (c) => {
    editingId.value = c.id;
    form.clearErrors();
    fillFromCampaign(c);
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
    form.clearErrors();
    if (props.mode !== 'index') {
        router.get(route('marketing.emails.index'), {}, { replace: true, preserveScroll: true });
    }
};

const submitWithStatus = (status) => {
    form.status = status;
    const options = {
        preserveScroll: true,
        onSuccess: () => { formOpen.value = false; form.defaults(emptyForm()).reset(); },
    };
    if (editingId.value) {
        form.put(route('marketing.emails.update', editingId.value), options);
    } else {
        form.post(route('marketing.emails.store'), options);
    }
};

// --- Aperçu (mode show) ---
const previewOpen = ref(props.mode === 'show');
watch(() => props.mode, (mode) => { previewOpen.value = mode === 'show'; });

const closePreview = () => {
    previewOpen.value = false;
    if (props.mode !== 'index') {
        router.get(route('marketing.emails.index'), {}, { replace: true, preserveScroll: true });
    }
};

// --- Envoi test ---
const testForm = useForm({ test_email: '' });
const testDialogOpen = ref(false);
const testCampaignId = ref(null);

const openTest = (c) => {
    testCampaignId.value = c.id;
    testForm.clearErrors();
    testForm.reset();
    testDialogOpen.value = true;
};

const submitTest = () => {
    testForm.post(route('marketing.emails.send-test', testCampaignId.value), {
        preserveScroll: true,
        onSuccess: () => { testDialogOpen.value = false; testForm.reset(); },
    });
};

// --- Groupes d'audience (CRUD JSON) ---
const groupsOpen = ref(false);
const groupsLoading = ref(false);
const groupsError = ref('');
const groups = ref([]);
const newGroup = ref({ name: '', description: '' });
const memberDrafts = ref({}); // { [groupId]: email string }
const memberErrors = ref({});

const syncGroups = () => {
    groups.value = (props.audienceGroups || []).map((g) => ({
        ...g,
        members: [...(g.members || [])],
    }));
};

const openGroups = async () => {
    groupsOpen.value = true;
    groupsLoading.value = true;
    groupsError.value = '';
    try {
        const { data } = await axios.get(route('marketing.audience-groups.index'));
        groups.value = data.groups || [];
    } catch (e) {
        groupsError.value = 'Impossible de charger les groupes.';
    } finally {
        groupsLoading.value = false;
    }
};

const reloadGroups = async () => {
    const { data } = await axios.get(route('marketing.audience-groups.index'));
    groups.value = data.groups || [];
    router.reload({ only: ['audienceGroups'] });
};

const createGroup = async () => {
    groupsError.value = '';
    try {
        await axios.post(route('marketing.audience-groups.store'), {
            name: newGroup.value.name.trim(),
            description: newGroup.value.description.trim() || null,
        });
        newGroup.value = { name: '', description: '' };
        await reloadGroups();
    } catch (e) {
        groupsError.value = e.response?.data?.message || 'Erreur lors de la création du groupe.';
    }
};

const deleteGroup = async (g) => {
    confirmAction({
        message: `Supprimer le groupe « ${g.name} » ? Les campagnes qui l'utilisent perdront leur audience.`,
        header: 'Supprimer le groupe',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: async () => {
            try {
                await axios.delete(route('marketing.audience-groups.destroy', g.id));
                if (form.audience_group_id === g.id) form.audience_group_id = null;
                await reloadGroups();
            } catch (e) {
                groupsError.value = 'Impossible de supprimer le groupe.';
            }
        },
    });
};

const addMember = async (g) => {
    const raw = (memberDrafts.value[g.id] || '').trim();
    if (!raw) return;
    const emails = raw.split(/[\s,;]+/).map((s) => s.trim().toLowerCase()).filter(Boolean);
    const invalid = emails.filter((e) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e));
    memberErrors.value[g.id] = invalid.length ? `Email invalide : ${invalid.join(', ')}` : '';
    const valid = emails.filter((e) => !invalid.includes(e));
    if (!valid.length) return;
    try {
        await axios.post(route('marketing.audience-groups.members.store', g.id), { emails: valid });
        memberDrafts.value[g.id] = '';
        await reloadGroups();
    } catch (e) {
        memberErrors.value[g.id] = e.response?.data?.message || 'Erreur lors de l\'ajout.';
    }
};

const removeMember = async (g, m) => {
    try {
        await axios.delete(route('marketing.audience-groups.members.destroy', [g.id, m.id]));
        await reloadGroups();
    } catch (e) {
        memberErrors.value[g.id] = 'Impossible de retirer ce contact.';
    }
};

// --- Suppression / Duplication ---
const removeCampaign = (c) => {
    confirmAction({
        message: `Supprimer définitivement « ${c.name || c.subject} » ?`,
        header: 'Supprimer la campagne',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('marketing.emails.destroy', c.id), { preserveScroll: true }),
    });
};

const duplicateCampaign = (c) => {
    router.get(route('marketing.emails.duplicate', c.id));
};

const viewCampaign = (c) => {
    router.get(route('marketing.emails.show', c.id));
};

// --- Helpers affichage ---
const formatDate = (d) => d
    ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(d))
    : '—';

const statusLabel = (s) => ({
    draft: 'Brouillon',
    scheduled: 'Programmée',
    queued: "En file d'envoi",
    sent: 'Envoyée',
}[s] ?? s);

const statusSeverity = (s) => ({
    draft: 'secondary',
    scheduled: 'info',
    queued: 'warn',
    sent: 'success',
}[s] ?? 'secondary');

const audienceLabel = (a, c) => ({
    all: 'Tous',
    students: 'Étudiants',
    instructors: 'Formateurs',
    group: c?.audience_group?.name ? `Groupe : ${c.audience_group.name}` : 'Groupe',
}[a] ?? a);

const kpis = computed(() => [
    { label: 'Total campagnes', value: props.stats?.total ?? 0, icon: 'pi pi-megaphone', color: 'bg-blue-50 text-blue-600' },
    { label: 'Actives', value: props.stats?.active ?? 0, icon: 'pi pi-bolt', color: 'bg-amber-50 text-amber-600' },
    { label: 'Envoyées', value: props.stats?.sent ?? 0, icon: 'pi pi-send', color: 'bg-emerald-50 text-emerald-600' },
    { label: 'Emails délivrés', value: props.stats?.sent_total ?? 0, icon: 'pi pi-envelope', color: 'bg-sky-50 text-sky-600' },
]);

const rowMenu = ref(null);
const rowMenuTarget = ref(null);

const toggleRowMenu = (event, row) => {
    rowMenuTarget.value = row;
    rowMenu.value?.toggle(event);
};

const rowMenuItems = computed(() => {
    const row = rowMenuTarget.value;
    if (!row) return [];
    const items = [
        { label: 'Voir', icon: 'pi pi-eye', command: () => viewCampaign(row) },
        { label: 'Dupliquer', icon: 'pi pi-copy', command: () => duplicateCampaign(row) },
        { label: 'Envoyer un test', icon: 'pi pi-send', command: () => openTest(row) },
    ];
    if (row.status !== 'sent') {
        items.push({ label: 'Modifier', icon: 'pi pi-pencil', command: () => openEdit(row) });
    }
    if (['draft', 'scheduled'].includes(row.status)) {
        items.push({ separator: true });
        items.push({ label: 'Supprimer', icon: 'pi pi-trash', command: () => removeCampaign(row), class: 'text-red-600' });
    }
    return items;
});
</script>

<template>
    <AppLayout>
        <Head title="Campagnes email — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Campagnes email"
                subtitle="Créez, ciblez et envoyez vos campagnes à votre audience"
                icon="pi-envelope"
                accent="emerald"
                badge="Marketing"
                :actions="[
                    { label: 'Groupes d\'audience', icon: 'pi-users', variant: 'secondary', onClick: openGroups },
                    { label: 'Nouvelle campagne', icon: 'pi-plus', variant: 'primary', onClick: openCreate },
                ]"
            />

            <div class="page-container page-section">
            <!-- KPI RÉELS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div v-for="kpi in kpis" :key="kpi.label" class="page-card flex items-center gap-4">
                    <div class="p-3 rounded-xl" :class="kpi.color">
                        <i :class="kpi.icon" class="text-xl" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</p>
                        <p class="text-2xl font-black text-slate-800 tabular-nums">{{ kpi.value }}</p>
                    </div>
                </div>
            </div>

            <!-- TABLE DES CAMPAGNES -->
            <div class="page-card">
                <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-2 sm:gap-4 mb-4">
                        <h2 class="text-lg font-black text-slate-800">Vos campagnes</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-72">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Rechercher un nom ou un objet…" class="w-full h-11" aria-label="Rechercher une campagne" @keyup.enter="applyFilters" />
                        </IconField>
                        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full sm:w-48" aria-label="Filtrer par statut" @change="applyFilters" />
                        <Button icon="pi pi-search" aria-label="Lancer la recherche" @click="applyFilters" />
                    </div>
                </div>

                <DataTable :value="campaigns?.data ?? []" dataKey="id" :lazy="true" :paginator="true"
                           :rows="campaigns?.per_page ?? 15" :totalRecords="campaigns?.total ?? 0"
                           :first="((campaigns?.current_page ?? 1) - 1) * (campaigns?.per_page ?? 15)"
                           @page="onPage" responsiveLayout="scroll" class="p-datatable-sm p-datatable-custom p-datatable-mobile border-none">
                    <Column header="Nom" style="min-width: 0">
                        <template #body="{ data }">
                            <div class="flex flex-col min-w-0 px-1 py-1">
                                <span class="font-bold text-slate-800 truncate">{{ data.name || '(Sans nom)' }}</span>
                                <span class="md:hidden text-[10px] text-slate-400 line-clamp-1">{{ data.subject }}</span>
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="md:hidden w-fit mt-0.5 text-[9px]" />
                            </div>
                        </template>
                    </Column>
                    <Column field="subject" header="Objet" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="min-width: 16rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 line-clamp-1">{{ data.subject }}</span>
                        </template>
                    </Column>
                    <Column header="Audience" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="width: 9rem">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-600">{{ audienceLabel(data.target_audience, data) }}</span>
                        </template>
                    </Column>
                    <Column header="Statut" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" style="width: 9rem">
                        <template #body="{ data }">
                            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column header="Envoyés" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="width: 7rem">
                        <template #body="{ data }">
                            <span class="tabular-nums font-bold text-slate-800">{{ data.sent_count ?? 0 }}</span>
                        </template>
                    </Column>
                    <Column header="Créée le" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" style="width: 11rem">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-500">{{ formatDate(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 3.5rem; min-width: 3.5rem">
                        <template #body="{ data }">
                            <div class="hidden md:flex items-center gap-1 whitespace-nowrap">
                                <Button icon="pi pi-eye" text rounded severity="secondary" v-tooltip.top="'Voir'" aria-label="Voir la campagne" @click="viewCampaign(data)" />
                                <Button icon="pi pi-copy" text rounded severity="secondary" v-tooltip.top="'Dupliquer'" aria-label="Dupliquer la campagne" @click="duplicateCampaign(data)" />
                                <Button icon="pi pi-send" text rounded severity="info" v-tooltip.top="'Envoyer un test'" aria-label="Envoyer un email de test" @click="openTest(data)" />
                                <Button v-if="data.status !== 'sent'" icon="pi pi-pencil" text rounded severity="secondary" v-tooltip.top="'Modifier'" aria-label="Modifier la campagne" @click="openEdit(data)" />
                                <Button v-if="['draft', 'scheduled'].includes(data.status)" icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Supprimer'" aria-label="Supprimer la campagne" @click="removeCampaign(data)" />
                            </div>
                            <Button
                                icon="pi pi-ellipsis-v"
                                class="p-button-text p-button-secondary p-button-rounded w-10 h-10 md:hidden text-slate-500"
                                :aria-label="t('pages.live_sessions.menu_more')"
                                :aria-haspopup="true"
                                @click="toggleRowMenu($event, data)"
                            />
                        </template>
                    </Column>

                    <template #empty>
                        <div class="py-16 text-center">
                            <i class="pi pi-inbox text-4xl text-slate-300 mb-4" aria-hidden="true"></i>
                            <p class="font-bold text-slate-700">Aucune campagne pour le moment</p>
                            <p class="text-sm text-slate-500 mt-1">Créez votre première campagne avec le bouton « Nouvelle campagne ».</p>
                        </div>
                    </template>
                </DataTable>
            </div>
            </div>
        </div>

        <Menu id="campaign-row-menu" ref="rowMenu" :model="rowMenuItems" popup :aria-label="t('pages.live_sessions.menu_more')" />

        <!-- DIALOGUE : CRÉATION / ÉDITION (même page) -->
        <SncDialog
            v-model:visible="formOpen"
            :title="editingId ? 'Modifier la campagne' : 'Nouvelle campagne'"
            subtitle="Rédigez le message, choisissez l'audience et planifiez l'envoi"
            :icon="editingId ? 'pi-pencil' : 'pi-megaphone'"
            :icon-class="editingId ? 'from-amber-500 to-orange-500' : 'from-blue-600 to-indigo-600'"
            width="760px"
            @hide="closeForm"
        >
            <form class="flex flex-col gap-5" @submit.prevent="submitWithStatus(form.status)">
                <SncSection icon="pi-file-edit" title="Contenu du message" subtitle="Ce que les destinataires verront" icon-class="from-blue-500 to-indigo-600">
                    <div class="flex flex-col gap-4">
                        <div class="field">
                            <label for="campaign-name" class="font-bold text-slate-700 text-sm mb-1 block">Nom <span class="text-rose-500">*</span></label>
                            <InputText id="campaign-name" v-model="form.name" class="w-full" :invalid="!!form.errors.name" placeholder="ex : Newsletter de novembre" />
                            <small v-if="form.errors.name" class="text-rose-500" role="alert">{{ form.errors.name }}</small>
                        </div>

                        <div class="field">
                            <label for="campaign-subject" class="font-bold text-slate-700 text-sm mb-1 block">Objet <span class="text-rose-500">*</span></label>
                            <InputText id="campaign-subject" v-model="form.subject" class="w-full" :invalid="!!form.errors.subject" placeholder="Objet visible par les destinataires" />
                            <small v-if="form.errors.subject" class="text-rose-500" role="alert">{{ form.errors.subject }}</small>
                        </div>

                        <div class="field">
                            <label for="campaign-body" class="font-bold text-slate-700 text-sm mb-1 block">Corps du message <span class="text-rose-500">*</span></label>
                            <Editor id="campaign-body" v-model="form.body" editorStyle="height: 220px" />
                            <small v-if="form.errors.body" class="text-rose-500" role="alert">{{ form.errors.body }}</small>
                        </div>
                    </div>
                </SncSection>

                <SncSection icon="pi-send" title="Expédition & audience" subtitle="Expéditeur et destinataires ciblés" icon-class="from-emerald-500 to-teal-600">
                    <div class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="field">
                                <label for="campaign-from-name" class="font-bold text-slate-700 text-sm mb-1 block">Nom d'expéditeur</label>
                                <InputText id="campaign-from-name" v-model="form.from_name" class="w-full" :invalid="!!form.errors.from_name" placeholder="SubNetCongo Academy" />
                                <small v-if="form.errors.from_name" class="text-rose-500" role="alert">{{ form.errors.from_name }}</small>
                            </div>
                            <div class="field">
                                <label for="campaign-from-email" class="font-bold text-slate-700 text-sm mb-1 block">Email d'expéditeur</label>
                                <InputText id="campaign-from-email" v-model="form.from_email" class="w-full" :invalid="!!form.errors.from_email" placeholder="academy@subnetcongo.net" />
                                <small v-if="form.errors.from_email" class="text-rose-500" role="alert">{{ form.errors.from_email }}</small>
                            </div>
                        </div>

                        <div class="field">
                            <label for="campaign-audience" class="font-bold text-slate-700 text-sm mb-1 block">Audience <span class="text-rose-500">*</span></label>
                            <Select id="campaign-audience" v-model="form.target_audience" :options="audienceOptions" optionLabel="label" optionValue="value" class="w-full" :invalid="!!form.errors.target_audience" />
                            <small v-if="form.errors.target_audience" class="text-rose-500" role="alert">{{ form.errors.target_audience }}</small>
                        </div>

                        <div v-if="form.target_audience === 'group'" class="field">
                            <label for="campaign-group" class="font-bold text-slate-700 text-sm mb-1 block">Groupe d'audience <span class="text-rose-500">*</span></label>
                            <Select
                                id="campaign-group"
                                v-model="form.audience_group_id"
                                :options="groupOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Choisir un groupe…"
                                class="w-full"
                                :invalid="!!form.errors.audience_group_id"
                                :disabled="!groupOptions.length"
                            />
                            <div class="flex items-center justify-between mt-1.5">
                                <small v-if="form.errors.audience_group_id" class="text-rose-500" role="alert">{{ form.errors.audience_group_id }}</small>
                                <small v-else-if="selectedGroup" class="text-slate-400">
                                    {{ selectedGroup.members?.length ?? 0 }} contact{{ (selectedGroup.members?.length ?? 0) > 1 ? 's' : '' }} dans ce groupe
                                </small>
                                <small v-else-if="!groupOptions.length" class="text-slate-400">Aucun groupe — créez-en un via « Groupes d'audience »</small>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-4">
                            <SncEmailChips
                                v-model="form.extra_emails"
                                label="Emails supplémentaires"
                                placeholder="ajouter un email…"
                                hint="Ces adresses recevront la campagne en plus de l'audience sélectionnée."
                            />
                            <SncEmailChips
                                v-model="form.excluded_emails"
                                label="Emails à exclure"
                                placeholder="exclure un email…"
                                hint="Ces adresses ne recevront pas la campagne, même si elles font partie de l'audience."
                            />
                        </div>
                    </div>
                </SncSection>
            </form>

            <template #footer>
                <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="closeForm" />
                <Button label="Enregistrer le brouillon" icon="pi pi-save" outlined severity="secondary" :loading="form.processing" @click="submitWithStatus('draft')" />
                <Button label="Envoyer maintenant" icon="pi pi-send" :loading="form.processing" @click="submitWithStatus('queued')" />
            </template>
        </SncDialog>

        <!-- DIALOGUE : APERÇU (mode show) -->
        <SncDialog
            v-model:visible="previewOpen"
            title="Aperçu de la campagne"
            subtitle="Statistiques, contenu et destinataires ciblés"
            icon="pi-eye"
            icon-class="from-violet-500 to-purple-600"
            width="760px"
            @hide="closePreview"
        >
            <div v-if="campaign" class="flex flex-col gap-5">
                <SncSection icon="pi-chart-bar" title="Statistiques d'envoi" icon-class="from-blue-500 to-indigo-600">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase">Statut</p>
                            <Tag :value="statusLabel(campaign.status)" :severity="statusSeverity(campaign.status)" class="mt-1" />
                        </div>
                        <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase">Audience</p>
                            <p class="font-bold text-slate-800 mt-1">{{ audienceLabel(campaign.target_audience, campaign) }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase">Envoyés</p>
                            <p class="font-bold text-slate-800 mt-1 tabular-nums">{{ campaign.sent_count ?? 0 }} / {{ campaign.total_recipients ?? 0 }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase">Échecs</p>
                            <p class="font-bold text-slate-800 mt-1 tabular-nums">{{ campaign.bounces_count ?? 0 }}</p>
                        </div>
                    </div>
                </SncSection>

                <SncSection icon="pi-envelope" title="Contenu" subtitle="Objet et corps du message" icon-class="from-emerald-500 to-teal-600">
                    <div class="flex flex-col gap-4">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Objet</p>
                            <p class="font-bold text-slate-800">{{ campaign.subject }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Corps</p>
                            <!-- HTML rédigé par un administrateur, rendu tel quel -->
                            <div class="border border-slate-200 rounded-xl p-4 max-h-64 overflow-y-auto text-sm text-slate-700 bg-white" v-html="campaign.body"></div>
                        </div>
                    </div>
                </SncSection>

                <SncSection icon="pi-users" title="Destinataires ciblés" :subtitle="`${recipientsCount} destinataire(s)`" icon-class="from-amber-500 to-orange-500">
                    <div class="border border-slate-200 rounded-xl max-h-48 overflow-y-auto divide-y divide-slate-100 bg-white">
                        <div v-for="r in recipientsPreview" :key="r.id" class="flex justify-between items-center px-4 py-2 text-sm">
                            <span class="font-medium text-slate-700">{{ r.name }}</span>
                            <span class="text-slate-400">{{ r.email }}</span>
                        </div>
                        <p v-if="recipientsCount > recipientsPreview.length" class="px-4 py-2 text-xs text-slate-400 italic">
                            … et {{ recipientsCount - recipientsPreview.length }} autre(s) destinataire(s).
                        </p>
                        <p v-if="recipientsCount === 0" class="px-4 py-3 text-sm text-slate-400 italic">Aucun destinataire pour cette audience.</p>
                    </div>
                </SncSection>
            </div>

            <template #footer>
                <Button v-if="campaign && campaign.status !== 'sent'" label="Modifier" icon="pi pi-pencil" outlined severity="secondary" @click="closePreview(); openEdit(campaign)" />
                <Button label="Fermer" icon="pi pi-times" text severity="secondary" @click="closePreview" />
            </template>
        </SncDialog>

        <!-- DIALOGUE : ENVOI TEST -->
        <SncDialog
            v-model:visible="testDialogOpen"
            title="Envoyer un email de test"
            subtitle="Un aperçu de la campagne sera envoyé immédiatement"
            icon="pi-send"
            icon-class="from-cyan-500 to-blue-600"
            width="440px"
        >
            <form class="flex flex-col gap-4" @submit.prevent="submitTest">
                <div class="field">
                    <label for="test-email" class="font-bold text-slate-700 text-sm mb-1 block">Email de test <span class="text-rose-500">*</span></label>
                    <InputText id="test-email" v-model="testForm.test_email" type="email" placeholder="destinataire@exemple.com" class="w-full" :invalid="!!testForm.errors.test_email" aria-label="Email de test" />
                    <small v-if="testForm.errors.test_email" class="text-rose-500" role="alert">{{ testForm.errors.test_email }}</small>
                </div>
            </form>

            <template #footer>
                <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="testDialogOpen = false" />
                <Button type="submit" label="Envoyer le test" icon="pi pi-send" :loading="testForm.processing" @click="submitTest" />
            </template>
        </SncDialog>

        <!-- DIALOGUE : GROUPES D'AUDIENCE -->
        <SncDialog
            v-model:visible="groupsOpen"
            title="Groupes d'audience"
            subtitle="Créez et gérez vos listes de contacts pour les campagnes"
            icon="pi-users"
            icon-class="from-indigo-500 to-violet-600"
            width="720px"
        >
            <div class="p-fluid flex flex-col gap-4">
                <p v-if="groupsError" class="text-rose-500 text-sm font-semibold bg-rose-50 border border-rose-200 rounded-lg px-3 py-2" role="alert">{{ groupsError }}</p>

                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Nouveau groupe</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <InputText v-model="newGroup.name" placeholder="Nom du groupe (ex. Anciens étudiants)" :invalid="!newGroup.name.trim() && !!groupsError" aria-label="Nom du groupe" />
                        <InputText v-model="newGroup.description" placeholder="Description (optionnel)" aria-label="Description du groupe" />
                    </div>
                    <div class="mt-3 flex justify-end">
                        <Button label="Créer le groupe" icon="pi pi-plus" size="small" :disabled="!newGroup.name.trim()" @click="createGroup" />
                    </div>
                </div>

                <div v-if="groupsLoading" class="py-10 text-center text-slate-400">
                    <i class="pi pi-spin pi-spinner text-2xl"></i>
                </div>

                <div v-else-if="!groups.length" class="py-10 text-center text-slate-400">
                    <i class="pi pi-users text-3xl mb-2 block"></i>
                    Aucun groupe pour le moment. Créez votre premier groupe ci-dessus.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="g in groups"
                        :key="g.id"
                        class="rounded-xl border border-slate-200 bg-white overflow-hidden"
                    >
                        <div class="flex items-center justify-between px-4 py-3 bg-slate-50/80 border-b border-slate-100">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800 truncate">{{ g.name }}</p>
                                <p v-if="g.description" class="text-xs text-slate-500 truncate">{{ g.description }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 bg-slate-100 rounded-full px-2.5 py-1">
                                    <i class="pi pi-user text-[10px]"></i> {{ g.members?.length ?? 0 }}
                                </span>
                                <Button icon="pi pi-trash" text rounded severity="danger" size="small" aria-label="Supprimer le groupe" @click="deleteGroup(g)" />
                            </div>
                        </div>

                        <div class="p-4">
                            <div v-if="g.members?.length" class="flex flex-wrap gap-2 mb-3">
                                <span
                                    v-for="m in g.members"
                                    :key="m.id"
                                    class="inline-flex items-center gap-1.5 pl-3 pr-1 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-semibold"
                                >
                                    <i class="pi pi-envelope text-[10px] opacity-70"></i>
                                    {{ m.email }}
                                    <button
                                        type="button"
                                        class="w-4 h-4 rounded-full bg-indigo-200/70 hover:bg-rose-500 hover:text-white text-indigo-700 flex items-center justify-center transition-colors"
                                        :aria-label="`Retirer ${m.email}`"
                                        @click="removeMember(g, m)"
                                    >
                                        <i class="pi pi-times text-[9px]"></i>
                                    </button>
                                </span>
                            </div>
                            <p v-else class="text-xs text-slate-400 mb-3">Aucun contact dans ce groupe.</p>

                            <div class="flex gap-2">
                                <InputText
                                    v-model="memberDrafts[g.id]"
                                    placeholder="Ajouter un email (Entrée pour valider)…"
                                    class="flex-1"
                                    :class="{ 'p-invalid': !!memberErrors[g.id] }"
                                    @keydown.enter.prevent="addMember(g)"
                                    :aria-label="`Ajouter un contact à ${g.name}`"
                                />
                                <Button icon="pi pi-plus" severity="secondary" outlined :aria-label="'Ajouter'" @click="addMember(g)" />
                            </div>
                            <small v-if="memberErrors[g.id]" class="text-rose-500 mt-1 block" role="alert">{{ memberErrors[g.id] }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Fermer" icon="pi pi-times" text severity="secondary" @click="groupsOpen = false" />
            </template>
        </SncDialog>
    </AppLayout>
</template>

<style scoped>
:deep(.p-datatable-thead > tr > th) {
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
}
</style>
