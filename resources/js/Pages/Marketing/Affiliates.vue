<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

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
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import ProgressBar from 'primevue/progressbar';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';
import Divider from 'primevue/divider';
import Calendar from 'primevue/calendar';
import OverlayPanel from 'primevue/overlaypanel';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const affiliates = ref([]);
const selectedAffiliates = ref(null);
const expandedRows = ref([]);
const displayDetailsDialog = ref(false);
const displaySettingsDialog = ref(false);
const currentAffiliate = ref(null);
const op = ref();

// --- FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    tier: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const tiers = ref([
    { label: 'Bronze (10%)', value: 'BRONZE', color: '#cd7f32' },
    { label: 'Silver (15%)', value: 'SILVER', color: '#c0c0c0' },
    { label: 'Gold (25%)', value: 'GOLD', color: '#ffd700' },
    { label: 'Platinum (40%)', value: 'PLATINUM', color: '#e5e4e2' }
]);

const statuses = ref(['Active', 'Pending', 'Suspended']);

// --- GENERATION DE DONNEES (Simulant une API E-learning) ---
const loadAffiliateData = () => {
    loading.value = true;
    setTimeout(() => {
        const mockData = [];
        const names = ['Jean-Pierre Bemba', 'Fatima Traoré', 'Alphonse Kabongo', 'Grâce Mutombo', 'David Nkulu', 'Sarah Mendy', 'Ismaël Cissé'];
        
        for (let i = 1; i <= 40; i++) {
            const tierIdx = Math.floor(Math.random() * 4);
            const salesCount = Math.floor(Math.random() * 150);
            mockData.push({
                id: i,
                user: {
                    name: names[i % names.length] + ' ' + (i > 7 ? i : ''),
                    email: `affiliate${i}@subnetcongo.cd`,
                    avatar: `https://i.pravatar.cc/150?u=aff${i}`,
                    joined_at: new Date(2023, Math.floor(Math.random() * 12), 15)
                },
                tier: tiers.value[tierIdx].value,
                stats: {
                    clicks: Math.floor(Math.random() * 5000),
                    conversions: salesCount,
                    conversion_rate: ((salesCount / 5000) * 100).toFixed(2),
                    total_revenue: salesCount * 45, // Prix moyen formation
                    commission_earned: (salesCount * 45 * (tierIdx + 1) * 0.1).toFixed(2)
                },
                balance: {
                    pending: (Math.random() * 500).toFixed(2),
                    withdrawable: (Math.random() * 2000).toFixed(2),
                    last_payout: '12/01/2025'
                },
                status: i % 15 === 0 ? 'Suspended' : (i % 8 === 0 ? 'Pending' : 'Active'),
                recent_sales: [
                    { id: 101, course: 'Masterclass Laravel Pro', date: '2025-02-01', amount: 99, commission: 14.85 },
                    { id: 102, course: 'UI/UX Design Avancé', date: '2025-01-28', amount: 150, commission: 22.50 },
                    { id: 103, course: 'Sécurité Web Congolaise', date: '2025-01-25', amount: 75, commission: 11.25 }
                ]
            });
        }
        affiliates.value = mockData;
        loading.value = false;
    }, 1200);
};

// --- LOGIQUE DES GRAPHIQUES ---
const performanceChartData = ref({
    labels: ['Août', 'Sep', 'Oct', 'Nov', 'Déc', 'Jan', 'Fév'],
    datasets: [
        {
            label: 'Commissions Générées ($)',
            data: [1200, 1900, 1700, 2400, 3100, 2800, 3500],
            fill: false,
            borderColor: '#F97316', // Orange SubNetCongo
            tension: 0.4,
            pointBackgroundColor: '#F97316'
        },
        {
            label: 'Nouveaux Affiliés',
            data: [5, 12, 8, 15, 20, 18, 25],
            fill: true,
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderColor: '#6366F1',
            tension: 0.4
        }
    ]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true } }
    },
    scales: {
        x: { grid: { display: false } },
        y: { grid: { color: '#f1f5f9' }, border: { dash: [5, 5] } }
    }
};

// --- ACTIONS ---
const showDetails = (data) => {
    currentAffiliate.value = data;
    displayDetailsDialog.value = true;
};

const openSettings = () => {
    displaySettingsDialog.value = true;
};

const getTierColor = (tier) => {
    return tiers.value.find(t => t.value === tier)?.color || '#94a3b8';
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Active': return 'success';
        case 'Pending': return 'warning';
        case 'Suspended': return 'danger';
        default: return 'info';
    }
};

const approvePayout = () => {
    toast.add({ severity: 'success', summary: 'Paiement Approuvé', detail: 'Le virement a été initié pour ce partenaire.', life: 4000 });
};

const toggleTier = (event) => {
    op.value.toggle(event);
};

// --- CALCULS STATISTIQUES ---
const totalCommissionMonth = computed(() => {
    return affiliates.value.reduce((acc, curr) => acc + parseFloat(curr.stats.commission_earned), 0).toLocaleString();
});

const totalSalesCount = computed(() => {
    return affiliates.value.reduce((acc, curr) => acc + curr.stats.conversions, 0);
});

onMounted(() => {
    loadAffiliateData();
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion Affiliation - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">
            
            <!-- EN-TETE -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-5">
                    <div class="bg-orange-500 p-4 rounded-2xl shadow-lg shadow-orange-100">
                        <i class="pi pi-share-alt text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Programme d'Affiliation</h1>
                        <p class="text-slate-500 font-medium">Suivez vos partenaires et optimisez vos ventes de formations</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Paramètres des Paliers" icon="pi pi-cog" class="p-button-outlined p-button-secondary border-2" @click="openSettings" />
                    <Button label="Nouvelle Campagne" icon="pi pi-megaphone" class="p-button-warning shadow-md px-6" />
                </div>
            </header>

            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <Card v-for="kpi in [
                    { label: 'Partenaires Actifs', val: '1,284', icon: 'pi-users', color: 'indigo', trend: '+12%' },
                    { label: 'Ventes Affiliées', val: totalSalesCount, icon: 'pi-shopping-cart', color: 'orange', trend: '+24%' },
                    { label: 'Commissions à Payer', val: '4,150.00 $', icon: 'pi-wallet', color: 'rose', trend: '-5%' },
                    { label: 'Revenu Net Généré', val: '42,900 $', icon: 'pi-chart-bar', color: 'emerald', trend: '+18%' }
                ]" :key="kpi.label" class="border-none shadow-sm rounded-3xl overflow-hidden hover:-translate-y-1 transition-transform duration-300">
                    <template #content>
                        <div class="flex justify-between items-center mb-4">
                            <div :class="`p-3 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-600` ">
                                <i :class="`pi ${kpi.icon} text-xl` "></i>
                            </div>
                            <Tag :value="kpi.trend" :severity="kpi.trend.includes('+') ? 'success' : 'danger'" class="rounded-lg" />
                        </div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</div>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                    </template>
                </Card>
            </div>

            <!-- PERFORMANCE GRAPH -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="h-full border-none shadow-sm rounded-3xl">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-2">
                                <span class="text-xl font-black text-slate-800">Evolution du Programme</span>
                                <div class="flex gap-2">
                                    <Button label="Jour" class="p-button-text p-button-sm text-slate-400" />
                                    <Button label="Mois" class="p-button-sm bg-indigo-50 text-indigo-600 border-none font-bold" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[350px]">
                                <Chart type="line" :data="performanceChartData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-span-12 lg:col-span-4">
                    <Card class="h-full border-none shadow-sm rounded-3xl bg-slate-900 text-white overflow-hidden">
                        <template #content>
                            <div class="relative z-10">
                                <h3 class="text-2xl font-bold mb-6">Top Formations <br/><span class="text-orange-400">par Affiliation</span></h3>
                                <div class="space-y-6">
                                    <div v-for="course in [
                                        { name: 'Devenir Freelance Web', share: 45, color: 'bg-orange-500' },
                                        { name: 'IA & Productivité', share: 32, color: 'bg-indigo-500' },
                                        { name: 'Excel pour Entreprise', share: 18, color: 'bg-emerald-500' }
                                    ]" :key="course.name">
                                        <div class="flex justify-between text-sm mb-2 font-medium">
                                            <span>{{ course.name }}</span>
                                            <span class="text-slate-400">{{ course.share }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-800 rounded-full h-2">
                                            <div :class="`h-2 rounded-full ${course.color}` " :style="{ width: course.share + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-10 p-4 rounded-2xl bg-slate-800/50 border border-slate-700">
                                    <div class="text-xs text-slate-400 uppercase font-bold mb-1">Moyenne Commission / Vente</div>
                                    <div class="text-2xl font-black">18.45 $</div>
                                </div>
                            </div>
                            <!-- Décoration Background -->
                            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-orange-500/20 blur-3xl rounded-full"></div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- TABLEAU DES AFFILIES -->
            <Card class="border-none shadow-sm rounded-3xl overflow-hidden">
                <template #content>
                    <DataTable 
                        v-model:filters="filters"
                        :value="affiliates" 
                        :paginator="true" 
                        :rows="10" 
                        dataKey="id" 
                        :loading="loading"
                        v-model:selection="selectedAffiliates"
                        v-model:expandedRows="expandedRows"
                        filterDisplay="menu"
                        :globalFilterFields="['user.name', 'user.email']"
                        class="p-datatable-lg custom-affiliate-table"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Affichage {first} à {last} sur {totalRecords} partenaires"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4 px-2">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter">Annuaire Partenaires</h2>
                                    <Badge :value="affiliates.length" severity="warning" />
                                </div>
                                <span class="p-input-icon-left w-full md:w-auto">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Chercher un nom ou email..." class="w-full md:w-80 rounded-xl border-slate-200" />
                                </span>
                            </div>
                        </template>

                        <!-- Expansion pour détails rapides -->
                        <template #expansion="{ data }">
                            <div class="p-6 bg-slate-50/50 border-y border-slate-100">
                                <h4 class="text-sm font-black text-slate-500 uppercase mb-4 tracking-widest">Dernières conversions générées</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div v-for="sale in data.recent_sales" :key="sale.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm mb-1">{{ sale.course }}</p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">{{ sale.date }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-black text-indigo-600">{{ sale.commission }} $</p>
                                            <p class="text-[9px] text-slate-300 font-bold">COMMISSION</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <Button label="Voir tout l'historique de vente" icon="pi pi-external-link" class="p-button-text p-button-sm font-bold text-indigo-500" />
                                </div>
                            </div>
                        </template>

                        <Column expander style="width: 3rem" />

                        <Column field="user.name" header="PARTENAIRE" sortable style="min-width: 18rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <Avatar :image="data.user.avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white" :class="data.status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300'"></div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 text-base leading-tight">{{ data.user.name }}</span>
                                        <span class="text-xs text-slate-400 font-medium italic">{{ data.user.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="tier" header="PALIER" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: getTierColor(data.tier) }"></div>
                                    <span class="font-black text-[11px] tracking-widest uppercase text-slate-600">{{ data.tier }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="stats.conversions" header="VENTES" sortable class="text-center" style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg font-black">{{ data.stats.conversions }}</span>
                            </template>
                        </Column>

                        <Column field="stats.commission_earned" header="GAINS TOTAUX" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800">{{ data.stats.commission_earned }} $</span>
                                    <div class="flex items-center gap-1 text-[9px] text-emerald-500 font-bold uppercase">
                                        <i class="pi pi-arrow-up text-[8px]"></i> +{{ (Math.random()*15).toFixed(1) }}%
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="balance.withdrawable" header="SOLDE DISPO" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded text-sm">{{ data.balance.withdrawable }} $</span>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <Badge :value="data.status" :severity="getStatusSeverity(data.status)" class="px-4 py-1" />
                            </template>
                        </Column>

                        <Column header="ACTION" alignFrozen="right" frozen style="min-width: 6rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-cog" class="p-button-rounded p-button-text p-button-secondary" @click="showDetails(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- MODALE: DETAILS AFFILIE -->
            <Dialog v-model:visible="displayDetailsDialog" :modal="true" :style="{ width: '850px' }" header="Gestion Partenaire" class="p-fluid affiliate-modal" v-if="currentAffiliate">
                <div class="grid grid-cols-12 gap-6 pt-4">
                    <!-- Sidebar Profil -->
                    <div class="col-span-12 lg:col-span-4 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <div class="text-center mb-6">
                            <Avatar :image="currentAffiliate.user.avatar" size="xlarge" shape="circle" class="border-4 border-white shadow-xl mb-4" />
                            <h3 class="text-xl font-black text-slate-800">{{ currentAffiliate.user.name }}</h3>
                            <p class="text-slate-500 text-sm font-medium mb-4">{{ currentAffiliate.user.email }}</p>
                            <Button label="Changer le Palier" icon="pi pi-sort-alt" class="p-button-outlined p-button-sm" @click="toggleTier" />
                            
                            <OverlayPanel ref="op">
                                <div class="flex flex-col gap-2 p-2">
                                    <Button v-for="t in tiers" :key="t.value" :label="t.label" class="p-button-text p-button-sm text-left font-bold" @click="currentAffiliate.tier = t.value" />
                                </div>
                            </OverlayPanel>
                        </div>

                        <Divider />

                        <div class="space-y-4">
                            <div class="flex justify-between items-center bg-white p-3 rounded-2xl shadow-sm">
                                <span class="text-xs font-bold text-slate-400 uppercase">Taux de conversion</span>
                                <span class="font-black text-indigo-600">{{ currentAffiliate.stats.conversion_rate }}%</span>
                            </div>
                            <div class="flex justify-between items-center bg-white p-3 rounded-2xl shadow-sm">
                                <span class="text-xs font-bold text-slate-400 uppercase">Total Clics</span>
                                <span class="font-black text-slate-800">{{ currentAffiliate.stats.clicks }}</span>
                            </div>
                            <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100">
                                <p class="text-[10px] font-black text-emerald-600 uppercase mb-2">Solde à payer</p>
                                <div class="text-3xl font-black text-emerald-800 mb-3">{{ currentAffiliate.balance.withdrawable }} $</div>
                                <Button label="Approuver Paiement" icon="pi pi-check" class="p-button-success p-button-sm w-full font-bold shadow-lg shadow-emerald-200" @click="approvePayout" />
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="col-span-12 lg:col-span-8">
                        <TabView class="custom-tabs">
                            <TabPanel header="Performances">
                                <div class="space-y-6 pt-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-4 border-2 border-slate-100 rounded-2xl">
                                            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Revenu apporté</p>
                                            <div class="text-2xl font-black text-slate-800">{{ currentAffiliate.stats.total_revenue }} $</div>
                                        </div>
                                        <div class="p-4 border-2 border-slate-100 rounded-2xl text-right">
                                            <p class="text-xs font-bold text-slate-400 uppercase mb-1">Partenariat depuis</p>
                                            <div class="text-base font-black text-slate-800">15 Oct 2023</div>
                                        </div>
                                    </div>
                                    
                                    <h4 class="font-black text-slate-800 border-l-4 border-orange-500 pl-3">Top 3 Formations vendues</h4>
                                    <DataTable :value="currentAffiliate.recent_sales" class="p-datatable-sm rounded-xl overflow-hidden border border-slate-100">
                                        <Column field="course" header="Cours"></Column>
                                        <Column field="amount" header="Prix ($)" class="font-bold"></Column>
                                        <Column field="commission" header="Comm. ($)">
                                            <template #body="{ data }">
                                                <span class="text-orange-500 font-bold">{{ data.commission }}</span>
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <TabPanel header="Liens & Assets">
                                <div class="space-y-4 pt-4">
                                    <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                                        <label class="block text-xs font-bold text-indigo-400 uppercase mb-2 tracking-widest">Lien d'affiliation principal</label>
                                        <div class="flex gap-2">
                                            <InputText readonly value="https://subnetcongo.cd/ref/aff-28492" class="flex-1 bg-white border-none font-medium text-indigo-700" />
                                            <Button icon="pi pi-copy" class="p-button-indigo" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="p-4 border border-slate-200 rounded-2xl flex items-center justify-between">
                                            <span class="text-sm font-bold">Bannières (V.2025)</span>
                                            <Button icon="pi pi-download" class="p-button-text p-button-sm" />
                                        </div>
                                        <div class="p-4 border border-slate-200 rounded-2xl flex items-center justify-between">
                                            <span class="text-sm font-bold">Email Templates</span>
                                            <Button icon="pi pi-download" class="p-button-text p-button-sm" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel header="Journal Actions">
                                <div class="space-y-4 pt-4 max-h-[300px] overflow-y-auto pr-2">
                                    <div v-for="log in [
                                        { date: 'Hier, 14:20', text: 'Changement de palier par Admin : Bronze -> Silver' },
                                        { date: '01 Fév, 09:12', text: 'Paiement de 450.00 $ traité avec succès' },
                                        { date: '28 Jan, 16:45', text: 'Nouveau record : 10 ventes en 24h' }
                                    ]" :key="log.date" class="flex gap-4 items-start">
                                        <div class="w-2 h-2 rounded-full bg-slate-300 mt-1.5"></div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">{{ log.date }}</p>
                                            <p class="text-sm text-slate-700 font-medium">{{ log.text }}</p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between w-full border-t border-slate-100 pt-4">
                        <Button label="Suspendre Partenariat" icon="pi pi-ban" class="p-button-danger p-button-text font-bold" />
                        <div class="flex gap-2">
                            <Button label="Envoyer un message" icon="pi pi-envelope" class="p-button-text p-button-secondary" />
                            <Button label="Enregistrer modifications" icon="pi pi-save" class="p-button-indigo px-8" @click="displayDetailsDialog = false" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- MODALE: PARAMETRES PALIERS -->
            <Dialog v-model:visible="displaySettingsDialog" :style="{ width: '500px' }" header="Configuration des Tiers" :modal="true">
                <div class="space-y-6 pt-4">
                    <InlineMessage severity="info" class="w-full">Les taux définis ici s'appliquent sur le montant HT des formations vendues.</InlineMessage>
                    <div v-for="t in tiers" :key="t.value" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: t.color }"></div>
                                <span class="font-black text-slate-800 uppercase text-sm tracking-widest">{{ t.label.split(' ')[0] }}</span>
                            </div>
                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Taux (%)</label>
                                <InputNumber v-model="t.rate" suffix="%" class="w-full" />
                            </div>
                            <div class="field">
                                <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">Ventes requises</label>
                                <InputNumber v-model="t.minSales" placeholder="ex: 50" class="w-full" />
                            </div>
                        </div>
                    </div>
                    <Button label="Ajouter un nouveau palier" icon="pi pi-plus" class="p-button-outlined p-button-indigo w-full border-2 border-dashed" />
                </div>
                <template #footer>
                    <Button label="Fermer" class="p-button-text" @click="displaySettingsDialog = false" />
                    <Button label="Appliquer les changements" icon="pi pi-check" class="p-button-indigo" />
                </template>
            </Dialog>

            <footer class="mt-12 text-center text-slate-400 text-sm pb-8 font-medium">
                © 2026 SubNetCongo - Système LMS & Affiliation Intégré.
            </footer>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom PrimeVue styles matching capture design */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-affiliate-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    padding: 2rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    padding: 1.5rem 1rem;
    text-transform: uppercase;
}

:deep(.p-datatable-tbody > tr) {
    transition: all 0.2s ease;
}

:deep(.p-datatable-tbody > tr:hover) {
    background-color: #f1f5f9/30 !important;
}

:deep(.p-paginator) {
    border: none;
    padding: 2rem;
    background: transparent;
}

:deep(.p-tabview-nav) {
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    border: none !important;
    background: transparent !important;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
}

/* Modal styles */
:deep(.p-dialog-header) {
    background: white;
    padding: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog-content) {
    padding: 0;
}

.p-button-indigo {
    background: #6366f1;
    border: none;
}

.p-button-indigo:hover {
    background: #4f46e5;
}

/* Table Animation */
.p-datatable-row-expansion-enter-active {
    transition: all 0.3s ease-out;
}
</style>