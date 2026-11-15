<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import cloneDeep from 'lodash/cloneDeep';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

// Composants Externes
import ForumSection from './ForumSection.vue';
import RadioButton from 'primevue/radiobutton';
import SncDialog from '@/Components/SncDialog.vue';

// ======================================================================================
// PRIMEVUE COMPONENTS (PrimeVue v4 API)
// ======================================================================================
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Drawer from 'primevue/drawer';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
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
const confirmAction = useConfirmAction();

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
    formCourses: { type: Array, default: () => [] },
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
            toast.add({ severity: 'error', summary: t('pages.forums.toast_network_error_summary'), detail: t('pages.forums.toast_network_error_detail'), life: 4000 });
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
    groupChatForm.post(route('social.forums.groups.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.forums.toast_group_created_summary'), detail: t('pages.forums.toast_group_created_detail') });
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
            toast.add({ severity: 'success', summary: t('pages.forums.toast_assignment_submitted_summary'), detail: t('pages.forums.toast_assignment_submitted_detail') });
            submitAssignmentDialog.value = false;
        }
    });
};

const openCreateAssignment = () => { assignmentCreateForm.reset(); createAssignmentDialog.value = true; };

const saveInstructorAssignment = () => {
    assignmentCreateForm.post(route('instructor.assignments.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.forums.toast_published_summary'), detail: t('pages.forums.toast_published_detail') });
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
    confirmAction({
        message: t('pages.forums.confirm_delete_question_message'), header: t('pages.forums.confirm_delete_question_header'), icon: 'pi pi-exclamation-triangle', danger: true,
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
        toast.add({ severity: 'error', summary: t('pages.forums.toast_form_invalid_summary'), detail: t('pages.forums.toast_form_invalid_detail') });
        return;
    }
    quizCreateForm.post(route('instructor.quizzes.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.forums.toast_quiz_created_summary'), detail: t('pages.forums.toast_quiz_created_detail') });
            createQuizDialog.value = false;
        }
    });
};

const openGradeDialog = (assignment) => {
    currentSubmissionToGrade.value = cloneDeep(assignment);
    gradeForm.reset();
    gradeForm.submission_id = assignment.pending_submissions?.[0]?.id ?? null;
    gradeSubmissionDialog.value = true;
};

const submitGrade = () => {
    gradeForm.post(route('instructor.grade.store'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.forums.toast_graded_summary'), detail: t('pages.forums.toast_graded_detail') });
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
        toast.add({ severity: 'warn', summary: t('pages.forums.toast_quiz_incomplete_summary'), detail: t('pages.forums.toast_quiz_incomplete_detail'), life: 4000 });
        return;
    }

    quizSubmissionForm.post(route('assignments.quiz.submit'), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.forums.toast_quiz_submitted_summary'), detail: t('pages.forums.toast_quiz_submitted_detail') });
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
    return new Intl.DateTimeFormat(locale.value, { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(dateString));
};
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'U';
const isImageAttachment = (fileName) => {
    if (!fileName) return false;
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(fileName.split('.').pop().toLowerCase());
};
const getAttachmentIcon = (fileName) => {
    if (!fileName) return 'pi pi-file';
    const ext = fileName.split('.').pop().toLowerCase();
    if (ext === 'pdf') return 'pi pi-file-pdf text-red-500';
    if (['zip', 'rar'].includes(ext)) return 'pi pi-box text-amber-500';
    if (isImageAttachment(fileName)) return 'pi pi-image text-blue-500';
    return 'pi pi-file text-slate-500';
};
const showReceipt = (index) => {
    const list = props.messages ?? [];
    if (list[index]?.sender_id !== props.currentUser?.id) return false;
    // Accusé de lecture affiché uniquement sur le dernier message envoyé par moi
    for (let i = index + 1; i < list.length; i++) {
        if (list[i].sender_id === props.currentUser?.id) return false;
    }
    return true;
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
        <Head :title="t('pages.forums.head_title')" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <PageHeader
                :title="t('pages.forums.page_title')"
                :subtitle="isInstructor ? t('pages.forums.subtitle_instructor') : t('pages.forums.subtitle_student')"
                icon="pi-globe"
                accent="blue"
            />

            <div class="page-container">

                <!-- KPI raccourcis -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-2">
                    <div @click="activeHubSection = 'chat'" class="cursor-pointer">
                        <PageKpi :label="t('pages.forums.kpi_messages')" :value="props.stats.unread_messages" icon="pi-envelope" color="red" />
                    </div>
                    <div @click="activeHubSection = 'assignments'" class="cursor-pointer">
                        <PageKpi
                            :label="isInstructor ? t('pages.forums.kpi_grading') : t('pages.forums.kpi_assignments_due')"
                            :value="isInstructor ? props.stats.needs_grading : props.stats.pending_assignments"
                            icon="pi-file-edit" color="amber" />
                    </div>
                    <div @click="activeHubSection = 'forum'" class="cursor-pointer">
                        <PageKpi :label="t('pages.forums.kpi_threads')" :value="props.stats.totalThreads" icon="pi-comments" color="blue" />
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-8 items-start w-full">

                <aside class="w-full lg:w-80 flex-shrink-0 sticky top-24">
                    <div class="bg-white/90 backdrop-blur-xl rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 p-5">

                        <div class="space-y-2">
                            <button @click="activeHubSection = 'forum'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'forum' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-comments text-lg" :class="activeHubSection === 'forum' ? 'text-blue-200' : 'text-slate-400'"></i> {{ t('pages.forums.nav_forum') }}</div>
                            </button>
                            <button @click="activeHubSection = 'chat'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'chat' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-send text-lg" :class="activeHubSection === 'chat' ? 'text-blue-200' : 'text-slate-400'"></i> {{ t('pages.forums.nav_messaging') }}</div>
                                <Badge v-if="props.stats.unread_messages > 0" :value="props.stats.unread_messages" severity="danger" />
                            </button>
                            <button @click="activeHubSection = 'assignments'" :class="['w-full flex items-center justify-between p-4 rounded-2xl transition-all duration-300 font-bold text-sm', activeHubSection === 'assignments' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 scale-[1.02]' : 'hover:bg-slate-100 text-slate-600 hover:text-slate-900']">
                                <div class="flex items-center gap-3"><i class="pi pi-file-edit text-lg" :class="activeHubSection === 'assignments' ? 'text-blue-200' : 'text-slate-400'"></i> {{ isInstructor ? t('pages.forums.nav_evaluations') : t('pages.forums.nav_my_assignments') }}</div>
                            </button>
                        </div>

                        <!-- Filtres du forum : visibles uniquement sur la section Forum -->
                        <div v-show="activeHubSection === 'forum'" class="mt-8 border-t border-slate-100 pt-6">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-4 mb-3">{{ t('pages.forums.filters_title') }}</h3>
                            <div class="flex flex-col gap-1 px-1">
                                <button @click="handleCategoryFilter(null)" :class="['flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all duration-200', !props.filters.category ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']">
                                    <span class="flex items-center gap-3"><i class="pi pi-objects-column"></i> {{ t('pages.forums.all_categories') }}</span>
                                </button>
                                <button v-for="cat in categories" :key="cat.id" @click="handleCategoryFilter(cat.id)" :class="['flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all duration-200', props.filters.category === cat.id ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800']">
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
                    <div v-show="activeHubSection === 'chat'" class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 overflow-hidden h-[calc(100vh-14rem)] min-h-[600px] max-h-[900px] flex flex-col md:flex-row animate-fade-in relative">

                        <div v-if="isAppLoading" class="absolute inset-0 bg-white/70 backdrop-blur-md z-30 flex items-center justify-center">
                            <div class="bg-white p-6 rounded-xl shadow-2xl flex flex-col items-center gap-4 border border-slate-100">
                                <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
                                <span class="font-black text-slate-700 text-sm">{{ t('pages.forums.syncing') }}</span>
                            </div>
                        </div>

                        <div class="w-full md:w-96 border-r border-slate-100 flex flex-col bg-slate-50/50 h-1/2 md:h-full flex-shrink-0">
                            <div class="p-5 md:p-6 bg-white border-b border-slate-100 shadow-sm z-10">
                                <div class="flex justify-between items-center mb-5">
                                    <h2 class="text-xl font-black text-slate-800">{{ t('pages.forums.messaging_title') }}</h2>
                                    <Button icon="pi pi-users" class="p-button-rounded p-button-secondary p-button-text bg-slate-50 hover:bg-blue-50 hover:text-blue-600" v-tooltip.top="t('pages.forums.create_group_tooltip')" @click="openCreateGroupDialog" />
                                </div>
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search text-slate-400" />
                                    <InputText v-model="chatSearchQuery" :placeholder="t('pages.forums.search_contact_placeholder')" class="w-full h-11 rounded-2xl bg-slate-50 border-slate-200 focus:border-blue-500 transition-colors font-medium" />
                                </IconField>
                            </div>

                            <div class="flex border-b border-slate-200 bg-white">
                                <button @click="activeChatTab = 'contacts'" class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-colors" :class="activeChatTab === 'contacts' ? 'text-blue-700 border-b-2 border-blue-600 bg-blue-50/50' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'">{{ t('pages.forums.tab_contacts') }}</button>
                                <button @click="activeChatTab = 'groups'" class="flex-1 py-4 text-xs font-black uppercase tracking-widest transition-colors" :class="activeChatTab === 'groups' ? 'text-blue-700 border-b-2 border-blue-600 bg-blue-50/50' : 'text-slate-400 hover:bg-slate-50 hover:text-slate-600'">{{ t('pages.forums.tab_groups') }}</button>
                            </div>

                            <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-1">
                                <template v-if="activeChatTab === 'contacts'">
                                    <div v-for="contact in filteredContacts" :key="contact.id" @click="selectChatSession(contact, 'contact')" class="p-3 md:p-4 rounded-2xl flex items-center gap-4 cursor-pointer transition-all duration-200 border border-transparent" :class="currentChatSession?.id === contact.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-200/50' : 'hover:bg-white hover:border-slate-200 hover:shadow-sm text-slate-700'">
                                        <div class="relative shrink-0">
                                            <Avatar :image="contact.avatar_path" :label="!contact.avatar_path ? getInitials(contact.name) : ''" shape="circle" size="large" class="shadow-sm font-black text-lg" :class="currentChatSession?.id === contact.id ? 'bg-white/20 text-white border border-white/30' : 'bg-slate-100 text-blue-600 border border-slate-200'" />
                                            <span class="absolute bottom-0 right-0 w-3.5 h-3.5 border-2 rounded-full" :class="[contact.is_online ? 'bg-emerald-500' : 'bg-slate-300', currentChatSession?.id === contact.id ? 'border-blue-600' : 'border-white']"></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <h4 class="font-bold text-sm truncate" :class="currentChatSession?.id === contact.id ? 'text-white' : 'text-slate-800'">{{ contact.name }}</h4>
                                                <Badge v-if="contact.unread_count > 0" :value="contact.unread_count > 99 ? '99+' : contact.unread_count" severity="danger" class="!text-[10px] !px-1.5 !py-0 shrink-0" />
                                            </div>
                                            <p class="text-[10px] uppercase font-bold tracking-widest mt-1" :class="currentChatSession?.id === contact.id ? 'text-blue-200' : 'text-slate-400'">{{ contact.role }}</p>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div v-for="group in filteredGroups" :key="group.id" @click="selectChatSession(group, 'group')" class="p-3 md:p-4 rounded-2xl flex items-center gap-4 cursor-pointer transition-all duration-200 border border-transparent" :class="currentChatSession?.id === group.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-200/50' : 'hover:bg-white hover:border-slate-200 hover:shadow-sm text-slate-700'">
                                        <Avatar :image="group.cover_path" icon="pi pi-users" shape="circle" size="large" class="shadow-sm shrink-0" :class="currentChatSession?.id === group.id ? 'bg-white/20 text-white border border-white/30' : 'bg-slate-100 text-slate-500 border border-slate-200'" />
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-sm truncate" :class="currentChatSession?.id === group.id ? 'text-white' : 'text-slate-800'">{{ group.name }}</h4>
                                            <p class="text-[10px] uppercase font-bold tracking-widest mt-1" :class="currentChatSession?.id === group.id ? 'text-blue-200' : 'text-slate-400'">{{ t('pages.forums.members_count', { count: group.members_count }) }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col bg-white/50 relative h-1/2 md:h-full">
                            <template v-if="currentChatSession">
                                <div class="p-4 md:p-6 border-b border-slate-100 bg-white/90 backdrop-blur-xl shadow-sm z-20 flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <Avatar :image="currentChatSession.cover_path || currentChatSession.avatar_path" :label="getInitials(currentChatSession.name)" shape="circle" size="large" class="bg-blue-50 text-blue-700 font-black border border-blue-100 shadow-sm" />
                                        <div>
                                            <h3 class="font-black text-slate-800 text-lg leading-tight">{{ currentChatSession.name }}</h3>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span v-if="currentChatSession.sessionType === 'contact'" class="w-2 h-2 rounded-full shadow-sm" :class="currentChatSession.is_online ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                                    {{ currentChatSession.sessionType === 'group' ? t('pages.forums.status_group') : (currentChatSession.is_online ? t('pages.forums.status_online') : t('pages.forums.status_offline')) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <Button icon="pi pi-ellipsis-v" text rounded class="text-slate-400 hover:bg-slate-50 hover:text-slate-800" />
                                </div>

                                <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50/50" ref="chatScrollContainer">
                                    <div v-for="(msg, msgIndex) in messages" :key="msg.id" class="flex flex-col animate-fade-in" :class="msg.sender_id === currentUser.id ? 'items-end' : 'items-start'">
                                        <div class="max-w-[85%] lg:max-w-[70%] flex flex-col gap-1">
                                            <!-- Nom de l'expéditeur (groupes uniquement) -->
                                            <span v-if="currentChatSession.sessionType === 'group' && msg.sender_id !== currentUser.id && msg.user?.name" class="text-[10px] font-black uppercase tracking-widest text-blue-500 px-1">{{ msg.user.name }}</span>
                                            <div :class="[msg.sender_id === currentUser.id ? 'bg-blue-600 text-white rounded-2xl rounded-br-md shadow-md shadow-blue-200' : 'bg-white text-slate-800 rounded-2xl rounded-bl-md border border-slate-200 shadow-sm', 'p-4 md:p-5']">
                                                <p v-if="msg.content" class="text-[15px] leading-relaxed whitespace-pre-line font-medium">{{ msg.content }}</p>

                                                <a v-if="msg.attachment_path" :href="msg.attachment_path" target="_blank" class="block rounded-xl overflow-hidden transition-transform hover:scale-[1.02] shadow-sm" :class="{'mt-4': msg.content}">
                                                    <img v-if="isImageAttachment(msg.attachment_name)" :src="msg.attachment_path" class="w-full h-auto max-h-72 object-cover border border-black/5 rounded-xl" :alt="t('pages.forums.attachment_alt')">
                                                    <div v-else class="p-3.5 flex items-center gap-3 rounded-xl border" :class="msg.sender_id === currentUser.id ? 'bg-blue-700/50 border-blue-500/50' : 'bg-slate-50 border-slate-200'">
                                                        <i :class="getAttachmentIcon(msg.attachment_name)" class="text-2xl drop-shadow-sm"></i>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-bold truncate" :class="msg.sender_id === currentUser.id ? 'text-blue-50' : 'text-slate-700'">{{ msg.attachment_name }}</p>
                                                        </div>
                                                        <i class="pi pi-download text-sm" :class="msg.sender_id === currentUser.id ? 'text-blue-200' : 'text-slate-400'"></i>
                                                    </div>
                                                </a>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-400 px-1 tracking-wide flex items-center gap-1" :class="msg.sender_id === currentUser.id ? 'justify-end' : 'justify-start'">
                                                {{ formatDate(msg.created_at) }}
                                                <template v-if="showReceipt(msgIndex)">
                                                    <i v-if="currentChatSession.sessionType === 'contact'" :class="[msg.is_read ? 'pi-check-double text-blue-500' : 'pi-check text-slate-300']" v-tooltip.top="msg.is_read ? t('pages.forums.receipt_read') : t('pages.forums.receipt_sent')"></i>
                                                </template>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 md:p-6 bg-white border-t border-slate-100 z-20 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                                    <div v-if="chatForm.attachment" class="mb-4 mx-2 p-3 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-100 shadow-sm animate-fade-in">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center text-blue-600"><i class="pi pi-paperclip"></i></div>
                                            <span class="text-xs font-black text-blue-800 truncate">{{ chatForm.attachment.name }}</span>
                                        </div>
                                        <Button icon="pi pi-times" text rounded size="small" severity="danger" class="hover:bg-red-100" @click="chatForm.attachment = null" />
                                    </div>

                                    <div class="flex items-end gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-200 focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-500/10 focus-within:bg-white transition-all shadow-sm">
                                        <FileUpload mode="basic" auto customUpload @select="handleChatFileUpload" chooseIcon="pi pi-paperclip" class="p-button-rounded p-button-text text-slate-500 hover:text-blue-600 hover:bg-slate-100 w-12 h-12 flex-shrink-0" v-tooltip.top="t('pages.forums.attach_file_tooltip')" />
                                        <Textarea v-model="chatForm.message" autoResize rows="1" :placeholder="t('pages.forums.message_placeholder')" class="flex-1 bg-transparent border-none shadow-none focus:ring-0 py-3.5 px-3 text-slate-700 font-medium resize-none max-h-32 custom-scrollbar text-[15px]" @keyup.enter.prevent="sendChatMessage" />
                                        <Button icon="pi pi-send" rounded class="p-button-primary shadow-lg shadow-blue-200 w-12 h-12 flex-shrink-0" @click="sendChatMessage" :loading="chatForm.processing" :disabled="!chatForm.message.trim() && !chatForm.attachment" />
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex-1 flex flex-col items-center justify-center text-center text-slate-400 bg-slate-50/50 p-8">
                                    <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center border border-slate-200 mb-6 shadow-sm">
                                        <i class="pi pi-comments text-5xl text-blue-200 transform -scale-x-100"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-700">{{ t('pages.forums.empty_conversations_title') }}</h3>
                                    <p class="text-sm max-w-sm mt-3 text-slate-500 font-medium leading-relaxed">{{ t('pages.forums.empty_conversations_text') }}</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- MODULE 3 : DEVOIRS & ÉVALUATIONS -->
                    <div v-show="activeHubSection === 'assignments'" class="animate-fade-in space-y-6">
                        <template v-if="!isInstructor">
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                <div v-if="!assignments.length" class="col-span-full p-20 text-center bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col items-center">
                                    <div class="w-28 h-28 bg-emerald-50 rounded-full flex items-center justify-center mb-6 border-8 border-emerald-100/50">
                                        <i class="pi pi-check text-5xl text-emerald-500"></i>
                                    </div>
                                    <h3 class="text-2xl font-black text-slate-800">{{ t('pages.forums.assignments_empty_title') }}</h3>
                                    <p class="text-slate-500 mt-2 font-medium">{{ t('pages.forums.assignments_empty_text') }}</p>
                                </div>

                                <div v-for="assignment in assignments" :key="assignment.id" class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col relative overflow-hidden group">
                                    <div class="absolute top-0 left-0 w-full h-1.5 transition-colors" :class="assignment.status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500'"></div>
                                    <div class="flex gap-4 mb-5 mt-2">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner border border-slate-100 shrink-0" :class="assignment.type === 'quiz' ? 'bg-amber-50 text-amber-500' : 'bg-blue-50 text-blue-600'">
                                            <i class="text-2xl" :class="assignment.type === 'quiz' ? 'pi pi-bolt' : 'pi pi-file-pdf'"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-black text-slate-800 text-lg leading-tight mb-2 group-hover:text-blue-600 transition-colors truncate">{{ assignment.title }}</h4>
                                            <Tag :value="assignment.course?.title" class="bg-slate-100 text-slate-600 text-[9px] font-black tracking-widest" />
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-4 rounded-2xl text-sm text-slate-600 mb-6 flex-1 line-clamp-3 border border-slate-100 leading-relaxed">{{ assignment.instructions }}</div>

                                    <div class="flex items-center justify-between mt-auto pt-5 border-t border-slate-100">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">{{ t('pages.forums.due_label') }}</span>
                                            <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5"><i class="pi pi-calendar text-blue-500"></i> {{ formatDate(assignment.due_date) }}</span>
                                        </div>

                                        <Button v-if="assignment.status === 'pending'" :label="assignment.type === 'quiz' ? t('pages.forums.btn_take_quiz') : t('pages.forums.btn_submit')" :icon="assignment.type === 'quiz' ? 'pi pi-play' : 'pi pi-upload'" class="p-button-primary font-black rounded-xl px-6 shadow-md shadow-blue-200" @click="assignment.type === 'quiz' ? openTakeQuiz(assignment) : openAssignmentSubmission(assignment)" />
                                        <div v-else-if="assignment.status === 'graded'" class="bg-emerald-50 text-emerald-700 rounded-xl px-5 py-2.5 font-black border border-emerald-200 flex items-center gap-2 shadow-sm">
                                            <i class="pi pi-check-circle"></i> {{ assignment.score }}/100
                                        </div>
                                        <Tag v-else :value="t('pages.forums.status_grading')" severity="info" class="rounded-xl px-4 py-2 text-xs font-black shadow-sm" />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-200/80 overflow-hidden">
                                <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center bg-white gap-4">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-800">{{ t('pages.forums.eval_center_title') }}</h3>
                                        <p class="text-sm text-slate-500 mt-1 font-medium">{{ t('pages.forums.eval_center_subtitle') }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <Button :label="t('pages.forums.btn_new_assignment')" icon="pi pi-plus" class="p-button-primary font-black rounded-xl shadow-md shadow-blue-200 px-6" @click="openCreateAssignment" />
                                        <Button :label="t('pages.forums.btn_new_quiz')" icon="pi pi-bolt" class="p-button-warning bg-amber-500 border-amber-500 hover:bg-amber-600 font-black rounded-xl shadow-md shadow-amber-200 px-6" @click="openCreateQuiz" />
                                    </div>
                                </div>

                                <DataTable :value="instructorAssignments" :paginator="true" :rows="10" class="p-datatable-lg border-none" dataKey="id" stripedRows hoverableRows>
                                    <template #empty>
                                        <div class="p-20 text-center text-slate-500 flex flex-col items-center">
                                            <i class="pi pi-folder-open text-6xl text-slate-200 mb-6"></i>
                                            <span class="font-black text-xl text-slate-700">{{ t('pages.forums.eval_empty_title') }}</span>
                                            <p class="text-sm font-medium mt-2 max-w-sm">{{ t('pages.forums.eval_empty_text') }}</p>
                                        </div>
                                    </template>
                                    <Column field="title" :header="t('pages.forums.col_evaluation')" style="min-width: 25rem" headerClass="!text-left">
                                        <template #body="{ data }">
                                            <div class="flex items-center gap-5 py-3">
                                                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm border border-slate-100" :class="data.type === 'quiz' ? 'bg-amber-50 text-amber-500' : 'bg-blue-50 text-blue-500'">
                                                    <i :class="data.type === 'quiz' ? 'pi pi-bolt' : 'pi pi-file-edit'" class="text-xl"></i>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-black text-slate-800 text-[15px] hover:text-blue-600 cursor-pointer transition-colors">{{ data.title }}</span>
                                                    <span class="text-xs text-slate-500 mt-1 font-medium">{{ data.course?.title }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </Column>
                                    <Column :header="t('pages.forums.col_due')" style="min-width: 14rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                                        <template #body="{ data }">
                                            <div class="flex items-center gap-2 text-[13px] font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg w-max border border-slate-100">
                                                <i class="pi pi-calendar text-blue-400"></i>
                                                {{ formatDate(data.due_date) }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column :header="t('pages.forums.col_pending_copies')" align="center" style="min-width: 12rem" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                                        <template #body="{ data }">
                                            <Badge :value="data.needs_grading_count" size="large" :severity="data.needs_grading_count > 0 ? 'danger' : 'secondary'" class="font-black shadow-sm text-sm px-3" />
                                        </template>
                                    </Column>
                                    <Column :header="t('pages.forums.col_actions')">
                                        <template #body="{ data }">
                                            <Button :label="t('pages.forums.btn_grade')" icon="pi pi-check-square" class="p-button-outlined p-button-primary font-black rounded-xl px-5" @click="openGradeDialog(data)" :disabled="data.needs_grading_count === 0" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                        </template>
                    </div>
                </main>
                </div>
            </div>
        </div>

        <!-- DIALOG : NOUVEAU GROUPE DE DISCUSSION -->
        <SncDialog
            v-model:visible="createGroupDialog"
            :title="t('pages.forums.group_dialog_title')"
            :subtitle="t('pages.forums.group_dialog_subtitle')"
            icon="pi-users"
            iconClass="from-blue-500 to-indigo-600"
            width="640px"
        >
            <div class="space-y-5">
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.group_name_label') }}</label>
                    <InputText v-model="groupChatForm.name" :placeholder="t('pages.forums.group_name_placeholder')" class="w-full" />
                    <p v-if="groupChatForm.errors.name" class="text-xs font-bold text-red-500">{{ groupChatForm.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.group_members_label') }}</label>
                    <PickList
                        v-model="pickListContacts"
                        :sourceHeader="t('pages.forums.picklist_source')"
                        :targetHeader="t('pages.forums.picklist_target')"
                        :dataKey="'id'"
                        :showSourceControls="false"
                        :showTargetControls="false"
                        class="forum-picklist"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-3 py-1">
                                <Avatar :image="option.avatar_path" :label="getInitials(option.name)" shape="circle" size="small" class="bg-slate-100 text-blue-600 font-black" />
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-bold text-slate-700 truncate">{{ option.name }}</span>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">{{ option.role }}</span>
                                </div>
                            </div>
                        </template>
                    </PickList>
                    <p v-if="groupChatForm.errors.members" class="text-xs font-bold text-red-500">{{ groupChatForm.errors.members }}</p>
                </div>
            </div>

            <template #footer>
                <Button :label="t('pages.forums.btn_cancel')" text class="font-bold text-slate-500" @click="createGroupDialog = false" />
                <Button :label="t('pages.forums.btn_create_group')" icon="pi pi-users" class="font-black" :loading="groupChatForm.processing" :disabled="!groupChatForm.name.trim() || pickListContacts[1].length === 0" @click="submitGroupChat" />
            </template>
        </SncDialog>

        <!-- DIALOG : CRÉATION DE DEVOIR (INSTRUCTEUR) -->
        <SncDialog
            v-model:visible="createAssignmentDialog"
            :title="t('pages.forums.assignment_dialog_title')"
            :subtitle="t('pages.forums.assignment_dialog_subtitle')"
            icon="pi-file-edit"
            iconClass="from-blue-500 to-blue-600"
            width="680px"
        >
            <div class="space-y-5">
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.assignment_title_label') }}</label>
                    <InputText v-model="assignmentCreateForm.title" :placeholder="t('pages.forums.assignment_title_placeholder')" class="w-full" />
                    <p v-if="assignmentCreateForm.errors.title" class="text-xs font-bold text-red-500">{{ assignmentCreateForm.errors.title }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.assignment_course_label') }}</label>
                        <Select v-model="assignmentCreateForm.course_id" :options="props.formCourses" optionLabel="title" optionValue="id" :placeholder="t('pages.forums.assignment_course_placeholder')" class="w-full" />
                        <p v-if="assignmentCreateForm.errors.course_id" class="text-xs font-bold text-red-500">{{ assignmentCreateForm.errors.course_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.assignment_due_label') }}</label>
                        <Calendar v-model="assignmentCreateForm.due_date" showTime hourFormat="24" dateFormat="dd/mm/yy" :showIcon="true" class="w-full" />
                        <p v-if="assignmentCreateForm.errors.due_date" class="text-xs font-bold text-red-500">{{ assignmentCreateForm.errors.due_date }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.assignment_max_score_label') }}</label>
                        <InputNumber v-model="assignmentCreateForm.max_score" :min="1" :max="1000" class="w-full" />
                    </div>
                    <div class="flex items-center gap-3 pb-2">
                        <Checkbox v-model="assignmentCreateForm.allow_late" :binary="true" inputId="allowLate" />
                        <label for="allowLate" class="text-sm font-bold text-slate-600 cursor-pointer">{{ t('pages.forums.assignment_allow_late_label') }}</label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.assignment_instructions_label') }}</label>
                    <Textarea v-model="assignmentCreateForm.instructions" rows="5" :placeholder="t('pages.forums.assignment_instructions_placeholder')" class="w-full" />
                    <p v-if="assignmentCreateForm.errors.instructions" class="text-xs font-bold text-red-500">{{ assignmentCreateForm.errors.instructions }}</p>
                </div>
            </div>

            <template #footer>
                <Button :label="t('pages.forums.btn_cancel')" text class="font-bold text-slate-500" @click="createAssignmentDialog = false" />
                <Button :label="t('pages.forums.btn_publish_assignment')" icon="pi pi-send" class="font-black" :loading="assignmentCreateForm.processing" :disabled="!assignmentCreateForm.title.trim() || !assignmentCreateForm.course_id || !assignmentCreateForm.instructions.trim() || !assignmentCreateForm.due_date" @click="saveInstructorAssignment" />
            </template>
        </SncDialog>

        <!-- DIALOG : CORRECTION D'UNE COPIE (INSTRUCTEUR) -->
        <SncDialog
            v-model:visible="gradeSubmissionDialog"
            :title="t('pages.forums.grade_dialog_title')"
            :subtitle="currentSubmissionToGrade?.title || ''"
            icon="pi-check-square"
            iconClass="from-emerald-500 to-emerald-600"
            width="560px"
        >
            <div v-if="currentSubmissionToGrade" class="space-y-5">
                <div v-if="!currentSubmissionToGrade.pending_submissions?.length" class="p-6 text-center bg-slate-50 rounded-xl border border-slate-200">
                    <i class="pi pi-inbox text-3xl text-slate-300 mb-2 block"></i>
                    <p class="text-sm font-bold text-slate-500">{{ t('pages.forums.no_pending_submissions') }}</p>
                </div>
                <template v-else>
                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.grade_submission_label') }}</label>
                        <Select
                            v-model="gradeForm.submission_id"
                            :options="currentSubmissionToGrade.pending_submissions"
                            optionLabel="student.name"
                            optionValue="id"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                <div class="flex flex-col py-1">
                                    <span class="text-sm font-bold text-slate-700">{{ option.student?.name || '—' }}</span>
                                    <span class="text-[11px] text-slate-400">{{ option.file_name }} · {{ formatDate(option.submitted_at) }}</span>
                                </div>
                            </template>
                            <template #value="{ value }">
                                <span v-if="value" class="text-sm font-bold text-slate-700">
                                    {{ currentSubmissionToGrade.pending_submissions.find(s => s.id === value)?.student?.name }}
                                </span>
                            </template>
                        </Select>
                        <p v-if="gradeForm.errors.submission_id" class="text-xs font-bold text-red-500">{{ gradeForm.errors.submission_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.grade_score_label') }} / {{ currentSubmissionToGrade.max_score || 100 }}</label>
                            <InputNumber v-model="gradeForm.score" :min="0" :max="currentSubmissionToGrade.max_score || 100" suffix=" %" class="w-full" />
                            <p v-if="gradeForm.errors.score" class="text-xs font-bold text-red-500">{{ gradeForm.errors.score }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">{{ t('pages.forums.grade_feedback_label') }}</label>
                        <Textarea v-model="gradeForm.feedback" rows="4" :placeholder="t('pages.forums.grade_feedback_placeholder')" class="w-full" />
                    </div>
                </template>
            </div>

            <template #footer>
                <Button :label="t('pages.forums.btn_cancel')" text class="font-bold text-slate-500" @click="gradeSubmissionDialog = false" />
                <Button :label="t('pages.forums.btn_grade_submit')" icon="pi pi-check" class="font-black" :loading="gradeForm.processing" :disabled="!gradeForm.submission_id || gradeForm.score === null" @click="submitGrade" />
            </template>
        </SncDialog>

        <Drawer v-model:visible="submitAssignmentDialog" position="right" class="w-full md:w-[500px] !bg-slate-50" :header="t('pages.forums.drawer_submit_title')">
            <div v-if="currentAssignment" class="flex flex-col h-full space-y-6 pt-4">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
                    <h3 class="font-black text-slate-800 text-xl">{{ currentAssignment.title }}</h3>
                    <p class="text-[13px] font-bold text-slate-500 flex items-center gap-2 mt-3 bg-slate-50 w-max px-3 py-1.5 rounded-lg"><i class="pi pi-clock text-blue-500"></i> {{ t('pages.forums.limit_label', { date: formatDate(currentAssignment.due_date) }) }}</p>
                </div>

                <div class="border-2 border-dashed border-blue-200 bg-blue-50/50 rounded-xl p-12 text-center relative hover:bg-blue-50 transition-colors shadow-inner group">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-5 shadow-sm group-hover:scale-110 transition-transform">
                        <i class="pi pi-cloud-upload text-4xl text-blue-500"></i>
                    </div>
                    <p class="font-black text-blue-900 text-lg mb-1">{{ t('pages.forums.upload_title') }}</p>
                    <p class="text-sm font-medium text-blue-600/70">{{ t('pages.forums.upload_subtitle') }}</p>

                    <FileUpload mode="basic" auto customUpload @select="onAssignmentSelect" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10" />

                    <div v-if="assignmentSubmitForm.submitted_file" class="absolute inset-4 bg-white border border-emerald-200 shadow-lg rounded-2xl flex items-center justify-between p-4 z-20 animate-fade-in">
                        <div class="flex items-center gap-4 overflow-hidden">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0"><i class="pi pi-file-check text-2xl"></i></div>
                            <span class="font-black text-sm text-slate-800 truncate">{{ assignmentSubmitForm.submitted_file.name }}</span>
                        </div>
                        <Button icon="pi pi-trash" text rounded severity="danger" class="bg-red-50 hover:bg-red-100" @click.stop="assignmentSubmitForm.submitted_file = null" />
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-2">{{ t('pages.forums.comment_label') }}</label>
                    <Textarea v-model="assignmentSubmitForm.student_comment" rows="5" :placeholder="t('pages.forums.comment_placeholder')" class="w-full rounded-xl border-slate-200 bg-white p-5 font-medium focus:border-blue-500 shadow-sm" />
                </div>

                <div class="mt-auto pt-6 border-t border-slate-200">
                    <Button :label="t('pages.forums.btn_confirm_submit')" icon="pi pi-send" class="w-full p-button-primary shadow-lg shadow-blue-200 font-black rounded-2xl py-4 text-lg" @click="submitStudentAssignment" :loading="assignmentSubmitForm.processing" :disabled="!assignmentSubmitForm.submitted_file" />
                </div>
            </div>
        </Drawer>

        <SncDialog v-model:visible="takeQuizDialog" :title="t('pages.forums.quiz_dialog_title')" :subtitle="currentQuizToTake?.course?.title || ''" icon="pi-bolt" iconClass="from-amber-500 to-amber-600" width="920px">
            <div v-if="currentQuizToTake" class="space-y-6">
                <div class="bg-white p-8 md:p-10 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-400"></div>
                    <h2 class="text-3xl font-black text-slate-800 mb-2">{{ currentQuizToTake.title }}</h2>
                    <p class="text-slate-600 font-medium mb-6">{{ currentQuizToTake.instructions }}</p>
                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex items-center gap-2 font-bold text-slate-700"><i class="pi pi-book text-blue-500"></i> {{ currentQuizToTake.course?.title }}</div>
                        <div class="flex items-center gap-2 font-bold text-slate-700"><i class="pi pi-clock text-red-500"></i> {{ t('pages.forums.limit_label', { date: formatDate(currentQuizToTake.due_date) }) }}</div>
                    </div>
                </div>

                <div v-for="(question, qIndex) in currentQuizToTake.questions" :key="question.id" class="bg-white p-8 md:p-10 rounded-xl border border-slate-200 shadow-sm animate-fade-in">
                    <div class="flex items-start gap-5">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center font-black shrink-0">{{ qIndex + 1 }}</div>
                        <div class="flex-1">
                            <p class="font-bold text-lg text-slate-800 leading-relaxed mb-6">{{ question.text }}</p>
                            <div class="space-y-4">
                                <div v-for="option in question.options" :key="option.id" class="flex items-center p-4 rounded-2xl border-2 transition-all cursor-pointer" :class="quizSubmissionForm.answers[question.id] === option.id ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-slate-200 bg-white hover:border-blue-300'">
                                    <RadioButton v-model="quizSubmissionForm.answers[question.id]" :inputId="`opt-${option.id}`" name="`q-${question.id}`" :value="option.id" />
                                    <label :for="`opt-${option.id}`" class="ml-4 font-bold text-slate-700 cursor-pointer flex-1">{{ option.text }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button :label="t('pages.forums.btn_cancel')" text class="font-bold text-slate-500" @click="takeQuizDialog = false" />
                <Button
                    :label="t('pages.forums.btn_submit_answers')"
                    icon="pi pi-check-circle"
                    class="font-black"
                    @click="submitStudentQuiz"
                    :loading="quizSubmissionForm.processing" />
            </template>
        </SncDialog>

        <SncDialog v-model:visible="createQuizDialog" :title="t('pages.forums.quiz_builder_title')" icon="pi-bolt" iconClass="from-amber-500 to-amber-600" width="980px">
            <div class="space-y-8">

                <div class="bg-white p-6 md:p-8 rounded-xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] grid grid-cols-1 md:grid-cols-3 gap-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-amber-400"></div>
                    <div class="col-span-1 md:col-span-3 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">{{ t('pages.forums.quiz_title_label') }}</label>
                        <InputText v-model="quizCreateForm.title" :placeholder="t('pages.forums.quiz_title_placeholder')" class="w-full font-black text-lg" />
                        <p v-if="quizCreateForm.errors.title" class="text-xs font-bold text-red-500">{{ quizCreateForm.errors.title }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">{{ t('pages.forums.quiz_course_label') }}</label>
                        <Select v-model="quizCreateForm.course_id" :options="props.formCourses" optionLabel="title" optionValue="id" :placeholder="t('pages.forums.assignment_course_placeholder')" class="w-full" />
                        <p v-if="quizCreateForm.errors.course_id" class="text-xs font-bold text-red-500">{{ quizCreateForm.errors.course_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">{{ t('pages.forums.quiz_time_label') }}</label>
                        <InputNumber v-model="quizCreateForm.time_limit_minutes" showButtons class="w-full" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">{{ t('pages.forums.quiz_passing_label') }}</label>
                        <InputNumber v-model="quizCreateForm.passing_score" showButtons :min="0" :max="100" suffix=" %" class="w-full" />
                    </div>
                </div>

                <div v-for="(question, qIndex) in quizCreateForm.questions" :key="question.id" class="bg-white p-8 md:p-10 rounded-xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative group animate-fade-in">
                    <div class="absolute top-8 right-8 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Button icon="pi pi-trash" text rounded severity="danger" class="bg-red-50 w-10 h-10" v-tooltip.top="t('pages.forums.delete_question_tooltip')" @click="removeQuizQuestion(qIndex)" />
                    </div>

                    <div class="space-y-4 w-full md:pr-16">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center font-black">{{ qIndex + 1 }}</div>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">{{ t('pages.forums.question_label') }}</span>
                        </div>
                        <Textarea v-model="question.text" autoResize rows="2" :placeholder="t('pages.forums.question_placeholder')" class="w-full font-black text-xl text-slate-800 bg-transparent border-b-2 border-slate-200 focus:border-amber-400 rounded-none px-0 py-3 shadow-none focus:ring-0 transition-colors" :class="{'border-red-400 placeholder-rose-300': !question.text}" />
                    </div>

                    <div class="mt-10 space-y-4 pl-4 md:pl-11 border-l-2 border-slate-100">
                        <div v-for="(option, optIndex) in question.options" :key="optIndex" class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center cursor-pointer transition-all shadow-sm shrink-0 hover:scale-110" :class="option.is_correct ? 'border-emerald-500 bg-emerald-500 text-white shadow-emerald-200' : 'border-slate-300 bg-white hover:border-emerald-400'" @click="setCorrectOption(qIndex, optIndex)" v-tooltip.left="t('pages.forums.correct_option_tooltip')">
                                <i class="pi pi-check text-sm" v-show="option.is_correct"></i>
                            </div>
                            <InputText v-model="option.text" :placeholder="t('pages.forums.option_placeholder')" class="flex-1 rounded-2xl bg-slate-50 border-slate-200 p-4 focus:border-emerald-400 font-bold text-slate-700" :class="{'border-red-300': !option.text}" />
                            <Button icon="pi pi-times" text rounded severity="secondary" class="hover:bg-slate-100 shrink-0 w-10 h-10 text-slate-400" @click="removeQuestionOption(qIndex, optIndex)" :disabled="question.options.length <= 2" />
                        </div>
                        <Button :label="t('pages.forums.btn_add_option')" icon="pi pi-plus" text class="font-black text-blue-600 mt-4 px-4 py-3 rounded-xl hover:bg-blue-50" @click="addQuestionOption(qIndex)" />
                    </div>
                </div>

                <Button :label="t('pages.forums.btn_add_question')" icon="pi pi-plus-circle" class="w-full p-button-outlined p-button-secondary border-dashed border-2 bg-white hover:bg-slate-50 py-8 font-black text-slate-600 rounded-xl text-lg transition-all shadow-sm" @click="addQuizQuestion" />
            </div>

            <template #footer>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center font-black text-slate-700">{{ quizCreateForm.questions.length }}</div>
                    <div class="hidden sm:flex flex-col">
                        <span class="font-black text-slate-800 text-sm">{{ t('pages.forums.questions_configured') }}</span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ t('pages.forums.ready_publish') }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('pages.forums.btn_cancel')" text class="font-bold text-slate-500" @click="createQuizDialog = false" />
                    <Button :label="t('pages.forums.btn_publish_quiz')" icon="pi pi-check-circle" class="font-black" @click="saveInstructorQuiz" :loading="quizCreateForm.processing" :disabled="!quizCreateForm.course_id || !quizCreateForm.title.trim()" />
                </div>
            </template>
        </SncDialog>

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
