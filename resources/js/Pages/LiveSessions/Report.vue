<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import Button from 'primevue/button';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    session: Object,
    stats: Object,
    chartData: Object,
    timezone: { type: String, default: 'Africa/Lubumbashi' },
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
        <Head :title="`${t('pages.live_session_report.head_prefix')} — ${session?.title}`" />
        <div class="page-bg min-h-screen font-sans pb-20">
            <PageHeader
                :title="t('pages.live_session_report.heading')"
                :subtitle="[session?.title, session?.course?.title].filter(Boolean).join(' · ')"
                icon="pi-chart-bar"
                accent="red"
            >
                <template #actions>
                    <Link :href="route('live-sessions.index')">
                        <Button :label="t('pages.live_session_report.btn_back')" icon="pi pi-arrow-left" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </template>
            </PageHeader>

            <div class="page-container page-section">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <PageKpi :label="t('pages.live_session_report.kpi_registered')" :value="stats?.total_registered ?? 0" icon="pi-users" color="blue" />
                    <PageKpi :label="t('pages.live_session_report.kpi_attendees')" :value="stats?.actual_attendees ?? 0" icon="pi-check-circle" color="emerald" />
                    <PageKpi :label="t('pages.live_session_report.kpi_attendance_rate')" :value="`${engagementRate}%`" icon="pi-percentage" color="sky" />
                    <PageKpi :label="t('pages.live_session_report.kpi_peak')" :value="stats?.peak_participants ?? 0" icon="pi-bolt" color="red" />
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm mb-6">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">{{ t('pages.live_session_report.chart_title') }}</h3>
                    <div class="h-72">
                        <Chart type="line" :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">{{ t('pages.live_session_report.participants_title') }}</h3>
                    <DataTable :value="session?.attendees ?? []" stripedRows paginator :rows="10" responsiveLayout="scroll" class="text-sm">
                        <Column :header="t('pages.live_session_report.col_participant')">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-black">
                                        {{ data.user?.name?.[0] ?? '?' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ data.user?.name ?? '—' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ data.user?.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column :header="t('pages.live_session_report.col_joined_at')" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">{{ data.joined_at ? new Date(data.joined_at).toLocaleTimeString(locale, { timeZone: props.timezone }) : '—' }}</template>
                        </Column>
                        <Column :header="t('pages.live_session_report.col_time_spent')" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">{{ formatTime(data.seconds_spent ?? 0) }}</template>
                        </Column>
                        <Column :header="t('pages.live_session_report.col_last_ping')" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                            <template #body="{ data }">{{ data.last_ping_at ? new Date(data.last_ping_at).toLocaleTimeString(locale, { timeZone: props.timezone }) : '—' }}</template>
                        </Column>
                        <Column :header="t('pages.live_session_report.col_status')">
                            <template #body="{ data }">
                                <Tag :value="data.left_at ? t('pages.live_session_report.status_left') : t('pages.live_session_report.status_active')" :severity="data.left_at ? 'secondary' : 'success'" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">{{ t('pages.live_session_report.no_participants') }}</div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
