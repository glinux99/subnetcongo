<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    realizations: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const applyFilters = () => router.get(route('realizations'), { search: search.value || undefined }, { preserveState: true });
</script>

<template>
    <Head :title="`Réalisations - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">Nos Réalisations</h1>
                <p class="text-xl text-blue-100 mt-4">Projets terrain, déploiements et formations dispensées</p>
            </div>
        </section>

        <section class="py-12 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto">
                <input v-model="search" @keyup.enter="applyFilters" type="search" placeholder="Rechercher..." class="w-full px-6 py-4 rounded-lg border border-slate-200" />
            </div>
        </section>

        <section class="py-20 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="realizations.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <article v-for="r in realizations.data" :key="r.id" class="bg-white rounded-lg overflow-hidden shadow hover:shadow-2xl transition">
                        <div class="h-56 bg-slate-200 overflow-hidden">
                            <img v-if="r.image" :src="r.image" class="w-full h-full object-cover" />
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-black text-slate-900 mb-2">{{ r.title }}</h3>
                            <p class="text-slate-600 line-clamp-3">{{ r.summary || r.description }}</p>
                        </div>
                    </article>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-folder-open text-6xl mb-3"></i>
                    <p class="text-xl font-bold">Aucune réalisation publiée pour le moment.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
