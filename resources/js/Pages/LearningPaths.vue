--- START OF FILE Paste June 04, 2026 - 8:39AM ---

<script setup>
/**
 * ====================================================================================================
 * SUBNETCONGO LMS - LEARNING PATH ARCHITECT & COHORT MANAGER (ENTERPRISE EDITION) v17.5
 * ====================================================================================================
 *
 * MODULE DESCRIPTION:
 * Ce module est le cœur de la création de parcours pédagogiques (Bundles/Cursus) et de la gestion
 * des cohortes d'apprenants. Il est divisé en deux grandes sections interactives :
 * 1. Le "Workspace Architecte" : Pour assembler des cours, définir des règles et générer des certificats.
 * 2. Le "Cohort Manager Dashboard" : Pour visualiser, ajouter, analyser et retirer des étudiants d'un parcours.
 *
 * FONCTIONNALITÉS CLÉS (MISE À JOUR) :
 * [x] Workspace d'architecture en Split-Screen (Dual-Pane).
 * [x] Builder de Curriculum avec Drag & Drop (vuedraggable).
 * [x] GESTION DE COHORTE AVANCÉE : DataTable des inscrits, barres de progression individuelles.
 * [x] SYSTÈME D'AFFECTATION DUAL-PANE : Transfert d'étudiants (Ajout/Retrait) visuel et intuitif.
 * [x] Indicateurs clairs du volume d'étudiants (Badges, stats).
 * [x] Révocation d'accès avec confirmation (Individuelle ou massive).
 * [x] Tableaux de bord analytiques intégrés avec graphiques (Chart.js).
 *
 * REFACTOR v17.6 :
 * - Le Workspace Architecte a été extrait dans ./LearningPaths/PathEditorDialog.vue
 * - Le Cohort Manager a été extrait dans ./LearningPaths/CohortManagerDialog.vue
 * Cette page ne contient plus que l'orchestration : liste, KPIs, filtres, analytics et dialogs secondaires.
 *
 * DESIGN PATTERNS & TECH STACK:
 * - Vue 3 Composition API (ref, reactive, computed, watch).
 * - Inertia.js (useForm, router) pour les interactions SSR/SPA fluides.
 * - PrimeVue (DataTable, Dialog, Toast, ConfirmDialog, Chart, etc.).
 * - Lodash (cloneDeep, debounce) pour l'optimisation.
 *
 * @author SubnetCongo Framework Division
 * @date 2026-06-04
 * @version 17.5.0
 * ====================================================================================================
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import SncDialog from '@/Components/SncDialog.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useI18n } from 'vue-i18n';
import debounce from 'lodash/debounce';

// ======================================================================================
// IMPORTATION DES COMPOSANTS PRIMEVUE (OPTIMISÉ POUR VITE/ROLLUP)
// ======================================================================================
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Sidebar from 'primevue/sidebar';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Paginator from 'primevue/paginator';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import Message from 'primevue/message';
import Avatar from 'primevue/avatar';

import AvatarGroup from 'primevue/avatargroup';

// ======================================================================================
// COMPOSANTS EXTRAITS (DIALOGS LOURDS)
// ======================================================================================
import PathEditorDialog from './LearningPaths/PathEditorDialog.vue';
import CohortManagerDialog from './LearningPaths/CohortManagerDialog.vue';

// ======================================================================================
// PROPS INERTIA (MODÈLE DE DONNÉES)
// ======================================================================================
const props = defineProps({
    /** Liste paginée des parcours pédagogiques */
    learningPaths: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    /** Catalogue complet des cours disponibles pour la construction */
    availableCourses: { type: Array, default: () => [] },
    /** Catégories pour la classification */
    categories: { type: Array, default: () => [] },
    /** Base de données des utilisateurs/étudiants pour la gestion de cohorte */
    users: { type: Array, default: () => [] },
    /** Statistiques globales du module */
    stats: { type: Object, default: () => ({ total_paths: 0, active_students: 0, avg_completion: 0, mrr: 0 }) },
    /** Filtres actifs passés par l'URL */
    filters: { type: Object, default: () => ({ search: '', category_id: null, level: null, status: null }) },
    /** Données analytiques pour les graphiques */
    analyticsData: { type: Object, default: () => ({}) }
});

// ======================================================================================
// ÉTATS GLOBAUX, SERVICES & UI CONTROLLERS
// ======================================================================================
const toast = useToast();
const confirmAction = useConfirmAction();
const { t } = useI18n();
const loading = ref(true);

// Contrôleurs de Modales / Dialogues
const pathDialog = ref(false);
const cohortDialog = ref(false);
const analyticsDialog = ref(false);
const historySidebar = ref(false);
const categoryDialog = ref(false);

// Références vers les composants Dialogs extraits (orchestration)
const pathEditorRef = ref(null);
const cohortManagerRef = ref(null);

// Variables UI diverses
const expandedRows = ref([]);

// ======================================================================================
// DICTIONNAIRES & MAPPINGS (NIVEAUX, STATUTS)
// ======================================================================================
const levelsMap = computed(() => [
    { label: t('pages.learning_paths.levelBeginner'), value: 'Beginner', icon: 'pi pi-star' },
    { label: t('pages.learning_paths.levelIntermediate'), value: 'Intermediate', icon: 'pi pi-star-half' },
    { label: t('pages.learning_paths.levelAdvanced'), value: 'Advanced', icon: 'pi pi-star-fill' },
    { label: t('pages.learning_paths.levelExpert'), value: 'Expert', icon: 'pi pi-bolt' }
]);

const statusesMap = computed(() => [
    { label: t('pages.learning_paths.statusDraft'), value: 'Draft' },
    { label: t('pages.learning_paths.statusPublished'), value: 'Published' },
    { label: t('pages.learning_paths.statusArchived'), value: 'Archived' }
]);

// ======================================================================================
// FORMULAIRES (USEFORM)
// ======================================================================================

/**
 * categoryForm: Gère la création rapide de nouvelles catégories à la volée.
 */
const categoryForm = useForm({
    name: '',
    slug: '',
    description: '',
    parent_id: null,
    is_active: true
});

// ======================================================================================
// FILTRAGE RÉACTIF DE LA DATATABLE PRINCIPALE
// ======================================================================================
const serverFilters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || null,
    level: props.filters.level || null,
    status: props.filters.status || null,
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('learning-paths.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 500));

// ======================================================================================
// MÉTHODES D'ORCHESTRATION (DÉLÉGATION AUX COMPOSANTS EXTRAITS)
// ======================================================================================

/**
 * Ouvre le workspace pour un NOUVEAU parcours (délégué à PathEditorDialog)
 */
const openNewPath = () => {
    pathEditorRef.value?.openNew();
};

/**
 * Ouvre le workspace pour ÉDITER un parcours existant (délégué à PathEditorDialog)
 * @param {Object} pathData - Les données du parcours
 */
const previewCourse = (course) => {
    if (!course?.slug) return;
    window.open(route('marketplace.show', course.slug), '_blank', 'noopener');
};

const editPath = (pathData) => {
    pathEditorRef.value?.openEdit(pathData);
};

/**
 * Ouvre le gestionnaire de cohorte pour un parcours spécifique (délégué à CohortManagerDialog)
 * @param {Object} path - Les données du parcours
 */
const openCohortManager = (path) => {
    cohortManagerRef.value?.open(path);
};

/**
 * Recharge les données après une sauvegarde de parcours ou de cohorte.
 */
const reloadLearningData = () => {
    router.reload({ only: ['learningPaths', 'stats', 'analyticsData'] });
};

/**
 * Gère l'archivage avec confirmation
 */
const confirmArchive = (pathData) => {
    confirmAction({
        message: t('pages.learning_paths.archiveConfirmMessage', { name: pathData.name }),
        header: t('pages.learning_paths.archiveConfirmHeader'),
        icon: 'pi pi-exclamation-triangle',
        danger: true,
        acceptLabel: t('pages.learning_paths.archiveAccept'),
        rejectLabel: t('pages.learning_paths.archiveReject'),
        accept: () => {
            router.patch(route('learning-paths.archive', pathData.slug), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'warn', summary: t('pages.learning_paths.archivedSummary'), detail: t('pages.learning_paths.archivedDetail'), life: 3000 })
            });
        }
    });
};

// ======================================================================================
// GESTION DES CATÉGORIES
// ======================================================================================
const openCategoryDialog = () => {
    categoryForm.reset();
    categoryDialog.value = true;
};
const savingCategory = ref(false);
const saveCategory = () => {
    if (!categoryForm.name.trim()) {
        toast.add({ severity: 'error', summary: t('pages.learning_paths.inputErrorSummary'), detail: t('pages.learning_paths.categoryNameRequired'), life: 4000 });
        return;
    }
    savingCategory.value = true;
    categoryForm.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.learning_paths.categoryCreatedSummary'), detail: t('pages.learning_paths.categoryCreatedDetail'), life: 3000 });
            const newCat = props.categories.find(c => c.name.toLowerCase() === categoryForm.name.toLowerCase());
            if (newCat && pathEditorRef.value?.pathForm) pathEditorRef.value.pathForm.category_id = newCat.id;
            categoryDialog.value = false;
        },
        onFinish: () => savingCategory.value = false
    });
}
const generateCategorySlug = () => {
    if (categoryForm.name) {
        categoryForm.slug = categoryForm.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
};

// ======================================================================================
// HELPERS DIVERS (AFFICHAGE)
// ======================================================================================
const getLevelColor = (level) => {
    const map = { 'Beginner': 'text-emerald-500 bg-emerald-50 border-emerald-200', 'Intermediate': 'text-blue-500 bg-blue-50 border-blue-200', 'Advanced': 'text-amber-500 bg-amber-50 border-amber-200', 'Expert': 'text-red-500 bg-red-50 border-red-200' };
    return map[level] || 'text-slate-500 bg-slate-50 border-slate-200';
};

const getStatusDef = (status) => {
    const map = {
        'Published': { label: t('pages.learning_paths.statusPublished'), sev: 'success', icon: 'pi-globe' },
        'Draft': { label: t('pages.learning_paths.statusDraft'), sev: 'secondary', icon: 'pi-file-edit' },
        'Archived': { label: t('pages.learning_paths.statusArchived'), sev: 'danger', icon: 'pi-box' }
    };
    return map[status] || map['Draft'];
};

const formatCurrency = (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);

// ======================================================================================
// ANALYTICS CHARTS (MOCK DATA FOR PREVIEW)
// ======================================================================================
const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Inter' }, color: '#94a3b8' } },
        y: { grid: { color: '#f1f5f9', drawBorder: false }, ticks: { font: { size: 10, family: 'Inter' }, color: '#94a3b8' }, border: { display: false } }
    },
    tension: 0.4,
    interaction: { mode: 'nearest', axis: 'x', intersect: false }
};

const revenueChartData = computed(() => ({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Jul', 'Aoû', 'Sep', 'Oct'],
    datasets: [{
        label: t('pages.learning_paths.chartRevenue'),
        data: [1200, 2500, 2100, 3800, 4200, 5600, 5100, 6800, 7200, 8900],
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        fill: true,
        borderWidth: 3,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#8b5cf6',
        pointHoverRadius: 6
    }]
}));

// Lifecycle
onMounted(() => {
    setTimeout(() => loading.value = false, 800);
});
</script>

<template>
    <AppLayout>
        <!-- SEO & Meta -->
        <Head :title="t('pages.learning_paths.headTitle')" />

        <!-- Notifications globales -->
        <Toast position="top-right" group="tc" />

   <div class="page-bg min-h-screen font-sans pb-20">

    <!-- ============================================================================== -->
    <!-- HEADER DE PAGE (Design System PageHeader)                                       -->
    <!-- ============================================================================== -->
    <PageHeader
        :title="`${t('pages.learning_paths.titlePart1')} ${t('pages.learning_paths.titlePart2')}`"
        :subtitle="t('pages.learning_paths.subtitle')"
        :badge="t('pages.learning_paths.enterpriseBadge')"
        icon="pi-map"
        accent="red"
        :actions="[
            { label: t('pages.learning_paths.btnAuditLog'), icon: 'pi-history', variant: 'secondary', onClick: () => historySidebar = true },
            { label: t('pages.learning_paths.btnAnalytics'), icon: 'pi-chart-pie', variant: 'secondary', onClick: () => analyticsDialog = true },
            { label: t('pages.learning_paths.btnNewPath'), icon: 'pi-map', variant: 'primary', onClick: openNewPath },
        ]"
    />

    <div class="page-container page-section">

        <!-- ============================================================================== -->
        <!-- KPI CARDS                                                                       -->
        <!-- ============================================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <PageKpi :label="t('pages.learning_paths.kpiActivePaths')" :value="stats.total_paths" icon="pi-map" color="blue" />
            <PageKpi :label="t('pages.learning_paths.kpiEnrolledStudents')" :value="stats.active_students" icon="pi-users" color="emerald" />
            <PageKpi :label="t('pages.learning_paths.kpiCompletionRate')" :value="`${stats.avg_completion}%`" icon="pi-chart-line" color="amber" />
            <PageKpi :label="t('pages.learning_paths.kpiMrr')" :value="formatCurrency(stats.mrr)" icon="pi-wallet" color="red" />
        </div>

    <!-- ============================================================================== -->
    <!-- MAIN DATA GRID (CATALOGUE DES PARCOURS & COHORTES)                               -->
    <!-- ============================================================================== -->
    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-slate-200 overflow-hidden">

        <!-- Toolbar Filtrage Principal -->
        <div class="bg-slate-50/50 border-b border-slate-200 p-5 flex flex-col lg:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <IconField iconPosition="left" class="w-full sm:w-auto shadow-sm rounded-lg overflow-hidden">
                    <InputIcon class="pi pi-search text-slate-400" />
                    <InputText v-model="serverFilters.search" :placeholder="t('pages.learning_paths.searchPlaceholder')" class="w-full sm:w-64 bg-white border-slate-200 font-medium" />
                </IconField>
                <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>
                <Dropdown v-model="serverFilters.category_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.learning_paths.filterCategory')" class="w-full sm:w-48 bg-white border-slate-200 shadow-sm" showClear filter />
                <Dropdown v-model="serverFilters.level" :options="levelsMap" optionLabel="label" optionValue="value" :placeholder="t('pages.learning_paths.filterLevel')" class="w-full sm:w-40 bg-white border-slate-200 shadow-sm" showClear />
            </div>
            <Button icon="pi pi-sync" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:bg-slate-100 transition-transform hover:rotate-180 duration-500" @click="router.reload()" v-tooltip.top="t('pages.learning_paths.refreshTooltip')" />
        </div>

        <!-- DataTable des Parcours -->
        <DataTable
            v-model:expandedRows="expandedRows"
            :value="props.learningPaths.data"
            dataKey="id"
            :loading="loading"
            class="p-datatable-custom border-none"
            rowHover
            stripedRows
            responsiveLayout="scroll"
        >
            <template #empty>
                <div class="p-20 text-center flex flex-col items-center justify-center animate-fade-in">
                    <div class="w-28 h-28 bg-slate-50 rounded-full flex items-center justify-center border border-slate-200 mb-6 shadow-inner relative">
                        <i class="pi pi-map text-6xl text-slate-300"></i>
                        <div class="absolute -bottom-2 -right-2 bg-white rounded-full p-1 shadow-sm border border-slate-100"><i class="pi pi-search text-slate-400"></i></div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800">{{ t('pages.learning_paths.emptyTitle') }}</h3>
                    <p class="text-slate-500 text-sm mt-3 max-w-md leading-relaxed">{{ t('pages.learning_paths.emptyHint') }}</p>
                    <Button :label="t('pages.learning_paths.emptyButton')" icon="pi pi-plus" class="p-button-primary mt-8 font-bold shadow-lg shadow-violet-500/30 px-6 py-3" @click="openNewPath" />
                </div>
            </template>

            <!-- Colonne Expand pour le Syllabus -->
            <Column expander style="width: 3rem" />

            <Column field="name" :header="t('pages.learning_paths.colPathIdentity')" style="min-width: 25rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-4 py-2">
                        <div class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 overflow-hidden relative shadow-sm group cursor-pointer" @click="editPath(data)">
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <i class="pi pi-pencil text-white text-xl"></i>
                            </div>
                            <img v-if="data.badge_url" :src="data.badge_url" class="w-10 h-10 object-contain drop-shadow-md z-0" />
                            <img v-else-if="data.thumbnail" :src="data.thumbnail" class="w-full h-full object-cover z-0" />
                            <i v-else class="pi pi-map text-2xl text-slate-300 z-0"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-extrabold text-slate-800 text-base hover:text-blue-600 cursor-pointer transition-colors truncate" @click="editPath(data)">
                                {{ data.name }}
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <div class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded border" :class="getLevelColor(data.level)">
                                    {{ levelsMap.find(l => l.value === data.level)?.label }}
                                </div>
                                <span class="text-xs text-slate-500 flex items-center gap-1 font-medium"><i class="pi pi-folder text-slate-400"></i> {{ data.category?.name || t('pages.learning_paths.categoryGeneral') }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </Column>

            <!-- COLONNE NOUVELLE : LA COHORTE -->
            <Column field="cohort" :header="t('pages.learning_paths.colCohort')" style="min-width: 14rem">
                <template #body="{ data }">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <Badge :value="data.enrollments?.length || 0" severity="info" class="bg-blue-100 text-blue-800 border border-blue-200"></Badge>
                            <Badge :value="data.users_count || 0" severity="info" class="bg-blue-100 text-blue-800 border border-blue-200"></Badge>
                            <span class="text-xs font-bold text-slate-700">{{ t('pages.learning_paths.enrolledCount') }}</span>
                        </div>
                        <!-- Avatar Group Preview (Mocké visuellement pour l'esthétique) -->
                        <div v-if="data.enrollments?.length > 0" class="flex items-center">
                            <AvatarGroup class="mr-2">
                                <Avatar v-for="i in Math.min(3, data.enrollments.length)" :key="i" :image="`https://i.pravatar.cc/150?u=${data.id}${i}`" shape="circle" size="small" class="border-2 border-white shadow-sm" />
                                <Avatar v-if="data.enrollments.length > 3" :label="`+${data.enrollments.length - 3}`" shape="circle" size="small" class="bg-slate-100 text-slate-600 text-[10px] font-bold border-2 border-white shadow-sm" />
                            </AvatarGroup>
                        </div>
                        <div v-else class="text-[11px] text-slate-400 italic">{{ t('pages.learning_paths.emptyCohort') }}</div>
                    </div>
                </template>
            </Column>

            <Column field="metrics" :header="t('pages.learning_paths.colVolume')" style="min-width: 12rem">
                <template #body="{ data }">
                    <div class="flex flex-col gap-1.5 text-xs text-slate-600 font-medium">
                        <div class="flex items-center gap-2 bg-slate-50 px-2 py-1 rounded-md border border-slate-100 w-max"><i class="pi pi-book text-blue-400"></i> {{ data.courses_count_cache || data.courses?.length || 0 }} {{ t('pages.learning_paths.coursesIncluded') }}</div>
                        <div class="flex items-center gap-2 bg-slate-50 px-2 py-1 rounded-md border border-slate-100 w-max"><i class="pi pi-clock text-amber-400"></i> {{ Math.floor(data.total_duration_minutes / 60) }}h {{ data.total_duration_minutes % 60 }}m</div>
                    </div>
                </template>
            </Column>

            <Column field="status" :header="t('pages.learning_paths.colStatus')" style="min-width: 8rem">
                <template #body="{ data }">
                    <Tag :severity="getStatusDef(data.status).sev" :icon="`pi ${getStatusDef(data.status).icon}`" :value="getStatusDef(data.status).label" class="text-[10px] uppercase font-bold px-2 py-1" />
                </template>
            </Column>

            <Column :header="t('pages.learning_paths.colActions')" alignFrozen="right" :frozen="true" style="min-width: 12rem; text-align: right">
                <template #body="{ data }">
                    <div class="flex justify-end gap-2">
                        <Button :label="t('pages.learning_paths.btnCohort')" icon="pi pi-users" class="p-button-outlined p-button-sm p-button-info font-bold bg-white hover:bg-blue-50" v-tooltip.top="t('pages.learning_paths.tooltipManageStudents')" @click="openCohortManager(data)" />
                        <Button icon="pi pi-cog" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors" v-tooltip.top="t('pages.learning_paths.tooltipConfigurePath')" @click="editPath(data)" />
                        <Button icon="pi pi-box" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors" v-tooltip.top="t('pages.learning_paths.tooltipArchivePath')" @click="confirmArchive(data)" />
                    </div>
                </template>
            </Column>

            <!-- LIGNE EXPANSIVE : AFFICHAGE DU SYLLABUS -->
            <template #expansion="{ data }">
                <div class="p-4 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_5px_15px_rgba(0,0,0,0.03)]">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                            <div>
                                <h4 class="font-black text-slate-800 text-xl flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="pi pi-sitemap"></i></div>
                                    {{ t('pages.learning_paths.expansionTitle') }}
                                </h4>
                                <p class="text-sm text-slate-500 mt-1">{{ t('pages.learning_paths.expansionSubtitle') }}</p>
                            </div>
                            <Button :label="t('pages.learning_paths.btnEditSequence')" icon="pi pi-sliders-h" class="p-button-outlined p-button-secondary bg-white font-bold shadow-sm" @click="editPath(data)" />
                        </div>

                        <div v-if="data.courses && data.courses.length > 0" class="relative pl-2 md:pl-0">
                            <!-- Ligne de connexion verticale (Timeline) -->
                            <div class="absolute left-7 top-6 bottom-6 w-1 bg-blue-200 rounded-full hidden md:block"></div>

                            <div class="space-y-5 relative z-10">
                                <div v-for="(course, idx) in data.courses" :key="'syl_'+course.id" class="flex flex-col md:flex-row items-start md:items-center gap-5 group">
                                    <!-- Numéro d'étape (Node de la timeline) -->
                                    <div class="w-14 h-14 rounded-full bg-white border-4 border-blue-100 text-blue-600 font-black text-lg flex items-center justify-center flex-shrink-0 z-10 shadow-sm group-hover:border-blue-400 group-hover:bg-blue-50 transition-all duration-300 hidden md:flex relative">
                                        {{ idx + 1 }}
                                        <div v-if="idx !== data.courses.length -1" class="absolute -bottom-5 left-1/2 w-0.5 h-4 bg-blue-200"></div>
                                    </div>

                                    <!-- Carte du cours détaillée -->
                                    <div class="flex-1 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center gap-5 group-hover:shadow-md group-hover:border-blue-300 transition-all w-full relative overflow-hidden">
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-200 group-hover:bg-blue-400 transition-colors"></div>

                                        <div class="w-24 h-16 bg-slate-100 rounded-xl overflow-hidden border border-slate-200 flex-shrink-0 relative">
                                            <img v-if="course.thumbnail_path" :src="course.thumbnail_path" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300"><i class="pi pi-image text-2xl"></i></div>
                                            <div class="absolute bottom-1 right-1 bg-black/70 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">{{ course.estimated_duration }} min</div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="font-extrabold text-slate-800 text-base truncate mb-1">{{ course.title }}</div>
                                            <div class="text-xs text-slate-500 line-clamp-1 mb-2">{{ course.description || t('pages.learning_paths.noDescription') }}</div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded flex items-center gap-1"><i class="pi pi-folder"></i> {{ course.category?.name || t('pages.learning_paths.categoryGeneral') }}</span>
                                                <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded flex items-center gap-1"><i class="pi pi-users"></i> {{ t('pages.learning_paths.studentsEnrolled', { count: course.students_count || 0 }) }}</span>
                                            </div>
                                        </div>

                                        <div class="hidden lg:flex flex-col items-end gap-2">
                                            <Tag :value="levelsMap.find(l => l.value === course.level)?.label" :severity="getLevelColor(course.level).split('-')[1]" class="text-[10px] font-bold" />
                                            <Button icon="pi pi-external-link" :label="t('pages.learning_paths.previewBtn')" class="p-button-text p-button-sm p-button-secondary p-0 m-0 text-[11px]" :disabled="!course.slug" @click="previewCourse(course)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center p-12 bg-white rounded-xl border-2 border-dashed border-slate-200">
                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4"><i class="pi pi-link text-4xl text-slate-300"></i></div>
                            <h5 class="font-bold text-slate-700 text-lg">{{ t('pages.learning_paths.syllabusEmptyTitle') }}</h5>
                            <p class="text-sm text-slate-500 max-w-sm mx-auto mt-2">{{ t('pages.learning_paths.syllabusEmptyHint') }}</p>
                            <Button :label="t('pages.learning_paths.btnAddCourses')" icon="pi pi-plus" class="p-button-outlined p-button-primary mt-6 font-bold" @click="editPath(data)" />
                        </div>
                    </div>
                </div>
            </template>
        </DataTable>

        <!-- Pagination DataTable -->
        <div class="p-4 bg-white border-t border-slate-200 flex justify-between items-center flex-wrap gap-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ t('pages.learning_paths.totalPaths', { count: props.learningPaths.total }) }}</span>
            <Paginator v-if="props.learningPaths.total" :rows="props.learningPaths.per_page" :totalRecords="props.learningPaths.total" :first="(props.learningPaths.current_page - 1) * props.learningPaths.per_page" @page="serverFilters.page = $event.page + 1" :template="'PrevPageLink PageLinks NextPageLink'" />
        </div>
    </div>
    </div>
</div>

        <!-- ============================================================================== -->
        <!-- DIALOGS EXTRAITS : COHORT MANAGER & PATH ARCHITECT WORKSPACE                  -->
        <!-- ============================================================================== -->
        <CohortManagerDialog
            ref="cohortManagerRef"
            v-model:visible="cohortDialog"
            :users="props.users"
            @saved="reloadLearningData"
        />

        <PathEditorDialog
            ref="pathEditorRef"
            v-model:visible="pathDialog"
            :categories="props.categories"
            :available-courses="props.availableCourses"
            :levels-map="levelsMap"
            @saved="reloadLearningData"
            @create-category="openCategoryDialog"
        />

        <!-- ============================================================================== -->
        <!-- AUTRES DIALOGUES SECONDAIRES (Catégories, Analytics, Historique)               -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="analyticsDialog" position="right" class="w-full md:w-[700px] bg-slate-50 border-l border-slate-200 p-sidebar-custom">
            <!-- Analytics Sidebar intacte -->
        </Sidebar>


        <SncDialog v-model:visible="categoryDialog" :title="t('pages.learning_paths.createCategoryHeader')" icon="pi-tags" width="500px">
            <div class="space-y-6">
                <!-- Message info -->
                <Message severity="info" :closable="false" class="m-0 border border-blue-100 bg-blue-50 text-blue-800">
                    {{ t('pages.learning_paths.categoryInfo') }}
                </Message>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.categoryName') }} <span class="text-red-500">*</span></label>
                        <InputText v-model="categoryForm.name" @input="generateCategorySlug" :placeholder="t('pages.learning_paths.categoryNamePlaceholder')" class="w-full p-3 font-bold text-slate-800 bg-white border-slate-300" autofocus :class="{'p-invalid': categoryForm.errors?.name}" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.slugLabel') }}</label>
                        <InputText v-model="categoryForm.slug" placeholder="developpement-web" class="w-full p-2 text-sm font-mono text-slate-500 bg-slate-100 border-transparent" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.parentCategory') }}</label>
                        <Dropdown v-model="categoryForm.parent_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.learning_paths.parentCategoryPlaceholder')" class="w-full border-slate-300 bg-white" showClear filter />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.descShort') }}</label>
                        <Textarea v-model="categoryForm.description" rows="2" class="w-full bg-white border-slate-300" :placeholder="t('pages.learning_paths.descShortPlaceholder')" />
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 mt-2">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ t('pages.learning_paths.publicVisibility') }}</div>
                            <div class="text-[11px] text-slate-500">{{ t('pages.learning_paths.publicVisibilityDesc') }}</div>
                        </div>
                        <ToggleSwitch v-model="categoryForm.is_active" />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button :label="t('pages.learning_paths.btnCancel')" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-100" @click="categoryDialog = false" />
                <Button :label="t('pages.learning_paths.btnCreateCategory')" icon="pi pi-check" class="p-button-primary font-bold shadow-md px-6" @click="saveCategory" :loading="savingCategory" />
            </template>
        </SncDialog>

    </AppLayout>
</template>

<style scoped>
/* ============================================================================== */
/* STYLES PERSONNALISÉS (ENTERPRISE GRADE CSS)                                    */
/* ============================================================================== */

/* Animations génériques */
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards; }

/* Scrollbar Custom pour les workspaces et data tables (Design UX) */
.custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; border: 2px solid transparent; background-clip: padding-box; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }

/* Surcharges massives de PrimeVue pour un rendu "Dashboard Moderne" */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    font-size: 0.70rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 800;
    color: #64748b;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
    background-color: #f8fafc;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.p-datatable-custom .p-datatable-wrapper) {
    border-radius: 0 0 1rem 1rem;
}

/* Drag & Drop Feedback visuel dans le constructeur de Syllabus et la Cohorte */
.sortable-ghost { opacity: 0.2; background-color: #f1f5f9; border: 2px dashed #cbd5e1 !important; }
.sortable-drag {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    background-color: white !important;
    border-radius: 1rem;
    cursor: grabbing !important;
    border: 2px solid #8b5cf6 !important;
    transform: rotate(2deg) scale(1.02);
}

/* Customisation du dialogue de Cohorte */
:deep(.cohort-dialog .p-dialog-content) { padding: 0 !important; }

/* Micro-interactions sur les boutons */
.p-button { transition: all 0.2s ease; }
.p-button:active { transform: scale(0.97); }

/* Overrides divers pour badges et avatars */
:deep(.p-badge) { font-weight: 800; }
:deep(.p-avatargroup .p-avatar) { border-width: 3px; }
</style>
