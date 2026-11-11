<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
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
});

const confirmAction = useConfirmAction();

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

const audienceOptions = [
    { label: 'Tous les utilisateurs', value: 'all' },
    { label: 'Étudiants', value: 'students' },
    { label: 'Formateurs', value: 'instructors' },
];

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

const audienceLabel = (a) => ({
    all: 'Tous',
    students: 'Étudiants',
    instructors: 'Formateurs',
}[a] ?? a);

const kpis = computed(() => [
    { label: 'Total campagnes', value: props.stats?.total ?? 0, icon: 'pi pi-megaphone', color: 'bg-blue-50 text-blue-600' },
    { label: 'Actives', value: props.stats?.active ?? 0, icon: 'pi pi-bolt', color: 'bg-amber-50 text-amber-600' },
    { label: 'Envoyées', value: props.stats?.sent ?? 0, icon: 'pi pi-send', color: 'bg-emerald-50 text-emerald-600' },
    { label: 'Emails délivrés', value: props.stats?.sent_total ?? 0, icon: 'pi pi-envelope', color: 'bg-sky-50 text-sky-600' },
]);
</script>

<template>
    <AppLayout>
        <Head title="Campagnes email — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Campagnes email"
                subtitle="Créez, ciblez et envoyez vos campagnes à votre audience"
                icon="pi-envelope"
                accent="blue"
                badge="Marketing"
                :actions="[{ label: 'Nouvelle campagne', icon: 'pi-plus', variant: 'primary', onClick: openCreate }]"
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
                <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-4 mb-4">
                        <h2 class="text-lg font-black text-slate-800">Vos campagnes</h2>
                    <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-72">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Rechercher un nom ou un objet…" class="w-full" aria-label="Rechercher une campagne" @keyup.enter="applyFilters" />
                        </IconField>
                        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full sm:w-48" aria-label="Filtrer par statut" @change="applyFilters" />
                        <Button icon="pi pi-search" aria-label="Lancer la recherche" @click="applyFilters" />
                    </div>
                </div>

                <DataTable :value="campaigns?.data ?? []" dataKey="id" :lazy="true" :paginator="true"
                           :rows="campaigns?.per_page ?? 15" :totalRecords="campaigns?.total ?? 0"
                           :first="((campaigns?.current_page ?? 1) - 1) * (campaigns?.per_page ?? 15)"
                           @page="onPage" responsiveLayout="scroll" class="p-datatable-sm">
                    <Column header="Nom" style="min-width: 12rem">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-800">{{ data.name || '(Sans nom)' }}</span>
                        </template>
                    </Column>
                    <Column field="subject" header="Objet" style="min-width: 16rem">
                        <template #body="{ data }">
                            <span class="text-slate-600 line-clamp-1">{{ data.subject }}</span>
                        </template>
                    </Column>
                    <Column header="Audience" style="width: 9rem">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-600">{{ audienceLabel(data.target_audience) }}</span>
                        </template>
                    </Column>
                    <Column header="Statut" style="width: 9rem">
                        <template #body="{ data }">
                            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column header="Envoyés" style="width: 7rem">
                        <template #body="{ data }">
                            <span class="tabular-nums font-bold text-slate-800">{{ data.sent_count ?? 0 }}</span>
                        </template>
                    </Column>
                    <Column header="Créée le" style="width: 11rem">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-500">{{ formatDate(data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 15rem">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-eye" text rounded severity="secondary" v-tooltip.top="'Voir'" aria-label="Voir la campagne" @click="viewCampaign(data)" />
                                <Button icon="pi pi-copy" text rounded severity="secondary" v-tooltip.top="'Dupliquer'" aria-label="Dupliquer la campagne" @click="duplicateCampaign(data)" />
                                <Button icon="pi pi-send" text rounded severity="info" v-tooltip.top="'Envoyer un test'" aria-label="Envoyer un email de test" @click="openTest(data)" />
                                <Button v-if="data.status !== 'sent'" icon="pi pi-pencil" text rounded severity="secondary" v-tooltip.top="'Modifier'" aria-label="Modifier la campagne" @click="openEdit(data)" />
                                <Button v-if="['draft', 'scheduled'].includes(data.status)" icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Supprimer'" aria-label="Supprimer la campagne" @click="removeCampaign(data)" />
                            </div>
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

        <!-- DIALOGUE : CRÉATION / ÉDITION (même page) -->
        <Dialog v-model:visible="formOpen" modal class="snc-dialog" :style="{ width: '46rem' }" :breakpoints="{ '960px': '95vw' }"
                @hide="closeForm">
            <template #header>
                <span class="p-dialog-title flex items-center gap-2">
                    <i :class="['pi', editingId ? 'pi-pencil' : 'pi-megaphone']"></i>
                    {{ editingId ? 'Modifier la campagne' : 'Nouvelle campagne' }}
                </span>
            </template>
            <form class="flex flex-col gap-4 pt-2" @submit.prevent="submitWithStatus(form.status)">
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

                <div class="field">
                    <label for="campaign-body" class="font-bold text-slate-700 text-sm mb-1 block">Corps du message <span class="text-rose-500">*</span></label>
                    <Editor id="campaign-body" v-model="form.body" editorStyle="height: 220px" />
                    <small v-if="form.errors.body" class="text-rose-500" role="alert">{{ form.errors.body }}</small>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <Button type="button" label="Annuler" text severity="secondary" @click="closeForm" />
                    <Button type="button" label="Enregistrer le brouillon" icon="pi pi-save" outlined severity="secondary" :loading="form.processing" @click="submitWithStatus('draft')" />
                    <Button type="button" label="Envoyer maintenant" icon="pi pi-send" :loading="form.processing" @click="submitWithStatus('queued')" />
                </div>
            </form>
        </Dialog>

        <!-- DIALOGUE : APERÇU (mode show) -->
        <Dialog v-model:visible="previewOpen" modal class="snc-dialog" :style="{ width: '46rem' }" :breakpoints="{ '960px': '95vw' }"
                @hide="closePreview">
            <template #header>
                <span class="p-dialog-title flex items-center gap-2">
                    <i class="pi pi-eye"></i>
                    Aperçu de la campagne
                </span>
            </template>
            <div v-if="campaign" class="flex flex-col gap-4 pt-2">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Statut</p>
                        <Tag :value="statusLabel(campaign.status)" :severity="statusSeverity(campaign.status)" class="mt-1" />
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Audience</p>
                        <p class="font-bold text-slate-800 mt-1">{{ audienceLabel(campaign.target_audience) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Envoyés</p>
                        <p class="font-bold text-slate-800 mt-1 tabular-nums">{{ campaign.sent_count ?? 0 }} / {{ campaign.total_recipients ?? 0 }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase">Échecs</p>
                        <p class="font-bold text-slate-800 mt-1 tabular-nums">{{ campaign.bounces_count ?? 0 }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Objet</p>
                    <p class="font-bold text-slate-800">{{ campaign.subject }}</p>
                </div>

                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Corps</p>
                    <!-- HTML rédigé par un administrateur, rendu tel quel -->
                    <div class="border border-slate-200 rounded-xl p-4 max-h-64 overflow-y-auto text-sm text-slate-700" v-html="campaign.body"></div>
                </div>

                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Destinataires ciblés ({{ recipientsCount }})</p>
                    <div class="border border-slate-200 rounded-xl max-h-48 overflow-y-auto divide-y divide-slate-100">
                        <div v-for="r in recipientsPreview" :key="r.id" class="flex justify-between items-center px-4 py-2 text-sm">
                            <span class="font-medium text-slate-700">{{ r.name }}</span>
                            <span class="text-slate-400">{{ r.email }}</span>
                        </div>
                        <p v-if="recipientsCount > recipientsPreview.length" class="px-4 py-2 text-xs text-slate-400 italic">
                            … et {{ recipientsCount - recipientsPreview.length }} autre(s) destinataire(s).
                        </p>
                        <p v-if="recipientsCount === 0" class="px-4 py-3 text-sm text-slate-400 italic">Aucun destinataire pour cette audience.</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button v-if="campaign && campaign.status !== 'sent'" label="Modifier" icon="pi pi-pencil" outlined severity="secondary" @click="closePreview(); openEdit(campaign)" />
                    <Button label="Fermer" text severity="secondary" @click="closePreview" />
                </div>
            </template>
        </Dialog>

        <!-- DIALOGUE : ENVOI TEST -->
        <Dialog v-model:visible="testDialogOpen" modal class="snc-dialog" :style="{ width: '28rem' }">
            <template #header>
                <span class="p-dialog-title flex items-center gap-2">
                    <i class="pi pi-send"></i>
                    Envoyer un email de test
                </span>
            </template>
            <form class="flex flex-col gap-3 pt-2" @submit.prevent="submitTest">
                <p class="text-sm text-slate-500">Un aperçu de la campagne sera envoyé immédiatement à cette adresse.</p>
                <InputText v-model="testForm.test_email" type="email" placeholder="destinataire@exemple.com" class="w-full" :invalid="!!testForm.errors.test_email" aria-label="Email de test" />
                <small v-if="testForm.errors.test_email" class="text-rose-500" role="alert">{{ testForm.errors.test_email }}</small>
                <div class="flex justify-end gap-2">
                    <Button type="button" label="Annuler" text severity="secondary" @click="testDialogOpen = false" />
                    <Button type="submit" label="Envoyer le test" icon="pi pi-send" :loading="testForm.processing" />
                </div>
            </form>
        </Dialog>
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
