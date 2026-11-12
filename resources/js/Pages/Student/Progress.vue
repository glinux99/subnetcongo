<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useLayout } from '@/sakai/layout/composables/layout';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({ kpis: Object, perCourse: Array, activity: Array });
const { isDarkTheme } = useLayout();

const kpiCards = computed(() => [
    { label: t('pages.student_progress.kpiCourses'), value: props.kpis?.courses_total ?? 0, icon: 'pi-book', color: 'blue' },
    { label: t('pages.student_progress.kpiCompleted'), value: props.kpis?.courses_completed ?? 0, icon: 'pi-check-circle', color: 'emerald' },
    { label: t('pages.student_progress.kpiCompletion'), value: `${props.kpis?.completion_rate ?? 0} %`, icon: 'pi-chart-line', color: 'amber' },
    { label: t('pages.student_progress.kpiStudyTime'), value: `${props.kpis?.hours_studied ?? 0} h`, icon: 'pi-clock', color: 'sky' },
    { label: t('pages.student_progress.kpiCertificates'), value: props.kpis?.certificates ?? 0, icon: 'pi-verified', color: 'violet' },
]);

const totalMinutes = computed(() => (props.activity ?? []).reduce((s, a) => s + (Number(a.minutes) || 0), 0));

const activityData = computed(() => ({
    labels: (props.activity ?? []).map(a => a.label),
    datasets: [{
        label: t('pages.student_progress.chartDataset'), data: (props.activity ?? []).map(a => a.minutes),
        fill: true, backgroundColor: 'rgba(37, 99, 235, 0.12)', borderColor: '#2563EB',
        tension: 0.4, pointBackgroundColor: '#2563EB', pointRadius: 2,
    }],
}));

const chartOptions = computed(() => {
    const grid = isDarkTheme.value ? '#1e293b' : '#f1f5f9';
    const tick = isDarkTheme.value ? '#64748b' : '#94a3b8';
    return {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { color: tick, font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
        },
    };
});

const progressColor = (p) => p >= 80 ? '#10B981' : p >= 50 ? '#F59E0B' : '#2563EB';
const statusTag = (c) => c.status === 'completed'
    ? { value: t('pages.student_progress.statusCompleted'), severity: 'success' }
    : c.progress >= 50 ? { value: t('pages.student_progress.statusWellAdvanced'), severity: 'warn' } : { value: t('pages.student_progress.statusInProgress'), severity: 'info' };
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.student_progress.headTitle')" />
        <div class="page-bg min-h-screen">
            <PageHeader
                :title="t('pages.student_progress.pageTitle')"
                icon="pi-chart-line"
                :badge="t('pages.student_progress.pageBadge')"
                :subtitle="t('pages.student_progress.pageSubtitle')"
                :actions="[{ label: t('pages.student_progress.actionMyCourses'), icon: 'pi-book', href: route('student.my-courses') }]"
            />

            <div class="page-container space-y-6">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                    <PageKpi v-for="k in kpiCards" :key="k.label" :label="k.label" :value="k.value" :icon="k.icon" :color="k.color" />
                </div>

                <PageCard :title="t('pages.student_progress.activityTitle')" :subtitle="t('pages.student_progress.activitySubtitle', { minutes: totalMinutes })">
                    <EmptyState
                        v-if="totalMinutes === 0"
                        compact
                        icon="pi-calendar"
                        :title="t('pages.student_progress.emptyActivityTitle')"
                        :description="t('pages.student_progress.emptyActivityDesc')"
                    />
                    <div v-else class="h-64">
                        <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" :aria-label="t('pages.student_progress.chartAria')" />
                    </div>
                </PageCard>

                <PageCard :title="t('pages.student_progress.detailTitle')" :padded="false">
                    <EmptyState
                        v-if="(perCourse ?? []).length === 0"
                        icon="pi-inbox"
                        :title="t('pages.student_progress.emptyCoursesTitle')"
                        :description="t('pages.student_progress.emptyCoursesDesc')"
                        :action-label="t('pages.student_progress.emptyCoursesAction')"
                        :action-href="route('marketplace.index')"
                    />
                    <ul v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                        <li v-for="c in perCourse" :key="c.course_id" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-3">
                                <Link :href="route('student.my-courses')" class="font-semibold text-slate-900 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 truncate">{{ c.course_title }}</Link>
                                <div class="flex items-center gap-3 shrink-0">
                                    <Tag :value="statusTag(c).value" :severity="statusTag(c).severity" />
                                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100 tabular-nums w-14 text-right">{{ Math.round(c.progress) }} %</span>
                                </div>
                            </div>
                            <ProgressBar
                                :value="c.progress"
                                :showValue="false"
                                class="!h-2"
                                :aria-label="t('pages.student_progress.courseProgressAria', { title: c.course_title, percent: Math.round(c.progress) })"
                                :style="`--p-progressbar-value-background: ${progressColor(c.progress)}`"
                            />
                            <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                                <span>{{ t('pages.student_progress.lessonsCount', { done: c.lessons_done, total: c.lessons_total }) }}</span>
                                <span v-if="c.avg_score > 0">{{ t('pages.student_progress.avgScore', { score: Math.round(c.avg_score) }) }}</span>
                                <span v-if="c.completed_at">{{ t('pages.student_progress.completedAt', { date: new Date(c.completed_at).toLocaleDateString(locale, { dateStyle: 'medium' }) }) }}</span>
                            </div>
                        </li>
                    </ul>
                </PageCard>
            </div>
        </div>
    </AppLayout>
</template>
