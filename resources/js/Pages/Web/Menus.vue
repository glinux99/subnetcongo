<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
    <Head :title="t('pages.web_menus.title')" />
    <AppLayout>
        <section class="pt-24 pb-8 px-6 bg-white border-b">
            <div class="max-w-[1500px] mx-auto flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900">{{ t('pages.web_menus.title') }}</h1>
                    <p class="text-slate-500 mt-2">{{ t('pages.web_menus.subtitle') }}</p>
                </div>
                <Link :href="route('web.menus.create')" class="px-6 py-3 bg-[#003399] text-white font-bold rounded"><i class="pi pi-plus mr-2"></i>{{ t('pages.web_menus.newMenu') }}</Link>
            </div>
        </section>
        <section class="py-10 px-6 bg-slate-50 min-h-[60vh]">
            <div class="max-w-[1500px] mx-auto">
                <div v-if="loading" class="text-center py-20 text-slate-500"><i class="pi pi-spin pi-spinner text-3xl"></i></div>
                <div v-else-if="menus.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="m in menus" :key="m.id" class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-black text-slate-900">{{ m.name }}</h3>
                        <p class="text-slate-500 text-sm mt-1">{{ t('pages.web_menus.location') }} : <span class="font-bold">{{ m.location }}</span></p>
                        <Link :href="route('web.menus.edit', m.id)" class="text-blue-600 font-bold text-sm mt-3 inline-block">{{ t('pages.web_menus.edit') }} →</Link>
                    </div>
                </div>
                <div v-else class="text-center py-20 text-slate-500">
                    <i class="pi pi-bars text-6xl mb-3"></i>
                    <p class="font-bold">{{ t('pages.web_menus.emptyBefore') }} <Link :href="route('web.menus.create')" class="text-blue-600 underline">Admin Menus</Link>.</p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
