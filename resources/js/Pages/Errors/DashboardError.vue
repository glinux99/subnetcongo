<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';

const props = defineProps({ error: String });
</script>

<template>
    <AppLayout>
        <Head title="Erreur dashboard" />
        <div class="min-h-screen bg-slate-50 font-sans flex items-center justify-center p-6">
            <div class="bg-white rounded-3xl p-8 max-w-lg text-center border border-slate-200 shadow-xl">
                <div class="w-20 h-20 bg-rose-100 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-exclamation-triangle text-3xl"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 mb-2">Tableau de bord indisponible</h1>
                <p class="text-sm text-slate-500 mb-6">{{ error || "Une erreur est survenue lors du chargement. Veuillez réessayer." }}</p>
                <div class="flex gap-2 justify-center">
                    <Link href="/dashboard"><Button label="Réessayer" icon="pi pi-refresh" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl" /></Link>
                    <Link href="/"><Button label="Accueil" icon="pi pi-home" outlined class="!rounded-xl" /></Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
