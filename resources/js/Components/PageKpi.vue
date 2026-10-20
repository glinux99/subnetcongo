<script setup>
/**
 * PageKpi — Carte indicateur.
 * Usage : <PageKpi label="..." :value="12" icon="pi-book" color="blue" :trend="12" subtext="..." :loading="false" />
 */
defineProps({
    label:   { type: String, required: true },
    value:   { type: [String, Number], default: '' },
    icon:    { type: String, default: 'pi-chart-bar' },
    color:   { type: String, default: 'blue' },
    trend:   { type: [Number, String], default: null },
    subtext: { type: String, default: '' },
    loading: { type: Boolean, default: false },
});

const colorMap = {
    blue:    'text-blue-600 bg-blue-50 dark:text-blue-300 dark:bg-blue-500/15',
    indigo:  'text-indigo-600 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-500/15',
    violet:  'text-violet-600 bg-violet-50 dark:text-violet-300 dark:bg-violet-500/15',
    emerald: 'text-emerald-600 bg-emerald-50 dark:text-emerald-300 dark:bg-emerald-500/15',
    amber:   'text-amber-600 bg-amber-50 dark:text-amber-300 dark:bg-amber-500/15',
    red:     'text-red-600 bg-red-50 dark:text-red-300 dark:bg-red-500/15',
    rose:    'text-rose-600 bg-rose-50 dark:text-rose-300 dark:bg-rose-500/15',
    sky:     'text-sky-600 bg-sky-50 dark:text-sky-300 dark:bg-sky-500/15',
    slate:   'text-slate-700 bg-slate-100 dark:text-slate-200 dark:bg-slate-700/60',
};
</script>

<template>
    <div class="page-kpi">
        <div class="flex justify-between items-start mb-4">
            <div :class="['page-kpi-icon-wrap', colorMap[color] || colorMap.blue]" aria-hidden="true">
                <i :class="['pi', icon, 'text-lg']"></i>
            </div>
            <span
                v-if="!loading && trend !== null && trend !== undefined && trend !== ''"
                :class="['page-kpi-trend', Number(trend) >= 0 ? 'page-kpi-trend-up' : 'page-kpi-trend-down']"
            >
                {{ Number(trend) > 0 ? '+' : '' }}{{ trend }}%
            </span>
        </div>
        <p class="page-kpi-label">{{ label }}</p>
        <div v-if="loading" class="h-8 w-20 rounded-md bg-slate-100 dark:bg-slate-800 animate-pulse" aria-hidden="true"></div>
        <p v-else class="page-kpi-value">{{ value }}</p>
        <p v-if="subtext" class="text-xs text-slate-500 dark:text-slate-400 mt-2">{{ subtext }}</p>
        <slot />
    </div>
</template>
