<script setup>
/**
 * EXAM & QUIZ MANAGEMENT SYSTEM - SUBNETCONGO LMS
 * Version: 5.2.0
 * Description: Système de création d'examens, banque de questions et suivi des résultats.
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
import Calendar from 'primevue/calendar';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import OverlayPanel from 'primevue/overlaypanel';
import PickList from 'primevue/picklist';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const examDialog = ref(false);
const questionBankDialog = ref(false);
const activeTab = ref(0);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Examens & Suivi' },
    { label: 'Tous les Examens', class: 'font-bold text-indigo-600' }
]);

// --- DATA: EXAM LIST ---
const exams = ref([
    {
        id: 1,
        title: 'Examen Final - Laravel Advanced',
        course: 'Mastering Laravel 11',
        type: 'Certification',
        duration: 60, // minutes
        passing_score: 80,
        questions_count: 20,
        attempts_allowed: 2,
        status: 'Active',
        avg_score: 74,
        created_at: '2024-01-15',
        questions: [
            { id: 101, text: 'Qu\'est-ce qu\'un Service Provider ?', type: 'qcm', points: 5, options: ['Un middleware', 'Le point central de boot', 'Un moteur de template'], correct: 1 },
            { id: 102, text: 'Laravel utilise Eloquent comme ORM par défaut.', type: 'boolean', points: 2, correct: true }
        ]
    },
    {
        id: 2,
        title: 'Quiz Hebdomadaire #3 - UI Design',
        course: 'UI/UX Design Specialist',
        type: 'Quiz',
        duration: 15,
        passing_score: 50,
        questions_count: 10,
        attempts_allowed: 0, // Illimité
        status: 'Draft',
        avg_score: 0,
        created_at: '2024-02-01',
        questions: []
    }
]);

// --- DATA: QUESTION BANK ---
const questionsBank = ref([
    { id: 501, text: 'Expliquez le concept de Dependency Injection.', category: 'PHP/Laravel', type: 'text', points: 10 },
    { id: 502, text: 'Quelle est la commande pour créer un contrôleur ?', category: 'Laravel', type: 'qcm', points: 5, options: ['make:controller', 'new:controller', 'artisan:ctrl'], correct: 0 }
]);

// --- UI STATE ---
const exam = ref({
    title: '',
    type: 'Quiz',
    status: 'Draft',
    passing_score: 70,
    duration: 30,
    questions: []
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// --- METHODS ---
const openNew = () => {
    exam.value = {
        title: '',
        type: 'Quiz',
        status: 'Draft',
        duration: 30,
        passing_score: 70,
        attempts_allowed: 1,
        questions: []
    };
    activeTab.value = 0;
    examDialog.value = true;
};

const editExam = (data) => {
    exam.value = JSON.parse(JSON.stringify(data));
    examDialog.value = true;
};

const addQuestion = (type) => {
    const newQ = {
        id: Date.now(),
        text: '',
        type: type,
        points: 5,
        options: type === 'qcm' ? ['', ''] : null,
        correct: type === 'boolean' ? true : 0
    };
    exam.value.questions.push(newQ);
};

const removeQuestion = (id) => {
    exam.value.questions = exam.value.questions.filter(q => q.id !== id);
};

const saveExam = () => {
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Examen enregistré et synchronisé.', life: 3000 });
    examDialog.value = false;
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Examens | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2rem] shadow-2xl shadow-indigo-100">
                            <i class="pi pi-file-edit text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Évaluations & Quiz</h1>
                            <p class="text-slate-500 font-medium italic">Gérez les examens, les certifications et les banques de questions.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Banque de Questions" icon="pi pi-database" class="p-button-text p-button-secondary rounded-xl" @click="questionBankDialog = true" />
                    <Divider layout="vertical" />
                    <Button label="Créer un Examen" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'Examens Actifs', val: '14', icon: 'pi-check-circle', color: 'indigo' },
                    { label: 'Tentatives (30j)', val: '1,240', icon: 'pi-users', color: 'emerald' },
                    { label: 'Taux Réussite Moyen', val: '68%', icon: 'pi-chart-line', color: 'amber' },
                    { label: 'Corrections en attente', val: '24', icon: 'pi-clock', color: 'rose' }
                ]" :key="stat.label" class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-5 group hover:shadow-xl transition-all">
                    <div :class="`w-14 h-14 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform` ">
                        <i :class="['pi text-2xl', stat.icon]"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-2xl font-black text-slate-800">{{ stat.val }}</p>
                    </div>
                </div>
            </div>

            <!-- --- EXAM TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-8 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-xl font-black text-slate-800 tracking-tight italic">Journal des Évaluations</h3>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="filters['global'].value" placeholder="Rechercher un examen..." class="w-80 rounded-2xl" />
                    </IconField>
                </div>

                <DataTable :value="exams" :paginator="true" :rows="10" :filters="filters" class="p-datatable-custom">
                    <Column field="title" header="Titre de l'examen" style="min-width: 20rem">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-800">{{ data.title }}</span>
                                <span class="text-xs text-indigo-500 font-medium">{{ data.course }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="type" header="Type">
                        <template #body="{ data }">
                            <Tag :value="data.type" :severity="data.type === 'Certification' ? 'info' : 'secondary'" class="text-[9px] font-black uppercase" />
                        </template>
                    </Column>
                    <Column field="questions_count" header="Questions" class="text-center font-bold" />
                    <Column field="passing_score" header="Passage (%)" class="text-center">
                        <template #body="{ data }">
                            <Badge :value="data.passing_score + '%'" severity="success" />
                        </template>
                    </Column>
                    <Column field="status" header="Statut">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div :class="['w-2 h-2 rounded-full', data.status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300']"></div>
                                <span class="text-sm font-bold text-slate-600">{{ data.status }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Actions" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-chart-bar" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Statistiques'" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editExam(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- EXAM BUILDER DIALOG (MULTI-TAB) --- -->
        <Dialog v-model:visible="examDialog" :style="{width: '1000px'}" header="Éditeur d'Examen" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <TabView v-model:activeIndex="activeTab" class="custom-tabs mt-4">

                <!-- ONGLET 1 : CONFIGURATION GÉNÉRALE -->
                <TabPanel>
                    <template #header><i class="pi pi-cog mr-2"></i> Paramètres</template>
                    <div class="grid grid-cols-12 gap-8 pt-6">
                        <div class="col-span-12 lg:col-span-8 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre de l'évaluation</label>
                                <InputText v-model="exam.title" placeholder="Ex: Examen Final Module 1" class="text-xl font-bold p-4 rounded-2xl" />
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Type</label>
                                    <SelectButton v-model="exam.type" :options="['Quiz', 'Certification', 'Placement']" />
                                </div>
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Statut</label>
                                    <SelectButton v-model="exam.status" :options="['Draft', 'Active', 'Archived']" />
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 space-y-6 bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4">Règles de passage</h4>
                            <div class="field">
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Score de passage (%)</label>
                                <InputNumber v-model="exam.passing_score" suffix="%" showButtons :min="0" :max="100" />
                            </div>
                            <div class="field">
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Durée (minutes)</label>
                                <InputNumber v-model="exam.duration" suffix=" min" showButtons :min="0" />
                                <small class="text-slate-400 italic">0 pour illimité.</small>
                            </div>
                            <div class="field">
                                <label class="text-[10px] font-bold text-slate-500 block mb-2">Tentatives autorisées</label>
                                <InputNumber v-model="exam.attempts_allowed" showButtons :min="0" />
                            </div>
                            <Divider />
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-600">Mélanger les questions</span>
                                <InputSwitch :modelValue="true" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 2 : QUESTION BUILDER -->
                <TabPanel>
                    <template #header><i class="pi pi-list mr-2"></i> Questions ({{ exam.questions.length }})</template>
                    <div class="pt-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Constructeur de Quiz</h3>
                            <div class="flex gap-2">
                                <Button label="Importer" icon="pi pi-download" class="p-button-outlined p-button-sm rounded-xl" />
                                <Button label="Ajouter une Question" icon="pi pi-plus" class="p-button-sm rounded-xl" @click="addQuestion('qcm')" />
                            </div>
                        </div>

                        <ScrollPanel style="height: 500px" class="pr-4">
                            <div class="space-y-6 pb-10">
                                <div v-for="(q, index) in exam.questions" :key="q.id" class="bg-white border border-slate-200 rounded-[2rem] shadow-sm overflow-hidden group">
                                    <div class="p-5 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                                        <div class="flex items-center gap-4">
                                            <Badge :value="index + 1" severity="info" class="w-8 h-8 flex items-center justify-center rounded-full" />
                                            <Dropdown v-model="q.type" :options="['qcm', 'boolean', 'text']" class="p-inputtext-sm border-none bg-transparent font-bold uppercase text-[10px]" />
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2 mr-4">
                                                <span class="text-[10px] font-bold text-slate-400">POINTS :</span>
                                                <InputNumber v-model="q.points" :min="1" class="w-16 p-inputtext-sm" />
                                            </div>
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger p-button-sm" @click="removeQuestion(q.id)" />
                                        </div>
                                    </div>

                                    <div class="p-6 space-y-4">
                                        <div class="field">
                                            <InputText v-model="q.text" placeholder="Énoncé de la question..." class="text-sm font-bold border-none bg-slate-50 rounded-xl p-3" />
                                        </div>

                                        <!-- TYPE QCM -->
                                        <div v-if="q.type === 'qcm'" class="space-y-3">
                                            <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-3">
                                                <RadioButton :name="'q'+q.id" :value="oIdx" v-model="q.correct" />
                                                <InputText v-model="q.options[oIdx]" :placeholder="'Option ' + (oIdx + 1)" class="flex-1 text-xs border-none bg-white ring-1 ring-slate-100 rounded-lg p-2" />
                                                <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-xs" @click="q.options.splice(oIdx, 1)" />
                                            </div>
                                            <Button label="Ajouter une option" icon="pi pi-plus" class="p-button-text p-button-sm text-indigo-600 font-bold" @click="q.options.push('')" />
                                        </div>

                                        <!-- TYPE VRAI/FAUX -->
                                        <div v-if="q.type === 'boolean'" class="flex gap-4">
                                            <div class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-xl">
                                                <RadioButton :name="'q'+q.id" :value="true" v-model="q.correct" />
                                                <label class="text-sm font-bold text-emerald-600">VRAI</label>
                                            </div>
                                            <div class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-xl">
                                                <RadioButton :name="'q'+q.id" :value="false" v-model="q.correct" />
                                                <label class="text-sm font-bold text-rose-600">FAUX</label>
                                            </div>
                                        </div>

                                        <!-- TYPE TEXTE -->
                                        <div v-if="q.type === 'text'" class="p-4 bg-indigo-50 border border-dashed border-indigo-200 rounded-2xl text-center">
                                            <i class="pi pi-info-circle text-indigo-400 mb-2"></i>
                                            <p class="text-[10px] text-indigo-600 font-bold">Cette question nécessitera une correction manuelle par un instructeur.</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="exam.questions.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-300">
                                    <i class="pi pi-plus-circle text-6xl mb-4"></i>
                                    <p class="text-xl font-bold italic">Commencez à ajouter des questions...</p>
                                </div>
                            </div>
                        </ScrollPanel>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full px-6 pb-6 border-t border-slate-50 pt-4">
                    <span class="text-xs text-slate-400 italic">ID Système : EXAM-{{ exam.id || 'NEW' }}</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="examDialog = false" />
                        <Button label="Enregistrer l'Examen" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="saveExam" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- BANQUE DE QUESTIONS DIALOG --- -->
        <Dialog v-model:visible="questionBankDialog" :style="{width: '800px'}" header="Banque de Questions Partagée" :modal="true">
            <div class="p-4">
                <DataTable :value="questionsBank" class="p-datatable-sm">
                    <Column field="text" header="Question" style="width: 60%"></Column>
                    <Column field="category" header="Catégorie"></Column>
                    <Column field="points" header="Pts"></Column>
                    <Column header="Action" class="text-right">
                        <template #body="{ data }">
                            <Button label="Utiliser" icon="pi pi-plus" class="p-button-sm p-button-outlined rounded-xl" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO EXAM MODULE
 */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 1.5rem 1rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.2s ease;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #f8fafc !important;
}

/* TabView Customization */
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
}

:deep(.custom-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

/* Form Styling */
:deep(.p-inputtext), :deep(.p-inputnumber-input), :deep(.p-dropdown) {
    border-radius: 12px;
    border-color: #e2e8f0;
}

:deep(.p-inputtext:focus) {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

:deep(.p-selectbutton .p-button) {
    font-size: 0.75rem;
    font-weight: 700;
}

:deep(.p-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    border-color: #6366F1;
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
