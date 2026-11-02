<script setup>
/**
 * SncInput — Champ de saisie standardisé du design system SUBNETCONGO.
 * Usage : <SncInput v-model="value" label="Email" type="email" :error="errorMessage" />
 */
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    id: { type: String, default: '' },
    name: { type: String, default: '' },
    autocomplete: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const inputId = computed(() => props.id || `input-${Math.random().toString(36).substr(2, 9)}`);

const inputClasses = computed(() => {
    const base = 'w-full bg-slate-50 dark:bg-slate-800 border rounded-xl px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
    const border = props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-700'
        : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:focus:border-blue-400';
    const disabled = props.disabled ? 'opacity-50 cursor-not-allowed bg-slate-100 dark:bg-slate-900' : '';

    return [base, border, disabled];
});

const labelClasses = computed(() => {
    return [
        'block text-sm font-bold mb-1.5',
        props.error ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300',
        props.required ? 'after:content-["*"] after:ml-0.5 after:text-red-500' : '',
    ];
});

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="inputId" :class="labelClasses">
            {{ label }}
        </label>
        <input
            :id="inputId"
            :name="name"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :autocomplete="autocomplete"
            :class="inputClasses"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${inputId}-error` : undefined"
            @input="updateValue"
        />
        <p
            v-if="error"
            :id="`${inputId}-error`"
            class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1"
            role="alert"
        >
            <i class="pi pi-exclamation-circle" aria-hidden="true"></i>
            {{ error }}
        </p>
    </div>
</template>
