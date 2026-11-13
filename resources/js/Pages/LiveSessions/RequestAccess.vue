<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({ session: Object, requested: Boolean, timezone: { type: String, default: 'Africa/Lubumbashi' } });

const form = useForm({});

const ask = () => form.post(route('live-sessions.request-access', props.session.uuid), { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.live_session_request_access.head_title', { title: session?.title || t('pages.live_session_request_access.session_fallback') })" />

        <div class="max-w-2xl mx-auto py-10 px-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 p-6 text-white">
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/70 mb-2">{{ t('pages.live_session_request_access.private_session') }}</p>
                    <h1 class="text-2xl font-black">{{ session?.title }}</h1>
                    <p class="text-white/80 text-sm mt-1">{{ session?.course?.title }}</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex flex-wrap gap-3 text-sm text-slate-600">
                        <span><i class="pi pi-user text-xs mr-1"></i> {{ session?.instructor?.name || t('pages.live_session_request_access.instructor_fallback') }}</span>
                        <span><i class="pi pi-calendar text-xs mr-1"></i> {{ session?.start_at ? new Date(session.start_at).toLocaleString(locale, { timeZone: props.timezone }) : t('pages.live_session_request_access.tbc') }}</span>
                        <Tag :value="session?.status === 'Live' ? t('pages.live_session_request_access.tag_live') : t('pages.live_session_request_access.tag_scheduled')" :severity="session?.status === 'Live' ? 'danger' : 'info'" />
                    </div>

                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                        <p class="font-bold mb-1">{{ t('pages.live_session_request_access.access_title') }}</p>
                        <p>{{ t('pages.live_session_request_access.access_message') }}</p>
                    </div>

                    <div v-if="requested" class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 text-sm">
                        <p class="font-bold mb-1">{{ t('pages.live_session_request_access.pending_title') }}</p>
                        <p>{{ t('pages.live_session_request_access.pending_message') }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button v-if="!requested" :label="t('pages.live_session_request_access.btn_request')" icon="pi pi-lock-open" @click="ask" :loading="form.processing" />
                        <Link :href="route('live-sessions.mine')">
                            <Button :label="t('pages.live_session_request_access.btn_back')" icon="pi pi-arrow-left" outlined severity="secondary" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
