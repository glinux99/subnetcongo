<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    session: Object,
    stats: Object,
    chartData: Object,
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: '#f1f5f9' } },
        x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { display: false } }
    }
});

const formatTime = (s) => `${Math.floor(s / 60)}m ${s % 60}s`;

const engagementRate = computed(() => {
    if (!props.stats?.total_registered) return 0;
    return Math.round((props.stats.actual_attendees / props.stats.total_registered) * 100);
});
</script>

<template>
    <AppLayout>
        <Head :title="`Rapport — ${session?.title}`" />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-chart-bar text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Rapport de session</h1>
                            <p class="text-slate-400 font-medium text-sm">{{ session?.title }} · {{ session?.course?.title }}</p>
                        </div>
                    </div>
                    <Link :href="route('live-sessions.index')">
                        <Button label="Retour" icon="pi pi-arrow-left" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Inscrits', value: stats?.total_registered ?? 0, icon: 'pi-users', color: 'from-indigo-500 to-blue-500' },
                        { label: 'Présents', value: stats?.actual_attendees ?? 0, icon: 'pi-check-circle', color: 'from-emerald-500 to-teal-500' },
                        { label: 'Taux présence', value: `${engagementRate}%`, icon: 'pi-percentage', color: 'from-amber-500 to-orange-500' },
                        { label: 'Pic participants', value: stats?.peak_participants ?? 0, icon: 'pi-bolt', color: 'from-rose-500 to-pink-500' },
                    ]" :key="kpi.label" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white shadow-lg mb-3', kpi.color]">
                            <i :class="['pi', kpi.icon, 'text-xl']"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-800 tracking-tight">{{ kpi.value }}</div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm mb-6">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Engagement en temps réel</h3>
                    <div class="h-72">
                        <Chart type="line" :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Liste des participants</h3>
                    <DataTable :value="session?.attendees ?? []" stripedRows paginator :rows="10" responsiveLayout="scroll" class="text-sm">
                        <Column header="Participant">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-black">
                                        {{ data.user?.name?.[0] ?? '?' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ data.user?.name ?? '—' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ data.user?.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Arrivée">
                            <template #body="{ data }">{{ data.joined_at ? new Date(data.joined_at).toLocaleTimeString('fr-FR') : '—' }}</template>
                        </Column>
                        <Column header="Temps passé">
                            <template #body="{ data }">{{ formatTime(data.seconds_spent ?? 0) }}</template>
                        </Column>
                        <Column header="Dernier ping">
                            <template #body="{ data }">{{ data.last_ping_at ? new Date(data.last_ping_at).toLocaleTimeString('fr-FR') : '—' }}</template>
                        </Column>
                        <Column header="Statut">
                            <template #body="{ data }">
                                <Tag :value="data.left_at ? 'Parti' : 'Actif'" :severity="data.left_at ? 'secondary' : 'success'" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">Aucun participant.</div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
