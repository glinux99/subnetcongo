<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});

const widgetTypes = [
    { key: 'hero_slider',  label: 'Hero Slider',     desc: 'Slides pleine page avec CTAs (géré depuis Landing).' },
    { key: 'course_grid',  label: 'Grille de cours', desc: 'Affiche les cours publiés (géré sur Academy).' },
    { key: 'stats',        label: 'Statistiques',    desc: 'Compteurs agrégés (étudiants, cours, etc.).' },
    { key: 'testimonials', label: 'Témoignages',     desc: 'Carousel des témoignages approuvés.' },
    { key: 'blog_feed',    label: 'Fil d\'actualité',desc: '3 derniers articles de blog publiés.' },
    { key: 'cta',          label: 'Appel à l\'action',desc: 'Bandeau CTA final (inscription / découverte).' },
    { key: 'realizations', label: 'Réalisations',    desc: 'Carrousel des projets terrain publiés.' },
    { key: 'faq',          label: 'FAQ',             desc: 'Questions/réponses configurables.' },
];
</script>

<template>
    <Head title="Widgets" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto">
                <h1 class="text-3xl font-black text-slate-900">Widgets de la page d'accueil</h1>
                <p class="text-slate-500 mt-2">Les widgets sont alimentés dynamiquement par les données BDD publiées.</p>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="w in widgetTypes" :key="w.key" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-black text-slate-900">{{ w.label }}</h3>
                    <p class="text-slate-500 text-sm mt-2">{{ w.desc }}</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
