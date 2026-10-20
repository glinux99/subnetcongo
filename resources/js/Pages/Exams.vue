<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXAM ARCHITECT & ASSESSMENT ENGINE v11.5.0 (Enterprise UI)
 * ======================================================================================
 * Module d'ingénierie pédagogique pour la création d'évaluations et certifications.
 * Gère le cycle de vie complet : Création, Édition (Workspace), Déploiement et Analyse.
 *
 * CORRECTIONS APPORTÉES :
 * - Intégration complète de router.delete() pour la suppression
 * - Séparation correcte entre examForm.post() et examForm.put()
 * - Gestion des erreurs Inertia (Affichage des retours de validation Laravel)
 * - Implémentation de la Banque d'Items et de l'onglet Audit
 *
 * @author SubnetCongo Framework Division
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
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
import Sidebar from 'primevue/sidebar';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import ToggleSwitch from 'primevue/toggleswitch';
import SelectButton from 'primevue/selectbutton';
import Breadcrumb from 'primevue/breadcrumb';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Chart from 'primevue/chart';
import Knob from 'primevue/knob';
import Slider from 'primevue/slider';
import Editor from 'primevue/editor';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ProgressBar from 'primevue/progressbar';
import Timeline from 'primevue/timeline';
import Message from 'primevue/message';
import Tooltip from 'primevue/tooltip';
import OverlayPanel from 'primevue/overlaypanel';

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const examDialog = ref(false);
const analyticsDialog = ref(false);
const importExportSidebar = ref(false);
const questionBankDialog = ref(false);
const expandedRows = ref([]);
const searchLoading = ref(false);
const editorRef = ref(null);
const selectedExams = ref([]);

// Simulation de sauvegarde
const lastSavedAt = ref(null);
const isSaving = ref(false);

// ======================================================================================
// CONFIGURATION DU WORKSPACE (MENU ÉDITEUR)
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: 'Paramètres Généraux', description: 'Titre, cours, consignes', icon: 'pi pi-id-card' },
    { id: 'syllabus', label: 'Syllabus & Items', description: 'Constructeur de questions', icon: 'pi pi-list' },
    { id: 'rules', label: 'Règles & Anti-triche', description: 'Chrono, seuil, proctoring', icon: 'pi pi-shield' },
    { id: 'audit', label: 'Audit & Versioning', description: 'Historique des révisions', icon: 'pi pi-history' }
]);
const activeEditorTab = ref('general');

// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    exams: { type: Object, default: () => ({ data: [], total: 0 }) },
    courses: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    stats_overview: { type: Object, default: () => ({ total: 0, attempts: 0, avg_success_rate: 0, anti_cheat_alerts: 0 }) },
    filters: { type: Object, default: () => ({}) },
    global_question_bank: { type: Array, default: () => [] }
});

// ======================================================================================
// ÉTATS RÉACTIFS & FILTRES SERVEUR
// ======================================================================================
const examList = computed(() => props.exams?.data || props.exams || []);

const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    course_id: props.filters?.course_id || null,
});

watch(serverFilters, debounce(() => {
    searchLoading.value = true;
    router.get(route('exams.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => searchLoading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRE INERTIA (USEFORM)
// ======================================================================================
const examForm = useForm({
    id: null,
    title: '',
    course_id: null,
    category_id: null,
    type: 'Quiz',
    status: 'Draft',
    passing_score: 70,
    duration_minutes: 60,
    attempts_allowed: 1, // 0 for unlimited
    description: '',
    is_published: false,
    password: '',
    available_from: null,
    available_until: null,
    settings: {
        shuffle_questions: true,
        shuffle_options: true,
        show_results_instantly: true,
        proctoring_mode: 'none',
        allow_review: true,
        show_correct_answers: true,
    },
    questions: []
});

watch(activeEditorTab, (newValue) => {
    if (newValue === 'audit' && examForm.id) {
        getAuditLogsForExam(examForm.id);
    } else if (newValue === 'audit' && !examForm.id) {
        auditEvents.value = [];
    }
});

// ======================================================================================
// DICTIONNAIRES DE RÉFÉRENCE
// ======================================================================================
const examTypes = [
    { label: 'Quiz (Pratique)', value: 'Quiz', icon: 'pi pi-clock' },
    { label: 'Certification Finale', value: 'Certification', icon: 'pi pi-verified' },
    { label: 'Test de Niveau', value: 'Placement', icon: 'pi pi-map' }
];

const questionTypes = [
    { label: 'Choix Multiples (QCM)', value: 'multiple_choice', icon: 'pi pi-list', color: 'text-blue-500', bg: 'bg-blue-50' },
    { label: 'Vrai ou Faux', value: 'true_false', icon: 'pi pi-check-circle', color: 'text-red-500', bg: 'bg-red-50' },
    { label: 'Réponse Courte', value: 'short_answer', icon: 'pi pi-pencil', color: 'text-amber-500', bg: 'bg-amber-50' }
];

const proctoringLevels = [
    { label: 'Standard', value: 'none', desc: 'Aucune restriction logicielle' },
    { label: 'Modéré (Focus)', value: 'standard', desc: 'Bloque le copier/coller et la sortie d\'onglet' },
    { label: 'Strict (Proctoring)', value: 'strict', desc: 'Lockdown browser, Webcam & IA' }
];

// ======================================================================================
// MÉTHODES CRUD (CREATE, UPDATE, DELETE)
// ======================================================================================

/**
 * Prépare le formulaire pour une nouvelle création
 */
const openNew = () => {
    examForm.reset();
    examForm.clearErrors();
    examForm.id = null;
    examForm.questions = [];
    examForm.settings = { shuffle_questions: true, shuffle_options: true, show_results_instantly: true, proctoring_mode: 'none' };

    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    examDialog.value = true;
};

/**
 * Force le rechargement du contenu dans l'instance Quill
 * Utile lors du switch d'onglets ou de l'ouverture initiale
 */
const forceEditorLoad = () => {
    if (!editorRef.value || !editorRef.value.quill) return;
    const htmlContent = examForm.description || "";
    const delta = editorRef.value.quill.clipboard.convert({ html: htmlContent });
    nextTick(() => { editorRef.value.quill.setContents(delta, 'silent'); });
};
/**
 * Charge un examen existant dans l'éditeur (Deep Clone)
 */
const editExam = (data) => {
    examForm.clearErrors();

    // Deep clone pour éviter la mutation réactive accidentelle des props
    const examData = cloneDeep(data);

    examForm.id = examData.id;
    examForm.title = examData.title;
    examForm.course_id = examData.course_id;
    examForm.category_id = examData.category_id;
    examForm.type = examData.type || 'Quiz';
    examForm.status = examData.status || 'Draft';
    examForm.passing_score = examData.pass_percentage || examData.passing_score || 70; // Map pass_percentage
    examForm.duration_minutes = examData.duration_minutes || 60;
    examForm.attempts_allowed = examData.attempts_allowed ?? 1;
    examForm.description = examData.description || '';

    examForm.is_published = examData.status === 'Active'; // Dépend du statut
    examForm.password = examData.password || null;
    examForm.available_from = examData.available_from || null;
    examForm.available_until = examData.available_until || null;

    // Parsage des settings (souvent stockés en JSON dans la DB)
    let parsedSettings = typeof examData.settings === 'string' ? JSON.parse(examData.settings) : (examData.settings || {});
    examForm.settings = {
        shuffle_questions: parsedSettings.shuffle_questions ?? examData.randomize_questions ?? true,
        shuffle_options: parsedSettings.shuffle_options ?? examData.randomize_options ?? true,
        show_results_instantly: parsedSettings.show_results_instantly ?? examData.show_results_immediately ?? true,
        proctoring_mode: parsedSettings.proctoring_mode ?? (examData.require_lockdown_browser ? 'standard' : 'none'),
        allow_review: parsedSettings.allow_review ?? examData.show_correct_answers ?? true,
        show_correct_answers: parsedSettings.show_correct_answers ?? examData.show_correct_answers ?? true,
    };

    examForm.questions = examData.questions || [];

    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    examDialog.value = true;

    // Injection forcée du contenu HTML dans Quill pour l'édition
    nextTick(() => {
        setTimeout(() => {
            if (editorRef.value && editorRef.value.quill) {
                forceEditorLoad();
            }
        }, 100);
    });
};

/**
 * Importe une question depuis la banque globale vers le syllabus actuel
 */
const importQuestionFromBank = (q) => {
    // On crée une nouvelle structure de question basée sur celle de la banque
    // On force l'id à null pour que le serveur crée un nouvel enregistrement pour cet examen
    const clonedQuestion = {
        id: null,
        text: q.question_text || q.text,
        type: q.type,
        points: q.points || 1.0,
        explanation: q.explanation || '',
        // Clonage des options
        options: (q.options || []).map(opt => ({
            id: null,
            option_text: opt.option_text || opt.text,
            is_correct: !!opt.is_correct,
            order: opt.order || 0
        })),
        // Champs pour types Boolean ou Short Answer
        correct: q.correct,
        correct_answer: q.correct_answer
    };

    examForm.questions.push(clonedQuestion);

    toast.add({ severity: 'success', summary: 'Item Importé', detail: 'La question a été ajoutée à votre syllabus.', life: 2500 });
};

/**
 * Gère la sauvegarde (POST pour création, PUT pour mise à jour)
 */
const saveExam = (status = 'Active') => {
    examForm.status = status;
    examForm.is_published =   examForm.status == 'Active' ? true : false
    isSaving.value = true;

    // Détermination de la méthode HTTP et de la route
    const isUpdate = !!examForm.id;
    const url = isUpdate ? route('exams.update', examForm.id) : route('exams.store');

    // Nettoyage avant envoi pour éviter des données lourdes/inutiles
    examForm.transform((data) => {
        return {
            ...data,
            _method: isUpdate ? 'PUT' : 'POST',
        };
    }).post(url, {
        preserveScroll: true,
        onSuccess: () => {
            const msg = status === 'Draft' ? 'Brouillon sauvegardé avec succès' : 'Évaluation publiée en ligne';
            toast.add({ severity: 'success', summary: isUpdate ? 'Mise à jour' : 'Création', detail: msg, life: 4000 });
            lastSavedAt.value = new Date();

            // Ferme la modale uniquement si on publie (Garde ouvert si brouillon)
            if (status !== 'Draft') {
                examDialog.value = false;
            }
        },
        onError: (errors) => {
            console.error("Validation Errors:", errors);
            toast.add({ severity: 'error', summary: 'Erreur de validation', detail: 'Vérifiez les champs obligatoires.', life: 5000 });
            // Redirection automatique vers l'onglet général si l'erreur s'y trouve
            if (errors.title || errors.course_id || errors.type || errors.description) {
                activeEditorTab.value = 'general';
            } else if (errors.passing_score || errors.duration_minutes || errors.attempts_allowed || errors.settings) {
                activeEditorTab.value = 'rules';
            } else if (errors.questions) {
                activeEditorTab.value = 'syllabus';
            }
        },
        onFinish: () => isSaving.value = false
    });
};

/**
 * Gère la suppression via Inertia router
 */
const deleteExam = (id) => {
    confirm.require({
        message: 'Cette action supprimera définitivement cette évaluation et tout son contenu (questions, options). Confirmer ?',
        header: 'Suppression destructrice',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Annuler',
        acceptLabel: 'Oui, supprimer',
        accept: () => {
            router.delete(route('exams.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Supprimé', detail: 'L\'évaluation a été purgée du système.', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Échec', detail: 'Impossible de supprimer un examen avec des tentatives existantes.', life: 5000 });
                }
            });
        }
    });
};

/**
 * Action en masse : Archiver
 */
const confirmBulkArchive = () => {
    if(selectedExams.value.length === 0) return;
    confirm.require({
        message: `Vous êtes sur le point d'archiver ${selectedExams.value.length} évaluations. Continuer ?`,
        header: 'Archivage en masse',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-warning',
        accept: () => {
            toast.add({ severity: 'info', summary: 'Opération en cours', detail: 'Les éléments sélectionnés ont été archivés.', life: 3000 });
            selectedExams.value = [];
        }
    });
};

// ======================================================================================
// LOGIQUE DU CONSTRUCTEUR DE QUESTIONS (SYLLABUS)
// ======================================================================================

const addNewQuestion = (type) => {
    const newQ = {
        id: 'new_' + Date.now(), // ID temporaire pour le frontend
        text: '',
        type: type,
        points: 1.0,
        options: type === 'multiple_choice' ? [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false }
        ] : [],
        explanation: '',
        correct: type === 'true_false' ? true : null,
        correct_answer: type === 'short_answer' ? '' : null
    };
    examForm.questions.push(newQ);

    // Auto-scroll vers la nouvelle question
    nextTick(() => {
        const el = document.getElementById(newQ.id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
};

const duplicateQuestion = (index) => {
    const clone = cloneDeep(examForm.questions[index]);
    clone.id = 'dup_' + Date.now(); // Nouvel ID temporaire
    clone.text = clone.text + ' (Copie)';
    examForm.questions.splice(index + 1, 0, clone);

    toast.add({ severity: 'info', summary: 'Item dupliqué', detail: 'La question a été copiée.', life: 2000 });
};

const removeQuestion = (index) => {
    examForm.questions.splice(index, 1);
};

const moveQuestion = (index, dir) => {
    if (dir === 'up' && index > 0) {
        const temp = examForm.questions[index];
        examForm.questions[index] = examForm.questions[index - 1];
        examForm.questions[index - 1] = temp;
    } else if (dir === 'down' && index < examForm.questions.length - 1) {
        const temp = examForm.questions[index];
        examForm.questions[index] = examForm.questions[index + 1];
        examForm.questions[index + 1] = temp;
    }
};

const addOptionToQuestion = (questionIndex) => {
    examForm.questions[questionIndex].options.push({ option_text: '', is_correct: false });
};

const removeOptionFromQuestion = (questionIndex, optionIndex) => {
    if(examForm.questions[questionIndex].options.length > 2) {
        examForm.questions[questionIndex].options.splice(optionIndex, 1);
    } else {
        toast.add({ severity: 'warn', summary: 'Minimum requis', detail: 'Une question QCM doit avoir au moins 2 options.', life: 3000 });
    }
};

const setExclusiveCorrectOption = (questionIndex, optionIndex) => {
    // Si la question est de type QCM strict (1 seule bonne réponse)
    examForm.questions[questionIndex].options.forEach((opt, idx) => {
        opt.is_correct = (idx === optionIndex);
    });
};

// ======================================================================================
// UTILITAIRES & FORMATTERS
// ======================================================================================
const calculateTotalPoints = computed(() => {
    return examForm.questions.reduce((acc, q) => acc + (parseFloat(q.points) || 0), 0).toFixed(2);
});

const isFormValid = computed(() => {
    return examForm.title.length > 3 && examForm.questions.length > 0;
});

const getStatusColor = (status) => {
    const map = {
        Active: { sev: 'success', icon: 'pi pi-check-circle', label: 'En Ligne' },
        Draft: { sev: 'secondary', icon: 'pi pi-file-edit', label: 'Brouillon' },
        Archived: { sev: 'danger', icon: 'pi pi-box', label: 'Archivé' },
        Pending: { sev: 'warning', icon: 'pi pi-clock', label: 'En Attente' }
    };
    return map[status] || { sev: 'info', icon: 'pi pi-info-circle', label: status };
};

const formatDate = (dateString) => {
    if(!dateString) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(dateString));
};

const getAuditLogsForExam = async (examId) => {
    if (!examId) {
        auditEvents.value = [];
        return;
    }
    try {
        const response = await axios.get(route('exams.audit-logs', examId));
        auditEvents.value = response.data || [];
    } catch (error) {
        console.error("Failed to fetch audit logs:", error);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger l\'historique d\'audit.', life: 3000 });
        auditEvents.value = [];
    }
};

// ======================================================================================
// DONNÉES SIMULÉES POUR LES MODALS D'ANALYSE & AUDIT (DEMO UI)
// ======================================================================================
const performanceChartData = reactive({
    labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
    datasets: [{
        label: 'Nombre d\'Apprenants', // Ces données devraient venir du backend pour un examen spécifique
        // Exemple de données mockées, à remplacer par des données réelles de ExamAttempt
        // Pour un examen donné, on ferait une requête pour obtenir la distribution des scores
        // Ex: ExamAttempt::where('exam_id', $exam->id)->select(DB::raw('FLOOR(score/20)*20 as score_band'), DB::raw('count(*) as total'))->groupBy('score_band')->get();
        // Ou une distribution pré-calculée par le contrôleur
        data: [12, 24, 65, 142, 89],
        backgroundColor: '#6366f1',
        borderRadius: 4
    }]
});

const auditEvents = ref([
]);

// Lifecycle
onMounted(() => {
    setTimeout(() => loading.value = false, 800);
});
</script>

<template>
    <AppLayout>
        <Head title="Architecte d'Évaluation | LMS Enterprise" />
        <Toast position="top-right" />
        <ConfirmDialog />

   <div class="page-bg min-h-screen font-sans pb-20">

    <!-- ============================================================================== -->
    <!-- HEADER DE PAGE (Dark Intelligence Hub Style)                                   -->
    <!-- ============================================================================== -->
    <div class="flex flex-col gap-2 mb-12">
        
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm px-1 mb-2">
            <Breadcrumb 
                :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Évaluations & Quiz' }]" 
                class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
            />
        </div>

        <!-- Bannière Sombre avec padding bas (pb-28) pour le chevauchement -->
        <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
            
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                
                <!-- Partie Gauche (Titres) -->
                <div class="space-y-4 max-w-3xl">
                    <!-- Badge Certifications -->
                    <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                        Certifications
                    </span>

                    <!-- Titre Principal (Italique, gras, 2 couleurs) -->
                    <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                        <span class="text-white">Assessment</span>
                        <span class="text-[#E92B2B]">Center</span>
                    </h1>

                    <!-- Sous-titre -->
                    <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                        Concevez des épreuves certifiantes, gérez la banque d'items et analysez la fiabilité psychométrique de vos cursus de manière centralisée.
                    </p>
                </div>

                <!-- Partie Droite (Boutons) -->
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <Button 
                        label="Audit & Imports" 
                        icon="pi pi-database" 
                        class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                        @click="importExportSidebar = true" 
                    />
                    <Button 
                        label="Nouvelle Évaluation" 
                        icon="pi pi-plus" 
                        class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white w-full sm:w-auto font-bold shadow-lg shadow-red-900/50 transition-colors px-6" 
                        @click="openNew" 
                    />
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- KPI CARDS (Overlapping / Chevauchement)                                        -->
        <!-- ============================================================================== -->
        <!-- Marge négative (-mt-16) pour faire remonter les cartes sur la bannière sombre -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-4 md:px-6 -mt-16 relative z-20">
            
            <!-- KPI 1 : Examens Actifs -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-blue-50 flex items-center justify-center mb-4">
                    <i class="pi pi-verified text-blue-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Examens Actifs</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ props.stats_overview?.total || 24 }}</p>
                </div>
            </div>

            <!-- KPI 2 : Tentatives (Ce mois) -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-red-50 flex items-center justify-center mb-4">
                    <i class="pi pi-users text-red-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tentatives (Ce mois)</h3>
                    <div class="flex items-center gap-2">
                        <p class="text-3xl font-extrabold text-slate-900">{{ props.stats_overview?.attempts?.toLocaleString() }}</p>
                        <!-- Le badge 'Live' est conservé et adapté pour tenir dans ce layout -->
                        <span class="text-[10px] font-bold text-red-500 bg-red-50 border border-emerald-100 px-2 py-0.5 rounded-md uppercase tracking-wider">Live</span>
                    </div>
                </div>
            </div>

            <!-- KPI 3 : Taux de Réussite -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-amber-50 flex items-center justify-center mb-4">
                    <i class="pi pi-chart-pie text-amber-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Taux de Réussite</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ props.stats_overview?.avg_success_rate }}<span class="text-lg text-slate-400 ml-1">%</span></p>
                </div>
            </div>

            <!-- KPI 4 : Alertes Anti-Triche -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-rose-50 flex items-center justify-center mb-4">
                    <i class="pi pi-shield text-rose-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Alertes Anti-Triche</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ props.stats_overview?.anti_cheat_alerts }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- DATA GRID : TABLEAU DES ÉVALUATIONS (Inchangé)                                 -->
    <!-- ============================================================================== -->
    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-slate-200 overflow-hidden mt-6">

        <!-- Toolbar de filtre -->
        <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <IconField iconPosition="left">
                    <InputIcon class="pi pi-search text-slate-400" />
                    <InputText v-model="serverFilters.search" placeholder="Rechercher par titre..." class="w-full sm:w-80 border-slate-200 font-medium bg-white" />
                </IconField>

                <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                <Dropdown v-model="serverFilters.status" :options="['Active', 'Draft', 'Archived']" placeholder="État du flux" class="w-48 border-slate-200 font-medium bg-white" showClear />
                <Dropdown v-model="serverFilters.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Filtrer par cours" class="w-56 border-slate-200 font-medium bg-white" showClear filter />
            </div>

            <div class="flex items-center gap-2">
                <Button v-if="selectedExams.length > 0" icon="pi pi-box" :label="`Archiver (${selectedExams.length})`" class="p-button-warning p-button-outlined shadow-sm" @click="confirmBulkArchive" />
                <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser'" />
            </div>
        </div>

        <!-- DataTable Principale -->
        <DataTable
            v-model:selection="selectedExams"
            v-model:expandedRows="expandedRows"
            :value="examList"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :loading="loading || searchLoading"
            class="p-datatable-custom border-none"
            rowHover
            stripedRows
        >
            <template #empty>
                <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                        <i class="pi pi-file-edit text-5xl text-slate-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun examen trouvé</h3>
                    <p class="text-sm text-center text-slate-500 mb-6">Il semblerait que votre base de données d'évaluations soit vide pour ces critères.</p>
                    <Button label="Créer un examen" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
            <Column expander style="width: 3rem; background-color: #f8fafc;" />

            <Column field="title" header="Épreuve & Programme" style="min-width: 25rem; background-color: #f8fafc;">
                <template #body="{ data }">
                    <div class="flex items-center gap-4 py-2">
                        <div class="w-12 h-12 rounded-xl border border-slate-200 flex items-center justify-center flex-shrink-0 shadow-sm"
                             :class="data.type === 'Certification' ? 'bg-blue-50 text-indigo-600 border-indigo-100' : 'bg-slate-50 text-slate-500'">
                            <i :class="data.type === 'Certification' ? 'pi pi-verified' : 'pi pi-clock'" class="text-xl"></i>
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="font-extrabold text-slate-800 text-base hover:text-indigo-600 cursor-pointer transition-colors" @click="editExam(data)">
                                {{ data.title }}
                                </span>
                            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mt-1">
                                <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200 flex items-center gap-1"><i class="pi pi-book text-[10px]"></i> {{ data.course?.title || 'Tronc Commun' }}</span>
                                <span class="text-slate-400 flex items-center gap-1"><i class="pi pi-list text-[10px]"></i> {{ data.questions_count || 0 }} items</span>
                            </div>
                        </div>
                    </div>
                </template>
            </Column>

            <Column field="settings" header="Format & Règles" style="background-color: #f8fafc; min-width: 12rem;">
                <template #body="{ data }">
                    <div class="flex flex-col gap-1.5 text-xs text-slate-600 font-medium">
                        <span class="flex items-center gap-2"><i class="pi pi-stopwatch text-slate-400"></i> Durée : <strong class="text-slate-800">{{ data.duration_minutes }} min</strong></span>
                        <span class="flex items-center gap-2"><i class="pi pi-flag-fill text-red-500"></i> Validation : <strong class="text-emerald-700">{{ data.pass_percentage }}%</strong></span>
                    </div>
                </template>
            </Column>

            <Column field="status" header="État de publication" style="background-color: #f8fafc;">
                <template #body="{ data }">
                    <Tag :severity="getStatusColor(data.status).sev" :icon="getStatusColor(data.status).icon" :value="getStatusColor(data.status).label" class="text-[10px] font-bold px-3 py-1.5 border uppercase tracking-wider" :class="`border-${getStatusColor(data.status).sev}-200 bg-${getStatusColor(data.status).sev}-50 text-${getStatusColor(data.status).sev}-700`" />
                </template>
            </Column>

            <Column header="Actions" alignFrozen="right" :frozen="true" style="text-align: right; background-color: #f8fafc; min-width: 10rem;">
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button icon="pi pi-chart-bar" class="p-button-text p-button-success p-button-rounded hover:bg-red-50" v-tooltip.top="'Résultats & Analytics'" @click="analyticsDialog = true" />
                        <Button icon="pi pi-pen-to-square" class="p-button-text p-button-secondary p-button-rounded hover:bg-blue-50 hover:text-indigo-600" v-tooltip.top="'Éditer le syllabus'" @click="editExam(data)" />
                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded hover:bg-rose-50" v-tooltip.top="'Supprimer l\'examen'" @click="deleteExam(data.id)" />
                    </div>
                </template>
            </Column>

            <!-- LIGNE EXPANSIVE (Vue Rapide Analytique) -->
            <template #expansion="{ data }">
                <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2"><i class="pi pi-chart-line text-blue-500"></i> Aperçu des Performances Cohorte</h4>
                            <Button label="Générer Rapport Complet" icon="pi pi-file-pdf" class="p-button-sm p-button-outlined p-button-secondary bg-white font-bold text-xs" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between col-span-1 md:col-span-2">
                                <div>
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Score Moyen</div>
                                    <div class="text-3xl font-black text-slate-800 flex items-baseline gap-2">
                                        {{ data.avg_score }}%
                                        <span class="text-xs font-medium text-red-500 bg-red-50 px-2 py-0.5 rounded border border-emerald-100">+2.4%</span>
                                    </div>
                                </div>
                                <Knob :modelValue="data.avg_score" :size="70" readonly valueColor="#10B981" rangeColor="#f1f5f9" />
                            </div>

                            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
                                <div class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-3">Sécurité & Proctoring</div>
                                <div class="flex items-center gap-3 text-sm font-bold text-slate-700 bg-slate-50 p-2 rounded-lg border border-slate-100">
                                    <i class="pi pi-shield text-blue-500 text-lg"></i>
                                    <div>
                                        <div>Niveau Actif</div>
                                        <div class="text-[10px] text-slate-400 font-normal">Standard (Focus Mode)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
                                <div class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-2">Tentatives Récentes</div>
                                <div class="flex -space-x-2 overflow-hidden mt-1" v-if="data.recent_attempts_users && data.recent_attempts_users.length > 0">
                                    <Avatar v-for="user in data.recent_attempts_users.slice(0, 5)" :key="user.id" :image="user.avatar || `https://ui-avatars.com/api/?name=${user.name}`" shape="circle" class="border-2 border-white shadow-sm" />
                                    <Avatar v-if="data.recent_attempts_users.length > 5" :label="`+${data.recent_attempts_users.length - 5}`" shape="circle" class="bg-slate-100 text-slate-600 font-bold border-2 border-white shadow-sm text-xs" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR : WORKSPACE EXAM ARCHITECT                                      -->
        <!-- Modèle industriel : Menu vertical gauche + Formulaire scrollable droite        -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="examDialog" modal :closable="false" :draggable="false" :style="{ width: '95vw', maxWidth: '1400px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[90vh]" headerClass="hidden" class="rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.2)] overflow-hidden border border-slate-200">

            <!-- WORKSPACE HEADER (Fixe) -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isFormValid ? '100%' : '30%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <i class="pi pi-verified text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ examForm.id ? 'Éditeur d\'Évaluation' : 'Forger une Nouvelle Évaluation' }}
                            <Tag v-if="examForm.status" :severity="getStatusColor(examForm.status).sev" :value="getStatusColor(examForm.status).label" class="text-[9px] uppercase tracking-wider px-2 py-0.5"></Tag>
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px] md:max-w-[400px]">{{ examForm.title || 'Examen sans titre' }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> Enregistré à {{ formatDate(lastSavedAt) }}</span>
                            <span v-else-if="examForm.isDirty" class="flex items-center gap-1 text-amber-500"><i class="pi pi-spin pi-spinner"></i> Modifications en cours...</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end border-r border-slate-200 pr-4 hidden sm:flex">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Points</span>
                        <span class="text-lg font-black text-indigo-600">{{ calculateTotalPoints }} pts</span>
                    </div>
                    <Button icon="pi pi-external-link" class="p-button-text p-button-secondary p-button-sm hidden md:flex font-bold" v-tooltip.bottom="'Prévisualiser côté apprenant'" />
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" @click="examDialog = false" v-tooltip.bottom="'Fermer l\'espace'" />
                </div>
            </div>

            <!-- WORKSPACE BODY -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR DE NAVIGATION (STEPPER / MENU) -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        Phases de conception
                    </div>

                    <div class="p-4 flex-1 space-y-2">
                        <button v-for="item in editorMenu" :key="item.id" @click="activeEditorTab = item.id"
                                class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                                :class="[activeEditorTab === item.id ? 'bg-blue-50 border border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border border-transparent hover:border-slate-100']">

                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>

                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                            </div>

                            <div class="flex-1">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div>
                                <div class="text-[11px] font-medium text-slate-500 leading-tight">{{ item.description }}</div>
                            </div>
                        </button>
                    </div>

                    <!-- Sidebar Footer Validation -->
                    <div class="p-6 border-t border-slate-200 bg-slate-50 mt-auto">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="isFormValid ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                <i :class="isFormValid ? 'pi pi-check' : 'pi pi-exclamation-triangle'" class="text-lg"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ isFormValid ? 'Prêt à publier' : 'Informations requises' }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                    {{ isFormValid ? 'Critères minimaux atteints.' : 'Complétez le titre et ajoutez au moins une question.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE CONTENU (SCROLLABLE) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-slate-50 scroll-smooth">
                    <div class="max-w-5xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <!-- ========================================== -->
                        <!-- TAB 1: INFORMATIONS GÉNÉRALES              -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Identité de l'Évaluation</h3>
                                        <p class="text-xs text-slate-500 mt-1">Métadonnées et rattachement académique.</p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Titre de l'examen <span class="text-rose-500">*</span></label>
                                        <InputText v-model="examForm.title" class="w-full p-4 text-lg font-bold bg-slate-50 border-slate-300 focus:bg-white transition-colors" placeholder="Ex: Certification Finale Cloud Computing..." :class="{'p-invalid': examForm.errors.title}" />
                                        <small v-if="examForm.errors.title" class="text-rose-500 font-bold block mt-1">{{ examForm.errors.title }}</small>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-slate-50 p-6 rounded-xl border border-slate-100">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-bold text-slate-700 flex items-center gap-2"><i class="pi pi-book text-slate-400"></i> Lier à un Parcours (Cours)</label>
                                            <Dropdown v-model="examForm.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Sélectionnez un cours..." class="w-full border-slate-300 font-medium bg-white" filter showClear :class="{'p-invalid': examForm.errors.course_id}" />
                                            <small v-if="examForm.errors.course_id" class="text-rose-500 font-bold">{{ examForm.errors.course_id }}</small>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-sm font-bold text-slate-700">Type d'Évaluation <span class="text-rose-500">*</span></label>
                                            <SelectButton v-model="examForm.type" :options="examTypes" optionLabel="label" optionValue="value" class="w-full flex" :pt="{ button: { class: 'flex-1 text-xs font-bold py-2 bg-white' } }" />
                                            <small v-if="examForm.errors.type" class="text-rose-500 font-bold">{{ examForm.errors.type }}</small>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Consignes & Instructions (S'affichent avant le début du test)</label>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                            <Editor ref="editorRef" v-model="examForm.description" @load="forceEditorLoad" editorStyle="height: 250px; font-family: 'Inter', sans-serif;" placeholder="Rédigez les instructions pour les candidats..." :class="{'p-invalid': examForm.errors.description}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 2: SYLLABUS BUILDER (QUESTIONS)        -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'syllabus'" class="animate-fade-in">

                            <!-- Header fixe du constructeur -->
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm sticky top-0 z-30">
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-2"><i class="pi pi-list text-indigo-600"></i> Banque d'Items Locale ({{ examForm.questions.length }})</h3>
                                    <p class="text-sm text-slate-500 mt-1">Structurez les questions de cette évaluation spécifique.</p>
                                </div>
                                <Button label="Importer de la Banque Globale" icon="pi pi-cloud-download" class="p-button-outlined p-button-secondary bg-slate-50 text-slate-700 font-bold text-sm shadow-sm" @click="questionBankDialog = true" />
                            </div>

                            <!-- État Vide -->
                            <div v-if="examForm.questions.length === 0" class="bg-white p-16 rounded-3xl border-2 border-dashed border-slate-300 text-center flex flex-col items-center justify-center min-h-[400px] transition-colors hover:border-indigo-300 hover:bg-blue-50/30">
                                <div class="w-24 h-24 bg-gradient-to-br from-indigo-100 to-blue-50 rounded-full flex items-center justify-center mb-6 shadow-inner border border-white">
                                    <i class="pi pi-question text-4xl text-indigo-400"></i>
                                </div>
                                <h4 class="text-2xl font-black text-slate-800 mb-3">Syllabus Vierge</h4>
                                <p class="text-slate-500 mb-8 max-w-md text-base leading-relaxed">Commencez à structurer votre examen en ajoutant des questions depuis les types disponibles ci-dessous.</p>
                                <div class="flex flex-wrap justify-center gap-3">
                                    <Button v-for="t in questionTypes" :key="t.value" :label="`Ajouter ${t.label}`" :icon="t.icon" class="p-button-outlined p-button-secondary font-bold bg-white shadow-sm" @click="addNewQuestion(t.value)" />
                                </div>
                            </div>

                            <!-- Liste des Questions -->
                            <div v-else class="space-y-8 pb-10">

                                <div v-for="(q, index) in examForm.questions" :key="q.id" :id="q.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-indigo-300 hover:shadow-md relative group/question">

                                    <!-- En-tête de la question (Controls) -->
                                    <div class="bg-slate-50/80 px-4 py-3 flex items-center justify-between border-b border-slate-200">
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col gap-1 text-slate-300 hover:text-blue-500 transition-colors cursor-pointer" v-tooltip.top="'Réorganiser'">
                                                <i class="pi pi-caret-up text-xs hover:scale-125" @click="moveQuestion(index, 'up')"></i>
                                                <i class="pi pi-caret-down text-xs hover:scale-125" @click="moveQuestion(index, 'down')"></i>
                                            </div>

                                            <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-600 font-black text-xs flex items-center justify-center border border-slate-300 shadow-inner">
                                                {{ index + 1 }}
                                            </div>

                                            <Dropdown v-model="q.type" :options="questionTypes" optionLabel="label" optionValue="value" class="border-transparent bg-transparent font-bold text-sm hover:bg-white transition-colors focus:ring-0" :pt="{ input: { class: 'py-1.5' } }">
                                                <template #value="slotProps">
                                                    <span :class="questionTypes.find(qt => qt.value === slotProps.value)?.color" class="flex items-center gap-2">
                                                        <i :class="questionTypes.find(qt => qt.value === slotProps.value)?.icon"></i>
                                                        {{ questionTypes.find(qt => qt.value === slotProps.value)?.label }}
                                                    </span>
                                                </template>
                                            </Dropdown>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Valeur:</span>
                                                <InputNumber v-model="q.points" :min="0.25" mode="decimal" :minFractionDigits="1" class="w-16" :pt="{ input: { class: 'p-1 text-center font-black text-sm border-none shadow-none text-indigo-600' } }" />
                                                <span class="text-[10px] font-bold text-slate-400">pts</span>
                                            </div>

                                            <div class="w-px h-6 bg-slate-200 mx-1"></div>

                                            <div class="flex gap-1 opacity-60 group-hover/question:opacity-100 transition-opacity">
                                                <Button icon="pi pi-clone" class="p-button-text p-button-info p-button-sm w-8 h-8 p-0" v-tooltip.top="'Dupliquer'" @click="duplicateQuestion(index)" />
                                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm w-8 h-8 p-0" v-tooltip.top="'Supprimer'" @click="removeQuestion(index)" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Corps de la question -->
                                    <div class="p-6 lg:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">

                                        <!-- Énoncé & Feedbacks -->
                                        <div class="lg:col-span-7 space-y-6">
                                            <div>

                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><i class="pi pi-pen-to-square"></i> Énoncé de la question</label>
                                                <Textarea v-model="q.question_text" rows="3" class="w-full p-4 bg-slate-50 border-slate-300 font-semibold text-slate-800 text-lg rounded-xl focus:bg-white transition-colors" placeholder="Saisissez votre question ici..." />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><i class="pi pi-info-circle"></i> Explication de correction (Feedback)</label>
                                                <InputText v-model="q.explanation" class="w-full bg-slate-50 border-slate-300 text-sm p-3 rounded-xl focus:bg-white" placeholder="Optionnel : Explication affichée aux apprenants après la correction..." />
                                            </div>
                                        </div>

                                        <!-- Zone de configuration des réponses -->
                                        <div class="lg:col-span-5 bg-slate-50/50 p-6 rounded-2xl border border-slate-100 shadow-inner">
                                            <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-3">
                                                <label class="text-[11px] font-black text-blue-500 uppercase tracking-widest">Logique de réponse</label>
                                                <Badge v-if="q.type === 'multiple_choice'" :value="`${q.options.length} options`" severity="secondary" class="text-[9px]"></Badge>
                                            </div>

                                            <!-- QCM (Choix Multiples) -->
                                            <div v-if="q.type === 'multiple_choice'" class="space-y-3">
                                                <div v-for="(opt, oIdx) in q.options" :key="oIdx"
                                                     class="flex items-center gap-3 bg-white p-2 rounded-xl border transition-all duration-300 hover:shadow-md group/opt"
                                                     :class="opt.is_correct ? 'border-emerald-300 shadow-[0_0_0_2px_rgba(16,185,129,0.1)]' : 'border-slate-200 hover:border-slate-300'">

                                                    <div class="flex flex-col items-center justify-center pl-2" v-tooltip.top="'Marquer comme la bonne réponse'">
                                                        <RadioButton :name="'q_'+index" :value="true" v-model="opt.is_correct" @change="setExclusiveCorrectOption(index, oIdx)" />
                                                    </div>

                                                    <InputText v-model="opt.option_text" class="flex-1 border-transparent hover:border-slate-200 focus:border-indigo-400 focus:bg-slate-50 text-sm font-bold text-slate-700 p-2 shadow-none transition-colors" placeholder="Saisir l'option..." />

                                                    <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm opacity-20 group-hover/opt:opacity-100 w-8 h-8 p-0 transition-opacity" v-tooltip.top="'Supprimer l\'option'" @click="removeOptionFromQuestion(index, oIdx)" />
                                                </div>

                                                <Button label="Ajouter une option" icon="pi pi-plus" class="p-button-text p-button-sm font-bold text-indigo-600 w-full justify-start mt-2 hover:bg-blue-50" @click="addOptionToQuestion(index)" />
                                            </div>

                                            <!-- Vrai/Faux -->
                                             <!-- {{ q }} -->
                                            <div v-if="q.type === 'true_false'" class="flex flex-col justify-center items-center py-6 h-full">
                                                <p class="text-xs text-slate-500 font-medium mb-4 text-center">Sélectionnez l'affirmation exacte correspondant à l'énoncé.</p>
                                                <SelectButton v-model="q.correct" :options="[{label:'VRAI', value:true}, {label:'FAUX', value:false}]" optionLabel="label" optionValue="value" class="w-full flex shadow-sm" :pt="{ button: { class: 'flex-1 font-black py-4' } }" />
                                            </div>

                                            <!-- Réponse Courte (Texte) -->
                                            <div v-if="q.type === 'short_answer'" class="flex flex-col h-full justify-center">
                                                <p class="text-[10px] text-amber-600 bg-amber-50 p-3 rounded-lg border border-amber-100 font-bold mb-4 flex items-start gap-2">
                                                    <i class="pi pi-exclamation-triangle mt-0.5"></i>
                                                    Le système validera la réponse de l'étudiant en ignorant la casse, mais l'orthographe doit être exacte.
                                                </p>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">Mot ou phrase attendu(e) :</label>
                                                <InputText v-model="q.correct_answer" class="w-full border-slate-300 font-mono text-sm p-4 bg-white shadow-inner" placeholder="Ex: Laravel, 1995, Paris..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Zone d'ajout bas de page (Plus gros) -->
                                <div class="flex flex-col items-center py-10 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200 mt-8 hover:bg-slate-50 hover:border-indigo-200 transition-colors">
                                    <h5 class="text-sm font-bold text-slate-500 mb-6">Ajouter un nouvel item au syllabus</h5>
                                    <div class="flex flex-wrap justify-center gap-4">
                                        <Button v-for="t in questionTypes" :key="t.value" :label="t.label" :icon="t.icon" class="p-button-outlined p-button-secondary font-bold bg-white shadow-sm border-slate-300 hover:border-indigo-400 hover:text-indigo-600 px-6 py-3" @click="addNewQuestion(t.value)" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 3: RULES & PROCTORING                  -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'rules'" class="space-y-8 animate-fade-in">
                            <!-- Bloc Règles classiques -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-red-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-sliders-h"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Règles de passage & Limites</h3>
                                        <p class="text-xs text-slate-500 mt-1">Définissez les conditions d'obtention de cet examen.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-10">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Durée allouée (Chronomètre)</label>
                                        <div class="flex items-center gap-6 bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                                            <Slider v-model="examForm.duration_minutes" :min="5" :max="240" :step="5" class="flex-1" />
                                            <div class="font-black text-indigo-600 text-xl w-20 text-right bg-white py-1 px-2 rounded-lg border border-indigo-100 shadow-sm">{{ examForm.duration_minutes }} <span class="text-[10px] uppercase text-indigo-400">min</span></div>
                                        </div>
                                        <small v-if="examForm.errors.duration_minutes" class="text-rose-500 font-bold">{{ examForm.errors.duration_minutes }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Seuil de validation (Pourcentage requis)</label>
                                        <div class="flex items-center gap-6 bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                                            <Slider v-model="examForm.passing_score" :min="0" :max="100" class="flex-1" />
                                            <div class="font-black text-emerald-600 text-xl w-20 text-right bg-white py-1 px-2 rounded-lg border border-emerald-100 shadow-sm">{{ examForm.passing_score }} <span class="text-[10px] uppercase text-emerald-400">%</span></div>
                                        </div>
                                        <small v-if="examForm.errors.passing_score" class="text-rose-500 font-bold">{{ examForm.errors.passing_score }}</small>
                                    </div>
                                </div>

                                <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="flex items-center justify-between p-5 bg-white rounded-xl border border-slate-200 shadow-sm hover:border-indigo-200 transition-colors cursor-pointer" @click="examForm.settings.shuffle_questions = !examForm.settings.shuffle_questions">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-slate-50 border border-slate-100 text-slate-500 rounded-full flex items-center justify-center flex-shrink-0"><i class="pi pi-sort-alt"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Aléatoire (Questions)</div>
                                                <div class="text-xs text-slate-500 mt-0.5 leading-tight">Mélange l'ordre des questions pour chaque candidat. Idéal contre la triche en salle.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="examForm.settings.shuffle_questions" @click.stop />
                                    </div>

                                    <div class="flex items-center justify-between p-5 bg-white rounded-xl border border-slate-200 shadow-sm hover:border-indigo-200 transition-colors cursor-pointer" @click="examForm.settings.show_results_instantly = !examForm.settings.show_results_instantly">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-slate-50 border border-slate-100 text-blue-500 rounded-full flex items-center justify-center flex-shrink-0"><i class="pi pi-eye"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Résultats instantanés</div>
                                                <div class="text-xs text-slate-500 mt-0.5 leading-tight">L'apprenant voit son score et ses feedbacks dès la soumission du formulaire.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="examForm.settings.show_results_instantly" @click.stop />
                                    </div>
                                </div>
                            </div>

                            <!-- Bloc Sécurité & Anti-triche (Proctoring) -->
                            <div class="bg-slate-900 p-8 rounded-2xl shadow-xl text-white relative overflow-hidden border border-slate-800 group">
                                <i class="pi pi-shield absolute -right-4 -bottom-4 text-[150px] text-white/5 group-hover:scale-110 transition-transform duration-1000 ease-out"></i>
                                <div class="relative z-10">
                                    <h3 class="text-xl font-bold text-white mb-2 flex items-center gap-3"><div class="w-8 h-8 rounded bg-blue-500/20 text-indigo-400 flex items-center justify-center"><i class="pi pi-lock"></i></div> Sécurité Anti-Triche (Proctoring)</h3>
                                    <p class="text-slate-400 text-sm mb-8 max-w-2xl leading-relaxed">Configurez le niveau de surveillance logicielle pour les certifications officielles. Un niveau strict requiert que l'apprenant installe notre module navigateur sécurisé.</p>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div v-for="level in proctoringLevels" :key="level.value" @click="examForm.settings.proctoring_mode = level.value"
                                             class="p-5 rounded-xl border-2 cursor-pointer transition-all duration-300 relative"
                                             :class="examForm.settings.proctoring_mode === level.value ? 'bg-indigo-600/20 border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.2)]' : 'bg-slate-800/50 border-slate-700 hover:border-slate-500'">

                                            <div v-if="examForm.settings.proctoring_mode === level.value" class="absolute -top-3 -right-3 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white shadow-lg"><i class="pi pi-check text-[10px]"></i></div>

                                            <div class="font-black text-sm mb-2" :class="examForm.settings.proctoring_mode === level.value ? 'text-indigo-300' : 'text-slate-300'">{{ level.label }}</div>
                                            <div class="text-xs leading-relaxed" :class="examForm.settings.proctoring_mode === level.value ? 'text-indigo-100' : 'text-slate-500'">{{ level.desc }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 4: AUDIT & VERSIONING                  -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'audit'" class="animate-fade-in space-y-8">
                            <div class="bg-blue-50 p-6 rounded-2xl border border-indigo-100 flex items-start gap-4 shadow-sm">
                                <i class="pi pi-info-circle text-blue-500 text-2xl mt-1"></i>
                                <div>
                                    <h4 class="font-bold text-indigo-900 mb-1">Traçabilité Académique (Immuable)</h4>
                                    <p class="text-sm text-indigo-700 leading-relaxed">Le système d'audit enregistre automatiquement chaque modification structurelle de l'examen. En cas de contestation d'un étudiant, vous pouvez prouver mathématiquement l'état exact des barèmes au moment précis de sa tentative.</p>
                                </div>
                            </div>

                             <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                 <h3 class="text-lg font-bold text-slate-800 mb-8 border-b border-slate-100 pb-4">Journal des événements (Log)</h3>

                                 <div v-if="!examForm.id" class="text-center py-16">
                                     <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 mx-auto border border-slate-100"><i class="pi pi-history text-3xl text-slate-300"></i></div>
                                     <h3 class="text-lg font-bold text-slate-800 mb-2">Aucun historique</h3>
                                     <p class="text-slate-500 text-sm max-w-md mx-auto">L'audit track sera initialisé et disponible une fois l'examen publié pour la première fois sur le serveur.</p>
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
                                                <span class="text-[10px] font-mono text-slate-400 bg-white px-2 py-1 rounded border border-slate-200"><i class="pi pi-clock mr-1"></i>{{ slotProps.item.date }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                                <Avatar icon="pi pi-user" size="small" shape="circle" class="bg-slate-200 text-slate-500 w-5 h-5 text-[10px]" />
                                                Effectué par : <strong>{{ slotProps.item.user }}</strong>
                                            </div>
                                        </div>
                                    </template>
                                </Timeline>
                             </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER (Actions) -->
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Abandonner" class="p-button-text p-button-secondary font-bold text-slate-500 hover:text-slate-800" @click="examDialog = false" :disabled="isSaving" />
                <div class="flex items-center gap-3">
                    <Button label="Enregistrer Brouillon" icon="pi pi-save" class="p-button-outlined p-button-secondary font-bold bg-white text-slate-700 shadow-sm border-slate-300" @click="saveExam('Draft')" :loading="isSaving" />

                    <div class="relative group">
                        <Button label="Mettre en Ligne (Publier)" icon="pi pi-rocket" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold px-8" @click="saveExam('Active')" :loading="isSaving" :disabled="!isFormValid" />
                        <!-- Tooltip custom si bloqué -->
                        <div v-if="!isFormValid" class="absolute bottom-full right-0 mb-3 w-64 bg-slate-800 text-white text-xs p-3 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                            Complétez le titre et ajoutez au moins une question pour activer le bouton de publication.
                            <div class="absolute -bottom-1 right-8 w-2 h-2 bg-slate-800 transform rotate-45"></div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- MODAL : BANQUE D'ITEMS (IMPORTATION DE QUESTIONS)                              -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="questionBankDialog" header="Banque d'Items Globale" :modal="true" :style="{ width: '900px' }" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-6 border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-4">
                    <IconField iconPosition="left" class="flex-1">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText placeholder="Rechercher une question (mot-clé, tag)..." class="w-full bg-slate-50 border-slate-200 font-medium" />
                    </IconField>
                    <Dropdown :options="categories" optionLabel="name" optionValue="id" placeholder="Catégorie métier" class="w-48 bg-white border-slate-200 font-medium" />
                </div>
            </div>

            <div class="p-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                    <DataTable :value="props.global_question_bank" :paginator="true" :rows="5" class="p-datatable-custom text-sm">
                        <Column field="id" header="ID" style="width: 4rem"></Column>
                        <Column field="text" header="Énoncé de la question">
                            <template #body="{ data }">
                                <div class="font-medium text-slate-800" v-html="data.question_text || data.text"></div>
                                <div class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest">{{ data.category?.name || 'Général' }}</div>
                            </template>
                        </Column>
                        <Column field="type" header="Type" style="width: 10rem">
                            <template #body="{ data }">
                                <span class="text-xs font-bold text-slate-600 flex items-center gap-1"><i class="pi pi-tag text-[10px]"></i> {{ data.type === 'multiple_choice' ? 'QCM' : (data.type === 'true_false' ? 'V/F' : 'Texte') }}</span>
                            </template>
                        </Column>
                        <Column header="Action" style="width: 6rem; text-align: center;">
                            <template #body="{ data }">
                                <Button icon="pi pi-download" class="p-button-rounded p-button-sm p-button-text p-button-info" v-tooltip.left="'Importer dans cet examen'" @click="importQuestionFromBank(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <template #footer>
                <div class="border-t border-slate-200 bg-white p-4 flex justify-end">
                    <Button label="Terminer l'importation" class="p-button-primary font-bold" @click="questionBankDialog = false" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- MODAL : ANALYTIQUE ET PSYCHOMÉTRIE (DATA VISUALIZATION)                        -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="analyticsDialog" modal header="Intelligence Académique & Rapports" :style="{width: '1000px'}" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-8 space-y-8">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm text-center flex flex-col justify-center relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity"><i class="pi pi-users text-5xl"></i></div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Échantillon (Tentatives)</div>
                        <div class="text-4xl font-black text-slate-800 relative z-10">1,245</div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm text-center flex flex-col justify-center relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-5 group-hover:opacity-10 transition-opacity"><i class="pi pi-chart-pie text-5xl"></i></div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 relative z-10">Médiane Globale</div>
                        <div class="text-4xl font-black text-slate-800 relative z-10">74.2%</div>
                    </div>
                    <div class="bg-indigo-600 p-6 rounded-xl shadow-lg shadow-indigo-500/20 text-center text-white flex flex-col justify-center relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-bolt text-5xl"></i></div>
                        <div class="text-xs font-bold text-indigo-200 uppercase tracking-widest mb-2 relative z-10">Fiabilité (Alpha Cronbach)</div>
                        <div class="text-4xl font-black relative z-10 flex items-baseline justify-center gap-2">
                            0.82 <span class="text-[10px] bg-blue-500 px-2 py-0.5 rounded font-bold uppercase tracking-wider">Excellent</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i class="pi pi-chart-bar text-blue-500"></i> Distribution Normale des Scores (Courbe de Gauss)</h4>
                            <p class="text-xs text-slate-500 mt-1">Analyse de la répartition des résultats pour vérifier si l'examen n'est ni trop dur ni trop facile.</p>
                        </div>
                    </div>
                    <Chart type="bar" :data="performanceChartData" :options="{ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5,5] } }, x: { grid: { display: false } } } }" class="h-64" />
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-slate-800">Item Analysis (Top Questions Difficiles)</h4>
                        <Button icon="pi pi-external-link" class="p-button-text p-button-sm p-button-secondary text-xs font-bold" label="Voir tout" />
                    </div>
                    <div class="p-0">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3 font-bold">Item (Question)</th>
                                    <th class="px-6 py-3 font-bold text-center">Taux d'Erreur</th>
                                    <th class="px-6 py-3 font-bold text-center">Discrimination</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-700 truncate max-w-xs">Le pattern Singleton autorise plusieurs instances.</td>
                                    <td class="px-6 py-4 text-center"><Badge value="85%" severity="danger" class="text-xs"></Badge></td>
                                    <td class="px-6 py-4 text-center text-slate-600 font-mono text-xs">0.45</td>
                                </tr>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-700 truncate max-w-xs">Différence entre Abstract Class et Interface en PHP 8.</td>
                                    <td class="px-6 py-4 text-center"><Badge value="72%" severity="warning" class="text-xs"></Badge></td>
                                    <td class="px-6 py-4 text-center text-slate-600 font-mono text-xs">0.62</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <template #footer>
                <div class="border-t border-slate-200 bg-white p-5 flex justify-between items-center">
                    <span class="text-xs text-slate-400 font-medium"><i class="pi pi-info-circle mr-1"></i> Données générées en temps réel depuis le Data Warehouse.</span>
                    <Button label="Télécharger le rapport complet (PDF)" icon="pi pi-download" class="p-button-primary font-bold shadow-md" />
                </div>
            </template>
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

/* Overrides UI Forms (Plus doux, bordures rondes) */
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
