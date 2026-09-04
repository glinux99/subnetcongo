<script setup>
/**
 * PageHeader — En-tête de page unifié (style StudentReport)
 * Usage : <PageHeader title="..." subtitle="..." badge="..." :actions="[...]" />
 */
defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
    badge:    { type: String, default: '' },
    icon:     { type: String, default: 'pi-th-large' }, // classe PrimeIcon
    accent:   { type: String, default: 'red' },         // red|indigo|violet|emerald|amber|blue
    actions:  { type: Array, default: () => [] },        // [{ label, icon, onClick, severity, ... }]
});

const accentMap = {
    red:     { bg: 'bg-red-600/15',     glow: 'bg-red-600/15',   icon: 'bg-red-600',     badge: 'bg-red-500/10 text-red-500 border-red-500/20' },
    indigo:  { bg: 'bg-indigo-600/15',  glow: 'bg-indigo-600/15', icon: 'bg-indigo-600',  badge: 'bg-indigo-500/10 text-indigo-500 border-indigo-500/20' },
    violet:  { bg: 'bg-violet-600/15',  glow: 'bg-violet-600/15', icon: 'bg-violet-600',  badge: 'bg-violet-500/10 text-violet-500 border-violet-500/20' },
    emerald: { bg: 'bg-emerald-600/15', glow: 'bg-emerald-600/15', icon: 'bg-emerald-600', badge: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
    amber:   { bg: 'bg-amber-500/15',   glow: 'bg-amber-500/15',  icon: 'bg-amber-500',   badge: 'bg-amber-500/10 text-amber-500 border-amber-500/20' },
    blue:    { bg: 'bg-blue-600/15',    glow: 'bg-blue-600/15',   icon: 'bg-blue-600',    badge: 'bg-blue-500/10 text-blue-500 border-blue-500/20' },
    slate:   { bg: 'bg-slate-600/15',   glow: 'bg-slate-600/15',  icon: 'bg-slate-600',   badge: 'bg-slate-500/10 text-slate-500 border-slate-500/20' },
};
</script>

<template>
    <header class="bg-[#0F172A] pt-12 pb-28 px-8 relative overflow-hidden">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full blur-[120px] pointer-events-none" :class="accentMap[accent]?.bg || accentMap.red.bg"></div>
        <div class="absolute bottom-[-20%] left-[-5%] w-[400px] h-[400px] bg-red-900/10 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-[0.07] mix-blend-overlay pointer-events-none"></div>

        <div class="max-w-[1500px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center shadow-2xl border border-white/10" :class="accentMap[accent]?.icon || accentMap.red.icon">
                    <i :class="['pi', icon, 'text-white text-3xl']"></i>
                </div>
                <div>
                    <div v-if="badge" class="flex items-center justify-center md:justify-start gap-2 mb-3 flex-wrap">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="accentMap[accent]?.badge || accentMap.red.badge">
                            {{ badge }}
                        </span>
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tighter italic uppercase leading-none">{{ title }}</h1>
                    <p v-if="subtitle" class="text-slate-400 mt-3 font-medium text-sm italic max-w-2xl">{{ subtitle }}</p>
                </div>
            </div>

            <div v-if="actions && actions.length" class="flex flex-wrap gap-3 justify-center">
                <template v-for="(action, idx) in actions" :key="idx">
                    <button
                        v-if="action.href"
                        :type="action.type || 'button'"
                        :class="[
                            action.variant === 'primary'
                                ? '!bg-red-600 hover:!bg-red-700 !border-none !text-white shadow-xl shadow-red-600/20 transition-all active:scale-95'
                                : '!bg-white/5 hover:!bg-white/10 !border-white/10 !text-white backdrop-blur-xl',
                            '!rounded-xl !px-6 !py-3 !font-black !uppercase !tracking-widest !text-[10px]'
                        ]"
                        @click="action.onClick && action.onClick()"
                    >
                        <i v-if="action.icon" :class="['pi', action.icon, 'mr-2']"></i>
                        {{ action.label }}
                    </button>
                    <a
                        v-else
                        :href="action.href"
                        :class="[
                            action.variant === 'primary'
                                ? '!bg-red-600 hover:!bg-red-700 !border-none !text-white shadow-xl shadow-red-600/20 transition-all active:scale-95'
                                : '!bg-white/5 hover:!bg-white/10 !border-white/10 !text-white backdrop-blur-xl',
                            'inline-flex items-center !rounded-xl !px-6 !py-3 !font-black !uppercase !tracking-widest !text-[10px]'
                        ]"
                    >
                        <i v-if="action.icon" :class="['pi', action.icon, 'mr-2']"></i>
                        {{ action.label }}
                    </a>
                </template>
            </div>
        </div>
    </header>
</template>
