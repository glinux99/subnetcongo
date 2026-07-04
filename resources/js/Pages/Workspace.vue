<script setup lang="ts">
/**
 * ======================================================================================
 * SUBNETCONGO LMS - GRADING WORKSPACE v2.0 (ENTERPRISE EDITION)
 * ======================================================================================
 * Interface de correction avancée.
 * Fonctionnalités ajoutées :
 * - TypeScript pour la sécurité des types
 * - Auto-calcul de la note finale
 * - Auto-sauvegarde silencieuse (Debounce)
 * - Feedback individuel par question
 * - Barre de progression de la correction
 * - Navigation étudiant précédent/suivant
 *
 * @author SubnetCongo Framework Division
 */

import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { debounce } from 'lodash'; // Nécessite npm install lodash

// Layout & UI Components
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Panel from 'primevue/panel';
import Button from 'primevue/button';
import Editor from 'primevue/editor';
import InputNumber from 'primevue/inputnumber';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import Textarea from 'primevue/textarea';
import ToggleButton from 'primevue/togglebutton';
import Skeleton from 'primevue/skeleton';

// --- TYPESCRIPT INTERFACES ---
interface Student {
    id: number;
    name: string;
    avatar?: string;
    email: string;
}

interface Question {
    id: number;
    question_text: string;
    points: number;
    ideal_answer?: string; // Réponse attendue (barème)
}

interface Answer {
    id: number;
    question: Question;
    submitted_text: string;
    earned_points?: number;
    teacher_comment?: string;
}

interface Submission {
    id: number;
    student: Student;
    assignment: { title: string; max_points: number; id: number };
    status: 'pending' | 'in_progress' | 'graded';
    grade: number;
    feedback_text: string;
    submitted_at: string;
}

const props = defineProps<{
    submission: Submission;
    answers: Answer[];
    nextSubmissionId?: number | null; // Pour naviguer vers l'étudiant suivant
    prevSubmissionId?: number | null;
}>();

const toast = useToast();
const isAutoSaving = ref(false);
const autoCalculate = ref(true); // Calcul automatique activé par défaut

// --- FORM SETUP ---
const gradingForm = useForm({
    final_grade: props.submission.grade || 0,
    feedback: props.submission.feedback_text || '',
    question_scores: props.answers.reduce((acc, ans) => {
        acc[ans.question.id] = ans.earned_points || 0;
        return acc;
    }, {} as Record<number, number>),
    question_feedback: props.answers.reduce((acc, ans) => {
        acc[ans.question.id] = ans.teacher_comment || '';
        return acc;
    }, {} as Record<number, string>),
});

// --- COMPUTED PROPERTIES (BUSINESS LOGIC) ---

// Calcule la somme des points attribués aux questions
const calculatedTotal = computed(() => {
    return Object.values(gradingForm.question_scores).reduce((sum, score) => sum + (score || 0), 0);
});

// Progression de la correction (combien de questions ont été notées)
const gradingProgress = computed(() => {
    if (props.answers.length === 0) return 100;
    const gradedCount = props.answers.filter(a => gradingForm.question_scores[a.question.id] !== undefined).length;
    return Math.round((gradedCount / props.answers.length) * 100);
});

// --- WATCHERS ---

// Met à jour la note finale automatiquement si l'option est activée
watch(calculatedTotal, (newTotal) => {
    if (autoCalculate.value) {
        gradingForm.final_grade = Number(newTotal.toFixed(2));
    }
});

// --- METHODS ---

const getStatusInfo = (status: string) => {
    const map: Record<string, { text: string; severity: string; icon: string }> = {
        pending: { text: 'À corriger', severity: 'warning', icon: 'pi-clock' },
        in_progress: { text: 'En cours', severity: 'info', icon: 'pi-spinner' },
        graded: { text: 'Publié', severity: 'success', icon: 'pi-check-circle' },
    };
    return map[status] || { text: status, severity: 'secondary', icon: 'pi-info-circle' };
};

// Fonction de soumission principale
const submitGrade = (publish: boolean = false) => {
    const routeName = 'gradings.update';

    gradingForm
        .transform(data => ({
            ...data,
            publish: publish,
            status: publish ? 'graded' : 'in_progress'
        }))
        .put(route(routeName, props.submission.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: publish ? 'success' : 'info',
                    summary: publish ? 'Note Publiée' : 'Brouillon Enregistré',
                    detail: publish ? 'L\'étudiant a été notifié.' : 'Vos modifications sont sauvegardées.',
                    life: 3000
                });

                // Si publié, proposer de passer au suivant
                if (publish && props.nextSubmissionId) {
                    navigateTo(props.nextSubmissionId);
                }
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs.', life: 5000 });
                console.error("Erreurs de validation:", errors);
            }
        });
};

// --- AUTO-SAVE LOGIC (Debounced) ---
const triggerAutoSave = debounce(() => {
    if (gradingForm.processing) return;
    isAutoSaving.value = true;

    gradingForm.put(route('gradings.update', props.submission.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { isAutoSaving.value = false; },
        onError: () => { isAutoSaving.value = false; }
    });
}, 2500); // Sauvegarde automatique après 2.5 secondes d'inactivité

// Écoute des changements pour l'auto-save
watch(() => gradingForm.data(), () => {
    triggerAutoSave();
}, { deep: true });

// --- NAVIGATION ---
const navigateTo = (id: number) => {
    router.visit(route('gradings.show', id));
};

// Empêcher de quitter si form dirty et non sauvegardé
const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (gradingForm.isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => { window.addEventListener('beforeunload', handleBeforeUnload); });
onUnmounted(() => { window.removeEventListener('beforeunload', handleBeforeUnload); });

</script>

<template>
    <AppLayout>
        <Head :title="`Correction | ${submission.student.name}`" />

        <div class="min-h-screen bg-slate-50/50 p-4 md:p-6 lg:p-8">
            <div class="max-w-[1400px] mx-auto">

                <!-- HEADER : Barre d'outils et contexte -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 mb-6 sticky top-0 z-10 transition-all">
                    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">

                        <!-- Info Étudiant & Devoir -->
                        <div class="flex items-center gap-4">
                            <Avatar :image="submission.student.avatar" :label="!submission.student.avatar ? submission.student.name.charAt(0) : ''" shape="circle" size="xlarge" class="shadow-sm" />
                            <div>
                                <h1 class="text-2xl font-black text-slate-800 tracking-tight">{{ submission.student.name }}</h1>
                                <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-slate-500">
                                    <span class="font-semibold text-slate-700">{{ submission.assignment.title }}</span>
                                    <Divider layout="vertical" class="hidden md:block" />
                                    <span>Soumis le: {{ new Date(submission.submitted_at).toLocaleDateString('fr-FR', { hour: '2-digit', minute:'2-digit' }) }}</span>
                                    <Divider layout="vertical" class="hidden md:block" />
                                    <Tag :value="getStatusInfo(submission.status).text" :icon="`pi ${getStatusInfo(submission.status).icon}`" :severity="getStatusInfo(submission.status).severity" rounded />
                                </div>
                            </div>
                        </div>

                        <!-- Actions & Navigation -->
                        <div class="flex items-center gap-3 w-full xl:w-auto justify-between xl:justify-end">
                            <div class="flex gap-2 mr-4">
                                <Button icon="pi pi-chevron-left" class="p-button-rounded p-button-text" :disabled="!prevSubmissionId" @click="prevSubmissionId && navigateTo(prevSubmissionId)" v-tooltip.bottom="'Étudiant précédent'" />
                                <Button icon="pi pi-chevron-right" class="p-button-rounded p-button-text" :disabled="!nextSubmissionId" @click="nextSubmissionId && navigateTo(nextSubmissionId)" v-tooltip.bottom="'Étudiant suivant'" />
                            </div>

                            <div class="flex gap-2">
                                <span v-if="isAutoSaving" class="flex items-center text-xs text-slate-400 mr-2">
                                    <i class="pi pi-sync pi-spin mr-1"></i> Sauvegarde...
                                </span>
                                <Button label="Brouillon" icon="pi pi-save" class="p-button-outlined p-button-secondary" @click="submitGrade(false)" :loading="gradingForm.processing" />
                                <Button label="Publier" icon="pi pi-send" @click="submitGrade(true)" :loading="gradingForm.processing" severity="success" />
                            </div>
                        </div>
                    </div>

                    <!-- Barre de progression de la correction -->
                    <div class="mt-4">
                        <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
                            <span>Progression de la correction</span>
                            <span>{{ gradingProgress }}%</span>
                        </div>
                        <ProgressBar :value="gradingProgress" :showValue="false" style="height: 6px" color="#3b82f6"></ProgressBar>
                    </div>
                </div>

                <!-- MAIN WORKSPACE -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                    <!-- Colonne de Gauche : Copie de l'étudiant (8 colonnes) -->
                    <div class="xl:col-span-8 space-y-6">

                        <div v-if="answers.length === 0" class="bg-white p-12 rounded-2xl border border-slate-200 text-center shadow-sm">
                            <i class="pi pi-check-square text-6xl text-slate-200 mb-4"></i>
                            <h3 class="text-xl font-bold text-slate-700">Correction 100% Automatique</h3>
                            <p class="text-slate-500 mt-2 max-w-md mx-auto">Ce devoir ne contient que des questions à choix multiples ou auto-évaluées. Aucune action manuelle n'est requise par question.</p>
                        </div>

                        <Panel v-for="(answer, index) in answers" :key="answer.id" :toggleable="true" class="shadow-sm border-none">
                            <template #header>
                                <div class="flex items-center gap-3 w-full">
                                    <div class="bg-blue-100 text-blue-700 font-bold rounded-full w-8 h-8 flex items-center justify-center shrink-0">
                                        Q{{ index + 1 }}
                                    </div>
                                    <span class="font-semibold text-slate-800" v-html="answer.question.question_text"></span>
                                    <div class="ml-auto flex items-center gap-2">
                                        <Tag :value="`/${answer.question.points} pts`" severity="secondary" rounded />
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                <!-- Réponse de l'étudiant -->
                                <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Réponse fournie</p>
                                    <div class="text-slate-800 font-serif text-[1.05rem] leading-relaxed whitespace-pre-wrap">{{ answer.submitted_text || 'Aucune réponse fournie.' }}</div>
                                </div>

                                <!-- Barème / Réponse attendue (Si disponible) -->
                                <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100" v-if="answer.question.ideal_answer">
                                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-3">Réponse attendue (Barème)</p>
                                    <div class="text-slate-700 text-sm leading-relaxed" v-html="answer.question.ideal_answer"></div>
                                </div>
                            </div>

                            <Divider type="dashed" />

                            <!-- Zone de notation par question -->
                            <div class="flex flex-col md:flex-row gap-4 bg-white">
                                <div class="w-full md:w-1/3">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Note attribuée</label>
                                    <div class="p-inputgroup">
                                        <InputNumber v-model="gradingForm.question_scores[answer.question.id]"
                                            mode="decimal" :maxFractionDigits="1" :min="0" :max="answer.question.points"
                                            class="font-bold text-lg"
                                            :class="{'p-invalid': gradingForm.question_scores[answer.question.id] > answer.question.points}" />
                                        <span class="p-inputgroup-addon font-bold bg-slate-50">/ {{ answer.question.points }}</span>
                                    </div>
                                </div>

                                <div class="w-full md:w-2/3">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Commentaire spécifique (Optionnel)</label>
                                    <Textarea v-model="gradingForm.question_feedback[answer.question.id]" rows="2" class="w-full" placeholder="Justifiez votre note pour cette question..." autoResize />
                                </div>
                            </div>
                        </Panel>
                    </div>

                    <!-- Colonne de Droite : Panneau de Synthèse (4 colonnes) -->
                    <div class="xl:col-span-4">
                        <div class="bg-white p-6 rounded-2xl shadow-lg border border-slate-200 sticky top-32">

                            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2 mb-6">
                                <i class="pi pi-calculator text-indigo-500"></i> Synthèse de Notation
                            </h3>

                            <div class="space-y-6">

                                <!-- Note Finale -->
                                <div class="p-5 bg-slate-50 rounded-xl border border-slate-200 relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-full h-1 bg-indigo-500"></div>
                                    <div class="flex justify-between items-center mb-4">
                                        <label for="final_grade" class="block text-sm font-bold text-slate-700">Note Globale</label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-500 font-medium">Auto-calcul</span>
                                            <ToggleButton v-model="autoCalculate" onIcon="pi pi-check" offIcon="pi pi-times" class="w-8 h-8 !p-0" />
                                        </div>
                                    </div>

                                    <div class="flex items-end gap-2">
                                        <InputNumber id="final_grade" v-model="gradingForm.final_grade"
                                            class="w-full" inputClass="text-3xl font-black text-indigo-600 !p-3"
                                            :min="0" :max="submission.assignment.max_points"
                                            :disabled="autoCalculate" />
                                        <span class="text-2xl font-bold text-slate-400 mb-2">/ {{ submission.assignment.max_points }}</span>
                                    </div>
                                    <small v-if="gradingForm.errors.final_grade" class="text-red-500 mt-2 block">{{ gradingForm.errors.final_grade }}</small>
                                </div>

                                <!-- Feedback Général -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                                        <i class="pi pi-comment text-slate-400"></i> Appréciation Générale
                                    </label>
                                    <Editor v-model="gradingForm.feedback" editorStyle="height: 250px" placeholder="Félicitations, excellent travail sur la partie 2...">
                                        <template v-slot:toolbar>
                                            <span class="ql-formats">
                                                <button class="ql-bold"></button>
                                                <button class="ql-italic"></button>
                                                <button class="ql-underline"></button>
                                            </span>
                                             <span class="ql-formats">
                                                <button class="ql-list" value="bullet"></button>
                                                <button class="ql-list" value="ordered"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <select class="ql-color"></select>
                                                <select class="ql-background"></select>
                                            </span>
                                        </template>
                                    </Editor>
                                    <small v-if="gradingForm.errors.feedback" class="text-red-500 mt-1 block">{{ gradingForm.errors.feedback }}</small>
                                </div>

                                <!-- Bouton Action Mobile (Caché sur desktop si besoin) -->
                                <Button label="Enregistrer et Publier" icon="pi pi-check-circle" size="large" class="w-full mt-4" severity="success" @click="submitGrade(true)" :loading="gradingForm.processing" />

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ajout d'animations fluides pour l'UI */
.p-panel {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.p-panel:hover {
    box-shadow: 0 4px 15px -3px rgb(0 0 0 / 0.1);
}
</style>
