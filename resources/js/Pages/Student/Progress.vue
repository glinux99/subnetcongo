<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';

const props = defineProps({
    kpis: Object,
    perCourse: Array,
    activity: Array,
});

const activityData = computed(() => ({
    labels: (props.activity ?? []).map(a => a.label),
    datasets: [{
        label: 'Minutes étudiées',
        data: (props.activity ?? []).map(a => a.minutes),
        fill: true,
        backgroundColor: 'rgba(139, 92, 246, 0.15)',
        borderColor: '#8B5CF6',
        tension: 0.4,
        pointBackgroundColor: '#8B5CF6',
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
    },
};

const getProgressColor = (p) => {
    if (p >= 80) return '#10B981';
    if (p >= 50) return '#F59E0B';
    return '#8B5CF6';
};
</script>

<template>
    <AppLayout>
        <Head title="Ma progression" />

        <div class="min-h-screen bg-[#F8FAFC] py-10 px-6 font-sans">
            <div class="max-w-[1400px] mx-auto space-y-8">
                <div>
                    <p class="text-[10px] font-black text-violet-500 uppercase tracking-widest">Analytics apprenant</p>
                    <h1 class="text-3xl font-black text-slate-800 italic tracking-tight mt-1">Ma progression</h1>
                </div>

                <!-- KPI Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div v-for="kpi in [
                        { label: 'Cours', val: kpis?.courses_total ?? 0, icon: 'pi-book', color: 'violet' },
                        { label: 'Terminés', val: kpis?.courses_completed ?? 0, icon: 'pi-check-circle', color: 'emerald' },
                        { label: 'Complétion', val: (kpis?.completion_rate ?? 0) + '%', icon: 'pi-chart-line', color: 'amber' },
                        { label: 'Heures', val: (kpis?.hours_studied ?? 0) + 'h', icon: 'pi-clock', color: 'sky' },
                        { label: 'Certificats', val: kpis?.certificates ?? 0, icon: 'pi-verified', color: 'rose' },
                    ]" :key="kpi.label"
                         class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                        <div :class="`w-10 h-10 bg-${kpi.color}-50 text-${kpi.color}-600 rounded-xl flex items-center justify-center mb-3`">
                            <i :class="`pi ${kpi.icon} text-lg`"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</p>
                        <h3 class="text-2xl font-black text-slate-800 mt-1">{{ kpi.val }}</h3>
                    </div>
                </div>

                <!-- Activity chart -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <h2 class="text-lg font-black text-slate-800 italic mb-4 flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-violet-600 rounded-full"></span>
                        Activité — 30 derniers jours (minutes)
                    </h2>
                    <div class="h-64">
                        <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" />
                    </div>
                </div>

                <!-- Per-course breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-black text-slate-800 italic">Détail par cours</h2>
                    </div>
                    <div v-if="(perCourse ?? []).length === 0" class="p-12 text-center text-slate-400">
                        <i class="pi pi-inbox text-4xl block mb-3"></i>
                        Aucune formation en cours.
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div v-for="c in perCourse" :key="c.course_id" class="p-5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between gap-4 mb-2">
                                <Link :href="route('student.my-courses')" class="font-black text-slate-800 italic hover:text-violet-600 flex-1 truncate">
                                    {{ c.course_title }}
                                </Link>
                                <div class="flex items-center gap-3">
                                    <Tag v-if="c.status === 'completed'" value="Terminé" severity="success" class="text-[10px]" />
                                    <Tag v-else-if="c.progress >= 50" value="Avancé" severity="warning" class="text-[10px]" />
                                    <Tag v-else value="En cours" severity="info" class="text-[10px]" />
                                    <span class="text-lg font-black text-slate-800 w-16 text-right">{{ Math.round(c.progress) }}%</span>
                                </div>
                            </div>
                            <ProgressBar :value="c.progress" :showValue="false" class="h-2 bg-slate-100"
                                         :style="`--p-progressbar-value-background: ${getProgressColor(c.progress)}`" />
                            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">
                                <span>{{ c.lessons_done }}/{{ c.lessons_total }} leçons</span>
                                <span v-if="c.avg_score > 0">Score moyen : {{ Math.round(c.avg_score) }}%</span>
                                <span v-if="c.completed_at">Terminé le {{ new Date(c.completed_at).toLocaleDateString('fr-FR') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>