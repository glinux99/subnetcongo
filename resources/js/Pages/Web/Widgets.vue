<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';

// Composants PrimeVue
import Button from 'primevue/button';
import Breadcrumb from 'primevue/breadcrumb';
import Tag from 'primevue/tag';
import InputSwitch from 'primevue/inputswitch';
import Dialog from 'primevue/dialog';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});

// Widgets actuellement sur la page
const widgetTypes = ref([
    { key: 'hero_slider',  label: 'Hero Slider',     desc: 'Slides pleine page avec CTAs (géré depuis Landing).', icon: 'pi pi-images', color: 'text-indigo-500', bg: 'bg-indigo-50', active: true },
    { key: 'course_grid',  label: 'Grille de cours', desc: 'Affiche les cours publiés (géré sur Academy).', icon: 'pi pi-th-large', color: 'text-emerald-500', bg: 'bg-emerald-50', active: true },
    { key: 'stats',        label: 'Statistiques',    desc: 'Compteurs agrégés (étudiants, cours, etc.).', icon: 'pi pi-chart-bar', color: 'text-blue-500', bg: 'bg-blue-50', active: true },
    { key: 'testimonials', label: 'Témoignages',     desc: 'Carousel des témoignages approuvés.', icon: 'pi pi-comments', color: 'text-amber-500', bg: 'bg-amber-50', active: false },
]);

// Catalogue des widgets disponibles (non encore ajoutés)
const availableWidgets = ref([
    { key: 'blog_feed',    label: 'Fil d\'actualité', desc: '3 derniers articles de blog publiés.', icon: 'pi pi-megaphone', color: 'text-rose-500', bg: 'bg-rose-50', active: true },
    { key: 'cta',          label: 'Appel à l\'action', desc: 'Bandeau CTA final (inscription / découverte).', icon: 'pi pi-bolt', color: 'text-purple-500', bg: 'bg-purple-50', active: true },
    { key: 'realizations', label: 'Réalisations',    desc: 'Carrousel des projets terrain publiés.', icon: 'pi pi-briefcase', color: 'text-cyan-500', bg: 'bg-cyan-50', active: false },
    { key: 'faq',          label: 'FAQ',             desc: 'Questions/réponses configurables.', icon: 'pi pi-question-circle', color: 'text-slate-500', bg: 'bg-slate-100', active: true },
    { key: 'partners',     label: 'Partenaires',     desc: 'Bandeau défilant des logos partenaires.', icon: 'pi pi-star', color: 'text-yellow-500', bg: 'bg-yellow-50', active: true },
]);

// ---------------------------------------------------------
// LOGIQUE DRAG & DROP
// ---------------------------------------------------------
const draggedIndex = ref(null);

const onDragStart = (index, event) => {
    draggedIndex.value = index;
    // Effet visuel lors du drag
    event.dataTransfer.effectAllowed = 'move';
    event.target.classList.add('opacity-50');
};

const onDragEnd = (event) => {
    event.target.classList.remove('opacity-50');
    draggedIndex.value = null;
};

const onDrop = (index) => {
    if (draggedIndex.value !== null && draggedIndex.value !== index) {
        // Déplace l'élément dans le tableau
        const movedItem = widgetTypes.value.splice(draggedIndex.value, 1)[0];
        widgetTypes.value.splice(index, 0, movedItem);
    }
    draggedIndex.value = null;
};

// ---------------------------------------------------------
// LOGIQUE AJOUT / SUPPRESSION
// ---------------------------------------------------------
const showAddModal = ref(false);

const addWidget = (index) => {
    const widget = availableWidgets.value.splice(index, 1)[0];
    widgetTypes.value.push(widget);
    if (availableWidgets.value.length === 0) {
        showAddModal.value = false; // Ferme si vide
    }
};

const removeWidget = (index) => {
    const widget = widgetTypes.value.splice(index, 1)[0];
    availableWidgets.value.push(widget);
};
</script>

<template>
    <Head title="Configuration des Widgets" />

    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-32">
            
            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE                                                                 -->
            <!-- ============================================================================== -->
            <div class="flex flex-col gap-2 mb-12 px-4 md:px-6 pt-4">
                
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Administration', icon: 'pi pi-cog' }, { label: 'Composants & Widgets' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
                    <i class="pi pi-objects-column absolute -right-10 -top-10 text-[15rem] text-white/5 rotate-12 pointer-events-none"></i>

                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                        <div class="space-y-4 max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                                Landing Page Builder
                            </span>
                            <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                                <span class="text-white">Widgets</span>
                                <span class="text-[#E92B2B]">Dynamiques</span>
                            </h1>
                            <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                                Gérez les blocs dynamiques qui composent votre page d'accueil. <strong class="text-white">Glissez-déposez</strong> les cartes pour changer leur ordre d'affichage.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <Button 
                                label="Ajouter un widget" 
                                icon="pi pi-plus" 
                                class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                                @click="showAddModal = true"
                            />
                            <Button 
                                label="Prévisualiser" 
                                icon="pi pi-eye" 
                                class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white w-full sm:w-auto font-bold shadow-lg shadow-red-900/50 transition-colors px-6" 
                                as="a"
                                href="/"
                                target="_blank"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- GRID DES WIDGETS (DRAG & DROP)                                                 -->
            <!-- ============================================================================== -->
            <div class="px-4 md:px-6 -mt-16 relative z-20 max-w-[100rem] mx-auto">
                
                <!-- Zone vide si aucun widget -->
                <div v-if="widgetTypes.length === 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                    <i class="pi pi-objects-column text-4xl text-slate-300 mb-3"></i>
                    <h3 class="text-lg font-bold text-slate-800">Aucun widget actif</h3>
                    <p class="text-sm text-slate-500 mb-4">Votre page d'accueil est vide. Ajoutez des widgets pour commencer.</p>
                    <Button label="Ajouter depuis le catalogue" icon="pi pi-plus" outlined @click="showAddModal = true" />
                </div>

                <!-- Grille Drag & Drop -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div 
                        v-for="(w, index) in widgetTypes" 
                        :key="w.key" 
                        draggable="true"
                        @dragstart="onDragStart(index, $event)"
                        @dragend="onDragEnd"
                        @dragover.prevent
                        @drop.prevent="onDrop(index)"
                        class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-6 flex flex-col justify-between hover:shadow-xl transition-all duration-300 group cursor-move relative"
                    >
                        <!-- Poignée de drag visuelle (optionnelle, informe l'utilisateur qu'il peut glisser) -->
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md z-10 hidden sm:flex">
                            <i class="pi pi-arrows-alt text-xs"></i>
                        </div>

                        <!-- En-tête de la carte -->
                        <div class="flex justify-between items-start mb-4">
                            <div :class="`w-12 h-12 rounded-xl flex items-center justify-center shadow-sm ${w.bg} ${w.color}`">
                                <i :class="`${w.icon} text-xl`"></i>
                            </div>
                            <!-- Simulation d'un toggle d'activation -->
                            <div class="flex flex-col items-end gap-1" @click.stop>
                                <InputSwitch v-model="w.active" class="scale-75 origin-right" />
                                <span class="text-[9px] font-bold uppercase tracking-widest" :class="w.active ? 'text-emerald-500' : 'text-slate-400'">
                                    {{ w.active ? 'Actif' : 'Masqué' }}
                                </span>
                            </div>
                        </div>

                        <!-- Contenu (Texte) -->
                        <div class="flex-1 mb-6">
                            <h3 class="text-lg font-black text-slate-800 mb-2 group-hover:text-indigo-600 transition-colors">{{ w.label }}</h3>
                            <p class="text-slate-500 text-sm leading-relaxed">{{ w.desc }}</p>
                        </div>

                        <!-- Action de pied de carte -->
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between" @click.stop>
                            <Button 
                                icon="pi pi-cog" 
                                class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100 text-slate-600" 
                                v-tooltip.top="'Configurer le widget'" 
                            />
                            
                            <!-- Nouveau Bouton "Retirer" -->
                            <Button 
                                icon="pi pi-trash" 
                                class="p-button-rounded p-button-text p-button-danger hover:bg-red-50 text-red-400" 
                                v-tooltip.top="'Retirer de la page'" 
                                @click="removeWidget(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- MODAL : CATALOGUE DE WIDGETS A AJOUTER                                         -->
            <!-- ============================================================================== -->
            <Dialog 
                v-model:visible="showAddModal" 
                modal 
                header="Catalogue de Widgets" 
                :style="{ width: '50rem' }" 
                :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
            >
                <p class="text-sm text-slate-500 mb-6">Sélectionnez un widget ci-dessous pour l'ajouter à votre page d'accueil.</p>
                
                <div v-if="availableWidgets.length === 0" class="text-center py-8 text-emerald-600 font-bold bg-emerald-50 rounded-xl border border-emerald-100">
                    <i class="pi pi-check-circle text-2xl mb-2"></i><br>
                    Tous les widgets disponibles ont déjà été ajoutés à la page !
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="(w, index) in availableWidgets" 
                        :key="w.key"
                        class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 transition-all cursor-pointer group"
                        @click="addWidget(index)"
                    >
                        <div :class="`w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center shadow-sm ${w.bg} ${w.color}`">
                            <i :class="`${w.icon} text-lg`"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm group-hover:text-indigo-700">{{ w.label }}</h4>
                            <p class="text-[11px] text-slate-500 line-clamp-1">{{ w.desc }}</p>
                        </div>
                        <i class="pi pi-plus-circle text-slate-300 group-hover:text-indigo-500 text-xl transition-colors"></i>
                    </div>
                </div>

                <template #footer>
                    <Button label="Fermer" icon="pi pi-times" @click="showAddModal = false" text class="p-button-secondary" />
                </template>
            </Dialog>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Style spécifique pour la carte en cours de glissement (Drag) */
.opacity-50 {
    opacity: 0.4 !important;
    transform: scale(0.95);
    border: 2px dashed #6366f1 !important; /* Bordure indigo lors du drag */
}

/* Ajustement subtil pour InputSwitch PrimeVue */
:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background-color: #10b981; /* Emerald-500 */
}
:deep(.p-inputswitch .p-inputswitch-slider) {
    background-color: #cbd5e1; /* Slate-300 */
}
</style>