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
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';
import { useI18n } from 'vue-i18n';

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
const { t } = useI18n();

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
    { label: t('pages.instructors.bulkMessage'), icon: 'pi pi-envelope', command: () => notify(t('pages.instructors.bulkMessagePending')) },
    { label: t('pages.instructors.bulkPayroll'), icon: 'pi pi-dollar', command: () => notify(t('pages.instructors.bulkPayrollPending')) },
    { separator: true },
    { label: t('pages.instructors.bulkSuspend'), icon: 'pi pi-lock', command: () => confirmBulkSuspend() }
];

// ======================================================================================
// CONFIGURATION DU WORKSPACE ÉDITEUR (STEPPER)
// ======================================================================================
const editorMenu = ref([
    { id: 'general', label: t('pages.instructors.stepGeneralLabel'), description: t('pages.instructors.stepGeneralDesc'), icon: 'pi pi-id-card' },
    { id: 'expertise', label: t('pages.instructors.stepExpertiseLabel'), description: t('pages.instructors.stepExpertiseDesc'), icon: 'pi pi-briefcase' },
    { id: 'media', label: t('pages.instructors.stepMediaLabel'), description: t('pages.instructors.stepMediaDesc'), icon: 'pi pi-image' },
    { id: 'finance', label: t('pages.instructors.stepFinanceLabel'), description: t('pages.instructors.stepFinanceDesc'), icon: 'pi pi-percentage' },
    { id: 'security', label: t('pages.instructors.stepSecurityLabel'), description: t('pages.instructors.stepSecurityDesc'), icon: 'pi pi-shield' }
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
        toast.add({ severity: 'warn', summary: t('pages.instructors.formIncomplete'), detail: t('pages.instructors.formIncompleteDetail'), life: 5000 });
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
            toast.add({ severity: 'success', summary: t('pages.instructors.operationSuccess'), detail: instructorForm.id ? t('pages.instructors.dossierUpdated') : t('pages.instructors.dossierCreated'), life: 3000 });
            lastSavedAt.value = new Date();
            instructorDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: t('pages.instructors.serverError'), detail: t('pages.instructors.serverErrorDetail'), life: 5000 });
        }
    });
};

const confirmDeleteInstructor = (instructor) => {
    confirm.require({
        message: t('pages.instructors.confirmDeleteMessage', { name: instructor.name }),
        header: t('pages.instructors.deleteHrDossierHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: t('pages.instructors.yesPurge'),
        rejectLabel: t('pages.instructors.cancel'),
        accept: () => {
            router.delete(route('users.instructors.destroy', instructor.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: t('pages.instructors.archived'), detail: t('pages.instructors.dossierDeleted'), life: 3000 })
            });
        }
    });
};

const confirmBulkDelete = () => {
    if (!selectedInstructors.value.length) return;
    confirm.require({
        message: t('pages.instructors.bulkDeleteMessage', { count: selectedInstructors.value.length }),
        header: t('pages.instructors.bulkPurgeHeader'),
        icon: 'pi pi-ban',
        acceptClass: 'p-button-danger',
        acceptLabel: t('pages.instructors.confirmPurge'),
        rejectLabel: t('pages.instructors.cancel'),
        accept: () => {
            const ids = selectedInstructors.value.map(i => i.id);
            router.delete(route('users.instructors.bulk-destroy'), {
                data: { ids },
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('pages.instructors.toastSuccess'), detail: t('pages.instructors.selectedDeleted'), life: 3000 });
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
            toast.add({ severity: 'warn', summary: t('pages.instructors.contractsSuspended'), detail: t('pages.instructors.instructorsSuspended', { count: ids.length }), life: 3000 });
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
        'Suspendu': { bg: 'bg-red-50 text-red-700 border-red-200' }
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

const notify = (msg) => toast.add({ severity: 'info', summary: t('pages.instructors.actionRequired'), detail: msg, life: 3000 });

// Upload Visuel Avatar
const uploadMockProgress = ref(0);
const onAvatarSelect = (event) => {
    instructorForm.avatar = event.files[0];
    uploadMockProgress.value = 0;
    const interval = setInterval(() => {
        uploadMockProgress.value += 15;
        if (uploadMockProgress.value >= 100) {
            clearInterval(interval);
            toast.add({ severity: 'success', summary: t('pages.instructors.mediaLoaded'), detail: t('pages.instructors.photoReady'), life: 2000 });
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
    labels: [t('pages.instructors.monthJan'), t('pages.instructors.monthFeb'), t('pages.instructors.monthMar'), t('pages.instructors.monthApr'), t('pages.instructors.monthMay'), t('pages.instructors.monthJun')],
    datasets: [{
        label: t('pages.instructors.revenueGenerated'),
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
        <Head :title="t('pages.instructors.headTitle')" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE (Design System PageHeader)                                       -->
            <!-- ============================================================================== -->
            <PageHeader
                :title="`${t('pages.instructors.titleLine1')} ${t('pages.instructors.titleHighlight')}`"
                :subtitle="t('pages.instructors.subtitle')"
                badge="HR Management"
                icon="pi-briefcase"
                accent="indigo"
                :actions="[
                    { label: t('pages.instructors.financialReports'), icon: 'pi-file-excel', variant: 'secondary' },
                    { label: t('pages.instructors.recruitExpert'), icon: 'pi-user-plus', variant: 'primary', onClick: openNew },
                ]"
            />

            <div class="page-container page-section">

                <!-- ============================================================================== -->
                <!-- KPI CARDS                                                                       -->
                <!-- ============================================================================== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <PageKpi :label="t('pages.instructors.kpiTotalExperts')" :value="props.stats.total_instructors || 0" icon="pi-briefcase" color="blue" />
                    <PageKpi :label="t('pages.instructors.kpiActiveCourses')" :value="props.stats.active_courses || 0" icon="pi-book" color="emerald" />
                    <PageKpi :label="t('pages.instructors.kpiQualityRating')" :value="props.stats.avg_rating || '0.0'" icon="pi-star-fill" color="amber" subtext="/ 5.0" />
                    <PageKpi :label="t('pages.instructors.kpiTotalCommissions')" :value="formatCurrency(props.stats.total_commissions)" icon="pi-wallet" color="red" />
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
                            <InputText v-model="serverFilters.search" :placeholder="t('pages.instructors.searchPlaceholder')" class="w-full sm:w-80 border-slate-200 shadow-sm font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.status" :options="props.statuses" :placeholder="t('pages.instructors.contractStatus')" class="w-full sm:w-48 border-slate-200 shadow-sm" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2 font-medium text-slate-700">
                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-400"></i>
                                    <span>{{ slotProps.value }}</span>
                                </div>
                                <span v-else class="text-slate-500">{{ t('pages.instructors.filterByStatus') }}</span>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.specialty" :options="props.specialtiesList" optionLabel="label" optionValue="value" :placeholder="t('pages.instructors.expertiseDomain')" class="w-full sm:w-56 border-slate-200 shadow-sm" showClear />
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedInstructors.length > 0" icon="pi pi-trash" :label="t('pages.instructors.purgeCount', { count: selectedInstructors.length })" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkDelete" />
                        <Button icon="pi pi-ellipsis-v" class="p-button-secondary p-button-text p-button-rounded bg-slate-50 border border-slate-200" @click="menu.toggle($event)" aria-haspopup="true" aria-controls="overlay_menu" v-tooltip.top="t('pages.instructors.bulkActions')" />
                        <Menu ref="menu" id="overlay_menu" :model="bulkActionsMenu" :popup="true" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="t('pages.instructors.refreshBase')" />
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
                            <h3 class="text-xl font-bold text-slate-800 mb-2">{{ t('pages.instructors.emptyTitle') }}</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                {{ t('pages.instructors.emptyDescription') }}
                            </p>
                            <Button :label="t('pages.instructors.addExpert')" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <!-- COLONNES -->
                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="name" :header="t('pages.instructors.colTrainerIdentity')" style="min-width: 22rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <div class="relative cursor-pointer group" @click="viewProfile(data)">
                                    <Avatar v-if="data.avatar_path" :image="data.avatar_path" shape="circle" size="xlarge" class="border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <Avatar v-else :label="data.name.charAt(0).toUpperCase()" shape="circle" size="xlarge" class="bg-blue-100 text-blue-700 font-black border-2 border-white shadow-sm transition-transform group-hover:scale-105" />
                                    <span :class="['absolute bottom-0 right-0 block h-3.5 w-3.5 rounded-full ring-2 ring-white', data.status === 'Actif' ? 'bg-emerald-500' : 'bg-slate-300']"></span>
                                </div>
                                <div class="flex flex-col justify-center">
                                    <span class="font-extrabold text-slate-800 text-base hover:text-blue-600 cursor-pointer transition-colors" @click="editInstructor(data)">
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

                    <Column field="status" :header="t('pages.instructors.contractStatus')" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <Tag
                                :value="data.status"
                                :icon="getStatusIcon(data.status)"
                                class="text-[10px] font-bold px-3 py-1.5 border"
                                :class="getSeverity(data.status).bg"
                            />
                        </template>
                    </Column>

                    <Column field="metrics" :header="t('pages.instructors.colEngagementKpi')" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-book text-slate-400"></i> {{ t('pages.instructors.assignedCourses') }}</span>
                                    <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ data.assigned_courses_count || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-users text-slate-400"></i> {{ t('pages.instructors.totalLearners') }}</span>
                                    <span class="font-bold text-slate-700">{{ data.total_students || 0 }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="finance" :header="t('pages.instructors.colFinanceRating')" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-dollar text-slate-400"></i> {{ t('pages.instructors.revenueGeneratedShort') }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(data.generated_revenue || 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-36">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-star-fill text-amber-400"></i> {{ t('pages.instructors.overallRating') }}</span>
                                    <span class="font-bold text-slate-800">{{ data.average_rating || 'N/A' }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('pages.instructors.actions')" alignFrozen="right" :frozen="true" style="min-width: 8rem; text-align: right; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-chart-line" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="t('pages.instructors.fullProfile360')" @click="viewProfile(data)" />
                                <Button icon="pi pi-user-edit" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="t('pages.instructors.editHrDossier')" @click="editInstructor(data)" />
                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-red-600 hover:bg-red-50" v-tooltip.top="t('pages.instructors.archiveContract')" @click="confirmDeleteInstructor(data)" />
                            </div>
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (PORTFOLIO DE COURS RAPIDE) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="pi pi-book"></i></div>
                                        {{ t('pages.instructors.activeTeachingPortfolio') }}
                                    </h4>
                                    <div class="flex gap-2">
                                        <Badge :value="t('pages.instructors.commissionRateBadge', { rate: data.commission_rate || 70 })" severity="info" class="bg-blue-50 text-blue-700 border border-blue-200"></Badge>
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
                                                    <span class="text-[10px] text-slate-500">{{ course.level || t('pages.instructors.general') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stats Rapides -->
                                        <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3">
                                            <div class="text-center">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ t('pages.instructors.learners') }}</p>
                                                <p class="text-sm font-black text-slate-700">{{ course.students_count || 0 }}</p>
                                            </div>
                                            <div class="text-center border-x border-slate-100">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ t('pages.instructors.reviews') }}</p>
                                                <p class="text-sm font-black text-amber-500 flex items-center justify-center gap-1"><i class="pi pi-star-fill text-[10px]"></i> {{ course.rating || 'N/A' }}</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">{{ t('pages.instructors.revenue') }}</p>
                                                <p class="text-sm font-black text-emerald-600">{{ formatCurrency(course.revenue || 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-slate-500 p-8 bg-white rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-3 hover:bg-slate-50 transition-colors">
                                    <i class="pi pi-inbox text-4xl text-slate-300"></i>
                                    <div class="text-center">
                                        <p class="font-bold text-slate-700 text-lg">{{ t('pages.instructors.noCourseAssigned') }}</p>
                                        <p class="text-sm max-w-sm mt-1">{{ t('pages.instructors.noCourseAssignedHelp') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
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
                <div class="absolute top-0 left-0 h-0.5 bg-blue-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '33%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-blue-500/30">
                        <i class="pi pi-user-edit text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ instructorForm.id ? t('pages.instructors.dossierTitle') : t('pages.instructors.integrationTitle') }}
                            <Tag v-if="instructorForm.status" :value="instructorForm.status" :class="getSeverity(instructorForm.status).bg" class="text-[9px] uppercase tracking-wider px-2 py-0.5 border" />
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px]">{{ instructorForm.name || t('pages.instructors.newProfile') }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> {{ t('pages.instructors.savedAt', { date: formatDate(lastSavedAt) }) }}</span>
                            <span v-else-if="instructorForm.isDirty" class="flex items-center gap-1 text-amber-500"><i class="pi pi-spin pi-spinner"></i> {{ t('pages.instructors.unsavedChanges') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" @click="instructorDialog = false" v-tooltip.bottom="t('pages.instructors.closeDossierNoSave')" />
                </div>
            </div>

            <!-- WORKSPACE BODY (FLEX ROW) -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR DE NAVIGATION (STEPPER) -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        {{ t('pages.instructors.dossierSections') }}
                    </div>

                    <div class="p-4 flex-1 space-y-2">
                        <button
                            v-for="item in editorMenu" :key="item.id"
                            @click="activeEditorTab = item.id"
                            class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                            :class="[
                                activeEditorTab === item.id
                                    ? 'bg-blue-50 border border-blue-100 shadow-sm'
                                    : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'
                            ]"
                        >
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-600 rounded-r-md"></div>

                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white">
                                    <i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px] bg-white rounded-full"></i>
                                    <i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px] bg-white rounded-full"></i>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-blue-800' : 'text-slate-700']">{{ item.label }}</div>
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
                                <div class="text-xs font-bold text-slate-800">{{ isEntireFormValid ? t('pages.instructors.dossierCompliant') : t('pages.instructors.requiredData') }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                    {{ isEntireFormValid ? t('pages.instructors.allRequiredFilled') : t('pages.instructors.checkWarnedSections') }}
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
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.instructors.civilStatusTitle') }}</h3>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.instructors.civilStatusSubtitle') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.expertFullName') }} <span class="text-red-500">*</span></label>
                                        <InputText v-model="instructorForm.name" class="w-full p-4 text-lg font-bold text-slate-800 bg-slate-50 focus:bg-white transition-colors" :placeholder="t('pages.instructors.expertFullNamePlaceholder')" :class="{'p-invalid': instructorForm.errors?.name || (!instructorForm.name && instructorForm.isDirty)}" />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.professionalEmail') }} <span class="text-red-500">*</span></label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-envelope text-slate-400" />
                                            <InputText v-model="instructorForm.email" type="email" class="w-full bg-slate-50 focus:bg-white" placeholder="alphonse.k@subnet.cd" :class="{'p-invalid': instructorForm.errors?.email}" />
                                        </IconField>
                                        <small class="text-slate-500 text-[11px]">{{ t('pages.instructors.trainerLoginHint') }}</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.mobilePhone') }}</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-phone text-slate-400" />
                                            <InputText v-model="instructorForm.numero" class="w-full bg-slate-50 focus:bg-white" placeholder="+243 ..." />
                                        </IconField>
                                    </div>

                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.addressOffice') }}</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-map-marker text-slate-400" />
                                            <InputText v-model="instructorForm.address" class="w-full bg-slate-50 focus:bg-white" :placeholder="t('pages.instructors.addressPlaceholder')" />
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
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.instructors.academicProfileTitle') }}</h3>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.instructors.academicProfileSubtitle') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.specialtiesLabel') }} <span class="text-red-500">*</span></label>
                                        <MultiSelect v-model="instructorForm.specialties" :options="props.specialtiesList" optionLabel="label" optionValue="value" :placeholder="t('pages.instructors.selectKeySkills')" display="chip" class="w-full bg-slate-50" :class="{'p-invalid': !instructorForm.specialties.length}" filter />
                                        <small class="text-slate-500 text-[11px]">{{ t('pages.instructors.classifyInstructorHint') }}</small>
                                    </div>

                                    <div class="space-y-2 mt-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.bioPresentationLabel') }}</label>
                                        <Textarea v-model="instructorForm.bio" rows="6" class="w-full p-4 bg-slate-50 border-slate-300 focus:bg-white leading-relaxed" :placeholder="t('pages.instructors.bioPlaceholder')" />
                                    </div>

                                    <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 space-y-4 mt-2">
                                        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2"><i class="pi pi-link text-slate-400"></i> {{ t('pages.instructors.digitalPresence') }}</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1">
                                                <label class="text-[11px] font-bold text-slate-500 uppercase">{{ t('pages.instructors.linkedinProfile') }}</label>
                                                <IconField iconPosition="left">
                                                    <InputIcon class="pi pi-linkedin text-blue-500" />
                                                    <InputText v-model="instructorForm.social.linkedin" placeholder="https://linkedin.com/in/..." class="w-full bg-white" />
                                                </IconField>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[11px] font-bold text-slate-500 uppercase">{{ t('pages.instructors.websitePortfolio') }}</label>
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
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">{{ t('pages.instructors.officialProfilePhoto') }}</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                                    <div class="flex flex-col items-center justify-center p-8 bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl relative group hover:bg-blue-50/50 hover:border-blue-300 transition-colors">

                                        <Avatar v-if="instructorForm.avatar" :image="typeof instructorForm.avatar === 'string' ? instructorForm.avatar : URL.createObjectURL(instructorForm.avatar)" size="xlarge" shape="circle" class="w-40 h-40 shadow-2xl ring-4 ring-white mb-6 object-cover" />
                                        <div v-else class="w-40 h-40 rounded-full bg-white flex items-center justify-center shadow-inner border border-slate-200 mb-6 text-slate-300">
                                            <i class="pi pi-camera text-6xl"></i>
                                        </div>

                                        <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" customUpload @select="onAvatarSelect" :chooseLabel="t('pages.instructors.selectProPhoto')" class="p-button-outlined p-button-sm font-bold rounded-full px-6 bg-white" auto />

                                        <ProgressBar v-if="uploadMockProgress > 0 && uploadMockProgress < 100" :value="uploadMockProgress" class="absolute bottom-0 left-4 right-4 h-1 rounded-t-none" :showValue="false" />
                                    </div>

                                    <div class="space-y-4">
                                        <h4 class="font-bold text-slate-800 text-sm">{{ t('pages.instructors.subnetcongoQualityStandards') }}</h4>
                                        <p class="text-sm text-slate-500 leading-relaxed mb-4">{{ t('pages.instructors.photoTrustParagraphPrefix') }} <strong>{{ t('pages.instructors.photoTrustParagraphBold') }}</strong>.</p>
                                        <ul class="space-y-3 text-sm text-slate-600 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> {{ t('pages.instructors.centeredPortrait') }}</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>{{ t('pages.instructors.recommendedFormat') }} :</strong> {{ t('pages.instructors.recommendedFormatDetail') }}</li>
                                            <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> {{ t('pages.instructors.weightBelow2mb') }}</li>
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
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.instructors.economicModelTitle') }}</h3>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.instructors.economicModelSubtitle') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-8">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.contractStatusLabel') }} <span class="text-red-500">*</span></label>
                                        <Dropdown v-model="instructorForm.status" :options="props.statuses" class="w-full md:w-1/2 font-medium bg-slate-50">
                                            <template #value="slotProps">
                                                <div class="flex items-center gap-2">
                                                    <i :class="getStatusIcon(slotProps.value)" class="text-slate-500"></i>
                                                    <span>{{ slotProps.value }}</span>
                                                </div>
                                            </template>
                                        </Dropdown>
                                        <small class="text-slate-500 text-[11px] block mt-1">{{ t('pages.instructors.activePaymentHint') }}</small>
                                    </div>

                                    <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 space-y-6 mt-4">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700">{{ t('pages.instructors.commissionRateLabel') }}</label>
                                                <p class="text-[11px] text-slate-500">{{ t('pages.instructors.commissionRateHelp') }}</p>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <span class="font-black text-emerald-600 text-2xl">{{ instructorForm.commission_rate }} %</span>
                                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400">{{ t('pages.instructors.platformPercent', { percent: 100 - instructorForm.commission_rate }) }}</span>
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
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-shield text-slate-400"></i> {{ t('pages.instructors.trainerPanelAccess') }}</h3>

                                <div class="space-y-4">
                                    <!-- Blocage Compte -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-red-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-red-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-lock"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">{{ t('pages.instructors.banSuspendAccess') }}</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">{{ t('pages.instructors.banSuspendAccessHelp') }}</div>
                                            </div>
                                        </div>
                                        <ToggleSwitch v-model="instructorForm.is_suspended" />
                                    </div>

                                    <!-- Reset Password -->
                                    <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-amber-200 transition-colors">
                                        <div class="flex gap-4">
                                            <div class="w-10 h-10 bg-white border border-slate-200 text-amber-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-key"></i></div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm">{{ t('pages.instructors.forcePasswordReset') }}</div>
                                                <div class="text-xs text-slate-500 mt-1 leading-tight max-w-md">{{ t('pages.instructors.forcePasswordResetHelp') }}</div>
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
                    <span v-if="!isEntireFormValid" class="text-amber-600 font-bold flex items-center gap-2"><i class="pi pi-exclamation-triangle"></i> {{ t('pages.instructors.completeAllSections') }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <Button :label="t('pages.instructors.close')" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-50" @click="instructorDialog = false" />

                    <div class="relative group">
                        <Button :label="t('pages.instructors.saveProfile')" icon="pi pi-check-circle" class="p-button-primary shadow-lg shadow-blue-500/30 font-bold px-8 text-sm" @click="saveInstructor" :loading="instructorForm.processing" :disabled="!isEntireFormValid" />
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
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                        <i class="pi pi-id-card text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="font-black text-xl text-slate-800 leading-tight">{{ t('pages.instructors.expertProfileTitle') }}</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ t('pages.instructors.hrDossierSubtitle') }}</p>
                    </div>
                </div>
            </template>

            <div v-if="currentInstructor" class="flex flex-col h-full relative">
                <!-- Hero Header -->
                <div class="bg-gradient-to-br from-blue-700 via-blue-600 to-blue-700 p-8 text-white rounded-b-[2rem] shadow-lg relative overflow-hidden flex-shrink-0">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-black/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex flex-col items-center text-center">
                        <Avatar v-if="currentInstructor.avatar_path" :image="currentInstructor.avatar_path" size="xlarge" shape="circle" class="ring-4 ring-white/30 mb-4 w-28 h-28 shadow-2xl bg-white object-cover" />
                        <Avatar v-else :label="currentInstructor.name?.charAt(0).toUpperCase()" size="xlarge" shape="circle" class="ring-4 ring-white/30 mb-4 w-28 h-28 shadow-2xl bg-white text-blue-600 font-black text-4xl" />

                        <h2 class="text-2xl font-black mb-1">{{ currentInstructor.name }}</h2>
                        <div class="flex items-center justify-center gap-2 mb-4">
                            <Tag :value="currentInstructor.status" class="bg-white/20 text-white border border-white/30 px-3 py-1 text-xs" />
                            <span class="text-blue-200 text-xs font-mono bg-black/20 px-2 py-1 rounded">{{ t('pages.instructors.commissionBadge', { rate: currentInstructor.commission_rate || 70 }) }}</span>
                        </div>

                        <div class="grid grid-cols-3 w-full mt-4 gap-3">
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-white/10 shadow-inner">
                                <p class="text-[9px] text-blue-200 uppercase font-bold tracking-widest mb-1">{{ t('pages.instructors.salesLearners') }}</p>
                                <p class="text-xl font-black">{{ currentInstructor.total_students || 0 }}</p>
                            </div>
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-white/10 shadow-inner">
                                <p class="text-[9px] text-blue-200 uppercase font-bold tracking-widest mb-1">{{ t('pages.instructors.activeCourses') }}</p>
                                <p class="text-xl font-black">{{ currentInstructor.assigned_courses_count || 0 }}</p>
                            </div>
                            <div class="bg-black/10 backdrop-blur-sm rounded-2xl p-3 border border-emerald-500/30 shadow-inner text-emerald-300">
                                <p class="text-[9px] text-emerald-200 uppercase font-bold tracking-widest mb-1">{{ t('pages.instructors.earnings') }}</p>
                                <p class="text-xl font-black">{{ formatCurrency(currentInstructor.generated_revenue || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Content -->
                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <TabView class="custom-tabview">

                        <!-- TAB: APERÇU -->
                        <TabPanel :header="t('pages.instructors.tabIdentityBio')">
                            <div class="space-y-6 pt-4">
                                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-map-marker"></i> {{ t('pages.instructors.contactAndWeb') }}</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-4 text-slate-600">
                                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-400"><i class="pi pi-envelope"></i></div>
                                            <span class="text-sm font-medium">{{ currentInstructor.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-4 text-slate-600">
                                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100 text-slate-400"><i class="pi pi-phone"></i></div>
                                            <span class="text-sm font-medium">{{ currentInstructor.numero || t('pages.instructors.notProvided') }}</span>
                                        </div>

                                        <Divider />

                                        <div class="flex gap-2" v-if="parseJSON(currentInstructor.social).linkedin || parseJSON(currentInstructor.social).website">
                                            <a v-if="parseJSON(currentInstructor.social).linkedin" :href="parseJSON(currentInstructor.social).linkedin" target="_blank" class="w-10 h-10 rounded-full bg-[#0077b5]/10 text-[#0077b5] flex items-center justify-center hover:bg-[#0077b5] hover:text-white transition-colors">
                                                <i class="pi pi-linkedin"></i>
                                            </a>
                                            <a v-if="parseJSON(currentInstructor.social).website" :href="parseJSON(currentInstructor.social).website" target="_blank" class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                                                <i class="pi pi-globe"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-tags"></i> {{ t('pages.instructors.specialties') }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <Tag v-for="spec in parseJSON(currentInstructor.specialties)" :key="spec" :value="spec.name || spec" class="bg-blue-50 text-blue-700 border border-blue-100 font-bold px-3 py-1" />
                                        <span v-if="!parseJSON(currentInstructor.specialties).length" class="text-sm text-slate-400 italic">{{ t('pages.instructors.noSpecialtyProvided') }}</span>
                                    </div>
                                </div>

                                <div v-if="currentInstructor.bio" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">{{ t('pages.instructors.biography') }}</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed italic bg-slate-50 p-4 rounded-xl">"{{ currentInstructor.bio }}"</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB: ANALYSE & FINANCE -->
                        <TabPanel :header="t('pages.instructors.tabFinancialAnalysis')">
                            <div class="pt-4 space-y-6">
                                <!-- Graphe de revenus (Mockup pour illustration UI B2B) -->
                                <div>
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-chart-bar"></i> {{ t('pages.instructors.commissionsEvolution') }}</h4>
                                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                        <div class="flex items-baseline gap-2 mb-4">
                                            <span class="text-2xl font-black text-slate-800">{{ formatCurrency(currentInstructor.generated_revenue || 0) }}</span>
                                            <span class="text-xs text-emerald-500 font-bold bg-emerald-50 px-2 py-1 rounded-md"><i class="pi pi-arrow-up text-[10px]"></i> {{ t('pages.instructors.overallTrend') }}</span>
                                        </div>
                                        <Chart type="line" :data="revenueChartData" :options="chartOptions" class="h-48 w-full" />
                                    </div>
                                </div>

                                <!-- Avis Global -->
                                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.instructors.pedagogicalQuality') }}</h4>
                                        <p class="text-sm text-slate-600">{{ t('pages.instructors.averageRatingHint') }}</p>
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
                    <Button :label="t('pages.instructors.close')" class="p-button-text p-button-secondary font-bold" @click="profileSidebar = false" />
                    <Button :label="t('pages.instructors.editProfile')" icon="pi pi-pencil" class="p-button-primary px-8 font-black shadow-lg shadow-blue-500/30 rounded-xl" @click="editInstructor(currentInstructor); profileSidebar = false" />
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
