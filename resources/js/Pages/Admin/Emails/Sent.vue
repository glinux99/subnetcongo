<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { ref, computed } from 'vue';

const props = defineProps({
    emails: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ page: 1, per_page: 20, total: 0, total_pages: 1 }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || 'all');
const isLoading = ref(false);

const statusOptions = [
    { label: 'Tous', value: 'all' },
    { label: 'Envoyés', value: 'sent' },
    { label: 'Échoués', value: 'failed' },
    { label: 'Brouillons', value: 'draft' },
];

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
        'sent': 'Envoyé',
        'failed': 'Échoué',
        'draft': 'Brouillon',
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
        message: 'Supprimer cet email ?',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('admin.emails.sent.delete', id), {
            preserveScroll: true,
            onSuccess: () => applyFilters()
        })
    });
};

const resendEmail = (email) => {
    router.post(route('admin.emails.send'), {
        to: email.to.join(', '),
        subject: email.subject,
        body: email.body,
    }, {
        preserveScroll: true,
        onSuccess: () => applyFilters()
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Emails envoyés" />
        
        <div class="gmail-sent-container min-h-screen bg-gray-50">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Button
                            icon="pi pi-arrow-left"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-full"
                            @click="router.visit(route('admin.emails.index'))"
                            text
                            rounded
                        />
                        <div>
                            <h1 class="text-lg font-medium text-gray-800">Emails envoyés</h1>
                            <p class="text-sm text-gray-500">Historique de vos envois</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Select
                            v-model="statusFilter"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            @change="applyFilters"
                            class="w-40"
                            placeholder="Filtrer par statut"
                        />
                        <Button
                            icon="pi pi-refresh"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-full"
                            @click="applyFilters"
                            :loading="isLoading"
                            text
                            rounded
                        />
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="max-w-6xl mx-auto p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="pi pi-envelope text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total</p>
                                <p class="text-xl font-bold text-gray-900">{{ stats.total ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="pi pi-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Envoyés</p>
                                <p class="text-xl font-bold text-gray-900">{{ stats.sent ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="pi pi-times-circle text-red-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Échoués</p>
                                <p class="text-xl font-bold text-gray-900">{{ stats.failed ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="pi pi-file text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Brouillons</p>
                                <p class="text-xl font-bold text-gray-900">{{ stats.drafts ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email list -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div v-if="emails.length" class="divide-y divide-gray-100">
                        <div
                            v-for="email in emails"
                            :key="email.id"
                            class="group hover:bg-gray-50 transition-all duration-200 cursor-pointer"
                            @click="router.visit(route('admin.emails.sent.show', email.id))"
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
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ getRecipients(email.to) }}
                                        </p>
                                        <span class="text-xs text-gray-500 shrink-0 ml-2">
                                            {{ formatDate(email.sent_at || email.created_at) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm text-gray-700 truncate font-medium">
                                            {{ email.subject || '(Sans objet)' }}
                                        </p>
                                        <span 
                                            :class="getStatusBadge(email.status)"
                                            class="text-xs px-2 py-0.5 rounded-full font-medium"
                                        >
                                            {{ getStatusLabel(email.status) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Quick actions -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                    <Button
                                        v-if="email.status === 'failed'"
                                        icon="pi pi-refresh"
                                        class="p-1 text-gray-400 hover:text-blue-600"
                                        @click="resendEmail(email)"
                                        title="Renvoyer"
                                        text
                                        rounded
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-1 text-gray-400 hover:text-red-600"
                                        @click="deleteEmail(email.id)"
                                        title="Supprimer"
                                        text
                                        rounded
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="flex flex-col items-center justify-center h-64 text-gray-500">
                        <i class="pi pi-send text-6xl mb-4"></i>
                        <p class="text-lg font-medium">Aucun email envoyé</p>
                        <p class="text-sm">Vos emails envoyés apparaîtront ici</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Page {{ pagination.page }} sur {{ pagination.total_pages }}
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
    </AppLayout>
</template>

<style scoped>
.gmail-sent-container {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
}
</style>
