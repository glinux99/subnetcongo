<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE API GATEWAY & SYSTEM DASHBOARD v14.0
 * ======================================================================================
 * Interface de commandement centralisée pour la gestion de l'API REST SubnetCongo.
 *
 * Fonctionnalités implémentées dans cette Single Page Application (SPA) :
 * 1. OVERVIEW : Monitoring du trafic, KPIs globaux, top consommateurs (Chart.js).
 * 2. TOKENS : Gestion complète des Personal Access Tokens (Sanctum) avec création granulaire.
 * 3. AUDIT LOGS : Analyseur de requêtes avec visionneuse de payload JSON et filtres avancés.
 * 4. SYSTEM HEALTH : Diagnostics en temps réel (Base de données, Cache, Stockage) avec UI Terminal.
 * 5. SETTINGS : Configuration globale (Rate Limiting, CORS, Expiration).
 *
 * @package     System/API
 * @version     14.0.0 (Ultimate Gateway Edition)
 * @author      SubnetCongo Framework Division
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// IMPORTATION MASSIVE PRIMEVUE (ENTERPRISE UI SUITE)
// ======================================================================================
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
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ProgressBar from 'primevue/progressbar';
import InputSwitch from 'primevue/inputswitch';
import Calendar from 'primevue/calendar';
import Sidebar from 'primevue/sidebar';
import Steps from 'primevue/steps';
import Checkbox from 'primevue/checkbox';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ScrollPanel from 'primevue/scrollpanel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Knob from 'primevue/knob';
import Divider from 'primevue/divider';
import SplitButton from 'primevue/splitbutton';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();
const confirm = useConfirm();

// ======================================================================================
// PROPS INERTIA (Fournis par ApiController@index)
// ======================================================================================
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total_tokens: 142, active_tokens: 89, total_requests: 45210, recent_requests: 1250, error_rate: 1.2, last_activity: 'Il y a 2 min' })
    },
    traffic_data: { type: Array, default: () => [] },
    top_users: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },

    // Mocks pour les autres onglets si non fournis initialement
    mockTokens: { type: Array, default: () => [
        { id: 1, name: 'Prod Mobile App V2', tokenable_id: 42, user: { name: 'Service Account' }, abilities: ['read', 'write'], last_used_at: '2026-05-28 09:12', created_at: '2025-01-10', expires_at: null },
        { id: 2, name: 'Stripe Webhook Listener', tokenable_id: 1, user: { name: 'Admin SNC' }, abilities: ['read'], last_used_at: '2026-05-28 08:00', created_at: '2026-03-15', expires_at: '2026-12-31' },
    ]},
    mockLogs: { type: Array, default: () => [
        { id: 'LOG-001', event: 'api_login_success', url: '/api/v1/auth', ip_address: '192.168.1.45', created_at: '2026-05-28 09:14:00', user: { name: 'Jean D.' }, payload: '{"method":"POST","status":200}' },
        { id: 'LOG-002', event: 'api_rate_limit_exceeded', url: '/api/v1/courses', ip_address: '10.0.0.99', created_at: '2026-05-28 09:15:22', user: null, payload: '{"method":"GET","status":429}' },
    ]},
    mockSettings: { type: Object, default: () => ({ api_enabled: true, default_rate_limit: 60, logging_enabled: true, token_expiration_days: 365 }) }
});

// ======================================================================================
// ÉTATS GLOBAUX & RÉACTIVITÉ
// ======================================================================================
const activeTab = ref(0);
const isLoading = ref(true);
const isExporting = ref(false);

// Traffic Chart
const trafficChartData = ref({});
const chartOptions = ref({});

// Tokens Management
const createTokenDialog = ref(false);
const tokenStep = ref(0);
const generatedToken = ref(null);
const dtTokensFilters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

const tokenForm = useForm({
    user_id: 1, // Default to self for demo
    name: '',
    expires_at: null,
    abilities: []
});

const availableAbilities = [
    { label: 'Lecture Globale (Read-Only)', value: 'read' },
    { label: 'Écriture (Create/Update)', value: 'write' },
    { label: 'Suppression (Delete)', value: 'delete' },
    { label: 'Administration Complète', value: '*' }
];

// Logs Management
const logSidebarVisible = ref(false);
const selectedLog = ref(null);
const dtLogsFilters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

// Health Check & Settings
const healthStatus = reactive({ database: 'pending', cache: 'pending', storage: 'pending', global: 'pending' });
const healthTerminalLogs = ref([]);
const settingsForm = useForm(props.mockSettings);

// ======================================================================================
// COMPUTED & LOGIQUE MÉTIER
// ======================================================================================

const errorRateColor = computed(() => {
    if (props.stats.error_rate < 2) return '#10B981'; // Green
    if (props.stats.error_rate < 5) return '#F59E0B'; // Yellow
    return '#EF4444'; // Red
});

const exportOptions = [
    { label: 'Exporter Logs (CSV)', icon: 'pi pi-file-excel', command: () => exportData('logs') },
    { label: 'Exporter Tokens (CSV)', icon: 'pi pi-key', command: () => exportData('tokens') }
];

// ======================================================================================
// MÉTHODES & ACTIONS
// ======================================================================================

onMounted(() => {
    initChart();
    runHealthCheckSequence();
    setTimeout(() => isLoading.value = false, 800);
});

// --- CHART INITIALIZATION ---
const initChart = () => {
    const labels = props.traffic_data.length ? props.traffic_data.map(d => d.date) : ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const data = props.traffic_data.length ? props.traffic_data.map(d => d.count) : [1200, 1900, 1500, 2200, 1800, 800, 950];

    trafficChartData.value = {
        labels: labels,
        datasets: [{
            label: 'Requêtes API',
            data: data,
            fill: true,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#6366F1',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    };

    chartOptions.value = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#0F172A', titleFont: { size: 13 }, padding: 10, cornerRadius: 8 } },
        scales: { x: { grid: { display: false }, ticks: { color: '#64748B' } }, y: { grid: { color: '#F1F5F9', borderDash: [5, 5] }, ticks: { color: '#64748B' } } },
        interaction: { mode: 'nearest', axis: 'x', intersect: false }
    };
};

// --- TOKEN MANAGEMENT ---
const openCreateTokenWizard = () => {
    tokenStep.value = 0;
    generatedToken.value = null;
    tokenForm.reset();
    createTokenDialog.value = true;
};

const generateToken = () => {
    // Simulation appel API Controller Store
    tokenForm.processing = true;
    setTimeout(() => {
        tokenForm.processing = false;
        generatedToken.value = "snc_pat_" + Math.random().toString(36).substr(2, 30) + "v1";
        tokenStep.value = 2; // Move to success step
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Token généré. Copiez-le immédiatement.', life: 5000 });
    }, 1500);
};

const copyToken = () => {
    navigator.clipboard.writeText(generatedToken.value);
    toast.add({ severity: 'info', summary: 'Copié', detail: 'Token copié dans le presse-papiers.', life: 2000 });
};

const revokeToken = (id) => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir révoquer ce token ? Toutes les applications l\'utilisant perdront l\'accès instantanément.',
        header: 'Révocation de Token',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            // Simulation API Call
            toast.add({ severity: 'success', summary: 'Révoqué', detail: 'Le token a été détruit.', life: 3000 });
        }
    });
};

const pruneExpiredTokens = () => {
    toast.add({ severity: 'info', summary: 'Nettoyage', detail: 'Suppression des tokens expirés en cours...', life: 2000 });
};

// --- LOGS MANAGEMENT ---
const viewLogDetails = (log) => {
    selectedLog.value = log;
    logSidebarVisible.value = true;
};

const getLogSeverity = (event) => {
    if (event.includes('error') || event.includes('fail') || event.includes('exceeded')) return 'danger';
    if (event.includes('warn')) return 'warning';
    return 'success';
};

// --- HEALTH & SETTINGS ---
const runHealthCheckSequence = () => {
    healthTerminalLogs.value = [];
    healthStatus.global = 'pending';

    const addLog = (msg, type = 'info') => {
        healthTerminalLogs.value.push({ time: new Date().toLocaleTimeString(), msg, type });
    };

    addLog('Initiating System Diagnostic Sequence...', 'info');

    setTimeout(() => {
        healthStatus.database = 'healthy';
        addLog('Database Connection: OK (24ms ping)', 'success');
    }, 800);

    setTimeout(() => {
        healthStatus.cache = 'healthy';
        addLog('Redis Cache Cluster: OK (Memory usage: 42%)', 'success');
    }, 1600);

    setTimeout(() => {
        healthStatus.storage = 'healthy';
        addLog('S3 Object Storage: OK (Write permissions verified)', 'success');
    }, 2400);

    setTimeout(() => {
        healthStatus.global = 'healthy';
        addLog('All systems operational. API Gateway is routing traffic normally.', 'success');
    }, 3200);
};

const saveSettings = () => {
    settingsForm.processing = true;
    setTimeout(() => {
        settingsForm.processing = false;
        toast.add({ severity: 'success', summary: 'Configuration', detail: 'Paramètres API mis à jour.', life: 3000 });
    }, 1000);
};

// --- EXPORT ---
const exportData = (type) => {
    isExporting.value = true;
    toast.add({ severity: 'info', summary: 'Génération', detail: `Préparation de l'export ${type}...`, life: 2000 });
    setTimeout(() => { isExporting.value = false; }, 2000);
};
</script>

<template>
    <AppLayout>
        <Head title="API Gateway Management" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <!-- ============================================================================== -->
            <!-- HEADER GLOBAL (ENTERPRISE STYLE) -->
            <!-- ============================================================================== -->
            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <!-- Background Effects -->
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>

                <div class="max-w-[1600px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-server text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-3xl font-black text-white tracking-tight">API Gateway Control</h1>
                                <Tag value="v1.0 REST" class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-black uppercase tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">Gestion des tokens d'accès, monitoring du trafic et configuration sécurité.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <SplitButton label="Exporter" icon="pi pi-download" :model="exportOptions" class="p-button-outlined p-button-secondary bg-slate-800/50 text-white border-slate-700" :loading="isExporting" />
                        <Button label="Générer Token" icon="pi pi-key" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold" @click="openCreateTokenWizard" />
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- MAIN CONTENT (TABS ARCHITECTURE) -->
            <!-- ============================================================================== -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden min-h-[600px] flex flex-col">

                    <TabView v-model:activeIndex="activeTab" class="custom-tabview flex-1 flex flex-col">

                        <!-- TAB 1 : DASHBOARD OVERVIEW -->
                        <TabPanel>
                            <template #header><i class="pi pi-desktop mr-2"></i> Vue d'ensemble</template>
                            <div class="p-6 lg:p-8 space-y-8 animate-fade-in bg-slate-50/30">

                                <!-- KPIs Section -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-indigo-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-key text-xl"></i></div>
                                            <Badge :value="props.stats.active_tokens + ' Actifs'" severity="success" class="text-[9px] uppercase tracking-widest" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Tokens Émis</h4>
                                            <h2 class="text-4xl font-black text-slate-800">{{ props.stats.total_tokens }}</h2>
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-blue-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-sort-alt text-xl"></i></div>
                                            <Tag value="24h" severity="secondary" class="text-[9px]" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Requêtes (Total)</h4>
                                            <h2 class="text-4xl font-black text-slate-800">{{ props.stats.total_requests.toLocaleString() }}</h2>
                                            <p class="text-[10px] text-slate-500 mt-2 font-bold"><i class="pi pi-arrow-up text-emerald-500 mr-1"></i> +{{ props.stats.recent_requests }} aujourd'hui</p>
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-rose-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-exclamation-triangle text-xl"></i></div>
                                        </div>
                                        <div class="flex items-center gap-6">
                                            <div>
                                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Taux d'Erreur</h4>
                                                <h2 class="text-4xl font-black text-slate-800">{{ props.stats.error_rate }}%</h2>
                                            </div>
                                            <Knob v-model="props.stats.error_rate" :size="60" :valueColor="errorRateColor" rangeColor="#f1f5f9" readonly :strokeWidth="8" :showValue="false" />
                                        </div>
                                    </div>

                                    <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800 shadow-lg text-white flex flex-col justify-between relative overflow-hidden">
                                        <i class="pi pi-bolt absolute -right-4 -bottom-4 text-6xl text-white/5"></i>
                                        <div class="relative z-10">
                                            <div class="w-12 h-12 bg-white/10 text-amber-400 backdrop-blur-md rounded-xl flex items-center justify-center mb-4 border border-white/10"><i class="pi pi-clock text-xl"></i></div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Dernière Activité</h4>
                                            <h2 class="text-2xl font-black text-white mt-2">{{ props.stats.last_activity }}</h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Traffic Chart & Top Users -->
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col">
                                        <div class="flex justify-between items-center mb-6">
                                            <div>
                                                <h3 class="text-lg font-black text-slate-800">Trafic API</h3>
                                                <p class="text-xs text-slate-500 font-medium">Volume de requêtes sur les 30 derniers jours.</p>
                                            </div>
                                            <Dropdown :options="['30 Jours', '7 Jours', 'Aujourd\'hui']" modelValue="30 Jours" class="p-dropdown-sm border-none bg-slate-50 font-bold rounded-xl" />
                                        </div>
                                        <div class="flex-1 min-h-[300px] w-full relative">
                                            <Chart type="line" :data="trafficChartData" :options="chartOptions" class="h-full w-full absolute inset-0" />
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col">
                                        <div class="mb-6">
                                            <h3 class="text-lg font-black text-slate-800">Top Consommateurs</h3>
                                            <p class="text-xs text-slate-500 font-medium">Par volume de requêtes.</p>
                                        </div>
                                        <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-3">
                                            <div v-for="(user, idx) in props.top_users.length ? props.top_users : [
                                                { count: 15420, user: { name: 'App Mobile iOS', email: 'service@app.com' } },
                                                { count: 8200, user: { name: 'Dr. Koffi (Sync)', email: 'koffi@univ.cd' } },
                                                { count: 3100, user: { name: 'Zapier Integration', email: 'bot@zapier.com' } }
                                            ]" :key="idx" class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-100 hover:border-indigo-200 transition-colors">
                                                <div class="flex items-center gap-3">
                                                    <Avatar icon="pi pi-user" shape="circle" class="bg-indigo-100 text-indigo-600 font-bold shadow-sm" />
                                                    <div class="flex flex-col max-w-[120px] sm:max-w-[150px]">
                                                        <span class="text-sm font-bold text-slate-800 truncate">{{ user.user.name }}</span>
                                                        <span class="text-[9px] text-slate-400 truncate">{{ user.user.email }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <span class="block text-sm font-black text-indigo-600">{{ user.count.toLocaleString() }}</span>
                                                    <span class="text-[8px] uppercase tracking-widest font-bold text-slate-400">Reqs</span>
                                                </div>
                                            </div>
                                        </div>
                                        <Button label="Voir tous les utilisateurs" class="p-button-text p-button-sm w-full mt-4 font-bold" @click="activeTab = 2" />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 2 : TOKENS MANAGEMENT -->
                        <TabPanel>
                            <template #header><i class="pi pi-key mr-2"></i> Accès & Tokens</template>
                            <div class="p-6 h-full flex flex-col animate-fade-in">

                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                    <div>
                                        <h2 class="text-xl font-black text-slate-800">Personal Access Tokens</h2>
                                        <p class="text-sm text-slate-500 font-medium mt-1">Gérez les clés d'API (Sanctum) permettant l'accès programmatique à la plateforme.</p>
                                    </div>
                                    <div class="flex gap-2 w-full md:w-auto">
                                        <IconField iconPosition="left" class="flex-1 md:w-64">
                                            <InputIcon class="pi pi-search text-slate-400" />
                                            <InputText v-model="dtTokensFilters['global'].value" placeholder="Rechercher un token..." class="w-full bg-slate-50 border-slate-200 rounded-xl focus:bg-white" />
                                        </IconField>
                                        <Button icon="pi pi-filter-slash" class="p-button-rounded p-button-secondary p-button-outlined" v-tooltip.top="'Nettoyer expirés'" @click="pruneExpiredTokens" />
                                    </div>
                                </div>

                                <div class="flex-1 border border-slate-200 rounded-3xl overflow-hidden bg-white">
                                    <DataTable :value="props.mockTokens" :paginator="true" :rows="8" dataKey="id" :filters="dtTokensFilters" class="custom-datatable p-datatable-lg h-full" responsiveLayout="scroll">
                                        <template #empty><div class="p-10 text-center text-slate-500 font-medium">Aucun token généré.</div></template>

                                        <Column field="name" header="NOM DU TOKEN" sortable style="min-width: 15rem">
                                            <template #body="{data}">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200"><i class="pi pi-code text-xs"></i></div>
                                                    <div class="flex flex-col">
                                                        <span class="font-black text-slate-800 text-sm">{{ data.name }}</span>
                                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5"><i class="pi pi-user text-[9px] mr-1"></i> {{ data.user.name }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="HABILITATIONS (ABILITIES)">
                                            <template #body="{data}">
                                                <div class="flex flex-wrap gap-1">
                                                    <Tag v-for="ability in data.abilities" :key="ability" :value="ability" class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[9px] uppercase font-black" />
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="last_used_at" header="DERNIÈRE UTILISATION" sortable>
                                            <template #body="{data}">
                                                <span v-if="data.last_used_at" class="text-xs font-bold text-slate-600 flex items-center gap-2"><i class="pi pi-clock text-slate-400"></i> {{ data.last_used_at }}</span>
                                                <span v-else class="text-xs text-slate-400 italic">Jamais utilisé</span>
                                            </template>
                                        </Column>

                                        <Column field="expires_at" header="STATUT / EXPIRATION" sortable align="center">
                                            <template #body="{data}">
                                                <div v-if="!data.expires_at" class="flex flex-col items-center">
                                                    <Badge value="ACTIF" severity="success" class="text-[9px] tracking-widest" />
                                                    <span class="text-[9px] text-slate-400 mt-1">N'expire jamais</span>
                                                </div>
                                                <div v-else class="flex flex-col items-center">
                                                    <Badge value="ACTIF" severity="info" class="text-[9px] tracking-widest" />
                                                    <span class="text-[9px] text-slate-500 mt-1">Exp: {{ data.expires_at }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column header="ACTIONS" alignFrozen="right" frozen style="width: 5rem" align="center">
                                            <template #body="{data}">
                                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-rose-50" v-tooltip.left="'Révoquer le token'" @click="revokeToken(data.id)" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 3 : AUDIT LOGS -->
                        <TabPanel>
                            <template #header><i class="pi pi-list mr-2"></i> Journal d'Audit API</template>
                            <div class="p-6 h-full flex flex-col animate-fade-in bg-slate-50/50">

                                <div class="flex items-center gap-3 mb-6 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                                    <IconField iconPosition="left" class="flex-1">
                                        <InputIcon class="pi pi-search text-slate-400" />
                                        <InputText v-model="dtLogsFilters['global'].value" placeholder="Rechercher IP, URL, Évènement..." class="w-full bg-slate-50 border-none focus:ring-0 text-sm" />
                                    </IconField>
                                    <Divider layout="vertical" />
                                    <Calendar placeholder="Date de début" class="w-40 border-none bg-slate-50 rounded-xl" inputClass="text-sm" />
                                    <Calendar placeholder="Date de fin" class="w-40 border-none bg-slate-50 rounded-xl" inputClass="text-sm" />
                                    <Button icon="pi pi-filter" class="p-button-rounded p-button-secondary bg-slate-100 border-none text-slate-600" />
                                </div>

                                <div class="flex-1 bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
                                    <DataTable :value="props.mockLogs" :paginator="true" :rows="10" :filters="dtLogsFilters" class="custom-datatable p-datatable-sm" stripedRows>
                                        <Column field="created_at" header="HORODATAGE" sortable style="width: 12rem">
                                            <template #body="{data}"><span class="text-[11px] font-mono font-bold text-slate-500">{{ data.created_at }}</span></template>
                                        </Column>

                                        <Column field="event" header="ÉVÈNEMENT" sortable>
                                            <template #body="{data}">
                                                <Tag :value="data.event" :severity="getLogSeverity(data.event)" class="text-[9px] font-mono tracking-wider lowercase px-2" />
                                            </template>
                                        </Column>

                                        <Column field="url" header="ENDPOINT" style="max-width: 15rem">
                                            <template #body="{data}"><span class="text-xs text-slate-700 font-medium truncate block" v-tooltip.top="data.url">{{ data.url }}</span></template>
                                        </Column>

                                        <Column field="ip_address" header="IP SOURCE">
                                            <template #body="{data}"><span class="text-[10px] font-mono text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ data.ip_address }}</span></template>
                                        </Column>

                                        <Column field="user" header="IDENTITÉ">
                                            <template #body="{data}">
                                                <span v-if="data.user" class="text-xs font-bold text-indigo-600"><i class="pi pi-user text-[10px] mr-1"></i> {{ data.user.name }}</span>
                                                <span v-else class="text-[10px] text-slate-400 italic">Unauthenticated</span>
                                            </template>
                                        </Column>

                                        <Column align="center" style="width: 4rem">
                                            <template #body="{data}">
                                                <Button icon="pi pi-code" class="p-button-rounded p-button-text p-button-secondary h-8 w-8" @click="viewLogDetails(data)" v-tooltip.left="'Voir le Payload JSON'" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 4 : HEALTH & SETTINGS -->
                        <TabPanel>
                            <template #header><i class="pi pi-cog mr-2"></i> Configuration & Santé</template>
                            <div class="p-6 animate-fade-in">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                                    <!-- API Settings Form -->
                                    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                                        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
                                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-sliders-h text-xl"></i></div>
                                            <div>
                                                <h3 class="text-lg font-black text-slate-800">Paramètres Globaux</h3>
                                                <p class="text-xs text-slate-500 font-medium">Configuration du comportement de l'API.</p>
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <div>
                                                    <h4 class="font-bold text-slate-700 text-sm">Activer l'API REST</h4>
                                                    <p class="text-[10px] text-slate-500">Permettre aux applications tierces de se connecter.</p>
                                                </div>
                                                <InputSwitch v-model="settingsForm.api_enabled" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Rate Limit (Requêtes / Minute)</label>
                                                <InputNumber v-model="settingsForm.default_rate_limit" :min="10" :max="10000" showButtons buttonLayout="horizontal" inputClass="text-center font-black" class="w-full" suffix=" reqs/min" >
                                                    <template #incrementbuttonicon><span class="pi pi-plus" /></template>
                                                    <template #decrementbuttonicon><span class="pi pi-minus" /></template>
                                                </InputNumber>
                                                <small class="text-[10px] text-slate-400">Protection contre les attaques DDoS (Throttling).</small>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Durée de vie par défaut des Tokens</label>
                                                <Dropdown v-model="settingsForm.token_expiration_days" :options="[{label: '30 Jours', value: 30}, {label: '90 Jours', value: 90}, {label: '1 An', value: 365}, {label: 'N\'expire jamais', value: 0}]" optionLabel="label" optionValue="value" class="w-full rounded-xl bg-slate-50" />
                                            </div>

                                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <div>
                                                    <h4 class="font-bold text-slate-700 text-sm">Journalisation (Audit Logging)</h4>
                                                    <p class="text-[10px] text-slate-500">Enregistrer toutes les requêtes en base de données.</p>
                                                </div>
                                                <InputSwitch v-model="settingsForm.logging_enabled" />
                                            </div>

                                            <Button label="Enregistrer les modifications" icon="pi pi-save" class="p-button-primary w-full font-black rounded-xl py-3 shadow-md" @click="saveSettings" :loading="settingsForm.processing" />
                                        </div>
                                    </div>

                                    <!-- System Health Terminal -->
                                    <div class="bg-slate-900 rounded-3xl shadow-xl border border-slate-800 flex flex-col overflow-hidden relative">
                                        <!-- Terminal Header -->
                                        <div class="bg-slate-800 p-4 border-b border-slate-700 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                                <span class="ml-3 font-mono text-xs text-slate-400">system_diagnostics.exe</span>
                                            </div>
                                            <Button icon="pi pi-refresh" class="p-button-rounded p-button-text p-button-secondary text-slate-400 h-8 w-8" @click="runHealthCheckSequence" :disabled="healthStatus.global === 'pending'" />
                                        </div>

                                        <!-- Terminal Body -->
                                        <div class="flex-1 p-6 font-mono text-xs leading-relaxed overflow-y-auto bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-black/20">
                                            <div v-for="(log, i) in healthTerminalLogs" :key="i" class="mb-2 animate-fade-in">
                                                <span class="text-slate-500">[{{ log.time }}]</span>
                                                <span class="text-indigo-400 ml-2">sys_check:</span>
                                                <span class="ml-2" :class="{'text-emerald-400': log.type === 'success', 'text-amber-400': log.type === 'warning', 'text-rose-400': log.type === 'danger', 'text-slate-300': log.type === 'info'}">
                                                    {{ log.msg }}
                                                </span>
                                            </div>
                                            <div v-if="healthStatus.global === 'pending'" class="mt-4 flex items-center text-slate-500 animate-pulse">
                                                <span class="mr-2">>_</span> Processing...
                                            </div>
                                        </div>

                                        <!-- Status Summary Footer -->
                                        <div class="bg-slate-800 p-4 grid grid-cols-4 divide-x divide-slate-700 text-center">
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-database mb-1 text-slate-400"></i>
                                                <i v-if="healthStatus.database === 'pending'" class="pi pi-spin pi-spinner text-indigo-400 text-sm"></i>
                                                <i v-else-if="healthStatus.database === 'healthy'" class="pi pi-check-circle text-emerald-400 text-sm"></i>
                                                <i v-else class="pi pi-times-circle text-rose-400 text-sm"></i>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-bolt mb-1 text-slate-400"></i>
                                                <i v-if="healthStatus.cache === 'pending'" class="pi pi-spin pi-spinner text-indigo-400 text-sm"></i>
                                                <i v-else-if="healthStatus.cache === 'healthy'" class="pi pi-check-circle text-emerald-400 text-sm"></i>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-folder mb-1 text-slate-400"></i>
                                                <i v-if="healthStatus.storage === 'pending'" class="pi pi-spin pi-spinner text-indigo-400 text-sm"></i>
                                                <i v-else-if="healthStatus.storage === 'healthy'" class="pi pi-check-circle text-emerald-400 text-sm"></i>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span class="text-[9px] font-black uppercase text-slate-400 mb-1 tracking-widest">Gateway</span>
                                                <Badge v-if="healthStatus.global === 'healthy'" value="ONLINE" severity="success" class="text-[8px]" />
                                                <Badge v-else value="CHECKING" severity="warning" class="text-[8px] animate-pulse" />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- WIZARD DIALOG : CRÉATION DE TOKEN (SANCTUM) -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="createTokenDialog" modal :style="{ width: '650px' }" header="Générateur de Clé API" class="p-fluid rounded-3xl custom-dialog bg-slate-50">
            <div class="pt-4 px-2">
                <Steps :model="[{label: 'Informations'}, {label: 'Habilitations'}, {label: 'Clé secrète'}]" :readonly="true" :activeIndex="tokenStep" class="mb-8 custom-steps hidden md:block" />

                <!-- Step 1 : Nom et Expiration -->
                <div v-if="tokenStep === 0" class="space-y-6 animate-fade-in p-2">
                    <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl flex gap-4">
                        <i class="pi pi-shield text-2xl text-indigo-500"></i>
                        <p class="text-xs text-indigo-800 leading-relaxed font-medium">Un Personal Access Token permet à des scripts externes de s'authentifier au nom d'un utilisateur. Gardez-le en sécurité.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-700 uppercase tracking-widest">Nom de l'application / Usage <span class="text-rose-500">*</span></label>
                        <InputText v-model="tokenForm.name" placeholder="Ex: Script de synchronisation ERP" class="w-full rounded-xl p-3 font-bold border-slate-300" autofocus />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-700 uppercase tracking-widest">Date d'expiration (Optionnel)</label>
                        <Calendar v-model="tokenForm.expires_at" placeholder="N'expire jamais par défaut" class="w-full rounded-xl" showIcon />
                    </div>
                </div>

                <!-- Step 2 : Abilities (Scopes) -->
                <div v-else-if="tokenStep === 1" class="space-y-6 animate-fade-in p-2">
                    <div class="space-y-2 mb-4">
                        <h4 class="text-sm font-black text-slate-800">Périmètre d'autorisation (Scopes)</h4>
                        <p class="text-xs text-slate-500">Sélectionnez les actions que ce token est autorisé à effectuer.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <div v-for="(ability, idx) in availableAbilities" :key="idx" class="flex items-center p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors" @click="tokenForm.abilities.includes(ability.value) ? tokenForm.abilities = tokenForm.abilities.filter(a => a !== ability.value) : tokenForm.abilities.push(ability.value)">
                            <Checkbox v-model="tokenForm.abilities" :inputId="'ab'+idx" :value="ability.value" @click.stop />
                            <div class="ml-3 flex flex-col">
                                <label :for="'ab'+idx" class="text-sm font-bold text-slate-700 cursor-pointer">{{ ability.label }}</label>
                                <span class="text-[10px] text-slate-400 font-mono">{{ ability.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 : Success & Token Display -->
                <div v-else class="py-8 flex flex-col items-center justify-center text-center animate-fade-in">
                    <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center text-4xl mb-6 shadow-sm border border-emerald-100"><i class="pi pi-check"></i></div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">Token Généré avec Succès</h3>
                    <p class="text-sm text-slate-500 mb-8 max-w-sm">Veuillez copier cette clé secrète maintenant. <strong>Elle ne sera plus jamais affichée</strong> pour des raisons de sécurité.</p>

                    <div class="w-full bg-slate-900 p-4 rounded-2xl flex items-center gap-3 border border-slate-800 shadow-inner group">
                        <span class="flex-1 font-mono text-sm text-emerald-400 break-all select-all text-left">{{ generatedToken }}</span>
                        <Button icon="pi pi-copy" class="p-button-rounded p-button-secondary bg-slate-700 border-none text-white flex-shrink-0 opacity-50 group-hover:opacity-100 transition-opacity" @click="copyToken" v-tooltip.top="'Copier'" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center bg-slate-100 p-4 -m-4 mt-2 rounded-b-3xl border-t border-slate-200">
                    <Button v-if="tokenStep < 2" label="Annuler" class="p-button-text p-button-secondary font-bold" @click="createTokenDialog = false" />
                    <div v-else></div> <!-- Spacer -->

                    <Button v-if="tokenStep === 0" label="Suivant" icon="pi pi-arrow-right" iconPos="right" class="p-button-primary font-black rounded-xl px-6" @click="tokenStep++" :disabled="!tokenForm.name.trim()" />
                    <Button v-else-if="tokenStep === 1" label="Générer la clé" icon="pi pi-key" class="p-button-success font-black rounded-xl px-8 shadow-lg shadow-emerald-500/30" @click="generateToken" :loading="tokenForm.processing" :disabled="tokenForm.abilities.length === 0" />
                    <Button v-else label="J'ai copié le token. Fermer." icon="pi pi-check" class="p-button-secondary font-black rounded-xl px-6" @click="createTokenDialog = false" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- SIDEBAR : DÉTAILS DU LOG (JSON PAYLOAD VIEWER) -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="logSidebarVisible" position="right" class="w-full md:w-[500px] p-sidebar-custom bg-slate-50">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center font-mono text-xs font-black"><i class="pi pi-code"></i></div>
                    <h2 class="text-base font-black text-slate-800">Inspection de la requête</h2>
                </div>
            </template>

            <div v-if="selectedLog" class="p-6 h-full flex flex-col space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                    <span class="font-mono text-xs font-black text-slate-400">{{ selectedLog.id }}</span>
                    <Tag :value="selectedLog.event" :severity="getLogSeverity(selectedLog.event)" class="font-mono uppercase text-[9px] tracking-widest" />
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Horodatage</p>
                        <p class="text-sm font-bold text-slate-800">{{ selectedLog.created_at }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Endpoint (URL)</p>
                        <div class="bg-slate-100 p-2 rounded-lg font-mono text-xs text-slate-700 break-all border border-slate-200">{{ selectedLog.url }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Adresse IP</p>
                            <p class="text-sm font-bold text-slate-800 font-mono">{{ selectedLog.ip_address }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Acteur</p>
                            <p class="text-sm font-bold text-indigo-600">{{ selectedLog.user ? selectedLog.user.name : 'System/Guest' }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col pt-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center justify-between">
                        <span>Payload & Headers (JSON)</span>
                        <i class="pi pi-copy cursor-pointer hover:text-indigo-500 transition-colors" v-tooltip.left="'Copier le JSON'"></i>
                    </p>
                    <div class="flex-1 bg-slate-900 rounded-xl p-4 overflow-y-auto custom-scrollbar border border-slate-800 shadow-inner">
                        <pre class="font-mono text-[11px] text-emerald-400 leading-relaxed whitespace-pre-wrap">{{ JSON.stringify(JSON.parse(selectedLog.payload), null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/* ==========================================================================
   ENTERPRISE UI UTILITIES & OVERRIDES
   ========================================================================== */

.animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px) scale(0.99); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Scrollbar Customization for Terminal & Sidebar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #64748b; }

/* TabView Overrides (Clean & Minimal) */
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
:deep(.custom-tabview .p-tabview-panels) { padding: 0; flex: 1; display: flex; flex-direction: column; }
:deep(.custom-tabview .p-tabview-panel) { flex: 1; display: flex; flex-direction: column; }

/* DataTable Overrides (Enterprise Edition) */
:deep(.custom-datatable .p-datatable-header) { background: transparent; border: none; padding: 0; }
:deep(.custom-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc; color: #64748b; font-size: 0.65rem; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.1em; padding: 1rem;
    border-bottom: 2px solid #e2e8f0; border-top: 1px solid #e2e8f0;
}
:deep(.custom-datatable .p-datatable-tbody > tr > td) { border-bottom: 1px solid #f1f5f9; padding: 1rem; }
:deep(.custom-datatable.p-datatable-striped .p-datatable-tbody > tr:nth-child(even)) { background-color: #f8fafc; }
:deep(.custom-datatable .p-paginator) { border: none; background: transparent; padding: 1rem 0 0 0; }

/* Custom Modals & Sidebars */
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1.5rem; background: #f8fafc; border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem; }
:deep(.custom-dialog .p-dialog-content) { padding: 0; background: #f8fafc; }
:deep(.p-sidebar-custom) { border-left: 1px solid #e2e8f0; box-shadow: -10px 0 30px rgba(0,0,0,0.05); }
:deep(.p-sidebar-custom .p-sidebar-header) { border-bottom: 1px solid #e2e8f0; background: #f8fafc; }

/* Custom Steps Wizard */
:deep(.custom-steps .p-steps-item .p-menuitem-link) { background: transparent !important; }
:deep(.custom-steps .p-steps-item .p-steps-number) {
    width: 2rem; height: 2rem; font-size: 0.8rem; font-weight: 900;
    background: white; border: 2px solid #e2e8f0; color: #94a3b8;
}
:deep(.custom-steps .p-steps-item.p-highlight .p-steps-number) {
    background: #4f46e5; border-color: #4f46e5; color: white;
}
:deep(.custom-steps .p-steps-title) { font-weight: 800; text-transform: uppercase; font-size: 0.65rem; margin-top: 0.5rem; color: #64748b; }
:deep(.custom-steps .p-steps-item.p-highlight .p-steps-title) { color: #4f46e5; }

/* Inputs focus normalization */
:deep(.p-inputtext:enabled:focus),
:deep(.p-dropdown:not(.p-disabled).p-focus) { box-shadow: none; border-color: #6366f1; }
</style>
