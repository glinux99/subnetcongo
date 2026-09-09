<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Paginator from 'primevue/paginator';
import { ref, computed } from 'vue';

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

const applyFilters = () => {
    router.get(route('admin.emails.index'), {
        search: search.value || undefined,
        folder: currentFolder.value || undefined,
    }, { preserveState: true, replace: true });
};

const onPage = (event) => {
    router.get(route('admin.emails.index'), {
        page: event.page + 1,
        per_page: event.rows,
        search: search.value || undefined,
        folder: currentFolder.value || undefined,
    }, { preserveState: true, replace: true });
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

// Générer des initiales pour l'avatar
const getInitials = (name) => {
    if (!name || name === 'Inconnu') return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0, 2).toUpperCase();
};

// Générer une couleur aléatoire mais fixe basée sur le nom
const getAvatarColor = (name) => {
    const colors = [
        'bg-rose-100 text-rose-700', 'bg-blue-100 text-blue-700', 
        'bg-green-100 text-green-700', 'bg-purple-100 text-purple-700', 
        'bg-amber-100 text-amber-700', 'bg-teal-100 text-teal-700'
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

const moveToTrash = (messageNumber) => {
    if (confirm('Déplacer cet email vers la corbeille ?')) {
        router.post(route('admin.emails.trash', messageNumber), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Boîte email" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <!-- Header avec bouton Nouveau Message -->
            <div class="bg-[#0f172a] pt-10 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-rose-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                            <i class="pi pi-envelope text-rose-400"></i>Boîte email
                        </h1>
                        <p class="text-slate-400 text-sm mt-1">{{ account }}</p>
                    </div>
                    <!-- Bouton pour composer (ajouter un email et des pièces jointes) -->
                    <Button 
                        label="Nouveau message" 
                        icon="pi pi-pen-to-square" 
                        class="!rounded-xl !bg-rose-500 !border-rose-500 hover:!bg-rose-600 shadow-lg shadow-rose-500/30"
                        @click="router.visit(route('admin.emails.compose'))" 
                    />
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <!-- Message d'erreur -->
                <div v-if="error" class="bg-rose-50 border border-rose-200 rounded-2xl p-6 mb-6 shadow-sm">
                    <div class="flex items-center gap-3 text-rose-700">
                        <i class="pi pi-exclamation-circle text-2xl"></i>
                        <div>
                            <p class="font-bold">Erreur de connexion IMAP</p>
                            <p class="text-sm text-rose-600">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="flex gap-6">
                    <!-- Sidebar: Dossiers -->
                    <div class="w-64 flex-shrink-0 hidden lg:block">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sticky top-6">
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Dossiers</h3>
                            <nav class="space-y-1">
                                <button
                                    v-for="folder in folders"
                                    :key="folder.path"
                                    @click="currentFolder = folder.short; applyFilters()"
                                    :class="[
                                        'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition',
                                        currentFolder === folder.short
                                            ? 'bg-rose-50 text-rose-700 font-bold'
                                            : 'text-slate-600 hover:bg-slate-50 font-medium'
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <i :class="[getFolderIcon(folder.name), currentFolder === folder.short ? 'text-rose-500' : 'text-slate-400']"></i>
                                        <span class="truncate">{{ folder.name }}</span>
                                    </div>
                                    <!-- Badge non lus par dossier (si dispo dans l'objet folder) -->
                                    <span v-if="folder.unread" class="bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ folder.unread }}
                                    </span>
                                </button>
                            </nav>

                            <!-- Statistiques -->
                            <div class="mt-6 pt-5 border-t border-slate-100">
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 mb-3">Statistiques</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Total</span>
                                        <span class="font-bold text-slate-700">{{ stats.total ?? 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500">Non lus</span>
                                        <span class="font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">{{ stats.unread ?? 0 }}</span>
                                    </div>
                                    <div v-if="stats.quota?.percent" class="mt-4">
                                        <div class="flex justify-between text-xs mb-1.5">
                                            <span class="text-slate-500 font-medium">Stockage utilisé</span>
                                            <span class="font-bold text-slate-700">{{ stats.quota.percent }}%</span>
                                        </div>
                                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                :class="['h-full rounded-full transition-all duration-500', stats.quota.percent > 90 ? 'bg-red-500' : 'bg-rose-400']"
                                                :style="{ width: Math.min(stats.quota.percent, 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu principal -->
                    <div class="flex-1 min-w-0">
                        <!-- Filtres & Recherche -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-3 mb-6">
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="flex-1 relative">
                                    <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <InputText
                                        v-model="search"
                                        @keyup.enter="applyFilters"
                                        placeholder="Rechercher un expéditeur, un objet..."
                                        class="!rounded-xl w-full !pl-10 !border-slate-200 hover:!border-rose-300 focus:!border-rose-500 focus:!ring-rose-500/20"
                                    />
                                </div>
                                <Select
                                    v-model="currentFolder"
                                    :options="folders"
                                    optionLabel="name"
                                    optionValue="short"
                                    @change="applyFilters"
                                    class="!rounded-xl min-w-[200px] !border-slate-200"
                                />
                                <Button label="Rechercher" @click="applyFilters" class="!rounded-xl !bg-slate-800 !border-slate-800 hover:!bg-slate-900" />
                            </div>
                        </div>

                        <!-- Liste des emails -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div v-if="emails.length" class="divide-y divide-slate-100">
                                <div
                                    v-for="email in emails"
                                    :key="email.message_number"
                                    :class="[
                                        'p-4 hover:bg-slate-50 transition-all duration-200 cursor-pointer group',
                                        !email.seen ? 'bg-rose-50/30' : ''
                                    ]"
                                    @click="router.visit(route('admin.emails.show', email.message_number))"
                                >
                                    <div class="flex items-start gap-4">
                                        
                                        <!-- Avatar / Initiales expéditeur -->
                                        <div class="relative pt-1 flex-shrink-0">
                                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shadow-sm', getAvatarColor(getFromName(email.from))]">
                                                {{ getInitials(getFromName(email.from)) }}
                                            </div>
                                            <!-- Point de statut non-lu -->
                                            <span v-if="!email.seen" class="absolute top-0 -right-1 w-3.5 h-3.5 bg-rose-500 border-2 border-white rounded-full"></span>
                                        </div>

                                        <!-- Contenu Email -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-4 mb-0.5">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <p :class="['truncate text-[15px]', !email.seen ? 'font-bold text-slate-900' : 'font-semibold text-slate-700']">
                                                        {{ getFromName(email.from) }}
                                                    </p>
                                                    <span v-if="email.flagged" class="text-amber-500 flex-shrink-0">
                                                        <i class="pi pi-star-fill text-xs"></i>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3 flex-shrink-0">
                                                    
                                                    <!-- AFFICHAGE DES PIÈCES JOINTES -->
                                                    <div v-if="email.has_attachments || (email.attachments && email.attachments.length)" 
                                                         class="flex items-center gap-1.5 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md text-[11px] font-bold border border-slate-200"
                                                         v-tooltip.top="'Contient des pièces jointes'">
                                                        <i class="pi pi-paperclip text-[10px]"></i>
                                                        <span v-if="email.attachments">{{ email.attachments.length }}</span>
                                                    </div>

                                                    <span :class="['text-xs whitespace-nowrap', !email.seen ? 'text-rose-600 font-semibold' : 'text-slate-400']">
                                                        {{ formatDate(email.date) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-3">
                                                <p :class="['text-sm truncate flex-1', !email.seen ? 'text-slate-900 font-medium' : 'text-slate-500']">
                                                    {{ email.subject || '(Sans objet)' }}
                                                </p>
                                            </div>

                                            <div class="flex items-center justify-between mt-1 h-7">
                                                <p class="text-xs text-slate-400 truncate w-1/2">{{ getFromEmail(email.from) }}</p>
                                                
                                                <!-- Boutons d'action (Apparaissent au survol) -->
                                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 translate-y-1 group-hover:translate-y-0">
                                                    <span class="text-[11px] text-slate-400 font-mono mr-2">{{ formatSize(email.size) }}</span>
                                                    
                                                    <Button
                                                        v-if="email.seen"
                                                        size="small" text rounded
                                                        class="!w-8 !h-8 !p-0 text-slate-500 hover:!bg-slate-200"
                                                        @click.stop="markAsUnread(email.message_number)"
                                                        v-tooltip.top="'Marquer comme non lu'"
                                                    >
                                                        <i class="pi pi-envelope text-sm"></i>
                                                    </Button>
                                                    <Button
                                                        v-else
                                                        size="small" text rounded
                                                        class="!w-8 !h-8 !p-0 text-rose-500 hover:!bg-rose-100"
                                                        @click.stop="markAsRead(email.message_number)"
                                                        v-tooltip.top="'Marquer comme lu'"
                                                    >
                                                        <i class="pi pi-envelope-open text-sm"></i>
                                                    </Button>
                                                    <Button
                                                        size="small" text rounded severity="danger"
                                                        class="!w-8 !h-8 !p-0 hover:!bg-red-100"
                                                        @click.stop="moveToTrash(email.message_number)"
                                                        v-tooltip.top="'Supprimer'"
                                                    >
                                                        <i class="pi pi-trash text-sm"></i>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- État vide (Aucun email) -->
                            <div v-else class="text-center py-20">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="pi pi-inbox text-4xl text-slate-300"></i>
                                </div>
                                <p class="text-slate-600 text-lg font-bold">Aucun email trouvé</p>
                                <p class="text-sm text-slate-400 mt-1">Votre dossier est vide pour le moment.</p>
                            </div>

                            <!-- Pagination -->
                            <div v-if="pagination.total_pages > 1" class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-center">
                                <Paginator
                                    :rows="pagination.per_page"
                                    :totalRecords="pagination.total"
                                    :first="(pagination.page - 1) * pagination.per_page"
                                    @page="onPage"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    class="!bg-transparent"
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
/* Assurez-vous d'avoir v-tooltip enregistré dans votre main.js pour que les bulles d'informations s'affichent correctement */
:deep(.p-inputtext) {
    box-shadow: none !important;
}
</style>