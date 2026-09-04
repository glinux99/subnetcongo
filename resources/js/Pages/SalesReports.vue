<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO FINANCE - COMMAND CENTER & SALES REPORTING v40.0
 * ======================================================================================
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// PRIME VUE
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Drawer from 'primevue/drawer';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import DatePicker from 'primevue/datepicker';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    filters: Object,
    summary: Object,
    salesOverTime: Array,
    filterOptions: Object,
    availableReports: Object,
});

const toast = useToast();
const exportMenu = ref();
const loadingTable = ref(false);
const transactionDrawer = ref(false);
const selectedRow = ref(null);

const dateRange = ref([
    props.filters?.start_date ? new Date(props.filters.start_date) : new Date(new Date().setDate(new Date().getDate() - 30)),
    props.filters?.end_date ? new Date(props.filters.end_date) : new Date()
]);

const selectedCurrency = ref('USD');
const selectedReportType = ref('top_students');
const tableData = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const reportTypesList = computed(() => Object.entries(props.availableReports).map(([value, label]) => ({ label, value })));

const fetchReportData = async () => {
    loadingTable.value = true;
    try {
        const response = await axios.post(route('reports.sales.generate'), {
            report_type: selectedReportType.value,
            start_date: dateRange.value[0]?.toISOString(),
            end_date: dateRange.value[1]?.toISOString()
        });
        tableData.value = response.data.payload.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de synchronisation des données financières.', life: 3000 });
    } finally {
        loadingTable.value = false;
    }
};

const kpiStats = computed(() => [
    { label: 'Revenu Net', val: props.summary.revenue.formatted, i: 'pi-wallet', c: 'red', g: props.summary.revenue.growth },
    { label: 'Panier Moyen', val: props.summary.avg_order_value.formatted, i: 'pi-shopping-cart', c: 'emerald', g: props.summary.avg_order_value.growth },
    { label: 'Volume Ventes', val: props.summary.courses_sold.formatted, i: 'pi-book', c: 'amber', g: props.summary.courses_sold.growth },
    { label: 'Remboursements', val: props.summary.refunds_amount.formatted, i: 'pi-replay', c: 'rose', g: props.summary.refunds_amount.growth }
]);

const salesTrendData = computed(() => ({
    labels: props.salesOverTime.map(item => item.date),
    datasets: [
        {
            label: 'Revenu Net',
            data: props.salesOverTime.map(item => item.net_revenue),
            borderColor: '#E11D48',
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(225, 29, 72, 0.05)'
        }
    ]
}));

const dynamicColumns = computed(() => {
    if (!tableData.value.length) return [];
    return Object.keys(tableData.value[0]).map(key => ({
        field: key,
        header: key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')
    }));
});

const applyGlobalFilters = () => fetchReportData();
const viewRowDetails = (data) => { selectedRow.value = data; transactionDrawer.value = true; };

onMounted(fetchReportData);
watch(selectedReportType, fetchReportData);
</script>

<template>
    <AppLayout>
        <Head title="Finance Intelligence Hub | SubnetCongo" />
        <Toast />

        <div class="page-bg min-h-screen font-sans pb-20">
            
            <!-- ── HEADER : FINANCIAL COMMAND (Mesh Gradient Ruby) ── -->
            <header class="bg-[#0F172A] pt-12 pb-28 px-8 relative overflow-hidden">
                <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] bg-red-600/15 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute bottom-[-20%] left-[-5%] w-[400px] h-[400px] bg-red-900/10 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-[1700px] mx-auto relative z-10 flex flex-col lg:flex-row justify-between items-center gap-8">
                    <div class="text-center lg:text-left">
                        <div class="flex items-center justify-center lg:justify-start gap-3 mb-4">
                            <span class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-red-500/20">Audit & Fiscal Intelligence</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic uppercase leading-none">
                            Financial <span class="text-red-600">Command</span> Center
                        </h1>
                        <p class="text-slate-400 mt-4 max-w-xl text-sm font-medium leading-relaxed italic">
                            Analyse des flux de revenus, audit transactionnel et monitoring de la croissance business de l'académie en temps réel.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-4 bg-white/5 p-2 rounded-[1rem] border border-white/10 backdrop-blur-xl">
                        <DatePicker v-model="dateRange" selectionMode="range" placeholder="Période fiscale" class="!bg-transparent !border-none !text-white !w-64" @date-select="applyGlobalFilters" />
                        <div class="w-[1px] h-8 bg-white/10 mx-2"></div>
                        <Button label="Exporter" icon="pi pi-download" @click="exportMenu.toggle($event)" class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-8 !py-3 !text-xs !font-black !uppercase !tracking-widest" />
                        <Menu ref="exportMenu" :model="[{label: 'CSV Data', icon: 'pi pi-file-excel'}, {label: 'PDF Report', icon: 'pi pi-file-pdf'}]" :popup="true" />
                    </div>
                </div>
            </header>

            <main class="max-w-[1700px] mx-auto px-8 -mt-16 relative z-20 space-y-8">
                
                <!-- ── KPI INSIGHTS ── -->
                <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="stat in kpiStats" :key="stat.label" class="bg-white p-8 rounded-[1.5rem] border border-slate-200/60 shadow-sm hover:shadow-2xl transition-all group overflow-hidden relative">
                        <div class="relative z-10">
                            <div :class="`bg-${stat.c}-50 text-${stat.c}-600`" class="w-12 h-12 rounded-xl flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                                <i :class="['pi', stat.i, 'text-xl']"></i>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ stat.label }}</p>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tighter italic">{{ stat.val }}</h2>
                            <Tag :severity="stat.g >= 0 ? 'success' : 'danger'" :value="`${stat.g >= 0 ? '+' : ''}${stat.g}%`" class="!mt-4 !rounded-lg !text-[9px] !font-black" />
                        </div>
                    </div>
                </section>

                <!-- ── ANALYTICS GRID ── -->
                <section class="grid grid-cols-12 gap-8">
                    <!-- Main Revenue Chart -->
                    <div class="col-span-12 lg:col-span-8 bg-white p-10 rounded-[1rem] border border-slate-200/50 shadow-sm">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="w-10 h-[2px] bg-red-600"></span> Performance Transactionnelle
                            </h3>
                            <SelectButton v-model="selectedCurrency" :options="['USD', 'EUR']" class="p-selectbutton-v40" />
                        </div>
                        <div class="h-[400px]">
                            <Chart type="line" :data="salesTrendData" class="h-full" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Payment Distribution -->
                    <div class="col-span-12 lg:col-span-4 bg-[#0F172A] p-10 rounded-[1rem] text-white shadow-2xl relative overflow-hidden">
                        <div class="relative z-10 flex flex-col h-full">
                            <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-10">Gateway Volume</h3>
                            <div class="flex-1 flex flex-col justify-center items-center gap-10">
                                <div class="relative flex items-center justify-center">
                                    <div class="absolute text-center">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase">Audit</p>
                                        <p class="text-xl font-black italic">100%</p>
                                    </div>
                                    <Chart type="doughnut" :data="salesTrendData" :options="{ cutout: '80%', plugins: { legend: { display: false } } }" class="w-48 h-48" />
                                </div>
                                <div class="grid grid-cols-2 gap-3 w-full">
                                    <div v-for="i in 4" :key="i" class="p-4 bg-white/5 rounded-xl border border-white/10 text-center">
                                        <p class="text-[9px] font-black text-slate-500 uppercase">Gateway {{ i }}</p>
                                        <p class="text-xs font-bold text-red-500 tracking-tight">Vérifié</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ── DYNAMIC EXPLORER TABLE ── -->
                <section class="bg-white rounded-[1rem] border border-slate-200/60 shadow-2xl overflow-hidden">
                    <div class="p-10 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-8 bg-slate-50/30">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tighter italic uppercase">Rapports Détaillés</h2>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-2">Exploration de la base de données transactionnelle</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <Select v-model="selectedReportType" :options="reportTypesList" optionLabel="label" optionValue="value" class="!rounded-xl !bg-white !min-w-[250px] !border-slate-200 shadow-sm" />
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher..." class="!rounded-xl !bg-white border-none shadow-inner !w-64" />
                            </IconField>
                        </div>
                    </div>

                    <DataTable :value="tableData" :paginator="true" :rows="10" :filters="filters" :loading="loadingTable" 
                               class="p-datatable-v40" responsiveLayout="scroll">
                        <Column v-for="col of dynamicColumns" :key="col.field" :field="col.field" :header="col.header" sortable>
                            <template #body="{ data }">
                                <div v-if="['student', 'customer'].includes(col.field)" class="flex items-center gap-3">
                                    <Avatar icon="pi pi-user" shape="circle" class="!bg-red-50 !text-red-600 font-black border border-red-100" />
                                    <span class="font-black text-slate-800 italic tracking-tight">{{ data[col.field] }}</span>
                                </div>
                                <span v-else-if="['revenue', 'spent', 'amount'].includes(col.field)" class="font-black text-slate-900 font-mono italic">
                                    {{ data[col.field] }} {{ selectedCurrency }}
                                </span>
                                <Tag v-else-if="col.field === 'status'" :value="data[col.field]" severity="success" class="!text-[9px] !font-black !uppercase !tracking-widest" />
                                <span v-else class="text-xs font-bold text-slate-600 uppercase">{{ data[col.field] }}</span>
                            </template>
                        </Column>
                        <Column header="Audit" alignFrozen="right" frozen>
                            <template #body="{ data }">
                                <Button icon="pi pi-shield" text rounded severity="secondary" @click="viewRowDetails(data)" v-tooltip="'Détails de l\'audit'" />
                            </template>
                        </Column>
                    </DataTable>
                </section>
            </main>
        </div>

        <!-- ── TRANSACTION INSPECTOR DRAWER ── -->
        <Drawer v-model:visible="transactionDrawer" position="right" header="Financial Audit" class="!w-[500px] !rounded-l-[3rem]">
            <div v-if="selectedRow" class="space-y-8 p-4">
                <div class="bg-[#0F172A] p-10 rounded-[1.5rem] text-white relative overflow-hidden shadow-2xl">
                    <div class="absolute -top-10 -right-10 w-48 h-48 bg-red-600/10 rounded-full blur-3xl"></div>
                    <p class="text-[10px] font-black text-red-500 uppercase tracking-[0.3em] mb-4">Inspection ID: #{{ selectedRow.id || 'N/A' }}</p>
                    <h2 class="text-3xl font-black italic tracking-tighter uppercase leading-none">Détails <br/> Transactionnels</h2>
                </div>

                <div class="bg-slate-50 p-8 rounded-[1rem] border border-slate-100 space-y-4">
                    <div v-for="(val, key) in selectedRow" :key="key" class="flex justify-between items-center py-3 border-b border-white last:border-none">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ key.replace(/_/g, ' ') }}</span>
                        <span class="text-sm font-black text-slate-800 italic">{{ val }}</span>
                    </div>
                </div>

                <div class="p-6 bg-red-50 rounded-xl border border-red-100 flex items-center gap-4">
                    <i class="pi pi-info-circle text-red-600"></i>
                    <p class="text-[10px] text-red-900 font-bold leading-relaxed uppercase tracking-wider">Données certifiées par le module fiscal SubnetCongo.</p>
                </div>
            </div>
        </Drawer>
    
    </AppLayout>
</template>

<style scoped>
/* PRESTIGE FINANCE OVERRIDES */
.italic { font-style: italic; }
.tracking-tighter { letter-spacing: -0.05em; }

:deep(.p-datatable-v40 .p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    border-bottom: 2px solid #F1F5F9;
}
:deep(.p-datatable-v40 .p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
}

:deep(.p-selectbutton-v40 .p-button) {
    background: #F1F5F9;
    border: none;
    font-size: 10px;
    font-weight: 900;
    padding: 0.5rem 1.5rem;
    border-radius: 12px;
}
:deep(.p-selectbutton-v40 .p-togglebutton-active) {
    background: #0F172A !important;
    color: white !important;
}

:deep(.p-datepicker) { border-radius: 20px; }
:deep(.p-inputtext:focus) { border-color: #E11D48; box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1); }
</style>