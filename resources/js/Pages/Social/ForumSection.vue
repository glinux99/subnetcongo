<script setup>
import { ref, reactive, computed, watch, onMounted,nextTick  } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// --- PRIMEVUE IMPORTS ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';
import Tooltip from 'primevue/tooltip';
import Menu from 'primevue/menu';
import Skeleton from 'primevue/skeleton';
import Paginator from 'primevue/paginator';
import SelectButton from 'primevue/selectbutton';
import ConfirmDialog from 'primevue/confirmdialog';
import ProgressSpinner from 'primevue/progressspinner';
import ColorPicker from 'primevue/colorpicker';
import ProgressBar from 'primevue/progressbar';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';

// --- DIRECTIVES ---
const vTooltip = Tooltip;

// --- PROPS & CONTEXT ---
const props = defineProps({
    threads: { type: Object, required: true }, // Attendu : Paginator Laravel (data, links, total, etc.)
    categories: { type: Array, required: true },
    filters: { type: Object, default: () => ({ search: '', category: null, status: 'all', sort: 'latest' }) },
    stats: { type: Object, default: () => ({ totalThreads: 0, totalReplies: 0, onlineUsers: 0 }) },
    isInstructor: { type: Boolean, default: false }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const toast = useToast();
const confirm = useConfirm();

// --- ÉTATS GLOBAUX ---
const isLoading = ref(false);
const viewThreadDialog = ref(false);
const newThreadDialog = ref(false);
const newCategoryDialog = ref(false);
const currentThread = ref(null);
const threadMenuRef = ref(null);
const activeMenuThread = ref(null);

// Optimistic UI pour les votes
const optimisticVotes = reactive({});

// --- FILTRES & RECHERCHE (Server-side Inertia) ---
const forumFilters = reactive({
    search: props.filters.search || '',
    category: props.filters.category || null,
    status: props.filters.status || 'all',
    sort: props.filters.sort || 'latest'
});

// Synchronisation automatique des données du thread ouvert (rechargement après action ou refresh)
watch(() => page.props.currentThreadInfo, (newVal) => {
    if (newVal && viewThreadDialog.value && newVal.id === currentThread.value?.id) {
        currentThread.value = cloneDeep(newVal);
        nextTick(() => scrollToBottom());
    }
}, { deep: true });

// Watcher pour synchroniser les filtres si changés depuis le parent (Sidebar Hub)
watch(() => props.filters, (newVal) => {
    forumFilters.category = newVal.category || null;
    forumFilters.search = newVal.search || '';
    forumFilters.sort = newVal.sort || 'latest';
}, { deep: true });

const statusOptions = ref([
    { label: 'Tous', value: 'all' },
    { label: 'Résolus', value: 'resolved' },
    { label: 'Non résolus', value: 'unresolved' }
]);

const sortOptions = ref([
    { label: 'Plus récents', value: 'latest' },
    { label: 'Plus populaires', value: 'popular' },
    { label: 'Sans réponse', value: 'unanswered' }
]);

// Watcher pour déclencher la recherche Inertia
watch(forumFilters, debounce((newFilters) => {
    isLoading.value = true;
    router.get(route('social.forum.index'), newFilters, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    });
}, 500), { deep: true });

// --- FORMULAIRES ---
const threadForm = useForm({
    title: '',
    category_id: null,
    content: '',
    attachments: []
});

const replyForm = useForm({
    thread_id: null,
    content: '',
    attachments: []
});

const categoryForm = useForm({
    name: '',
    description: '',
    color: '6366f1',
    icon: 'pi pi-tag',
    is_active: true
});

const submitCategory = () => {
    categoryForm.post(route('social.forums.categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Nouvelle catégorie ajoutée.' });
            newCategoryDialog.value = false;
            categoryForm.reset();
        }
    });
};

// --- ÉDITEUR CONFIGURATION ---
const editorFormats = [
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    ['code-block', 'link']
];

// --- GESTION DES FICHIERS ---
const handleFileUpload = (event, formInstance) => {
    formInstance.attachments = event.files;
};

const removeFile = (index, formInstance) => {
    formInstance.attachments.splice(index, 1);
};

// --- UTILITAIRE DE DÉFILEMENT ---
const scrollToBottom = () => {
    nextTick(() => {
        const container = document.querySelector('.thread-scroll-container .p-scrollpanel-content');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
};

// --- MÉTHODES : NAVIGATION & DIALOGS ---
const openNewThread = () => {
    threadForm.reset();
    threadForm.clearErrors();
    if (props.categories.length > 0) threadForm.category_id = props.categories[0].id;
    newThreadDialog.value = true;
};

const openThread = (thread) => {
    // Si la route API renvoie les détails complets, on les charge ici.
    isLoading.value = true;
    router.get(route('social.forums.threads.show', thread.id), {}, {
        preserveState: true,
        onSuccess: (page) => {
            currentThread.value = cloneDeep(page.props.currentThreadInfo || thread);
            replyForm.reset();
            replyForm.clearErrors();
            replyForm.thread_id = thread.id;
            viewThreadDialog.value = true;
            scrollToBottom();
        },
        onFinish: () => isLoading.value = false
    });

    // Fallback immédiat pour la démo
    currentThread.value = cloneDeep(thread);
    replyForm.reset();
    replyForm.thread_id = thread.id;
    viewThreadDialog.value = true;
    scrollToBottom();
};

// --- MÉTHODES : ACTIONS CRUD ---
const submitThread = () => {
    threadForm.post(route('social.threads.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Sujet publié avec brio !', life: 4000 });
            newThreadDialog.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les champs du formulaire.', life: 4000 });
        }
    });
};

const submitReply = () => {
    replyForm.post(route('social.forums.threads.replies.store', currentThread.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Réponse ajoutée', detail: 'Votre contribution a été publiée.', life: 3000 });
            replyForm.reset('content', 'attachments');
            // Le watcher sur currentThreadInfo gérera la mise à jour des données
            // suite à la redirection Redirect::back() du contrôleur.
            scrollToBottom();
        }
    });
};

// --- MÉTHODES : ACTIONS AVANCÉES ---
const toggleUpvote = (threadId, event) => {
    if(event) event.stopPropagation();

    // Optimistic UI
    const isUpvoted = optimisticVotes[threadId]?.voted || false;
    const currentCount = optimisticVotes[threadId]?.count || props.threads.data.find(t => t.id === threadId)?.votes_count || 0;

    optimisticVotes[threadId] = {
        voted: !isUpvoted,
        count: isUpvoted ? currentCount - 1 : currentCount + 1
    };

    router.post(route('forum.upvote', threadId), {}, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            // Rollback en cas d'erreur
            optimisticVotes[threadId] = { voted: isUpvoted, count: currentCount };
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d\'enregistrer le vote.' });
        }
    });
};

const toggleStatus = (thread, action) => {
    const routeName = `forum.threads.${action}`; // pin, lock, resolve
    router.post(route(routeName, thread.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Action effectuée', detail: `Le statut du sujet a été mis à jour.`, life: 3000 });
            if (currentThread.value && currentThread.value.id === thread.id) {
                if(action === 'resolve') currentThread.value.is_resolved = true;
                if(action === 'pin') currentThread.value.is_pinned = !currentThread.value.is_pinned;
                if(action === 'lock') currentThread.value.is_locked = !currentThread.value.is_locked;
            }
        }
    });
};

const confirmDelete = (thread) => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir supprimer définitivement ce sujet et toutes ses réponses ?',
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle text-red-500',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('forum.threads.destroy', thread.id), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Sujet supprimé avec succès.', life: 3000 });
                    viewThreadDialog.value = false;
                }
            });
        }
    });
};

const onPageChange = (event) => {
    // Inertia gère la pagination via l'URL
    const pageNumber = event.page + 1;
    router.get(route('social.forum.index'), { ...forumFilters, page: pageNumber }, { preserveState: true, preserveScroll: true });
};

// --- UTILITAIRES ---
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??';

const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " ans";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " mois";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " j";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " h";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " min";
    return "À l'instant";
};

// Configuration du menu contextuel par sujet
const getThreadMenuItems = (thread) => {
    const items = [
        { label: 'Copier le lien', icon: 'pi pi-link', command: () => copyLink(thread.id) },
        { label: 'Signaler', icon: 'pi pi-flag', command: () => toast.add({ severity: 'info', summary: 'Signalé', detail: 'L\'équipe de modération a été avertie.'}) }
    ];

    if (props.isInstructor || thread.author_id === currentUser.value.id) {
        items.push({ separator: true });
        if(props.isInstructor) {
            items.push({ label: thread.is_pinned ? 'Désépingler' : 'Épingler', icon: 'pi pi-bookmark', command: () => toggleStatus(thread, 'pin') });
            items.push({ label: thread.is_locked ? 'Déverrouiller' : 'Verrouiller', icon: 'pi pi-lock', command: () => toggleStatus(thread, 'lock') });
        }
        if(!thread.is_resolved) {
            items.push({ label: 'Marquer comme résolu', icon: 'pi pi-check-circle', command: () => toggleStatus(thread, 'resolve') });
        }
        items.push({ label: 'Supprimer', icon: 'pi pi-trash', class: 'text-red-500', command: () => confirmDelete(thread) });
    }
    return items;
};

const openThreadMenu = (event, thread) => {
    activeMenuThread.value = thread;
    threadMenuRef.value.toggle(event);
};

const copyLink = (id) => {
    const url = `${window.location.origin}/forum/threads/${id}`;
    navigator.clipboard.writeText(url);
    toast.add({ severity: 'secondary', summary: 'Lien copié', detail: 'Le lien a été copié dans le presse-papier.', life: 2000 });
};

</script>

<template>
    <div class="max-w-7xl mx-auto w-full flex flex-col md:flex-row gap-6 p-4 md:p-8 animate-fade-in font-sans h-screen max-h-[1000px]">
        <ConfirmDialog></ConfirmDialog>

        <!-- SIDEBAR GAUCHE : Filtres et Navigation -->
        <aside class="w-full md:w-80 flex-shrink-0 flex flex-col gap-6">
            <!-- Bouton Principal -->
            <Button
                label="Nouvelle Discussion"
                icon="pi pi-plus"
                class="w-full p-button-primary p-button-lg font-black rounded-2xl shadow-lg shadow-indigo-500/30 hover:scale-[1.02] transition-transform"
                @click="openNewThread"
            />

            <!-- Panel Filtres -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-6 flex flex-col gap-6">
                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Recherche globale</h3>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText
                            v-model="forumFilters.search"
                            placeholder="Mots-clés, erreurs..."
                            class="w-full bg-slate-50 border-slate-200 rounded-xl focus:ring-indigo-500"
                        />
                    </IconField>
                </div>

                <button @click="newCategoryDialog = true" type="button" class="w-full text-indigo-600 hover:bg-indigo-50 border border-indigo-100 p-2 rounded-xl font-bold text-xs transition-all">+ Gérer les catégories</button>

                <Divider class="m-0" />

                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Statut</h3>
                    <SelectButton v-model="forumFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                </div>
            </div>

            <!-- Panel Statistiques -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 blur-2xl rounded-full"></div>
                <h3 class="text-sm font-bold text-slate-300 mb-4 flex items-center gap-2">
                    <i class="pi pi-chart-bar"></i> Statistiques du Forum
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 p-3 rounded-xl border border-white/10 backdrop-blur-sm">
                        <div class="text-2xl font-black text-white">{{ stats.totalThreads || 0 }}</div>
                        <div class="text-xs text-slate-400 font-medium">Sujets créés</div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/10 backdrop-blur-sm">
                        <div class="text-2xl font-black text-emerald-400">{{ stats.onlineUsers || 1 }}</div>
                        <div class="text-xs text-slate-400 font-medium">En ligne</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ZONE PRINCIPALE : Liste des Threads -->
        <main class="flex-1 bg-white rounded-3xl shadow-sm border border-slate-200/60 flex flex-col overflow-hidden relative">

            <!-- Loading Overlay -->
            <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex items-center justify-center">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" animationDuration="1s"/>
            </div>

            <!-- Toolbar Header -->
            <header class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white z-10">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Discussions</h1>
                    <p class="text-sm text-slate-500 font-medium mt-1">
                        Trouvez des réponses ou aidez la communauté.
                    </p>
                </div>
                <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-xl border border-slate-200/60">
                    <button
                        v-for="sort in sortOptions" :key="sort.value"
                        @click="forumFilters.sort = sort.value"
                        class="px-4 py-2 text-sm font-bold rounded-lg transition-all"
                        :class="forumFilters.sort === sort.value ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    >
                        {{ sort.label }}
                    </button>
                </div>
            </header>

            <!-- Liste des Sujets -->
            <div class="flex-1 overflow-y-auto bg-slate-50/30 p-2 scroll-smooth">

                <!-- Empty State -->
                <div v-if="!props.threads.data?.length && !isLoading" class="flex flex-col items-center justify-center h-full p-10 text-center animate-fade-in">
                    <div class="w-32 h-32 bg-slate-100 rounded-full flex items-center justify-center mb-6">
                        <i class="pi pi-comments text-5xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-700 mb-2">Aucun sujet trouvé</h3>
                    <p class="text-slate-500 max-w-md mx-auto mb-6">
                        Il semble qu'aucune discussion ne corresponde à vos critères actuels. Soyez le premier à lancer le débat !
                    </p>
                    <Button label="Créer un sujet" icon="pi pi-plus" class="p-button-outlined rounded-xl" @click="openNewThread" />
                </div>

                <!-- Skeleton Loading -->
                <template v-if="isLoading && !props.threads.data?.length">
                    <div v-for="i in 5" :key="`skel-${i}`" class="p-5 mb-2 bg-white rounded-2xl border border-slate-100 flex gap-4">
                        <Skeleton shape="circle" size="3rem" class="flex-shrink-0"></Skeleton>
                        <div class="flex-1 space-y-3">
                            <Skeleton width="60%" height="1.5rem"></Skeleton>
                            <Skeleton width="100%" height="1rem"></Skeleton>
                            <Skeleton width="80%" height="1rem"></Skeleton>
                        </div>
                    </div>
                </template>

                <!-- Les Cartes de Threads -->
                <div
                    v-for="thread in props.threads.data" :key="thread.id"
                    class="group bg-white p-5 mb-3 rounded-2xl border border-slate-200/60 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer relative flex gap-4 sm:gap-6"
                    @click="openThread(thread)"
                >
                    <!-- Badges Absolus -->
                    <div v-if="thread.is_pinned" class="absolute -left-[1px] top-4 bottom-4 w-[4px] bg-amber-400 rounded-r-md"></div>
                    <div v-if="thread.is_resolved" class="absolute -left-[1px] top-4 bottom-4 w-[4px] bg-emerald-400 rounded-r-md"></div>

                    <!-- Colonne Vote UI -->
                    <div class="flex flex-col items-center flex-shrink-0">
                        <button
                            @click="toggleUpvote(thread.id, $event)"
                            class="w-12 h-12 rounded-xl flex flex-col items-center justify-center transition-colors border"
                            :class="(optimisticVotes[thread.id]?.voted || thread.has_voted)
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-600'
                                : 'bg-slate-50 border-transparent text-slate-400 hover:bg-slate-100 hover:text-slate-600'"
                        >
                            <i class="pi pi-angle-up text-xl -mb-1 font-bold"></i>
                            <span class="text-sm font-black">{{ optimisticVotes[thread.id]?.count ?? thread.votes_count }}</span>
                        </button>
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <Tag v-if="thread.category" :value="thread.category.name" class="bg-slate-100 text-slate-600 text-[10px] uppercase font-black tracking-wider px-2 py-0.5" />
                            <Badge v-if="thread.is_pinned" value="ÉPINGLÉ" severity="warning" class="text-[10px]" />
                            <Badge v-if="thread.is_locked" value="VERROUILLÉ" severity="danger" class="text-[10px]" />
                            <Tag v-if="thread.is_resolved" icon="pi pi-check" value="RÉSOLU" class="bg-emerald-100 text-emerald-700 text-[10px] uppercase font-black" />
                        </div>

                        <h3 class="text-lg sm:text-xl font-black text-slate-800 mb-1 leading-tight group-hover:text-indigo-600 transition-colors line-clamp-1 pr-8">
                            {{ thread.title }}
                        </h3>

                        <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed mb-4">
                            {{ thread.content_preview || thread.content?.replace(/<[^>]*>?/gm, '').substring(0, 150) + '...' }}
                        </p>

                        <!-- Footer de la carte -->
                        <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                            <div class="flex items-center gap-3">
                                <Avatar :label="getInitials(thread.author?.name)" :image="thread.author?.avatar" shape="circle" size="small" class="w-7 h-7 bg-indigo-100 text-indigo-700 font-bold text-xs" />
                                <div class="text-xs">
                                    <span class="font-bold text-slate-700">{{ thread.author?.name || 'Utilisateur' }}</span>
                                    <span class="text-slate-400 mx-1">•</span>
                                    <span class="text-slate-400" v-tooltip.top="thread.created_at">{{ formatTimeAgo(thread.created_at) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-xs font-bold text-slate-400">
                                <div class="flex items-center gap-1.5" v-tooltip.top="'Vues'">
                                    <i class="pi pi-eye"></i> {{ thread.views_count || 0 }}
                                </div>
                                <div class="flex items-center gap-1.5" :class="{'text-indigo-500': thread.replies_count > 0}" v-tooltip.top="'Réponses'">
                                    <i class="pi pi-comments"></i> {{ thread.replies_count || 0 }}
                                </div>

                                <!-- Menu Contextuel Trigger -->
                                <button @click.stop="openThreadMenu($event, thread)" class="p-1.5 rounded-md hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-800">
                                    <i class="pi pi-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginator (Server-side) -->
            <div class="border-t border-slate-100 bg-white p-2">
                <Paginator
                    :rows="props.threads.per_page || 15"
                    :totalRecords="props.threads.total || 0"
                    :first="(props.threads.current_page - 1) * (props.threads.per_page || 15)"
                    @page="onPageChange"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    currentPageReportTemplate="{first} à {last} sur {totalRecords}"
                />
            </div>
        </main>

        <!-- Menu Contextuel Flottant -->
        <Menu ref="threadMenuRef" :model="activeMenuThread ? getThreadMenuItems(activeMenuThread) : []" :popup="true" class="w-56 rounded-xl shadow-xl border-slate-100" />

        <!-- ==================================================================================== -->
        <!-- DIALOG : VISUALISER UN SUJET (FULL THREAD VIEW)                                      -->
        <!-- ==================================================================================== -->
        <Dialog
    v-model:visible="viewThreadDialog"
    modal
    :dismissableMask="true"
    class="m-0 sm:m-4"
    :style="{ width: '100%', maxWidth: '1000px', height: '100%', maxHeight: '90vh' }"
    :pt="{
        root: { class: 'bg-slate-50 overflow-hidden rounded-2xl flex flex-col' },
        header: { class: 'bg-white border-b border-slate-100 py-4 px-6 shrink-0 z-10' },
        content: { class: 'p-0 bg-transparent overflow-hidden flex flex-col flex-1 h-full' } /* Ajout de h-full ici */
    }"
>
    <template #header>
        <div class="flex items-center gap-3 w-full pr-12">
            <Tag v-if="currentThread?.category" :value="currentThread.category.name" class="bg-indigo-50 text-indigo-700 uppercase font-black" />
            <span class="text-sm font-medium text-slate-400">Discussion #{{ currentThread?.id }}</span>
            <div class="ml-auto flex gap-2">
                <Button icon="pi pi-refresh" text rounded severity="secondary" @click="openThread(currentThread)" v-tooltip.left="'Actualiser les réponses'" />
                <Tag v-if="currentThread?.is_resolved" icon="pi pi-check" value="Résolu" severity="success" />
                <Tag v-if="currentThread?.is_locked" icon="pi pi-lock" value="Verrouillé" severity="danger" />
            </div>
        </div>
    </template>

    <template v-if="currentThread">
        <!-- CONTENEUR PRINCIPAL FLEX : Sépare proprement le scroll et le formulaire -->
        <div class="flex flex-col h-full w-full overflow-hidden">

            <!-- ZONE DE DÉFILEMENT (Prend l'espace restant avec flex-1 et min-h-0) -->
            <div class="flex-1 min-h-0 relative">
                <ScrollPanel style="width: 100%; height: 100%;" class="bg-slate-50/50 thread-scroll-container">
                    <!-- Remplacement de pb-48 par pb-8 car le formulaire ne flotte plus au-dessus -->
                    <div class="max-w-4xl mx-auto p-4 md:p-8 space-y-6 pb-8">

                        <!-- POST ORIGINAL (OP) -->
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 overflow-hidden relative">
                            <!-- En-tête OP -->
                            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-start">
                                <div class="flex items-center gap-4">
                                    <Avatar :label="getInitials(currentThread.author?.name)" :image="currentThread.author?.avatar" size="large" shape="circle" class="bg-indigo-600 text-white font-black shadow-md w-12 h-12 text-lg" />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-black text-slate-800 text-lg">{{ currentThread.author?.name }}</h3>
                                            <Badge v-if="currentThread.author?.role === 'admin' || currentThread.author?.role === 'instructor'" value="Équipe" severity="info" class="text-[10px] bg-blue-100 text-blue-700" />
                                            <Tag value="Auteur" class="bg-slate-200 text-slate-600 text-[10px] font-black uppercase" />
                                        </div>
                                        <p class="text-xs text-slate-500 font-medium">Publié {{ formatTimeAgo(currentThread.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Contrôles OP -->
                                <div class="flex gap-2">
                                    <Button v-if="!currentThread.is_resolved && (isInstructor || currentThread.author_id === currentUser.id)"
                                            label="Marquer Résolu" icon="pi pi-check" size="small" class="p-button-outlined p-button-success rounded-xl"
                                            @click="toggleStatus(currentThread, 'resolve')" />
                                </div>
                            </div>

                            <!-- Contenu OP -->
                            <div class="p-6 md:p-8">
                                <h1 class="text-2xl md:text-3xl font-black text-slate-800 mb-6 leading-tight">{{ currentThread.title }}</h1>
                                <div class="prose prose-slate max-w-none prose-headings:font-black prose-a:text-indigo-600 prose-img:rounded-xl" v-html="currentThread.content"></div>

                                <!-- Pièces jointes OP (Sujet principal) -->
                                <div v-if="currentThread.attachments?.length || currentThread.attachment_path" class="mt-8 pt-6 border-t border-slate-100">
                                    <h4 class="text-sm font-bold text-slate-500 mb-3 uppercase tracking-wider">Pièces jointes</h4>
                                    <div class="flex flex-wrap gap-3">
                                        <!-- Cas d'une collection (relation attachments) -->
                                        <a v-for="file in currentThread.attachments" :key="file.id" :href="file.url" target="_blank" download
                                           class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:bg-slate-100 hover:border-slate-300 transition-colors">
                                            <i class="pi pi-file text-slate-400 text-xl"></i>
                                            <div>
                                                <div class="text-sm font-bold text-slate-700">{{ file.name }}</div>
                                                <div class="text-xs text-slate-500">{{ (file.size / 1024).toFixed(1) }} KB</div>
                                            </div>
                                            <i class="pi pi-download text-slate-400 ml-2"></i>
                                        </a>

                                        <!-- Cas du fichier unique stocké en base (ForumTopic) -->
                                        <a v-if="currentThread.attachment_path" :href="'/media/' + currentThread.attachment_path" target="_blank" download
                                           class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:bg-slate-100 hover:border-slate-300 transition-colors">
                                            <i class="pi pi-file text-indigo-400 text-xl"></i>
                                            <div>
                                                <div class="text-sm font-bold text-slate-700">{{ currentThread.attachment_name || 'Télécharger le document' }}</div>
                                                <div class="text-xs text-slate-500">Fichier joint</div>
                                            </div>
                                            <i class="pi pi-download text-slate-400 ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- LISTE DES RÉPONSES -->
                        <div v-if="currentThread.replies?.length" class="space-y-4">
                            <div class="flex items-center gap-4 py-2">
                                <h3 class="text-lg font-black text-slate-800">Réponses</h3>
                                <Badge :value="currentThread.replies.length" class="bg-slate-200 text-slate-700" />
                                <div class="flex-1 h-px bg-slate-200"></div>
                            </div>

                            <div v-for="reply in currentThread.replies" :key="reply.id"
                                 class="bg-white rounded-2xl p-5 md:p-6 border border-slate-200/60 shadow-sm relative group"
                                 :class="{'border-emerald-300 bg-emerald-50/10': reply.is_solution}">

                                <div v-if="reply.is_solution" class="absolute -top-3 right-6 bg-emerald-500 text-white text-xs font-black px-3 py-1 rounded-full shadow-sm flex items-center gap-1">
                                    <i class="pi pi-star-fill"></i> Solution Acceptée
                                </div>

                                <div class="flex gap-4">
                                    <Avatar :label="getInitials(reply.author?.name)" :image="reply.author?.avatar" shape="circle" class="w-10 h-10 bg-slate-100 text-slate-600 font-bold shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <span class="font-bold text-slate-800 mr-2">{{ reply.author?.name }}</span>
                                                <Tag v-if="reply.author?.id === currentThread.author_id" value="Auteur" class="bg-slate-100 text-slate-500 text-[10px] px-1 py-0" />
                                                <div class="text-xs text-slate-400 mt-0.5">{{ formatTimeAgo(reply.created_at) }}</div>
                                            </div>

                                            <Button v-if="!currentThread.is_resolved && currentThread.author_id === currentUser.id"
                                                    icon="pi pi-check" text rounded severity="success" size="small" v-tooltip.left="'Marquer comme solution'" />
                                        </div>

                                        <div class="prose prose-sm max-w-none text-slate-700 leading-relaxed" v-html="reply.content"></div>

                                        <!-- Pièces jointes pour les réponses -->
                                        <div v-if="reply.attachment_path" class="mt-4 pt-3 border-t border-slate-50">
                                            <a :href="'/media/' + reply.attachment_path" target="_blank" download
                                               class="inline-flex items-center gap-3 p-2 bg-slate-50 border border-slate-100 rounded-xl hover:bg-slate-100 transition-colors text-xs font-bold text-slate-600">
                                                <i class="pi pi-paperclip text-indigo-500"></i>
                                                <span class="truncate max-w-[200px]">{{ reply.attachment_name || 'Fichier joint' }}</span>
                                                <i class="pi pi-download ml-1 text-[10px]"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ScrollPanel>
            </div>

            <!-- FORMULAIRE DE RÉPONSE (Fixe en bas grâce à shrink-0, plus d'absolute) -->
            <div v-if="!currentThread.is_locked" class="shrink-0 bg-white border-t border-slate-200 p-4 md:p-6 shadow-[0_-10px_30px_rgba(0,0,0,0.03)] z-20">
                <div class="max-w-4xl mx-auto flex gap-4 items-start">
                    <Avatar :label="getInitials(currentUser.name)" shape="circle" class="hidden sm:flex w-10 h-10 bg-indigo-100 text-indigo-700 shrink-0" />
                    <div class="flex-1 space-y-3">
                        <Editor v-model="replyForm.content" :formats="editorFormats" editorStyle="height: 120px" class="rounded-xl border-slate-200" placeholder="Rédigez votre réponse détaillée ici..." />

                        <!-- Fichiers joints prévisualisation -->
                        <div v-if="replyForm.attachments && replyForm.attachments.length > 0" class="flex flex-wrap gap-2 p-2 bg-slate-50 rounded-lg border border-slate-100">
                            <Chip v-for="(file, idx) in replyForm.attachments" :key="idx" removable @remove="removeFile(idx, replyForm)" class="text-xs font-bold text-slate-600 bg-white border border-slate-200">
                                {{ file.name }}
                            </Chip>
                        </div>

                        <div class="flex justify-between items-center">
                            <FileUpload mode="basic" name="attachments[]" :multiple="true" :maxFileSize="5000000" customUpload @select="e => handleFileUpload(e, replyForm)"
                                        chooseLabel="Joindre un fichier" chooseIcon="pi pi-paperclip" class="p-button-text p-button-secondary p-button-sm font-bold" />

                            <Button label="Répondre" icon="pi pi-send" class="p-button-primary rounded-xl px-6 font-black shadow-md shadow-indigo-500/20"
                                    @click="submitReply" :loading="replyForm.processing" :disabled="!replyForm.content.replace(/<[^>]*>?/gm, '').trim()" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message verrouillé -->
            <div v-else class="shrink-0 bg-slate-50 border-t border-slate-200 p-6 text-center text-slate-500 font-bold z-20 flex items-center justify-center gap-2">
                <i class="pi pi-lock text-red-400 text-lg"></i> Cette discussion a été verrouillée par un modérateur.
            </div>

        </div>
    </template>
</Dialog>
        <!-- ==================================================================================== -->
        <!-- DIALOG : NOUVEAU SUJET (COMPLEX FORM)                                                -->
        <!-- ==================================================================================== -->
        <Dialog
            v-model:visible="newThreadDialog"
            modal
            header="Lancer une nouvelle discussion"
            :style="{ width: '100%', maxWidth: '800px' }"
            :pt="{
                root: { class: 'rounded-3xl shadow-2xl' },
                header: { class: 'bg-slate-50 border-b border-slate-100 px-8 py-5' },
                title: { class: 'text-xl font-black text-slate-800' },
                content: { class: 'p-8 space-y-6' },
                footer: { class: 'p-6 bg-slate-50 border-t border-slate-100' }
            }"
        >
            <!-- Erreurs globales -->
            <div v-if="Object.keys(threadForm.errors).length > 0" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-bold border border-red-100 flex items-center gap-3">
                <i class="pi pi-exclamation-circle text-xl"></i>
                Veuillez corriger les erreurs ci-dessous avant de publier.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Titre -->
                <div class="col-span-1 md:col-span-2 space-y-2">
                    <label class="font-black text-slate-700 text-xs uppercase tracking-wider flex justify-between">
                        Titre de la discussion <span class="text-red-500">*</span>
                    </label>
                    <InputText v-model="threadForm.title" placeholder="Ex: Comment résoudre l'erreur 404 sur mon projet ?"
                               class="w-full rounded-xl p-3 border-slate-200 focus:ring-indigo-500 font-medium"
                               :class="{'p-invalid': threadForm.errors.title}" />
                    <small v-if="threadForm.errors.title" class="p-error block mt-1">{{ threadForm.errors.title }}</small>
                </div>

                <!-- Catégorie -->
                <div class="col-span-1 space-y-2">
                    <label class="font-black text-slate-700 text-xs uppercase tracking-wider flex justify-between">
                        Catégorie <span class="text-red-500">*</span>
                        <button v-if="isInstructor" @click="newCategoryDialog = true" type="button" class="text-indigo-600 hover:underline lowercase font-bold transition-all hover:text-indigo-800">+ créer</button>
                    </label>
                    <Dropdown v-model="threadForm.category_id" :options="categories" optionLabel="name" optionValue="id"
                              placeholder="Choisir..." class="w-full rounded-xl h-[46px] border-slate-200"
                              :class="{'p-invalid': threadForm.errors.category_id}">
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-3 font-medium">
                                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: '#' + (categories.find(c => c.id === slotProps.value)?.color || '6366f1') }"></span>
                                <span class="truncate">{{ categories.find(c => c.id === slotProps.value)?.name }}</span>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #empty>
                            <div class="p-4 text-center flex flex-col items-center gap-2">
                                <p class="text-xs text-slate-400 italic">Aucune catégorie disponible.</p>
                                <button v-if="isInstructor" @click="newCategoryDialog = true" type="button" class="text-indigo-600 font-bold text-xs hover:underline">+ Créer une catégorie</button>
                            </div>
                        </template>
                    </Dropdown>
                    <small v-if="threadForm.errors.category_id" class="p-error block mt-1">{{ threadForm.errors.category_id }}</small>
                </div>
            </div>

            <!-- Contenu Enrichi -->
            <div class="space-y-2">
                <label class="font-black text-slate-700 text-xs uppercase tracking-wider flex justify-between">
                    Votre message <span class="text-red-500">*</span>
                </label>
                <Editor v-model="threadForm.content"
                        editorStyle="height: 300px;"
                        :class="{'border-red-400': threadForm.errors.content}"
                        placeholder="Décrivez votre problème ou posez votre question en détail. Utilisez le Markdown ou les outils ci-dessus pour formater votre code." />
                <small v-if="threadForm.errors.content" class="p-error block mt-1">{{ threadForm.errors.content }}</small>
            </div>

            <!-- Upload de Fichiers Avancé -->
            <div class="space-y-2 bg-slate-50 p-5 rounded-2xl border border-slate-200 border-dashed">
                <label class="font-black text-slate-700 text-xs uppercase tracking-wider mb-2 block">
                    Pièces jointes (Optionnel)
                </label>
                <FileUpload name="files" url="/api/upload" :multiple="true" accept="image/*,.pdf,.zip" :maxFileSize="10000000"
                            @select="e => handleFileUpload(e, threadForm)"
                            :pt="{
                                buttonbar: { class: 'hidden' },
                                content: { class: 'p-0 bg-transparent border-none' }
                            }">
                    <template #header="{ chooseCallback }">
                        <div class="flex items-center justify-between w-full">
                            <Button @click="chooseCallback()" icon="pi pi-cloud-upload" label="Parcourir les fichiers" class="p-button-outlined p-button-secondary rounded-xl bg-white" />
                            <span class="text-xs text-slate-400 font-medium">Max 10MB par fichier. PNG, JPG, PDF, ZIP.</span>
                        </div>
                    </template>
                    <template #content="{ files, removeFileCallback }">
                        <div v-if="files.length > 0" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="(file, index) of files" :key="file.name + file.type + file.size"
                                 class="p-3 bg-white border border-slate-200 rounded-xl flex items-center justify-between shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <img v-if="file.type.startsWith('image/')" role="presentation" :alt="file.name" :src="file.objectURL" width="40" height="40" class="rounded-lg object-cover w-10 h-10 border border-slate-100" />
                                    <i v-else class="pi pi-file text-2xl text-slate-400"></i>
                                    <div class="flex flex-col overflow-hidden">
                                        <span class="font-bold text-sm text-slate-700 truncate">{{ file.name }}</span>
                                        <span class="text-xs text-slate-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                                    </div>
                                </div>
                                <Button icon="pi pi-times" @click="removeFileCallback(index); removeFile(index, threadForm)" class="p-button-rounded p-button-danger p-button-text" />
                            </div>
                        </div>
                    </template>
                </FileUpload>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full">
                    <Button label="Annuler" text severity="secondary" @click="newThreadDialog = false" class="font-bold rounded-xl" />
                    <Button label="Publier le sujet" icon="pi pi-send" class="p-button-primary px-8 py-3 font-black rounded-xl shadow-lg shadow-indigo-500/30"
                            @click="submitThread" :loading="threadForm.processing" />
                </div>
            </template>
        </Dialog>

        <!-- ==================================================================================== -->
        <!-- DIALOG : NOUVELLE CATÉGORIE (ADMINISTRATION)                                         -->
        <!-- ==================================================================================== -->
        <Dialog v-model:visible="newCategoryDialog" modal header="📐 Paramétrage de Catégorie" :style="{ width: '450px' }" class="rounded-3xl shadow-2xl">
            <div class="flex flex-col gap-6 py-4">
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Désignation</label>
                    <InputText v-model="categoryForm.name" class="w-full rounded-xl border-slate-200 p-3 font-bold" placeholder="Nom de la catégorie..." :class="{'p-invalid': categoryForm.errors.name}" />
                    <small v-if="categoryForm.errors.name" class="p-error font-bold">{{ categoryForm.errors.name }}</small>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Description</label>
                    <Textarea v-model="categoryForm.description" rows="2" class="w-full rounded-xl border-slate-200 p-3 text-sm" placeholder="Quel est le but de cet espace ?" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Identité Visuelle</label>
                        <div class="flex items-center gap-3 p-2 bg-slate-50 border border-slate-200 rounded-xl h-[46px]">
                            <ColorPicker v-model="categoryForm.color" />
                            <span class="text-xs font-mono font-bold text-slate-600">#{{ categoryForm.color }}</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Icône</label>
                        <Dropdown v-model="categoryForm.icon" :options="['pi pi-tag', 'pi pi-comments', 'pi pi-info-circle', 'pi pi-bolt', 'pi pi-code']" class="w-full rounded-xl h-[46px] border-slate-200">
                            <template #option="slotProps"><i :class="slotProps.option" class="mr-2"></i> {{ slotProps.option.split('-')[1] }}</template>
                        </Dropdown>
                    </div>
                </div>

                <div class="flex items-center gap-4 p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl">
                    <input type="checkbox" v-model="categoryForm.is_active" class="w-5 h-5 accent-indigo-600 cursor-pointer" />
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-indigo-900 uppercase">Activer immédiatement</span>
                        <span class="text-[10px] text-indigo-500 font-medium italic">Rendre visible dans la liste du forum.</span>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" text severity="secondary" @click="newCategoryDialog = false" class="font-bold rounded-xl" />
                <Button label="Créer la catégorie" icon="pi pi-plus-circle" @click="submitCategory" :loading="categoryForm.processing" class="p-button-primary rounded-xl px-6 font-black shadow-lg shadow-indigo-200" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
/* Animations et transitions fluides */
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Surcharge PrimeVue Quill Editor pour un look moderne */
:deep(.p-editor-container) {
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
:deep(.p-editor-toolbar) {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-top: none;
    border-left: none;
    border-right: none;
}
:deep(.p-editor-content) {
    border: none !important;
    background: #ffffff;
}
:deep(.ql-editor) {
    font-family: inherit;
    font-size: 0.95rem;
    color: #334155;
}
:deep(.ql-editor.ql-blank::before) {
    color: #94a3b8;
    font-style: normal;
}

/* Scrollbar Custom pour le panneau principal et dialogues */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Amélioration de la typographie Markdown générée (prose) */
:deep(.prose pre) {
    background-color: #1e293b;
    border-radius: 0.75rem;
    padding: 1rem;
}
:deep(.prose blockquote) {
    border-left-color: #818cf8;
    background-color: #eef2ff;
    padding: 0.5rem 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    font-style: normal;
    color: #4f46e5;
}
</style>
