<script setup>
/**
 * SncProgress — Barre de progression standardisée du design system SUBNETCONGO.
 * Usage : <SncProgress :value="75" label="Progression" show-label />
 */
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v)
    },
    color: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'success', 'warning', 'danger'].includes(v)
    },
    label: { type: String, default: '' },
    showLabel: { type: Boolean, default: false },
    striped: { type: Boolean, default: false },
    animated: { type: Boolean, default: false },
});

const percentage = computed(() => {
    return Math.min(100, Math.max(0, (props.value / props.max) * 100));
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'h-1.5',
        md: 'h-2.5',
        lg: 'h-4',
    };
    return sizes[props.size];
});

const colorClasses = computed(() => {
    const colors = {
        primary: 'bg-blue-600 dark:bg-blue-500',
        success: 'bg-emerald-600 dark:bg-emerald-500',
        warning: 'bg-amber-500 dark:bg-amber-400',
        danger: 'bg-red-600 dark:bg-red-500',
    };
    return colors[props.color];
});

const barClasses = computed(() => {
    const classes = [colorClasses.value];
    if (props.striped) classes.push('bg-[length:1rem_1rem] bg-[linear-gradient(45deg,rgba(255,255,255,0.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.15)_50%,rgba(255,255,255,0.15)_75%,transparent_75%,transparent)]');
    if (props.animated) classes.push('animate-[progress-bar-stripes_1s_linear_infinite]');
    return classes;
});
</script>

<template>
    <div class="space-y-1.5">
        <div v-if="label || showLabel" class="flex justify-between items-center text-sm">
            <span v-if="label" class="font-medium text-slate-700 dark:text-slate-300">{{ label }}</span>
            <span v-if="showLabel" class="font-bold text-slate-900 dark:text-slate-100">{{ Math.round(percentage) }}%</span>
        </div>
        <div :class="['w-full bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden', sizeClasses]" role="progressbar" :aria-valuenow="percentage" :aria-valuemin="0" :aria-valuemax="100">
            <div
                :class="barClasses"
                :style="{ width: `${percentage}%` }"
                :aria-label="`${Math.round(percentage)}%`"
            />
        </div>
    </div>
</template>
