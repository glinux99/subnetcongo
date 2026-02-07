<script setup>
/**
 * ADVANCED MEDIA LIBRARY & ASSET MANAGEMENT - SUBNETCONGO LMS
 * Version: 8.2.0
 * Description: Gestionnaire centralisé des ressources pédagogiques multimédias.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataView from 'primevue/dataview';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import Breadcrumb from 'primevue/breadcrumb';
import Menu from 'primevue/menu';
import ContextMenu from 'primevue/contextmenu';
import ScrollPanel from 'primevue/scrollpanel';
import SelectButton from 'primevue/selectbutton';
import Slider from 'primevue/slider';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Chip from 'primevue/chip';
import OverlayPanel from 'primevue/overlaypanel';
import Skeleton from 'primevue/skeleton';
import Message from 'primevue/message';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const layout = ref('grid'); // 'grid' or 'list'
const uploadDialog = ref(false);
const fileSidebar = ref(false);
const folderDialog = ref(false);
const cm = ref(); // Context Menu reference

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'E-learning' },
    { label: 'Bibliothèque Médias', class: 'font-bold text-indigo-600' }
]);

// --- CATEGORIES & STORAGE STATS ---
const categories = ref([
    { label: 'Vidéos', icon: 'pi pi-video', count: 142, size: '85.4 GB', color: 'text-rose-500', bg: 'bg-rose-50' },
    { label: 'Images', icon: 'pi pi-image', count: 1250, size: '4.2 GB', color: 'text-indigo-500', bg: 'bg-indigo-50' },
    { label: 'Documents', icon: 'pi pi-file-pdf', count: 450, size: '1.8 GB', color: 'text-amber-500', bg: 'bg-amber-50' },
    { label: 'Audios', icon: 'pi pi-volume-up', count: 85, size: '2.1 GB', color: 'text-emerald-500', bg: 'bg-emerald-50' }
]);

const storageUsage = reactive({
    used: 93.5,
    total: 250,
    percent: 37
});

// --- MOCK DATA: MEDIA FILES ---
const files = ref(Array.from({ length: 40 }, (_, i) => ({
    id: i + 1,
    name: i % 3 === 0 ? `Module_Video_Cours_${i}.mp4` : (i % 2 === 0 ? `Schema_Architecture_${i}.png` : `Guide_Apprenant_v${i}.pdf`),
    type: i % 3 === 0 ? 'video' : (i % 2 === 0 ? 'image' : 'pdf'),
    size: (Math.random() * 50 + 1).toFixed(1) + (i % 3 === 0 ? ' MB' : ' KB'),
    url: `https://picsum.photos/seed/${i}/400/300`,
    created_at: '2024-02-01',
    dimensions: i % 2 === 0 ? '1920x1080' : null,
    duration: i % 3 === 0 ? '12:45' : null,
    author: 'Daniel Kikimba',
    folder: 'Cours Laravel'
})));

// --- UI STATE & SELECTION ---
const selectedFile = ref(null);
const selectedFiles = ref([]);
const searchKeyword = ref('');
const activeFolder = ref('Tous les fichiers');

const contextMenuItems = ref([
    { label: 'Ouvrir', icon: 'pi pi-external-link' },
    { label: 'Copier le lien CDN', icon: 'pi pi-copy' },
    { label: 'Renommer', icon: 'pi pi-pencil' },
    { label: 'Déplacer', icon: 'pi pi-directions' },
    { separator: true },
    { label: 'Supprimer', icon: 'pi pi-trash', class: 'text-rose-500 font-bold' }
]);

// --- FILTERS LOGIC ---
const filteredFiles = computed(() => {
    let result = files.value;
    if (searchKeyword.value) {
        result = result.filter(f => f.name.toLowerCase().includes(searchKeyword.value.toLowerCase()));
    }
    return result;
});

// --- METHODS ---
const onFileSelect = (event, file) => {
    selectedFile.value = file;
    fileSidebar.value = true;
};

const onRightClick = (event, file) => {
    selectedFile.value = file;
    cm.value.show(event);
};

const getFileIcon = (type) => {
    switch (type) {
        case 'video': return 'pi pi-play-circle text-rose-500';
        case 'image': return 'pi pi-image text-indigo-500';
        case 'pdf': return 'pi pi-file-pdf text-amber-500';
        default: return 'pi pi-file text-slate-400';
    }
};

const deleteFile = (id) => {
    files.value = files.value.filter(f => f.id !== id);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Le média a été retiré de la bibliothèque.' });
};

const onUpload = () => {
    toast.add({ severity: 'success', summary: 'Upload Terminé', detail: 'Vos fichiers sont prêts.', life: 3000 });
    uploadDialog.value = false;
};

const copyCdnLink = () => {
    navigator.clipboard.writeText('https://cdn.subnetcongo.cd/media/' + selectedFile.value.name);
    toast.add({ severity: 'info', summary: 'Lien Copié', detail: 'Lien CDN prêt à être inséré dans un cours.', life: 2000 });
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 1000);
});

</script>

<template>
    <AppLayout>
        <Head title="Médiathèque Cloud | SubnetCongo" />
        <Toast />
        <ContextMenu ref="cm" :model="contextMenuItems" />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 rotate-2 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-cloud-upload text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Médiathèque Cloud</h1>
                            <p class="text-slate-500 font-medium italic">Gérez vos assets pédagogiques et diffusez-les sur le CDN <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button label="Nouveau Dossier" icon="pi pi-folder-plus" class="p-button-outlined p-button-secondary bg-white rounded-2xl" @click="folderDialog = true" />
                    <Button label="Upload Médias" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-2xl font-bold" @click="uploadDialog = true" />
                </div>
            </header>

            <div class="grid grid-cols-12 gap-8">

                <!-- --- LEFT SIDEBAR: CATEGORIES & STORAGE --- -->
                <aside class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- Storage Analytics Card -->
                    <div class="bg-slate-900 rounded-[3rem] p-8 text-white shadow-2xl relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 text-9xl text-white/5 opacity-20"><i class="pi pi-database"></i></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Stockage Utilisé</h3>

                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-4xl font-black">{{ storageUsage.used }}</span>
                            <span class="text-slate-400 font-bold">/ {{ storageUsage.total }} GB</span>
                        </div>
                        <ProgressBar :value="storageUsage.percent" :showValue="false" class="h-2 rounded-full mb-4 bg-white/10" color="#6366F1" />
                        <p class="text-[10px] font-medium text-slate-400 italic">37% de votre quota entreprise utilisé.</p>

                        <Button label="Augmenter le forfait" class="w-full mt-8 p-button-outlined p-button-secondary border-white/20 text-white font-bold rounded-xl" />
                    </div>

                    <!-- Category Navigation -->
                    <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Bibliothèques</h3>
                        <div class="space-y-2">
                            <div v-for="cat in categories" :key="cat.label" class="flex items-center justify-between p-4 rounded-2xl hover:bg-slate-50 transition-all cursor-pointer group">
                                <div class="flex items-center gap-4">
                                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shadow-sm transition-all group-hover:scale-110', cat.bg, cat.color]">
                                        <i :class="cat.icon"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">{{ cat.label }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold">{{ cat.count }} fichiers</p>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-slate-300 group-hover:text-indigo-600">{{ cat.size }}</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- --- MAIN EXPLORER AREA --- -->
                <main class="col-span-12 lg:col-span-9 space-y-6">

                    <!-- Search & Tools Bar -->
                    <div class="bg-white p-4 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="flex bg-slate-100 p-1 rounded-xl">
                                <Button icon="pi pi-th-large" @click="layout = 'grid'" :class="['p-button-sm p-button-text', layout === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-400']" />
                                <Button icon="pi pi-list" @click="layout = 'list'" :class="['p-button-sm p-button-text', layout === 'list' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-400']" />
                            </div>
                            <Divider layout="vertical" class="hidden md:block" />
                            <IconField iconPosition="left" class="flex-1 md:w-96">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="searchKeyword" placeholder="Chercher dans les médias..." class="w-full border-none ring-1 ring-slate-100 rounded-xl bg-slate-50 focus:bg-white transition-all" />
                            </IconField>
                        </div>
                        <div class="flex gap-2">
                            <Dropdown placeholder="Trier par" :options="['Nom', 'Date', 'Taille']" class="w-40 border-none bg-slate-50 rounded-xl ring-1 ring-slate-100" />
                        </div>
                    </div>

                    <!-- Grid Explorer -->
                    <div v-if="layout === 'grid'" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div v-for="file in filteredFiles" :key="file.id"
                            @contextmenu="onRightClick($event, file)"
                            @click="onFileSelect($event, file)"
                            class="group relative bg-white border border-slate-100 rounded-[2.5rem] p-4 hover:shadow-2xl hover:shadow-indigo-100/50 hover:-translate-y-1 transition-all duration-300 cursor-pointer">

                            <!-- Thumbnail Area -->
                            <div class="aspect-square bg-slate-50 rounded-[2rem] overflow-hidden flex items-center justify-center relative border border-slate-50">
                                <img v-if="file.type === 'image'" :src="file.url" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                <i v-else :class="[getFileIcon(file.type), 'text-5xl opacity-40 group-hover:opacity-100 group-hover:scale-110 transition-all']"></i>

                                <!-- File Format Badge -->
                                <div class="absolute top-3 left-3">
                                    <Tag :value="file.type.toUpperCase()" :severity="file.type === 'video' ? 'danger' : 'info'" class="text-[8px] font-black tracking-widest rounded-lg px-2" />
                                </div>
                            </div>

                            <!-- Info Footer -->
                            <div class="mt-4 px-2">
                                <h4 class="text-sm font-black text-slate-800 truncate mb-1">{{ file.name }}</h4>
                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                    <span>{{ file.size }}</span>
                                    <span>{{ file.created_at }}</span>
                                </div>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col gap-2">
                                <Button icon="pi pi-copy" class="p-button-rounded p-button-secondary p-button-sm shadow-xl bg-white/90" v-tooltip.left="'Lien CDN'" @click.stop="copyCdnLink" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm shadow-xl bg-white/90" @click.stop="deleteFile(file.id)" />
                            </div>
                        </div>
                    </div>

                    <!-- List Explorer -->
                    <div v-else class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                        <DataTable :value="filteredFiles" class="p-datatable-custom" responsiveLayout="stack">
                            <Column field="name" header="Fichier" style="min-width: 20rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100">
                                            <i :class="getFileIcon(data.type)"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800">{{ data.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.folder }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column field="size" header="Taille" sortable />
                            <Column field="type" header="Type">
                                <template #body="{ data }">
                                    <Tag :value="data.type" severity="secondary" class="text-[9px] font-black uppercase tracking-widest" />
                                </template>
                            </Column>
                            <Column field="created_at" header="Ajouté le" sortable />
                            <Column header="Actions" class="text-right">
                                <template #body="{ data }">
                                    <div class="flex justify-end gap-1">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" />
                                        <Button icon="pi pi-download" class="p-button-rounded p-button-text p-button-info" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="deleteFile(data.id)" />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <div v-if="filteredFiles.length === 0" class="py-20 text-center bg-white rounded-[3rem] border border-dashed border-slate-200">
                        <i class="pi pi-search text-5xl text-slate-200 mb-4"></i>
                        <p class="text-xl font-bold text-slate-400 italic">Aucun média ne correspond à votre recherche.</p>
                        <Button label="Réinitialiser" class="p-button-text p-button-indigo mt-4 font-bold" @click="searchKeyword = ''" />
                    </div>
                </main>
            </div>
        </div>

        <!-- --- UPLOAD DIALOG --- -->
        <Dialog v-model:visible="uploadDialog" :style="{width: '600px'}" header="Upload Vers le Cloud" :modal="true" class="p-fluid rounded-3xl overflow-hidden">
            <div class="p-4 space-y-6">
                <div class="p-10 border-2 border-dashed border-indigo-200 bg-indigo-50/30 rounded-[2.5rem] text-center flex flex-col items-center justify-center space-y-4 hover:bg-white transition-colors cursor-pointer group">
                    <div class="w-20 h-20 bg-indigo-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-indigo-100 group-hover:scale-110 transition-transform">
                        <i class="pi pi-upload text-4xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800">Glissez-déposez vos fichiers</h3>
                        <p class="text-slate-400 text-sm italic">Images, Vidéos (4K supportée), PDF, Audio (Max 500MB)</p>
                    </div>
                    <FileUpload mode="basic" chooseLabel="Sélectionner manuellement" class="rounded-xl px-6 font-bold" @upload="onUpload" />
                </div>

                <div class="space-y-4">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Réglages d'upload</h4>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                        <span class="text-sm font-bold text-slate-600">Convertir automatiquement en WebP</span>
                        <InputSwitch :modelValue="true" />
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                        <span class="text-sm font-bold text-slate-600">Générer des thumbnails</span>
                        <InputSwitch :modelValue="true" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- --- FILE INSPECTOR SIDEBAR --- -->
        <Sidebar v-model:visible="fileSidebar" position="right" class="w-full md:w-[450px] p-sidebar-custom">
            <div v-if="selectedFile" class="flex flex-col h-full bg-white">

                <!-- Preview Header -->
                <div class="p-8 bg-slate-50 border-b border-slate-100 text-center">
                    <div class="aspect-video bg-white rounded-3xl shadow-xl overflow-hidden mb-6 border border-white flex items-center justify-center">
                        <img v-if="selectedFile.type === 'image'" :src="selectedFile.url" class="w-full h-full object-contain" />
                        <i v-else :class="[getFileIcon(selectedFile.type), 'text-7xl']"></i>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 leading-tight break-all">{{ selectedFile.name }}</h2>
                    <Tag :value="selectedFile.type" severity="info" class="mt-2 text-[9px] uppercase font-black tracking-widest" />
                </div>

                <ScrollPanel class="flex-1 p-8">
                    <div class="space-y-8">
                        <!-- Details Section -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Informations Techniques</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Taille</p>
                                    <p class="text-sm font-bold text-slate-700">{{ selectedFile.size }}</p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-2xl">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Dimensions</p>
                                    <p class="text-sm font-bold text-slate-700">{{ selectedFile.dimensions || 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Emplacement Cloud</p>
                                <p class="text-xs font-mono text-indigo-600 break-all">s3://subnet-congo/media/{{ selectedFile.name }}</p>
                            </div>
                        </div>

                        <Divider />

                        <!-- Marketing/Integration Section -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Intégration & Partage</h4>
                            <div class="field">
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-2">Lien Public CDN</label>
                                <div class="p-inputgroup">
                                    <InputText readonly :value="'https://cdn.subnetcongo.cd/assets/' + selectedFile.id" class="text-xs bg-slate-50 border-none ring-1 ring-slate-100" />
                                    <Button icon="pi pi-copy" class="p-button-secondary" @click="copyCdnLink" />
                                </div>
                            </div>
                            <div class="bg-indigo-900 p-6 rounded-[2rem] text-white shadow-xl relative overflow-hidden group">
                                <div class="relative z-10">
                                    <h5 class="font-black italic mb-1">Générer un QR Code</h5>
                                    <p class="text-[10px] text-indigo-300 font-medium leading-relaxed">Permet aux élèves de scanner ce média sur mobile directement depuis un support papier.</p>
                                    <Button label="Générer" class="mt-4 p-button-sm p-button-white p-button-outlined w-full rounded-xl font-black" />
                                </div>
                                <i class="pi pi-qrcode absolute -right-4 -bottom-4 text-7xl text-white/5"></i>
                            </div>
                        </div>
                    </div>
                </ScrollPanel>

                <div class="p-8 border-t border-slate-100 flex gap-4 bg-white">
                    <Button label="Remplacer" icon="pi pi-refresh" class="flex-1 p-button-outlined rounded-2xl font-bold" />
                    <Button label="Télécharger" icon="pi pi-download" class="flex-1 p-button-primary rounded-2xl font-bold shadow-lg shadow-indigo-100" />
                </div>
            </div>
        </Sidebar>

        <!-- --- NEW FOLDER DIALOG --- -->
        <Dialog v-model:visible="folderDialog" :style="{width: '400px'}" header="Créer un Dossier" :modal="true">
            <div class="field pt-4">
                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Nom du dossier</label>
                <InputText placeholder="Ex: Ressources Marketing" class="p-3 rounded-xl" autofocus />
            </div>
            <template #footer>
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="folderDialog = false" />
                <Button label="Créer le dossier" icon="pi pi-check" class="p-button-primary px-6 rounded-xl shadow-lg" @click="folderDialog = false" />
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO MEDIA ENGINE
 */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 2rem 1.5rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #fcfcff !important;
}

/* Sidebar Customization */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
}

/* Input Overrides */
:deep(.p-inputtext), :deep(.p-dropdown) {
    border-radius: 12px;
    border-color: #f1f5f9;
}

:deep(.p-progressbar) {
    background: rgba(255,255,255,0.1);
}

/* Scrollbar Smooth */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

/* Media Cards Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.group {
    animation: fadeIn 0.4s ease-out forwards;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
