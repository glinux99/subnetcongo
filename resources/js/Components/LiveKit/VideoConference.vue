<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Room, RoomEvent, LocalVideoTrack, LocalAudioTrack, createLocalScreenTracks } from 'livekit-client';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const page = usePage();

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
});

const toast = useToast();

let echoChannel = null;

const room = ref(null);
const isConnected = ref(false);
const isConnecting = ref(false);
const errorMessage = ref('');
const participants = ref([]);
const localTracks = ref({ audio: null, video: null });
const isAudioEnabled = ref(true);
const isVideoEnabled = ref(true);
const isScreenSharing = ref(false);
const showChat = ref(false);
const chatMessages = ref([]);
const chatInput = ref('');
const wsUrl = ref('');
const accessToken = ref('');
const isInstructor = ref(false);
const currentUser = ref(null);

let heartbeatInterval = null;

const canJoin = computed(() => {
    const now = new Date();
    const startAt = new Date(props.session.start_at);
    const endAt = new Date(props.session.ended_at || new Date(startAt.getTime() + (props.session.duration || 120) * 60000));
    return now >= startAt && now <= endAt;
});

const fetchToken = async () => {
    try {
        const response = await fetch(route('live-sessions.livekit-token', { live_session: props.session.id }), {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Impossible de récupérer le token d\'accès.');
        }

        const data = await response.json();
        accessToken.value = data.token;
        isInstructor.value = data.is_instructor;
        currentUser.value = data.user;
        wsUrl.value = (data.ws_url || 'ws://localhost:7880').replace(/\/+$/, '');
    } catch (error) {
        errorMessage.value = error.message;
        toast.add({ severity: 'error', summary: 'Erreur', detail: error.message, life: 5000 });
    }
};

const connectToRoom = async () => {
    if (!canJoin.value) {
        errorMessage.value = 'Cette session n\'est pas active actuellement.';
        return;
    }

    isConnecting.value = true;
    errorMessage.value = '';

    try {
        await fetchToken();

        let livekitUrl = (wsUrl.value || '').trim();
        if (!livekitUrl) {
            livekitUrl = 'ws://localhost:7880';
        }
        if (!livekitUrl.startsWith('ws://') && !livekitUrl.startsWith('wss://')) {
            livekitUrl = 'ws://' + livekitUrl;
        }
        livekitUrl = livekitUrl.replace(/\/+$/, '');

        console.log('[LiveKit] Connecting to:', livekitUrl);

        room.value = new Room({
            adaptiveStream: true,
            dynacast: true,
        });

        room.value.on(RoomEvent.Connected, () => {
            isConnected.value = true;
            isConnecting.value = false;
            toast.add({ severity: 'success', summary: 'Connecté', detail: 'Vous êtes dans la session LiveKit.', life: 3000 });
            updateParticipantsList();
        });

        room.value.on(RoomEvent.Disconnected, () => {
            isConnected.value = false;
            isConnecting.value = false;
            toast.add({ severity: 'info', summary: 'Déconnecté', detail: 'Vous avez quitté la session.', life: 3000 });
        });

        room.value.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
            const videoEl = document.getElementById(`video-${participant.identity}`);
            if (videoEl) track.attach(videoEl);
            updateParticipantsList();
        });

        room.value.on(RoomEvent.TrackUnsubscribed, (track) => {
            track.detach();
            updateParticipantsList();
        });

        room.value.on(RoomEvent.ParticipantConnected, (participant) => {
            updateParticipantsList();
            toast.add({ severity: 'info', summary: 'Participant', detail: `${participant.identity} a rejoint.`, life: 3000 });
        });

        room.value.on(RoomEvent.ParticipantDisconnected, (participant) => {
            updateParticipantsList();
            toast.add({ severity: 'warn', summary: 'Participant', detail: `${participant.identity} a quitté.`, life: 3000 });
        });

        await room.value.connect(livekitUrl, accessToken.value);

        if (isVideoEnabled.value) {
            const videoTrack = await LocalVideoTrack.create();
            await room.value.localParticipant.publishTrack(videoTrack);
            localTracks.value.video = videoTrack;
            const localVideoEl = document.getElementById('local-video');
            if (localVideoEl) videoTrack.attach(localVideoEl);
        }

        if (isAudioEnabled.value) {
            const audioTrack = await LocalAudioTrack.create();
            await room.value.localParticipant.publishTrack(audioTrack);
            localTracks.value.audio = audioTrack;
        }

        await room.value.localParticipant.setCameraEnabled(isVideoEnabled.value);
        await room.value.localParticipant.setMicrophoneEnabled(isAudioEnabled.value);

    } catch (error) {
        console.error('LiveKit connection error:', error);
        isConnecting.value = false;
        errorMessage.value = error.message || 'Erreur de connexion au serveur LiveKit.';
        toast.add({ severity: 'error', summary: 'Erreur', detail: errorMessage.value, life: 5000 });
    }
};

const disconnectFromRoom = async () => {
    if (room.value) {
        await room.value.disconnect();
        room.value = null;
        isConnected.value = false;
        participants.value = [];
    }
};

const toggleAudio = async () => {
    if (!room.value) return;
    isAudioEnabled.value = !isAudioEnabled.value;
    await room.value.localParticipant.setMicrophoneEnabled(isAudioEnabled.value);
};

const toggleVideo = async () => {
    if (!room.value) return;
    isVideoEnabled.value = !isVideoEnabled.value;
    await room.value.localParticipant.setCameraEnabled(isVideoEnabled.value);
};

const toggleScreenShare = async () => {
    if (!room.value) return;

    try {
        if (!isScreenSharing.value) {
            const screenTracks = await createLocalScreenTracks();
            const screenTrack = screenTracks.videoTrack;
            await room.value.localParticipant.publishTrack(screenTrack);
            localTracks.value.screen = screenTrack;
            isScreenSharing.value = true;
        } else {
            if (localTracks.value.screen) {
                await room.value.localParticipant.unpublishTrack(localTracks.value.screen);
                localTracks.value.screen.stop();
                localTracks.value.screen = null;
            }
            isScreenSharing.value = false;
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de partager l\'écran.', life: 4000 });
    }
};

const sendChatMessage = async () => {
    if (!chatInput.value.trim()) return;

    const message = {
        session_id: props.session.id,
        user_id: currentUser.value?.id,
        user_name: currentUser.value?.name || 'Utilisateur',
        message: chatInput.value.trim(),
        timestamp: new Date().toLocaleTimeString(),
    };

    try {
        await fetch(route('live-sessions.chat.send', { live_session: props.session.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: message.message }),
        });

        chatInput.value = '';
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d\'envoyer le message.', life: 4000 });
    }
};

const updateParticipantsList = () => {
    if (!room.value) return;
    participants.value = Array.from(room.value.participants.values()).map(p => ({
        identity: p.identity,
        isLocal: p.isLocal,
        audioTrack: p.audioTrackPublications?.size > 0,
        videoTrack: p.videoTrackPublications?.size > 0,
    }));
};

const leaveRoom = async () => {
    await disconnectFromRoom();
    window.location.href = route('live-sessions.index');
};

const sendHeartbeat = async () => {
    if (!isConnected.value) return;
    try {
        await fetch(route('live-sessions.heartbeat', { live_session: props.session.id }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });
    } catch (error) {
        console.error('Heartbeat failed:', error);
    }
};

onMounted(async () => {
    if (props.session.provider !== 'livekit') {
        errorMessage.value = 'Cette session n\'utilise pas LiveKit. Redirection...';
        setTimeout(() => {
            const sessionId = props.session?.id || props.session?.live_session_id;
            if (sessionId) {
                window.location.href = route('live-sessions.join', { live_session: sessionId });
            } else {
                window.location.href = route('live-sessions.index');
            }
        }, 2000);
        return;
    }

    if (canJoin.value) {
        await connectToRoom();
        heartbeatInterval = setInterval(sendHeartbeat, 60000);

        echoChannel = echo.private(`live-session.${props.session.id}.chat`);
        echoChannel.listen('.chat.message', (e) => {
            chatMessages.value.push({
                id: e.id || Date.now() + Math.random(),
                sender: e.user_name,
                message: e.message,
                timestamp: e.timestamp || new Date().toLocaleTimeString(),
            });
        });
    } else {
        errorMessage.value = 'Cette session n\'est pas active actuellement.';
    }
});

onUnmounted(() => {
    if (heartbeatInterval) clearInterval(heartbeatInterval);
    if (echoChannel) {
        echoChannel.stopListening('.chat.message');
        echo.leave(`live-session.${props.session.id}.chat`);
    }
    disconnectFromRoom();
});
</script>

<template>
    <div class="livekit-video-conference">
        <Toast />

        <div class="max-w-[1600px] mx-auto p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <Tag value="LiveKit" severity="danger" class="animate-pulse" />
                        <span v-if="isInstructor" class="text-xs text-slate-500 font-semibold">Mode Instructeur</span>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tighter">{{ session.title }}</h1>
                    <p class="text-slate-500 mt-1">{{ session.course?.title || 'Session en direct' }}</p>
                </div>
                <div class="flex gap-3">
                    <Button v-if="!isConnected" label="Rejoindre" icon="pi pi-sign-in" class="p-button-primary" @click="connectToRoom" :loading="isConnecting" :disabled="!canJoin" />
                    <Button v-else label="Quitter" icon="pi pi-sign-out" class="p-button-danger" @click="leaveRoom" />
                </div>
            </div>

            <div v-if="errorMessage" class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 font-medium">
                {{ errorMessage }}
            </div>

            <div v-if="!canJoin && !errorMessage" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-700 font-medium">
                Cette session n'est pas active actuellement. Elle débutera à {{ new Date(session.start_at).toLocaleString('fr-FR') }}.
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                <div class="xl:col-span-3 space-y-6">
                    <Card class="border-none shadow-sm rounded-[1.5rem] overflow-hidden bg-slate-900">
                        <template #content>
                            <div v-if="!isConnected" class="aspect-video bg-slate-800 rounded-xl flex items-center justify-center">
                                <div class="text-center">
                                    <i class="pi pi-camera text-6xl text-slate-600 mb-4"></i>
                                    <p class="text-slate-400 text-lg font-semibold">Cliquez sur "Rejoindre" pour démarrer la session vidéo</p>
                                    <p class="text-slate-500 text-sm mt-2">Votre caméra et microphone seront activés automatiquement</p>
                                </div>
                            </div>
                            <div v-else class="aspect-video bg-slate-900 rounded-xl overflow-hidden relative">
                                <div class="grid grid-cols-2 gap-2 p-2 h-full">
                                    <div class="relative bg-slate-800 rounded-lg overflow-hidden">
                                        <video id="local-video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                        <div class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded">{{ currentUser?.name || 'Moi' }}</div>
                                    </div>
                                    <div v-for="participant in participants" :key="participant.identity" class="relative bg-slate-800 rounded-lg overflow-hidden">
                                        <video :id="`video-${participant.identity}`" autoplay playsinline class="w-full h-full object-cover"></video>
                                        <div class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded">{{ participant.identity }}</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <div v-if="isConnected" class="flex gap-3">
                        <Button :icon="`pi pi-${isAudioEnabled ? 'microphone' : 'microphone-slash'}`" class="p-button-outlined flex-1" @click="toggleAudio" :severity="isAudioEnabled ? 'secondary' : 'danger'" />
                        <Button :icon="`pi pi-${isVideoEnabled ? 'video' : 'video-slash'}`" class="p-button-outlined flex-1" @click="toggleVideo" :severity="isVideoEnabled ? 'secondary' : 'danger'" />
                        <Button :icon="`pi pi-${isScreenSharing ? 'desktop' : 'clone'}`" class="p-button-outlined flex-1" @click="toggleScreenShare" :severity="isScreenSharing ? 'primary' : 'secondary'" />
                        <Button icon="pi pi-comments" class="p-button-outlined flex-1" @click="showChat = !showChat" :severity="showChat ? 'primary' : 'secondary'" />
                    </div>
                </div>

                <div class="space-y-6">
                    <Card>
                        <template #title>Participants ({{ participants.length + 1 }})</template>
                        <template #content>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <i class="pi pi-user text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ currentUser?.name || 'Moi' }} (vous)</p>
                                        <p class="text-xs text-slate-500">Instructeur</p>
                                    </div>
                                </div>
                                <div v-for="participant in participants" :key="participant.identity" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                        <i class="pi pi-user text-slate-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ participant.identity }}</p>
                                        <div class="flex gap-2 mt-1">
                                            <i v-if="participant.audioTrack" class="pi pi-microphone text-xs text-emerald-500"></i>
                                            <i v-if="participant.videoTrack" class="pi pi-video text-xs text-indigo-500"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <Dialog v-model:visible="showChat" header="Chat en direct" :style="{ width: '400px' }" class="p-fluid">
                <div class="h-[400px] overflow-auto space-y-3 mb-4">
                    <div v-for="msg in chatMessages" :key="msg.id" class="p-3 bg-slate-50 rounded-xl">
                        <p class="text-xs font-semibold text-slate-700">{{ msg.sender }}</p>
                        <p class="text-sm text-slate-600">{{ msg.message }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ msg.timestamp }}</p>
                    </div>
                    <div v-if="chatMessages.length === 0" class="text-center text-slate-400 py-8">
                        <p>Aucun message pour le moment.</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <InputText v-model="chatInput" placeholder="Écrivez un message..." class="flex-1" @keyup.enter="sendChatMessage" />
                    <Button label="Envoyer" icon="pi pi-send" @click="sendChatMessage" />
                </div>
            </Dialog>
        </div>
    </div>
</template>

<style scoped>
.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.video-tile {
    position: relative;
    aspect-ratio: 16/9;
    background: #1e293b;
    border-radius: 0.75rem;
    overflow: hidden;
}

.video-tile video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.participant-label {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>
