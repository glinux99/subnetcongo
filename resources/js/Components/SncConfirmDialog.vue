<script setup>
/**
 * SncConfirmDialog — Confirmation professionnelle SubnetCongo.
 * Remplace le ConfirmDialog PrimeVue brut dans toute l'application.
 *
 *   <SncConfirmDialog />              (groupe par défaut)
 *   <SncConfirmDialog group="app" />  (groupe utilisé par useConfirmAction)
 */
import ConfirmDialog from 'primevue/confirmdialog';
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    group: { type: String, default: undefined },
});

const isDanger = (confirmation) => confirmation?.acceptProps?.severity === 'danger';

const tileIcon = (confirmation) => {
    if (confirmation?.icon) {
        return confirmation.icon.replace(/^pi\s+/, '');
    }
    return isDanger(confirmation) ? 'pi-exclamation-triangle' : 'pi-question-circle';
};
</script>

<template>
    <ConfirmDialog :group="group" class="snc-confirm">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="snc-confirm-panel" role="alertdialog" aria-modal="true" :aria-label="message?.header">
                <!-- HEADER -->
                <div class="snc-confirm-header">
                    <div v-if="isDanger(message)" class="snc-confirm-accent snc-confirm-accent-danger"></div>
                    <div v-else class="snc-confirm-accent snc-confirm-accent-info"></div>

                    <div :class="['snc-confirm-tile', isDanger(message) ? 'snc-confirm-tile-danger' : 'snc-confirm-tile-info']" aria-hidden="true">
                        <i :class="['pi', tileIcon(message), 'text-xl']"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p :class="['snc-confirm-eyebrow', isDanger(message) ? 'text-red-600' : 'text-blue-600']">
                            {{ isDanger(message) ? t('components.confirm_dialog.eyebrow_danger') : t('components.confirm_dialog.eyebrow_info') }}
                        </p>
                        <h3 class="snc-confirm-title">{{ message?.header ?? t('components.confirm_dialog.fallback_title') }}</h3>
                    </div>
                    <button
                        type="button"
                        class="snc-confirm-close"
                        :aria-label="t('components.confirm_dialog.close')"
                        @click="rejectCallback()"
                    >
                        <i class="pi pi-times" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- MESSAGE -->
                <div class="snc-confirm-body">
                    <i :class="['pi', isDanger(message) ? 'pi-info-circle text-red-400' : 'pi-info-circle text-blue-400', 'snc-confirm-body-icon']" aria-hidden="true"></i>
                    <p class="snc-confirm-message">{{ message?.message }}</p>
                </div>

                <!-- FOOTER -->
                <div class="snc-confirm-footer">
                    <Button
                        :label="message?.rejectProps?.label ?? t('components.confirm_dialog.reject')"
                        severity="secondary"
                        outlined
                        class="snc-confirm-cancel"
                        @click="rejectCallback()"
                    />
                    <Button
                        :label="message?.acceptProps?.label ?? t('components.confirm_dialog.accept')"
                        :class="['snc-confirm-accept', isDanger(message) ? 'snc-confirm-accept-danger' : 'snc-confirm-accept-info']"
                        @click="acceptCallback()"
                        autofocus
                    />
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>

<style scoped>
.snc-confirm-panel {
    width: min(460px, 92vw);
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: 0 24px 48px -16px rgba(15, 23, 42, 0.35);
    display: flex;
    flex-direction: column;
}

.snc-confirm-header {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 1rem 1.1rem;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
}

.snc-confirm-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.snc-confirm-accent-danger { background: linear-gradient(90deg, #E1251B, #f87171, transparent); }
.snc-confirm-accent-info { background: linear-gradient(90deg, #2563eb, #60a5fa, transparent); }

.snc-confirm-tile {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 8px 20px -6px rgba(37, 99, 235, 0.45);
}
.snc-confirm-tile-info {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}
.snc-confirm-tile-danger {
    background: linear-gradient(135deg, #e1251b, #b91c1c);
    box-shadow: 0 8px 20px -6px rgba(225, 37, 27, 0.45);
}

.snc-confirm-eyebrow {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.snc-confirm-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
    letter-spacing: -0.01em;
}

.snc-confirm-close {
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    border-radius: 9999px;
    border: 0;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
}
.snc-confirm-close:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: rotate(90deg);
}

.snc-confirm-body {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.1rem;
    background: #f8fafc;
}
.snc-confirm-body-icon {
    font-size: 1.1rem;
    margin-top: 0.1rem;
    flex-shrink: 0;
}
.snc-confirm-message {
    font-size: 0.875rem;
    line-height: 1.55;
    color: #475569;
    white-space: pre-line;
}

.snc-confirm-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.6rem;
    padding: 0.75rem 1.1rem;
    background: #fff;
    border-top: 1px solid #f1f5f9;
}
.snc-confirm-cancel {
    border-radius: 0.7rem !important;
    font-weight: 600 !important;
}
.snc-confirm-accept {
    border: 0 !important;
    border-radius: 0.7rem !important;
    font-weight: 700 !important;
    color: #fff !important;
    padding-inline: 1.1rem !important;
    transition: transform 0.15s ease, filter 0.15s ease !important;
}
.snc-confirm-accept:hover {
    filter: brightness(1.05);
    transform: translateY(-1px);
}
.snc-confirm-accept:active {
    transform: translateY(0);
}
.snc-confirm-accept-info {
    background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
    box-shadow: 0 10px 22px -8px rgba(37, 99, 235, 0.55);
}
.snc-confirm-accept-danger {
    background: linear-gradient(135deg, #e1251b, #b91c1c) !important;
    box-shadow: 0 10px 22px -8px rgba(225, 37, 27, 0.55);
}

/* Le Dialog de base : on enlève ses marges pour laisser le panneau parler */
:deep(.p-dialog-content) {
    padding: 0 !important;
    background: transparent !important;
}
:deep(.p-dialog-footer) {
    display: none !important;
}

.snc-confirm {
    filter: drop-shadow(0 20px 40px rgba(15, 23, 42, 0.35));
}

.app-dark .snc-confirm-panel {
    background: #0f172a;
    border-color: #1e293b;
    box-shadow: 0 24px 48px -16px rgba(2, 6, 23, 0.8);
}
.app-dark .snc-confirm-header {
    background: #0f172a;
    border-bottom-color: #1e293b;
}
.app-dark .snc-confirm-title { color: #f1f5f9; }
.app-dark .snc-confirm-body { background: #0b1222; }
.app-dark .snc-confirm-message { color: #cbd5e1; }
.app-dark .snc-confirm-footer { background: #0f172a; border-top-color: #1e293b; }
.app-dark .snc-confirm-close { background: #1e293b; color: #94a3b8; }
.app-dark .snc-confirm-close:hover { background: #334155; color: #f1f5f9; }

@media (prefers-reduced-motion: reduce) {
    .snc-confirm-close,
    .snc-confirm-accept { transition: none; }
}
</style>
