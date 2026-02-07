<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

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
import InputSwitch from 'primevue/inputswitch';
import ProgressBar from 'primevue/progressbar';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import Calendar from 'primevue/calendar';
import CodeEditor from 'primevue/editor'; // Utilisé ici pour simuler un visualiseur JSON
import ScrollPanel from 'primevue/scrollpanel';
import InlineMessage from 'primevue/inlinemessage';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const activeTab = ref(0);
const displayKeyDialog = ref(false);
const displayWebhookDialog = ref(false);
const displayLogDetail = ref(false);
const submitted = ref(false);

// --- DONNEES SIMULEES ---
const apiKeys = ref([]);
const webhooks = ref([]);
const apiLogs = ref([]);
const currentLog = ref(null);

const scopesOptions = ref([
    { label: 'Courses:Read', value: 'courses.read' },
    { label: 'Courses:Write', value: 'courses.write' },
    { label: 'Users:Read', value: 'users.read' },
    { label: 'Users:Manage', value: 'users.manage' },
    { label: 'Sales:Analytics', value: 'sales.view' },
    { label: 'Settings:Admin', value: 'settings.admin' }
]);

const webhookEvents = ref([
    { label: 'Inscription Étudiant', value: 'user.signup' },
    { label: 'Cours Complété', value: 'course.finished' },
    { label: 'Paiement Reçu', value: 'payment.success' },
    { label: 'Certificat Émis', value: 'certificate.generated' }
]);

// --- FORM STATES ---
const newKey = ref({
    name: '',
    scopes: [],
    expires_at: null,
    status: true
});

const newWebhook = ref({
    url: '',
    events: [],
    secret: 'sk_live_67890abcde...',
    status: true
});

// --- CHARTS CONFIG ---
const trafficChartData = ref({
    labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '23:59'],
    datasets: [
        {
            label: 'Requêtes API',
            data: [450, 200, 1200, 2800, 3100, 2400, 1100],
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }
    ]
});

const performanceChartData = ref({
    labels: ['v1/courses', 'v1/users', 'v1/auth', 'v1/enroll', 'v1/media'],
    datasets: [{
        label: 'Latence Moyenne (ms)',
        data: [120, 85, 45, 110, 250],
        backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#6366F1', '#EF4444']
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } }
};

// --- INITIALISATION ---
const initApiDashboard = () => {
    loading.value = true;
    setTimeout(() => {
        // Mock Keys
        apiKeys.value = [
            { id: 1, name: 'Mobile App Integration', prefix: 'sn_live_...', scopes: ['users.read', 'courses.read'], created: '2025-01-10', last_used: 'Il y a 2 min', status: true },
            { id: 2, name: 'Stripe Connector', prefix: 'sn_test_...', scopes: ['sales.view'], created: '2024-12-05', last_used: 'Hier', status: true },
            { id: 3, name: 'Marketing Automation', prefix: 'sn_live_...', scopes: ['users.manage'], created: '2024-11-20', last_used: 'Jamais', status: false }
        ];

        // Mock Webhooks
        webhooks.value = [
            { id: 1, url: 'https://zapier.com/hooks/123', events: ['user.signup', 'payment.success'], last_delivery: 'Success (200)', status: true },
            { id: 2, url: 'https://crm.subnet.cd/webhook', events: ['course.finished'], last_delivery: 'Failed (500)', status: false }
        ];

        // Mock Logs
        const methods = ['GET', 'POST', 'PUT', 'DELETE'];
        const endpoints = ['/v1/courses', '/v1/users/45', '/v1/auth/login', '/v1/media/upload'];
        for (let i = 0; i < 20; i++) {
            apiLogs.value.push({
                id: i,
                method: methods[Math.floor(Math.random() * 4)],
                endpoint: endpoints[Math.floor(Math.random() * 4)],
                status: Math.random() > 0.8 ? 500 : (Math.random() > 0.1 ? 200 : 401),
                duration: Math.floor(Math.random() * 300) + 'ms',
                time: '14:20:' + (i < 10 ? '0' + i : i),
                ip: '197.12.88.' + i,
                payload: '{"id": 45, "action": "sync"}'
            });
        }
        loading.value = false;
    }, 1500);
};

// --- ACTIONS ---
const createKey = () => {
    submitted.value = true;
    if (newKey.value.name) {
        toast.add({ severity: 'success', summary: 'Clé API générée', detail: 'Veuillez copier la clé, elle ne sera plus visible.', life: 5000 });
        displayKeyDialog.value = false;
    }
};

const viewLogDetail = (log) => {
    currentLog.value = log;
    displayLogDetail.value = true;
};

const getStatusSeverity = (status) => {
    if (status >= 200 && status < 300) return 'success';
    if (status >= 400 && status < 500) return 'warning';
    return 'danger';
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'info', summary: 'Copié', detail: 'Élément copié dans le presse-papier', life: 2000 });
};

onMounted(() => {
    initApiDashboard();
});

</script>

<template>
    <AppLayout>
        <Head title="API & Developer Console - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-slate-900 p-4 rounded-3xl shadow-xl shadow-slate-200">
                        <i class="pi pi-code text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Console Développeur</h1>
                        <p class="text-slate-500 font-medium">Gérez vos API, Webhooks et surveillez l'écosystème technique</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Documentation API" icon="pi pi-book" class="p-button-outlined p-button-secondary border-2 font-bold" />
                    <Button label="Générer une Clé" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" @click="displayKeyDialog = true" />
                </div>
            </header>

            <!-- KPI MONITORING -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Requêtes (24h)', val: '42,850', color: 'indigo', icon: 'pi-cloud-upload' },
                    { label: 'Taux de Succès', val: '99.4%', color: 'emerald', icon: 'pi-check-circle' },
                    { label: 'Latence Moyenne', val: '114ms', color: 'amber', icon: 'pi-bolt' },
                    { label: 'Erreurs Actives', val: '14', color: 'rose', icon: 'pi-exclamation-circle' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS VISUALS -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-4">
                                <span class="text-xl font-black text-slate-800">Trafic API en Temps Réel</span>
                                <Tag value="Live" severity="success" class="animate-pulse" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[320px] p-2">
                                <Chart type="line" :data="trafficChartData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-span-12 lg:col-span-4">
                    <Card class="border-none shadow-sm rounded-[2.5rem] h-full">
                        <template #title><span class="text-xl font-black text-slate-800 px-4 pt-4">Latence par Endpoint</span></template>
                        <template #content>
                            <div class="h-[320px] p-2">
                                <Chart type="bar" :data="performanceChartData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- TABS MANAGEMENT -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-50 p-6">
                <TabView v-model:activeIndex="activeTab" class="custom-api-tabs">

                    <!-- TAB 1: API KEYS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-key"></i>
                                <span>Clés d'Accès</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="apiKeys" class="p-datatable-sm">
                                <template #header>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-black text-slate-700 uppercase tracking-tighter">Gestion des identifiants</h3>
                                        <div class="p-input-icon-left">
                                            <i class="pi pi-search" />
                                            <InputText placeholder="Rechercher une clé..." class="rounded-xl" />
                                        </div>
                                    </div>
                                </template>
                                <Column field="name" header="NOM DU SERVICE" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col">
                                            <span class="font-black text-slate-800">{{ data.name }}</span>
                                            <span class="text-[10px] font-mono text-slate-400">{{ data.prefix }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="scopes" header="DROITS (SCOPES)" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-wrap gap-1">
                                            <Tag v-for="s in data.scopes" :key="s" :value="s" class="text-[9px] bg-slate-100 text-slate-600 border border-slate-200" />
                                        </div>
                                    </template>
                                </Column>
                                <Column field="last_used" header="DERNIER USAGE"></Column>
                                <Column field="status" header="STATUT">
                                    <template #body="{ data }">
                                        <InputSwitch v-model="data.status" />
                                    </template>
                                </Column>
                                <Column header="ACTION" style="width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-refresh" class="p-button-text p-button-secondary p-button-sm p-button-rounded" v-tooltip="'Révoquer & Regénérer'" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm p-button-rounded" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: WEBHOOKS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-external-link"></i>
                                <span>Webhooks</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div v-for="wh in webhooks" :key="wh.id" class="p-6 border border-slate-100 rounded-3xl bg-slate-50/50 hover:bg-white hover:shadow-xl transition-all group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-slate-400 uppercase mb-1">Payload URL</span>
                                            <h4 class="font-mono text-sm text-indigo-600 truncate max-w-[300px]">{{ wh.url }}</h4>
                                        </div>
                                        <InputSwitch v-model="wh.status" />
                                    </div>
                                    <div class="flex flex-wrap gap-2 mb-6">
                                        <Badge v-for="ev in wh.events" :key="ev" :value="ev" severity="info" class="text-[9px]" />
                                    </div>
                                    <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                        <div class="text-[10px] font-bold" :class="wh.last_delivery.includes('Success') ? 'text-emerald-500' : 'text-rose-500' ">
                                            Dernier envoi: {{ wh.last_delivery }}
                                        </div>
                                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Button icon="pi pi-cog" class="p-button-text p-button-secondary p-button-sm" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" />
                                        </div>
                                    </div>
                                </div>
                                <div class="border-2 border-dashed border-slate-200 rounded-3xl flex flex-col items-center justify-center p-10 hover:bg-slate-50 cursor-pointer transition-colors" @click="displayWebhookDialog = true">
                                    <i class="pi pi-plus text-slate-300 text-3xl mb-4"></i>
                                    <span class="font-black text-slate-400">Ajouter un Endpoint</span>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 3: API LOGS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-history"></i>
                                <span>Logs des Requêtes</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="apiLogs" :paginator="true" :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
                                <Column field="time" header="HEURE" style="width: 8rem"></Column>
                                <Column field="method" header="MÉTHODE" style="width: 8rem">
                                    <template #body="{ data }">
                                        <span :class="`font-black px-2 py-0.5 rounded text-[10px] ${data.method === 'GET' ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600'}` ">
                                            {{ data.method }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="endpoint" header="ENDPOINT" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <span class="font-mono text-xs text-slate-600">{{ data.endpoint }}</span>
                                    </template>
                                </Column>
                                <Column field="status" header="STATUT">
                                    <template #body="{ data }">
                                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="font-bold" />
                                    </template>
                                </Column>
                                <Column field="duration" header="DURÉE"></Column>
                                <Column field="ip" header="CLIENT IP"></Column>
                                <Column header="ACTION" style="width: 5rem">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-eye" class="p-button-text p-button-secondary p-button-sm" @click="viewLogDetail(data)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                </TabView>
            </div>

            <!-- MODAL: GENERATE API KEY -->
            <Dialog v-model:visible="displayKeyDialog" :modal="true" :style="{ width: '550px' }" header="🔑 Nouvelle Clé API" class="p-fluid api-modal">
                <div class="space-y-6 pt-4">
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Nom de l'application / service</label>
                        <InputText v-model="newKey.name" placeholder="ex: Application Mobile iOS" class="rounded-xl border-2" />
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Étendue des droits (Scopes)</label>
                        <MultiSelect v-model="newKey.scopes" :options="scopesOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner les droits..." display="chip" class="rounded-xl" />
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Date d'expiration</label>
                        <Calendar v-model="newKey.expires_at" showIcon class="rounded-xl" />
                    </div>

                    <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100 flex items-start gap-4">
                        <i class="pi pi-shield text-2xl text-amber-500 mt-1"></i>
                        <p class="text-xs font-medium text-amber-700 leading-relaxed">
                            Pour des raisons de sécurité, la clé secrète ne sera affichée qu'une seule fois. Si vous la perdez, vous devrez la révoquer et en générer une nouvelle.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-end gap-2 border-t border-slate-50 pt-4">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="displayKeyDialog = false" />
                        <Button label="Générer la Clé" icon="pi pi-check" class="p-button-primary px-8 shadow-lg shadow-indigo-100 font-black" @click="createKey" />
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: LOG DETAIL -->
            <Dialog v-model:visible="displayLogDetail" :modal="true" :style="{ width: '700px' }" header="🔍 Détails de la Requête" class="p-fluid" v-if="currentLog">
                <div class="space-y-6 pt-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Request ID</span>
                            <span class="font-mono text-xs font-bold">req_48293049284</span>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-right">
                            <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">Status</span>
                            <Tag :value="currentLog.status" :severity="getStatusSeverity(currentLog.status)" />
                        </div>
                    </div>

                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab header="URL & Headers">
                            <div class="bg-slate-900 p-4 rounded-xl text-indigo-300 font-mono text-xs overflow-x-auto">
                                <p><span class="text-white">URL:</span> https://api.subnetcongo.cd{{ currentLog.endpoint }}</p>
                                <p><span class="text-white">METHOD:</span> {{ currentLog.method }}</p>
                                <p class="mt-2 text-slate-500">// Headers</p>
                                <p>Authorization: Bearer sn_live_**********</p>
                                <p>Content-Type: application/json</p>
                                <p>User-Agent: SubNet-Mobile-Client/1.2</p>
                            </div>
                        </AccordionTab>
                        <AccordionTab header="Payload (Request)">
                            <div class="bg-slate-900 p-4 rounded-xl text-emerald-400 font-mono text-xs overflow-x-auto">
                                <pre>{{ currentLog.payload }}</pre>
                            </div>
                        </AccordionTab>
                        <AccordionTab header="Response (JSON)">
                            <div class="bg-slate-900 p-4 rounded-xl text-amber-400 font-mono text-xs overflow-x-auto">
                                <pre>{ "status": "success", "data": { "sync_completed": true } }</pre>
                            </div>
                        </AccordionTab>
                    </Accordion>
                </div>
            </Dialog>

            <!-- FOOTER INFO -->
            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Backend Infrastructure. API Gateway v4.5.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom TabView for API Console */
:deep(.custom-api-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.custom-api-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    padding: 1.5rem 2rem;
}

:deep(.custom-api-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
}

:deep(.custom-api-tabs .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* DataTable SubNet Design */
:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 0;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9/50 !important;
}

/* Accordion Custom Styling */
:deep(.p-accordion-header-link) {
    background: #f8fafc !important;
    border-radius: 1rem !important;
    font-weight: 700 !important;
    color: #334155 !important;
}

:deep(.p-accordion-content) {
    background: transparent !important;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Animations & Transitions */
.p-button-indigo {
    background: #6366f1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4f46e5;
}

/* Code Pre Formatting */
pre {
    margin: 0;
    white-space: pre-wrap;
}
</style>
