<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import debounce from 'lodash/debounce';

import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';
import Avatar from 'primevue/avatar';
import InputNumber from 'primevue/inputnumber';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';

const props = defineProps({
    affiliates: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    stats: { type: Object, default: () => ({}) },
    chart: { type: Object, default: () => ({ labels: [], signups: [], conversions: [] }) },
    top_affiliates: { type: Array, default: () => [] },
    tiers: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    affiliateDetail: { type: Object, default: null },
});

const toast = useToast();
const confirmAction = useConfirmAction();

// --- FORMATAGE ---
const fmtNumber = (n) => new Intl.NumberFormat('fr-FR').format(Number(n ?? 0));
const fmtMoney = (n) => `${new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n ?? 0))} $`;
const fmtDate = (iso) => iso ? new Date(iso).toLocaleDateString('fr-FR') : '—';

const STATUS_LABELS = { active: 'Actif', pending: 'En attente', inactive: 'Inactif', banned: 'Banni' };
const STATUS_SEVERITY = { active: 'success', pending: 'warning', inactive: 'secondary', banned: 'danger' };
const PAYOUT_STATUS = { pending: 'En attente', processing: 'En cours', completed: 'Payé', failed: 'Échoué' };
const PAYOUT_SEVERITY = { pending: 'warning', processing: 'info', completed: 'success', failed: 'danger' };

const statusLabel = (s) => STATUS_LABELS[s] ?? s;
const statusSeverity = (s) => STATUS_SEVERITY[s] ?? 'info';
const payoutLabel = (s) => PAYOUT_STATUS[s] ?? s;
const payoutSeverity = (s) => PAYOUT_SEVERITY[s] ?? 'info';
const tierOf = (value) => props.tiers.find((t) => t.value === value) ?? { label: value, color: '#94a3b8' };
const tierLabel = (value) => tierOf(value).label;
const tierColor = (value) => tierOf(value).color;

const initials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase() || name.substring(0, 2).toUpperCase();
};

// --- FILTRES SERVEUR ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || null);
const tierFilter = ref(props.filters.tier || null);

const filterParams = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (tierFilter.value) params.tier = tierFilter.value;
    return params;
};

const applyFilters = debounce(() => {
    router.get(route('marketing.affiliates.index'), filterParams(), { preserveState: true, preserveScroll: true, replace: true });
}, 350);

watch([search, statusFilter, tierFilter], applyFilters);

const onPage = (event) => {
    router.get(route('marketing.affiliates.index'), {
        ...filterParams(),
        page: event.page + 1,
        per_page: event.rows,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const exportCsv = () => {
    const params = new URLSearchParams(filterParams());
    window.location.href = `${route('marketing.affiliates.export')}?${params.toString()}`;
};

// --- CHARGEMENT / TABLE ---
const tableLoading = ref(false);
let stopStart = null;
let stopFinish = null;
onMounted(() => {
    stopStart = router.on('start', () => { tableLoading.value = true; });
    stopFinish = router.on('finish', () => { tableLoading.value = false; });
});
onBeforeUnmount(() => { stopStart?.(); stopFinish?.(); });

const firstRow = computed(() => ((props.affiliates?.current_page ?? 1) - 1) * (props.affiliates?.per_page ?? 10));

// --- GRAPHE (données réelles, 6 derniers mois) ---
const chartData = computed(() => ({
    labels: props.chart?.labels ?? [],
    datasets: [
        {
            label: 'Nouveaux affiliés',
            data: props.chart?.signups ?? [],
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#2563eb',
        },
        {
            label: 'Conversions',
            data: props.chart?.conversions ?? [],
            borderColor: '#f97316',
            backgroundColor: 'transparent',
            fill: false,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#f97316',
        },
    ],
}));

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
    },
};

// --- ACTIONS ---
const showDetail = (affiliate) => {
    router.get(route('marketing.affiliates.show', affiliate.id), {}, { only: ['affiliateDetail'], preserveState: true, preserveScroll: true });
};

const detailVisible = ref(false);
watch(() => props.affiliateDetail, (value) => { if (value) detailVisible.value = true; });

const approveAffiliate = (affiliate) => {
    confirmAction({
        message: `Approuver ${affiliate.name} ? Son compte affilié sera activé immédiatement.`,
        acceptLabel: 'Approuver',
        accept: () => router.post(route('marketing.affiliates.approve', affiliate.id), {}, { preserveScroll: true }),
    });
};

const rejectAffiliate = (affiliate) => {
    confirmAction({
        message: `Refuser la demande de ${affiliate.name} ? Le compte en attente sera définitivement supprimé.`,
        header: 'Refuser la demande',
        acceptLabel: 'Refuser et supprimer',
        danger: true,
        accept: () => router.post(route('marketing.affiliates.reject', affiliate.id), {}, { preserveScroll: true }),
    });
};

const generatePayout = (affiliate) => {
    confirmAction({
        message: `Générer un paiement en attente de ${fmtMoney(affiliate.unpaid_earnings)} pour ${affiliate.name} ? Le solde à payer sera remis à zéro.`,
        header: 'Générer un paiement',
        acceptLabel: 'Générer',
        accept: () => router.post(route('marketing.affiliates.generate-payout', affiliate.id), {}, { preserveScroll: true }),
    });
};

const destroyAffiliate = (affiliate) => {
    confirmAction({
        message: `Supprimer ${affiliate.name} ? Son affiliation sera désactivée (statut inactif) et l'historique des ventes est conservé.`,
        header: 'Supprimer l’affilié',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('marketing.affiliates.destroy', affiliate.id), { preserveScroll: true }),
    });
};

const copyCode = async (code) => {
    try {
        await navigator.clipboard.writeText(code);
        toast.add({ severity: 'success', summary: 'Copié', detail: `Code ${code} copié dans le presse-papier.`, life: 2500 });
    } catch (e) {
        toast.add({ severity: 'warn', summary: 'Copie impossible', detail: `Code : ${code}`, life: 4000 });
    }
};

// --- NOUVEL AFFILIÉ ---
const showNewDialog = ref(false);
const emailRegex = /^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/;
const clientEmailError = ref('');

const form = useForm({ name: '', email: '', tier: 'bronze', commission_rate: 10 });

const openNewDialog = () => {
    clientEmailError.value = '';
    showNewDialog.value = true;
};

const submitNewAffiliate = () => {
    clientEmailError.value = '';
    if (!emailRegex.test(form.email)) {
        clientEmailError.value = 'Adresse email invalide (ex : prenom.nom@domaine.cd).';
        return;
    }
    form.post(route('marketing.affiliates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showNewDialog.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Programme d'affiliation - SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                title="Programme d'Affiliation"
                subtitle="Suivez vos partenaires et optimisez les ventes de formations"
                icon="pi-share-alt"
                accent="emerald"
                badge="Marketing"
                :actions="[
                    { label: 'Exporter CSV', icon: 'pi-file-export', variant: 'secondary', onClick: exportCsv },
                    { label: 'Nouvel affilié', icon: 'pi-plus', variant: 'primary', onClick: openNewDialog },
                ]"
            />

            <div class="page-container page-section">
            <!-- KPI CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 md:gap-6 mb-8">
                <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Partenaires actifs</span>
                                <div class="text-3xl font-black text-slate-800">{{ fmtNumber(stats.active) }}</div>
                                <span class="text-[10px] text-slate-400 font-bold">sur {{ fmtNumber(stats.total) }} inscrits</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-blue-50 text-blue-600"><i class="pi pi-users text-xl"></i></div>
                        </div>
                    </template>
                </Card>
                <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">En attente</span>
                                <div class="text-3xl font-black text-amber-600">{{ fmtNumber(stats.pending) }}</div>
                                <span class="text-[10px] text-slate-400 font-bold">demandes à valider</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-amber-50 text-amber-600"><i class="pi pi-clock text-xl"></i></div>
                        </div>
                    </template>
                </Card>
                <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Liens cliqués</span>
                                <div class="text-3xl font-black text-slate-800">{{ fmtNumber(stats.total_clicks) }}</div>
                                <span class="text-[10px] text-slate-400 font-bold">clics cumulés</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-sky-50 text-sky-600"><i class="pi pi-link text-xl"></i></div>
                        </div>
                    </template>
                </Card>
                <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Ventes affiliées</span>
                                <div class="text-3xl font-black text-slate-800">{{ fmtNumber(stats.total_conversions) }}</div>
                                <span class="text-[10px] text-slate-400 font-bold">conversions</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600"><i class="pi pi-shopping-cart text-xl"></i></div>
                        </div>
                    </template>
                </Card>
                <Card class="border-none shadow-sm rounded-xl overflow-hidden col-span-2 md:col-span-1">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Commissions à payer</span>
                                <div class="text-2xl font-black text-rose-600">{{ fmtMoney(stats.unpaid_earnings) }}</div>
                                <span class="text-[10px] text-slate-400 font-bold">{{ fmtMoney(stats.total_paid) }} déjà payés</span>
                            </div>
                            <div class="p-3 rounded-2xl bg-rose-50 text-rose-600"><i class="pi pi-wallet text-xl"></i></div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- GRAPHE + TOP PARTENAIRES -->
            <div class="grid grid-cols-12 gap-6 mb-8">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="h-full border-none shadow-sm rounded-xl">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-2">
                                <span class="text-xl font-black text-slate-800">Évolution du programme</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">6 derniers mois</span>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[300px]">
                                <Chart type="line" :data="chartData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-span-12 lg:col-span-4">
                    <Card class="h-full border-none shadow-sm rounded-xl">
                        <template #title>
                            <div class="px-4 pt-2 flex justify-between items-center">
                                <span class="text-xl font-black text-slate-800">Top partenaires</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">par gains</span>
                            </div>
                        </template>
                        <template #content>
                            <div v-if="top_affiliates.length" class="space-y-3">
                                <div v-for="partner in top_affiliates" :key="partner.id" class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors">
                                    <div class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: tierColor(partner.tier) }"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-800 text-sm leading-tight truncate">{{ partner.name }}</p>
                                        <p class="text-[10px] font-mono text-slate-400">{{ partner.code }} · {{ tierLabel(partner.tier) }}</p>
                                    </div>
                                    <span class="font-black text-slate-800 text-sm whitespace-nowrap">{{ fmtMoney(partner.total_earnings) }}</span>
                                </div>
                            </div>
                            <p v-else class="text-sm text-slate-400 text-center py-8">Aucun affilié pour le moment.</p>
                            <Divider />
                            <div class="flex justify-between items-center px-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total payé</span>
                                <span class="font-black text-emerald-600">{{ fmtMoney(stats.total_paid) }}</span>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- TABLEAU DES AFFILIÉS -->
            <Card class="border-none shadow-sm rounded-xl overflow-hidden">
                <template #content>
                    <DataTable
                        :value="affiliates?.data ?? []"
                        :loading="tableLoading"
                        :lazy="true"
                        :paginator="true"
                        :rows="affiliates?.per_page ?? 10"
                        :totalRecords="affiliates?.total ?? 0"
                        :first="firstRow"
                        :rowsPerPageOptions="[10, 25, 50]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Affichage {first} à {last} sur {totalRecords} affiliés"
                        dataKey="id"
                        responsiveLayout="scroll"
                        @page="onPage"
                        class="p-datatable-lg"
                    >
                        <template #header>
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 px-2 py-2">
                                <div class="flex items-center gap-3 shrink-0">
                                    <h2 class="text-xl font-black text-slate-800">Annuaire des partenaires</h2>
                                    <Badge :value="affiliates?.total ?? 0" severity="warning" />
                                </div>
                                <div class="flex flex-col sm:flex-row sm:flex-wrap lg:flex-nowrap items-stretch sm:items-center gap-3 w-full lg:w-auto">
                                    <IconField iconPosition="left" class="w-full sm:w-64 shrink-0">
                                        <InputIcon class="pi pi-search text-slate-400" />
                                        <InputText v-model="search" placeholder="Nom, email ou code..." class="w-full h-11" aria-label="Rechercher un partenaire" />
                                    </IconField>
                                    <Select v-model="statusFilter" :options="statuses" :optionLabel="(s) => STATUS_LABELS[s] ?? s" placeholder="Tous les statuts" showClear class="w-full sm:w-44 h-11 shrink-0" inputClass="!flex !items-center" aria-label="Filtrer par statut" />
                                    <Select v-model="tierFilter" :options="tiers" optionLabel="label" optionValue="value" placeholder="Tous les tiers" showClear class="w-full sm:w-44 h-11 shrink-0" inputClass="!flex !items-center" aria-label="Filtrer par tier" />
                                </div>
                            </div>
                        </template>

                        <template #empty>
                            <div class="text-center py-10">
                                <i class="pi pi-users text-4xl text-slate-300 mb-3"></i>
                                <p class="font-bold text-slate-500">Aucun affilié trouvé</p>
                                <p class="text-xs text-slate-400">Modifiez vos filtres ou créez un nouvel affilié.</p>
                            </div>
                        </template>

                        <Column field="referral_code" header="Code" style="min-width: 9rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1">
                                    <span class="px-2 py-1 bg-slate-100 border border-slate-200 rounded font-mono text-xs font-bold text-blue-700">{{ data.referral_code }}</span>
                                    <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-secondary p-button-sm" v-tooltip.top="'Copier le code'" @click="copyCode(data.referral_code)" aria-label="Copier le code" />
                                </div>
                            </template>
                        </Column>

                        <Column field="name" header="Nom" style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar :label="initials(data.name)" shape="circle" class="bg-blue-100 text-blue-700 font-bold" />
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 leading-tight">{{ data.name }}</span>
                                        <span class="text-[10px] text-slate-400">depuis {{ fmtDate(data.joined_at) }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="email" header="Email" style="min-width: 12rem">
                            <template #body="{ data }">
                                <span class="text-sm text-slate-600">{{ data.email }}</span>
                            </template>
                        </Column>

                        <Column field="tier" header="Tier" style="min-width: 9rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: tierColor(data.tier) }"></span>
                                    <span class="text-xs font-bold text-slate-600 uppercase">{{ data.tier }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="clicks" header="Clics" style="min-width: 6rem">
                            <template #body="{ data }"><span class="font-bold text-slate-700">{{ fmtNumber(data.clicks) }}</span></template>
                        </Column>

                        <Column field="conversions" header="Conversions" style="min-width: 7rem">
                            <template #body="{ data }">
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded font-black text-sm">{{ fmtNumber(data.conversions) }}</span>
                            </template>
                        </Column>

                        <Column field="unpaid_earnings" header="À payer" style="min-width: 8rem">
                            <template #body="{ data }">
                                <span :class="Number(data.unpaid_earnings) > 0 ? 'font-black text-rose-600' : 'text-slate-400'">{{ fmtMoney(data.unpaid_earnings) }}</span>
                            </template>
                        </Column>

                        <Column field="status" header="Statut" style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" frozen style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 whitespace-nowrap">
                                    <Button icon="pi pi-eye" text rounded severity="secondary" v-tooltip.top="'Voir le détail'" @click="showDetail(data)" aria-label="Voir le détail" />
                                    <Button v-if="data.status === 'pending'" icon="pi pi-check" text rounded severity="success" v-tooltip.top="'Approuver'" @click="approveAffiliate(data)" aria-label="Approuver" />
                                    <Button v-if="Number(data.unpaid_earnings) > 0" icon="pi pi-wallet" text rounded severity="warning" v-tooltip.top="'Générer le paiement'" @click="generatePayout(data)" aria-label="Générer un paiement" />
                                    <Button v-else icon="pi pi-wallet" text rounded severity="warning" disabled v-tooltip.top="'Rien à payer'" aria-label="Rien à payer" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" v-tooltip.top="'Supprimer'" @click="destroyAffiliate(data)" aria-label="Supprimer" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- DIALOG : DÉTAIL AFFILIÉ -->
            <SncDialog
                v-model:visible="detailVisible"
                title="Détail du partenaire"
                subtitle="Profil, performances et historique des commissions"
                icon="pi-id-card"
                icon-class="from-blue-600 to-indigo-600"
                width="960px"
                v-if="affiliateDetail"
            >
                <div class="grid grid-cols-12 gap-5">
                    <div class="col-span-12">
                    <SncSection icon="pi-user" title="Profil & performances" subtitle="Identité, code de parrainage et indicateurs clés" icon-class="from-slate-600 to-slate-700">
                    <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 lg:col-span-4">
                        <div class="text-center mb-4">
                            <Avatar :label="initials(affiliateDetail.name)" shape="circle" size="xlarge" class="bg-blue-100 text-blue-700 font-black text-2xl mb-3" />
                            <h3 class="text-xl font-black text-slate-800">{{ affiliateDetail.name }}</h3>
                            <p class="text-slate-500 text-sm mb-3">{{ affiliateDetail.email }}</p>
                            <div class="flex justify-center gap-2">
                                <Tag :value="statusLabel(affiliateDetail.status)" :severity="statusSeverity(affiliateDetail.status)" />
                                <Tag :value="tierLabel(affiliateDetail.tier)" severity="info" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between bg-white p-2 rounded-xl border border-slate-100 mb-2">
                            <span class="font-mono text-xs font-bold text-blue-700">{{ affiliateDetail.referral_code }}</span>
                            <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-sm" @click="copyCode(affiliateDetail.referral_code)" aria-label="Copier le code" />
                        </div>
                        <div class="text-[11px] text-slate-400 text-center mb-4">Partenaire depuis {{ fmtDate(affiliateDetail.joined_at) }} · commission {{ affiliateDetail.commission_rate }} %</div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between bg-white p-2 rounded-xl"><span class="text-slate-500">Clics</span><strong>{{ fmtNumber(affiliateDetail.clicks) }}</strong></div>
                            <div class="flex justify-between bg-white p-2 rounded-xl"><span class="text-slate-500">Conversions</span><strong>{{ fmtNumber(affiliateDetail.conversions) }}</strong></div>
                            <div class="flex justify-between bg-white p-2 rounded-xl"><span class="text-slate-500">Taux de conversion</span><strong>{{ affiliateDetail.conversion_rate }} %</strong></div>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-8">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <div class="p-3 border border-slate-100 rounded-2xl bg-white">
                                <p class="text-[9px] font-black text-slate-400 uppercase">Revenus approuvés</p>
                                <p class="text-lg font-black text-slate-800">{{ fmtMoney(affiliateDetail.referrals_amount) }}</p>
                            </div>
                            <div class="p-3 border border-slate-100 rounded-2xl bg-white">
                                <p class="text-[9px] font-black text-slate-400 uppercase">Commissions gagnées</p>
                                <p class="text-lg font-black text-slate-800">{{ fmtMoney(affiliateDetail.referrals_commission) }}</p>
                            </div>
                            <div class="p-3 border border-slate-100 rounded-2xl bg-white">
                                <p class="text-[9px] font-black text-slate-400 uppercase">Déjà payé</p>
                                <p class="text-lg font-black text-emerald-700">{{ fmtMoney(affiliateDetail.payouts_paid) }}</p>
                            </div>
                            <div class="p-3 border border-rose-100 rounded-2xl bg-rose-50">
                                <p class="text-[9px] font-black text-rose-400 uppercase">À payer</p>
                                <p class="text-lg font-black text-rose-700">{{ fmtMoney(affiliateDetail.unpaid_earnings) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Derniers referrals</h4>
                                <table v-if="affiliateDetail.referrals?.length" class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-slate-400 border-b border-slate-100">
                                            <th class="pb-1">Cours</th><th class="pb-1">Date</th><th class="pb-1 text-right">Comm.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ref in affiliateDetail.referrals" :key="ref.id" class="border-b border-slate-50">
                                            <td class="py-1.5 pr-2 font-medium text-slate-700 max-w-[140px] truncate">{{ ref.course }}</td>
                                            <td class="py-1.5 pr-2 text-slate-500">{{ fmtDate(ref.date) }}</td>
                                            <td class="py-1.5 text-right font-bold text-orange-600">{{ fmtMoney(ref.commission) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p v-else class="text-xs text-slate-400 py-4 text-center">Aucun referral pour le moment.</p>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Derniers paiements</h4>
                                <table v-if="affiliateDetail.payouts?.length" class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-slate-400 border-b border-slate-100">
                                            <th class="pb-1">Date</th><th class="pb-1">Montant</th><th class="pb-1 text-right">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="payout in affiliateDetail.payouts" :key="payout.id" class="border-b border-slate-50">
                                            <td class="py-1.5 pr-2 text-slate-500">{{ fmtDate(payout.payout_date ?? payout.created_at) }}</td>
                                            <td class="py-1.5 pr-2 font-bold text-slate-800">{{ fmtMoney(payout.amount) }}</td>
                                            <td class="py-1.5 text-right"><Tag :value="payoutLabel(payout.status)" :severity="payoutSeverity(payout.status)" class="text-[9px]" /></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p v-else class="text-xs text-slate-400 py-4 text-center">Aucun paiement pour le moment.</p>
                            </div>
                        </div>

                        <p v-if="affiliateDetail.notes" class="mt-4 text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-3"><strong>Notes :</strong> {{ affiliateDetail.notes }}</p>
                    </div>
                    </div>
                    </SncSection>
                    </div>
                </div>

                <template #footer>
                    <Button label="Fermer" icon="pi pi-times" text severity="secondary" @click="detailVisible = false" />
                    <Button v-if="affiliateDetail.status === 'pending'" label="Approuver" icon="pi pi-check" severity="success" size="small" @click="approveAffiliate(affiliateDetail)" />
                    <Button v-if="affiliateDetail.status === 'pending'" label="Refuser" icon="pi pi-times" severity="danger" outlined size="small" @click="rejectAffiliate(affiliateDetail)" />
                    <Button label="Générer un paiement" icon="pi pi-wallet" severity="warning" outlined size="small" :disabled="Number(affiliateDetail.unpaid_earnings) <= 0" @click="generatePayout(affiliateDetail)" />
                    <Button label="Supprimer" icon="pi pi-trash" severity="danger" text size="small" @click="destroyAffiliate(affiliateDetail)" />
                </template>
            </SncDialog>

            <!-- DIALOG : NOUVEL AFFILIÉ -->
            <SncDialog
                v-model:visible="showNewDialog"
                title="Nouvel affilié"
                subtitle="Créez une demande de partenariat avec commission personnalisée"
                icon="pi-user-plus"
                icon-class="from-emerald-500 to-teal-600"
                width="560px"
            >
                <form class="flex flex-col gap-4" @submit.prevent="submitNewAffiliate">
                    <SncSection icon="pi-id-card" title="Informations du partenaire" icon-class="from-blue-500 to-indigo-600">
                        <div class="flex flex-col gap-4">
                            <div class="field">
                                <label for="aff-name" class="text-xs font-bold text-slate-500 uppercase mb-1 block">Nom complet</label>
                                <InputText id="aff-name" v-model="form.name" placeholder="ex : Grâce Mutombo" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
                                <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                            </div>
                            <div class="field">
                                <label for="aff-email" class="text-xs font-bold text-slate-500 uppercase mb-1 block">Email</label>
                                <InputText id="aff-email" v-model="form.email" type="email" placeholder="ex : partenaire@domaine.cd" class="w-full" :class="{ 'p-invalid': form.errors.email || clientEmailError }" />
                                <small class="p-error" v-if="clientEmailError">{{ clientEmailError }}</small>
                                <small class="p-error" v-else-if="form.errors.email">{{ form.errors.email }}</small>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label for="aff-tier" class="text-xs font-bold text-slate-500 uppercase mb-1 block">Tier</label>
                                    <Select id="aff-tier" v-model="form.tier" :options="tiers" optionLabel="label" optionValue="value" class="w-full" />
                                    <small class="p-error" v-if="form.errors.tier">{{ form.errors.tier }}</small>
                                </div>
                                <div class="field">
                                    <label for="aff-rate" class="text-xs font-bold text-slate-500 uppercase mb-1 block">Commission (%)</label>
                                    <InputNumber id="aff-rate" v-model="form.commission_rate" suffix=" %" :min="0" :max="100" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" />
                                    <small class="p-error" v-if="form.errors.commission_rate">{{ form.errors.commission_rate }}</small>
                                </div>
                            </div>
                        </div>
                    </SncSection>
                    <p class="text-[11px] text-slate-400 leading-snug">L'affilié sera créé avec le statut <strong>En attente</strong>. Il devra être approuvé avant de pouvoir générer des commissions. Si l'email existe déjà, il sera rattaché à ce compte.</p>
                </form>

                <template #footer>
                    <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="showNewDialog = false" />
                    <Button type="submit" label="Créer la demande" icon="pi pi-check" :loading="form.processing" @click="submitNewAffiliate" />
                </template>
            </SncDialog>

            <footer class="mt-12 text-center text-slate-400 text-xs pb-6 font-medium">
                © 2026 SubNetCongo — Programme d'affiliation
            </footer>
            </div>
        </div>
    </AppLayout>
</template>
