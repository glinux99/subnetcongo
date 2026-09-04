<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - INSTRUCTOR MANAGEMENT & PAYROLL v12.0.0 (Enterprise Edition)
 * ======================================================================================
 * Module de gestion centralisée du corps enseignant, des experts et formateurs.
 * Interface de pilotage RH, suivi des commissions, performances et assignations.
 *
 * UX/UI PARADIGMS IMPLÉMENTÉS :
 * - Workspace Editor : Création/Édition via un Stepper de validation intelligent
 * - Profil 360° : Sidebar d'analyse profonde (Cours, Finance, Avis)
 * - Row Expansion : Aperçu rapide du portfolio de l'instructeur dans le Datatable
 * - Data-Driven : Conçu pour s'interfacer directement avec les Props Inertia (DB)
 *
 * @author  SubnetCongo Framework Division
 * @module  Instructors/HR
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// PRIMEVUE COMPONENTS (Importation exhaustive pour Enterprise UI)
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
import Tooltip from 'primevue/tooltip';
import Avatar from 'primevue/avatar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Menu from 'primevue/menu';
import Slider from 'primevue/slider';
import Rating from 'primevue/rating';
import MultiSelect from 'primevue/multiselect';
import Chart from 'primevue/chart';

// ======================================================================================
// PROPS INERTIA (Données strictes provenant du backend/DB)
// ======================================================================================
const props = defineProps({
    instructors: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    statuses: { type: Array, default: () => ['Actif', 'En intégration', 'Inactif', 'Suspendu'] },
    specialtiesList: {
        type: Array,
        default: () => [
            { label: 'Développement Web', value: 'Développement Web' },
            { label: 'Intelligence Artificielle', value: 'Intelligence Artificielle' },
            { label: 'Design UI/UX', value: 'Design UI/UX' },
            { label: 'Cybersécurité', value: 'Cybersécurité' },
            { label: 'Marketing Digital', value: 'Marketing Digital' },
            { label: 'Data Science', value: 'Data Science' },
            { label: 'Cloud Computing', value: 'Cloud Computing' },
            { label: 'Management & Agile', value: 'Management & Agile' }
        ]
    },
    stats: {
        type: Object,
        default: () => ({ total_instructors: 0, active_courses: 0, avg_rating: 0, total_commissions: 0 })
    },
    filters: { type: Object, default: () => ({}) }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const selectedInstructors = ref([]);
const expandedRows = ref([]);

// Toggles de l'interface
const instructorDialog = ref(false);
const profileSidebar = ref(false);
const currentInstructor = ref(null);

// Simulation de sauvegarde
const lastSavedAt = ref(null);

// Menu contextuel actions groupées
const menu = ref();
const bulkActionsMenu = [
    { label: 'Envoyer une communication', icon: 'pi pi-envelope', command: () => notify('Module de messagerie en cours d\'intégration.') },
    { label: 'Générer rapport de paie global', icon: 'pi pi-dollar', command: () => notify('Génération du fichier de paie (CSV) en cours...') },
    { separator: true },
    { label: 'Suspendre les contrats', icon: 'pi pi-lock', command: () => confirmBulkSuspend() }
];

// ======================================================================================
// CONFIGURATION DU WORKSPACE ÉDITEUR (STEPPER)
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: 'Identité & Contact', description: 'Informations personnelles', icon: 'pi pi-id-card' },
    { id: 'expertise', label: 'Expertise & Bio', description: 'Compétences et présentation', icon: 'pi pi-briefcase' },
    { id: 'media', label: 'Identité Visuelle', description: 'Photo de profil officielle', icon: 'pi pi-image' },
    { id: 'finance', label: 'Contrat & Finance', description: 'Commissions et statut RH', icon: 'pi pi-percentage' },
    { id: 'security', label: 'Accès & Sécurité', description: 'Contrôle d\'accès au LMS', icon: 'pi pi-shield' }
]);
const activeEditorTab = ref('general');

// ======================================================================================
// FILTRAGE RÉACTIF (Synchronisé avec le serveur)
// ======================================================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    specialty: props.filters?.specialty || null
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('users.instructors.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRE INERTIA - CRM INSTRUCTEUR (Strict Data)
// ======================================================================================
const instructorForm = useForm({
    id: null,
    name: '',
    email: '',
    numero: '',
    address: '',
    bio: '',
    specialties: [],
    status: 'En intégration',
    commission_rate: 70, // Standard SubnetCongo
    avatar: null,
    social: {
        linkedin: '',
        website: ''
    },
    is_suspended: false,
    requires_password_reset: false
});

// ======================================================================================
// VALIDATION DYNAMIQUE DU WORKSPACE (UI UX FRONTEND)
// ======================================================================================
const tabValidation = computed(() => {
    return {
        general: instructorForm.name?.trim().length > 2 && instructorForm.email?.trim().includes('@'),
        expertise: instructorForm.specialties && instructorForm.specialties.length > 0,
        media: true, // Avatar optionnel
        finance: instructorForm.commission_rate >= 0 && instructorForm.commission_rate <= 100 && instructorForm.status !== null,
        security: true
    };
});

const isEntireFormValid = computed(() => {
    return tabValidation.value.general && tabValidation.value.expertise && tabValidation.value.finance;
});

// ======================================================================================
// MÉTHODES CRUD DU CYCLE DE VIE
// ======================================================================================
const openNew = () => {
    instructorForm.reset();
    instructorForm.id = null;
    instructorForm.commission_rate = 70;
    instructorForm.status = 'En intégration';
    instructorForm.specialties = [];
    instructorForm.social = { linkedin: '', website: '' };
    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    instructorDialog.value = true;
};

const editInstructor = (data) => {
    instructorForm.clearErrors();
    const instructorData = cloneDeep(data);

    instructorForm.id = instructorData.id;
    instructorForm.name = instructorData.name || '';
    instructorForm.email = instructorData.email || '';
    instructorForm.numero = instructorData.numero || '';
    instructorForm.address = instructorData.address || '';
    instructorForm.bio = instructorData.bio || '';
    // Parsing JSON si stocké en string dans la DB
    instructorForm.specialties = typeof instructorData.specialties === 'string' ? JSON.parse(instructorData.specialties) : (instructorData.specialties || []);
    instructorForm.status = instructorData.status || 'Actif';
    instructorForm.commission_rate = instructorData.commission_rate !== undefined ? instructorData.commission_rate : 70;
    instructorForm.avatar = instructorData.avatar_path || null;
    instructorForm.social = typeof instructorData.social === 'string' ? JSON.parse(instructorData.social) : (instructorData.social || { linkedin: '', website: '' });

    instructorForm.is_suspended = instructorData.status === 'Suspendu';
    instructorForm.requires_password_reset = false;

    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    instructorDialog.value = true;
};

const viewProfile = (data) => {
    currentInstructor.value = cloneDeep(data);
    profileSidebar.value = true;
};

const saveInstructor = () => {
    if (!isEntireFormValid.value) {
        toast.add({ severity: 'warn', summary: 'Formulaire incomplet', detail: 'Veuillez remplir les champs obligatoires (Nom, Email, Spécialités, Commission).', life: 5000 });
        if (!tabValidation.value.general) activeEditorTab.value = 'general';
        else if (!tabValidation.value.expertise) activeEditorTab.value = 'expertise';
        else if (!tabValidation.value.finance) activeEditorTab.value = 'finance';
        return;
    }

    if (instructorForm.is_suspended) instructorForm.status = 'Suspendu';
    else if (instructorForm.status === 'Suspendu' && !instructorForm.is_suspended) instructorForm.status = 'Actif';

    const url = instructorForm.id ? route('users.instructors.update', instructorForm.id) : route('users.instructors.store');

    instructorForm.transform((data) => {
        const transformed = { ...data, _method: instructorForm.id ? 'PUT' : 'POST' };
        if (typeof data.avatar === 'string') delete transformed.avatar; // Éviter erreur fichier
        // S'assurer que les objets/arrays sont stringifiés si le backend l'exige
        transformed.specialties = JSON.stringify(data.specialties);
        transformed.social = JSON.stringify(data.social);
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Opération réussie', detail: `Dossier formateur ${instructorForm.id ? 'mis à jour' : 'créé'}.`, life: 3000 });
            lastSavedAt.value = new Date();
            instructorDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur Serveur', detail: 'La validation a échoué. Vérifiez vos saisies.', life: 5000 });
        }
    });
};

const confirmDeleteInstructor = (instructor) => {
    confirm.require({
        message: `La suppression de ${instructor.name} est-elle définitive ? Si cet expert a des cours actifs, il est recommandé de le suspendre plutôt.`,
        header: 'Suppression du dossier RH',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Oui, purger',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('users.instructors.destroy', instructor.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: 'Archivé', detail: 'Dossier formateur supprimé avec succès.', life: 3000 })
            });
        }
    });
};

const confirmBulkDelete = () => {
    if (!selectedInstructors.value.length) return;
    confirm.require({
        message: `Vous allez supprimer DÉFINITIVEMENT les ${selectedInstructors.value.length} instructeurs sélectionnés. Confirmer ?`,
        header: 'Purge en masse',
        icon: 'pi pi-ban',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Confirmer la purge',
        rejectLabel: 'Annuler',
        accept: () => {
            const ids = selectedInstructors.value.map(i => i.id);
            router.delete(route('users.instructors.bulk-destroy'), {
                data: { ids },
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: 'Les dossiers sélectionnés ont été effacés.', life: 3000 });
                    selectedInstructors.value = [];
                }
            });
        }
    });
};

const confirmBulkSuspend = () => {
    if (!selectedInstructors.value.length) return;
    const ids = selectedInstructors.value.map(i => i.id);
    router.post(route('users.instructors.bulk-suspend'), { ids }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'warn', summary: 'Contrats Suspendus', detail: `${ids.length} formateurs ont été suspendus.`, life: 3000 });
            selectedInstructors.value = [];
        }
    });
};

// ======================================================================================
// HELPERS, UTILITAIRES ET FORMATTERS
// ======================================================================================
const getSeverity = (status) => {
    const map = {
        'Actif': { bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
        'En intégration': { bg: 'bg-blue-50 text-blue-700 border-blue-200' },
        'Inactif': { bg: 'bg-slate-50 text-slate-700 border-slate-200' },
        'Suspendu': { bg: 'bg-rose-50 text-rose-700 border-rose-200' }
    };
    return map[status] || map['Inactif'];
};

const getStatusIcon = (status) => {
    const map = {
        'Actif': 'pi pi-check-circle',
        'En intégration': 'pi pi-spin pi-spinner',
        'Inactif': 'pi pi-minus-circle',
        'Suspendu': 'pi pi-lock'
    };
    return map[status] || 'pi pi-circle';
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(date));
};

const parseJSON = (data) => {
    if (!data) return [];
    if (typeof data === 'object') return data;
    try { return JSON.parse(data); } catch(e) { return []; }
};

const onPageChange = (event) => {
    router.get(route('users.instructors.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true, preserveScroll: true });
};

const notify = (msg) => toast.add({ severity: 'info', summary: 'Action requise', detail: msg, life: 3000 });

// Upload Visuel Avatar
const uploadMockProgress = ref(0);
const onAvatarSelect = (event) => {
    instructorForm.avatar = event.files[0];
    uploadMockProgress.value = 0;
    const interval = setInterval(() => {
        uploadMockProgress.value += 15;
        if (uploadMockProgress.value >= 100) {
            clearInterval(interval);
            toast.add({ severity: 'success', summary: 'Média chargé', detail: 'La photo est prête pour la sauvegarde.', life: 2000 });
        }
    }, 100);
};

// ======================================================================================
// COMPUTED PROPERTIES (Mappage DB)
// ======================================================================================
const enrichedInstructors = computed(() => {
    if (!props.instructors?.data) return [];
    return props.instructors.data;
});

// Mockup Chart Data pour le Profil 360
const chartOptions = {
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false } }
    },
    tension: 0.4
};
const revenueChartData = ref({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{
        label: 'Revenus générés ($)',
        data: [1200, 1800, 1650, 2400, 3100, 4200],
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        fill: true,
        borderWidth: 2,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#4f46e5',
        pointRadius: 4
    }]
});

// ======================================================================================
// HOOKS
// ======================================================================================
onMounted(() => {
    setTimeout(() => loading.value = false, 500);
});
</script>

<template>
    <AppLayout>
        <Head title="Corps Enseignant | Enterprise HR" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE ET KPI                                                          -->
            <!-- ============================================================================== -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Ressources Humaines', class: 'font-semibold text-indigo-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            Corps Enseignant & Experts
                            <Badge value="HR Management" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Supervisez l'équipe pédagogique. Pilotez les assignations de cours, analysez les indicateurs de qualité (avis, engagement) et gérez la politique de rémunération (commissions).
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Rapports Financiers" icon="pi pi-file-excel" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 hover:bg-slate-50 w-full sm:w-auto font-medium shadow-sm" />
                    <Button label="Recruter un Expert" icon="pi pi-user-plus" class="p-button-primary shadow-md shadow-indigo-500/20 w-full sm:w-auto font-bold px-6" @click="openNew" />
                </div>
            </div>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Effectif Instructeurs -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-briefcase text-6xl text-indigo-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-users text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Experts</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.stats.total_instructors || 0 }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Cours Dispensés -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-book text-6xl text-emerald-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-video text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Formations Actives</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats.active_courses || 0 }}</h3>
                        <p class="text-xs text-emerald-600 font-medium mt-1">Publiées sur la plateforme</p>
                    </div>
                </div>

                <!-- Note Moyenne -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-star-fill text-6xl text-amber-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-star text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Note de Qualité</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.stats.avg_rating || '0.0' }}</h3>
                            <Rating :modelValue="Number(props.stats.avg_rating)" readonly :cancel="false" class="pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Commissions Versées -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-wallet text-6xl text-rose-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class="pi pi-dollar text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Commissions</p>
                        </div>
                        <h3 class="text-3xl font-black text-slate-800">{{ formatCurrency(props.stats.total_commissions) }}</h3>
                        <p class="text-xs text-slate-400 mt-1">Reversées depuis le lancement</p>
                    </div>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DATA GRID : ANNUAIRE DES INSTRUCTEURS                                          -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- TOOLBAR DE FILTRAGE -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-auto">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher (Nom, Email)..." class="w-full sm:w-80 border-slate-200 shadow-sm font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.status" :options="props.statuses" placeholder="Statut Contrat" class="w-full sm:w-48 border-slate-200 shadow-sm" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2 font-medium text-slate-700">
                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-400"></i>
                                    <span>{{ slotProps.value }}</span>
                                </div>
                                <span v-else class="text-slate-500">Filtrer par statut</span>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.specialty" :options="props.specialtiesList" optionLabel="label" optionValue="value" placeholder="Domaine d'expertise" class="w-full sm:w-56 border-slate-200 shadow-sm" showClear />
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedInstructors.length > 0" icon="pi pi-trash" :label="`Purger (${selectedInstructors.length})`" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkDelete" />
                        <Button icon="pi pi-ellipsis-v" class="p-button-secondary p-button-text p-button-rounded bg-slate-50 border border-slate-200" @click="menu.toggle($event)" aria-haspopup="true" aria-controls="overlay_menu" v-tooltip.top="'Actions Groupées'" />
                        <Menu ref="menu" id="overlay_menu" :model="bulkActionsMenu" :popup="true" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser la base'" />
                    </div>
                </div>

                <!-- TABLEAU DE DONNÉES -->
                <DataTable
                    v-model:selection="selectedInstructors"
                    v-model:expandedRows="expandedRows"
                    :value="enrichedInstructors"
                    dataKey="id"
                    :lazy="true"
                    :paginator="true"
                    :rows="props.instructors.per_page"
                    :totalRecords="props.instructors.total"
                    :first="(props.instructors.current_page - 1) * props.instructors.per_page"
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
                                <i class="pi pi-briefcase text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun instructeur trouvé</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                La base de données ne renvoie aucun expert pour ces filtres. Modifiez votre recherche ou recrutez un nouveau formateur.
                            </p>
                            <Button label="Ajouter un Expert" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <!-- COLONNES -->
                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="name" header="Identité Formateur" style="min-width: 22rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <div class="relative cursor-pointer group" @click="viewProfile(data)">
                                    <Avatar v-if="data.avatar_path" :image="data.avatar_path" shape="circle" size="xlarge" class="border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <Avatar v-else :label="data.name.charAt(0).toUpperCase()" shape="circle" size="xlarge" class="bg-indigo-100 text-indigo-700 font-black border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <span :class="['absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full ring-2 ring-white', data.status === 'Actif' ? 'bg-emerald-500' : 'bg-slate-300']"></span>
                                </div>
                                <div class="flex flex-col justify-center">
                                    <span class="font-extrabold text-slate-800 text-base hover:text-indigo-600 cursor-pointer transition-colors" @click="editInstructor(data)">
                                        {{ data.name }}
                                    </span>
                                    <div class="text-xs text-slate-500 flex items-center gap-3 mt-0.5">
                                        <span class="flex items-center gap-1"><i class="pi pi-envelope text-[10px]"></i> {{ data.email }}</span>
                                    </div>
                                    <!-- Badges Spécialités -->
                                    <div class="flex flex-wrap gap-1 mt-1.5" v-if="parseJSON(data.specialties).length">
                                        <span v-for="(spec, idx) in parseJSON(data.specialties).slice(0, 2)" :key="idx" class="text-[9px] bg-slate-100 border border-slate-200 text-slate-600 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">
                                            {{ spec.name || spec }}
                                        </span>
                                        <span v-if="parseJSON(data.specialties).length > 2" class="text-[9px] text-slate-400 font-bold ml-1">+{{ parseJSON(data.specialties).length - 2 }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="Statut Contrat" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status"
                                :icon="getStatusIcon(data.status)"
                                class="text-[10px] font-bold px-3 py-1.5 border"
                                :class="getSeverity(data.status).bg"
                            />
                        </template>
                    </Column>

                    <Column field="metrics" header="Engagement & KPI" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-book text-slate-400"></i> Cours affectés</span>
                                    <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ data.assigned_courses_count || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-users text-slate-400"></i> Total Élèves</span>
                                    <span class="font-bold text-slate-700">{{ data.total_students || 0 }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="finance" header="Finance & Avis" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-dollar text-slate-400"></i> CA Généré</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(data.generated_revenue || 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-star-fill text-amber-400"></i> Avis Global</span>
                                    <span class="font-bold text-slate-800">{{ data.average_rating || 'N/A' }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="min-width: 8rem; text-align: right; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-chart-line" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-indigo-600 hover:bg-indigo-50" v-tooltip.top="'Profil Complet 360°'" @click="viewProfile(data)" />
                                <Button icon="pi pi-user-edit" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="'Modifier le dossier RH'" @click="editInstructor(data)" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-rose-600 hover:bg-rose-50" v-tooltip.top="'Archiver le contrat'" @click="confirmDeleteInstructor(data)" />
                            </div>
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (PORTFOLIO DE COURS RAPIDE) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="pi pi-book"></i></div>
                                        Portfolio Pédagogique Actif
                                    </h4>
                                    <div class="flex gap-2">
                                        <Badge :value="`Taux de Commission : ${data.commission_rate || 70}%`" severity="info" class="bg-blue-50 text-blue-700 border border-blue-200"></Badge>
                                    </div>
                                </div>

                                <div v-if="data.assigned_courses && data.assigned_courses.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                    <div v-for="course in data.assigned_courses" :key="course.id" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">

                                        <!-- Header Cours -->
                                        <div class="flex items-center gap-4 mb-4">
                                            <div class="w-16 h-12 rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                                <img v-if="course.thumbnail_path" :src="course.thumbnail_path" class="w-full h-full object-cover" />
                                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400"><i class="pi pi-image text-xl"></i></div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h5 class="font-bold text-sm text-slate-800 truncate" :title="course.title">{{ course.title }}</h5>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <Tag :value="course.status" severity="success" class="text-[8px] uppercase tracking-widest px-1.5 py-0" />
                                                    <span class="text-[10px] text-slate-500">{{ course.level || 'Général' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stats Rapides -->
                                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3">
                                            <div class="text-center">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Élèves</p>
                                                <p class="text-sm font-black text-slate-700">{{ course.students_count || 0 }}</p>
                                            </div>
                                            <div class="text-center border-x border-slate-100">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Avis</p>
                                                <p class="text-sm font-black text-amber-500 flex items-center justify-center gap-1"><i class="pi pi-star-fill text-[10px]"></i> {{ course.rating || 'N/A' }}</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Revenu</p>
                                                <p class="text-sm font-black text-emerald-600">{{ formatCurrency(course.revenue || 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-slate-500 p-8 bg-white rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-3 hover:bg-slate-50 transition-colors">
                                    <i class="pi pi-inbox text-4xl text-slate-300"></i>
                                    <div class="text-center">
                                        <p class="font-bold text-slate-700 text-lg">Aucune formation assignée</p>
                                        <p class="text-sm max-w-sm mt-1">Cet expert n'a pas encore créé de contenu pédagogique sur la plateforme.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR ULTRA PRO (INSTRUCTOR WORKSPACE)                                -->
        <!-- Layout strict : Full height contraint, Stepper à gauche, Contenu scrollable    -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="instructorDialog"
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
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '33%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <i class="pi pi-user-edit text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ instructorForm.id ? 'Dossier Instructeur' : 'Intégration RH (Expert)' }}
                            <Tag v-if="instructorForm.status" :value="instructorForm.status" :class="getSeverity(instructorForm.status).bg" class="text-[9px] uppercase tracking-wider px-2 py-0.5 border" />
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px]">{{ instructorForm.name || 'Nouveau Profil' }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> Enregistré à {{ formatDate(lastSavedAt) }}</span>
                            <span v-else-if="instructorForm.isDirty" class="flex items-center gap-1 text-amber-500"><i class="pi pi-spin pi-spinner"></i> Modifications en cours...</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" @click="instructorDialog = false" v-tooltip.bottom="'Fermer le dossier sans sauvegarder'" />
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
                                    {{ isEntireFormValid ? 'Toutes les infos obligatoires sont remplies.' : 'Vérifiez les sections avec avertissement.' }}
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
                                        <label class="block text-sm font-bold text-slate-700">Nom Complet de l'Expert <span class="text-rose-500">*</span></label>
                                        <InputText v-model="instructorForm.name" class="w-full p-4 text-lg font-bold text-slate-800 bg-slate-50 focus:bg-white transition-colors" placeholder="Ex: Alphonse Kabongo" :class="{'p-invalid': instructorForm.errors?.name || (!instructorForm.name && instructorForm.isDirty)}" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Adresse Email Professionnelle <span class="text-rose-500">*</span></label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-envelope text-slate-400" />
                                            <InputText v-model="instructorForm.email" type="email" class="w-full bg-slate-50 focus:bg-white" placeholder="alphonse.k@subnet.cd" :class="{'p-invalid': instructorForm.errors?.email}" />
                                        </IconField>
                                        <small class="text-slate-500 text-[11px]">Sert d'identifiant de connexion pour le tableau de bord Formateur.</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Téléphone Mobile</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-phone text-slate-400" />
                                            <InputText v-model="instructorForm.numero" class="w-full bg-slate-50 focus:bg-white" placeholder="+243 ..." />
                                        </IconField>
                                    </div>

                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Adresse / Bureau</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-map-marker text-slate-400" />
                                            <InputText v-model="instructorForm.address" class="w-full bg-slate-50 focus:bg-white" placeholder="Ville, Commune, Numéro..." />
                                        </IconField>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2 : EXPERTISE & BIO -->
                        <div v-show="activeEditorTab === 'expertise'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-briefcase"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Profil Académique & Bio</h3>
                                        <p class="text-xs text-slate-500 mt-1">Ces informations seront visibles par les étudiants sur les pages de cours.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Domaines de Spécialité <span class="text-rose-500">*</span></label>
                                        <MultiSelect v-model="instructorForm.specialties" :options="props.specialtiesList" optionLabel="label" optionValue="value" placeholder="Sélectionnez les compétences clés..." display="chip" class="w-full bg-slate-50" :class="{'p-invalid': !instructorForm.specialties.length}" filter />
                                        <small class="text-slate-500 text-[11px]">Sert à classifier l'instructeur dans l'annuaire public.</small>
                                    </div>

                                    <div class="space-y-2 mt-2">
                                        <label class="block text-sm font-bold text-slate-700">Biographie Professionnelle (Présentation)</label>
                                        <Textarea v-model="instructorForm.bio" rows="6" class="w-full p-4 bg-slate-50 border-slate-300 focus:bg-white leading-relaxed" placeholder="Décrivez le parcours, les diplômes et l'expérience de l'expert en quelques paragraphes. C'est son argumentaire de vente..." />
                                    </div>

                                    <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 space-y-4 mt-2">
                                        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2"><i class="pi pi-link text-slate-400"></i> Présence Digitale</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-[11px] font-bold text-slate-500 uppercase">Profil LinkedIn</label>
                                                <IconField iconPosition="left">
                                                    <InputIcon class="pi pi-linkedin text-blue-500" />
                                                    <InputText v-model="instructorForm.social.linkedin" placeholder="https://linkedin.com/in/..." class="w-full bg-white" />
                                                </IconField>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[11px] font-bold text-slate-500 uppercase">Site Web / Portfolio</label>
                                                <IconField iconPosition="left">
                                                    <InputIcon class="pi pi-globe text-slate-400" />
                                                    <InputText v-model="instructorForm.social.website" placeholder="https://..." class="w-full bg-white" />
                                                </IconField>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3 : MÉDIAS (AVATAR) -->
                        <div v-show="activeEditorTab === 'media'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Photo de Profil Officielle</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                                    <div class="flex flex-col items-center justify-center p-8 bg-slate-50 border-2 border-dashed border-slate-300 rounded-3xl relative group hover:bg-indigo-50/50 hover:border-indigo-300 transition-colors">

                                        <Avatar v-if="instructorForm.avatar" :image="typeof instructorForm.avatar === 'string' ? instructorForm.avatar : URL.createObjectURL(instructorForm.avatar)" size="xlarge" shape="circle" class="w-40 h-40 shadow-2xl ring-4 ring-white mb-6 object-cover" />
                                        <div v-else class="w-40 h-40 rounded-full bg-white flex items-center justify-center shadow-inner border border-slate-200 mb-6 text-slate-300">
                                            <i class="pi pi-camera text-6xl"></i>
                                        </div>

                                        <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" customUpload @select="onAvatarSelect" chooseLabel="Sélectionner une photo PRO" class="p-button-outlined p-button-sm font-bold rounded-full px-6 bg-white" auto />

                                        <ProgressBar v-if="uploadMockProgress > 0 && uploadMockProgress < 100" :value="uploadMockProgress" class="absolute bottom-0 left-4 right-4 h-1 rounded-t-none" :showValue="false" />
                                    </div>

                                    <div class="space-y-4">
                                        <h4 class="font-bold text-slate-800 text-sm">Standards Qualité SubNetCongo</h4>
                                        <p class="text-sm text-slate-500 leading-relaxed mb-4">La photo de l'instructeur est un gage de confiance. Les cours dont les formateurs ont une photo professionnelle ont <strong>40% de conversion en plus</strong>.</p>
                                        <ul class="space-y-3 text-sm text-slate-600 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> Portrait centré, visage dégagé, fond uni.</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Format recommandé :</strong> 800x800px (Carré).</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> Poids inférieur à 2 Mo (.PNG ou .JPG).</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4 : FINANCE & CONTRAT -->
                        <div v-show="activeEditorTab === 'finance'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-percentage"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Modèle Économique & Statut RH</h3>
                                        <p class="text-xs text-slate-500 mt-1">Définissez la part de revenu reversée sur chaque vente de cours.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Statut du Contrat <span class="text-rose-500">*</span></label>
                                        <Dropdown v-model="instructorForm.status" :options="props.statuses" class="w-full md:w-1/2 font-medium bg-slate-50">
                                            <template #value="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-500"></i>
                                                    <span>{{ slotProps.value }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                        <small class="text-slate-500 text-[11px] block mt-1">Seuls les statuts "Actif" permettent de recevoir des paiements automatiques.</small>
                                    </div>

                                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 space-y-6 mt-4">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700">Taux de Commission Formateur (%)</label>
                                                <p class="text-[11px] text-slate-500">Pourcentage reversé à l'expert sur le prix net du cours.</p>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <span class="font-black text-emerald-600 text-2xl">{{ instructorForm.commission_rate }} %</span>
                                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Plateforme: {{ 100 - instructorForm.commission_rate }}%</span>
                                            </div>
                                        </div>

                                        <Slider v-model="instructorForm.commission_rate" class="w-full" :min="0" :max="100" />

                                        <div class="flex justify-between text-[10px] text-slate-400 font-mono font-bold px-1">
                                            <span>0%</span>
                                            <span>25%</span>
                                            <span>50%</span>
                                            <span>75%</span>
                                            <span>100%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5 : SÉCURITÉ -->
                        <div v-show="activeEditorTab === 'security'" class="space-y-8 animate-fade-in" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-shield text-slate-400"></i> Accès au Panel Formateur</h3>

                                <div class="space-y-4">
                                    <!-- Blocage Compte -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-rose-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-rose-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-lock"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Bannir / Suspendre l'accès</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">L'instructeur ne pourra plus accéder à son tableau de bord, ni modifier ses cours. Ses cours actuels resteront en ligne sauf intervention.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="instructorForm.is_suspended" />
                                    </div>

                                    <!-- Reset Password -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-amber-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-amber-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-key"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">Forcer la réinitialisation du mot de passe</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">Un lien sécurisé de changement de mot de passe sera envoyé à son adresse email lors de la sauvegarde.</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="instructorForm.requires_password_reset" />
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
                    <span v-if="!isEntireFormValid" class="text-amber-600 font-bold flex items-center gap-2"><i class="pi pi-exclamation-triangle"></i> Remplissez toutes les sections pour valider le contrat.</span>
                </div>

                <div class="flex items-center gap-3">
                    <Button label="Fermer" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-50" @click="instructorDialog = false" />

                    <div class="relative group">
                        <Button label="Enregistrer le Profil" icon="pi pi-check-circle" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold px-8 text-sm" @click="saveInstructor" :loading="instructorForm.processing" :disabled="!isEntireFormValid" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- SIDEBAR PROFIL 360° (ANALYTICS & DETAILS)                                      -->
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
            <template #header>
                <div class="flex items-center gap-4 w-full">
                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">
                        <i class="pi pi-id-card text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="font-black text-xl text-slate-800 leading-tight">Profil Expert</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Dossier RH & Performance</p>
                    </div>
                </div>
            </template>

            <div v-if="currentInstructor" class="flex flex-col h-full relative">
                <!-- Hero Header -->
                <div class="bg-gradient-to-br from-indigo-700 via-indigo-600 to-blue-700 p-8 text-white rounded-b-[2rem] shadow-lg relative overflow-hidden flex-shrink-0">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-black/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex flex-col items-center text-center">
                        <Avatar v-if="currentInstructor.avatar_path" :image="currentInstructor.avatar_path" size="xlarge" shape="circle" class="ring-4 ring-white/30 mb-4 w-28 h-28 shadow-2xl bg-white object-cover" />
                        <Avatar v-else :label="currentInstructor.name?.charAt(0).toUpperCase()" size="xlarge" shape="circle" class="ring-4 ring-white/30 mb-4 w-28 h-28 shadow-2xl bg-white text-indigo-600 font-black text-4xl" />

                        <h2 class="text-2xl font-black mb-1">{{ currentInstructor.name }}</h2>
                        <div class="flex items-center justify-center gap-2 mb-4">
                            <Tag :value="currentInstructor.status" class="bg-white/20 text-white border border-white/30 px-3 py-1 text-xs" />
                            <span class="text-indigo-200 text-xs font-mono bg-black/20 px-2 py-1 rounded">Comm: {{ currentInstructor.commission_rate || 70 }}%</span>
                        </div>

                        <div class="grid grid-cols-3 w-full mt-4 gap-3">
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-white/10 shadow-inner">
                                <p class="text-[9px] text-indigo-200 uppercase font-bold tracking-widest mb-1">Ventes (Élèves)</p>
                                <p class="text-xl font-black">{{ currentInstructor.total_students || 0 }}</p>
                            </div>
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-white/10 shadow-inner">
                                <p class="text-[9px] text-indigo-200 uppercase font-bold tracking-widest mb-1">Cours Actifs</p>
                                <p class="text-xl font-black">{{ currentInstructor.assigned_courses_count || 0 }}</p>
                            </div>
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-emerald-500/30 shadow-inner text-emerald-300">
                                <p class="text-[9px] text-emerald-200 uppercase font-bold tracking-widest mb-1">Gains ($)</p>
                                <p class="text-xl font-black">{{ formatCurrency(currentInstructor.generated_revenue || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Content -->
                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <TabView class="custom-tabview">

                        <!-- TAB: APERÇU -->
                        <TabPanel header="Identité & Bio">
                            <div class="space-y-6 pt-4">
                                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-map-marker"></i> Coordonnées & Web</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-4 text-slate-600">
                                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-400"><i class="pi pi-envelope"></i></div>
                                            <span class="text-sm font-medium">{{ currentInstructor.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-4 text-slate-600">
                                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-400"><i class="pi pi-phone"></i></div>
                                            <span class="text-sm font-medium">{{ currentInstructor.numero || 'Non renseigné' }}</span>
                                        </div>

                                        <Divider />

                                        <div class="flex gap-2" v-if="parseJSON(currentInstructor.social).linkedin || parseJSON(currentInstructor.social).website">
                                            <a v-if="parseJSON(currentInstructor.social).linkedin" :href="parseJSON(currentInstructor.social).linkedin" target="_blank" class="w-10 h-10 rounded-full bg-[#0077b5]/10 text-[#0077b5] flex items-center justify-center hover:bg-[#0077b5] hover:text-white transition-colors">
                                                <i class="pi pi-linkedin"></i>
                                            </a>
                                            <a v-if="parseJSON(currentInstructor.social).website" :href="parseJSON(currentInstructor.social).website" target="_blank" class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-colors">
                                                <i class="pi pi-globe"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-tags"></i> Spécialités</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Tag v-for="spec in parseJSON(currentInstructor.specialties)" :key="spec" :value="spec.name || spec" class="bg-indigo-50 text-indigo-700 border border-indigo-100 font-bold px-3 py-1" />
                                        <span v-if="!parseJSON(currentInstructor.specialties).length" class="text-sm text-slate-400 italic">Aucune spécialité renseignée.</span>
                                    </div>
                                </div>

                                <div v-if="currentInstructor.bio" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Biographie</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed italic bg-slate-50 p-4 rounded-xl">"{{ currentInstructor.bio }}"</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB: ANALYSE & FINANCE -->
                        <TabPanel header="Analyse Financière">
                            <div class="pt-4 space-y-6">
                                <!-- Graphe de revenus (Mockup pour illustration UI B2B) -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-chart-bar"></i> Évolution des commissions (6 mois)</h4>
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                        <div class="flex items-baseline gap-2 mb-4">
                                            <span class="text-2xl font-black text-slate-800">{{ formatCurrency(currentInstructor.generated_revenue || 0) }}</span>
                                            <span class="text-xs text-emerald-500 font-bold bg-emerald-50 px-2 py-1 rounded-md"><i class="pi pi-arrow-up text-[10px]"></i> Tendance globale</span>
                                        </div>
                                        <Chart type="line" :data="revenueChartData" :options="chartOptions" class="h-48 w-full" />
                                    </div>
                                </div>

                                <!-- Avis Global -->
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Qualité Pédagogique</h4>
                                        <p class="text-sm text-slate-600">Note moyenne basée sur l'ensemble du catalogue.</p>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <div class="text-3xl font-black text-amber-500 flex items-center gap-2">
                                            {{ currentInstructor.average_rating || 'N/A' }}
                                            <i class="pi pi-star-fill text-xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2 mt-1">
                    <Button label="Fermer" class="p-button-text p-button-secondary font-bold" @click="profileSidebar = false" />
                    <Button label="Modifier Profil" icon="pi pi-pencil" class="p-button-primary px-8 font-black shadow-lg shadow-indigo-500/30 rounded-xl" @click="editInstructor(currentInstructor); profileSidebar = false" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
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

/* Expansion Row Styles */
:deep(.p-datatable .p-datatable-tbody > tr.p-datatable-row-expansion > td) {
    padding: 0 !important;
    background: #f8fafc;
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
:deep(.p-inputtext:enabled:focus),
:deep(.p-dropdown:not(.p-disabled).p-focus) {
    box-shadow: none;
    border-color: #818cf8;
}

/* Sidebar Custom Overlay */
:deep(.p-sidebar-custom) {
    background-color: #f8fafc;
    padding: 0;
}
:deep(.p-sidebar-header) {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: white;
}
:deep(.p-sidebar-content) {
    padding: 0;
    overflow: hidden;
}

/* Slider Customization */
:deep(.p-slider) {
    background: #e2e8f0;
    height: 0.5rem;
}
:deep(.p-slider .p-slider-handle) {
    background: #10b981;
    border: 3px solid white;
    width: 1.25rem;
    height: 1.25rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
:deep(.p-slider .p-slider-range) {
    background: #10b981;
}
</style>
