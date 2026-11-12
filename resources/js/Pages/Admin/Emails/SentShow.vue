<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    email: { type: Object, default: () => ({}) },
});

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

const getStatusBadge = (status) => {
    const badges = {
        'sent': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'draft': 'bg-yellow-100 text-yellow-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        'sent': t('pages.admin_email_sent_show.status_sent'),
        'failed': t('pages.admin_email_sent_show.status_failed'),
        'draft': t('pages.admin_email_sent_show.status_draft'),
    };
    return labels[status] || status;
};

const goBack = () => {
    router.visit(route('admin.emails.sent'));
};

const resendEmail = () => {
    router.post(route('admin.emails.send'), {
        to: props.email.to.join(', '),
        subject: props.email.subject,
        body: props.email.body,
    }, {
        preserveScroll: true,
        onSuccess: () => goBack()
    });
};

const headerActions = computed(() => {
    const actions = [
        { label: t('pages.admin_email_sent_show.btn_back'), icon: 'pi-arrow-left', onClick: goBack, variant: 'secondary' },
    ];
    if (props.email?.status === 'failed') {
        actions.push({ label: t('pages.admin_email_sent_show.btn_resend'), icon: 'pi-refresh', onClick: resendEmail, variant: 'primary' });
    }
    return actions;
});
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_email_sent_show.page_title')" />
        
        <div class="min-h-screen bg-slate-50">
            <!-- PageHeader -->
            <PageHeader
                :title="email.subject || t('pages.admin_email_sent_show.no_subject')"
                :subtitle="t('pages.admin_email_sent_show.subtitle', { to: email.to.join(', '), date: formatDate(email.sent_at || email.created_at) })"
                :badge="t('pages.admin_email_sent_show.badge')"
                icon="pi-send"
                accent="blue"
                :actions="headerActions"
            />

            <!-- Email content -->
            <div class="max-w-4xl mx-auto p-4">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- Email header -->
                    <div class="p-6 border-b border-slate-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-3 mb-4">
                                    <span :class="getStatusBadge(email.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                                        {{ getStatusLabel(email.status) }}
                                    </span>
                                    <span class="text-sm text-slate-500">
                                        {{ formatDate(email.sent_at || email.created_at) }}
                                    </span>
                                    <span v-if="email.sender_name || email.sender_photo" class="flex items-center gap-1.5 text-sm text-slate-500">
                                        <img v-if="email.sender_photo" :src="email.sender_photo" :alt="email.sender_name" class="w-6 h-6 rounded-full object-cover" />
                                        <template v-if="email.sender_name">{{ t('pages.admin_email_sent_show.by_sender', { name: email.sender_name }) }}</template>
                                    </span>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_email_sent_show.label_to') }}</span>
                                        <span class="text-sm text-slate-900">{{ email.to.join(', ') }}</span>
                                    </div>
                                    <div v-if="email.cc && email.cc.length" class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_email_sent_show.label_cc') }}</span>
                                        <span class="text-sm text-slate-900">{{ email.cc.join(', ') }}</span>
                                    </div>
                                    <div v-if="email.bcc && email.bcc.length" class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-700 w-16">{{ t('pages.admin_email_sent_show.label_bcc') }}</span>
                                        <span class="text-sm text-slate-900">{{ email.bcc.join(', ') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email body -->
                    <div class="p-6">
                        <div class="prose max-w-none" v-html="email.body"></div>
                    </div>

                    <!-- Attachments -->
                    <div v-if="email.attachments && email.attachments.length" class="p-6 border-t border-slate-100">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">{{ t('pages.admin_email_sent_show.attachments_title', { count: email.attachments.length }) }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div
                                v-for="(attachment, index) in email.attachments"
                                :key="index"
                                class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg"
                            >
                                <i class="pi pi-file text-slate-400 text-xl"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ attachment.name }}</p>
                                    <p class="text-xs text-slate-500">{{ attachment.size }} o</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error message -->
                    <div v-if="email.status === 'failed' && email.error_message" class="p-6 border-t border-red-100 bg-red-50">
                        <div class="flex items-start gap-3">
                            <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
                            <div>
                                <h4 class="font-medium text-red-800">{{ t('pages.admin_email_sent_show.send_error_title') }}</h4>
                                <p class="text-sm text-red-700 mt-1">{{ email.error_message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.gmail-sent-show-container {
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
</style>
