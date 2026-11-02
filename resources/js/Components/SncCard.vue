<script setup>
/**
 * SncCard — Carte standardisée du design system SUBNETCONGO.
 * Usage : <SncCard title="Titre" hoverable>
 *   <template #header>actions</template>
 *   contenu
 *   <template #footer>...</template>
 * </SncCard>
 */
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    padded: { type: Boolean, default: true },
    hoverable: { type: Boolean, default: false },
    bordered: { type: Boolean, default: true },
    as: { type: String, default: 'div' },
});

const cardClasses = computed(() => {
    const base = 'bg-white dark:bg-slate-800 rounded-xl transition-all duration-200';
    const border = props.bordered ? 'border border-slate-200 dark:border-slate-700' : '';
    const hover = props.hoverable ? 'hover:shadow-lg hover:-translate-y-0.5' : 'shadow-sm';
    return [base, border, hover];
});
</script>

<template>
    <component :is="as" :class="cardClasses">
        <div v-if="title || $slots.header" class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 v-if="title" class="font-bold text-slate-900 dark:text-slate-100">{{ title }}</h3>
                <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.header" class="flex items-center gap-2">
                <slot name="header" />
            </div>
        </div>
        <div :class="padded ? 'p-5' : ''">
            <slot />
        </div>
        <div v-if="$slots.footer" class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 rounded-b-xl">
            <slot name="footer" />
        </div>
    </component>
</template>
