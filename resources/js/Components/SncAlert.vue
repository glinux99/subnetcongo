<script setup>
/**
 * SncAlert — Message d'alerte standardisé du design system SUBNETCONGO.
 * Usage : <SncAlert variant="error" title="Erreur" dismissible @dismiss="...">
 * Les messages d'alerte doivent avoir aria-live="polite" pour l'accessibilité.
 */
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (v) => ['info', 'success', 'warning', 'error'].includes(v)
    },
    title: { type: String, default: '' },
    dismissible: { type: Boolean, default: false },
});

const emit = defineEmits(['dismiss']);

const classes = computed(() => {
    const base = 'rounded-xl border p-4 flex gap-3';
    const variants = {
        info: 'bg-blue-50 border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/30',
        success: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/30',
        warning: 'bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/30',
        error: 'bg-red-50 border-red-200 dark:bg-red-500/10 dark:border-red-500/30',
    };
    return [base, variants[props.variant]];
});

const iconMap = {
    info: 'pi-info-circle text-blue-600 dark:text-blue-400',
    success: 'pi-check-circle text-emerald-600 dark:text-emerald-400',
    warning: 'pi-exclamation-triangle text-amber-600 dark:text-amber-400',
    error: 'pi-times-circle text-red-600 dark:text-red-400',
};

const titleColor = computed(() => {
    const colors = {
        info: 'text-blue-800 dark:text-blue-200',
        success: 'text-emerald-800 dark:text-emerald-200',
        warning: 'text-amber-800 dark:text-amber-200',
        error: 'text-red-800 dark:text-red-200',
    };
    return colors[props.variant];
});

const textColor = computed(() => {
    const colors = {
        info: 'text-blue-700 dark:text-blue-300',
        success: 'text-emerald-700 dark:text-emerald-300',
        warning: 'text-amber-700 dark:text-amber-300',
        error: 'text-red-700 dark:text-red-300',
    };
    return colors[props.variant];
});
</script>

<template>
    <div :class="classes" role="alert" :aria-live="variant === 'error' ? 'assertive' : 'polite'">
        <i :class="['pi', iconMap[variant], 'text-xl mt-0.5 flex-shrink-0']" aria-hidden="true"></i>
        <div class="flex-1 min-w-0">
            <h4 v-if="title" :class="['font-bold text-sm mb-1', titleColor]">{{ title }}</h4>
            <p :class="['text-sm', textColor]">
                <slot />
            </p>
        </div>
        <button
            v-if="dismissible"
            type="button"
            @click="emit('dismiss')"
            class="flex-shrink-0 p-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/10 transition-colors"
            :aria-label="'Fermer l\'alerte'"
        >
            <i class="pi pi-times text-slate-400" aria-hidden="true"></i>
        </button>
    </div>
</template>
