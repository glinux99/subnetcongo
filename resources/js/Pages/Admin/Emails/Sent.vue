<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    emails: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ page: 1, per_page: 20, total: 0, total_pages: 1 }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || 'all');
const isLoading = ref(false);

const statusOptions = computed(() => [
    { label: t('pages.admin_emails_sent.filter_all'), value: 'all' },
    { label: t('pages.admin_emails_sent.status_sent_plural'), value: 'sent' },
    { label: t('pages.admin_emails_sent.status_failed_plural'), value: 'failed' },
    { label: t('pages.admin_emails_sent.status_draft_plural'), value: 'draft' },
]);

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('admin.emails.sent'), {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

const onPage = (event) => {
    isLoading.value = true;
    router.get(route('admin.emails.sent'), {
        page: event.page + 1,
        per_page: event.rows,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

const formatDate = (iso) => {
    if (!iso) return '';
    try {
        const date = new Date(iso);
        return date.toLocaleString(locale.value, {
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
        'sent': t('pages.admin_emails_sent.status_sent'),
        'failed': t('pages.admin_emails_sent.status_failed'),
        'draft': t('pages.admin_emails_sent.status_draft'),
    };
    return labels[status] || status;
};

const getRecipients = (to) => {
    if (!to || !Array.isArray(to)) return '';
    return to.join(', ');
};

const confirmAction = useConfirmAction();
const deleteEmail = (id) => {
    confirmAction({
        message: t('pages.admin_emails_sent.confirm_delete_message'),
        acceptLabel: t('pages.admin_emails_sent.btn_delete'),
        danger: true,
        accept: () => router.delete(route('admin.emails.sent.delete', id), {
            preserveScroll: true,
            onSuccess: () => applyFilters()
        })
    });
};

const openEmail = (email) => {
    if (email.imap) {
        router.visit(route('admin.emails.sent.imap', email.message_number));
    } else {
        router.visit(route('admin.emails.sent.show', email.id));
    }
};

const resendEmail = (email) => {
    router.post(route('admin.emails.send'), {
        to: (email.to || []).join(', '),
        subject: email.subject,
        body: typeof email.body === 'string' ? email.body : '',
    }, {
        preserveScroll: true,
        onSuccess: () => applyFilters()
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.admin_emails_sent.page_title')" />
        
        <div class="min-h-screen bg-slate-50 font-sans pb-20">
            <!-- PageHeader -->
            <PageHeader
                :title="t('pages.admin_emails_sent.page_title')"
                :subtitle="t('pages.admin_emails_sent.subtitle')"
                :badge="t('pages.admin_emails_sent.badge')"
                icon="pi-send"
                accent="blue"
                :actions="[
                    { label: t('pages.admin_emails_sent.btn_back'), icon: 'pi-arrow-left', href: route('admin.emails.index'), variant: 'secondary' },
                    { label: t('pages.admin_emails_sent.btn_refresh'), icon: 'pi-refresh', onClick: applyFilters, variant: 'secondary' }
                ]"
            />

            <div class="max-w-[1600px] mx-auto px-4 md:px-6 pt-6">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <PageKpi
                        :label="t('pages.admin_emails_sent.kpi_total')"
                        :value="stats.total ?? 0"
                        icon="pi-envelope"
                        color="blue"
                    />
                    <PageKpi
                        :label="t('pages.admin_emails_sent.kpi_sent')"
                        :value="stats.sent ?? 0"
                        icon="pi-check-circle"
                        color="emerald"
                    />
                    <PageKpi
                        :label="t('pages.admin_emails_sent.kpi_failed')"
                        :value="stats.failed ?? 0"
                        icon="pi-times-circle"
                        color="red"
                    />
                    <PageKpi
                        :label="t('pages.admin_emails_sent.kpi_drafts')"
                        :value="stats.drafts ?? 0"
                        icon="pi-file"
                        color="amber"
                    />
                </div>

                <!-- Filtre -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <div class="flex flex-col md:flex-row gap-3">
                        <Select
                            v-model="statusFilter"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            @change="applyFilters"
                            class="!rounded-xl min-w-[200px] border-slate-200"
                            :placeholder="t('pages.admin_emails_sent.filter_placeholder')"
                        />
                    </div>
                </div>

                <!-- Email list -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div v-if="emails.length" class="divide-y divide-slate-100">
                        <div
                            v-for="email in emails"
                            :key="email.id ?? 'imap-' + email.message_number"
                            class="group hover:bg-slate-50 transition-all duration-200 cursor-pointer"
                            @click="openEmail(email)"
                        >
                            <div class="flex items-center p-4 gap-4">
                                <!-- Status icon -->
                                <div class="shrink-0">
                                    <i 
                                        :class="{
                                            'pi pi-check-circle text-green-500': email.status === 'sent',
                                            'pi pi-times-circle text-red-500': email.status === 'failed',
                                            'pi pi-file text-yellow-500': email.status === 'draft',
                                        }"
                                        class="text-xl"
                                    ></i>
                                </div>

                                <!-- Email content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ getRecipients(email.to) }}
                                        </p>
                                        <span class="text-xs text-slate-500 shrink-0 ml-2">
                                            {{ formatDate(email.sent_at || email.created_at) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm text-slate-700 truncate font-medium">
                                            {{ email.subject || t('pages.admin_emails_sent.no_subject') }}
                                        </p>
                                        <span 
                                            :class="getStatusBadge(email.status)"
                                            class="text-xs px-2 py-0.5 rounded-full font-medium"
                                        >
                                            {{ getStatusLabel(email.status) }}
                                        </span>
                                        <span v-if="email.sender_name || email.sender_photo" class="text-xs text-slate-400 truncate flex items-center gap-1">
                                            ·
                                            <img v-if="email.sender_photo" :src="email.sender_photo" :alt="email.sender_name" class="w-4 h-4 rounded-full object-cover" />
                                            <template v-if="email.sender_name">{{ t('pages.admin_emails_sent.by_sender', { name: email.sender_name }) }}</template>
                                        </span>
                                        <span v-if="email.imap" class="text-xs text-slate-400">
                                            · IMAP
                                        </span>
                                    </div>
                                </div>

                                <!-- Quick actions -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                    <Button
                                        v-if="email.status === 'failed'"
                                        icon="pi pi-refresh"
                                        class="p-1 text-slate-400 hover:text-blue-600"
                                        @click="resendEmail(email)"
                                        :title="t('pages.admin_emails_sent.title_resend')"
                                        text
                                        rounded
                                    />
                                    <Button
                                        v-if="!email.imap"
                                        icon="pi pi-trash"
                                        class="p-1 text-slate-400 hover:text-red-600"
                                        @click="deleteEmail(email.id)"
                                        :title="t('pages.admin_emails_sent.title_delete')"
                                        text
                                        rounded
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="flex flex-col items-center justify-center h-64 text-slate-500">
                        <i class="pi pi-send text-6xl mb-4"></i>
                        <p class="text-lg font-medium">{{ t('pages.admin_emails_sent.empty_title') }}</p>
                        <p class="text-sm">{{ t('pages.admin_emails_sent.empty_subtitle') }}</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4 bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            {{ t('pages.admin_emails_sent.page_of', { page: pagination.page, total: pagination.total_pages }) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                icon="pi pi-chevron-left"
                                class="p-2"
                                :disabled="pagination.page <= 1"
                                @click="onPage({ page: pagination.page - 2, rows: pagination.per_page })"
                                text
                                rounded
                            />
                            <Button
                                icon="pi pi-chevron-right"
                                class="p-2"
                                :disabled="pagination.page >= pagination.total_pages"
                                @click="onPage({ page: pagination.page, rows: pagination.per_page })"
                                text
                                rounded
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.gmail-sent-container {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
}
</style>
