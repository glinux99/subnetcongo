<script setup>
/**
 * SncSkeleton — Skeleton loader standardisé du design system SUBNETCONGO.
 * Usage : <SncSkeleton variant="text" class="w-32 h-4" />
 *
 * Variantes : text, circular, rectangular, avatar
 */
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'text',
        validator: (v) => ['text', 'circular', 'rectangular', 'avatar'].includes(v)
    },
    count: { type: Number, default: 1 },
    class: { type: String, default: '' },
});

const variantClasses = computed(() => {
    const base = 'animate-pulse bg-slate-200 dark:bg-slate-700';
    const variants = {
        text: 'rounded h-4',
        circular: 'rounded-full',
        rectangular: 'rounded-lg',
        avatar: 'rounded-full',
    };
    return [base, variants[props.variant]];
});
</script>

<template>
    <div v-for="i in count" :key="i" :class="[variantClasses, props.class]" aria-hidden="true" />
</template>
