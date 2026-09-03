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

        <div class="min-h-screen bg-[#F8FAFC] font-sans pb-20">
            
            <!-- HEADER ULTIMATE V30 (Prestige Dark) -->
            <div class="bg-[#0F172A] pt-12 pb-24 px-6 lg:px-12 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-emerald-600/20 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-amber-500/10 rounded-full blur-[100px] pointer-events-none"></div>
                
                <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-emerald-500/10 text-emerald-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-emerald-500/20">Verified Credentials</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic uppercase leading-none">
                            Mes <span class="text-emerald-500">Certifications</span>
                        </h1>
                        <p class="text-slate-400 mt-4 max-w-lg text-sm font-medium leading-relaxed">
                            Vos accomplissements officiels. Chaque certificat est vérifiable via un code unique et reconnu par nos partenaires.
                        </p>
                    </div>

                    <!-- KPI Cards Header -->
                    <div class="flex items-center gap-4 bg-white/5 p-2 rounded-[1rem] border border-white/10 backdrop-blur-xl">
                        <div class="px-8 py-3 text-center border-r border-white/10">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Total</p>
                            <p class="text-white font-black text-2xl italic">{{ meta?.total ?? 0 }}</p>
                        </div>
                        <div class="px-8 py-3 text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Score Moyen</p>
                            <p class="text-emerald-400 font-black text-2xl italic">{{ meta?.avg_score ?? 0 }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="max-w-7xl mx-auto px-6 -mt-12 relative z-20">
                <div class="bg-white rounded-[1.5rem] shadow-2xl border border-slate-200/50 p-8 lg:p-12 min-h-[500px]">
                    
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
                                        <div class="w-16 h-16 bg-white rounded-1xl overflow-hidden border-2 border-emerald-500/30 p-1 shadow-inner">
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
.italic { font-style: italic; }
:deep(.p-tag) {
    border-radius: 8px;
}
:deep(.p-button.p-button-outlined) {
    border: 1px solid rgba(255, 255, 255, 0.1);
}
:deep(.p-button.p-button-outlined:hover) {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.3);
}
</style>