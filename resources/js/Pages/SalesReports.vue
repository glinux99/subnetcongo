<script setup>
/**
 * FINANCIAL COMMAND CENTER & SALES REPORTING - SUBNETCONGO LMS
 * Version: 11.0.0
 * Description: Système d'analyse financière, gestion des revenus et audit des transactions.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Calendar from 'primevue/calendar';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import Breadcrumb from 'primevue/breadcrumb';
import ScrollPanel from 'primevue/scrollpanel';
import Menu from 'primevue/menu';
import OverlayPanel from 'primevue/overlaypanel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Knob from 'primevue/knob';
import Message from 'primevue/message';
import Chip from 'primevue/chip';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const transactionSidebar = ref(false);
const exportMenu = ref();
const dateRange = ref([new Date(new Date().setDate(new Date().getDate() - 30)), new Date()]);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Tableau de Bord' },
    { label: 'Rapports de Ventes', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const paymentGateways = ref([
    { label: 'M-Pesa / Airtel / Orange', value: 'mobile_money', icon: 'pi pi-mobile', color: 'text-amber-500' },
    { label: 'Stripe (International)', value: 'stripe', icon: 'pi pi-credit-card', color: 'text-indigo-500' },
    { label: 'PayPal', value: 'paypal', icon: 'pi pi-paypal', color: 'text-blue-600' },
    { label: 'Virement Bancaire', value: 'bank', icon: 'pi pi-building', color: 'text-slate-500' }
]);

const currencyOptions = ref([
    { label: 'USD ($)', value: 'USD' },
    { label: 'CDF (FC)', value: 'CDF' }
]);
const selectedCurrency = ref('USD');

// --- MOCK DATA: TRANSACTIONS ---
const transactions = ref(Array.from({ length: 30 }, (_, i) => ({
    id: `TXN-${98542 + i}`,
    customer: {
        name: ['Arnaud Ngoy', 'Marie Kabamba', 'Sarah Loft', 'Kevin Ndongala'][i % 4],
        email: `user${i}@subnet.cd`,
        avatar: `https://i.pravatar.cc/150?u=${i}`
    },
    item: ['Laravel Mastery', 'UI/UX Pro', 'Vue.js Advanced', 'CyberSec 101'][i % 4],
    amount: (Math.random() * 200 + 49).toFixed(2),
    gateway: ['mobile_money', 'stripe', 'paypal'][i % 3],
    status: i % 10 === 0 ? 'Refunded' : (i % 8 === 0 ? 'Failed' : 'Completed'),
    date: '2024-02-0' + (i % 9 + 1),
    tax: '16%',
    coupon: i % 5 === 0 ? 'PROMO20' : null
})));

// --- UI STATE ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    gateway: { value: null, matchMode: FilterMatchMode.IN }
});

const selectedTransaction = ref(null);

// --- CHART DATA: SALES TREND ---
const salesTrendData = ref({
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
        {
            label: 'Ventes Actuelles ($)',
            data: [450, 680, 520, 950, 1200, 850, 420],
            borderColor: '#6366F1',
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(99, 102, 241, 0.1)'
        },
        {
            label: 'Période Précédente ($)',
            data: [300, 450, 400, 700, 900, 600, 350],
            borderColor: '#CBD5E1',
            borderDash: [5, 5],
            tension: 0.4
        }
    ]
});

// --- CHART DATA: REVENUE PER CHANNEL ---
const channelDistribution = ref({
    labels: ['Mobile Money', 'Stripe', 'PayPal', 'Bancaire'],
    datasets: [{
        data: [45, 25, 20, 10],
        backgroundColor: ['#F59E0B', '#6366F1', '#2563EB', '#64748B'],
        hoverOffset: 20
    }]
});

// --- METHODS ---
const viewTransactionDetails = (data) => {
    selectedTransaction.value = data;
    transactionSidebar.value = true;
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Completed': return 'success';
        case 'Refunded': return 'warning';
        case 'Failed': return 'danger';
        default: return 'info';
    }
};

const exportData = (type) => {
    toast.add({ severity: 'info', summary: 'Exportation', detail: `Le rapport ${type} est prêt au téléchargement.`, life: 3000 });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: selectedCurrency.value }).format(value);
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- COMPUTED STATS ---
const totalSales = computed(() => transactions.value.filter(t => t.status === 'Completed').reduce((acc, t) => acc + parseFloat(t.amount), 0));
const refundRate = ref(2.4);

</script>

<template>
    <AppLayout>
        <Head title="Rapports de Ventes & Revenus | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 rotate-2 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-chart-line text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Finance & Business Intelligence</h1>
                            <p class="text-slate-500 font-medium italic">Audit financier et analyse de croissance de <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100">
                    <Calendar v-model="dateRange" selectionMode="range" :showIcon="true" class="w-64" placeholder="Filtrer par date" />
                    <SelectButton v-model="selectedCurrency" :options="currencyOptions" optionLabel="label" optionValue="value" class="custom-selectbutton" />
                    <Divider layout="vertical" />
                    <Button label="Exporter Rapports" icon="pi pi-download" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="exportMenu.toggle($event)" />
                    <Menu ref="exportMenu" :model="[ {label: 'Excel (.xlsx)', icon: 'pi pi-file-excel', command: () => exportData('Excel')}, {label: 'PDF (Comptable)', icon: 'pi pi-file-pdf', command: () => exportData('PDF')} ]" :popup="true" />
                </div>
            </header>

            <!-- --- TOP KPI CARDS --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div v-for="stat in [
                    { label: 'Chiffre d\'Affaires Net', val: formatCurrency(totalSales), icon: 'pi-wallet', color: 'indigo', trend: '+15.4% vs mois dernier' },
                    { label: 'Panier Moyen (AOV)', val: formatCurrency(84.50), icon: 'pi-shopping-cart', color: 'emerald', trend: 'Stable' },
                    { label: 'Taux de Remboursement', val: refundRate + '%', icon: 'pi-replay', color: 'rose', trend: '-0.5% amélioration' },
                    { label: 'Nouveaux Clients', val: '142', icon: 'pi-user-plus', color: 'amber', trend: '+22.1% croissance' }
                ]" :key="stat.label" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ stat.label }}</p>
                        <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ stat.val }}</h3>
                        <p class="text-[10px] font-bold mt-4 px-2 py-1 bg-slate-50 rounded-lg inline-block" :class="stat.trend.includes('+') ? 'text-emerald-500' : 'text-slate-400'">
                            <i :class="stat.trend.includes('+') ? 'pi pi-arrow-up' : 'pi pi-minus'" class="text-[8px] mr-1"></i>
                            {{ stat.trend }}
                        </p>
                    </div>
                    <div :class="`absolute -right-4 -bottom-4 text-8xl text-${stat.color}-50 opacity-0 group-hover:opacity-100 transition-opacity` ">
                        <i :class="['pi', stat.icon]"></i>
                    </div>
                </div>
            </div>

            <!-- --- ANALYTICS CHARTS SECTION --- -->
            <div class="grid grid-cols-12 gap-8 mb-12">

                <!-- Main Sales Chart -->
                <div class="col-span-12 lg:col-span-8 bg-white p-10 rounded-[3.5rem] shadow-sm border border-slate-100 h-[500px] flex flex-col">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <h2 class="text-2xl font-black text-slate-800 tracking-tight italic">Courbe de Croissance</h2>
                            <p class="text-sm text-slate-400 font-medium">Revenus quotidiens comparés à la période précédente.</p>
                        </div>
                        <div class="flex gap-2">
                            <Tag value="Objectif : $50k" severity="info" class="rounded-xl px-4" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <Chart type="line" :data="salesTrendData" :options="{ maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }" class="h-full" />
                    </div>
                </div>

                <!-- Channel Distribution -->
                <div class="col-span-12 lg:col-span-4 bg-slate-900 rounded-[3.5rem] p-10 text-white shadow-2xl relative overflow-hidden h-[500px] flex flex-col">
                    <div class="relative z-10 flex flex-col h-full">
                        <h2 class="text-2xl font-black italic mb-2 tracking-tight">Canaux de Paiement</h2>
                        <p class="text-slate-400 text-xs mb-10 uppercase font-bold tracking-widest">Utilisation des passerelles</p>

                        <div class="flex-1 flex flex-col justify-center items-center">
                            <Chart type="doughnut" :data="channelDistribution" :options="{ cutout: '75%', plugins: { legend: { display: false } } }" class="w-full max-w-[220px]" />
                            <div class="mt-10 grid grid-cols-2 gap-4 w-full">
                                <div v-for="gw in paymentGateways" :key="gw.value" class="flex items-center gap-3 bg-white/5 p-3 rounded-2xl border border-white/10">
                                    <i :class="[gw.icon, gw.color, 'text-lg']"></i>
                                    <span class="text-[10px] font-bold uppercase text-slate-300">{{ gw.label.split(' ')[0] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <i class="pi pi-credit-card absolute -right-6 -bottom-6 text-[10rem] text-white/5"></i>
                </div>
            </div>

            <!-- --- TRANSACTIONS TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/20">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Flux des Transactions</h2>
                        <p class="text-sm text-slate-400 font-medium">Historique détaillé des paiements et abonnements.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <MultiSelect v-model="filters['gateway'].value" :options="paymentGateways" optionLabel="label" optionValue="value" placeholder="Passerelles" class="w-64 rounded-xl border-none ring-1 ring-slate-100 shadow-inner" />
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Réf, Client, Produit..." class="w-80 rounded-2xl border-none ring-1 ring-slate-100 focus:ring-2 focus:ring-indigo-600 transition-all shadow-inner" />
                        </IconField>
                    </div>
                </div>

                <DataTable
                    :value="transactions"
                    :paginator="true"
                    :rows="10"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    class="p-datatable-custom"
                    responsiveLayout="stack"
                >
                    <Column field="id" header="Réf. Transaction" style="min-width: 10rem">
                        <template #body="{ data }">
                            <span class="font-mono text-xs font-black text-indigo-600">{{ data.id }}</span>
                        </template>
                    </Column>

                    <Column field="customer.name" header="Client" style="min-width: 16rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :image="data.customer.avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ data.customer.name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ data.customer.email }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="item" header="Produit / Cours" style="min-width: 15rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-tag text-slate-300 text-xs"></i>
                                <span class="text-sm font-medium text-slate-700">{{ data.item }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="amount" header="Montant" sortable>
                        <template #body="{ data }">
                            <span class="font-black text-slate-900">${{ data.amount }}</span>
                        </template>
                    </Column>

                    <Column field="gateway" header="Méthode">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i :class="[paymentGateways.find(g => g.value === data.gateway)?.icon, 'text-slate-400']"></i>
                                <span class="text-[10px] font-bold text-slate-500 uppercase">{{ data.gateway.replace('_', ' ') }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-lg" />
                        </template>
                    </Column>

                    <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-file-pdf" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Facture'" />
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Détails'" @click="viewTransactionDetails(data)" />
                                <Button v-if="data.status === 'Completed'" icon="pi pi-replay" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Rembourser'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- TRANSACTION INSPECTOR SIDEBAR --- -->
        <Sidebar v-model:visible="transactionSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div v-if="selectedTransaction" class="flex flex-col h-full bg-white">

                <!-- Inspector Header -->
                <div class="p-10 bg-slate-900 text-white rounded-b-[4rem] shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-8">
                            <Badge :value="selectedTransaction.status" :severity="getStatusSeverity(selectedTransaction.status)" class="rounded-xl px-4 font-black" />
                            <span class="text-xs text-slate-400 font-mono">{{ selectedTransaction.id }}</span>
                        </div>
                        <h2 class="text-5xl font-black tracking-tighter mb-2">${{ selectedTransaction.amount }}</h2>
                        <p class="text-indigo-400 font-bold uppercase tracking-widest text-xs">Montant total encaissé</p>
                    </div>
                </div>

                <ScrollPanel class="flex-1 p-10">
                    <div class="space-y-10">
                        <!-- Customer Section -->
                        <div class="space-y-6">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-600 rounded-full"></span> Informations Client
                            </h4>
                            <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-3xl border border-slate-100">
                                <Avatar :image="selectedTransaction.customer.avatar" size="xlarge" shape="circle" class="shadow-lg border-4 border-white" />
                                <div>
                                    <p class="text-lg font-black text-slate-800 leading-tight">{{ selectedTransaction.customer.name }}</p>
                                    <p class="text-sm text-slate-500 font-medium">{{ selectedTransaction.customer.email }}</p>
                                    <Button label="Voir le profil étudiant" icon="pi pi-external-link" class="p-button-text p-button-sm p-0 mt-2 font-bold" />
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Billing Details -->
                        <div class="space-y-6">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-4 bg-emerald-600 rounded-full"></span> Détails de Facturation
                            </h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400 font-medium">Article :</span>
                                    <span class="text-slate-800 font-black italic">{{ selectedTransaction.item }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400 font-medium">Date :</span>
                                    <span class="text-slate-800 font-bold">{{ selectedTransaction.date }} à 14:32</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400 font-medium">TVA (DGI RDC) :</span>
                                    <span class="text-slate-800 font-bold">{{ selectedTransaction.tax }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400 font-medium">Code Promo :</span>
                                    <Chip :label="selectedTransaction.coupon || 'Aucun'" class="text-[10px] font-black" />
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-400 font-medium">ID Passerelle :</span>
                                    <code class="text-indigo-600 font-bold bg-indigo-50 px-2 rounded">ch_3N8jYV2eZvKYbx</code>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <!-- Fraud Analysis / Compliance -->
                        <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2.5rem]">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="pi pi-shield text-indigo-500 text-xl"></i>
                                <span class="text-sm font-black text-slate-800 uppercase tracking-widest">Audit Compliance</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <Knob :modelValue="98" :size="60" :readonly="true" valueColor="#10B981" rangeColor="#E2E8F0" />
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Score de confiance élevé</p>
                                    <p class="text-[10px] text-slate-400">IP vérifiée : Kinshasa, RDC. Paiement 3D Secure.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </ScrollPanel>

                <div class="p-10 border-t border-slate-100 flex gap-4 bg-white">
                    <Button label="Imprimer Reçu" icon="pi pi-print" class="flex-1 p-button-outlined rounded-2xl font-bold" />
                    <Button label="Envoyer par Email" icon="pi pi-envelope" class="flex-1 p-button-primary rounded-2xl font-bold shadow-lg shadow-indigo-100" />
                </div>
            </div>
        </Sidebar>

</AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO FINANCIAL ENGINE
 */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 2.5rem 1.5rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #fcfcff !important;
}

/* TabView Overrides */
:deep(.p-tabview .p-tabview-nav) {
    background: transparent;
    padding: 0 2rem;
}

/* Sidebar & ScrollPanel */
:deep(.p-sidebar-custom) {
    border-radius: 5rem 0 0 5rem;
    box-shadow: -30px 0 80px rgba(0,0,0,0.1);
}

:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
}

/* Knob / Progress Custom */
:deep(.p-knob-text) {
    font-weight: 900;
    font-size: 1.2rem;
    fill: #1e293b;
}

:deep(.p-progressbar) {
    background: #f1f5f9;
}

/* SelectButton Customization */
:deep(.custom-selectbutton .p-button) {
    background: white;
    border: 1px solid #f1f5f9;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 0.5rem 1rem;
}

:deep(.custom-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
