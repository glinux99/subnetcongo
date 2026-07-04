<script setup>
/**
 * FINANCIAL COMMAND CENTER & SALES REPORTING - SUBNETCONGO LMS
 * Version: 11.5.0 (Connecté au backend dynamique)
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Sidebar from 'primevue/sidebar';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Calendar from 'primevue/calendar';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import Breadcrumb from 'primevue/breadcrumb';
import ScrollPanel from 'primevue/scrollpanel';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Knob from 'primevue/knob';
import Chip from 'primevue/chip';

// --- INERTIA PROPS ---
const props = defineProps({
    filters: Object,
    summary: Object,
    salesOverTime: Array,
    filterOptions: Object,
    availableReports: Object,
});

// --- INITIALIZATION ---
const toast = useToast();
const exportMenu = ref();
const loadingTable = ref(false);
const transactionSidebar = ref(false);
const selectedRow = ref(null);

// Analyse et initialisation des dates depuis les filtres du backend
const dateRange = ref([
    props.filters?.start_date ? new Date(props.filters.start_date) : new Date(new Date().setDate(new Date().getDate() - 30)),
    props.filters?.end_date ? new Date(props.filters.end_date) : new Date()
]);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Tableau de Bord' },
    { label: 'Rapports de Ventes', class: 'font-bold text-indigo-600' }
]);

// --- UI STATE & DYNAMIC TABLE ---
const currencyOptions = ref([
    { label: 'EUR (€)', value: 'EUR' },
    { label: 'USD ($)', value: 'USD' }
]);
const selectedCurrency = ref('EUR');

const selectedReportType = ref('top_students');
const tableData = ref([]);
const globalFilter = ref('');
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Mapping pour convertir l'objet "availableReports" du backend en tableau pour le Dropdown
const reportTypesList = computed(() => {
    return Object.entries(props.availableReports).map(([value, label]) => ({ label, value }));
});

// --- FETCH REPORT DATA (API) ---
const fetchReportData = async () => {
    loadingTable.value = true;
    try {
        const response = await axios.post(route('reports.sales.generate'), {
            report_type: selectedReportType.value,
            start_date: dateRange.value[0]?.toISOString(),
            end_date: dateRange.value[1]?.toISOString(),
            group_by: 'day'
        });
        tableData.value = response.data.payload.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les données du rapport.', life: 3000 });
    } finally {
        loadingTable.value = false;
    }
};

// --- COMPUTED STATS (FROM BACKEND SUMMARY) ---
const kpiStats = computed(() =>[
    {
        label: 'Chiffre d\'Affaires Net',
        val: props.summary.revenue.formatted,
        icon: 'pi-wallet',
        color: 'indigo',
        trend: `${props.summary.revenue.growth >= 0 ? '+' : ''}${props.summary.revenue.growth}%`,
        positive: props.summary.revenue.growth >= 0
    },
    {
        label: 'Panier Moyen (AOV)',
        val: props.summary.avg_order_value.formatted,
        icon: 'pi-shopping-cart',
        color: 'emerald',
        trend: `${props.summary.avg_order_value.growth >= 0 ? '+' : ''}${props.summary.avg_order_value.growth}%`,
        positive: props.summary.avg_order_value.growth >= 0
    },
    {
        label: 'Cours Vendus',
        val: props.summary.courses_sold.formatted,
        icon: 'pi-book',
        color: 'amber',
        trend: `${props.summary.courses_sold.growth >= 0 ? '+' : ''}${props.summary.courses_sold.growth}%`,
        positive: props.summary.courses_sold.growth >= 0
    },
    {
        label: 'Remboursements',
        val: props.summary.refunds_amount.formatted,
        icon: 'pi-replay',
        color: 'rose',
        // Moins de remboursements = positif, donc la logique est inversée
        trend: `${props.summary.refunds_amount.growth > 0 ? '+' : ''}${props.summary.refunds_amount.growth}%`,
        positive: props.summary.refunds_amount.growth <= 0
    }
]);

// --- CHART DATA: SALES TREND (FROM BACKEND) ---
const salesTrendData = computed(() => {
    return {
        labels: props.salesOverTime.map(item => item.date),
        datasets:[
            {
                label: 'Revenu Net (€)',
                data: props.salesOverTime.map(item => item.net_revenue),
                borderColor: '#6366F1',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(99, 102, 241, 0.1)'
            },
            {
                label: 'Taxes Collectées (€)',
                data: props.salesOverTime.map(item => item.total_tax),
                borderColor: '#CBD5E1',
                borderDash: [5, 5],
                tension: 0.4
            }
        ]
    };
});

// --- CHART DATA: CHANNELS (FETCHED ON MOUNT) ---
const paymentChannelsData = ref([]);
const channelDistribution = computed(() => {
    return {
        labels: paymentChannelsData.value.map(item => item.method),
        datasets:[{
            data: paymentChannelsData.value.map(item => item.volume),
            backgroundColor:['#F59E0B', '#6366F1', '#2563EB', '#64748B', '#10B981'],
            hoverOffset: 20
        }]
    };
});

const fetchPaymentChannels = async () => {
    try {
        const res = await axios.post(route('reports.sales.generate'), {
            report_type: 'sales_by_payment_method',
            start_date: dateRange.value[0]?.toISOString(),
            end_date: dateRange.value[1]?.toISOString(),
        });
        paymentChannelsData.value = res.data.payload.data;
    } catch (e) {
        console.error("Impossible de charger les méthodes de paiement");
    }
};

// --- DYNAMIC COLUMNS RESOLVER ---
const dynamicColumns = computed(() => {
    if (!tableData.value.length) return[];
    // Récupère les clés du premier objet pour créer les colonnes dynamiquement
    const keys = Object.keys(tableData.value[0]);
    return keys.map(key => {
        let header = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
        return { field: key, header: header };
    });
});

// --- METHODS ---
const applyGlobalFilters = () => {
    router.get(route('reports.sales.index'), {
        start_date: dateRange.value[0]?.toISOString(),
        end_date: dateRange.value[1]?.toISOString(),
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            fetchReportData();
            fetchPaymentChannels();
        }
    });
};

const viewRowDetails = (data) => {
    selectedRow.value = data;
    transactionSidebar.value = true;
};

const exportData = (format) => {
    const url = new URL(route('reports.sales.export'), window.location.origin);
    url.searchParams.append('report_type', selectedReportType.value);
    url.searchParams.append('format', format);
    if (dateRange.value[0]) url.searchParams.append('start_date', dateRange.value[0].toISOString());
    if (dateRange.value[1]) url.searchParams.append('end_date', dateRange.value[1].toISOString());

    window.open(url.toString(), '_blank');
    toast.add({ severity: 'success', summary: 'Exportation', detail: `Le rapport ${format.toUpperCase()} est en cours de téléchargement.`, life: 3000 });
};

// Observer les changements du type de rapport pour recharger le tableau
watch(selectedReportType, () => {
    fetchReportData();
});

// Initial Fetch
onMounted(() => {
    fetchReportData();
    fetchPaymentChannels();
});

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

                <div class="flex flex-wrap items-center gap-3 bg-white p-3 rounded-[2rem] shadow-sm border border-slate-100">
                    <Calendar v-model="dateRange" selectionMode="range" :showIcon="true" class="w-64" placeholder="Filtrer par date" @date-select="applyGlobalFilters" />
                    <SelectButton v-model="selectedCurrency" :options="currencyOptions" optionLabel="label" optionValue="value" class="custom-selectbutton hidden md:flex" />
                    <Divider layout="vertical" class="hidden md:block" />
                    <Button label="Exporter" icon="pi pi-download" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="exportMenu.toggle($event)" />
                    <Menu ref="exportMenu" :model="[ {label: 'Exporter en CSV', icon: 'pi pi-file-excel', command: () => exportData('csv')}, {label: 'Exporter en PDF', icon: 'pi pi-file-pdf', command: () => exportData('pdf')} ]" :popup="true" />
                </div>
            </header>

            <!-- --- TOP KPI CARDS --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div v-for="stat in kpiStats" :key="stat.label" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">{{ stat.label }}</p>
                        <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ stat.val }}</h3>
                        <p class="text-[10px] font-bold mt-4 px-2 py-1 rounded-lg inline-block"
                           :class="stat.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                            <i :class="stat.positive ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-[8px] mr-1"></i>
                            {{ stat.trend }} vs préc.
                        </p>
                    </div>
                    <div :class="`absolute -right-4 -bottom-4 text-8xl text-${stat.color}-50 opacity-0 group-hover:opacity-100 transition-opacity`">
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
                            <h2 class="text-2xl font-black text-slate-800 tracking-tight italic">Courbe de Revenus</h2>
                            <p class="text-sm text-slate-400 font-medium">Revenus nets comparés aux taxes collectées.</p>
                        </div>
                        <div class="flex gap-2">
                            <Tag :value="`Commandes : ${props.summary.orders_count.formatted}`" severity="info" class="rounded-xl px-4" />
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
                        <p class="text-slate-400 text-xs mb-10 uppercase font-bold tracking-widest">Répartition des volumes</p>

                        <div class="flex-1 flex flex-col justify-center items-center">
                            <Chart v-if="paymentChannelsData.length" type="doughnut" :data="channelDistribution" :options="{ cutout: '75%', plugins: { legend: { display: false } } }" class="w-full max-w-[220px]" />
                            <div v-else class="text-slate-500 italic text-sm">Données insuffisantes...</div>

                            <div class="mt-10 grid grid-cols-2 gap-4 w-full">
                                <div v-for="gw in paymentChannelsData" :key="gw.method" class="flex flex-col items-center gap-1 bg-white/5 p-3 rounded-2xl border border-white/10 text-center">
                                    <span class="text-[10px] font-bold uppercase text-slate-300">{{ gw.method }}</span>
                                    <span class="text-xs font-black text-indigo-400">{{ gw.volume }} €</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <i class="pi pi-credit-card absolute -right-6 -bottom-6 text-[10rem] text-white/5"></i>
                </div>
            </div>

            <!-- --- DYNAMIC REPORTS TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/20">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Explorateur de Rapports</h2>
                        <p class="text-sm text-slate-400 font-medium">Basculez entre différents types d'analyses détaillées.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Dropdown v-model="selectedReportType" :options="reportTypesList" optionLabel="label" optionValue="value" placeholder="Type de rapport" class="w-72 rounded-xl border-none ring-1 ring-slate-100 shadow-inner font-bold text-slate-700" />
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher..." class="w-64 rounded-2xl border-none ring-1 ring-slate-100 focus:ring-2 focus:ring-indigo-600 transition-all shadow-inner" />
                        </IconField>
                    </div>
                </div>

                <DataTable
                    :value="tableData"
                    :paginator="true"
                    :rows="10"
                    :filters="filters"
                    :globalFilterFields="dynamicColumns.map(c => c.field)"
                    :loading="loadingTable"
                    class="p-datatable-custom"
                    responsiveLayout="stack"
                    emptyMessage="Aucune donnée disponible pour cette période."
                >
                    <!-- Colonnes générées dynamiquement -->
                    <Column v-for="col of dynamicColumns" :key="col.field" :field="col.field" :header="col.header" sortable>
                        <template #body="{ data }">

                            <!-- Template Spécifique pour l'Etudiant -->
                            <div v-if="col.field === 'student' || col.field === 'customer'" class="flex items-center gap-3">
                                <Avatar icon="pi pi-user" shape="circle" size="large" class="bg-indigo-100 text-indigo-600 border-2 border-white shadow-sm" />
                                <span class="font-bold text-slate-800">{{ data[col.field] }}</span>
                            </div>

                            <!-- Template Spécifique pour les montants (revenue, spent, value, amount) -->
                            <span v-else-if="['revenue', 'spent', 'value', 'amount', 'total_spent'].includes(col.field)" class="font-black text-slate-900">
                                {{ data[col.field] }} €
                            </span>

                            <!-- Template Spécifique pour le statut ou les tags -->
                            <Tag v-else-if="col.field === 'status' || col.field === 'payment_method'" :value="data[col.field]" severity="info" class="text-[10px] uppercase font-black px-3 py-1" />

                            <!-- Valeur par défaut -->
                            <span v-else class="text-sm font-medium text-slate-700">{{ data[col.field] }}</span>

                        </template>
                    </Column>

                    <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Détails'" @click="viewRowDetails(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- DETAILS INSPECTOR SIDEBAR --- -->
        <Sidebar v-model:visible="transactionSidebar" position="right" class="w-full md:w-[450px] p-sidebar-custom">
            <div v-if="selectedRow" class="flex flex-col h-full bg-white">
                <div class="p-10 bg-slate-900 text-white rounded-b-[4rem] shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-8">
                            <Badge value="DÉTAILS RAPPORT" severity="info" class="rounded-xl px-4 font-black" />
                        </div>
                        <h2 class="text-3xl font-black tracking-tighter mb-2">Inspecteur de données</h2>
                        <p class="text-indigo-400 font-bold uppercase tracking-widest text-xs">{{ selectedReportType.replace(/_/g, ' ') }}</p>
                    </div>
                </div>

                <ScrollPanel class="flex-1 p-10">
                    <div class="space-y-6">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1 h-4 bg-indigo-600 rounded-full"></span> Informations détaillées
                        </h4>

                        <div class="space-y-4">
                            <!-- Affichage dynamique de toutes les clés/valeurs de la ligne sélectionnée -->
                            <div v-for="(val, key) in selectedRow" :key="key" class="flex justify-between items-center text-sm border-b border-slate-50 pb-2">
                                <span class="text-slate-400 font-medium capitalize">{{ key.replace(/_/g, ' ') }} :</span>
                                <span class="text-slate-800 font-bold max-w-[200px] text-right truncate" :title="val">
                                    {{['revenue', 'spent', 'amount'].includes(key) ? val + ' €' : val }}
                                </span>
                            </div>
                        </div>

                        <Divider />
                        <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2.5rem]">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="pi pi-shield text-indigo-500 text-xl"></i>
                                <span class="text-sm font-black text-slate-800 uppercase tracking-widest">Données vérifiées</span>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-relaxed">Les informations présentées sont extraites directement de la base de données financière sécurisée de SubnetCongo.</p>
                        </div>
                    </div>
                </ScrollPanel>
            </div>
        </Sidebar>
    </AppLayout>
</template>

<style scoped>
/* CUSTOM STYLES - SUBNETCONGO FINANCIAL ENGINE */

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

/* Sidebar & ScrollPanel */
:deep(.p-sidebar-custom) {
    border-radius: 5rem 0 0 5rem;
    box-shadow: -30px 0 80px rgba(0,0,0,0.1);
}

:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
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
