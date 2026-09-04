<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';

const props = defineProps({ kpis: Object, perCourse: Array, activity: Array });

const activityData = computed(() => ({
    labels: (props.activity ?? []).map(a => a.label),
    datasets: [{
        label: 'Minutes étudiées', data: (props.activity ?? []).map(a => a.minutes),
        fill: true, backgroundColor: 'rgba(99, 102, 241, 0.15)', borderColor: '#6366F1',
        tension: 0.4, pointBackgroundColor: '#6366F1',
    }],
}));

const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
    },
};

const getProgressColor = (p) => p >= 80 ? '#10B981' : p >= 50 ? '#F59E0B' : '#8B5CF6';
</script>

<template>
    <AppLayout>
        <Head title="Ma progression" />
        <div class="page-bg min-h-screen font-sans pb-20">

            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                        <i class="pi pi-chart-line text-white text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">Ma progression</h1>
                            <Tag value="Analytics" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">Tableau de bord de votre avancement sur 30 jours.</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px] space-y-8">
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        <div v-for="kpi in [
                            { label: 'Cours', val: kpis?.courses_total ?? 0, icon: 'pi-book', iconBg: 'bg-indigo-50', iconText: 'text-indigo-600', border: 'hover:border-indigo-300' },
                            { label: 'Terminés', val: kpis?.courses_completed ?? 0, icon: 'pi-check-circle', iconBg: 'bg-emerald-50', iconText: 'text-emerald-600', border: 'hover:border-emerald-300' },
                            { label: 'Complétion', val: (kpis?.completion_rate ?? 0) + '%', icon: 'pi-chart-line', iconBg: 'bg-amber-50', iconText: 'text-amber-500', border: 'hover:border-amber-300' },
                            { label: 'Heures', val: (kpis?.hours_studied ?? 0) + 'h', icon: 'pi-clock', iconBg: 'bg-sky-50', iconText: 'text-sky-500', border: 'hover:border-sky-300' },
                            { label: 'Certificats', val: kpis?.certificates ?? 0, icon: 'pi-verified', iconBg: 'bg-rose-50', iconText: 'text-rose-500', border: 'hover:border-rose-300' },
                        ]" :key="kpi.label" :class="`bg-white p-5 rounded-3xl border border-slate-200 shadow-sm ${kpi.border} transition-colors group`">
                            <div :class="`w-10 h-10 ${kpi.iconBg} ${kpi.iconText} rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform`">
                                <i :class="`pi ${kpi.icon} text-lg`"></i>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-1 font-mono">{{ kpi.val }}</h3>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <h2 class="text-lg font-black text-slate-800 mb-4 flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-chart-line text-lg"></i></div>
                            Activité — 30 derniers jours (minutes)
                        </h2>
                        <div class="h-64">
                            <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" />
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                            <div class="w-10 h-10 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center"><i class="pi pi-list text-lg"></i></div>
                            <h2 class="text-lg font-black text-slate-800">Détail par cours</h2>
                        </div>
                        <div v-if="(perCourse ?? []).length === 0" class="p-12 text-center text-slate-400">
                            <i class="pi pi-inbox text-4xl block mb-3"></i>
                            Aucune formation en cours.
                        </div>
                        <div v-else class="divide-y divide-slate-100">
                            <div v-for="c in perCourse" :key="c.course_id" class="p-5 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <Link :href="route('student.my-courses')" class="font-black text-slate-800 hover:text-indigo-600 flex-1 truncate tracking-tight">{{ c.course_title }}</Link>
                                    <div class="flex items-center gap-3">
                                        <Tag v-if="c.status === 'completed'" value="Terminé" severity="success" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                                        <Tag v-else-if="c.progress >= 50" value="Avancé" severity="warning" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                                        <Tag v-else value="En cours" severity="info" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                                        <span class="text-lg font-black text-slate-800 w-16 text-right font-mono">{{ Math.round(c.progress) }}%</span>
                                    </div>
                                </div>
                                <ProgressBar :value="c.progress" :showValue="false" class="!h-2 !bg-slate-100" :style="`--p-progressbar-value-background: ${getProgressColor(c.progress)}`" />
                                <div class="flex justify-between text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">
                                    <span>{{ c.lessons_done }}/{{ c.lessons_total }} leçons</span>
                                    <span v-if="c.avg_score > 0">Score moyen : {{ Math.round(c.avg_score) }}%</span>
                                    <span v-if="c.completed_at">Terminé le {{ new Date(c.completed_at).toLocaleDateString('fr-FR') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>