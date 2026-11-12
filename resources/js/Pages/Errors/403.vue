<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/sakai/layout/AppLayout.vue';

const { t } = useI18n();

defineProps({
    message: { type: String, default: '' },
    roles:   { type: String, default: '' },
});
</script>

<template>
    <Head :title="`403 — ${t('error.403_title')}`" />
    <AppLayout>
        <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-6 py-16 sm:py-24 bg-gradient-to-br from-[#FDFFFF] via-slate-50 to-rose-50 dark:from-[#020617] dark:via-slate-900 dark:to-rose-950/20">
            <div class="w-full max-w-2xl">
                <!-- Carte principale -->
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-slate-900/5 dark:shadow-black/40 border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <!-- Bandeau -->
                    <div class="h-2 bg-gradient-to-r from-red-600 via-[#E1251B] to-amber-500"></div>

                    <div class="p-8 sm:p-12">
                        <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-10 text-center sm:text-left">
                            <!-- Icône secondaire décorative -->
                            <div class="relative flex-shrink-0">
                                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-red-50 dark:bg-red-950/40 flex items-center justify-center border-2 border-red-200 dark:border-red-900/60">
                                    <i class="pi pi-lock text-red-600 dark:text-red-400 text-4xl sm:text-5xl"></i>
                                </div>
                                <span class="absolute -bottom-2 -right-2 w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-red-600 text-white text-base sm:text-lg font-black flex items-center justify-center shadow-lg">!</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-[0.3em] text-red-600 dark:text-red-400">
                                    {{ t('error.403_code') }}
                                </p>
                                <h1 class="mt-2 text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-slate-100 leading-none tracking-tight">
                                    403
                                </h1>
                                <h2 class="mt-3 text-xl sm:text-2xl font-bold text-slate-800 dark:text-slate-100 leading-snug">
                                    {{ t('error.403_title') }}
                                </h2>
                                <p class="mt-3 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ message || t('error.403_desc') }}
                                </p>
                            </div>
                        </div>

                        <!-- Boutons de navigation -->
                        <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 sm:py-4 bg-[#0B1221] hover:bg-slate-800 text-white font-bold rounded-xl transition-colors text-sm sm:text-base shadow-sm"
                            >
                                <i class="pi pi-arrow-left"></i>
                                {{ t('error.back_dashboard') }}
                            </Link>
                            <Link
                                :href="route('student.my-courses')"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 sm:py-4 bg-[#2563EB] hover:bg-blue-700 text-white font-bold rounded-xl transition-colors text-sm sm:text-base shadow-sm"
                            >
                                <i class="pi pi-graduation-cap"></i>
                                {{ t('error.go_my_courses') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Liens d'aide en bas -->
                <div class="mt-6 text-center text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
                    <span class="inline-flex items-center gap-2">
                        <i class="pi pi-info-circle text-slate-400"></i>
                        {{ t('error.403_help_text') }}
                    </span>
                    <span class="hidden sm:inline text-slate-300 dark:text-slate-700">•</span>
                    <Link :href="route('social.support.index')" class="text-[#2563EB] dark:text-blue-400 font-bold hover:underline">
                        {{ t('error.contact_support') }}
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
