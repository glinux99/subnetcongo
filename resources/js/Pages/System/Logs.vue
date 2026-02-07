<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
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
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ProgressBar from 'primevue/progressbar';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import Calendar from 'primevue/calendar';
import ScrollPanel from 'primevue/scrollpanel';
import InlineMessage from 'primevue/inlinemessage';
import OverlayPanel from 'primevue/overlaypanel';
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const activeTab = ref(0);
const displayLogDialog = ref(false);
const currentLog = ref(null);
const autoRefresh = ref(true);
const refreshInterval = ref(null);

// --- FILTRES ET OPTIONS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
    channel: { value: null, matchMode: FilterMatchMode.IN },
    date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] }
});

const logLevels = ref([
    { label: 'CRITICAL', color: 'bg-rose-600', severity: 'danger' },
    { label: 'ERROR', color: 'bg-rose-400', severity: 'danger' },
    { label: 'WARNING', color: 'bg-amber-400', severity: 'warning' },
    { label: 'INFO', color: 'bg-indigo-400', severity: 'info' },
    { label: 'DEBUG', color: 'bg-slate-400', severity: 'secondary' }
]);

const channels = ref([
    { label: 'Authentification', value: 'auth' },
    { label: 'Base de données', value: 'database' },
    { label: 'E-mails', value: 'mail' },
    { label: 'Système / Kernel', value: 'system' },
    { label: 'Paiements', value: 'payment' }
]);

// --- MOCK DATA GENERATION ---
const logs = ref([]);
const securityLogs = ref([]);

const generateMockLogs = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const messages = [
            'User login successful',
            'Connection lost with DB cluster',
            'Failed to send certificate email',
            'New course published: Laravel Mastery',
            'Integrity constraint violation at row 452',
            'High CPU usage detected on node-01',
            'Brute force attempt detected from IP 192.168.1.45',
            'API Key sn_live_... authenticated successfully',
            'Cache cleared by administrator'
        ];

        for (let i = 1; i <= 100; i++) {
            const levelIdx = Math.floor(Math.random() * logLevels.value.length);
            const channelIdx = Math.floor(Math.random() * channels.value.length);
            data.push({
                id: i,
                level: logLevels.value[levelIdx].label,
                channel: channels.value[channelIdx].value,
                message: messages[i % messages.length],
                user: i % 3 === 0 ? 'Admin_David' : (i % 5 === 0 ? 'Guest' : 'System'),
                ip: `197.88.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
                timestamp: new Date(Date.now() - (i * 60000)).toLocaleString(),
                payload: '{"request_id": "req_84920", "user_agent": "Mozilla/5.0...", "db_query": "SELECT * FROM users WHERE id=..."}'
            });
        }
        logs.value = data;

        securityLogs.value = data.filter(l => l.channel === 'auth' || l.level === 'CRITICAL');
        loading.value = false;
    }, 1200);
};

// --- CHARTS CONFIG ---
const eventTimelineData = ref({
    labels: ['10h', '11h', '12h', '13h', '14h', '15h', '16h'],
    datasets: [
        {
            label: 'Erreurs',
            data: [2, 5, 12, 4, 8, 15, 3],
            borderColor: '#EF4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4
        },
        {
            label: 'Activités',
            data: [80, 120, 150, 110, 140, 180, 160],
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }
    ]
});

const levelDistributionData = ref({
    labels: ['Critical', 'Error', 'Warning', 'Info'],
    datasets: [{
        data: [5, 15, 25, 55],
        backgroundColor: ['#E11D48', '#FB7185', '#FBBF24', '#818CF8']
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 10 } } } },
    scales: { x: { display: false }, y: { display: false } }
};

// --- ACTIONS ---
const viewLogDetail = (log) => {
    currentLog.value = log;
    displayLogDialog.value = true;
};

const getLevelSeverity = (level) => {
    return logLevels.value.find(l => l.label === level)?.severity || 'info';
};

const clearLogs = () => {
    toast.add({ severity: 'warn', summary: 'Logs nettoyés', detail: 'Les archives de plus de 30 jours ont été purgées.', life: 3000 });
};

const downloadLogs = () => {
    toast.add({ severity: 'success', summary: 'Exportation', detail: 'Le fichier CSV est prêt au téléchargement.', life: 3000 });
};

// --- REAL-TIME REFRESH ---
const startAutoRefresh = () => {
    refreshInterval.value = setInterval(() => {
        if (autoRefresh.value) {
            // Simulation de l'ajout d'un nouveau log
            const newLog = {
                id: Date.now(),
                level: 'INFO',
                channel: 'system',
                message: 'Auto-check: All systems operational',
                user: 'System',
                ip: '127.0.0.1',
                timestamp: new Date().toLocaleString(),
                payload: '{}'
            };
            logs.value = [newLog, ...logs.value.slice(0, 99)];
        }
    }, 5000);
};

onMounted(() => {
    generateMockLogs();
    startAutoRefresh();
});

onUnmounted(() => {
    if (refreshInterval.value) clearInterval(refreshInterval.value);
});

</script>

<template>
    <AppLayout>
        <Head title="Logs & Observabilité - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-slate-900 p-4 rounded-3xl shadow-xl shadow-slate-200 rotate-2">
                        <i class="pi pi-database text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Logs & Santé Système</h1>
                        <p class="text-slate-500 font-medium">Surveillance de l'infrastructure et audit des activités</p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <div class="flex items-center gap-2 px-3">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Auto-Refresh</span>
                        <InputSwitch v-model="autoRefresh" />
                    </div>
                    <Divider layout="vertical" />
                    <Button icon="pi pi-download" class="p-button-text p-button-secondary" v-tooltip.top="'Télécharger CSV'" @click="downloadLogs" />
                    <Button icon="pi pi-trash" class="p-button-text p-button-danger" v-tooltip.top="'Purger les logs'" @click="clearLogs" />
                </div>
            </header>

            <!-- MONITORING KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Total Événements (24h)', val: '124,500', color: 'indigo', icon: 'pi-list', trend: '+5%' },
                    { label: 'Taux d\'Erreur', val: '0.45%', color: 'rose', icon: 'pi-exclamation-circle', trend: '-0.1%' },
                    { label: 'Temps de réponse', val: '112ms', color: 'emerald', icon: 'pi-bolt', trend: 'Optimal' },
                    { label: 'Tentatives Login Échouées', val: '42', color: 'amber', icon: 'pi-shield', trend: 'Attention' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                        <div class="mt-2 text-[10px] font-bold" :class="kpi.color === 'rose' ? 'text-rose-500' : 'text-emerald-500' ">{{ kpi.trend }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500 group-hover:scale-110 transition-transform` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <!-- INFRASTRUCTURE HEALTH -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <!-- System Resource Graph -->
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-4">
                                <span class="text-xl font-black text-slate-800 tracking-tight">Chronologie des Incidents</span>
                                <Badge value="Live Data" severity="info" class="animate-pulse" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[300px] p-2">
                                <Chart type="line" :data="eventTimelineData" :options="{ ...chartOptions, scales: { x: { display: true }, y: { display: true } } }" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Resource Gauges -->
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white h-full relative overflow-hidden">
                        <h3 class="text-xl font-bold mb-8 relative z-10">Statut de <br/><span class="text-emerald-400">l'Infrastructure</span></h3>

                        <div class="space-y-6 relative z-10">
                            <div v-for="res in [
                                { name: 'CPU Load', val: 42, color: '#10B981' },
                                { name: 'Memory Usage', val: 68, color: '#6366F1' },
                                { name: 'Disk Space', val: 15, color: '#F59E0B' }
                            ]" :key="res.name">
                                <div class="flex justify-between text-xs font-bold uppercase mb-2">
                                    <span>{{ res.name }}</span>
                                    <span>{{ res.val }}%</span>
                                </div>
                                <ProgressBar :value="res.val" :showValue="false" style="height: 6px" class="rounded-full bg-slate-800" />
                            </div>
                        </div>

                        <div class="mt-10 flex gap-3 relative z-10">
                            <div class="flex items-center gap-2 bg-slate-800 px-4 py-2 rounded-xl">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-[10px] font-bold">API OK</span>
                            </div>
                            <div class="flex items-center gap-2 bg-slate-800 px-4 py-2 rounded-xl">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-[10px] font-bold">DB OK</span>
                            </div>
                        </div>

                        <!-- Decoration -->
                        <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-indigo-500/10 blur-[100px] rounded-full"></div>
                    </div>
                </div>
            </div>

            <!-- LOGS VIEWER -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-50 p-6">
                <TabView v-model:activeIndex="activeTab" class="custom-logs-tabs">

                    <!-- TAB 1: ALL LOGS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-list"></i>
                                <span>Journal Principal</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable
                                v-model:filters="filters"
                                :value="logs"
                                :paginator="true"
                                :rows="15"
                                dataKey="id"
                                :loading="loading"
                                stripedRows
                                filterDisplay="menu"
                                :globalFilterFields="['message', 'user', 'ip']"
                                class="p-datatable-sm custom-logs-table"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                                        <div class="flex items-center gap-3">
                                            <h2 class="text-xl font-black text-slate-800 tracking-tighter uppercase">Audit Système</h2>
                                            <Tag :value="logs.length" severity="info" />
                                        </div>
                                        <div class="flex gap-2">
                                            <Dropdown v-model="filters['level'].value" :options="logLevels" optionLabel="label" optionValue="label" placeholder="Niveau" class="w-36 rounded-xl" showClear />
                                            <span class="p-input-icon-left w-full md:w-80">
                                                <i class="pi pi-search" />
                                                <InputText v-model="filters['global'].value" placeholder="Rechercher message, IP..." class="w-full rounded-2xl border-slate-200" />
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                <Column field="level" header="NIVEAU" style="width: 8rem">
                                    <template #body="{ data }">
                                        <Tag :value="data.level" :severity="getLevelSeverity(data.level)" class="w-full font-black text-[9px] px-2 py-1" />
                                    </template>
                                </Column>

                                <Column field="timestamp" header="HORODATAGE" sortable style="width: 12rem">
                                    <template #body="{ data }">
                                        <span class="text-[10px] font-bold text-slate-500">{{ data.timestamp }}</span>
                                    </template>
                                </Column>

                                <Column field="channel" header="CANAL" style="width: 8rem">
                                    <template #body="{ data }">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400">{{ data.channel }}</span>
                                    </template>
                                </Column>

                                <Column field="message" header="MESSAGE" style="min-width: 25rem">
                                    <template #body="{ data }">
                                        <span class="font-bold text-slate-700 text-sm truncate max-w-[400px]">{{ data.message }}</span>
                                    </template>
                                </Column>

                                <Column field="user" header="ACTEUR" style="width: 10rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <Avatar icon="pi pi-user" size="small" shape="circle" class="bg-slate-100" />
                                            <span class="text-xs font-bold text-slate-600">{{ data.user }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="ACTION" alignFrozen="right" frozen style="width: 5rem">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-search-plus" class="p-button-text p-button-secondary p-button-rounded" @click="viewLogDetail(data)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: SECURITY LOGS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2 text-rose-500">
                                <i class="pi pi-shield"></i>
                                <span>Sécurité & Alertes</span>
                            </div>
                        </template>
                        <div class="pt-6 grid grid-cols-12 gap-8">
                            <!-- Security Map/Alerts -->
                            <div class="col-span-12 lg:col-span-4">
                                <div class="bg-rose-50 border border-rose-100 p-8 rounded-[2rem] space-y-6">
                                    <div class="flex items-center gap-4">
                                        <div class="p-4 bg-rose-500 rounded-2xl text-white">
                                            <i class="pi pi-bolt text-2xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-rose-900">Alertes Critiques</h3>
                                            <p class="text-xs text-rose-700 font-medium">Révision nécessaire immédiate</p>
                                        </div>
                                    </div>
                                    <Divider />
                                    <div class="space-y-4">
                                        <div v-for="i in 3" :key="i" class="p-4 bg-white rounded-2xl border border-rose-100 shadow-sm flex items-start gap-4">
                                            <i class="pi pi-exclamation-triangle text-rose-500 mt-1"></i>
                                            <div>
                                                <p class="text-xs font-black text-slate-800">Brute Force IP: 197.88.22.{{ i }}</p>
                                                <p class="text-[10px] text-slate-400">Tentatives de login: 15 / 1min</p>
                                                <Button label="Bannir IP" class="p-button-text p-button-danger p-button-sm p-0 mt-2 font-bold" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Table -->
                            <div class="col-span-12 lg:col-span-8">
                                <DataTable :value="securityLogs" :rows="8" paginator class="p-datatable-sm">
                                    <Column field="timestamp" header="HEURE"></Column>
                                    <Column field="message" header="ALERTE SECURITE"></Column>
                                    <Column field="ip" header="ORIGINE IP">
                                        <template #body="{ data }">
                                            <Badge :value="data.ip" severity="danger" />
                                        </template>
                                    </Column>
                                    <Column header="GEOLOC" style="width: 5rem">
                                        <template #body>
                                            <i class="pi pi-map-marker text-rose-300"></i>
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </div>
                    </TabPanel>

                </TabView>
            </div>

            <!-- LOG DETAIL MODAL -->
            <Dialog v-model:visible="displayLogDialog" :modal="true" :style="{ width: '800px' }" header="Détails de l'Événement Système" class="p-fluid log-detail-modal" v-if="currentLog">
                <div class="pt-4">
                    <div class="flex justify-between items-center mb-8 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <div class="flex items-center gap-4">
                            <Tag :value="currentLog.level" :severity="getLevelSeverity(currentLog.level)" class="px-6 py-2 text-lg font-black" />
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Référence Log</span>
                                <span class="font-mono font-bold text-slate-800">#EVT-{{ currentLog.id }}-SUBNET</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black text-slate-400 uppercase">Horodatage</p>
                            <p class="font-bold text-slate-800">{{ currentLog.timestamp }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 lg:col-span-4 space-y-6">
                            <div class="p-6 border border-slate-100 rounded-3xl space-y-4">
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Service Émetteur</span>
                                    <Tag :value="currentLog.channel" severity="info" class="w-full font-bold uppercase tracking-widest" />
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Adresse IP Source</span>
                                    <span class="font-mono text-sm font-bold text-slate-700">{{ currentLog.ip }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Acteur Système</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Avatar icon="pi pi-user" shape="circle" size="small" class="bg-indigo-100 text-indigo-600" />
                                        <span class="text-sm font-bold text-slate-800">{{ currentLog.user }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-900 rounded-3xl text-white overflow-hidden relative">
                                <i class="pi pi-chart-pie absolute -bottom-4 -right-4 text-8xl text-white/5 rotate-12"></i>
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Analyse Contextuelle</h4>
                                <Chart type="doughnut" :data="levelDistributionData" :options="chartOptions" class="h-32" />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
                            <div class="p-6 border border-slate-100 rounded-3xl flex-1">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Message Complet</h4>
                                <div class="p-4 bg-slate-50 border-l-4 border-indigo-500 rounded-r-2xl">
                                    <p class="text-slate-800 font-bold leading-relaxed">{{ currentLog.message }}</p>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-800 rounded-3xl flex-1">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Metadata / Payload JSON</h4>
                                    <Button icon="pi pi-copy" class="p-button-text p-button-white p-button-sm" v-tooltip="'Copier le JSON'" />
                                </div>
                                <ScrollPanel style="width: 100%; height: 180px">
                                    <pre class="text-emerald-400 font-mono text-xs leading-relaxed p-2">
{
  "request_id": "req_84920_subnet",
  "method": "POST",
  "uri": "/api/v1/auth/login",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/121.0.0.0 Safari/537.36",
  "client_ip": "{{ currentLog.ip }}",
  "performance": {
    "execution_time": "45.2ms",
    "db_queries": 4,
    "memory_peak": "12.4MB"
  },
  "context": {
    "site_id": "subnet_kinshasa_01",
    "environment": "production"
  }
}
                                    </pre>
                                </ScrollPanel>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full border-t border-slate-100 pt-6 px-2">
                        <Button label="Signaler cet Incident" icon="pi pi-flag" class="p-button-text p-button-danger font-bold" />
                        <div class="flex gap-2">
                            <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="displayLogDialog = false" />
                            <Button label="Trace Complète (Stack)" icon="pi pi-external-link" class="p-button-indigo px-8 font-black shadow-lg shadow-indigo-100" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Observability Engine v5.0. Système de monitoring intégré.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo Logs */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-logs-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 1rem;
}

:deep(.p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    text-transform: uppercase;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
}

:deep(.p-progressbar) {
    background: #1e293b;
}

:deep(.p-progressbar .p-progressbar-value) {
    border-radius: 10px;
}

/* Tabs UI */
:deep(.custom-logs-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #F1F5F9;
}

:deep(.custom-logs-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94A3B8;
    padding: 1.5rem 2rem;
}

:deep(.custom-logs-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1 !important;
    border-bottom: 2px solid #6366F1 !important;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Transitions */
.p-button-indigo {
    background: #6366F1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4F46E5;
}

/* JSON Formatting */
pre {
    margin: 0;
    white-space: pre-wrap;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

/* Animations */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
