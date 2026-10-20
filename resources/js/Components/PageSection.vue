<script setup>
/**
 * PageSection — Section titrée.
 * Usage : <PageSection title="Titre" subtitle="..." :pulse="true">...</PageSection>
 */
defineProps({
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
    pulse:    { type: Boolean, default: false },
    accent:   { type: String, default: 'blue' },
});

const accentMap = {
    blue: 'bg-blue-600', red: 'bg-red-600', indigo: 'bg-indigo-600', violet: 'bg-violet-600',
    emerald: 'bg-emerald-600', amber: 'bg-amber-500', slate: 'bg-slate-600',
};
</script>

<template>
    <section class="page-section">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h2 class="page-section-title">
                    <span v-if="pulse" class="w-2 h-2 rounded-full animate-pulse-dot" :class="accentMap[accent] || accentMap.blue" aria-hidden="true"></span>
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="page-section-subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </div>
        <slot />
    </section>
</template>
