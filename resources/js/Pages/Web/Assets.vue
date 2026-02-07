<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import DataView from 'primevue/dataview';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import Menu from 'primevue/menu';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import SelectButton from 'primevue/selectbutton';
import ContextMenu from 'primevue/contextmenu';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Tooltip from 'primevue/tooltip';

const toast = useToast();

// --- STATE & DATA ---
const layout = ref('grid'); // 'grid' or 'list'
const selectedFiles = ref([]);
const fileSidebar = ref(false);
const uploadDialog = ref(false);
const activeCategory = ref('Tous');
const folderDialog = ref(false);
const newFolderName = ref('');
const searchKeyword = ref('');
const cm = ref(); // Context Menu

const categories = ref([
    { label: 'Tous', icon: 'pi pi-folder-open', count: 124, color: 'text-slate-600' },
    { label: 'Images', icon: 'pi pi-image', count: 85, color: 'text-indigo-600' },
    { label: 'Vidéos', icon: 'pi pi-video', count: 12, color: 'text-rose-600' },
    { label: 'Documents', icon: 'pi pi-file-pdf', count: 22, color: 'text-amber-600' },
    { label: 'Archives', icon: 'pi pi-box', count: 5, color: 'text-emerald-600' }
]);

const home = ref({ icon: 'pi pi-home', to: '/' });
const items = ref([
    { label: 'Administration' },
    { label: 'Site Web' },
    { label: 'Fichiers Publics', class: 'font-bold' }
]);

// --- MOCK DATA GENERATION ---
const files = ref([
    { id: 1, name: 'Bannière_Accueil.png', type: 'image', size: '2.4 MB', date: '12 Jan 2024', url: 'https://picsum.photos/seed/1/400/300', category: 'Images' },
    { id: 2, name: 'Formation_Laravel_Full.mp4', type: 'video', size: '145 MB', date: '05 Jan 2024', url: '', category: 'Vidéos' },
    { id: 3, name: 'Guide_Etudiant_2024.pdf', type: 'pdf', size: '1.2 MB', date: '20 Déc 2023', url: '', category: 'Documents' },
    { id: 4, name: 'Assets_Icones.zip', type: 'archive', size: '45 MB', date: '15 Déc 2023', url: '', category: 'Archives' },
    { id: 5, name: 'Logo_Subnet_Congo.svg', type: 'image', size: '12 KB', date: '10 Déc 2023', url: 'https://picsum.photos/seed/logo/400/300', category: 'Images' },
    { id: 6, name: 'Intro_Motion_Design.mov', type: 'video', size: '89 MB', date: '01 Déc 2023', url: '', category: 'Vidéos' },
    { id: 7, name: 'Contrat_Enseignant.docx', type: 'doc', size: '450 KB', date: '28 Nov 2023', url: '', category: 'Documents' },
    { id: 8, name: 'Team_Meeting_Jan.png', type: 'image', size: '3.1 MB', date: '02 Jan 2024', url: 'https://picsum.photos/seed/team/400/300', category: 'Images' },
    { id: 9, name: 'Database_Backup.sql', type: 'file', size: '12 MB', date: '25 Déc 2023', url: '', category: 'Archives' },
    { id: 10, name: 'Webinar_CyberSecurity.mp4', type: 'video', size: '210 MB', date: '14 Nov 2023', url: '', category: 'Vidéos' },
]);

const selectedFile = ref(files.value[0]);

// --- COMPUTED ---
const filteredFiles = computed(() => {
    let result = files.value;
    if (activeCategory.value !== 'Tous') {
        result = result.filter(f => f.category === activeCategory.value);
    }
    if (searchKeyword.value) {
        result = result.filter(f => f.name.toLowerCase().includes(searchKeyword.value.toLowerCase()));
    }
    return result;
});

const storageUsage = ref(65); // 65% utilized

// --- METHODS ---
const getFileIcon = (type) => {
    switch (type) {
        case 'image': return 'pi pi-image text-indigo-500';
        case 'video': return 'pi pi-video text-rose-500';
        case 'pdf': return 'pi pi-file-pdf text-orange-500';
        case 'archive': return 'pi pi-box text-emerald-500';
        default: return 'pi pi-file text-slate-400';
    }
};

const formatSize = (bytes) => {
    return bytes; // Simple mock string in our data
};

const onUpload = () => {
    toast.add({ severity: 'info', summary: 'Succès', detail: 'Fichier téléchargé avec succès', life: 3000 });
    uploadDialog.value = false;
};

const deleteFile = (file) => {
    files.value = files.value.filter(f => f.id !== file.id);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Fichier déplacé vers la corbeille', life: 3000 });
};

const selectFileDetails = (file) => {
    selectedFile.value = file;
    fileSidebar.value = true;
};

const menuItems = ref([
    { label: 'Renommer', icon: 'pi pi-pencil' },
    { label: 'Télécharger', icon: 'pi pi-download' },
    { label: 'Déplacer', icon: 'pi pi-directions' },
    { separator: true },
    { label: 'Supprimer', icon: 'pi pi-trash', class: 'text-red-500' }
]);

const onRightClick = (event, file) => {
    selectedFile.value = file;
    cm.value.show(event);
};

</script>

<template>
    <AppLayout>
        <Head title="Assets & Fichiers Publics" />
        <Toast />
        <ContextMenu ref="cm" :model="menuItems" />

        <div class="flex flex-col h-screen bg-[#F8FAFC]">

            <!-- HEADER BAR -->
            <header class="bg-white border-b border-slate-200 p-4 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col">
                    <Breadcrumb :home="home" :model="items" class="bg-transparent border-none p-0 mb-1 text-xs" />
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Fichiers Publics</h1>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <IconField iconPosition="left" class="flex-1 md:w-80">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="searchKeyword" placeholder="Rechercher un asset..." class="w-full bg-slate-50 border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-500 rounded-xl" />
                    </IconField>
                    <Button icon="pi pi-plus" label="Nouveau Dossier" class="p-button-outlined p-button-secondary rounded-xl hidden lg:flex" @click="folderDialog = true" />
                    <Button icon="pi pi-upload" label="Upload" class="p-button-primary rounded-xl shadow-lg shadow-indigo-100" @click="uploadDialog = true" />
                </div>
            </header>

            <div class="flex flex-1 overflow-hidden">

                <!-- LEFT SIDEBAR: CATEGORIES & STORAGE -->
                <aside class="w-72 bg-white border-r border-slate-200 hidden lg:flex flex-col p-6">
                    <div class="mb-8">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Bibliothèques</h3>
                        <nav class="space-y-1">
                            <div v-for="cat in categories" :key="cat.label"
                                @click="activeCategory = cat.label"
                                :class="['flex items-center justify-between p-3 rounded-xl cursor-pointer transition-all', activeCategory === cat.label ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-600 hover:bg-slate-50']">
                                <div class="flex items-center gap-3">
                                    <i :class="[cat.icon, activeCategory === cat.label ? 'text-indigo-600' : cat.color]"></i>
                                    <span>{{ cat.label }}</span>
                                </div>
                                <Badge :value="cat.count" :severity="activeCategory === cat.label ? 'primary' : 'secondary'" class="text-[10px]" />
                            </div>
                        </nav>
                    </div>

                    <Divider />

                    <div class="mt-auto p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-slate-500 uppercase">Stockage Cloud</span>
                            <span class="text-xs font-black text-indigo-600">65%</span>
                        </div>
                        <ProgressBar :value="storageUsage" :showValue="false" style="height: 6px" class="rounded-full" />
                        <p class="text-[10px] text-slate-400 mt-2 font-medium text-center">3.2 GB sur 5 GB utilisés</p>
                        <Button label="Augmenter quota" class="p-button-text p-button-sm w-full mt-2 text-[10px] font-bold" />
                    </div>
                </aside>

                <!-- MAIN CONTENT AREA -->
                <main class="flex-1 overflow-y-auto p-6 lg:p-8">

                    <!-- TOOLBAR -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-4">
                            <div class="flex bg-white rounded-lg p-1 border border-slate-200">
                                <Button icon="pi pi-th-large" @click="layout = 'grid'" :class="['p-button-sm p-button-text', layout === 'grid' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400']" />
                                <Button icon="pi pi-list" @click="layout = 'list'" :class="['p-button-sm p-button-text', layout === 'list' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400']" />
                            </div>
                            <span class="text-sm font-bold text-slate-500">{{ filteredFiles.length }} Éléments</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Dropdown placeholder="Trier par" :options="['Nom', 'Date', 'Taille']" class="p-inputtext-sm border-none bg-white shadow-sm ring-1 ring-slate-200" />
                        </div>
                    </div>

                    <!-- GRID VIEW -->
                    <div v-if="layout === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        <div v-for="file in filteredFiles" :key="file.id"
                            @contextmenu="onRightClick($event, file)"
                            @click="selectFileDetails(file)"
                            class="group relative bg-white border border-slate-100 rounded-2xl p-4 hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-200 transition-all cursor-pointer">

                            <!-- Thumbnail -->
                            <div class="aspect-video mb-4 rounded-xl bg-slate-50 overflow-hidden flex items-center justify-center border border-slate-50">
                                <img v-if="file.type === 'image'" :src="file.url" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                <i v-else :class="[getFileIcon(file.type), 'text-4xl']"></i>
                            </div>

                            <!-- Info -->
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-slate-700 truncate mb-1">{{ file.name }}</span>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ file.size }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ file.date }}</span>
                                </div>
                            </div>

                            <!-- Actions Overlay (on hover) -->
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                <Button icon="pi pi-download" class="p-button-rounded p-button-secondary p-button-sm w-8 h-8 bg-white/90" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm w-8 h-8 bg-white/90" @click.stop="deleteFile(file)" />
                            </div>
                        </div>
                    </div>

                    <!-- LIST VIEW -->
                    <div v-else class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                    <th class="p-4">Nom</th>
                                    <th class="p-4">Type</th>
                                    <th class="p-4">Date</th>
                                    <th class="p-4">Taille</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="file in filteredFiles" :key="file.id"
                                    @click="selectFileDetails(file)"
                                    class="border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer group">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <i :class="[getFileIcon(file.type), 'text-xl']"></i>
                                            <span class="text-sm font-bold text-slate-700">{{ file.name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <Tag :value="file.category" severity="secondary" class="text-[10px] uppercase font-bold" />
                                    </td>
                                    <td class="p-4 text-sm text-slate-500 font-medium">{{ file.date }}</td>
                                    <td class="p-4 text-sm text-slate-500 font-medium">{{ file.size }}</td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Button icon="pi pi-download" class="p-button-text p-button-secondary p-button-sm" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click.stop="deleteFile(file)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>

        <!-- --- DIALOGS --- -->

        <!-- Upload Dialog -->
        <Dialog v-model:visible="uploadDialog" header="Ajouter des fichiers" :modal="true" :style="{ width: '500px' }" class="p-fluid">
            <div class="p-4">
                <FileUpload name="demo[]" url="/api/upload" @upload="onUpload" :multiple="true" accept="image/*,video/*,application/pdf" :maxFileSize="100000000">
                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-8 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50">
                            <i class="pi pi-cloud-upload text-4xl text-indigo-400 mb-4"></i>
                            <p class="text-slate-600 font-bold">Glissez-déposez vos fichiers ici</p>
                            <p class="text-slate-400 text-xs mt-1">Images, Vidéos ou PDF (Max 100MB)</p>
                        </div>
                    </template>
                </FileUpload>
            </div>
        </Dialog>

        <!-- New Folder Dialog -->
        <Dialog v-model:visible="folderDialog" header="Créer un dossier" :modal="true" :style="{ width: '350px' }">
            <div class="p-fluid mt-2">
                <div class="field">
                    <label class="font-bold text-sm text-slate-700 mb-2 block">Nom du dossier</label>
                    <InputText v-model="newFolderName" placeholder="Ex: Cours de PHP 2024" autofocus />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="folderDialog = false" />
                <Button label="Créer" icon="pi pi-check" class="p-button-primary rounded-xl px-4" @click="folderDialog = false" />
            </template>
        </Dialog>

        <!-- Sidebar Details -->
        <Sidebar v-model:visible="fileSidebar" position="right" class="w-full md:w-[400px]">
            <div v-if="selectedFile" class="flex flex-col h-full p-4">
                <h2 class="text-xl font-black text-slate-900 mb-6">Détails du fichier</h2>

                <div class="aspect-video bg-slate-100 rounded-2xl overflow-hidden mb-6 border border-slate-200 flex items-center justify-center">
                    <img v-if="selectedFile.type === 'image'" :src="selectedFile.url" class="w-full h-full object-contain" />
                    <i v-else :class="[getFileIcon(selectedFile.type), 'text-6xl']"></i>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nom du fichier</label>
                        <p class="font-bold text-slate-800 break-all">{{ selectedFile.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Taille</label>
                            <p class="font-bold text-slate-800">{{ selectedFile.size }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Type</label>
                            <Tag :value="selectedFile.type" severity="info" class="font-bold uppercase" />
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Dernière modification</label>
                        <p class="font-medium text-slate-600 italic">{{ selectedFile.date }}</p>
                    </div>

                    <Divider />

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Lien public</label>
                        <div class="flex gap-2 mt-2">
                            <InputText readonly :value="'https://cdn.subnetcongo.cd/assets/' + selectedFile.id" class="text-xs flex-1 bg-slate-50 border-slate-200" />
                            <Button icon="pi pi-copy" class="p-button-outlined p-button-secondary" v-tooltip.top="'Copier le lien'" />
                        </div>
                    </div>
                </div>

                <div class="mt-auto grid grid-cols-2 gap-3 pt-6 border-t border-slate-100">
                    <Button label="Aperçu" icon="pi pi-external-link" class="p-button-outlined" />
                    <Button label="Télécharger" icon="pi pi-download" class="p-button-primary" />
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
:deep(.p-breadcrumb) {
    background: transparent;
    padding: 0;
}

:deep(.p-dataview .p-dataview-content) {
    background: transparent;
}

/* Scrollbar Customization */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

:deep(.p-progressbar) {
    background: #e2e8f0;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

:deep(.p-tag) {
    padding: 0.25rem 0.6rem;
}

:deep(.p-dialog-header) {
    padding: 1.5rem;
    border-radius: 1.5rem 1.5rem 0 0;
}

:deep(.p-sidebar) {
    border-radius: 2rem 0 0 2rem;
}
</style>
