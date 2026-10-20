<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Paginator from 'primevue/paginator';
import Breadcrumb from 'primevue/breadcrumb';
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
</script>

<template>
    <Head title="Boîte email" />
    <AppLayout>
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1600px] mx-auto">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Administration', icon: 'pi pi-cog' }, { label: 'Boîte Email' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-[#ff4d4d] text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                                Connecté : {{ account }}
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                                <span class="text-white">BOÎTE</span>
                                <span class="text-[#EF4444]">EMAIL</span>
                            </h1>

                            <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                                Gérez vos correspondances IMAP directement depuis la plateforme. Lisez, classez et répondez à vos emails sans quitter votre environnement de travail.
                            </p>
                        </div>

                        <!-- Partie Droite (Boutons) -->
                        <div class="flex items-center gap-3 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto">
                            <Button 
                                label="Actualiser" 
                                icon="pi pi-sync" 
                                @click="applyFilters"
                                class="!bg-transparent hover:!bg-white/10 !border !border-slate-600 !text-white w-full sm:w-auto text-sm font-medium transition-colors" 
                            />
                            <Button 
                                label="Nouveau message" 
                                icon="pi pi-pen-to-square" 
                                @click="router.visit(route('admin.emails.compose'))"
                                class="!bg-[#EF4444] hover:!bg-red-600 !border-none !text-white w-full sm:w-auto text-sm font-bold shadow-lg shadow-red-900/50 transition-colors" 
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES KPI (Chevauchement) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- KPI 1 : Total -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center">
                            <i class="pi pi-inbox text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Emails</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats.total ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 2 : Non Lus -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center">
                            <i class="pi pi-envelope text-red-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Non lus</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats.unread ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 3 : Quota -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center">
                            <i class="pi pi-database text-red-500 text-sm"></i>
                        </div>
                        <div class="w-full">
                            <div class="flex justify-between items-end mb-2">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Quota serveur</div>
                                <div class="text-xl font-black text-slate-800 leading-none">{{ stats.quota?.percent ?? 0 }}<span class="text-xs text-slate-400 ml-0.5">%</span></div>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden w-full">
                                <div :class="['h-full rounded-full transition-all duration-500', (stats.quota?.percent ?? 0) > 85 ? 'bg-red-500' : 'bg-red-500']" :style="{ width: Math.min(stats.quota?.percent ?? 0, 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4 : Dossiers -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center">
                            <i class="pi pi-folder-open text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dossiers Sync.</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ folders.length }}</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU WEBMAIL (Sidebar + Liste)                                              -->
                <!-- ============================================================================== -->
                <div class="relative z-20 mt-6">
                    
                    <!-- Message d'erreur -->
                    <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-5 mb-6 shadow-sm flex items-center gap-3 text-red-700">
                        <i class="pi pi-exclamation-triangle text-2xl"></i>
                        <div>
                            <p class="font-bold text-sm">Erreur de connexion IMAP</p>
                            <p class="text-xs text-red-600">{{ error }}</p>
                        </div>
                    </div>

                    <div v-else class="flex flex-col lg:flex-row gap-6 items-start">
                        
                        <!-- SIDEBAR DOSSIERS -->
                        <div class="w-full lg:w-64 flex-shrink-0 lg:sticky lg:top-6">
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                                
                                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                                    <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500 flex items-center gap-2">
                                        <i class="pi pi-folder"></i> Navigation
                                    </h3>
                                </div>

                                <div class="p-3 space-y-1">
                                    <button
                                        v-for="folder in folders"
                                        :key="folder.path"
                                        @click="currentFolder = folder.short; applyFilters()"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-all duration-200 font-bold"
                                        :class="currentFolder === folder.short ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <i :class="[getFolderIcon(folder.name), currentFolder === folder.short ? 'text-blue-500' : 'text-slate-400']" class="text-lg"></i>
                                            <span class="truncate">{{ folder.name }}</span>
                                        </div>
                                        <span v-if="folder.unread" class="bg-[#EF4444] text-white text-[10px] font-black px-2 py-0.5 rounded shadow-sm">
                                            {{ folder.unread }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- CONTENU PRINCIPAL (Recherche + Liste) -->
                        <div class="flex-1 min-w-0 w-full flex flex-col gap-6">
                            
                            <!-- Barre de recherche -->
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                                <div class="flex flex-col md:flex-row gap-3">
                                    <div class="flex-1 relative">
                                        <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <InputText
                                            v-model="search"
                                            @keyup.enter="applyFilters"
                                            placeholder="Rechercher un expéditeur, un sujet..."
                                            class="!rounded-xl w-full !pl-10 border-slate-200"
                                        />
                                    </div>
                                    <!-- Sélecteur de dossier version mobile/tablette -->
                                    <Select
                                        v-model="currentFolder"
                                        :options="folders"
                                        optionLabel="name"
                                        optionValue="short"
                                        @change="applyFilters"
                                        class="!rounded-xl min-w-[200px] border-slate-200 lg:hidden"
                                    />
                                    <Button label="Rechercher" icon="pi pi-search" @click="applyFilters" class="!rounded-xl !bg-slate-800 !border-slate-800 hover:!bg-slate-900 font-bold" />
                                </div>
                            </div>

                            <!-- Liste des emails -->
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                                <div v-if="emails.length" class="divide-y divide-slate-100">
                                    <div
                                        v-for="email in emails"
                                        :key="email.message_number"
                                        :class="[
                                            'p-4 hover:bg-slate-50 transition-all duration-200 cursor-pointer group flex flex-col md:flex-row gap-4 items-start md:items-center',
                                            !email.seen ? 'bg-blue-50/30' : ''
                                        ]"
                                        @click="router.visit(route('admin.emails.show', email.message_number))"
                                    >
                                        <!-- Avatar & Statut -->
                                        <div class="relative shrink-0 hidden sm:block">
                                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shadow-sm', getAvatarColor(getFromName(email.from))]">
                                                {{ getInitials(getFromName(email.from)) }}
                                            </div>
                                            <span v-if="!email.seen" class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-[#EF4444] border-2 border-white rounded-full"></span>
                                        </div>

                                        <!-- Corps du message dans la liste -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <p class="truncate text-[14px]" :class="!email.seen ? 'font-black text-slate-900' : 'font-bold text-slate-700'">
                                                    {{ getFromName(email.from) }}
                                                </p>
                                                <span v-if="email.flagged" class="text-red-500 shrink-0"><i class="pi pi-star-fill text-xs"></i></span>
                                            </div>
                                            
                                            <div class="flex items-center gap-2">
                                                <p class="text-[13px] truncate" :class="!email.seen ? 'text-slate-800 font-bold' : 'text-slate-500 font-medium'">
                                                    {{ email.subject || '(Sans objet)' }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Méta-données & Actions -->
                                        <div class="flex items-center justify-between w-full md:w-auto md:flex-col md:items-end gap-2 shrink-0">
                                            <div class="flex items-center gap-3">
                                                <!-- Icône trombone -->
                                                <div v-if="email.has_attachments || (email.attachments && email.attachments.length)" class="text-slate-400" v-tooltip.top="'Pièce(s) jointe(s)'">
                                                    <i class="pi pi-paperclip text-sm"></i>
                                                </div>
                                                <span class="text-xs whitespace-nowrap" :class="!email.seen ? 'text-blue-600 font-black' : 'text-slate-400 font-bold'">
                                                    {{ formatDate(email.date) }}
                                                </span>
                                            </div>

                                            <!-- Actions Rapides (Hover) -->
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <Button
                                                    v-if="email.seen"
                                                    size="small" text rounded
                                                    class="!w-8 !h-8 !p-0 text-slate-400 hover:!bg-slate-200 hover:!text-slate-700"
                                                    @click.stop="markAsUnread(email.message_number)"
                                                    v-tooltip.top="'Marquer comme non lu'"
                                                >
                                                    <i class="pi pi-envelope text-xs"></i>
                                                </Button>
                                                <Button
                                                    v-else
                                                    size="small" text rounded
                                                    class="!w-8 !h-8 !p-0 text-[#EF4444] hover:!bg-red-50"
                                                    @click.stop="markAsRead(email.message_number)"
                                                    v-tooltip.top="'Marquer comme lu'"
                                                >
                                                    <i class="pi pi-envelope-open text-xs"></i>
                                                </Button>
                                                <Button
                                                    size="small" text rounded severity="danger"
                                                    class="!w-8 !h-8 !p-0 hover:!bg-red-50 text-red-400"
                                                    @click.stop="moveToTrash(email.message_number)"
                                                    v-tooltip.top="'Supprimer'"
                                                >
                                                    <i class="pi pi-trash text-xs"></i>
                                                </Button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- État vide -->
                                <div v-else class="text-center py-24 bg-slate-50/50">
                                    <div class="w-20 h-20 bg-white shadow-sm border border-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="pi pi-inbox text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="text-slate-700 text-lg font-black">Aucun email trouvé</p>
                                    <p class="text-sm text-slate-500 mt-1">Ce dossier est vide pour le moment.</p>
                                </div>

                                <!-- Pagination -->
                                <div v-if="pagination.total_pages > 1" class="p-4 border-t border-slate-100 bg-slate-50 flex justify-center">
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
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-inputtext), :deep(.p-select) {
    box-shadow: none !important;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-select:not(.p-disabled).p-focus) {
    border-color: #EF4444;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1) !important;
}
</style>