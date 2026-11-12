<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    message: { type: Object, required: true },
});

const replyForm = useForm({ reply_message: props.message.reply_message || '' });

const submitReply = () => replyForm.post(route('admin.messages.reply', props.message.id), { preserveScroll: true });
const archive = () => router.post(route('admin.messages.archive', props.message.id));
const confirmAction = useConfirmAction();
const destroyMsg = () => {
    confirmAction({
        header: t('pages.admin_contact_message.confirm_delete_header'),
        message: t('pages.admin_contact_message.confirm_delete_message'),
        acceptLabel: t('pages.admin_contact_message.btn_delete'),
        danger: true,
        accept: () => router.delete(route('admin.messages.destroy', props.message.id)),
    });
};

const statusLabel = (s) => ({
    new: t('pages.admin_contact_message.status_new'),
    read: t('pages.admin_contact_message.status_read'),
    replied: t('pages.admin_contact_message.status_replied'),
    archived: t('pages.admin_contact_message.status_archived'),
    spam: t('pages.admin_contact_message.status_spam'),
}[s] || s);

// Correction pour utiliser les Severities natives de PrimeVue
const statusSeverity = (s) => ({ 
    new: 'danger', 
    read: 'info', 
    replied: 'success', 
    archived: 'secondary', 
    spam: 'warning' 
}[s] || 'secondary');

const formatDate = (iso) => { 
    if(!iso) return ''; 
    try { 
        return new Date(iso).toLocaleString(locale.value, { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); 
    } catch(_) { return iso; } 
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_contact_message.head_title', { ticket: message.ticket_number })" />
        
        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="`${t('pages.admin_contact_message.hero_title_1')} ${t('pages.admin_contact_message.hero_title_2')}`"
                :subtitle="`${t('pages.admin_contact_message.subject_label')} ${message.subject}`"
                :badge="t('pages.admin_contact_message.ticket_badge', { ticket: message.ticket_number })"
                icon="pi-envelope"
                accent="blue"
                :actions="[{ label: t('pages.admin_contact_message.back_to_inbox'), icon: 'pi-arrow-left', variant: 'secondary', href: route('admin.messages.index') }]"
            >
                <template #meta>
                    <div class="mt-3">
                        <Tag :value="statusLabel(message.status)" :severity="statusSeverity(message.status)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                    </div>
                </template>
            </PageHeader>

            <div class="page-container page-section">

                <!-- CARTES KPI / EXPÉDITEUR -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <PageKpi :label="t('pages.admin_contact_message.kpi_sender')" :value="message.name" icon="pi-user" color="blue" />
                    <a :href="`mailto:${message.email}`" class="block hover:no-underline">
                        <PageKpi :label="t('pages.admin_contact_message.kpi_email')" :value="message.email" icon="pi-envelope" color="sky" />
                    </a>
                    <PageKpi :label="t('pages.admin_contact_message.kpi_phone')" :value="message.phone || t('pages.admin_contact_message.phone_empty')" icon="pi-phone" color="red" />
                </div>

                <!-- ============================================================================== -->
                <!-- CONTENU DU MESSAGE ET RÉPONSE                                                  -->
                <!-- ============================================================================== -->
                <div class="space-y-6">
                    
                    <!-- Message Reçu -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="bg-slate-50 border-b border-slate-100 p-4 md:p-6 flex justify-between items-center">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-600 flex items-center gap-2">
                                <i class="pi pi-align-left text-blue-400"></i> {{ t('pages.admin_contact_message.content_title') }}
                            </h3>
                            <span class="text-xs font-bold text-slate-400 bg-white px-3 py-1 rounded-md border border-slate-200 shadow-sm">
                                {{ t('pages.admin_contact_message.received_at', { date: formatDate(message.created_at) }) }}
                            </span>
                        </div>
                        <div class="p-6 md:p-8">
                            <pre class="whitespace-pre-wrap font-sans text-[15px] text-slate-700 leading-relaxed">{{ message.message }}</pre>
                            
                            <!-- Méta-données discrètes -->
                            <div class="mt-8 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                <span><i class="pi pi-wifi mr-1"></i> IP : {{ message.ip_address || '—' }}</span>
                                <span><i class="pi pi-link mr-1"></i> {{ t('pages.admin_contact_message.meta_source') }} {{ message.source || '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Si déjà répondu -->
                    <div v-if="message.status === 'replied' && message.reply_message" class="bg-blue-50 rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                        <div class="bg-blue-100/50 border-b border-blue-200 p-4 md:p-6 flex justify-between items-center">
                            <h3 class="text-sm font-black uppercase tracking-widest text-blue-800 flex items-center gap-2">
                                <i class="pi pi-reply text-blue-600"></i> {{ t('pages.admin_contact_message.reply_title') }}
                            </h3>
                            <span class="text-xs font-bold text-blue-600 bg-white px-3 py-1 rounded-md shadow-sm">
                                {{ t('pages.admin_contact_message.sent_at', { date: formatDate(message.replied_at) }) }}
                            </span>
                        </div>
                        <div class="p-6 md:p-8">
                            <pre class="whitespace-pre-wrap font-sans text-[15px] text-blue-900 leading-relaxed">{{ message.reply_message }}</pre>
                        </div>
                    </div>

                    <!-- Formulaire de réponse -->
                    <div v-if="message.status !== 'archived' && message.status !== 'replied'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="bg-slate-50 border-b border-slate-100 p-4 md:p-6">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-600 flex items-center gap-2">
                                <i class="pi pi-send text-blue-400"></i> {{ t('pages.admin_contact_message.reply_form_title') }}
                            </h3>
                        </div>
                        <div class="p-6 md:p-8">
                            <form @submit.prevent="submitReply">
                                <Textarea 
                                    v-model="replyForm.reply_message" 
                                    rows="8" 
                                    class="w-full !rounded-xl !bg-slate-50 border-slate-200 text-slate-700 text-[15px] leading-relaxed p-4" 
                                    :placeholder="t('pages.admin_contact_message.reply_placeholder')"
                                />
                                <p v-if="replyForm.errors.reply_message" class="text-red-500 text-xs font-bold mt-2"><i class="pi pi-exclamation-triangle"></i> {{ replyForm.errors.reply_message }}</p>
                                
                                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-100">
                                    <div class="flex gap-2 w-full sm:w-auto">
                                        <Button type="button" :label="t('pages.admin_contact_message.btn_archive')" icon="pi pi-folder" outlined @click="archive" class="!rounded-xl text-slate-600 border-slate-300 hover:bg-slate-50 w-full sm:w-auto font-bold" />
                                        <Button type="button" :label="t('pages.admin_contact_message.btn_delete')" icon="pi pi-trash" outlined @click="destroyMsg" class="!rounded-xl text-red-500 border-red-200 hover:bg-red-50 w-full sm:w-auto font-bold" />
                                    </div>
                                    <Button type="submit" :label="t('pages.admin_contact_message.btn_send_reply')" icon="pi pi-paper-plane" :loading="replyForm.processing" class="!bg-[#EF4444] hover:!bg-red-600 !border-none !rounded-xl font-bold px-8 py-3 w-full sm:w-auto shadow-md shadow-red-500/20" />
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Si archivé, juste l'option supprimer -->
                    <div v-else class="flex justify-end pt-4">
                        <Button :label="t('pages.admin_contact_message.btn_delete_final')" icon="pi pi-trash" outlined @click="destroyMsg" class="!rounded-xl text-red-500 border-red-200 hover:bg-red-50 font-bold" />
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Overrides UI Forms PrimeVue */
:deep(.p-textarea) {
    border-radius: 0.75rem;
    transition: all 0.2s;
}
:deep(.p-textarea:enabled:focus) {
    border-color: #EF4444;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
    background-color: #ffffff !important;
}
</style>