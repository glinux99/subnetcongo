<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - API GATEWAY MANAGEMENT (100% REAL DATA)
 * ======================================================================================
 * Toutes les données proviennent du backend via Inertia + fetch JSON :
 *  - Stats tokens / requêtes / erreurs depuis audit_logs (table réelle).
 *  - Tokens Sanctum réels (PersonalAccessToken).
 *  - Logs depuis audit_logs (filtrés sur method / event_name / url).
 *  - Settings persistés dans web_settings (group='api').
 *  - Health check temps réel (DB, cache, storage).
 * ======================================================================================
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { FilterMatchMode } from '@primevue/core/api';

import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import SncDialog from '@/Components/SncDialog.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import InputSwitch from 'primevue/inputswitch';
import Calendar from 'primevue/calendar';
import Sidebar from 'primevue/sidebar';
import Steps from 'primevue/steps';
import Checkbox from 'primevue/checkbox';
import Knob from 'primevue/knob';
import Divider from 'primevue/divider';
import SplitButton from 'primevue/splitbutton';
import InputNumber from 'primevue/inputnumber';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ScrollPanel from 'primevue/scrollpanel';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const toast = useToast();
const confirmAction = useConfirmAction();

// ======================================================================================
// PROPS INERTIA (100% backend)
// ======================================================================================
const props = defineProps({
    stats:        { type: Object, required: true },
    traffic_data: { type: Array,  default: () => [] },
    methods:      { type: Object, default: () => ({}) },
    top_users:    { type: Array,  default: () => [] },
    settings:     { type: Object, required: true },
    health:       { type: Object, required: true },
});

// ======================================================================================
// ÉTAT GLOBAL
// ======================================================================================
const activeTab = ref(0);
const isLoading = ref({ tokens: false, logs: false });

// ---- Tokens ----
const tokens = ref([]);
const tokensPagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
const users = ref([]);
const dtTokensFilters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });
const createTokenDialog = ref(false);
const tokenStep = ref(0);
const generatedToken = ref(null);
const tokenForm = useForm({
    user_id: null,
    name: '',
    abilities: ['read'],
    expires_at: null,
});
const availableAbilities = [
    { label: t('pages.api.ability_read'),  value: 'read' },
    { label: t('pages.api.ability_write'), value: 'write' },
    { label: t('pages.api.ability_delete'), value: 'delete' },
    { label: t('pages.api.ability_admin'), value: '*' },
];

// ---- Logs ----
const logs = ref([]);
const logsPagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
const availableMethods = ref([]);
const availableLevels = ref(['info', 'warning', 'error', 'critical']);
const logFilters = reactive({ search: '', method: null, level: null, date_from: null, date_to: null });
const logSidebarVisible = ref(false);
const selectedLog = ref(null);
const dtLogsFilters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

// ---- Settings ----
const settingsForm = useForm({ ...props.settings });
const healthStatus = ref(props.health);
const healthLoading = ref(false);
const healthTerminalLogs = ref([]);

// ---- Chart ----
const trafficChartData = ref({});
const chartOptions = ref({});

// ======================================================================================
// COMPUTED
// ======================================================================================
const errorRateColor = computed(() => {
    if (props.stats.error_rate < 2) return '#10B981';
    if (props.stats.error_rate < 5) return '#F59E0B';
    return '#EF4444';
});

const exportOptions = [
    { label: t('pages.api.export_csv'),  icon: 'pi pi-file-excel', command: () => exportLogs() },
];

// ======================================================================================
// LIFECYCLE
// ======================================================================================
onMounted(() => {
    initChart();
    loadTokens();
    loadLogs();
    // Health est déjà chargé via props ; pas de re-run auto (l'utilisateur a un bouton)
});

// ======================================================================================
// CHARTS
// ======================================================================================
const initChart = () => {
    // Si pas de données, on génère un tableau des 30 derniers jours à zéro (pas de faux nombres)
    const labels = [];
    const data = [];
    if (props.traffic_data && props.traffic_data.length) {
        for (const r of props.traffic_data) {
            labels.push(r.date);
            data.push(r.count);
        }
    } else {
        for (let i = 29; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            labels.push(d.toISOString().slice(5, 10));
            data.push(0);
        }
    }

    trafficChartData.value = {
        labels,
        datasets: [{
            label: t('pages.api.chart_label'),
            data,
            fill: true,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.12)',
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#6366F1',
            pointRadius: 3,
            pointHoverRadius: 6,
        }],
    };
    chartOptions.value = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#0F172A', cornerRadius: 8 } },
        scales: { x: { grid: { display: false }, ticks: { color: '#64748B' } }, y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { color: '#64748B' } } },
    };
};

// ======================================================================================
// TOKENS
// ======================================================================================
const loadTokens = async (page = 1) => {
    isLoading.value.tokens = true;
    try {
        const params = new URLSearchParams();
        params.set('page', page);
        if (dtTokensFilters.value.global.value) params.set('search', dtTokensFilters.value.global.value);
        const res = await fetch(`/system/api/tokens?${params}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        tokens.value = data.tokens.data;
        tokensPagination.value = {
            current_page: data.tokens.current_page,
            last_page:    data.tokens.last_page,
            total:        data.tokens.total,
            per_page:     data.tokens.per_page,
        };
        users.value = data.users;
        if (!tokenForm.user_id && data.users.length) tokenForm.user_id = data.users[0].id;
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.api.error'), detail: t('pages.api.tokens_load_failed'), life: 4000 });
    } finally {
        isLoading.value.tokens = false;
    }
};

const openCreateTokenWizard = () => {
    tokenStep.value = 0;
    generatedToken.value = null;
    tokenForm.reset();
    tokenForm.abilities = ['read'];
    createTokenDialog.value = true;
};

const generateToken = async () => {
    tokenForm.processing = true;
    try {
        const res = await fetch('/system/api/tokens', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({
                user_id:    tokenForm.user_id,
                name:       tokenForm.name,
                abilities:  tokenForm.abilities,
                expires_at: tokenForm.expires_at ? new Date(tokenForm.expires_at).toISOString().slice(0, 19).replace('T', ' ') : null,
            }),
        });
        const data = await res.json();
        if (! res.ok) {
            toast.add({ severity: 'error', summary: t('pages.api.error'), detail: data.message || t('pages.api.create_failed'), life: 4000 });
            return;
        }
        generatedToken.value = data.plain_token;
        tokenStep.value = 2;
        toast.add({ severity: 'success', summary: t('pages.api.token_generated'), detail: t('pages.api.token_generated_detail'), life: 5000 });
        loadTokens();
        router.reload({ only: ['stats'] });
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.api.error_network'), detail: e.message, life: 4000 });
    } finally {
        tokenForm.processing = false;
    }
};

const copyToken = async () => {
    try {
        await navigator.clipboard.writeText(generatedToken.value);
        toast.add({ severity: 'info', summary: t('pages.api.copied'), detail: t('pages.api.copied_detail'), life: 2000 });
    } catch (e) {
        toast.add({ severity: 'warn', summary: t('pages.api.copy_manual'), detail: t('pages.api.copy_manual_detail'), life: 4000 });
    }
};

const revokeToken = (token) => {
    confirmAction({
        message: t('pages.api.revoke_confirm', { name: token.name }),
        header: t('pages.api.revoke_header'),
        icon: 'pi pi-exclamation-triangle',
        danger: true,
        accept: async () => {
            try {
                const res = await fetch(`/system/api/tokens/${token.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                });
                const data = await res.json();
                if (res.ok) {
                    toast.add({ severity: 'success', summary: t('pages.api.revoked'), detail: data.message || t('pages.api.token_deleted'), life: 3000 });
                    loadTokens();
                    router.reload({ only: ['stats'] });
                } else {
                    toast.add({ severity: 'error', summary: t('pages.api.error'), detail: data.message || t('pages.api.revoke_failed'), life: 4000 });
                }
            } catch (e) {
                toast.add({ severity: 'error', summary: t('pages.api.error_network'), detail: e.message, life: 4000 });
            }
        },
    });
};

const pruneExpiredTokens = async () => {
    try {
        const res = await fetch('/system/api/tokens/prune', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        });
        const data = await res.json();
        toast.add({ severity: 'success', summary: t('pages.api.cleanup'), detail: data.message || t('pages.api.tokens_pruned'), life: 3000 });
        loadTokens();
        router.reload({ only: ['stats'] });
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.api.error'), detail: e.message, life: 4000 });
    }
};

// ======================================================================================
// LOGS
// ======================================================================================
const loadLogs = async (page = 1) => {
    isLoading.value.logs = true;
    try {
        const params = new URLSearchParams();
        params.set('page', page);
        if (logFilters.search)   params.set('search', logFilters.search);
        if (logFilters.method)   params.set('method', logFilters.method);
        if (logFilters.level)    params.set('level', logFilters.level);
        if (logFilters.date_from) params.set('date_from', logFilters.date_from);
        if (logFilters.date_to)   params.set('date_to',   logFilters.date_to);
        const res = await fetch(`/system/api/logs?${params}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        logs.value = data.logs.data;
        logsPagination.value = {
            current_page: data.logs.current_page,
            last_page:    data.logs.last_page,
            total:        data.logs.total,
            per_page:     data.logs.per_page,
        };
        if (data.methods) availableMethods.value = data.methods;
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.api.error'), detail: t('pages.api.logs_load_failed'), life: 4000 });
    } finally {
        isLoading.value.logs = false;
    }
};

const resetLogFilters = () => {
    logFilters.search = ''; logFilters.method = null; logFilters.level = null;
    logFilters.date_from = null; logFilters.date_to = null;
    loadLogs();
};

const viewLogDetails = (log) => { selectedLog.value = log; logSidebarVisible.value = true; };

const getLogSeverity = (level) => {
    if (level === 'error' || level === 'critical') return 'danger';
    if (level === 'warning') return 'warning';
    return 'success';
};

const getMethodColor = (method) => {
    return {
        GET: 'success', POST: 'info', PUT: 'warn', PATCH: 'warn', DELETE: 'danger',
    }[method] || 'secondary';
};

// ======================================================================================
// SETTINGS & HEALTH
// ======================================================================================
const saveSettings = async () => {
    settingsForm.processing = true;
    try {
        const res = await fetch('/system/api/settings', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify(settingsForm),
        });
        const data = await res.json();
        if (res.ok) {
            toast.add({ severity: 'success', summary: t('pages.api.settings_summary'), detail: data.message || t('pages.api.settings_saved'), life: 3000 });
            router.reload({ only: ['settings', 'stats'] });
        } else {
            toast.add({ severity: 'error', summary: t('pages.api.error'), detail: data.message || t('pages.api.settings_save_failed'), life: 4000 });
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.api.error_network'), detail: e.message, life: 4000 });
    } finally {
        settingsForm.processing = false;
    }
};

const runHealthCheck = async () => {
    healthLoading.value = true;
    healthTerminalLogs.value = [];
    const now = new Date();
    const add = (msg, type = 'info') => healthTerminalLogs.value.push({ time: now.toLocaleTimeString(), msg, type });

    add(t('pages.api.health_start'));
    try {
        const res = await fetch('/system/api/health', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        healthStatus.value = data;

        add(`${t('pages.api.health_db')} : ${data.database ? t('pages.api.ok') : t('pages.api.fail')}${data.database_error ? ' (' + data.database_error + ')' : ''}`, data.database ? 'success' : 'danger');
        add(`Cache : ${data.cache ? t('pages.api.ok') : t('pages.api.fail')}${data.cache_error ? ' (' + data.cache_error + ')' : ''}`, data.cache ? 'success' : 'danger');
        add(`${t('pages.api.health_storage')} : ${data.storage ? t('pages.api.ok') : t('pages.api.fail')}${data.storage_error ? ' (' + data.storage_error + ')' : ''}`, data.storage ? 'success' : 'danger');
        add(t('pages.api.env_line', { php: data.php, laravel: data.laravel }), 'info');
        add(t('pages.api.status_line', { status: data.status === 'healthy' ? t('pages.api.operational') : t('pages.api.degraded') }), data.status === 'healthy' ? 'success' : 'warning');
    } catch (e) {
        add(t('pages.api.health_unreachable') + ' ' + e.message, 'danger');
    } finally {
        healthLoading.value = false;
    }
};

const exportLogs = () => {
    const params = new URLSearchParams();
    if (logFilters.date_from) params.set('date_from', logFilters.date_from);
    if (logFilters.date_to)   params.set('date_to',   logFilters.date_to);
    window.location.href = `/system/api/export-logs?${params}`;
    toast.add({ severity: 'info', summary: t('pages.api.export_summary'), detail: t('pages.api.export_csv_detail'), life: 2000 });
};
</script>

<template>
    <AppLayout>
        <Head title="API Gateway Management" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- HEADER -->
            <PageHeader
                title="API Gateway Control"
                :subtitle="t('pages.api.monitoring')"
                icon="pi-server"
                accent="blue"
            >
                <template #meta>
                    <Tag :value="healthStatus.status === 'healthy' ? 'ONLINE' : t('pages.api.degraded')" :severity="healthStatus.status === 'healthy' ? 'success' : 'danger'" class="!text-[10px] !font-black !uppercase !tracking-widest mt-3" />
                </template>
                <template #actions>
                    <SplitButton :label="t('pages.api.export')" icon="pi pi-download" :model="exportOptions" class="p-button-outlined p-button-secondary bg-slate-800/50 text-white border-slate-700" />
                    <Button :label="t('pages.api.generate_token')" icon="pi pi-key" class="p-button-primary shadow-lg shadow-blue-500/30 font-bold" @click="openCreateTokenWizard" />
                </template>
            </PageHeader>

            <!-- MAIN -->
            <div class="page-container page-section">
                <div class="bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden min-h-[600px] flex flex-col">
                    <TabView v-model:activeIndex="activeTab" class="custom-tabview flex-1 flex flex-col">

                        <!-- ════════════ TAB 1 : DASHBOARD ════════════ -->
                        <TabPanel>
                            <template #header><i class="pi pi-desktop mr-2"></i> {{ t('pages.api.tab_overview') }}</template>
                            <div class="p-6 lg:p-8 space-y-8 bg-slate-50/30">

                                <!-- KPIs -->
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-blue-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-key text-xl"></i></div>
                                            <Badge :value="t('pages.api.tokens_active', { n: stats.active_tokens })" severity="success" class="text-[9px] uppercase tracking-widest" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.api.tokens_issued') }}</h4>
                                            <h2 class="text-4xl font-black text-slate-800">{{ stats.total_tokens }}</h2>
                                            <p v-if="stats.expired_tokens" class="text-[10px] text-red-500 font-bold mt-2"><i class="pi pi-clock mr-1"></i>{{ t('pages.api.expired_count', { n: stats.expired_tokens }) }}</p>
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-blue-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-sort-alt text-xl"></i></div>
                                            <Tag value="Live" severity="info" class="text-[9px]" />
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.api.requests_total') }}</h4>
                                            <h2 class="text-4xl font-black text-slate-800">{{ stats.total_requests.toLocaleString() }}</h2>
                                            <p class="text-[10px] text-slate-500 mt-2 font-bold"><i class="pi pi-arrow-up text-emerald-500 mr-1"></i>{{ t('pages.api.requests_today', { n: stats.requests_today }) }}</p>
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between group hover:border-red-300 transition-colors">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-exclamation-triangle text-xl"></i></div>
                                        </div>
                                        <div class="flex items-center gap-6">
                                            <div>
                                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.api.error_rate') }}</h4>
                                                <h2 class="text-4xl font-black text-slate-800">{{ stats.error_rate }}%</h2>
                                                <p class="text-[10px] text-slate-500 font-bold mt-2">{{ t('pages.api.errors_logged', { n: stats.errors_count }) }}</p>
                                            </div>
                                            <Knob :modelValue="stats.error_rate" :size="60" :valueColor="errorRateColor" rangeColor="#f1f5f9" readonly :strokeWidth="8" :showValue="false" />
                                        </div>
                                    </div>

                                    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800 shadow-lg text-white flex flex-col justify-between relative overflow-hidden">
                                        <i class="pi pi-bolt absolute -right-4 -bottom-4 text-6xl text-white/5"></i>
                                        <div class="relative z-10">
                                            <div class="w-12 h-12 bg-white/10 text-amber-400 backdrop-blur-md rounded-xl flex items-center justify-center mb-4 border border-white/10"><i class="pi pi-clock text-xl"></i></div>
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.api.last_activity') }}</h4>
                                            <h2 class="text-2xl font-black text-white mt-2">{{ stats.last_activity }}</h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Trafic + Top Users -->
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col">
                                        <div class="flex justify-between items-center mb-6">
                                            <div>
                                                <h3 class="text-lg font-black text-slate-800">{{ t('pages.api.traffic_title') }}</h3>
                                                <p class="text-xs text-slate-500 font-medium">{{ t('pages.api.traffic_desc_pre') }} <code>audit_logs</code>.</p>
                                            </div>
                                            <div v-if="methods && Object.keys(methods).length" class="flex gap-2 text-[10px]">
                                                <span v-for="(c, m) in methods" :key="m" class="px-2 py-1 bg-slate-100 rounded font-bold"><span class="text-blue-600">{{ m }}</span> {{ c }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-h-[300px] w-full relative">
                                            <Chart type="line" :data="trafficChartData" :options="chartOptions" class="h-full w-full absolute inset-0" />
                                        </div>
                                    </div>

                                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col">
                                        <div class="mb-6">
                                            <h3 class="text-lg font-black text-slate-800">{{ t('pages.api.top_consumers') }}</h3>
                                            <p class="text-xs text-slate-500 font-medium">{{ t('pages.api.top_consumers_desc') }}</p>
                                        </div>
                                        <ScrollPanel class="flex-1 pr-2" style="height: 280px">
                                            <div v-if="top_users.length === 0" class="text-center text-slate-400 italic py-12 text-sm">{{ t('pages.api.no_active_users') }}</div>
                                            <div v-for="(row, idx) in top_users" :key="idx" class="flex items-center justify-between p-3 mb-2 rounded-2xl bg-slate-50 border border-slate-100 hover:border-blue-200 transition-colors">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <Avatar :label="(row.user?.name || '?').charAt(0).toUpperCase()" shape="circle" class="bg-blue-100 text-blue-600 font-bold" />
                                                    <div class="flex flex-col min-w-0">
                                                        <span class="text-sm font-bold text-slate-800 truncate">{{ row.user?.name || '—' }}</span>
                                                        <span class="text-[9px] text-slate-400 truncate">{{ row.user?.email || '—' }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <span class="block text-sm font-black text-blue-600">{{ row.count.toLocaleString() }}</span>
                                                    <span class="text-[8px] uppercase tracking-widest font-bold text-slate-400">Reqs</span>
                                                </div>
                                            </div>
                                        </ScrollPanel>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- ════════════ TAB 2 : TOKENS ════════════ -->
                        <TabPanel>
                            <template #header><i class="pi pi-key mr-2"></i> {{ t('pages.api.tab_tokens', { n: stats.total_tokens }) }}</template>
                            <div class="p-6 h-full flex flex-col">

                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                    <div>
                                        <h2 class="text-xl font-black text-slate-800">Personal Access Tokens</h2>
                                        <p class="text-sm text-slate-500 font-medium mt-1">{{ t('pages.api.sanctum_keys') }}</p>
                                    </div>
                                    <div class="flex gap-2 w-full md:w-auto">
                                        <IconField iconPosition="left" class="flex-1 md:w-64">
                                            <InputIcon class="pi pi-search text-slate-400" />
                                            <InputText v-model="dtTokensFilters['global'].value" :placeholder="t('pages.api.search_token')" class="w-full bg-slate-50 border-slate-200 rounded-xl focus:bg-white" @keyup.enter="loadTokens()" />
                                        </IconField>
                                        <Button icon="pi pi-filter-slash" class="p-button-rounded p-button-secondary p-button-outlined" v-tooltip.top="t('pages.api.clean_expired')" @click="pruneExpiredTokens" />
                                        <Button icon="pi pi-refresh" class="p-button-rounded p-button-secondary p-button-outlined" v-tooltip.top="t('pages.api.reload')" @click="loadTokens()" />
                                    </div>
                                </div>

                                <div class="flex-1 border border-slate-200 rounded-xl overflow-hidden bg-white">
                                    <DataTable :value="tokens" :paginator="true" :rows="tokensPagination.per_page" :totalRecords="tokensPagination.total" :first="(tokensPagination.current_page - 1) * tokensPagination.per_page" :lazy="true" @page="(e) => loadTokens(e.page + 1)" dataKey="id" :filters="dtTokensFilters" class="custom-datatable p-datatable-lg h-full" :loading="isLoading.tokens" responsiveLayout="scroll">
                                        <template #empty><div class="p-10 text-center text-slate-500 font-medium">{{ t('pages.api.no_tokens') }}</div></template>

                                        <Column field="name" :header="t('pages.api.col_token_name')" sortable style="min-width: 18rem">
                                            <template #body="{data}">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200"><i class="pi pi-code text-xs"></i></div>
                                                    <div class="flex flex-col">
                                                        <span class="font-black text-slate-800 text-sm">{{ data.name }}</span>
                                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5"><i class="pi pi-user text-[9px] mr-1"></i> {{ data.user?.name || '—' }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column :header="t('pages.api.col_abilities')">
                                            <template #body="{data}">
                                                <div class="flex flex-wrap gap-1">
                                                    <Tag v-for="ab in data.abilities" :key="ab" :value="ab" class="bg-blue-50 text-blue-700 border border-blue-100 text-[9px] uppercase font-black" />
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="last_used_at" :header="t('pages.api.col_last_used')" sortable>
                                            <template #body="{data}">
                                                <span v-if="data.last_used_at" class="text-xs font-bold text-slate-600 flex items-center gap-2"><i class="pi pi-clock text-slate-400"></i> {{ new Date(data.last_used_at).toLocaleString(locale) }}</span>
                                                <span v-else class="text-xs text-slate-400 italic">{{ t('pages.api.never_used') }}</span>
                                            </template>
                                        </Column>

                                        <Column field="expires_at" :header="t('pages.api.col_status')" sortable align="center">
                                            <template #body="{data}">
                                                <div v-if="!data.expires_at" class="flex flex-col items-center">
                                                    <Badge :value="t('pages.api.active')" severity="success" class="text-[9px] tracking-widest" />
                                                    <span class="text-[9px] text-slate-400 mt-1">{{ t('pages.api.never_expires') }}</span>
                                                </div>
                                                <div v-else class="flex flex-col items-center">
                                                    <Badge :value="data.is_expired ? t('pages.api.expired') : t('pages.api.active')" :severity="data.is_expired ? 'danger' : 'info'" class="text-[9px] tracking-widest" />
                                                    <span class="text-[9px] text-slate-500 mt-1">{{ t('pages.api.expiry_line', { date: new Date(data.expires_at).toLocaleDateString(locale) }) }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column :header="t('pages.api.col_actions')" alignFrozen="right" frozen style="width: 5rem" align="center">
                                            <template #body="{data}">
                                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger hover:bg-red-50" v-tooltip.left="t('pages.api.revoke')" @click="revokeToken(data)" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- ════════════ TAB 3 : LOGS ════════════ -->
                        <TabPanel>
                            <template #header><i class="pi pi-list mr-2"></i> {{ t('pages.api.tab_logs') }}</template>
                            <div class="p-6 h-full flex flex-col bg-slate-50/50">

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center mb-6 p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                                    <IconField iconPosition="left" class="md:col-span-4">
                                        <InputIcon class="pi pi-search text-slate-400" />
                                        <InputText v-model="logFilters.search" :placeholder="t('pages.api.search_audit')" class="w-full bg-slate-50 border-none focus:ring-0 text-sm" @keyup.enter="loadLogs()" />
                                    </IconField>
                                    <Dropdown v-model="logFilters.method" :options="availableMethods" :placeholder="t('pages.api.http_method')" class="md:col-span-2" showClear @change="loadLogs()" />
                                    <Dropdown v-model="logFilters.level" :options="availableLevels" :placeholder="t('pages.api.level')" class="md:col-span-2" showClear @change="loadLogs()" />
                                    <Calendar v-model="logFilters.date_from" :placeholder="t('pages.api.from_date')" class="md:col-span-2" dateFormat="yy-mm-dd" showIcon @date-select="loadLogs()" />
                                    <Calendar v-model="logFilters.date_to"   :placeholder="t('pages.api.to_date')" class="md:col-span-1" dateFormat="yy-mm-dd" showIcon @date-select="loadLogs()" />
                                    <Button icon="pi pi-filter-slash" class="p-button-secondary p-button-outlined md:col-span-1" v-tooltip.top="t('pages.api.reset')" @click="resetLogFilters" />
                                </div>

                                <div class="flex-1 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                                    <DataTable :value="logs" :paginator="true" :rows="logsPagination.per_page" :totalRecords="logsPagination.total" :first="(logsPagination.current_page - 1) * logsPagination.per_page" :lazy="true" @page="(e) => loadLogs(e.page + 1)" class="custom-datatable p-datatable-sm" stripedRows :loading="isLoading.logs">
                                        <template #empty><div class="p-10 text-center text-slate-500 font-medium">{{ t('pages.api.no_logs') }}</div></template>

                                        <Column field="created_at" :header="t('pages.api.col_timestamp')" sortable style="width: 11rem">
                                            <template #body="{data}"><span class="text-[11px] font-mono font-bold text-slate-500">{{ new Date(data.created_at).toLocaleString(locale) }}</span></template>
                                        </Column>

                                        <Column field="method" :header="t('pages.api.col_method')" style="width: 6rem">
                                            <template #body="{data}"><Tag v-if="data.method" :value="data.method" :severity="getMethodColor(data.method)" class="text-[9px] font-black" /></template>
                                        </Column>

                                        <Column field="event" :header="t('pages.api.col_event')" sortable style="min-width: 14rem">
                                            <template #body="{data}">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-slate-700">{{ data.event }}</span>
                                                    <span v-if="data.description" class="text-[10px] text-slate-400 truncate max-w-xs">{{ data.description }}</span>
                                                </div>
                                            </template>
                                        </Column>

                                        <Column field="level" :header="t('pages.api.col_level')" style="width: 6rem">
                                            <template #body="{data}"><Tag :value="data.level || 'info'" :severity="getLogSeverity(data.level)" class="text-[9px] uppercase font-black" /></template>
                                        </Column>

                                        <Column field="url" :header="t('pages.api.col_endpoint')" style="max-width: 18rem">
                                            <template #body="{data}"><span class="text-xs text-slate-700 font-mono truncate block" v-tooltip.top="data.url">{{ data.url || '—' }}</span></template>
                                        </Column>

                                        <Column field="ip_address" header="IP" style="width: 9rem">
                                            <template #body="{data}"><span v-if="data.ip_address" class="text-[10px] font-mono text-slate-500 bg-slate-100 px-2 py-1 rounded">{{ data.ip_address }}</span></template>
                                        </Column>

                                        <Column field="user" :header="t('pages.api.col_identity')" style="min-width: 12rem">
                                            <template #body="{data}">
                                                <span v-if="data.user" class="text-xs font-bold text-blue-600"><i class="pi pi-user text-[10px] mr-1"></i> {{ data.user.name }}</span>
                                                <span v-else class="text-[10px] text-slate-400 italic">{{ t('pages.api.system') }}</span>
                                            </template>
                                        </Column>

                                        <Column align="center" style="width: 4rem">
                                            <template #body="{data}">
                                                <Button icon="pi pi-code" class="p-button-rounded p-button-text p-button-secondary h-8 w-8" @click="viewLogDetails(data)" v-tooltip.left="t('pages.api.details_json')" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- ════════════ TAB 4 : SETTINGS & HEALTH ════════════ -->
                        <TabPanel>
                            <template #header><i class="pi pi-cog mr-2"></i> {{ t('pages.api.tab_settings') }}</template>
                            <div class="p-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                                    <!-- SETTINGS -->
                                    <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                                        <div class="flex items-center gap-3 mb-8 border-b border-slate-100 pb-4">
                                            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center"><i class="pi pi-sliders-h text-xl"></i></div>
                                            <div>
                                                <h3 class="text-lg font-black text-slate-800">{{ t('pages.api.global_settings') }}</h3>
                                                <p class="text-xs text-slate-500 font-medium">{{ t('pages.api.persisted_in') }} <code>web_settings</code> {{ t('pages.api.persisted_after') }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <div>
                                                    <h4 class="font-bold text-slate-700 text-sm">{{ t('pages.api.enable_api') }}</h4>
                                                    <p class="text-[10px] text-slate-500">{{ t('pages.api.enable_api_desc') }}</p>
                                                </div>
                                                <InputSwitch v-model="settingsForm.api_enabled" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('pages.api.rate_limit') }}</label>
                                                <InputNumber v-model="settingsForm.default_rate_limit" :min="1" :max="10000" :minFractionDigits="0" showButtons buttonLayout="horizontal" inputClass="text-center font-black" class="w-full" />
                                                <small class="text-[10px] text-slate-400">{{ t('pages.api.ddos_protection') }}</small>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('pages.api.token_ttl') }}</label>
                                                <InputNumber v-model="settingsForm.token_expiration_days" :min="0" :max="3650" :suffix="t('pages.api.days_suffix')" showButtons buttonLayout="horizontal" inputClass="text-center font-black" class="w-full" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('pages.api.cors_origins') }}</label>
                                                <InputText v-model="settingsForm.cors_allowed_origins" placeholder="*, https://app.example.com" class="w-full !rounded-xl" />
                                            </div>

                                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                                <div>
                                                    <h4 class="font-bold text-slate-700 text-sm">{{ t('pages.api.audit_logging') }}</h4>
                                                    <p class="text-[10px] text-slate-500">{{ t('pages.api.audit_logging_desc') }}</p>
                                                </div>
                                                <InputSwitch v-model="settingsForm.logging_enabled" />
                                            </div>

                                            <Button :label="t('pages.api.save_changes')" icon="pi pi-save" class="p-button-primary w-full font-black rounded-xl py-3 shadow-md" @click="saveSettings" :loading="settingsForm.processing" />
                                        </div>
                                    </div>

                                    <!-- HEALTH -->
                                    <div class="bg-slate-900 rounded-xl shadow-xl border border-slate-800 flex flex-col overflow-hidden">
                                        <div class="bg-slate-800 p-4 border-b border-slate-700 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                                <span class="ml-3 font-mono text-xs text-slate-400">diagnostic.sh</span>
                                            </div>
                                            <Button icon="pi pi-refresh" class="p-button-rounded p-button-text p-button-secondary text-slate-400 h-8 w-8" @click="runHealthCheck" :loading="healthLoading" v-tooltip.top="t('pages.api.re_diagnose')" />
                                        </div>

                                        <div class="flex-1 p-6 font-mono text-xs leading-relaxed overflow-y-auto bg-black/30 min-h-[300px]">
                                            <div v-for="(log, i) in healthTerminalLogs" :key="i" class="mb-1">
                                                <span class="text-slate-500">[{{ log.time }}]</span>
                                                <span class="text-blue-400 ml-2">sys_check:</span>
                                                <span class="ml-2" :class="{'text-emerald-400': log.type === 'success', 'text-amber-400': log.type === 'warning', 'text-red-400': log.type === 'danger', 'text-slate-300': log.type === 'info'}">
                                                    {{ log.msg }}
                                                </span>
                                            </div>
                                            <div v-if="healthTerminalLogs.length === 0" class="text-slate-500 italic">{{ t('pages.api.click_to_diagnose') }}</div>
                                        </div>

                                        <div class="bg-slate-800 p-4 grid grid-cols-4 divide-x divide-slate-700 text-center">
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-database mb-1 text-slate-400"></i>
                                                <i :class="healthStatus.database ? 'pi pi-check-circle text-emerald-400' : 'pi pi-times-circle text-red-400'" class="text-sm"></i>
                                                <span class="text-[9px] text-slate-500 mt-1 font-bold uppercase tracking-widest">DB</span>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-bolt mb-1 text-slate-400"></i>
                                                <i :class="healthStatus.cache ? 'pi pi-check-circle text-emerald-400' : 'pi pi-times-circle text-red-400'" class="text-sm"></i>
                                                <span class="text-[9px] text-slate-500 mt-1 font-bold uppercase tracking-widest">Cache</span>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-folder mb-1 text-slate-400"></i>
                                                <i :class="healthStatus.storage ? 'pi pi-check-circle text-emerald-400' : 'pi pi-times-circle text-red-400'" class="text-sm"></i>
                                                <span class="text-[9px] text-slate-500 mt-1 font-bold uppercase tracking-widest">{{ t('pages.api.health_storage') }}</span>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <i class="pi pi-server mb-1 text-slate-400"></i>
                                                <Badge :value="healthStatus.status === 'healthy' ? 'ONLINE' : t('pages.api.degraded')" :severity="healthStatus.status === 'healthy' ? 'success' : 'danger'" class="text-[8px]" />
                                                <span class="text-[9px] text-slate-500 mt-1 font-bold uppercase tracking-widest">Gateway</span>
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

        <!-- WIZARD : CREATE TOKEN -->
        <SncDialog v-model:visible="createTokenDialog" :title="t('pages.api.wizard_header')" icon="pi-key" icon-class="from-emerald-500 to-emerald-600" width="650px">
            <div>
                <Steps :model="[{label: t('pages.api.step_information')}, {label: t('pages.api.step_abilities')}, {label: t('pages.api.step_secret')}]" :readonly="true" :activeIndex="tokenStep" class="mb-8 custom-steps hidden md:block" />

                <!-- Step 1 -->
                <div v-if="tokenStep === 0" class="space-y-6 animate-fade-in p-2">
                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex gap-4">
                        <i class="pi pi-shield text-2xl text-blue-500"></i>
                        <p class="text-xs text-blue-800 leading-relaxed font-medium">{{ t('pages.api.token_info_notice') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ t('pages.api.user_label') }}</label>
                        <Dropdown v-model="tokenForm.user_id" :options="users" optionLabel="name" optionValue="id" :placeholder="t('pages.api.choose_user')" class="w-full" filter />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ t('pages.api.app_name') }}</label>
                        <InputText v-model="tokenForm.name" :placeholder="t('pages.api.app_name_placeholder')" class="w-full rounded-xl p-3 font-bold border-slate-300" autofocus />
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ t('pages.api.expiry_optional') }}</label>
                        <Calendar v-model="tokenForm.expires_at" :placeholder="t('pages.api.never_expires_default')" class="w-full rounded-xl" showIcon dateFormat="yy-mm-dd" />
                    </div>
                </div>

                <!-- Step 2 -->
                <div v-else-if="tokenStep === 1" class="space-y-6 animate-fade-in p-2">
                    <div class="space-y-2 mb-4">
                        <h4 class="text-sm font-black text-slate-800">{{ t('pages.api.scopes_title') }}</h4>
                        <p class="text-xs text-slate-500">{{ t('pages.api.scopes_desc') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <div v-for="(ab, idx) in availableAbilities" :key="idx" class="flex items-center p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors" @click="tokenForm.abilities.includes(ab.value) ? tokenForm.abilities = tokenForm.abilities.filter(a => a !== ab.value) : tokenForm.abilities.push(ab.value)">
                            <Checkbox :modelValue="tokenForm.abilities.includes(ab.value)" @update:modelValue="(v) => v ? tokenForm.abilities.push(ab.value) : tokenForm.abilities = tokenForm.abilities.filter(a => a !== ab.value)" :inputId="'ab'+idx" @click.stop />
                            <div class="ml-3 flex flex-col">
                                <label :for="'ab'+idx" class="text-sm font-bold text-slate-700 cursor-pointer">{{ ab.label }}</label>
                                <span class="text-[10px] text-slate-400 font-mono">{{ ab.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div v-else class="py-8 flex flex-col items-center justify-center text-center animate-fade-in">
                    <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center text-4xl mb-6 shadow-sm border border-emerald-100"><i class="pi pi-check"></i></div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">{{ t('pages.api.token_created') }}</h3>
                    <p class="text-sm text-slate-500 mb-8 max-w-sm">{{ t('pages.api.token_created_desc_pre') }}<strong>{{ t('pages.api.token_created_desc_strong') }}</strong>.</p>

                    <div class="w-full bg-slate-900 p-4 rounded-2xl flex items-center gap-3 border border-slate-800 shadow-inner">
                        <code class="flex-1 font-mono text-xs text-emerald-400 break-all select-all text-left">{{ generatedToken }}</code>
                        <Button icon="pi pi-copy" class="p-button-rounded p-button-secondary bg-slate-700 border-none text-white flex-shrink-0" @click="copyToken" v-tooltip.top="t('pages.api.copy')" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full">
                    <Button v-if="tokenStep < 2" :label="t('pages.api.cancel')" class="p-button-text p-button-secondary font-bold" @click="createTokenDialog = false" />
                    <div v-else></div>
                    <Button v-if="tokenStep === 0" :label="t('pages.api.next')" icon="pi pi-arrow-right" iconPos="right" class="p-button-primary font-black rounded-xl px-6" @click="tokenStep++" :disabled="!tokenForm.name.trim() || !tokenForm.user_id" />
                    <Button v-else-if="tokenStep === 1" :label="t('pages.api.generate_key')" icon="pi pi-key" class="p-button-success font-black rounded-xl px-8 shadow-lg shadow-emerald-500/30" @click="generateToken" :loading="tokenForm.processing" :disabled="tokenForm.abilities.length === 0" />
                    <Button v-else :label="t('pages.api.copied_close')" icon="pi pi-check" class="p-button-secondary font-black rounded-xl px-6" @click="createTokenDialog = false" />
                </div>
            </template>
        </SncDialog>

        <!-- SIDEBAR : LOG DETAILS -->
        <Sidebar v-model:visible="logSidebarVisible" position="right" class="w-full md:w-[500px] bg-slate-50">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center"><i class="pi pi-code"></i></div>
                    <h2 class="text-base font-black text-slate-800">{{ t('pages.api.log_detail', { id: selectedLog?.id }) }}</h2>
                </div>
            </template>

            <div v-if="selectedLog" class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ t('pages.api.label_date') }}</p>
                        <p class="text-xs font-bold text-slate-700 mt-1">{{ new Date(selectedLog.created_at).toLocaleString(locale) }}</p>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ t('pages.api.label_method') }}</p>
                        <Tag v-if="selectedLog.method" :value="selectedLog.method" :severity="getMethodColor(selectedLog.method)" class="text-[9px] font-black mt-1" />
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ t('pages.api.label_level') }}</p>
                        <Tag :value="selectedLog.level || 'info'" :severity="getLogSeverity(selectedLog.level)" class="text-[9px] uppercase font-black mt-1" />
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">IP</p>
                        <p class="text-xs font-mono text-slate-700 mt-1">{{ selectedLog.ip_address || '—' }}</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ t('pages.api.label_event') }}</p>
                    <p class="text-sm font-mono font-bold text-slate-800">{{ selectedLog.event }}</p>
                    <p v-if="selectedLog.event_name" class="text-[10px] text-slate-500 font-mono">{{ selectedLog.event_name }}</p>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">URL</p>
                    <p class="text-xs font-mono text-slate-700 break-all">{{ selectedLog.url || '—' }}</p>
                </div>

                <div v-if="selectedLog.description" class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ t('pages.api.label_description') }}</p>
                    <p class="text-xs text-slate-700">{{ selectedLog.description }}</p>
                </div>

                <div v-if="selectedLog.user" class="bg-white p-4 rounded-xl border border-slate-200 flex items-center gap-3">
                    <Avatar :label="selectedLog.user.name.charAt(0).toUpperCase()" shape="circle" class="bg-blue-100 text-blue-600 font-bold" />
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ selectedLog.user.name }}</p>
                        <p class="text-[10px] text-slate-400">{{ selectedLog.user.email }}</p>
                    </div>
                </div>

                <div v-if="selectedLog.metadata" class="bg-slate-900 p-4 rounded-xl">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Metadata (JSON)</p>
                    <pre class="text-[10px] font-mono text-emerald-400 overflow-x-auto whitespace-pre-wrap break-all">{{ typeof selectedLog.metadata === 'string' ? selectedLog.metadata : JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                </div>

                <div v-if="selectedLog.user_agent" class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">User Agent</p>
                    <p class="text-[10px] text-slate-500 break-all">{{ selectedLog.user_agent }}</p>
                </div>
            </div>
        </Sidebar>
    </AppLayout>
</template>

