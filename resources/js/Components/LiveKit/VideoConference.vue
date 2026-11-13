<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Room, RoomEvent, Track } from 'livekit-client';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    session: { type: Object, required: true },
    user: { type: Object, default: () => ({}) },
    requiresConfirmation: { type: Boolean, default: false },
});

const toast = useToast();

const room = ref(null);
const isConnected = ref(false);
const isConnecting = ref(false);
const errorMessage = ref('');
const participants = ref([]);
const screenShares = ref([]);
const isAudioEnabled = ref(false);
const isVideoEnabled = ref(false);
const isScreenSharing = ref(false);
const showChat = ref(true);
const chatMessages = ref([]);
const chatInput = ref('');
const showEmojis = ref(false);
const fileInput = ref(null);
const chatPoll = ref(null);
const accessToken = ref('');
const wsUrl = ref('');
const isInstructor = ref(false);
const currentUser = ref(null);
const audioBlocked = ref(false);
const isFullscreen = ref(false);
const stageEl = ref(null);

const showConfirm = ref(false);
const confirmName = ref('');
const confirmEmail = ref('');
const confirmSubmitting = ref(false);

const audioDevices = ref([]);
const videoDevices = ref([]);
const selectedAudioId = ref('');
const selectedVideoId = ref('');
const previewEl = ref(null);
const previewStream = ref(null);
const micGranted = ref(false);
const camGranted = ref(false);
const permissionError = ref('');
const requestingMic = ref(false);
const requestingCamera = ref(false);

const emojis = ['👍', '👏', '😂', '😊', '😍', '🙌', '🎉', '🔥', '💡', '❓', '✅', '🙏', '😮', '😢', '👋', '💪', '🤝', '⭐', '📌', '🎓'];

let heartbeatInterval = null;
let refreshTimer = null;

const videoElements = new Map();
const screenElements = new Map();

const setVideoEl = (identity) => (el) => {
    if (el) videoElements.set(identity, el);
    else videoElements.delete(identity);
};

const setScreenEl = (identity) => (el) => {
    if (el) screenElements.set(identity, el);
    else screenElements.delete(identity);
};

const canJoin = computed(() => {
    if (!props.session?.start_at) return true;
    if (props.session?.status === 'Live') return true;

    const now = Date.now();
    const startAt = new Date(props.session.start_at).getTime();
    const endAt = new Date(props.session.ended_at
        || new Date(startAt + (props.session.duration || 120) * 60000)).getTime();

    return now >= startAt - 15 * 60000 && now <= endAt;
});

const fetchToken = async () => {
    const response = await fetch(route('live-sessions.livekit-token', { live_session: props.session.uuid }), {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new Error(body.error || "Impossible de récupérer le token d'accès.");
    }

    const data = await response.json();
    accessToken.value = data.token;
    isInstructor.value = !!data.is_instructor;
    currentUser.value = data.user;
    wsUrl.value = (data.ws_url || '').replace(/\/+$/, '');
};

const listDevices = async () => {
    if (!navigator.mediaDevices?.enumerateDevices) return;

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        audioDevices.value = devices.filter((d) => d.kind === 'audioinput');
        videoDevices.value = devices.filter((d) => d.kind === 'videoinput');

        if (!selectedAudioId.value && audioDevices.value[0]) selectedAudioId.value = audioDevices.value[0].deviceId;
        if (!selectedVideoId.value && videoDevices.value[0]) selectedVideoId.value = videoDevices.value[0].deviceId;
    } catch (e) {
        /* non bloquant */
    }
};

const permissionMessage = (error) => {
    if (error?.name === 'NotAllowedError' || error?.name === 'PermissionDeniedError') {
        return "Autorisation refusée. Cliquez sur l'icône cadenas dans la barre d'adresse, autorisez l'accès, puis réessayez.";
    }
    if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
        return 'Aucun périphérique détecté sur cet appareil.';
    }
    if (error?.name === 'NotReadableError') {
        return 'Périphérique déjà utilisé par une autre application.';
    }
    return "Accès impossible : " + (error?.message || 'erreur inconnue');
};

const stopPreview = () => {
    previewStream.value?.getTracks().forEach((t) => t.stop());
    previewStream.value = null;
    if (previewEl.value) previewEl.value.srcObject = null;
};

const startPreview = async () => {
    stopPreview();

    const stream = await navigator.mediaDevices.getUserMedia({
        video: selectedVideoId.value ? { deviceId: { exact: selectedVideoId.value } } : true,
    });

    previewStream.value = stream;
    await nextTick();
    if (previewEl.value) {
        previewEl.value.srcObject = stream;
        previewEl.value.muted = true;
        previewEl.value.play?.().catch(() => {});
    }
};

const requestMic = async () => {
    if (requestingMic.value) return;
    requestingMic.value = true;
    permissionError.value = '';

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: selectedAudioId.value ? { deviceId: { exact: selectedAudioId.value } } : true,
        });
        stream.getTracks().forEach((t) => t.stop());
        micGranted.value = true;
        isAudioEnabled.value = true;
        await listDevices();

        if (room.value && isConnected.value) {
            try { await room.value.localParticipant.setMicrophoneEnabled(true); } catch (e) { isAudioEnabled.value = false; }
        }
    } catch (error) {
        micGranted.value = false;
        permissionError.value = permissionMessage(error);
    } finally {
        requestingMic.value = false;
    }
};

const requestCamera = async () => {
    if (requestingCamera.value) return;
    requestingCamera.value = true;
    permissionError.value = '';

    try {
        await startPreview();
        camGranted.value = true;
        isVideoEnabled.value = true;
        await listDevices();

        if (room.value && isConnected.value) {
            try { await room.value.localParticipant.setCameraEnabled(true); } catch (e) { isVideoEnabled.value = false; }
        }
    } catch (error) {
        camGranted.value = false;
        permissionError.value = permissionMessage(error);
    } finally {
        requestingCamera.value = false;
    }
};

const onAudioDeviceChange = async () => {
    if (micGranted.value) await requestMic();
};

const onVideoDeviceChange = async () => {
    if (camGranted.value) await requestCamera();
};

const attachTracks = () => {
    participants.value.forEach((p) => {
        const el = videoElements.get(p.identity);
        if (el && p.videoTrack) {
            p.videoTrack.attach(el);
            el.muted = p.isLocal;
        }
    });

    screenShares.value.forEach((s) => {
        const el = screenElements.get(s.identity);
        if (el && s.track) {
            s.track.attach(el);
            el.muted = true;
        }
    });
};

const refreshParticipants = () => {
    const r = room.value;
    if (!r) return;

    const list = [];
    const shares = [];
    const all = [r.localParticipant, ...r.remoteParticipants.values()];

    all.forEach((p) => {
        const videoPub = p.getTrackPublication(Track.Source.Camera);
        const audioPub = p.getTrackPublication(Track.Source.Microphone);
        const screenPub = p.getTrackPublication(Track.Source.ScreenShare);

        list.push({
            identity: p.identity,
            name: p.name || p.identity,
            isLocal: p.isLocal,
            hasVideo: !!videoPub?.track && !videoPub.isMuted,
            hasAudio: !!audioPub?.track && !audioPub.isMuted,
            videoTrack: videoPub?.track ?? null,
            participant: p,
        });

        if (screenPub?.track && !screenPub.isMuted) {
            shares.push({
                identity: p.identity,
                name: p.name || p.identity,
                isLocal: p.isLocal,
                track: screenPub.track,
            });
        }
    });

    participants.value = list;
    screenShares.value = shares;
    nextTick(attachTracks);
};

const connectToRoom = async () => {
    if (!canJoin.value) {
        errorMessage.value = "Cette session n'est pas encore active.";
        return;
    }

    isConnecting.value = true;
    errorMessage.value = '';

    try {
        await fetchToken();

        let livekitUrl = (wsUrl.value || '').trim();
        if (!livekitUrl) throw new Error('URL LiveKit manquante. Vérifiez LIVEKIT_WS_URL dans .env.');
        if (!livekitUrl.startsWith('ws://') && !livekitUrl.startsWith('wss://')) {
            livekitUrl = 'ws://' + livekitUrl;
        }

        room.value = new Room({ adaptiveStream: true, dynacast: true });

        room.value
            .on(RoomEvent.Connected, () => {
                isConnected.value = true;
                isConnecting.value = false;
                toast.add({ severity: 'success', summary: 'Connecté', detail: 'Vous êtes dans la session.', life: 3000 });
                refreshParticipants();

                heartbeatInterval = setInterval(sendHeartbeat, 60000);
                refreshTimer = setInterval(refreshParticipants, 5000);

                room.value?.startAudio?.().then(() => {
                    audioBlocked.value = room.value ? !room.value.canPlaybackAudio : false;
                }).catch(() => { audioBlocked.value = true; });
            })
            .on(RoomEvent.Disconnected, () => {
                isConnected.value = false;
                isConnecting.value = false;
            })
            .on(RoomEvent.ParticipantConnected, refreshParticipants)
            .on(RoomEvent.ParticipantDisconnected, refreshParticipants)
            .on(RoomEvent.TrackPublished, refreshParticipants)
            .on(RoomEvent.TrackUnpublished, refreshParticipants)
            .on(RoomEvent.TrackSubscribed, (track) => {
                if (track.kind === Track.Kind.Audio) {
                    track.attach();
                }
                refreshParticipants();
            })
            .on(RoomEvent.TrackUnsubscribed, (track) => {
                track.detach();
                refreshParticipants();
            })
            .on(RoomEvent.TrackMuted, refreshParticipants)
            .on(RoomEvent.TrackUnmuted, refreshParticipants)
            .on(RoomEvent.LocalTrackPublished, refreshParticipants)
            .on(RoomEvent.LocalTrackUnpublished, refreshParticipants)
            .on(RoomEvent.AudioPlaybackStatusChanged, () => {
                audioBlocked.value = room.value ? !room.value.canPlaybackAudio : false;
            });

        await room.value.connect(livekitUrl, accessToken.value);

        // Autorisations manquantes : on les demande séparément (micro puis caméra)
        if (!micGranted.value) {
            await requestMic();
        }
        if (!camGranted.value) {
            await requestCamera();
        }

        if (selectedAudioId.value) {
            try { await room.value.switchActiveDevice('audioinput', selectedAudioId.value); } catch (e) { /* ignore */ }
        }
        if (selectedVideoId.value) {
            try { await room.value.switchActiveDevice('videoinput', selectedVideoId.value); } catch (e) { /* ignore */ }
        }

        stopPreview();

        if (micGranted.value) {
            try {
                await room.value.localParticipant.setMicrophoneEnabled(true);
                isAudioEnabled.value = true;
            } catch (e) {
                isAudioEnabled.value = false;
                toast.add({ severity: 'warn', summary: 'Micro', detail: 'Microphone indisponible.', life: 4000 });
            }
        }

        if (camGranted.value) {
            try {
                await room.value.localParticipant.setCameraEnabled(true);
                isVideoEnabled.value = true;
            } catch (e) {
                isVideoEnabled.value = false;
                toast.add({ severity: 'warn', summary: 'Caméra', detail: 'Caméra indisponible.', life: 4000 });
            }
        }

        refreshParticipants();
    } catch (error) {
        console.error('LiveKit connection error:', error);
        isConnecting.value = false;
        errorMessage.value = error?.message || 'Erreur de connexion au serveur LiveKit.';
        toast.add({ severity: 'error', summary: 'Erreur', detail: errorMessage.value, life: 5000 });
    }
};

const disconnectFromRoom = async () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
    if (room.value) {
        await room.value.disconnect();
        room.value = null;
    }
    isConnected.value = false;
    participants.value = [];
    screenShares.value = [];
    videoElements.clear();
    screenElements.clear();
};

const toggleAudio = async () => {
    if (!room.value) return;

    if (!isAudioEnabled.value && !micGranted.value) {
        await requestMic();
        if (!micGranted.value) return;
    }

    isAudioEnabled.value = !isAudioEnabled.value;
    await room.value.localParticipant.setMicrophoneEnabled(isAudioEnabled.value);
};

const toggleVideo = async () => {
    if (!room.value) return;

    if (!isVideoEnabled.value && !camGranted.value) {
        await requestCamera();
        if (!camGranted.value) return;
    }

    isVideoEnabled.value = !isVideoEnabled.value;
    await room.value.localParticipant.setCameraEnabled(isVideoEnabled.value);
};

const toggleScreenShare = async () => {
    if (!room.value) return;

    try {
        if (!isScreenSharing.value) {
            await room.value.localParticipant.setScreenShareEnabled(true, { audio: true });
            isScreenSharing.value = true;
        } else {
            await room.value.localParticipant.setScreenShareEnabled(false);
            isScreenSharing.value = false;
        }
        refreshParticipants();
    } catch (error) {
        if (error?.name !== 'NotAllowedError') {
            toast.add({ severity: 'error', summary: 'Erreur', detail: "Impossible de partager l'écran.", life: 4000 });
        }
    }
};

const enableAudio = async () => {
    try {
        await room.value?.startAudio();
        audioBlocked.value = room.value ? !room.value.canPlaybackAudio : false;
    } catch (e) {
        audioBlocked.value = true;
    }
};

const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await stageEl.value?.requestFullscreen?.();
        } else {
            await document.exitFullscreen();
        }
    } catch (e) {
        toast.add({ severity: 'warn', summary: 'Plein écran', detail: 'Non disponible dans ce navigateur.', life: 3000 });
    }
};

const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

const sendChatMessage = async () => {
    if (!chatInput.value.trim()) return;

    const text = chatInput.value.trim();

    try {
        const response = await fetch(route('live-sessions.chat.send', { live_session: props.session.uuid }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: text }),
        });

        if (!response.ok) throw new Error('Envoi impossible');

        chatInput.value = '';
        showEmojis.value = false;
        await loadChat();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: "Impossible d'envoyer le message.", life: 4000 });
    }
};

const appendEmoji = (emoji) => {
    chatInput.value += emoji;
};

const loadChat = async () => {
    try {
        const response = await fetch(route('live-sessions.chat.messages', { live_session: props.session.uuid }), {
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) return;

        const data = await response.json();
        chatMessages.value = (data.messages ?? []).map((m) => ({
            ...m,
            mine: !!currentUser.value && m.sender === currentUser.value.name,
        }));
    } catch (e) {
        /* non bloquant */
    }
};

const uploadDocument = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await fetch(route('live-sessions.documents.upload', { live_session: props.session.uuid }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            const body = await response.json().catch(() => ({}));
            throw new Error(body.message || body.error || 'Envoi impossible');
        }

        toast.add({ severity: 'success', summary: 'Document partagé', detail: file.name, life: 3000 });
        await loadChat();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: error.message, life: 5000 });
    } finally {
        event.target.value = '';
    }
};

const formatBytes = (bytes) => {
    const value = Number(bytes || 0);
    if (value < 1024) return `${value} o`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(0)} Ko`;
    return `${(value / (1024 * 1024)).toFixed(1)} Mo`;
};

const sendHeartbeat = async () => {
    if (!isConnected.value) return;
    try {
        await fetch(route('live-sessions.heartbeat', { live_session: props.session.uuid }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        /* non bloquant */
    }
};

const leaveRoom = async () => {
    await disconnectFromRoom();

    if (props.requiresConfirmation) {
        confirmName.value = currentUser.value?.name || '';
        confirmEmail.value = '';
        showConfirm.value = true;
        return;
    }

    window.location.href = route('live-sessions.mine');
};

const submitConfirmation = async () => {
    confirmSubmitting.value = true;
    try {
        await fetch(route('live-sessions.confirm-info', { live_session: props.session.uuid }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name: confirmName.value, email: confirmEmail.value }),
        });
    } catch (e) {
        /* non bloquant */
    } finally {
        confirmSubmitting.value = false;
        showConfirm.value = false;
        window.location.href = route('live-sessions.mine');
    }
};

const skipConfirmation = () => {
    showConfirm.value = false;
    window.location.href = route('live-sessions.mine');
};

const copyLink = async () => {
    const link = route('live-sessions.join', props.session.uuid);
    try {
        await navigator.clipboard.writeText(link);
        toast.add({ severity: 'success', summary: 'Lien copié', detail: link, life: 4000 });
    } catch (e) {
        window.prompt('Copiez le lien de la session :', link);
    }
};

onMounted(async () => {
    if (props.session?.provider !== 'livekit') {
        errorMessage.value = "Cette session n'utilise pas LiveKit.";
        return;
    }

    document.addEventListener('fullscreenchange', onFullscreenChange);
    await listDevices();

    if (!canJoin.value) {
        errorMessage.value = "Cette session n'est pas active actuellement.";
    }

    await loadChat();
    chatPoll.value = setInterval(loadChat, 4000);
});

onUnmounted(() => {
    if (heartbeatInterval) clearInterval(heartbeatInterval);
    if (chatPoll.value) clearInterval(chatPoll.value);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    stopPreview();
    disconnectFromRoom();
});
</script>

<template>
    <div class="livekit-video-conference">
        <div class="max-w-[1600px] mx-auto p-4 lg:p-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between mb-4 gap-4 flex-wrap">
                <div class="min-w-0">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <Tag :value="isConnected ? 'En direct' : 'Prêt à rejoindre'" :severity="isConnected ? 'danger' : 'info'" :class="isConnected ? 'animate-pulse' : ''" />
                        <span class="text-xs text-slate-500 font-semibold flex items-center gap-1">
                            <i class="pi pi-users text-[10px]"></i> {{ participants.length }} participant(s)
                        </span>
                        <span v-if="isInstructor" class="text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 px-2 py-1 rounded">Formateur</span>
                    </div>
                    <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight truncate">{{ session.title }}</h1>
                    <p class="text-slate-500 mt-1">{{ session.course?.title || 'Session en direct' }}</p>
                </div>
                <div class="flex gap-2">
                    <Button icon="pi pi-link" rounded outlined severity="secondary" v-tooltip.bottom="'Copier le lien de la session'" @click="copyLink" />
                    <Button icon="pi pi-comments" :severity="showChat ? 'primary' : 'secondary'" :outlined="!showChat" rounded v-tooltip.bottom="showChat ? 'Masquer le chat' : 'Afficher le chat'" @click="showChat = !showChat" />
                    <Button v-if="!isConnected" label="Rejoindre" icon="pi pi-sign-in" @click="connectToRoom" :loading="isConnecting" :disabled="!canJoin" />
                    <Button v-else label="Quitter" icon="pi pi-sign-out" severity="danger" @click="leaveRoom" />
                </div>
            </div>

            <div v-if="errorMessage" class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 font-medium">
                {{ errorMessage }}
            </div>

            <div v-if="!canJoin && !errorMessage" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-700 font-medium">
                Cette session n'est pas encore active. Début prévu : {{ new Date(session.start_at).toLocaleString('fr-FR') }}.
            </div>

            <div class="flex flex-col xl:flex-row gap-4 items-start">
                <!-- Scène vidéo -->
                <div class="flex-1 min-w-0 w-full space-y-3">
                    <div ref="stageEl" class="relative aspect-video bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
                        <!-- Autorisations manquantes une fois connecté -->
                        <div v-if="isConnected && (!micGranted || !camGranted)" class="absolute top-2 left-1/2 -translate-x-1/2 z-20 flex flex-wrap gap-2 bg-black/75 p-2 rounded-xl">
                            <Button v-if="!micGranted" label="Autoriser le micro" size="small" severity="warn" icon="pi pi-microphone" :loading="requestingMic" @click="requestMic" />
                            <Button v-if="!camGranted" label="Autoriser la caméra" size="small" severity="warn" icon="pi pi-video" :loading="requestingCamera" @click="requestCamera" />
                        </div>

                        <!-- Pré-salle -->
                        <div v-if="!isConnected" class="absolute inset-0 flex flex-col lg:flex-row items-center justify-center gap-6 p-4 lg:p-6">
                            <div class="w-full max-w-xl aspect-video bg-black rounded-xl overflow-hidden relative border border-white/10">
                                <video ref="previewEl" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                <div v-if="!camGranted" class="absolute inset-0 flex items-center justify-center p-6 text-center">
                                    <div>
                                        <i class="pi pi-video text-4xl text-slate-600 mb-3 block"></i>
                                        <p class="text-slate-400 text-sm leading-relaxed">
                                            {{ permissionError || 'Autorisez la caméra pour vous prévisualiser.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full max-w-sm space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Microphone</label>
                                    <div class="flex gap-2">
                                        <select v-model="selectedAudioId" @change="onAudioDeviceChange"
                                                class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                            <option v-if="!audioDevices.length" value="">Aucun micro détecté</option>
                                            <option v-for="d in audioDevices" :key="d.deviceId" :value="d.deviceId">{{ d.label || 'Microphone' }}</option>
                                        </select>
                                        <Button :icon="micGranted ? 'pi pi-check' : 'pi pi-microphone'" :severity="micGranted ? 'success' : 'secondary'"
                                                :outlined="!micGranted" :loading="requestingMic" v-tooltip.top="micGranted ? 'Micro autorisé' : 'Autoriser le micro'" @click="requestMic" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Caméra</label>
                                    <div class="flex gap-2">
                                        <select v-model="selectedVideoId" @change="onVideoDeviceChange"
                                                class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                            <option v-if="!videoDevices.length" value="">Aucune caméra détectée</option>
                                            <option v-for="d in videoDevices" :key="d.deviceId" :value="d.deviceId">{{ d.label || 'Caméra' }}</option>
                                        </select>
                                        <Button :icon="camGranted ? 'pi pi-check' : 'pi pi-video'" :severity="camGranted ? 'success' : 'secondary'"
                                                :outlined="!camGranted" :loading="requestingCamera" v-tooltip.top="camGranted ? 'Caméra autorisée' : 'Autoriser la caméra'" @click="requestCamera" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                    <span :class="micGranted ? 'text-emerald-600' : 'text-slate-400'"><i class="pi pi-microphone text-[10px] mr-1"></i>Micro {{ micGranted ? 'autorisé' : 'non autorisé' }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span :class="camGranted ? 'text-emerald-600' : 'text-slate-400'"><i class="pi pi-video text-[10px] mr-1"></i>Caméra {{ camGranted ? 'autorisée' : 'non autorisée' }}</span>
                                </div>

                                <Button label="Rejoindre la salle" icon="pi pi-video" class="w-full" severity="danger"
                                        :loading="isConnecting" :disabled="!canJoin" @click="connectToRoom" />
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Le navigateur demande l'autorisation du micro et de la caméra séparément.
                                </p>
                            </div>
                        </div>

                        <!-- Partage d'écran -->
                        <div v-else-if="screenShares.length" class="absolute inset-0 p-2 pb-20 flex flex-col gap-2">
                            <div v-for="s in screenShares" :key="'screen-' + s.identity" class="flex-1 min-h-0 bg-black rounded-lg overflow-hidden relative">
                                <video :ref="setScreenEl(s.identity)" autoplay playsinline class="w-full h-full object-contain"></video>
                                <div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs px-2 py-1 rounded flex items-center gap-2">
                                    <i class="pi pi-desktop text-[10px] text-emerald-400"></i>
                                    <span>Partage d'écran — {{ s.name }}<template v-if="s.isLocal"> (vous)</template></span>
                                </div>
                            </div>
                            <div class="flex gap-2 h-24 shrink-0 overflow-x-auto">
                                <div v-for="p in participants" :key="'thumb-' + p.identity" class="relative w-40 shrink-0 bg-slate-800 rounded-lg overflow-hidden">
                                    <video :ref="setVideoEl(p.identity)" autoplay playsinline :muted="p.isLocal" class="w-full h-full object-cover"></video>
                                    <div class="absolute bottom-1 left-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded truncate max-w-[90%]">
                                        {{ p.name }}<template v-if="p.isLocal"> (vous)</template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grille participants -->
                        <div v-else class="absolute inset-0 grid gap-2 p-2 pb-20" :class="participants.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                            <div v-for="p in participants" :key="p.identity" class="relative bg-slate-800 rounded-lg overflow-hidden">
                                <video :ref="setVideoEl(p.identity)" autoplay playsinline :muted="p.isLocal" class="w-full h-full object-cover"></video>
                                <div class="absolute bottom-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded flex items-center gap-2">
                                    <span>{{ p.name }}<template v-if="p.isLocal"> (vous)</template></span>
                                    <i v-if="p.hasAudio" class="pi pi-microphone text-[10px] text-emerald-400"></i>
                                    <i v-if="!p.hasVideo" class="pi pi-video-slash text-[10px] text-rose-400"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Barre de contrôle superposée (visible en plein écran) -->
                        <div v-if="isConnected" class="absolute bottom-0 inset-x-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
                            <div v-if="audioBlocked" class="mb-2 flex justify-center">
                                <Button label="Activer le son" icon="pi pi-volume-up" size="small" severity="warn" @click="enableAudio" />
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <Button :icon="`pi pi-${isAudioEnabled ? 'microphone' : 'microphone-slash'}`" rounded
                                        :severity="isAudioEnabled ? 'secondary' : 'danger'"
                                        v-tooltip.top="isAudioEnabled ? 'Couper le micro' : 'Activer le micro'" @click="toggleAudio" />
                                <Button :icon="`pi pi-${isVideoEnabled ? 'video' : 'video-slash'}`" rounded
                                        :severity="isVideoEnabled ? 'secondary' : 'danger'"
                                        v-tooltip.top="isVideoEnabled ? 'Couper la caméra' : 'Activer la caméra'" @click="toggleVideo" />
                                <Button :icon="`pi pi-${isScreenSharing ? 'desktop' : 'clone'}`" rounded
                                        :severity="isScreenSharing ? 'primary' : 'secondary'"
                                        v-tooltip.top="isScreenSharing ? 'Arrêter le partage' : 'Partager l’écran'" @click="toggleScreenShare" />
                                <Button :icon="`pi pi-${isFullscreen ? 'window-minimize' : 'window-maximize'}`" rounded severity="secondary"
                                        v-tooltip.top="isFullscreen ? 'Quitter le plein écran' : 'Plein écran'" @click="toggleFullscreen" />
                                <Button icon="pi pi-comments" rounded :severity="showChat ? 'primary' : 'secondary'"
                                        v-tooltip.top="showChat ? 'Masquer le chat' : 'Afficher le chat'" @click="showChat = !showChat" />
                                <Button icon="pi pi-sign-out" rounded severity="danger" v-tooltip.top="'Quitter'" @click="leaveRoom" />
                            </div>
                        </div>
                    </div>

                    <!-- Participants -->
                    <div v-if="participants.length" class="flex gap-2 overflow-x-auto pb-1">
                        <div v-for="p in participants" :key="'chip-' + p.identity"
                             class="flex items-center gap-2 bg-white border border-slate-200 rounded-full pl-1 pr-3 py-1 shrink-0">
                            <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-black flex items-center justify-center">
                                {{ (p.name || '?').charAt(0).toUpperCase() }}
                            </span>
                            <span class="text-xs font-semibold text-slate-700 max-w-[140px] truncate">{{ p.name }}<template v-if="p.isLocal"> (vous)</template></span>
                            <i class="pi text-[10px]" :class="p.hasAudio ? 'pi-microphone text-emerald-500' : 'pi-microphone-slash text-rose-400'"></i>
                            <i class="pi text-[10px]" :class="p.hasVideo ? 'pi-video text-indigo-500' : 'pi-video-slash text-rose-400'"></i>
                        </div>
                    </div>
                </div>

                <!-- Panneau chat latéral -->
                <aside v-if="showChat" class="w-full xl:w-[340px] shrink-0 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[520px] xl:h-[calc(100vh-220px)] xl:sticky xl:top-4">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-800 flex items-center gap-2"><i class="pi pi-comments text-indigo-500"></i> Chat en direct</h2>
                        <Button icon="pi pi-times" rounded text severity="secondary" v-tooltip.left="'Fermer le chat'" @click="showChat = false" />
                    </div>

                    <div class="flex-1 overflow-auto p-3 space-y-2">
                        <div v-for="msg in chatMessages" :key="msg.id" class="p-2.5 rounded-xl" :class="msg.mine ? 'bg-indigo-50' : 'bg-slate-50'">
                            <p class="text-[11px] font-bold text-slate-700">{{ msg.sender }}</p>

                            <a v-if="msg.file" :href="msg.file.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 mt-1 p-2 bg-white border border-slate-200 rounded-lg hover:border-indigo-400">
                                <i class="pi pi-file text-indigo-600"></i>
                                <span class="text-xs text-slate-700 truncate">{{ msg.file.name }}</span>
                                <span class="text-[10px] text-slate-400 shrink-0">{{ formatBytes(msg.file.size) }}</span>
                                <i class="pi pi-download text-slate-400 text-xs ml-auto"></i>
                            </a>

                            <p v-else class="text-sm text-slate-700 break-words">{{ msg.message }}</p>
                            <p class="text-[10px] text-slate-400 mt-1">{{ msg.timestamp }}</p>
                        </div>
                        <div v-if="chatMessages.length === 0" class="text-center text-slate-400 py-10">
                            <i class="pi pi-comment text-3xl mb-2 block text-slate-300"></i>
                            <p class="text-sm">Aucun message pour le moment.</p>
                        </div>
                    </div>

                    <div class="relative border-t border-slate-100 p-3">
                        <div v-if="showEmojis" class="absolute bottom-full left-3 right-3 mb-1 bg-white border border-slate-200 rounded-xl shadow-lg p-2 grid grid-cols-8 gap-1">
                            <button v-for="e in emojis" :key="e" type="button" class="text-xl hover:bg-slate-100 rounded p-1" @click="appendEmoji(e)">{{ e }}</button>
                        </div>
                        <div class="flex gap-2">
                            <input ref="fileInput" type="file" class="hidden" @change="uploadDocument"
                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.png,.jpg,.jpeg,.webp,.gif,.mp4" />
                            <Button icon="pi pi-paperclip" rounded outlined severity="secondary" v-tooltip.top="'Partager un document'" @click="fileInput?.click()" />
                            <Button icon="pi pi-smile" rounded outlined severity="secondary" v-tooltip.top="'Émojis'" @click="showEmojis = !showEmojis" />
                            <InputText v-model="chatInput" placeholder="Écrivez un message..." class="flex-1" @keyup.enter="sendChatMessage" @focus="showEmojis = false" />
                            <Button icon="pi pi-send" rounded @click="sendChatMessage" v-tooltip.top="'Envoyer'" />
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- Confirmation des informations en fin de session -->
        <Dialog v-model:visible="showConfirm" modal header="Confirmez vos informations" :style="{ width: '440px', maxWidth: '95vw' }">
            <div class="space-y-3 pt-2">
                <p class="text-sm text-slate-600">Merci d'avoir suivi cette session. Confirmez vos informations pour votre attestation de présence.</p>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Nom complet</label>
                    <InputText v-model="confirmName" class="w-full" />
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">E-mail</label>
                    <InputText v-model="confirmEmail" type="email" class="w-full" />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 w-full">
                    <Button label="Plus tard" text severity="secondary" @click="skipConfirmation" />
                    <Button label="Confirmer" icon="pi pi-check" :loading="confirmSubmitting" @click="submitConfirmation" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
video {
    transform: translateZ(0);
}

:deep(.p-button.p-button-danger:not(.p-button-outlined)) .p-button-icon,
:deep(.p-button.p-button-danger:not(.p-button-outlined)) .p-button-label,
:deep(.p-button.p-button-primary:not(.p-button-outlined)) .p-button-icon,
:deep(.p-button.p-button-primary:not(.p-button-outlined)) .p-button-label {
    color: #ffffff !important;
}
</style>
