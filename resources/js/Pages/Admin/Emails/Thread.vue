<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Divider from 'primevue/divider';
import { ref, computed } from 'vue';

const props = defineProps({
    email: { type: Object, required: true },
    thread: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    messageNumber: { type: [Number, String], required: true },
});

const showReplyForm = ref(false);
const replyingTo = ref(null);
const replyForm = useForm({
    to: '',
    cc: '',
    reply_message: '',
});

const formatDate = (iso) => {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString('fr-FR', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return iso;
    }
};

const getAddressList = (addresses) => {
    if (!addresses || !addresses.length) return '';
    return addresses.map(a => a.name ? `${a.name} <${a.email}>` : a.email).join(', ');
};

const getAvatarColor = (name) => {
    const colors = ['bg-rose-100 text-rose-700', 'bg-blue-100 text-blue-700', 'bg-green-100 text-green-700', 'bg-purple-100 text-purple-700'];
    if (!name) return colors[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
};

const startReply = (toEmail) => {
    showReplyForm.value = true;
    replyingTo.value = toEmail;
    replyForm.to = toEmail;
    replyForm.reply_message = '';
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

const goToEmail = (msgNumber) => {
    router.visit(route('admin.emails.show', msgNumber));
};
</script>

<template>
    <Head title="Conversation" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header -->
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="max-w-[1100px] mx-auto relative z-10">
                    <Link :href="route('admin.emails.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4 transition">
                        <i class="pi pi-arrow-left"></i> Retour à la boîte de réception
                    </Link>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-black text-white tracking-tight">
                                Conversation ({{ thread.length }} messages)
                            </h1>
                            <p class="text-slate-400 text-sm mt-1">{{ email.subject }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                label="Répondre"
                                icon="pi pi-reply"
                                size="small"
                                @click="startReply(email.from?.[0]?.email)"
                                class="!rounded-xl !bg-rose-500 !border-rose-500"
                            />
                            <Button
                                label="Transférer"
                                icon="pi pi-send"
                                size="small"
                                outlined
                                @click="router.visit(route('admin.emails.forward', messageNumber))"
                                class="!rounded-xl"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1100px] mx-auto px-4 lg:px-6 -mt-8 relative z-20 space-y-6">
                <!-- Thread Messages -->
                <div class="space-y-4">
                    <div
                        v-for="(msg, index) in thread"
                        :key="msg.message_number"
                        :id="`msg-${msg.message_number}`"
                        class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden"
                    >
                        <!-- Message Header -->
                        <div class="p-4 flex items-start gap-4">
                            <!-- Avatar -->
                            <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold shadow-sm flex-shrink-0', getAvatarColor(getAddressList(msg.from))]">
                                {{ getInitials(getAddressList(msg.from)) }}
                            </div>

                            <!-- Header Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-4 mb-1">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ getAddressList(msg.from) }}</p>
                                        <p class="text-xs text-slate-500">À: {{ getAddressList(msg.to) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="msg.flagged" class="text-amber-500">
                                            <i class="pi pi-flag-fill"></i>
                                        </span>
                                        <span class="text-xs text-slate-400">{{ formatDate(msg.date) }}</span>
                                        <Tag v-if="!msg.seen" value="Nouveau" severity="info" class="!text-[10px]" />
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                <div class="flex items-center gap-2 mt-2">
                                    <Button
                                        v-if="index === thread.length - 1"
                                        label="Répondre"
                                        icon="pi pi-reply"
                                        size="small"
                                        text
                                        @click="startReply(msg.from?.[0]?.email)"
                                        class="!rounded-xl"
                                    />
                                    <Button
                                        label="Voir"
                                        icon="pi pi-external-link"
                                        size="small"
                                        text
                                        @click="goToEmail(msg.message_number)"
                                        class="!rounded-xl"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div class="px-4 pb-4 border-t border-slate-50">
                            <div class="pt-4">
                                <div v-if="msg.html_body && msg.html_body.length > 0" class="email-content prose max-w-none" v-html="msg.html_body"></div>
                                <pre v-else class="whitespace-pre-wrap font-sans text-sm text-slate-700">{{ msg.text_body || msg.body || '' }}</pre>
                            </div>

                            <!-- Attachments -->
                            <div v-if="msg.has_attachments && msg.attachments?.length" class="mt-4 pt-4 border-t border-slate-100">
                                <p class="text-xs font-black uppercase text-slate-500 mb-2">
                                    <i class="pi pi-paperclip mr-1"></i>Pièces jointes
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="(att, i) in msg.attachments"
                                        :key="i"
                                        class="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-lg text-sm"
                                    >
                                        <i :class="[
                                            'pi',
                                            att.subtype === 'PDF' ? 'pi-file-pdf text-rose-500' :
                                            att.subtype === 'JPEG' || att.subtype === 'PNG' ? 'pi-image text-emerald-500' :
                                            'pi-file text-slate-500'
                                        ]"></i>
                                        <span class="text-slate-700">{{ att.filename }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reply Form -->
                <div v-if="showReplyForm" class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-4">
                        <i class="pi pi-reply mr-2"></i>Répondre à {{ replyingTo }}
                    </h3>
                    <form @submit.prevent="submitReply" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1 block">À</label>
                                <InputText v-model="replyForm.to" class="!rounded-xl w-full" />
                            </div>
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1 block">Cc (optionnel)</label>
                                <InputText v-model="replyForm.cc" type="email" class="!rounded-xl w-full" placeholder="copie@email.com" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-1 block">Votre réponse</label>
                            <Textarea v-model="replyForm.reply_message" rows="8" class="w-full !rounded-xl" placeholder="Tapez votre réponse..." />
                            <p v-if="replyForm.errors.reply_message" class="text-rose-500 text-xs mt-1">{{ replyForm.errors.reply_message }}</p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <Button type="button" label="Annuler" icon="pi pi-times" outlined @click="showReplyForm = false" class="!rounded-xl" />
                            <Button type="submit" label="Envoyer" icon="pi pi-send" :loading="replyForm.processing" class="!bg-emerald-600 hover:!bg-emerald-500 !border-none !rounded-xl" />
                        </div>
                    </form>
                </div>

                <!-- Reply Button (if form hidden) -->
                <div v-if="!showReplyForm" class="flex justify-center">
                    <Button
                        label="Répondre à cette conversation"
                        icon="pi pi-reply"
                        @click="startReply(thread[thread.length - 1]?.from?.[0]?.email)"
                        class="!rounded-xl !bg-rose-500 !border-rose-500"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.email-content :deep(img) { max-width: 100%; height: auto; }
.email-content :deep(a) { color: #6366f1; }
.email-content :deep(blockquote) { border-left: 3px solid #e2e8f0; padding-left: 1rem; color: #64748b; }
</style>
