<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from  '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import SelectButton from 'primevue/selectbutton';
import ProgressBar from 'primevue/progressbar';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';

const { t } = useI18n();
const toast = useToast();

// --- STATE MANAGEMENT ---
const loading = ref(true);
const subscriptions = ref([]);
const selectedSubscriptions = ref(null);
const globalFilterValue = ref('');
const displayPlanDialog = ref(false);
const displaySubDialog = ref(false);
const submitted = ref(false);

// Form States
const plan = ref({
    id: null,
    name: '',
    price: 0,
    interval: 'monthly',
    features: '',
    status: 'active',
    color: '#6366F1'
});

const subDetail = ref(null);

// --- FILTERS CONFIG ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'plan.name': { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    verified: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- CONSTANTS & DATA ---
const statuses = ref(['Active', 'Pending', 'Expired', 'Canceled']);
const intervals = ref([
    { label: 'Mensuel', value: 'monthly' },
    { label: 'Trimestriel', value: 'quarterly' },
    { label: 'Annuel', value: 'yearly' }
]);

const plansList = ref([
    { name: 'Starter', code: 'START', count: 450, mrr: 4500, color: 'emerald' },
    { name: 'Professional', code: 'PRO', count: 890, mrr: 12450, color: 'indigo' },
    { name: 'Enterprise', code: 'ENT', count: 120, mrr: 35000, color: 'amber' }
]);

// --- MOCK DATA GENERATION ---
const loadSubscriptions = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const names = ['Alice Vasseur', 'Thomas Legrand', 'Sophie Martin', 'Jean Dupont', 'Marie Curie', 'Luc Besson', 'Marc Levy'];
        const planNames = ['Starter', 'Professional', 'Enterprise'];

        for (let i = 1; i <= 50; i++) {
            const planIdx = Math.floor(Math.random() * 3);
            const statusIdx = Math.floor(Math.random() * 4);
            data.push({
                id: 1000 + i,
                user: {
                    name: names[Math.floor(Math.random() * names.length)],
                    email: `user${i}@example.com`,
                    avatar: `https://i.pravatar.cc/150?u=${i}`
                },
                plan: {
                    name: planNames[planIdx],
                    price: [19, 49, 199][planIdx],
                    code: ['START', 'PRO', 'ENT'][planIdx]
                },
                startDate: new Date(2023, Math.floor(Math.random() * 11), Math.floor(Math.random() * 28)),
                nextBilling: new Date(2025, 2, 15),
                status: statuses.value[statusIdx],
                paymentMethod: 'Visa **** 4242',
                usage: Math.floor(Math.random() * 100)
            });
        }
        subscriptions.value = data;
        loading.value = false;
    }, 1000);
};

// --- CHARTS DATA ---
const revenueChartData = ref({
    labels: ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Fév'],
    datasets: [
        {
            label: 'Revenu Récurrent (MRR)',
            data: [42000, 45000, 44000, 51000, 56000, 59500],
            fill: true,
            borderColor: '#6366F1',
            tension: 0.4,
            backgroundColor: 'rgba(99, 102, 241, 0.1)'
        }
    ]
});

const distributionChartData = ref({
    labels: ['Starter', 'Professional', 'Enterprise'],
    datasets: [{
        data: [450, 890, 120],
        backgroundColor: ['#10B981', '#6366F1', '#F59E0B'],
        hoverBackgroundColor: ['#059669', '#4F46E5', '#D97706']
    }]
});

const chartOptions = {
    plugins: {
        legend: { labels: { color: '#495057' } }
    },
    scales: {
        x: { ticks: { color: '#495057' }, grid: { color: '#ebedef' } },
        y: { ticks: { color: '#495057' }, grid: { color: '#ebedef' } }
    }
};

// --- ACTIONS ---
const openNewPlan = () => {
    plan.value = { id: null, name: '', price: 0, interval: 'monthly', features: '', status: 'active' };
    submitted.value = false;
    displayPlanDialog.value = true;
};

const hideDialog = () => {
    displayPlanDialog.value = false;
    submitted.value = false;
};

const savePlan = () => {
    submitted.value = true;
    if (plan.value.name.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Plan mis à jour avec succès', life: 3000 });
        displayPlanDialog.value = false;
    }
};

const viewSubscription = (data) => {
    subDetail.value = { ...data };
    displaySubDialog.value = true;
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Active': return 'success';
        case 'Pending': return 'warning';
        case 'Expired': return 'danger';
        case 'Canceled': return 'info';
        default: return null;
    }
};

const exportCSV = () => {
    // Logic for PrimeVue export
};

const toggleSubStatus = () => {
    const newStatus = subDetail.value.status === 'Active' ? 'Canceled' : 'Active';
    subDetail.value.status = newStatus;
    toast.add({ severity: 'info', summary: 'Statut Modifié', detail: `L'abonnement est maintenant ${newStatus}`, life: 3000 });
};

// --- LIFECYCLE ---
onMounted(() => {
    loadSubscriptions();
});

// Computed for total revenue
const totalMRR = computed(() => {
    return subscriptions.value.reduce((acc, curr) => acc + curr.plan.price, 0).toLocaleString();
});

const activeCount = computed(() => {
    return subscriptions.value.filter(s => s.status === 'Active').length;
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Abonnements" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER SECTION -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 flex items-center gap-3">
                        <i class="pi pi-credit-card text-indigo-600 text-2xl"></i>
                        Abonnements & Plans
                    </h1>
                    <p class="text-slate-500 font-medium mt-1">Gérez vos revenus récurrents et les offres tarifaires</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Nouveau Plan" icon="pi pi-plus" class="p-button-primary shadow-md" @click="openNewPlan" />
                    <Button label="Exporter" icon="pi pi-download" class="p-button-outlined p-button-secondary" @click="exportCSV" />
                </div>
            </header>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <Card class="border-none shadow-sm rounded-1xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-slate-500 font-bold text-xs uppercase mb-1">Revenu Mensuel (MRR)</span>
                                <div class="text-3xl font-black text-slate-800">{{ totalMRR }} €</div>
                            </div>
                            <div class="bg-emerald-50 p-3 rounded-xl">
                                <i class="pi pi-chart-line text-emerald-600"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs">
                            <span class="text-emerald-500 font-bold mr-2"><i class="pi pi-arrow-up"></i> +8.4%</span>
                            <span class="text-slate-400 font-medium text-[10px]">PAR RAPPORT AU MOIS DERNIER</span>
                        </div>
                    </template>
                </Card>

                <Card class="border-none shadow-sm rounded-1xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-slate-500 font-bold text-xs uppercase mb-1">Abonnements Actifs</span>
                                <div class="text-3xl font-black text-slate-800">{{ activeCount }}</div>
                            </div>
                            <div class="bg-indigo-50 p-3 rounded-xl">
                                <i class="pi pi-users text-indigo-600"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs">
                            <span class="text-indigo-500 font-bold mr-2">1,240 total</span>
                            <span class="text-slate-400 font-medium text-[10px]">CLIENTS PAYANTS</span>
                        </div>
                    </template>
                </Card>

                <Card class="border-none shadow-sm rounded-1xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-slate-500 font-bold text-xs uppercase mb-1">Taux d'Attrition (Churn)</span>
                                <div class="text-3xl font-black text-slate-800">2.1%</div>
                            </div>
                            <div class="bg-rose-50 p-3 rounded-xl">
                                <i class="pi pi-sync text-rose-600"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs">
                            <span class="text-rose-500 font-bold mr-2">-0.5%</span>
                            <span class="text-slate-400 font-medium text-[10px]">AMÉLIORATION</span>
                        </div>
                    </template>
                </Card>

                <Card class="border-none shadow-sm rounded-1xl overflow-hidden">
                    <template #content>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-slate-500 font-bold text-xs uppercase mb-1">ARPU (Revenu Moyen)</span>
                                <div class="text-3xl font-black text-slate-800">34.50 €</div>
                            </div>
                            <div class="bg-amber-50 p-3 rounded-xl">
                                <i class="pi pi-wallet text-amber-600"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs">
                            <span class="text-amber-500 font-bold mr-2"><i class="pi pi-arrow-up"></i> +2.10 €</span>
                            <span class="text-slate-400 font-medium text-[10px]">VS TRIMESTRE PRÉCÉDENT</span>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- ANALYTICS SECTION -->
            <div class="grid grid-cols-12 gap-8 mb-8">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-1xl h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-2">
                                <span class="text-lg font-bold">Croissance des Revenus</span>
                                <SelectButton v-model="selectedSubscriptions" :options="['6M', '1Y', 'ALL']" class="p-button-sm" />
                            </div>
                        </template>
                        <template #content>
                            <Chart type="line" :data="revenueChartData" :options="chartOptions" class="h-64" />
                        </template>
                    </Card>
                </div>
                <div class="col-span-12 lg:col-span-4">
                    <Card class="border-none shadow-sm rounded-1xl h-full">
                        <template #title><span class="text-lg font-bold px-2">Répartition des Plans</span></template>
                        <template #content>
                            <div class="flex flex-col items-center">
                                <Chart type="doughnut" :data="distributionChartData" :options="{ cutout: '70%', plugins: { legend: { position: 'bottom' } } }" class="h-48 mb-4" />
                                <div class="w-full space-y-3">
                                    <div v-for="p in plansList" :key="p.code" class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <div :class="`w-3 h-3 rounded-full bg-${p.color}-500` "></div>
                                            <span class="text-slate-600">{{ p.name }}</span>
                                        </div>
                                        <span class="font-bold text-slate-800">{{ p.count }} users</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- MAIN DATA TABLE -->
            <Card class="border-none shadow-sm rounded-1xl overflow-hidden">
                <template #content>
                    <DataTable
                        v-model:filters="filters"
                        :value="subscriptions"
                        :paginator="true"
                        :rows="10"
                        dataKey="id"
                        :loading="loading"
                        stripedRows
                        v-model:selection="selectedSubscriptions"
                        removableSort
                        filterDisplay="menu"
                        :globalFilterFields="['user.name', 'user.email', 'plan.name', 'status']"
                        class="p-datatable-sm"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xl font-bold text-slate-800">Liste des Abonnés</h3>
                                    <Badge :value="subscriptions.length" severity="info" />
                                </div>
                                <span class="p-input-icon-left w-full md:w-auto">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Recherche globale..." class="w-full md:w-80" />
                                </span>
                            </div>
                        </template>

                        <template #empty> Aucun abonnement trouvé. </template>
                        <template #loading> Chargement des données... </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column field="user.name" header="Utilisateur" sortable style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar :image="data.user.avatar" shape="circle" size="medium" />
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-700 leading-none mb-1">{{ data.user.name }}</span>
                                        <span class="text-xs text-slate-400">{{ data.user.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="plan.name" header="Offre" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <Tag :value="data.plan.name" :severity="data.plan.code === 'ENT' ? 'warning' : 'info'" class="px-3" />
                            </template>
                        </Column>

                        <Column field="plan.price" header="Tarif" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-800">{{ data.plan.price }} €<span class="text-[10px] text-slate-400">/mois</span></span>
                            </template>
                        </Column>

                        <Column field="startDate" header="Inscrit le" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.startDate.toLocaleDateString() }}
                            </template>
                        </Column>

                        <Column field="usage" header="Usage" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <div class="flex justify-between text-[10px] font-bold">
                                        <span>{{ data.usage }}%</span>
                                        <span class="text-slate-400">Quota</span>
                                    </div>
                                    <ProgressBar :value="data.usage" :showValue="false" style="height: 6px"
                                        :color="data.usage > 90 ? '#F43F5E' : '#6366F1'" />
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Statut" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <Badge :value="data.status" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" frozen style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" @click="viewSubscription(data)" />
                                    <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-secondary" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- MODAL: CREATE/EDIT PLAN -->
            <Dialog v-model:visible="displayPlanDialog" :style="{width: '550px'}" header="Détails du Plan Tarifaire" :modal="true" class="p-fluid">
                <div class="space-y-6 pt-4">
                    <div class="field">
                        <label for="name" class="font-bold block mb-2">Nom de l'offre</label>
                        <InputText id="name" v-model.trim="plan.name" required="true" autofocus :class="{'p-invalid': submitted && !plan.name}" placeholder="ex: Pro Max" />
                        <small class="p-error" v-if="submitted && !plan.name">Le nom est obligatoire.</small>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="field">
                            <label for="price" class="font-bold block mb-2">Prix (€)</label>
                            <InputNumber id="price" v-model="plan.price" mode="currency" currency="EUR" locale="fr-FR" />
                        </div>
                        <div class="field">
                            <label for="interval" class="font-bold block mb-2">Cycle de facturation</label>
                            <Dropdown id="interval" v-model="plan.interval" :options="intervals" optionLabel="label" optionValue="value" />
                        </div>
                    </div>

                    <div class="field">
                        <label for="features" class="font-bold block mb-2">Fonctionnalités incluses (une par ligne)</label>
                        <Textarea id="features" v-model="plan.features" rows="4" placeholder="Accès illimité&#10;Support 24/7&#10;Stockage 100Go" />
                    </div>

                    <div class="field">
                        <label class="font-bold block mb-2">Status du plan</label>
                        <div class="flex gap-4">
                            <div class="flex items-center">
                                <Checkbox inputId="status1" name="status" value="active" v-model="plan.status" />
                                <label for="status1" class="ml-2">Actif (Visible par les clients)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="hideDialog" />
                    <Button label="Enregistrer l'offre" icon="pi pi-check" class="p-button-primary" @click="savePlan" />
                </template>
            </Dialog>

            <!-- MODAL: VIEW SUBSCRIPTION DETAIL -->
            <Dialog v-model:visible="displaySubDialog" :style="{width: '700px'}" header="Fiche Abonné" :modal="true" v-if="subDetail">
                <div class="p-2">
                    <div class="flex items-center gap-6 mb-8 p-4 bg-slate-50 rounded-1xl">
                        <Avatar :image="subDetail.user.avatar" size="xlarge" shape="circle" class="border-4 border-white shadow-sm" />
                        <div class="flex-1">
                            <h2 class="text-2xl font-black text-slate-800">{{ subDetail.user.name }}</h2>
                            <p class="text-slate-500 font-medium">{{ subDetail.user.email }}</p>
                            <div class="mt-2 flex gap-2">
                                <Tag value="Vérifié" severity="success" icon="pi pi-check-circle" />
                                <Tag :value="subDetail.status" :severity="getStatusSeverity(subDetail.status)" />
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-400 block uppercase">Client depuis</span>
                            <span class="font-bold text-slate-800">{{ subDetail.startDate.toLocaleDateString() }}</span>
                        </div>
                    </div>

                    <TabView>
                        <TabPanel header="Abonnement">
                            <div class="grid grid-cols-2 gap-8 py-4">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Plan actuel</label>
                                        <div class="text-lg font-black text-indigo-600">{{ subDetail.plan.name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Dernier Paiement</label>
                                        <div class="font-bold text-slate-700">Le 15/02/2025 - {{ subDetail.plan.price }} €</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Méthode de paiement</label>
                                        <div class="flex items-center gap-2 font-medium text-slate-700">
                                            <i class="pi pi-credit-card"></i> {{ subDetail.paymentMethod }}
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <h4 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                                        <i class="pi pi-info-circle"></i> Notes internes
                                    </h4>
                                    <p class="text-sm text-slate-500 italic">Client fidèle, utilise principalement le module de statistiques avancées. Potentiel upgrade vers Enterprise en Q3.</p>
                                </div>
                            </div>

                            <Divider />

                            <div class="flex justify-between items-center bg-rose-50 p-4 rounded-xl mt-4">
                                <div>
                                    <h4 class="font-bold text-rose-800">Zone de Danger</h4>
                                    <p class="text-xs text-rose-600">L'annulation prendra effet à la fin du cycle de facturation actuel.</p>
                                </div>
                                <Button :label="subDetail.status === 'Active' ? 'Résilier l\'abonnement' : 'Réactiver'"
                                    :class="subDetail.status === 'Active' ? 'p-button-danger p-button-outlined' : 'p-button-success'"
                                    @click="toggleSubStatus" />
                            </div>
                        </TabPanel>

                        <TabPanel header="Historique Facturation">
                            <div class="py-2">
                                <DataTable :value="[
                                    { id: 1, date: '15 Jan 2025', amount: '49.00 €', status: 'Payé', ref: 'INV-2025-001' },
                                    { id: 2, date: '15 Déc 2024', amount: '49.00 €', status: 'Payé', ref: 'INV-2024-098' },
                                    { id: 3, date: '15 Nov 2024', amount: '49.00 €', status: 'Payé', ref: 'INV-2024-045' }
                                ]" class="p-datatable-sm">
                                    <Column field="date" header="Date"></Column>
                                    <Column field="ref" header="Facture"></Column>
                                    <Column field="amount" header="Montant" class="font-bold"></Column>
                                    <Column field="status" header="Statut">
                                        <template #body="{ data }">
                                            <span class="text-emerald-500 font-bold"><i class="pi pi-check"></i> {{ data.status }}</span>
                                        </template>
                                    </Column>
                                    <Column header="PDF">
                                        <template #body>
                                            <Button icon="pi pi-file-pdf" class="p-button-text p-button-sm p-button-secondary" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <TabPanel header="Logs d'activité">
                            <div class="space-y-4 py-4">
                                <div class="flex gap-3 text-sm">
                                    <div class="text-indigo-500"><i class="pi pi-circle-fill text-[8px]"></i></div>
                                    <div>
                                        <span class="font-bold">Changement de carte</span> - 12/02/2025
                                        <p class="text-slate-400 text-xs">Utilisateur a mis à jour ses informations bancaires.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    <div class="text-slate-300"><i class="pi pi-circle-fill text-[8px]"></i></div>
                                    <div>
                                        <span class="font-bold">Connexion réussie</span> - 10/02/2025
                                        <p class="text-slate-400 text-xs">IP: 192.168.1.1 (Paris, FR)</p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
                <template #footer>
                    <Button label="Fermer" icon="pi pi-times" class="p-button-secondary p-button-text" @click="displaySubDialog = false" />
                    <Button label="Envoyer un message" icon="pi pi-envelope" class="p-button-primary" />
                </template>
            </Dialog>

            <!-- FOOTER INFO -->
            <footer class="mt-12 text-center text-slate-400 text-sm pb-8">
                <p>&copy; 2026 SubNetCongo Analytics. Tous droits réservés.</p>
                <div class="flex justify-center gap-4 mt-2">
                    <a href="#" class="hover:text-indigo-500">Conditions d'utilisation</a>
                    <span>•</span>
                    <a href="#" class="hover:text-indigo-500">Politique de confidentialité</a>
                </div>
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom PrimeVue Overrides */
:deep(.p-card) {
    border-radius: 1rem;
}

:deep(.p-datatable .p-datatable-header) {
    background: transparent;
    padding: 1.5rem 0;
    border: none;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #F1F5F9;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.025em;
    padding: 1rem;
}

:deep(.p-progressbar .p-progressbar-value) {
    border-radius: 10px;
}

:deep(.p-tabview .p-tabview-panels) {
    padding: 1rem 0;
}

:deep(.p-dialog .p-dialog-header) {
    padding: 1.5rem;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 0 1.5rem 1.5rem 1.5rem;
}

.field label {
    font-size: 0.875rem;
    color: #334155;
}

/* Animations simples */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Glassmorphism subtle */
.bg-white-glass {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}
</style>
