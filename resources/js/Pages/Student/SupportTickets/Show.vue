<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';

const props = defineProps({
    ticket: { type: Object, required: true },
});

const replyForm = useForm({ message: '' });
const submitReply = () => {
    replyForm.post(route('student.support.reply', props.ticket.id), { preserveScroll: true, onSuccess: () => replyForm.reset() });
};
const closeTicket = () => {
    if (confirm('Fermer ce ticket ?')) router.post(route('student.support.close', props.ticket.id));
};

const statusLabel = (s) => ({ open:'Ouvert', new:'Nouveau', pending:'En attente', in_progress:'En cours', resolved:'Résolu', closed:'Fermé' }[s] || s);
const statusSeverity = (s) => ({ open:'info', new:'info', pending:'warning', in_progress:'info', resolved:'success', closed:'secondary' }[s] || 'secondary');
const priorityLabel = (p) => ({ low:'Basse', medium:'Moyenne', high:'Haute', urgent:'Urgente' }[p] || p);
const priorityColor = (p) => ({ urgent:'rose', high:'orange', medium:'amber', low:'emerald' }[p] || 'slate');
const formatDate = (iso) => { if(!iso) return ''; try { return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(_) { return iso; } };
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_number}`" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[1000px] mx-auto relative z-10">
                    <Link :href="route('student.support.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4">
                        <i class="pi pi-arrow-left"></i> Retour à mes tickets
                    </Link>
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-slate-400 text-xs font-mono">{{ ticket.ticket_number }}</p>
                            <h1 class="text-3xl font-black text-white tracking-tight mt-1">{{ ticket.subject }}</h1>
                            <div class="flex items-center gap-2 mt-3">
                                <Tag :value="statusLabel(ticket.status)" :severity="statusSeverity(ticket.status)" class="!text-[10px] !font-black !uppercase" />
                                <Tag :value="priorityLabel(ticket.priority)" :severity="priorityColor(ticket.priority)" class="!text-[10px] !font-black !uppercase" />
                                <Tag v-if="ticket.category" :value="ticket.category.name" :style="{ backgroundColor: ticket.category.color }" class="!text-white !text-[10px] !font-black !uppercase" />
                            </div>
                        </div>
                        <Button v-if="ticket.status !== 'closed'" label="Fermer le ticket" icon="pi pi-times" outlined @click="closeTicket" class="!border-white/20 !text-white hover:!bg-white/10 !rounded-xl" />
                    </div>
                </div>
            </div>

            <div class="max-w-[1000px] mx-auto px-4 lg:px-6 -mt-6 relative z-20 space-y-6">
                <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-3">Conversation</h3>
                    <div class="space-y-3">
                        <div v-for="(block, i) in (ticket.message || '').split(/\n\n--- /)" :key="i" class="p-4 rounded-2xl" :class="i === 0 ? 'bg-indigo-50 border border-indigo-100' : 'bg-slate-50'">
                            <pre class="whitespace-pre-wrap font-sans text-sm text-slate-700">{{ block }}</pre>
                        </div>
                    </div>
                </div>

                <div v-if="ticket.status !== 'closed'" class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-3">Répondre</h3>
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <Textarea v-model="replyForm.message" rows="4" class="w-full !rounded-xl" placeholder="Votre réponse..." />
                        <p v-if="replyForm.errors.message" class="text-rose-500 text-xs">{{ replyForm.errors.message }}</p>
                        <div class="flex justify-end">
                            <Button type="submit" label="Envoyer la réponse" icon="pi pi-send" :loading="replyForm.processing" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
