<script setup>
/**
 * ATOMIC QUESTION BANK MANAGER - SUBNETCONGO LMS
 * Version: 10.1.0
 * Description: Gestionnaire centralisé des items d'évaluation avec éditeur multi-format.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
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
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Rating from 'primevue/rating';
import OverlayPanel from 'primevue/overlaypanel';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const questionDialog = ref(false);
const previewOverlay = ref();
const importDialog = ref(false);
const activeTab = ref(0);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Examens & Suivi' },
    { label: 'Banque de Questions', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const categories = ref([
    { label: 'Backend PHP/Laravel', value: 'laravel' },
    { label: 'Frontend Vue.js', value: 'vuejs' },
    { label: 'Design UI/UX', value: 'uiux' },
    { label: 'Base de données SQL', value: 'sql' },
    { label: 'DevOps & Docker', value: 'devops' }
]);

const questionTypes = ref([
    { label: 'QCM (Unique)', value: 'mcq_single', icon: 'pi pi-circle-fill', color: 'bg-indigo-500' },
    { label: 'Choix Multiples', value: 'mcq_multiple', icon: 'pi pi-check-square', color: 'bg-blue-500' },
    { label: 'Vrai / Faux', value: 'boolean', icon: 'pi pi-arrows-h', color: 'bg-emerald-500' },
    { label: 'Réponse Courte', value: 'short_answer', icon: 'pi pi-pencil', color: 'bg-amber-500' },
    { label: 'Correspondance', value: 'matching', icon: 'pi pi-th-large', color: 'bg-purple-500' }
]);

const difficultyLevels = [
    { label: 'Facile', value: 'easy', severity: 'success' },
    { label: 'Intermédiaire', value: 'medium', severity: 'warning' },
    { label: 'Difficile', value: 'hard', severity: 'danger' }
];

// --- MOCK DATA: QUESTION BANK ---
const questions = ref(Array.from({ length: 25 }, (_, i) => ({
    id: 1000 + i,
    stem: i % 2 === 0 ? "Quelle fonction est utilisée pour définir une route dans Laravel ?" : "Comment déclarer une variable réactive en Vue 3 ?",
    type: i % 3 === 0 ? 'mcq_single' : (i % 2 === 0 ? 'boolean' : 'short_answer'),
    category: i % 2 === 0 ? 'laravel' : 'vuejs',
    difficulty: i % 5 === 0 ? 'hard' : (i % 2 === 0 ? 'easy' : 'medium'),
    points: i % 5 === 0 ? 10 : 5,
    usage_count: Math.floor(Math.random() * 5),
    success_rate: Math.floor(Math.random() * 100),
    last_modified: '2024-01-20',
    options: [
        { id: 1, text: 'Route::get()', is_correct: true },
        { id: 2, text: 'Route::path()', is_correct: false },
        { id: 3, text: 'Router::url()', is_correct: false }
    ],
    explanation: "La façade Route permet de définir les points d'entrée de l'application."
})));

// --- UI STATE ---
const question = ref({
    stem: '',
    type: 'mcq_single',
    category: 'laravel',
    difficulty: 'easy',
    points: 5,
    options: [],
    explanation: '',
    settings: {
        shuffle_options: true,
        case_sensitive: false
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    category: { value: null, matchMode: FilterMatchMode.IN },
    difficulty: { value: null, matchMode: FilterMatchMode.EQUALS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- METHODS ---
const openNew = () => {
    question.value = {
        stem: '',
        type: 'mcq_single',
        category: 'laravel',
        difficulty: 'easy',
        points: 5,
        options: [
            { id: 1, text: '', is_correct: true },
            { id: 2, text: '', is_correct: false }
        ],
        explanation: '',
        settings: { shuffle_options: true }
    };
    activeTab.value = 0;
    questionDialog.value = true;
};

const editQuestion = (data) => {
    question.value = JSON.parse(JSON.stringify(data));
    questionDialog.value = true;
};

const addOption = () => {
    question.value.options.push({
        id: Date.now(),
        text: '',
        is_correct: false
    });
};

const removeOption = (idx) => {
    question.value.options.splice(idx, 1);
};

const setCorrectOption = (idx) => {
    if (question.value.type === 'mcq_single' || question.value.type === 'boolean') {
        question.value.options.forEach((opt, i) => opt.is_correct = i === idx);
    }
};

const saveQuestion = () => {
    toast.add({ severity: 'success', summary: 'Question Enregistrée', detail: 'La banque de questions a été mise à jour.', life: 3000 });
    questionDialog.value = false;
};

const getDifficultySeverity = (val) => difficultyLevels.find(d => d.value === val)?.severity;

const showPreview = (event, data) => {
    question.value = data;
    previewOverlay.value.toggle(event);
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- CHART DATA: SUCCESS RATE DISTRIBUTION ---
const chartData = ref({
    labels: ['Réussite > 80%', 'Réussite 50-80%', 'Réussite < 50%'],
    datasets: [{
        data: [15, 8, 2],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
        hoverOffset: 10
    }]
});

</script>

<template>
    <AppLayout>
        <Head title="Banque de Questions | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 rotate-1 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-database text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Banque de Questions</h1>
                            <p class="text-slate-500 font-medium italic">Stockez, organisez et analysez vos items d'évaluation <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Importer (GIFT/CSV)" icon="pi pi-upload" class="p-button-text p-button-secondary rounded-xl" @click="importDialog = true" />
                    <Divider layout="vertical" />
                    <Button label="Nouvelle Question" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- ANALYTICS KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'Total Questions', val: questions.length, icon: 'pi-list', color: 'indigo' },
                    { label: 'Difficulté Moyenne', val: 'Médium', icon: 'pi-chart-bar', color: 'amber' },
                    { label: 'Utilisées en Exam', val: '84%', icon: 'pi-check-circle', color: 'emerald' },
                    { label: 'Questions à revoir', val: '3', icon: 'pi-exclamation-triangle', color: 'rose' }
                ]" :key="stat.label" class="bg-white p-7 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-5 hover:shadow-xl transition-all">
                    <div :class="`w-14 h-14 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center` ">
                        <i :class="['pi text-2xl', stat.icon]"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-2xl font-black text-slate-800 tracking-tight">{{ stat.val }}</p>
                    </div>
                </div>
            </div>

            <!-- --- MAIN DATA TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/20">
                    <div class="flex items-center gap-4">
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Inventaire des Items</h2>
                        <Chip label="v10.1 Stable" class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-3" />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <MultiSelect v-model="filters['category'].value" :options="categories" optionLabel="label" optionValue="value" placeholder="Catégories" class="w-64 rounded-xl border-none ring-1 ring-slate-200" />
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher un énoncé..." class="w-72 rounded-2xl border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all" />
                        </IconField>
                    </div>
                </div>

                <DataTable
                    :value="questions"
                    :paginator="true"
                    :rows="10"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    class="p-datatable-custom"
                    responsiveLayout="stack"
                >
                    <Column field="type" header="Type" style="width: 5rem">
                        <template #body="{ data }">
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-sm', questionTypes.find(t => t.value === data.type)?.color]"
                                v-tooltip.top="questionTypes.find(t => t.value === data.type)?.label">
                                <i :class="questionTypes.find(t => t.value === data.type)?.icon"></i>
                            </div>
                        </template>
                    </Column>

                    <Column field="stem" header="Énoncé de la question" style="min-width: 25rem">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-slate-700 line-clamp-1 hover:text-indigo-600 cursor-pointer transition-colors" @click="editQuestion(data)">{{ data.stem }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ categories.find(c => c.value === data.category)?.label }}</span>
                                    <Divider layout="vertical" class="h-2 m-0" />
                                    <span class="text-[9px] font-bold text-indigo-400 uppercase">Utilisée {{ data.usage_count }} fois</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="difficulty" header="Difficulté" sortable>
                        <template #body="{ data }">
                            <Tag :value="data.difficulty" :severity="getDifficultySeverity(data.difficulty)" class="text-[9px] font-black uppercase px-3 py-1 tracking-widest rounded-lg" />
                        </template>
                    </Column>

                    <Column field="success_rate" header="Performance" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 bg-slate-100 h-1.5 rounded-full overflow-hidden w-20">
                                    <div :class="['h-full rounded-full', data.success_rate > 70 ? 'bg-emerald-500' : (data.success_rate > 40 ? 'bg-amber-500' : 'bg-rose-500')]"
                                        :style="{ width: data.success_rate + '%' }"></div>
                                </div>
                                <span class="text-[10px] font-black text-slate-500">{{ data.success_rate }}%</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="points" header="Points" sortable class="text-center">
                        <template #body="{ data }">
                            <Badge :value="data.points" severity="info" class="font-black" />
                        </template>
                    </Column>

                    <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" @click="showPreview($event, data)" />
                                <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Dupliquer'" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editQuestion(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- QUESTION EDITOR SIDEBAR (THE BIG DRAWER) --- -->
        <Sidebar v-model:visible="questionDialog" position="right" class="w-full md:w-[950px] p-sidebar-custom">
            <div v-if="question" class="flex flex-col h-full bg-white">

                <!-- Drawer Header -->
                <div class="p-8 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-xl', questionTypes.find(t => t.value === question.type)?.color]">
                            <i :class="[questionTypes.find(t => t.value === question.type)?.icon, 'text-2xl']"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">{{ question.id ? 'Modifier la Question' : 'Nouvel Item Atomique' }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configuration de la logique d'évaluation</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary" @click="questionDialog = false" />
                </div>

                <ScrollPanel class="flex-1">
                    <TabView v-model:activeIndex="activeTab" class="custom-tabs p-8">

                        <!-- TAB 1: ÉNONCÉ & TYPE -->
                        <TabPanel>
                            <template #header><i class="pi pi-pencil mr-2"></i> Énoncé & Format</template>
                            <div class="space-y-8 pt-4 pb-10">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3">Format de la question</label>
                                    <SelectButton v-model="question.type" :options="questionTypes" optionLabel="label" optionValue="value" class="custom-type-selector">
                                        <template #option="{ option }">
                                            <div class="flex flex-col items-center gap-2 p-2">
                                                <i :class="option.icon" class="text-xl"></i>
                                                <span class="text-[9px] font-black uppercase">{{ option.label }}</span>
                                            </div>
                                        </template>
                                    </SelectButton>
                                </div>

                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3">Corps de la question (Stem)</label>
                                    <Editor v-model="question.stem" editorStyle="height: 250px" placeholder="Saisissez ici l'énoncé de votre question..." />
                                </div>

                                <div class="grid grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Catégorie Taxonomique</label>
                                        <Dropdown v-model="question.category" :options="categories" optionLabel="label" optionValue="value" class="rounded-xl border-slate-200" />
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Points attribués</label>
                                        <InputNumber v-model="question.points" showButtons :min="1" suffix=" pts" class="rounded-xl border-slate-200" />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 2: RÉPONSES & LOGIQUE -->
                        <TabPanel>
                            <template #header><i class="pi pi-list mr-2"></i> Réponses & Solutions</template>
                            <div class="space-y-8 pt-4 pb-10">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-black text-slate-800 tracking-tight italic">Options de réponse</h3>
                                    <Button v-if="question.type !== 'boolean' && question.type !== 'short_answer'" label="Ajouter une Option" icon="pi pi-plus" class="p-button-sm p-button-outlined rounded-xl font-bold" @click="addOption" />
                                </div>

                                <!-- MCQ Logic -->
                                <div v-if="question.type === 'mcq_single' || question.type === 'mcq_multiple' || question.type === 'boolean'" class="space-y-4">
                                    <div v-for="(opt, idx) in question.options" :key="opt.id" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-4 group hover:bg-white hover:border-indigo-200 transition-all">
                                        <div class="flex items-center gap-3">
                                            <RadioButton v-if="question.type === 'mcq_single' || question.type === 'boolean'" :name="'correct_'+question.id" :value="true" v-model="opt.is_correct" @change="setCorrectOption(idx)" />
                                            <Checkbox v-else :binary="true" v-model="opt.is_correct" />
                                            <Badge :value="String.fromCharCode(65 + idx)" severity="secondary" class="rounded-lg" />
                                        </div>
                                        <InputText v-model="opt.text" placeholder="Saisissez la réponse..." class="flex-1 bg-transparent border-none p-0 focus:ring-0 font-bold text-slate-700" />
                                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Button icon="pi pi-image" class="p-button-text p-button-secondary p-button-sm" v-tooltip.top="'Ajouter Image'" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="removeOption(idx)" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Short Answer Logic -->
                                <div v-if="question.type === 'short_answer'" class="space-y-6">
                                    <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-3xl">
                                        <div class="flex items-center gap-4 mb-4">
                                            <i class="pi pi-info-circle text-indigo-500"></i>
                                            <span class="text-sm font-bold text-indigo-900">Configurez les réponses exactes (la casse peut être ignorée).</span>
                                        </div>
                                        <div class="space-y-3">
                                            <div v-for="(opt, idx) in question.options" :key="opt.id" class="flex gap-2">
                                                <InputText v-model="opt.text" placeholder="Réponse attendue..." class="rounded-xl" />
                                                <Button icon="pi pi-times" class="p-button-danger p-button-text" @click="removeOption(idx)" />
                                            </div>
                                            <Button label="Ajouter un synonyme / variante" icon="pi pi-plus" class="p-button-text p-button-sm p-0 font-bold" @click="addOption" />
                                        </div>
                                    </div>
                                </div>

                                <Divider />

                                <!-- FEEDBACK SECTION -->
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3 italic">Explication corrective (Feedback)</label>
                                    <Editor v-model="question.explanation" editorStyle="height: 150px" placeholder="Pourquoi est-ce la bonne réponse ?" />
                                    <p class="text-[10px] text-slate-400 mt-2">Ce texte sera affiché à l'apprenant après la soumission du quiz.</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 3: RÉGLAGES & STATS -->
                        <TabPanel>
                            <template #header><i class="pi pi-cog mr-2"></i> Paramètres & Analytics</template>
                            <div class="space-y-10 pt-4 pb-10">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Réglages Système</h4>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700">Mélanger les options</p>
                                                <p class="text-[10px] text-slate-400 uppercase font-black">Randomise l'ordre A,B,C,D</p>
                                            </div>
                                            <InputSwitch v-model="question.settings.shuffle_options" />
                                        </div>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700">Niveau de Difficulté</p>
                                            </div>
                                            <SelectButton v-model="question.difficulty" :options="difficultyLevels" optionLabel="label" optionValue="value" class="p-selectbutton-sm" />
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Performance Actuelle</h4>
                                        <div class="p-6 bg-indigo-900 rounded-[2.5rem] text-white shadow-xl relative overflow-hidden">
                                            <i class="pi pi-chart-line absolute -right-6 -bottom-6 text-9xl text-white/5"></i>
                                            <div class="relative z-10">
                                                <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-4">Taux de réussite moyen</p>
                                                <h3 class="text-5xl font-black italic">{{ question.success_rate || 0 }}%</h3>
                                                <div class="mt-6 flex items-center gap-3">
                                                    <AvatarGroup>
                                                        <Avatar image="https://i.pravatar.cc/150?u=1" shape="circle" />
                                                        <Avatar image="https://i.pravatar.cc/150?u=2" shape="circle" />
                                                        <Avatar image="https://i.pravatar.cc/150?u=3" shape="circle" />
                                                        <Avatar label="+40" shape="circle" class="bg-indigo-500 text-xs" />
                                                    </AvatarGroup>
                                                    <span class="text-[10px] font-bold text-indigo-200 uppercase">Diplômés testés</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Divider />

                                <div class="space-y-4">
                                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Utilisation dans les examens</h4>
                                    <InlineMessage severity="info" class="w-full justify-start">Cette question est actuellement active dans **3 examens**.</InlineMessage>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div v-for="ex in ['Certification Laravel Final', 'Quiz Hebdo #2', 'Test d\'entrée']" :key="ex" class="p-4 border border-slate-100 rounded-2xl bg-white shadow-sm flex items-center gap-3">
                                            <i class="pi pi-file-o text-indigo-400"></i>
                                            <span class="text-xs font-bold text-slate-600">{{ ex }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </ScrollPanel>

                <div class="p-8 border-t border-slate-100 flex gap-4 bg-white">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold flex-1" @click="questionDialog = false" />
                    <Button label="Mettre en Banque" icon="pi pi-check" class="p-button-primary px-10 rounded-2xl shadow-xl shadow-indigo-100 flex-1" @click="saveQuestion" />
                </div>
            </div>
        </Sidebar>

        <!-- --- PREVIEW OVERLAY PANEL --- -->
        <OverlayPanel ref="previewOverlay" class="w-[500px] shadow-2xl rounded-[2.5rem] border border-slate-100 overflow-hidden">
            <div v-if="question" class="p-6 space-y-6">
                <div class="flex justify-between items-center">
                    <Tag :value="question.type" severity="secondary" class="uppercase text-[9px]" />
                    <span class="text-[10px] font-black text-slate-400 uppercase">Preview Étudiant</span>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-black text-slate-800 italic" v-html="question.stem"></h3>

                    <div class="space-y-2">
                        <div v-for="(opt, idx) in question.options" :key="opt.id" class="p-4 border border-slate-100 rounded-2xl flex items-center gap-4 hover:border-indigo-400 transition-all cursor-pointer group">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-xs font-black text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all">
                                {{ String.fromCharCode(65 + idx) }}
                            </div>
                            <span class="text-sm font-medium text-slate-600">{{ opt.text }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400">Total : {{ question.points }} Points</span>
                    <Button label="Vérifier" class="p-button-sm rounded-xl font-bold" />
                </div>
            </div>
        </OverlayPanel>

</AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO QUESTION BANK SYSTEM
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

/* Tabs Styling */
:deep(.custom-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 2rem;
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

/* SelectButton custom styling for question formats */
:deep(.custom-type-selector .p-button) {
    background: white;
    border: 1px solid #f1f5f9;
    flex: 1;
    transition: all 0.2s;
}

:deep(.custom-type-selector .p-button.p-highlight) {
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
