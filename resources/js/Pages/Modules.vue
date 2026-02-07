<script setup>
/**
 * PEDAGOGICAL STRUCTURE & LESSON MANAGER - SUBNETCONGO LMS
 * Version: 9.0.0
 * Description: Gestionnaire avancé des chapitres (modules) et des leçons individuelles.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import InputSwitch from 'primevue/inputswitch';
import SelectButton from 'primevue/selectbutton';
import Editor from 'primevue/editor';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import FileUpload from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import OverlayPanel from 'primevue/overlaypanel';
import Rating from 'primevue/rating';
import Listbox from 'primevue/listbox';
import MultiSelect from 'primevue/multiselect';
import Menu from 'primevue/menu';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const moduleDialog = ref(false);
const lessonSidebar = ref(false);
const expandedRows = ref([]);
const activeTab = ref(0);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Contenu Pédagogique' },
    { label: 'Leçons & Modules', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const courses = ref([
    { name: 'Laravel Mastery 11', id: 1 },
    { name: 'Vue.js 3 Fundamentals', id: 2 },
    { name: 'UI/UX Design Pro', id: 3 },
    { name: 'CyberSecurity Advanced', id: 4 }
]);

const lessonTypes = ref([
    { label: 'Vidéo HD', value: 'video', icon: 'pi pi-play-circle' },
    { label: 'Document PDF', value: 'pdf', icon: 'pi pi-file-pdf' },
    { label: 'Quiz Interactif', value: 'quiz', icon: 'pi pi-question-circle' },
    { label: 'Texte / Article', value: 'text', icon: 'pi pi-align-left' },
    { label: 'Lien Externe', value: 'link', icon: 'pi pi-external-link' }
]);

// --- MOCK DATA: MODULES & LESSONS ---
const modules = ref([
    {
        id: 1,
        title: 'Fondamentaux du Framework',
        course_name: 'Laravel Mastery 11',
        order: 1,
        status: 'published',
        lessons_count: 5,
        total_duration: '1h 45m',
        is_locked: false,
        lessons: [
            { id: 101, title: 'Installation de l\'environnement', type: 'video', duration: '12:00', status: 'active', is_free: true },
            { id: 102, title: 'Structure des dossiers', type: 'text', duration: '05:00', status: 'active', is_free: false },
            { id: 103, title: 'Le Cycle de Vie (Request/Response)', type: 'video', duration: '25:00', status: 'active', is_free: false }
        ]
    },
    {
        id: 2,
        title: 'Base de données & Eloquent',
        course_name: 'Laravel Mastery 11',
        order: 2,
        status: 'draft',
        lessons_count: 8,
        total_duration: '3h 20m',
        is_locked: true,
        lessons: [
            { id: 201, title: 'Migrations & Schémas', type: 'video', duration: '18:00', status: 'active', is_free: false },
            { id: 202, title: 'Relations One-to-Many', type: 'video', duration: '32:00', status: 'draft', is_free: false }
        ]
    },
    {
        id: 3,
        title: 'Authentification & Sécurité',
        course_name: 'Laravel Mastery 11',
        order: 3,
        status: 'published',
        lessons_count: 4,
        total_duration: '1h 10m',
        is_locked: false,
        lessons: []
    }
]);

// --- UI STATE ---
const selectedCourse = ref(null);
const currentModule = ref({});
const currentLesson = ref({});
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// --- METHODS ---
const openNewModule = () => {
    currentModule.value = {
        title: '',
        order: modules.value.length + 1,
        status: 'draft',
        lessons: []
    };
    moduleDialog.value = true;
};

const editModule = (data) => {
    currentModule.value = { ...data };
    moduleDialog.value = true;
};

const openLessonEditor = (lesson = null, module = null) => {
    if (lesson) {
        currentLesson.value = { ...lesson };
    } else {
        currentLesson.value = {
            title: '',
            type: 'video',
            duration: '00:00',
            status: 'draft',
            content: '',
            is_free: false
        };
    }
    lessonSidebar.value = true;
};

const saveModule = () => {
    toast.add({ severity: 'success', summary: 'Module Enregistré', detail: 'La structure pédagogique a été mise à jour.', life: 3000 });
    moduleDialog.value = false;
};

const saveLesson = () => {
    toast.add({ severity: 'success', summary: 'Leçon Mise à jour', detail: 'Le contenu de la leçon a été synchronisé.', life: 3000 });
    lessonSidebar.value = false;
};

const getStatusSeverity = (status) => {
    return status === 'published' || status === 'active' ? 'success' : 'secondary';
};

const toggleLock = (module) => {
    module.is_locked = !module.is_locked;
    toast.add({ severity: 'info', summary: 'Verrouillage', detail: module.is_locked ? 'Module verrouillé (Prérequis)' : 'Module accessible librement.' });
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- COMPUTED STATS ---
const totalLessons = computed(() => modules.value.reduce((acc, m) => acc + m.lessons_count, 0));

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Leçons & Modules | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 rotate-1 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-list text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Syllabus & Curriculum</h1>
                            <p class="text-slate-500 font-medium italic">Organisez le savoir et structurez l'expérience d'apprentissage.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Dropdown v-model="selectedCourse" :options="courses" optionLabel="name" placeholder="Sélectionner un cours" class="w-72 border-none bg-slate-50 font-bold rounded-xl" />
                    <Divider layout="vertical" />
                    <Button label="Nouveau Module" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="openNewModule" />
                </div>
            </header>

            <!-- --- KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'Modules créés', val: modules.length, icon: 'pi-folder', color: 'indigo' },
                    { label: 'Leçons totales', val: totalLessons, icon: 'pi-book', color: 'emerald' },
                    { label: 'Temps d\'étude', val: '12h 45m', icon: 'pi-clock', color: 'amber' },
                    { label: 'Taux de complétion', val: '64%', icon: 'pi-chart-line', color: 'rose' }
                ]" :key="stat.label" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ stat.val }}</h3>
                        <div class="w-10 h-1 bg-slate-100 mt-4 rounded-full overflow-hidden">
                            <div :class="`h-full bg-${stat.color}-500 w-2/3` "></div>
                        </div>
                    </div>
                    <div :class="`absolute -right-4 -bottom-4 text-7xl text-${stat.color}-50 opacity-0 group-hover:opacity-100 transition-opacity` ">
                        <i :class="['pi', stat.icon]"></i>
                    </div>
                </div>
            </div>

            <!-- --- MAIN DATA TABLE: MODULES WITH EXPANSION --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/20">
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Structure du Programme</h2>
                    <div class="flex gap-4">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher..." class="w-80 rounded-2xl border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all" />
                        </IconField>
                        <Button icon="pi pi-sort-alt" class="p-button-outlined p-button-secondary rounded-xl" v-tooltip.top="'Réorganiser'" />
                    </div>
                </div>

                <DataTable
                    :value="modules"
                    v-model:expandedRows="expandedRows"
                    dataKey="id"
                    :loading="loading"
                    class="p-datatable-custom"
                >
                    <Column expander style="width: 5rem" />

                    <Column field="order" header="#" style="width: 5rem">
                        <template #body="{ data }">
                            <span class="font-black text-slate-300">#{{ data.order }}</span>
                        </template>
                    </Column>

                    <Column field="title" header="Module (Chapitre)" style="min-width: 25rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100 shadow-sm">
                                    <i class="pi pi-folder-open text-indigo-500"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 leading-tight">{{ data.title }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ data.course_name }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="lessons_count" header="Volume">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Badge :value="data.lessons_count + ' leçons'" severity="info" class="text-[9px] font-black uppercase" />
                                <span class="text-[10px] text-slate-400 font-bold tracking-tighter">{{ data.total_duration }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[10px] font-black uppercase tracking-widest px-3 py-1" />
                        </template>
                    </Column>

                    <Column header="Accès">
                        <template #body="{ data }">
                            <Button :icon="data.is_locked ? 'pi pi-lock' : 'pi pi-lock-open'"
                                :class="['p-button-rounded p-button-text', data.is_locked ? 'p-button-warning' : 'p-button-success']"
                                @click="toggleLock(data)"
                                v-tooltip.top="data.is_locked ? 'Prérequis requis' : 'Accès libre'" />
                        </template>
                    </Column>

                    <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-plus-circle" class="p-button-rounded p-button-text p-button-success" v-tooltip.top="'Ajouter Leçon'" @click="openLessonEditor(null, data)" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editModule(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>

                    <!-- --- ROW EXPANSION: LESSONS LIST --- -->
                    <template #expansion="{ data }">
                        <div class="p-8 bg-slate-50/50 rounded-[2.5rem] m-6 border border-slate-100 shadow-inner">
                            <div class="flex justify-between items-center mb-6 px-4">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Leçons contenues dans : {{ data.title }}</h4>
                                <Button label="Réorganiser les leçons" icon="pi pi-sort" class="p-button-text p-button-sm p-button-secondary font-bold" />
                            </div>

                            <DataTable :value="data.lessons" class="p-datatable-lessons">
                                <Column field="title" header="Titre de la leçon">
                                    <template #body="slot">
                                        <div class="flex items-center gap-3">
                                            <i :class="[lessonTypes.find(t => t.value === slot.data.type)?.icon, 'text-indigo-400']"></i>
                                            <span class="font-bold text-slate-700">{{ slot.data.title }}</span>
                                            <Badge v-if="slot.data.is_free" value="Gratuit" severity="success" class="text-[8px]" />
                                        </div>
                                    </template>
                                </Column>
                                <Column field="type" header="Format">
                                    <template #body="slot">
                                        <Tag :value="slot.data.type" severity="secondary" class="text-[9px] uppercase font-black" />
                                    </template>
                                </Column>
                                <Column field="duration" header="Durée" />
                                <Column field="status" header="Statut">
                                    <template #body="slot">
                                        <Tag :value="slot.data.status" :severity="getStatusSeverity(slot.data.status)" class="text-[9px] uppercase" />
                                    </template>
                                </Column>
                                <Column header="Actions" class="text-right">
                                    <template #body="slot">
                                        <div class="flex justify-end gap-1">
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info p-button-sm" @click="openLessonEditor(slot.data)" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger p-button-sm" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>

                            <div v-if="data.lessons.length === 0" class="text-center py-10 opacity-40">
                                <i class="pi pi-info-circle text-4xl mb-4"></i>
                                <p class="font-bold italic">Aucune leçon dans ce module pour le moment.</p>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- --- MODULE EDITOR DIALOG --- -->
        <Dialog v-model:visible="moduleDialog" :style="{width: '650px'}" header="Paramètres du Module" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-4 space-y-8 pt-6">
                <div class="field">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre du chapitre</label>
                    <InputText v-model="currentModule.title" placeholder="Ex: Introduction aux Objets" class="text-lg font-bold p-3 rounded-xl border-slate-200 shadow-inner" />
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="field">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Ordre d'affichage</label>
                        <InputNumber v-model="currentModule.order" showButtons class="rounded-xl" />
                    </div>
                    <div class="field">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Statut de publication</label>
                        <SelectButton v-model="currentModule.status" :options="['draft', 'published']" class="custom-selectbutton" />
                    </div>
                </div>

                <div class="bg-amber-50 p-6 rounded-[2rem] border border-amber-100 flex gap-4">
                    <i class="pi pi-exclamation-triangle text-amber-500 text-2xl"></i>
                    <div>
                        <h4 class="font-bold text-amber-900">Règle de Progression</h4>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-xs text-amber-700">Verrouiller ce module par un prérequis ?</span>
                            <InputSwitch v-model="currentModule.is_locked" />
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3 p-4 border-t border-slate-50">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="moduleDialog = false" />
                    <Button label="Sauvegarder" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="saveModule" />
                </div>
            </template>
        </Dialog>

        <!-- --- LESSON BUILDER SIDEBAR (THE BIG EDITOR) --- -->
        <Sidebar v-model:visible="lessonSidebar" position="right" class="w-full md:w-[850px] p-sidebar-custom">
            <div v-if="currentLesson" class="flex flex-col h-full bg-white">

                <div class="p-8 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-xl',
                            currentLesson.type === 'video' ? 'bg-rose-500' : 'bg-indigo-500']">
                            <i :class="lessonTypes.find(t => t.value === currentLesson.type)?.icon + ' text-2xl'"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ currentLesson.id ? 'Éditer la Leçon' : 'Nouvelle Leçon' }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configuration de contenu granulé</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary" @click="lessonSidebar = false" />
                </div>

                <ScrollPanel class="flex-1">
                    <TabView class="custom-tabs p-6">
                        <!-- TAB: CONTENT -->
                        <TabPanel>
                            <template #header><i class="pi pi-pencil mr-2"></i> Contenu Principal</template>
                            <div class="space-y-8 pt-4 pb-10">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre de la leçon</label>
                                    <InputText v-model="currentLesson.title" placeholder="Titre explicatif..." class="text-lg font-bold p-3 rounded-xl" />
                                </div>

                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Type de Leçon</label>
                                    <SelectButton v-model="currentLesson.type" :options="lessonTypes" optionLabel="label" optionValue="value" class="custom-lesson-type-select">
                                        <template #option="slotProps">
                                            <div class="flex flex-col items-center gap-2 p-2">
                                                <i :class="slotProps.option.icon"></i>
                                                <span class="text-[9px] font-black uppercase">{{ slotProps.option.label }}</span>
                                            </div>
                                        </template>
                                    </SelectButton>
                                </div>

                                <!-- VIDEO SOURCE SECTION (Conditionnal) -->
                                <div v-if="currentLesson.type === 'video'" class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl">
                                    <i class="pi pi-play-circle absolute -right-6 -bottom-6 text-[10rem] text-white/5"></i>
                                    <div class="relative z-10 space-y-6">
                                        <h3 class="text-xl font-black italic">Source Vidéo</h3>
                                        <div class="grid grid-cols-2 gap-6">
                                            <div class="field">
                                                <label class="text-[10px] font-black text-slate-400 uppercase block mb-2">Plateforme</label>
                                                <Dropdown :options="['Vimeo', 'YouTube', 'Subnet CDN (S3)', 'Wistia']" placeholder="Choisir..." class="w-full bg-white/10 border-none text-white rounded-xl" />
                                            </div>
                                            <div class="field">
                                                <label class="text-[10px] font-black text-slate-400 uppercase block mb-2">ID / URL de la vidéo</label>
                                                <InputText placeholder="Ex: 85474125" class="w-full bg-white/10 border-none text-white rounded-xl" />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-2xl">
                                            <span class="text-xs font-bold">Durée de la vidéo (Format MM:SS)</span>
                                            <InputText v-model="currentLesson.duration" class="w-24 text-center bg-transparent border-none text-indigo-400 font-black" />
                                        </div>
                                    </div>
                                </div>

                                <!-- TEXT EDITOR SECTION (Conditionnal) -->
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Corps de la leçon (Support écrit)</label>
                                    <Editor v-model="currentLesson.content" editorStyle="height: 400px" class="rounded-2xl" />
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB: SETTINGS & SEO -->
                        <TabPanel>
                            <template #header><i class="pi pi-cog mr-2"></i> Réglages & Marketing</template>
                            <div class="space-y-8 pt-4 pb-10">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-indigo-900">Leçon Gratuite</p>
                                            <p class="text-[10px] text-indigo-500 uppercase font-black mt-1 italic">Utilisable comme aperçu</p>
                                        </div>
                                        <InputSwitch v-model="currentLesson.is_free" />
                                    </div>
                                    <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-slate-700">Commentaires</p>
                                            <p class="text-[10px] text-slate-400 uppercase font-black mt-1 italic">Autoriser les questions</p>
                                        </div>
                                        <InputSwitch :modelValue="true" />
                                    </div>
                                </div>

                                <Divider />

                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Ressources Téléchargeables</h4>
                                    <div class="p-10 border-2 border-dashed border-slate-200 rounded-[2.5rem] bg-slate-50 text-center flex flex-col items-center justify-center space-y-4 hover:bg-white transition-all cursor-pointer">
                                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-md">
                                            <i class="pi pi-file-pdf text-3xl text-rose-500"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-400">Glissez ici les fichiers (PDF, ZIP, Code Source) rattachés à cette leçon.</p>
                                        <FileUpload mode="basic" chooseLabel="Sélectionner Fichiers" class="p-button-sm rounded-xl px-6" />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </ScrollPanel>

                <div class="p-8 border-t border-slate-100 flex gap-4 bg-white">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold flex-1" @click="lessonSidebar = false" />
                    <Button label="Enregistrer la Leçon" icon="pi pi-check" class="p-button-primary px-10 rounded-2xl shadow-xl shadow-indigo-100 flex-1" @click="saveLesson" />
                </div>
            </div>
        </Sidebar>

</AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO MODULES & LESSONS SYSTEM
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
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #fcfcff !important;
}

/* Expansion Table Styling */
:deep(.p-datatable-lessons .p-datatable-thead > tr > th) {
    background: transparent;
    padding: 1rem;
    font-size: 0.65rem;
    color: #94a3b8;
    border-bottom: 1px solid #e2e8f0;
}

:deep(.p-datatable-lessons .p-datatable-tbody > tr > td) {
    padding: 1rem;
    background: transparent;
}

/* TabView Customization */
:deep(.custom-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 1rem;
}

:deep(.custom-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #94a3b8;
    padding: 1.5rem;
    transition: all 0.3s;
}

:deep(.custom-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

/* SelectButton custom styling for lesson types */
:deep(.custom-lesson-type-select .p-button) {
    background: white;
    border: 1px solid #f1f5f9;
    flex: 1;
}

:deep(.custom-lesson-type-select .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

/* Sidebar Customization */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
}

/* Input & Progress bar */
:deep(.p-inputtext), :deep(.p-inputnumber-input), :deep(.p-dropdown) {
    border-radius: 12px;
    border-color: #e2e8f0;
}

:deep(.p-progressbar) {
    background: #f1f5f9;
    border-radius: 10px;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Editor Corners */
:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
    border-color: #f1f5f9;
}

:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
    border-color: #f1f5f9;
}

/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
