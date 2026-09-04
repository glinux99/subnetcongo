<script setup>
/**
 * PageSection — Section avec titre (style StudentReport)
 * Usage :
 *   <PageSection title="Titre" subtitle="..." :pulse="true">
 *     ...
 *   </PageSection>
 */
defineProps({
    title:       { type: String, required: true },
    subtitle:    { type: String, default: '' },
    pulse:       { type: Boolean, default: false },
    accent:      { type: String, default: 'red' },
});

const accentMap = {
    red: 'bg-red-600', indigo: 'bg-indigo-600', violet: 'bg-violet-600',
    emerald: 'bg-emerald-600', amber: 'bg-amber-500', blue: 'bg-blue-600', slate: 'bg-slate-600',
};
</script>

<template>
    <section class="space-y-6">
        <div>
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                <span v-if="pulse" class="w-2 h-2 rounded-full animate-pulse" :class="accentMap[accent] || accentMap.red"></span>
                {{ title }}
            </h2>
            <p v-if="subtitle" class="text-xs text-slate-500 font-medium italic mt-1">{{ subtitle }}</p>
        </div>
        <slot />
    </section>
</template>
