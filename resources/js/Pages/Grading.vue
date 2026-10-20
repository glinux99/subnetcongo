<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ADVANCED ACADEMIC GRADING & FEEDBACK CONSOLE v12.0.1
 * ======================================================================================
 * Console experte pour l'évaluation manuelle des copies, projets et certifications.
 *
 * @author  SubnetCongo Framework Division
 * @module  Evaluation/Grading
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
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
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import InputSwitch from 'primevue/inputswitch';
import Divider from 'primevue/divider';
import Knob from 'primevue/knob';
import ScrollPanel from 'primevue/scrollpanel';
import Breadcrumb from 'primevue/breadcrumb';
import Editor from 'primevue/editor';
import Slider from 'primevue/slider';
import Chart from 'primevue/chart';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import OverlayPanel from 'primevue/overlaypanel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Message from 'primevue/message';
import ConfirmDialog from 'primevue/confirmdialog';
import Tooltip from 'primevue/tooltip';
import Skeleton from 'primevue/skeleton';
import SplitButton from 'primevue/splitbutton';

// ======================================================================================
// PROPS & INITIALISATION
// ======================================================================================
const props = defineProps({
    submissions: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({ pending: 0, average: 0, appeals: 0 }) }
});

const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const searchLoading = ref(false);

// Toggles d'interface
const gradingDialog = ref(false);
const statisticsSidebar = ref(false);
const plagiarismSidebar = ref(false);
const peerReviewSidebar = ref(false);
const aiInsightsSidebar = ref(false);
const historySidebar = ref(false);
const annotationPanel = ref();
const aiLoading = ref(false);
const isFullScreen = ref(false);

// États globaux
const selectedSubmissions = ref([]);
const activeSubmission = ref(null);
const expandedRows = ref([]);

// Simulation Enregistrement Audio
const recordTime = ref(0);
const isRecording = ref(false);
let recordInterval;

// ======================================================================================
// FORMULAIRE INERTIA (USEFORM) POUR LA CORRECTION
// ======================================================================================
const gradingForm = useForm({
    id: null,
    status: 'draft', // 'draft', 'graded', 'revision_requested'
    total_points: 0,
    feedback: '',
    internal_notes: '',
    rubric_data: [], // Format: {id, points_assigned}
    rubric_scores: {}, // <-- NOUVEAU : objet pour stocker les scores par clé de rubrique
    question_scores: {},
    general_feedback_mode: 'rich_text',
    ai_review_requested: false,
    publish: true,
    annotations: [],
    plagiarism_verified: false,
    feedback_attachments: [],
    send_notification: true,
});

/**
 * Définitions des niveaux de rubriques pour le feedback dynamique.
 */
const rubricLevelDefinitions = {
    clarity: [
        { val: 0, label: 'Insuffisant', desc: 'Code illisible ou non structuré.' },
        { val: 2.5, label: 'Acceptable', desc: 'Structure basique, lisibilité moyenne.' },
        { val: 5, label: 'Excellent', desc: 'Respect parfait des conventions de nommage.' }
    ],
    technical_accuracy: [
        { val: 0, label: 'Incorrect', desc: 'Erreurs de logique majeures.' },
        { val: 3, label: 'Partiel', desc: 'Fonctionne mais présente des failles.' },
        { val: 5, label: 'Optimal', desc: 'Solution technique irréprochable.' }
    ]
};

/**
 * Retourne la description textuelle du niveau de rubrique sélectionné.
 * @param {string} key
 * @param {number} value
 */
const getRubricDescription = (key, value) => {
    const levels = rubricLevelDefinitions[key] || [];
    const match = [...levels].reverse().find(l => value >= l.val);
    return match ? match.desc : 'Sélectionnez un score...';
};

const currentPlagiarismDetails = ref([
    { source: 'github.com/laravel/framework', match: 14, url: '#', color: 'bg-rose-500' },
    { source: 'stackoverflow.com/q/45874', match: 8, url: '#', color: 'bg-amber-500' }
]);

// ======================================================================================
// FILTRAGE RÉACTIF
// ======================================================================================
const serverFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || null,
    course_id: props.filters.course_id || null,
});

watch(serverFilters, debounce(() => {
    searchLoading.value = true;
    router.get(route('gradings.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => searchLoading.value = false
    });
}, 400));

// ======================================================================================
// CALCULS DYNAMIQUES DU WORKSPACE
// ======================================================================================

/**
 * Calcule dynamiquement la note sur 100 en fonction des sliders de la grille (Rubric)
 * et/ou des points attribués manuellement à chaque question.
 */
const calculatedGrade = computed(() => {
    if (!activeSubmission.value) return 0;

    let total = 0;

    // Si l'évaluation utilise une grille (Rubrics)
    if (activeSubmission.value.rubric && activeSubmission.value.rubric.length > 0) {
        let rubricScore = 0;
        activeSubmission.value.rubric.forEach(r => {
            const score = gradingForm.rubric_scores?.[r.key] ?? 0; // <-- sécurisé
            rubricScore += (score / r.max) * (100 * r.weight);
        });
        total += rubricScore;
    }

    // Ajout des points par questions manuelles (si applicable)
    let questionScore = 0;
    let maxQuestionPoints = 0;
    if (activeSubmission.value.answers) {
        activeSubmission.value.answers.forEach(a => {
            questionScore += gradingForm.question_scores?.[a.id] || 0;
            maxQuestionPoints += (a.question?.max_points || a.max_points || 0);
        });
        // Si on utilise uniquement les questions
        if(!activeSubmission.value.rubric || activeSubmission.value.rubric.length === 0) {
            total = maxQuestionPoints > 0 ? (questionScore / maxQuestionPoints) * 100 : 0;
        }
    }

    return Math.min(100, Math.round(total));
});

// Synchronise la note calculée avec le formulaire
watch(calculatedGrade, (newVal) => {
    gradingForm.total_points = newVal;
});

// ======================================================================================
// ACTIONS DU WORKSPACE (CRUD)
// ======================================================================================

/**
 * Ouvre l'espace de correction pour une soumission spécifique
 */
const openGrading = (submission) => {
    activeSubmission.value = submission;

    // Reset Form
    gradingForm.reset();
    gradingForm.clearErrors();
    gradingForm.id = submission.id;
    gradingForm.feedback = submission.feedback_text || '';
    gradingForm.internal_notes = submission.internal_notes || '';
    gradingForm.status = submission.status;
    gradingForm.rubric_scores = {}; // Réinitialisation

    // Init Rubric Data et rubric_scores
    if(submission.rubric) {
        gradingForm.rubric_data = submission.rubric.map((r, index) => {
            // Ensure ID is an integer for Laravel validation
            const rubricId = typeof r.id === 'number' ? r.id : (index + 1);
            return {
                id: rubricId,
                points_assigned: Math.floor(r.max / 2)
            };
        });
        // Initialiser rubric_scores avec des valeurs par défaut (moitié du max)
        submission.rubric.forEach(r => {
            gradingForm.rubric_scores[r.key] = Math.floor(r.max / 2);
        });
    } else {
        gradingForm.rubric_data = [];
    }

    // Init Question Scores
    gradingForm.question_scores = {};
    if(submission.answers) {
        submission.answers.forEach(a => {
            gradingForm.question_scores[a.id] = a.assigned_points || 0;
        });
    }

    gradingDialog.value = true;
};

/**
 * Sauvegarde la note et le feedback
 * status: 'draft' (Brouillon), 'graded' (Publié), 'revision_requested' (Renvoyé)
 */
const saveGrade = (status = 'graded') => {
    gradingForm.status = status;

    // Validation manuelle avant envoi pour correspondre aux règles backend
    if (!gradingForm.feedback || gradingForm.feedback.length < 5) {
        toast.add({ severity: 'error', summary: 'Validation', detail: 'Le feedback est obligatoire (min 5 caractères).', life: 3000 });
        return;
    }

    let confirmMsg = '';
    let confirmHeader = '';
    if (status === 'graded') {
        confirmMsg = `Vous êtes sur le point de valider définitivement la note de ${gradingForm.total_points}/100. L'apprenant en sera notifié.`;
        confirmHeader = 'Publier la note';
    } else if (status === 'revision_requested') {
        confirmMsg = `L'apprenant devra soumettre une nouvelle version de son travail basée sur vos commentaires.`;
        confirmHeader = 'Demander une révision';
    } else {
        // Simple sauvegarde de brouillon sans confirmation
        processSave(status);
        return;
    }

    confirm.require({
        message: confirmMsg,
        header: confirmHeader,
        icon: 'pi pi-exclamation-triangle',
        acceptClass: status === 'graded' ? 'p-button-primary' : 'p-button-warning',
        acceptLabel: 'Confirmer',
        rejectLabel: 'Annuler',
        accept: () => processSave(status)
    });
};

const processSave = (status) => {
    // URL correspondant à la route PUT manuelle (sans nom)
    const url = `/gradings/${gradingForm.id}`;

    // Préparation des données complexes (Rubriques, Annotations, etc.)
    if (activeSubmission.value.rubric) {
        gradingForm.rubric_data = activeSubmission.value.rubric.map((r, index) => {
            return {
                id: r.id || (index + 1),
                points_assigned: gradingForm.rubric_scores[r.key] || 0
            };
        });
    }

    gradingForm.transform((data) => ({
        ...data,
        _method: 'PUT',
        total_points: data.rubric_data.length > 0 ? null : data.total_points, // required_without:rubric_data
        plagiarism_verified: !!activeSubmission.value.similarity_score,
        annotations: data.annotations || []
    })).post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            const summary = status === 'draft' ? 'Brouillon sauvegardé'
                         : (status === 'graded' ? 'Correction Validée' : 'Révision demandée');
            toast.add({ severity: 'success', summary, detail: 'Le dossier a été mis à jour avec succès.', life: 4000 });
            if (status !== 'draft') gradingDialog.value = false;
        },
        onError: (err) => {
            let detail = 'Vérifiez les données du formulaire.';
            if (err.feedback) detail = err.feedback;

            toast.add({
                severity: 'error',
                summary: 'Erreur de validation',
                detail: detail,
                life: 5000
            });
        }
    });
};

/**
 * Action de masse : Publier plusieurs brouillons d'un coup
 */
const confirmBulkPublish = () => {
    if(selectedSubmissions.value.length === 0) return;

    confirm.require({
        message: `Vous êtes sur le point de publier définitivement les notes de ${selectedSubmissions.value.length} apprenants. Les notifications seront envoyées.`,
        header: 'Publication en masse',
        icon: 'pi pi-send',
        acceptClass: 'p-button-primary',
        acceptLabel: 'Publier',
        accept: () => {
            router.post(route('gradings.bulkPublish'), {
                ids: selectedSubmissions.value.map(s => s.id)
            }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Publication réussie', detail: 'Les notes ont été transmises aux apprenants.', life: 4000 });
                    selectedSubmissions.value = [];
                }
            });
        }
    });
};

/**
 * Basculer le mode plein écran pour le Workspace
 */
const toggleFullScreen = () => {
    isFullScreen.value = !isFullScreen.value;
    if (isFullScreen.value) {
        document.documentElement.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
};

/**
 * Logique avancée pour le marquage de code / texte (Annotations contextuelles)
 */
const selectedText = ref('');
const captureSelection = () => {
    const selection = window.getSelection();
    if (selection.toString().length > 2) {
        selectedText.value = selection.toString();
        // Ici on pourrait ouvrir automatiquement le panel d'annotation
    }
};

// ======================================================================================
// GESTION DU FEEDBACK IA & ANNOTATIONS
// ======================================================================================

const generateAIFeedback = () => {
    aiLoading.value = true;
    toast.add({ severity: 'info', summary: 'IA SubnetCongo', detail: 'Analyse sémantique de la copie en cours...', life: 3000 });

    // Simulation d'un appel API vers un LLM
    setTimeout(() => {
        let suggestedFeedback = `<p>Bonjour ${activeSubmission.value.student.name},</p><p>Votre travail démontre une bonne compréhension globale. Cependant, j'ai noté les points suivants :</p><ul>`;

        // Génère des remarques basées sur la grille
        activeSubmission.value.rubric.forEach(r => {
            const score = gradingForm.rubric_scores[r.key] || 0;
            if(score < r.max) {
                suggestedFeedback += `<li><strong>${r.label} :</strong> L'approche peut être approfondie pour atteindre le niveau maximum.</li>`;
            } else {
                suggestedFeedback += `<li><strong>${r.label} :</strong> Excellent travail sur ce critère.</li>`;
            }
        });
        suggestedFeedback += `</ul><p>Continuez vos efforts !</p>`;

        gradingForm.feedback = suggestedFeedback;
        aiLoading.value = false;
        toast.add({ severity: 'success', summary: 'Génération terminée', detail: 'Le feedback a été pré-rempli. Vous pouvez le modifier.', life: 4000 });
    }, 2000);
};

const handleAnnotation = (event) => {
    annotationPanel.value.toggle(event);
};

const saveAnnotation = () => {
    toast.add({ severity: 'success', summary: 'Annotation ajoutée', detail: 'Le marqueur a été placé sur la copie.', life: 2000 });
    annotationPanel.value.hide();
};

// ======================================================================================
// GESTION AUDIO (FEEDBACK VOCAL)
// ======================================================================================
const startVoiceRecord = () => {
    isRecording.value = true;
    recordTime.value = 0;
    recordInterval = setInterval(() => recordTime.value++, 1000);
};

const stopVoiceRecord = () => {
    if(!isRecording.value) return;
    isRecording.value = false;
    clearInterval(recordInterval);

    if(recordTime.value > 1) {
        toast.add({ severity: 'info', summary: 'Audio Enregistré', detail: `Un feedback vocal de ${formatTime(recordTime.value)} a été joint.`, life: 3000 });
    }
};

const formatTime = (s) => {
    const min = Math.floor(s / 60);
    const sec = s % 60;
    return `${min}:${sec < 10 ? '0' : ''}${sec}`;
};

// ======================================================================================
// STATISTIQUES ET CHARTS DE COHORTE
// ======================================================================================
const scoreDistribution = ref({
    labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
    datasets: [{ label: 'Apprenants', data: [1, 4, 12, 54, 18], backgroundColor: '#6366F1', borderRadius: 6 }]
});

const cohortTrendData = ref({
    labels: ['Module 1', 'Module 2', 'Module 3', 'Final'],
    datasets: [
        { label: 'Moyenne Cohorte', data: [65, 59, 80, 81], borderColor: '#10B981', tension: 0.4 },
        { label: 'Cible Pédagogique', data: [70, 70, 70, 75], borderColor: '#94A3B8', borderDash: [5, 5], tension: 0 }
    ]
});

// ======================================================================================
// UTILITAIRES D'INTERFACE
// ======================================================================================
const getUrgencySeverity = (u) => ({ critical: 'danger', high: 'danger', medium: 'warning', low: 'success' }[u] || 'info');

const getStatusBadge = (s) => {
    const map = {
        pending: { label: 'En attente', sev: 'secondary', icon: 'pi-clock' },
        in_progress: { label: 'Brouillon', sev: 'warning', icon: 'pi-file-edit' },
        revision_requested: { label: 'À réviser (Étudiant)', sev: 'danger', icon: 'pi-replay' },
        graded: { label: 'Corrigé & Publié', sev: 'success', icon: 'pi-check-circle' }
    };
    return map[s] || { label: s, sev: 'info', icon: 'pi-info-circle' };
};

const getSimilaritySeverity = (score) => {
    if (score > 30) return 'danger';
    if (score > 15) return 'warning';
    return 'success';
};

// Lifecycle
onMounted(() => {
    setTimeout(() => loading.value = false, 800);
});
</script>

<template>
    <AppLayout>
        <Head title="Console de Correction | LMS Enterprise" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER ET NAVIGATION GLOBALE                                                   -->
            <!-- ============================================================================== -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Évaluation & Suivi', class: 'font-semibold text-blue-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            Console de Correction
                            <Badge value="Instructor Space" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Gérez la file d'attente des copies, évaluez avec des grilles critériées, détectez les similarités et fournissez un feedback riche aux apprenants.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Vue Cohorte (Analytics)" icon="pi pi-chart-bar" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 shadow-sm font-medium w-full sm:w-auto" @click="statisticsSidebar = true" />
                    <Button label="Audit Plagiat Global" icon="pi pi-shield" class="p-button-outlined p-button-danger bg-white shadow-sm font-medium w-full sm:w-auto" @click="plagiarismSidebar = true" />
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- KPI DASHBOARD                                                                  -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class="pi pi-inbox text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Attente Correction</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats?.pending || 12 }}</h3>
                        <span class="text-xs font-medium text-rose-500 bg-rose-50 px-2 py-1 rounded-md">+2 urgentes</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-chart-line text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Moyenne Globale</p>
                    </div>
                    <h3 class="text-4xl font-black text-slate-800">{{ props.stats?.average || 74.2 }}%</h3>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-red-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-bolt text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">SLA Productivité</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">4.5h</h3>
                        <span class="text-xs font-medium text-emerald-500">Temps moy/copie</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-exclamation-triangle text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Recours / Plagiats</p>
                    </div>
                    <h3 class="text-4xl font-black text-amber-600">{{ props.stats?.appeals || 2 }}</h3>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DATA GRID : FILE D'ATTENTE DES SOUMISSIONS                                     -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-10">

                <!-- Toolbar de filtrage -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher étudiant, matricule..." class="w-full sm:w-80 border-slate-200 font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.status" :options="['pending', 'in_progress', 'graded']" placeholder="État du flux" class="w-48 border-slate-200 font-medium" showClear>
                            <template #option="slotProps">
                                <span class="flex items-center gap-2"><i :class="`pi ${getStatusBadge(slotProps.option).icon}`"></i> {{ getStatusBadge(slotProps.option).label }}</span>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button v-if="selectedSubmissions.length > 0" icon="pi pi-send" :label="`Publier (${selectedSubmissions.length})`" class="p-button-primary shadow-sm" @click="confirmBulkPublish" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser la file'" />
                    </div>
                </div>

                <!-- DataTable Principal -->
                <DataTable
                    v-model:selection="selectedSubmissions"
                    v-model:expandedRows="expandedRows"
                    :value="props.submissions.data"
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
                            <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mb-6 border border-emerald-100">
                                <i class="pi pi-check-circle text-5xl text-emerald-400"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Pile de correction vide</h3>
                            <p class="text-sm text-center text-slate-500">Toutes les copies ont été traitées. Prenez un café !</p>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="student.name" header="Apprenant & Rendu" style="min-width: 22rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <div class="relative">
                                    <Avatar :image="data.student.avatar" shape="circle" size="large" class="shadow-sm border border-slate-200" />
                                    <div v-if="data.status === 'in_progress'" class="absolute -bottom-1 -right-1 w-4 h-4 bg-amber-400 border-2 border-white rounded-full flex items-center justify-center" v-tooltip.top="'Brouillon en cours'"><i class="pi pi-pencil text-[8px] text-white"></i></div>
                                </div>
                                <div class="flex flex-col justify-center">
                                    <div class="font-extrabold text-slate-800 text-base hover:text-blue-600 cursor-pointer flex items-center gap-2" @click="openGrading(data)">
                                        {{ data.student.name }}
                                        <Badge :value="data.student.cohort" severity="secondary" class="text-[9px] bg-slate-100 text-slate-600 border border-slate-200"></Badge>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1 font-medium flex items-center gap-2">
                                        <span class="flex items-center gap-1"><i class="pi pi-calendar text-[10px]"></i> {{ data.submitted_at }}</span>
                                        <Tag v-if="data.similarity_score > 15" :severity="getSimilaritySeverity(data.similarity_score)" class="text-[9px] px-1 py-0"><i class="pi pi-shield text-[8px] mr-1"></i> Plagiat {{data.similarity_score}}%</Tag>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="exam" header="Évaluation & Programme" style="min-width: 18rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-slate-700 text-sm line-clamp-1">{{ data.exam }}</span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-bold flex items-center gap-1"><i class="pi pi-book text-[9px]"></i> {{ data.course }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="urgency" header="Priorité" style="background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <div v-if="data.urgency === 'critical'" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></div>
                                <Tag :value="data.urgency" :severity="getUrgencySeverity(data.urgency)" class="text-[10px] font-bold uppercase tracking-wider px-2 py-1" />
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État Correction" style="background-color: #f8fafc; min-width: 12rem;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5 w-full">
                                <div class="flex justify-between items-center text-[11px] font-bold" :class="`text-${getStatusBadge(data.status).sev}-700`">
                                    <span class="flex items-center gap-1"><i :class="`pi ${getStatusBadge(data.status).icon} text-[10px]`"></i> {{ getStatusBadge(data.status).label }}</span>
                                    <span>{{ data.progress }}%</span>
                                </div>
                                <ProgressBar :value="data.progress" :showValue="false" style="height: 4px" class="bg-slate-100" :pt="{ value: { class: `bg-${getStatusBadge(data.status).sev}-500` } }" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="text-align: right; background-color: #f8fafc; min-width: 8rem;">
                        <template #body="{ data }">
                            <Button label="Ouvrir Workspace" icon="pi pi-external-link" class="p-button-sm p-button-primary shadow-sm font-bold w-full" @click="openGrading(data)" />
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (Aperçu Historique Apprenant) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2"><i class="pi pi-history text-blue-500"></i> Historique académique de l'apprenant</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-1">Moyenne Générale</div>
                                        <div class="text-2xl font-black text-slate-800">82.4%</div>
                                    </div>
                                    <Knob :modelValue="82.4" :size="50" readonly valueColor="#6366F1" rangeColor="#f1f5f9" />
                                </div>
                                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                                    <div class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-2">Précédente évaluation</div>
                                    <div class="text-sm font-bold text-slate-700 truncate">Projet UI/UX Fondations</div>
                                    <div class="text-xs text-emerald-600 mt-1 font-bold">Note : 18/20</div>
                                </div>
                                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
                                    <Button label="Voir le dossier complet" icon="pi pi-user" class="p-button-sm p-button-outlined p-button-secondary font-bold w-full" />
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- ============================================================================== -->
            <!-- AI & ASSISTANT SECTION (FOOTER BANNER)                                         -->
            <!-- ============================================================================== -->
            <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-2xl p-8 lg:p-10 flex flex-col lg:flex-row items-center justify-between gap-8 shadow-xl relative overflow-hidden">
                <i class="pi pi-bolt absolute -right-4 -bottom-4 text-[150px] text-white/5 rotate-12"></i>
                <div class="relative z-10 flex items-start gap-6 lg:w-2/3">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-white backdrop-blur-sm border border-white/20 flex-shrink-0">
                        <i class="pi pi-sparkles text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-white mb-2">Auto-Grading & Assistant IA (SNC-AI)</h3>
                        <p class="text-indigo-100 text-sm leading-relaxed max-w-2xl">
                            Gagnez du temps sur les questions ouvertes. Activez l'analyse sémantique pour générer des suggestions de feedback constructives pré-remplies basées sur votre grille critériée.
                        </p>
                    </div>
                </div>
                <div class="relative z-10 w-full lg:w-auto">
                    <Button label="Configurer l'Assistant IA" icon="pi pi-cog" class="p-button-outlined p-button-secondary bg-white text-indigo-900 border-none font-bold px-8 py-3 w-full shadow-lg" />
                </div>
            </div>

        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG : WORKSPACE DE CORRECTION (SPLIT-PANE LAYOUT STRICT)                    -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="gradingDialog" modal :closable="false" :draggable="false" :style="{ width: '100vw', height: '100vh', maxHeight: '100vh' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50" headerClass="hidden" class="m-0 rounded-none shadow-none">

            <!-- WORKSPACE HEADER (FIXE) -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">

                <div class="flex items-center gap-6">
                    <Button icon="pi pi-arrow-left" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 w-10 h-10" @click="gradingDialog = false" v-tooltip.bottom="'Quitter la correction'" />
                    <div class="h-8 w-px bg-slate-200"></div>

                    <div class="flex items-center gap-4">
                        <Avatar :image="activeSubmission?.student.avatar" shape="circle" class="shadow-sm border border-slate-200" />
                        <div>
                            <h2 class="text-sm font-black text-slate-800 leading-tight flex items-center gap-2">
                                {{ activeSubmission?.student.name }}
                                <Tag :value="getStatusBadge(gradingForm.status).label" :severity="getStatusBadge(gradingForm.status).sev" class="text-[9px] px-2 py-0.5 uppercase tracking-wider border" :class="`border-${getStatusBadge(gradingForm.status).sev}-200 bg-${getStatusBadge(gradingForm.status).sev}-50 text-${getStatusBadge(gradingForm.status).sev}-700`"></Tag>
                            </h2>
                            <div class="text-[11px] font-medium text-slate-500 mt-0.5 truncate max-w-[300px] md:max-w-[500px]">
                                {{ activeSubmission?.exam }} — {{ activeSubmission?.course }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex flex-col items-end border-r border-slate-200 pr-6 hidden md:flex">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Note Calculée</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black" :class="gradingForm.total_points >= 50 ? 'text-emerald-600' : 'text-rose-600'">{{ gradingForm.total_points }}</span>
                            <span class="text-sm font-bold text-slate-400">/ 100</span>
                        </div>
                    </div>
                    <Button v-if="activeSubmission?.similarity_score > 15" :label="`Plagiat (${activeSubmission.similarity_score}%)`" icon="pi pi-shield" class="p-button-danger p-button-outlined p-button-sm font-bold bg-white" @click="plagiarismSidebar = true" />
                </div>
            </div>

            <!-- WORKSPACE BODY (FLEX SPLIT PANE) -->
            <div class="flex-1 flex overflow-hidden">

                <!-- --- PANNEAU GAUCHE : COPIE DE L'ÉTUDIANT (Scrollable) --- -->
                <div class="flex-1 lg:flex-[2] overflow-y-auto custom-scrollbar bg-slate-50/50 p-6 lg:p-10 pb-32">
                    <div class="max-w-4xl mx-auto space-y-8">

                        <!-- Message Alerte Plagiat -->
                        <Message v-if="activeSubmission?.similarity_score > 20" severity="error" icon="pi pi-exclamation-triangle" class="border border-rose-200 shadow-sm rounded-xl">
                            <span class="font-bold">Intégrité Compromise :</span> Un taux de similarité de {{ activeSubmission.similarity_score }}% a été détecté. Veuillez examiner les sources avant validation.
                        </Message>

                        <!-- Boucle des Questions (Rendu Étudiant) -->
                        <div v-for="(q, idx) in activeSubmission?.answers" :key="q.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden group">

                            <!-- Question Header -->
                            <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-200 rounded text-slate-600 font-black text-sm flex items-center justify-center border border-slate-300 shadow-inner">Q{{ idx + 1 }}</div>
                                    <h3 class="font-bold text-slate-800 text-sm">{{ q.question?.title || q.title }}</h3>
                                </div>
                                <div class="flex items-center gap-3 self-end sm:self-auto">
                                    <Tag :value="`Barème: ${q.question?.max_points || q.max_points} pts`" severity="secondary" class="bg-white border border-slate-200 text-slate-600 font-bold" />
                                    <Button icon="pi pi-comment" class="p-button-text p-button-secondary p-button-sm p-button-rounded bg-white border border-slate-200 shadow-sm w-8 h-8" @click="handleAnnotation($event)" v-tooltip.top="'Ajouter une annotation textuelle'" />
                                </div>
                            </div>

                            <!-- Student Answer Content -->
                            <div class="p-6 lg:p-8">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-6">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Énoncé</p>
                                    <p class="text-sm font-medium text-slate-800">{{ q.question?.text || q.text }}</p>
                                </div>

                                <!-- Text Selection Wrapper (Simulation d'annotation) -->
                                <div class="relative group/answer">
                                    <div class="absolute -left-3 top-0 bottom-0 w-1 bg-indigo-200 rounded-full group-hover/answer:bg-indigo-400 transition-colors"></div>

                                    <div class="text-slate-800 leading-relaxed text-[15px] pl-4 font-serif">
                                        <pre v-if="q.student_answer" class="bg-slate-900 text-emerald-400 p-4 rounded-xl text-sm font-mono overflow-x-auto border border-slate-800">{{ q.student_answer }}</pre>
                                        <div v-else class="text-slate-400 italic">Aucune réponse fournie.</div>
                                    </div>
                                </div>

                                <!-- Attribution manuelle des points (Optionnelle si Rubric) -->
                                <div class="mt-8 flex justify-end items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Points accordés :</span>
                                    <InputNumber v-model="gradingForm.question_scores[q.id]" mode="decimal" showButtons :min="0" :max="q.question?.max_points || q.max_points" class="w-32" :pt="{ input: { class: 'text-center font-black text-blue-600' } }" />
                                </div>
                            </div>

                            <!-- Corrigé Type Accordion -->
                            <Accordion>
                                <AccordionTab>
                                    <template #header>
                                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-600"><i class="pi pi-verified"></i> Afficher le Corrigé Type</div>
                                    </template>
                                    <div class="p-5 bg-red-50/50 rounded-xl border border-emerald-100 text-sm text-emerald-800 leading-relaxed font-medium">
                                        {{ q.question?.ideal_answer || q.ideal_answer }}
                                    </div>
                                </AccordionTab>
                            </Accordion>
                        </div>
                    </div>
                </div>

                <!-- --- PANNEAU DROIT : OUTILS DE NOTATION (Fixe en largeur) --- -->
                <div class="w-full lg:w-[450px] flex-none bg-white border-l border-slate-200 flex flex-col z-10 shadow-[-10px_0_20px_rgba(0,0,0,0.02)]">

                    <!-- Scrollable Form Area -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-8 pb-32">

                        <!-- Grille d'évaluation (Rubric Engine) -->
                        <div v-if="activeSubmission?.rubric && activeSubmission.rubric.length > 0">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Grille Critériée</h3>
                            </div>

                            <div class="space-y-6 bg-slate-50 p-5 rounded-xl border border-slate-100 shadow-inner">
                                <div v-for="rubric in activeSubmission.rubric" :key="rubric.key" class="space-y-3">
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <div class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                                {{ rubric.label }}
                                                <i class="pi pi-info-circle text-slate-300 text-[10px]" v-tooltip.top="rubric.help_text"></i>
                                            </div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase mt-1 tracking-wider">Poids : {{ rubric.weight * 100 }}%</div>
                                        </div>
                                        <div class="text-lg font-black" :class="(gradingForm.rubric_scores?.[rubric.key] ?? 0) >= (rubric.max/2) ? 'text-blue-600' : 'text-rose-500'">
                                            {{ gradingForm.rubric_scores?.[rubric.key] ?? 0 }}<span class="text-xs text-slate-400 font-normal">/{{ rubric.max }}</span>
                                        </div>
                                    </div>
                                    <Slider v-model="gradingForm.rubric_scores[rubric.key]" :min="0" :max="rubric.max" :step="0.5" class="w-full" />
                                    <!-- Description dynamique du niveau sélectionné -->
                                    <div class="p-3 bg-white rounded-lg border border-slate-100 text-[11px] text-slate-600 italic leading-snug">
                                        <span class="font-bold text-blue-500 not-italic mr-1">Statut:</span>
                                        {{ getRubricDescription(rubric.key, gradingForm.rubric_scores[rubric.key]) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider v-if="activeSubmission?.rubric" />

                        <!-- Feedback Constructif -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Feedback Général</h3>
                                <div class="flex gap-1">
                                    <Button icon="pi pi-list" class="p-button-text p-button-secondary p-button-sm" v-tooltip.top="'Utiliser un canevas prédéfini'" />
                                    <Button icon="pi pi-sparkles" label="Assistant IA" class="p-button-outlined p-button-info p-button-sm font-bold text-xs bg-blue-50" @click="generateAIFeedback" :loading="aiLoading" />
                                </div>
                            </div>

                            <!-- Smart Suggestion Area -->
                            <div v-if="gradingForm.feedback === ''" class="mb-4 p-4 bg-blue-50 rounded-xl border border-dashed border-indigo-200 text-center">
                                <p class="text-xs text-blue-600 font-medium italic">"L'élève a bien respecté les contraintes de temps mais manque de rigueur sur le typage PHP."</p>
                                <Button label="Utiliser cette suggestion" class="p-button-text p-button-sm text-[10px] font-bold mt-1" />
                            </div>

                            <!-- Audio Feedback Simulator -->
                            <div :class="['p-4 rounded-xl mb-4 flex items-center justify-between cursor-pointer transition-all border shadow-sm select-none',
                                 isRecording ? 'bg-rose-50 border-rose-300 text-rose-700 scale-[0.98]' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300']"
                                 @mousedown="startVoiceRecord" @mouseup="stopVoiceRecord" @mouseleave="stopVoiceRecord">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-8 h-8 rounded-full flex items-center justify-center', isRecording ? 'bg-rose-500 text-white animate-pulse' : 'bg-slate-100 text-slate-400']">
                                        <i class="pi pi-microphone"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold">{{ isRecording ? 'Enregistrement en cours...' : 'Feedback Audio' }}</div>
                                        <div class="text-[10px] font-medium uppercase" :class="isRecording ? 'text-rose-500' : 'text-slate-400'">{{ isRecording ? formatTime(recordTime) : 'Maintenir pour parler' }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rich Text Feedback -->
                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <Editor v-model="gradingForm.feedback" editorStyle="height: 250px" placeholder="Rédigez un commentaire constructif global qui sera visible par l'étudiant..." />
                            </div>
                        </div>

                        <Divider />

                        <!-- Paramètres de workflow -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest mb-2">Paramètres de publication</h3>

                            <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                                <div>
                                    <div class="text-sm font-bold text-slate-700">Publication Immédiate</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Rend la note visible après sauvegarde.</div>
                                </div>
                                <InputSwitch v-model="gradingForm.publish" />
                            </div>

                            <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
                                <div>
                                    <div class="text-sm font-bold text-slate-700">Notifier l'apprenant</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Envoie un email avec le lien du feedback.</div>
                                </div>
                                <InputSwitch v-model="gradingForm.send_notification" :disabled="!gradingForm.publish" />
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar (Fixed Right Panel) -->
                    <div class="p-6 border-t border-slate-200 bg-slate-50 flex flex-col gap-3 z-20 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                        <div class="flex gap-2">
                            <Button label="Brouillon" icon="pi pi-save" class="p-button-outlined p-button-secondary bg-white font-bold flex-1" @click="saveGrade('draft')" />
                            <Button icon="pi pi-replay" class="p-button-outlined p-button-warning bg-white font-bold" v-tooltip.top="'Demander une révision à l\'étudiant'" @click="saveGrade('revision_requested')" />
                        </div>
                        <Button label="Valider la Correction" icon="pi pi-check-circle" class="p-button-primary w-full shadow-lg font-bold py-3 text-lg" @click="saveGrade('graded')" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- OVERLAY : ANNOTATION RAPIDE INLINE                                             -->
        <!-- ============================================================================== -->
        <OverlayPanel ref="annotationPanel" class="w-[350px] shadow-2xl rounded-2xl border border-slate-200 p-0 overflow-hidden">
            <div class="p-5 bg-white">
                <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="pi pi-comment text-blue-500"></i> Placer un marqueur</h4>
                <Dropdown :options="['Excellent point', 'Conceptuellement faux', 'Hors sujet', 'Erreur de syntaxe']" placeholder="Tag contextuel..." class="w-full mb-3 border-slate-200 text-sm" />
                <Textarea rows="3" class="w-full bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:bg-white transition-colors" placeholder="Détaillez votre remarque..." />
                <div class="flex justify-end gap-2 mt-4">
                    <Button label="Annuler" class="p-button-text p-button-secondary p-button-sm font-bold" @click="annotationPanel.value.hide()" />
                    <Button label="Sauvegarder" class="p-button-primary p-button-sm font-bold px-4" @click="saveAnnotation" />
                </div>
            </div>
        </OverlayPanel>

        <!-- ============================================================================== -->
        <!-- SIDEBAR : AUDIT PLAGIAT (SNC-SHIELD)                                           -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="plagiarismSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-xl flex items-center justify-center"><i class="pi pi-shield text-xl"></i></div>
                    <h2 class="text-xl font-bold text-slate-800">Rapport d'Intégrité SNC</h2>
                </div>
            </template>
            <div class="p-6 space-y-8">

                <!-- Score Global -->
                <div class="bg-white border border-slate-200 p-8 rounded-2xl text-center shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-2" :class="activeSubmission?.similarity_score > 20 ? 'bg-rose-500' : 'bg-amber-400'"></div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Taux de Similarité</div>
                    <div class="text-6xl font-black mb-4" :class="activeSubmission?.similarity_score > 20 ? 'text-rose-600' : 'text-amber-500'">{{ activeSubmission?.similarity_score || 22 }}%</div>
                    <p class="text-sm text-slate-600 font-medium leading-relaxed">Cette copie présente des segments identiques à des publications existantes sur internet ou dans la base interne.</p>
                </div>

                <!-- Détails des sources -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Sources détectées</h4>
                    <div v-for="source in currentPlagiarismDetails" :key="source.source" class="p-4 bg-white border border-slate-200 rounded-xl flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div :class="`w-2 h-2 rounded-full ${source.color}`"></div>
                            <a :href="source.url" target="_blank" class="text-sm font-bold text-slate-700 hover:text-blue-600 cursor-pointer underline decoration-slate-200">{{ source.source }}</a>
                        </div>
                        <Badge :value="`${source.match}%`" :severity="source.match > 10 ? 'danger' : 'warning'" />
                    </div>
                </div>

                <!-- Actions de décision -->
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 mt-8">
                    <h4 class="text-sm font-bold text-slate-800 mb-4">Décision Académique</h4>
                    <div class="flex flex-col gap-3">
                        <Button label="Invalider la copie (Attribuer 0)" icon="pi pi-ban" class="p-button-danger shadow-sm font-bold" />
                        <Button label="Ignorer (Faux Positif)" icon="pi pi-check" class="p-button-outlined p-button-secondary bg-white font-bold" />
                    </div>
                </div>
            </div>
        </Sidebar>

        <!-- ============================================================================== -->
        <!-- SIDEBAR : STATISTIQUES COHORTE (ANALYTICS)                                     -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="statisticsSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 text-blue-600 rounded-xl flex items-center justify-center"><i class="pi pi-chart-bar text-xl"></i></div>
                    <h2 class="text-xl font-bold text-slate-800">Analytique Cohorte</h2>
                </div>
            </template>
            <div class="p-6 space-y-8">

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-1">Moyenne Globale</div>
                        <div class="text-3xl font-black text-blue-600">74.2%</div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm text-center">
                        <div class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-1">Taux de Réussite</div>
                        <div class="text-3xl font-black text-emerald-600">92%</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 text-center">Distribution des Notes</h4>
                    <Chart type="bar" :data="scoreDistribution" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { borderDash: [5,5] } } } }" class="h-48" />
                </div>

                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Tendance de Progression</h4>
                    <Chart type="line" :data="cohortTrendData" :options="{ maintainAspectRatio: false, scales: { x: { grid: { display: false } } } }" class="h-48" />
                </div>

                <Button label="Générer Rapport PDF (Administration)" icon="pi pi-download" class="p-button-outlined p-button-secondary w-full font-bold bg-white" />
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée SaaS / macOS */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
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

/* Custom Knob Styling */
:deep(.p-knob-text) {
    font-weight: 900 !important;
    font-family: inherit;
    fill: #4f46e5;
}

/* Custom Slider Styling */
:deep(.p-slider .p-slider-handle) {
    background-color: #4f46e5;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
:deep(.p-slider .p-slider-range) {
    background-color: #818cf8;
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

/* Editor Styling */
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
