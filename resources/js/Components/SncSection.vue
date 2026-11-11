<script setup>
/**
 * SncSection — Carte de section à l'intérieur d'un SncDialog.
 * Reproduit les sections du dialog cours (/courses) :
 *  carte blanche arrondie, en-tête avec tuile icône + titre + sous-titre,
 *  contenu en grille.
 */
defineProps({
    icon:     { type: String, default: 'pi-info-circle' },
    iconClass:{ type: String, default: 'from-blue-500 to-blue-600' },
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <header class="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <div
                class="w-8 h-8 rounded-lg bg-gradient-to-br text-white flex items-center justify-center shadow-sm flex-shrink-0"
                :class="iconClass"
                aria-hidden="true"
            >
                <i :class="['pi', icon, 'text-sm']"></i>
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-slate-800 leading-tight">{{ title }}</h4>
                <p v-if="subtitle" class="text-[11px] text-slate-500 mt-0.5">{{ subtitle }}</p>
            </div>
        </header>
        <div class="p-5">
            <slot />
        </div>
    </section>
</template>
