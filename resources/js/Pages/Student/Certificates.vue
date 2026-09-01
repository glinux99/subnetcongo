<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const props = defineProps({
    certificates: Array,
    meta: Object,
});

const toast = useToast();

const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const download = (cert) => {
    window.open(cert.download_url, '_blank', 'noopener');
    toast.add({ severity: 'success', summary: 'Téléchargement lancé', detail: cert.course_title, life: 2500 });
};

const verify = (cert) => {
    navigator.clipboard?.writeText(cert.verify_url);
    toast.add({ severity: 'info', summary: 'Lien de vérification copié', life: 2500 });
};
</script>

<template>
    <AppLayout>
        <Head title="Mes certificats" />
        <Toast />

        <div class="min-h-screen bg-[#F8FAFC] py-10 px-6 font-sans">
            <div class="max-w-[1200px] mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Trust Center</p>
                        <h1 class="text-3xl font-black text-slate-800 italic tracking-tight mt-1">Mes certificats</h1>
                        <p class="text-sm text-slate-400 mt-2">
                            {{ meta?.total ?? 0 }} certificats actifs · Score moyen : <strong>{{ meta?.avg_score ?? 0 }}%</strong>
                        </p>
                    </div>
                </div>

                <div v-if="(certificates ?? []).length === 0"
                     class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-16 text-center">
                    <i class="pi pi-verified text-5xl text-slate-300 mb-4 block"></i>
                    <h3 class="text-xl font-black text-slate-700 italic mb-2">Aucun certificat pour le moment</h3>
                    <p class="text-slate-400 text-sm">Terminez vos formations pour débloquer vos premiers diplômes.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="cert in certificates" :key="cert.id"
                         class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white relative overflow-hidden shadow-xl">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-300/10 rounded-full blur-3xl"></div>
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-14 h-14 bg-white/95 rounded-xl overflow-hidden shadow-lg flex-shrink-0">
                                    <img v-if="cert.course_image" :src="getImageUrl(cert.course_image)" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-emerald-700">
                                        <i class="pi pi-book text-2xl"></i>
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-amber-300">Certificat</p>
                                    <h3 class="font-black italic truncate">{{ cert.course_title }}</h3>
                                    <p class="text-[10px] opacity-80 mt-1">Code : {{ cert.certificate_code }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black italic">{{ Math.round(cert.score) }}%</p>
                                <p class="text-[9px] uppercase tracking-widest opacity-80">Score</p>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest opacity-90 mb-4">
                            <span><i class="pi pi-calendar text-amber-300"></i> Délivré le {{ cert.issued_at }}</span>
                        </div>

                        <div class="flex gap-2">
                            <Button label="Télécharger PDF" icon="pi pi-download"
                                    class="p-button-warning flex-1 font-black text-xs"
                                    @click="download(cert)" />
                            <Button icon="pi pi-link" outlined class="border-white/40 text-white hover:bg-white/10"
                                    v-tooltip.top="'Copier le lien de vérification'"
                                    @click="verify(cert)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>