<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import VideoConference from '@/Components/LiveKit/VideoConference.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    session: Object,
    user: Object,
    requires_confirmation: { type: Boolean, default: false },
});

const toast = useToast();
const minutesLeft = ref(0);
let heartbeat = null;
let countdown = null;

const isLiveKit = computed(() => props.session?.provider === 'livekit');
const providerLabel = computed(() => {
    const p = props.session?.provider;
    return ({ zoom: 'Zoom', meet: 'Google Meet', bbb: 'BigBlueButton', jitsi: 'Jitsi Meet', livekit: 'LiveKit (In-App)' })[p] ?? t('pages.live_session_player.provider_fallback');
});

const joinUrl = computed(() => props.session?.meeting_url);
const isMock = computed(() => !joinUrl.value && props.session?.provider !== 'livekit');

const sendHeartbeat = async () => {
    try {
        await window.axios?.post?.(route('live-sessions.heartbeat', props.session.uuid));
    } catch (e) {}
};

const formatTime = (m, s) => `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

const computeRemaining = () => {
    if (!props.session?.start_at || !props.session?.duration) return 0;
    const end = new Date(props.session.start_at).getTime() + props.session.duration * 60000;
    return Math.max(0, Math.floor((end - Date.now()) / 60000));
};

onMounted(() => {
    minutesLeft.value = computeRemaining();
    countdown = setInterval(() => {
        const m = computeRemaining();
        minutesLeft.value = m;
        if (m === 0) {
            toast.add({ severity: 'warn', summary: t('pages.live_session_player.session_ended'), detail: t('pages.live_session_player.session_ended_detail'), life: 5000 });
            clearInterval(countdown);
        }
    }, 60000);
    heartbeat = setInterval(sendHeartbeat, 60000);
    toast.add({ severity: 'success', summary: t('pages.live_session_player.connected'), detail: t('pages.live_session_player.welcome', { title: props.session?.title }), life: 3000 });
});

onBeforeUnmount(() => {
    if (heartbeat) clearInterval(heartbeat);
    if (countdown) clearInterval(countdown);
});

const openInNewTab = () => joinUrl.value && window.open(joinUrl.value, '_blank', 'noopener');
</script>

<template>
    <AppLayout>
        <Head :title="session?.title || t('pages.live_session_player.head_fallback')" />

        <div class="page-bg min-h-screen font-sans pb-20">
            <PageHeader
                :title="session?.title || t('pages.live_session_player.head_fallback')"
                :subtitle="`${providerLabel} · ${t('pages.live_session_player.code')} : ${session?.passcode ?? '—'}`"
                :badge="session?.status"
                icon="pi-video"
                accent="red"
            >
                <template #actions>
                    <div class="flex items-center gap-3 text-white/80 text-[10px] font-black uppercase tracking-widest">
                        <div class="bg-red-500/20 text-red-300 px-3 py-1.5 rounded-full border border-red-500/30 flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            {{ t('pages.live_session_player.live_badge') }}
                        </div>
                        <span v-if="!isMock" class="bg-blue-500/20 text-blue-300 px-3 py-1.5 rounded-full border border-blue-500/30">{{ t('pages.live_session_player.real_session') }}</span>
                        <span v-else class="bg-amber-500/20 text-amber-300 px-3 py-1.5 rounded-full border border-amber-500/30">{{ t('pages.live_session_player.demo_mode') }}</span>
                        <span>~{{ t('pages.live_session_player.minutes_left', { count: minutesLeft }) }}</span>
                    </div>
                </template>
            </PageHeader>

            <div class="page-container page-section">
                <div v-if="isLiveKit" class="bg-slate-900 rounded-xl overflow-hidden shadow-2xl mb-6">
                    <VideoConference :session="session" :user="user" :requires-confirmation="requires_confirmation" />
                </div>

                <div v-else-if="isMock" class="bg-slate-900 rounded-xl overflow-hidden shadow-2xl mb-6">
                    <div class="aspect-video flex flex-col items-center justify-center text-white/80 p-8 text-center">
                        <i class="pi pi-info-circle text-6xl mb-4 text-amber-400"></i>
                        <h2 class="text-2xl font-black mb-2">{{ session?.title }}</h2>
                        <p class="text-sm mb-6 text-amber-300">{{ t('pages.live_session_player.demo_notice') }}</p>
                        <Tag :value="t('pages.live_session_player.demo_mode')" severity="warning" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                    </div>
                </div>

                <div v-else class="bg-slate-900 rounded-xl overflow-hidden shadow-2xl mb-6">
                    <div class="aspect-video flex flex-col items-center justify-center text-white/80 p-8 text-center">
                        <i class="pi pi-video text-6xl mb-4 text-blue-400"></i>
                        <h2 class="text-2xl font-black mb-2">{{ session?.title }}</h2>
                        <p class="text-sm mb-6">{{ t('pages.live_session_player.join_notice') }}</p>
                        <Button :label="t('pages.live_session_player.btn_join')" icon="pi pi-external-link" @click="openInNewTab" class="!bg-gradient-to-r !from-blue-500 !to-red-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 !px-6" />
                        <p class="text-[10px] text-white/40 mt-4 font-mono">{{ joinUrl }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_session_player.instructor') }}</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.instructor?.name ?? '—' }}</div>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_session_player.duration') }}</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.duration ?? '—' }} min</div>
                    </div>
                    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.live_session_player.capacity') }}</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.max_participants ?? '—' }} {{ t('pages.live_session_player.participants_unit') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
