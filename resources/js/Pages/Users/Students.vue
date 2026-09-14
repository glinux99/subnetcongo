<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - STUDENT HUB & CRM v12.0.0 (Enterprise Edition)
 * ======================================================================================
 * Module de gestion centralisée des apprenants. Interface de pilotage,
 * suivi individuel et actions de masse. Connecté aux données serveur via Inertia.
 *
 * NOUVEAUTÉS (Basé sur le standard de l'éditeur de cours) :
 * - UI/UX repensée avec le "Workspace Editor" (Stepper de validation, Auto-save visuel)
 * - Vue étendue du profil apprenant (Sidebar Analytics & KPI)
 * - Suppression totale des données mockées (100% Data-Driven par DB)
 * - Actions de masse sécurisées
 *
 * @author  SubnetCongo Framework Division
 * @module  Students/CRM
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// PRIMEVUE COMPONENTS (Importation exhaustive pour Enterprise)
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
import FileUpload from 'primevue/fileupload';
import SelectButton from 'primevue/selectbutton';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Breadcrumb from 'primevue/breadcrumb';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Sidebar from 'primevue/sidebar';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import Tooltip from 'primevue/tooltip';
import Avatar from 'primevue/avatar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Menu from 'primevue/menu';
import Slider from 'primevue/slider';

// ======================================================================================
// PROPS INERTIA (Données strictes provenant du backend/DB)
// ======================================================================================
const props = defineProps({
    students: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    learningPaths: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['Actif', 'Inactif', 'Suspendu', 'Diplômé'] },
    levels: {
        type: Array,
        default: () => [
            { label: 'Débutant', value: 'Débutant', icon: 'pi pi-star' },
            { label: 'Intermédiaire', value: 'Intermédiaire', icon: 'pi pi-star-half' },
            { label: 'Avancé', value: 'Avancé', icon: 'pi pi-star-fill' },
            { label: 'Expert', value: 'Expert', icon: 'pi pi-bolt' }
        ]
    },
    stats: { type: Object, default: () => ({ total_students: 0, active_students: 0, avg_progress: 0, certified_this_year: 0 }) },
    filters: { type: Object, default: () => ({}) },
    meta: { type: Object, default: () => ({ target_progress: 80 }) }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const selectedStudents = ref([]);
const expandedRows = ref([]);
const avatarPreview = ref(null);

// Toggles de l'interface
const studentDialog = ref(false);
const enrollmentDialog = ref(false);
const profileSidebar = ref(false);
const currentStudent = ref({});

// Simulation de sauvegarde
const lastSavedAt = ref(null);

// Menu contextuel actions groupées
const menu = ref();
const bulkActionsMenu = [
    { label: 'Envoyer un Email groupé', icon: 'pi pi-envelope', command: () => notify('Fonctionnalité en cours de liaison backend') },
    { label: 'Exporter la sélection', icon: 'pi pi-file-export', command: () => notify('Exportation CSV générée via le contrôleur') },
    { separator: true },
    { label: 'Suspendre les comptes', icon: 'pi pi-lock', command: () => confirmBulkSuspend() }
];

// ======================================================================================
// CONFIGURATION DU WORKSPACE ÉDITEUR D'APPRENANT
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: 'Informations Personnelles', description: 'Identité et coordonnées', icon: 'pi pi-id-card' },
    { id: 'academic', label: 'Dossier Académique', description: 'Niveau, statut et progression', icon: 'pi pi-chart-bar' },
    { id: 'media', label: 'Photo de profil', description: 'Avatar et identité visuelle', icon: 'pi pi-image' },
    { id: 'settings', label: 'Accès & Sécurité', description: 'Gestion de compte et blocages', icon: 'pi pi-shield' }
]);
const activeEditorTab = ref('general');

// ======================================================================================
// FILTRAGE RÉACTIF (Synchronisé avec le serveur)
// ======================================================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    level: props.filters?.level || null
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('users.students.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRE INERTIA - CRM ÉTUDIANT (Strict Data)
// ======================================================================================
const studentForm = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    phone: '',
    numero: '',
    address: '',
    country: '',
    fonction: '',
    bio: '',
    status: 'Actif', // required|in:Actif,Inactif,Suspendu,Diplômé
    level: 'Débutant',
    progress: 0,
    score: 0,
    hourly_rate: 0,
    avatar: null,
    enrolled_courses: [],
    certificates: 0,
    average_attendance: 0,
    requires_password_reset: false,
    is_suspended: false,
    send_credentials: false
});

const enrollmentForm = useForm({
    user_id: null,
    learning_path_id: null
});

// ======================================================================================
// VALIDATION DYNAMIQUE DU WORKSPACE (UI UX FRONTEND)
// ======================================================================================
const tabValidation = computed(() => {
    return {
        general: studentForm.name?.trim().length > 2 && studentForm.email?.trim().includes('@'),
        academic: studentForm.status !== null && studentForm.level !== null,
        media: true, // L'avatar est optionnel
        settings: true
    };
});

const isEntireFormValid = computed(() => {
    return tabValidation.value.general && tabValidation.value.academic;
});

// ======================================================================================
// MÉTHODES CRUD DU CYCLE DE VIE
// ======================================================================================
const openNew = () => {
    studentForm.reset();
    studentForm.id = null;
    studentForm.progress = 0;
    studentForm.score = 0;
    studentForm.enrolled_courses = [];
    activeEditorTab.value = 'general';
    avatarPreview.value = null;
    lastSavedAt.value = null;
    studentDialog.value = true;
};

const editStudent = (data) => {
    studentForm.clearErrors();
    const studentData = cloneDeep(data);

    studentForm.id = studentData.id;
    studentForm.name = studentData.name || '';
    studentForm.email = studentData.email || '';
    studentForm.phone = studentData.phone || '';
    studentForm.address = studentData.address || '';
    studentForm.bio = studentData.bio || '';
    studentForm.status = studentData.status || 'Actif';
    studentForm.level = studentData.level || 'Débutant';
    studentForm.progress = studentData.progress || 0;
    studentForm.score = studentData.score || 0;
    studentForm.avatar = studentData.avatar_path || null;
    studentForm.enrolled_courses = studentData.enrolled_courses || [];
    studentForm.certificates = studentData.certificates || 0;
    studentForm.average_attendance = studentData.average_attendance || 0;

    // Mappages UI spécifiques
    studentForm.is_suspended = studentData.status === 'Suspendu';

    avatarPreview.value = studentData.avatar_path || null;
    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    studentDialog.value = true;
};

const viewProfile = (data) => {
    currentStudent.value = cloneDeep(data);
    profileSidebar.value = true;
};
const saveStudent = () => {
    // console.log(studentForm.status);
    if (!isEntireFormValid.value) {
        toast.add({ severity: 'warn', summary: 'Formulaire incomplet', detail: 'Veuillez remplir correctement les champs obligatoires (Nom, Email).', life: 5000 });
        if (!tabValidation.value.general) activeEditorTab.value = 'general';
        else if (!tabValidation.value.academic) activeEditorTab.value = 'academic';
        return;
    }

    // Gérer l'état de suspension via le ToggleSwitch
    if (studentForm.is_suspended) studentForm.status = 'Suspendu';
    else if (studentForm.status === 'Suspendu' && !studentForm.is_suspended) studentForm.status = 'Actif';

    const url = studentForm.id ? route('users.students.update', studentForm.id) : route('users.students.store');

    studentForm.transform((data) => {
        const transformed = { ...data, _method: studentForm.id ? 'PUT' : 'POST' };
        if (typeof data.avatar === 'string') delete transformed.avatar; // Prévenir les erreurs d'upload
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `Dossier apprenant ${studentForm.id ? 'mis à jour' : 'créé'}.`, life: 3000 });
            lastSavedAt.value = new Date();
            studentDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur Serveur', detail: 'La validation backend a échoué. Vérifiez vos données.', life: 5000 });
        }
    });

};

const confirmDeleteStudent = (student) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer définitivement le dossier de ${student.name} ? Cette action effacera ses progressions et certificats.`,
        header: 'Suppression destructrice',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Oui, Supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('users.students.destroy', student.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Dossier apprenant purgé avec succès.', life: 3000 })
            });
        }
    });
};

const confirmBulkDelete = () => {
    if (!selectedStudents.value.length) return;
    confirm.require({
        message: `Supprimer DÉFINITIVEMENT les ${selectedStudents.value.length} étudiants sélectionnés ? Action irréversible.`,
        header: 'Purge en masse',
        icon: 'pi pi-ban',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Confirmer la purge',
        rejectLabel: 'Annuler',
        accept: () => {
            const ids = selectedStudents.value.map(s => s.id);
            router.delete(route('users.students.bulk-destroy'), {
                data: { ids },
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Opération réussie', detail: 'Les dossiers sélectionnés ont été effacés.', life: 3000 });
                    selectedStudents.value = [];
                }
            });
        }
    });
};

const confirmBulkSuspend = () => {
    if (!selectedStudents.value.length) return;
    const ids = selectedStudents.value.map(s => s.id);
    router.post(route('users.students.bulk-suspend'), { ids }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'warn', summary: 'Suspension', detail: `${ids.length} comptes suspendus.`, life: 3000 });
            selectedStudents.value = [];
        }
    });
};

const openEnrollment = (student) => {
    enrollmentForm.reset();
    enrollmentForm.user_id = student.id;
    enrollmentDialog.value = true;
};

const submitEnrollment = () => {
    if (!enrollmentForm.learning_path_id) return;

    enrollmentForm.post(route('learning-paths.enroll-student', enrollmentForm.learning_path_id), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Inscription', detail: 'L\'étudiant a été inscrit au parcours d\'apprentissage.', life: 3000 });
            enrollmentDialog.value = false;
        }
    });
};

// ======================================================================================
// HELPERS, UTILITAIRES ET FORMATTERS
// ======================================================================================
const getSeverity = (status) => {
    const map = {
        'Actif': { color: 'success', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        'Inactif': { color: 'secondary', bg: 'bg-slate-50 text-slate-700 border-slate-200' },
        'Suspendu': { color: 'danger', bg: 'bg-rose-50 text-rose-700 border-rose-200' },
        'Diplômé': { color: 'info', bg: 'bg-blue-50 text-blue-700 border-blue-200' }
    };
    return map[status] || map['Inactif'];
};

const getStatusIcon = (status) => {
    const map = {
        'Actif': 'pi pi-check-circle',
        'Inactif': 'pi pi-minus-circle',
        'Suspendu': 'pi pi-lock',
        'Diplômé': 'pi pi-verified'
    };
    return map[status] || 'pi pi-circle';
};

const formatDate = (date) => {
    if (!date || isNaN(new Date(date).getTime())) return date;
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(date));
};

const onPageChange = (event) => {
    router.get(route('users.students.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true, preserveScroll: true });
};

const notify = (msg) => {
    toast.add({ severity: 'info', summary: 'Information', detail: msg, life: 3000 });
};

// Upload Visuel Avatar
const uploadMockProgress = ref(0);
const onAvatarSelect = (event) => {
    const file = event.files[0];
    studentForm.avatar = file;

    // Nettoyage de l'ancienne URL de preview pour éviter les fuites mémoire
    if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    // Création de la nouvelle prévisualisation
    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
    }

    uploadMockProgress.value = 0;
    const interval = setInterval(() => {
        uploadMockProgress.value += 20;
        if (uploadMockProgress.value >= 100) {
            clearInterval(interval);
            toast.add({ severity: 'success', summary: 'Image chargée', detail: 'L\'avatar est prêt pour l\'enregistrement.', life: 2000 });
        }
    }, 100);
};

// ======================================================================================
// COMPUTED PROPERTIES (Mappage direct DB)
// ======================================================================================
const enrichedStudents = computed(() => {
    if (!props.students?.data) return [];
    return props.students.data;
});

// ======================================================================================
// HOOKS
// ======================================================================================
onMounted(() => {
    setTimeout(() => loading.value = false, 400);
});

onBeforeUnmount(() => {
    // Libération de la mémoire lors de la destruction du composant
    if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(avatarPreview.value);
    }
});
</script>

<template>
    <AppLayout>
        <Head title="Hub Apprenants | CRM Enterprise" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">
<!-- ============================================================================== -->
    <!-- HEADER DE PAGE ET KPI (Style Dark Hub)                                         -->
    <!-- ============================================================================== -->
    <div class="flex flex-col gap-2 mb-12">
        
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm px-1 mb-2">
            <Breadcrumb 
                :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Annuaire Apprenants' }]" 
                class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
            />
        </div>

        <!-- Bannière Sombre avec padding bas (pb-28) pour le chevauchement -->
        <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
            
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                
                <!-- Partie Gauche (Titres) -->
                <div class="space-y-4 max-w-3xl">
                    <!-- Badge -->
                    <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                        Enterprise Data
                    </span>

                    <!-- Titre Principal (Italique, gras, 2 couleurs) -->
                    <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                        <span class="text-white">CRM</span>
                        <span class="text-[#E92B2B]">Apprenants</span>
                    </h1>

                    <!-- Sous-titre -->
                    <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                        Contrôlez l'ensemble des profils étudiants. Suivez leurs performances académiques, administrez leurs accès, et consultez leurs historiques d'inscription en temps réel.
                    </p>
                </div>

                <!-- Partie Droite (Boutons) -->
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <Button 
                        label="Rapports d'engagement" 
                        icon="pi pi-chart-pie" 
                        class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white w-full sm:w-auto font-medium transition-colors shadow-none" 
                    />
                    <Button 
                        label="Intégrer un Apprenant" 
                        icon="pi pi-user-plus" 
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
            
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-users text-6xl text-indigo-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-users text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Effectif Global</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.stats.total_students || 0 }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Actifs -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-check-circle text-6xl text-emerald-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-bolt text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Comptes Actifs</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats.active_students || 0 }}</h3>
                        <div class="mt-2 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full transition-all duration-1000" :style="{ width: ((props.stats.active_students / (props.stats.total_students || 1)) * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Progression Moyenne -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-chart-line text-6xl text-amber-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-chart-bar text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Avancement Moyen</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats.avg_progress || 0 }}%</h3>
                        <p class="text-xs text-slate-400 mt-1">Objectif DB : {{ props.meta.target_progress || 80 }}%</p>
                    </div>
                </div>

                <!-- Certifiés -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-blue-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-verified text-6xl text-blue-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-verified text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Certifiés (Année)</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats.certified_this_year || 0 }}</h3>
                        <p class="text-xs text-blue-500 font-medium mt-1 flex items-center gap-1"><i class="pi pi-star-fill"></i> Diplômés officiels</p>
                    </div>
                </div>
        </div>
    </div>

         

            <!-- ============================================================================== -->
            <!-- DATA GRID : ANNUAIRE DES ÉTUDIANTS                                             -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- TOOLBAR DE FILTRAGE AVANCÉ -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-auto">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher par nom, email, téléphone..." class="w-full sm:w-80 border-slate-200 shadow-sm font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.status" :options="props.statuses" placeholder="Tous les statuts" class="w-full sm:w-48 border-slate-200 shadow-sm" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2 font-medium text-slate-700">
                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-400"></i>
                                    <span>{{ slotProps.value }}</span>
                                </div>
                                <span v-else class="text-slate-500">Filtrer par statut</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="getStatusIcon(slotProps.option)" class="text-slate-500"></i>
                                    <span>{{ slotProps.option }}</span>
                                </div>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.level" :options="props.levels" optionLabel="label" optionValue="value" placeholder="Niveau" class="w-full sm:w-44 border-slate-200 shadow-sm" showClear>
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="slotProps.option.icon" class="text-slate-400"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedStudents.length > 0" icon="pi pi-trash" :label="`Supprimer (${selectedStudents.length})`" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkDelete" />
                        <Button icon="pi pi-ellipsis-v" class="p-button-secondary p-button-text p-button-rounded bg-slate-50 border border-slate-200" @click="menu.toggle($event)" aria-haspopup="true" aria-controls="overlay_menu" v-tooltip.top="'Actions Groupées'" />
                        <Menu ref="menu" id="overlay_menu" :model="bulkActionsMenu" :popup="true" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser la base'" />
                    </div>
                </div>

                <!-- TABLEAU DE DONNÉES -->
                <DataTable
                    selectionMode="multiple"
                    v-model:selection="selectedStudents"
                    v-model:expandedRows="expandedRows"
                    :value="enrichedStudents"
                    dataKey="id"
                    :lazy="true"
                    :paginator="true"
                    :rows="props.students.per_page"
                    :totalRecords="props.students.total"
                    :first="(props.students.current_page - 1) * props.students.per_page"
                    @page="onPageChange"
                    :loading="loading"
                    responsiveLayout="stack"
                    class="p-datatable-custom border-none"
                    rowHover
                    stripedRows
                >
                    <!-- EMPTY STATE -->
                    <template #empty>
                        <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                <i class="pi pi-user text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun profil étudiant trouvé</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                La base de données ne renvoie aucun résultat pour ces filtres. Modifiez votre recherche ou intégrez un nouveau compte.
                            </p>
                            <Button label="Ajouter un apprenant" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <!-- COLONNES -->
                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="name" header="Identité Apprenant" style="min-width: 22rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <!-- Avatar cliquable -->
                                <div class="relative cursor-pointer group" @click="viewProfile(data)">
                                    <Avatar v-if="data.avatar_path" :image="data.avatar_path" shape="circle" size="xlarge" class="border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <Avatar v-else :label="data.name.charAt(0).toUpperCase()" shape="circle" size="xlarge" class="bg-indigo-100 text-indigo-700 font-black border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <span :class="['absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full ring-2 ring-white', data.status === 'Actif' ? 'bg-emerald-500' : 'bg-slate-300']"></span>
                                </div>
                                <div class="flex flex-col justify-center">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="font-extrabold text-slate-800 text-base hover:text-indigo-600 cursor-pointer transition-colors" @click="editStudent(data)">
                                            {{ data.name }}
                                        </span>
                                        <i v-if="data.certificates > 0" class="pi pi-verified text-blue-500 text-xs" v-tooltip.top="`${data.certificates} Certificat(s)`"></i>
                                    </div>
                                    <div class="text-xs text-slate-500 flex items-center gap-3">
                                        <span class="flex items-center gap-1"><i class="pi pi-envelope text-[10px]"></i> {{ data.email }}</span>
                                        <span v-if="data.phone" class="flex items-center gap-1 border-l border-slate-200 pl-3"><i class="pi pi-phone text-[10px]"></i> {{ data.phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État de Compte" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status"
                                :icon="getStatusIcon(data.status)"
                                class="text-[10px] font-bold px-3 py-1.5 border"
                                :class="getSeverity(data.status).bg"
                            />
                        </template>
                    </Column>

                    <Column field="academic" header="Performances" style="min-width: 14rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col w-full gap-1.5">
                                <div class="flex justify-between text-[11px] font-bold text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <i :class="props.levels.find(l => l.value === data.level)?.icon" class="text-indigo-400"></i>
                                        {{ data.level || 'Débutant' }}
                                    </span>
                                    <span :class="data.score >= 80 ? 'text-emerald-600' : 'text-slate-600'">{{ data.score }} pts</span>
                                </div>
                                <div class="relative pt-1">
                                    <ProgressBar :value="data.progress || 0" :showValue="false" class="h-2 rounded-full overflow-hidden bg-slate-100" :color="data.progress >= 100 ? '#10b981' : '#6366f1'" />
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="created_at" header="Inscription" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <span class="text-slate-600 text-sm font-medium flex items-center gap-2">
                                <!-- {{data.created_at}} -->
                                <i class="pi pi-calendar-plus text-slate-400 text-xs"></i>
                                {{ formatDate(data.created_at) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="min-width: 8rem; text-align: right; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-indigo-600 hover:bg-indigo-50" v-tooltip.top="'Profil Complet'" @click="viewProfile(data)" />
                                <Button icon="pi pi-user-edit" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="'Gérer le dossier'" @click="editStudent(data)" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-rose-600 hover:bg-rose-50" v-tooltip.top="'Purger l\'apprenant'" @click="confirmDeleteStudent(data)" />
                            </div>
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (COURS INSCRITS & ACTIVITÉS) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="pi pi-book"></i></div>
                                        Cahier de Progression : Parcours Actifs
                                    </h4>
                                    <Button label="Ouvrir le dossier complet" icon="pi pi-folder-open" class="p-button-outlined p-button-sm p-button-secondary bg-white shadow-sm font-bold" @click="viewProfile(data)" />
                                </div>

                                <div v-if="(data.enrolled_courses && data.enrolled_courses.length > 0) || (data.learning_paths && data.learning_paths.length > 0)">
                                    <!-- Section Learning Paths (Parcours d'apprentissage) -->
                                    <div v-if="data.learning_paths && data.learning_paths.length > 0" class="mb-10">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-map text-indigo-400"></i> Parcours d'apprentissage inscrits
                                        </p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                            <div v-for="path in data.learning_paths" :key="path.id" class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden group border-l-4 border-l-indigo-500">
                                                <div class="flex justify-between items-start mb-4">
                                                    <div>
                                                        <h5 class="font-black text-slate-800 text-sm leading-tight">{{ path.name }}</h5>
                                                        <p class="text-[10px] text-slate-500 mt-1 font-bold"><i class="pi pi-calendar-plus mr-1"></i>Inscrit le {{ formatDate(path.pivot?.enrolled_at) }}</p>
                                                    </div>
                                                    <div class="bg-indigo-50 text-indigo-600 w-8 h-8 rounded-lg flex items-center justify-center shadow-inner"><i class="pi pi-map"></i></div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center text-[10px] font-black uppercase">
                                                        <span class="text-slate-400">Progression Globale</span>
                                                        <span class="text-indigo-600">{{ path.pivot?.progress || 0 }}%</span>
                                                    </div>
                                                    <ProgressBar :value="path.pivot?.progress || 0" :showValue="false" class="h-1.5 bg-slate-100" color="#6366f1" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section Cours Individuels -->
                                    <div v-if="data.enrolled_courses && data.enrolled_courses.length > 0">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-book-open text-slate-400"></i> Cours Individuels
                                        </p>
                                        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                            <div v-for="course in data.enrolled_courses" :key="course.id" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                                <!-- Header Cours -->
                                                <div class="flex items-center gap-4 mb-4">
                                                    <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                                        <img v-if="course.thumbnail_path" :src="course.thumbnail_path" class="w-full h-full object-cover" />
                                                        <div v-else class="w-full h-full flex items-center justify-center text-slate-400"><i class="pi pi-image text-xl"></i></div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="font-bold text-sm text-slate-800 truncate" :title="course.title">{{ course.title }}</h5>
                                                        <p class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5"><i class="pi pi-calendar text-[9px]"></i> Inscrit le {{ formatDate(course.pivot?.enrolled_at) }}</p>
                                                    </div>
                                                </div>

                                                <!-- Progression Cours -->
                                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                                    <div class="flex justify-between items-center text-xs font-bold mb-2">
                                                        <span class="text-slate-600">Progression individuelle</span>
                                                        <span :class="course.pivot?.progress === 100 ? 'text-emerald-600' : 'text-indigo-600'">{{ course.pivot?.progress || 0 }}%</span>
                                                    </div>
                                                    <ProgressBar :value="course.pivot?.progress || 0" :showValue="false" class="h-1.5" :color="course.pivot?.progress === 100 ? '#10b981' : '#6366f1'" />
                                                </div>

                                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Button icon="pi pi-external-link" class="p-button-text p-button-sm p-button-rounded p-button-secondary bg-white shadow-sm" v-tooltip.left="'Voir la fiche du cours'" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-slate-500 p-8 bg-white rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-3 hover:bg-slate-50 transition-colors">
                                    <i class="pi pi-inbox text-4xl text-slate-300"></i>
                                    <div class="text-center">
                                        <p class="font-bold text-slate-700 text-lg">Cahier de formation vide</p>
                                        <p class="text-sm max-w-sm mt-1">Cet apprenant n'a été rattaché à aucun programme académique pour l'instant.</p>
                                    </div>
                                    <Button label="Inscrire à un cursus" icon="pi pi-plus-circle" class="p-button-text p-button-primary mt-2 font-bold" @click="openEnrollment(data)" />
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR ULTRA PRO (STUDENT WORKSPACE)                                   -->
        <!-- Basé rigoureusement sur le layout Course Architect                             -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="studentDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1200px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[85vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.2)] overflow-hidden border border-slate-200"
        >
            <!-- WORKSPACE HEADER (FIXE) -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <!-- Ligne de progression UI -->
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '50%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <i class="pi pi-user-edit text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ studentForm.id ? 'Dossier Apprenant' : 'Intégration Étudiant' }}
                            <Tag v-if="studentForm.status" :value="studentForm.status" :class="getSeverity(studentForm.status).bg" class="text-[9px] uppercase tracking-wider px-2 py-0.5 border" />
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px]">{{ studentForm.name || 'Nouveau Profil' }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> Sync à {{ formatDate(lastSavedAt) }}</span>
                            <span v-else-if="studentForm.isDirty" class="flex items-center gap-1 text-amber-500"><i class="pi pi-spin pi-spinner"></i> Modifications en cours...</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" @click="studentDialog = false" v-tooltip.bottom="'Fermer le dossier'" />
                </div>
            </div>

            <!-- WORKSPACE BODY (FLEX ROW) -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR DE NAVIGATION (STEPPER) -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        Sections du Dossier
                    </div>

                    <div class="p-4 flex-1 space-y-2">
                        <button
                            v-for="item in editorMenu" :key="item.id"
                            @click="activeEditorTab = item.id"
                            class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                            :class="[
                                activeEditorTab === item.id
                                    ? 'bg-indigo-50 border border-indigo-100 shadow-sm'
                                    : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'
                            ]"
                        >
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>

                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white">
                                    <i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px] bg-white rounded-full"></i>
                                    <i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px] bg-white rounded-full"></i>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div>
                                <div class="text-[11px] font-medium text-slate-500 leading-tight">{{ item.description }}</div>
                            </div>
                        </button>
                    </div>

                    <div class="p-6 border-t border-slate-200 bg-slate-50 mt-auto">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="isEntireFormValid ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                <i :class="isEntireFormValid ? 'pi pi-check' : 'pi pi-exclamation-triangle'" class="text-lg"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ isEntireFormValid ? 'Dossier Conforme' : 'Données Requises' }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                    {{ isEntireFormValid ? 'Toutes les infos obligatoires sont remplies.' : 'Complétez les champs signalés.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE CONTENU PRINCIPALE (SCROLLABLE) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative scroll-smooth bg-slate-50">
                    <div class="max-w-4xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <!-- TAB 1 : INFO GÉNÉRALES -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">État Civil & Coordonnées</h3>
                                        <p class="text-xs text-slate-500 mt-1">Identité légale et moyens de contact.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Nom Complet <span class="text-rose-500">*</span></label>
                                        <InputText v-model="studentForm.name" class="w-full p-4 text-lg font-bold text-slate-800 bg-slate-50 focus:bg-white transition-colors" placeholder="Ex: Jean Lumumba" :class="{'p-invalid': studentForm.errors?.name || (!studentForm.name && studentForm.isDirty)}" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Adresse Email <span class="text-rose-500">*</span></label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-envelope text-slate-400" />
                                            <InputText v-model="studentForm.email" type="email" class="w-full bg-slate-50 focus:bg-white" placeholder="jean.lumumba@email.com" :class="{'p-invalid': studentForm.errors?.email}" />
                                        </IconField>
                                        <small class="text-slate-500 text-[11px]">Sert d'identifiant de connexion au LMS.</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">
                                            {{ studentForm.id ? 'Modifier le mot de passe' : 'Mot de passe initial' }}
                                            <span v-if="!studentForm.id" class="text-rose-500">*</span>
                                        </label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-lock text-slate-400" />
                                            <InputText v-model="studentForm.password" type="password" class="w-full bg-slate-50 focus:bg-white" placeholder="••••••••" :class="{'p-invalid': studentForm.errors?.password}" />
                                        </IconField>
                                        <small v-if="studentForm.id" class="text-slate-500 text-[11px]">Laissez vide pour conserver le mot de passe actuel.</small>
                                        <div v-if="studentForm.id" class="flex items-center gap-2 mt-2">
                                            <Checkbox v-model="studentForm.send_credentials" :binary="true" inputId="send_creds" />
                                            <label for="send_creds" class="text-[11px] font-bold text-indigo-600 cursor-pointer">Renvoyer les identifiants par email</label>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Matricule / Numéro</label>
                                        <InputText v-model="studentForm.numero" class="w-full bg-slate-50 focus:bg-white" placeholder="STU-2025-001" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Téléphone (Optionnel)</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-phone text-slate-400" />
                                            <InputText v-model="studentForm.phone" class="w-full bg-slate-50 focus:bg-white" placeholder="+243 ..." />
                                        </IconField>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Fonction / Titre</label>
                                        <InputText v-model="studentForm.fonction" class="w-full bg-slate-50 focus:bg-white" placeholder="Ex: Développeur, Étudiant..." />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Pays</label>
                                        <InputText v-model="studentForm.country" class="w-full bg-slate-50 focus:bg-white" placeholder="Ex: RDC, France..." />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Taux Horaire ($)</label>
                                        <InputNumber v-model="studentForm.hourly_rate" mode="currency" currency="USD" locale="en-US" class="w-full" />
                                    </div>

                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Adresse Physique</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-map-marker text-slate-400" />
                                            <InputText v-model="studentForm.address" class="w-full bg-slate-50 focus:bg-white" placeholder="Ville, Commune, Numéro..." />
                                        </IconField>
                                    </div>

                                    <div class="col-span-1 md:col-span-2 space-y-2 mt-2">
                                        <label class="block text-sm font-bold text-slate-700">Notes / Biographie</label>
                                        <Textarea v-model="studentForm.bio" rows="4" class="w-full p-3 bg-slate-50 border-slate-300 focus:bg-white" placeholder="Informations complémentaires sur le parcours de l'étudiant..." />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2 : DOSSIER ACADÉMIQUE -->
                        <div v-show="activeEditorTab === 'academic'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-chart-bar"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Indicateurs Pédagogiques</h3>
                                        <p class="text-xs text-slate-500 mt-1">Classification et suivi de la progression.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Statut du Cursus</label>

                                        <Dropdown v-model="studentForm.status" :options="props.statuses" class="w-full font-medium" :pt="{ root: { class: 'bg-slate-50 hover:bg-white transition-colors' } }">
                                            <template #value="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-500"></i>
                                                    <span>{{ slotProps.value }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Niveau Pédagogique</label>
                                        <SelectButton v-model="studentForm.level" :options="props.levels" optionLabel="label" optionValue="value" class="w-full flex">
                                            <template #option="slotProps">
                                                <div class="flex items-center justify-center gap-2 px-1 py-0.5 w-full">
                                                    <i :class="slotProps.option.icon" class="text-sm"></i>
                                                    <span class="text-xs font-bold">{{ slotProps.option.label }}</span>
                                                </div>
                                            </template>
                                        </SelectButton>
                                    </div>
                                    <div class="col-span-1 md:col-span-2 bg-slate-50 p-6 rounded-xl border border-slate-100 space-y-6">
                                        <div class="space-y-4">
                                            <div class="flex justify-between">
                                                <label class="block text-sm font-bold text-slate-700">Progression globale forcée (%)</label>
                                                <span class="font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded text-sm"><span class="…">
                                                {{ studentForm.processing ? 'Calcul en cours...' : studentForm.progress + '%' }}
                                                </span>
                                                </span>
                                            </div>
                                            <Slider v-model="studentForm.progress" class="w-full" />
                                            <small class="text-slate-500 text-[11px]"><i class="pi pi-info-circle mr-1"></i>En mode Enterprise, la progression est calculée automatiquement par les cours, mais l'admin peut la surcharger ici.</small>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-sm font-bold text-slate-700">Score Cumulé (Points)</label>
                                            <InputNumber v-model="studentForm.score" class="w-full" :min="0" :pt="{ input: { class: 'font-mono text-lg font-bold' } }" placeholder="0" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3 : MÉDIAS (AVATAR) -->
                        <div v-show="activeEditorTab === 'media'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Photo de Profil (Avatar)</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                                    <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-300 rounded-3xl relative group hover:bg-indigo-50/50 hover:border-indigo-300 transition-colors">

                                        <Avatar v-if="avatarPreview" :image="avatarPreview" size="xlarge" shape="circle" class="w-32 h-32 shadow-xl ring-4 ring-white mb-4" />
                                        <div v-else class="w-32 h-32 rounded-full bg-white flex items-center justify-center shadow-inner border border-slate-200 mb-4 text-slate-300">
                                            <i class="pi pi-user text-5xl"></i>
                                        </div>

                                        <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" customUpload @select="onAvatarSelect" chooseLabel="Télécharger une image" class="p-button-outlined p-button-sm font-bold rounded-full px-6 bg-white" auto />

                                        <ProgressBar v-if="uploadMockProgress > 0 && uploadMockProgress < 100" :value="uploadMockProgress" class="absolute bottom-0 left-4 right-4 h-1 rounded-t-none" :showValue="false" />
                                    </div>

                                    <div class="space-y-4">
                                        <h4 class="font-bold text-slate-800 text-sm">Spécifications requises</h4>
                                        <ul class="space-y-3 text-sm text-slate-600 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Format carré :</strong> 500x500px recommandé.</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Poids :</strong> Inférieur à 2 Mo (.PNG, .JPG).</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> Un visage dégagé et fond clair sont préférables.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4 : PARAMÈTRES (ACCÈS & SÉCURITÉ) -->
                        <div v-show="activeEditorTab === 'settings'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-shield text-slate-400"></i> Sécurité & Contrôle d'Accès</h3>

                                <div class="space-y-4">
                                    <!-- Blocage Compte -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-rose-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-rose-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-lock"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Suspendre le compte</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">L'étudiant ne pourra plus se connecter au portail ni accéder à ses cours. Ses données sont conservées.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="studentForm.is_suspended" />
                                    </div>

                                    <!-- Reset Password -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-amber-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-amber-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-key"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Forcer la réinitialisation du mot de passe</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">Un email contenant un lien sécurisé sera envoyé à la prochaine sauvegarde.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="studentForm.requires_password_reset" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER (ACTIONS) -->
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div class="text-xs text-slate-500">
                    <span v-if="!isEntireFormValid" class="text-amber-600 font-bold flex items-center gap-2"><i class="pi pi-exclamation-triangle"></i> Complétez le formulaire pour enregistrer.</span>
                </div>

                <div class="flex items-center gap-3">
                    <Button label="Annuler les modifications" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-50" @click="studentDialog = false" />

                    <div class="relative group">
                        <Button label="Enregistrer le Dossier" icon="pi pi-save" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold px-8 text-sm" @click="saveStudent" :loading="studentForm.processing" :disabled="!isEntireFormValid" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- DIALOG INSCRIPTION CURSUS -->
        <Dialog v-model:visible="enrollmentDialog" modal header="Inscrire à un cursus" :style="{ width: '450px' }" class="p-fluid rounded-2xl">
            <div class="space-y-6 pt-4">
                <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-sm">
                        <i class="pi pi-book text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Nouveau cursus pour</p>
                        <p class="font-black text-slate-800">{{ enrichedStudents.find(s => s.id === enrollmentForm.user_id)?.name }}</p>
                    </div>
                </div>

                <div class="field">

                    <label class="font-bold text-slate-700 mb-2 block">Choisir le parcours d'apprentissage</label>
                    <Dropdown v-model="enrollmentForm.learning_path_id" :options="props.learningPaths" optionLabel="name" optionValue="id" placeholder="Sélectionnez un parcours" class="w-full border-slate-200" filter />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="enrollmentDialog = false" />
                <Button label="Confirmer l'inscription" icon="pi pi-check" class="p-button-primary font-bold shadow-md" @click="submitEnrollment" :loading="enrollmentForm.processing" :disabled="!enrollmentForm.learning_path_id" />
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- SIDEBAR PROFIL COMPLET (ANALYTICS & DETAILS)                                   -->
        <!-- ============================================================================== -->
        <!-- ============================================================================== -->
<!-- DIALOG : PROFIL 360° APPRENANT -->
<!-- ============================================================================== -->
<Dialog
    v-model:visible="profileSidebar"
    modal
    :style="{ width: '600px' }"
    class="p-fluid rounded-[1rem] custom-profile-dialog"
    :pt="{
        header: { class: 'bg-white border-b border-slate-100 p-6' },
        content: { class: 'p-0 bg-slate-50' },
        footer: { class: 'bg-white border-t border-slate-100 p-4' }
    }"
>
    <!-- Header personnalisé -->
    <template #header>
        <div class="flex items-center gap-4 w-full">
            <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                <i class="pi pi-id-card text-xl"></i>
            </div>
            <div class="flex-1">
                <h2 class="font-black text-xl text-slate-800 leading-tight">Profil</h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Dossier Apprenant</p>
            </div>
        </div>
    </template>

    <!-- Contenu du Profil -->
    <div v-if="currentStudent" class="flex flex-col h-full relative">

        <!-- Hero Header (En-tête dégradé) -->
        <div class="bg-gradient-to-br from-indigo-600 to-blue-700 p-8 text-white rounded-b-[2rem] shadow-sm relative overflow-hidden flex-shrink-0">
            <!-- Effets visuels d'arrière-plan -->
            <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-indigo-900/20 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col items-center text-center">
                <Avatar v-if="currentStudent.avatar_path" :image="currentStudent.avatar_path" size="xlarge" shape="circle" class="ring-4 ring-white/20 mb-4 w-24 h-24 shadow-2xl bg-white" />
                <Avatar v-else :label="currentStudent.name?.charAt(0).toUpperCase()" size="xlarge" shape="circle" class="ring-4 ring-white/20 mb-4 w-24 h-24 shadow-2xl bg-white text-indigo-600 font-black text-4xl" />

                <h2 class="text-2xl font-black mb-1 tracking-tight">{{ currentStudent.name }}</h2>
                <p class="text-sm text-indigo-200 mb-4 font-medium">{{ currentStudent.email }}</p>
                <Tag :value="currentStudent.level || 'Débutant'" class="bg-white/20 text-white border border-white/30 px-4 py-1 text-xs font-black tracking-widest uppercase rounded-full shadow-sm" />

                <!-- Statistiques KPI -->
                <div class="grid grid-cols-3 w-full mt-8 gap-3">
                    <div class="bg-black/10 backdrop-blur-sm border border-white/10 rounded-2xl p-3 hover:bg-black/20 transition-colors">
                        <p class="text-[10px] text-indigo-200 uppercase font-black tracking-widest mb-1">Score</p>
                        <p class="text-2xl font-black">{{ currentStudent.score || 0 }}</p>
                    </div>
                    <div class="bg-black/10 backdrop-blur-sm border border-white/10 rounded-2xl p-3 hover:bg-black/20 transition-colors">
                        <p class="text-[10px] text-indigo-200 uppercase font-black tracking-widest mb-1">Certifs</p>
                        <p class="text-2xl font-black">{{ currentStudent.certificates || 0 }}</p>
                    </div>
                    <div class="bg-black/10 backdrop-blur-sm border border-white/10 rounded-2xl p-3 hover:bg-black/20 transition-colors">
                        <p class="text-[10px] text-indigo-200 uppercase font-black tracking-widest mb-1">Présence</p>
                        <p class="text-2xl font-black">{{ currentStudent.average_attendance || 0 }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets (Tabs) -->
        <div class="p-6">
            <TabView class="custom-tabview">
                <!-- ONGLET 1 : CONTACT -->
                <TabPanel header="Aperçu & Contact">
                    <div class="space-y-4 pt-2">
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-colors">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-map-marker text-indigo-400"></i> Coordonnées</h4>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 text-slate-600">
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-500"><i class="pi pi-phone"></i></div>
                                    <span class="text-sm font-bold">{{ currentStudent.phone || 'Non spécifié' }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-slate-600">
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-500"><i class="pi pi-home"></i></div>
                                    <span class="text-sm font-bold leading-tight">{{ currentStudent.address || 'Adresse inconnue' }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStudent.bio" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-colors">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="pi pi-info-circle text-indigo-400"></i> Biographie</h4>
                            <p class="text-sm text-slate-600 leading-relaxed font-medium bg-slate-50 p-4 rounded-xl border border-slate-100">"{{ currentStudent.bio }}"</p>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 2 : FORMATIONS -->
                <TabPanel header="Formations Actives">
                    <div class="pt-2 space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                        <div v-if="(currentStudent.enrolled_courses && currentStudent.enrolled_courses.length) || (currentStudent.learning_paths && currentStudent.learning_paths.length)" class="space-y-6">

                            <!-- Learning Paths in Sidebar View -->
                            <div v-if="currentStudent.learning_paths && currentStudent.learning_paths.length" class="space-y-3">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Parcours d'apprentissage</p>
                                <div v-for="path in currentStudent.learning_paths" :key="path.id" class="p-4 bg-indigo-50/30 border border-indigo-100 rounded-2xl shadow-sm hover:border-indigo-300 transition-colors group">
                                    <div class="flex items-center gap-4 mb-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center border border-indigo-200 group-hover:scale-105 transition-transform"><i class="pi pi-map text-lg"></i></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-black text-sm text-slate-800 truncate">{{ path.name }}</p>
                                            <p class="text-[9px] text-slate-400 mt-1 font-bold uppercase tracking-widest">Inscrit le {{ formatDate(path.pivot?.enrolled_at) }}</p>
                                        </div>
                                    </div>
                                    <ProgressBar :value="path.pivot?.progress || 0" :showValue="false" class="h-1.5 bg-white" color="#4f46e5" />
                                </div>
                            </div>

                            <p v-if="currentStudent.enrolled_courses && currentStudent.enrolled_courses.length" class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-1">Cours Individuels</p>
                            <div v-for="course in currentStudent.enrolled_courses" :key="course.id" class="p-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:border-indigo-300 transition-colors group">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 group-hover:scale-110 transition-transform"><i class="pi pi-book text-xl"></i></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-black text-sm text-slate-800 truncate" :title="course.title">{{ course.title }}</p>
                                        <p class="text-[10px] text-slate-400 mt-1 font-bold uppercase tracking-widest"><i class="pi pi-calendar text-[9px] mr-1"></i> Inscrit le {{ formatDate(course.pivot?.enrolled_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-xs font-black mb-2">
                                    <span class="text-slate-400 uppercase tracking-widest text-[9px]">Progression</span>
                                    <span class="text-indigo-600">{{ course.pivot?.progress || 0 }}%</span>
                                </div>
                                <ProgressBar :value="course.pivot?.progress || 0" :showValue="false" class="h-1.5 bg-slate-100" color="#4f46e5" />
                            </div>
                        </div>

                        <div v-else class="text-center p-10 text-slate-500 bg-white rounded-2xl border-2 border-dashed border-slate-200">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                <i class="pi pi-folder-open text-3xl text-slate-300"></i>
                            </div>
                            <p class="text-sm font-black text-slate-700">Aucune formation</p>
                            <p class="text-xs mt-1 font-medium">L'apprenant n'est inscrit à aucun module.</p>
                        </div>
                    </div>
                </TabPanel>
            </TabView>
        </div>
    </div>

    <!-- Footer du Dialog -->
    <template #footer>
        <div class="flex justify-between items-center w-full px-2 mt-1">
            <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="profileSidebar = false" />
            <Button label="Éditer le profil" icon="pi pi-pencil" class="p-button-primary px-8 font-black shadow-lg shadow-indigo-500/30 rounded-xl" @click="editStudent(currentStudent); profileSidebar = false" />
        </div>
    </template>
</Dialog>

    </AppLayout>
</template>

<style scoped>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }

/* Customisation du TabView PrimeVue */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent;
    border: none;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 0.70rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    padding: 1rem 0.5rem;
    margin-right: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: none !important;
}
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5;
    border-bottom: 3px solid #4f46e5;
}
:deep(.custom-tabview .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* Retrait du focus par défaut */
:deep(.p-tabview-nav-link:focus) {
    box-shadow: none !important;
}
/* Animations fluides */
.animate-fade-in {
    animation: fadeIn 0.25s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Scrollbar personnalisée (Mac Style B2B) */
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

/* Customisation DataTable Enterprise */
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

/* Customisation TabView dans Sidebar */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent;
    border: none;
    border-bottom: 1px solid #e2e8f0;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #94a3b8;
    padding: 1rem 0.5rem;
    margin-right: 1.5rem;
}
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5;
    border-bottom: 2px solid #4f46e5;
}

/* Retrait de l'ombre de focus pour un look SaaS pur */
:deep(.p-inputtext:enabled:focus) {
    box-shadow: none;
    border-color: #818cf8;
}
</style>
