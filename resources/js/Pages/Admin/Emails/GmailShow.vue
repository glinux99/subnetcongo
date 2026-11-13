<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();
const props = defineProps({
    email: { type: Object, default: () => ({}) },
    folders: { type: Array, default: () => [] },
    messageNumber: { type: Number, default: null },
    folder: { type: String, default: null },
});

const folderPayload = () => props.folder ? { folder: props.folder } : {};

const showReplyForm = ref(false);
const replyText = ref('');
const cc = ref('');
const bcc = ref('');
const showCcBcc = ref(false);
const isSending = ref(false);
const replyAttachments = ref([]);
const replyFileInput = ref(null);
const maxAttachmentSize = 25 * 1024 * 1024;

const addReplyAttachment = (event) => {
    const files = Array.from(event.target.files);
    const tooLarge = files.filter((f) => f.size > maxAttachmentSize);
    if (tooLarge.length > 0) {
        toast.add({
            severity: 'error',
            summary: t('pages.admin_emails_gmail.attach_too_large_title'),
            detail: t('pages.admin_emails_gmail.attach_too_large_detail', { names: tooLarge.map((f) => f.name).join(', ') }),
            life: 5000,
        });
    }
    const ok = files.filter((f) => f.size <= maxAttachmentSize);
    if (ok.length > 0) replyAttachments.value.push(...ok);
    event.target.value = '';
};

const removeReplyAttachment = (index) => {
    replyAttachments.value.splice(index, 1);
};

const openReplyFilePicker = () => {
    replyFileInput.value?.click();
};

const formatDate = (iso) => {
    if (!iso) return '';
    try {
        const date = new Date(iso);
        return date.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return iso;
    }
};

const formatSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / 1048576).toFixed(1) + ' Mo';
};

const getFromName = (from) => {
    if (!from || !from.length) return 'Inconnu';
    return from[0].name || from[0].email;
};

const getFromEmail = (from) => {
    if (!from || !from.length) return '';
    return from[0].email || '';
};

const getToNames = (to) => {
    if (!to || !to.length) return '';
    return to.map(addr => addr.name || addr.email).join(', ');
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

const goBack = () => {
    router.visit(route('admin.emails.index', props.folder ? { folder: props.folder } : {}));
};

const markAsUnread = () => {
    router.post(route('admin.emails.markAsUnread', props.messageNumber), folderPayload(), {
        preserveScroll: true,
        onSuccess: () => goBack()
    });
};

const archive = () => {
    router.post(route('admin.emails.archive', props.messageNumber), folderPayload(), {
        preserveScroll: true,
        onSuccess: () => goBack()
    });
};

const unarchive = () => {
    router.post(route('admin.emails.unarchive', props.messageNumber), folderPayload(), {
        preserveScroll: true,
        onSuccess: () => goBack()
    });
};

const confirmAction = useConfirmAction();
const moveToTrash = () => {
    confirmAction({
        message: 'Déplacer cet email vers la corbeille ?',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.post(route('admin.emails.trash', props.messageNumber), folderPayload(), {
            preserveScroll: true,
            onSuccess: () => goBack()
        })
    });
};

const permanentlyDelete = () => {
    confirmAction({
        message: 'Supprimer définitivement cet email ?',
        acceptLabel: 'Supprimer définitivement',
        danger: true,
        accept: () => router.post(route('admin.emails.permanentlyDelete', props.messageNumber), folderPayload(), {
            preserveScroll: true,
            onSuccess: () => goBack()
        })
    });
};

const forward = () => {
    router.post(route('admin.emails.forward', props.messageNumber), folderPayload());
};

const sendReply = () => {
    if (!replyText.value.trim()) return;

    isSending.value = true;
    const formData = new FormData();
    formData.append('reply_message', replyText.value);
    if (cc.value) formData.append('cc', cc.value);
    if (bcc.value) formData.append('bcc', bcc.value);
    if (props.folder) formData.append('folder', props.folder);
    replyAttachments.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
    });
    router.post(route('admin.emails.reply', props.messageNumber), formData, {
        preserveScroll: true,
        onSuccess: () => {
            showReplyForm.value = false;
            replyText.value = '';
            cc.value = '';
            bcc.value = '';
            replyAttachments.value = [];
        },
        onFinish: () => isSending.value = false
    });
};

const downloadAttachment = (attachment) => {
    // Implement attachment download logic
    console.log('Downloading attachment:', attachment);
};

const isArchivedFolder = computed(() => (props.folder || '').toLowerCase().includes('archive'));

const headerActions = computed(() => [
    { label: 'Retour', icon: 'pi-arrow-left', onClick: goBack, variant: 'secondary' },
    { label: 'Répondre', icon: 'pi-reply', onClick: () => showReplyForm.value = !showReplyForm.value, variant: 'primary' },
    { label: 'Transférer', icon: 'pi-forward', onClick: forward, variant: 'secondary' },
    isArchivedFolder.value
        ? { label: 'Désarchiver', icon: 'pi-inbox', onClick: unarchive, variant: 'secondary' }
        : { label: 'Archiver', icon: 'pi-box', onClick: archive, variant: 'secondary' },
    { label: 'Supprimer', icon: 'pi-trash', onClick: moveToTrash, variant: 'secondary' }
]);

const getAttachmentIcon = (filename) => {
    const ext = filename.split('.').pop().toLowerCase();
    const icons = {
        pdf: 'pi pi-file-pdf text-red-500',
        doc: 'pi pi-file-word text-blue-500',
        docx: 'pi pi-file-word text-blue-500',
        xls: 'pi pi-file-excel text-green-500',
        xlsx: 'pi pi-file-excel text-green-500',
        ppt: 'pi pi-file-powerpoint text-orange-500',
        pptx: 'pi pi-file-powerpoint text-orange-500',
        zip: 'pi pi-file-zip text-purple-500',
        rar: 'pi pi-file-zip text-purple-500',
        jpg: 'pi pi-image text-green-500',
        jpeg: 'pi pi-image text-green-500',
        png: 'pi pi-image text-green-500',
        gif: 'pi pi-image text-green-500',
    };
    return icons[ext] || 'pi pi-file text-gray-500';
};
</script>

<template>
    <AppLayout>
        <Head title="Lire l'email" />
        
        <div class="min-h-screen bg-slate-50">
            <!-- PageHeader -->
            <PageHeader
                :title="email.subject || '(Sans objet)'"
                :subtitle="`De: ${getFromName(email.from)} - ${formatDate(email.date)}`"
                badge="Email"
                icon="pi-envelope-open"
                accent="blue"
                :actions="headerActions"
            />

            <!-- Email content -->
            <div class="max-w-4xl mx-auto p-4">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- Email header -->
                    <div class="p-6 border-b border-slate-100">
                        <div class="flex items-start gap-4">
                            <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold', getAvatarColor(getFromName(email.from))]">
                                {{ getInitials(getFromName(email.from)) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ getFromName(email.from) }}</p>
                                        <p class="text-sm text-slate-500">{{ getFromEmail(email.from) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-slate-500">{{ formatDate(email.date) }}</p>
                                        <p class="text-xs text-slate-400">{{ formatSize(email.size) }}</p>
                                    </div>
                                </div>
                                <div class="text-sm text-slate-600">
                                    <span class="font-medium">À :</span> {{ getToNames(email.to) }}
                                    <span v-if="email.cc && email.cc.length" class="ml-4">
                                        <span class="font-medium">Cc :</span> {{ getToNames(email.cc) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email body -->
                    <div class="p-6">
                        <div class="prose max-w-none" v-html="email.body || email.html_body"></div>
                    </div>

                    <!-- Attachments -->
                    <div v-if="email.has_attachments && email.attachments" class="p-6 border-t border-slate-100">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Pièces jointes ({{ email.attachments.length }})</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div
                                v-for="(attachment, index) in email.attachments"
                                :key="index"
                                class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer"
                                @click="downloadAttachment(attachment)"
                            >
                                <i :class="getAttachmentIcon(attachment.filename)" class="text-2xl"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ attachment.filename }}</p>
                                    <p class="text-xs text-slate-500">{{ formatSize(attachment.size) }}</p>
                                </div>
                                <Button
                                    icon="pi pi-download"
                                    class="p-1 text-slate-400 hover:text-blue-600"
                                    @click.stop="downloadAttachment(attachment)"
                                    text
                                    rounded
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reply form -->
                <div v-if="showReplyForm" class="mt-4 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-medium text-slate-800">Répondre à {{ getFromName(email.from) }}</h3>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-slate-600 font-medium">À :</span>
                            <span class="text-slate-900">{{ getFromEmail(email.from) }}</span>
                            <Button
                                icon="pi pi-plus"
                                class="p-1 text-slate-400 hover:text-slate-600"
                                @click="showCcBcc = !showCcBcc"
                                text
                                rounded
                            />
                        </div>
                        <div v-if="showCcBcc" class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-slate-600 font-medium">Cc :</span>
                                <InputText v-model="cc" placeholder="Ajouter des destinataires en copie" class="flex-1" />
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-slate-600 font-medium">Cci :</span>
                                <InputText v-model="bcc" placeholder="Ajouter des destinataires en copie cachée" class="flex-1" />
                            </div>
                        </div>
                        <Textarea
                            v-model="replyText"
                            placeholder="Écrivez votre réponse..."
                            rows="8"
                            class="w-full"
                        />
                        <div v-if="replyAttachments.length > 0" class="space-y-2">
                            <div
                                v-for="(file, index) in replyAttachments"
                                :key="index"
                                class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg"
                            >
                                <i class="pi pi-file text-slate-400"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ file.name }}</p>
                                    <p class="text-xs text-slate-500">{{ formatSize(file.size) }}</p>
                                </div>
                                <Button
                                    icon="pi pi-times"
                                    class="p-1 text-slate-400 hover:text-red-600"
                                    @click="removeReplyAttachment(index)"
                                    text
                                    rounded
                                />
                            </div>
                        </div>
                        <input
                            type="file"
                            multiple
                            @change="addReplyAttachment"
                            class="hidden"
                            ref="replyFileInput"
                        />
                        <div class="flex items-center justify-between pt-2">
                            <div class="flex items-center gap-2">
                                <Button
                                    icon="pi pi-paperclip"
                                    class="text-slate-600 hover:bg-slate-100"
                                    @click="openReplyFilePicker"
                                    text
                                />
                                <Button
                                    label="Envoyer"
                                    icon="pi pi-send"
                                    class="bg-blue-600 hover:bg-blue-700 text-white"
                                    @click="sendReply"
                                    :loading="isSending"
                                    :disabled="!replyText.trim()"
                                />
                                <Button
                                    label="Annuler"
                                    class="text-slate-600 hover:bg-slate-100"
                                    @click="showReplyForm = false"
                                    text
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.gmail-read-container {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
}

.prose {
    line-height: 1.6;
}

.prose img {
    max-width: 100%;
    height: auto;
}

.prose a {
    color: #1a73e8;
    text-decoration: none;
}

.prose a:hover {
    text-decoration: underline;
}

.prose blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin-left: 0;
    font-style: italic;
    color: #6b7280;
}
</style>
