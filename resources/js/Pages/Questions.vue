<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ATOMIC QUESTION BANK SYSTEM v11.5.0 (Enterprise UI)
 * ======================================================================================
 * Interface de gestion haute performance pour items pédagogiques (Banque de questions).
 *
 * NOUVEAUTÉS :
 * - Alignement complet UI/UX sur le Course & Exam Architect (Workspace Layout)
 * - Éditeur multi-format avec navigation par onglets verticaux
 * - Standardisation B2B (Design épuré, ombres subtiles, data grid optimisé)
 *
 * @author  SubnetCongo Framework Division
 * @module  Evaluation/QuestionBank
 */

import { ref, reactive, computed, watch, onMounted, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// IMPORTATION DES COMPOSANTS PRIMEVUE
// ======================================================================================
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from 'primevue/confirmdialog';
import SelectButton from 'primevue/selectbutton';
import Editor from 'primevue/editor';
import Breadcrumb from 'primevue/breadcrumb';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';
import Tooltip from 'primevue/tooltip';
import Timeline from 'primevue/timeline';

// ======================================================================================
// PROPS DEPUIS LE CONTROLLER (LARAVEL/INERTIA)
// ======================================================================================
const props = defineProps({
    questions: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 15 }) },
    filters: { type: Object, default: () => ({}) },
    statistics: { type: Object, default: () => ({ total_items: 0, active_items: 0, avg_difficulty: 0, needs_review: 0 }) },
    meta: { type: Object, default: () => ({ questionTypes: {}, categories: [], difficulties: {}, statuses: [] }) }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const searchLoading = ref(false);

const questionDialog = ref(false);
const analyticsDialog = ref(false);
const importDialog = ref(false);
const editorRef = ref(null);

const selectedQuestions = ref([]);
const lastSavedAt = ref(null);
const isSaving = ref(false);

// ======================================================================================
// CONFIGURATION DU WORKSPACE (MENU ÉDITEUR)
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: 'Énoncé & Métadonnées', description: 'Format, texte, points', icon: 'pi pi-pencil' },
    { id: 'logic', label: 'Logique de Réponse', description: 'Options, feedback, règles', icon: 'pi pi-check-square' },
    { id: 'audit', label: 'Audit & Versions', description: 'Historique de l\'item', icon: 'pi pi-history' }
]);
const activeEditorTab = ref('general');

// ======================================================================================
// FILTRAGE RÉACTIF (SERVER-SIDE)
// ======================================================================================
const serverFilters = reactive({
    search: props.filters.search || '',
    type: props.filters.type || null,
    category_id: props.filters.category_id || null,
    difficulty: props.filters.difficulty || null,
    status: props.filters.status || null
});

watch(serverFilters, debounce(() => {
    searchLoading.value = true;
    router.get(route('questions.index'), serverFilters, {
        preserveState: true,
        replace: true,
        onFinish: () => searchLoading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRE INERTIA (USEFORM)
// ======================================================================================
const form = useForm({
    id: null,
    question_text: '',
    type: 'multiple_choice',
    category_id: null,
    points: 1.0,
    difficulty: 'intermediate',
    status: 'draft',
    explanation: '',
    options: [],
    correct_answer: '',
    pairs: [],
    metadata: { language: 'php', tests: '' },
    image: null,
    change_summary: ''
});

// ======================================================================================
// LOGIQUE MÉTIER & WORKSPACE
// ======================================================================================

/**
 * Force le rechargement du contenu dans l'instance Quill (PrimeVue Editor)
 */
const forceEditorLoad = () => {
    if (!editorRef.value || !editorRef.value.quill) return;
    const htmlContent = form.question_text || "";
    // On injecte le contenu dans Quill pour assurer l'affichage
    const delta = editorRef.value.quill.clipboard.convert({ html: htmlContent });
    nextTick(() => { editorRef.value.quill.setContents(delta, 'silent'); });
};

const openNew = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.type = 'multiple_choice';
    form.question_text = '';
    if(editorRef.value && editorRef.value.quill) editorRef.value.quill.setContents([], 'silent');
    form.options = [
        { option_text: '', is_correct: true },
        { option_text: '', is_correct: false }
    ];
    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    questionDialog.value = true;
};

const editQuestion = async (data) => {
    form.clearErrors();
    const qData = cloneDeep(data);

    form.id = qData.id;
    form.question_text = qData.question_text;
    form.type = qData.type;
    form.category_id = qData.category_id;
    form.points = qData.points;
    form.difficulty = qData.difficulty;
    form.status = qData.status;
    form.explanation = qData.explanation || '';
    form.options = qData.options || [];
    form.correct_answer = qData.correct_answer || '';
    form.pairs = qData.pairs || [];
    form.metadata = qData.metadata ? (typeof qData.metadata === 'string' ? JSON.parse(qData.metadata) : qData.metadata) : { language: 'php', tests: '' };
    form.change_summary = '';

    // Chargement de l'audit trail réel depuis le serveur
    auditEvents.value = [];
    try {
        const response = await axios.get(route('questions.audit', qData.id));
        auditEvents.value = response.data;
    } catch (e) {
        console.error("Audit load failed", e);
    }

    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    questionDialog.value = true;

    // Injection forcée du contenu HTML dans Quill après un court délai
    nextTick(() => {
        setTimeout(() => {
            forceEditorLoad();
        }, 150);
    });
};

const saveQuestion = () => {
    isSaving.value = true;
    const isUpdate = !!form.id;
    const url = isUpdate ? route('questions.update', form.id) : route('questions.store');

    form.transform((data) => ({
        ...data,
        _method: isUpdate ? 'PUT' : 'POST'
    })).post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Item Sauvegardé', detail: 'La banque a été mise à jour avec succès.', life: 3000 });
            lastSavedAt.value = new Date();
            questionDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les informations saisies.', life: 5000 });
            if (errors.question_text || errors.type) activeEditorTab.value = 'general';
            else activeEditorTab.value = 'logic';
        },
        onFinish: () => isSaving.value = false
    });
};

const confirmDelete = (id) => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir archiver cet item ? Il ne sera plus disponible pour les nouveaux examens.',
        header: 'Archivage de l\'item',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('questions.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'warn', summary: 'Archivé', detail: 'L\'item a été archivé.', life: 3000 })
            });
        }
    });
};

const duplicateQuestion = (id) => {
    router.post(route('questions.duplicate', id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'info', summary: 'Clonage', detail: 'Copie de l\'item générée.', life: 3000 })
    });
};

// --- GESTION DES OPTIONS DYNAMIQUES ---
const addOption = () => form.options.push({ option_text: '', is_correct: false });
const removeOption = (index) => form.options.splice(index, 1);
const toggleExclusiveCorrect = (index) => {
    if (form.type === 'multiple_choice' || form.type === 'mcq_single' || form.type === 'multiple_choice_single') {
        form.options.forEach((opt, i) => opt.is_correct = (i === index));
    }
};

const addPair = () => {
    if(!form.pairs) form.pairs = [];
    form.pairs.push({ key: '', value: '' });
};
const removePair = (index) => form.pairs.splice(index, 1);

// ======================================================================================
// UTILS & FORMATTERS
// ======================================================================================
const isFormValid = computed(() => form.question_text.length > 5);

const getTypeLabel = (type) => props.meta.questionTypes[type] || type;
const getStatusSeverity = (status) => {
    const map = { published: 'success', draft: 'secondary', under_review: 'warning', archived: 'danger' };
    return map[status] || 'info';
};
const getDifficultySeverity = (diff) => {
    const map = { beginner: 'success', intermediate: 'warning', advanced: 'danger', expert: 'help' };
    return map[diff] || 'info';
};
const getTypeIcon = (type) => {
    const icons = { multiple_choice: 'pi pi-list', true_false: 'pi pi-check-circle', short_answer: 'pi pi-pencil', matching: 'pi pi-th-large', coding: 'pi pi-code' };
    return icons[type] || 'pi pi-question';
};
const getTypeColor = (type) => {
    const colors = { multiple_choice: 'text-indigo-500 bg-indigo-50', true_false: 'text-emerald-500 bg-emerald-50', short_answer: 'text-amber-500 bg-amber-50', matching: 'text-blue-500 bg-blue-50', coding: 'text-slate-700 bg-slate-200' };
    return colors[type] || 'text-slate-500 bg-slate-100';
};

const onPage = (event) => {
    router.get(route('questions.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true });
};

// ======================================================================================
// DATA VISUALIZATION (CHARTS)
// ======================================================================================
const chartData = computed(() => ({
    labels: ['Facile', 'Intermédiaire', 'Avancé', 'Expert'],
    datasets: [{
        label: 'Répartition de la banque',
        data: props.statistics?.difficulty_distribution || [0, 0, 0, 0],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6366F1'],
        borderRadius: 4
    }]
}));
const chartOptions = { plugins: { legend: { display: false } }, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { grid: { borderDash: [5,5] } } } };

// L'audit trail est maintenant chargé dynamiquement
const auditEvents = ref([]);

onMounted(() => setTimeout(() => loading.value = false, 800));
</script>

<template>
    <AppLayout>
        <Head title="Banque d'Items Atomiques | LMS Enterprise" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER ET NAVIGATION GLOBALE                                                   -->
            <!-- ============================================================================== -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'Ingénierie Pédagogique', icon: 'pi pi-home' }, { label: 'Banque d\'Items', class: 'font-semibold text-indigo-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            Banque de Questions Centrale
                            <Badge value="v11.5" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Gérez votre patrimoine d'évaluation. Ces items atomiques peuvent être réutilisés dynamiquement dans n'importe quel examen ou certification.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Importer (GIFT/CSV)" icon="pi pi-upload" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 shadow-sm font-medium w-full sm:w-auto" @click="importDialog = true" />
                    <Button label="Créer un Item" icon="pi pi-plus" class="p-button-primary shadow-md shadow-indigo-500/20 font-bold px-6 w-full sm:w-auto" @click="openNew" />
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- KPI DASHBOARD                                                                  -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-list text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Items</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">{{ props.statistics?.total_items || 1245 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-check-circle text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Items Actifs</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">{{ props.statistics?.active_items || 980 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-star text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Barème Moyen</p>
                    </div>
                    <h3 class="text-4xl font-black text-slate-800">{{ props.statistics?.avg_difficulty || 1.5 }} <span class="text-sm text-slate-400">pts</span></h3>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">À Réviser</p>
                    </div>
                    <h3 class="text-4xl font-black text-rose-600">{{ props.statistics?.needs_review || 14 }}</h3>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DATA GRID : TABLEAU DES QUESTIONS                                              -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- Toolbar de filtre -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher dans l'énoncé..." class="w-full sm:w-80 border-slate-200 font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.type" :options="Object.entries(props.meta.questionTypes || {}).map(([k,v]) => ({label:v, value:k}))" optionLabel="label" optionValue="value" placeholder="Format" class="w-40 border-slate-200 font-medium" showClear />
                        <Dropdown v-model="serverFilters.difficulty" :options="Object.entries(props.meta.difficulties || {}).map(([k,v]) => ({label:v, value:k}))" optionLabel="label" optionValue="value" placeholder="Niveau" class="w-40 border-slate-200 font-medium" showClear />
                    </div>

                    <div class="flex items-center gap-2">
                        <Button v-if="selectedQuestions.length > 0" icon="pi pi-trash" :label="`Archiver (${selectedQuestions.length})`" class="p-button-danger p-button-outlined shadow-sm" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser'" />
                    </div>
                </div>

                <!-- DataTable -->
                <DataTable
                    :value="props.questions?.data || []"
                    :lazy="true"
                    :paginator="true"
                    :rows="props.questions?.per_page || 15"
                    :totalRecords="props.questions?.total || 0"
                    @page="onPage"
                    v-model:selection="selectedQuestions"
                    dataKey="id"
                    :loading="loading || searchLoading"
                    class="p-datatable-custom border-none"
                    rowHover
                    stripedRows
                >
                    <template #empty>
                        <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                <i class="pi pi-database text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Banque vide</h3>
                            <p class="text-sm text-center text-slate-500 mb-6">Aucun item ne correspond à vos filtres actuels.</p>
                            <Button label="Créer un item" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />

                    <Column field="type" header="Format" style="width: 5rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm" :class="getTypeColor(data.type)" v-tooltip.top="getTypeLabel(data.type)">
                                <i :class="['text-lg', getTypeIcon(data.type)]"></i>
                            </div>
                        </template>
                    </Column>

                    <Column field="question_text" header="Énoncé de l'Item" style="min-width: 25rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1 py-2">
                                <div class="font-extrabold text-slate-800 text-sm line-clamp-2 hover:text-indigo-600 cursor-pointer transition-colors" @click="editQuestion(data)" v-html="data.question_text || 'Énoncé non défini'"></div>

                                <!-- Aperçu des options / réponses en ligne -->
                                <div v-if="data.options && data.options.length" class="flex flex-wrap gap-1 mt-1">
                                    <template v-for="(opt, idx) in data.options" :key="idx">
                                        <span v-if="opt.option_text"
                                              :class="['text-[9px] px-1.5 py-0.5 rounded border flex items-center gap-1',
                                                       opt.is_correct ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold' : 'bg-slate-50 text-slate-400 border-slate-200']">
                                            <i v-if="opt.is_correct" class="pi pi-check text-[8px]"></i>
                                            {{ opt.option_text }}
                                        </span>
                                    </template>
                                </div>
                                <div v-else-if="data.correct_answer" class="text-[9px] font-bold text-emerald-600 mt-1 uppercase flex items-center gap-1">
                                    <i class="pi pi-key text-[10px]"></i>
                                    Réponse: {{ data.correct_answer === 'true' ? 'VRAI' : (data.correct_answer === 'false' ? 'FAUX' : data.correct_answer) }}
                                </div>

                                <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ data.category?.name || 'Général' }}</span>
                                    <span v-if="data.attempts_count"><i class="pi pi-chart-bar mr-1"></i>{{ data.attempts_count }} pass.</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="difficulty" header="Niveau" style="background-color: #f8fafc; min-width: 8rem;">
                        <template #body="{ data }">
                            <Tag :value="data.difficulty" :severity="getDifficultySeverity(data.difficulty)" class="text-[10px] font-bold px-2 py-1 uppercase tracking-wider" />
                        </template>
                    </Column>

                    <Column field="points" header="Pts" style="background-color: #f8fafc; width: 6rem; text-align: center;">
                        <template #body="{ data }">
                            <Badge :value="data.points" severity="secondary" class="font-bold bg-slate-100 text-slate-600 border border-slate-200" />
                        </template>
                    </Column>

                    <Column field="status" header="État" style="background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div :class="['w-2 h-2 rounded-full', data.status === 'published' ? 'bg-emerald-500' : 'bg-slate-300']"></div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ data.status }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="text-align: right; background-color: #f8fafc; min-width: 10rem;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-chart-pie" class="p-button-text p-button-success p-button-rounded hover:bg-emerald-50" v-tooltip.top="'Analyse psychométrique'" @click="analyticsDialog = true" />
                                <Button icon="pi pi-copy" class="p-button-text p-button-info p-button-rounded hover:bg-blue-50" v-tooltip.top="'Cloner'" @click="duplicateQuestion(data.id)" />
                                <Button icon="pi pi-pen-to-square" class="p-button-text p-button-secondary p-button-rounded hover:bg-indigo-50 hover:text-indigo-600" @click="editQuestion(data)" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded hover:bg-rose-50" @click="confirmDelete(data.id)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- ============================================================================== -->
            <!-- STATS FOOTER SECTION                                                           -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-10">
                <div class="lg:col-span-2 bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-base font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="pi pi-chart-bar text-indigo-500"></i> Répartition par Difficulté</h3>
                    <Chart type="bar" :data="chartData" :options="chartOptions" class="h-48" />
                </div>
                <div class="bg-indigo-900 p-8 rounded-xl text-white shadow-xl relative overflow-hidden border border-indigo-800 flex flex-col justify-between">
                    <i class="pi pi-bolt absolute -right-6 -bottom-6 text-9xl text-white/5 rotate-12"></i>
                    <div class="relative z-10">
                        <h3 class="text-base font-bold mb-3 flex items-center gap-2 text-indigo-100"><i class="pi pi-sparkles text-amber-400"></i> Conseil IA Pédagogique</h3>
                        <p class="text-indigo-200 text-sm leading-relaxed">
                            Votre banque manque de questions de type "Expert" (Seulement 15%). Pour vos certifications finales, nous recommandons d'équilibrer la base avec plus d'items d'analyse complexe (ex: Format Rédaction).
                        </p>
                    </div>
                    <Button label="Générer avec l'IA" class="p-button-sm p-button-outlined p-button-secondary bg-white text-indigo-900 border-none font-bold mt-6 w-full" />
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR : WORKSPACE ATOMIC QUESTION                                     -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="questionDialog" modal :closable="false" :draggable="false" :style="{ width: '90vw', maxWidth: '1200px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[85vh]" headerClass="hidden" class="rounded-xl shadow-2xl overflow-hidden border border-slate-200">

            <!-- WORKSPACE HEADER -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isFormValid ? '100%' : '40%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md">
                        <i class="pi pi-th-large text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight">
                            {{ form.id ? 'Éditer l\'Item #'+form.id : 'Nouvel Item Atomique' }}
                            <Tag :value="form.status" class="text-[9px] uppercase tracking-wider px-2 py-0.5 ml-2 bg-slate-100 text-slate-600 border border-slate-200"></Tag>
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span>Conception de la banque</span>
                            <span v-if="lastSavedAt" class="text-emerald-600 flex items-center gap-1"><i class="pi pi-check-circle"></i> Enregistré</span>
                        </div>
                    </div>
                </div>

                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 w-10 h-10" @click="questionDialog = false" />
            </div>

            <!-- WORKSPACE BODY -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR MENU -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">Sections</div>
                    <div class="p-4 flex-1 space-y-2">
                        <button v-for="item in editorMenu" :key="item.id" @click="activeEditorTab = item.id"
                                class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                                :class="[activeEditorTab === item.id ? 'bg-indigo-50 border border-indigo-100' : 'hover:bg-slate-50 border border-transparent']">

                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>
                            <i :class="[item.icon, 'text-xl mt-0.5 transition-colors', activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                            <div>
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div>
                                <div class="text-[11px] font-medium text-slate-500 leading-tight">{{ item.description }}</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- CONTENT AREA -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-slate-50">
                    <div class="max-w-4xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <!-- TAB 1: ÉNONCÉ & MÉTA -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Classification & Énoncé</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Format de question</label>
                                        <Dropdown v-model="form.type" :options="Object.entries(props.meta.questionTypes || {}).map(([k,v]) => ({label:v, value:k}))" optionLabel="label" optionValue="value" class="w-full border-slate-300" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Catégorie Métier</label>
                                        <Dropdown v-model="form.category_id" :options="props.meta.categories" optionLabel="name" optionValue="id" placeholder="Sélectionnez..." class="w-full border-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2 mb-6">
                                    <label class="block text-sm font-bold text-slate-700">Énoncé principal (Stem) <span class="text-rose-500">*</span></label>
                                    <Editor ref="editorRef" v-model="form.question_text" editorStyle="height: 200px" placeholder="Rédigez la question ici..." @load="forceEditorLoad" />
                                    <small class="text-rose-500 font-bold" v-if="form.errors.question_text">{{ form.errors.question_text }}</small>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50 p-5 rounded-xl border border-slate-100">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Barème (Points)</label>
                                        <InputNumber v-model="form.points" mode="decimal" :minFractionDigits="1" class="w-full" :pt="{ input: { class: 'text-center font-bold text-indigo-600' } }" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Difficulté</label>
                                        <Dropdown v-model="form.difficulty" :options="Object.entries(props.meta.difficulties || {}).map(([k,v]) => ({label:v, value:k}))" optionLabel="label" optionValue="value" class="w-full" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Statut</label>
                                        <Dropdown v-model="form.status" :options="['draft', 'published', 'under_review', 'archived']" class="w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: LOGIQUE DE RÉPONSE -->
                        <div v-show="activeEditorTab === 'logic'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                                    <h3 class="text-lg font-bold text-slate-800">Paramétrage de la Réponse</h3>
                                    <Tag :value="getTypeLabel(form.type)" class="bg-indigo-50 text-indigo-600 border border-indigo-100" />
                                </div>

                                <!-- QCM -->
                                <div v-if="['multiple_choice', 'mcq_single', 'mcq_multiple'].includes(form.type)" class="space-y-4">
                                    <div v-for="(opt, idx) in form.options" :key="idx"
                                         class="flex items-start gap-4 p-4 rounded-xl border transition-all bg-slate-50"
                                         :class="opt.is_correct ? 'border-emerald-300 shadow-[0_0_0_2px_rgba(16,185,129,0.1)] bg-white' : 'border-slate-200 hover:border-indigo-200'">

                                        <div class="pt-2 flex flex-col items-center">
                                            <RadioButton :name="'opt'" :value="true" :modelValue="!!(opt.is_correct == true || opt.is_correct == 1 || opt.is_correct == '1')" @update:modelValue="toggleExclusiveCorrect(idx)" />
                                            <span class="text-[9px] font-bold mt-1 uppercase" :class="!!(opt.is_correct == true || opt.is_correct == 1 || opt.is_correct == '1') ? 'text-emerald-500' : 'text-slate-400'">Juste</span>
                                        </div>

                                        <div class="flex-1 space-y-2">
                                            <InputText v-model="opt.option_text" placeholder="Texte de l'option..." class="w-full font-bold text-slate-700 bg-white" />
                                            <InputText v-model="opt.feedback" placeholder="Feedback spécifique si sélectionnée (Optionnel)" class="w-full text-xs text-slate-500 bg-transparent border-transparent focus:bg-white focus:border-slate-300 shadow-none" />
                                        </div>

                                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded mt-1" @click="removeOption(idx)" />
                                    </div>
                                    <Button label="Ajouter une option" icon="pi pi-plus" class="p-button-outlined p-button-secondary bg-white text-indigo-600 font-bold mt-2 w-full" @click="addOption" />
                                </div>

                                <!-- Vrai/Faux -->
                                <div v-else-if="form.type === 'true_false'" class="flex justify-center py-10 bg-slate-50 rounded-xl border border-slate-200">
                                    <div class="text-center w-full max-w-sm">
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">L'affirmation est-elle vraie ou fausse ?</p>
                                        <SelectButton v-model="form.correct_answer" :options="[{label:'VRAI', value:'true'}, {label:'FAUX', value:'false'}]" optionLabel="label" optionValue="value" class="w-full flex" :pt="{ button: { class: 'flex-1 font-black py-4 bg-white' } }" />
                                    </div>
                                </div>

                                <!-- Réponse Courte -->
                                <div v-else-if="form.type === 'short_answer'" class="bg-amber-50 p-6 rounded-xl border border-amber-100">
                                    <label class="block text-sm font-bold text-amber-900 mb-2 flex items-center gap-2"><i class="pi pi-pencil"></i> Réponse exacte attendue</label>
                                    <InputText v-model="form.correct_answer" class="w-full p-4 font-mono text-sm bg-white border-amber-200 shadow-sm" placeholder="Mot-clé exact ou expression..." />
                                    <p class="text-[10px] text-amber-700 mt-2 font-bold uppercase tracking-widest">La validation ignore la casse (Majuscules/Minuscules).</p>
                                </div>

                                <!-- Matching -->
                                <div v-else-if="form.type === 'matching'" class="space-y-4">
                                    <div v-for="(pair, pidx) in form.pairs" :key="pidx" class="flex items-center gap-4 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                        <InputText v-model="pair.key" placeholder="Élément A" class="flex-1" />
                                        <i class="pi pi-arrows-h text-slate-400"></i>
                                        <InputText v-model="pair.value" placeholder="Élément B correspondant" class="flex-1" />
                                        <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm" @click="removePair(pidx)" />
                                    </div>
                                    <Button label="Ajouter une paire" icon="pi pi-plus" class="p-button-text font-bold text-indigo-600" @click="addPair" />
                                </div>

                                <Divider class="my-8" />

                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Explication globale (Feedback après examen)</label>
                                    <Textarea v-model="form.explanation" rows="4" class="w-full bg-slate-50 border-slate-300" placeholder="Expliquez pourquoi est-ce la bonne réponse..." />
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: AUDIT -->
                        <div v-show="activeEditorTab === 'audit'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Version & Journal</h3>

                                <div class="mb-8">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Note de mise à jour (Optionnel)</label>
                                    <InputText v-model="form.change_summary" class="w-full bg-slate-50 border-slate-300" placeholder="Ex: Correction d'une faute d'orthographe dans l'option A..." />
                                </div>

                                <div v-if="!form.id" class="text-center py-10 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="pi pi-history text-3xl text-slate-300 mb-3"></i>
                                    <p class="text-sm font-bold text-slate-500">L'historique sera disponible après la création.</p>
                                </div>

                                <Timeline v-else :value="auditEvents" class="custom-timeline">
                                    <template #marker="slotProps">
                                        <span class="flex items-center justify-center w-8 h-8 rounded-full z-10 shadow-sm text-white" :style="{ backgroundColor: slotProps.item.color }">
                                            <i :class="slotProps.item.icon" class="text-[11px]"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 ml-4 mb-6 shadow-sm">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="font-bold text-slate-800 text-sm">{{ slotProps.item.status }}</span>
                                                <span class="text-[10px] font-mono text-slate-400 bg-white px-2 py-1 rounded border border-slate-200">{{ slotProps.item.date }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                                <Avatar icon="pi pi-user" size="small" shape="circle" class="bg-slate-200 w-5 h-5 text-[10px]" />
                                                <strong>{{ slotProps.item.user }}</strong>
                                            </div>
                                        </div>
                                    </template>
                                </Timeline>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER -->
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="questionDialog = false" :disabled="isSaving" />
                <Button label="Enregistrer l'Item" icon="pi pi-check" class="p-button-primary shadow-lg font-bold px-8" @click="saveQuestion" :loading="isSaving" :disabled="!isFormValid" />
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- MODAL : ANALYSE D'ITEM (STATISTIQUES)                                          -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="analyticsDialog" modal header="Analyse Statistique de l'Item" class="w-full md:w-[700px] rounded-xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-indigo-600 text-white p-6 rounded-xl shadow-md text-center">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-indigo-200 mb-1">Indice de Difficulté (p)</div>
                        <div class="text-3xl font-black">0.42</div>
                    </div>
                    <div class="bg-white border border-slate-200 p-6 rounded-xl shadow-sm text-center">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Discrimination (D)</div>
                        <div class="text-3xl font-black text-slate-800">0.74</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="text-sm font-bold text-slate-800 mb-4">Analyse des Distracteurs (QCM)</h4>
                    <div class="space-y-4">
                        <div v-for="d in 4" :key="d" class="flex items-center gap-4">
                            <span class="w-16 text-xs font-bold text-slate-500">Option {{ ['A','B','C','D'][d-1] }}</span>
                            <div class="flex-1 bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div :class="d === 2 ? 'bg-emerald-500' : 'bg-slate-400'" class="h-full" :style="{ width: (d === 2 ? 65 : d * 10) + '%' }"></div>
                            </div>
                            <span class="text-xs font-bold" :class="d === 2 ? 'text-emerald-600' : 'text-slate-600'">{{ d === 2 ? 65 : d * 10 }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- MODAL : IMPORTATION                                                            -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="importDialog" modal header="Importer des Items (GIFT / CSV)" class="w-full md:w-[600px] rounded-xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-8 text-center space-y-6">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mx-auto border border-indigo-100">
                    <i class="pi pi-upload text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Upload de fichier</h3>
                    <p class="text-sm text-slate-500">Glissez-déposez votre fichier format Moodle GIFT ou CSV Standard.</p>
                </div>

                <FileUpload mode="basic" name="bank_file" accept=".txt,.gift,.csv" :maxFileSize="2000000" chooseLabel="Parcourir l'ordinateur" class="p-button-outlined p-button-secondary bg-white w-full border-dashed border-2 py-4 font-bold" />

                <div class="p-4 bg-amber-50 rounded-xl border border-amber-100 text-left flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-amber-500 mt-0.5"></i>
                    <p class="text-xs text-amber-800 leading-relaxed">Assurez-vous que l'encodage du fichier est en <strong>UTF-8</strong> pour préserver les accents et caractères spéciaux de vos énoncés.</p>
                </div>
            </div>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée façon macOS / SaaS Moderne */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
    border: 2px solid transparent;
    background-clip: padding-box;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* Animations d'entrée (Fade In) */
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Customisation PrimeVue DataTable B2B */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Timeline Custom (Audit Log) */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    width: 100%;
}
:deep(.custom-timeline .p-timeline-event-separator) {
    margin-right: 1rem;
}

/* Overrides UI Forms */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-dropdown:not(.p-disabled).p-focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
}

/* Override Editor Toolbar */
:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
    background-color: #f8fafc;
    border-color: #e2e8f0;
}
:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
    border-color: #e2e8f0;
}
</style>
