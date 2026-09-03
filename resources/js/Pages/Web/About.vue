<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    page: { type: Object, default: null },
    about: { type: Object, default: () => ({}) },
    team: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="`À propos - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-20 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] via-[#003399] to-[#001A4D] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <span class="inline-flex items-center gap-2 text-red-400">
                    <span class="w-12 h-[2px] bg-red-400"></span>
                    <span class="font-black uppercase tracking-[0.5em] text-sm italic">À propos</span>
                </span>
                <h1 class="text-5xl md:text-7xl font-black mt-4 tracking-tighter">{{ page?.title || identity.site_name || 'SubnetCongo' }}</h1>
                <p class="text-xl text-blue-100 mt-6 max-w-3xl mx-auto">{{ about.tagline || identity.site_tagline || '' }}</p>
            </div>
        </section>

        <section class="py-20 bg-white px-6">
            <div class="max-w-4xl mx-auto prose prose-lg">
                <p v-if="page?.content" class="text-slate-700 leading-relaxed text-lg whitespace-pre-line">{{ page.content }}</p>
                <p v-else class="text-slate-700 leading-relaxed text-lg">{{ about.history || identity.site_description || 'SubnetCongo est un centre d\'excellence numérique dédié à la formation et au déploiement d\'infrastructures en République Démocratique du Congo.' }}</p>
            </div>
        </section>

        <section class="py-20 bg-slate-50 px-6">
            <div class="max-w-[1500px] mx-auto">
                <h2 class="text-4xl md:text-5xl font-black text-center text-slate-900 tracking-tighter mb-12">Nos chiffres</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(value, key) in stats" :key="key" class="p-8 bg-white rounded-lg shadow text-center">
                        <p class="text-4xl font-black text-[#003399]">{{ value }}+</p>
                        <p class="text-slate-500 uppercase tracking-widest text-xs font-black mt-2">{{ key }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="team.length" class="py-20 bg-white px-6">
            <div class="max-w-[1500px] mx-auto">
                <h2 class="text-4xl md:text-5xl font-black text-center text-slate-900 tracking-tighter mb-12">Notre équipe</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div v-for="member in team" :key="member.id" class="text-center">
                        <img :src="member.avatar || `https://i.pravatar.cc/150?u=${member.id}`" class="w-32 h-32 rounded-full mx-auto mb-4" :alt="member.name" />
                        <p class="font-black text-slate-900">{{ member.name }}</p>
                        <p class="text-slate-500 text-sm">{{ member.bio || 'Instructeur' }}</p>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
