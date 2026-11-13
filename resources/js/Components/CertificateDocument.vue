<script setup>
/**
 * CertificateDocument — Rendu partagé d'un template de certificat SubnetCongo
 * (document A4 positionnable). Utilisé par l'éditeur (interactive) et par
 * l'aperçu HD plein écran (lecture seule).
 *
 * Props :
 *   templateForm      — blueprint du template (orientation, éléments, fond, bordure…)
 *   backgroundPreview — object URL locale si une image de fond vient d'être choisie
 *   selectedElementId — id de l'élément sélectionné (mode interactive)
 *   draggingElementId — id de l'élément en cours de drag (mode interactive)
 *   interactive       — autorise le drag & l'édition inline (contenteditable)
 *
 * Émet :
 *   start-drag (event, element)
 *   text-blur  (event, element)
 */
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    templateForm: { type: Object, required: true },
    backgroundPreview: { type: String, default: null },
    selectedElementId: { type: [Number, String, null], default: null },
    draggingElementId: { type: [Number, String, null], default: null },
    interactive: { type: Boolean, default: false },
});

const emit = defineEmits(['start-drag', 'text-blur']);

const documentStyle = computed(() => {
    const width = props.templateForm.orientation === 'landscape' ? 1120 : 792;
    const height = props.templateForm.orientation === 'landscape' ? 792 : 1120;
    return {
        width: `${width}px`,
        height: `${height}px`,
        backgroundColor: props.templateForm.background_color,
    };
});

const elementStyle = (el) => {
    const zIndex = props.templateForm.elements.findIndex((e) => e.id === el.id) + 10;
    const base = {
        position: 'absolute',
        left: `${el.x}px`,
        top: `${el.y}px`,
        width: el.width ? `${el.width}px` : 'auto',
        height: el.height ? `${el.height}px` : 'auto',
        transform: `rotate(${el.rotation || 0}deg)`,
        opacity: el.opacity !== undefined ? el.opacity / 100 : 1,
        zIndex,
    };

    if (props.interactive) {
        base.cursor = props.draggingElementId === el.id ? 'grabbing' : 'grab';
        base.outline = props.selectedElementId === el.id ? '2px dashed #dc2626' : 'none';
    }

    return base;
};

const textStyle = (el) => ({
    fontSize: `${el.style.fontSize}px`,
    color: el.style.color,
    fontFamily: el.style.fontFamily,
    letterSpacing: `${el.style.letterSpacing}px`,
    fontWeight: el.style.fontWeight,
    fontStyle: el.style.fontStyle,
    textDecoration: el.style.textDecoration || 'none',
    textAlign: el.style.textAlign,
    lineHeight: el.style.lineHeight || 1.2,
});

const filterStyle = (filters) => {
    if (!filters) return {};
    return {
        filter: `brightness(${filters.brightness}%) contrast(${filters.contrast}%) grayscale(${filters.grayscale}%) saturate(${filters.saturate ?? 100}%) hue-rotate(${filters.hueRotate}deg) invert(${filters.invert ? 100 : 0}%) blur(${filters.blur || 0}px)`,
    };
};

const startDrag = (event, el) => {
    if (!props.interactive) return;
    emit('start-drag', event, el);
};
</script>

<template>
    <div class="relative bg-white shadow-[0_20px_100px_rgba(0,0,0,0.8)] ring-1 ring-white/10 overflow-hidden select-none" :style="documentStyle">
        <!-- Layer 0: Background Generation -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <img v-if="backgroundPreview || templateForm.background_url" :src="backgroundPreview || templateForm.background_url" class="absolute inset-0 w-full h-full object-cover transition-opacity" :style="[filterStyle(templateForm.bg_filters), { opacity: templateForm.bg_opacity / 100 }]" alt="" />
            <div class="absolute inset-0 mix-blend-multiply" :style="{ backgroundColor: `#${(templateForm.background_color || '#ffffff').replace('#', '')}`, opacity: (100 - (backgroundPreview ? templateForm.bg_opacity : 0)) / 100 }"></div>
        </div>

        <!-- Layer 1: Master Border -->
        <div v-if="templateForm.border_width > 0" class="absolute pointer-events-none z-10" :style="{ top: '40px', left: '40px', right: '40px', bottom: '40px', border: `${templateForm.border_width}px solid #${templateForm.border_color.replace('#', '')}` }"></div>

        <!-- Layer 2: Elements -->
        <div
            v-for="el in templateForm.elements"
            :key="el.id"
            class="absolute z-20 group quantum-element"
            :class="{ 'is-selected': interactive && selectedElementId === el.id, 'is-dragging': interactive && draggingElementId === el.id }"
            :style="elementStyle(el)"
            @mousedown.stop="startDrag($event, el)"
        >
            <!-- Bounding Box (mode interactive uniquement) -->
            <div v-if="interactive && selectedElementId === el.id" class="absolute -inset-1.5 border-2 border-red-500 pointer-events-none z-30">
                <div class="absolute -top-1.5 -left-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                <div class="absolute -bottom-1.5 -left-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                <div class="absolute -bottom-1.5 -right-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
            </div>

            <div class="w-full h-full relative overflow-visible flex items-center justify-center">
                <!-- TEXT -->
                <div v-if="el.type === 'text'" class="w-full h-full outline-none whitespace-pre-wrap break-words" :class="interactive ? 'select-text' : ''" :contenteditable="interactive" @blur="interactive && emit('text-blur', $event, el)" :style="textStyle(el)" spellcheck="false" :title="interactive ? t('pages.certificates.doubleClickEdit') : ''" v-html="el.content"></div>

                <!-- LOGO -->
                <div v-if="el.type === 'logo'" class="w-full h-full pointer-events-none flex items-center justify-center">
                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="filterStyle(el.filters)" alt="" />
                    <div v-else class="w-full h-full bg-slate-100 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 rounded-xl">
                        <i class="pi pi-shield text-4xl mb-2"></i><span class="text-xs font-bold uppercase tracking-widest text-center px-2">{{ t('pages.certificates.logoSpace') }}</span>
                    </div>
                </div>

                <!-- SIGNATURE -->
                <div v-if="el.type === 'signature'" class="w-full h-full pointer-events-none flex items-center justify-center">
                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="filterStyle(el.filters)" alt="" />
                    <div v-else class="w-full h-full bg-slate-100/50 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 rounded-xl">
                        <i class="pi pi-pencil text-3xl mb-2"></i><span class="text-[10px] font-bold uppercase tracking-widest">{{ t('pages.certificates.signatureSpace') }}</span>
                    </div>
                </div>

                <!-- QR CODE -->
                <div v-if="el.type === 'qr'" class="w-full h-full pointer-events-none bg-white p-3 border border-slate-200 flex items-center justify-center shadow-sm">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=TrustCenterValidation" class="w-full h-full object-contain opacity-80" alt="QR code" />
                </div>

                <!-- CUSTOM IMAGE -->
                <div v-if="el.type === 'image'" class="w-full h-full pointer-events-none flex items-center justify-center">
                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="filterStyle(el.filters)" alt="" />
                    <div v-else class="w-full h-full bg-blue-50 border border-blue-100 flex flex-col items-center justify-center text-blue-300">
                        <i class="pi pi-image text-3xl mb-1"></i><span class="text-[8px] font-bold uppercase">{{ t('pages.certificates.freeImage') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.quantum-element {
    transition: box-shadow 0.2s ease, outline 0.1s ease;
}
.quantum-element.is-dragging {
    opacity: 0.8;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    z-index: 999 !important;
}
</style>
