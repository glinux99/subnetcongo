<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    session: Object,
    user: Object,
});

const toast = useToast();
const minutesLeft = ref(0);
let heartbeat = null;
let countdown = null;

const joinUrl = computed(() => props.session?.meeting_url);
const providerLabel = computed(() => ({
    zoom: 'Zoom', meet: 'Google Meet', bbb: 'BigBlueButton', jitsi: 'Jitsi Meet'
}[props.session?.provider] ?? 'Classe virtuelle'));

const providerColor = computed(() => ({
    zoom: 'from-blue-500 to-blue-700', meet: 'from-emerald-500 to-emerald-700',
    bbb: 'from-indigo-500 to-indigo-700', jitsi: 'from-slate-600 to-slate-800'
}[props.session?.provider] ?? 'from-indigo-500 to-rose-500'));

const sendHeartbeat = async () => {
    try {
        await window.axios?.post?.(route('live-sessions.heartbeat', props.session.id));
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
            toast.add({ severity: 'warn', summary: 'Session terminée', detail: 'La session live est arrivée à son terme.', life: 5000 });
            clearInterval(countdown);
        }
    }, 60000);
    heartbeat = setInterval(sendHeartbeat, 60000);
    toast.add({ severity: 'success', summary: 'Connecté', detail: `Bienvenue dans la session ${props.session?.title}`, life: 3000 });
});

onBeforeUnmount(() => {
    if (heartbeat) clearInterval(heartbeat);
    if (countdown) clearInterval(countdown);
});

const openInNewTab = () => joinUrl.value && window.open(joinUrl.value, '_blank', 'noopener');
</script>

<template>
    <AppLayout>
        <Head :title="session?.title || 'Session live'" />
        <Toast />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div :class="['w-16 h-16 bg-gradient-to-br rounded-2xl flex items-center justify-center shadow-lg border border-white/10', providerColor]">
                            <i class="pi pi-video text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">{{ session?.title }}</h1>
                                <Tag :value="session?.status" :severity="session?.status === 'Live' ? 'success' : 'info'" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ providerLabel }} · Code : {{ session?.passcode ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-white/80 text-[10px] font-black uppercase tracking-widest">
                        <div class="bg-rose-500/20 text-rose-300 px-3 py-1.5 rounded-full border border-rose-500/30 flex items-center gap-2">
                            <span class="w-2 h-2 bg-rose-500 rounded-full animate-pulse"></span>
                            EN DIRECT
                        </div>
                        <span>~{{ minutesLeft }} min restantes</span>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl mb-6">
                    <div class="aspect-video flex flex-col items-center justify-center text-white/80 p-8 text-center">
                        <i class="pi pi-video text-6xl mb-4 text-indigo-400"></i>
                        <h2 class="text-2xl font-black mb-2">{{ session?.title }}</h2>
                        <p class="text-sm mb-6">Cliquez ci-dessous pour rejoindre la salle virtuelle dans un nouvel onglet sécurisé.</p>
                        <Button label="Rejoindre la session" icon="pi pi-external-link" @click="openInNewTab" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 !px-6" />
                        <p class="text-[10px] text-white/40 mt-4 font-mono">{{ joinUrl }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Instructeur</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.instructor?.name ?? '—' }}</div>
                    </div>
                    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Durée</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.duration ?? '—' }} min</div>
                    </div>
                    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Capacité</div>
                        <div class="text-lg font-black text-slate-800">{{ session?.max_participants ?? '—' }} participants</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
