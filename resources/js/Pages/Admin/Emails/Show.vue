<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import { ref, computed } from 'vue';

const props = defineProps({
    email: { type: Object, required: true },
    folders: { type: Array, default: () => [] },
    messageNumber: { type: [Number, String], required: true },
});

// --- Helpers pour l'expéditeur et les initiales ---
const getFromName = (from) => {
    if (!from || !from.length) return 'Inconnu';
    return from[0].name || from[0].email;
};

const getFromEmail = (from) => {
    if (!from || !from.length) return '';
    return from[0].email || '';
};

const getInitials = (name) => {
    if (!name || name === 'Inconnu') return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
};

const getAvatarColor = (name) => {
    const colors = [
        'bg-rose-100 text-rose-700', 'bg-blue-100 text-blue-700',
        'bg-green-100 text-green-700', 'bg-purple-100 text-purple-700',
        'bg-amber-100 text-amber-700', 'bg-teal-100 text-teal-700'
    ];
    if (!name) return colors[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
};

// --- Formulaire de réponse (façon Gmail) ---
const showReplyForm = ref(false);
const showCcBcc = ref(false); // Bascule pour afficher/masquer CC et CCI
const fileInput = ref(null);

const replyForm = useForm({
    to: getFromEmail(props.email.from),
    cc: '',
    bcc: '',
    reply_message: '',
    attachments: [], // Tableau pour stocker les nouvelles pièces jointes
});

// Gestion des pièces jointes à envoyer
const triggerFileInput = () => fileInput.value.click();

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    replyForm.attachments = [...replyForm.attachments, ...files];
    // Réinitialiser l'input pour pouvoir resélectionner le même fichier si on l'a supprimé
    event.target.value = '';
};

const removeAttachment = (index) => {
    replyForm.attachments.splice(index, 1);
};

const submitReply = () => {
    // Inertia gère automatiquement le passage en FormData si des fichiers sont présents
    replyForm.post(route('admin.emails.reply', props.messageNumber), {
        preserveScroll: true,
        onSuccess: () => {
            showReplyForm.value = false;
            replyForm.reset();
        },
    });
};

// --- Actions de l'email ---
const markAsFlagged = () => {
    router.post(route('admin.emails.flag', props.messageNumber), {}, {
        preserveScroll: true,
        onSuccess: () => props.email.flagged = true
    });
};

const unflag = () => {
    router.post(route('admin.emails.unflag', props.messageNumber), {}, {
        preserveScroll: true,
        onSuccess: () => props.email.flagged = false
    });
};

const moveToTrash = () => {
    if (confirm('Déplacer cet email vers la corbeille ?')) {
        router.post(route('admin.emails.trash', props.messageNumber));
    }
};

// --- Formatage ---
const formatDate = (iso) => {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString('fr-FR', {
            weekday: 'short', day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    } catch (e) { return iso; }
};

const formatSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / 1048576).toFixed(1) + ' Mo';
};

const getAddressList = (addresses) => {
    if (!addresses || !addresses.length) return '';
    return addresses.map(a => a.name ? `${a.name} <${a.email}>` : a.email).join(', ');
};

const toDisplay = computed(() => getAddressList(props.email.to));
const ccDisplay = computed(() => getAddressList(props.email.cc));

const cleanBody = computed(() => {
    return props.email.body || props.email.html_body || props.email.text_body || '';
});

const hasHtml = computed(() => {
    return props.email.html_body && props.email.html_body.length > 0;
});
</script>

<template>
    <Head :title="email.subject || 'Email'" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header avec bandeau foncé -->
            <div class="bg-[#0f172a] pt-8 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[100px]"></div>
                <div class="max-w-[1200px] mx-auto relative z-10">
                    <Link :href="route('admin.emails.index')" class="inline-flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors bg-slate-800/50 px-4 py-2 rounded-full backdrop-blur-sm">
                        <i class="pi pi-arrow-left text-xs"></i> Retour à la boîte de réception
                    </Link>

                    <div class="flex flex-col md:flex-row items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <h1 class="text-3xl font-black text-white tracking-tight leading-tight">
                                {{ email.subject || '(sans objet)' }}
                            </h1>
                            <div class="flex items-center gap-3 mt-4 flex-wrap">
                                <Tag v-if="email.flagged" value="Important" class="!bg-amber-500/20 !text-amber-400 !border !border-amber-500/30 !rounded-full !px-3" />
                                <Tag v-if="email.has_attachments" icon="pi pi-paperclip" value="Pièces jointes" class="!bg-slate-700/50 !text-slate-300 !border !border-slate-600/50 !rounded-full !px-3" />
                            </div>
                        </div>

                        <!-- Actions globales -->
                        <div class="flex items-center gap-2 flex-shrink-0 bg-slate-800/50 p-1.5 rounded-2xl backdrop-blur-sm border border-slate-700/50">
                            <Button v-if="email.flagged" icon="pi pi-star-fill" class="!text-amber-400 hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="unflag" v-tooltip.top="'Retirer le marqueur'" />
                            <Button v-else icon="pi pi-star" class="!text-slate-400 hover:!text-white hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="markAsFlagged" v-tooltip.top="'Marquer comme important'" />
                            <div class="w-px h-6 bg-slate-700 mx-1"></div>
                            <Button icon="pi pi-reply" class="!text-slate-400 hover:!text-white hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="showReplyForm = true" v-tooltip.top="'Répondre'" />
                            <Button icon="pi pi-trash" class="!text-rose-400 hover:!bg-rose-500/20 !w-10 !h-10 !p-0" text rounded @click="moveToTrash" v-tooltip.top="'Supprimer'" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1200px] mx-auto px-4 lg:px-6 -mt-12 relative z-20 space-y-6">

                <!-- Corps de l'email (En-tête + Contenu) -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">

                    <!-- Méta de l'expéditeur -->
                    <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col md:flex-row gap-6 justify-between items-start bg-slate-50/50">
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shadow-sm flex-shrink-0', getAvatarColor(getFromName(email.from))]">
                                {{ getInitials(getFromName(email.from)) }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-end gap-2 mb-1">
                                    <h3 class="text-lg font-bold text-slate-900 truncate">{{ getFromName(email.from) }}</h3>
                                    <span class="text-sm text-slate-500 mb-0.5">&lt;{{ getFromEmail(email.from) }}&gt;</span>
                                </div>
                                <div class="text-sm text-slate-600 space-y-0.5">
                                    <p><span class="font-medium text-slate-400 w-8 inline-block">À :</span> {{ toDisplay || 'Moi' }}</p>
                                    <p v-if="ccDisplay"><span class="font-medium text-slate-400 w-8 inline-block">Cc :</span> {{ ccDisplay }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700">{{ formatDate(email.date) }}</p>
                            <p class="text-xs text-slate-400 mt-1 font-mono">Taille : {{ formatSize(email.size) }}</p>
                        </div>
                    </div>

                    <!-- Pièces jointes reçues -->
                    <div v-if="email.has_attachments && email.attachments?.length" class="px-6 md:px-8 py-4 border-b border-slate-100 bg-slate-50/30">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-3 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> {{ email.attachments.length }} Pièce(s) jointe(s)
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="(attachment, index) in email.attachments" :key="index"
                                class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer group min-w-[200px] max-w-[300px]">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-indigo-50 transition-colors">
                                    <i :class="[
                                        'pi text-xl',
                                        attachment.subtype === 'PDF' ? 'pi-file-pdf text-rose-500' :
                                        attachment.subtype === 'JPEG' || attachment.subtype === 'PNG' ? 'pi-image text-emerald-500' :
                                        'pi-file text-slate-500'
                                    ]"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-700 truncate group-hover:text-indigo-600 transition-colors">{{ attachment.filename }}</p>
                                    <p class="text-xs text-slate-400">{{ formatSize(attachment.size) }}</p>
                                </div>
                                <!-- Icone de téléchargement au survol -->
                                <i class="pi pi-download text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu du message -->
                    <div class="p-6 md:p-8 min-h-[200px]">
                        <div v-if="hasHtml" class="email-content prose max-w-none text-slate-800" v-html="email.html_body"></div>
                        <pre v-else class="whitespace-pre-wrap font-sans text-[15px] leading-relaxed text-slate-800">{{ cleanBody }}</pre>
                    </div>
                </div>

                <!-- Bouton d'action rapide (si formulaire masqué) -->
                <div v-if="!showReplyForm" class="flex items-center gap-3">
                    <Button
                        label="Répondre"
                        icon="pi pi-reply"
                        @click="showReplyForm = true"
                        class="!bg-white !text-slate-700 !border-slate-200 hover:!bg-slate-50 hover:!border-slate-300 !rounded-full shadow-sm px-6"
                    />
                    <Button
                        label="Transférer"
                        icon="pi pi-arrow-right"
                        outlined
                        class="!bg-white !text-slate-700 !border-slate-200 hover:!bg-slate-50 hover:!border-slate-300 !rounded-full shadow-sm px-6"
                    />
                </div>

                <!-- Formulaire de réponse enrichi -->
                <div v-if="showReplyForm" id="replyForm" class="bg-white rounded-3xl shadow-lg border border-indigo-100 overflow-hidden transform transition-all">
                    <!-- Header formulaire -->
                    <div class="bg-indigo-50/50 px-6 py-4 border-b border-indigo-100 flex items-center justify-between">
                        <h3 class="text-indigo-900 font-bold flex items-center gap-2">
                            <i class="pi pi-reply"></i> Répondre
                        </h3>
                        <button @click="showReplyForm = false" class="text-slate-400 hover:text-slate-600">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitReply" class="flex flex-col">
                        <!-- Destinataires & CC/CCI -->
                        <div class="px-6 py-2 border-b border-slate-100">
                            <div class="flex items-center py-2 text-sm">
                                <span class="w-12 text-slate-400 font-medium">À :</span>
                                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full font-medium">{{ replyForm.to }}</span>
                                <button type="button" @click="showCcBcc = !showCcBcc" class="ml-auto text-xs text-indigo-600 hover:underline font-medium">
                                    {{ showCcBcc ? 'Masquer Cc/Cci' : 'Ajouter Cc/Cci' }}
                                </button>
                            </div>

                            <div v-show="showCcBcc" class="space-y-2 py-2 border-t border-slate-100 mt-2">
                                <div class="flex items-center">
                                    <span class="w-12 text-slate-400 font-medium text-sm">Cc :</span>
                                    <InputText v-model="replyForm.cc" class="flex-1 !border-none !shadow-none focus:!ring-0 !p-0 text-sm" placeholder="Ex: collaborateur@domaine.com" />
                                </div>
                                <div class="flex items-center">
                                    <span class="w-12 text-slate-400 font-medium text-sm">Cci :</span>
                                    <InputText v-model="replyForm.bcc" class="flex-1 !border-none !shadow-none focus:!ring-0 !p-0 text-sm" placeholder="Ex: discret@domaine.com" />
                                </div>
                            </div>
                        </div>

                        <!-- Zone de texte -->
                        <div class="px-6 py-4">
                            <Textarea
                                v-model="replyForm.reply_message"
                                rows="8"
                                class="w-full !border-none !shadow-none focus:!ring-0 !p-0 text-[15px] text-slate-800 resize-none bg-transparent"
                                placeholder="Écrivez votre réponse ici..."
                            />
                            <p v-if="replyForm.errors.reply_message" class="text-rose-500 text-xs mt-2">{{ replyForm.errors.reply_message }}</p>
                        </div>

                        <!-- Fichiers joints en attente -->
                        <div v-if="replyForm.attachments.length" class="px-6 pb-4 flex flex-wrap gap-2">
                            <div v-for="(file, index) in replyForm.attachments" :key="index"
                                 class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-lg text-sm border border-slate-200">
                                <i class="pi pi-file text-slate-500"></i>
                                <span class="truncate max-w-[150px] font-medium text-slate-700">{{ file.name }}</span>
                                <span class="text-xs text-slate-400">({{ formatSize(file.size) }})</span>
                                <button type="button" @click="removeAttachment(index)" class="ml-1 text-slate-400 hover:text-rose-500">
                                    <i class="pi pi-times-circle text-base"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Barre d'outils bas (Boutons Envoyer et Joindre) -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between rounded-b-3xl">
                            <!-- Input de fichier caché -->
                            <input type="file" multiple ref="fileInput" class="hidden" @change="handleFileSelect" />

                            <Button type="button" icon="pi pi-paperclip" text rounded class="!text-slate-500 hover:!bg-slate-200" @click="triggerFileInput" v-tooltip.top="'Joindre des fichiers'" />

                            <div class="flex items-center gap-3">
                                <Button type="button" label="Annuler" text @click="showReplyForm = false" class="!text-slate-500 font-medium" />
                                <Button type="submit" label="Envoyer" icon="pi pi-send" :loading="replyForm.processing" class="!bg-indigo-600 hover:!bg-indigo-700 !border-none !rounded-full px-6 shadow-md shadow-indigo-200" />
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styling de base pour l'email au format HTML rendu proprement */
.email-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1rem 0;
}
.email-content :deep(a) {
    color: #4f46e5; /* indigo-600 */
    text-decoration: underline;
}
.email-content :deep(blockquote) {
    border-left: 4px solid #cbd5e1;
    padding-left: 1rem;
    margin-left: 0;
    color: #475569;
    font-style: italic;
}
.email-content :deep(p) {
    margin-bottom: 1em;
}
</style>
