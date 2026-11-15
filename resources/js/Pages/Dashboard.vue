<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - DASHBOARD ADMINISTRATEUR
 * ======================================================================================
 *
 * Tableau de bord administrateur avec design system SUBNETCONGO.
 * Objectifs : hiérarchie claire, filtres réellement utiles, mobile et accessibilité.
 */

import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import debounce from 'lodash/debounce';

import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Chart from 'primevue/chart';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Timeline from 'primevue/timeline';
import ScrollPanel from 'primevue/scrollpanel';

import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';
import PageSection from '@/Components/PageSection.vue';
import ContextHint from '@/Components/ContextHint.vue';
import SncSkeleton from '@/Components/SncSkeleton.vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    meta: Object,
    availableFilters: Object,
    kpis: Object,
    chartData: Object,
    cohorts: Object,
    leaderboard: Array,
    activities: Array,
    supportStatus: Object,
    infra: Object,
});

const toast = useToast();
const confirmAction = useConfirmAction();

const isLoading = ref(false);
const isRefreshing = ref(false);
const lastUpdated = ref(new Date());
const lastInfraUpdate = ref(new Date());

const activeFilters = reactive({
    year: props.availableFilters?.years?.[0] || new Date().getFullYear(),
    category: 'all',
    search: '',
});

const yearOptions = computed(() => props.availableFilters?.years || [new Date().getFullYear()]);
const categoryOptions = computed(() => props.availableFilters?.categories || []);

const hasActiveFilters = computed(() => (
    String(activeFilters.search || '').trim() !== ''
    || activeFilters.category !== 'all'
    || Number(activeFilters.year) !== Number(yearOptions.value[0])
));

const infraState = reactive({
    cpu: props.infra?.cpu || 0,
    ram: props.infra?.ram || 0,
    disk: props.infra?.disk || 0,
    latency: props.infra?.db_latency || 0,
    status: props.infra?.status || 'unknown',
    platform: props.infra?.platform || 'unknown',
    uptime: props.infra?.uptime || '',
});

const applyInfra = (infra) => {
    if (!infra) return;
    infraState.cpu = Number(infra.cpu) || 0;
    infraState.ram = Number(infra.ram) || 0;
    infraState.disk = Number(infra.disk) || 0;
    infraState.latency = Number(infra.db_latency) || 0;
    infraState.status = String(infra.status || 'unknown');
    infraState.platform = String(infra.platform || 'unknown');
    infraState.uptime = String(infra.uptime || '');
    lastInfraUpdate.value = new Date();
};

watch(() => props.infra, (value) => applyInfra(value), { deep: true });

const wsLive = ref(false);
let infraEchoChannel = null;

// ======================================================================================
// FORMATAGE
// ======================================================================================
const formatCurrency = (value) => new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
}).format(Number(value) || 0);

const formatNumber = (value) => new Intl.NumberFormat(locale.value).format(Number(value) || 0);

const formatCompact = (value) => new Intl.NumberFormat(locale.value, {
    notation: 'compact',
    maximumFractionDigits: 1,
}).format(Number(value) || 0);

const formatPercent = (value) => `${Math.round(Number(value) || 0)}%`;
const clampPercent = (value) => Math.min(100, Math.max(0, Number(value) || 0));

const timeFormatter = computed(() => new Intl.DateTimeFormat(locale.value, {
    hour: '2-digit',
    minute: '2-digit',
}));
const formatTime = (date) => (date ? timeFormatter.value.format(date) : '—');
const lastUpdatedLabel = computed(() => formatTime(lastUpdated.value));
const lastInfraUpdateLabel = computed(() => formatTime(lastInfraUpdate.value));

const prefersReducedMotion = typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ======================================================================================
// KPI
// ======================================================================================
const revenueTotal = computed(() => Number(props.kpis?.revenue?.total || 0));
const predictedRevenue = computed(() => Number(props.kpis?.revenue?.predicted || 0));
const revenueGrowth = computed(() => Number(props.kpis?.revenue?.growth || 0));
const revenueProgress = computed(() => clampPercent(props.kpis?.revenue?.progress));
const newStudents = computed(() => Number(props.kpis?.students?.new_month || 0));
const completionRate = computed(() => clampPercent(props.kpis?.academic?.completion));
const averageScore = computed(() => Number(props.kpis?.academic?.avg_score || 0));
const certificatesIssued = computed(() => Number(props.kpis?.academic?.certificates_issued || 0));
const cpuUsage = computed(() => clampPercent(infraState.cpu));

const trendRevenueLabel = computed(() => t('pages.dashboard.trendRevenue', {
    year: Number(activeFilters.year) - 1,
}));
const trendNewMonthLabel = computed(() => t('pages.dashboard.trendNewMonth', {
    count: newStudents.value,
}));

// ======================================================================================
// INFRASTRUCTURE
// ======================================================================================
const infraUsage = computed(() => Math.max(
    clampPercent(infraState.cpu),
    clampPercent(infraState.ram),
    clampPercent(infraState.disk),
));

const infraLevel = computed(() => {
    const usage = infraUsage.value;
    const latency = Number(infraState.latency) || 0;

    if (usage >= 90 || latency >= 500 || infraState.status === 'critical') return 'critical';
    if (usage >= 75 || latency >= 200 || infraState.status === 'warning' || infraState.status === 'unknown') return 'warning';
    return 'healthy';
});

const infraStatus = computed(() => ({
    healthy: {
        label: t('pages.dashboard.statusHealthy'),
        hero: 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200',
        card: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
    warning: {
        label: t('pages.dashboard.statusWarning'),
        hero: 'border-amber-400/30 bg-amber-500/10 text-amber-200',
        card: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    critical: {
        label: t('pages.dashboard.statusCritical'),
        hero: 'border-rose-400/30 bg-rose-500/10 text-rose-200',
        card: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
        dot: 'bg-rose-500',
    },
}[infraLevel.value]));

const metricTone = (value) => {
    const level = clampPercent(value);
    if (level >= 90) return { bar: 'bg-rose-500', text: 'text-rose-600 dark:text-rose-400' };
    if (level >= 75) return { bar: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' };
    return { bar: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' };
};

const infraMetrics = computed(() => {
    const cpu = clampPercent(infraState.cpu);
    const ram = clampPercent(infraState.ram);
    const disk = clampPercent(infraState.disk);

    return [
        { key: 'cpu', label: 'CPU', icon: 'pi-desktop', value: cpu, tone: metricTone(cpu), ariaLabel: `CPU : ${cpu}%` },
        { key: 'ram', label: 'RAM', icon: 'pi-server', value: ram, tone: metricTone(ram), ariaLabel: `RAM : ${ram}%` },
        { key: 'disk', label: t('pages.dashboard.disk'), icon: 'pi-database', value: disk, tone: metricTone(disk), ariaLabel: `${t('pages.dashboard.disk')} : ${disk}%` },
    ];
});

// ======================================================================================
// PERFORMANCE DES COURS
// ======================================================================================
const COURSE_STATUS_META = {
    published: {
        label: 'Publié',
        classes: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
    draft: {
        label: 'Brouillon',
        classes: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    archived: {
        label: 'Archivé',
        classes: 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        dot: 'bg-slate-400',
    },
};

const courseStatusMeta = (status) => COURSE_STATUS_META[String(status || '').toLowerCase()] || {
    label: status || '—',
    classes: 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
    dot: 'bg-slate-400',
};

const progressTone = (value) => {
    const progress = Number(value) || 0;
    if (progress >= 75) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-blue-500';
    if (progress > 0) return 'bg-amber-500';
    return 'bg-slate-300 dark:bg-slate-600';
};

const cohortRows = computed(() => (props.cohorts?.data || []).slice(0, 6).map((course) => {
    const progress = clampPercent(course.avg_progress);

    return {
        ...course,
        studentsLabel: formatNumber(course.students),
        revenueLabel: formatCurrency(course.revenue),
        progress,
        progressLabel: formatPercent(progress),
        progressClass: progressTone(progress),
        statusMeta: courseStatusMeta(course.status),
    };
}));

const cohortTotal = computed(() => Number(props.cohorts?.total || props.cohorts?.data?.length || 0));

// ======================================================================================
// OPÉRATIONS & COMMUNAUTÉ
// ======================================================================================
const auditMarkerClass = (type) => {
    if (type === 'error') return 'bg-rose-500';
    if (type === 'success') return 'bg-emerald-500';
    return 'bg-indigo-500';
};

const auditIcon = (type) => (type === 'error' ? 'pi-times' : 'pi-check');

const prioritySeverity = (priority) => {
    const value = String(priority || '').toLowerCase();
    if (value.includes('high') || value.includes('urgent') || value.includes('crit')) return 'danger';
    if (value.includes('med') || value.includes('normal')) return 'warn';
    if (value.includes('low')) return 'success';
    return 'info';
};

const ticketStatusSeverity = (status) => {
    const value = String(status || '').toLowerCase();
    if (value.includes('open') || value.includes('pending') || value.includes('progress')) return 'warn';
    if (value.includes('closed') || value.includes('resolved')) return 'success';
    return 'secondary';
};

const initials = (name) => String(name || '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('') || '?';

const AVATAR_TONES = [
    'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    'bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
];
const avatarTone = (index) => AVATAR_TONES[index % AVATAR_TONES.length];

const rankTone = (index) => {
    if (index === 0) return 'bg-amber-400 text-amber-950';
    if (index === 1) return 'bg-slate-300 text-slate-800';
    if (index === 2) return 'bg-amber-700 text-white';
    return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
};

// ======================================================================================
// GRAPHIQUES
// ======================================================================================
const legendLabels = {
    color: '#94a3b8',
    usePointStyle: true,
    boxWidth: 8,
    boxHeight: 8,
    padding: 16,
    font: { family: 'Inter', weight: '600', size: 11 },
};

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: prefersReducedMotion ? false : { duration: 500 },
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'bottom', labels: { ...legendLabels } },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 12,
            cornerRadius: 10,
            displayColors: true,
            callbacks: {
                label: (context) => ` ${context.dataset.label}: ${formatCurrency(context.parsed?.y ?? 0)}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10, weight: '600' } },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(148, 163, 184, 0.18)' },
            border: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10 }, callback: (value) => formatCompact(value) },
        },
    },
}));

const radarOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: prefersReducedMotion ? false : { duration: 500 },
    plugins: {
        legend: { position: 'bottom', labels: { ...legendLabels } },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 12,
            cornerRadius: 10,
            callbacks: {
                label: (context) => ` ${context.dataset.label}: ${formatNumber(context.parsed?.r ?? 0)}`,
            },
        },
    },
    scales: {
        r: {
            beginAtZero: true,
            grid: { color: 'rgba(148, 163, 184, 0.18)' },
            angleLines: { color: 'rgba(148, 163, 184, 0.18)' },
            pointLabels: { color: '#94a3b8', font: { size: 9, weight: '600' }, padding: 2 },
            ticks: { display: false },
        },
    },
}));

const skillsRadarShort = computed(() => {
    const radar = props.chartData?.skills_radar;
    if (!radar || !Array.isArray(radar.labels)) return radar;
    return {
        ...radar,
        labels: radar.labels.map((label) => (typeof label === 'string' && label.length > 17 ? `${label.slice(0, 15).trimEnd()}…` : label)),
    };
});

const revenueChartAria = computed(() => t('pages.dashboard.chartRevenueAria', {
    year: Number(activeFilters.year),
    previous: Number(activeFilters.year) - 1,
}));

const skillsChartAria = computed(() => t('pages.dashboard.chartSkillsAria'));

// ======================================================================================
// ACTIONS
// ======================================================================================
const applyFilters = debounce(() => {
    isLoading.value = true;

    router.get(route('admin.dashboard'), activeFilters, {
        preserveState: true,
        preserveScroll: true,
        showProgress: false,
        only: ['kpis', 'chartData', 'cohorts'],
        onSuccess: () => {
            lastUpdated.value = new Date();
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: t('common.error'),
                detail: t('pages.dashboard.loadError'),
                life: 5000,
            });
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
}, 350);

const resetFilters = () => {
    activeFilters.year = yearOptions.value[0] || new Date().getFullYear();
    activeFilters.category = 'all';
    activeFilters.search = '';
    applyFilters();
};

const refreshDashboard = () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;
    router.reload({
        showProgress: false,
        only: ['meta', 'kpis', 'chartData', 'cohorts', 'leaderboard', 'activities', 'supportStatus', 'infra'],
        onSuccess: () => {
            lastUpdated.value = new Date();
            lastInfraUpdate.value = new Date();
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: t('common.error'),
                detail: t('pages.dashboard.loadError'),
                life: 5000,
            });
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const headerActions = computed(() => [
    {
        label: t('pages.dashboard.refresh'),
        icon: isRefreshing.value ? 'pi-spin pi-spinner' : 'pi-refresh',
        variant: 'primary',
        disabled: isRefreshing.value,
        onClick: refreshDashboard,
    },
    {
        label: t('pages.dashboard.purgeCache'),
        icon: 'pi-trash',
        variant: 'danger',
        ariaLabel: t('pages.dashboard.purgeHeader'),
        onClick: purgeCache,
    },
]);

// Mises à jour infra via WebSocket uniquement. Le rechargement manuel reste sur le bouton.
const subscribeInfraUpdates = () => {
    if (!window.Echo) return;

    try {
        infraEchoChannel = window.Echo.private('admin.dashboard.metrics')
            .subscribed(() => { wsLive.value = true; })
            .listen('.admin-dashboard.metrics-updated', (event) => {
                applyInfra(event?.infra);
            })
            .error(() => { wsLive.value = false; });
    } catch (e) {
        wsLive.value = false;
    }
};

function purgeCache() {
    confirmAction({
        message: t('pages.dashboard.purgeMessage'),
        header: t('pages.dashboard.purgeHeader'),
        icon: 'pi pi-exclamation-triangle',
        danger: true,
        accept: () => {
            router.post(route('admin.analytics.purge'), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: 'warn',
                        summary: t('pages.dashboard.cachePurged'),
                        detail: t('pages.dashboard.cachePurgedDetail'),
                    });
                    refreshDashboard();
                },
            });
        },
    });
}

onMounted(() => {
    lastUpdated.value = new Date();
    lastInfraUpdate.value = new Date();
    subscribeInfraUpdates();
});

onBeforeUnmount(() => {
    if (infraEchoChannel && window.Echo) {
        window.Echo.leave('admin.dashboard.metrics');
    }
    applyFilters.cancel?.();
});
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.dashboard.headTitle')" />

        <div class="page-bg min-h-screen">
            <PageHeader
                :title="props.meta?.greeting || t('pages.dashboard.fallbackTitle')"
                :subtitle="props.meta?.message || t('pages.dashboard.fallbackSubtitle')"
                icon="pi-chart-bar"
                badge="Admin"
                accent="blue"
                :actions="headerActions"
            >
                <template #meta>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-semibold" :class="infraStatus.hero">
                            <span class="h-1.5 w-1.5 rounded-full" :class="infraStatus.dot" aria-hidden="true"></span>
                            {{ infraStatus.label }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-300">
                            <i class="pi pi-clock text-[10px]" aria-hidden="true"></i>
                            {{ t('pages.dashboard.lastUpdated', { time: lastUpdatedLabel }) }}
                        </span>
                        <!-- <span v-if="props.meta?.version" class="inline-flex items-center rounded-full border border-white/10 px-3 py-1 text-[11px] font-medium text-slate-400">
                            v{{ props.meta.version }}-{{ props.meta.channel || 'stable' }}
                        </span> -->
                    </div>
                </template>
            </PageHeader>

            <div class="page-container space-y-6">
                <!-- Filtres fonctionnels : recherche, année et catégorie -->
                <form
                    class="page-card-flush p-4 md:p-5"
                    role="search"
                    :aria-label="t('pages.dashboard.filtersAria')"
                    @submit.prevent="applyFilters"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="dashboard-search" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ t('pages.dashboard.filterSearch') }}
                            </label>
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText
                                    id="dashboard-search"
                                    v-model="activeFilters.search"
                                    :placeholder="t('pages.dashboard.searchPlaceholder')"
                                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800"
                                    @input="applyFilters"
                                />
                            </IconField>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:items-end">
                            <div>
                                <label for="dashboard-year" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    {{ t('pages.dashboard.filterYear') }}
                                </label>
                                <select
                                    id="dashboard-year"
                                    v-model.number="activeFilters.year"
                                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 lg:min-w-[7.5rem]"
                                    @change="applyFilters"
                                >
                                    <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="dashboard-category" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    {{ t('pages.dashboard.filterCategory') }}
                                </label>
                                <select
                                    id="dashboard-category"
                                    v-model="activeFilters.category"
                                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 lg:min-w-[11rem]"
                                    @change="applyFilters"
                                >
                                    <option value="all">{{ t('pages.dashboard.filterAllCategories') }}</option>
                                    <option v-for="category in categoryOptions" :key="category.id" :value="String(category.id)">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            :disabled="!hasActiveFilters"
                            @click="resetFilters"
                        >
                            <i class="pi pi-filter-slash" aria-hidden="true"></i>
                            {{ t('common.resetFilters') }}
                        </button>
                    </div>
                    <ContextHint id="dashboard-filters" :message="t('pages.dashboard.filtersHint')" />
                </form>

                <p class="sr-only" aria-live="polite">
                    {{ isLoading ? t('pages.dashboard.updating') : '' }}
                </p>

                <!-- KPI -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:gap-6 xl:grid-cols-4">
                    <PageKpi
                        :label="t('pages.dashboard.kpiRevenue')"
                        :value="formatCurrency(revenueTotal)"
                        icon="pi-dollar"
                        color="blue"
                        :trend="revenueGrowth"
                        :trend-label="trendRevenueLabel"
                        :subtext="t('pages.dashboard.kpiRevenueSub', { amount: formatCurrency(predictedRevenue) })"
                        :loading="isLoading"
                    >
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <span>{{ t('pages.dashboard.targetProgress') }}</span>
                                <span>{{ formatPercent(revenueProgress) }}</span>
                            </div>
                            <div
                                class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                role="meter"
                                :aria-valuenow="revenueProgress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-label="t('pages.dashboard.targetProgress')"
                            >
                                <div class="h-full rounded-full bg-blue-600 transition-all duration-500" :style="{ width: `${revenueProgress}%` }"></div>
                            </div>
                        </div>
                    </PageKpi>

                    <PageKpi
                        :label="t('pages.dashboard.kpiStudents')"
                        :value="formatNumber(props.kpis?.students?.total || 0)"
                        icon="pi-users"
                        color="indigo"
                        :trend="newStudents"
                        trend-suffix=""
                        :trend-label="trendNewMonthLabel"
                        :subtext="t('pages.dashboard.kpiStudentsSub', { rate: props.kpis?.students?.churn || 0 })"
                        :loading="isLoading"
                    />

                    <PageKpi
                        :label="t('pages.dashboard.kpiCompletion')"
                        :value="formatPercent(completionRate)"
                        icon="pi-graduation-cap"
                        color="emerald"
                        :subtext="t('pages.dashboard.kpiCompletionSub', { count: certificatesIssued })"
                        :loading="isLoading"
                    >
                        <div class="mt-4 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            {{ t('pages.dashboard.kpiAvgScore', { score: formatPercent(averageScore) }) }}
                        </div>
                    </PageKpi>

                    <PageKpi
                        :label="t('pages.dashboard.kpiSystemHealth')"
                        :value="formatPercent(cpuUsage)"
                        icon="pi-server"
                        color="violet"
                        :subtext="t('pages.dashboard.kpiHealthSub', { latency: infraState.latency })"
                        :loading="isLoading"
                    >
                        <div class="mt-4 flex flex-wrap gap-2 text-[10px] font-bold">
                            <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                RAM {{ clampPercent(infraState.ram) }}%
                            </span>
                            <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ t('pages.dashboard.disk') }} {{ clampPercent(infraState.disk) }}%
                            </span>
                        </div>
                    </PageKpi>
                </div>

                <!-- Analytique -->
                <PageSection :title="t('pages.dashboard.analyticsTitle')" :subtitle="t('pages.dashboard.analyticsSubtitle')">
                    <div class="grid grid-cols-1 gap-4 transition-opacity duration-200 md:gap-6 md:grid-cols-2 xl:grid-cols-3" :class="isLoading ? 'opacity-60' : ''">
                        <PageCard class="min-w-0 xl:col-span-2" :title="t('pages.dashboard.chartRevenueTitle')" :subtitle="t('pages.dashboard.chartRevenueSubtitle')">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                        {{ t('pages.dashboard.revenueYear', { year: activeFilters.year }) }}
                                    </p>
                                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                        {{ formatCurrency(revenueTotal) }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold"
                                    :class="revenueGrowth >= 0
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'"
                                >
                                    <i :class="['pi', revenueGrowth >= 0 ? 'pi-arrow-up-right' : 'pi-arrow-down-right', 'text-[10px]']" aria-hidden="true"></i>
                                    {{ revenueGrowth > 0 ? '+' : '' }}{{ revenueGrowth }}%
                                </span>
                            </div>
                            <div class="relative mt-4 h-[260px] min-w-0 sm:h-[300px]" role="img" :aria-label="revenueChartAria">
                                <Chart
                                    v-if="props.chartData?.revenue_evolution"
                                    type="line"
                                    :data="props.chartData.revenue_evolution"
                                    :options="chartOptions"
                                    class="h-full w-full"
                                    aria-hidden="true"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-400">
                                    {{ t('pages.dashboard.noData') }}
                                </div>
                            </div>
                        </PageCard>

                        <PageCard class="min-w-0" :title="t('pages.dashboard.chartSkillsTitle')" :subtitle="t('pages.dashboard.chartSkillsSubtitle')">
                            <div class="relative h-[280px] min-w-0 sm:h-[340px]" role="img" :aria-label="skillsChartAria">
                                <Chart
                                    v-if="props.chartData?.skills_radar"
                                    type="radar"
                                     :data="skillsRadarShort"
                                     :options="radarOptions"
                                    class="h-full w-full"
                                    aria-hidden="true"
                                />
                                <div v-else class="flex h-full items-center justify-center text-sm text-slate-400">
                                    {{ t('pages.dashboard.noData') }}
                                </div>
                            </div>
                        </PageCard>
                    </div>
                </PageSection>

                <!-- Performance des cours (les filtres agissent sur cette table) -->
                <PageSection
                    :title="t('pages.dashboard.coursesTitle')"
                    :subtitle="t('pages.dashboard.coursesSubtitle', { count: cohortTotal })"
                    :aria-busy="isLoading"
                >
                    <PageCard :padded="false" class="overflow-hidden">
                        <div v-if="isLoading" class="space-y-3 p-5" aria-hidden="true">
                            <SncSkeleton v-for="i in 4" :key="i" variant="rectangular" class="h-12" />
                        </div>

                        <div v-else-if="!cohortRows.length" class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500" aria-hidden="true">
                                <i class="pi pi-inbox text-xl"></i>
                            </span>
                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ t('pages.dashboard.coursesEmpty') }}</p>
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="resetFilters"
                            >
                                <i class="pi pi-filter-slash" aria-hidden="true"></i>
                                {{ t('common.resetFilters') }}
                            </button>
                        </div>

                        <template v-else>
                            <!-- Tableau desktop -->
                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full text-sm">
                                    <caption class="sr-only">{{ t('pages.dashboard.coursesTitle') }}</caption>
                                    <thead>
                                        <tr class="border-b border-slate-100 bg-slate-50/70 text-left dark:border-slate-800 dark:bg-slate-800/40">
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.coursesTitle') }}</th>
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.coursesInstructor') }}</th>
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.coursesStudents') }}</th>
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.coursesProgress') }}</th>
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.kpiRevenue') }}</th>
                                            <th scope="col" class="px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('common.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="course in cohortRows" :key="course.id" class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                            <td class="px-5 py-4">
                                                <p class="max-w-[22rem] truncate text-sm font-bold text-slate-800 dark:text-slate-100" :title="course.title">{{ course.title }}</p>
                                                <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ course.category }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-xs font-medium text-slate-600 dark:text-slate-300">{{ course.instructor }}</td>
                                            <td class="px-5 py-4 text-xs font-bold tabular-nums text-slate-700 dark:text-slate-200">{{ course.studentsLabel }}</td>
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                                        role="meter"
                                                        :aria-valuenow="course.progress"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        :aria-label="`${course.title} : ${course.progressLabel}`"
                                                    >
                                                        <div class="h-full rounded-full" :class="course.progressClass" :style="{ width: `${course.progress}%` }"></div>
                                                    </div>
                                                    <span class="text-[11px] font-bold tabular-nums text-slate-500 dark:text-slate-400">{{ course.progressLabel }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-xs font-bold tabular-nums text-slate-700 dark:text-slate-200">{{ course.revenueLabel }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide" :class="course.statusMeta.classes">
                                                    <span class="h-1.5 w-1.5 rounded-full" :class="course.statusMeta.dot" aria-hidden="true"></span>
                                                    {{ course.statusMeta.label }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Cartes mobile -->
                            <ul class="divide-y divide-slate-100 lg:hidden dark:divide-slate-800">
                                <li v-for="course in cohortRows" :key="course.id" class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ course.title }}</p>
                                            <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ course.category }} · {{ course.instructor }}</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="course.statusMeta.classes">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="course.statusMeta.dot" aria-hidden="true"></span>
                                            {{ course.statusMeta.label }}
                                        </span>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.coursesStudents') }}</p>
                                            <p class="mt-0.5 text-sm font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ course.studentsLabel }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.kpiRevenue') }}</p>
                                            <p class="mt-0.5 text-sm font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ course.revenueLabel }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            <span>{{ t('pages.dashboard.coursesProgress') }}</span>
                                            <span>{{ course.progressLabel }}</span>
                                        </div>
                                        <div
                                            class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                            role="meter"
                                            :aria-valuenow="course.progress"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            :aria-label="`${course.title} : ${course.progressLabel}`"
                                        >
                                            <div class="h-full rounded-full" :class="course.progressClass" :style="{ width: `${course.progress}%` }"></div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </template>
                    </PageCard>
                </PageSection>

                <!-- Infrastructure -->
                <PageSection :title="t('pages.dashboard.infraTitle')" :subtitle="t('pages.dashboard.infraSubtitle')">
                    <PageCard>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold" :class="infraStatus.card">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="infraStatus.dot" aria-hidden="true"></span>
                                    {{ infraStatus.label }}
                                </span>
                                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('pages.dashboard.infraLastMeasure', { time: lastInfraUpdateLabel }) }}
                                </span>
                                <span
                                    v-if="wsLive"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                                    :title="t('pages.dashboard.realtimeHint')"
                                >
                                    <i class="pi pi-wifi text-[10px]" aria-hidden="true"></i>
                                    {{ t('pages.dashboard.realtime') }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400"
                                    :title="t('pages.dashboard.manualHint')"
                                >
                                    <i class="pi pi-refresh text-[10px]" aria-hidden="true"></i>
                                    {{ t('pages.dashboard.manualUpdate') }}
                                </span>
                            </div>
                            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                {{ t('pages.dashboard.infraThresholds') }}
                            </p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div
                                v-for="metric in infraMetrics"
                                :key="metric.key"
                                class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-800/40"
                                role="meter"
                                :aria-valuenow="metric.value"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-label="metric.ariaLabel"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <p class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                                        <i :class="['pi', metric.icon, 'text-slate-400']" aria-hidden="true"></i>
                                        {{ metric.label }}
                                    </p>
                                    <p class="text-sm font-extrabold tabular-nums" :class="metric.tone.text">{{ metric.value }}%</p>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200/70 dark:bg-slate-700/60" aria-hidden="true">
                                    <div class="h-full rounded-full transition-all duration-500" :class="metric.tone.bar" :style="{ width: `${metric.value}%` }"></div>
                                </div>
                            </div>
                        </div>

                        <dl class="mt-5 grid grid-cols-1 gap-px overflow-hidden rounded-xl border border-slate-100 bg-slate-100 sm:grid-cols-3 dark:border-slate-800 dark:bg-slate-800">
                            <div class="bg-white p-4 dark:bg-slate-900">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.dbLatency') }}</dt>
                                <dd class="mt-1 text-sm font-extrabold tabular-nums text-slate-800 dark:text-slate-100">{{ infraState.latency }} ms</dd>
                            </div>
                            <div class="bg-white p-4 dark:bg-slate-900">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.platform') }}</dt>
                                <dd class="mt-1 truncate text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ infraState.platform }}</dd>
                            </div>
                            <div class="bg-white p-4 dark:bg-slate-900">
                                <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Uptime</dt>
                                <dd class="mt-1 text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ infraState.uptime || t('pages.dashboard.uptimeRunning') }}</dd>
                            </div>
                        </dl>
                    </PageCard>
                </PageSection>

                <!-- Opérations -->
                <PageSection :title="t('pages.dashboard.operationsTitle')" :subtitle="t('pages.dashboard.operationsSubtitle')">
                    <div class="grid grid-cols-1 gap-4 md:gap-6 xl:grid-cols-2">
                        <PageCard
                            class="min-w-0"
                            :title="t('pages.dashboard.auditTitle')"
                            :subtitle="t('pages.dashboard.auditSubtitle', { count: props.activities?.length || 0 })"
                        >
                            <ScrollPanel style="height: 320px" class="pr-2">
                                <Timeline v-if="props.activities?.length" :value="props.activities">
                                    <template #marker="slotProps">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-white shadow-md" :class="auditMarkerClass(slotProps.item.type)">
                                            <i :class="['pi', auditIcon(slotProps.item.type), 'text-[10px]']" aria-hidden="true"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <div class="mb-5">
                                            <p class="text-xs font-bold leading-snug text-slate-800 dark:text-slate-100">{{ slotProps.item.action }}</p>
                                            <p class="mt-1 text-[11px] font-medium italic text-slate-500 dark:text-slate-400">{{ slotProps.item.description }}</p>
                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                                                <span class="font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400">{{ slotProps.item.user }}</span>
                                                <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">•</span>
                                                <span class="font-medium text-slate-500 dark:text-slate-400">{{ slotProps.item.time }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Timeline>
                                <div v-else class="flex h-full flex-col items-center justify-center gap-2 py-8 text-center">
                                    <i class="pi pi-history text-2xl text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.noRecentActivity') }}</p>
                                </div>
                            </ScrollPanel>
                        </PageCard>

                        <PageCard
                            class="min-w-0"
                            :title="t('pages.dashboard.supportTickets')"
                            :subtitle="t('pages.dashboard.openTickets', { count: props.supportStatus?.open_tickets || 0 })"
                        >
                            <div class="mb-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.supportAvgResponse') }}</p>
                                    <p class="mt-1 text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ props.supportStatus?.avg_response_time || '—' }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.supportSatisfaction') }}</p>
                                    <p class="mt-1 text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ props.supportStatus?.satisfaction_rate || '—' }}</p>
                                </div>
                            </div>

                            <ul v-if="props.supportStatus?.recent_tickets?.length" class="max-h-[260px] space-y-3 overflow-y-auto pr-1">
                                <li
                                    v-for="ticket in props.supportStatus.recent_tickets"
                                    :key="ticket.id"
                                    class="rounded-xl border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="font-mono text-[10px] font-bold text-slate-400">#{{ ticket.id }}</span>
                                        <Tag :value="ticket.priority" :severity="prioritySeverity(ticket.priority)" class="text-[10px] font-bold uppercase" />
                                    </div>
                                    <p class="mt-2 text-xs font-bold leading-snug text-slate-700 dark:text-slate-200">{{ ticket.subject }}</p>
                                    <div class="mt-2">
                                        <Tag :value="ticket.status" :severity="ticketStatusSeverity(ticket.status)" class="text-[10px] font-bold uppercase" />
                                    </div>
                                </li>
                            </ul>
                            <div v-else class="flex flex-col items-center justify-center gap-2 py-10 text-center">
                                <i class="pi pi-ticket text-2xl text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('pages.dashboard.noOpenTickets') }}</p>
                            </div>
                        </PageCard>
                    </div>
                </PageSection>

                <!-- Classement -->
                <PageSection v-if="props.leaderboard?.length" :title="t('pages.dashboard.hallOfFame')" :subtitle="t('pages.dashboard.topLearners')">
                    <PageCard :padded="false">
                        <ol class="divide-y divide-slate-100 dark:divide-slate-800">
                            <li
                                v-for="(student, index) in props.leaderboard.slice(0, 5)"
                                :key="student.id"
                                class="flex items-center gap-3 px-4 py-4 sm:gap-4 sm:px-5"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold" :class="rankTone(index)">
                                    {{ index + 1 }}
                                </span>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold" :class="avatarTone(index)" aria-hidden="true">
                                    {{ initials(student.name) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ student.name }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ t('pages.dashboard.leaderboardCourses', { count: student.courses_completed || 0 }) }}
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold tabular-nums text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                    <i class="pi pi-bolt text-[9px]" aria-hidden="true"></i>
                                    {{ formatNumber(student.points || 0) }} XP
                                </span>
                            </li>
                        </ol>
                    </PageCard>
                </PageSection>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-timeline-event-opposite) { display: none; }
:deep(.p-timeline-event-content) { padding-left: 0.75rem; }
:deep(.p-timeline-event-separator) { flex: 0 0 auto; }
</style>
