<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import PageCard from '@/Components/PageCard.vue';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    ticket: { type: Object, required: true },
});

const replyForm = useForm({ message: '' });
const submitReply = () => {
    replyForm.post(route('student.support.reply', props.ticket.ticket_number), { preserveScroll: true, onSuccess: () => replyForm.reset() });
};
const confirmAction = useConfirmAction();
const closeTicket = () => {
    confirmAction({
        header: t('pages.support_ticket_show.confirmHeader'),
        message: t('pages.support_ticket_show.confirmMessage'),
        acceptLabel: t('pages.support_ticket_show.confirmAccept'),
        accept: () => router.post(route('student.support.close', props.ticket.ticket_number)),
    });
};

const statusLabel = (s) => ({ open:t('pages.support_ticket_show.stOpen'), new:t('pages.support_ticket_show.stNew'), pending:t('pages.support_ticket_show.stPending'), in_progress:t('pages.support_ticket_show.stInProgress'), resolved:t('pages.support_ticket_show.stResolved'), closed:t('pages.support_ticket_show.stClosed') }[s] || s);
const statusSeverity = (s) => ({ open:'info', new:'info', pending:'warn', in_progress:'info', resolved:'success', closed:'secondary' }[s] || 'secondary');
const priorityLabel = (p) => ({ low:t('pages.support_ticket_show.prioLow'), medium:t('pages.support_ticket_show.prioMedium'), high:t('pages.support_ticket_show.prioHigh'), urgent:t('pages.support_ticket_show.prioUrgent') }[p] || p);
const prioritySeverity = (p) => ({ urgent:'danger', high:'warn', medium:'info', low:'success' }[p] || 'secondary');
const formatDate = (iso) => { if(!iso) return ''; try { return new Date(iso).toLocaleString(locale.value, { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(_) { return iso; } };
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.support_ticket_show.headTitle', { number: ticket.ticket_number })" />
        <div class="page-bg min-h-screen">
            <header class="page-header">
                <div class="page-header-orb-1" aria-hidden="true"></div>
                <div class="max-w-[1000px] mx-auto relative z-10">
                    <Link :href="route('student.support.index')" class="inline-flex items-center gap-2 text-sm text-slate-300 hover:text-white mb-5 rounded">
                        <i class="pi pi-arrow-left text-xs" aria-hidden="true"></i> {{ t('pages.support_ticket_show.backLink') }}
                    </Link>
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-slate-400 text-xs font-mono">{{ ticket.ticket_number }}</p>
                            <h1 class="page-header-title mt-1 break-words">{{ ticket.subject }}</h1>
                            <div class="flex items-center gap-2 mt-3 flex-wrap">
                                <Tag :value="statusLabel(ticket.status)" :severity="statusSeverity(ticket.status)" />
                                <Tag :value="t('pages.support_ticket_show.priorityTag', { priority: priorityLabel(ticket.priority).toLowerCase() })" :severity="prioritySeverity(ticket.priority)" />
                                <Tag v-if="ticket.category" :value="ticket.category.name" :style="{ backgroundColor: ticket.category.color }" class="!text-white" />
                            </div>
                        </div>
                        <button v-if="ticket.status !== 'closed'" type="button" class="btn-header-secondary" @click="closeTicket">
                            <i class="pi pi-check-circle" aria-hidden="true"></i> {{ t('pages.support_ticket_show.closeBtn') }}
                        </button>
                    </div>
                </div>
            </header>

            <div class="max-w-[1000px] mx-auto px-4 md:px-6 -mt-16 relative z-20 pb-20 space-y-6">
                <PageCard :title="t('pages.support_ticket_show.conversationTitle')">
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

                <PageCard v-if="ticket.status !== 'closed'" :title="t('pages.support_ticket_show.replyTitle')" :subtitle="t('pages.support_ticket_show.replySubtitle')">
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <label for="ticket-reply" class="sr-only">{{ t('pages.support_ticket_show.replyLabel') }}</label>
                        <Textarea
                            id="ticket-reply"
                            v-model="replyForm.message"
                            rows="4"
                            autoResize
                            class="w-full"
                            :placeholder="t('pages.support_ticket_show.replyPlaceholder')"
                            :invalid="!!replyForm.errors.message"
                            :aria-describedby="replyForm.errors.message ? 'ticket-reply-error' : undefined"
                        />
                        <p v-if="replyForm.errors.message" id="ticket-reply-error" class="text-red-600 dark:text-red-400 text-xs" role="alert">{{ replyForm.errors.message }}</p>
                        <div class="flex justify-end">
                            <Button type="submit" :label="t('pages.support_ticket_show.sendReply')" icon="pi pi-send" :loading="replyForm.processing" :disabled="!replyForm.message.trim()" />
                        </div>
                    </form>
                </PageCard>
                <div v-else class="page-card text-center text-sm text-slate-500 dark:text-slate-400">
                    <i class="pi pi-lock mr-2" aria-hidden="true"></i>{{ t('pages.support_ticket_show.closedNote') }}
                    <Link :href="route('student.support.create')" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline ml-1">{{ t('pages.support_ticket_show.newTicketLink') }}</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
