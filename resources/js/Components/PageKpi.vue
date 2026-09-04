<script setup>
/**
 * PageKpi — Carte KPI (style StudentReport)
 * Usage : <PageKpi label="..." value="..." icon="..." color="red" :trend="12" />
 */
defineProps({
    label:   { type: String, required: true },
    value:   { type: [String, Number], required: true },
    icon:    { type: String, default: 'pi-chart-bar' },
    color:   { type: String, default: 'red' }, // red|indigo|emerald|amber|blue|slate|violet
    trend:   { type: [Number, String], default: null },
    subtext: { type: String, default: '' },
});

const colorMap = {
    red:     { icon: 'text-red-600',     bg: 'bg-red-50' },
    indigo:  { icon: 'text-indigo-600',  bg: 'bg-indigo-50' },
    emerald: { icon: 'text-emerald-600', bg: 'bg-emerald-50' },
    amber:   { icon: 'text-amber-600',   bg: 'bg-amber-50' },
    blue:    { icon: 'text-blue-600',    bg: 'bg-blue-50' },
    slate:   { icon: 'text-slate-900',   bg: 'bg-slate-100' },
    violet:  { icon: 'text-violet-600',  bg: 'bg-violet-50' },
    rose:    { icon: 'text-rose-600',    bg: 'bg-rose-50' },
};
</script>

<template>
    <div class="bg-white p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-xl transition-all group">
        <div class="flex justify-between items-start mb-4">
            <div :class="[(colorMap[color] || colorMap.red).bg, 'w-10 h-10 rounded-xl flex items-center justify-center group-hover:rotate-6 transition-transform']">
                <i :class="['pi', icon, (colorMap[color] || colorMap.red).icon, 'text-lg']"></i>
            </div>
            <span
                v-if="trend !== null && trend !== undefined"
                :class="['!text-[9px] !font-black !px-2 !py-1 !rounded-md', Number(trend) >= 0 ? '!bg-emerald-50 !text-emerald-600' : '!bg-rose-50 !text-rose-600']"
            >
                {{ Number(trend) > 0 ? '+' : '' }}{{ trend }}%
            </span>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ label }}</p>
        <h2 class="text-3xl font-black text-slate-900 tracking-tighter italic">{{ value }}</h2>
        <p v-if="subtext" class="text-[10px] text-slate-500 font-bold mt-2 italic">{{ subtext }}</p>
    </div>
</template>
