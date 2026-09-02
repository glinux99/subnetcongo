<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const props = defineProps({ certificates: Array, meta: Object });
const toast = useToast();
const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=400' : (path.startsWith('http') ? path : `/storage/${path}`);
const download = (cert) => { window.open(cert.download_url, '_blank'); toast.add({ severity: 'success', summary: 'Téléchargement lancé', detail: cert.course_title, life: 2500 }); };
const verify = (cert) => { navigator.clipboard?.writeText(cert.verify_url); toast.add({ severity: 'info', summary: 'Lien de vérification copié', life: 2500 }); };
</script>

<template>
    <AppLayout>
        <Head title="Mes certificats" />
        <Toast />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-emerald-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30 border border-white/10">
                        <i class="pi pi-verified text-white text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">Mes certificats</h1>
                            <Tag :value="`${meta?.total ?? 0} actifs`" class="!bg-emerald-500/20 !text-emerald-300 !border !border-emerald-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">Trust Center · Score moyen : <strong class="text-emerald-300 font-black">{{ meta?.avg_score ?? 0 }}%</strong></p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1200px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div v-if="(certificates ?? []).length === 0" class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-verified text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-700">Aucun certificat pour le moment</h3>
                        <p class="text-slate-400 text-sm mt-2">Terminez vos formations pour débloquer vos premiers diplômes.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="cert in certificates" :key="cert.id" class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-6 text-white relative overflow-hidden shadow-xl">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-300/10 rounded-full blur-3xl"></div>
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-14 h-14 bg-white/95 rounded-2xl overflow-hidden shadow-lg shrink-0">
                                        <img v-if="cert.course_image" :src="getImageUrl(cert.course_image)" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-emerald-700">
                                            <i class="pi pi-book text-2xl"></i>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-amber-300">Certificat</p>
                                        <h3 class="font-black truncate tracking-tight">{{ cert.course_title }}</h3>
                                        <p class="text-[10px] opacity-80 mt-1 font-mono">Code : {{ cert.certificate_code }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-black font-mono">{{ Math.round(cert.score) }}%</p>
                                    <p class="text-[9px] uppercase tracking-widest opacity-80">Score</p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest opacity-90 mb-4">
                                <span><i class="pi pi-calendar text-amber-300"></i> Délivré le {{ cert.issued_at }}</span>
                            </div>
                            <div class="flex gap-2">
                                <Button label="Télécharger PDF" icon="pi pi-download" @click="download(cert)" class="!bg-amber-500 hover:!bg-amber-400 !text-slate-900 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-2.5 flex-1" />
                                <Button icon="pi pi-link" @click="verify(cert)" outlined class="!border-white/40 !text-white hover:!bg-white/10 !rounded-xl" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>