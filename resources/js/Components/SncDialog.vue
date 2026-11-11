<script setup>
/**
 * SncDialog — Dialogue premium SubnetCongo.
 * Reproduit le style des dialogs de /courses :
 *  - header custom : tuile icône dégradée + titre + sous-titre + bouton fermer
 *  - corps : fond slate-50, contenu en cartes, UNE seule barre de défilement
 *  - footer : actions alignées à droite sur fond clair
 *
 * Usage :
 *   <SncDialog v-model:visible="open" title="Nouveau coupon" subtitle="…" icon="pi-ticket" width="760px">
 *       …contenu…
 *       <template #footer>…boutons…</template>
 *   </SncDialog>
 */
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const props = defineProps({
    visible:  { type: Boolean, default: false },
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon:     { type: String, default: 'pi-file-edit' },
    iconClass:{ type: String, default: 'from-blue-500 to-blue-600' },
    width:    { type: String, default: '560px' },
    closable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:visible', 'hide']);

const close = () => emit('update:visible', false);
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :closable="false"
        :draggable="false"
        :style="{ width: props.width, maxWidth: '95vw' }"
        :breakpoints="{ '768px': '96vw' }"
        class="snc-dialog snc-dialog-premium"
        contentClass="!p-0 !overflow-hidden bg-slate-50"
        headerClass="!p-0 !border-0"
        footerClass="!p-0 !border-0"
        @update:visible="emit('update:visible', $event)"
        @hide="emit('hide')"
    >
        <!-- HEADER CUSTOM (slot #header → .p-dialog-header, hors zone scrollable) -->
        <template #header>
            <div class="flex items-center justify-between gap-4 px-6 py-4 bg-white border-b border-slate-200 relative w-full">
                <!-- barre de statut en haut -->
                <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-500"></div>

                <div class="flex items-center gap-4 min-w-0">
                    <div
                        class="w-11 h-11 rounded-xl bg-gradient-to-br text-white flex items-center justify-center shadow-md flex-shrink-0"
                        :class="iconClass"
                        :style="{ boxShadow: '0 8px 20px -6px rgba(37, 99, 235, 0.4)' }"
                        aria-hidden="true"
                    >
                        <i :class="['pi', icon, 'text-lg']"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-slate-800 leading-tight truncate">{{ title }}</h3>
                        <p v-if="subtitle" class="text-[11px] font-medium text-slate-500 truncate mt-0.5">{{ subtitle }}</p>
                    </div>
                </div>

                <Button
                    v-if="closable"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    rounded
                    class="!w-9 !h-9 !bg-slate-100 hover:!bg-slate-200 !text-slate-600 flex-shrink-0"
                    aria-label="Fermer"
                    @click="close"
                />
            </div>
        </template>

        <!-- CORPS : seule zone scrollable -->
        <div class="p-fluid px-6 py-5 max-h-[70vh] overflow-y-auto snc-dialog-body">
            <slot />
        </div>

        <!-- FOOTER (slot #footer → .p-dialog-footer, hors zone scrollable) -->
        <template #footer>
            <div v-if="$slots.footer" class="flex items-center justify-end gap-3 px-6 py-4 bg-white border-t border-slate-200 w-full">
                <slot name="footer" />
            </div>
        </template>
    </Dialog>
</template>
