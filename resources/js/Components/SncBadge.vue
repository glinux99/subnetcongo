<script setup>
/**
 * SncBadge — Badge standardisé du design system SUBNETCONGO.
 * Usage : <SncBadge variant="success">Nouveau</SncBadge>
 */
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(v)
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v)
    },
    dot: { type: Boolean, default: false },
});

const classes = computed(() => {
    const sizeClasses = {
        sm: 'px-2 py-0.5 text-[10px]',
        md: 'px-2.5 py-1 text-xs',
        lg: 'px-3 py-1.5 text-sm',
    };

    const variantClasses = {
        default: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        primary: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
        success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        warning: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
        danger: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
        info: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    };

    return [
        'inline-flex items-center gap-1.5 font-bold rounded-lg uppercase tracking-wider',
        sizeClasses[props.size],
        variantClasses[props.variant],
    ];
});
</script>

<template>
    <span :class="classes">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
        <slot />
    </span>
</template>
