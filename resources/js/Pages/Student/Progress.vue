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

const props = defineProps({ kpis: Object, perCourse: Array, activity: Array });
const { isDarkTheme } = useLayout();

const kpiCards = computed(() => [
    { label: 'Cours suivis', value: props.kpis?.courses_total ?? 0, icon: 'pi-book', color: 'blue' },
    { label: 'Terminés', value: props.kpis?.courses_completed ?? 0, icon: 'pi-check-circle', color: 'emerald' },
    { label: 'Complétion', value: `${props.kpis?.completion_rate ?? 0} %`, icon: 'pi-chart-line', color: 'amber' },
    { label: 'Temps d\'étude', value: `${props.kpis?.hours_studied ?? 0} h`, icon: 'pi-clock', color: 'sky' },
    { label: 'Certificats', value: props.kpis?.certificates ?? 0, icon: 'pi-verified', color: 'violet' },
]);

const totalMinutes = computed(() => (props.activity ?? []).reduce((s, a) => s + (Number(a.minutes) || 0), 0));

const activityData = computed(() => ({
    labels: (props.activity ?? []).map(a => a.label),
    datasets: [{
        label: 'Minutes étudiées', data: (props.activity ?? []).map(a => a.minutes),
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
    ? { value: 'Terminé', severity: 'success' }
    : c.progress >= 50 ? { value: 'Bien avancé', severity: 'warn' } : { value: 'En cours', severity: 'info' };
</script>

<template>
    <AppLayout>
        <Head title="Ma progression" />
        <div class="page-bg min-h-screen">
            <PageHeader
                title="Ma progression"
                icon="pi-chart-line"
                badge="30 derniers jours"
                subtitle="Suivez votre avancement, votre temps d'étude et vos résultats par formation."
                :actions="[{ label: 'Mes formations', icon: 'pi-book', href: route('student.my-courses') }]"
            />

            <div class="page-container space-y-6">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                    <PageKpi v-for="k in kpiCards" :key="k.label" :label="k.label" :value="k.value" :icon="k.icon" :color="k.color" />
                </div>

                <PageCard title="Activité" :subtitle="`${totalMinutes} minutes d'étude sur les 30 derniers jours`">
                    <EmptyState
                        v-if="totalMinutes === 0"
                        compact
                        icon="pi-calendar"
                        title="Pas encore d'activité ce mois-ci"
                        description="Quelques minutes par jour suffisent pour progresser régulièrement."
                    />
                    <div v-else class="h-64">
                        <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" aria-label="Graphique des minutes étudiées par jour" />
                    </div>
                </PageCard>

                <PageCard title="Détail par cours" :padded="false">
                    <EmptyState
                        v-if="(perCourse ?? []).length === 0"
                        icon="pi-inbox"
                        title="Aucune formation en cours"
                        description="Inscrivez-vous à une formation pour suivre votre progression ici."
                        action-label="Explorer le catalogue"
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
                                :aria-label="`Progression ${c.course_title} : ${Math.round(c.progress)} %`"
                                :style="`--p-progressbar-value-background: ${progressColor(c.progress)}`"
                            />
                            <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                                <span>{{ c.lessons_done }}/{{ c.lessons_total }} leçons</span>
                                <span v-if="c.avg_score > 0">Score moyen : {{ Math.round(c.avg_score) }} %</span>
                                <span v-if="c.completed_at">Terminé le {{ new Date(c.completed_at).toLocaleDateString('fr-FR') }}</span>
                            </div>
                        </li>
                    </ul>
                </PageCard>
            </div>
        </div>
    </AppLayout>
</template>
