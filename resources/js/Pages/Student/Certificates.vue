<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const props = defineProps({ 
    certificates: Array, 
    meta: Object 
});

const toast = useToast();

const PLACEHOLDER = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=400';

const getImageUrl = (path) => {
    if (!path) return PLACEHOLDER;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const handleImageError = (e) => { e.target.src = PLACEHOLDER; };

const download = (cert) => { 
    window.open(cert.download_url, '_blank'); 
    toast.add({ severity: 'success', summary: 'Génération du PDF', detail: 'Le téléchargement a commencé.', life: 3000 }); 
};

const verify = (cert) => { 
    navigator.clipboard?.writeText(cert.verify_url); 
    toast.add({ severity: 'info', summary: 'Lien copié', detail: 'Lien de vérification prêt à être partagé.', life: 2500 }); 
};
</script>

<template>
    <AppLayout>
        <Head title="Mes Certifications | SubnetCongo Academy" />
        <Toast />

        <div class="page-bg min-h-screen font-sans pb-20">
            
      <!-- ============================================================================== -->
<!-- HEADER & KPI (Style "Banque de Questions Centrale")                            -->
<!-- ============================================================================== -->
<div class="flex flex-col mb-12">
    
    <!-- 1. BANNIÈRE SOMBRE -->
    <!-- Le padding bottom (pb-24) est important pour laisser la place aux cartes de remonter -->
    <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
        
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
            
            <!-- Partie Gauche (Titres) -->
            <div class="max-w-3xl">
                <!-- Petit Badge rouge sombre -->
                <span class="inline-block border border-red-900/50 text-[#ff4d4d] text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                    Verified Credentials
                </span>

                <!-- Titre Principal (Italique, très gras, espacement réduit) -->
                <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                    <span class="text-white">MES</span>
                    <span class="text-[#FF3B3B]">CERTIFICATIONS</span>
                </h1>

                <!-- Sous-titre (Italique, gris clair) -->
                <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                    Vos accomplissements officiels. Chaque certificat est vérifiable via un code unique et reconnu par nos partenaires.
                </p>
            </div>

            <!-- Partie Droite (Boutons) -->
            <div class="flex items-center gap-3 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto">
                <Button 
                    label="Exporter Profil" 
                    icon="pi pi-download" 
                    class="!bg-transparent hover:!bg-white/10 !border !border-slate-600 !text-white w-full sm:w-auto text-sm font-medium transition-colors" 
                />
                <Button 
                    label="Partager Réseau" 
                    icon="pi pi-share-alt" 
                    class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !text-white w-full sm:w-auto text-sm font-bold shadow-lg shadow-red-900/50 transition-colors" 
                />
            </div>
        </div>
    </div>

    <!-- 2. CARTES KPI (Chevauchement) -->
    <!-- Le -mt-16 fait remonter les cartes sur la bannière sombre. Le z-20 les garde au premier plan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4 md:px-6 -mt-16 relative z-20">
        
        <!-- KPI 1 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-4">
            <div class="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center">
                <i class="pi pi-list text-indigo-500 text-sm"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Obtenu</div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ meta?.total ?? 0 }}</div>
            </div>
        </div>

        <!-- KPI 2 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-4">
            <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center">
                <i class="pi pi-check-circle text-emerald-500 text-sm"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Score Moyen</div>
                <div class="text-2xl font-black text-slate-800 leading-none">{{ meta?.avg_score ?? 0 }}<span class="text-sm text-slate-400 ml-1">%</span></div>
            </div>
        </div>

        <!-- KPI 3 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-4">
            <div class="w-8 h-8 rounded bg-amber-50 flex items-center justify-center">
                <i class="pi pi-star text-amber-500 text-sm"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Validité Globale</div>
                <div class="text-2xl font-black text-slate-800 leading-none">À jour</div>
            </div>
        </div>

        <!-- KPI 4 -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-4">
            <div class="w-8 h-8 rounded bg-rose-50 flex items-center justify-center">
                <i class="pi pi-share-alt text-rose-500 text-sm"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Partage Réseau</div>
                <div class="text-2xl font-black text-slate-800 leading-none">Actif</div>
            </div>
        </div>
        
    </div>
</div>
<!-- FIN HEADER & KPI -->

            <!-- MAIN CONTENT -->
            <div class="max-w-7xl mx-auto px-6 -mt-12 relative z-20">
                <div class="bg-white rounded-[1rem] shadow-2xl border border-slate-200/50 p-8 lg:p-12 min-h-[500px]">
                    
                    <!-- Empty State -->
                    <div v-if="(certificates ?? []).length === 0" class="py-24 text-center">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                            <i class="pi pi-verified text-4xl text-slate-300"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 tracking-tight">En attente de réussite</h3>
                        <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto font-medium">Complétez vos modules et réussissez vos examens pour voir vos certificats apparaître ici.</p>
                        <Link :href="route('marketplace.index')" class="mt-8 inline-block">
                            <Button label="Explorer les cours" icon="pi pi-search" class="!bg-[#0F172A] !border-none !rounded-xl !px-8 !py-3 !text-xs !font-black !uppercase !tracking-widest" />
                        </Link>
                    </div>

                    <!-- Certificate Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div v-for="cert in certificates" :key="cert.id" class="group relative bg-[#0F172A] rounded-[1rem] overflow-hidden shadow-2xl hover:-translate-y-2 transition-all duration-500">
                            
                            <!-- Design Element: Card Shine -->
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/20 to-transparent opacity-50 pointer-events-none"></div>
                            
                            <div class="p-8 relative z-10 flex flex-col h-full">
                                <!-- Top Bar -->
                                <div class="flex justify-between items-start mb-6">
                                    <div class="flex gap-4 items-center">
                                        <div class="w-16 h-16 bg-white rounded-xl overflow-hidden border-2 border-emerald-500/30 p-1 shadow-inner">
                                            <img :src="getImageUrl(cert.course_image)" @error="handleImageError" class="w-full h-full object-cover rounded-xl" />
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-emerald-400 uppercase tracking-[0.2em] mb-1">SubnetCongo Official</p>
                                            <h3 class="text-lg font-black text-white leading-tight tracking-tight italic">{{ cert.course_title }}</h3>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-3xl font-black text-emerald-400 italic font-mono">{{ Math.round(cert.score) }}%</div>
                                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Grade</p>
                                    </div>
                                </div>

                                <!-- Middle Info -->
                                <div class="space-y-4 mb-8">
                                    <div class="flex items-center gap-3 text-slate-400 text-xs font-medium">
                                        <i class="pi pi-calendar text-emerald-500"></i>
                                        <span>Délivré le <span class="text-white">{{ cert.issued_at }}</span></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-slate-400 text-xs font-medium">
                                        <i class="pi pi-key text-emerald-500"></i>
                                        <span>ID: <span class="text-white font-mono">{{ cert.certificate_code }}</span></span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto flex gap-3">
                                    <Button label="Télécharger PDF" icon="pi pi-download" @click="download(cert)"
                                        class="flex-1 !bg-emerald-500 hover:!bg-emerald-400 !border-none !rounded-xl !py-3.5 !text-[10px] !font-black !uppercase !tracking-widest !text-[#0F172A] transition-all active:scale-95 shadow-lg shadow-emerald-500/20" />
                                    
                                    <Button icon="pi pi-external-link" @click="verify(cert)" outlined
                                        class="!border-white/10 !text-white hover:!bg-white/5 !rounded-xl !px-4" 
                                        v-tooltip.top="'Copier le lien de vérification'" />
                                </div>
                            </div>

                            <!-- Decorative Seal -->
                            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full flex items-center justify-center rotate-12 pointer-events-none border border-white/5">
                                <i class="pi pi-verified text-white/10 text-6xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée façon macOS / SaaS Moderne */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* Animations d'entrée (Fade In) */
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Customisation PrimeVue DataTable B2B */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Timeline Custom (Audit Log) */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    width: 100%;
}
:deep(.custom-timeline .p-timeline-event-separator) {
    margin-right: 1rem;
}

/* Overrides UI Forms */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-dropdown:not(.p-disabled).p-focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
}

/* Override Editor Toolbar */
:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
    background-color: #f8fafc;
    border-color: #e2e8f0;
}
:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
    border-color: #e2e8f0;
}
</style>