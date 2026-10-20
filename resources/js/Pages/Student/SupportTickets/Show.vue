<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import PageCard from '@/Components/PageCard.vue';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';

const props = defineProps({
    ticket: { type: Object, required: true },
});

const replyForm = useForm({ message: '' });
const submitReply = () => {
    replyForm.post(route('student.support.reply', props.ticket.id), { preserveScroll: true, onSuccess: () => replyForm.reset() });
};
const confirmAction = useConfirmAction();
const closeTicket = () => {
    confirmAction({
        header: 'Fermer le ticket',
        message: 'Fermer ce ticket ? Vous pourrez en ouvrir un nouveau si le problème revient.',
        acceptLabel: 'Fermer le ticket',
        accept: () => router.post(route('student.support.close', props.ticket.id)),
    });
};

const statusLabel = (s) => ({ open:'Ouvert', new:'Nouveau', pending:'En attente', in_progress:'En cours', resolved:'Résolu', closed:'Fermé' }[s] || s);
const statusSeverity = (s) => ({ open:'info', new:'info', pending:'warn', in_progress:'info', resolved:'success', closed:'secondary' }[s] || 'secondary');
const priorityLabel = (p) => ({ low:'Basse', medium:'Moyenne', high:'Haute', urgent:'Urgente' }[p] || p);
const prioritySeverity = (p) => ({ urgent:'danger', high:'warn', medium:'info', low:'success' }[p] || 'secondary');
const formatDate = (iso) => { if(!iso) return ''; try { return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(_) { return iso; } };
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_number}`" />
    <AppLayout>
        <div class="page-bg min-h-screen">
            <header class="page-header">
                <div class="page-header-orb-1" aria-hidden="true"></div>
                <div class="max-w-[1000px] mx-auto relative z-10">
                    <Link :href="route('student.support.index')" class="inline-flex items-center gap-2 text-sm text-slate-300 hover:text-white mb-5 rounded">
                        <i class="pi pi-arrow-left text-xs" aria-hidden="true"></i> Retour à mes tickets
                    </Link>
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-slate-400 text-xs font-mono">{{ ticket.ticket_number }}</p>
                            <h1 class="page-header-title mt-1 break-words">{{ ticket.subject }}</h1>
                            <div class="flex items-center gap-2 mt-3 flex-wrap">
                                <Tag :value="statusLabel(ticket.status)" :severity="statusSeverity(ticket.status)" />
                                <Tag :value="`Priorité ${priorityLabel(ticket.priority).toLowerCase()}`" :severity="prioritySeverity(ticket.priority)" />
                                <Tag v-if="ticket.category" :value="ticket.category.name" :style="{ backgroundColor: ticket.category.color }" class="!text-white" />
                            </div>
                        </div>
                        <button v-if="ticket.status !== 'closed'" type="button" class="btn-header-secondary" @click="closeTicket">
                            <i class="pi pi-check-circle" aria-hidden="true"></i> Fermer le ticket
                        </button>
                    </div>
                </div>
            </header>

            <div class="max-w-[1000px] mx-auto px-4 md:px-6 -mt-16 relative z-20 pb-20 space-y-6">
                <PageCard title="Conversation">
                    <ol class="space-y-3">
                        <li
                            v-for="(block, i) in (ticket.message || '').split(/\n\n--- /)"
                            :key="i"
                            class="p-4 rounded-xl border"
                            :class="i === 0 ? 'bg-blue-50 border-blue-100 dark:bg-blue-500/10 dark:border-blue-500/20' : 'bg-slate-50 border-slate-100 dark:bg-slate-800/50 dark:border-slate-700'"
                        >
                            <pre class="whitespace-pre-wrap font-sans text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ i === 0 ? block : '--- ' + block }}</pre>
                        </li>
                    </ol>
                </PageCard>

                <PageCard v-if="ticket.status !== 'closed'" title="Répondre" subtitle="Notre équipe vous répond généralement sous 24 h ouvrées.">
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <label for="ticket-reply" class="sr-only">Votre réponse</label>
                        <Textarea
                            id="ticket-reply"
                            v-model="replyForm.message"
                            rows="4"
                            autoResize
                            class="w-full"
                            placeholder="Décrivez votre problème ou ajoutez des précisions…"
                            :invalid="!!replyForm.errors.message"
                            :aria-describedby="replyForm.errors.message ? 'ticket-reply-error' : undefined"
                        />
                        <p v-if="replyForm.errors.message" id="ticket-reply-error" class="text-rose-600 dark:text-rose-400 text-xs" role="alert">{{ replyForm.errors.message }}</p>
                        <div class="flex justify-end">
                            <Button type="submit" label="Envoyer la réponse" icon="pi pi-send" :loading="replyForm.processing" :disabled="!replyForm.message.trim()" />
                        </div>
                    </form>
                </PageCard>
                <div v-else class="page-card text-center text-sm text-slate-500 dark:text-slate-400">
                    <i class="pi pi-lock mr-2" aria-hidden="true"></i>Ce ticket est fermé.
                    <Link :href="route('student.support.create')" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline ml-1">Ouvrir un nouveau ticket</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
