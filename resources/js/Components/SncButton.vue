<script setup>
/**
 * SncButton — Bouton standardisé du design system SUBNETCONGO.
 * Usage : <SncButton variant="primary" icon="pi-check" loading :disabled>Label</SncButton>
 *
 * Variantes : primary, secondary, danger, success, ghost
 * Tailles : sm, md (default), lg
 */
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'danger', 'success', 'ghost'].includes(v)
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v)
    },
    icon: { type: String, default: '' },
    iconPosition: {
        type: String,
        default: 'left',
        validator: (v) => ['left', 'right'].includes(v)
    },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
    ariaLabel: { type: String, default: '' },
});

const baseClasses = computed(() => {
    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs min-h-[2rem]',
        md: 'px-4 py-2 text-sm min-h-[2.5rem]',
        lg: 'px-5 py-2.5 text-base min-h-[3rem]',
    };

    const variantClasses = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 focus:ring-offset-2 shadow-lg shadow-blue-500/20',
        secondary: 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 focus:ring-slate-500 focus:ring-offset-2 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 focus:ring-offset-2 shadow-lg shadow-red-500/20',
        success: 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 focus:ring-offset-2 shadow-lg shadow-emerald-500/20',
        ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 focus:ring-slate-500 focus:ring-offset-0 dark:text-slate-300 dark:hover:bg-slate-800',
    };

    return [
        'inline-flex items-center justify-center gap-2 font-bold rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none',
        sizeClasses[props.size],
        variantClasses[props.variant],
    ];
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :aria-label="ariaLabel || undefined"
        :class="baseClasses"
    >
        <i v-if="loading" class="pi pi-spin pi-spinner" aria-hidden="true"></i>
        <i v-else-if="icon && iconPosition === 'left'" :class="['pi', icon]" aria-hidden="true"></i>
        <span v-if="$slots.default"><slot /></span>
        <i v-if="!loading && icon && iconPosition === 'right'" :class="['pi', icon]" aria-hidden="true"></i>
    </button>
</template>
