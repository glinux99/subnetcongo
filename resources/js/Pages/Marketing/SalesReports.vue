<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
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
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import ProgressBar from 'primevue/progressbar';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import SelectButton from 'primevue/selectbutton';
import OverlayPanel from 'primevue/overlaypanel';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';

const { t } = useI18n();
const toast = useToast();

// --- STATE MANAGEMENT ---
const loading = ref(true);
const sales = ref([]);
const selectedSales = ref(null);
const displayDetailDialog = ref(false);
const currentTransaction = ref(null);
const dateRange = ref([new Date(new Date().setDate(new Date().getDate() - 30)), new Date()]);
const opExport = ref();

// --- FILTERS & OPTIONS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'course.title': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'student.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },
    category: { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    gateway: { value: null, matchMode: FilterMatchMode.IN }
});

const categories = ref([
    { label: 'Développement Web', value: 'Web Dev' },
    { label: 'Design UI/UX', value: 'Design' },
    { label: 'Business & Marketing', value: 'Business' },
    { label: 'Cybersécurité', value: 'Security' },
    { label: 'Intelligence Artificielle', value: 'AI' }
]);

const gateways = ref([
    { label: 'Stripe', value: 'Stripe' },
    { label: 'PayPal', value: 'PayPal' },
    { label: 'M-Pesa', value: 'M-Pesa' },
    { label: 'Orange Money', value: 'Orange' },
    { label: 'Virement Bancaire', value: 'Wire' }
]);

const statuses = ref(['Completed', 'Pending', 'Refunded', 'Failed']);

// --- MOCK DATA GENERATION ---
const loadSalesData = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const studentNames = ['Alice Vasseur', 'Thomas Legrand', 'Sophie Martin', 'Jean-Luc Mpoyi', 'Mariam Keita', 'David Kimimba', 'Sarah Mwamba'];
        const courseTitles = ['Masterclass Vue 3', 'Laravel Advanced', 'UI/UX Fondamentaux', 'Ethical Hacking 101', 'Python pour Data Science'];
        
        for (let i = 1; i <= 60; i++) {
            const amount = Math.floor(Math.random() * (250 - 29) + 29);
            const statusIdx = Math.floor(Math.random() * 10);
            const gatewayIdx = Math.floor(Math.random() * gateways.value.length);
            
            data.push({
                id: 8000 + i,
                invoice_ref: `INV-2025-${1000 + i}`,
                student: {
                    name: studentNames[i % studentNames.length],
                    email: `user${i}@subnet.cd`,
                    avatar: `https://i.pravatar.cc/150?u=sale${i}`
                },
                course: {
                    title: courseTitles[i % courseTitles.length],
                    category: categories.value[i % categories.value.length].value
                },
                amount: amount,
                tax: (amount * 0.16).toFixed(2), // TVA 16% (RDC)
                net_amount: (amount * 0.84).toFixed(2),
                date: new Date(2025, 1, Math.floor(Math.random() * 28) + 1),
                gateway: gateways.value[gatewayIdx].value,
                status: statusIdx > 8 ? 'Refunded' : (statusIdx > 7 ? 'Pending' : 'Completed'),
                country: 'RDC',
                coupon: i % 5 === 0 ? 'WELCOME25' : null
            });
        }
        sales.value = data;
        loading.value = false;
    }, 1500);
};

// --- CHARTS CONFIGURATION ---
const revenueTrendsData = ref({
    labels: ['Sep', 'Oct', 'Nov', 'Déc', 'Jan', 'Fév'],
    datasets: [
        {
            label: 'Revenu 2024 ($)',
            data: [12400, 15600, 14200, 19800, 22100, 18500],
            borderColor: '#CBD5E1',
            borderDash: [5, 5],
            fill: false,
            tension: 0.4
        },
        {
            label: 'Revenu 2025 ($)',
            data: [18200, 21500, 20400, 28600, 31200, 34500],
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }
    ]
});

const categoryDistributionData = ref({
    labels: ['Web Dev', 'Design', 'Business', 'Security', 'AI'],
    datasets: [{
        data: [45, 20, 15, 10, 10],
        backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } }
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } }
    }
};

// --- COMPUTED VALUES ---
const totalGrossRevenue = computed(() => {
    return sales.value.reduce((acc, sale) => acc + (sale.status === 'Completed' ? sale.amount : 0), 0).toLocaleString();
});

const totalRefunds = computed(() => {
    return sales.value.reduce((acc, sale) => acc + (sale.status === 'Refunded' ? sale.amount : 0), 0).toLocaleString();
});

const averageOrderValue = computed(() => {
    const completed = sales.value.filter(s => s.status === 'Completed');
    return completed.length ? (completed.reduce((acc, s) => acc + s.amount, 0) / completed.length).toFixed(2) : 0;
});

// --- METHODS ---
const onRowSelect = (event) => {
    currentTransaction.value = event.data;
    displayDetailDialog.value = true;
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Completed': return 'success';
        case 'Pending': return 'warning';
        case 'Refunded': return 'danger';
        case 'Failed': return 'secondary';
        default: return null;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(value);
};

const exportCSV = () => {
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'Fichier CSV généré avec succès', life: 3000 });
};

const triggerPrint = () => {
    window.print();
};

const toggleExport = (event) => {
    opExport.value.toggle(event);
};

onMounted(() => {
    loadSalesData();
});
</script>

<template>
    <AppLayout>
        <Head title="Rapports de Vente - SubNetCongo" />
        <Toast />

        <div class="page-bg min-h-screen font-sans pb-20">
            
            <!-- HEADER SECTION -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-emerald-600 p-4 rounded-3xl shadow-xl shadow-emerald-100 -rotate-3 hover:rotate-0 transition-transform cursor-pointer">
                        <i class="pi pi-chart-bar text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Analyse des Revenus</h1>
                        <p class="text-slate-500 font-medium">Suivi financier détaillé et performances des ventes</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Calendar v-model="dateRange" selectionMode="range" :manualInput="false" placeholder="Période" class="w-64 border-none" icon="pi pi-calendar" showIcon />
                    <Divider layout="vertical" class="hidden md:block" />
                    <Button icon="pi pi-download" label="Exporter" class="p-button-text p-button-indigo font-bold" @click="toggleExport" />
                    <OverlayPanel ref="opExport">
                        <div class="flex flex-col gap-2 w-48">
                            <Button label="Format Excel (.xlsx)" icon="pi pi-file-excel" class="p-button-text p-button-secondary text-left" @click="exportCSV" />
                            <Button label="Format PDF (.pdf)" icon="pi pi-file-pdf" class="p-button-text p-button-secondary text-left" @click="triggerPrint" />
                            <Button label="Format CSV (.csv)" icon="pi pi-file" class="p-button-text p-button-secondary text-left" @click="exportCSV" />
                        </div>
                    </OverlayPanel>
                </div>
            </header>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Chiffre d\'Affaire Brut', val: totalGrossRevenue + ' $', color: 'indigo', icon: 'pi-wallet', trend: '+18.4%' },
                    { label: 'Panier Moyen (AOV)', val: averageOrderValue + ' $', color: 'emerald', icon: 'pi-shopping-cart', trend: '+2.1%' },
                    { label: 'Remboursements', val: totalRefunds + ' $', color: 'rose', icon: 'pi-history', trend: '-5.2%' },
                    { label: 'Revenu Net (TVA déduite)', val: (parseFloat(totalGrossRevenue.replace(',','')) * 0.84).toLocaleString() + ' $', color: 'amber', icon: 'pi-percentage', trend: '+15.8%' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[1.5rem] border border-slate-50 shadow-sm hover:shadow-xl transition-all duration-300 group">
                    <div class="flex justify-between items-start mb-6">
                        <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-600 group-hover:scale-110 transition-transform` ">
                            <i :class="`pi ${kpi.icon} text-2xl` "></i>
                        </div>
                        <Badge :value="kpi.trend" :severity="kpi.trend.includes('+') ? 'success' : 'danger'" class="rounded-lg px-2" />
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                    <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                </div>
            </div>

            <!-- ANALYTICS SECTION -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-3xl overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-4">
                                <div>
                                    <span class="text-xl font-black text-slate-800 block">Performance Temporelle</span>
                                    <span class="text-xs text-slate-400 font-medium italic">Comparaison des revenus mensuels 2024 vs 2025</span>
                                </div>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-refresh" class="p-button-rounded p-button-text p-button-secondary" />
                                    <Button icon="pi pi-ellipsis-h" class="p-button-rounded p-button-text p-button-secondary" />
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[380px] p-2">
                                <Chart type="line" :data="revenueTrendsData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <Card class="border-none shadow-sm rounded-3xl h-1/2">
                        <template #title><span class="text-lg font-black text-slate-800 px-4 pt-4">Ventes par Catégorie</span></template>
                        <template #content>
                            <div class="h-[200px] relative">
                                <Chart type="doughnut" :data="categoryDistributionData" :options="{ ...chartOptions, cutout: '70%' }" class="h-full" />
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-3xl font-black text-slate-800">45%</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">WEB DEV</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="border-none shadow-sm rounded-3xl h-1/2 bg-slate-900 text-white overflow-hidden">
                        <template #content>
                            <div class="relative z-10 p-2">
                                <h4 class="text-xl font-bold mb-4">Top Méthodes de <br/><span class="text-emerald-400 font-black italic">Paiement</span></h4>
                                <div class="space-y-4">
                                    <div v-for="g in [
                                        { name: 'M-Pesa / Orange', val: 55, color: 'bg-emerald-500' },
                                        { name: 'Cartes Bancaires', val: 30, color: 'bg-indigo-500' },
                                        { name: 'PayPal', val: 15, color: 'bg-amber-500' }
                                    ]" :key="g.name" class="space-y-1">
                                        <div class="flex justify-between text-xs font-bold uppercase tracking-tighter">
                                            <span>{{ g.name }}</span>
                                            <span class="text-slate-400">{{ g.val }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div :class="`h-full rounded-full ${g.color}` " :style="{ width: g.val + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                                <Button label="Voir tous les gateways" class="p-button-text p-button-sm p-button-white mt-8 px-0 text-xs font-bold" icon="pi pi-arrow-right" iconPos="right" />
                            </div>
                            <!-- Subtle BG decoration -->
                            <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-emerald-500/10 blur-[80px] rounded-full"></div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- SALES LEDGER (DATATABLE) -->
            <Card class="border-none shadow-sm rounded-[1.5rem] overflow-hidden">
                <template #content>
                    <DataTable 
                        v-model:filters="filters"
                        :value="sales" 
                        :paginator="true" 
                        :rows="12" 
                        dataKey="id" 
                        :loading="loading"
                        stripedRows
                        v-model:selection="selectedSales"
                        filterDisplay="menu"
                        :globalFilterFields="['invoice_ref', 'student.name', 'course.title']"
                        class="p-datatable-lg custom-sales-table"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        currentPageReportTemplate="Ligne {first} à {last} sur {totalRecords} transactions"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-6 px-2">
                                <div class="flex items-center gap-4">
                                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter">Journal des Transactions</h2>
                                    <Tag :value="sales.length + ' Entrées'" severity="info" class="px-3" />
                                </div>
                                <div class="flex gap-3 w-full md:w-auto">
                                    <span class="p-input-icon-left flex-1 md:w-80">
                                        <i class="pi pi-search" />
                                        <InputText v-model="filters['global'].value" placeholder="Recherche transaction..." class="w-full rounded-2xl border-slate-200" />
                                    </span>
                                    <Button icon="pi pi-filter" class="p-button-outlined p-button-secondary rounded-2xl border-2" v-tooltip="'Filtres avancés'" />
                                </div>
                            </div>
                        </template>

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column field="invoice_ref" header="RÉFÉRENCE" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-mono font-bold text-slate-500">{{ data.invoice_ref }}</span>
                            </template>
                        </Column>

                        <Column field="student.name" header="CLIENT / ÉLÈVE" sortable style="min-width: 16rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar :image="data.student.avatar" shape="circle" size="medium" />
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800 leading-none mb-1">{{ data.student.name }}</span>
                                        <span class="text-xs text-slate-400 font-medium italic">{{ data.student.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="course.title" header="FORMATION ACQUISE" style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 text-sm truncate max-w-[200px]">{{ data.course.title }}</span>
                                    <span class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">{{ data.course.category }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="date" header="DATE" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <span class="text-sm font-medium text-slate-600">{{ data.date.toLocaleDateString() }}</span>
                            </template>
                        </Column>

                        <Column field="gateway" header="PAIEMENT" style="min-width: 9rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i :class="['pi', data.gateway === 'Wire' ? 'pi-building' : 'pi-credit-card']" class="text-slate-300 text-xs"></i>
                                    <span class="text-xs font-bold text-slate-600 uppercase tracking-tighter">{{ data.gateway }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="amount" header="MONTANT" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <div class="flex flex-col items-end">
                                    <span class="font-black text-slate-800">{{ formatCurrency(data.amount) }}</span>
                                    <span class="text-[9px] font-bold text-slate-400">TVA: {{ data.tax }} $</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" sortable style="min-width: 9rem">
                            <template #body="{ data }">
                                <Badge :value="data.status" :severity="getStatusSeverity(data.status)" class="px-4 py-1 uppercase text-[9px] font-black" />
                            </template>
                        </Column>

                        <Column header="ACTION" alignFrozen="right" frozen style="min-width: 6rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" @click="onRowSelect({data})" />
                            </template>
                        </Column>

                        <ColumnGroup type="footer">
                            <Row>
                                <Column footer="TOTAUX SÉLECTIONNÉS:" :colspan="5" footerStyle="text-align:right; font-weight: 800; color: #64748b" />
                                <Column :footer="formatCurrency(sales.reduce((acc,s) => acc+s.amount, 0))" footerStyle="font-weight: 900; color: #1e293b; text-align: right" />
                                <Column footer="" />
                                <Column footer="" />
                            </Row>
                        </ColumnGroup>
                    </DataTable>
                </template>
            </Card>

            <!-- TRANSACTION DETAIL MODAL -->
            <Dialog v-model:visible="displayDetailDialog" :modal="true" :style="{ width: '750px' }" header="Détails de la Transaction" class="p-fluid sales-detail-modal" v-if="currentTransaction">
                <div class="pt-6">
                    <!-- Invoice Summary Top -->
                    <div class="flex justify-between items-center bg-slate-50 p-6 rounded-3xl border border-slate-100 mb-8">
                        <div>
                            <span class="text-[10px] font-black text-slate-400 uppercase block mb-1">RÉFÉRENCE FACTURE</span>
                            <h2 class="text-2xl font-black text-slate-800">{{ currentTransaction.invoice_ref }}</h2>
                            <p class="text-xs font-bold text-indigo-500 mt-1 uppercase tracking-widest">Transaction {{ currentTransaction.status }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-4xl font-black text-slate-800">{{ formatCurrency(currentTransaction.amount) }}</span>
                            <div class="flex items-center gap-2 justify-end mt-1">
                                <img :src="`/assets/gateways/${currentTransaction.gateway.toLowerCase()}.png`" class="h-4 grayscale" v-if="false" />
                                <span class="text-xs font-bold text-slate-400 italic">via {{ currentTransaction.gateway }}</span>
                            </div>
                        </div>
                    </div>

                    <TabView class="custom-sales-tabs">
                        <TabPanel header="Informations Générales">
                            <div class="grid grid-cols-2 gap-8 py-4">
                                <!-- Student Info -->
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Client / Étudiant</h4>
                                        <div class="flex items-center gap-4 p-4 border border-slate-100 rounded-2xl">
                                            <Avatar :image="currentTransaction.student.avatar" size="xlarge" shape="circle" />
                                            <div>
                                                <p class="font-black text-slate-800">{{ currentTransaction.student.name }}</p>
                                                <p class="text-xs text-slate-500">{{ currentTransaction.student.email }}</p>
                                                <p class="text-[10px] text-slate-400 mt-1">Pays: {{ currentTransaction.country }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Produit</h4>
                                        <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl">
                                            <p class="font-bold text-indigo-700">{{ currentTransaction.course.title }}</p>
                                            <p class="text-xs font-medium text-indigo-400">Accès à vie • Licence Standard</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Breakdown -->
                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Décomposition Financière</h4>
                                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                                        <span class="text-slate-500">Sous-total HT</span>
                                        <span class="font-bold">{{ (currentTransaction.amount * 0.84).toFixed(2) }} $</span>
                                    </div>
                                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                                        <span class="text-slate-500">TVA (16% RDC)</span>
                                        <span class="font-bold">{{ currentTransaction.tax }} $</span>
                                    </div>
                                    <div class="flex justify-between text-sm py-2 border-b border-slate-50" v-if="currentTransaction.coupon">
                                        <span class="text-emerald-500 font-bold">Coupon ({{ currentTransaction.coupon }})</span>
                                        <span class="font-bold text-emerald-600">-10.00 $</span>
                                    </div>
                                    <div class="flex justify-between text-lg pt-4">
                                        <span class="font-black text-slate-800">Total payé TTC</span>
                                        <span class="font-black text-indigo-600">{{ formatCurrency(currentTransaction.amount) }}</span>
                                    </div>
                                    
                                    <div class="mt-8 p-4 bg-amber-50 rounded-2xl flex items-start gap-3 border border-amber-100">
                                        <i class="pi pi-shield text-amber-500 mt-1"></i>
                                        <p class="text-[11px] font-medium text-amber-700 leading-relaxed">
                                            Cette transaction est protégée par la garantie SubNetCongo. Le retrait instructeur sera débloqué après 14 jours de rétractation élève.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel header="Logs Techniques">
                            <div class="space-y-4 py-4 max-h-[300px] overflow-y-auto pr-2">
                                <div v-for="log in [
                                    { time: '12:05:22', action: 'Initiation du checkout par client', ip: '105.15.120.4' },
                                    { time: '12:06:45', action: 'Callback reçu de ' + currentTransaction.gateway, ip: 'System' },
                                    { time: '12:06:46', action: 'Validation signature Webhook', ip: 'Gateway API' },
                                    { time: '12:06:50', action: 'Mise à jour statut commande: COMPLETED', ip: 'System' },
                                    { time: '12:06:52', action: 'Envoi facture mail automatique', ip: 'MailService' }
                                ]" :key="log.time" class="flex gap-4 items-start border-l-2 border-slate-100 pl-4 relative">
                                    <div class="absolute -left-1.5 top-2 w-3 h-3 rounded-full bg-slate-200"></div>
                                    <div class="flex-1">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-[10px] font-black text-slate-400 uppercase">{{ log.time }}</span>
                                            <span class="text-[9px] font-mono text-slate-300">{{ log.ip }}</span>
                                        </div>
                                        <p class="text-sm font-bold text-slate-700">{{ log.action }}</p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>

                <template #footer>
                    <div class="flex justify-between w-full border-t border-slate-50 pt-4 px-2">
                        <Button label="Effectuer un Remboursement" icon="pi pi-refresh" class="p-button-danger p-button-text font-bold" />
                        <div class="flex gap-2">
                            <Button label="Télécharger PDF" icon="pi pi-file-pdf" class="p-button-outlined p-button-secondary font-bold" @click="triggerPrint" />
                            <Button label="Envoyer Facture par Mail" icon="pi pi-envelope" class="p-button-indigo px-8 font-black" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- FOOTER INFO -->
            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Financial Suite v3.2. Système de rapports sécurisé.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Components Custom Styling */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-sales-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 1rem;
}

:deep(.p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #64748B;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.125em;
    padding: 1.5rem 1rem;
    text-transform: uppercase;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-datatable-tbody > tr) {
    transition: all 0.2s ease;
    cursor: pointer;
}

:deep(.p-datatable-tbody > tr:hover) {
    background-color: #F8FAFC !important;
}

:deep(.p-paginator) {
    border: none;
    padding: 2rem;
    background: transparent;
}

:deep(.p-tabview-nav) {
    border-bottom: 2px solid #F1F5F9;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94A3B8;
    padding: 1.25rem 2rem;
    transition: all 0.3s;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1 !important;
    border-bottom: 2px solid #6366F1 !important;
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
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

.p-button-indigo {
    background: #6366F1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4F46E5;
}

/* Animations */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

@media print {
    .p-layout-sidebar, .p-layout-topbar, .p-button, header {
        display: none !important;
    }
    .p-card, .p-datatable {
        box-shadow: none !important;
        border: 1px solid #eee !important;
    }
}
</style>