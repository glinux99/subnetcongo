<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { useConfirmAction } from '@/composables/useConfirmAction';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputSwitch from 'primevue/inputswitch';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    subscriptions: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    plans: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    selectedSubscription: { type: Object, default: null },
});

const confirmAction = useConfirmAction();
const page = usePage();

const activeTabIndex = ref(new URLSearchParams((page.url.split('?')[1] ?? '')).get('tab') === 'plans' ? 1 : 0);

const statusOptions = [
    { label: 'Tous les statuts', value: '' },
    { label: 'Actif', value: 'active' },
    { label: 'Résilié', value: 'canceled' },
    { label: 'Expiré', value: 'expired' },
    { label: 'Essai', value: 'trialing' },
    { label: 'Impayé', value: 'past_due' },
];

const planOptions = computed(() => [
    { label: 'Tous les plans', value: '' },
    ...props.plans.map((p) => ({ label: p.name, value: p.code })),
]);

const activePlanOptions = computed(() =>
    props.plans.filter((p) => p.is_active).map((p) => ({
        label: `${p.name} — ${formatCents(p.price)}/${p.interval === 'year' ? 'an' : 'mois'}`,
        value: p.code,
    }))
);

const userOptions = computed(() =>
    props.users.map((u) => ({ label: `${u.name} — ${u.email}`, value: u.id }))
);

const filterForm = useForm({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    plan: props.filters?.plan ?? '',
});

const buildParams = (pageNumber = 1, perPage = null) => {
    const params = { page: pageNumber };
    if (filterForm.search) params.search = filterForm.search;
    if (filterForm.status) params.status = filterForm.status;
    if (filterForm.plan) params.plan = filterForm.plan;
    if (perPage) params.per_page = perPage;
    else if (props.filters?.per_page) params.per_page = props.filters.per_page;
    return params;
};

const applyFilters = (pageNumber = 1, perPage = null) => {
    router.get(route('billing.subscriptions.index'), buildParams(pageNumber, perPage), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(() => filterForm.search, debounce(() => applyFilters(1), 350));
watch(() => filterForm.status, () => applyFilters(1));
watch(() => filterForm.plan, () => applyFilters(1));

const hasActiveFilters = computed(() => !!(filterForm.search || filterForm.status || filterForm.plan));

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    filterForm.plan = '';
};

const onPage = (event) => {
    applyFilters(event.page + 1, event.rows);
};

const exportUrl = computed(() => {
    const params = buildParams(1);
    delete params.page;
    params.export = 'csv';
    return route('billing.subscriptions.index', params);
});

const downloadExport = () => {
    window.location.href = exportUrl.value;
};

const formatCents = (cents) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: props.stats?.currency ?? 'USD' })
        .format(((Number(cents) || 0) / 100));

const formatDate = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '—');

const intervalLabel = (interval) => (interval === 'year' ? 'Annuel' : interval === 'month' ? 'Mensuel' : (interval || '—'));

const STATUS = {
    active: { label: 'Actif', severity: 'success' },
    canceled: { label: 'Résilié', severity: 'danger' },
    expired: { label: 'Expiré', severity: 'warning' },
    trialing: { label: 'Essai', severity: 'info' },
    past_due: { label: 'Impayé', severity: 'warning' },
};
const statusTag = (status) => STATUS[status] ?? { label: status ?? '—', severity: 'info' };

const initials = (name) =>
    (name || '?').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('') || '?';

const detailRow = ref(null);
const detailOpen = ref(false);
const detailId = ref(null);

const openDetail = (sub) => {
    detailRow.value = sub;
    detailId.value = sub.id;
    detailOpen.value = true;
};

const detailSub = computed(() => {
    if (detailId.value === null) return null;
    const fresh = props.subscriptions.data.find((s) => s.id === detailId.value);
    return fresh ?? detailRow.value;
});

onMounted(() => {
    if (props.selectedSubscription) openDetail(props.selectedSubscription);
});

const askCancel = (sub) => {
    confirmAction({
        header: 'Résilier l’abonnement',
        message: `Résilier l’abonnement de ${sub.user?.name ?? 'cet utilisateur'} ? La résiliation prend effet immédiatement.`,
        acceptLabel: 'Résilier',
        rejectLabel: 'Conserver',
        danger: true,
        accept: () => router.post(route('billing.subscriptions.cancel', sub.id), {}, { preserveScroll: true, preserveState: true }),
    });
};

const askResume = (sub) => {
    confirmAction({
        header: 'Réactiver l’abonnement',
        message: `Réactiver l’abonnement de ${sub.user?.name ?? 'cet utilisateur'} ?`,
        acceptLabel: 'Réactiver',
        rejectLabel: 'Annuler',
        accept: () => router.post(route('billing.subscriptions.resume', sub.id), {}, { preserveScroll: true, preserveState: true }),
    });
};

const askDelete = (sub) => {
    confirmAction({
        header: 'Supprimer l’abonnement',
        message: 'Supprimer définitivement cet abonnement résilié ? Cette action est irréversible.',
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        danger: true,
        accept: () => {
            detailOpen.value = false;
            router.delete(route('billing.subscriptions.destroy', sub.id), { preserveScroll: true, preserveState: true });
        },
    });
};

const newSubOpen = ref(false);
const subForm = useForm({
    user_id: null,
    plan_code: '',
});

const openNewSub = () => {
    subForm.reset();
    subForm.clearErrors();
    newSubOpen.value = true;
};

const saveSubscription = () => {
    subForm.post(route('billing.subscriptions.store'), {
        preserveScroll: true,
        onSuccess: () => { newSubOpen.value = false; subForm.reset(); },
    });
};

const planDialogOpen = ref(false);
const planForm = useForm({
    id: null,
    code: '',
    name: '',
    description: '',
    price: 0,
    currency: 'USD',
    interval: 'month',
    duration_months: 1,
    featuresText: '',
    is_active: true,
});

planForm.transform((data) => ({
    code: data.code,
    name: data.name,
    description: data.description,
    price: Math.round((Number(data.price) || 0) * 100),
    currency: data.currency,
    interval: data.interval,
    duration_months: data.duration_months,
    features: String(data.featuresText || '').split('\n').map((l) => l.trim()).filter(Boolean),
    is_active: data.is_active,
}));

const openNewPlan = () => {
    planForm.reset();
    planForm.clearErrors();
    planForm.id = null;
    planForm.interval = 'month';
    planForm.duration_months = 1;
    planForm.currency = 'USD';
    planForm.is_active = true;
    planDialogOpen.value = true;
};

const openEditPlan = (plan) => {
    planForm.clearErrors();
    planForm.id = plan.id;
    planForm.code = plan.code;
    planForm.name = plan.name;
    planForm.description = plan.description ?? '';
    planForm.price = (plan.price ?? 0) / 100;
    planForm.currency = plan.currency ?? 'USD';
    planForm.interval = plan.interval ?? 'month';
    planForm.duration_months = plan.duration_months ?? 1;
    planForm.featuresText = (plan.features ?? []).join('\n');
    planForm.is_active = !!plan.is_active;
    planDialogOpen.value = true;
};

const savePlan = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => { planDialogOpen.value = false; planForm.reset(); },
    };
    if (planForm.id) {
        planForm.put(route('billing.plans.update', planForm.id), options);
    } else {
        planForm.post(route('billing.plans.store'), options);
    }
};

const togglePlanActive = (plan) => {
    router.put(route('billing.plans.update', plan.id), { is_active: plan.is_active }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const askDeletePlan = (plan) => {
    confirmAction({
        header: 'Supprimer le plan',
        message: `Supprimer le plan « ${plan.name} » ? Impossible s’il est utilisé par des abonnements.`,
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        danger: true,
        accept: () => router.delete(route('billing.plans.destroy', plan.id), { preserveScroll: true }),
    });
};

const intervalOptions = [
    { label: 'Mensuel', value: 'month' },
    { label: 'Annuel', value: 'year' },
];
</script>

<template>
    <AppLayout>
        <Head title="Abonnements & Plans" />

        <div class="page-bg min-h-screen">
            <PageHeader
                title="Abonnements & Plans"
                subtitle="Gérez les abonnements réels et les offres tarifaires"
                icon="pi-credit-card"
                accent="emerald"
                badge="Facturation"
                :actions="[
                    { label: 'Gérer les plans', icon: 'pi-cog', variant: 'secondary', onClick: () => activeTabIndex = 1 },
                    { label: 'Exporter CSV', icon: 'pi-download', variant: 'secondary', onClick: downloadExport },
                    { label: 'Nouveau Abonnement', icon: 'pi-plus', variant: 'primary', onClick: openNewSub },
                ]"
            />

            <div class="page-container page-section">
            <TabView v-model:activeIndex="activeTabIndex">
                <TabPanel header="Abonnements">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 pt-2">
                        <Card class="border-none shadow-sm rounded-xl">
                            <template #content>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-slate-500 font-bold text-xs uppercase mb-1">MRR (actifs)</span>
                                        <div class="text-3xl font-black text-slate-800">{{ formatCents(stats.mrrCents) }}</div>
                                    </div>
                                    <div class="bg-emerald-50 p-3 rounded-xl"><i class="pi pi-chart-line text-emerald-600"></i></div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">Total encaissé : <span class="font-semibold text-slate-600">{{ formatCents(stats.totalCollectedCents) }}</span></p>
                            </template>
                        </Card>

                        <Card class="border-none shadow-sm rounded-xl">
                            <template #content>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-slate-500 font-bold text-xs uppercase mb-1">Abonnements actifs</span>
                                        <div class="text-3xl font-black text-slate-800">{{ stats.totalActive ?? 0 }}</div>
                                    </div>
                                    <div class="bg-blue-50 p-3 rounded-xl"><i class="pi pi-users text-blue-600"></i></div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">{{ stats.total ?? 0 }} au total · {{ stats.totalCanceled ?? 0 }} résiliés · {{ stats.totalExpired ?? 0 }} expirés</p>
                            </template>
                        </Card>

                        <Card class="border-none shadow-sm rounded-xl">
                            <template #content>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-slate-500 font-bold text-xs uppercase mb-1">Churn (résiliés / total)</span>
                                        <div class="text-3xl font-black text-slate-800">{{ stats.churnNonDispo ? '—' : (stats.churnRate + ' %') }}</div>
                                    </div>
                                    <div class="bg-rose-50 p-3 rounded-xl"><i class="pi pi-user-minus text-rose-600"></i></div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">Estimatif simple — pas un taux cohorté mensuel</p>
                            </template>
                        </Card>

                        <Card class="border-none shadow-sm rounded-xl">
                            <template #content>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="block text-slate-500 font-bold text-xs uppercase mb-1">ARPU</span>
                                        <div class="text-3xl font-black text-slate-800">{{ formatCents(stats.arpuCents) }}</div>
                                    </div>
                                    <div class="bg-amber-50 p-3 rounded-xl"><i class="pi pi-wallet text-amber-600"></i></div>
                                </div>
                                <p class="mt-3 text-xs text-slate-400">Revenu moyen par abonnement actif / mois</p>
                            </template>
                        </Card>
                    </div>

                    <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                        <template #content>
                            <DataTable
                                :value="subscriptions.data"
                                dataKey="id"
                                lazy
                                :first="(subscriptions.current_page - 1) * subscriptions.per_page"
                                :rows="subscriptions.per_page"
                                :totalRecords="subscriptions.total"
                                @page="onPage"
                                stripedRows
                                responsiveLayout="scroll"
                                class="p-datatable-sm"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <h2 class="text-xl font-bold text-slate-800">Liste des abonnés</h2>
                                            <Badge :value="subscriptions.total" severity="info" />
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                                            <IconField iconPosition="left" class="grow md:grow-0">
                                                <InputIcon class="pi pi-search text-slate-400" />
                                                <InputText v-model="filterForm.search" placeholder="Utilisateur, email, plan..." class="w-full md:w-64" aria-label="Recherche abonnements" />
                                            </IconField>
                                            <Select v-model="filterForm.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-44" aria-label="Filtrer par statut" />
                                            <Select v-model="filterForm.plan" :options="planOptions" optionLabel="label" optionValue="value" class="w-44" aria-label="Filtrer par plan" />
                                            <Button v-if="hasActiveFilters" icon="pi pi-filter-slash" text rounded severity="secondary" aria-label="Réinitialiser les filtres" @click="resetFilters" />
                                        </div>
                                    </div>
                                </template>

                                <template #empty>
                                    <div class="text-center py-8">
                                        <i class="pi pi-inbox text-3xl text-slate-300 mb-2"></i>
                                        <p class="text-slate-500">Aucun abonnement trouvé.</p>
                                        <Button v-if="hasActiveFilters" label="Réinitialiser les filtres" text class="mt-2" @click="resetFilters" />
                                    </div>
                                </template>

                                <Column header="Utilisateur" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold shrink-0" aria-hidden="true">
                                                {{ initials(data.user?.name) }}
                                            </div>
                                            <div class="flex flex-col min-w-0">
                                                <span class="font-bold text-slate-700 leading-tight truncate">{{ data.user?.name ?? 'Utilisateur supprimé' }}</span>
                                                <span class="text-xs text-slate-400 truncate">{{ data.user?.email ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Plan" style="min-width: 11rem">
                                    <template #body="{ data }">
                                        <Tag :value="data.plan_name ?? data.plan?.name ?? '—'" severity="info" class="px-3" />
                                        <div class="text-[11px] text-slate-400 mt-1 font-mono">{{ data.plan_code ?? '' }}</div>
                                    </template>
                                </Column>

                                <Column header="Prix" style="min-width: 8rem">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-800">{{ formatCents(data.plan_price) }}</span>
                                        <span class="text-[11px] text-slate-400"> / {{ data.interval === 'year' ? 'an' : 'mois' }}</span>
                                    </template>
                                </Column>

                                <Column header="Intervalle" style="min-width: 7rem">
                                    <template #body="{ data }">{{ intervalLabel(data.interval) }}</template>
                                </Column>

                                <Column header="Début" style="min-width: 7rem">
                                    <template #body="{ data }">{{ formatDate(data.starts_at ?? data.created_at) }}</template>
                                </Column>

                                <Column header="Fin" style="min-width: 7rem">
                                    <template #body="{ data }">{{ formatDate(data.ends_at) }}</template>
                                </Column>

                                <Column header="Statut" style="min-width: 7rem">
                                    <template #body="{ data }">
                                        <Tag :value="statusTag(data.status).label" :severity="statusTag(data.status).severity" />
                                    </template>
                                </Column>

                                <Column header="Actions" alignFrozen="right" frozen style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-1 whitespace-nowrap">
                                            <Button icon="pi pi-eye" text rounded severity="secondary" :aria-label="`Voir la fiche de ${data.user?.name ?? 'l’abonné'}`" title="Voir le détail" @click="openDetail(data)" />
                                            <Button v-if="data.status === 'active'" icon="pi pi-ban" text rounded severity="danger" aria-label="Résilier l’abonnement" title="Résilier" @click="askCancel(data)" />
                                            <Button v-if="data.status === 'canceled'" icon="pi pi-refresh" text rounded severity="success" aria-label="Réactiver l’abonnement" title="Réactiver" @click="askResume(data)" />
                                            <Button v-if="data.status === 'canceled'" icon="pi pi-trash" text rounded severity="danger" aria-label="Supprimer l’abonnement" title="Supprimer" @click="askDelete(data)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel header="Plans">
                    <div class="flex justify-between items-center mb-4 pt-2">
                        <p class="text-sm text-slate-500">Plans tarifaires proposés à la souscription. Un plan utilisé par des abonnements ne peut pas être supprimé (désactivez-le).</p>
                        <Button label="Nouveau plan" icon="pi pi-plus" @click="openNewPlan" />
                    </div>

                    <DataTable :value="plans" dataKey="id" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
                        <template #empty>
                            <div class="text-center py-8 text-slate-500">Aucun plan. Créez le premier plan tarifaire.</div>
                        </template>
                        <Column header="Code" style="min-width: 8rem">
                            <template #body="{ data }"><span class="font-mono text-xs text-slate-600">{{ data.code }}</span></template>
                        </Column>
                        <Column field="name" header="Nom" style="min-width: 11rem" />
                        <Column header="Prix" style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-bold">{{ formatCents(data.price) }}</span>
                                <span class="text-[11px] text-slate-400"> / {{ data.interval === 'year' ? 'an' : 'mois' }}</span>
                            </template>
                        </Column>
                        <Column header="Intervalle" style="min-width: 7rem">
                            <template #body="{ data }">{{ intervalLabel(data.interval) }}</template>
                        </Column>
                        <Column header="Durée" style="min-width: 7rem">
                            <template #body="{ data }">{{ data.duration_months }} mois</template>
                        </Column>
                        <Column header="Actif" style="min-width: 6rem">
                            <template #body="{ data }">
                                <InputSwitch v-model="data.is_active" :aria-label="`${data.is_active ? 'Désactiver' : 'Activer'} le plan ${data.name}`" @change="togglePlanActive(data)" />
                            </template>
                        </Column>
                        <Column header="Actions" alignFrozen="right" frozen style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 whitespace-nowrap">
                                    <Button icon="pi pi-pencil" text rounded severity="secondary" :aria-label="`Modifier le plan ${data.name}`" title="Modifier" @click="openEditPlan(data)" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" :aria-label="`Supprimer le plan ${data.name}`" title="Supprimer" @click="askDeletePlan(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
            </div>

            <SncDialog
                v-model:visible="newSubOpen"
                title="Nouvel abonnement"
                subtitle="Création manuelle — aucun paiement n'est encaissé automatiquement"
                icon="pi-user-plus"
                icon-class="from-blue-600 to-indigo-600"
                width="560px"
            >
                <div class="flex flex-col gap-4">
                    <SncSection icon="pi-user" title="Utilisateur & plan" icon-class="from-slate-600 to-slate-700">
                        <div class="flex flex-col gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-600 mb-2" for="sub-user">Utilisateur</label>
                                <Select id="sub-user" v-model="subForm.user_id" :options="userOptions" optionLabel="label" optionValue="value" filter class="w-full" placeholder="Rechercher un utilisateur..." />
                                <small v-if="subForm.errors.user_id" class="p-error" role="alert">{{ subForm.errors.user_id }}</small>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-600 mb-2" for="sub-plan">Plan</label>
                                <Select id="sub-plan" v-model="subForm.plan_code" :options="activePlanOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Choisir un plan actif..." />
                                <small v-if="subForm.errors.plan_code" class="p-error" role="alert">{{ subForm.errors.plan_code }}</small>
                            </div>
                        </div>
                    </SncSection>
                </div>
                <template #footer>
                    <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="newSubOpen = false" />
                    <Button label="Créer l'abonnement" icon="pi pi-check" :loading="subForm.processing" :disabled="!subForm.user_id || !subForm.plan_code" @click="saveSubscription" />
                </template>
            </SncDialog>

            <SncDialog
                v-model:visible="planDialogOpen"
                :title="planForm.id ? 'Modifier le plan' : 'Nouveau plan'"
                subtitle="Configurez le prix, la facturation et les fonctionnalités"
                icon="pi-cog"
                :icon-class="planForm.id ? 'from-amber-500 to-orange-500' : 'from-violet-500 to-purple-600'"
                width="640px"
            >
                <div class="flex flex-col gap-5">
                    <SncSection icon="pi-id-card" title="Identité du plan" icon-class="from-blue-500 to-indigo-600">
                        <div class="flex flex-col gap-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-code">Code</label>
                                    <InputText id="plan-code" v-model.trim="planForm.code" :disabled="!!planForm.id" placeholder="ex: starter" class="w-full" :class="{ 'p-invalid': planForm.errors.code }" />
                                    <small v-if="planForm.errors.code" class="p-error" role="alert">{{ planForm.errors.code }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-name">Nom</label>
                                    <InputText id="plan-name" v-model.trim="planForm.name" placeholder="ex: Professional" class="w-full" :class="{ 'p-invalid': planForm.errors.name }" />
                                    <small v-if="planForm.errors.name" class="p-error" role="alert">{{ planForm.errors.name }}</small>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-desc">Description</label>
                                <Textarea id="plan-desc" v-model="planForm.description" rows="2" class="w-full" autoResize />
                            </div>
                        </div>
                    </SncSection>

                    <SncSection icon="pi-credit-card" title="Tarification" subtitle="Prix, intervalle et durée d'accès" icon-class="from-emerald-500 to-teal-600">
                        <div class="flex flex-col gap-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-price">Prix (USD)</label>
                                    <InputNumber id="plan-price" v-model="planForm.price" mode="currency" currency="USD" locale="fr-FR" class="w-full" :minFractionDigits="2" />
                                    <small v-if="planForm.errors.price" class="p-error" role="alert">{{ planForm.errors.price }}</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-interval">Intervalle</label>
                                    <Select id="plan-interval" v-model="planForm.interval" :options="intervalOptions" optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2" for="plan-duration">Durée (mois)</label>
                                    <InputNumber id="plan-duration" v-model="planForm.duration_months" :min="1" :max="120" class="w-full" showButtons />
                                    <small v-if="planForm.errors.duration_months" class="p-error" role="alert">{{ planForm.errors.duration_months }}</small>
                                </div>
                                <div class="flex items-center gap-3 pb-1">
                                    <InputSwitch id="plan-active" v-model="planForm.is_active" />
                                    <label for="plan-active" class="text-sm font-semibold text-slate-600 cursor-pointer">Plan actif (visible à la souscription)</label>
                                </div>
                            </div>
                        </div>
                    </SncSection>

                    <SncSection icon="pi-list-check" title="Fonctionnalités" subtitle="Une fonctionnalité par ligne" icon-class="from-amber-500 to-orange-500">
                        <div>
                            <Textarea id="plan-features" v-model="planForm.featuresText" rows="4" class="w-full" placeholder="Accès illimité&#10;Support prioritaire&#10;Certificats" />
                            <small v-if="planForm.errors.features" class="p-error" role="alert">{{ planForm.errors.features }}</small>
                        </div>
                    </SncSection>
                </div>
                <template #footer>
                    <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="planDialogOpen = false" />
                    <Button label="Enregistrer" icon="pi pi-check" :loading="planForm.processing" :disabled="!planForm.name || (!planForm.id && !planForm.code)" @click="savePlan" />
                </template>
            </SncDialog>

            <SncDialog
                v-model:visible="detailOpen"
                title="Fiche abonné"
                subtitle="Informations du compte et gestion du cycle de vie"
                icon="pi-id-card"
                icon-class="from-slate-600 to-slate-700"
                width="640px"
            >
                <div v-if="detailSub" class="flex flex-col gap-5">
                    <SncSection icon="pi-user" title="Abonné" icon-class="from-blue-500 to-indigo-600">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 border border-slate-200/70 rounded-xl">
                            <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-lg font-black shrink-0" aria-hidden="true">
                                {{ initials(detailSub.user?.name) }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-xl font-black text-slate-800 leading-tight">{{ detailSub.user?.name ?? 'Utilisateur supprimé' }}</h2>
                                <p class="text-slate-500 text-sm truncate">{{ detailSub.user?.email ?? '—' }}</p>
                                <Tag class="mt-2" :value="statusTag(detailSub.status).label" :severity="statusTag(detailSub.status).severity" />
                            </div>
                        </div>
                    </SncSection>

                    <SncSection icon="pi-info-circle" title="Détails de l'abonnement" icon-class="from-emerald-500 to-teal-600">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Plan actuel</span>
                                <div class="font-bold text-slate-800">{{ detailSub.plan_name ?? detailSub.plan?.name ?? '—' }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ detailSub.plan_code ?? '' }}</div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Cycle</span>
                                <div class="font-semibold text-slate-700">{{ intervalLabel(detailSub.interval) }} · {{ formatCents(detailSub.plan_price) }}</div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Client depuis</span>
                                <div class="font-semibold text-slate-700">{{ formatDate(detailSub.created_at) }}</div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Début</span>
                                <div class="font-semibold text-slate-700">{{ formatDate(detailSub.starts_at ?? detailSub.created_at) }}</div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Fin</span>
                                <div class="font-semibold text-slate-700">{{ formatDate(detailSub.ends_at) }}</div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Total encaissé</span>
                                <div class="font-bold text-slate-800">{{ formatCents(detailSub.total_paid) }}</div>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-[11px] font-bold text-slate-400 uppercase mb-1">Référence</span>
                                <div class="font-mono text-xs text-slate-500 break-all">{{ detailSub.stripe_id ?? '—' }}</div>
                            </div>
                        </div>
                    </SncSection>

                    <SncSection icon="pi-exclamation-triangle" title="Zone d'administration" subtitle="Actions irréversibles sur l'abonnement" icon-class="from-rose-500 to-red-600">
                        <div class="flex flex-wrap justify-between items-center gap-3 bg-rose-50 border border-rose-100 p-4 rounded-xl">
                            <p class="text-xs text-rose-600 max-w-xs">La résiliation prend effet immédiatement (sans période de grâce).</p>
                            <div class="flex gap-2">
                                <Button v-if="detailSub.status === 'active'" label="Résilier l'abonnement" icon="pi pi-ban" outlined severity="danger" size="small" @click="askCancel(detailSub)" />
                                <Button v-if="detailSub.status === 'canceled'" label="Réactiver" icon="pi pi-refresh" outlined severity="success" size="small" @click="askResume(detailSub)" />
                                <Button v-if="detailSub.status === 'canceled'" label="Supprimer" icon="pi pi-trash" severity="danger" size="small" @click="askDelete(detailSub)" />
                            </div>
                        </div>
                    </SncSection>
                </div>
                <template #footer>
                    <Button label="Fermer" icon="pi pi-times" text severity="secondary" @click="detailOpen = false" />
                </template>
            </SncDialog>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #F1F5F9;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.025em;
}
</style>
