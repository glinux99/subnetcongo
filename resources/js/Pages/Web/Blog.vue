<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    posts: { type: Object, default: () => ({ data: [], links: [] }) },
    featured: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const applyFilters = () => router.get(route('blog'), { search: search.value || undefined }, { preserveState: true });
</script>

<template>
    <Head :title="`Blog - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">Blog</h1>
                <p class="text-xl text-blue-100 mt-4">Actualités, tutoriels et analyses</p>
            </div>
        </section>

        <section v-if="featured.length" class="py-16 px-6 bg-white">
            <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                <Link v-for="post in featured" :key="post.id" :href="route('blog')" class="group block">
                    <div class="h-48 rounded-lg overflow-hidden bg-slate-200 mb-4">
                        <img v-if="post.featured_image" :src="post.featured_image" class="w-full h-full object-cover group-hover:scale-110 transition" />
                    </div>
                    <h3 class="text-xl font-black text-slate-900 line-clamp-2">{{ post.title }}</h3>
                    <p class="text-slate-500 text-sm mt-2 line-clamp-2">{{ post.excerpt }}</p>
                </Link>
            </div>
        </section>

        <section class="py-12 px-6 bg-slate-50">
            <div class="max-w-[1500px] mx-auto">
                <input v-model="search" @keyup.enter="applyFilters" type="search" placeholder="Rechercher un article..." class="w-full px-6 py-4 rounded-lg border border-slate-200 mb-12" />

                <div v-if="posts.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <article v-for="post in posts.data" :key="post.id" class="bg-white rounded-lg overflow-hidden shadow hover:shadow-2xl transition">
                        <div class="h-48 bg-slate-200 overflow-hidden">
                            <img v-if="post.featured_image" :src="post.featured_image" class="w-full h-full object-cover" />
                        </div>
                        <div class="p-6">
                            <p class="text-xs text-slate-500 mb-2">{{ post.published_at }}</p>
                            <h3 class="text-xl font-black text-slate-900 mb-2 line-clamp-2">{{ post.title }}</h3>
                            <p class="text-slate-600 line-clamp-3">{{ post.excerpt }}</p>
                        </div>
                    </article>
                </div>
                <div v-else class="text-center py-12 text-slate-500">
                    <i class="pi pi-book text-5xl mb-3"></i>
                    <p class="font-bold">Aucun article publié.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
