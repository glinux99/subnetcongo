<script setup>
/**
 * ContextHint — Message d'aide contextuelle discret et évolutif.
 * Se masque si : réglage global désactivé, utilisateur a fermé le message,
 * ou compte plus ancien que ui.help_hints_ttl_days (0 = jamais masquer par l'âge).
 */
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    id: { type: String, required: true },
    message: { type: String, required: true },
});

const { t } = useI18n();
const page = usePage();
const visible = ref(false);

const userId = computed(() => page.props.auth?.user?.id ?? 'anonymous');
const storageKey = computed(() => `subnetcongo.help-hint.${userId.value}.${props.id}`);

const ui = computed(() => page.props.ui || {});
const hintsEnabled = computed(() => ui.value.helpHintsEnabled !== false);
const ttlDays = computed(() => {
    const value = Number(ui.value.helpHintsTtlDays ?? 3);
    return Number.isFinite(value) ? Math.max(0, value) : 3;
});

const accountAgeDays = computed(() => {
    const createdAt = page.props.auth?.user?.created_at;
    if (!createdAt) return 0;
    const created = new Date(createdAt).getTime();
    if (Number.isNaN(created)) return 0;
    return Math.floor((Date.now() - created) / 86_400_000);
});

const isDismissed = () => {
    try {
        return localStorage.getItem(storageKey.value) === '1';
    } catch (_) {
        return false;
    }
};

const dismiss = () => {
    visible.value = false;
    try {
        localStorage.setItem(storageKey.value, '1');
    } catch (_) { /* stockage indisponible */ }
};

onMounted(() => {
    const ageExpired = ttlDays.value > 0 && accountAgeDays.value > ttlDays.value;
    visible.value = hintsEnabled.value && !isDismissed() && !ageExpired;
});
</script>

<template>
    <div
        v-if="visible"
        class="mt-3 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-2 text-[11px] font-medium leading-snug text-blue-900 dark:border-blue-500/20 dark:bg-blue-950/30 dark:text-blue-200"
        role="note"
    >
        <i class="pi pi-info-circle mt-0.5 shrink-0 text-blue-500 dark:text-blue-300" aria-hidden="true"></i>
        <p class="min-w-0 flex-1">{{ message }}</p>
        <button
            type="button"
            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-blue-500 transition-colors hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 dark:hover:bg-blue-900/50"
            :aria-label="t('components.context_hint.dismiss')"
            :title="t('components.context_hint.dismiss')"
            @click="dismiss"
        >
            <i class="pi pi-times text-xs" aria-hidden="true"></i>
        </button>
    </div>
</template>
