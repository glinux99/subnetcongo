<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    courses: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    levels: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const level = ref(props.filters.level || '');

const applyFilters = () => {
    router.get(route('academy'), {
        search: search.value || undefined,
        category: category.value || undefined,
        level: level.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="`Académie - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        <section class="relative pt-32 pb-16 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] to-[#003399] opacity-80"></div>
            <div class="relative max-w-[1500px] mx-auto px-6 text-center">
                <h1 class="text-5xl md:text-7xl font-black tracking-tighter">Académie</h1>
                <p class="text-xl text-blue-100 mt-4">Tous les cours publiés par nos instructeurs</p>
            </div>
        </section>

        <section class="py-12 bg-white px-6 border-b">
            <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="search" @keyup.enter="applyFilters" type="search" placeholder="Rechercher un cours..." class="px-6 py-4 rounded-lg border border-slate-200 focus:border-blue-500 outline-none" />
                <select v-model="category" @change="applyFilters" class="px-6 py-4 rounded-lg border border-slate-200">
                    <option value="">Toutes les catégories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
                </select>
                <select v-model="level" @change="applyFilters" class="px-6 py-4 rounded-lg border border-slate-200">
                    <option value="">Tous niveaux</option>
                    <option v-for="(label, value) in levels" :key="value" :value="value">{{ label }}</option>
                </select>
            </div>
        </section>

        <section class="py-20 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="courses.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <Link v-for="course in courses.data" :key="course.id" :href="route('marketplace.show', course.id)" class="group bg-white rounded-lg overflow-hidden shadow hover:shadow-2xl transition">
                        <div class="relative h-56 overflow-hidden bg-slate-200">
                            <img v-if="course.image" :src="course.image" class="w-full h-full object-cover group-hover:scale-110 transition" />
                            <span v-if="course.is_free" class="absolute top-4 right-4 bg-green-500 text-white text-xs font-black uppercase px-3 py-1 rounded-full">Gratuit</span>
                            <span v-else class="absolute top-4 right-4 bg-blue-900 text-white text-xs font-black uppercase px-3 py-1 rounded-full">${{ course.price }}</span>
                        </div>
                        <div class="p-8">
                            <p class="text-xs font-black uppercase tracking-widest text-[#003399] mb-2">{{ course.category?.name || '—' }}</p>
                            <h4 class="text-xl font-black text-slate-900 mb-2 line-clamp-2">{{ course.title }}</h4>
                            <p class="text-slate-500 text-sm">{{ course.instructor?.name || '—' }}</p>
                        </div>
                    </Link>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-search text-6xl mb-4"></i>
                    <p class="text-xl font-bold">Aucun cours trouvé.</p>
                </div>

                <div v-if="courses.links?.length > 3" class="mt-12 flex justify-center gap-2">
                    <Link v-for="(l, i) in courses.links" :key="i" :href="l.url || '#'" v-html="l.label"
                        :class="['px-4 py-2 rounded', l.active ? 'bg-[#003399] text-white' : 'bg-white text-slate-700 hover:bg-slate-100', !l.url && 'opacity-50']" />
                </div>
            </div>
        </section>
    </AppLayout>
</template>
