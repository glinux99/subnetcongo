<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT EXAM CENTER v3.0 (Ultimate Edition)
 * ======================================================================================
 */
import { ref, computed } from 'vue';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    exams: Array
});

// Filtres
const search = ref('');
const activeFilter = ref('all');

const filteredExams = computed(() => {
    let list = props.exams || [];
    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(e => e.title?.toLowerCase().includes(s) || e.course?.title?.toLowerCase().includes(s));
    }
    if (activeFilter.value === 'available') list = list.filter(e => !e.is_passed && e.remaining_attempts !== 0);
    else if (activeFilter.value === 'passed') list = list.filter(e => e.is_passed);
    else if (activeFilter.value === 'failed') list = list.filter(e => !e.is_passed && e.attempts_count > 0 && e.remaining_attempts === 0);
    return list;
});

const stats = computed(() => ({
    total: props.exams?.length ?? 0,
    available: props.exams?.filter(e => !e.is_passed && e.remaining_attempts !== 0).length ?? 0,
    passed: props.exams?.filter(e => e.is_passed).length ?? 0,
    locked: props.exams?.filter(e => e.attempts_count > 0 && e.remaining_attempts === 0).length ?? 0,
}));

const getStatus = (exam) => {
    if (exam.is_passed) return { label: t('pages.exams_index.status_passed'), sev: 'success', icon: 'pi-verified', bg: 'bg-emerald-500' };
    if (exam.active_attempt_uuid) return { label: t('pages.exams_index.status_in_progress'), sev: 'warn', icon: 'pi-spin pi-spinner', bg: 'bg-amber-500' };
    if (exam.remaining_attempts === 0) return { label: t('pages.exams_index.status_locked'), sev: 'danger', icon: 'pi-lock', bg: 'bg-red-600' };
    return { label: t('pages.exams_index.status_available'), sev: 'info', icon: 'pi-play', bg: 'bg-red-600' };
};

const formatDuration = (min) => min ? (min < 60 ? `${min} min` : `${Math.floor(min/60)}h${min%60 || ''}`) : '—';
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.exams_index.head_title')" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <PageHeader
                :title="`${t('pages.exams_index.title_base')} ${t('pages.exams_index.title_accent')}`"
                :subtitle="t('pages.exams_index.subtitle')"
                :badge="t('pages.exams_index.badge')"
                icon="pi pi-file"
                accent="red"
            >
                <template #actions>
                    <div class="w-full md:w-96">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-500" />
                            <InputText v-model="search" :placeholder="t('pages.exams_index.search_placeholder')"
                                class="w-full h-11 !rounded-xl" />
                        </IconField>
                    </div>
                </template>
            </PageHeader>

            <div class="page-container page-section">
                
                <!-- ── KPI CARDS ── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <PageKpi :label="t('pages.exams_index.kpi_total')" :value="stats.total" icon="pi-file" color="slate" />
                    <PageKpi :label="t('pages.exams_index.kpi_available')" :value="stats.available" icon="pi-play-circle" color="red" />
                    <PageKpi :label="t('pages.exams_index.kpi_certifications')" :value="stats.passed" icon="pi-verified" color="emerald" />
                    <PageKpi :label="t('pages.exams_index.kpi_locked')" :value="stats.locked" icon="pi-lock" color="amber" />
                </div>

                <!-- ── FILTRES ── -->
                <div class="flex gap-2 mb-8 bg-white p-2 rounded-xl border border-slate-200 w-fit shadow-sm">
                    <button v-for="f in [{k:'all',l:t('pages.exams_index.filter_all')}, {k:'available',l:t('pages.exams_index.filter_available')}, {k:'passed',l:t('pages.exams_index.filter_passed')}, {k:'failed',l:t('pages.exams_index.filter_failed')}]" :key="f.k"
                        @click="activeFilter = f.k"
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        activeFilter === f.k ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'text-slate-500 hover:bg-slate-50']">
                        {{ f.l }}
                    </button>
                </div>

                <!-- ── GRILLE EXAMENS ── -->
                <div v-if="filteredExams.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="exam in filteredExams" :key="exam.id" 
                        class="group bg-white rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:border-red-100 transition-all duration-500 flex flex-col overflow-hidden">
                        
                        <!-- Status Header -->
                        <div :class="getStatus(exam).bg" class="h-1.5 w-full"></div>

                        <div class="p-8 flex-1 space-y-6">
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-red-600 uppercase tracking-[0.2em]">{{ exam.type || t('pages.exams_index.exam_fallback') }}</span>
                                    <h3 class="text-lg font-black text-slate-800 leading-tight italic tracking-tight line-clamp-2">{{ exam.title }}</h3>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 shadow-inner">
                                    <i :class="getStatus(exam).icon" class="text-slate-400"></i>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-xs font-bold text-slate-500 italic">
                                <i class="pi pi-book text-red-500"></i>
                                <span class="truncate">{{ exam.course?.title || t('pages.exams_index.general_training') }}</span>
                            </div>

                            <!-- Meta Grid -->
                            <div class="grid grid-cols-3 gap-4 py-4 border-y border-slate-50">
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.exams_index.meta_duration') }}</p>
                                    <p class="text-xs font-black text-slate-900">{{ formatDuration(exam.duration_minutes) }}</p>
                                </div>
                                <div class="text-center border-x border-slate-50">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.exams_index.meta_pass') }}</p>
                                    <p class="text-xs font-black text-slate-900">{{ exam.passing_score ?? 70 }}%</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.exams_index.meta_attempts') }}</p>
                                    <p class="text-xs font-black text-slate-900">{{ exam.remaining_attempts }}</p>
                                </div>
                            </div>

                            <!-- Score Visual -->
                            <div v-if="exam.best_score !== null">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.exams_index.performance') }}</p>
                                    <span :class="exam.is_passed ? 'text-emerald-500' : 'text-red-600'" class="text-sm font-black italic tracking-tighter">{{ exam.best_score }}%</span>
                                </div>
                                <ProgressBar :value="exam.best_score" :showValue="false" class="h-1.5" 
                                    :style="`--p-progressbar-value-background: ${exam.is_passed ? '#10B981' : '#E11D48'}`" />
                            </div>
                        </div>

                        <!-- ── ACTIONS ── -->
                        <div class="p-6 bg-slate-50/50 border-t border-slate-50 flex gap-3">
                            <Link v-if="exam.active_attempt_uuid" :href="route('exams.take', exam.uuid)" class="flex-1">
                                <Button :label="t('pages.exams_index.btn_resume')" icon="pi pi-spin pi-spinner" class="w-full !bg-amber-500 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            </Link>

                            <Link v-else-if="exam.remaining_attempts !== 0 && !exam.is_passed" :href="route('exams.take', exam.uuid)" class="flex-1">
                                <Button :label="t('pages.exams_index.btn_start')" icon="pi pi-play-circle" class="w-full !bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest transition-all active:scale-95 shadow-lg shadow-red-600/20" />
                            </Link>

                            <div v-else class="flex-1">
                                <Button :label="exam.is_passed ? t('pages.exams_index.btn_already_passed') : t('pages.exams_index.btn_locked')" icon="pi pi-lock" disabled 
                                    class="w-full !bg-slate-200 !text-slate-500 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest opacity-60" />
                            </div>

                            <Link v-if="exam.results?.length" :href="route('exams.attempts', exam.uuid)">
                                <Button icon="pi pi-history" text class="!text-slate-400 hover:!text-blue-600" v-tooltip="t('pages.exams_index.tooltip_attempts_history')" />
                            </Link>
                            <Link v-if="exam.results?.length" :href="route('exams.results', exam.results[0].uuid)">
                                <Button icon="pi pi-chart-bar" text class="!text-slate-400 hover:!text-red-600" v-tooltip="t('pages.exams_index.tooltip_performance')" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ── EMPTY STATE ── -->
                <div v-else class="py-24 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-200 shadow-inner">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                        <i class="pi pi-file-edit text-slate-300 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight italic uppercase">{{ t('pages.exams_index.empty_title') }}</h3>
                    <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto font-medium leading-relaxed">
                        {{ search ? t('pages.exams_index.empty_no_results', { search }) : t('pages.exams_index.empty_hint') }}
                    </p>
                    <Link :href="route('student.my-courses')" class="mt-8 inline-block">
                        <Button :label="t('pages.exams_index.btn_my_courses')" icon="pi pi-book" class="!bg-red-600 !border-none !rounded-xl !px-10 !py-4 !text-xs !font-black !uppercase !tracking-widest shadow-xl" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar) { background: #F1F5F9; border-radius: 99px; }
:deep(.p-progressbar-value) { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
</style>