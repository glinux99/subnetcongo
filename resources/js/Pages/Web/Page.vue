<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    page: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`${page.title} - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">{{ page.title }}</h1>
            </div>
        </section>
        <section class="py-20 px-6 bg-white">
            <div class="max-w-4xl mx-auto">
                <div v-if="page.featured_image" class="mb-10 rounded-lg overflow-hidden">
                    <img :src="page.featured_image" class="w-full" :alt="page.title" />
                </div>
                <article class="prose prose-lg max-w-none text-slate-700 leading-relaxed">
                    <p v-if="page.excerpt" class="text-xl text-slate-500 italic mb-8">{{ page.excerpt }}</p>
                    <div v-html="page.content || ''"></div>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
