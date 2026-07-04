<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import cloneDeep from 'lodash/cloneDeep';

// Composants Externes
import ForumSection from './ForumSection.vue';
import RadioButton from 'primevue/radiobutton';

// ======================================================================================
// PRIMEVUE COMPONENTS (PrimeVue v4 API)
// ======================================================================================
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Drawer from 'primevue/drawer';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import ConfirmDialog from 'primevue/confirmdialog';
import PickList from 'primevue/picklist';
import Timeline from 'primevue/timeline';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import Tooltip from 'primevue/tooltip';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';
import Message from 'primevue/message';

// Directives
const vTooltip = Tooltip;

const toast = useToast();
const confirm = useConfirm();

// ======================================================================================
// PROPS INERTIA (Data-Driven)
// ======================================================================================
const props = defineProps({
    currentUser: { type: Object, required: true },
    activeSection: { type: String, default: 'forum' },
    threads: { type: Object, default: () => ({ data: [], total: 0 }) },
    categories: { type: Array, default: () => [] },
    contacts: { type: Array, default: () => [] },
    chatGroups: { type: Array, default: () => [] },
    messages: { type: Array, default: () => [] },
    activeConversationId: { type: [Number, String], default: null },
    filters: { type: Object, default: () => ({}) },
    activeCourses: { type: Array, default: () => [] },
    assignments: { type: Array, default: () => [] },
    instructorAssignments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ unread_messages: 0, pending_assignments: 0, needs_grading: 0 }) }
});

const isInstructor = computed(() => ['instructor', 'admin'].includes(props.currentUser?.role));

// ======================================================================================
// ÉTATS GLOBAUX DU HUB
// ======================================================================================
const isAppLoading = ref(false);
const activeHubSection = ref(props.activeSection || 'forum');
const activeChatTab = ref('contacts');
const chatSearchQuery = ref('');

// --- États Chat ---
const createGroupDialog = ref(false);
const currentChatSession = ref(null);
const chatScrollContainer = ref(null);
const pickListContacts = ref([[], []]);

// --- États Académiques ---
const submitAssignmentDialog = ref(false);
const currentAssignment = ref(null);
const createAssignmentDialog = ref(false);
const createQuizDialog = ref(false);
const gradeSubmissionDialog = ref(false);
const currentSubmissionToGrade = ref(null);
const takeQuizDialog = ref(false);
const currentQuizToTake = ref(null);

// ======================================================================================
// FORMULAIRES REACTIFS (USEFORM INERTIA)
// ======================================================================================
const chatForm = useForm({ receiver_id: null, group_id: null, message: '', attachment: null });
const groupChatForm = useForm({ name: '', members: [], cover: null });
const assignmentSubmitForm = useForm({ assignment_id: null, submitted_file: null, student_comment: '' });
const assignmentCreateForm = useForm({ title: '', course_id: null, instructions: '', due_date: null, max_score: 100, allow_late: false });
const quizCreateForm = useForm({ title: '', course_id: null, description: '', time_limit_minutes: 30, passing_score: 60, questions: [] });
const gradeForm = useForm({ submission_id: null, score: null, feedback: '' });
const quizSubmissionForm = useForm({
    assignment_id: null,
    answers: {} // { question_id: option_id }
});

// ======================================================================================
// LOGIQUE : MESSAGERIE & GROUPES
// ======================================================================================
const filteredContacts = computed(() => {
    if (!chatSearchQuery.value) return props.contacts;
    return props.contacts.filter(c => c.name.toLowerCase().includes(chatSearchQuery.value.toLowerCase()));
});

const filteredGroups = computed(() => {
    if (!chatSearchQuery.value) return props.chatGroups;
    return props.chatGroups.filter(g => g.name.toLowerCase().includes(chatSearchQuery.value.toLowerCase()));
});

const selectChatSession = (entity, type = 'contact') => {
    currentChatSession.value = { ...entity, sessionType: type };
    chatForm.reset();
    chatForm.clearErrors();
    chatForm.receiver_id = type === 'contact' ? entity.id : null;
    chatForm.group_id = type === 'group' ? entity.id : null;

    isAppLoading.value = true;
    router.reload({
        only: ['messages'],
        data: { chat_id: entity.id, chat_type: type },
        onSuccess: () => { isAppLoading.value = false; scrollToBottom(); },
        onError: () => {
            isAppLoading.value = false;
            toast.add({ severity: 'error', summary: 'Erreur Réseau', detail: 'Impossible de charger l\'historique.', life: 4000 });
        }
    });
};

const sendChatMessage = () => {
    if (!chatForm.message.trim() && !chatForm.attachment) return;
    chatForm.post(route('social.messages.store'), {
        preserveScroll: true,
        onSuccess: () => { chatForm.reset('message', 'attachment'); scrollToBottom(); }
    });
};

const handleChatFileUpload = (event) => { chatForm.attachment = event.files[0]; };

const scrollToBottom = async () => {
    await nextTick();
    if (chatScrollContainer.value) {
        chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
    }
};

const openCreateGroupDialog = () => {
    groupChatForm.reset();
    pickListContacts.value = [cloneDeep(props.contacts), []];
    createGroupDialog.value = true;
};

const submitGroupChat = () => {
    groupChatForm.members = pickListContacts.value[1].map(c => c.id);
    groupChatForm.post(route('social.groups.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Groupe créé', detail: 'Votre espace collaboratif est prêt.' });
            createGroupDialog.value = false;
        }
    });
};

// ======================================================================================
// LOGIQUE : DEVOIRS & ÉVALUATIONS
// ======================================================================================
const openAssignmentSubmission = (assignment) => {
    currentAssignment.value = cloneDeep(assignment);
    assignmentSubmitForm.reset();
    assignmentSubmitForm.assignment_id = assignment.id;
    submitAssignmentDialog.value = true;
};

const onAssignmentSelect = (event) => { assignmentSubmitForm.submitted_file = event.files[0]; };

const submitStudentAssignment = () => {
    assignmentSubmitForm.post(route('assignments.submit'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Travail remis', detail: 'Votre devoir a été envoyé au professeur.' });
            submitAssignmentDialog.value = false;
        }
    });
};

const openCreateAssignment = () => { assignmentCreateForm.reset(); createAssignmentDialog.value = true; };

const saveInstructorAssignment = () => {
    assignmentCreateForm.post(route('instructor.assignments.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Publié', detail: 'Le devoir est disponible pour les étudiants.' });
            createAssignmentDialog.value = false;
        }
    });
};

const openCreateQuiz = () => {
    quizCreateForm.reset();
    quizCreateForm.questions = [];
    addQuizQuestion();
    createQuizDialog.value = true;
};

const addQuizQuestion = () => {
    quizCreateForm.questions.push({
        id: Date.now(), text: '', points: 10,
        options: [{ text: '', is_correct: true }, { text: '', is_correct: false }]
    });
};

const removeQuizQuestion = (index) => {
    confirm.require({
        message: 'Supprimer cette question ?', header: 'Confirmation', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger',
        accept: () => quizCreateForm.questions.splice(index, 1)
    });
};

const addQuestionOption = (qIndex) => { quizCreateForm.questions[qIndex].options.push({ text: '', is_correct: false }); };
const removeQuestionOption = (qIndex, optIndex) => { quizCreateForm.questions[qIndex].options.splice(optIndex, 1); };
const setCorrectOption = (qIndex, optIndex) => {
    quizCreateForm.questions[qIndex].options.forEach((opt, idx) => { opt.is_correct = (idx === optIndex); });
};

const saveInstructorQuiz = () => {
    if (quizCreateForm.questions.some(q => !q.text.trim() || q.options.some(o => !o.text.trim()))) {
        toast.add({ severity: 'error', summary: 'Formulaire invalide', detail: 'Veuillez remplir tous les champs de questions et options.' });
        return;
    }
    quizCreateForm.post(route('instructor.quizzes.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Quiz Généré', detail: 'L\'interrogation a été créée.' });
            createQuizDialog.value = false;
        }
    });
};

const openGradeDialog = (submission) => {
    currentSubmissionToGrade.value = cloneDeep(submission);
    gradeForm.reset();
    gradeForm.submission_id = submission.id;
    gradeSubmissionDialog.value = true;
};

const submitGrade = () => {
    gradeForm.post(route('instructor.grade.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Corrigé', detail: 'La note a été attribuée avec succès.' });
            gradeSubmissionDialog.value = false;
        }
    });
};

const openTakeQuiz = (assignment) => {
    currentQuizToTake.value = cloneDeep(assignment);
    quizSubmissionForm.reset();
    quizSubmissionForm.assignment_id = assignment.id;
    const initialAnswers = {};
    if (assignment.questions && Array.isArray(assignment.questions)) {
        assignment.questions.forEach(q => {
            initialAnswers[q.id] = null;
        });
    }
    quizSubmissionForm.answers = initialAnswers;
    takeQuizDialog.value = true;
};

const submitStudentQuiz = () => {
    const allAnswered = Object.values(quizSubmissionForm.answers).every(ans => ans !== null);
    if (!allAnswered) {
        toast.add({ severity: 'warn', summary: 'Incomplet', detail: 'Veuillez répondre à toutes les questions avant de soumettre.', life: 4000 });
        return;
    }

    quizSubmissionForm.post(route('assignments.quiz.submit'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Quiz Soumis', detail: 'Vos réponses ont été enregistrées et seront notées.' });
            takeQuizDialog.value = false;
            router.reload({ only: ['assignments'] });
        }
    });
};

// ======================================================================================
// UTILITAIRES
// ======================================================================================
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(dateString));
};
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'U';
const isImageAttachment = (fileName) => {
    if (!fileName) return false;
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(fileName.split('.').pop().toLowerCase());
};
const getAttachmentIcon = (fileName) => {
    if (!fileName) return 'pi pi-file';
    const ext = fileName.split('.').pop().toLowerCase();
    if (ext === 'pdf') return 'pi pi-file-pdf text-rose-500';
    if (['zip', 'rar'].includes(ext)) return 'pi pi-box text-amber-500';
    if (isImageAttachment(fileName)) return 'pi pi-image text-indigo-500';
    return 'pi pi-file text-slate-500';
};
const handleCategoryFilter = (catId) => {
    activeHubSection.value = 'forum';
    router.get(route('social.forum.index'), { ...props.filters, category: catId }, { preserveState: true, preserveScroll: true });
};

onMounted(() => { if (activeHubSection.value === 'chat') scrollToBottom(); });
watch(activeHubSection, (newVal) => { if (newVal === 'chat') scrollToBottom(); });
</script>

<template>
    <AppLayout>
        <Head title="Hub Social & Académique" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="min-h-screen bg-[#f4f7f9] flex flex-col font-sans pb-10">

            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 text-white pt-12 pb-24 px-6 lg:px-12 relative overflow-hidden flex-shrink-0 shadow-xl border-b border-indigo-900/50">
                <div class="absolute -right-32 -top-32 w-[40rem] h-[40rem] bg-indigo-500/20 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute -left-20 bottom-0 w-[30rem] h-[30rem] bg-blue-500/10 rounded-full blur-[80px] pointer-events-none"></div>

                <div class="relative z-20 max-w-[1600px] 2xl:max-w-[95%] mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-white/10 backdrop-blur-xl rounded-2xl flex items-center justify-center border border-white/20 shadow-[0_8px_30px_rgb(0,0,0,0.2)]">
                            <i class="pi pi-globe text-3xl text-indigo-300"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-black tracking-tight text-white mb-1">Hub Communautaire</h1>
                            <p class="text-indigo-200/90 font-medium text-sm max-w-lg leading-relaxed">
                                {{ isInstructor ? "Espace Professeur : Gérez vos évaluations, animez vos groupes et interagissez avec les étudiants." : "Espace Étudiant : Discutez, collaborez sur le forum et remettez vos devoirs." }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white/5 backdrop-blur-xl border border-white/10 px-6 py-4 rounded-2xl flex items-center gap-5 hover:bg-white/10 transition-all cursor-pointer shadow-lg group" @click="activeHubSection = 'chat'">
                            <div class="w-12 h-12 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform"><i class="pi pi-envelope text-xl"></i></div>
                            <div>
                                <p class="text-[10px] uppercase font-black text-indigo-200 tracking-widest mb-0.5">Messages</p>
                                <p class="text-2xl font-black leading-none text-white">{{ props.stats.unread_messages }}</p>
                            </div>
                        </div>
                        <div v-if="!isInstructor" class="bg-white/5 backdrop-blur-xl border border-white/10 px-6 py-4 rounded-2xl flex items-center gap-5 hover:bg-white/10 transition-all cursor-pointer shadow-lg group" @click="activeHubSection = 'assignments'">
                            <div class="w-12 h-12 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform"><i class="pi pi-file-edit text-xl"></i></div>
                            <div>
                                <p class="text-[10px] uppercase font-black text-indigo-200 tracking-widest mb-0.5">Devoirs Dûs</p>
                                <p class="text-2xl font-black leading-none text-white">{{ props.stats.pending_assignments }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 max-w-[1600px] 2xl:max-w-[95%] mx-auto w-full px-4 lg:px-8 -mt-12 relative z-30 flex flex-col lg:flex-row gap-8 items-start">

                <aside class="w-full lg:w-80 flex-shrink-0 sticky top-24">
                    <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 p-5">

                        <div class="flex items-center gap-4 p-4 bg-slate-50/80 rounded-2xl border border-slate-100 mb-6 shadow-sm">
                            <Avatar :image="currentUser?.avatar" :label="!currentUser?.avatar ? getInitials(currentUser?.name) : ''" shape="circle" size="large" class="bg-indigo-600 text-white font-black shadow-md border-2 border-white" />
                            <div class="flex flex-col overflow-hidden">
                                <span class="font-black text-slate-800 text-sm truncate">{{ currentUser?.name }}</span>
                                <Tag :value="isInstructor ? 'INSTRUCTEUR' : 'APPRENANT'" :severity="isInstructor ? 'info' : 'success'" class="text-[9px] font-black tracking-widest mt-1 w-max px-2 py-0.5 shadow-sm" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <button @click="activeHubSection = 'forum'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'forum' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-comments text-lg" :class="activeHubSection === 'forum' ? 'text-indigo-200' : 'text-slate-400'"></i> Espace Forum</div>
                            </button>
                            <button @click="activeHubSection = 'chat'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'chat' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-send text-lg" :class="activeHubSection === 'chat' ? 'text-indigo-200' : 'text-slate-400'"></i> Messagerie</div>
                                <Badge v-if="props.stats.unread_messages > 0" :value="props.stats.unread_messages" severity="danger" />
                            </button>
                            <button @click="activeHubSection = 'assignments'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'assignments' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-file-edit text-lg" :class="activeHubSection === 'assignments' ? 'text-indigo-200' : 'text-slate-400'"></i> {{ isInstructor ? 'Évaluations' : 'Mes Devoirs' }}</div>
                            </button>
                            <button v-if="!isInstructor" @click="activeHubSection = 'courses'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'courses' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-video text-lg" :class="activeHubSection === 'courses' ? 'text-indigo-200' : 'text-slate-400'"></i> Mes Cours</div>
                            </button>
                        </div>

                        <div class="mt-8 border-t border-slate-100 pt-6">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-4 mb-3">Filtres Forum</h3>
                            <div class="flex flex-col gap-1 px-1">
                                <button @click="handleCategoryFilter(null)" :class="['flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all duration-200', !props.filters.category ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']">
                                    <span class="flex items-center gap-3"><i class="pi pi-objects-column"></i> Toutes les catégories</span>
                                </button>
                                <button v-for="cat in categories" :key="cat.id" @click="handleCategoryFilter(cat.id)" :class="['flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all duration-200', props.filters.category === cat.id ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']">
                                    <span class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: cat.color || '#6366f1' }"></span>
                                        {{ cat.name }}
                                    </span>
                                    <Badge :value="cat.threads_count" severity="secondary" class="bg-white border border-slate-200 text-slate-600 text-[10px] font-black shadow-sm"></Badge>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="flex-1 min-w-0 w-full flex flex-col gap-6 relative">

                    <!-- FORUM (Composant Externe) -->
                    <ForumSection v-show="activeHubSection === 'forum'" class="w-full" :threads="threads" :categories="categories" :currentUser="currentUser" :isInstructor="isInstructor" />

                    <!-- MESSAGERIE & CHAT -->
                    <div v-show="activeHubSection === 'chat'" class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 overflow-hidden h-[calc(100vh-14rem)] min-h-[600px] max-h-[900px] flex flex-col md:flex-row animate-fade-in relative">

                        <div v-if="isAppLoading" class="absolute inset-0 bg-white/70 backdrop-blur-md z-30 flex items-center justify-center">
                            <div class="bg-white p-6 rounded-3xl shadow-2xl flex flex-col items-center gap-4 border border-slate-100">
                                <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
                                <span class="font-black text-slate-700 text-sm">Synchronisation...</span>
                            </div>
                        </div>

                        <div class="w-full md:w-96 border-r border-slate-100 flex flex-col bg-slate-50/50 h-1/2 md:h-full flex-shrink-0">
                            <div class="p-5 md:p-6 bg-white border-b border-slate-100 shadow-sm z-10">
                                <div class="flex justify-between items-center mb-5">
                                    <h2 class="text-xl font-black text-slate-800">Messagerie</h2>
                                    <Button icon="pi pi-users" class="p-button-rounded p-button-secondary p-button-text bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600" v-tooltip.top="'Créer un groupe'" @click="openCreateGroupDialog" />
                                </div>
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search text-slate-400" />
                                    <InputText v-model="chatSearchQuery" placeholder="Rechercher un contact..." class="w-full rounded-2xl bg-slate-50 border-slate-200 focus:border-indigo-500 transition-colors font-medium" />
                                </IconField>
                            </div>

                            <div class="flex border-b border-slate-200 bg-white">
                                <button @click="activeChatTab = 'contacts'" class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-colors" :class="activeChatTab === 'contacts' ? 'text-indigo-700 border-b-2 border-indigo-600 bg-indigo-50/50' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'">Contacts</button>
                                <button @click="activeChatTab = 'groups'" class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-colors" :class="activeChatTab === 'groups' ? 'text-indigo-700 border-b-2 border-indigo-600 bg-indigo-50/50' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'">Groupes</button>
                            </div>

                            <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-1">
                                <template v-if="activeChatTab === 'contacts'">
                                    <div v-for="contact in filteredContacts" :key="contact.id" @click="selectChatSession(contact, 'contact')" class="p-3 md:p-4 rounded-2xl flex items-center gap-4 cursor-pointer transition-all duration-200 border border-transparent" :class="currentChatSession?.id === contact.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200/50' : 'hover:bg-white hover:border-slate-200 hover:shadow-sm text-slate-700'">
                                        <div class="relative shrink-0">
                                            <Avatar :image="contact.avatar_path" :label="!contact.avatar_path ? getInitials(contact.name) : ''" shape="circle" size="large" class="shadow-sm font-black text-lg" :class="currentChatSession?.id === contact.id ? 'bg-white/20 text-white border border-white/30' : 'bg-slate-100 text-indigo-600 border border-slate-200'" />
                                            <span class="absolute bottom-0 right-0 w-3.5 h-3.5 border-2 rounded-full" :class="[contact.is_online ? 'bg-emerald-500' : 'bg-slate-300', currentChatSession?.id === contact.id ? 'border-indigo-600' : 'border-white']"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-sm truncate" :class="currentChatSession?.id === contact.id ? 'text-white' : 'text-slate-800'">{{ contact.name }}</h4>
                                            <p class="text-[10px] uppercase font-bold tracking-widest mt-1" :class="currentChatSession?.id === contact.id ? 'text-indigo-200' : 'text-slate-400'">{{ contact.role }}</p>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div v-for="group in filteredGroups" :key="group.id" @click="selectChatSession(group, 'group')" class="p-3 md:p-4 rounded-2xl flex items-center gap-4 cursor-pointer transition-all duration-200 border border-transparent" :class="currentChatSession?.id === group.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200/50' : 'hover:bg-white hover:border-slate-200 hover:shadow-sm text-slate-700'">
                                        <Avatar icon="pi pi-users" shape="circle" size="large" class="shadow-sm shrink-0" :class="currentChatSession?.id === group.id ? 'bg-white/20 text-white border border-white/30' : 'bg-slate-100 text-slate-500 border border-slate-200'" />
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-sm truncate" :class="currentChatSession?.id === group.id ? 'text-white' : 'text-slate-800'">{{ group.name }}</h4>
                                            <p class="text-[10px] uppercase font-bold tracking-widest mt-1" :class="currentChatSession?.id === group.id ? 'text-indigo-200' : 'text-slate-400'">{{ group.members_count }} membres</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col bg-white/50 relative h-1/2 md:h-full">
                            <template v-if="currentChatSession">
                                <div class="p-4 md:p-6 border-b border-slate-100 bg-white/90 backdrop-blur-xl shadow-sm z-20 flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <Avatar :image="currentChatSession.avatar_path" :label="getInitials(currentChatSession.name)" shape="circle" size="large" class="bg-indigo-50 text-indigo-700 font-black border border-indigo-100 shadow-sm" />
                                        <div>
                                            <h3 class="font-black text-slate-800 text-lg leading-tight">{{ currentChatSession.name }}</h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span v-if="currentChatSession.sessionType === 'contact'" class="w-2 h-2 rounded-full shadow-sm" :class="currentChatSession.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                                    {{ currentChatSession.sessionType === 'group' ? 'Groupe de discussion' : (currentChatSession.is_online ? 'En Ligne' : 'Hors Ligne') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <Button icon="pi pi-ellipsis-v" text rounded class="text-slate-400 hover:bg-slate-50 hover:text-slate-800" />
                                </div>

                                <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50/50" ref="chatScrollContainer">
                                    <div v-for="msg in messages" :key="msg.id" class="flex flex-col animate-fade-in" :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'">
                                        <div class="max-w-[85%] lg:max-w-[70%] flex flex-col gap-1.5">
                                            <div :class="[msg.sender_id === currentUser.id ? 'bg-indigo-600 text-white rounded-l-2xl rounded-tr-2xl shadow-md shadow-indigo-200' : 'bg-white text-slate-800 rounded-r-2xl rounded-tl-2xl border border-slate-200 shadow-sm', 'p-4 md:p-5']">
                                                <p v-if="msg.content" class="text-[15px] leading-relaxed whitespace-pre-line font-medium">{{ msg.content }}</p>

                                                <a v-if="msg.attachment_path" :href="msg.attachment_path" target="_blank" class="block rounded-xl overflow-hidden transition-transform hover:scale-[1.02] shadow-sm" :class="{'mt-4': msg.content}">
                                                    <img v-if="isImageAttachment(msg.attachment_name)" :src="msg.attachment_path" class="w-full h-auto max-h-72 object-cover border border-black/5 rounded-xl" alt="Pièce jointe">
                                                    <div v-else class="p-3.5 flex items-center gap-3 rounded-xl border" :class="msg.sender_id === currentUser.id ? 'bg-indigo-700/50 border-indigo-500/50' : 'bg-slate-50 border-slate-200'">
                                                        <i :class="getAttachmentIcon(msg.attachment_name)" class="text-2xl drop-shadow-sm"></i>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-bold truncate" :class="msg.sender_id === currentUser.id ? 'text-indigo-50' : 'text-slate-700'">{{ msg.attachment_name }}</p>
                                                        </div>
                                                        <i class="pi pi-download text-sm" :class="msg.sender_id === currentUser.id ? 'text-indigo-200' : 'text-slate-400'"></i>
                                                    </div>
                                                </a>
                                            </div>
                                            <span class="text-[10px] font-black text-slate-400 px-2 tracking-wide" :class="msg.sender_id === currentUser.id ? 'text-right' : 'text-left'">
                                                {{ formatDate(msg.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 md:p-6 bg-white border-t border-slate-100 z-20 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                                    <div v-if="chatForm.attachment" class="mb-4 mx-2 p-3 bg-indigo-50 rounded-xl flex items-center justify-between border border-indigo-100 shadow-sm animate-fade-in">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-indigo-600"><i class="pi pi-paperclip"></i></div>
                                            <span class="text-xs font-black text-indigo-800 truncate">{{ chatForm.attachment.name }}</span>
                                        </div>
                                        <Button icon="pi pi-times" text rounded size="small" severity="danger" class="hover:bg-rose-100" @click="chatForm.attachment = null" />
                                    </div>

                                    <div class="flex items-end gap-3 bg-slate-50 p-2.5 rounded-3xl border border-slate-200 focus-within:border-indigo-400 focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:bg-white transition-all shadow-sm">
                                        <FileUpload mode="basic" auto customUpload @select="handleChatFileUpload" chooseIcon="pi pi-paperclip" class="p-button-rounded p-button-text text-slate-500 hover:text-indigo-600 hover:bg-slate-100 w-12 h-12 flex-shrink-0" v-tooltip.top="'Joindre un fichier'" />
                                        <Textarea v-model="chatForm.message" autoResize rows="1" placeholder="Écrivez votre message..." class="flex-1 bg-transparent border-none shadow-none focus:ring-0 py-3.5 px-3 text-slate-700 font-medium resize-none max-h-32 custom-scrollbar text-[15px]" @keyup.enter.prevent="sendChatMessage" />
                                        <Button icon="pi pi-send" rounded class="p-button-primary shadow-lg shadow-indigo-200 w-12 h-12 flex-shrink-0" @click="sendChatMessage" :loading="chatForm.processing" :disabled="!chatForm.message.trim() && !chatForm.attachment" />
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex-1 flex flex-col items-center justify-center text-center text-slate-400 bg-slate-50/50 p-8">
                                    <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center border border-slate-200 mb-6 shadow-sm">
                                        <i class="pi pi-comments text-5xl text-indigo-200 transform -scale-x-100"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-700">Vos Conversations</h3>
                                    <p class="text-sm max-w-sm mt-3 text-slate-500 font-medium leading-relaxed">Sélectionnez un contact ou un groupe dans le menu de gauche pour commencer à discuter en temps réel.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- MODULE 3 : DEVOIRS & ÉVALUATIONS -->
                    <div v-show="activeHubSection === 'assignments'" class="animate-fade-in space-y-6">
                        <template v-if="!isInstructor">
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                <div v-if="!assignments.length" class="col-span-full p-20 text-center bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center">
                                    <div class="w-28 h-28 bg-emerald-50 rounded-full flex items-center justify-center mb-6 border-8 border-emerald-100/50">
                                        <i class="pi pi-check text-5xl text-emerald-500"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-800">Tout est en ordre !</h3>
                                    <p class="text-slate-500 mt-2 font-medium">Vous n'avez aucun devoir en attente pour le moment.</p>
                                </div>

                                <div v-for="assignment in assignments" :key="assignment.id" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col relative overflow-hidden group">
                                    <div class="absolute top-0 left-0 w-full h-1.5 transition-colors" :class="assignment.status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500'"></div>
                                    <div class="flex gap-4 mb-5 mt-2">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner border border-slate-100 shrink-0" :class="assignment.type === 'quiz' ? 'bg-amber-50 text-amber-500' : 'bg-indigo-50 text-indigo-600'">
                                            <i class="text-2xl" :class="assignment.type === 'quiz' ? 'pi pi-bolt' : 'pi pi-file-pdf'"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-black text-slate-800 text-lg leading-tight mb-2 group-hover:text-indigo-600 transition-colors truncate">{{ assignment.title }}</h4>
                                            <Tag :value="assignment.course?.title" class="bg-slate-100 text-slate-600 text-[9px] font-black tracking-widest" />
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-4 rounded-2xl text-sm text-slate-600 mb-6 flex-1 line-clamp-3 border border-slate-100 leading-relaxed">{{ assignment.instructions }}</div>

                                    <div class="flex items-center justify-between mt-auto pt-5 border-t border-slate-100">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Échéance</span>
                                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5"><i class="pi pi-calendar text-indigo-500"></i> {{ formatDate(assignment.due_date) }}</span>
                                        </div>

                                        <Button v-if="assignment.status === 'pending'" :label="assignment.type === 'quiz' ? 'Passer' : 'Remettre'" :icon="assignment.type === 'quiz' ? 'pi pi-play' : 'pi pi-upload'" class="p-button-primary font-black rounded-xl px-6 shadow-md shadow-indigo-200" @click="assignment.type === 'quiz' ? openTakeQuiz(assignment) : openAssignmentSubmission(assignment)" />
                                        <div v-else-if="assignment.status === 'graded'" class="bg-emerald-50 text-emerald-700 rounded-xl px-5 py-2.5 font-black border border-emerald-200 flex items-center gap-2 shadow-sm">
                                            <i class="pi pi-check-circle"></i> {{ assignment.score }}/100
                                        </div>
                                        <Tag v-else value="En correction" severity="info" class="rounded-xl px-4 py-2 text-xs font-black shadow-sm" />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 overflow-hidden">
                                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center bg-white gap-4">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-800">Centre d'Évaluations</h3>
                                        <p class="text-sm text-slate-500 mt-1 font-medium">Créez des devoirs, concevez des quiz et notez les copies soumises.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <Button label="Nouveau Devoir" icon="pi pi-plus" class="p-button-primary font-black rounded-xl shadow-md shadow-indigo-200 px-6" @click="openCreateAssignment" />
                                        <Button label="Nouveau Quiz" icon="pi pi-bolt" class="p-button-warning bg-amber-500 border-amber-500 hover:bg-amber-600 font-black rounded-xl shadow-md shadow-amber-200 px-6" @click="openCreateQuiz" />
                                    </div>
                                </div>

                                <DataTable :value="instructorAssignments" :paginator="true" :rows="10" class="p-datatable-lg border-none" dataKey="id" stripedRows hoverableRows>
                                    <template #empty>
                                        <div class="p-20 text-center text-slate-500 flex flex-col items-center">
                                            <i class="pi pi-folder-open text-6xl text-slate-200 mb-6"></i>
                                            <span class="font-black text-xl text-slate-700">Aucune évaluation configurée</span>
                                            <p class="text-sm font-medium mt-2 max-w-sm">Commencez par créer un devoir ou un quiz pour vos étudiants.</p>
                                        </div>
                                    </template>
                                    <Column field="title" header="Évaluation" style="min-width: 25rem">
                                        <template #body="{ data }">
                                            <div class="flex items-center gap-5 py-3">
                                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm border border-slate-100" :class="data.type === 'quiz' ? 'bg-amber-50 text-amber-500' : 'bg-indigo-50 text-indigo-500'">
                                                    <i :class="data.type === 'quiz' ? 'pi pi-bolt' : 'pi pi-file-edit'" class="text-xl"></i>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-black text-slate-800 text-[15px] hover:text-indigo-600 cursor-pointer transition-colors">{{ data.title }}</span>
                                                    <span class="text-xs text-slate-500 mt-1 font-medium">{{ data.course?.title }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Échéance" style="min-width: 14rem">
                                        <template #body="{ data }">
                                            <div class="flex items-center gap-2 text-[13px] font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg w-max border border-slate-100">
                                                <i class="pi pi-calendar text-indigo-400"></i>
                                                {{ formatDate(data.due_date) }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Copies en attente" align="center" style="min-width: 12rem">
                                        <template #body="{ data }">
                                            <Badge :value="data.needs_grading_count" size="large" :severity="data.needs_grading_count > 0 ? 'danger' : 'secondary'" class="font-black shadow-sm text-sm px-3" />
                                        </template>
                                    </Column>
                                    <Column header="Actions" alignFrozen="right" :frozen="true">
                                        <template #body="{ data }">
                                            <Button label="Corriger" icon="pi pi-check-square" class="p-button-outlined p-button-primary font-black rounded-xl px-5" @click="openGradeDialog(data)" :disabled="data.needs_grading_count === 0" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </template>
                    </div>
                </main>
            </div>
        </div>

        <Drawer v-model:visible="submitAssignmentDialog" position="right" class="w-full md:w-[500px] !bg-slate-50" header="Remise de Travail">
            <div v-if="currentAssignment" class="flex flex-col h-full space-y-6 pt-4">
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
                    <h3 class="font-black text-slate-800 text-xl">{{ currentAssignment.title }}</h3>
                    <p class="text-[13px] font-bold text-slate-500 flex items-center gap-2 mt-3 bg-slate-50 w-max px-3 py-1.5 rounded-lg"><i class="pi pi-clock text-indigo-500"></i> Limite : {{ formatDate(currentAssignment.due_date) }}</p>
                </div>

                <div class="border-2 border-dashed border-indigo-200 bg-indigo-50/50 rounded-3xl p-12 text-center relative hover:bg-indigo-50 transition-colors shadow-inner group">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-sm group-hover:scale-110 transition-transform">
                        <i class="pi pi-cloud-upload text-4xl text-indigo-500"></i>
                    </div>
                    <p class="font-black text-indigo-900 text-lg mb-1">Uploader votre document</p>
                    <p class="text-sm font-medium text-indigo-600/70">Glissez-déposez ou cliquez pour parcourir</p>

                    <FileUpload mode="basic" auto customUpload @select="onAssignmentSelect" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" />

                    <div v-if="assignmentSubmitForm.submitted_file" class="absolute inset-4 bg-white border border-emerald-200 shadow-lg rounded-2xl flex items-center justify-between p-4 z-20 animate-fade-in">
                        <div class="flex items-center gap-4 overflow-hidden">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0"><i class="pi pi-file-check text-2xl"></i></div>
                            <span class="font-black text-sm text-slate-800 truncate">{{ assignmentSubmitForm.submitted_file.name }}</span>
                        </div>
                        <Button icon="pi pi-trash" text rounded severity="danger" class="bg-rose-50 hover:bg-rose-100" @click.stop="assignmentSubmitForm.submitted_file = null" />
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-2">Commentaire pour le professeur (Optionnel)</label>
                    <Textarea v-model="assignmentSubmitForm.student_comment" rows="5" placeholder="Ajouter une note ou des précisions..." class="w-full rounded-3xl border-slate-200 bg-white p-5 font-medium focus:border-indigo-500 shadow-sm" />
                </div>

                <div class="mt-auto pt-6 border-t border-slate-200">
                    <Button label="Confirmer l'envoi du devoir" icon="pi pi-send" class="w-full p-button-primary shadow-lg shadow-indigo-200 font-black rounded-2xl py-4 text-lg" @click="submitStudentAssignment" :loading="assignmentSubmitForm.processing" :disabled="!assignmentSubmitForm.submitted_file" />
                </div>
            </div>
        </Drawer>

        <Dialog v-model:visible="takeQuizDialog" modal maximized header="Passer l'Interrogation" class="bg-slate-50" :pt="{ header: { class: 'bg-white border-b border-slate-200 shadow-sm z-10 py-5' }, title: {class: 'font-black text-xl text-slate-800'} }">
            <div v-if="currentQuizToTake" class="max-w-[1000px] mx-auto py-10 space-y-8 pb-36">
                <div class="bg-white p-8 md:p-10 rounded-3xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-400"></div>
                    <h2 class="text-3xl font-black text-slate-800 mb-2">{{ currentQuizToTake.title }}</h2>
                    <p class="text-slate-600 font-medium mb-6">{{ currentQuizToTake.instructions }}</p>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2 font-bold text-slate-700"><i class="pi pi-book text-indigo-500"></i> {{ currentQuizToTake.course?.title }}</div>
                        <div class="flex items-center gap-2 font-bold text-slate-700"><i class="pi pi-clock text-rose-500"></i> Limite : {{ formatDate(currentQuizToTake.due_date) }}</div>
                    </div>
                </div>

                <div v-for="(question, qIndex) in currentQuizToTake.questions" :key="question.id" class="bg-white p-8 md:p-10 rounded-3xl border border-slate-200 shadow-sm animate-fade-in">
                    <div class="flex items-start gap-5">
                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center font-black shrink-0">{{ qIndex + 1 }}</div>
                        <div class="flex-1">
                            <p class="font-bold text-lg text-slate-800 leading-relaxed mb-6">{{ question.text }}</p>
                            <div class="space-y-4">
                                <div v-for="option in question.options" :key="option.id" class="flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer" :class="quizSubmissionForm.answers[question.id] === option.id ? 'border-indigo-500 bg-indigo-50 shadow-md' : 'border-slate-200 bg-white hover:border-indigo-300'">
                                    <RadioButton v-model="quizSubmissionForm.answers[question.id]" :inputId="`opt-${option.id}`" name="`q-${question.id}`" :value="option.id" />
                                    <label :for="`opt-${option.id}`" class="ml-4 font-bold text-slate-700 cursor-pointer flex-1">{{ option.text }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center bg-white/95 backdrop-blur-xl border-t border-slate-200 p-6 md:px-10 absolute bottom-0 left-0 w-full shadow-[0_-20px_40px_rgba(0,0,0,0.06)] z-20">
                    <Button label="Annuler" text class="font-black text-slate-500 px-6 rounded-xl hover:bg-slate-50" @click="takeQuizDialog = false" />
                    <Button
                        label="Soumettre mes réponses"
                        icon="pi pi-check-circle"
                        class="p-button-primary shadow-lg shadow-indigo-200/50 font-black rounded-xl px-10 py-4 text-white text-lg"
                        @click="submitStudentQuiz"
                        :loading="quizSubmissionForm.processing" />
                </div>
            </template>
        </Dialog>

        <Dialog v-model:visible="createQuizDialog" modal maximized header="⚡ Constructeur d'Interrogation" class="bg-slate-50" :pt="{ header: { class: 'bg-white border-b border-slate-200 shadow-sm z-10 py-5' }, title: {class: 'font-black text-xl text-slate-800'} }">
            <div class="max-w-[1000px] mx-auto py-10 space-y-8 pb-36">

                <div class="bg-white p-8 md:p-10 rounded-3xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] grid grid-cols-1 md:grid-cols-2 gap-8 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-400"></div>
                    <div class="col-span-1 md:col-span-2 space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Titre de l'évaluation</label>
                        <InputText v-model="quizCreateForm.title" placeholder="Ex: Évaluation finale - Semestre 1" class="w-full font-black text-xl p-5 rounded-2xl bg-slate-50 border-slate-200 focus:border-amber-400 focus:ring-4 focus:ring-amber-500/10 transition-all shadow-sm" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Temps alloué (Minutes)</label>
                        <InputNumber v-model="quizCreateForm.time_limit_minutes" showButtons class="w-full" :pt="{ input: { class: 'font-black bg-slate-50 p-4 text-lg' } }" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Score minimum requis (%)</label>
                        <InputNumber v-model="quizCreateForm.passing_score" showButtons :min="0" :max="100" suffix=" %" class="w-full" :pt="{ input: { class: 'font-black bg-slate-50 p-4 text-lg' } }" />
                    </div>
                </div>

                <div v-for="(question, qIndex) in quizCreateForm.questions" :key="question.id" class="bg-white p-8 md:p-10 rounded-3xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative group animate-fade-in">
                    <div class="absolute top-8 right-8 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Button icon="pi pi-trash" text rounded severity="danger" class="bg-rose-50 w-10 h-10" v-tooltip.top="'Supprimer cette question'" @click="removeQuizQuestion(qIndex)" />
                    </div>

                    <div class="space-y-4 w-full md:pr-16">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center font-black">{{ qIndex + 1 }}</div>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Question</span>
                        </div>
                        <Textarea v-model="question.text" autoResize rows="2" placeholder="Saisissez l'intitulé complet de la question..." class="w-full font-black text-xl text-slate-800 bg-transparent border-b-2 border-slate-200 focus:border-amber-400 rounded-none px-0 py-3 shadow-none focus:ring-0 transition-colors" :class="{'border-rose-400 placeholder-rose-300': !question.text}" />
                    </div>

                    <div class="mt-10 space-y-4 pl-4 md:pl-11 border-l-2 border-slate-100">
                        <div v-for="(option, optIndex) in question.options" :key="optIndex" class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center cursor-pointer transition-all shadow-sm shrink-0 hover:scale-110" :class="option.is_correct ? 'border-emerald-500 bg-emerald-500 text-white shadow-emerald-200' : 'border-slate-300 bg-white hover:border-emerald-400'" @click="setCorrectOption(qIndex, optIndex)" v-tooltip.left="'Cocher pour définir comme bonne réponse'">
                                <i class="pi pi-check text-sm" v-show="option.is_correct"></i>
                            </div>
                            <InputText v-model="option.text" placeholder="Proposition de réponse..." class="flex-1 rounded-2xl bg-slate-50 border-slate-200 p-4 focus:border-emerald-400 font-bold text-slate-700" :class="{'border-rose-300': !option.text}" />
                            <Button icon="pi pi-times" text rounded severity="secondary" class="hover:bg-slate-100 shrink-0 w-10 h-10 text-slate-400" @click="removeQuestionOption(qIndex, optIndex)" :disabled="question.options.length <= 2" />
                        </div>
                        <Button label="Ajouter une proposition" icon="pi pi-plus" text class="font-black text-indigo-600 mt-4 px-4 py-3 rounded-xl hover:bg-indigo-50" @click="addQuestionOption(qIndex)" />
                    </div>
                </div>

                <Button label="Ajouter une nouvelle question" icon="pi pi-plus-circle" class="w-full p-button-outlined p-button-secondary border-dashed border-2 bg-white hover:bg-slate-50 py-8 font-black text-slate-600 rounded-3xl text-lg transition-all shadow-sm" @click="addQuizQuestion" />
            </div>

            <template #footer>
                <div class="flex justify-between items-center bg-white/95 backdrop-blur-xl border-t border-slate-200 p-6 md:px-10 absolute bottom-0 left-0 w-full shadow-[0_-20px_40px_rgba(0,0,0,0.06)] z-20">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center font-black text-xl text-slate-700">{{ quizCreateForm.questions.length }}</div>
                        <div class="flex flex-col">
                            <span class="font-black text-slate-800">Questions configurées</span>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Prêt pour la publication</span>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <Button label="Annuler" text class="font-black text-slate-500 px-6 rounded-xl hover:bg-slate-50" @click="createQuizDialog = false" />
                        <Button label="Publier le Quiz" icon="pi pi-check-circle" class="p-button-warning bg-amber-500 border-amber-500 hover:bg-amber-600 shadow-lg shadow-amber-200/50 font-black rounded-xl px-10 py-4 text-white text-lg" @click="saveInstructorQuiz" :loading="quizCreateForm.processing" />
                    </div>
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

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
</style>
