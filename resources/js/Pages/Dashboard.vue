<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXECUTIVE ANALYTICS DASHBOARD v13.5.0 (Enterprise Gold Edition)
 * ======================================================================================
 *
 * Ce composant est l'interface de pilotage stratégique de SubnetCongo.
 * Connecté au DashboardController.php (Engine v13.5), il traite les flux de données
 * financiers, académiques et d'infrastructure en temps réel.
 *
 * ARCHITECTURE FRONTEND :
 * - Composition API (Vue 3) + Script Setup
 * - Data Transformation Layer (pour adapter les props Chart.js)
 * - Infrastructure Polling System (Synchronisation asynchrone DB/Serveur)
 * - Glassmorphism Enterprise UI Design
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, Transition } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import debounce from 'lodash/debounce';

// --- PRIMEVUE SERVICES & CORE ---
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- COMPOSANTS PRIMEVUE (ENTERPRISE STACK) ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import ProgressBar from 'primevue/progressbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Badge from 'primevue/badge';
import Knob from 'primevue/knob';
import InputText from 'primevue/inputtext';
import ScrollPanel from 'primevue/scrollpanel';
import Timeline from 'primevue/timeline';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import SelectButton from 'primevue/selectbutton';
import Breadcrumb from 'primevue/breadcrumb';
import Skeleton from 'primevue/skeleton';
import Sidebar from 'primevue/sidebar';
import MeterGroup from 'primevue/metergroup';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import OverlayBadge from 'primevue/overlaybadge';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputSwitch from 'primevue/inputswitch';

// ======================================================================================
// 1. DÉFINITION DES PROPS (SYNCHRONISÉES AVEC LE CONTROLLER)
// ======================================================================================
const props = defineProps({
    meta: Object,
    availableFilters: Object,
    kpis: Object,
    chartData: Object,
    cohorts: Object, // Paginated Object
    leaderboard: Array,
    activities: Array,
    supportStatus: Object,
    infra: Object
});

// ======================================================================================
// 2. ÉTAT RÉACTIF (STATE MANAGEMENT)
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const isLoading = ref(true);
const isExporting = ref(false);
const showExportModal = ref(false);
const showFilterSidebar = ref(false);
const expandedRows = ref([]);

// État des filtres
const activeFilters = reactive({
    year: props.availableFilters.years[0],
    month: props.availableFilters.months[nowMonth()],
    category: 'all',
    search: '',
});

function nowMonth() { return new Date().getMonth() + 1; }

// ======================================================================================
// 3. SYSTÈME DE MONITORING INFRASTRUCTURE (REAL-TIME)
// ======================================================================================
const infraState = reactive({
    cpu: props.infra.cpu,
    ram: props.infra.ram,
    disk: props.infra.disk,
    latency: props.infra.db_latency,
    status: props.infra.status,
    platform: props.infra.platform
});

let infraPollingInterval = null;

const startInfraPolling = () => {
    // Polling intelligent : rafraîchit uniquement les données infra toutes les 30s
    infraPollingInterval = setInterval(() => {
        router.reload({
            only: ['infra', 'meta'],
            onSuccess: (page) => {
                infraState.cpu = page.props.infra.cpu;
                infraState.ram = page.props.infra.ram;
                infraState.disk = page.props.infra.disk;
                infraState.latency = page.props.infra.db_latency;
                infraState.status = page.props.infra.status;
            }
        });
    }, 30000);
};

// ======================================================================================
// 4. CONFIGURATION DES GRAPHIQUES (TRANSFORMATION DATA)
// ======================================================================================

// Options globales Chart.js
const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#475569', font: { family: 'Inter', weight: '600' } } },
        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
        y: { grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { color: '#94a3b8' } }
    }
}));

const radarOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        r: {
            grid: { color: '#e2e8f0' },
            pointLabels: { font: { size: 10, weight: 'bold' }, color: '#475569' }
        }
    }
}));

// ======================================================================================
// 5. MÉTHODES D'ACTION & LOGIQUE MÉTIER
// ======================================================================================

const handleExport = (type) => {
    isExporting.value = true;
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'Génération du flux de données en cours...', life: 2000 });

    // Redirection vers le moteur d'exportation Chunked du Controller
    window.location.href = route('admin.analytics.export', { type: type });

    setTimeout(() => {
        isExporting.value = false;
        showExportModal.value = false;
    }, 3000);
};

const applyDeepFilters = debounce(() => {
    isLoading.value = true;
    router.get(route('admin.dashboard'), activeFilters, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isLoading.value = false;
            showFilterSidebar.value = false;
            toast.add({ severity: 'success', summary: 'Actualisé', detail: 'Tableau de bord mis à jour.', life: 3000 });
        }
    });
}, 500);

const purgeCache = () => {
    confirm.require({
        message: 'Voulez-vous forcer le recalcul de toute l\'intelligence analytique ?',
        header: 'Purge du Cache',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.post(route('admin.analytics.purge'), {}, {
                onSuccess: () => toast.add({ severity: 'warn', summary: 'Cache Purgé', detail: 'Les données sont maintenant fraîches.' })
            });
        }
    });
};

onMounted(() => {
    setTimeout(() => isLoading.value = false, 1000);
    startInfraPolling();
});

onBeforeUnmount(() => {
    if (infraPollingInterval) clearInterval(infraPollingInterval);
});

// Helpers formattage
const formatCurrency = (val) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(val);
const formatNumber = (val) => new Intl.NumberFormat('fr-FR').format(val);

</script>

<template>
    <AppLayout>
        <Head title="Executive Intelligence Command" />
        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-[#F8FAFC] pb-20">

            <!-- --- [SECTION 1] TOP COMMAND CENTER --- -->
            <div class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm backdrop-blur-md bg-white/80">
                <div class="max-w-[1800px] mx-auto px-6 py-4 flex flex-col lg:flex-row justify-between items-center gap-6">

                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-gradient-to-tr from-indigo-600 to-violet-600 rounded-xl flex items-center justify-center shadow-xl shadow-indigo-100 animate-pulse-slow">
                            <i class="pi pi-chart-bar text-white text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Gouvernance SNC</span>
                                <Badge :value="props.meta.version" severity="info" class="text-[9px] h-4" />
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight italic">{{ props.meta.greeting }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="activeFilters.search" @input="applyDeepFilters" placeholder="Rechercher une cohorte..." class="w-64 bg-slate-50 border-none rounded-xl font-bold" />
                        </IconField>

                        <div class="h-10 w-px bg-slate-200 mx-2"></div>

                        <Button icon="pi pi-filter" label="Filtres" class="p-button-outlined p-button-secondary rounded-xl font-black" @click="showFilterSidebar = true" />
                        <Button icon="pi pi-cloud-download" label="Intelligence Export" class="p-button-primary rounded-xl font-black shadow-lg shadow-indigo-100" @click="showExportModal = true" />
                        <Button icon="pi pi-refresh" class="p-button-secondary p-button-text rounded-xl" @click="purgeCache" v-tooltip.bottom="'Forcer le recalcul'" />
                    </div>
                </div>
            </div>

            <!-- --- [SECTION 2] MACRO KPI GRID --- -->
            <div class="max-w-[1800px] mx-auto px-6 mt-10">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">

                    <!-- KPI : REVENUS & PRÉDICTION -->
                    <div class="bg-slate-900 rounded-[1rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                        <i class="pi pi-wallet absolute -right-6 -bottom-6 text-9xl text-white/5 group-hover:rotate-12 transition-transform duration-700"></i>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-white/10 backdrop-blur-xl rounded-xl flex items-center justify-center border border-white/10">
                                    <i class="pi pi-dollar text-emerald-400 text-xl"></i>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Prédiction M+1</span>
                                    <p class="text-emerald-400 font-black italic">{{ formatCurrency(props.kpis.revenue.predicted) }}</p>
                                </div>
                            </div>
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Chiffre d'Affaires {{ props.availableFilters.years[0] }}</h4>
                            <h2 class="text-5xl font-black tracking-tighter mb-6 italic">{{ formatCurrency(props.kpis.revenue.total) }}</h2>

                            <div class="space-y-2">
                                <div class="flex justify-between text-[10px] font-black uppercase">
                                    <span>Progression Objectif</span>
                                    <span>{{ props.kpis.revenue.progress }}%</span>
                                </div>
                                <ProgressBar :value="props.kpis.revenue.progress" :showValue="false" class="h-1.5 bg-white/10 border-none" color="#10B981" />
                            </div>
                        </div>
                    </div>

                    <!-- KPI : ÉTUDIANTS & CHURN -->
                    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm relative group">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                                <i class="pi pi-users text-xl"></i>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Taux d'Attrition</span>
                                <p class="text-rose-500 font-black italic">{{ props.kpis.students.churn }}%</p>
                            </div>
                        </div>
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Communauté Active</h4>
                        <h2 class="text-5xl font-black text-slate-800 tracking-tighter mb-4 italic">{{ formatNumber(props.kpis.students.total) }}</h2>
                        <div class="flex items-center gap-2 text-emerald-500 font-bold text-sm">
                            <i class="pi pi-arrow-up-right"></i>
                            <span>+{{ props.kpis.students.new_month }} ce mois-ci</span>
                        </div>
                    </div>

                    <!-- KPI : RÉUSSITE ACADÉMIQUE -->
                    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm relative group">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center">
                                <i class="pi pi-graduation-cap text-xl"></i>
                            </div>
                            <Tag :value="props.kpis.academic.certificates_issued + ' Diplômes'" severity="warning" class="font-black text-[9px]" />
                        </div>
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Taux de Complétion</h4>
                        <h2 class="text-5xl font-black text-slate-800 tracking-tighter mb-4 italic">{{ props.kpis.academic.completion }}%</h2>
                        <div class="flex items-center gap-4">
                            <div class="flex -space-x-3">
                                <Avatar v-for="i in 4" :key="i" :image="`https://i.pravatar.cc/100?u=${i}`" shape="circle" class="border-2 border-white" />
                            </div>
                            <span class="text-xs font-bold text-slate-400 italic">Score moy. : {{ props.kpis.academic.avg_score }}/100</span>
                        </div>
                    </div>

                    <!-- KPI : INFRASTRUCTURE HEALTH -->
                    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm relative overflow-hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <i class="pi pi-server text-indigo-500"></i> System Health
                            </h3>
                            <div class="flex gap-1">
                                <div v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full" :class="infraState.cpu < 80 ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6 items-center">
                            <div class="flex justify-center">
                                <Knob v-model="infraState.cpu" :size="100" :strokeWidth="12" valueColor="#6366F1" rangeColor="#F1F5F9" readonly valueTemplate="{value}%" />
                            </div>
                            <div class="space-y-4">
                                <div class="text-center">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">Latence DB</p>
                                    <p class="text-lg font-black text-slate-800 italic">{{ infraState.latency }}ms</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">Plateforme</p>
                                    <Tag :value="infraState.platform" severity="secondary" class="text-[8px] uppercase font-black" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- --- [SECTION 3] ANALYTICAL CHARTS --- -->
            <div class="max-w-[1800px] mx-auto px-6 mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Evolution des Revenus (Gros Graphe) -->
                <div class="lg:col-span-2 bg-white rounded-[1rem] p-10 border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-10">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 italic">Trajectoire Financière SNC</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Comparatif Annuel N vs N-1</p>
                        </div>
                        <div class="flex gap-2">
                            <Button icon="pi pi-expand" class="p-button-text p-button-secondary rounded-xl" />
                        </div>
                    </div>
                    <div class="h-[450px]">
                        <Chart type="line" :data="props.chartData.revenue_evolution" :options="chartOptions" class="h-full w-full" />
                    </div>
                </div>

                <!-- Radar de Compétences -->
                <div class="bg-white rounded-[1rem] p-10 border border-slate-200 shadow-sm flex flex-col">
                    <div class="mb-10 text-center">
                        <h3 class="text-xl font-black text-slate-800 italic">Offre vs Demande</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Équilibre des compétences enseignées</p>
                    </div>
                    <div class="flex-1 min-h-[350px]">
                        <Chart type="radar" :data="props.chartData.skills_radar" :options="radarOptions" class="h-full w-full" />
                    </div>
                    <div class="mt-8 pt-8 border-t border-slate-100 grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-black text-rose-500 italic">42</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Cours Publics</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-emerald-500 italic">15.4K</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Inscriptions</p>
                        </div>
                    </div>
                </div>
            </div>

         <!-- --- [SECTION 4] OPERATIONS : TIMELINE & INFRA & LEADERBOARD --- -->
<div class="max-w-[1800px] mx-auto px-6 mt-12 grid grid-cols-1 lg:grid-cols-4 gap-8">

    <!-- Infrastructure Real-time Details -->
    <div class="bg-slate-900 rounded-[1rem] p-8 text-white shadow-xl flex flex-col justify-between">
        <div>
            <h3 class="text-lg font-black italic flex items-center gap-3 mb-8">
                <i class="pi pi-bolt text-amber-400"></i> Live Metrics
            </h3>
            <div class="space-y-8">
                <div>
                    <div class="flex justify-between text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                        <span>Mémoire RAM (Cache)</span>
                        <span>{{ infraState.ram }}%</span>
                    </div>
                    <ProgressBar :value="infraState.ram" :showValue="false" class="h-1.5 bg-white/10 border-none" color="#8B5CF6" />
                </div>
                <div>
                    <div class="flex justify-between text-xs font-black uppercase tracking-widest text-slate-400 mb-2">
                        <span>Stockage Stockage</span>
                        <span>{{ infraState.disk }}%</span>
                    </div>
                    <ProgressBar :value="infraState.disk" :showValue="false" class="h-1.5 bg-white/10 border-none" color="#EC4899" />
                </div>
            </div>
        </div>

        <div class="mt-12 bg-white/5 p-6 rounded-xl border border-white/10">
            <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-4">Temps d'activité (Uptime)</p>
            <p class="text-sm font-bold italic">{{ props.infra.uptime || 'Analysing...' }}</p>
            <div class="flex gap-2 mt-4">
                <div class="flex-1 h-1 bg-emerald-500 rounded-full"></div>
                <div class="flex-1 h-1 bg-emerald-500 rounded-full"></div>
                <div class="flex-1 h-1 bg-emerald-500/20 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Audit Trail (Timeline) -->
    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm">
        <h3 class="text-lg font-black italic text-slate-800 mb-8 flex justify-between items-center">
            Journal d'Audit
            <Badge :value="props.activities.length" severity="secondary" />
        </h3>
        <ScrollPanel style="height: 100px" class="custom-scroll pr-4">
            <Timeline :value="props.activities">
                <template #marker="slotProps">
                    <span class="flex w-8 h-8 items-center justify-center text-white rounded-full shadow-lg" :class="`bg-${slotProps.item.type === 'error' ? 'rose' : slotProps.item.type === 'success' ? 'emerald' : 'indigo'}-500`">
                        <i :class="`pi ${slotProps.item.type === 'error' ? 'pi-times' : 'pi-check'} text-[10px]`"></i>
                    </span>
                </template>
                <template #content="slotProps">
                    <div class="mb-8">
                        <h4 class="text-xs font-black text-slate-800 leading-tight mb-1">{{ slotProps.item.action }}</h4>
                        <p class="text-[10px] font-medium text-slate-400 italic mb-2">{{ slotProps.item.description }}</p>
                        <div class="flex items-center gap-2">
                            <span class="text-[8px] font-black uppercase tracking-widest text-indigo-500">{{ slotProps.item.user }}</span>
                            <span class="text-[8px] font-bold text-slate-300">• {{ slotProps.item.time }}</span>
                        </div>
                    </div>
                </template>
            </Timeline>
        </ScrollPanel>
    </div>

    <!-- Support Center (Tickets) -->
    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm flex flex-col">
        <h3 class="text-lg font-black italic text-slate-800 mb-8 flex items-center gap-3">
            <OverlayBadge :value="props.supportStatus.open_tickets" severity="danger">
                <i class="pi pi-comment text-indigo-500"></i>
            </OverlayBadge>
            Tickets Ouverts
        </h3>
        <div class="space-y-4 flex-1 overflow-y-auto">
            <div v-for="ticket in props.supportStatus.recent_tickets" :key="ticket.id" class="p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-300 transition-all cursor-pointer group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[9px] font-black text-slate-400 font-mono">#{{ ticket.id }}</span>
                    <Tag :value="ticket.priority" :severity="ticket.priority === 'High' ? 'danger' : 'info'" class="text-[8px] uppercase font-black" />
                </div>
                <p class="text-xs font-bold text-slate-700 leading-tight group-hover:text-indigo-600 mb-2">{{ ticket.subject }}</p>
                <Badge :value="ticket.status" severity="secondary" class="text-[8px] font-black" />
            </div>
        </div>
        <Button label="Gérer le Support" class="p-button-outlined p-button-secondary rounded-xl mt-6 font-black text-xs" />
    </div>

    <!-- Leaderboard (Gamification) - CORRIGÉ -->
    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm flex flex-col">
        <h3 class="text-lg font-black italic text-slate-800 mb-8">SNC Hall of Fame</h3>
        
        <!-- Ajout du conteneur avec max-height et overflow-y-auto -->
        <div class="overflow-y-auto max-h-[280px] pr-2 custom-scroll flex-1">
            <div class="space-y-6">
                <div v-for="student in props.leaderboard" :key="student.rank" class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-6 text-sm font-black italic" :class="student.rank <= 3 ? 'text-amber-500' : 'text-slate-300'">{{ student.rank }}</span>
                        <Avatar :image="student.avatar" shape="circle" size="large" />
                        <div>
                            <p class="text-xs font-black text-slate-800">{{ student.name }}</p>
                            <div class="flex items-center gap-2">
                                <i :class="`pi pi-arrow-${student.trend} text-[8px] text-${student.trend === 'up' ? 'emerald' : 'rose'}-500`"></i>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">SNC Credits</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-indigo-600 italic">{{ student.score }}</p>
                        <span class="text-[8px] font-bold text-slate-300 uppercase">XP</span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

            <!-- --- [SECTION 5] DATA MANAGEMENT TABLE --- -->
            <div class="max-w-[1800px] mx-auto px-6 mt-12 mb-20">
                <div class="bg-white rounded-[1rem] p-10 border border-slate-200 shadow-sm relative overflow-hidden">

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                        <div>
                            <h3 class="text-2xl font-black text-slate-800 italic">Moteur des Cohortes Académiques</h3>
                            <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1">Détails des performances par programme d'enseignement</p>
                        </div>
                        <div class="flex gap-3">
                            <Button icon="pi pi-plus" label="Nouvelle Cohorte" class="p-button-primary rounded-xl font-black" />
                            <Button icon="pi pi-file-excel" class="p-button-outlined p-button-success rounded-xl" v-tooltip.bottom="'Export Excel'" />
                        </div>
                    </div>

                    <DataTable :value="props.cohorts.data" dataKey="id" :rows="10"
                               class="p-datatable-lg custom-datatable" responsiveLayout="scroll"
                               v-model:expandedRows="expandedRows">

                        <Column expander style="width: 3rem" />

                        <Column field="id" header="REF" sortable>
                            <template #body="{data}">
                                <span class="font-mono text-[10px] font-black text-slate-400 uppercase tracking-widest">#{{ data.id }}</span>
                            </template>
                        </Column>

                        <Column field="title" header="PROGRAMME" sortable style="min-width: 25rem">
                            <template #body="{data}">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-800 italic leading-tight">{{ data.title }}</span>
                                    <div class="flex gap-2 mt-2">
                                        <Tag :value="data.category" severity="info" class="text-[8px] font-black uppercase px-2" />
                                        <span v-for="tag in data.tags.slice(0,2)" :key="tag" class="text-[8px] font-bold text-slate-400 uppercase tracking-widest border border-slate-100 px-2 rounded-md">{{ tag }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="instructor" header="INSTRUCTEUR" align="center">
                            <template #body="{data}">
                                <div class="flex flex-col items-center">
                                    <Avatar icon="pi pi-user" shape="circle" class="bg-slate-100 text-slate-500 mb-1" />
                                    <span class="text-[10px] font-bold text-slate-600">{{ data.instructor }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="students" header="APPRENANTS" sortable align="center">
                            <template #body="{data}">
                                <div class="px-4 py-1.5 bg-indigo-50 text-indigo-600 rounded-full font-black text-xs inline-block">
                                    {{ formatNumber(data.students) }}
                                </div>
                            </template>
                        </Column>

                        <Column field="revenue" header="CA GÉNÉRÉ" sortable align="right">
                            <template #body="{data}">
                                <span class="font-black text-emerald-600 italic">{{ formatCurrency(data.revenue) }}</span>
                            </template>
                        </Column>

                        <Column field="avg_progress" header="PROGRESSION MOY." style="min-width: 12rem">
                            <template #body="{data}">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <ProgressBar :value="data.avg_progress" :showValue="false" class="h-1.5" :color="data.avg_progress > 70 ? '#10B981' : '#6366F1'" />
                                    </div>
                                    <span class="text-[10px] font-black text-slate-500">{{ data.avg_progress }}%</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" align="center">
                            <template #body="{data}">
                                <Badge :value="data.status" :severity="data.status === 'published' ? 'success' : 'warn'" class="text-[9px] uppercase font-black tracking-widest" />
                            </template>
                        </Column>

                        <Column header="ACTION" align="center">
                            <template #body>
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" />
                            </template>
                        </Column>

                        <!-- Row Expansion for sub-modules analysis -->
                        <template #expansion="slotProps">
                            <div class="p-8 bg-slate-50 border-y border-slate-100 shadow-inner">
                                <h5 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-3">
                                    <i class="pi pi-list text-indigo-500"></i> Distribution de Validation par Module
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div v-for="mod in 4" :key="mod" class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                                        <div class="flex justify-between items-center mb-4">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Module {{ mod }}</span>
                                            <i class="pi pi-check-circle text-emerald-500"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800 mb-2 italic">Architecture Infrastructure CCNA</p>
                                        <ProgressBar :value="75 - (mod * 10)" :showValue="false" class="h-1" color="#6366F1" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </DataTable>

                    <!-- Simple Pagination -->
                    <div class="mt-8 flex justify-between items-center bg-slate-50 p-6 rounded-xl border border-slate-100">
                        <span class="text-xs font-bold text-slate-500 italic">Affichage de {{ props.cohorts.from }} à {{ props.cohorts.to }} sur {{ props.cohorts.total }} résultats</span>
                        <div class="flex gap-2">
                            <Button icon="pi pi-chevron-left" class="p-button-outlined p-button-secondary rounded-xl h-10 w-10" :disabled="!props.cohorts.prev_page_url" />
                            <Button icon="pi pi-chevron-right" class="p-button-outlined p-button-secondary rounded-xl h-10 w-10" :disabled="!props.cohorts.next_page_url" />
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- --- [SIDEBAR] DEEP FILTERS --- -->
        <Sidebar v-model:visible="showFilterSidebar" position="right" class="w-full md:w-[450px] p-sidebar-custom">
            <template #header>
                <div class="flex items-center gap-3">
                    <i class="pi pi-filter text-xl text-indigo-600"></i>
                    <h2 class="text-xl font-black italic text-slate-800">Intelligence Filters</h2>
                </div>
            </template>
            <div class="p-8 space-y-10 h-full overflow-y-auto">

                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Période Temporelle</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Année</span>
                            <Dropdown v-model="activeFilters.year" :options="props.availableFilters.years" class="w-full rounded-xl font-bold" />
                        </div>
                        <div class="space-y-2">
                            <span class="text-[9px] font-bold text-slate-400 uppercase">Mois</span>
                            <Dropdown v-model="activeFilters.month" :options="Object.keys(props.availableFilters.months)" optionLabel="name" class="w-full rounded-xl font-bold" />
                        </div>
                    </div>
                </div>

                <Divider />

                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Catégorie Académique</label>
                    <SelectButton v-model="activeFilters.category" :options="[{name: 'Tous', value: 'all'}, ...props.availableFilters.categories.map(c => ({name: c.name, value: c.id}))]" optionLabel="name" optionValue="value" class="custom-selectbutton" />
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Paramètres d'Affichage</label>
                    <div class="space-y-4 bg-slate-50 p-6 rounded-xl border border-slate-100">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-700 italic">Afficher les prédictions</span>
                            <InputSwitch :modelValue="true" />
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-700 italic">Données Anonymisées</span>
                            <InputSwitch :modelValue="false" />
                        </div>
                    </div>
                </div>

            </div>
            <div class="absolute bottom-0 left-0 w-full p-8 bg-white border-t border-slate-100 flex gap-4">
                <Button label="Réinitialiser" class="p-button-text p-button-secondary flex-1 font-black" @click="activeFilters.category = 'all'; applyDeepFilters()" />
                <Button label="Appliquer l'Analyse" class="p-button-primary flex-1 font-black rounded-xl shadow-xl shadow-indigo-100" @click="applyDeepFilters" />
            </div>
        </Sidebar>

        <!-- --- [DIALOG] MASSIVE EXPORT ENGINE --- -->
        <Dialog v-model:visible="showExportModal" modal header="SNC Massive Export Engine" :style="{ width: '550px' }" class="rounded-[1rem] overflow-hidden">
            <div class="p-8 space-y-8">
                <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 flex gap-4 items-start">
                    <i class="pi pi-info-circle text-indigo-500 mt-1"></i>
                    <p class="text-[11px] text-indigo-800 leading-relaxed font-bold italic">
                        L'exportation utilise un moteur asynchrone (Chunked Stream). Vous pouvez exporter des millions de lignes sans impact sur les performances du serveur.
                    </p>
                </div>

                <div class="space-y-4">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Type de Données</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="p-6 border border-slate-200 rounded-[1rem] flex items-center justify-between hover:border-emerald-500 hover:bg-emerald-50 transition-all cursor-pointer group" @click="handleExport('payments')">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-file-excel text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 italic">Journal des Paiements</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Toutes les transactions .CSV</p>
                                </div>
                            </div>
                            <i class="pi pi-chevron-right text-slate-300 group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        <div class="p-6 border border-slate-200 rounded-[1rem] flex items-center justify-between hover:border-indigo-500 hover:bg-indigo-50 transition-all cursor-pointer group" @click="handleExport('enrollments')">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-users text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 italic">Base Apprenants</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Inscriptions & Progression .CSV</p>
                                </div>
                            </div>
                            <i class="pi pi-chevron-right text-slate-300 group-hover:translate-x-2 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer v-if="isExporting">
                <div class="w-full flex flex-col items-center gap-4 p-8 bg-slate-50">
                    <ProgressBar mode="indeterminate" class="w-full h-1" />
                    <span class="text-xs font-black text-indigo-600 uppercase tracking-widest animate-pulse">Streaming des données en cours...</span>
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* --- ENTERPRISE ANIMATIONS --- */
@keyframes pulse-slow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.9; transform: scale(0.98); }
}
.animate-pulse-slow { animation: pulse-slow 4s infinite ease-in-out; }

/* --- CUSTOM DATATABLE STYLING --- */
:deep(.custom-datatable) {
    border: none !important;
}

:deep(.p-datatable-header) {
    background: transparent !important;
    border: none !important;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc !important;
    color: #64748b !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.1em !important;
    padding: 1rem !important;
    border: none !important;
}

:deep(.p-datatable-tbody > tr) {
    background: transparent !important;
    transition: all 0.3s ease;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9 !important;
}

:deep(.p-datatable-tbody > tr > td) {
    padding: 1rem !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

/* --- TABVIEW CUSTOM --- */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent !important;
    border: none !important;
    justify-content: center !important;
    gap: 2rem !important;
    padding: 1rem !important;
}

/* --- KNOB STYLING --- */
:deep(.p-knob-text) {
    font-weight: 900 !important;
    font-family: 'Inter', sans-serif !important;
    font-style: italic !important;
}

/* --- SIDEBAR & DIALOGS --- */
:deep(.p-sidebar-header) {
    padding: 2rem !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

/* --- UTILS --- */
.tracking-tighter { letter-spacing: -0.05em; }
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

/* --- SELECTBUTTON ENTERPRISE --- */
:deep(.custom-selectbutton .p-button) {
    background: white !important;
    border: 1px solid #e2e8f0 !important;
    font-weight: 800 !important;
    font-size: 11px !important;
    color: #64748b !important;
    border-radius: 1rem !important;
    margin: 0.25rem !important;
}

:deep(.custom-selectbutton .p-highlight) {
    background: #6366F1 !important;
    border-color: #6366F1 !important;
    color: white !important;
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3) !important;
}
</style>

<style scoped>
/* ==========================================================================
   ENTERPRISE STYLES & OVERRIDES (PrimeVue)
   ========================================================================== */

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }

/* Animation d'entrée globale */
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* TabView Customization (Subtle & Elegant) */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent; border: none; border-bottom: 1px solid #e2e8f0; padding: 0 1rem;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent; border: none; font-weight: 800; text-transform: uppercase;
    font-size: 0.75rem; color: #94a3b8; padding: 1.25rem 1rem; margin-right: 1rem;
    transition: all 0.2s ease;
}
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5; border-bottom: 3px solid #4f46e5;
}
:deep(.custom-tabview .p-tabview-panels) { padding: 0; }

/* DataTable Enterprise Edition */
:deep(.custom-datatable .p-datatable-header) { background: transparent; border: none; padding: 0; }
:deep(.custom-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc; color: #64748b; font-size: 0.65rem; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.1em; padding: 1rem;
    border-bottom: 2px solid #e2e8f0; border-top: 1px solid #e2e8f0;
}
:deep(.custom-datatable .p-datatable-tbody > tr) { transition: background-color 0.2s; }
:deep(.custom-datatable .p-datatable-tbody > tr:hover) { background-color: #f8fafc; }
:deep(.custom-datatable .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9; padding: 1rem;
}
:deep(.custom-datatable .p-paginator) { border: none; background: transparent; padding-top: 1rem; }

/* Timeline Customization */
:deep(.p-timeline-event-opposite) { display: none; } /* Enlever la colonne vide à gauche */
:deep(.p-timeline-event-content) { padding-left: 1rem; }

/* Custom Overlays & Modals */
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1rem; }
:deep(.custom-dialog .p-dialog-content) { padding: 0; }
:deep(.p-sidebar-custom) { border-left: 1px solid #e2e8f0; box-shadow: -10px 0 30px rgba(0,0,0,0.05); }

/* Utilities */
.inner-shadow { box-shadow: inset 0 2px 10px rgba(0,0,0,0.02); }

/* Transition pour le message de bienvenue */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
