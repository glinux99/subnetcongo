<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
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
        'bg-red-100 text-red-700', 'bg-blue-100 text-blue-700',
        'bg-blue-100 text-blue-700', 'bg-blue-100 text-blue-700',
        'bg-red-100 text-red-700', 'bg-cyan-100 text-cyan-700'
    ];
    if (!name) return colors[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
};

// --- Formulaire de réponse (façon Gmail) ---
const showReplyForm = ref(false);
const showCcBcc = ref(false); 
const fileInput = ref(null);

const replyForm = useForm({
    to: getFromEmail(props.email.from),
    cc: '',
    bcc: '',
    reply_message: '',
    attachments: [], 
});

const triggerFileInput = () => fileInput.value.click();

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    replyForm.attachments = [...replyForm.attachments, ...files];
    event.target.value = '';
};

const removeAttachment = (index) => {
    replyForm.attachments.splice(index, 1);
};

const submitReply = () => {
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

const confirmAction = useConfirmAction();
const moveToTrash = () => {
    confirmAction({
        message: 'Déplacer cet email vers la corbeille ?',
        acceptLabel: 'Mettre à la corbeille',
        danger: true,
        accept: () => router.post(route('admin.emails.trash', props.messageNumber)),
    });
};

// --- Formatage ---
const formatDate = (iso) => {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
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
    <Head :title="email.subject || 'Lecture Email'" />
    <AppLayout>
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1200px] mx-auto">
                
                <!-- Lien de retour type Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-3">
                    <Link :href="route('admin.emails.index')" class="text-slate-500 hover:text-slate-800 text-[11px] font-black uppercase tracking-widest flex items-center gap-2 transition-colors">
                        <i class="pi pi-arrow-left"></i> Retour à la boîte de réception
                    </Link>
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-[#ff4d4d] text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                                Webmail Client
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-3 leading-none">
                                <span class="text-white">LECTURE</span>
                                <span class="text-[#EF4444]">EMAIL</span>
                            </h1>

                            <p class="text-slate-400 text-sm md:text-base italic leading-relaxed font-bold flex items-center gap-2">
                                Sujet : <span class="text-white">{{ email.subject || '(sans objet)' }}</span>
                            </p>
                            
                            <!-- Badges -->
                            <div class="flex items-center gap-2 mt-4">
                                <Tag v-if="email.flagged" value="Important" class="!bg-red-500/20 !text-red-400 !border !border-red-500/30 !rounded-full !px-3" />
                                <Tag v-if="email.has_attachments" icon="pi pi-paperclip" value="Pièces jointes" class="!bg-slate-700/50 !text-slate-300 !border !border-slate-600/50 !rounded-full !px-3" />
                            </div>
                        </div>

                        <!-- Partie Droite (Actions) -->
                        <div class="flex items-center gap-2 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto bg-slate-800/50 p-1.5 rounded-2xl border border-slate-700">
                            <Button v-if="email.flagged" icon="pi pi-star-fill" class="!text-red-400 hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="unflag" v-tooltip.top="'Retirer le marqueur'" />
                            <Button v-else icon="pi pi-star" class="!text-slate-400 hover:!text-white hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="markAsFlagged" v-tooltip.top="'Marquer comme important'" />
                            
                            <div class="w-px h-6 bg-slate-700 mx-1"></div>
                            
                            <Button icon="pi pi-reply" class="!text-slate-400 hover:!text-white hover:!bg-slate-700 !w-10 !h-10 !p-0" text rounded @click="showReplyForm = true" v-tooltip.top="'Répondre'" />
                            <Button icon="pi pi-trash" class="!text-[#EF4444] hover:!bg-red-900/50 !w-10 !h-10 !p-0" text rounded @click="moveToTrash" v-tooltip.top="'Supprimer'" />
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES D'INFORMATIONS (Chevauchement) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- Expéditeur -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex items-center gap-4 h-32">
                        <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shadow-sm shrink-0', getAvatarColor(getFromName(email.from))]">
                            {{ getInitials(getFromName(email.from)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Expéditeur</div>
                            <div class="text-lg font-black text-slate-800 truncate" :title="getFromName(email.from)">{{ getFromName(email.from) }}</div>
                        </div>
                    </div>

                    <!-- Destinataires -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center h-32">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><i class="pi pi-envelope text-blue-400"></i> Adresses</div>
                        <div class="text-sm text-slate-600 truncate"><span class="font-medium text-slate-400 w-8 inline-block">De:</span> <span class="font-bold text-slate-800">{{ getFromEmail(email.from) }}</span></div>
                        <div class="text-sm text-slate-600 truncate"><span class="font-medium text-slate-400 w-8 inline-block">À:</span> {{ toDisplay || 'Moi' }}</div>
                    </div>

                    <!-- Date & Poids -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center h-32">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><i class="pi pi-calendar text-blue-400"></i> Réception</div>
                        <div class="text-[13px] font-bold text-slate-800 capitalize">{{ formatDate(email.date) }}</div>
                        <div class="text-xs text-slate-400 mt-1 font-mono">Poids du message : {{ formatSize(email.size) }}</div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- CONTENU DE L'EMAIL ET RÉPONSE                                                  -->
                <!-- ============================================================================== -->
                <div class="mt-6 space-y-6 px-2 md:px-4 relative z-20">
                    
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        
                        <!-- Pièces jointes reçues -->
                        <div v-if="email.has_attachments && email.attachments?.length" class="px-6 md:px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-500 mb-3 flex items-center gap-2">
                                <i class="pi pi-paperclip"></i> {{ email.attachments.length }} Pièce(s) jointe(s)
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <div v-for="(attachment, index) in email.attachments" :key="index"
                                    class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-blue-300 hover:shadow-md transition-all cursor-pointer group min-w-[200px] max-w-[300px]">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                                        <i :class="[
                                            'pi text-xl',
                                            attachment.subtype === 'PDF' ? 'pi-file-pdf text-red-500' :
                                            attachment.subtype === 'JPEG' || attachment.subtype === 'PNG' ? 'pi-image text-blue-500' :
                                            'pi-file text-slate-500'
                                        ]"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-700 truncate group-hover:text-blue-600 transition-colors">{{ attachment.filename }}</p>
                                        <p class="text-xs text-slate-400">{{ formatSize(attachment.size) }}</p>
                                    </div>
                                    <i class="pi pi-download text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Contenu du message -->
                        <div class="p-6 md:p-8 min-h-[300px]">
                            <div v-if="hasHtml" class="email-content prose max-w-none text-slate-800" v-html="email.html_body"></div>
                            <pre v-else class="whitespace-pre-wrap font-sans text-[15px] leading-relaxed text-slate-800">{{ cleanBody }}</pre>
                        </div>
                        
                        <!-- Actions de bas de page -->
                        <div class="bg-slate-50 border-t border-slate-100 p-4 md:p-6 flex items-center gap-3">
                            <Button
                                v-if="!showReplyForm"
                                label="Répondre"
                                icon="pi pi-reply"
                                @click="showReplyForm = true"
                                class="!bg-white !text-slate-700 !border-slate-200 hover:!bg-slate-100 hover:!border-slate-300 !rounded-xl shadow-sm px-6 font-bold"
                            />
                        </div>
                    </div>

                    <!-- Formulaire de réponse enrichi -->
                    <div v-if="showReplyForm" id="replyForm" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden transform transition-all">
                        
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-slate-700 font-black uppercase tracking-widest text-xs flex items-center gap-2">
                                <i class="pi pi-reply text-blue-500"></i> Nouvelle réponse
                            </h3>
                            <button @click="showReplyForm = false" class="text-slate-400 hover:text-slate-600">
                                <i class="pi pi-times"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitReply" class="flex flex-col">
                            <!-- Destinataires & CC/CCI -->
                            <div class="px-6 py-2 border-b border-slate-100">
                                <div class="flex items-center py-2 text-sm">
                                    <span class="w-12 text-slate-400 font-bold">À :</span>
                                    <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-bold border border-blue-100">{{ replyForm.to }}</span>
                                    <button type="button" @click="showCcBcc = !showCcBcc" class="ml-auto text-xs text-blue-600 hover:underline font-bold">
                                        {{ showCcBcc ? 'Masquer Cc/Cci' : 'Ajouter Cc/Cci' }}
                                    </button>
                                </div>

                                <div v-show="showCcBcc" class="space-y-2 py-2 border-t border-slate-100 mt-2">
                                    <div class="flex items-center">
                                        <span class="w-12 text-slate-400 font-bold text-sm">Cc :</span>
                                        <InputText v-model="replyForm.cc" class="flex-1 !border-none !shadow-none focus:!ring-0 !p-0 text-sm font-medium" placeholder="Ex: collaborateur@domaine.com" />
                                    </div>
                                    <div class="flex items-center">
                                        <span class="w-12 text-slate-400 font-bold text-sm">Cci :</span>
                                        <InputText v-model="replyForm.bcc" class="flex-1 !border-none !shadow-none focus:!ring-0 !p-0 text-sm font-medium" placeholder="Ex: discret@domaine.com" />
                                    </div>
                                </div>
                            </div>

                            <!-- Zone de texte -->
                            <div class="px-6 py-4">
                                <Textarea
                                    v-model="replyForm.reply_message"
                                    rows="10"
                                    class="w-full !border-none !shadow-none focus:!ring-0 !p-0 text-[15px] text-slate-800 leading-relaxed resize-none bg-transparent"
                                    placeholder="Écrivez votre réponse ici..."
                                />
                                <p v-if="replyForm.errors.reply_message" class="text-red-500 text-xs font-bold mt-2"><i class="pi pi-exclamation-triangle"></i> {{ replyForm.errors.reply_message }}</p>
                            </div>

                            <!-- Fichiers joints en attente -->
                            <div v-if="replyForm.attachments.length" class="px-6 pb-4 flex flex-wrap gap-2 border-t border-slate-50 pt-4">
                                <div v-for="(file, index) in replyForm.attachments" :key="index"
                                     class="flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-lg text-sm border border-slate-200">
                                    <i class="pi pi-file text-slate-500"></i>
                                    <span class="truncate max-w-[200px] font-bold text-slate-700">{{ file.name }}</span>
                                    <span class="text-xs text-slate-400">({{ formatSize(file.size) }})</span>
                                    <button type="button" @click="removeAttachment(index)" class="ml-2 text-slate-400 hover:text-red-500 transition-colors">
                                        <i class="pi pi-times-circle text-base"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Barre d'outils bas (Boutons Envoyer et Joindre) -->
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between rounded-b-xl">
                                <!-- Input de fichier caché -->
                                <input type="file" multiple ref="fileInput" class="hidden" @change="handleFileSelect" />

                                <Button type="button" icon="pi pi-paperclip" text rounded class="!text-slate-500 hover:!bg-slate-200" @click="triggerFileInput" v-tooltip.top="'Joindre des fichiers'" />

                                <div class="flex items-center gap-3">
                                    <Button type="button" label="Annuler" text @click="showReplyForm = false" class="!text-slate-500 font-bold" />
                                    <Button type="submit" label="Envoyer le message" icon="pi pi-send" :loading="replyForm.processing" class="!bg-[#EF4444] hover:!bg-red-600 !border-none !rounded-xl px-6 font-bold shadow-md shadow-red-500/20" />
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Styling de base pour l'email au format HTML rendu proprement */
.email-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1rem 0;
}
.email-content :deep(a) {
    color: #4f46e5; /* blue-600 */
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