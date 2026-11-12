<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({ error: String });
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.errors_dashboard.title')" />
        <div class="min-h-screen bg-slate-50 font-sans flex items-center justify-center p-6">
            <div class="bg-white rounded-xl p-8 max-w-lg text-center border border-slate-200 shadow-xl">
                <div class="w-20 h-20 bg-red-100 text-red-500 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-exclamation-triangle text-3xl"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 mb-2">{{ t('pages.errors_dashboard.heading') }}</h1>
                <p class="text-sm text-slate-500 mb-6">{{ error || t('pages.errors_dashboard.default_error') }}</p>
                <div class="flex gap-2 justify-center">
                    <Link href="/dashboard"><Button :label="t('pages.errors_dashboard.retry')" icon="pi pi-refresh" class="!bg-gradient-to-r !from-blue-500 !to-red-500 !border-none !rounded-xl" /></Link>
                    <Link href="/"><Button :label="t('pages.errors_dashboard.home')" icon="pi pi-home" outlined class="!rounded-xl" /></Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
