<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({ sessions: Array, timezone: { type: String, default: 'Africa/Lubumbashi' }, courses: Array });

const live = computed(() => (props.sessions ?? []).filter(s => s.status === 'Live'));
const upcoming = computed(() => (props.sessions ?? []).filter(s => s.status !== 'Live'));

const isJoinable = (s) => {
    if (s.status === 'Live') return true;
    if (!s.start_at) return false;
    const start = new Date(s.start_at).getTime();
    const now = Date.now();
    return now >= start - 15 * 60000;
};

const formatDate = (value) => value
    ? new Date(value).toLocaleString(locale.value, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: props.timezone })
    : t('pages.live_session_student.date_tbc');

const statusSeverity = (s) => (s === 'Live' ? 'danger' : 'info');
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.live_session_student.head_title')" />

        <PageHeader
            :title="t('pages.live_session_student.head_title')"
            :subtitle="t('pages.live_session_student.subtitle')"
            icon="pi-desktop"
            accent="red"
        />

        <div class="mt-4 flex flex-wrap gap-2">
            <Link :href="route('live-sessions.public')">
                <Button :label="t('pages.live_session_student.btn_public_sessions')" icon="pi pi-globe" outlined severity="secondary" size="small" />
            </Link>
        </div>

        <div class="mt-6 space-y-8">
            <section v-if="live.length">
                <h2 class="text-lg font-black text-slate-900 mb-3 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ t('pages.live_session_student.live_now') }}
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <article v-for="s in live" :key="s.id" class="bg-white rounded-2xl border border-rose-200 shadow-sm p-5">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <Tag :value="t('pages.live_session_student.tag_live')" severity="danger" class="animate-pulse" />
                            <span class="text-xs text-slate-500 font-semibold">{{ s.instructor?.name || t('pages.live_session_student.instructor_fallback') }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ s.title }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ s.course?.title }}</p>
                        <p class="text-sm text-slate-600 mt-3">
                            <i class="pi pi-clock text-xs mr-1"></i> {{ t('pages.live_session_student.started_at') }} {{ formatDate(s.actual_start_at || s.start_at) }}
                        </p>
                        <Link v-if="isJoinable(s)" :href="s.share_url || route('live-sessions.join', s.uuid)" class="block mt-4">
                            <Button :label="t('pages.live_session_student.btn_join_session')" icon="pi pi-video" class="w-full" severity="danger" />
                        </Link>
                    </article>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-black text-slate-900 mb-3">{{ t('pages.live_session_student.upcoming') }}</h2>
                <div v-if="upcoming.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <article v-for="s in upcoming" :key="s.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <Tag :value="s.status === 'Scheduled' ? t('pages.live_session_student.tag_scheduled') : s.status" :severity="statusSeverity(s.status)" />
                            <span class="text-xs text-slate-500 font-semibold">{{ s.instructor?.name || t('pages.live_session_student.instructor_fallback') }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ s.title }}</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ s.course?.title }}</p>
                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-600 mt-3">
                            <span><i class="pi pi-calendar text-xs mr-1"></i> {{ formatDate(s.start_at) }}</span>
                            <span v-if="s.duration"><i class="pi pi-hourglass text-xs mr-1"></i> {{ s.duration }} min</span>
                        </div>
                        <div class="mt-4">
                            <Link v-if="isJoinable(s)" :href="s.share_url || route('live-sessions.join', s.uuid)" class="block">
                                <Button :label="t('pages.live_session_student.btn_join')" icon="pi pi-video" class="w-full" />
                            </Link>
                            <p v-else class="text-xs text-slate-400 text-center py-2">
                                {{ t('pages.live_session_student.join_hint') }}
                            </p>
                        </div>
                    </article>
                </div>
                <EmptyState
                    v-else
                    icon="pi pi-desktop"
                    :title="t('pages.live_session_student.empty_title')"
                    :description="t('pages.live_session_student.empty_description')"
                />
            </section>
        </div>
    </AppLayout>
</template>
