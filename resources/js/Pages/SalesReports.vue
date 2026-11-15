<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO FINANCE - COMMAND CENTER & SALES REPORTING v40.0
 * ======================================================================================
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
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
import DatePicker from 'primevue/datepicker';
import Chart from 'primevue/chart';
import SelectButton from 'primevue/selectbutton';
import Menu from 'primevue/menu';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
const rowMenu = ref(null);
const rowMenuTarget = ref(null);

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
        toast.add({ severity: 'error', summary: t('pages.sales_reports.errorSummary'), detail: t('pages.sales_reports.errorDetail'), life: 3000 });
    } finally {
        loadingTable.value = false;
    }
};

const kpiStats = computed(() => [
    { label: t('pages.sales_reports.kpiNetRevenue'), val: props.summary.revenue.formatted, i: 'pi-wallet', c: 'red', g: props.summary.revenue.growth },
    { label: t('pages.sales_reports.kpiAvgOrder'), val: props.summary.avg_order_value.formatted, i: 'pi-shopping-cart', c: 'emerald', g: props.summary.avg_order_value.growth },
    { label: t('pages.sales_reports.kpiSalesVolume'), val: props.summary.courses_sold.formatted, i: 'pi-book', c: 'amber', g: props.summary.courses_sold.growth },
    { label: t('pages.sales_reports.kpiRefunds'), val: props.summary.refunds_amount.formatted, i: 'pi-replay', c: 'rose', g: props.summary.refunds_amount.growth }
]);

const salesTrendData = computed(() => ({
    labels: props.salesOverTime.map(item => item.date),
    datasets: [
        {
            label: t('pages.sales_reports.kpiNetRevenue'),
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

// Colonnes dynamiques : la 1re colonne "identité" (student/customer) reste visible sous md,
// tout le reste est replie derriere md/lg. Si aucun champ identite n'existe, la 1re colonne est gardee.
const IDENTITY_FIELDS = ['student', 'customer'];
const MONEY_FIELDS = ['revenue', 'spent', 'amount'];
const dynamicColumnClass = (field, position) => {
    const keys = Object.keys(tableData.value[0] ?? {});
    const hasIdentity = IDENTITY_FIELDS.some((f) => keys.includes(f));
    if (IDENTITY_FIELDS.includes(field)) return '';
    if (!hasIdentity && position === 0) return '';
    if (field === 'status' || MONEY_FIELDS.includes(field)) return 'hidden md:table-cell';
    return 'hidden lg:table-cell';
};

const toggleRowMenu = (event, row) => {
    rowMenuTarget.value = row;
    rowMenu.value?.toggle(event);
};

const rowMenuItems = computed(() => [
    { label: t('pages.sales_reports.auditTooltip'), icon: 'pi pi-shield', command: () => viewRowDetails(rowMenuTarget.value) },
]);

onMounted(fetchReportData);
watch(selectedReportType, fetchReportData);
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.sales_reports.headTitle')" />

        <div class="page-bg min-h-screen font-sans pb-20">
            
            <!-- ── HEADER : FINANCIAL COMMAND ── -->
            <PageHeader
                :title="`${t('pages.sales_reports.titlePart1')} ${t('pages.sales_reports.titleHighlight')} ${t('pages.sales_reports.titlePart2')}`"
                :subtitle="t('pages.sales_reports.subtitle')"
                :badge="t('pages.sales_reports.badge')"
                icon="pi-wallet"
                accent="emerald"
            >
                <template #actions>
                    <DatePicker v-model="dateRange" selectionMode="range" :placeholder="t('pages.sales_reports.periodPlaceholder')" class="!bg-white/10 !border !border-white/20 !text-white" @date-select="applyGlobalFilters" />
                    <Button :label="t('pages.sales_reports.export')" icon="pi pi-download" @click="exportMenu.toggle($event)" class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-6 !py-3 !text-xs !font-black !uppercase !tracking-widest" />
                    <Menu ref="exportMenu" :model="[{label: t('pages.sales_reports.exportCsv'), icon: 'pi pi-file-excel'}, {label: t('pages.sales_reports.exportPdf'), icon: 'pi pi-file-pdf'}]" :popup="true" />
                    <Menu id="report-row-menu" ref="rowMenu" :model="rowMenuItems" popup :aria-label="t('pages.live_sessions.menu_more')" />
                </template>
            </PageHeader>

            <div class="page-container page-section">

                <!-- ── KPI INSIGHTS ── -->
                <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                    <PageKpi v-for="stat in kpiStats" :key="stat.label"
                        :label="stat.label" :value="stat.val" :icon="stat.i" :color="stat.c" :trend="stat.g" />
                </section>

                <!-- ── ANALYTICS GRID ── -->
                <section class="grid grid-cols-12 gap-8">
                    <!-- Main Revenue Chart -->
                    <div class="col-span-12 lg:col-span-8 bg-white p-4 sm:p-10 rounded-[1rem] border border-slate-200/50 shadow-sm">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.3em] flex items-center gap-3">
                                <span class="w-10 h-[2px] bg-red-600"></span> {{ t('pages.sales_reports.transactionalPerformance') }}
                            </h3>
                            <SelectButton v-model="selectedCurrency" :options="['USD', 'EUR']" class="p-selectbutton-v40" />
                        </div>
                        <div class="h-[400px]">
                            <Chart type="line" :data="salesTrendData" class="h-full" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Payment Distribution -->
                    <div class="col-span-12 lg:col-span-4 bg-[#0F172A] p-4 sm:p-10 rounded-[1rem] text-white shadow-2xl relative overflow-hidden">
                        <div class="relative z-10 flex flex-col h-full">
                            <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-10">{{ t('pages.sales_reports.gatewayVolume') }}</h3>
                            <div class="flex-1 flex flex-col justify-center items-center gap-10">
                                <div class="relative flex items-center justify-center">
                                    <div class="absolute text-center">
                                        <p class="text-[10px] font-black text-slate-500 uppercase">{{ t('pages.sales_reports.auditLabel') }}</p>
                                        <p class="text-xl font-black italic">100%</p>
                                    </div>
                                    <Chart type="doughnut" :data="salesTrendData" :options="{ cutout: '80%', plugins: { legend: { display: false } } }" class="w-48 h-48" />
                                </div>
                                <div class="grid grid-cols-2 gap-3 w-full">
                                    <div v-for="i in 4" :key="i" class="p-4 bg-white/5 rounded-xl border border-white/10 text-center">
                                        <p class="text-[9px] font-black text-slate-500 uppercase">{{ t('pages.sales_reports.gatewayLabel', { n: i }) }}</p>
                                        <p class="text-xs font-bold text-red-500 tracking-tight">{{ t('pages.sales_reports.verified') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ── DYNAMIC EXPLORER TABLE ── -->
                <section class="bg-white rounded-[1rem] border border-slate-200/60 shadow-2xl overflow-hidden">
                    <div class="p-4 sm:p-10 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4 sm:gap-8 bg-slate-50/30">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tighter italic uppercase">{{ t('pages.sales_reports.detailedReports') }}</h2>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-2">{{ t('pages.sales_reports.reportsSubtitle') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <Select v-model="selectedReportType" :options="reportTypesList" optionLabel="label" optionValue="value" class="!rounded-xl !bg-white !min-w-[250px] !border-slate-200 shadow-sm" />
                            <IconField iconPosition="left" class="w-full sm:w-64">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" :placeholder="t('pages.sales_reports.searchPlaceholder')" class="!rounded-xl !bg-white border-none shadow-inner w-full h-11" />
                            </IconField>
                        </div>
                    </div>

                    <DataTable :value="tableData" :paginator="true" :rows="10" :filters="filters" :loading="loadingTable"
                               class="p-datatable-v40 p-datatable-custom p-datatable-mobile snc-fluid-table" responsiveLayout="scroll">
                        <Column v-for="(col, index) of dynamicColumns" :key="col.field" :field="col.field" :header="col.header" sortable
                                :headerClass="dynamicColumnClass(col.field, index)" :bodyClass="dynamicColumnClass(col.field, index)">
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
                        <Column :header="t('pages.sales_reports.auditColumn')" style="width: 3.5rem; min-width: 3.5rem">
                            <template #body="{ data }">
                                <Button icon="pi pi-shield" text rounded severity="secondary" class="hidden md:inline-flex w-10 h-10" v-tooltip="t('pages.sales_reports.auditTooltip')" :aria-label="t('pages.sales_reports.auditTooltip')" @click="viewRowDetails(data)" />
                                <Button
                                    icon="pi pi-ellipsis-v"
                                    class="p-button-text p-button-secondary p-button-rounded w-10 h-10 md:hidden text-slate-500"
                                    :aria-label="t('pages.live_sessions.menu_more')"
                                    :aria-haspopup="true"
                                    @click="toggleRowMenu($event, data)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </section>
            </div>
        </div>

        <!-- ── TRANSACTION INSPECTOR DRAWER ── -->
        <Drawer v-model:visible="transactionDrawer" position="right" :header="t('pages.sales_reports.drawerTitle')" class="!w-full sm:!w-[500px] !rounded-l-[3rem]">
            <div v-if="selectedRow" class="space-y-8 p-4">
                <div class="bg-[#0F172A] p-10 rounded-[1rem] text-white relative overflow-hidden shadow-2xl">
                    <div class="absolute -top-10 -right-10 w-48 h-48 bg-red-600/10 rounded-full blur-3xl"></div>
                    <p class="text-[10px] font-black text-red-500 uppercase tracking-[0.3em] mb-4">{{ t('pages.sales_reports.inspectionId', { id: selectedRow.id || 'N/A' }) }}</p>
                    <h2 class="text-3xl font-black italic tracking-tighter uppercase leading-none">{{ t('pages.sales_reports.transactionDetails') }}</h2>
                </div>

                <div class="bg-slate-50 p-8 rounded-[1rem] border border-slate-100 space-y-4">
                    <div v-for="(val, key) in selectedRow" :key="key" class="flex justify-between items-center py-3 border-b border-white last:border-none">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ key.replace(/_/g, ' ') }}</span>
                        <span class="text-sm font-black text-slate-800 italic">{{ val }}</span>
                    </div>
                </div>

                <div class="p-6 bg-red-50 rounded-xl border border-red-100 flex items-center gap-4">
                    <i class="pi pi-info-circle text-red-600"></i>
                    <p class="text-[10px] text-red-900 font-bold leading-relaxed uppercase tracking-wider">{{ t('pages.sales_reports.certifiedData') }}</p>
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