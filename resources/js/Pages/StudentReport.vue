<script setup lang="ts">
/**
 * ======================================================================================
 * SUBNETCONGO LMS - STUDENT ANALYTICS HUB v40.0 (Prestige Edition)
 * ======================================================================================
 */
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// UI CORE
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Avatar from 'primevue/avatar';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Timeline from 'primevue/timeline';
import Skeleton from 'primevue/skeleton';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

// --- INTERFACES ---
interface Exam {
    id: number; title: string; status: 'Réussi' | 'Échoué' | 'En attente';
    score: number | null; attempted_at: string;
}
interface Course { id: number; title: string; exams: Exam[]; }

interface StudentProps {
    student: { id: number; name: string; avatar?: string; email: string; enrollment_date: string };
    globalStats: {
        average_score: number; score_trend: number;
        success_rate: number; success_trend: number;
        completed_exams: number; total_attempts: number;
    };
    coursesData: Course[];
    performanceOverTime: { labels: string[]; scores: number[] };
    skillsData: { labels: string[]; scores: number[] };
    activityLog: { status: string; date: string; icon: string; color: string; description: string }[];
}

const props = defineProps<StudentProps>();
const toast = useToast();
const isLoading = ref(true);
const searchQuery = ref('');

onMounted(() => {
    setTimeout(() => { isLoading.value = false; }, 600);
});

// FILTRAGE
const filteredCourses = computed(() => {
    if (!searchQuery.value) return props.coursesData;
    const q = searchQuery.value.toLowerCase();
    return props.coursesData.map(c => ({
        ...c,
        exams: c.exams.filter(e => e.title.toLowerCase().includes(q))
    })).filter(c => c.exams.length > 0 || c.title.toLowerCase().includes(q));
});

// --- CHARTS CONFIG (SubnetCongo Ruby Palette) ---
const lineChartData = computed(() => ({
    labels: props.performanceOverTime.labels,
    datasets: [{
        label: t('pages.student_report_detail.chartPerformance'),
        data: props.performanceOverTime.scores,
        fill: true,
        borderColor: '#E11D48',
        backgroundColor: 'rgba(225, 29, 72, 0.1)',
        tension: 0.4,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#E11D48',
        pointRadius: 5
    }]
}));

const radarChartData = computed(() => ({
    labels: props.skillsData?.labels || [],
    datasets: [{
        label: t('pages.student_report_detail.chartMastery'),
        data: props.skillsData?.scores || [],
        backgroundColor: 'rgba(225, 29, 72, 0.2)',
        borderColor: '#E11D48',
        pointBackgroundColor: '#E11D48'
    }]
}));

const getStatusProps = (s: string) => {
    const map: any = {
        'Réussi': { sev: 'success', icon: 'pi-check-circle' },
        'Échoué': { sev: 'danger', icon: 'pi-times' },
        'En attente': { sev: 'warn', icon: 'pi-clock' }
    };
    return map[s] || { sev: 'secondary', icon: 'pi-info' };
};

const headerActions = computed(() => [
    { label: t('pages.student_report_detail.backToReports'), icon: 'pi-arrow-left', variant: 'secondary', href: route('student-reports.index') },
]);
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.student_report_detail.headTitle', { name: student.name })" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="student.name"
                :subtitle="t('pages.student_report_detail.subtitle', { email: student.email })"
                icon="pi-id-card"
                accent="blue"
                :badge="t('pages.student_report_detail.badge')"
                :actions="headerActions"
            >
                <template #meta>
                    <div class="flex items-center gap-3 mt-3">
                        <Avatar :image="student.avatar" :label="student.name.charAt(0)" shape="circle" size="small"
                            class="!bg-white/10 !text-white !font-black border border-white/20" />
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ t('pages.student_report_detail.studentId', { id: student.id }) }}</span>
                    </div>
                </template>
            </PageHeader>

            <main class="page-container page-section space-y-8">
                
                <!-- ETAT DE CHARGEMENT -->
                <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                    <Skeleton height="120px" borderRadius="32px" v-for="i in 4" :key="i" />
                </div>

                <!-- 1. KPI CARDS V40 -->
                <section v-else class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                    <div v-for="kpi in [
                        { l: t('pages.student_report_detail.kpiAvg'), v: globalStats.average_score + '%', t: globalStats.score_trend, i: 'pi-percentage', c: 'text-red-600', bg: 'bg-red-50' },
                        { l: t('pages.student_report_detail.kpiSuccess'), v: globalStats.success_rate + '%', t: globalStats.success_trend, i: 'pi-verified', c: 'text-emerald-600', bg: 'bg-emerald-50' },
                        { l: t('pages.student_report_detail.kpiCompleted'), v: globalStats.completed_exams, t: globalStats.success_trend, i: 'pi-book', c: 'text-blue-600', bg: 'bg-blue-50' },
                        { l: t('pages.student_report_detail.kpiAttempts'), v: globalStats.total_attempts, t: globalStats.success_trend, i: 'pi-sync', c: 'text-slate-900', bg: 'bg-slate-100' }
                    ]" :key="kpi.l" class="bg-white p-4 sm:p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-xl transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div :class="kpi.bg" class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:rotate-6 transition-transform">
                                <i :class="['pi', kpi.i, kpi.c, 'text-lg']"></i>
                            </div>
                          <Tag 
    v-if="kpi.t !== undefined && kpi.t !== null" 
    :severity="Number(kpi.t) >= 0 ? 'success' : 'danger'" 
    :value="`${Number(kpi.t) > 0 ? '+' : ''}${kpi.t}%`" 
    class="!text-[9px] !font-black" 
/>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.l }}</p>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tighter italic">{{ kpi.v }}</h2>
                    </div>
                </section>

                <!-- 2. ANALYTICS TABS -->
                <div class="bg-white rounded-[1rem] border border-slate-200 shadow-2xl overflow-hidden min-h-[600px]">
                    <Tabs value="0">
                        <TabList class="bg-slate-50/50 px-8 pt-4 !border-none">
                            <Tab value="0" class="!text-xs !font-black !uppercase !tracking-widest !px-8">{{ t('pages.student_report_detail.tabPerformance') }}</Tab>
                            <Tab value="1" class="!text-xs !font-black !uppercase !tracking-widest !px-8">{{ t('pages.student_report_detail.tabGrades') }}</Tab>
                            <Tab value="2" class="!text-xs !font-black !uppercase !tracking-widest !px-8">{{ t('pages.student_report_detail.tabTimeline') }}</Tab>
                        </TabList>

                        <TabPanels class="p-8">
                            <!-- TAB 0 : GRAPHIQUES -->
                            <TabPanel value="0">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                                    <div class="lg:col-span-2 space-y-6">
                                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                            <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span> {{ t('pages.student_report_detail.evolutionTitle') }}
                                        </h3>
                                        <div class="h-[400px] p-6 rounded-[1rem] bg-slate-50 border border-slate-100 shadow-inner">
                                            <Chart type="line" :data="lineChartData" class="h-full" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" />
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest text-center">{{ t('pages.student_report_detail.skillMapping') }}</h3>
                                        <div class="h-[400px] flex items-center justify-center p-6 rounded-[1rem] border border-red-50">
                                            <Chart type="radar" :data="radarChartData" class="h-full" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 1 : DATA TABLE V40 -->
                            <TabPanel value="1">
                                <div class="space-y-8">
                                    <div class="flex justify-between items-center mb-6">
                                        <IconField iconPosition="left" class="w-full max-w-md">
                                            <InputIcon class="pi pi-search" />
                                            <InputText v-model="searchQuery" :placeholder="t('pages.student_report_detail.searchPlaceholder')" class="w-full !rounded-xl !bg-slate-50 border-none !py-4" />
                                        </IconField>
                                    </div>

                                    <div v-for="course in filteredCourses" :key="course.id" class="rounded-[1rem] border border-slate-100 overflow-hidden mb-8 shadow-sm">
                                        <div class="bg-[#0F172A] px-4 sm:px-8 py-4 sm:py-5 flex justify-between items-center gap-3 flex-wrap">
                                            <h4 class="font-black text-white text-sm tracking-tight italic uppercase">{{ course.title }}</h4>
                                            <span class="text-[10px] font-black text-red-500 uppercase tracking-[0.2em]">{{ t('pages.student_report_detail.assessmentsCount', { count: course.exams.length }) }}</span>
                                        </div>

                                        <DataTable :value="course.exams" responsiveLayout="scroll" class="p-datatable-v40">
                                            <Column field="title" :header="t('pages.student_report_detail.colAssessment')">
                                                <template #body="{ data }">
                                                    <span class="font-black text-slate-800 tracking-tight italic">{{ data.title }}</span>
                                                </template>
                                            </Column>
                                            <Column headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" :header="t('pages.student_report_detail.colScore')">
                                                <template #body="{ data }">
                                                    <div v-if="data.score !== null" class="w-32">
                                                        <div class="flex justify-between mb-1">
                                                            <span class="text-[10px] font-bold text-slate-400">{{ t('pages.student_report_detail.precision') }}</span>
                                                            <span class="text-xs font-black text-slate-800">{{ data.score }}%</span>
                                                        </div>
                                                        <ProgressBar :value="data.score" :showValue="false" class="h-1 bg-slate-100" 
                                                            :style="`--p-progressbar-value-background: ${data.score >= 50 ? '#10B981' : '#E11D48'}`" />
                                                    </div>
                                                    <span v-else class="text-[10px] italic text-slate-300">{{ t('pages.student_report_detail.pending') }}</span>
                                                </template>
                                            </Column>
                                            <Column :header="t('pages.student_report_detail.colStatus')">
                                                <template #body="{ data }">
                                                    <Tag :severity="getStatusProps(data.status).sev" :value="data.status" 
                                                         class="!text-[9px] !font-black !uppercase !px-3 !rounded-lg" />
                                                </template>
                                            </Column>
                                            <Column :header="t('pages.student_report_detail.colDate')" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                                                <template #body="{ data }">
                                                    <span class="text-xs font-bold text-slate-500 italic">{{ data.attempted_at || '---' }}</span>
                                                </template>
                                            </Column>
                                        </DataTable>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 2 : TIMELINE ELITE -->
                            <TabPanel value="2">
                                <div class="max-w-3xl mx-auto py-10 px-6 bg-slate-50 rounded-[1rem] border border-slate-100">
                                    <Timeline :value="activityLog" align="alternate" class="custom-timeline-v40">
                                        <template #marker="slotProps">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-xl" :style="{ backgroundColor: slotProps.item.color }">
                                                <i :class="slotProps.item.icon" class="text-sm"></i>
                                            </div>
                                        </template>
                                        <template #content="slotProps">
                                            <div class="p-6 bg-white rounded-xl shadow-sm border border-slate-200 mb-6 group hover:shadow-lg transition-all">
                                                <h4 class="text-sm font-black text-slate-800 italic uppercase mb-1">{{ slotProps.item.status }}</h4>
                                                <p class="text-xs text-slate-500 font-medium leading-relaxed mb-3">{{ slotProps.item.description }}</p>
                                                <span class="text-[10px] font-black text-slate-400 tracking-widest italic">{{ slotProps.item.date }}</span>
                                            </div>
                                        </template>
                                    </Timeline>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<style scoped>
/* PRESTIGE V40 CORE STYLES */
.italic { font-style: italic; }
.tracking-tighter { letter-spacing: -0.05em; }

/* TABS OVERRIDES */
:deep(.p-tablist-tab-list) { border: none !important; }
:deep(.p-tab) { transition: all 0.3s ease; border-bottom: 2px solid transparent !important; color: #94A3B8; }
:deep(.p-tab-active) { color: #E11D48 !important; border-bottom-color: #E11D48 !important; }

/* DATATABLE V40 */
:deep(.p-datatable-v40 .p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1.5rem 2rem;
    border: none;
}
:deep(.p-datatable-v40 .p-datatable-tbody > tr) { border-bottom: 1px solid #F1F5F9; }
:deep(.p-datatable-v40 .p-datatable-tbody > tr:hover) { background: #F8FAFC !important; }

/* TIMELINE V40 */
:deep(.p-timeline-event-opposite) { display: none; }
@media (min-width: 768px) {
    :deep(.p-timeline-event-opposite) { display: block; flex: 1; padding: 0 1rem; }
}

/* KNOB / PROGRESS OVERRIDES */
:deep(.p-progressbar) { border-radius: 99px; }
:deep(.p-progressbar-value) { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
</style>