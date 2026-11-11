<script setup>
/**
 * EmailChipsInput — Saisie d'une liste d'emails sous forme de chips.
 * - Entrée / virgule / point-virgule / espace pour valider un ou plusieurs emails
 * - Coller une liste (une par ligne ou séparés par des virgules) fonctionne aussi
 * - Chaque chip peut être retirée individuellement
 *
 * Usage :
 *   <EmailChipsInput v-model="form.extra_emails" label="Emails supplémentaires" placeholder="ajouter un email…" />
 */
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    label:      { type: String, default: '' },
    placeholder:{ type: String, default: 'Ajouter un email…' },
    hint:       { type: String, default: '' },
    invalid:    { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const draft = ref('');
const error = ref('');

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const commit = () => {
    const parts = draft.value
        .split(/[\s,;]+/)
        .map((s) => s.trim().toLowerCase())
        .filter(Boolean);

    if (!parts.length) return;

    const list = [...props.modelValue];
    const bad = [];

    for (const p of parts) {
        if (!EMAIL_RE.test(p)) {
            bad.push(p);
        } else if (!list.includes(p)) {
            list.push(p);
        }
    }

    error.value = bad.length ? `Email invalide : ${bad.join(', ')}` : '';
    if (list.length !== props.modelValue.length) {
        emit('update:modelValue', list);
    }
    draft.value = '';
};

const remove = (email) => {
    emit('update:modelValue', props.modelValue.filter((e) => e !== email));
};
</script>

<template>
    <div class="field">
        <label v-if="label" class="font-bold text-slate-700 text-sm mb-1.5 block">{{ label }}</label>

        <div class="flex gap-2">
            <InputText
                v-model="draft"
                :placeholder="placeholder"
                class="flex-1"
                :class="{ 'p-invalid': invalid || !!error }"
                @keydown.enter.prevent="commit"
                @keydown.tab="commit"
                @blur="commit"
                aria-label="Ajouter un email"
            />
            <Button
                icon="pi pi-plus"
                severity="secondary"
                outlined
                :disabled="!draft.trim()"
                aria-label="Ajouter"
                @click="commit"
            />
        </div>

        <div v-if="modelValue.length" class="flex flex-wrap gap-2 mt-2.5">
            <span
                v-for="email in modelValue"
                :key="email"
                class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold"
            >
                <i class="pi pi-envelope text-[10px] opacity-70"></i>
                {{ email }}
                <button
                    type="button"
                    class="w-4 h-4 rounded-full bg-blue-200/70 hover:bg-rose-500 hover:text-white text-blue-700 flex items-center justify-center transition-colors"
                    :aria-label="`Retirer ${email}`"
                    @click="remove(email)"
                >
                    <i class="pi pi-times text-[9px]"></i>
                </button>
            </span>
        </div>

        <small v-if="error" class="text-rose-500 mt-1 block" role="alert">{{ error }}</small>
        <small v-else-if="hint" class="text-slate-400 mt-1 block">{{ hint }}</small>
    </div>
</template>
