<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - COHORT & GROUP ARCHITECT v15.0.0 (ULTIMATE ENTERPRISE EDITION)
 * ======================================================================================
 * - 100% Inertia.js (0% Axios, 0% API externes).
 * - Utilise `preserveState` et `preserveScroll` pour une UX fluide sans rechargement.
 * - Synchronisation dynamique des données via la réactivité des props Inertia.
 *
 * @author  SubnetCongo Framework Division
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';
import { FilterMatchMode } from '@primevue/core/api';
// ======================================================================================
// PRIMEVUE COMPONENTS & DIRECTIVES (Adapté v4)
// ======================================================================================
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select'; // Remplaçant de Dropdown sous v4
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import SelectButton from 'primevue/selectbutton';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Breadcrumb from 'primevue/breadcrumb';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ProgressBar from 'primevue/progressbar';
import ToggleSwitch from 'primevue/toggleswitch';
import vTooltip from 'primevue/tooltip'; // Préfixé par "v" pour l'auto-enregistrement sous script setup
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Menu from 'primevue/menu';
import DatePicker from 'primevue/datepicker'; // Remplaçant de Calendar sous v4
import Chart from 'primevue/chart';
import Message from 'primevue/message'; // Remplaçant d'InlineMessage sous v4
import Timeline from 'primevue/timeline';
import Editor from 'primevue/editor';
import FileUpload from 'primevue/fileupload';
// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    cohorts: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    instructorsList: { type: Array, default: () => [] },
    coursesList: { type: Array, default: () => [] },
    allStudentsList: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['Actif', 'Programmé', 'Terminé', 'Suspendu'] },
    stats: { type: Object, default: () => ({ total_cohorts: 0, total_enrolled_students: 0, avg_cohort_completion: 0, active_courses_in_cohorts: 0 }) },
    filters: { type: Object, default: () => ({}) }
});
// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const selectedCohorts = ref([]);
const expandedRows = ref([]);
// Visibilité des Dialogs
const cohortEditorDialog = ref(false);
const profileDialog = ref(false);
const messageDialog = ref(false);
const addStudentDialog = ref(false);
const liveSessionDialog = ref(false);
const transferDialog = ref(false);
const prereqEditDialog = ref(false);
const activeCohortId = ref(null);
const currentCohort = ref(null);
const lastSavedAt = ref(null);
const studentActionMenu = ref();
const selectedStudentForAction = ref(null);
// Données réactives pour le profil 360°
const cohortMembers = ref([]);
const cohortDocuments = ref([]);
const cohortLiveSessions = ref([]);
const cohortAuditLogs = ref([]);
const cohortPrerequisites = ref([]);
// ======================================================================================
// FORMULAIRES (INERTIA)
// ======================================================================================
const cohortForm = useForm({
    id: null, name: '', description: '', status: 'Programmé', instructor_id: null,
    assistant_ids: [], courses: [], prerequisites: [],
    start_date: null, end_date: null,
    max_students: 50, is_private: false, auto_enrollment: true, pass_percentage: 70
});
const messageForm = useForm({ cohort_id: null, subject: '', body: '', send_email: true, send_notification: true });
const studentEnrollForm = useForm({ cohort_id: null, student_ids: [], notify_students: true });
const liveSessionForm = useForm({
    id: null,
    title: '',
    course_id: null,
    instructor_id: null,
    start_at: null,
    duration: 60,
    provider: 'zoom',
    max_participants: 50,
    is_recorded: false,
    passcode: '',
    settings: {
        host_video: '1',
        participant_video: '1',
        mute_upon_entry: '1',
        waiting_room: '1'
    }
});
// ======================================================================================
// FILTRAGE & RECHERCHE SERVEUR (INERTIA)
// ======================================================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    instructor_id: props.filters?.instructor_id || null
});
watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('users.cohorts.index'), serverFilters, { preserveState: true, preserveScroll: true, replace: true, onFinish: () => loading.value = false });
}, 400));
// Réinitialisation de l'ID lors de la fermeture du profil
watch(profileDialog, (isVisible) => {
    if (!isVisible) {
        activeCohortId.value = null;
    }
});
// Source de vérité réactive pour la cohorte active
const activeCohortFromProps = computed(() => {
    if (!activeCohortId.value) return null;
    return props.cohorts.data.find(c => c.id === activeCohortId.value);
});
// Synchronisation de l'état local du dialogue avec les modifications réactives des props
watch(activeCohortFromProps, (newCohortData) => {
    if (newCohortData) {
        currentCohort.value = cloneDeep(newCohortData);
        cohortLiveSessions.value = newCohortData.live_sessions || [];
        cohortMembers.value = newCohortData.members || [];
        cohortDocuments.value = newCohortData.documents || [];
        cohortAuditLogs.value = newCohortData.audit_logs || [];
        cohortPrerequisites.value = newCohortData.prerequisites || [];
        updateProfileCharts(newCohortData);
    }
}, { deep: true });
// ======================================================================================
// CONFIGURATION DU WORKSPACE ÉDITEUR (STEPPER)
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: 'Identité de la Cohorte', description: 'Nom, Description et Statut', icon: 'pi pi-id-card' },
    { id: 'pedagogy', label: 'Équipe Pédagogique', description: 'Instructeur principal et Tuteurs', icon: 'pi pi-users' },
    { id: 'curriculum', label: 'Curriculum & Prérequis', description: 'Cours et conditions d\'accès', icon: 'pi pi-book' },
    { id: 'planning', label: 'Planification', description: 'Dates de début et de fin', icon: 'pi pi-calendar' },
    { id: 'settings', label: 'Paramètres Avancés', description: 'Capacité et inscriptions', icon: 'pi pi-cog' }
]);
const activeEditorTab = ref('general');
const tabValidation = computed(() => ({
    general: cohortForm.name?.trim().length > 3 && cohortForm.status !== null,
    pedagogy: cohortForm.instructor_id !== null,
    curriculum: cohortForm.courses.length > 0,
    planning: cohortForm.start_date !== null,
    settings: cohortForm.max_students > 0
}));
const isEntireFormValid = computed(() => Object.values(tabValidation.value).every(Boolean));
// ======================================================================================
// UTILS : SÉRIALISATION CORRECTE DES DATES (évite le décalage de timezone)
// ======================================================================================
const formatLocalDateToYYYYMMDD = (date) => {
    if (!date) return null;
    const d = new Date(date);
    if (isNaN(d.getTime())) return null;
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
// ======================================================================================
// MÉTHODES CRUD COHORTES (INERTIA)
// ======================================================================================
const openNewCohort = () => {
    cohortForm.reset(); cohortForm.id = null; activeEditorTab.value = 'general'; lastSavedAt.value = null; cohortEditorDialog.value = true;
};
const editCohort = (data) => {
    cohortForm.clearErrors();
    const cData = cloneDeep(data);
    cohortForm.id = cData.id; cohortForm.name = cData.name || ''; cohortForm.description = cData.description || '';
    cohortForm.status = cData.status || 'Programmé'; cohortForm.instructor_id = cData.instructor_id || null;
    cohortForm.assistant_ids = cData.assistant_ids || []; cohortForm.courses = cData.assigned_courses?.map(c => c.id) || [];
    cohortForm.prerequisites = cData.prerequisites || [];
    cohortForm.start_date = cData.start_date ? new Date(cData.start_date) : null;
    cohortForm.end_date = cData.end_date ? new Date(cData.end_date) : null;
    cohortForm.max_students = cData.max_students || 50; cohortForm.is_private = cData.is_private || false;
    cohortForm.auto_enrollment = cData.auto_enrollment !== false; cohortForm.pass_percentage = cData.pass_percentage || 70;
    activeEditorTab.value = 'general'; cohortEditorDialog.value = true;
};
const saveCohort = () => {
    if (!isEntireFormValid.value) { toast.add({ severity: 'warn', summary: 'Erreur', detail: 'Formulaire incomplet.' }); return; }
    const url = cohortForm.id ? route('users.cohorts.update', cohortForm.id) : route('users.cohorts.store');
    cohortForm.transform((data) => ({
        ...data,
        _method: cohortForm.id ? 'PUT' : 'POST',
        start_date: formatLocalDateToYYYYMMDD(data.start_date),
        end_date: formatLocalDateToYYYYMMDD(data.end_date),
    })).post(url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Succès', detail: `Cohorte sauvegardée.` }); cohortEditorDialog.value = false; }
    });
};
// ======================================================================================
// GESTION 360 : OUVERTURE DU PROFIL
// ======================================================================================
const openProfileDialog = (cohortData) => {
    activeCohortId.value = cohortData.id;
    profileDialog.value = true;
    loading.value = true;
    router.get(route('users.cohorts.index'), { ...serverFilters, selected_id: cohortData.id }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => loading.value = false,
    });
};
// Filtrage local des étudiants
const studentSearchQuery = ref('');
const filteredCohortMembers = computed(() => {
    const members = Array.isArray(cohortMembers.value) ? cohortMembers.value : [];
    if (!studentSearchQuery.value?.trim()) {
        return [...members]; // Important: retourne une copie pour éviter les mutations inattendues par le DataTable
    }
    const query = studentSearchQuery.value.trim().toLowerCase();
    return members.filter(member => {
        const name = (member.name || "").toLowerCase();
        const email = (member.email || "").toLowerCase();
        return name.includes(query) || email.includes(query);
    });
});
// Graphiques
const profileCharts = reactive({ line: {}, doughnut: {} });
const updateProfileCharts = (cohort) => {
    if (!cohort) return;
    profileCharts.line = {
        labels: ['S1', 'S2', 'S3', 'S4'],
        datasets: [{ label: 'Engagement (%)', data: [20, 45, 60, cohort.avg_completion || 80], borderColor: '#10B981', fill: true, backgroundColor: 'rgba(16, 185, 129, 0.1)' }]
    };
    profileCharts.doughnut = {
        labels: ['Excellents', 'Moyens', 'Difficulté'],
        datasets: [{ data: [cohort.excellent_count || 15, cohort.average_count || 25, cohort.difficult_count || 10], backgroundColor: ['#10B981', '#F59E0B', '#EF4444'] }]
    };
};
// ======================================================================================
// GESTION DES ÉTUDIANTS (INERTIA WEB ROUTES)
// ======================================================================================
const openAddStudentDialog = () => {
    studentEnrollForm.reset();
    studentEnrollForm.cohort_id = currentCohort.value.id;
    addStudentDialog.value = true;
};
const enrollStudents = () => {
    studentEnrollForm.post(route('users.cohorts.members.add', currentCohort.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `Étudiants enrôlés.` });
            addStudentDialog.value = false;
        }
    });
};
const toggleStudentMenu = (event, student) => {
    selectedStudentForAction.value = student;
    studentActionMenu.value.toggle(event);
};
const studentMenuItems = computed(() => [
    { label: 'Transférer de cohorte', icon: 'pi pi-arrow-right-arrow-left', command: () => openTransferDialog(selectedStudentForAction.value) },
    { label: 'Suspendre l\'accès', icon: 'pi pi-lock', command: () => suspendStudent(selectedStudentForAction.value) },
    { separator: true },
    { label: 'Retirer du groupe', icon: 'pi pi-trash', class: 'text-rose-500', command: () => confirmRemoveStudent(selectedStudentForAction.value) }
]);
const suspendStudent = (student) => {
    router.patch(route('users.cohorts.members.suspend', { cohort: currentCohort.value.id, student: student.id }), {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'warn', summary: 'Suspendu', detail: 'Accès bloqué pour cet étudiant.' })
    });
};
const confirmRemoveStudent = (student) => {
    confirm.require({
        message: `Retirer ${student.name} de cette cohorte ?`, header: 'Retrait', icon: 'pi pi-user-minus', acceptClass: 'p-button-danger',
        accept: () => router.delete(route('users.cohorts.members.remove', { cohort: currentCohort.value.id, student: student.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Retiré', detail: 'Étudiant retiré.' })
        })
    });
};
// TRANSFERT
const transferFormObject = useForm({ student_id: null, to_cohort_id: null, transfer_progress: true });
const openTransferDialog = (student) => {
    transferFormObject.reset();
    transferFormObject.student_id = student.id;
    transferDialog.value = true;
};
const executeTransfer = () => {
    transferFormObject.post(route('users.cohorts.members.transfer'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Transféré', detail: 'Déplacement réussi.' });
            transferDialog.value = false;
        }
    });
};
// ======================================================================================
// GESTION DES DOCUMENTS (INERTIA POST)
// ======================================================================================
const onDocumentUpload = (event) => {
    const file = event.files[0];
    router.post(route('users.cohorts.documents.store', currentCohort.value.id), {
        file: file,
    }, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Upload réussi', detail: 'Document ajouté.' }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'upload.' })
    });
};
const downloadDocument = (doc) => {
    window.open(route('users.cohorts.documents.download', doc.id), '_blank');
};
const deleteDocument = (doc) => {
    confirm.require({
        message: `Supprimer le document "${doc.name}" ?`, header: 'Confirmation', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('users.cohorts.documents.destroy', { cohort: currentCohort.value.id, document: doc.id }), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Document supprimé.' })
            });
        }
    });
};
const getDocIcon = (type) => {
    if (type?.startsWith('image')) return 'pi pi-image';
    if (type?.includes('pdf')) return 'pi pi-file-pdf';
    if (type?.includes('zip')) return 'pi pi-box';
    return 'pi pi-file';
};
// ======================================================================================
// GESTION DES SESSIONS LIVE (INERTIA POST)
// ======================================================================================
const openLiveSessionDialog = (session = null) => {
    liveSessionForm.reset();
    if (currentCohort.value) {
        if (session) {
            liveSessionForm.id = session.id;
            liveSessionForm.title = session.title;
            liveSessionForm.course_id = session.course_id;
            liveSessionForm.instructor_id = session.instructor_id;
            liveSessionForm.start_at = session.session_date ? new Date(session.session_date) : null;
            liveSessionForm.duration = session.duration || 60;
            liveSessionForm.meeting_url = session.meeting_url;
        } else {
            liveSessionForm.instructor_id = currentCohort.value.instructor_id;
            if (currentCohort.value.assigned_courses?.length > 0) {
                liveSessionForm.course_id = currentCohort.value.assigned_courses[0].id;
            }
        }
    }
    liveSessionDialog.value = true;
};
const saveLiveSession = () => {
    if (!liveSessionForm.title || !liveSessionForm.start_at || !liveSessionForm.course_id || !liveSessionForm.instructor_id) {
        toast.add({ severity: 'error', summary: 'Champs requis', detail: 'Titre, Date, Cours et Instructeur sont obligatoires.' });
        return;
    }
    const url = liveSessionForm.id
        ? route('users.cohorts.live-sessions.update', { cohort: currentCohort.value.id, live_session: liveSessionForm.id })
        : route('users.cohorts.live-sessions.store', currentCohort.value.id);
    liveSessionForm
        .transform((data) => ({
            ...data,
            _method: liveSessionForm.id ? 'PUT' : 'POST',
            start_at: data.start_at ? data.start_at.toISOString() : null,
        }))
        .post(url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                liveSessionDialog.value = false;
                toast.add({ severity: 'success', summary: liveSessionForm.id ? 'Mis à jour' : 'Planifié', detail: 'La session live a été enregistrée.' });
                liveSessionForm.reset();
            },
            onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les informations.' })
        });
};
const deleteLiveSession = (session) => {
    confirm.require({
        message: `Supprimer la session "${session.title}" ?`, header: 'Confirmation', icon: 'pi pi-exclamation-triangle', acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('users.cohorts.live-sessions.destroy', { cohort: currentCohort.value.id, live_session: session.id }), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Session live supprimée.' })
            });
        }
    });
};
// ======================================================================================
// GESTION DES PRÉREQUIS (INERTIA PUT)
// ======================================================================================
const openPrereqEditor = () => prereqEditDialog.value = true;
const savePrerequisites = () => {
    router.put(route('users.cohorts.prerequisites.update', currentCohort.value.id), {
        prerequisites: cohortPrerequisites.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Mis à jour', detail: 'Prérequis enregistrés.' });
            prereqEditDialog.value = false;
        }
    });
};
// ======================================================================================
// UTILITAIRES
// ======================================================================================
const getSeverity = (s) => ({ 'Actif': { bg: 'bg-emerald-50 text-emerald-700 border-emerald-200', icon: 'pi-check-circle' }, 'Programmé': { bg: 'bg-blue-50 text-blue-700 border-blue-200', icon: 'pi-calendar' }, 'Terminé': { bg: 'bg-slate-50 text-slate-700 border-slate-200', icon: 'pi-flag-fill' }, 'Suspendu': { bg: 'bg-rose-50 text-rose-700 border-rose-200', icon: 'pi-lock' } }[s] || { bg: 'bg-slate-50 text-slate-700', icon: 'pi-info' });
const formatDate = (date) => date ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(date)) : '-';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??';
onMounted(() => { setTimeout(() => loading.value = false, 400); });
</script>

<template>
    <AppLayout>
        <Head title="Architecte de Cohortes | LMS Enterprise" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- PAGE HEADER -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Organisation' }]" class="bg-transparent p-0 border-none text-xs text-slate-500 mb-2" />
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        Cohortes & Promotions <Badge value="Group Architect" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                    </h1>
                </div>
                <Button label="Créer une Cohorte" icon="pi pi-plus-circle" class="p-button-primary shadow-lg shadow-indigo-500/30 px-6 font-bold" @click="openNewCohort" />
            </div>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between group hover:shadow-lg hover:border-indigo-300 transition-all duration-300">
                    <div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Groupes</p>
                        <h3 class="text-4xl font-black text-slate-800 mt-2">{{ props.stats.total_cohorts || 0 }}</h3>
                    </div>
                    <div class="p-4 rounded-2xl bg-indigo-50 text-indigo-500 group-hover:scale-110 transition-transform">
                        <i class="pi pi-objects-column text-2xl"></i>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between group hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
                    <div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Élèves Groupés</p>
                        <h3 class="text-4xl font-black text-slate-800 mt-2">{{ props.stats.total_enrolled_students || 0 }}</h3>
                    </div>
                    <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-500 group-hover:scale-110 transition-transform">
                        <i class="pi pi-users text-2xl"></i>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex items-center justify-between group hover:shadow-lg hover:border-amber-300 transition-all duration-300">
                    <div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Cours Assignés</p>
                        <h3 class="text-4xl font-black text-slate-800 mt-2">{{ props.stats.active_courses_in_cohorts || 0 }}</h3>
                    </div>
                    <div class="p-4 rounded-2xl bg-amber-50 text-amber-500 group-hover:scale-110 transition-transform">
                        <i class="pi pi-book text-2xl"></i>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-center group hover:shadow-lg hover:border-rose-300 transition-all duration-300">
                    <div class="flex justify-between items-center">
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Complétion Moyenne</p>
                        <h3 class="text-2xl font-black text-slate-800">{{ props.stats.avg_cohort_completion || 0 }}%</h3>
                    </div>
                    <ProgressBar :value="props.stats.avg_cohort_completion || 0" :showValue="false" class="h-2 mt-3 [&>div]:bg-rose-500" />
                </div>
            </div>

            <!-- DATATABLE DES COHORTES -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50/50 border-b border-slate-200 p-6 flex flex-wrap justify-between items-center gap-4">
                    <IconField iconPosition="left" class="w-full md:w-80">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText v-model="serverFilters.search" placeholder="Rechercher une promotion..." class="w-full border-slate-200 shadow-sm" />
                    </IconField>
                    <div class="flex gap-3">
                        <Dropdown v-model="serverFilters.status" :options="props.statuses" placeholder="Statut" showClear class="w-40 border-slate-200 shadow-sm" />
                        <Button icon="pi pi-megaphone" label="Annonce Groupée" class="p-button-outlined p-button-warning shadow-sm font-bold bg-white" @click="messageDialog = true" :disabled="!selectedCohorts.length" />
                    </div>
                </div>

                <DataTable v-model:selection="selectedCohorts" v-model:expandedRows="expandedRows" :value="props.cohorts.data" dataKey="id" :loading="loading" class="p-datatable-custom border-none" stripedRows rowHover>
                    <template #empty><div class="p-20 text-center text-slate-500"><i class="pi pi-users text-5xl mb-4 text-slate-300"></i><h3 class="text-xl font-bold text-slate-800">Aucune cohorte trouvée</h3></div></template>
                    <Column selectionMode="multiple" headerStyle="width: 3rem;" />
                    <Column expander style="width: 3rem;" />

                    <Column field="name" header="Identité" style="min-width: 20rem;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1 py-2">
                                <span class="font-black text-slate-800 text-lg hover:text-indigo-600 cursor-pointer" @click="openProfileDialog(data)">{{ data.name }}</span>
                                <div class="flex items-center gap-2">
                                    <Tag :value="data.status" :class="getSeverity(data.status).bg" class="text-[9px] uppercase font-bold border px-2 py-0.5" />
                                    <span v-if="data.is_private" class="text-[10px] text-amber-500 font-bold"><i class="pi pi-lock text-[9px]"></i> Privé</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="instructor" header="Équipe Pédagogique" style="min-width: 15rem;">
                        <template #body="{ data }">
                            <div v-if="data.instructor" class="flex items-center gap-3">
                                <Avatar :image="data.instructor.avatar_path" :label="data.instructor.name.charAt(0)" shape="circle" class="bg-indigo-50 text-indigo-700" />
                                <div><span class="font-bold text-slate-700 text-sm block">{{ data.instructor.name }}</span><span class="text-[9px] uppercase tracking-widest text-slate-400">Instructeur Principal</span></div>
                            </div>
                            <span v-else class="text-xs text-slate-400 italic">Non assigné</span>
                        </template>
                    </Column>

                    <Column field="metrics" header="Engagement" style="min-width: 12rem;">
                        <template #body="{ data }">
                            <div class="flex flex-col w-full gap-1">
                                <div class="flex justify-between text-[10px] font-bold"><span class="text-slate-500">Membres: {{ data.members_count || 0 }}/{{ data.max_students }}</span><span class="text-emerald-500">Score: {{ data.avg_score || 0 }}%</span></div>
                                <ProgressBar :value="data.avg_completion || 0" :showValue="false" class="h-1.5 bg-slate-100 [&>div]:bg-indigo-500" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="width: 10rem; text-align: right;">
                        <template #body="{ data }">
                            <Button icon="pi pi-chart-pie" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-indigo-600" v-tooltip="'Vue 360°'" @click="openProfileDialog(data)" />
                            <Button icon="pi pi-cog" class="p-button-text p-button-secondary p-button-rounded text-slate-500" v-tooltip="'Configurer'" @click="editCohort(data)" />
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (APERÇU DU CURRICULUM) -->
                    <template #expansion="{ data }">
                        <div class="p-6 bg-slate-50/80 border-y border-slate-200 shadow-inner">
                            <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-4"><i class="pi pi-book text-indigo-600"></i> Curriculum Assigné</h4>
                            <div v-if="data.assigned_courses?.length" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div v-for="course in data.assigned_courses" :key="course.id" class="bg-white p-4 rounded-xl border border-slate-200 flex items-start gap-3 shadow-sm">
                                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0"><i class="pi pi-video"></i></div>
                                    <div><div class="font-bold text-sm text-slate-800 line-clamp-1">{{ course.title }}</div><div class="text-xs text-slate-500"><i class="pi pi-clock"></i> {{ course.duration || 0 }}h</div></div>
                                </div>
                            </div>
                            <div v-else class="text-slate-500 text-sm italic p-4 bg-white border border-dashed border-slate-300 rounded-xl text-center">Aucun cours assigné.</div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG 1 : WORKSPACE ÉDITEUR DE COHORTE (STEPPER UI)                           -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="cohortEditorDialog" modal :closable="false" :draggable="false" :style="{ width: '95vw', maxWidth: '1200px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[85vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '25%' }"></div>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white"><i class="pi pi-objects-column"></i></div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight">{{ cohortForm.id ? 'Configuration de la Cohorte' : 'Générer une Nouvelle Cohorte' }}</h2>
                        <div class="text-[11px] font-medium text-slate-500 mt-0.5">Étape active : {{ editorMenu.find(m => m.id === activeEditorTab)?.label }}</div>
                    </div>
                </div>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="cohortEditorDialog = false" />
            </div>

            <div class="flex-1 flex overflow-hidden relative">
                <!-- SIDEBAR STEPPER -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-sm">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">Étapes</div>
                    <div class="p-4 flex-1 space-y-2">
                        <button v-for="item in editorMenu" :key="item.id" @click="activeEditorTab = item.id" class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden" :class="activeEditorTab === item.id ? 'bg-indigo-50 border border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'">
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>
                            <div class="relative mt-0.5"><i :class="[item.icon, activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400']" class="text-xl"></i><div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white"><i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px]"></i><i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px]"></i></div></div>
                            <div class="flex-1"><div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div><div class="text-[11px] text-slate-500 leading-tight">{{ item.description }}</div></div>
                        </button>
                    </div>
                </div>

                <!-- MAIN CONTENT AREA -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative scroll-smooth bg-slate-50">
                    <div class="max-w-4xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <div v-show="activeEditorTab === 'general'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-id-card text-indigo-500"></i> Informations Générales</h3>
                                <div class="space-y-6">
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Nom de la promotion <span class="text-rose-500">*</span></label><InputText v-model="cohortForm.name" class="w-full p-4 text-lg font-bold bg-slate-50" placeholder="Ex: Masterclass Dev 2026" /></div>
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Description</label><Editor v-model="cohortForm.description" editorStyle="height: 150px" /></div>
                                    <div class="w-full md:w-1/2"><label class="block text-sm font-bold text-slate-700 mb-2">Statut <span class="text-rose-500">*</span></label><Dropdown v-model="cohortForm.status" :options="props.statuses" class="w-full font-medium bg-slate-50" /></div>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeEditorTab === 'pedagogy'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-users text-blue-500"></i> Équipe Pédagogique</h3>
                                <div class="space-y-6">
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Instructeur Référent <span class="text-rose-500">*</span></label><Dropdown v-model="cohortForm.instructor_id" :options="props.instructorsList" optionLabel="name" optionValue="id" filter class="w-full bg-slate-50" placeholder="Rechercher..." /></div>
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Tuteurs / Assistants (Optionnel)</label><MultiSelect v-model="cohortForm.assistant_ids" :options="props.instructorsList" optionLabel="name" optionValue="id" filter display="chip" class="w-full bg-slate-50" placeholder="Sélectionner des assistants..." /></div>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeEditorTab === 'curriculum'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-purple-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-book text-purple-500"></i> Curriculum & Évaluation</h3>
                                <div class="space-y-6">
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Cours assignés à la cohorte <span class="text-rose-500">*</span></label><MultiSelect v-model="cohortForm.courses" :options="props.coursesList" optionLabel="title" optionValue="id" filter display="chip" class="w-full bg-slate-50" placeholder="Sélectionner les modules..." /></div>
                                    <Divider />
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Score minimum de validation (%)</label><InputNumber v-model="cohortForm.pass_percentage" :min="0" :max="100" suffix=" %" showButtons class="w-40 bg-slate-50" /></div>
                                    <Divider />
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Prérequis de la cohorte</label>
                                        <p class="text-xs text-slate-500 mb-3">Sélectionnez les cours que les étudiants doivent valider avant de s'inscrire.</p>
                                        <MultiSelect v-model="cohortForm.prerequisites" :options="props.coursesList" optionLabel="title" optionValue="id" filter display="chip" class="w-full bg-slate-50" placeholder="Cours exigés en prérequis..." />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeEditorTab === 'planning'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-calendar text-emerald-500"></i> Calendrier</h3>
                                <div class="grid grid-cols-2 gap-6">
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Début officiel <span class="text-rose-500">*</span></label><Calendar v-model="cohortForm.start_date" dateFormat="dd/mm/yy" showIcon class="w-full" /></div>
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Fin (Optionnel)</label><Calendar v-model="cohortForm.end_date" dateFormat="dd/mm/yy" showIcon class="w-full" :minDate="cohortForm.start_date" /></div>
                                </div>
                            </div>
                        </div>

                        <div v-show="activeEditorTab === 'settings'" class="space-y-8 animate-fade-in">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-cog text-amber-500"></i> Paramètres & Inscriptions</h3>
                                <div class="space-y-6">
                                    <div><label class="block text-sm font-bold text-slate-700 mb-2">Capacité Maximale <span class="text-rose-500">*</span></label><InputNumber v-model="cohortForm.max_students" showButtons class="w-40 bg-slate-50" /></div>
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200"><div class="flex gap-4"><div class="w-10 h-10 bg-white border border-slate-200 text-indigo-500 rounded-full flex items-center justify-center shadow-sm shrink-0"><i class="pi pi-lock"></i></div><div><div class="font-bold text-slate-800 text-sm">Cohorte Privée</div><div class="text-xs text-slate-500 max-w-md">Masquer du catalogue public.</div></div></div><ToggleSwitch v-model="cohortForm.is_private" /></div>
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200"><div class="flex gap-4"><div class="w-10 h-10 bg-white border border-slate-200 text-emerald-500 rounded-full flex items-center justify-center shadow-sm shrink-0"><i class="pi pi-sync"></i></div><div><div class="font-bold text-slate-800 text-sm">Inscription Automatique</div><div class="text-xs text-slate-500 max-w-md">Email automatique d'accès.</div></div></div><ToggleSwitch v-model="cohortForm.auto_enrollment" /></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <span v-if="!isEntireFormValid" class="text-xs font-bold text-amber-600 flex items-center gap-2"><i class="pi pi-exclamation-triangle"></i> Complétez la configuration.</span>
                <span v-else class="text-xs font-bold text-emerald-500 flex items-center gap-2"><i class="pi pi-check"></i> Prêt à enregistrer.</span>
                <div class="flex items-center gap-3">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="cohortEditorDialog = false" />
                    <Button label="Enregistrer la Cohorte" icon="pi pi-save" class="p-button-primary shadow-lg font-bold px-8" @click="saveCohort" :disabled="!isEntireFormValid" :loading="cohortForm.processing" />
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG 2 : PROFIL 360° (WORKSPACE COMPLET AVEC TABS ET GRAPHIQUES)             -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="profileDialog" modal maximizable :draggable="false" :style="{ width: '98vw', height: '98vh', maxWidth: '1600px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-full" headerClass="hidden" class="rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            <div class="flex-none h-[100px] bg-slate-900 border-b border-slate-800 flex items-center justify-between px-8 z-20 shadow-lg relative">
                <i class="pi pi-objects-column absolute -bottom-10 -right-10 text-9xl text-white/5 pointer-events-none"></i>
                <div class="flex items-center gap-6 relative z-10">
                    <div class="w-14 h-14 bg-indigo-500/20 border border-indigo-400/30 rounded-2xl flex items-center justify-center shadow-inner"><i class="pi pi-building text-2xl text-indigo-300"></i></div>
                    <div>
                        <div class="flex items-center gap-3 mb-1"><Tag :value="currentCohort?.status" severity="info" class="text-[9px] uppercase font-bold tracking-widest bg-indigo-500/30 text-indigo-100 border border-indigo-400" /><span class="text-[10px] text-slate-400 font-mono">#ID-{{ currentCohort?.id }}</span></div>
                        <h2 class="text-2xl font-black text-white m-0 tracking-tight">{{ currentCohort?.name }}</h2>
                    </div>
                </div>
                <div class="flex items-center gap-3 relative z-10">
                    <Button icon="pi pi-megaphone" label="Diffuser Annonce" class="p-button-warning p-button-outlined text-amber-400 border-amber-400 font-bold" @click="messageDialog = true; messageForm.cohort_id = currentCohort?.id" />
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text text-white hover:bg-white/10" @click="profileDialog = false" />
                </div>
            </div>

            <div class="flex-1 overflow-hidden relative flex flex-col" v-if="currentCohort">
                <TabView class="h-full flex flex-col custom-tabview-enterprise w-full">

                    <TabPanel>
                        <template #header><i class="pi pi-chart-pie mr-2"></i> Tableau de bord</template>
                        <div class="p-8 h-[calc(98vh-160px)] overflow-y-auto custom-scrollbar">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0"><i class="pi pi-users"></i></div><div><p class="text-xs font-bold text-slate-500 uppercase">Effectif actuel</p><h3 class="text-2xl font-black text-slate-800">{{ currentCohort.members_count || cohortMembers.length }} / {{ currentCohort.max_students }}</h3></div></div>
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0"><i class="pi pi-check-circle"></i></div><div><p class="text-xs font-bold text-slate-500 uppercase">Complétion Moy.</p><h3 class="text-2xl font-black text-slate-800">{{ currentCohort.avg_completion || 0 }}%</h3></div></div>
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0"><i class="pi pi-calendar-clock"></i></div><div><p class="text-xs font-bold text-slate-500 uppercase">Début Officiel</p><h3 class="text-xl font-black text-slate-800">{{ formatDate(currentCohort.start_date) }}</h3></div></div>
                            </div>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm"><h4 class="font-bold text-slate-700 mb-4">Progression dans le temps</h4><Chart type="line" :data="profileCharts.line" class="h-64" /></div>
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm"><h4 class="font-bold text-slate-700 mb-4">Répartition des niveaux</h4><Chart type="doughnut" :data="profileCharts.doughnut" class="h-64 flex justify-center" /></div>
                            </div>
                        </div>
                    </TabPanel>

                   <TabPanel>
    <template #header>
        <i class="pi pi-users mr-2"></i>
        Étudiants inscrits
    </template>

    <div class="p-8 h-[calc(98vh-160px)] flex flex-col">

        <!-- Barre d'outils -->
        <div
            class="flex justify-between items-center bg-white p-4 rounded-t-2xl border border-b-0 border-slate-200 shadow-sm"
        >
            <IconField iconPosition="left">
                <InputIcon class="pi pi-search text-slate-400" />
                <InputText
                    v-model="studentSearchQuery"
                    placeholder="Rechercher un étudiant..."
                    class="p-inputtext-sm border-slate-200 w-64"
                />
            </IconField>

            <Button
                label="Enrôler des étudiants"
                icon="pi pi-user-plus"
                class="p-button-primary p-button-sm font-bold shadow-md"
                @click="openAddStudentDialog"
            />
        </div>

        <!-- Tableau -->
       <DataTable
            :value="filteredCohortMembers"
            dataKey="id"
            :paginator="true"
            :rows="8"
            stripedRows
            scrollable
            scrollHeight="flex"
            class="p-datatable-sm border-x border-b border-slate-200 rounded-b-2xl shadow-sm flex-1"
        >
            <!-- Etudiant -->
            <Column header="Étudiant" style="min-width:18rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-3">
                        <Avatar
                            :image="data.avatar_path"
                            :label="getInitials(data.name)"
                            shape="circle"
                            class="bg-slate-100 text-slate-600 border border-slate-200"
                        />
                        <div>
                            <div class="font-bold text-slate-800">{{ data.name }}</div>
                            <div class="text-xs text-slate-500">{{ data.email }}</div>
                        </div>
                    </div>
                </template>
            </Column>

            <!-- Progression -->
            <Column header="Progression" style="width:18rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <ProgressBar
                            :value="data.pivot?.progress ?? 0"
                            :showValue="false"
                            class="w-full h-2 [&>div]:bg-indigo-500"
                        />
                        <span class="text-xs font-bold text-slate-600 w-12 text-right">{{ data.pivot?.progress ?? 0 }}%</span>
                    </div>
                </template>
            </Column>

            <!-- Date -->
            <Column field="pivot.enrolled_at" header="Inscrit le" style="width:10rem">
                <template #body="{ data }">
                    <span class="text-xs text-slate-500">
                        <!-- {{ formatDate(data.pivot?.enrolled_at) }} -->
                    </span>
                </template>
            </Column>

            <Column header="Statut" style="width:8rem">
                <template #body="{ data }">
                    <Tag v-if="data.pivot?.status === 'active'" value="Actif" severity="success" />
                    <Tag v-else-if="data.pivot?.status === 'suspended'" value="Suspendu" severity="warning" />
                    <Tag v-else :value="data.pivot?.status" severity="secondary" />
                </template>
            </Column>

            <!-- Actions -->
            <Column header="Actions" style="width:70px" alignFrozen="right" frozen>
                <template #body="{ data }">
                    <Button icon="pi pi-ellipsis-v" text rounded @click="toggleStudentMenu($event, data)" />
                </template>
            </Column>

            <!-- Aucun résultat -->
            <template #empty>
                <div class="text-center p-8 text-slate-500">
                    Aucun étudiant ne correspond à votre recherche.
                </div>
            </template>
        </DataTable>

        <Menu
            ref="studentActionMenu"
            :model="studentMenuItems"
            popup
        />

    </div>
            </TabPanel>

                    <TabPanel>
                        <template #header><i class="pi pi-video mr-2"></i> Lives & Ressources</template>
                        <div class="p-8 h-[calc(98vh-160px)] overflow-y-auto custom-scrollbar">
                            <div class="flex justify-between items-center mb-6">
                                <div><h3 class="text-lg font-black text-slate-800">Calendrier des Visioconférences</h3><p class="text-sm text-slate-500">Séances exclusives à la cohorte.</p></div>
                                <Button label="Nouvelle Session" icon="pi pi-plus" class="p-button-primary shadow-md font-bold" @click="openLiveSessionDialog" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                <div v-for="session in cohortLiveSessions" :key="session.id" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-colors">
                                    <div class="flex justify-between items-start mb-4"><Tag :value="session.status" :severity="session.status === 'à venir' ? 'info' : 'secondary'" class="text-[9px] uppercase font-bold" /><div class="bg-indigo-50 p-1.5 rounded-lg text-indigo-500 w-8 h-8 flex items-center justify-center"><i class="pi pi-desktop"></i></div></div>
                                    <h4 class="font-bold text-slate-800 text-lg mb-1">{{ session.title }}</h4>
                                    <p class="text-xs font-bold text-indigo-600 mb-4 flex items-center gap-2"><i class="pi pi-calendar"></i> {{ formatDate(session.start_at) }} </p>
                                    <Button label="Rejoindre" icon="pi pi-external-link" class="p-button-sm p-button-outlined p-button-indigo w-full font-bold" @click="window.open(session.meeting_url, '_blank')" />
                                </div>
                                <div v-if="cohortLiveSessions.length === 0" class="col-span-3 text-center p-8 border border-dashed border-slate-300 rounded-xl text-slate-500">Aucune session live programmée.</div>
                            </div>

                            <Divider />
                            <div class="flex justify-between items-center mb-6 mt-8">
                                <div><h3 class="text-lg font-black text-slate-800">Ressources Partagées</h3><p class="text-sm text-slate-500">Documents accessibles à tous les membres.</p></div>
                                <FileUpload mode="basic" auto chooseLabel="Uploader un fichier" class="p-button-sm p-button-secondary p-button-outlined font-bold" @upload="onDocumentUpload" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div v-for="doc in cohortDocuments" :key="doc.id" class="bg-white border border-slate-200 p-4 rounded-xl flex items-center gap-4 shadow-sm hover:bg-slate-50 cursor-pointer group" @click="downloadDocument(doc)">
                                    <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-lg flex items-center justify-center shrink-0">
                                        <i :class="getDocIcon(doc.type)" class="text-xl"></i>
                                    </div>
                                    <div class="overflow-hidden flex-1">
                                        <div class="font-bold text-sm text-slate-800 truncate" :title="doc.name">{{ doc.name }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5">{{ doc.size }}</div>
                                    </div>
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger opacity-0 group-hover:opacity-100 transition-opacity" @click.stop="deleteDocument(doc)" />
                                </div>
                                <div v-if="cohortDocuments.length === 0" class="col-span-4 text-center p-8 bg-white border border-dashed border-slate-300 rounded-xl text-slate-500">Aucun document partagé.</div>
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel>
                        <template #header><i class="pi pi-shield mr-2"></i> Prérequis</template>
                        <div class="p-8 h-[calc(98vh-160px)] overflow-y-auto bg-white m-8 mt-4 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex justify-between items-center mb-6">
                                <div><h3 class="text-lg font-black text-slate-800">Prérequis de la cohorte</h3><p class="text-sm text-slate-500">Cours que les étudiants doivent valider avant de rejoindre.</p></div>
                                <Button label="Modifier les prérequis" icon="pi pi-pencil" class="p-button-primary shadow-md font-bold" @click="openPrereqEditor" />
                            </div>
                            <div v-if="cohortPrerequisites.length > 0" class="space-y-3">
                                <div v-for="courseId in cohortPrerequisites" :key="courseId" class="bg-white p-4 rounded-xl border border-slate-200 flex items-center gap-4 shadow-sm">
                                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center shrink-0"><i class="pi pi-book"></i></div>
                                    <div class="flex-1">
                                        <div class="font-bold text-slate-800">{{ props.coursesList.find(c => c.id === courseId)?.title || 'Cours inconnu' }}</div>
                                        <div class="text-xs text-slate-500">{{ props.coursesList.find(c => c.id === courseId)?.duration || '' }}h</div>
                                    </div>
                                    <Tag value="Requis" severity="warn" />
                                </div>
                            </div>
                            <div v-else class="text-center p-8 border border-dashed border-slate-300 rounded-xl text-slate-500">Aucun prérequis défini.</div>
                        </div>
                    </TabPanel>

                    <TabPanel>
                        <template #header><i class="pi pi-history mr-2"></i> Journal d'Audit</template>
                        <div class="p-8 h-[calc(98vh-160px)] overflow-y-auto bg-white m-8 mt-4 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-black text-slate-800 mb-8">Traçabilité des actions administratives</h3>
                            <Timeline :value="cohortAuditLogs">
                                <template #marker="slotProps"><span class="flex w-8 h-8 items-center justify-center text-white rounded-full shadow-sm" :style="{ backgroundColor: slotProps.item.color || '#4f46e5' }"><i :class="slotProps.item.icon" class="text-sm"></i></span></template>
                                <template #content="slotProps"><div class="mb-6"><div class="font-bold text-slate-800">{{ slotProps.item.action }}</div><div class="text-xs text-slate-500 mt-1 flex items-center gap-2"><i class="pi pi-clock"></i> {{ slotProps.item.date }} | Par: <span class="font-bold text-slate-700">{{ slotProps.item.user }}</span></div></div></template>
                            </Timeline>
                            <div v-if="cohortAuditLogs.length === 0" class="text-center p-8 text-slate-500">Aucune activité enregistrée.</div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>
        </Dialog>

        <!-- DIALOG : AJOUT D'ÉTUDIANTS -->
        <Dialog v-model:visible="addStudentDialog" modal :closable="false" :draggable="false" :style="{ width: '800px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[70vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200">
            <div class="flex-none h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2"><i class="pi pi-user-plus text-indigo-600"></i> Enrôler des Étudiants</h2>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="addStudentDialog = false" />
            </div>
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <InlineMessage severity="info" class="w-full mb-8">Les étudiants sélectionnés auront immédiatement accès au curriculum de la cohorte.</InlineMessage>
                <div class="field mb-8">
                    <label class="font-bold text-slate-700 text-sm block mb-3">Rechercher dans la base globale <span class="text-rose-500">*</span></label>
                    <MultiSelect v-model="studentEnrollForm.student_ids" :options="props.allStudentsList" optionLabel="name" optionValue="id" filter display="chip" placeholder="Tapez le nom d'un étudiant..." class="w-full bg-white p-1">
                        <template #option="slotProps"><div class="flex items-center gap-3"><Avatar :image="slotProps.option.avatar_path" shape="circle" size="small" /><div><div class="font-bold text-sm">{{ slotProps.option.name }}</div><div class="text-[10px] text-slate-500">{{ slotProps.option.email }}</div></div></div></template>
                    </MultiSelect>
                </div>
                <div class="flex items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <ToggleSwitch v-model="studentEnrollForm.notify_students" inputId="notify" />
                    <div><label for="notify" class="font-bold text-sm text-slate-800 cursor-pointer block">Envoyer une notification email</label><span class="text-xs text-slate-500">Avertit les étudiants de leur ajout.</span></div>
                </div>
            </div>
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-end px-6 z-30 gap-3 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="addStudentDialog = false" />
                <Button label="Confirmer l'ajout" icon="pi pi-check" class="p-button-primary shadow-md font-bold px-6" @click="enrollStudents" :disabled="!studentEnrollForm.student_ids.length" :loading="studentEnrollForm.processing" />
            </div>
        </Dialog>

        <!-- DIALOG : TRANSFERT D'ÉTUDIANT -->
        <Dialog v-model:visible="transferDialog" modal :closable="false" :draggable="false" :style="{ width: '600px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[60vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200">
            <div class="flex-none h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2"><i class="pi pi-arrow-right-arrow-left text-amber-500"></i> Transférer l'Étudiant</h2>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="transferDialog = false" />
            </div>
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <InlineMessage severity="warn" class="w-full mb-8">Attention : Cet étudiant sera retiré de la cohorte actuelle et déplacé vers la nouvelle destination.</InlineMessage>
                <div class="field mb-8">
                    <label class="font-bold text-slate-700 text-sm block mb-3">Cohorte de destination <span class="text-rose-500">*</span></label>
                    <Dropdown v-model="transferFormObject.to_cohort_id" :options="props.cohorts.data.filter(c => c.id !== currentCohort?.id)" optionLabel="name" optionValue="id" placeholder="Sélectionnez une cohorte..." class="w-full bg-white" />
                </div>
                <div class="flex items-center justify-between p-5 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div><h4 class="font-bold text-sm text-slate-800">Conserver la progression</h4><p class="text-xs text-slate-500">Garder les scores sur les cours communs.</p></div>
                    <ToggleSwitch v-model="transferFormObject.transfer_progress" />
                </div>
            </div>
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-end px-6 z-30 gap-3 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="transferDialog = false" />
                <Button label="Exécuter le transfert" icon="pi pi-check" class="p-button-warning shadow-md font-bold px-6" @click="executeTransfer" :disabled="!transferFormObject.to_cohort_id" :loading="transferFormObject.processing" />
            </div>
        </Dialog>

        <!-- DIALOG : CREATION SESSION LIVE -->
        <Dialog v-model:visible="liveSessionDialog" modal :closable="false" :draggable="false" :style="{ width: '600px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[80vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200">
            <div class="flex-none h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2"><i class="pi pi-video text-indigo-600"></i> Planifier un Live</h2>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="liveSessionDialog = false" />
            </div>
            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="font-bold text-slate-700 text-sm block mb-2">Titre de la session <span class="text-rose-500">*</span></label>
                        <InputText v-model="liveSessionForm.title" class="w-full bg-white" placeholder="Ex: Masterclass Q&A" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Cours <span class="text-rose-500">*</span></label>
                        <Dropdown v-model="liveSessionForm.course_id" :options="currentCohort?.assigned_courses" optionLabel="title" optionValue="id" placeholder="Choisir le cours" class="w-full bg-white" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Instructeur <span class="text-rose-500">*</span></label>
                        <Dropdown v-model="liveSessionForm.instructor_id" :options="props.instructorsList" optionLabel="name" optionValue="id" placeholder="Choisir l'expert" class="w-full bg-white" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Date & Heure <span class="text-rose-500">*</span></label>
                        <Calendar v-model="liveSessionForm.start_at" showTime hourFormat="24" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Durée (minutes) <span class="text-rose-500">*</span></label>
                        <InputNumber v-model="liveSessionForm.duration" :min="15" :max="480" showButtons class="w-full" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Fournisseur <span class="text-rose-500">*</span></label>
                        <Dropdown v-model="liveSessionForm.provider" :options="[{label: 'Zoom Video', value: 'zoom'}, {label: 'Google Meet', value: 'meet'}, {label: 'BigBlueButton', value: 'bbb'}, {label: 'Jitsi Meet', value: 'jitsi'}]" optionLabel="label" optionValue="value" class="w-full bg-white" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Max Participants</label>
                        <InputNumber v-model="liveSessionForm.max_participants" :min="1" showButtons class="w-full" />
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 text-sm block mb-2">Code d'accès</label>
                        <InputText v-model="liveSessionForm.passcode" class="w-full bg-white" placeholder="Optionnel" />
                    </div>
                    <div class="col-span-2">
                        <label class="font-bold text-slate-700 text-sm block mb-2">Lien de la réunion (URL)</label>
                        <InputText v-model="liveSessionForm.meeting_url" class="w-full bg-white" placeholder="https://zoom.us/j/..." />
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-white border border-slate-200 rounded-xl">
                        <ToggleSwitch v-model="liveSessionForm.is_recorded" />
                        <span class="text-xs font-bold text-slate-700">Enregistrer automatiquement</span>
                    </div>
                </div>
                <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 grid grid-cols-2 gap-4">
                    <div class="flex items-center justify-between col-span-2 text-[10px] font-bold text-indigo-700 uppercase tracking-widest border-b border-indigo-100 pb-2">Réglages Salle</div>
                    <div class="flex items-center justify-between text-xs font-medium text-slate-600"><span>Host Vidéo</span> <ToggleSwitch v-model="liveSessionForm.settings.host_video" trueValue="1" falseValue="0" /></div>
                    <div class="flex items-center justify-between text-xs font-medium text-slate-600"><span>Client Vidéo</span> <ToggleSwitch v-model="liveSessionForm.settings.participant_video" trueValue="1" falseValue="0" /></div>
                    <div class="flex items-center justify-between text-xs font-medium text-slate-600"><span>Muet Entrée</span> <ToggleSwitch v-model="liveSessionForm.settings.mute_upon_entry" trueValue="1" falseValue="0" /></div>
                    <div class="flex items-center justify-between text-xs font-medium text-slate-600"><span>Waiting Room</span> <ToggleSwitch v-model="liveSessionForm.settings.waiting_room" trueValue="1" falseValue="0" /></div>
                </div>
            </div>
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-end px-6 z-30 gap-3 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="liveSessionDialog = false" />
                <Button label="Planifier le Live" icon="pi pi-calendar-plus" class="p-button-primary shadow-md font-bold px-6" @click="saveLiveSession" :disabled="!liveSessionForm.title || !liveSessionForm.start_at" :loading="liveSessionForm.processing" />
            </div>
        </Dialog>

        <!-- DIALOG : ANNONCE DE MASSE -->
        <Dialog v-model:visible="messageDialog" modal :closable="false" :draggable="false" :style="{ width: '700px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[80vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200">
            <div class="flex-none h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2"><i class="pi pi-megaphone text-amber-500"></i> Diffuser une Annonce</h2>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="messageDialog = false" />
            </div>
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar space-y-6">
                <InlineMessage severity="warn" class="w-full mb-4">Ce message sera envoyé à tous les membres de la cohorte.</InlineMessage>
                <div><label class="font-bold text-slate-700 text-sm block mb-2 uppercase tracking-wide">Sujet de l'annonce <span class="text-rose-500">*</span></label><InputText v-model="messageForm.subject" class="w-full font-bold bg-white p-3" autofocus /></div>
                <div><label class="font-bold text-slate-700 text-sm block mb-2 uppercase tracking-wide">Contenu détaillé <span class="text-rose-500">*</span></label><Editor v-model="messageForm.body" editorStyle="height: 200px" /></div>
                <div class="flex items-center gap-6 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-3"><ToggleSwitch v-model="messageForm.send_email" inputId="sendMail" /><label for="sendMail" class="text-sm font-bold text-slate-700 cursor-pointer">Par Email</label></div>
                    <div class="flex items-center gap-3"><ToggleSwitch v-model="messageForm.send_notification" inputId="sendNotif" /><label for="sendNotif" class="text-sm font-bold text-slate-700 cursor-pointer">Notification Push</label></div>
                </div>
            </div>
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-end px-6 z-30 gap-3 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="messageDialog = false" />
                <Button label="Envoyer l'Annonce" icon="pi pi-send" class="p-button-warning shadow-md font-black px-8" @click="messageDialog = false; toast.add({ severity:'success', summary:'Diffusé', detail:'L\'annonce a été envoyée.' })" :disabled="!messageForm.subject || !messageForm.body" />
            </div>
        </Dialog>

        <!-- DIALOG : MODIFICATION DES PRÉREQUIS (éditeur simplifié) -->
        <Dialog v-model:visible="prereqEditDialog" modal :closable="false" :draggable="false" :style="{ width: '600px' }" contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[50vh]" headerClass="hidden" class="rounded-2xl shadow-2xl border border-slate-200">
            <div class="flex-none h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2"><i class="pi pi-shield text-amber-500"></i> Gérer les prérequis</h2>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100" @click="prereqEditDialog = false" />
            </div>
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <p class="text-sm text-slate-500 mb-4">Sélectionnez les cours que les étudiants doivent valider avant de pouvoir s'inscrire à cette cohorte.</p>
                <MultiSelect v-model="cohortPrerequisites" :options="props.coursesList" optionLabel="title" optionValue="id" filter display="chip" class="w-full bg-white" placeholder="Sélectionner des cours..." />
            </div>
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-end px-6 z-30 gap-3 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="prereqEditDialog = false" />
                <Button label="Enregistrer" icon="pi pi-check" class="p-button-primary shadow-md font-bold px-6" @click="savePrerequisites" />
            </div>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Force la refonte visuelle des TabViews PrimeVue pour un rendu "Enterprise SaaS" */
:deep(.custom-tabview-enterprise .p-tabview-nav) {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 2rem;
}
:deep(.custom-tabview-enterprise .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-weight: 800;
    font-size: 0.875rem;
    padding: 1.5rem 1.5rem;
    transition: all 0.2s ease;
}
:deep(.custom-tabview-enterprise .p-tabview-nav li:not(.p-highlight):not(.p-disabled):hover .p-tabview-nav-link) {
    background: transparent;
    border-bottom-color: #cbd5e1;
    color: #334155;
}
:deep(.custom-tabview-enterprise .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
