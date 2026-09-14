<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';

// Composants PrimeVue
import Button from 'primevue/button';
import Breadcrumb from 'primevue/breadcrumb';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import Chips from 'primevue/chips';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    identity: { type: Object, default: () => ({
        site_name: 'SubnetCongo Academy',
        site_tagline: 'Leader de la formation Tech',
        site_description: 'Rejoignez la meilleure plateforme d\'apprentissage en ligne en RDC. Formations certifiantes en Réseaux, Programmation et Cybersécurité.',
        contact_address: 'Goma, RDC',
        contact_phone: '+243 900 000 000'
    }) },
});

// Formulaire réactif pour la prévisualisation en temps réel
const seoForm = ref({
    title: props.identity.site_name + ' — ' + props.identity.site_tagline,
    description: props.identity.site_description,
    keywords: ['formation', 'RDC', 'e-learning', 'SubnetCongo', 'tech'],
    sitemap: true,
    robots: true,
    schemaOrg: true
});

// Calcul de la longueur pour les recommandations Google (Titre max 60, Desc max 160)
const titleLength = computed(() => seoForm.value.title.length);
const descLength = computed(() => seoForm.value.description.length);

const titleColor = computed(() => titleLength.value > 60 ? 'text-red-500' : 'text-emerald-500');
const descColor = computed(() => descLength.value > 160 ? 'text-red-500' : 'text-emerald-500');
</script>

<template>
    <Head title="Référencement (SEO)" />

    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-32 bg-slate-50">
            
            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE (Style Dark Hub)                                                -->
            <!-- ============================================================================== -->
            <div class="flex flex-col gap-2 mb-12 px-4 md:px-6 pt-4">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Administration', icon: 'pi pi-cog' }, { label: 'Référencement SEO' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- Bannière Sombre -->
                <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
                    
                    <i class="pi pi-globe absolute -right-10 -top-10 text-[15rem] text-white/5 rotate-12 pointer-events-none"></i>

                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                        
                        <!-- Partie Gauche -->
                        <div class="space-y-4 max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                                Moteurs de recherche
                            </span>

                            <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                                <span class="text-white">Optimisation</span>
                                <span class="text-[#E92B2B]">SEO</span>
                            </h1>

                            <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                                Optimisez votre visibilité sur Google et Bing. Configurez vos balises Meta, vos sitemaps et vos micro-données pour attirer plus d'étudiants.
                            </p>
                        </div>

                        <!-- Partie Droite -->
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <Button 
                                label="Scanner le site" 
                                icon="pi pi-search" 
                                class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                            />
                            <Button 
                                label="Sauvegarder" 
                                icon="pi pi-check" 
                                class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white w-full sm:w-auto font-bold shadow-lg shadow-red-900/50 transition-colors px-6" 
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- KPI CARDS (Overlapping)                                                        -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-4 md:px-6 -mt-16 relative z-20 max-w-[100rem] mx-auto">
                
                <!-- KPI 1 : Score SEO -->
                <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36 relative overflow-hidden">
                    <div class="w-10 h-10 rounded-md bg-emerald-50 flex items-center justify-center mb-2 z-10 relative">
                        <i class="pi pi-percentage text-emerald-500 text-lg"></i>
                    </div>
                    <div class="z-10 relative">
                        <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Score SEO Actuel</h3>
                        <div class="flex items-end gap-2">
                            <p class="text-3xl font-extrabold text-slate-900">92</p>
                            <span class="text-xs font-bold text-emerald-500 mb-1">/100</span>
                        </div>
                    </div>
                    <!-- Barre de progression de fond -->
                    <ProgressBar :value="92" :showValue="false" class="absolute bottom-0 left-0 w-full h-1" :pt="{ value: { class: 'bg-emerald-500' } }"></ProgressBar>
                </div>

                <!-- KPI 2 : Pages Indexées -->
                <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                    <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center mb-4">
                        <i class="pi pi-sitemap text-indigo-500 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pages Indexées</h3>
                        <p class="text-3xl font-extrabold text-slate-900">145</p>
                    </div>
                </div>

                <!-- KPI 3 : Erreurs Crawl -->
                <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                    <div class="w-10 h-10 rounded-md bg-rose-50 flex items-center justify-center mb-4">
                        <i class="pi pi-exclamation-triangle text-rose-500 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Erreurs de Crawl</h3>
                        <div class="flex items-center gap-2">
                            <p class="text-3xl font-extrabold text-slate-900">0</p>
                            <span class="text-[10px] font-bold text-emerald-500 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md uppercase">Parfait</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 4 : Temps Réponse -->
                <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                    <div class="w-10 h-10 rounded-md bg-amber-50 flex items-center justify-center mb-4">
                        <i class="pi pi-stopwatch text-amber-500 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Vitesse Serveur</h3>
                        <p class="text-3xl font-extrabold text-slate-900">124<span class="text-lg text-slate-400 ml-1">ms</span></p>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- MAIN CONTENT : Formulaire & Prévisualisation SERP                              -->
            <!-- ============================================================================== -->
            <div class="px-4 md:px-6 relative z-20 mt-6 max-w-[100rem] mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Colonne Formulaire Principal -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Configuration Meta -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
                            <h2 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <i class="pi pi-pencil text-indigo-500"></i> Balises Meta (Page d'accueil)
                            </h2>

                            <div class="space-y-6">
                                <!-- Meta Title -->
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <label class="block text-sm font-bold text-slate-700">Meta Title</label>
                                        <span class="text-[10px] font-bold tracking-widest" :class="titleColor">
                                            {{ titleLength }} / 60 char.
                                        </span>
                                    </div>
                                    <InputText v-model="seoForm.title" class="w-full font-medium" />
                                    <p class="text-xs text-slate-500 mt-1">Le titre principal affiché dans les onglets du navigateur et les résultats de recherche.</p>
                                </div>

                                <!-- Meta Description -->
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <label class="block text-sm font-bold text-slate-700">Meta Description</label>
                                        <span class="text-[10px] font-bold tracking-widest" :class="descColor">
                                            {{ descLength }} / 160 char.
                                        </span>
                                    </div>
                                    <Textarea v-model="seoForm.description" rows="3" class="w-full font-medium" />
                                    <p class="text-xs text-slate-500 mt-1">Un résumé attractif incitant au clic. Évitez de dépasser 160 caractères.</p>
                                </div>

                                <!-- Mots-clés (Keywords) -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Mots-clés (Meta Keywords)</label>
                                    <Chips v-model="seoForm.keywords" class="w-full" :pt="{ input: { class: 'w-full' }, container: { class: 'w-full' } }" separator="," />
                                    <p class="text-xs text-slate-500 mt-1">Appuyez sur "Entrée" ou "," pour ajouter un mot-clé. (Moins pertinent aujourd'hui pour Google, utile pour la recherche interne).</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Colonne Prévisualisation & Tech -->
                    <div class="space-y-6">
                        
                        <!-- SERP Google Preview -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
                            <h2 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <i class="pi pi-google text-indigo-500"></i> Aperçu Google
                            </h2>

                            <!-- Simulation de la recherche Google -->
                            <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-7 h-7 bg-slate-100 rounded-full flex items-center justify-center border border-slate-200">
                                        <i class="pi pi-image text-slate-400 text-[10px]"></i>
                                    </div>
                                    <div>
                                        <div class="text-[12px] text-slate-800">Votre Plateforme</div>
                                        <div class="text-[11px] text-slate-500 leading-none">https://www.votresite.com</div>
                                    </div>
                                </div>
                                <div class="text-[#1a0dab] text-[18px] font-normal leading-tight mb-1 hover:underline cursor-pointer font-serif truncate">
                                    {{ seoForm.title || 'Titre de votre page' }}
                                </div>
                                <div class="text-[#4d5156] text-[13px] leading-snug line-clamp-2">
                                    {{ seoForm.description || 'La description de votre site apparaîtra ici dans les résultats de recherche.' }}
                                </div>
                            </div>
                            
                            <p class="text-[11px] text-slate-400 mt-4 text-center italic">
                                L'aperçu ci-dessus est généré en temps réel. L'affichage final peut varier selon l'appareil.
                            </p>
                        </div>

                        <!-- Paramètres Techniques (Robots, Sitemap) -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
                            <h2 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-2">
                                <i class="pi pi-wrench text-indigo-500"></i> Fichiers Techniques
                            </h2>

                            <div class="space-y-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-sm text-slate-700">Sitemap.xml Auto</div>
                                        <div class="text-[11px] text-slate-500">Génère et met à jour le plan du site automatiquement.</div>
                                    </div>
                                    <InputSwitch v-model="seoForm.sitemap" />
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-sm text-slate-700">Fichier Robots.txt</div>
                                        <div class="text-[11px] text-slate-500">Autorise les robots Google à crawler votre plateforme.</div>
                                    </div>
                                    <InputSwitch v-model="seoForm.robots" />
                                </div>

                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-bold text-sm text-slate-700">Schema.org (JSON-LD)</div>
                                        <div class="text-[11px] text-slate-500">Ajoute des micro-données pour les "Rich Snippets" de cours.</div>
                                    </div>
                                    <InputSwitch v-model="seoForm.schemaOrg" />
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-5 border-t border-slate-100">
                                <Button label="Regénérer le Sitemap" icon="pi pi-refresh" outlined class="w-full p-button-sm text-slate-600 border-slate-300 hover:bg-slate-50" />
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Styles PrimeVue Overrides */
:deep(.p-inputtext), :deep(.p-textarea) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-textarea:enabled:focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
}

/* Chips style pour Mots clés */
:deep(.p-chips .p-chips-multiple-container) {
    border-radius: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-color: #e2e8f0;
}
:deep(.p-chips .p-chips-token) {
    background-color: #f1f5f9;
    color: #475569;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
}

/* Switch styling */
:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background-color: #10b981;
}
:deep(.p-inputswitch .p-inputswitch-slider) {
    background-color: #cbd5e1;
}
</style>