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
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputSwitch from 'primevue/inputswitch';
import ProgressBar from 'primevue/progressbar';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import InlineMessage from 'primevue/inlinemessage';
import Message from 'primevue/message';
import Avatar from 'primevue/avatar';
import SelectButton from 'primevue/selectbutton';
import Password from 'primevue/password';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const activeTab = ref(0);
const displayGatewayDialog = ref(false);
const displayPayoutDialog = ref(false);
const displayRefundDialog = ref(false);
const submitted = ref(false);

// --- DONNEES DES PASSERELLES ---
const gateways = ref([
    { id: 'mpesa', name: 'Vodacom M-Pesa', type: 'Mobile Money', status: true, country: 'RDC', fee: '2.5%', icon: 'pi-mobile', color: 'bg-red-600' },
    { id: 'orange', name: 'Orange Money', type: 'Mobile Money', status: true, country: 'RDC', fee: '2.5%', icon: 'pi-mobile', color: 'bg-orange-500' },
    { id: 'stripe', name: 'Stripe', type: 'Card / Global', status: true, country: 'Global', fee: '2.9% + 0.30$', icon: 'pi-credit-card', color: 'bg-indigo-600' },
    { id: 'paypal', name: 'PayPal', type: 'Wallet / Global', status: false, country: 'Global', fee: '3.4%', icon: 'pi-paypal', color: 'bg-blue-800' },
    { id: 'wire', name: 'Virement Bancaire', type: 'Manual', status: true, country: 'Global', fee: '0%', icon: 'pi-building', color: 'bg-slate-700' }
]);

// --- SYSTEME DE PAYOUTS (MOCK) ---
const payouts = ref([
    { id: 1, instructor: 'David Kimimba', amount: 1250.00, method: 'M-Pesa', status: 'PENDING', requested_at: '2025-02-05' },
    { id: 2, instructor: 'Sarah Mwamba', amount: 840.50, method: 'Bank Transfer', status: 'PROCESSING', requested_at: '2025-02-06' },
    { id: 3, instructor: 'Jean-Pierre', amount: 2100.00, method: 'Stripe', status: 'PAID', requested_at: '2025-01-28' },
    { id: 4, instructor: 'Marc Levy', amount: 450.00, status: 'FAILED', method: 'PayPal', requested_at: '2025-02-01' }
]);

// --- TRANSACTIONS LOGS (MOCK) ---
const transactions = ref([]);

const initTransactions = () => {
    const data = [];
    const statuses = ['SUCCESS', 'FAILED', 'PENDING', 'REFUNDED'];
    for (let i = 1; i <= 30; i++) {
        data.push({
            id: 9000 + i,
            ref: `TRX-${Date.now()}-${i}`,
            user: `User_${i}`,
            amount: (Math.random() * 150 + 20).toFixed(2),
            gateway: gateways.value[Math.floor(Math.random() * gateways.value.length)].name,
            status: statuses[Math.floor(Math.random() * 4)],
            date: '2025-02-07 14:' + (i < 10 ? '0'+i : i)
        });
    }
    transactions.value = data;
};

// --- CHARTS CONFIG ---
const revenueData = ref({
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
        {
            label: 'Volume Mobile Money',
            data: [4500, 5200, 3800, 6100, 7200, 5000, 4200],
            backgroundColor: '#F97316',
            borderRadius: 8
        },
        {
            label: 'Volume Cartes / Global',
            data: [2100, 1800, 2500, 2200, 3100, 1500, 1200],
            backgroundColor: '#6366F1',
            borderRadius: 8
        }
    ]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } }
};

// --- FORM STATES ---
const selectedGateway = ref(null);
const gatewayConfig = ref({
    api_key: '',
    secret_key: '',
    webhook_url: 'https://api.subnetcongo.cd/v1/payments/webhook',
    is_test_mode: true
});

// --- ACTIONS ---
const configureGateway = (gw) => {
    selectedGateway.ref = gw;
    displayGatewayDialog.value = true;
};

const saveConfig = () => {
    toast.add({ severity: 'success', summary: 'Configuration mise à jour', detail: 'La passerelle est maintenant opérationnelle.', life: 3000 });
    displayGatewayDialog.value = false;
};

const approvePayout = (payout) => {
    payout.status = 'PROCESSING';
    toast.add({ severity: 'info', summary: 'Payout Initié', detail: `Le reversement de ${payout.amount}$ est en cours.`, life: 3000 });
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'SUCCESS': case 'PAID': return 'success';
        case 'PENDING': case 'PROCESSING': return 'warning';
        case 'FAILED': case 'REFUNDED': return 'danger';
        default: return 'info';
    }
};

onMounted(() => {
    initTransactions();
    setTimeout(() => { loading.value = false; }, 1000);
});

</script>

<template>
    <AppLayout>
        <Head title="Configuration des Paiements - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-emerald-600 p-4 rounded-3xl shadow-xl shadow-emerald-100 rotate-2">
                        <i class="pi pi-wallet text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Système de Paiement</h1>
                        <p class="text-slate-500 font-medium">Gérez vos revenus, vos taxes et les reversements instructeurs</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Rapport Fiscal" icon="pi pi-file-pdf" class="p-button-outlined p-button-secondary border-2 font-bold" />
                    <Button label="Nouvelle Passerelle" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" />
                </div>
            </header>

            <!-- FINANCE KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Revenu du Jour', val: '4,150.00 $', color: 'indigo', icon: 'pi-chart-line' },
                    { label: 'Payouts en Attente', val: '3,240.50 $', color: 'amber', icon: 'pi-users' },
                    { label: 'Taux de Succès', val: '97.8%', color: 'emerald', icon: 'pi-check-circle' },
                    { label: 'Remboursements', val: '120.00 $', color: 'rose', icon: 'pi-history' }
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

            <!-- PERFORMANCE ANALYTICS -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-4">
                                <span class="text-xl font-black text-slate-800">Flux de Trésorerie Hebdomadaire</span>
                                <SelectButton :options="['$ USD', 'FC CDF']" modelValue="$ USD" class="p-button-sm" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[320px] p-2">
                                <Chart type="bar" :data="revenueData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-span-12 lg:col-span-4">
                    <Card class="border-none shadow-sm rounded-[2.5rem] h-full flex flex-col justify-center bg-slate-900 text-white p-4">
                        <template #content>
                            <div class="text-center space-y-4">
                                <div class="w-20 h-20 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto">
                                    <i class="pi pi-shield text-emerald-400 text-4xl"></i>
                                </div>
                                <h3 class="text-2xl font-black">Sécurité Financière</h3>
                                <p class="text-slate-400 text-sm">Le cryptage SSL est actif sur toutes les transactions. Vos Webhooks sont protégés par signature HMAC.</p>
                                <Divider class="before:border-slate-700" />
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-slate-800 rounded-2xl">
                                        <p class="text-[10px] text-slate-500 uppercase font-bold">Charge CPU</p>
                                        <p class="text-xl font-black">12%</p>
                                    </div>
                                    <div class="p-4 bg-slate-800 rounded-2xl">
                                        <p class="text-[10px] text-slate-500 uppercase font-bold">Latency</p>
                                        <p class="text-xl font-black">85ms</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- MANAGEMENT TABS -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-50 p-6">
                <TabView v-model:activeIndex="activeTab" class="custom-payment-tabs">

                    <!-- TAB 1: PASSRELLES (GATEWAYS) -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-cog"></i>
                                <span>Passerelles de Paiement</span>
                            </div>
                        </template>
                        <div class="pt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="gw in gateways" :key="gw.id" class="p-6 border border-slate-100 rounded-[2rem] bg-slate-50/50 hover:bg-white hover:shadow-xl transition-all group">
                                <div class="flex justify-between items-start mb-6">
                                    <div :class="`w-14 h-14 rounded-2xl ${gw.color} flex items-center justify-center shadow-lg text-white` ">
                                        <i :class="`pi ${gw.icon} text-2xl` "></i>
                                    </div>
                                    <InputSwitch v-model="gw.status" />
                                </div>
                                <h4 class="text-xl font-black text-slate-800 mb-1">{{ gw.name }}</h4>
                                <div class="flex items-center gap-2 mb-6">
                                    <Tag :value="gw.type" severity="info" class="text-[9px]" />
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ gw.country }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-white rounded-xl mb-6">
                                    <span class="text-xs font-bold text-slate-400 uppercase">Frais plateforme</span>
                                    <span class="font-black text-slate-800">{{ gw.fee }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button label="Configurer" icon="pi pi-sliders-h" class="p-button-outlined p-button-indigo p-button-sm w-full font-bold" @click="configureGateway(gw)" />
                                    <Button icon="pi pi-refresh" class="p-button-outlined p-button-secondary p-button-sm" v-tooltip="'Tester la connexion'" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: PAYOUTS (REVERSEMENTS) -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users"></i>
                                <span>Payouts Instructeurs</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="payouts" class="p-datatable-sm" responsiveLayout="scroll">
                                <template #header>
                                    <div class="flex justify-between items-center mb-4 px-2">
                                        <h3 class="text-lg font-black text-slate-700 uppercase tracking-tighter">Demandes de reversement</h3>
                                        <Button label="Batch Payment" icon="pi pi-check-square" class="p-button-sm p-button-success" />
                                    </div>
                                </template>
                                <Column field="instructor" header="INSTRUCTEUR" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <Avatar icon="pi pi-user" shape="circle" class="bg-indigo-100 text-indigo-600" />
                                            <span class="font-bold text-slate-800">{{ data.instructor }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="amount" header="MONTANT" sortable>
                                    <template #body="{ data }">
                                        <span class="font-black text-slate-800">{{ data.amount.toFixed(2) }} $</span>
                                    </template>
                                </Column>
                                <Column field="method" header="MÉTHODE">
                                    <template #body="{ data }">
                                        <Badge :value="data.method" severity="info" />
                                    </template>
                                </Column>
                                <Column field="status" header="STATUT">
                                    <template #body="{ data }">
                                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] font-black px-3" />
                                    </template>
                                </Column>
                                <Column header="ACTION" style="width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2" v-if="data.status === 'PENDING'">
                                            <Button label="Approuver" icon="pi pi-check" class="p-button-text p-button-success p-button-sm font-bold" @click="approvePayout(data)" />
                                            <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm" />
                                        </div>
                                        <Button v-else label="Voir Détails" icon="pi pi-search" class="p-button-text p-button-secondary p-button-sm" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- TAB 3: TRANSACTION LOGS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-history"></i>
                                <span>Journal Transactionnel</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="transactions" :paginator="true" :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
                                <template #header>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="p-input-icon-left w-full md:w-80">
                                            <i class="pi pi-search" />
                                            <InputText placeholder="Recherche transaction..." class="w-full rounded-xl border-slate-200" />
                                        </span>
                                        <Button icon="pi pi-download" class="p-button-text p-button-secondary" />
                                    </div>
                                </template>
                                <Column field="ref" header="RÉFÉRENCE" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <span class="font-mono text-[10px] text-slate-400">{{ data.ref }}</span>
                                    </template>
                                </Column>
                                <Column field="user" header="UTILISATEUR"></Column>
                                <Column field="amount" header="MONTANT">
                                    <template #body="{ data }">
                                        <span class="font-black text-slate-800">{{ data.amount }} $</span>
                                    </template>
                                </Column>
                                <Column field="gateway" header="SOURCE"></Column>
                                <Column field="status" header="STATUT">
                                    <template #body="{ data }">
                                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] font-black" />
                                    </template>
                                </Column>
                                <Column field="date" header="HORODATAGE"></Column>
                                <Column header="WEBHOOK" style="width: 5rem">
                                    <template #body>
                                        <i class="pi pi-check-circle text-emerald-500"></i>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                </TabView>
            </div>

            <!-- MODAL: CONFIGURE GATEWAY -->
            <Dialog v-model:visible="displayGatewayDialog" :modal="true" :style="{ width: '600px' }" header="⚙️ Configuration de la Passerelle" class="p-fluid payment-modal">
                <div class="space-y-6 pt-4">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center gap-4">
                        <div class="p-4 bg-white rounded-2xl shadow-sm">
                            <i class="pi pi-key text-2xl text-indigo-500"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800">Paramètres API</h4>
                            <p class="text-xs text-slate-400">Ne partagez jamais vos clés secrètes avec des tiers.</p>
                        </div>
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Clé Publique (API Key)</label>
                        <InputText v-model="gatewayConfig.api_key" placeholder="pk_live_..." class="rounded-xl border-2" />
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Clé Secrète (Secret Key)</label>
                        <Password v-model="gatewayConfig.secret_key" :feedback="false" toggleMask placeholder="sk_live_..." class="rounded-xl" />
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Webhook Endpoint</label>
                        <div class="flex gap-2">
                            <InputText v-model="gatewayConfig.webhook_url" readonly class="flex-1 bg-slate-50 border-none font-mono text-xs text-slate-400" />
                            <Button icon="pi pi-copy" class="p-button-text p-button-secondary" />
                        </div>
                    </div>

                    <Divider />

                    <div class="flex items-center justify-between p-4 bg-amber-50 rounded-2xl border border-amber-100">
                        <div>
                            <span class="text-sm font-black text-amber-800">Mode Bac à Sable (Test Mode)</span>
                            <p class="text-[10px] text-amber-600">Activez pour simuler des paiements sans débit réel.</p>
                        </div>
                        <InputSwitch v-model="gatewayConfig.is_test_mode" />
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full pt-4 border-t border-slate-50">
                        <Button label="Réinitialiser" icon="pi pi-refresh" class="p-button-text p-button-danger font-bold" />
                        <div class="flex gap-2">
                            <Button label="Annuler" class="p-button-text p-button-secondary" @click="displayGatewayDialog = false" />
                            <Button label="Enregistrer Configuration" icon="pi pi-save" class="p-button-primary px-8 shadow-lg shadow-indigo-100 font-black" @click="saveConfig" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Financial Core. Paiements sécurisés v4.2.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Tabs UI SubNet Style */
:deep(.custom-payment-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.custom-payment-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    padding: 1.5rem 2rem;
}

:deep(.custom-payment-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
}

:deep(.custom-payment-tabs .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* DataTable Customization */
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

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Transitions */
.p-button-indigo {
    background: #6366f1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4f46e5;
}

/* Gateway Colors Override */
.bg-red-600 { background-color: #E11D48; }
.bg-orange-500 { background-color: #F97316; }
.bg-indigo-600 { background-color: #6366F1; }
.bg-blue-800 { background-color: #1E40AF; }
.bg-slate-700 { background-color: #334155; }
</style>
