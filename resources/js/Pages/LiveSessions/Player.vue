<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import VideoConference from '@/Components/LiveKit/VideoConference.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    session: Object,
    shareToken: { type: String, default: null },
    user: Object,
    requires_confirmation: { type: Boolean, default: false },
    timezone: { type: String, default: 'Africa/Lubumbashi' },
});

const sessionTimezone = computed(() => props.session?.settings?.timezone || props.timezone || 'Africa/Lubumbashi');

const formatDateTime = (value, options = {}) => {
    if (!value) return '—';
    return new Date(value).toLocaleString('fr-FR', { timeZone: sessionTimezone.value, ...options });
};

const formatShortDateTime = (value) => formatDateTime(value, { dateStyle: 'short', timeStyle: 'short' });
const formatLongDate = (value) => value ? new Date(value).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', timeZone: sessionTimezone.value }) : '—';
const formatTime = (value) => value ? new Date(value).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: sessionTimezone.value }) : '—';

const toast = useToast();
const minutesLeft = ref(0);
let heartbeat = null;
let countdown = null;

const isLiveKit = computed(() => props.session?.provider === 'livekit');
const providerLabel = computed(() => {
    const p = props.session?.provider;
    return ({ zoom: 'Zoom', meet: 'Google Meet', bbb: 'BigBlueButton', jitsi: 'Jitsi Meet', livekit: 'LiveKit (In-App)' })[p] ?? 'Session en direct';
});

const joinUrl = computed(() => props.session?.meeting_url);
const isMock = computed(() => !joinUrl.value && props.session?.provider !== 'livekit');

const statusMeta = computed(() => {
    const s = props.session?.status ?? 'Scheduled';
    if (s === 'Live') return { label: 'EN DIRECT', cls: 'text-rose-300 bg-rose-500/15 border-rose-500/40', dot: 'bg-rose-500' };
    if (s === 'Ended' || s === 'Cancelled') return { label: s === 'Ended' ? 'TERMINÉE' : 'ANNULÉE', cls: 'text-slate-300 bg-slate-700/40 border-slate-600/40', dot: 'bg-slate-400' };
    return { label: 'EN ATTENTE', cls: 'text-amber-300 bg-amber-500/15 border-amber-500/35', dot: 'bg-amber-400' };
});

const copied = ref(false);
const openingLink = ref(false);
const showDetails = ref(false);
const copyShare = async () => {
    try {
        await navigator.clipboard?.writeText(
            `${window.location.origin}/l/${props.shareToken ?? props.session?.uuid}`
        );
        copied.value = true;
        toast.add({ severity: 'info', summary: 'Lien copié', life: 1500 });
        setTimeout(() => (copied.value = false), 1800);
    } catch {
        toast.add({ severity: 'error', summary: 'Erreur', detail: "Copie impossible.", life: 2500 });
    }
};

const copyLinkIcon = computed(() => copied.value ? 'pi pi-check' : 'pi pi-link');

// Heartbeat — presence ping
const sendHeartbeat = async () => {
    try {
        await window.axios?.post?.(route('live-sessions.heartbeat', props.session.uuid));
    } catch {}
};

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
            toast.add({ severity: 'warn', summary: 'Session terminée', life: 3000 });
            clearInterval(countdown);
        }
    }, 60000);
    heartbeat = setInterval(sendHeartbeat, 60000);
});

onBeforeUnmount(() => {
    if (heartbeat) clearInterval(heartbeat);
    if (countdown) clearInterval(countdown);
});

const openInNewTab = () => {
    if (!joinUrl.value || openingLink.value) return;

    openingLink.value = true;
    const win = window.open(joinUrl.value, '_blank');

    if (!win) {
        openingLink.value = false;
        toast.add({
            severity: 'error',
            summary: 'Ouverture bloquée',
            detail: 'Autorisez les fenêtres pop-up pour rejoindre la salle, ou copiez le lien ci-dessous.',
            life: 5000,
        });
        return;
    }

    try { win.opener = null; } catch {}
    toast.add({ severity: 'success', summary: 'Salle ouverte', detail: 'La session s\'ouvre dans un nouvel onglet.', life: 3000 });
    setTimeout(() => (openingLink.value = false), 2500);
};
</script>

<template>
    <AppLayout>
        <Head :title="session?.title || 'Session live'" />

        <!-- Cadre sombre pleine page : ambiance studio -->
        <div class="relative flex flex-1 min-h-0 flex-col bg-slate-950">
            <!-- Décor : grille subtile + glow -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-32 left-1/4 h-72 w-72 rounded-full bg-red-600/15 blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 h-72 w-72 rounded-full bg-blue-600/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.06] bg-[linear-gradient(rgba(255,255,255,0.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.8)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
            </div>

            <div class="relative z-10 flex min-h-0 flex-1 flex-col">
                <!-- ===== Header compact ===== -->
                <header class="h-16 shrink-0 border-b border-white/[0.06] bg-slate-950/80 backdrop-blur-xl px-4 flex items-center gap-4">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2.5 shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded-xl pr-3 border-r border-white/[0.06]">
                        <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-red-600 via-red-500 to-orange-500 flex items-center justify-center shadow-lg shadow-red-600/40">
                            <i class="pi pi-video text-sm text-white"></i>
                        </div>
                        <div class="hidden sm:flex flex-col leading-tight">
                            <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-widest">SubnetCongo</span>
                            <span class="text-xs font-bold text-white">Live</span>
                        </div>
                    </Link>

                    <!-- Fil d'Ariane compact -->
                    <nav class="hidden md:flex items-center gap-1.5 text-[11px] text-slate-500" aria-label="Breadcrumb">
                        <Link href="/dashboard" class="hover:text-slate-200 transition-colors">Accueil</Link>
                        <i class="pi pi-angle-right text-[9px] text-slate-700"></i>
                        <Link href="/mes-lives" class="hover:text-slate-200 transition-colors hidden lg:inline">Classes virtuelles</Link>
                        <i class="pi pi-angle-right text-[9px] text-slate-700 hidden lg:inline"></i>
                        <span class="text-slate-200 font-medium max-w-[280px] truncate">{{ session?.title }}</span>
                    </nav>

                    <!-- Live badge center -->
                    <div class="flex-1 flex justify-center">
                        <div class="flex items-center gap-2 bg-slate-900/80 border border-white/[0.06] px-3.5 py-1.5 rounded-full text-[11px] font-semibold backdrop-blur">
                            <span class="relative flex h-2.5 w-2.5">
                                <span :class="['animate-ping absolute inline-flex h-full w-full rounded-full opacity-75', statusMeta.dot === 'bg-slate-400' ? 'hidden' : '']"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="statusMeta.dot"></span>
                            </span>
                            <span :class="statusMeta.cls">{{ statusMeta.label }}</span>
                            <span class="text-slate-600">|</span>
                            <span class="text-slate-400 font-mono tabular-nums">~{{ minutesLeft }} min</span>
                        </div>
                    </div>

                    <!-- Right bloc: détails + partage + retour -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            @click="showDetails = !showDetails"
                            class="h-9 w-9 inline-flex items-center justify-center rounded-xl text-slate-400 hover:text-white bg-white/[0.03] hover:bg-white/[0.08] border border-white/[0.06] hover:border-white/[0.14] transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                            :class="showDetails ? '!text-white !bg-white/[0.10] !border-white/[0.16]' : ''"
                            aria-label="Afficher les détails de la session"
                            :aria-expanded="showDetails"
                        >
                            <i class="pi pi-info-circle text-[13px]"></i>
                        </button>
                        <button
                            type="button"
                            @click="copyShare"
                            class="h-9 px-3 md:px-4 inline-flex items-center gap-2 text-[11px] font-semibold text-slate-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.08] border border-white/[0.06] hover:border-white/[0.14] rounded-xl transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                            :aria-label="copied ? 'Lien copié' : 'Copier le lien de la session'"
                            title="Copier le lien de la session"
                        >
                            <i :class="copyLinkIcon"></i>
                            <span class="hidden md:inline">{{ copied ? 'Copié' : 'Partager' }}</span>
                        </button>
                        <Link :href="route('live-sessions.index')" class="h-9 w-9 inline-flex items-center justify-center rounded-xl text-slate-400 hover:text-white hover:bg-white/[0.06] border border-transparent hover:border-white/[0.08] transition-all focus-visible:outline-2 focus-visible:outline-red-400" aria-label="Retour aux classes">
                            <i class="pi pi-arrow-left"></i>
                        </Link>
                    </div>
                </header>

                <!-- ===== Body fragmenté ===== -->
                <main class="relative flex min-h-0 flex-1 flex-col overflow-hidden p-2 sm:p-3">
                    <!-- ==== Stage vidéo plein espace ==== -->
                    <section v-if="isLiveKit" class="relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-white/[0.06] bg-slate-950 shadow-2xl">
                        <VideoConference :session="session" :user="user" :requires-confirmation="requires_confirmation" />
                    </section>

                    <section v-else-if="isMock" class="relative flex min-h-0 flex-1 flex-col overflow-y-auto rounded-2xl border border-white/[0.06] bg-slate-900 shadow-2xl p-6 sm:p-10">
                        <div class="m-auto max-w-xl text-center">
                            <div class="mx-auto mb-5 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/25">
                                <i class="pi pi-info-circle text-3xl text-amber-400"></i>
                            </div>
                            <h2 class="text-xl md:text-2xl font-black text-white mb-2 tracking-tight">Session en mode démonstration</h2>
                            <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">Aucun fournisseur de visioconférence n'est configuré. Cette salle ne peut pas être rejointe pour le moment.</p>
                        </div>
                    </section>

                    <section v-else class="relative flex min-h-0 flex-1 flex-col overflow-y-auto rounded-2xl border border-white/[0.06] bg-slate-900 shadow-2xl p-6 sm:p-10">
                        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl">
                            <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.07)_1px,transparent_1px)] [background-size:22px_22px]"></div>
                            <div class="absolute top-0 right-0 h-48 w-48 rounded-full bg-blue-600/15 blur-3xl"></div>
                            <div class="absolute bottom-0 left-0 h-48 w-48 rounded-full bg-red-600/15 blur-3xl"></div>
                        </div>

                        <div class="relative m-auto max-w-xl text-center text-slate-100">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600/25 to-red-600/25 border border-white/[0.10] mb-5">
                                <i class="pi pi-video text-2xl text-blue-300"></i>
                            </div>
                            <h2 class="text-xl md:text-2xl font-black text-white tracking-tight break-words">{{ session?.title }}</h2>
                            <p class="mt-2 text-xs sm:text-sm text-slate-400 leading-relaxed break-words">{{ session?.course?.title ?? 'Session en direct' }}</p>

                            <div class="my-6 p-3 rounded-xl bg-slate-800/50 border border-white/[0.06] text-left text-[11px] text-slate-300 space-y-2">
                                <div class="flex items-center gap-2"><i class="pi pi-clock text-[10px] text-slate-500"></i> {{ formatDateTime(session.start_at) }}</div>
                                <div class="flex items-center gap-2"><i class="pi pi-stopwatch text-[10px] text-slate-500"></i> {{ session?.duration ?? '—' }} min</div>
                                <div class="flex items-center gap-2"><i class="pi pi-users text-[10px] text-slate-500"></i> {{ session?.max_participants ?? '—' }} participants max</div>
                            </div>

                            <button
                                type="button"
                                @click="openInNewTab"
                                :disabled="openingLink"
                                class="inline-flex items-center justify-center gap-2 h-12 px-7 rounded-xl bg-gradient-to-r from-red-600 to-orange-500 text-[11px] font-black uppercase tracking-widest text-white shadow-lg shadow-red-900/30 hover:brightness-110 transition-all focus-visible:outline-2 focus-visible:outline-red-400 disabled:cursor-wait disabled:opacity-70"
                            >
                                <i :class="openingLink ? 'pi pi-spin pi-spinner' : 'pi pi-external-link'" class="text-sm"></i>
                                {{ openingLink ? 'Ouverture…' : 'Rejoindre la salle' }}
                            </button>
                            <p class="mt-4 text-[10px] text-slate-500 font-mono break-all px-4">{{ joinUrl }}</p>
                        </div>
                    </section>

                    <!-- ==== Détails de session (drawer) ==== -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="showDetails" class="absolute inset-0 z-20">
                            <button type="button" class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" @click="showDetails = false" aria-label="Fermer les détails"></button>
                            <aside class="absolute right-0 top-0 bottom-0 w-full max-w-sm overflow-y-auto border-l border-white/[0.08] bg-slate-900/95 backdrop-blur-xl shadow-2xl">
                                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/[0.06] bg-slate-900/95 px-4 py-3.5 backdrop-blur-xl">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Détails</p>
                                        <h2 class="mt-0.5 text-sm font-bold text-white truncate">Session</h2>
                                    </div>
                                    <button type="button" class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/[0.06] transition-colors" @click="showDetails = false" aria-label="Fermer les détails">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>

                                <div class="space-y-4 p-4">
                                    <div class="flex items-center gap-3 rounded-xl border border-white/[0.06] bg-white/[0.03] p-3">
                                        <div class="h-12 w-12 shrink-0 rounded-xl bg-gradient-to-br from-amber-500 to-red-600 flex items-center justify-center text-sm font-black text-white">
                                            {{ (session?.instructor?.name || 'F').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[13px] font-semibold text-white truncate">{{ session?.instructor?.name ?? 'Instructeur' }}</p>
                                            <p class="text-[10px] text-slate-400 truncate">{{ session?.instructor?.bio ?? 'Formateur' }}</p>
                                        </div>
                                    </div>

                                    <dl class="overflow-hidden rounded-xl border border-white/[0.06] text-[11px] divide-y divide-white/[0.06]">
                                        <div class="flex items-center justify-between gap-3 bg-white/[0.02] px-3 py-2.5">
                                            <dt class="text-slate-500">Durée</dt>
                                            <dd class="text-slate-200 font-mono">{{ session?.duration ?? '—' }} min</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 bg-white/[0.02] px-3 py-2.5">
                                            <dt class="text-slate-500">Début</dt>
                                            <dd class="text-slate-200 font-mono">{{ formatShortDateTime(session.start_at) }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 bg-white/[0.02] px-3 py-2.5">
                                            <dt class="text-slate-500">Participants</dt>
                                            <dd class="text-slate-200 font-mono">{{ session?.max_participants ?? '—' }} max</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 bg-white/[0.02] px-3 py-2.5">
                                            <dt class="text-slate-500">Fournisseur</dt>
                                            <dd class="text-emerald-400 font-semibold uppercase tracking-wide">{{ providerLabel }}</dd>
                                        </div>
                                    </dl>

                                    <div class="flex items-center gap-2 rounded-xl border border-emerald-400/[0.12] bg-emerald-500/[0.06] px-3 py-2.5">
                                        <i class="pi pi-lock shrink-0 text-xs text-emerald-400"></i>
                                        <span class="text-[11px] font-medium text-emerald-300">Connexion sécurisée de bout en bout</span>
                                    </div>

                                    <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-3">
                                        <p class="mb-2 flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-500"><i class="pi pi-book text-[10px]"></i> Au programme</p>
                                        <ul class="space-y-1.5 text-[11px] text-slate-300">
                                            <li v-if="session?.course?.title" class="flex items-start gap-2"><span class="mt-[3px] text-[10px] text-red-500">▸</span><span class="min-w-0 break-words">{{ session.course.title }}</span></li>
                                            <li class="flex items-start gap-2"><span class="mt-[3px] text-[10px] text-red-500">▸</span>Session de cours en direct</li>
                                            <li class="flex items-start gap-2"><span class="mt-[3px] text-[10px] text-red-500">▸</span>Questions / réponses avec l'instructeur</li>
                                        </ul>
                                    </div>

                                    <p class="border-t border-white/[0.06] pt-3 text-[10px] font-mono text-slate-600">
                                        {{ formatLongDate(session.start_at) }} · {{ formatTime(session.start_at) }}
                                    </p>
                                </div>
                            </aside>
                        </div>
                    </transition>
                </main>
            </div>
        </div>
    </AppLayout>
</template>
