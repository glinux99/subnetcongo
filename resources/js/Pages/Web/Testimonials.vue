<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    testimonials: { type: Object, default: () => ({ data: [], links: [] }) },
});
</script>

<template>
    <Head :title="`Témoignages - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">Témoignages</h1>
                <p class="text-xl text-blue-100 mt-4">Ce que disent nos apprenants et partenaires</p>
            </div>
        </section>

        <section class="py-20 px-6 bg-white">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="testimonials.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="t in testimonials.data" :key="t.id" class="p-10 bg-slate-50 rounded-lg shadow hover:shadow-2xl transition">
                        <div class="flex items-center gap-2 text-amber-400 mb-4">
                            <i v-for="i in (t.rating || 5)" :key="i" class="pi pi-star-fill"></i>
                        </div>
                        <p class="text-slate-700 italic mb-6">"{{ t.content }}"</p>
                        <div class="flex items-center gap-4">
                            <img :src="t.avatar || `https://i.pravatar.cc/150?u=${t.id}`" class="w-12 h-12 rounded-full" :alt="t.name" />
                            <div>
                                <p class="font-black text-slate-900">{{ t.name }}</p>
                                <p class="text-slate-500 text-sm">{{ t.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-comments text-6xl mb-3"></i>
                    <p class="text-xl font-bold">Aucun témoignage approuvé pour le moment.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
