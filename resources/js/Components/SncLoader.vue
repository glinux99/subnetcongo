<script setup>
/**
 * SncLoader — Spinner de chargement standardisé du design system SUBNETCONGO.
 * Usage : <SncLoader size="md" text="Chargement..." />
 */
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v)
    },
    text: { type: String, default: '' },
    overlay: { type: Boolean, default: false },
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'w-4 h-4 text-sm',
        md: 'w-6 h-6 text-lg',
        lg: 'w-8 h-8 text-xl',
        xl: 'w-12 h-12 text-2xl',
    };
    return sizes[props.size];
});
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center gap-3',
            overlay ? 'fixed inset-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm z-50' : ''
        ]"
        role="status"
        aria-live="polite"
    >
        <i :class="['pi pi-spin pi-spinner text-blue-600 dark:text-blue-400', sizeClasses]" aria-hidden="true"></i>
        <span v-if="text" class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ text }}</span>
    </div>
</template>
