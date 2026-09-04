<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';

const props = defineProps({
    message: { type: Object, required: true },
});

const replyForm = useForm({ reply_message: props.message.reply_message || '' });
const submitReply = () => replyForm.post(route('admin.messages.reply', props.message.id), { preserveScroll: true });
const archive = () => router.post(route('admin.messages.archive', props.message.id));
const destroyMsg = () => {
    if (confirm('Supprimer définitivement ce message ?')) router.delete(route('admin.messages.destroy', props.message.id));
};

const statusLabel = (s) => ({ new:'Nouveau', read:'Lu', replied:'Répondu', archived:'Archivé', spam:'Spam' }[s] || s);
const statusColor = (s) => ({ new:'rose', read:'amber', replied:'emerald', archived:'secondary', spam:'secondary' }[s] || 'secondary');
const formatDate = (iso) => { if(!iso) return ''; try { return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(_) { return iso; } };
</script>

<template>
    <Head :title="`Message ${message.ticket_number}`" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="max-w-[1000px] mx-auto relative z-10">
                    <Link :href="route('admin.messages.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4">
                        <i class="pi pi-arrow-left"></i> Retour aux messages
                    </Link>
                    <p class="text-slate-400 text-xs font-mono">{{ message.ticket_number }} · Reçu le {{ formatDate(message.created_at) }}</p>
                    <div class="flex items-start justify-between gap-4 flex-wrap mt-2">
                        <h1 class="text-3xl font-black text-white tracking-tight">{{ message.subject }}</h1>
                        <Tag :value="statusLabel(message.status)" :severity="statusColor(message.status)" class="!text-[10px] !font-black !uppercase" />
                    </div>
                </div>
            </div>

            <div class="max-w-[1000px] mx-auto px-4 lg:px-6 -mt-8 relative z-20 space-y-6">
                <div class="bg-white rounded-2xl shadow border border-slate-100 p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Nom</p>
                        <p class="font-bold text-slate-900">{{ message.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Email</p>
                        <a :href="`mailto:${message.email}`" class="font-bold text-indigo-600 hover:underline break-all">{{ message.email }}</a>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Téléphone</p>
                        <p class="font-bold text-slate-900">{{ message.phone || '—' }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-3">Message reçu</h3>
                    <pre class="whitespace-pre-wrap font-sans text-sm text-slate-700 bg-slate-50 p-4 rounded-xl">{{ message.message }}</pre>
                    <p class="text-xs text-slate-400 mt-3">IP : {{ message.ip_address || '—' }} · Source : {{ message.source || '—' }}</p>
                </div>

                <div v-if="message.status === 'replied' && message.reply_message" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-emerald-700 mb-3">Votre réponse (envoyée le {{ formatDate(message.replied_at) }})</h3>
                    <pre class="whitespace-pre-wrap font-sans text-sm text-slate-700">{{ message.reply_message }}</pre>
                </div>

                <div v-if="message.status !== 'archived' && message.status !== 'replied'" class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-3">Répondre par email</h3>
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <Textarea v-model="replyForm.reply_message" rows="8" class="w-full !rounded-xl" placeholder="Tapez votre réponse ici. Elle sera envoyée à l'expéditeur par email ET enregistrée dans le système." />
                        <p v-if="replyForm.errors.reply_message" class="text-rose-500 text-xs">{{ replyForm.errors.reply_message }}</p>
                        <div class="flex justify-end gap-3">
                            <Button type="button" label="Archiver" icon="pi pi-inbox" outlined @click="archive" class="!rounded-xl" />
                            <Button type="button" label="Supprimer" icon="pi pi-trash" severity="danger" outlined @click="destroyMsg" class="!rounded-xl" />
                            <Button type="submit" label="Envoyer la réponse" icon="pi pi-send" :loading="replyForm.processing" class="!bg-rose-600 hover:!bg-rose-500 !border-none !rounded-xl" />
                        </div>
                    </form>
                </div>
                <div v-else class="flex justify-end gap-3">
                    <Button label="Supprimer" icon="pi pi-trash" severity="danger" outlined @click="destroyMsg" class="!rounded-xl" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
