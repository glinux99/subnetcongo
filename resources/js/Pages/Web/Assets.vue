<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const assets = ref({ data: [], total: 0 });
const loading = ref(true);
const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});

const fetch = async () => {
    try {
        const { data } = await axios.get(route('web.assets.index'), { params: { per_page: 20 } });
        assets.value = data.props?.assets || data;
    } catch (e) { /* route may not exist yet */ }
    loading.value = false;
};
onMounted(fetch);
</script>

<template>
    <Head title="Médiathèque" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto">
                <h1 class="text-3xl font-black text-slate-900">Médiathèque</h1>
                <p class="text-slate-500 mt-2">Tous les fichiers uploadés (images, documents, vidéos).</p>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="loading" class="text-center py-20 text-slate-500"><i class="pi pi-spin pi-spinner text-3xl"></i></div>
                <div v-else-if="assets.data?.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="a in assets.data" :key="a.id" class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="aspect-square bg-slate-100 flex items-center justify-center">
                            <img v-if="a.mime_type?.startsWith('image/')" :src="a.url" class="w-full h-full object-cover" />
                            <i v-else class="pi pi-file text-4xl text-slate-400"></i>
                        </div>
                        <div class="p-3">
                            <p class="text-xs font-bold text-slate-900 truncate">{{ a.name }}</p>
                            <p class="text-xs text-slate-500">{{ a.human_size }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-images text-6xl mb-3"></i>
                    <p class="font-bold">Aucun fichier. Allez sur <Link :href="route('web.assets.index')" class="text-blue-600 underline">Médiathèque Admin</Link> pour uploader.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
