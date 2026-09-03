<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - CORE MEDIA ENGINE UI v10.5.2
 * ======================================================================================
 * Interface de pilotage des actifs numériques.
 * Intègre : S3 Browser, Image Optimizer, Meta Editor & Cloud Monitoring.
 *
 * @author  SubnetCongo Framework Division
 * @module  Media/Library
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';
import debounce from 'lodash/debounce';

// --- PROPS DÉFINIES PAR LE MEDIA_LIBRARY_CONTROLLER ---
const props = defineProps({
    assets: Object,         // Données paginées (Inertia Paginated Collection)
    filters: Object,        // Filtres actifs (search, category, folder, etc.)
    folders: Array,         // Liste des dossiers virtuels uniques
    storageStats: Object,   // Statistiques d'utilisation (GB, %, count)
    permissions: Object     // can_upload, can_delete, etc.
});

// --- INITIALISATION ---
const toast = useToast();
const loading = ref(false);
const layout = ref('grid');
const uploadDialog = ref(false);
const fileSidebar = ref(false);
const folderDialog = ref(false);
const bulkActionMenu = ref();
const cm = ref();

// --- FILTRAGE RÉACTIF (SERVER-SIDE SYNC) ---
const serverFilters = reactive({
    search: props.filters.search || '',
    category: props.filters.category || null,
    folder: props.filters.folder || '/',
    sort_by: props.filters.sort_by || 'created_at',
    sort_order: props.filters.sort_order || 'desc'
});

// Watcher avec Debounce pour limiter les appels serveurs
watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('media.index'), serverFilters, {
        preserveState: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 400));

// --- FORMULAIRES INERTIA ---
const uploadForm = useForm({
    files: [],
    folder: serverFilters.folder,
    collection: 'library',
    is_private: false
});

const metadataForm = useForm({
    id: null,
    name: '',
    folder: '',
    alt_text: '',
    description: '',
    tags: []
});

const folderForm = useForm({
    name: ''
});

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = computed(() => {
    const items = [{ label: 'Bibliothèque Média' }];
    if (serverFilters.folder !== '/') {
        items.push({ label: serverFilters.folder, class: 'font-bold text-indigo-600' });
    }
    return items;
});

// --- MÉTHODES DE NAVIGATION & ACTIONS ---

const onPage = (event) => {
    router.get(route('media.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows
    }, { preserveState: true });
};

const selectFolder = (folderName) => {
    serverFilters.folder = folderName;
};

const openInspector = (file) => {
    metadataForm.id = file.id;
    metadataForm.name = file.name;
    metadataForm.folder = file.folder;
    metadataForm.alt_text = file.properties?.alt_text || '';
    metadataForm.description = file.properties?.description || '';
    metadataForm.tags = file.properties?.tags || [];

    fileSidebar.value = true;
};

const updateMetadata = () => {
    metadataForm.put(route('media.update', metadataForm.id), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Mis à jour', detail: 'Métadonnées synchronisées.', life: 3000 });
            fileSidebar.value = false;
        }
    });
};

const deleteMedia = (id) => {
    if (confirm('Supprimer cet asset définitivement du cloud ?')) {
        router.delete(route('media.destroy', id), {
            onSuccess: () => toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Asset retiré du stockage.', life: 3000 })
        });
    }
};

const handleUpload = () => {
    uploadForm.post(route('media.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Cloud Sync', detail: 'Fichiers envoyés avec succès.', life: 3000 });
            uploadDialog.value = false;
            uploadForm.reset();
        }
    });
};

const copyCdnUrl = (file) => {
    navigator.clipboard.writeText(file.cdn_url);
    toast.add({ severity: 'info', summary: 'Lien Copié', detail: 'URL CDN prête pour l\'intégration.', life: 2000 });
};

const downloadMedia = (id) => {
    window.location.href = route('media.download', id);
};

// --- LOGIQUE VISUELLE ---

const getFileIcon = (type) => {
    const map = {
        video: 'pi pi-play-circle text-rose-500',
        image: 'pi pi-image text-indigo-500',
        pdf: 'pi pi-file-pdf text-amber-500',
        audio: 'pi pi-volume-up text-emerald-500'
    };
    return map[type] || 'pi pi-file text-slate-400';
};

const getCategoryStats = (type) => {
    // Simulé ou extrait de props.storageStats.by_type
    return props.storageStats.by_type.find(t => t.mime_type.includes(type)) || { count: 0, size: 0 };
};

</script>

<template>
    <AppLayout>
        <Head title="Médiathèque Cloud v10 | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen pb-20">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-6">
                        <div class="bg-indigo-600 p-5 rounded-[1.2rem] shadow-2xl shadow-indigo-100 rotate-2 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-cloud text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase italic">Médiathèque Cloud</h1>
                            <p class="text-slate-500 font-medium italic">Stockage objet S3 & Distribution CDN <span class="text-indigo-600 font-bold underline">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-3 rounded-[1.2rem] border border-slate-100 shadow-sm">
                    <Button label="Nouveau Dossier" icon="pi pi-folder-plus" class="p-button-text p-button-secondary rounded-xl font-bold" @click="folderDialog = true" />
                    <Divider layout="vertical" />
                    <Button label="Propager des Assets" icon="pi pi-plus-circle" class="p-button-primary shadow-xl shadow-indigo-200 px-10 rounded-2xl font-black italic" @click="uploadDialog = true" />
                </div>
            </header>

            <div class="grid grid-cols-12 gap-8">

                <!-- --- GAUCHE : NAVIGATION & ANALYTICS --- -->
                <aside class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- Cloud Health & Storage -->
                    <div class="bg-slate-900 rounded-[3.5rem] p-10 text-white shadow-2xl relative overflow-hidden group">
                        <i class="pi pi-database absolute -right-10 -bottom-10 text-[15rem] text-white/5 rotate-12 transition-transform duration-1000 group-hover:rotate-0"></i>

                        <div class="relative z-10">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-300 mb-8 italic">Infrastructure Cloud</h3>

                            <div class="flex items-baseline gap-2 mb-4">
                                <span class="text-5xl font-black italic tracking-tighter">{{ props.storageStats.total_human.split(' ')[0] }}</span>
                                <span class="text-indigo-400 font-bold text-xl uppercase">{{ props.storageStats.total_human.split(' ')[1] }}</span>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between text-[10px] font-black uppercase text-slate-400">
                                    <span>Utilisation du Quota</span>
                                    <span>{{ props.storageStats.usage_percent }}%</span>
                                </div>
                                <ProgressBar :value="props.storageStats.usage_percent" :showValue="false" class="h-2 rounded-full bg-white/10" color="#6366F1" />
                            </div>

                            <div class="mt-10 flex items-center gap-4 p-4 bg-white/5 rounded-2xl border border-white/10">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Serveur S3 Opérationnel</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by File Types -->
                    <div class="bg-white rounded-[3rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 italic">Bibliothèques</h3>

                        <div class="space-y-3">
                            <div v-for="cat in [
                                { id: 'image', label: 'Photothèque', icon: 'pi pi-image', color: 'text-indigo-500', bg: 'bg-indigo-50' },
                                { id: 'video', label: 'Vidéothèque', icon: 'pi pi-play-circle', color: 'text-rose-500', bg: 'bg-rose-50' },
                                { id: 'pdf', label: 'Documents PDF', icon: 'pi pi-file-pdf', color: 'text-amber-500', bg: 'bg-amber-50' },
                                { id: 'audio', label: 'Archives Audio', icon: 'pi pi-volume-up', color: 'text-emerald-500', bg: 'bg-emerald-50' }
                            ]" :key="cat.id"
                                @click="serverFilters.category = cat.id"
                                :class="['flex items-center justify-between p-4 rounded-[1.5rem] transition-all cursor-pointer group', serverFilters.category === cat.id ? 'bg-indigo-600 text-white shadow-xl translate-x-2' : 'hover:bg-slate-50']">

                                <div class="flex items-center gap-4">
                                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shadow-sm', serverFilters.category === cat.id ? 'bg-white/20 text-white' : cat.bg + ' ' + cat.color]">
                                        <i :class="cat.icon"></i>
                                    </div>
                                    <span class="text-sm font-black italic">{{ cat.label }}</span>
                                </div>
                                <i v-if="serverFilters.category === cat.id" class="pi pi-check-circle"></i>
                            </div>
                        </div>

                        <Button v-if="serverFilters.category" label="Toutes les catégories" class="p-button-text p-button-sm w-full mt-6 font-bold" @click="serverFilters.category = null" />
                    </div>

                    <!-- Cloud Folders List -->
                    <div class="bg-indigo-900 rounded-[3rem] p-8 text-white shadow-xl">
                        <h3 class="text-xs font-black uppercase tracking-widest text-indigo-300 mb-6">Dossiers Cloud</h3>
                        <ScrollPanel style="height: 250px">
                            <div class="space-y-2 pr-4">
                                <div v-for="folder in props.folders" :key="folder"
                                    @click="selectFolder(folder)"
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 transition-colors cursor-pointer group">
                                    <i :class="['pi', serverFilters.folder === folder ? 'pi-folder-open text-amber-400' : 'pi-folder text-indigo-400']"></i>
                                    <span :class="['text-xs font-bold truncate', serverFilters.folder === folder ? 'text-white' : 'text-indigo-100']">{{ folder }}</span>
                                </div>
                            </div>
                        </ScrollPanel>
                    </div>
                </aside>

                <!-- --- DROITE : EXPLORATEUR D'ASSETS --- -->
                <main class="col-span-12 lg:col-span-9 space-y-8">

                    <!-- Search & Layout Tools -->
                    <div class="bg-white p-6 rounded-[1.5rem] shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center gap-6 w-full md:w-auto">
                            <div class="flex bg-slate-100 p-1.5 rounded-[1.2rem] shadow-inner">
                                <Button icon="pi pi-th-large" @click="layout = 'grid'" :class="['p-button-sm p-button-text px-4', layout === 'grid' ? 'bg-white shadow-md text-indigo-600' : 'text-slate-400']" />
                                <Button icon="pi pi-list" @click="layout = 'list'" :class="['p-button-sm p-button-text px-4', layout === 'list' ? 'bg-white shadow-md text-indigo-600' : 'text-slate-400']" />
                            </div>
                            <Divider layout="vertical" class="hidden md:block h-8" />
                            <IconField iconPosition="left" class="flex-1 md:w-[400px]">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="serverFilters.search" placeholder="Rechercher par nom, tag ou description..." class="w-full border-none ring-1 ring-slate-100 rounded-2xl bg-slate-50 focus:bg-white focus:ring-4 focus:ring-indigo-100 transition-all p-4" />
                            </IconField>
                        </div>

                        <div class="flex items-center gap-3">
                            <Dropdown v-model="serverFilters.sort_by" :options="[
                                {label: 'Date de création', value: 'created_at'},
                                {label: 'Nom de fichier', value: 'name'},
                                {label: 'Taille', value: 'size'}
                            ]" optionLabel="label" optionValue="value" class="w-48 rounded-xl border-none ring-1 ring-slate-100 bg-slate-50" />

                            <Button icon="pi pi-sync" class="p-button-outlined p-button-secondary rounded-xl" v-tooltip.bottom="'Rafraîchir'" @click="router.reload()" />
                        </div>
                    </div>

                    <!-- Current Path Display -->
                    <div class="flex items-center gap-4 px-4">
                        <div class="flex items-center gap-2 bg-indigo-50 text-indigo-600 px-4 py-2 rounded-full shadow-sm">
                            <i class="pi pi-home text-xs"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">Root</span>
                        </div>
                        <i v-if="serverFilters.folder !== '/'" class="pi pi-chevron-right text-slate-300 text-xs"></i>
                        <div v-if="serverFilters.folder !== '/'" class="flex items-center gap-2 bg-white text-slate-600 px-4 py-2 rounded-full shadow-sm border border-slate-100">
                            <i class="pi pi-folder-open text-xs text-amber-500"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest">{{ serverFilters.folder }}</span>
                            <Button icon="pi pi-times" class="p-button-text p-button-xs p-0 h-4 w-4 ml-2" @click="serverFilters.folder = '/'" />
                        </div>
                    </div>

                    <!-- GRID VIEW --- -->
                    <div v-if="layout === 'grid'" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-8">
                        <div v-for="file in props.assets.data" :key="file.id"
                            @click="openInspector(file)"
                            class="group relative bg-white border border-slate-100 rounded-[3.5rem] p-5 hover:shadow-[0_30px_60px_-15px_rgba(99,102,241,0.2)] hover:-translate-y-2 transition-all duration-500 cursor-pointer overflow-hidden">

                            <!-- Header Decoration -->
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-indigo-200 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <!-- Thumbnail Wrapper -->
                            <div class="aspect-square bg-slate-50 rounded-[1.5rem] overflow-hidden flex items-center justify-center relative border border-slate-50 shadow-inner">
                                <img v-if="file.type === 'image'" :src="file.thumbnail" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                <div v-else class="flex flex-col items-center gap-4">
                                    <i :class="[getFileIcon(file.type), 'text-6xl opacity-30 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500']"></i>
                                    <span v-if="file.duration" class="bg-slate-900/80 text-white text-[9px] font-black px-3 py-1 rounded-full backdrop-blur-md">{{ file.duration }}</span>
                                </div>

                                <!-- Overlay Tools -->
                                <div class="absolute inset-0 bg-indigo-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                                    <Button icon="pi pi-copy" class="p-button-rounded p-button-white shadow-2xl" v-tooltip.top="'Lien CDN'" @click.stop="copyCdnUrl(file)" />
                                    <Button icon="pi pi-download" class="p-button-rounded p-button-white shadow-2xl" @click.stop="downloadMedia(file.id)" />
                                </div>

                                <div class="absolute top-4 left-4">
                                    <Tag :value="file.mime_type.split('/')[1].toUpperCase()" severity="info" class="text-[8px] font-black tracking-[0.2em] rounded-lg px-3 py-1 shadow-sm" />
                                </div>
                            </div>

                            <!-- File Details Footer -->
                            <div class="mt-6 px-2">
                                <h4 class="text-sm font-black text-slate-800 truncate mb-2 group-hover:text-indigo-600 transition-colors">{{ file.name }}</h4>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                        <span class="text-[9px] font-black text-slate-400 uppercase">{{ file.size }}</span>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-300 italic">{{ file.created_at }}</span>
                                </div>
                            </div>

                            <!-- Contextual Floating Button -->
                            <Button icon="pi pi-trash" class="absolute top-6 right-6 p-button-rounded p-button-danger p-button-sm opacity-0 group-hover:opacity-100 transition-all shadow-xl" @click.stop="deleteMedia(file.id)" />
                        </div>
                    </div>

                    <!-- LIST VIEW --- -->
                    <div v-else class="bg-white rounded-[4rem] shadow-xl shadow-slate-200/40 border border-slate-100 overflow-hidden">
                        <DataTable :value="props.assets.data" class="p-datatable-custom" responsiveLayout="stack">
                            <Column field="name" header="Fichier Asset" style="min-width: 25rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-6 py-2">
                                        <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 shadow-sm relative group">
                                            <img v-if="data.type === 'image'" :src="data.thumbnail" class="w-full h-full object-cover rounded-2xl" />
                                            <i v-else :class="getFileIcon(data.type)" class="text-xl"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-base font-black text-slate-700 hover:text-indigo-600 cursor-pointer transition-colors" @click="openInspector(data)">{{ data.name }}</span>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ data.mime_type }}</span>
                                                <Divider layout="vertical" class="h-2 m-0" />
                                                <span class="text-[9px] font-bold text-indigo-400 uppercase">{{ data.author }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column field="size" header="Poids" sortable class="text-xs font-bold text-slate-500" />

                            <Column field="folder" header="Dossier">
                                <template #body="{ data }">
                                    <Chip :label="data.folder" icon="pi pi-folder" class="text-[9px] font-black uppercase bg-slate-50" />
                                </template>
                            </Column>

                            <Column field="created_at" header="Synchronisé" sortable class="text-xs text-slate-400 italic" />

                            <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                                <template #body="{ data }">
                                    <div class="flex justify-end gap-2">
                                        <Button icon="pi pi-external-link" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Lien Public'" @click="copyCdnUrl(data)" />
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="openInspector(data)" />
                                        <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-info" @click="downloadMedia(data.id)" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="deleteMedia(data.id)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- EMPTY STATE --- -->
                    <div v-if="props.assets.data.length === 0" class="py-32 text-center bg-white rounded-[4rem] border-4 border-dashed border-slate-100">
                        <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
                            <i class="pi pi-search-plus text-6xl text-slate-200"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-800 tracking-tighter uppercase italic">Aucun résultat trouvé</h3>
                        <p class="text-slate-400 font-medium max-w-xs mx-auto mt-4">Nous n'avons trouvé aucun média correspondant à vos critères de filtrage actuels.</p>
                        <Button label="Réinitialiser tous les filtres" icon="pi pi-refresh" class="p-button-text p-button-indigo mt-8 font-black uppercase italic"
                            @click="Object.assign(serverFilters, {search: '', category: null, folder: '/'})" />
                    </div>

                    <!-- PAGINATION --- -->
                    <div class="flex justify-center mt-12">
                        <Paginator
                            :rows="props.assets.per_page"
                            :totalRecords="props.assets.total"
                            :first="(props.assets.current_page - 1) * props.assets.per_page"
                            @page="onPage"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                            class="rounded-[1rem] shadow-xl border border-slate-100 px-8 py-4"
                        />
                    </div>
                </main>
            </div>

            <!-- --- SECTION : GUIDE DE L'ADMINISTRATEUR --- -->
            <section class="mt-20 grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2 bg-indigo-900 p-12 rounded-[4rem] text-white shadow-2xl relative overflow-hidden group">
                    <i class="pi pi-bolt absolute -right-10 -bottom-10 text-[18rem] text-white/5 rotate-12 transition-transform duration-700 group-hover:rotate-0"></i>
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-8">
                            <Badge value="Conseil SNC" severity="warning" class="font-black px-4 py-1" />
                            <h3 class="text-3xl font-black tracking-tighter italic uppercase">Optimisation du stockage</h3>
                        </div>
                        <p class="text-indigo-100 text-xl leading-relaxed mb-10 max-w-2xl font-medium">
                            Pour garantir une lecture fluide sur mobile en Afrique Centrale, nous recommandons de convertir vos vidéos en <span class="text-amber-400 font-bold underline">HLS (m3u8)</span>.
                            Le moteur SubNetCongo peut automatiser cette tâche lors de l'upload.
                        </p>
                        <div class="flex gap-4">
                            <Button label="En savoir plus sur le transcodage" class="p-button-warning px-8 rounded-2xl font-black italic shadow-xl shadow-amber-900/50" />
                            <Button label="Ignorer l'astuce" class="p-button-text p-button-white font-bold" />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-12 rounded-[4rem] border border-slate-100 shadow-sm flex flex-col justify-center items-center text-center">
                    <div class="w-20 h-20 bg-emerald-50 rounded-[1rem] flex items-center justify-center text-emerald-500 mb-6 shadow-inner">
                        <i class="pi pi-shield text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-4">Sécurité des Assets</h4>
                    <p class="text-sm text-slate-400 font-medium leading-relaxed italic">
                        Tous vos fichiers sont scannés par notre algorithme antivirus cloud avant d'être persistés sur les clusters S3.
                    </p>
                    <Divider class="my-6" />
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Protection Active : 100%</span>
                </div>
            </section>
        </div>

        <!-- --- MODAL : UPLOAD GLOBAL --- -->
        <Dialog v-model:visible="uploadDialog" modal header="Téléverser vers le Cloud S3" :style="{width: '700px'}" class="rounded-[3.5rem] overflow-hidden">
            <template #header>
                <div class="flex items-center gap-4 p-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg">
                        <i class="pi pi-upload text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Passerelle d'Upload</h2>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Dossier cible : {{ serverFilters.folder }}</p>
                    </div>
                </div>
            </template>

            <div class="p-8 space-y-8">
                <div class="p-16 border-4 border-dashed border-indigo-100 bg-indigo-50/30 rounded-[4rem] text-center flex flex-col items-center justify-center space-y-6 hover:bg-white hover:border-indigo-400 transition-all cursor-pointer group relative">
                    <div class="w-24 h-24 bg-white rounded-[1.5rem] flex items-center justify-center text-indigo-600 shadow-xl group-hover:scale-110 transition-transform duration-500">
                        <i class="pi pi-cloud-upload text-4xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 italic uppercase tracking-tighter">Glissez-déposez vos médias</h3>
                        <p class="text-slate-400 text-sm font-medium mt-2">Images, Vidéos (Max 500MB), Audio & Supports PDF</p>
                    </div>

                    <!-- Real HTML File Input for Inertia -->
                    <input type="file" multiple class="absolute inset-0 opacity-0 cursor-pointer"
                        @change="(e) => uploadForm.files = Array.from(e.target.files)" />

                    <div v-if="uploadForm.files.length > 0" class="w-full">
                        <Divider />
                        <h4 class="text-xs font-black text-indigo-600 uppercase mb-4">{{ uploadForm.files.length }} fichiers sélectionnés</h4>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <Chip v-for="file in uploadForm.files" :key="file.name" :label="file.name" icon="pi pi-file" class="bg-white border border-indigo-100 text-[9px] font-bold" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center justify-between shadow-inner">
                        <div>
                            <span class="text-sm font-black text-slate-700 block italic">Fichiers Privés</span>
                            <p class="text-[9px] text-slate-400 uppercase font-bold">Visible uniquement par vous</p>
                        </div>
                        <InputSwitch v-model="uploadForm.is_private" />
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center justify-between shadow-inner">
                        <div>
                            <span class="text-sm font-black text-slate-700 block italic">Auto-Optimisation</span>
                            <p class="text-[9px] text-slate-400 uppercase font-bold">Compression WebP intelligente</p>
                        </div>
                        <InputSwitch :modelValue="true" />
                    </div>
                </div>

                <div v-if="uploadForm.processing" class="space-y-2">
                    <div class="flex justify-between text-[10px] font-black uppercase text-indigo-600 italic">
                        <span>Flux de données vers S3...</span>
                        <span>{{ uploadForm.progress?.percentage }}%</span>
                    </div>
                    <ProgressBar :value="uploadForm.progress?.percentage" :showValue="false" class="h-3 rounded-full" color="#6366F1" />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full p-8 border-t border-slate-50 bg-slate-50/50">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-black italic uppercase" @click="uploadDialog = false" />
                    <Button label="Lancer la Synchronisation" icon="pi pi-check-circle"
                        :loading="uploadForm.processing"
                        class="p-button-primary px-12 py-4 rounded-[1rem] shadow-2xl shadow-indigo-200 font-black italic text-lg"
                        @click="handleUpload" />
                </div>
            </template>
        </Dialog>

        <!-- --- SIDEBAR : INSPECTEUR DE MÉDIA --- -->
        <Sidebar v-model:visible="fileSidebar" position="right" class="w-full md:w-[550px] p-sidebar-custom">
            <div v-if="metadataForm.id" class="flex flex-col h-full bg-white">

                <!-- Inspector Header -->
                <div class="p-10 bg-slate-900 border-b border-slate-800 text-center relative overflow-hidden">
                    <i class="pi pi-search-plus absolute -right-10 -bottom-10 text-[12rem] text-white/5 rotate-12"></i>

                    <div class="relative z-10">
                        <div class="aspect-video bg-white/5 rounded-[1.5rem] shadow-2xl overflow-hidden mb-8 border border-white/10 flex items-center justify-center group relative">
                            <img v-if="metadataForm.id" :src="props.assets.data.find(f => f.id === metadataForm.id)?.thumbnail" class="w-full h-full object-contain" />
                            <div class="absolute inset-0 bg-indigo-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                                <Button icon="pi pi-eye" label="Plein écran" class="p-button-white p-button-text font-black uppercase italic" />
                            </div>
                        </div>
                        <h2 class="text-2xl font-black text-white italic tracking-tighter break-all">{{ metadataForm.name }}</h2>
                        <div class="flex justify-center gap-3 mt-4">
                            <Badge :value="props.assets.data.find(f => f.id === metadataForm.id)?.mime_type" severity="info" class="text-[9px] font-black uppercase" />
                            <Badge :value="props.assets.data.find(f => f.id === metadataForm.id)?.size" severity="warning" class="text-[9px] font-black uppercase" />
                        </div>
                    </div>
                </div>

                <ScrollPanel class="flex-1">
                    <div class="p-10 space-y-12 pb-24">
                        <!-- Metadata Fields -->
                        <div class="space-y-6">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest italic flex items-center gap-3">
                                <i class="pi pi-pencil text-indigo-500"></i> Édition des Métadonnées
                            </h4>

                            <div class="field">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 ml-2 tracking-widest">Nom de l'Asset</label>
                                <InputText v-model="metadataForm.name" class="w-full rounded-2xl border-slate-200 p-4 font-bold text-slate-700 shadow-sm" />
                            </div>

                            <div class="field">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 ml-2 tracking-widest">Dossier de Rangement</label>
                                <Dropdown v-model="metadataForm.folder" :options="props.folders" class="w-full rounded-2xl border-slate-200 p-2 shadow-sm" />
                            </div>

                            <div class="field">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 ml-2 tracking-widest italic">Texte Alternatif (SEO/Accessibilité)</label>
                                <InputText v-model="metadataForm.alt_text" placeholder="Description courte pour lecteurs d'écran..." class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" />
                            </div>

                            <div class="field">
                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 ml-2 tracking-widest italic">Notes & Description Longue</label>
                                <Textarea v-model="metadataForm.description" rows="4" placeholder="Détails internes sur l'utilisation de cet asset..." class="w-full rounded-3xl border-slate-200 p-4 shadow-sm" />
                            </div>
                        </div>

                        <Divider align="center"><span class="text-[9px] font-black text-slate-300 uppercase italic">Localisation & CDN</span></Divider>

                        <!-- Cloud Info Section -->
                        <div class="space-y-6">
                            <div class="p-6 bg-slate-50 border border-slate-100 rounded-3xl space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Emplacement AWS-S3</span>
                                    <span class="text-[9px] font-mono text-indigo-600 bg-indigo-50 px-2 py-1 rounded">eu-west-3</span>
                                </div>
                                <div class="p-4 bg-white border border-slate-200 rounded-2xl shadow-inner overflow-hidden">
                                    <p class="text-[10px] font-mono text-slate-500 break-all leading-relaxed">{{ props.assets.data.find(f => f.id === metadataForm.id)?.cdn_url }}</p>
                                </div>
                                <Button label="Copier l'URL CDN" icon="pi pi-copy" class="p-button-text p-button-sm w-full font-black uppercase text-[10px]"
                                    @click="copyCdnUrl(props.assets.data.find(f => f.id === metadataForm.id))" />
                            </div>

                            <div class="bg-indigo-900 p-8 rounded-[3rem] text-white shadow-xl relative overflow-hidden group">
                                <i class="pi pi-qrcode absolute -right-4 -bottom-4 text-7xl text-white/5 transition-transform group-hover:rotate-12"></i>
                                <div class="relative z-10">
                                    <h5 class="text-lg font-black italic tracking-tighter mb-2">Partage Mobile Rapide</h5>
                                    <p class="text-[10px] text-indigo-200 font-medium leading-relaxed italic mb-6">
                                        Générez un QR Code dynamique pour permettre aux élèves d'accéder à ce média via un support physique ou externe.
                                    </p>
                                    <Button label="Générer QR-Code SNC" icon="pi pi-qr-code" class="p-button-white p-button-outlined w-full rounded-2xl font-black italic" />
                                </div>
                            </div>
                        </div>
                    </div>
                </ScrollPanel>

                <!-- Inspector Footer -->
                <div class="p-8 border-t border-slate-100 flex gap-4 bg-white">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-black italic flex-1 uppercase" @click="fileSidebar = false" />
                    <Button label="Enregistrer les Métadonnées" icon="pi pi-save"
                        :loading="metadataForm.processing"
                        class="p-button-primary px-10 py-5 rounded-[1.2rem] shadow-2xl shadow-indigo-200 flex-[2] font-black italic text-lg tracking-tighter"
                        @click="updateMetadata" />
                </div>
            </div>
        </Sidebar>

        <!-- --- MODAL : NOUVEAU DOSSIER --- -->
        <Dialog v-model:visible="folderDialog" modal header="Créer une unité de rangement" :style="{width: '450px'}" class="rounded-[3rem] overflow-hidden">
            <div class="p-10 space-y-8">
                <div class="w-20 h-20 bg-amber-50 rounded-[1.5rem] flex items-center justify-center text-amber-500 mx-auto shadow-inner">
                    <i class="pi pi-folder-plus text-3xl"></i>
                </div>

                <div class="field">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3 italic text-center">Nom de l'unité virtuelle</label>
                    <InputText v-model="folderForm.name" placeholder="Ex: Vidéos Chapitre 01" class="w-full p-4 rounded-2xl border-slate-200 text-center font-bold text-lg shadow-sm" autofocus />
                </div>

                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                    <p class="text-[10px] text-slate-500 text-center font-medium italic leading-relaxed">
                        Note : Les dossiers sont "virtuels". Un dossier n'existe que s'il contient au moins un asset numérique.
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-center gap-3 p-8 border-t border-slate-50">
                    <Button label="Abandonner" class="p-button-text p-button-secondary font-black italic uppercase" @click="folderDialog = false" />
                    <Button label="Valider la création" icon="pi pi-check" class="p-button-primary px-8 py-3 rounded-2xl font-black italic shadow-lg" @click="folderDialog = false" />
                </div>
            </template>
        </Dialog>



    </AppLayout>
</template>

<style scoped>
/**
 * ======================================================================================
 * CUSTOM STYLES - SUBNETCONGO MEDIA ENGINE UI
 * ======================================================================================
 */

/* Datatable & Grid Personalization */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 2.5rem 1.5rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #fcfcff !important;
}

/* Sidebar Customisation */
:deep(.p-sidebar-custom) {
    border-radius: 5rem 0 0 5rem;
    box-shadow: -30px 0 80px rgba(0,0,0,0.15);
}

:deep(.p-sidebar-header) {
    display: none;
}

/* UI Elements Override */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 1.25rem;
    border-color: #f1f5f9;
    transition: all 0.3s;
}

:deep(.p-inputtext:focus) {
    border-color: #6366F1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

/* Progress Bars */
:deep(.p-progressbar) {
    background: rgba(255,255,255,0.1);
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Animations */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.group {
    animation: fadeInUp 0.5s ease-out forwards;
}

/* Scrollbar Style */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f8fafc;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* Custom SelectButton */
:deep(.custom-selectbutton .p-button) {
    background: #fff;
    border: 2px solid #f1f5f9;
    flex: 1;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.7rem;
    padding: 1.2rem;
}

:deep(.custom-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
    .p-sidebar-custom {
        border-radius: 0;
    }
}
</style>
