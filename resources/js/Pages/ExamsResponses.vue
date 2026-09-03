--- START OF FILE ExamsResponses.vue ---

<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXAM ARCHITECT & ASSESSMENT ENGINE v13.0.0 (Ultimate Edition)
 * ======================================================================================
 * @module AssessmentEngine
 * @description Moteur de rendu d'évaluation ultra-haute performance.
 *
 * SYSTÈME DE PROTECTION CONTRE LES ERREURS :
 * - Correction du bug "Cannot read properties of null (reading 'uuid')"
 * - Validation stricte des schémas de réponse par type de question
 * - Redondance de stockage (RAM + LocalStorage + ServerSync)
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick, shallowRef } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3'; // <-- Ajout de route
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { useEventListener, useFullscreen, useBattery, useNetwork } from '@vueuse/core';

// --- PRIMEVUE COMPONENTS ---
import Button from 'primevue/button';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ProgressBar from 'primevue/progressbar';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ScrollPanel from 'primevue/scrollpanel';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Message from 'primevue/message';
import Sidebar from 'primevue/sidebar';
import Knob from 'primevue/knob';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Skeleton from 'primevue/skeleton';
import OverlayPanel from 'primevue/overlaypanel';
import Timeline from 'primevue/timeline';
import Dropdown from 'primevue/dropdown';
import PickList from 'primevue/picklist';
import OrderList from 'primevue/orderlist';
import Inplace from 'primevue/inplace';
import Chip from 'primevue/chip';
import Panel from 'primevue/panel';
import Stepper from 'primevue/stepper'; // PrimeVue 4 style (simulé ici via v-if)
import Splitter from 'primevue/splitter';
import SplitterPanel from 'primevue/splitterpanel';

// ======================================================================================
// PROPS & INITIALISATION SÉCURISÉE
// ======================================================================================
const props = defineProps({
    exam: { type: Object, required: true },
    attempt: { type: Object, required: true, default: () => ({ uuid: null, answers: {} }) },
    questions: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({ name: 'Invité', avatar: null }) }
});

const toast = useToast();
const confirm = useConfirm();
const { isSupported, isFullscreen, enter, exit, toggle } = useFullscreen();
const { isOnline } = useNetwork();
const battery = useBattery();

// ======================================================================================
// ÉTAT RÉACTIF (REFS)
// ======================================================================================

// Réponses : Initialisation ultra-sécurisée pour éviter les crashs au montage
const answers = ref({});
const initializeAnswers = () => {
    try {
        if (props.attempt?.answers) {
            answers.value = typeof props.attempt.answers === 'string'
                ? JSON.parse(props.attempt.answers)
                : JSON.parse(JSON.stringify(props.attempt.answers));
        } else {
            answers.value = {};
        }

        // Initialiser les structures pour les types complexes si vide
        props.questions.forEach(q => {
            if (!answers.value[q.id]) {
                if (q.type === 'multiple_choice_multiple') answers.value[q.id] = [];
                else if (q.type === 'ordering') answers.value[q.id] = [...q.options];
                else if (q.type === 'matching') answers.value[q.id] = {};
                else answers.value[q.id] = null;
            }
        });
    } catch (e) {
        console.error("Erreur initialisation réponses:", e);
        answers.value = {};
    }
};

const flaggedQuestions = ref([]);
const currentStep = ref('intro'); // intro | testing | review | results
const currentIndex = ref(0);
const isSyncing = ref(false);
const isStarting = ref(false); // Pour le bouton "Démarrer"
const lastSaved = ref(null);

// Proctoring & Biométrie
const proctoringLogs = ref([]);
const tabSwitchCount = ref(0);
const keyboardAnomalies = ref(0);
const isCameraActive = ref(false);
const webcamVideo = ref(null);
const proctoringScore = ref(100);

// Chronomètre
const totalDurationSeconds = props.exam.duration_minutes * 60;
const timeLeft = ref(props.attempt?.time_left || totalDurationSeconds);
const timerInterval = ref(null);

// UI States
const sidebarVisible = ref(false);
const helpDialog = ref(false);
const calculatorVisible = ref(false);
const notepadContent = ref('');
const showNotepad = ref(false);

// State for Calculator
const calculatorDisplay = ref('0');
const calculatorMemory = ref(null);
const calculatorOperator = ref(null);
const waitingForOperand = ref(false);

// ======================================================================================
// COMPUTED PROPERTIES (LOGIQUE MÉTIER)
// ======================================================================================

const currentQuestion = computed(() => {
    if (!props.questions || props.questions.length === 0) return null;
    return props.questions[currentIndex.value];
});

const progress = computed(() => {
    if (!props.questions.length) return 0;
    return Math.round(((currentIndex.value + 1) / props.questions.length) * 100);
});

const globalCompletion = computed(() => {
    if (!props.questions.length) return 0;
    const answeredCount = props.questions.filter(q => isAnswered(q.id)).length;
    return Math.round((answeredCount / props.questions.length) * 100);
});

const formattedTime = computed(() => {
    const hours = Math.floor(timeLeft.value / 3600);
    const minutes = Math.floor((timeLeft.value % 3600) / 60);
    const seconds = timeLeft.value % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const handleCalculatorInput = (input) => {
    if (['/', '*', '-', '+'].includes(input)) {
        if (calculatorOperator.value && !waitingForOperand.value) {
            handleCalculatorInput('=');
        }
        calculatorMemory.value = parseFloat(calculatorDisplay.value);
        calculatorOperator.value = input;
        waitingForOperand.value = true;
        return;
    }

    if (input === '=') {
        if (!calculatorOperator.value || calculatorMemory.value === null) return;
        const currentValue = parseFloat(calculatorDisplay.value);
        let result;
        switch (calculatorOperator.value) {
            case '+': result = calculatorMemory.value + currentValue; break;
            case '-': result = calculatorMemory.value - currentValue; break;
            case '*': result = calculatorMemory.value * currentValue; break;
            case '/':
                if (currentValue === 0) {
                    calculatorDisplay.value = 'Erreur';
                    return;
                }
                result = calculatorMemory.value / currentValue;
                break;
        }
        calculatorDisplay.value = String(result);
        calculatorOperator.value = null;
        calculatorMemory.value = null;
        waitingForOperand.value = false;
        return;
    }

    if (input === '.') {
        if (!calculatorDisplay.value.includes('.')) {
            calculatorDisplay.value += '.';
        }
        return;
    }

    if (waitingForOperand.value) {
        calculatorDisplay.value = input;
        waitingForOperand.value = false;
    } else {
        calculatorDisplay.value = calculatorDisplay.value === '0' ? input : calculatorDisplay.value + input;
    }
};

const clearCalculator = () => {
    calculatorDisplay.value = '0';
    calculatorMemory.value = null;
    calculatorOperator.value = null;
    waitingForOperand.value = false;
};

// ======================================================================================
// GESTION DES RÉPONSES PAR TYPE
// ======================================================================================

const isAnswered = (questionId) => {
    if (!answers.value || answers.value[questionId] === undefined || answers.value[questionId] === null) return false;
    const ans = answers.value[questionId];

    if (Array.isArray(ans)) return ans.length > 0;
    if (typeof ans === 'object') return Object.keys(ans).length > 0;
    if (typeof ans === 'string') return ans.trim().length > 0;
    return true;
};

const handleAnswerInput = (questionId, value) => {
    answers.value[questionId] = value;
    saveToLocalBackup();

    // Si mode strict, on sync au serveur toutes les 5 actions
    if (props.exam.settings?.sync_frequency === 'high') {
         persistToServer();
    }
};

/**
 * Logique spécifique pour le Matching (Appariement)
 */
const setMatchingPair = (questionId, leftId, rightId) => {
    if (!answers.value[questionId]) answers.value[questionId] = {};
    answers.value[questionId][leftId] = rightId;
    saveToLocalBackup();
};

/**
 * Logique pour Ordonnancement
 */
const updateOrdering = (questionId, newList) => {
    answers.value[questionId] = newList.map(item => item.id);
    saveToLocalBackup();
};

// ======================================================================================
// NAVIGATION ET ÉVÉNEMENTS
// ======================================================================================

const nextQuestion = () => {
    if (currentIndex.value < props.questions.length - 1) {
        currentIndex.value++;
        scrollToTop();
    } else {
        currentStep.value = 'review';
    }
};

const prevQuestion = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
        scrollToTop();
    }
};

const jumpToQuestion = (index) => {
    currentIndex.value = index;
    currentStep.value = 'testing';
    sidebarVisible.value = false;
};

const toggleFlag = (questionId) => {
    const idx = flaggedQuestions.value.indexOf(questionId);
    if (idx === -1) flaggedQuestions.value.push(questionId);
    else flaggedQuestions.value.splice(idx, 1);
};

const scrollToTop = () => {
    nextTick(() => {
        const el = document.getElementById('question-canvas');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    });
};

// ======================================================================================
// SYNC & SÉCURITÉ (LE COEUR DU FIX)
// ======================================================================================

const persistToServer = async () => {
    // FIX : Vérification cruciale de l'existence de l'UUID
    if (!props.attempt?.uuid) {
        console.warn("Persistance annulée : UUID de tentative manquant.");
        return;
    }

    if (!isOnline.value || isSyncing.value) return;

    isSyncing.value = true;
    try {
        await router.patch(route('attempts.update-answers', props.attempt.uuid), {
            answers: answers.value,
            time_spent: totalDurationSeconds - timeLeft.value,
            metadata: {
                battery: battery.level.value,
                proctoring_score: proctoringScore.value
            }
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                lastSaved.value = new Date();
                isSyncing.value = false;
            },
            onFinish: () => { isSyncing.value = false; }
        });
    } catch (err) {
        console.error("Erreur Sync Serveur:", err);
        isSyncing.value = false;
    }
};

const logProctoringEvent = (event, severity = 'medium') => {
    if (!props.attempt?.uuid) return;

    const log = {
        event,
        severity,
        timestamp: new Date().toISOString(),
        qIndex: currentIndex.value
    };
    proctoringLogs.value.unshift(log);

    if (severity === 'high') proctoringScore.value -= 10;

    // Notification visuelle discrète pour l'utilisateur
    toast.add({
        severity: severity === 'high' ? 'error' : 'warn',
        summary: 'Sécurité Exam',
        detail: `Incident détecté : ${event}`,
        life: 3000
    });

    // Envoi immédiat
    router.post(route('attempts.flag', props.attempt.uuid), {
        reason: event,
        log_data: log
    }, { preserveScroll: true });
};

const saveToLocalBackup = () => {
    if (!props.attempt?.uuid) return;
    localStorage.setItem(`subnet_lms_bak_${props.attempt.uuid}`, JSON.stringify({
        answers: answers.value,
        timestamp: Date.now()
    }));
};

// ======================================================================================
// GESTION DU TEMPS
// ======================================================================================

// Fonction interne pour démarrer ou reprendre l'examen (appelée après création de la tentative)
const beginOrResumeTest = () => {
    if (props.exam.settings?.require_fullscreen && isSupported) {
        enter();
    }

    currentStep.value = 'testing';
    initializeAnswers();
    initTimer();
    setupProctoringListeners();

    if (props.exam.settings?.enable_camera) {
        initWebcam();
    }
};

// Gestionnaire du clic sur le bouton "Lancer l'Évaluation"
const handleStartClick = () => {
    if (isStarting.value) return;

    // S'il n'y a pas de tentative active, on en crée une via le serveur.
    if (!props.attempt?.uuid) {
        isStarting.value = true;
        router.post(route('attempts.start', props.exam.id), {}, {
            preserveState: false, // On recharge pour obtenir le nouvel 'attempt'
            onFinish: () => { isStarting.value = false; }
        });
        return;
    }

    // Sinon, on commence directement
    beginOrResumeTest();
};

const initTimer = () => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
            // Sync automatique toutes les 60 secondes
            if (timeLeft.value % 60 === 0) persistToServer();
        } else {
            // Le temps est écoulé, on soumet automatiquement
            handleAutoSubmit();
        }
        // Sécurité supplémentaire : si timeLeft devient négatif, on force l'arrêt
        if (timeLeft.value <= 0) {
            clearInterval(timerInterval.value);
            timerInterval.value = null;
        }
    }, 1000);
};

const handleAutoSubmit = () => {
    clearInterval(timerInterval.value);
    timerInterval.value = null;
    toast.add({ severity: 'error', summary: 'Temps Épuisé', detail: 'Soumission automatique en cours...', life: 5000 });
    forceSubmit();
};

const forceSubmit = () => {
    if (!props.attempt?.uuid) return;

    router.post(route('attempts.submit', props.attempt.uuid), {
        answers: answers.value,
        time_spent: totalDurationSeconds - timeLeft.value,
        logs: proctoringLogs.value,
        final_score_proctoring: proctoringScore.value
    }, {
        onStart: () => isSyncing.value = true,
        onSuccess: () => {
            localStorage.removeItem(`subnet_lms_bak_${props.attempt.uuid}`);
            currentStep.value = 'results';
        }
    });
};

const confirmSubmission = () => {
    const unanswered = props.questions.length - Object.keys(answers.value).filter(k => isAnswered(k)).length;

    confirm.require({
        header: 'Soumission Finale',
        message: unanswered > 0
            ? `Attention : Il vous reste ${unanswered} question(s) sans réponse. Voulez-vous vraiment terminer ?`
            : 'Confirmez-vous la fin de votre épreuve ? Toute modification sera impossible.',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Oui, Envoyer ma copie',
        rejectLabel: 'Continuer l\'examen',
        acceptClass: 'p-button-danger',
        accept: () => forceSubmit()
    });
};

// ======================================================================================
// PROCTORING AVANCÉ & LISTENERS
// ======================================================================================

const setupProctoringListeners = () => {
    // Changement d'onglet
    useEventListener(document, 'visibilitychange', () => {
        if (document.hidden) {
            tabSwitchCount.value++;
            logProctoringEvent(`Sortie d'écran #${tabSwitchCount.value}`, tabSwitchCount.value > 2 ? 'high' : 'medium');
        }
    });

    // Blocage touches interdites
    useEventListener(window, 'keydown', (e) => {
        const forbidden = ['F12', 'PrintScreen', 'Meta'];
        if (forbidden.includes(e.key) || (e.ctrlKey && ['c', 'v', 'p', 's'].includes(e.key.toLowerCase()))) {
            e.preventDefault();
            keyboardAnomalies.value++;
            logProctoringEvent(`Raccourci interdit : ${e.key}`, 'medium');
        }
    });

    // Clic droit
    useEventListener(document, 'contextmenu', (e) => e.preventDefault());
};

const initWebcam = async () => {
    try {
        const constraints = { video: { width: 320, height: 240 } };
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        if (webcamVideo.value) {
            webcamVideo.value.srcObject = stream;
            isCameraActive.value = true;
        }
    } catch (err) {
        logProctoringEvent("Échec démarrage webcam", "high");
        toast.add({ severity: 'error', summary: 'Erreur Caméra', detail: 'La webcam est obligatoire pour cet examen.' });
    }
};

const getTypeSeverity = (type) => {
    switch (type) {
        case 'multiple_choice': return 'info';
        case 'multiple_choice_multiple': return 'warning';
        case 'true_false': return 'success';
        case 'short_answer': return 'secondary';
        case 'matching': return 'help';
        case 'ordering': return 'contrast';
        case 'essay': return 'primary';
        default: return 'secondary';
    }
};

const getTypeIcon = (type) => {
    switch (type) {
        case 'multiple_choice': return 'pi pi-dot-circle';
        case 'multiple_choice_multiple': return 'pi pi-check-square';
        case 'true_false': return 'pi pi-check-circle';
        case 'short_answer': return 'pi pi-pencil';
        case 'matching': return 'pi pi-link';
        case 'ordering': return 'pi pi-sort-alt';
        case 'essay': return 'pi pi-file-edit';
        default: return 'pi pi-question';
    }
};

const formatTypeName = (type) => type?.replace(/_/g, ' ').toUpperCase();

// ======================================================================================
// LIFECYCLE
// ======================================================================================

onMounted(() => {
    // Si une tentative existe déjà au chargement (reprise après rafraîchissement), on démarre directement.
    if (props.attempt?.uuid) {
        beginOrResumeTest();

        // Tenter de restaurer une sauvegarde locale en cas de crash
        const backup = localStorage.getItem(`subnet_lms_bak_${props.attempt.uuid}`);
        if (backup) {
            try {
                const data = JSON.parse(backup);
                if (Date.now() - data.timestamp < 3600000) { // < 1h
                    answers.value = data.answers;
                    toast.add({ severity: 'info', summary: 'Restauration', detail: 'Vos réponses ont été récupérées après l\'interruption.' });
                }
            } catch (e) {
                console.warn("Backup corrompu", e);
            }
        }
    }

    // Bloquer le bouton "Retour" du navigateur
    history.pushState(null, null, location.href);
    window.onpopstate = () => {
        history.go(1);
        toast.add({ severity: 'warn', summary: 'Navigation Interdite', detail: 'Utilisez les boutons de l\'interface.' });
    };
});

onBeforeUnmount(() => {
    if (timerInterval.value) clearInterval(timerInterval.value);
    // Arrêter la webcam
    if (webcamVideo.value?.srcObject) {
        webcamVideo.value.srcObject.getTracks().forEach(track => track.stop());
    }
});

</script>

<template>
    <div class="min-h-screen bg-[#F0F2F5] font-sans text-slate-900 selection:bg-indigo-500 selection:text-white">
        <Head :title="`Certification - ${exam.title}`" />
        <Toast position="bottom-right" />
        <ConfirmDialog />

        <!-- ========================================================================== -->
        <!-- NAVIGATION SUPÉRIEURE : HUD D'ÉVALUATION                                   -->
        <!-- ========================================================================== -->
        <nav class="sticky top-0 z-[100] bg-white/90 backdrop-blur-xl border-b border-slate-200 px-6 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-5">
                <div class="relative">
                    <Avatar :image="user.avatar" :label="user.name?.charAt(0)" size="large" shape="circle" class="border-2 border-indigo-100 shadow-sm" />
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white" :class="isOnline ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                </div>
                <div class="hidden md:block">
                    <h1 class="text-sm font-black text-slate-800 leading-none mb-1 uppercase tracking-tight">{{ exam.title }}</h1>
                    <div class="flex items-center gap-3">
                        <Tag :value="`ID: ${attempt?.uuid?.substring(0, 8) || 'NON-SYNC'}`" severity="secondary" class="text-[9px] font-mono" />
                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                            <i class="pi pi-shield text-[9px]"></i> Proctoring Actif
                        </span>
                    </div>
                </div>
            </div>

            <!-- CHRONO & PROGRESSION -->
            <div class="flex flex-col items-center">
                <div :class="[
                    'flex items-center gap-4 px-6 py-2 rounded-2xl border-2 transition-all duration-300',
                    timeLeft < 300 ? 'bg-rose-50 border-rose-200 text-rose-600 animate-pulse' : 'bg-slate-900 border-slate-800 text-white'
                ]">
                    <i class="pi pi-clock font-bold"></i>
                    <span class="text-2xl font-black font-mono tracking-tighter tabular-nums">{{ formattedTime }}</span>
                </div>
                <div class="w-full mt-2 h-1 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 transition-all duration-1000" :style="{ width: progress + '%' }"></div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="hidden lg:flex items-center gap-4 mr-4">
                    <div class="text-right">
                        <p class="text-[9px] font-black text-slate-400 uppercase leading-none">Complétion</p>
                        <p class="text-sm font-black text-slate-700">{{ globalCompletion }}%</p>
                    </div>
                    <Knob v-model="globalCompletion" :size="40" :strokeWidth="12" valueColor="#6366f1" rangeColor="#f1f5f9" readonly />
                </div>

                <Button icon="pi pi-calculator" @click="calculatorVisible = true" rounded text severity="secondary" v-tooltip.bottom="'Calculatrice'" />
                <Button icon="pi pi-file-edit" @click="showNotepad = true" rounded text severity="secondary" v-tooltip.bottom="'Brouillon'" />
                <Button label="Terminer" severity="danger" class="font-black px-6 shadow-lg shadow-rose-100" @click="confirmSubmission" v-if="currentStep === 'testing'" />
            </div>
        </nav>

        <main class="max-w-[1600px] mx-auto p-4 md:p-8 lg:p-10">

            <!-- STEP 1 : ÉCRAN D'ACCUEIL & CONSIGNES -->
            <div v-if="currentStep === 'intro'" class="max-w-4xl mx-auto py-10 animate-fade-in">
                <Card class="border-none shadow-2xl rounded-[1.5rem] overflow-hidden">
                    <template #header>
                        <div class="h-48 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-900 flex flex-col items-center justify-center text-white relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10 pattern-dots scale-150"></div>
                            <i class="pi pi-verified text-6xl mb-4 opacity-50"></i>
                            <h2 class="text-4xl font-black tracking-tighter">Prêt pour l'évaluation ?</h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="p-6 md:p-10">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center transition-transform hover:scale-105">
                                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="pi pi-list text-xl"></i>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Items</p>
                                    <p class="text-2xl font-black text-slate-800">{{ questions.length }} Questions</p>
                                </div>
                                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center transition-transform hover:scale-105">
                                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="pi pi-stopwatch text-xl"></i>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Durée</p>
                                    <p class="text-2xl font-black text-slate-800">{{ exam.duration_minutes }} Minutes</p>
                                </div>
                                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center transition-transform hover:scale-105">
                                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <i class="pi pi-shield-check text-xl"></i>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Seuil</p>
                                    <p class="text-2xl font-black text-slate-800">{{ exam.pass_percentage }}%</p>
                                </div>
                            </div>

                            <div class="bg-indigo-50/50 p-8 rounded-3xl border border-indigo-100/50 mb-10 prose prose-slate max-w-none">
                                <h3 class="text-indigo-900 font-black mb-4 flex items-center gap-2">
                                    <i class="pi pi-info-circle"></i> Instructions de l'examinateur
                                </h3>
                                <div v-html="exam.description" class="text-slate-600 leading-relaxed"></div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12">
                                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white border border-slate-200">
                                    <i class="pi pi-camera text-slate-400 mt-1"></i>
                                    <div>
                                        <h5 class="font-bold text-sm text-slate-800">Webcam Requise</h5>
                                        <p class="text-xs text-slate-500">Votre visage doit rester visible dans le cadre durant toute la session.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white border border-slate-200">
                                    <i class="pi pi-window-maximize text-slate-400 mt-1"></i>
                                    <div>
                                        <h5 class="font-bold text-sm text-slate-800">Mode Plein Écran</h5>
                                        <p class="text-xs text-slate-500">Le quitter sera enregistré comme une tentative de fraude.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- UN SEUL BOUTON : avec gestion du chargement -->
                            <Button label="Lancer l'Évaluation" icon="pi pi-bolt" size="large" class="w-full py-6 text-2xl font-black rounded-[1rem] shadow-2xl shadow-indigo-200" @click="handleStartClick" :loading="isStarting" />
                            <p class="text-center text-[11px] text-slate-400 mt-8 font-bold uppercase tracking-widest">Code de conduite Subnet Congo v12.4 s'applique</p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- STEP 2 : TESTING INTERFACE -->
            <div v-if="currentStep === 'testing'" class="grid grid-cols-12 gap-8 animate-fade-in">

                <!-- SIDEBAR GAUCHE : NAVIGATION & PROCTORING -->
                <aside class="col-span-12 lg:col-span-3 space-y-6">

                    <!-- Webcam Feed -->
                    <div class="bg-slate-900 rounded-[1rem] overflow-hidden aspect-video relative border-4 border-white shadow-xl shadow-slate-200 group">
                        <video ref="webcamVideo" autoplay muted playsinline class="w-full h-full object-cover grayscale opacity-80 group-hover:grayscale-0 transition-all"></video>
                        <div class="absolute top-4 left-4 flex items-center gap-2">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            <Tag value="REC" severity="danger" class="font-black text-[10px]" />
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 bg-black/40 backdrop-blur-md p-3 rounded-xl border border-white/10">
                            <p class="text-[9px] text-white/70 font-black uppercase tracking-tighter">Analyse Biométrique IA</p>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 h-1 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-400" :style="{width: proctoringScore + '%'}"></div>
                                </div>
                                <span class="text-[10px] font-bold text-white">{{ proctoringScore }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sélecteur de Question -->
                    <div class="bg-white rounded-[1rem] p-8 border border-slate-200 shadow-sm sticky top-24">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 flex justify-between">
                            Sélecteur <span>{{ currentIndex + 1 }}/{{ questions.length }}</span>
                        </h3>

                        <div class="grid grid-cols-5 gap-3">
                            <button v-for="(q, idx) in questions" :key="q.id"
                                @click="jumpToQuestion(idx)"
                                :class="[
                                    'h-11 rounded-xl text-xs font-black transition-all flex items-center justify-center border-2',
                                    currentIndex === idx ? 'bg-indigo-600 text-white border-indigo-600 scale-110 shadow-lg shadow-indigo-100 z-10' :
                                    isAnswered(q.id) ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-white text-slate-400 border-slate-100 hover:border-indigo-200',
                                    flaggedQuestions.includes(q.id) ? 'ring-2 ring-amber-400 ring-offset-2' : ''
                                ]">
                                {{ idx + 1 }}
                            </button>
                        </div>

                        <Divider class="my-8" />

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-2"><i class="pi pi-check-circle text-emerald-500"></i> Répondu</span>
                                <span class="text-slate-900">{{ questions.filter(q => isAnswered(q.id)).length }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-2"><i class="pi pi-circle text-slate-200"></i> Restant</span>
                                <span class="text-slate-900">{{ questions.length - questions.filter(q => isAnswered(q.id)).length }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                <span class="flex items-center gap-2"><i class="pi pi-flag-fill text-amber-500"></i> Révision</span>
                                <span class="text-slate-900">{{ flaggedQuestions.length }}</span>
                            </div>
                        </div>

                        <div v-if="isSyncing" class="mt-8 p-4 bg-indigo-50 rounded-2xl flex items-center gap-3">
                            <i class="pi pi-spin pi-spinner text-indigo-600 text-sm"></i>
                            <span class="text-[10px] font-black text-indigo-700 uppercase">Synchronisation...</span>
                        </div>
                        <div v-else-if="lastSaved" class="mt-8 text-center">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                                Sauvegardé à {{ lastSaved.toLocaleTimeString() }}
                            </span>
                        </div>
                    </div>
                </aside>

                <!-- CENTRE : CANVAS DE QUESTION (CONTENU DYNAMIQUE) -->
                <div class="col-span-12 lg:col-span-9 space-y-6" id="question-canvas">
                    <div class="bg-white rounded-[3rem] border border-slate-200 shadow-xl overflow-hidden min-h-[750px] flex flex-col transition-all duration-500 hover:shadow-2xl">

                        <!-- ========================================== -->
                        <!-- EN-TÊTE DE LA QUESTION                     -->
                        <!-- ========================================== -->
                        <div class="px-10 py-8 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-6">
                                <!-- Index Circulaire -->
                                <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-indigo-200 relative">
                                    {{ currentIndex + 1 }}
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-emerald-500 rounded-full border-4 border-white flex items-center justify-center" v-if="isAnswered(currentQuestion?.id)">
                                        <i class="pi pi-check text-[10px]"></i>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <!-- Badge de Type de Question -->
                                        <Tag :severity="getTypeSeverity(currentQuestion?.type)" class="px-3 py-1 rounded-lg" v-if="currentQuestion?.type">
                                            <div class="flex items-center gap-2">
                                                <i :class="getTypeIcon(currentQuestion?.type)" class="text-[10px]"></i>
                                                <span class="uppercase text-[10px] font-black tracking-widest">{{ formatTypeName(currentQuestion?.type) }}</span>
                                            </div>
                                        </Tag>
                                        <Tag :value="`${currentQuestion?.points} points`" severity="secondary" class="uppercase text-[10px] font-black px-3" />
                                    </div>
                                    <h4 class="text-slate-400 font-bold text-[11px] uppercase tracking-widest">Évaluation en cours de traitement</h4>
                                </div>
                            </div>

                            <!-- Action de Révision -->
                            <div class="flex gap-2">
                                <Button :icon="flaggedQuestions.includes(currentQuestion?.id) ? 'pi pi-flag-fill' : 'pi pi-flag'"
                                        :label="flaggedQuestions.includes(currentQuestion?.id) ? 'Marquée pour révision' : 'Réviser plus tard'"
                                        :severity="flaggedQuestions.includes(currentQuestion?.id) ? 'warning' : 'secondary'"
                                        text class="font-black text-xs px-4 py-3 rounded-xl transition-all"
                                        @click="toggleFlag(currentQuestion?.id)" />
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- CORPS DE LA QUESTION (LOGIQUE DYNAMIQUE)   -->
                        <!-- ========================================== -->
                        <div class="p-10 lg:p-16 flex-1 flex flex-col">

                            <!-- Énoncé de la question -->
                            <div class="mb-12">
                                <h2 class="text-2xl md:text-4xl font-bold text-slate-800 leading-[1.3] mb-6">
                                    {{ currentQuestion?.question_text }}
                                </h2>
                                <div v-if="currentQuestion?.attachment" class="rounded-3xl overflow-hidden border border-slate-200 mb-8 max-w-2xl">
                                    <img :src="currentQuestion.attachment" class="w-full h-auto object-cover" alt="Illustration question" />
                                </div>
                            </div>

                            <!-- ZONE DE RÉPONSE SELON LE TYPE -->
                            <div class="flex-1">

                                <!-- 1. TYPE: TRUE / FALSE -->
                                <div v-if="currentQuestion?.type === 'true_false'" class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                                    <div v-for="option in [true, false]" :key="option.toString()"
                                         @click="handleAnswerInput(currentQuestion.id, option)"
                                         :class="[
                                            'group relative p-10 rounded-[1.5rem] border-2 cursor-pointer transition-all duration-300 flex flex-col items-center justify-center gap-4 overflow-hidden',
                                            answers[currentQuestion.id] === option ?
                                                (option ? 'bg-emerald-50 border-emerald-500 shadow-xl shadow-emerald-100' : 'bg-rose-50 border-rose-500 shadow-xl shadow-rose-100') :
                                                'bg-white border-slate-100 hover:border-slate-300 hover:bg-slate-50'
                                         ]">
                                        <div :class="['w-16 h-16 rounded-2xl flex items-center justify-center text-2xl transition-all',
                                                    answers[currentQuestion.id] === option ? (option ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white') : 'bg-slate-100 text-slate-400']">
                                            <i :class="option ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                                        </div>
                                        <span :class="['text-2xl font-black uppercase tracking-tighter', answers[currentQuestion.id] === option ? (option ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-600']">
                                            {{ option ? 'Vrai / Correct' : 'Faux / Incorrect' }}
                                        </span>
                                        <!-- Tick de sélection -->
                                        <div v-if="answers[currentQuestion.id] === option" class="absolute top-4 right-4">
                                            <i class="pi pi-check-circle text-xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. TYPE: MULTIPLE CHOICE (RADIO) -->
                                <div v-if="currentQuestion?.type === 'multiple_choice'" class="grid grid-cols-1 gap-4 max-w-5xl">
                                    <div v-for="(opt, idx) in currentQuestion.options" :key="opt.id"
                                         @click="handleAnswerInput(currentQuestion.id, opt.id)"
                                         :class="[
                                            'group p-6 rounded-[1.5rem] border-2 cursor-pointer transition-all duration-300 flex items-center gap-6',
                                            answers[currentQuestion.id] === opt.id ? 'bg-indigo-50 border-indigo-500 shadow-lg shadow-indigo-100' : 'bg-white border-slate-100 hover:border-slate-300 hover:bg-slate-50'
                                         ]">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black transition-all border-2"
                                             :class="answers[currentQuestion.id] === opt.id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-100 text-slate-400 border-slate-200'">
                                            {{ String.fromCharCode(65 + idx) }}
                                        </div>
                                        <span :class="['text-lg font-bold', answers[currentQuestion.id] === opt.id ? 'text-indigo-900' : 'text-slate-600']">
                                            {{ opt.option_text }}
                                        </span>
                                    </div>
                                </div>

                                <!-- 3. TYPE: MULTIPLE RESPONSE (CHECKBOX) -->
                                <div v-if="currentQuestion?.type === 'multiple_choice_multiple'" class="grid grid-cols-1 gap-4 max-w-5xl">
                                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 mb-4 flex items-center gap-3">
                                        <i class="pi pi-info-circle text-amber-600"></i>
                                        <span class="text-xs font-bold text-amber-800">Plusieurs réponses possibles pour cette question.</span>
                                    </div>
                                    <div v-for="opt in currentQuestion.options" :key="opt.id"
                                         @click="() => {
                                             const current = Array.isArray(answers[currentQuestion.id]) ? [...answers[currentQuestion.id]] : [];
                                             const index = current.indexOf(opt.id);
                                             if (index === -1) current.push(opt.id);
                                             else current.splice(index, 1);
                                             handleAnswerInput(currentQuestion.id, current);
                                         }"
                                         :class="[
                                            'group p-6 rounded-[1.5rem] border-2 cursor-pointer transition-all flex items-center gap-6',
                                            answers[currentQuestion.id]?.includes(opt.id) ? 'bg-indigo-50 border-indigo-500 shadow-md' : 'bg-white border-slate-100 hover:border-slate-300'
                                         ]">
                                        <div class="flex items-center justify-center">
                                            <Checkbox :modelValue="answers[currentQuestion.id]" :value="opt.id" class="p-checkbox-lg" />
                                        </div>
                                        <span :class="['text-lg font-bold', answers[currentQuestion.id]?.includes(opt.id) ? 'text-indigo-900' : 'text-slate-700']">
                                            {{ opt.option_text }}
                                        </span>
                                    </div>
                                </div>

                                <!-- 4. TYPE: SHORT ANSWER (Saisie texte) -->
                                <div v-if="currentQuestion?.type === 'short_answer'" class="max-w-2xl">
                                    <div class="space-y-4">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-4">Votre Réponse</label>
                                        <InputText
                                            v-model="answers[currentQuestion.id]"
                                            class="w-full p-6 text-xl font-medium border-2 border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 bg-white transition-all shadow-sm placeholder-slate-400"
                                            placeholder="Saisissez votre réponse ici..."
                                            @input="handleAnswerInput(currentQuestion.id, $event.target.value)"
                                        />
                                        <p class="text-xs text-slate-400 italic px-4">Attention à l'orthographe et à la casse (Majuscules/Minuscules) si spécifié.</p>
                                    </div>
                                </div>

                                <!-- 5. TYPE: MATCHING (APPARIEMENT) -->
                                <div v-if="currentQuestion?.type === 'matching'" class="space-y-4 max-w-5xl">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div v-for="leftItem in currentQuestion.pairs.left" :key="leftItem.id"
                                             class="flex flex-col md:flex-row items-stretch md:items-center gap-4 bg-white p-4 rounded-3xl border border-slate-200 hover:border-indigo-300 transition-colors">

                                            <!-- Élément fixe (Gauche) -->
                                            <div class="flex-1 flex items-center p-6 bg-slate-50 rounded-2xl border border-slate-100">
                                                <span class="font-black text-slate-700">{{ leftItem.text }}</span>
                                            </div>

                                            <!-- Séparateur -->
                                            <div class="hidden md:flex items-center justify-center text-slate-300">
                                                <i class="pi pi-link text-xl"></i>
                                            </div>

                                            <!-- Sélecteur (Droite) -->
                                            <div class="flex-1">
                                                <Dropdown
                                                    v-model="answers[currentQuestion.id][leftItem.id]"
                                                    :options="currentQuestion.pairs.right"
                                                    optionLabel="text"
                                                    optionValue="id"
                                                    placeholder="Associer à..."
                                                    class="w-full rounded-2xl p-4 border-2 border-slate-100 hover:border-indigo-200 transition-all"
                                                    @change="saveToLocalBackup"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 6. TYPE: ORDERING (ORDONNANCEMENT) -->
                                <div v-if="currentQuestion?.type === 'ordering'" class="space-y-6 max-w-2xl mx-auto text-center">
                                    <div class="bg-indigo-50 p-4 rounded-2xl mb-6 inline-flex items-center gap-2">
                                        <i class="pi pi-sort-alt text-indigo-600"></i>
                                        <span class="text-xs font-black text-indigo-700 uppercase">Faites glisser les éléments pour les ordonner</span>
                                    </div>

                                    <OrderList v-model="answers[currentQuestion.id]" listStyle="height:auto" @reorder="saveToLocalBackup" class="custom-order-list">
                                        <template #item="slotProps">
                                            <div class="flex items-center p-6 bg-white border-2 border-slate-100 rounded-2xl gap-6 shadow-sm hover:border-indigo-400 transition-all cursor-move mb-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-indigo-100">
                                                    <i class="pi pi-bars"></i>
                                                </div>
                                                <span class="font-bold text-slate-700 text-lg">{{ slotProps.item.option_text }}</span>
                                            </div>
                                        </template>
                                    </OrderList>
                                </div>

                                <!-- 7. TYPE: ESSAY (RÉDACTION) -->
                                <div v-if="currentQuestion?.type === 'essay'" class="space-y-4">
                                    <div class="flex justify-between items-center px-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Zone de rédaction libre</label>
                                        <Tag :value="`${answers[currentQuestion.id]?.split(/\s+/).filter(Boolean).length || 0} mots`" severity="info" />
                                    </div>
                                    <Textarea
                                        v-model="answers[currentQuestion.id]"
                                        rows="12"
                                        class="w-full p-10 text-xl font-medium border-2 border-slate-100 rounded-[1.5rem] focus:border-indigo-500 transition-all bg-slate-50/50 shadow-inner"
                                        placeholder="Rédigez votre réponse de manière détaillée ici..."
                                        @input="handleAnswerInput(currentQuestion.id, $event.target.value)"
                                    />
                                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-100 flex items-center gap-3">
                                        <i class="pi pi-cloud-upload text-blue-500"></i>
                                        <span class="text-[10px] font-bold text-blue-700 uppercase">Sauvegarde en temps réel activée sur le cloud Subnet</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- PIED DE PAGE : NAVIGATION                  -->
                        <!-- ========================================== -->
                        <div class="px-10 py-10 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <Button label="Question Précédente" icon="pi pi-arrow-left" text size="large"
                                    class="font-black text-slate-500 hover:text-indigo-600 p-4 rounded-2xl"
                                    @click="prevQuestion" :disabled="currentIndex === 0" />

                            <div class="flex items-center gap-6">
                                <div class="hidden md:flex flex-col items-end">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter leading-none mb-1">Status Sync</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                        <span class="text-[10px] font-bold text-slate-600">Copie Sécurisée</span>
                                    </div>
                                </div>

                                <Button :label="currentIndex === questions.length - 1 ? 'Récapitulatif Final' : 'Continuer'"
                                        :icon="currentIndex === questions.length - 1 ? 'pi pi-verified' : 'pi pi-arrow-right'"
                                        iconPos="right" size="large"
                                        :severity="currentIndex === questions.length - 1 ? 'success' : 'primary'"
                                        class="px-12 py-5 text-xl font-black rounded-2xl shadow-xl shadow-indigo-100 transition-transform active:scale-95"
                                        @click="nextQuestion" />
                            </div>
                        </div>
                    </div>

                    <!-- LOG DE SÉCURITÉ IA (VISIBLE SI INCIDENTS) -->
                    <div v-if="proctoringLogs.length > 0" class="bg-white rounded-[1.5rem] p-8 border-2 border-rose-100 shadow-lg animate-fade-in">
                        <div class="flex items-center justify-between mb-6">
                            <h5 class="text-[10px] font-black text-rose-500 uppercase tracking-widest flex items-center gap-2">
                                <i class="pi pi-shield"></i> Surveillance Intégrité Académique
                            </h5>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Événements récents</span>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(log, i) in proctoringLogs.slice(0, 2)" :key="i"
                                 class="flex items-center justify-between p-4 bg-rose-50/50 rounded-xl border border-rose-100">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-exclamation-triangle text-rose-500"></i>
                                    <span class="text-xs font-bold text-rose-800">{{ log.event }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-rose-400">{{ new Date(log.timestamp).toLocaleTimeString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3 : RÉVISION FINALE -->
            <div v-if="currentStep === 'review'" class="max-w-6xl mx-auto animate-fade-in py-10">
                <div class="bg-white rounded-[3rem] shadow-2xl overflow-hidden border border-slate-200">
                    <div class="p-12 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/50">
                        <div>
                            <h2 class="text-4xl font-black text-slate-900 tracking-tighter">Révision finale</h2>
                            <p class="text-slate-500 font-medium">Parcourez vos réponses avant la clôture définitive de la session.</p>
                        </div>
                        <div class="flex gap-4">
                            <Button label="Retour au Test" icon="pi pi-pencil" text class="font-black" @click="currentStep = 'testing'" />
                            <Button label="Soumettre ma copie" icon="pi pi-send" severity="success" class="font-black px-8 py-4 rounded-2xl shadow-lg shadow-emerald-100" @click="confirmSubmission" />
                        </div>
                    </div>

                    <div class="p-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="(q, idx) in questions" :key="q.id"
                                @click="jumpToQuestion(idx)"
                                class="p-6 rounded-3xl border-2 transition-all cursor-pointer hover:scale-[1.02] active:scale-95 group"
                                :class="[
                                    isAnswered(q.id) ? 'bg-white border-slate-100 hover:border-indigo-500' : 'bg-rose-50 border-rose-100 hover:border-rose-400',
                                    flaggedQuestions.includes(q.id) ? 'ring-2 ring-amber-400 ring-offset-4' : ''
                                ]">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                                         :class="isAnswered(q.id) ? 'bg-slate-100 text-slate-500' : 'bg-rose-200 text-rose-700'">
                                        {{ idx + 1 }}
                                    </div>
                                    <i v-if="flaggedQuestions.includes(q.id)" class="pi pi-flag-fill text-amber-500"></i>
                                    <i :class="['pi', isAnswered(q.id) ? 'pi-check-circle text-emerald-500' : 'pi-exclamation-circle text-rose-500']"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-800 line-clamp-2 mb-2 group-hover:text-indigo-600 transition-colors">
                                    {{ q.question_text }}
                                </p>
                                <div class="text-[10px] font-black uppercase text-slate-400">
                                    {{ isAnswered(q.id) ? 'Réponse enregistrée' : 'Non répondu' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-20 p-12 bg-slate-900 rounded-[3rem] text-white flex flex-col md:flex-row items-center gap-12 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                            <div class="flex-1 text-center md:text-left relative z-10">
                                <h3 class="text-3xl font-black mb-4 tracking-tight">Prêt pour la certification ?</h3>
                                <p class="text-slate-400 leading-relaxed max-w-xl">
                                    Une fois validée, votre copie sera instantanément analysée par le module académique Subnet.
                                    Le processus est irréversible.
                                </p>
                            </div>
                            <Button label="Envoyer maintenant" icon="pi pi-check-circle" size="large" severity="success" class="px-12 py-6 text-2xl font-black rounded-3xl shadow-2xl relative z-10" @click="confirmSubmission" />
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- ========================================================================== -->
        <!-- OVERLAYS D'OUTILS                                                          -->
        <!-- ========================================================================== -->

        <!-- Sidebar Mobile -->
        <Sidebar v-model:visible="sidebarVisible" position="right" class="w-full sm:w-[450px]">
            <template #header>
                <div class="font-black text-2xl tracking-tighter uppercase">Plan d'épreuve</div>
            </template>
            <div class="grid grid-cols-4 gap-3 p-4">
                <button v-for="(q, idx) in questions" :key="q.id" @click="jumpToQuestion(idx)"
                    :class="['h-14 rounded-2xl font-black transition-all border-2', currentIndex === idx ? 'bg-indigo-600 text-white' : 'bg-slate-50']">
                    {{ idx + 1 }}
                </button>
            </div>
        </Sidebar>

        <!-- Brouillon / Notepad -->
        <Dialog v-model:visible="showNotepad" header="Brouillon Personnel (Non noté)" :style="{width: '600px'}" modal class="rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-2">
                <Textarea v-model="notepadContent" rows="15" class="w-full font-mono text-sm p-6 bg-slate-50 border-none focus:ring-0 rounded-2xl" placeholder="Notez vos idées ici..." />
                <div class="mt-4 flex justify-end">
                    <Button label="Fermer" icon="pi pi-times" text class="font-bold" @click="showNotepad = false" />
                </div>
            </div>
        </Dialog>

        <!-- Calculatrice (Simplifiée) -->
        <Dialog v-model:visible="calculatorVisible" header="Calculatrice Scientifique" :style="{width: '320px'}" modal class="rounded-3xl overflow-hidden shadow-2xl" position="topright">
             <div class="p-4 bg-slate-900 rounded-2xl">
                 <div class="h-20 bg-slate-800 rounded-xl mb-4 flex items-center justify-end px-6 text-white text-3xl font-mono overflow-hidden">
                     {{ calculatorDisplay }}
                 </div>
                 <div class="grid grid-cols-4 gap-2">
                     <button v-for="n in ['7','8','9','/','4','5','6','*','1','2','3','-','0','.','=','+']" :key="n"
                             @click="handleCalculatorInput(n)"
                             class="h-12 rounded-lg bg-slate-700 text-white font-bold hover:bg-indigo-500 transition-colors">
                         {{ n }}
                     </button>
                     <button @click="clearCalculator" class="col-span-4 h-12 rounded-lg bg-rose-500 text-white font-bold uppercase text-xs">Effacer</button>
                 </div>
             </div>
        </Dialog>

    </div>
</template>

<style scoped>
/* ANIMATIONS */
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* CUSTOM COMPONENTS OVERRIDES */
:deep(.p-orderlist .p-orderlist-list) {
    padding: 0;
    background: transparent;
    border: none;
    gap: 1rem;
    display: flex;
    flex-direction: column;
}

:deep(.p-orderlist-controls) {
    display: none; /* On utilise uniquement le drag & drop */
}

:deep(.p-dropdown) {
    border-radius: 1.25rem;
    border: 2px solid #f1f5f9;
}

:deep(.p-checkbox .p-checkbox-box) {
    border-radius: 0.75rem;
    width: 2rem;
    height: 2rem;
    border-width: 2px;
}

:deep(.p-checkbox.p-checkbox-checked .p-checkbox-box) {
    background: #10b981;
    border-color: #10b981;
}

/* TIMELINE CUSTOM */
.customized-timeline :deep(.p-timeline-event-marker) {
    border: 2px solid #6366f1;
    background: #fff;
}

/* FONT MONO POUR LES CHIFFRES */
.tabular-nums {
    font-variant-numeric: tabular-nums;
}

/* BACKGROUND PATTERNS */
.pattern-dots {
    background-image: radial-gradient(#ffffff 1px, transparent 1px);
    background-size: 20px 20px;
}

/* SCROLLBARS */
::-webkit-scrollbar {
    width: 8px;
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
</style>
