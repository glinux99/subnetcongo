<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const pages = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await axios.get(route('web.pages.index'));
        pages.value = data.props?.pages?.data || data.pages?.data || [];
    } catch (e) { pages.value = []; }
    loading.value = false;
});
</script>

<template>
    <Head title="Pages CMS" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900">Pages CMS</h1>
                    <p class="text-slate-500 mt-2">Gérez les pages de contenu (Landing, About, FAQ, etc.)</p>
                </div>
                <Link :href="route('web.pages.create')" class="px-6 py-3 bg-[#003399] text-white font-bold rounded"><i class="pi pi-plus mr-2"></i>Nouvelle page</Link>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="loading" class="text-center py-20 text-slate-500"><i class="pi pi-spin pi-spinner text-3xl"></i></div>
                <div v-else-if="pages.length" class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100">
                            <tr><th class="text-left p-4">Titre</th><th class="text-left p-4">Slug</th><th class="text-left p-4">Statut</th><th class="p-4"></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in pages" :key="p.id" class="border-t">
                                <td class="p-4 font-bold">{{ p.title }}</td>
                                <td class="p-4 text-slate-500">{{ p.slug }}</td>
                                <td class="p-4">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-bold', p.is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                                        {{ p.is_published ? 'Publié' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <Link :href="route('web.pages.edit', p.id)" class="text-blue-600 font-bold">Éditer →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-file text-6xl mb-3"></i>
                    <p class="font-bold">Aucune page. Créez-en une depuis <Link :href="route('web.pages.create')" class="text-blue-600 underline">Admin Pages</Link>.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
