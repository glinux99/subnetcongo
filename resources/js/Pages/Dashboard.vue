<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXECUTIVE ANALYTICS DASHBOARD v13.0.0 (Enterprise Edition)
 * ======================================================================================
 * Ce composant est l'interface décisionnelle ultime pour les administrateurs et directeurs.
 * Il orchestre la visualisation en temps réel des données pédagogiques, financières et techniques.
 *
 * NOUVEAUTÉS UI/UX :
 * - Chargement asynchrone avec Skeleton Screens (Skeleton Loaders).
 * - Datatable avancée avec Row Expansion (Détails des sous-modules).
 * - Graphiques dynamiques connectés aux filtres globaux.
 * - Panneaux latéraux (Sidebars) pour les détails et les filtres profonds.
 * - Architecture 100% Data-Driven (toutes les données proviennent des props de la BDD).
 *
 * @package     Education/Analytics
 * @version     13.0.0
 * @author      SubnetCongo Framework Division
 * @license     Proprietary - SubnetCongo
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, Transition } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

// --- PRIMEVUE SERVICES ---
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIMEVUE COMPONENTS (Massive Import for Enterprise UI) ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import ProgressBar from 'primevue/progressbar';
import Menu from 'primevue/menu';
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
import InlineMessage from 'primevue/inlinemessage';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Skeleton from 'primevue/skeleton';
import Sidebar from 'primevue/sidebar';
import MeterGroup from 'primevue/metergroup';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import InputSwitch from 'primevue/inputswitch';
import Checkbox from 'primevue/checkbox';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import OverlayBadge from 'primevue/overlaybadge';
import { usePage } from '@inertiajs/vue3';

// --- INITIALISATION DES SERVICES ---
const toast = useToast();
const confirm = useConfirm();

// ======================================================================================
// 1. PROPS (DATA-DRIVEN ARCHITECTURE) - Venant de Laravel/DB
// ======================================================================================
const props = defineProps({
    meta: {
        type: Object,
        default: () => ({ greeting: 'Chargement des données...', last_sync: new Date().toISOString(), version: 'v13.0.0' })
    },

    // Filtres disponibles depuis la DB
    availableFilters: {
        type: Object,
        default: () => ({
            years: [2026, 2025, 2024, 2023],
            categories: ['Développement', 'Design', 'Marketing', 'Data Science', 'Business'],
            regions: ['Kinshasa', 'Lubumbashi', 'Dakar', 'Abidjan', 'Paris']
        })
    },

    // KPI Globaux
    kpis: {
        type: Object,
        default: () => ({
            students: { current: 15420, previous: 14200, growth: 8.5 },
            courses: { current: 342, previous: 330, active_percentage: 92 },
            completion: { rate: 76.5, previous_rate: 78.2, drop: -1.7 },
            revenue: { current: 1250400, target: 1500000, currency: '$' },
            server_health: { cpu: 42, ram: 68, db_latency: 12, status: 'Optimal' }
        })
    },

    // Graphiques Data (Séries temporelles et catégorielles de la DB)
    chartData: {
        type: Object,
        default: () => ({
            sparklines: {
                logins: [120, 150, 140, 180, 220, 190, 250],
                sales: [15, 22, 18, 35, 40, 28, 45],
                certifications: [5, 12, 8, 15, 25, 20, 32]
            },
            yearlyEnrollments: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [
                    { label: '2026', data: [1200, 1350, 1100, 1600, 1900, 0, 0, 0, 0, 0, 0, 0] },
                    { label: '2025', data: [1000, 1150, 950, 1400, 1600, 1500, 1200, 1100, 1800, 2000, 2100, 1900] }
                ]
            },
            skillsGap: {
                labels: ['Développement', 'UI/UX', 'DevOps', 'Data Science', 'CyberSec', 'Soft Skills'],
                current_supply: [65, 59, 90, 81, 56, 55],
                market_demand: [28, 48, 40, 19, 96, 27]
            },
            regionalPerformance: {
                labels: ['Kinshasa', 'Dakar', 'Abidjan', 'Douala', 'Lagos'],
                data: [45, 25, 15, 10, 5]
            }
        })
    },

    // Datatable des Cohortes/Cours
    cohorts: {
        type: Array,
        default: () => [
            { id: 'C-1001', name: 'Mastering Laravel 11 Enterprise', category: 'Développement', instructor: 'Dr. Mukendi', students: 840, revenue: 125000, progress: 65, status: 'Active', created_at: '2026-01-10', sub_modules: [{ name: 'Routing Avancé', completed: 840 }, { name: 'Eloquent Deep Dive', completed: 420 }] },
            { id: 'C-1002', name: 'UI/UX Design Systems', category: 'Design', instructor: 'Sarah Koné', students: 1250, revenue: 85000, progress: 100, status: 'Completed', created_at: '2025-11-05', sub_modules: [{ name: 'Figma AutoLayout', completed: 1250 }, { name: 'Design Tokens', completed: 1250 }] },
            { id: 'C-1003', name: 'DevOps & Docker Swarm', category: 'DevOps', instructor: 'Alain M.', students: 320, revenue: 45000, progress: 42, status: 'In Review', created_at: '2026-03-15', sub_modules: [{ name: 'Conteneurisation', completed: 300 }, { name: 'CI/CD Pipelines', completed: 150 }] },
            { id: 'C-1004', name: 'Data Science with Python', category: 'Data Science', instructor: 'Prof. Ilunga', students: 2100, revenue: 315000, progress: 88, status: 'Active', created_at: '2026-02-01', sub_modules: [{ name: 'Pandas & Numpy', completed: 2000 }, { name: 'Machine Learning', completed: 1800 }] },
            { id: 'C-1005', name: 'CyberSecurity Fundamentals', category: 'CyberSec', instructor: 'Jean-Paul T.', students: 150, revenue: 15000, progress: 15, status: 'Draft', created_at: '2026-05-10', sub_modules: [{ name: 'Network Sec', completed: 0 }] }
        ]
    },

    // Leaderboard
    leaderboard: {
        type: Array,
        default: () => [
            { id: 1, name: 'Alice V.', avatar: 'https://i.pravatar.cc/150?u=1', score: 14500, rank: 1, country: 'CD' },
            { id: 2, name: 'Thomas L.', avatar: 'https://i.pravatar.cc/150?u=2', score: 13200, rank: 2, country: 'SN' },
            { id: 3, name: 'Sophie M.', avatar: 'https://i.pravatar.cc/150?u=3', score: 12850, rank: 3, country: 'CI' },
            { id: 4, name: 'Marc D.', avatar: 'https://i.pravatar.cc/150?u=4', score: 11900, rank: 4, country: 'CD' },
            { id: 5, name: 'Julie K.', avatar: 'https://i.pravatar.cc/150?u=5', score: 10500, rank: 5, country: 'CM' }
        ]
    },

    // Activités & Logs
    activities: {
        type: Array,
        default: () => [
            { id: 1, event: 'Nouvelle Inscription massive', details: '50 étudiants inscrits via API B2B', user: 'Système', time: 'Il y a 5 min', type: 'info', icon: 'pi-users' },
            { id: 2, event: 'Alerte Serveur DB', details: 'Latence au-delà de 50ms sur le cluster principal', user: 'AWS RDS', time: 'Il y a 14 min', type: 'danger', icon: 'pi-database' },
            { id: 3, event: 'Paiement Stripe Réussi', details: 'Lot de 125 abonnements validés', user: 'Finance Bot', time: 'Il y a 45 min', type: 'success', icon: 'pi-dollar' },
            { id: 4, event: 'Mise à jour Cours', details: 'Nouveau module publié par Dr. Mukendi', user: 'Instructeur', time: 'Il y a 2 heures', type: 'warning', icon: 'pi-file-edit' },
        ]
    },

    // Support Tickets (Pour la profondeur du Dashboard)
    supportTickets: {
        type: Array,
        default: () => [
            { id: 'TKT-901', subject: 'Problème accès vidéo chapitre 3', status: 'Open', priority: 'High', user: 'Alice V.' },
            { id: 'TKT-902', subject: 'Erreur facturation mois de Mai', status: 'In Progress', priority: 'Critical', user: 'Enterprise SA' },
            { id: 'TKT-903', subject: 'Demande de certificat PDF', status: 'Closed', priority: 'Low', user: 'Thomas L.' },
        ]
    }
});

// ======================================================================================
// 2. STATE MANAGEMENT & RÉACTIVITÉ
// ======================================================================================
const isLoading = ref(true);
const isExporting = ref(false);
const showExportModal = ref(false);
const showFilterSidebar = ref(false);
const showCourseDetailModal = ref(false);
const selectedCourseDetail = ref(null);
const dynamicMessages = ref([]);
const currentMessageIndex = ref(0);
let messageInterval = null;

const page = usePage();

// Filtres Actifs
const activeFilters = reactive({
    year: 2026,
    search: '',
    categories: [],
    dateRange: null
});

// Configuration du Menu
const menu = ref();
const expandedRows = ref([]);

// Datatable Filters (PrimeVue setup)
const dtFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    status: { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
});

// Données dynamiques des graphes (Transformées à partir des props)
const yearlyEnrollmentChartData = ref({});
const skillsRadarChartData = ref({});
const regionalDoughnutData = ref({});

// Live Server Mock State
const liveServerMetrics = reactive({
    cpu: props.kpis.server_health.cpu,
    ram: props.kpis.server_health.ram,
    requests: 1245
});
let liveInterval = null;

// ======================================================================================
// 3. COMPUTED PROPERTIES (BUSINESS LOGIC)
// ======================================================================================

const currentDynamicMessage = computed(() => {
    if (dynamicMessages.value.length === 0) return props.meta.greeting;
    return dynamicMessages.value[currentMessageIndex.value];
});

const kpiRevenueProgress = computed(() => {
    const p = (props.kpis.revenue.current / props.kpis.revenue.target) * 100;
    return p > 100 ? 100 : p.toFixed(1);
});

const getInfraStatusColor = computed(() => {
    if (liveServerMetrics.cpu > 85) return '#EF4444'; // Red
    if (liveServerMetrics.cpu > 60) return '#F59E0B'; // Yellow
    return '#10B981'; // Green
});

const getInfraStatusSeverity = computed(() => {
    if (liveServerMetrics.cpu > 85) return 'danger';
    if (liveServerMetrics.cpu > 60) return 'warn';
    return 'success';
});

// Configuration des compteurs MeterGroup pour le stockage
const storageMeters = ref([
    { label: 'Vidéos', color: '#6366F1', value: 45 },
    { label: 'Documents', color: '#10B981', value: 20 },
    { label: 'Base de Données', color: '#F59E0B', value: 15 },
    { label: 'Libre', color: '#E2E8F0', value: 20 }
]);

// ======================================================================================
// 4. CHART CONFIGURATIONS (PRIME VUE / CHART.JS)
// ======================================================================================
const globalChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#475569', font: { family: 'Inter', weight: '600' }, usePointStyle: true } },
        tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', titleFont: { size: 13, family: 'Inter' }, padding: 12, cornerRadius: 8 }
    }
};

const barChartOptions = {
    ...globalChartOptions,
    scales: {
        y: { grid: { color: '#F1F5F9', borderDash: [5, 5] }, ticks: { color: '#64748B' } },
        x: { grid: { display: false }, ticks: { color: '#64748B' } }
    }
};

const radarOptions = {
    ...globalChartOptions,
    scales: {
        r: { grid: { color: '#F1F5F9' }, angleLines: { color: '#E2E8F0' }, pointLabels: { color: '#475569', font: { weight: 'bold' } } }
    }
};

const sparklineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    scales: { x: { display: false }, y: { display: false } },
    elements: { line: { tension: 0.4, borderWidth: 3 }, point: { radius: 0 } }
};

// ======================================================================================
// 5. LIFECYCLE & METHODS
// ======================================================================================

onMounted(() => {
    // 1. Initialisation des Graphiques avec les données de la BDD
    initCharts();

    // 2. Simulation de la latence réseau (Skeleton Loading)
    setTimeout(() => {
        isLoading.value = false;
        toast.add({ severity: 'success', summary: 'Synchronisation', detail: 'Toutes les données sont à jour.', life: 3000 });
    }, 1500);

    // 3. Simulation WebSockets (Données serveur en direct)
    liveInterval = setInterval(() => {
        // Variation aléatoire pour CPU et RAM
        liveServerMetrics.cpu = Math.max(10, Math.min(100, liveServerMetrics.cpu + (Math.random() * 10 - 5)));
        liveServerMetrics.ram = Math.max(20, Math.min(100, liveServerMetrics.ram + (Math.random() * 6 - 3)));
        liveServerMetrics.requests += Math.floor(Math.random() * 5);
    }, 3000);
});

onBeforeUnmount(() => {
    if (liveInterval) clearInterval(liveInterval);
});

// Fonction d'initialisation des graphes
const initCharts = () => {
    yearlyEnrollmentChartData.value = {
        labels: props.chartData.yearlyEnrollments.labels,
        datasets: [
            {
                type: 'bar',
                label: 'Inscriptions ' + props.chartData.yearlyEnrollments.datasets[0].label,
                backgroundColor: '#6366F1',
                borderRadius: 6,
                data: props.chartData.yearlyEnrollments.datasets[0].data
            },
            {
                type: 'line',
                label: 'Tendance ' + props.chartData.yearlyEnrollments.datasets[1].label,
                borderColor: '#10B981',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                data: props.chartData.yearlyEnrollments.datasets[1].data
            }
        ]
    };

    skillsRadarChartData.value = {
        labels: props.chartData.skillsGap.labels,
        datasets: [
            {
                label: 'Compétences Enseignées',
                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                borderColor: '#6366F1',
                pointBackgroundColor: '#6366F1',
                data: props.chartData.skillsGap.current_supply
            },
            {
                label: 'Demande du Marché (B2B)',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: '#10B981',
                pointBackgroundColor: '#10B981',
                data: props.chartData.skillsGap.market_demand
            }
        ]
    };

    regionalDoughnutData.value = {
        labels: props.chartData.regionalPerformance.labels,
        datasets: [{
            data: props.chartData.regionalPerformance.data,
            backgroundColor: ['#6366F1', '#8B5CF6', '#EC4899', '#F43F5E', '#F59E0B'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    };
};

// --- LOGIQUE FILTRAGE ET RECHERCHE ---
const applyFilters = debounce(() => {
    isLoading.value = true;

    // Simulation appel API Inertia avec préservation du state
    // router.get(route('dashboard.analytics'), activeFilters, { preserveState: true });

    setTimeout(() => {
        isLoading.value = false;
        showFilterSidebar.value = false;
        toast.add({ severity: 'info', summary: 'Filtres appliqués', detail: 'Le tableau de bord a été recalculé.', life: 3000 });
    }, 800);
}, 600);

watch(() => activeFilters.year, () => { applyFilters(); });
watch(() => activeFilters.search, (newVal) => { dtFilters.value.global.value = newVal; });

// --- GESTION DES ACTIONS UI ---
const viewCourseDetails = (course) => {
    selectedCourseDetail.value = course;
    showCourseDetailModal.value = true;
};

const triggerExportAction = (format) => {
    isExporting.value = true;
    setTimeout(() => {
        isExporting.value = false;
        showExportModal.value = false;
        toast.add({ severity: 'success', summary: 'Rapport Généré', detail: `Le fichier ${format} a été téléchargé avec succès.`, life: 4000 });
        // window.open(`/api/export?format=${format}`, '_blank');
    }, 2500);
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Active': return 'success';
        case 'Completed': return 'info';
        case 'In Review': return 'warn';
        case 'Draft': return 'secondary';
        default: return 'primary';
    }
};

const getPrioritySeverity = (prio) => {
    if (prio === 'Critical') return 'danger';
    if (prio === 'High') return 'warn';
    if (prio === 'Low') return 'secondary';
    return 'info';
};

// Helper Sparkline
const getSparkData = (dataArray, color) => ({
    labels: dataArray.map((_, i) => i),
    datasets: [{ data: dataArray, borderColor: color, backgroundColor: color + '20', fill: true }]
});
</script>

<template>
    <AppLayout>
        <Head title="Executive Analytics - SubnetCongo" />

        <!-- Toast & Confirm global -->
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="min-h-screen bg-[#F8FAFC] pb-20 font-sans">

            <!-- ============================================================================== -->
            <!-- HEADER / COMMAND CENTER -->
            <!-- ============================================================================== -->
            <div class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm backdrop-blur-xl bg-white/80">
                <div class="max-w-[1600px] mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">

                    <!-- Branding & Titre -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                            <i class="pi pi-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <Breadcrumb :home="{ icon: 'pi pi-home' }" :model="[{ label: 'Gouvernance' }, { label: 'Analytics' }]" class="bg-transparent p-0 text-[10px] uppercase font-bold tracking-widest text-slate-400 mb-1" />
                            <Transition name="fade" mode="out-in">
                                <h1 :key="currentMessageIndex" class="text-2xl font-black text-slate-800 tracking-tight leading-none min-h-[32px]">
                                    {{ currentDynamicMessage }}
                                </h1>
                            </Transition>
                        </div>
                    </div>

                    <!-- Actions Rapides & Filtres Globaux -->
                    <div class="flex items-center gap-3">
                        <IconField iconPosition="left" class="hidden md:block">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="activeFilters.search" placeholder="Rechercher un cours, ID..." class="w-64 bg-slate-50 border-slate-200 rounded-xl text-sm font-medium" />
                        </IconField>

                        <SelectButton v-model="activeFilters.year" :options="props.availableFilters.years" class="hidden lg:flex p-selectbutton-sm font-bold" />

                        <Button icon="pi pi-filter" label="Filtres" class="p-button-outlined p-button-secondary rounded-xl bg-white font-bold" @click="showFilterSidebar = true" />
                        <Button icon="pi pi-cloud-download" label="Export" class="p-button-primary rounded-xl font-bold shadow-lg shadow-indigo-200" @click="showExportModal = true" />
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- CONTENU PRINCIPAL (MAIN DASHBOARD) -->
            <!-- ============================================================================== -->
            <div class="max-w-[1600px] mx-auto px-6 mt-8 space-y-8">

                <!-- HEADER METADATA (Last Sync) -->
                <div class="flex justify-between items-center">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <i class="pi pi-sync mr-1 animate-spin" style="animation-duration: 3s;"></i>
                        Dernière synchro : {{ props.meta.last_sync }}
                    </p>
                    <Tag :value="props.meta.version" severity="info" class="rounded-full text-[10px]" />
                </div>

                <!-- SECTION 1 : MACRO KPI CARDS (4 COLONNES) -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                    <!-- KPI 1 : Apprenants -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:shadow-xl transition-all">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-700 z-0"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center"><i class="pi pi-users text-xl"></i></div>
                                <Badge :value="`+${props.kpis.students.growth}%`" severity="success" class="font-black" />
                            </div>
                            <h4 class="text-sm font-black text-slate-500 uppercase tracking-widest mb-1">Total Inscrits</h4>
                            <template v-if="isLoading"><Skeleton width="60%" height="3rem" class="mb-2" /></template>
                            <template v-else>
                                <h2 class="text-4xl font-black text-slate-800 tracking-tighter">{{ props.kpis.students.current.toLocaleString() }}</h2>
                                <p class="text-xs text-slate-400 mt-2 font-medium">Contre {{ props.kpis.students.previous.toLocaleString() }} le mois dernier.</p>
                            </template>
                        </div>
                    </div>

                    <!-- KPI 2 : Revenus -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-6 border border-slate-700 shadow-xl relative overflow-hidden group text-white">
                        <i class="pi pi-wallet absolute right-4 bottom-4 text-6xl text-white/5 group-hover:rotate-12 group-hover:scale-110 transition-transform duration-500"></i>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-12 h-12 bg-white/10 text-emerald-400 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10"><i class="pi pi-dollar text-xl"></i></div>
                            </div>
                            <h4 class="text-sm font-black text-slate-300 uppercase tracking-widest mb-1">Chiffre d'Affaires</h4>
                            <template v-if="isLoading"><Skeleton width="60%" height="3rem" class="mb-2 bg-white/20" /></template>
                            <template v-else>
                                <h2 class="text-4xl font-black tracking-tighter">{{ props.kpis.revenue.currency }}{{ props.kpis.revenue.current.toLocaleString() }}</h2>
                                <div class="mt-4">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest mb-1 text-slate-400">
                                        <span>Objectif Annuel</span>
                                        <span class="text-emerald-400">{{ kpiRevenueProgress }}%</span>
                                    </div>
                                    <ProgressBar :value="Number(kpiRevenueProgress)" :showValue="false" class="h-1.5 bg-white/10 border-none" color="#34d399" />
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- KPI 3 : Cours Actifs -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:shadow-xl transition-all">
                        <div class="relative z-10 flex flex-col h-full justify-between">
                            <div class="flex justify-between items-start mb-2">
                                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i class="pi pi-book text-xl"></i></div>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-slate-500 uppercase tracking-widest mb-1">Programmes Actifs</h4>
                                <template v-if="isLoading"><Skeleton width="50%" height="2.5rem" /></template>
                                <template v-else>
                                    <div class="flex items-end gap-3">
                                        <h2 class="text-4xl font-black text-slate-800 tracking-tighter">{{ props.kpis.courses.current }}</h2>
                                        <span class="text-sm font-bold text-blue-500 mb-1 border-b-2 border-blue-200">{{ props.kpis.courses.active_percentage }}% Actifs</span>
                                    </div>
                                </template>
                            </div>
                            <!-- Mini Sparkline intégrée -->
                            <div class="h-10 w-full mt-4">
                                <Chart type="line" :data="getSparkData(props.chartData.sparklines.sales, '#3b82f6')" :options="sparklineOptions" class="h-full" />
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4 : Réussite -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:shadow-xl transition-all">
                        <div class="relative z-10 h-full flex flex-col justify-between">
                            <div class="flex justify-between items-start mb-2">
                                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center"><i class="pi pi-star-fill text-xl"></i></div>
                                <Badge :value="`${props.kpis.completion.drop}%`" severity="danger" class="font-black" />
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-slate-500 uppercase tracking-widest mb-1">Taux de Complétion</h4>
                                <template v-if="isLoading"><Skeleton width="50%" height="2.5rem" /></template>
                                <template v-else>
                                    <h2 class="text-4xl font-black text-slate-800 tracking-tighter">{{ props.kpis.completion.rate }}%</h2>
                                    <p class="text-xs text-rose-500 mt-1 font-bold flex items-center gap-1"><i class="pi pi-arrow-down text-[10px]"></i> Légère baisse constatée.</p>
                                </template>
                            </div>
                            <div class="h-10 w-full mt-4">
                                <Chart type="line" :data="getSparkData(props.chartData.sparklines.certifications, '#f59e0b')" :options="sparklineOptions" class="h-full" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2 : GRAPHIQUES ANALYTIQUES COMPLEXES (2 COLONNES) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Grand Graphique : Inscriptions -->
                    <div class="col-span-1 lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-black text-slate-800">Dynamique des Inscriptions</h3>
                                <p class="text-xs font-medium text-slate-500">Comparatif d'acquisition sur les 12 derniers mois.</p>
                            </div>
                            <Button icon="pi pi-ellipsis-h" class="p-button-rounded p-button-text p-button-secondary" />
                        </div>

                        <div v-if="isLoading" class="h-[350px] flex items-end gap-2 justify-between">
                            <Skeleton v-for="i in 12" :key="i" width="100%" :height="`${Math.random() * 80 + 20}%`" class="rounded-t-md" />
                        </div>
                        <div v-else class="h-[350px]">
                            <Chart type="bar" :data="yearlyEnrollmentChartData" :options="barChartOptions" class="h-full w-full" />
                        </div>
                    </div>

                    <!-- Radar Chart : Skills Gap -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col">
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-slate-800">Skills Gap Analysis</h3>
                            <p class="text-xs font-medium text-slate-500">Alignement de notre offre vs besoins du marché.</p>
                        </div>

                        <div v-if="isLoading" class="flex-1 flex justify-center items-center">
                            <Skeleton shape="circle" size="15rem" />
                        </div>
                        <div v-else class="flex-1 relative min-h-[300px]">
                            <Chart type="radar" :data="skillsRadarChartData" :options="radarOptions" class="h-full w-full" />
                        </div>
                    </div>
                </div>

                <!-- SECTION 3 : TABLEAU DE BORD OPÉRATIONNEL & LEADERBOARD (3 COLONNES) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Colonne 1 : Infrastructure (Live Data) -->
                    <div class="bg-slate-900 rounded-3xl p-6 border border-slate-800 shadow-xl text-white">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-black text-white flex items-center gap-2">
                                <i class="pi pi-server text-indigo-400"></i> Infra System
                            </h3>
                            <Tag :value="liveServerMetrics.cpu > 80 ? 'CRITIQUE' : 'EN LIGNE'" :severity="getInfraStatusSeverity" class="text-[10px] uppercase font-black tracking-widest" />
                        </div>

                        <div class="flex justify-center mb-8">
                            <Knob v-model="liveServerMetrics.cpu" :size="140" :valueColor="getInfraStatusColor" rangeColor="#1e293b" :strokeWidth="10" readonly valueTemplate="{value}% CPU" />
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="flex justify-between text-xs font-bold mb-2 text-slate-300 uppercase tracking-widest">
                                    <span>Mémoire RAM</span>
                                    <span>{{ liveServerMetrics.ram.toFixed(0) }}%</span>
                                </div>
                                <ProgressBar :value="liveServerMetrics.ram" :showValue="false" class="h-2 bg-slate-800 border-none" color="#8b5cf6" />
                            </div>

                            <div>
                                <p class="text-xs font-bold mb-2 text-slate-300 uppercase tracking-widest">Utilisation Stockage</p>
                                <MeterGroup :value="storageMeters" />
                            </div>
                        </div>
                    </div>

                    <!-- Colonne 2 : Activité Récente (Timeline) -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                        <h3 class="text-lg font-black text-slate-800 mb-6">Journal d'Audit</h3>
                        <ScrollPanel style="height: 380px" class="pr-3 custom-scroll">
                            <Timeline :value="props.activities" class="w-full">
                                <template #marker="slotProps">
                                    <span class="flex w-8 h-8 items-center justify-center text-white rounded-full shadow-md" :class="`bg-${slotProps.item.type === 'info' ? 'blue' : slotProps.item.type === 'success' ? 'emerald' : slotProps.item.type === 'danger' ? 'rose' : 'amber'}-500`">
                                        <i :class="`pi ${slotProps.item.icon} text-xs`"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-6 ml-2">
                                        <p class="text-sm font-black text-slate-800 leading-tight">{{ slotProps.item.event }}</p>
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ slotProps.item.details }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <Badge :value="slotProps.item.user" severity="secondary" class="text-[9px]" />
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest"><i class="pi pi-clock text-[9px]"></i> {{ slotProps.item.time }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Timeline>
                        </ScrollPanel>
                    </div>

                    <!-- Colonne 3 : Support & Leaderboard (Tabs) -->
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                        <TabView class="custom-tabview flex-1 flex flex-col">
                            <TabPanel header="Top Apprenants">
                                <ScrollPanel style="height: 340px" class="px-2">
                                    <div class="flex flex-col gap-3 p-4">
                                        <div v-for="user in props.leaderboard" :key="user.id" class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100 cursor-pointer">
                                            <div class="flex items-center gap-3">
                                                <div class="relative">
                                                    <Avatar :image="user.avatar" shape="circle" size="large" class="shadow-sm border border-slate-200" />
                                                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center text-[8px] font-black text-white shadow-sm" :class="user.rank === 1 ? 'bg-amber-400' : user.rank === 2 ? 'bg-slate-400' : user.rank === 3 ? 'bg-orange-600' : 'bg-indigo-400'">{{ user.rank }}</span>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-sm text-slate-800">{{ user.name }}</p>
                                                    <p class="text-[10px] text-slate-400 font-medium uppercase tracking-widest flex items-center gap-1"><i class="pi pi-globe text-[9px]"></i> {{ user.country }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-black text-indigo-600">{{ user.score.toLocaleString() }}</p>
                                                <p class="text-[9px] text-slate-400 uppercase tracking-widest font-bold">Points</p>
                                            </div>
                                        </div>
                                    </div>
                                </ScrollPanel>
                            </TabPanel>
                            <TabPanel>
                                <template #header>
                                    <OverlayBadge value="3" severity="danger" class="mr-2">Support</OverlayBadge>
                                </template>
                                <ScrollPanel style="height: 340px">
                                    <div class="flex flex-col gap-3 p-4">
                                        <div v-for="ticket in props.supportTickets" :key="ticket.id" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 hover:border-indigo-300 transition-colors cursor-pointer group">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-[10px] font-black text-slate-500 font-mono">{{ ticket.id }}</span>
                                                <Tag :value="ticket.priority" :severity="getPrioritySeverity(ticket.priority)" class="text-[9px] uppercase font-black" />
                                            </div>
                                            <p class="text-sm font-bold text-slate-800 mb-3 group-hover:text-indigo-600 transition-colors">{{ ticket.subject }}</p>
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="flex items-center gap-1 font-medium text-slate-500"><i class="pi pi-user"></i> {{ ticket.user }}</span>
                                                <Badge :value="ticket.status" :severity="ticket.status === 'Open' ? 'warning' : 'success'" />
                                            </div>
                                        </div>
                                    </div>
                                </ScrollPanel>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>

                <!-- SECTION 4 : DATATABLE AVANCÉE (ROW EXPANSION) -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm mb-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-800">Catalogue & Performances par Cohorte</h3>
                            <p class="text-xs font-medium text-slate-500">Cliquez sur la flèche pour voir le détail des sous-modules.</p>
                        </div>
                        <Button icon="pi pi-external-link" label="Gérer le Catalogue" class="p-button-outlined p-button-secondary font-bold rounded-xl" />
                    </div>

                    <DataTable v-model:expandedRows="expandedRows" :value="props.cohorts" dataKey="id"
                               :paginator="true" :rows="5" :filters="dtFilters"
                               class="p-datatable-lg custom-datatable" responsiveLayout="scroll">

                        <template #empty>
                            <div class="p-8 text-center text-slate-500 font-medium">Aucun programme trouvé.</div>
                        </template>
                        <template #loading>
                            <div class="p-8 text-center text-indigo-500 font-bold"><i class="pi pi-spin pi-spinner mr-2"></i> Chargement des données...</div>
                        </template>

                        <Column expander style="width: 3rem" />

                        <Column field="id" header="REF" sortable>
                            <template #body="{data}">
                                <span class="font-mono text-xs font-black text-slate-400">{{ data.id }}</span>
                            </template>
                        </Column>

                        <Column field="name" header="PROGRAMME" sortable style="min-width: 18rem">
                            <template #body="{data}">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ data.name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ data.category }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="instructor" header="INSTRUCTEUR" sortable>
                            <template #body="{data}">
                                <div class="flex items-center gap-2">
                                    <Avatar icon="pi pi-user" shape="circle" class="bg-indigo-50 text-indigo-600" size="small" />
                                    <span class="text-sm font-medium text-slate-700">{{ data.instructor }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="students" header="APPRENANTS" sortable align="center">
                            <template #body="{data}">
                                <span class="font-black text-slate-800">{{ data.students.toLocaleString() }}</span>
                            </template>
                        </Column>

                        <Column field="revenue" header="CA GÉNÉRÉ" sortable align="right">
                            <template #body="{data}">
                                <span class="font-black text-emerald-600">${{ data.revenue.toLocaleString() }}</span>
                            </template>
                        </Column>

                        <Column field="progress" header="AVANCEMENT GLOBAL" sortable>
                            <template #body="{data}">
                                <div class="flex items-center gap-3 w-full max-w-[150px]">
                                    <ProgressBar :value="data.progress" :showValue="false" class="h-2 w-full bg-slate-100" :color="data.progress === 100 ? '#10B981' : '#6366F1'" />
                                    <span class="text-[10px] font-black text-slate-500">{{ data.progress }}%</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" sortable align="center">
                            <template #body="{data}">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase font-black text-[9px] tracking-widest px-2" />
                            </template>
                        </Column>

                        <Column header="ACTIONS" align="center">
                            <template #body="{data}">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" @click="viewCourseDetails(data)" v-tooltip.top="'Voir le détail'" />
                            </template>
                        </Column>

                        <!-- EXPANSION TEMPLATE (Détails Sous-modules) -->
                        <template #expansion="slotProps">
                            <div class="p-6 bg-slate-50/50 border-y border-slate-100 inner-shadow">
                                <h5 class="text-sm font-black text-slate-800 mb-4 flex items-center gap-2"><i class="pi pi-list text-indigo-500"></i> Sous-Modules du Programme</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="(module, idx) in slotProps.data.sub_modules" :key="idx" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 font-black text-xs">{{ idx + 1 }}</div>
                                            <span class="font-bold text-sm text-slate-700">{{ module.name }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="block text-lg font-black text-indigo-600">{{ module.completed }}</span>
                                            <span class="text-[9px] text-slate-400 uppercase font-bold tracking-widest">Validés</span>
                                        </div>
                                    </div>
                                    <div v-if="!slotProps.data.sub_modules || slotProps.data.sub_modules.length === 0" class="col-span-full text-slate-400 text-sm italic">
                                        Aucun sous-module configuré.
                                    </div>
                                </div>
                            </div>
                        </template>

                    </DataTable>
                </div>

            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- SIDEBAR : FILTRES AVANCÉS -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="showFilterSidebar" position="right" class="w-full md:w-[400px] p-sidebar-custom">
            <template #header>
                <div class="flex items-center gap-3">
                    <i class="pi pi-filter text-xl text-indigo-600"></i>
                    <h2 class="text-lg font-black text-slate-800">Filtres Avancés</h2>
                </div>
            </template>
            <div class="p-6 space-y-8 h-full overflow-y-auto">
                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Période d'Analyse</label>
                    <Calendar v-model="activeFilters.dateRange" selectionMode="range" :manualInput="false" placeholder="Sélectionnez une plage..." class="w-full rounded-xl" />
                </div>

                <Divider />

                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Catégories de Formation</label>
                    <MultiSelect v-model="activeFilters.categories" :options="props.availableFilters.categories" placeholder="Toutes les catégories" :maxSelectedLabels="2" class="w-full rounded-xl font-medium" />
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Zone Géographique</label>
                    <div class="flex flex-col gap-2">
                        <div v-for="region in props.availableFilters.regions" :key="region" class="flex items-center">
                            <Checkbox v-model="activeFilters.categories" :inputId="region" :value="region" />
                            <label :for="region" class="ml-2 text-sm text-slate-700 font-medium">{{ region }}</label>
                        </div>
                    </div>
                </div>

                <Divider />

                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Inclure cours archivés</label>
                    <InputSwitch />
                </div>
            </div>

            <div class="absolute bottom-0 left-0 w-full p-4 bg-slate-50 border-t border-slate-200 flex gap-3">
                <Button label="Réinitialiser" class="p-button-text p-button-secondary font-bold flex-1" />
                <Button label="Appliquer" icon="pi pi-check" class="p-button-primary font-bold flex-1 rounded-xl shadow-lg" @click="applyFilters" />
            </div>
        </Sidebar>

        <!-- ============================================================================== -->
        <!-- DIALOG : EXPORT DE DONNÉES -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="showExportModal" modal header="Générateur de Rapports" :style="{ width: '500px' }" class="p-fluid custom-dialog rounded-3xl">
            <div class="p-4 space-y-6">
                <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex gap-3 items-start">
                    <i class="pi pi-info-circle text-indigo-500 mt-0.5"></i>
                    <p class="text-xs text-indigo-800 leading-relaxed font-medium">Les données exportées tiendront compte des filtres globaux actuellement appliqués sur le tableau de bord.</p>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-black text-slate-800">Format de sortie</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center cursor-pointer hover:border-rose-500 hover:bg-rose-50 transition-colors" @click="triggerExportAction('PDF')">
                            <i class="pi pi-file-pdf text-3xl text-rose-500 mb-2"></i>
                            <span class="font-bold text-sm text-slate-700">Rapport PDF</span>
                            <span class="text-[10px] text-slate-400 text-center mt-1">Graphiques inclus</span>
                        </div>
                        <div class="border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center cursor-pointer hover:border-emerald-500 hover:bg-emerald-50 transition-colors" @click="triggerExportAction('CSV')">
                            <i class="pi pi-file-excel text-3xl text-emerald-500 mb-2"></i>
                            <span class="font-bold text-sm text-slate-700">Données CSV</span>
                            <span class="text-[10px] text-slate-400 text-center mt-1">Brutes (Pour Excel)</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-700">Inclure les données financières</span>
                        <InputSwitch :modelValue="true" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-700">Anonymiser les apprenants (RGPD)</span>
                        <InputSwitch :modelValue="false" />
                    </div>
                </div>
            </div>
            <template #footer v-if="isExporting">
                <div class="flex justify-center p-4 w-full">
                    <i class="pi pi-spin pi-spinner text-2xl text-indigo-600"></i>
                    <span class="ml-2 font-bold text-indigo-600">Génération en cours...</span>
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : DÉTAIL DU COURS -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="showCourseDetailModal" modal :style="{ width: '800px' }" class="custom-dialog p-0 overflow-hidden rounded-[2rem]">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-500 font-mono text-xs font-black">{{ selectedCourseDetail?.id }}</div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800">{{ selectedCourseDetail?.name }}</h2>
                        <Tag :value="selectedCourseDetail?.category" class="bg-indigo-100 text-indigo-700 text-[10px] uppercase font-bold px-2 py-0" />
                    </div>
                </div>
            </template>
            <div v-if="selectedCourseDetail" class="p-6 bg-slate-50 min-h-[400px]">
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Inscrits</p>
                        <p class="text-2xl font-black text-slate-800">{{ selectedCourseDetail.students }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Revenus</p>
                        <p class="text-2xl font-black text-emerald-600">${{ selectedCourseDetail.revenue.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Avancement</p>
                        <p class="text-2xl font-black text-indigo-600">{{ selectedCourseDetail.progress }}%</p>
                    </div>
                </div>

                <h3 class="text-sm font-black text-slate-800 mb-4 uppercase tracking-widest">Informations Générales</h3>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-slate-500 text-sm">Instructeur</span>
                        <span class="font-black text-slate-800 text-sm">{{ selectedCourseDetail.instructor }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-slate-500 text-sm">Date de création</span>
                        <span class="font-black text-slate-800 text-sm">{{ selectedCourseDetail.created_at }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-bold text-slate-500 text-sm">Statut Actuel</span>
                        <Tag :value="selectedCourseDetail.status" :severity="getStatusSeverity(selectedCourseDetail.status)" class="uppercase font-black text-[10px]" />
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end p-4 bg-white border-t border-slate-100">
                    <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="showCourseDetailModal = false" />
                    <Button label="Accéder au Studio" icon="pi pi-external-link" class="p-button-primary font-bold rounded-xl" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

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
    background: transparent; border: none; border-bottom: 1px solid #e2e8f0; padding: 0 1.5rem;
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
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1.5rem; }
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
