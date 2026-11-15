<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({ sessions: Array, timezone: { type: String, default: 'Africa/Lubumbashi' } });

const formatDate = (value) => value
    ? new Date(value).toLocaleString(locale.value, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: props.timezone })
    : t('pages.live_sessions_public.date_tbc');
</script>

<template>
    <Head :title="`${t('pages.live_sessions_public.head_title')} - Subnet Congo Academy`">
        <meta name="description" content="Webinaires et classes virtuelles en direct de Subnet Congo Academy : cours en ligne, démonstrations réseaux et formation certifiée en RDC." />
    </Head>

    <div class="min-h-screen bg-slate-50 font-sans">
        <header class="bg-slate-900 text-white">
            <div class="max-w-6xl mx-auto px-4 py-10">
                <p class="text-[10px] font-black uppercase tracking-widest text-white/60 mb-2">Subnet Congo Academy</p>
                <h1 class="text-3xl lg:text-4xl font-black tracking-tight">{{ t('pages.live_sessions_public.heading') }}</h1>
                <p class="text-white/70 mt-2 max-w-2xl">{{ t('pages.live_sessions_public.subtitle') }}</p>
                <div class="flex gap-2 mt-6">
                    <Link :href="route('login')">
                        <Button :label="t('pages.live_sessions_public.btn_login')" icon="pi pi-sign-in" />
                    </Link>
                    <Link :href="route('landing')">
                        <Button :label="t('pages.live_sessions_public.btn_back_site')" icon="pi pi-arrow-left" outlined severity="secondary" />
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 py-10">
            <div v-if="sessions?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <article v-for="s in sessions" :key="s.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <Tag :value="s.status === 'Live' ? t('pages.live_sessions_public.tag_live') : t('pages.live_sessions_public.tag_scheduled')" :severity="s.status === 'Live' ? 'danger' : 'info'" :class="s.status === 'Live' ? 'animate-pulse' : ''" />
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ t('pages.live_sessions_public.tag_webinar') }}</span>
                    </div>

                    <h2 class="text-lg font-bold text-slate-900">{{ s.title }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ s.course?.title }}</p>

                    <div class="text-sm text-slate-600 mt-3 space-y-1">
                        <p><i class="pi pi-user text-xs mr-1"></i> {{ s.instructor?.name || t('pages.live_sessions_public.instructor_fallback') }}</p>
                        <p><i class="pi pi-calendar text-xs mr-1"></i> {{ formatDate(s.start_at) }}</p>
                        <p v-if="s.duration"><i class="pi pi-hourglass text-xs mr-1"></i> {{ s.duration }} min</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <Link :href="s.share_url || route('live-sessions.join', s.uuid)" class="block">
                            <Button :label="s.status === 'Live' ? t('pages.live_sessions_public.btn_join_now') : t('pages.live_sessions_public.btn_register_join')"
                                    :icon="s.status === 'Live' ? 'pi pi-video' : 'pi pi-ticket'"
                                    :severity="s.status === 'Live' ? 'danger' : 'primary'" class="w-full" />
                        </Link>
                    </div>
                </article>
            </div>

            <div v-else class="text-center py-20">
                <i class="pi pi-desktop text-4xl text-slate-300 mb-3 block"></i>
                <h2 class="text-lg font-bold text-slate-700">{{ t('pages.live_sessions_public.empty_title') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ t('pages.live_sessions_public.empty_hint') }}</p>
            </div>
        </main>
    </div>
</template>
