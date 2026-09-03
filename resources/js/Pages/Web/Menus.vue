<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});

import { ref, onMounted } from 'vue';
import axios from 'axios';

const menus = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await axios.get(route('web.menus.index'));
        menus.value = data.props?.menus?.data || data.menus?.data || [];
    } catch (e) { menus.value = []; }
    loading.value = false;
});
</script>

<template>
    <Head title="Menus" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900">Menus du site</h1>
                    <p class="text-slate-500 mt-2">Configurez les menus header, footer, sidebar.</p>
                </div>
                <Link :href="route('web.menus.create')" class="px-6 py-3 bg-[#003399] text-white font-bold rounded"><i class="pi pi-plus mr-2"></i>Nouveau menu</Link>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="loading" class="text-center py-20 text-slate-500"><i class="pi pi-spin pi-spinner text-3xl"></i></div>
                <div v-else-if="menus.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="m in menus" :key="m.id" class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-black text-slate-900">{{ m.name }}</h3>
                        <p class="text-slate-500 text-sm mt-1">Emplacement : <span class="font-bold">{{ m.location }}</span></p>
                        <Link :href="route('web.menus.edit', m.id)" class="text-blue-600 font-bold text-sm mt-3 inline-block">Éditer →</Link>
                    </div>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-bars text-6xl mb-3"></i>
                    <p class="font-bold">Aucun menu. Créez-en un depuis <Link :href="route('web.menus.create')" class="text-blue-600 underline">Admin Menus</Link>.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
