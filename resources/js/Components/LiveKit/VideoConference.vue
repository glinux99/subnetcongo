<script setup>
import { ref, shallowRef, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Room, RoomEvent, Track } from 'livekit-client';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    session: { type: Object, required: true },
    user: { type: Object, default: () => ({}) },
    requiresConfirmation: { type: Boolean, default: false },
});

const toast = useToast();

const room = shallowRef(null);
const isConnected = ref(false);
const isConnecting = ref(false);
const connectingStatus = ref('');
const errorMessage = ref('');
const participants = ref([]);
const screenShares = ref([]);
const isAudioEnabled = ref(false);
const isVideoEnabled = ref(false);
const isScreenSharing = ref(false);
const showChat = ref(false);
const chatMessages = ref([]);
const pendingMessages = ref([]);
const chatInput = ref('');
const chatSending = ref(false);
const chatRefreshing = ref(false);
const chatScrollEl = ref(null);
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
const previewStream = shallowRef(null);
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

const displayedMessages = computed(() => [...chatMessages.value, ...pendingMessages.value]);

const sessionTimezone = computed(() => props.session?.settings?.timezone || 'Africa/Lubumbashi');
const sessionStartLabel = computed(() => {
    if (!props.session?.start_at) return '—';
    return new Date(props.session.start_at).toLocaleString('fr-FR', { timeZone: sessionTimezone.value });
});

const scrollChatToBottom = async () => {
    await nextTick();
    if (chatScrollEl.value) chatScrollEl.value.scrollTop = chatScrollEl.value.scrollHeight;
};

const syncLocalMediaState = () => {
    const participant = room.value?.localParticipant;
    if (!participant) return;

    const audioPub = participant.getTrackPublication(Track.Source.Microphone);
    const videoPub = participant.getTrackPublication(Track.Source.Camera);

    isAudioEnabled.value = !!audioPub?.track && !audioPub.isMuted;
    isVideoEnabled.value = !!videoPub?.track && !videoPub.isMuted;
};

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
        await listDevices();
    } catch (error) {
        micGranted.value = false;
        permissionError.value = permissionMessage(error);
        if (isConnected.value) {
            toast.add({ severity: 'error', summary: 'Micro', detail: permissionError.value, life: 5000 });
        }
    } finally {
        requestingMic.value = false;
    }
};

const requestCamera = async () => {
    if (requestingCamera.value) return;
    requestingCamera.value = true;
    permissionError.value = '';

    try {
        if (room.value && isConnected.value) {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: selectedVideoId.value ? { deviceId: { exact: selectedVideoId.value } } : true,
            });
            stream.getTracks().forEach((t) => t.stop());
        } else {
            await startPreview();
        }
        camGranted.value = true;
        await listDevices();
    } catch (error) {
        camGranted.value = false;
        permissionError.value = permissionMessage(error);
        if (isConnected.value) {
            toast.add({ severity: 'error', summary: 'Caméra', detail: permissionError.value, life: 5000 });
        }
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
    connectingStatus.value = 'Préparation de la salle…';
    errorMessage.value = '';
    toast.add({ severity: 'info', summary: 'Connexion en cours', detail: 'Vous allez rejoindre la salle…', life: 2000 });

    try {
        await fetchToken();
        connectingStatus.value = 'Ouverture d\'une connexion sécurisée…';

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
                connectingStatus.value = '';
                toast.add({ severity: 'success', summary: 'Connecté', detail: 'Vous êtes dans la session.', life: 3000 });
                refreshParticipants();
                syncLocalMediaState();

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
            .on(RoomEvent.TrackMuted, () => {
                refreshParticipants();
                syncLocalMediaState();
            })
            .on(RoomEvent.TrackUnmuted, () => {
                refreshParticipants();
                syncLocalMediaState();
            })
            .on(RoomEvent.LocalTrackPublished, () => {
                refreshParticipants();
                syncLocalMediaState();
            })
            .on(RoomEvent.LocalTrackUnpublished, () => {
                refreshParticipants();
                syncLocalMediaState();
            })
            .on(RoomEvent.AudioPlaybackStatusChanged, () => {
                audioBlocked.value = room.value ? !room.value.canPlaybackAudio : false;
            });

        await room.value.connect(livekitUrl, accessToken.value);
        connectingStatus.value = 'Activation de vos périphériques…';

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

        syncLocalMediaState();
        refreshParticipants();
    } catch (error) {
        console.error('LiveKit connection error:', error);
        isConnecting.value = false;
        connectingStatus.value = '';
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

    try {
        if (!isAudioEnabled.value && !micGranted.value) {
            await requestMic();
            if (!micGranted.value) return;
        }

        const enable = !isAudioEnabled.value;
        await room.value.localParticipant.setMicrophoneEnabled(enable);
        syncLocalMediaState();

        if (enable && !isAudioEnabled.value) {
            toast.add({ severity: 'warn', summary: 'Micro', detail: 'Le microphone n’a pas pu être activé.', life: 4000 });
        }
    } catch (error) {
        syncLocalMediaState();
        toast.add({ severity: 'error', summary: 'Micro', detail: permissionMessage(error), life: 5000 });
    }
};

const toggleVideo = async () => {
    if (!room.value) return;

    try {
        if (!isVideoEnabled.value && !camGranted.value) {
            await requestCamera();
            if (!camGranted.value) return;
        }

        const enable = !isVideoEnabled.value;
        await room.value.localParticipant.setCameraEnabled(enable);
        syncLocalMediaState();

        if (enable && !isVideoEnabled.value) {
            toast.add({ severity: 'warn', summary: 'Caméra', detail: 'La caméra n’a pas pu être activée.', life: 4000 });
        }
    } catch (error) {
        syncLocalMediaState();
        toast.add({ severity: 'error', summary: 'Caméra', detail: permissionMessage(error), life: 5000 });
    }
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
    if (chatSending.value) return;

    const text = chatInput.value.trim();
    if (!text) return;

    const tempId = `pending-${Date.now()}`;
    pendingMessages.value.push({
        id: tempId,
        message: text,
        sender: currentUser.value?.name || 'Vous',
        timestamp: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
        mine: true,
        pending: true,
        failed: false,
    });

    chatInput.value = '';
    showEmojis.value = false;
    chatSending.value = true;
    scrollChatToBottom();

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

        const data = await response.json().catch(() => ({}));
        pendingMessages.value = pendingMessages.value.filter((m) => m.id !== tempId);

        if (data.message) {
            chatMessages.value.push({ ...data.message, mine: true });
            scrollChatToBottom();
        }

        await loadChat();
    } catch (error) {
        const pending = pendingMessages.value.find((m) => m.id === tempId);
        if (pending) {
            pending.pending = false;
            pending.failed = true;
        }
        toast.add({ severity: 'error', summary: 'Erreur', detail: "Impossible d'envoyer le message. Cliquez sur Réessayer.", life: 4000 });
    } finally {
        chatSending.value = false;
    }
};

const retryChatMessage = (msg) => {
    pendingMessages.value = pendingMessages.value.filter((m) => m.id !== msg.id);
    chatInput.value = msg.message;
    sendChatMessage();
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
            mine: m.mine ?? (!!currentUser.value && m.sender === currentUser.value.name),
        }));

        if (showChat.value) scrollChatToBottom();
    } catch (e) {
        /* non bloquant */
    }
};

const reloadChat = async () => {
    if (chatRefreshing.value) return;
    chatRefreshing.value = true;
    try {
        await loadChat();
    } finally {
        chatRefreshing.value = false;
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
    permissionError.value = '';
    errorMessage.value = '';

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
    <div class="livekit-video-conference relative flex min-h-0 w-full flex-1 overflow-hidden bg-slate-950 text-slate-100">
        <!-- ===== Colonne scène ===== -->
        <div class="relative flex min-h-0 min-w-0 flex-1 flex-col">
            <div class="relative flex min-h-0 flex-1 p-2.5 sm:p-3">
                <div ref="stageEl" class="relative flex min-h-0 w-full flex-col overflow-hidden rounded-xl border border-white/[0.08] bg-[#070b11] shadow-inner">
                    <!-- Statut connecté -->
                    <div v-if="isConnected" class="absolute left-3 top-3 z-20 flex items-center gap-2 rounded-full border border-white/[0.10] bg-slate-950/75 px-3 py-1.5 backdrop-blur-md">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-500 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                        </span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-rose-300">En direct</span>
                        <span class="h-3 w-px bg-white/10"></span>
                        <span class="text-[10px] font-semibold text-slate-300">{{ participants.length }} participant{{ participants.length > 1 ? 's' : '' }}</span>
                    </div>

                    <!-- Actions haut droite -->
                    <div class="absolute right-3 top-3 z-20 flex items-center gap-2">
                        <span v-if="isInstructor" class="inline-flex items-center gap-1.5 rounded-full border border-indigo-400/30 bg-indigo-500/15 px-2.5 py-1.5 text-[9px] font-black uppercase tracking-widest text-indigo-200 backdrop-blur">
                            <i class="pi pi-star-fill text-[9px]"></i>
                            Formateur
                        </span>
                        <button
                            v-if="!showChat"
                            type="button"
                            class="inline-flex h-9 items-center gap-2 rounded-xl border border-white/[0.10] bg-slate-950/70 px-3 text-[11px] font-semibold text-slate-200 backdrop-blur-md transition-colors hover:bg-white/[0.10] focus-visible:outline-2 focus-visible:outline-red-400"
                            @click="showChat = true"
                            aria-label="Ouvrir le chat"
                        >
                            <i class="pi pi-comments text-xs"></i>
                            <span class="hidden sm:inline">Chat</span>
                        </button>
                    </div>

                    <!-- Erreur connecté -->
                    <div v-if="isConnected && errorMessage" class="absolute left-1/2 top-16 z-30 w-[min(92%,460px)] -translate-x-1/2 rounded-xl border border-rose-400/25 bg-rose-950/90 px-4 py-3 text-center text-xs font-medium text-rose-200 shadow-2xl backdrop-blur">
                        {{ errorMessage }}
                    </div>

                    <!-- ===== Pré-salle ===== -->
                    <div v-if="!isConnected" class="absolute inset-0 overflow-y-auto p-4 sm:p-6">
                        <div class="mx-auto flex min-h-full w-full max-w-5xl flex-col items-center justify-center gap-5 lg:flex-row lg:items-center">
                            <!-- Aperçu -->
                            <div class="w-full max-w-2xl lg:flex-1">
                                <div class="relative aspect-video w-full overflow-hidden rounded-2xl border border-white/[0.10] bg-slate-900 shadow-2xl">
                                    <video ref="previewEl" autoplay playsinline muted class="h-full w-full object-cover"></video>
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/10"></div>

                                    <div v-if="!camGranted" class="absolute inset-0 flex items-center justify-center p-6 text-center">
                                        <div class="max-w-xs">
                                            <div class="mx-auto mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.10] bg-white/[0.05]">
                                                <i class="pi pi-video text-2xl text-slate-500"></i>
                                            </div>
                                            <p class="text-xs leading-relaxed text-slate-400">
                                                {{ permissionError || 'Autorisez la caméra pour vous prévisualiser.' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-wide backdrop-blur" :class="micGranted ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/25' : 'bg-slate-950/60 text-slate-400 ring-1 ring-white/10'">
                                            <i :class="micGranted ? 'pi pi-microphone' : 'pi pi-microphone pi-is-off'" class="text-[9px]"></i>
                                            Micro
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-wide backdrop-blur" :class="camGranted ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/25' : 'bg-slate-950/60 text-slate-400 ring-1 ring-white/10'">
                                            <i :class="camGranted ? 'pi pi-video' : 'pi pi-video pi-is-off'" class="text-[9px]"></i>
                                            Caméra
                                        </span>
                                    </div>

                                    <div class="absolute inset-x-3 bottom-3 flex items-end justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Vous allez rejoindre</p>
                                            <p class="mt-0.5 truncate text-sm font-bold text-white">{{ session.title }}</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-950/70 px-2.5 py-1 text-[9px] font-semibold text-slate-300 ring-1 ring-white/10">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-500"></span>
                                            Aperçu
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contrôles pré-salle -->
                            <div class="flex w-full max-w-sm shrink-0 flex-col justify-center lg:w-[330px]">
                                <div class="mb-4">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-red-400">Salle d'attente</p>
                                    <h2 class="mt-1 text-xl font-black tracking-tight text-white">Prêt à rejoindre ?</h2>
                                    <p class="mt-1 break-words text-xs leading-relaxed text-slate-400">{{ session.course?.title || 'Session de classe virtuelle' }}</p>
                                </div>

                                <div class="space-y-2.5">
                                    <!-- Micro -->
                                    <div class="rounded-xl border border-white/[0.08] bg-white/[0.035] p-3">
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                <i class="pi pi-microphone text-[10px]"></i>
                                                Microphone
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase" :class="micGranted ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700/40 text-slate-400'">
                                                <i :class="micGranted ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'" class="text-[9px]"></i>
                                                {{ micGranted ? 'Autorisé' : 'Requis' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <select v-model="selectedAudioId" @change="onAudioDeviceChange" class="h-9 min-w-0 flex-1 rounded-lg border border-white/[0.10] bg-slate-950/70 px-2.5 text-[11px] text-slate-200 outline-none transition-colors [color-scheme:dark] focus:border-red-400/60">
                                                <option v-if="!audioDevices.length" value="">Aucun micro détecté</option>
                                                <option v-for="d in audioDevices" :key="d.deviceId" :value="d.deviceId">{{ d.label || 'Microphone' }}</option>
                                            </select>
                                            <button
                                                type="button"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition-colors focus-visible:outline-2 focus-visible:outline-red-400"
                                                :class="micGranted ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' : 'border-white/[0.10] bg-white/[0.04] text-slate-300 hover:bg-white/[0.10]'"
                                                :aria-label="micGranted ? 'Micro autorisé' : 'Autoriser le micro'"
                                                @click="requestMic"
                                            >
                                                <i :class="requestingMic ? 'pi pi-spin pi-spinner' : micGranted ? 'pi pi-check' : 'pi pi-microphone'" class="text-xs"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Caméra -->
                                    <div class="rounded-xl border border-white/[0.08] bg-white/[0.035] p-3">
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                                <i class="pi pi-video text-[10px]"></i>
                                                Caméra
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase" :class="camGranted ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-700/40 text-slate-400'">
                                                <i :class="camGranted ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'" class="text-[9px]"></i>
                                                {{ camGranted ? 'Autorisée' : 'Requise' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <select v-model="selectedVideoId" @change="onVideoDeviceChange" class="h-9 min-w-0 flex-1 rounded-lg border border-white/[0.10] bg-slate-950/70 px-2.5 text-[11px] text-slate-200 outline-none transition-colors [color-scheme:dark] focus:border-red-400/60">
                                                <option v-if="!videoDevices.length" value="">Aucune caméra détectée</option>
                                                <option v-for="d in videoDevices" :key="d.deviceId" :value="d.deviceId">{{ d.label || 'Caméra' }}</option>
                                            </select>
                                            <button
                                                type="button"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition-colors focus-visible:outline-2 focus-visible:outline-red-400"
                                                :class="camGranted ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' : 'border-white/[0.10] bg-white/[0.04] text-slate-300 hover:bg-white/[0.10]'"
                                                :aria-label="camGranted ? 'Caméra autorisée' : 'Autoriser la caméra'"
                                                @click="requestCamera"
                                            >
                                                <i :class="requestingCamera ? 'pi pi-spin pi-spinner' : camGranted ? 'pi pi-check' : 'pi pi-video'" class="text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="errorMessage" class="mt-3 rounded-xl border border-rose-400/25 bg-rose-500/10 px-3 py-2.5 text-[11px] leading-relaxed text-rose-200">
                                    {{ errorMessage }}
                                </div>
                                <div v-else-if="!canJoin" class="mt-3 flex items-start gap-2 rounded-xl border border-amber-400/25 bg-amber-500/10 px-3 py-2.5 text-[11px] leading-relaxed text-amber-200">
                                    <i class="pi pi-clock mt-0.5 text-[11px]"></i>
                                    <span>Session inactive. Début prévu le {{ sessionStartLabel }}.</span>
                                </div>

                                <button
                                    type="button"
                                    class="mt-3 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-orange-500 text-[11px] font-black uppercase tracking-widest text-white shadow-lg shadow-red-950/40 transition-all hover:brightness-110 focus-visible:outline-2 focus-visible:outline-red-400 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:brightness-100"
                                    :disabled="!canJoin || isConnecting"
                                    @click="connectToRoom"
                                >
                                    <i :class="isConnecting ? 'pi pi-spin pi-spinner' : 'pi pi-video'" class="text-sm"></i>
                                    {{ !canJoin ? 'Session non active' : isConnecting ? 'Connexion…' : 'Rejoindre la salle' }}
                                </button>
                                <p class="mt-2.5 text-center text-[10px] leading-relaxed text-slate-500">
                                    Le micro et la caméra sont demandés séparément par le navigateur.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Partage d'écran ===== -->
                    <div v-else-if="screenShares.length" class="absolute inset-0 flex flex-col gap-2 p-2.5 pb-24">
                        <div v-for="s in screenShares" :key="'screen-' + s.identity" class="relative min-h-0 flex-1 overflow-hidden rounded-xl bg-black ring-1 ring-white/[0.10]">
                            <video :ref="setScreenEl(s.identity)" autoplay playsinline class="h-full w-full object-contain"></video>
                            <div class="absolute bottom-2 left-2 flex items-center gap-1.5 rounded-lg bg-black/70 px-2 py-1 text-[10px] text-white backdrop-blur">
                                <i class="pi pi-desktop text-[9px] text-emerald-400"></i>
                                <span>Partage d'écran — {{ s.name }}<template v-if="s.isLocal"> (vous)</template></span>
                            </div>
                        </div>
                        <div class="flex h-20 shrink-0 gap-2 overflow-x-auto overflow-y-hidden">
                            <div v-for="p in participants" :key="'thumb-' + p.identity" class="relative w-28 shrink-0 overflow-hidden rounded-lg bg-slate-800 ring-1 ring-white/[0.10]">
                                <video :ref="setVideoEl(p.identity)" autoplay playsinline :muted="p.isLocal" class="h-full w-full object-cover"></video>
                                <div class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/80 to-transparent px-1.5 pb-1 pt-4 text-[9px] text-white">
                                    {{ p.name }}<template v-if="p.isLocal"> (vous)</template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Grille participants ===== -->
                    <div v-else class="absolute inset-0 p-2.5 pb-24">
                        <div class="grid h-full min-h-0 w-full gap-2" :class="participants.length <= 1 ? 'grid-cols-1' : participants.length <= 4 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-2 xl:grid-cols-3'">
                            <div v-for="p in participants" :key="p.identity" class="relative min-h-0 overflow-hidden rounded-xl bg-slate-900 ring-1 ring-white/[0.10]">
                                <video :ref="setVideoEl(p.identity)" autoplay playsinline :muted="p.isLocal" class="h-full w-full object-cover"></video>

                                <div v-if="!p.hasVideo" class="absolute inset-0 flex items-center justify-center bg-slate-900">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-red-500/70 to-orange-500/70 text-xl font-black text-white shadow-lg">
                                        {{ (p.name || '?').charAt(0).toUpperCase() }}
                                    </div>
                                </div>

                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-black/80 to-transparent px-2.5 pb-2 pt-6">
                                    <span class="min-w-0 truncate text-[11px] font-semibold text-white">{{ p.name }}<template v-if="p.isLocal"> (vous)</template></span>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <i :class="p.hasAudio ? 'pi pi-microphone text-emerald-400' : 'pi pi-microphone pi-is-off text-rose-400'" class="text-[10px]"></i>
                                        <i :class="p.hasVideo ? 'pi pi-video text-slate-200' : 'pi pi-video pi-is-off text-rose-400'" class="text-[10px]"></i>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!participants.length" class="col-span-full flex h-full min-h-[220px] flex-col items-center justify-center gap-3 text-center">
                                <i class="pi pi-spin pi-spinner text-2xl text-slate-500"></i>
                                <div>
                                    <p class="text-sm font-semibold text-slate-300">Connexion à la salle…</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Les participants apparaîtront ici.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Overlay de connexion ===== -->
                    <div v-if="isConnecting" class="absolute inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-slate-950/90 px-6 text-center backdrop-blur-sm">
                        <div class="relative flex h-16 w-16 items-center justify-center">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500/25"></span>
                            <span class="relative inline-flex h-14 w-14 items-center justify-center rounded-full border border-red-400/30 bg-red-500/10">
                                <i class="pi pi-spin pi-spinner text-xl text-red-300"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Connexion à la salle…</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ connectingStatus }}</p>
                        </div>
                        <p class="max-w-xs text-[10px] leading-relaxed text-slate-500">
                            Autorisez le micro et la caméra si votre navigateur le demande.
                        </p>
                    </div>

                    <!-- ===== Barre de contrôle ===== -->
                    <div v-if="isConnected" class="absolute inset-x-0 bottom-0 z-30 flex flex-col items-center gap-2 bg-gradient-to-t from-black/90 via-black/50 to-transparent px-3 pb-3 pt-12">
                        <button
                            v-if="audioBlocked"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/15 px-3 py-1.5 text-[10px] font-bold text-amber-200 backdrop-blur transition-colors hover:bg-amber-500/25"
                            @click="enableAudio"
                        >
                            <i class="pi pi-volume-up text-[10px]"></i>
                            Activer le son
                        </button>

                        <div class="flex items-center gap-1.5 rounded-2xl border border-white/[0.10] bg-slate-950/85 p-1.5 shadow-2xl backdrop-blur-xl">
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                                :class="isAudioEnabled ? 'bg-white/[0.06] text-slate-200 hover:bg-white/[0.12]' : 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30 hover:bg-rose-500/25'"
                                :aria-label="isAudioEnabled ? 'Couper le micro' : 'Activer le micro'"
                                :aria-pressed="isAudioEnabled"
                                v-tooltip.top="isAudioEnabled ? 'Couper le micro' : 'Activer le micro'"
                                @click="toggleAudio"
                            >
                                <i :class="isAudioEnabled ? 'pi pi-microphone' : 'pi pi-microphone pi-is-off'" class="text-sm"></i>
                            </button>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                                :class="isVideoEnabled ? 'bg-white/[0.06] text-slate-200 hover:bg-white/[0.12]' : 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30 hover:bg-rose-500/25'"
                                :aria-label="isVideoEnabled ? 'Couper la caméra' : 'Activer la caméra'"
                                :aria-pressed="isVideoEnabled"
                                v-tooltip.top="isVideoEnabled ? 'Couper la caméra' : 'Activer la caméra'"
                                @click="toggleVideo"
                            >
                                <i :class="isVideoEnabled ? 'pi pi-video' : 'pi pi-video pi-is-off'" class="text-sm"></i>
                            </button>
                            <span class="mx-0.5 h-6 w-px bg-white/[0.08]"></span>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                                :class="isScreenSharing ? 'bg-blue-500/20 text-blue-300 ring-1 ring-blue-400/30 hover:bg-blue-500/30' : 'bg-white/[0.06] text-slate-200 hover:bg-white/[0.12]'"
                                :aria-label="isScreenSharing ? 'Arrêter le partage' : 'Partager l\'écran'"
                                :aria-pressed="isScreenSharing"
                                v-tooltip.top="isScreenSharing ? 'Arrêter le partage' : 'Partager l\'écran'"
                                @click="toggleScreenShare"
                            >
                                <i :class="isScreenSharing ? 'pi pi-desktop' : 'pi pi-clone'" class="text-sm"></i>
                            </button>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/[0.06] text-slate-200 transition-all hover:bg-white/[0.12] focus-visible:outline-2 focus-visible:outline-red-400"
                                :aria-label="isFullscreen ? 'Quitter le plein écran' : 'Plein écran'"
                                v-tooltip.top="isFullscreen ? 'Quitter le plein écran' : 'Plein écran'"
                                @click="toggleFullscreen"
                            >
                                <i :class="isFullscreen ? 'pi pi-window-minimize' : 'pi pi-window-maximize'" class="text-sm"></i>
                            </button>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all focus-visible:outline-2 focus-visible:outline-red-400"
                                :class="showChat ? 'bg-blue-500/20 text-blue-300 ring-1 ring-blue-400/30 hover:bg-blue-500/30' : 'bg-white/[0.06] text-slate-200 hover:bg-white/[0.12]'"
                                :aria-label="showChat ? 'Masquer le chat' : 'Afficher le chat'"
                                :aria-pressed="showChat"
                                v-tooltip.top="showChat ? 'Masquer le chat' : 'Afficher le chat'"
                                @click="showChat = !showChat"
                            >
                                <i class="pi pi-comments text-sm"></i>
                            </button>
                            <span class="mx-0.5 h-6 w-px bg-white/[0.08]"></span>
                            <button
                                type="button"
                                class="flex h-10 items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 text-[10px] font-bold uppercase tracking-wider text-white transition-colors hover:bg-rose-500 focus-visible:outline-2 focus-visible:outline-red-400"
                                aria-label="Quitter la session"
                                v-tooltip.top="'Quitter la session'"
                                @click="leaveRoom"
                            >
                                <i class="pi pi-sign-out text-sm"></i>
                                <span class="hidden sm:inline">Quitter</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Chat en direct ===== -->
        <aside v-if="showChat" class="absolute inset-0 z-40 flex min-h-0 flex-col border-l border-white/[0.08] bg-slate-900 md:static md:z-auto md:w-[340px] md:shrink-0 md:bg-slate-900/60 md:backdrop-blur-xl">
            <div class="flex h-14 shrink-0 items-center justify-between border-b border-white/[0.07] px-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/15 text-blue-300 ring-1 ring-blue-400/20">
                        <i class="pi pi-comments text-[11px]"></i>
                    </span>
                    <div>
                        <h2 class="text-[12px] font-bold text-white">Chat en direct</h2>
                        <p class="text-[9px] font-medium uppercase tracking-widest text-slate-500">{{ displayedMessages.length }} message{{ displayedMessages.length > 1 ? 's' : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="chatRefreshing"
                        @click="reloadChat"
                        aria-label="Recharger les messages"
                        v-tooltip.top="'Recharger les messages'"
                    >
                        <i :class="chatRefreshing ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" class="text-xs"></i>
                    </button>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white/[0.06] hover:text-white" @click="showChat = false" aria-label="Fermer le chat">
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>
            </div>

            <div ref="chatScrollEl" class="min-h-0 flex-1 space-y-2.5 overflow-y-auto px-3 py-3">
                <div v-for="msg in displayedMessages" :key="msg.id" class="flex flex-col" :class="msg.mine ? 'items-end' : 'items-start'">
                    <div
                        class="max-w-[88%] rounded-2xl px-3 py-2 transition-opacity"
                        :class="[
                            msg.mine ? 'rounded-br-md' : 'rounded-bl-md',
                            msg.failed ? 'bg-rose-500/15 text-rose-100 ring-1 ring-rose-400/30' : msg.mine ? 'bg-blue-600 text-white' : 'bg-white/[0.06] text-slate-200 ring-1 ring-white/[0.05]',
                            msg.pending ? 'opacity-80' : '',
                        ]"
                    >
                        <p v-if="!msg.mine" class="mb-0.5 text-[10px] font-bold text-slate-400">{{ msg.sender }}</p>

                        <a v-if="msg.file" :href="msg.file.url" target="_blank" rel="noopener" class="mt-1 flex items-center gap-2 rounded-lg border border-white/[0.10] bg-slate-950/40 p-2 transition-colors hover:border-blue-400/50">
                            <i class="pi pi-file text-xs text-blue-300"></i>
                            <span class="min-w-0 flex-1 truncate text-[10px] text-slate-200">{{ msg.file.name }}</span>
                            <span class="shrink-0 text-[9px] text-slate-500">{{ formatBytes(msg.file.size) }}</span>
                            <i class="pi pi-download text-[10px] text-slate-400"></i>
                        </a>

                        <p v-else class="break-words text-[12px] leading-relaxed">{{ msg.message }}</p>
                    </div>
                    <span class="mt-1 flex items-center gap-1.5 px-1 text-[9px] text-slate-500">
                        <template v-if="msg.pending">
                            <i class="pi pi-spin pi-spinner text-[9px] text-blue-300"></i>
                            <span class="text-blue-300">Envoi…</span>
                        </template>
                        <template v-else-if="msg.failed">
                            <i class="pi pi-exclamation-circle text-[9px] text-rose-400"></i>
                            <span class="text-rose-300">Non envoyé</span>
                            <button type="button" class="font-semibold text-blue-300 underline-offset-2 hover:underline" @click="retryChatMessage(msg)">Réessayer</button>
                        </template>
                        <template v-else>{{ msg.timestamp }}</template>
                    </span>
                </div>

                <div v-if="displayedMessages.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl border border-white/[0.08] bg-white/[0.03]">
                        <i class="pi pi-comment text-lg text-slate-600"></i>
                    </div>
                    <p class="text-xs font-semibold text-slate-400">Aucun message pour le moment.</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-600">Posez votre première question à l'instructeur.</p>
                </div>
            </div>

            <div class="relative shrink-0 border-t border-white/[0.07] p-3">
                <div v-if="showEmojis" class="absolute bottom-full left-3 right-3 z-10 mb-2 grid grid-cols-8 gap-1 rounded-xl border border-white/[0.10] bg-slate-900 p-2 shadow-2xl">
                    <button v-for="e in emojis" :key="e" type="button" class="rounded-lg p-1 text-lg transition-colors hover:bg-white/[0.08]" @click="appendEmoji(e)">{{ e }}</button>
                </div>

                <input ref="fileInput" type="file" class="hidden" @change="uploadDocument" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.png,.jpg,.jpeg,.webp,.gif,.mp4" />

                <div class="flex items-center gap-1.5">
                    <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.03] text-slate-400 transition-colors hover:bg-white/[0.08] hover:text-white" aria-label="Partager un document" v-tooltip.top="'Partager un document'" @click="fileInput?.click()">
                        <i class="pi pi-paperclip text-xs"></i>
                    </button>
                    <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.03] text-slate-400 transition-colors hover:bg-white/[0.08] hover:text-white" aria-label="Insérer un émoji" v-tooltip.top="'Émojis'" @click="showEmojis = !showEmojis">
                        <span aria-hidden="true" class="text-sm leading-none">🙂</span>
                    </button>
                    <input
                        v-model="chatInput"
                        type="text"
                        placeholder="Écrivez un message…"
                        class="h-9 min-w-0 flex-1 rounded-lg border border-white/[0.08] bg-white/[0.05] px-3 text-[11px] text-slate-100 outline-none transition-colors placeholder:text-slate-500 focus:border-blue-400/60"
                        @keyup.enter="sendChatMessage"
                        @focus="showEmojis = false"
                    />
                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition-all hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-2 focus-visible:outline-blue-400"
                        :disabled="!chatInput.trim() || chatSending"
                        :aria-label="chatSending ? 'Envoi en cours' : 'Envoyer le message'"
                        v-tooltip.top="chatSending ? 'Envoi en cours…' : 'Envoyer'"
                        @click="sendChatMessage"
                    >
                        <i :class="chatSending ? 'pi pi-spin pi-spinner' : 'pi pi-send'" class="text-xs"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Confirmation des informations en fin de session -->
        <Dialog v-model:visible="showConfirm" modal header="Confirmez vos informations" :style="{ width: '440px', maxWidth: '95vw' }">
            <div class="space-y-3 pt-2">
                <p class="text-sm text-slate-600">Merci d'avoir suivi cette session. Confirmez vos informations pour votre attestation de présence.</p>
                <div>
                    <label class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">Nom complet</label>
                    <input v-model="confirmName" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-500" />
                </div>
                <div>
                    <label class="mb-1 block text-[10px] font-black uppercase tracking-widest text-slate-400">E-mail</label>
                    <input v-model="confirmEmail" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-500" />
                </div>
            </div>
            <template #footer>
                <div class="flex w-full justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="h-9 rounded-lg px-3 text-xs font-semibold text-slate-500 hover:bg-slate-100" @click="skipConfirmation">Plus tard</button>
                    <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg bg-blue-600 px-3 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50" :disabled="confirmSubmitting" @click="submitConfirmation">
                        <i :class="confirmSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-check'" class="text-[11px]"></i>
                        Confirmer
                    </button>
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
