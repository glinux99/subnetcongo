<script setup>
/**
 * ADVANCED ANALYTICS ENGINE - SUBNETCONGO LMS
 * Version: 3.5.0
 * Documentation: Ce module gère l'agrégation de données massives pour le pilotage stratégique.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE COMPONENTS ---
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import SelectButton from 'primevue/selectbutton';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Sidebar from 'primevue/sidebar';
import Dialog from 'primevue/dialog';
import Menu from 'primevue/menu';
import OverlayPanel from 'primevue/overlaypanel';
import Skeleton from 'primevue/skeleton';
import Knob from 'primevue/knob';
import ScrollPanel from 'primevue/scrollpanel';
import Breadcrumb from 'primevue/breadcrumb';
import InputSwitch from 'primevue/inputswitch';
import Slider from 'primevue/slider';

const toast = useToast();
const loading = ref(true);
const exportMenu = ref();
const dateRange = ref([new Date(new Date().setDate(new Date().getDate() - 30)), new Date()]);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([{ label: 'Reporting' }, { label: 'Analytique Avancée', class: 'font-bold text-blue-600' }]);

// --- OPTIONS DE FILTRES ---
const categories = ref([
    { name: 'Développement Web', code: 'web' },
    { name: 'Data Science', code: 'data' },
    { name: 'Design UI/UX', code: 'design' },
    { name: 'Business & Marketing', code: 'biz' }
]);

const selectedCategories = ref([]);
const reportType = ref('revenue');

// --- DATA: MOCK DATA GENERATION (Massive Block) ---
// Simulation d'une réponse API complexe pour peupler les graphiques

const financialData = reactive({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
    datasets: [
        {
            label: 'Revenus 2025 ($)',
            data: [12500, 15800, 14200, 19500, 22000, 21000, 24500, 28900, 31000, 29500, 35000, 42000],
            fill: true,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4
        },
        {
            label: 'Objectif ($)',
            data: [10000, 12000, 15000, 18000, 20000, 22000, 25000, 28000, 30000, 32000, 35000, 40000],
            borderColor: '#94A3B8',
            borderDash: [5, 5],
            fill: false,
            tension: 0
        }
    ]
});

const engagementData = reactive({
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
        {
            label: 'Apprenants Actifs',
            data: [450, 580, 620, 590, 410, 220, 180],
            backgroundColor: '#818CF8'
        }
    ]
});

const categoryPerformance = reactive({
    labels: ['Développement', 'Design', 'Marketing', 'Soft Skills', 'Langues', 'Gestion'],
    datasets: [
        {
            label: 'Taux de Complétion',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            borderColor: '#10B981',
            pointBackgroundColor: '#10B981',
            data: [85, 72, 60, 90, 55, 78]
        },
        {
            label: 'Volume d\'inscriptions',
            backgroundColor: 'rgba(99, 102, 241, 0.2)',
            borderColor: '#6366F1',
            pointBackgroundColor: '#6366F1',
            data: [95, 65, 80, 45, 70, 60]
        }
    ]
});

const courseLeaderboard = ref([
    { id: 1, title: 'Maîtriser Laravel 11', students: 1250, revenue: 15400, growth: 12.5, rating: 4.9, status: 'Top Seller' },
    { id: 2, title: 'UI/UX Design Masterclass', students: 980, revenue: 12200, growth: -2.1, rating: 4.7, status: 'Stable' },
    { id: 3, title: 'Data Analysis avec Python', students: 850, revenue: 10500, growth: 25.4, rating: 4.8, status: 'Trending' },
    { id: 4, title: 'English for Business', students: 720, revenue: 6500, growth: 5.2, rating: 4.5, status: 'Normal' },
    { id: 5, title: 'Vendre sur WhatsApp en RDC', students: 2400, revenue: 4500, growth: 45.8, rating: 4.9, status: 'Viral' }
]);

const demographicData = ref({
    labels: ['Kinshasa', 'Lubumbashi', 'Goma', 'Matadi', 'Bukavu', 'Autres'],
    datasets: [{
        data: [45, 20, 12, 8, 5, 10],
        backgroundColor: ['#6366F1', '#818CF8', '#A5B4FC', '#C7D2FE', '#E0E7FF', '#F1F5F9']
    }]
});

// --- CHART OPTIONS CONFIGURATION (Detailed) ---
const lineChartOptions = computed(() => ({
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'bold' } } },
        tooltip: {
            padding: 15,
            backgroundColor: '#1E293B',
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            cornerRadius: 12
        }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#64748B' } },
        y: {
            grid: { borderDash: [5, 5], color: '#E2E8F0' },
            ticks: { callback: (value) => '$' + value, color: '#64748B' }
        }
    }
}));

const radarOptions = {
    maintainAspectRatio: false,
    scales: { r: { angleLines: { display: true }, suggestMin: 0, suggestMax: 100 } },
    plugins: { legend: { position: 'bottom' } }
};

// --- LOGIQUE METIER ---
const runGlobalAudit = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        toast.add({ severity: 'success', summary: 'Rapport Actualisé', detail: 'Données synchronisées avec le serveur de Kinshasa.', life: 3000 });
    }, 1500);
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Viral': return 'danger';
        case 'Trending': return 'success';
        case 'Top Seller': return 'info';
        case 'Stable': return 'warning';
        default: return 'secondary';
    }
};

const exportData = (type) => {
    toast.add({ severity: 'info', summary: 'Génération du fichier', detail: `Le rapport ${type.toUpperCase()} est en préparation...`, life: 2000 });
};

const exportItems = [
    { label: 'Excel (.xlsx)', icon: 'pi pi-file-excel', command: () => exportData('excel') },
    { label: 'PDF Report', icon: 'pi pi-file-pdf', command: () => exportData('pdf') },
    { label: 'JSON Data', icon: 'pi pi-code', command: () => exportData('json') }
];

onMounted(() => {
    runGlobalAudit();
});
</script>

<template>
    <AppLayout>
        <Head title="Analytique Stratégique | SubnetCongo" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- --- HEADER: COMMAND CENTER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-600 p-5 rounded-[1rem] shadow-2xl shadow-indigo-100 relative group">
                            <i class="pi pi-chart-bar text-white text-3xl"></i>
                            <div class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500"></span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">LMS Business Intelligence</h1>
                            <p class="text-slate-500 font-medium">Pilotage de la performance éducative en temps réel.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 p-3 bg-white rounded-[1rem] shadow-sm border border-slate-100">
                    <Calendar v-model="dateRange" selectionMode="range" :showIcon="true" class="w-64" placeholder="Période d'analyse" />
                    <Divider layout="vertical" />
                    <Button label="Exporter" icon="pi pi-upload" class="p-button-outlined p-button-secondary rounded-xl" @click="exportMenu.toggle($event)" />
                    <Menu ref="exportMenu" :model="exportItems" :popup="true" />
                    <Button label="Rafraîchir" icon="pi pi-sync" class="p-button-primary shadow-xl shadow-indigo-200 px-6 rounded-xl font-bold" @click="runGlobalAudit" :loading="loading" />
                </div>
            </header>

            <!-- --- SECTION 1: GLOBAL KPIs (SKELETON READY) --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div v-for="kpi in [
                    { label: 'Revenu Mensuel (MRR)', val: '$42,000', trend: '+18.5%', color: 'indigo', icon: 'pi-dollar' },
                    { label: 'Apprenants Actifs', val: '8,420', trend: '+5.2%', color: 'emerald', icon: 'pi-users' },
                    { label: 'Taux de Complétion', val: '74.8%', trend: '+2.1%', color: 'amber', icon: 'pi-verified' },
                    { label: 'Nouveaux Inscrits', val: '1,250', trend: '+22.4%', color: 'rose', icon: 'pi-user-plus' }
                ]" :key="kpi.label" class="bg-white p-8 rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-500 group relative overflow-hidden">
                    <div v-if="loading"><Skeleton width="100%" height="100px" /></div>
                    <div v-else>
                        <div class="flex justify-between items-start mb-6">
                            <div :class="`p-4 rounded-xl bg-${kpi.color}-50 text-${kpi.color}-600 group-hover:scale-110 transition-transform` ">
                                <i :class="`pi ${kpi.icon} text-xl` "></i>
                            </div>
                            <Badge :value="kpi.trend" :severity="kpi.trend.startsWith('+') ? 'success' : 'danger'" />
                        </div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</h3>
                        <p class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ kpi.val }}</p>
                        <div class="mt-4 flex items-center gap-2">
                            <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                                <div :class="`bg-${kpi.color}-500 h-full w-[70%]` "></div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 italic">vs mois dernier</span>
                        </div>
                    </div>
                    <!-- Background Decoration -->
                    <div :class="`absolute -right-4 -bottom-4 text-8xl text-${kpi.color}-50 opacity-0 group-hover:opacity-100 transition-opacity` ">
                        <i :class="`pi ${kpi.icon}` "></i>
                    </div>
                </div>
            </div>

            <!-- --- SECTION 2: MAIN FINANCIAL & ENGAGEMENT CHARTS --- -->
            <div class="grid grid-cols-12 gap-8 mb-12">

                <!-- Financial Trend -->
                <div class="col-span-12 lg:col-span-8">
                    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 h-[500px] flex flex-col">
                        <div class="flex justify-between items-start mb-10">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Analyse de Croissance Financière</h2>
                                <p class="text-sm text-slate-400 font-medium">Comparaison des revenus réels face aux objectifs fixés.</p>
                            </div>
                            <SelectButton v-model="reportType" :options="['revenue', 'profit', 'enrollments']" class="text-xs" />
                        </div>
                        <div class="flex-1">
                            <Chart type="line" :data="financialData" :options="lineChartOptions" class="h-full" />
                        </div>
                    </div>
                </div>

                <!-- Demographic / Geographic Distribution -->
                <div class="col-span-12 lg:col-span-4">
                    <div class="bg-slate-900 rounded-[3rem] p-10 text-white shadow-2xl h-[500px] flex flex-col relative overflow-hidden">
                        <div class="relative z-10 flex flex-col h-full">
                            <h2 class="text-2xl font-black italic mb-2 tracking-tight">Répartition Géographique</h2>
                            <p class="text-slate-400 text-xs mb-8 uppercase font-bold tracking-widest">Top Provinces (RDC)</p>

                            <div class="flex-1 flex flex-col justify-center items-center">
                                <Chart type="doughnut" :data="demographicData" :options="{ cutout: '70%', plugins: { legend: { display: false } } }" class="w-full max-w-[200px]" />
                                <div class="mt-8 grid grid-cols-2 gap-4 w-full">
                                    <div v-for="(prov, idx) in ['Kinshasa', 'L’shi', 'Goma', 'Autres']" :key="idx" class="bg-white/5 p-3 rounded-xl border border-white/10">
                                        <p class="text-[10px] font-black text-slate-500 uppercase">{{ prov }}</p>
                                        <p class="text-lg font-bold">{{ [45, 20, 12, 23][idx] }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <i class="pi pi-map-marker absolute -right-6 -bottom-6 text-9xl text-white/5"></i>
                    </div>
                </div>
            </div>

            <!-- --- SECTION 3: COURSE PERFORMANCE & MATRICES --- -->
            <div class="grid grid-cols-12 gap-8 mb-12">

                <!-- Radar: Success Matrix -->
                <div class="col-span-12 lg:col-span-4 bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 h-[500px]">
                    <h2 class="text-xl font-black text-slate-800 mb-2">Matrice de Succès</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase mb-8">Performance par Catégorie</p>
                    <div class="h-[320px]">
                        <Chart type="radar" :data="categoryPerformance" :options="radarOptions" class="h-full" />
                    </div>
                </div>

                <!-- Table: Top Performing Courses -->
                <div class="col-span-12 lg:col-span-8 bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 h-[500px]">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-xl font-black text-slate-800">Top 5 des Formations</h2>
                            <p class="text-xs text-slate-400">Basé sur les revenus et la satisfaction étudiant.</p>
                        </div>
                        <Button label="Voir tout" class="p-button-text p-button-sm font-bold" />
                    </div>

                    <DataTable :value="courseLeaderboard" class="p-datatable-sm">
                        <Column field="title" header="Titre du Cours">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                    <span class="font-bold text-slate-700">{{ data.title }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="students" header="Élèves" sortable>
                            <template #body="{ data }">
                                <span class="font-mono font-bold">{{ data.students.toLocaleString() }}</span>
                            </template>
                        </Column>
                        <Column field="revenue" header="CA ($)" sortable>
                            <template #body="{ data }">
                                <span class="text-slate-900 font-black">${{ data.revenue.toLocaleString() }}</span>
                            </template>
                        </Column>
                        <Column field="growth" header="Tendance">
                            <template #body="{ data }">
                                <span :class="data.growth >= 0 ? 'text-red-500' : 'text-rose-500'" class="font-bold">
                                    <i :class="data.growth >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-[10px] mr-1"></i>
                                    {{ Math.abs(data.growth) }}%
                                </span>
                            </template>
                        </Column>
                        <Column field="status" header="Badge">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] uppercase font-black" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <!-- --- SECTION 4: ADVANCED DATA EXPLORER (TABBED) --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <TabView class="custom-analytics-tabs">

                    <!-- TAB: SATISFACTION ÉTUDIANTS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2 py-2 px-4">
                                <i class="pi pi-heart-fill text-rose-400"></i>
                                <span class="font-bold text-sm">Satisfaction & NPS</span>
                            </div>
                        </template>

                        <div class="p-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
                            <div class="lg:col-span-4 flex flex-col items-center justify-center text-center p-8 bg-slate-50 rounded-[1rem] border border-slate-100">
                                <h4 class="text-slate-400 font-black uppercase text-xs tracking-[0.2em] mb-6">Net Promoter Score</h4>
                                <Knob v-model="value" :readonly="true" :size="150" valueTemplate="{value}%" valueColor="#6366F1" rangeColor="#E2E8F0" />
                                <p class="mt-6 text-sm text-slate-600 font-medium">92% des apprenants de SubnetCongo recommanderaient la plateforme à leurs proches.</p>
                                <Divider />
                                <div class="flex gap-4">
                                    <div class="text-center">
                                        <p class="text-2xl font-black text-red-500">85</p>
                                        <p class="text-[9px] uppercase font-bold text-slate-400">Promoteurs</p>
                                    </div>
                                    <div class="text-center border-x border-slate-200 px-4">
                                        <p class="text-2xl font-black text-amber-500">12</p>
                                        <p class="text-[9px] uppercase font-bold text-slate-400">Passifs</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-black text-rose-500">3</p>
                                        <p class="text-[9px] uppercase font-bold text-slate-400">Détracteurs</p>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-8">
                                <h3 class="text-xl font-black text-slate-800 mb-6">Engagement Hebdomadaire (Heatmap)</h3>
                                <div class="h-[300px]">
                                    <Chart type="bar" :data="engagementData" :options="lineChartOptions" class="h-full" />
                                </div>
                                <div class="mt-8 flex gap-4">
                                    <InlineMessage severity="info" class="flex-1">Pointe d'activité détectée le Mercredi entre 18h et 21h.</InlineMessage>
                                    <InlineMessage severity="warn" class="flex-1">Baisse d'engagement le Dimanche après-midi.</InlineMessage>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB: ANALYSE DES EXAMENS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2 py-2 px-4">
                                <i class="pi pi-verified text-indigo-400"></i>
                                <span class="font-bold text-sm">Qualité Académique</span>
                            </div>
                        </template>
                        <div class="p-10">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="p-6 bg-indigo-50 rounded-xl border border-indigo-100">
                                    <p class="text-xs font-bold text-indigo-400 uppercase mb-2">Taux de Réussite global</p>
                                    <h4 class="text-3xl font-black text-indigo-900">88.4%</h4>
                                </div>
                                <div class="p-6 bg-emerald-50 rounded-xl border border-emerald-100">
                                    <p class="text-xs font-bold text-emerald-400 uppercase mb-2">Diplômes émis (30j)</p>
                                    <h4 class="text-3xl font-black text-emerald-900">142</h4>
                                </div>
                                <div class="p-6 bg-amber-50 rounded-xl border border-amber-100">
                                    <p class="text-xs font-bold text-amber-400 uppercase mb-2">Temps de correction moy.</p>
                                    <h4 class="text-3xl font-black text-amber-900">4.2h</h4>
                                </div>
                            </div>
                            <div class="bg-slate-50 p-8 rounded-[1rem] border border-slate-100">
                                <h4 class="font-bold text-slate-800 mb-4">Distribution des scores (Examens finaux)</h4>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-bold text-slate-400 w-20">0-50%</span>
                                    <ProgressBar :value="5" class="flex-1" :showValue="false" />
                                </div>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="text-xs font-bold text-slate-400 w-20">50-75%</span>
                                    <ProgressBar :value="25" class="flex-1" :showValue="false" />
                                </div>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="text-xs font-bold text-slate-400 w-20">75-90%</span>
                                    <ProgressBar :value="55" class="flex-1" :showValue="false" />
                                </div>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="text-xs font-bold text-slate-400 w-20">90-100%</span>
                                    <ProgressBar :value="15" class="flex-1" :showValue="false" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>

            <!-- --- SECTION 5: REAL-TIME FEED & NOTIFICATIONS --- -->
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 lg:col-span-4 bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
                    <h2 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                        <i class="pi pi-history text-indigo-500"></i>
                        Flux d'activité
                    </h2>
                    <ScrollPanel style="height: 350px">
                        <div class="space-y-6">
                            <div v-for="i in 6" :key="i" class="flex gap-4 p-4 hover:bg-slate-50 rounded-xl transition-all cursor-pointer group">
                                <Avatar image="https://i.pravatar.cc/150?u=a" shape="circle" class="ring-2 ring-indigo-50 group-hover:ring-indigo-200 transition-all" />
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Jean-Pierre K. <span class="font-normal text-slate-500">s'est inscrit à</span> UX Design</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Il y a 2 minutes • Kinshasa</p>
                                </div>
                            </div>
                        </div>
                    </ScrollPanel>
                </div>

                <div class="col-span-12 lg:col-span-8 bg-indigo-900 rounded-[3rem] p-10 text-white flex flex-col md:flex-row items-center gap-10 shadow-2xl relative overflow-hidden">
                    <div class="absolute -left-20 -top-20 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10 space-y-4 max-w-lg">
                        <h2 class="text-3xl font-black italic tracking-tight">Besoin d'un rapport personnalisé ?</h2>
                        <p class="text-indigo-200 text-sm leading-relaxed font-medium">L'outil d'exportation avancée vous permet de générer des rapports PDF complets incluant les revenus, le churn et les performances par province pour présentation en conseil d'administration.</p>
                        <div class="flex gap-4 pt-4">
                            <Button label="Générer Rapport Annuel" icon="pi pi-file-pdf" class="p-button-white p-button-raised rounded-xl px-6" />
                            <Button label="Assistance Data" icon="pi pi-question-circle" class="p-button-text p-button-plain text-white font-bold" />
                        </div>
                    </div>
                    <div class="relative z-10 flex-1 hidden md:flex items-center justify-center">
                        <div class="w-48 h-48 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                            <i class="pi pi-database text-6xl text-indigo-300"></i>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </AppLayout>
</template>

<style scoped>
/* --- ANALYTICS CUSTOM STYLING --- */

/* Tabs Overrides */
:deep(.custom-analytics-tabs .p-tabview-nav) {
    background: #FDFDFF;
    border-bottom: 2px solid #F1F5F9;
    padding: 0 2rem;
}

:deep(.custom-analytics-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    color: #94A3B8;
    padding: 2rem 1.5rem;
    transition: all 0.3s;
}

:deep(.custom-analytics-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1 !important;
    box-shadow: inset 0 -3px 0 #6366F1 !important;
}

:deep(.custom-analytics-tabs .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* DataTable Overrides */
:deep(.p-datatable-sm .p-datatable-thead > tr > th) {
    background: #F8FAFC;
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #64748B;
    padding: 1rem;
    letter-spacing: 0.1em;
}

:deep(.p-datatable-sm .p-datatable-tbody > tr > td) {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #F1F5F9;
}

/* Knob / ProgressBar */
:deep(.p-knob-text) {
    font-size: 1.5rem;
    font-weight: 900;
    fill: #1E293B;
}

:deep(.p-progressbar) {
    background: #F1F5F9;
    border-radius: 10px;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Global Transitions */
.p-button {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.p-button:active {
    transform: scale(0.95);
}

/* Custom Scrollbar for Real-time feed */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #E2E8F0;
    border-radius: 10px;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
