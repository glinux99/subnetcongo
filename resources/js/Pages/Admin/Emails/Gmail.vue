<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    emails: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ page: 1, per_page: 20, total: 0, total_pages: 1 }) },
    folders: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    account: { type: String, default: '' },
    error: { type: String, default: null },
});

const search = ref(props.filters.search || '');
const currentFolder = ref(props.filters.folder || 'INBOX');
const selectedEmails = ref([]);
const selectAll = ref(false);
const searchQuery = ref('');
const isLoading = ref(false);
const showAdvancedSearch = ref(false);

// Watch for changes in search
watch(search, (newVal) => {
    if (newVal === '') {
        applyFilters();
    }
});

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('admin.emails.index'), {
        search: search.value || undefined,
        folder: currentFolder.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
};

const onPage = (event) => {
    isLoading.value = true;
    router.get(route('admin.emails.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: search.value || undefined,
        folder: currentFolder.value || undefined,
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
        const now = new Date();
        const diff = now - date;

        if (diff < 86400000 && date.getDate() === now.getDate()) {
            return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
        if (diff < 604800000) {
            return date.toLocaleDateString('fr-FR', { weekday: 'short', hour: '2-digit', minute: '2-digit' });
        }
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
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

const getFolderIcon = (name) => {
    const lower = (name || '').toLowerCase();
    if (lower.includes('inbox') || lower.includes('réception')) return 'pi pi-inbox';
    if (lower.includes('sent') || lower.includes('envoyés')) return 'pi pi-send';
    if (lower.includes('draft') || lower.includes('brouillon')) return 'pi pi-file';
    if (lower.includes('trash') || lower.includes('corbeille')) return 'pi pi-trash';
    if (lower.includes('spam') || lower.includes('indésirable')) return 'pi pi-exclamation-triangle';
    if (lower.includes('archive')) return 'pi pi-box';
    return 'pi pi-folder';
};

// Email actions
const markAsRead = (messageNumber) => {
    router.post(route('admin.emails.markAsRead', messageNumber), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const email = props.emails.find(e => e.message_number === messageNumber);
            if (email) email.seen = true;
        }
    });
};

const markAsUnread = (messageNumber) => {
    router.post(route('admin.emails.markAsUnread', messageNumber), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const email = props.emails.find(e => e.message_number === messageNumber);
            if (email) email.seen = false;
        }
    });
};

const confirmAction = useConfirmAction();
const moveToTrash = (messageNumber) => {
    confirmAction({
        message: 'Déplacer cet email vers la corbeille ?',
        acceptLabel: 'Mettre à la corbeille',
        danger: true,
        accept: () => router.post(route('admin.emails.trash', messageNumber), {}, { preserveScroll: true }),
    });
};

const archive = (messageNumber) => {
    router.post(route('admin.emails.archive', messageNumber), {}, {
        preserveScroll: true,
        onSuccess: () => {
            applyFilters();
        }
    });
};

const toggleSelect = (messageNumber) => {
    const index = selectedEmails.value.indexOf(messageNumber);
    if (index > -1) {
        selectedEmails.value.splice(index, 1);
    } else {
        selectedEmails.value.push(messageNumber);
    }
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedEmails.value = props.emails.map(e => e.message_number);
    } else {
        selectedEmails.value = [];
    }
};

const bulkAction = (action) => {
    if (selectedEmails.value.length === 0) return;

    const actions = {
        'read': () => selectedEmails.value.forEach(id => markAsRead(id)),
        'unread': () => selectedEmails.value.forEach(id => markAsUnread(id)),
        'trash': () => {
            confirmAction({
                message: `Déplacer ${selectedEmails.value.length} email(s) vers la corbeille ?`,
                acceptLabel: 'Supprimer',
                danger: true,
                accept: () => {
                    selectedEmails.value.forEach(id => moveToTrash(id));
                    selectedEmails.value = [];
                }
            });
        },
        'archive': () => {
            selectedEmails.value.forEach(id => archive(id));
            selectedEmails.value = [];
        }
    };

    if (actions[action]) {
        actions[action]();
    }
};

const getUnreadCount = (folder) => {
    return folder.unread || 0;
};

const isFolderActive = (folder) => {
    return currentFolder.value === folder.short;
};

const folderStats = computed(() => {
    const stats = {};
    props.folders.forEach(folder => {
        stats[folder.short] = getUnreadCount(folder);
    });
    return stats;
});
</script>

<template>
    <AppLayout>
        <Head title="Boîte email" />
        
        <div class="gmail-container min-h-screen bg-gray-50 font-sans">
            
            <!-- Header Gmail-like -->
            <div class="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-50">
                <div class="flex items-center justify-between max-w-full">
                    <!-- Logo et titre -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                            <i class="pi pi-envelope text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">Gmail</h1>
                            <p class="text-xs text-gray-500">{{ account }}</p>
                        </div>
                    </div>

                    <!-- Barre de recherche -->
                    <div class="flex-1 max-w-2xl mx-8">
                        <div class="relative">
                            <i class="pi pi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <InputText
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Rechercher dans la boîte mail"
                                class="w-full pl-10 pr-4 py-2 rounded-full border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                            <Button
                                icon="pi pi-times"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600"
                                @click="search = ''; applyFilters()"
                                v-if="search"
                                text
                                rounded
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <Button
                            icon="pi pi-refresh"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-full"
                            @click="applyFilters"
                            :loading="isLoading"
                            text
                            rounded
                        />
                        <Button
                            icon="pi pi-pen-to-square"
                            label="Nouveau message"
                            class="bg-blue-600 hover:bg-blue-700 text-white rounded-full px-4 py-2"
                            @click="router.visit(route('admin.emails.compose'))"
                        />
                    </div>
                </div>
            </div>

            <div class="flex h-[calc(100vh-80px)]">
                <!-- Sidebar Gmail-style -->
                <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
                    <!-- Compose button -->
                    <div class="p-4">
                        <Button
                            icon="pi pi-pen-to-square"
                            label="Nouveau message"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 font-medium"
                            @click="router.visit(route('admin.emails.compose'))"
                        />
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-2 space-y-1">
                        <button
                            v-for="folder in folders"
                            :key="folder.path"
                            @click="currentFolder = folder.short; applyFilters()"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-all duration-200 font-medium"
                            :class="isFolderActive(folder) 
                                ? 'bg-blue-50 text-blue-700 border-r-4 border-blue-600' 
                                : 'text-gray-700 hover:bg-gray-100'"
                        >
                            <div class="flex items-center gap-3">
                                <i :class="getFolderIcon(folder.name)" class="text-lg"></i>
                                <span class="truncate">{{ folder.name }}</span>
                            </div>
                            <span 
                                v-if="getUnreadCount(folder) > 0"
                                class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"
                            >
                                {{ getUnreadCount(folder) }}
                            </span>
                        </button>
                    </nav>

                    <!-- Stats -->
                    <div class="p-4 border-t border-gray-200">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total</span>
                                <span class="font-medium">{{ stats.total ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Non lus</span>
                                <span class="font-medium text-red-600">{{ stats.unread ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Quota</span>
                                <span class="font-medium">{{ stats.quota?.percent ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content area -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Toolbar -->
                    <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="selectAll"
                                @change="toggleSelectAll"
                                :binary="true"
                            />
                            <div class="flex items-center gap-1" v-if="selectedEmails.length > 0">
                                <Button
                                    icon="pi pi-check"
                                    class="p-1 text-gray-600 hover:bg-gray-100 rounded"
                                    @click="bulkAction('read')"
                                    title="Marquer comme lu"
                                    text
                                    rounded
                                />
                                <Button
                                    icon="pi pi-envelope"
                                    class="p-1 text-gray-600 hover:bg-gray-100 rounded"
                                    @click="bulkAction('unread')"
                                    title="Marquer comme non lu"
                                    text
                                    rounded
                                />
                                <Button
                                    icon="pi pi-box"
                                    class="p-1 text-gray-600 hover:bg-gray-100 rounded"
                                    @click="bulkAction('archive')"
                                    title="Archiver"
                                    text
                                    rounded
                                />
                                <Button
                                    icon="pi pi-trash"
                                    class="p-1 text-gray-600 hover:bg-gray-100 rounded hover:text-red-600"
                                    @click="bulkAction('trash')"
                                    title="Supprimer"
                                    text
                                    rounded
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">
                                {{ emails.length }} email(s)
                            </span>
                        </div>
                    </div>

                    <!-- Email list -->
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="emails.length" class="divide-y divide-gray-100">
                            <div
                                v-for="email in emails"
                                :key="email.message_number"
                                class="group hover:bg-gray-50 transition-all duration-200 cursor-pointer"
                                :class="{
                                    'bg-blue-50/30': !email.seen,
                                    'bg-gray-50': selectedEmails.includes(email.message_number)
                                }"
                                @click="router.visit(route('admin.emails.show', email.message_number))"
                            >
                                <div class="flex items-center p-4 gap-3">
                                    <!-- Checkbox -->
                                    <div class="flex items-center" @click.stop>
                                        <Checkbox
                                            :modelValue="selectedEmails.includes(email.message_number)"
                                            @change="toggleSelect(email.message_number)"
                                            :binary="true"
                                        />
                                    </div>

                                    <!-- Avatar -->
                                    <div class="relative shrink-0">
                                        <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold', getAvatarColor(getFromName(email.from))]">
                                            {{ getInitials(getFromName(email.from)) }}
                                        </div>
                                        <span v-if="!email.seen" class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 border-2 border-white rounded-full"></span>
                                    </div>

                                    <!-- Email content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate text-sm" :class="!email.seen ? 'font-bold text-gray-900' : 'font-medium text-gray-700'">
                                                    {{ getFromName(email.from) }}
                                                </p>
                                                <span v-if="email.flagged" class="text-yellow-500">
                                                    <i class="pi pi-star-fill text-xs"></i>
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-500 shrink-0 ml-2">
                                                {{ formatDate(email.date) }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm truncate" :class="!email.seen ? 'text-gray-800 font-medium' : 'text-gray-600'">
                                                {{ email.subject || '(Sans objet)' }}
                                            </p>
                                            <span v-if="email.has_attachments" class="text-gray-400 text-xs">
                                                <i class="pi pi-paperclip"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Quick actions -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                        <Button
                                            icon="pi pi-reply"
                                            class="p-1 text-gray-400 hover:text-blue-600"
                                            @click="router.visit(route('admin.emails.reply', email.message_number))"
                                            title="Répondre"
                                            text
                                            rounded
                                        />
                                        <Button
                                            icon="pi pi-box"
                                            class="p-1 text-gray-400 hover:text-green-600"
                                            @click="archive(email.message_number)"
                                            title="Archiver"
                                            text
                                            rounded
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-1 text-gray-400 hover:text-red-600"
                                            @click="moveToTrash(email.message_number)"
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
                            <i class="pi pi-inbox text-6xl mb-4"></i>
                            <p class="text-lg font-medium">Aucun email</p>
                            <p class="text-sm">Votre boîte de réception est vide</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white border-t border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between">
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
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.gmail-container {
    font-family: 'Google Sans', Roboto, Arial, sans-serif;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Animations */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from {
    transform: translateX(-100%);
}

.slide-leave-to {
    transform: translateX(100%);
}
</style>
