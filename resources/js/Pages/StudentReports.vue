<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE ANALYTICS: STUDENT INTELLIGENCE HUB
 * ======================================================================================
 * Composant de monitoring des apprenants (Vue 3 / Inertia.js / PrimeVue).
 *
 * RÈGLES DE CONCEPTION STRICTES APPLIQUÉES :
 * - Single Source of Truth : 100% des données proviennent du backend (Controller).
 * - Aucune donnée codée en dur (statistiques, historiques, compétences, statuts).
 * - Affichage conditionnel (v-if) pour prévenir les erreurs si le backend omet des données.
 * - Utilisation de Dialog pour l'aperçu détaillé (remplace Sidebar).
 * - Formatage vertical (Style Guide Vue) pour les composants à multiples attributs.
 *
 * ATTENTES DU CONTROLLER LARAVEL (Inertia Props) :
 * - `students` : LengthAwarePaginator contenant les objets étudiants.
 *   (Chaque étudiant doit inclure : id, name, email, avatar, is_online, avg_score,
 *    submissions_count, last_active_human, ip_address, skills[], recent_activities[])
 * - `filters` : Objet contenant l'état actuel des filtres.
 * - `courses` : Tableau d'objets {id, name} pour le filtre des cours.
 * - `stats` : Objet contenant {avg_score, active_now, total_certifications, at_risk_students}.
 * - `availableStatuses` : Tableau {label, value, severity} pour les filtres.
 *
 * @version 13.0.0 (Refonte Enterprise)
 * @author SubnetCongo Framework Division
 * @license Enterprise Proprietary
 * ======================================================================================
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import debounce from 'lodash/debounce';

// ============================================================================
// IMPORTATIONS PRIMEVUE (CORE COMPONENTS)
// ============================================================================
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Paginator from 'primevue/paginator';
import Breadcrumb from 'primevue/breadcrumb';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog'; // Remplacement de Sidebar
import OverlayPanel from 'primevue/overlaypanel';
import Tooltip from 'primevue/tooltip';
import Toast from 'primevue/toast';
import Message from 'primevue/message';
import Skeleton from 'primevue/skeleton';
import { useToast } from 'primevue/usetoast';

// ============================================================================
// DÉFINITION DES PROPRIÉTÉS (PROPS)
// ============================================================================
const props = defineProps({
    /**
     * Données paginées des étudiants envoyées par le backend.
     */
    students: {
        type: Object,
        required: true
    },

    /**
     * État actuel des filtres appliqués (pour conserver l'état lors de la navigation).
     */
    filters: {
        type: Object,
        required: false,
        default: () => ({})
    },

    /**
     * Liste des cours disponibles pour le menu déroulant de filtrage.
     */
    courses: {
        type: Array,
        required: false,
        default: () => []
    },

    /**
     * Statistiques globales de la cohorte (KPIs). Doit être fourni par le Controller.
     */
    stats: {
        type: Object,
        required: true
    },

    /**
     * Liste des statuts possibles générée par le backend (ex: Actif, Inactif).
     */
    availableStatuses: {
        type: Array,
        required: false,
        default: () => []
    }
});

// ============================================================================
// GESTION DE L'ÉTAT RÉACTIF (STATE)
// ============================================================================
const toast = useToast();
const isLoading = ref(false);
const isExporting = ref(false);
const displayMode = ref('table'); // Modes: 'table' | 'grid'

// Gestion de la vue détaillée (Dialog)
const showQuickView = ref(false);
const selectedStudent = ref(null);

// Gestion des actions en masse (Bulk Actions)
const selectedStudents = ref([]);

// Référence pour le menu contextuel (Actions de ligne)
const op = ref();
const contextStudent = ref(null);

// ============================================================================
// INITIALISATION DES FILTRES
// ============================================================================
const activeFilters = ref({
    search: props.filters.search || '',
    course: props.filters.course || null,
    status: props.filters.status || null,
    performance: props.filters.performance || null
});

// ============================================================================
// LOGIQUE MÉTIER & MÉTHODES
// ============================================================================

/**
 * Synchronisation globale des données avec le backend via Inertia.js.
 * Utilisation de debounce pour éviter de surcharger le serveur lors de la frappe.
 *
 * @function syncData
 */
const syncData = debounce(() => {
    isLoading.value = true;

    router.get(
        route('student-reports.index'),
        {
            ...activeFilters.value,
            page: 1 // Toujours revenir à la page 1 lors d'un nouveau filtrage
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
                toast.add({
                    severity: 'error',
                    summary: 'Erreur réseau',
                    detail: 'Impossible de synchroniser les données avec le serveur.',
                    life: 5000
                });
            }
        }
    );
}, 500);

// Écouteur sur les filtres pour déclencher la synchronisation
watch(activeFilters, () => syncData(), { deep: true });

/**
 * Gestion de la pagination spécifique.
 *
 * @param {Object} event - Événement de pagination PrimeVue
 */
const onPageChange = (event) => {
    isLoading.value = true;
    router.get(
        props.students.path,
        {
            page: event.page + 1,
            ...activeFilters.value
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => { isLoading.value = false; }
        }
    );
};

/**
 * Ouvre le composant Dialog pour afficher les informations détaillées de l'étudiant.
 * Les informations affichées dépendent STRICTEMENT des données contenues dans l'objet `student`.
 *
 * @param {Object} student - L'objet étudiant sélectionné
 */
const openQuickView = (student) => {
    selectedStudent.value = student;
    showQuickView.value = true;
};

/**
 * Ouvre le menu contextuel (OverlayPanel) pour une ligne spécifique.
 *
 * @param {Event} event - Événement de clic natif
 * @param {Object} student - L'étudiant ciblé par le menu
 */
const toggleContextMenu = (event, student) => {
    contextStudent.value = student;
    op.value.toggle(event);
};

/**
 * Déclenche le processus d'exportation de données vers le backend.
 *
 * @function triggerExport
 */
const triggerExport = () => {
    isExporting.value = true;
    toast.add({
        severity: 'info',
        summary: 'Exportation initiée',
        detail: 'Le serveur prépare votre fichier d\'export...',
        life: 3000
    });

    // Requête réelle vers le backend pour l'exportation
    router.post(
        route('student-reports.export'),
        { ...activeFilters.value },
        {
            onFinish: () => {
                isExporting.value = false;
                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Exportation terminée avec succès.',
                    life: 5000
                });
            }
        }
    );
};

/**
 * Détermine la classe de sévérité visuelle en fonction du score.
 *
 * @param {Number} score - Le score moyen de l'étudiant
 * @returns {String} - Le nom de la classe de sévérité (success, warning, danger)
 */
const getScoreSeverity = (score) => {
    if (score === null || score === undefined) return 'secondary';
    if (score >= 80) return 'success';
    if (score >= 50) return 'warning';
    return 'danger';
};

/**
 * Réinitialise tous les filtres actifs à leur état par défaut.
 */
const resetFilters = () => {
    activeFilters.value = {
        search: '',
        course: null,
        status: null,
        performance: null
    };
};

// ============================================================================
// CONFIGURATION DU BREADCRUMB
// ============================================================================
const breadcrumbHome = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Intelligence Engine', disabled: true },
    { label: 'Student Analytics', active: true }
]);

</script>

<template>
    <AppLayout>
        <Head title="Intelligence Hub - Rapports Étudiants" />

        <!-- Conteneur Global des Toasts -->
        <Toast position="top-right" />

        <div class="min-h-screen bg-slate-50 pb-20 layout-wrapper">

            <!-- ============================================================== -->
            <!-- HEADER SECTION (Glassmorphism & Actions globales)              -->
            <!-- ============================================================== -->
            <header class="sticky top-0 z-30 bg-white/85 backdrop-blur-xl border-b border-slate-200 px-6 py-5 shadow-sm transition-all duration-300">
                <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">

                    <!-- Titre et Fil d'Ariane -->
                    <div class="flex items-center gap-4">
                        <div class="p-4 bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl shadow-lg shadow-indigo-200/50 flex items-center justify-center">
                            <i class="pi pi-chart-bar text-white text-2xl"></i>
                        </div>
                        <div class="flex flex-col">
                            <Breadcrumb
                                :home="breadcrumbHome"
                                :model="breadcrumbItems"
                                class="p-0 bg-transparent mb-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-none"
                            />
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight leading-none">
                                Student Intelligence Hub
                            </h1>
                            <p class="text-slate-500 text-sm font-medium mt-1.5">
                                Surveillance analytique centralisée synchronisée avec le Controller
                            </p>
                        </div>
                    </div>

                    <!-- Actions Principales -->
                    <div class="flex items-center gap-3">
                        <Button
                            icon="pi pi-file-export"
                            label="Exporter le rapport"
                            class="p-button-outlined p-button-secondary font-bold hover:bg-slate-50 transition-colors"
                            :loading="isExporting"
                            @click="triggerExport"
                        />
                        <!-- Bouton prévu pour être lié à une action de création gérée par Inertia -->
                        <Link :href="route('student-reports.create')" v-if="$page.props.auth.can_create_report">
                            <Button
                                icon="pi pi-plus"
                                label="Nouvelle Analyse"
                                class="font-black bg-indigo-600 hover:bg-indigo-700 border-none shadow-xl shadow-indigo-200/50 transition-all"
                            />
                        </Link>
                    </div>
                </div>
            </header>

            <!-- ============================================================== -->
            <!-- CONTENU PRINCIPAL DE LA PAGE                                   -->
            <!-- ============================================================== -->
            <main class="max-w-[1600px] mx-auto p-6 space-y-8 mt-4">

                <!-- ---------------------------------------------------------- -->
                <!-- 1. KPI STATS GRID (Données injectées depuis le backend)    -->
                <!-- ---------------------------------------------------------- -->
                <section
                    v-if="stats"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                    aria-label="Statistiques clés"
                >
                    <!-- KPI 1 : Moyenne Globale -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-5">
                            <div class="p-4 bg-indigo-50/80 rounded-2xl text-indigo-600 transition-transform duration-300 group-hover:scale-110">
                                <i class="pi pi-percentage text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-slate-500 font-bold text-[11px] uppercase tracking-widest">
                            Moyenne Globale Cohorte
                        </p>
                        <h2 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">
                            {{ stats.avg_score !== null ? stats.avg_score + '%' : 'N/A' }}
                        </h2>
                    </div>

                    <!-- KPI 2 : Apprenants Actifs -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-5">
                            <div class="p-4 bg-emerald-50/80 rounded-2xl text-emerald-600 transition-transform duration-300 group-hover:scale-110">
                                <i class="pi pi-users text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-slate-500 font-bold text-[11px] uppercase tracking-widest">
                            Apprenants Actifs Actuellement
                        </p>
                        <h2 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">
                            {{ stats.active_now ?? 0 }}
                        </h2>
                    </div>

                    <!-- KPI 3 : Certifications -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-5">
                            <div class="p-4 bg-blue-50/80 rounded-2xl text-blue-600 transition-transform duration-300 group-hover:scale-110">
                                <i class="pi pi-verified text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-slate-500 font-bold text-[11px] uppercase tracking-widest">
                            Certifications Délivrées
                        </p>
                        <h2 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">
                            {{ stats.total_certifications ?? 0 }}
                        </h2>
                    </div>

                    <!-- KPI 4 : Profils à Risque -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 group">
                        <div class="flex justify-between items-start mb-5">
                            <div class="p-4 bg-rose-50/80 rounded-2xl text-rose-600 transition-transform duration-300 group-hover:scale-110">
                                <i class="pi pi-exclamation-triangle text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-slate-500 font-bold text-[11px] uppercase tracking-widest">
                            Profils En Décrochage
                        </p>
                        <h2 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">
                            {{ stats.at_risk_students ?? 0 }}
                        </h2>
                    </div>
                </section>

                <!-- Message de repli si le backend n'envoie pas de stats -->
                <Message
                    v-else
                    severity="warn"
                    :closable="false"
                >
                    Les données statistiques globales ne sont pas disponibles depuis le serveur.
                </Message>

                <!-- ---------------------------------------------------------- -->
                <!-- 2. BARRE DE FILTRAGE AVANCÉE                               -->
                <!-- ---------------------------------------------------------- -->
                <section class="bg-white p-4 rounded-[1.5rem] border border-slate-200 flex flex-wrap items-center justify-between gap-4 shadow-sm z-20 relative">

                    <div class="flex flex-wrap items-center gap-4 flex-1">
                        <!-- Recherche textuelle -->
                        <div class="flex-1 min-w-[280px] max-w-md">
                            <span class="p-input-icon-left w-full">
                                <i class="pi pi-search text-slate-400" />
                                <InputText
                                    v-model="activeFilters.search"
                                    placeholder="Rechercher par ID, Nom, Email..."
                                    class="w-full border-slate-200 bg-slate-50/50 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-shadow"
                                />
                            </span>
                        </div>

                        <!-- Filtre par cours (données depuis Controller) -->
                        <Dropdown
                            v-if="courses && courses.length > 0"
                            v-model="activeFilters.course"
                            :options="courses"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Sélectionner un cours"
                            class="w-full md:w-64 bg-slate-50/50 border-slate-200 rounded-xl"
                            showClear
                        />

                        <!-- Filtre par statuts (données depuis Controller) -->
                        <MultiSelect
                            v-if="availableStatuses && availableStatuses.length > 0"
                            v-model="activeFilters.status"
                            :options="availableStatuses"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filtrer par statut"
                            class="w-full md:w-56 bg-slate-50/50 border-slate-200 rounded-xl"
                            :maxSelectedLabels="2"
                        />
                    </div>

                    <!-- Contrôles d'affichage et réinitialisation -->
                    <div class="flex items-center gap-3">
                        <Button
                            icon="pi pi-filter-slash"
                            class="p-button-text p-button-secondary hover:bg-slate-100 rounded-xl"
                            v-tooltip.bottom="'Réinitialiser les filtres'"
                            @click="resetFilters"
                        />

                        <div class="h-8 w-[1px] bg-slate-200 hidden md:block"></div>

                        <!-- Toggle Table / Grid -->
                        <div class="flex bg-slate-100 p-1.5 rounded-xl gap-1">
                            <Button
                                icon="pi pi-list"
                                :class="displayMode === 'table' ? 'bg-white shadow-sm text-indigo-600' : 'p-button-text text-slate-400'"
                                @click="displayMode = 'table'"
                                class="p-2 rounded-lg"
                                v-tooltip.top="'Vue Tableau'"
                            />
                            <Button
                                icon="pi pi-th-large"
                                :class="displayMode === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'p-button-text text-slate-400'"
                                @click="displayMode = 'grid'"
                                class="p-2 rounded-lg"
                                v-tooltip.top="'Vue Grille'"
                            />
                        </div>
                    </div>
                </section>

                <!-- ---------------------------------------------------------- -->
                <!-- 3. ZONE DE DONNÉES (TABLEAU OU GRILLE)                     -->
                <!-- ---------------------------------------------------------- -->
                <div class="relative min-h-[500px]">

                    <!-- Overlay de chargement (Feedback visuel) -->
                    <div
                        v-if="isLoading"
                        class="absolute inset-0 bg-white/70 backdrop-blur-[3px] z-20 flex items-center justify-center rounded-[2rem]"
                    >
                        <div class="flex flex-col items-center gap-4">
                            <i class="pi pi-spin pi-spinner text-5xl text-indigo-600"></i>
                            <span class="text-sm font-bold text-slate-600 uppercase tracking-widest">
                                Synchronisation Serveur...
                            </span>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!-- MODE VUE TABLEAU (Datatable)   -->
                    <!-- ============================== -->
                    <div
                        v-if="displayMode === 'table'"
                        class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden flex flex-col"
                    >
                        <!-- Barre d'actions en masse (S'affiche si des lignes sont sélectionnées) -->
                        <Transition name="slide-down">
                            <div
                                v-if="selectedStudents.length > 0"
                                class="bg-indigo-900 p-4 flex flex-wrap items-center justify-between gap-4"
                            >
                                <span class="text-white font-black text-sm tracking-wide">
                                    {{ selectedStudents.length }} Profil(s) sélectionné(s)
                                </span>
                                <div class="flex gap-2">
                                    <Button label="Notifier" icon="pi pi-envelope" class="p-button-sm p-button-success border-none" />
                                    <Button label="Générer Rapport" icon="pi pi-file-pdf" class="p-button-sm bg-white/20 hover:bg-white/30 text-white border-none" />
                                </div>
                            </div>
                        </Transition>

                        <DataTable
                            :value="students.data"
                            v-model:selection="selectedStudents"
                            dataKey="id"
                            responsiveLayout="stack"
                            breakpoint="960px"
                            class="enterprise-table"
                            :rowHover="true"
                            stripedRows
                        >
                            <!-- Sélection multiple -->
                            <Column selectionMode="multiple" headerStyle="width: 4rem"></Column>

                            <!-- Colonne: Identité Étudiant -->
                            <Column header="Identité Apprenant" class="py-5">
                                <template #body="{ data }">
                                    <div
                                        class="flex items-center gap-4 group cursor-pointer"
                                        @click="openQuickView(data)"
                                    >
                                        <div class="relative flex-shrink-0">
                                            <!-- Fallback Icon si pas d'avatar depuis le backend -->
                                            <Avatar
                                                v-if="data.avatar"
                                                :image="data.avatar"
                                                shape="circle"
                                                size="xlarge"
                                                class="shadow-sm border border-slate-200 group-hover:border-indigo-400 transition-colors"
                                            />
                                            <Avatar
                                                v-else
                                                icon="pi pi-user"
                                                shape="circle"
                                                size="xlarge"
                                                class="bg-slate-100 text-slate-400 border border-slate-200"
                                            />
                                            <div
                                                v-if="data.is_online"
                                                class="absolute bottom-0 right-0 w-3.5 h-3.5 rounded-full border-2 border-white bg-emerald-500 shadow-sm"
                                                title="En ligne actuellement"
                                            ></div>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-black text-slate-800 text-[15px] truncate group-hover:text-indigo-600 transition-colors">
                                                {{ data.name || 'Nom Inconnu' }}
                                            </span>
                                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                                <span class="text-[11px] font-mono font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                                                    ID: {{ data.id }}
                                                </span>
                                                <span class="text-[11px] text-slate-500 font-medium truncate max-w-[200px]">
                                                    {{ data.email }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Colonne: Performance (Calcul strict basé sur data.avg_score du backend) -->
                            <Column header="Performance Analytics" style="min-width: 250px">
                                <template #body="{ data }">
                                    <div class="space-y-2.5 w-full max-w-[200px]" v-if="data.avg_score !== null">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                                Score Algorithmique
                                            </span>
                                            <span :class="`font-black text-sm text-${getScoreSeverity(data.avg_score)}-600`">
                                                {{ data.avg_score }}%
                                            </span>
                                        </div>
                                        <ProgressBar
                                            :value="data.avg_score"
                                            :showValue="false"
                                            :style="{height: '6px'}"
                                            :class="`perf-bar-${getScoreSeverity(data.avg_score)} bg-slate-100 rounded-full overflow-hidden`"
                                        />
                                    </div>
                                    <span v-else class="text-xs italic text-slate-400">Non évalué</span>
                                </template>
                            </Column>

                            <!-- Colonne: Engagement -->
                            <Column header="Engagement" class="text-center" style="min-width: 120px">
                                <template #body="{ data }">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-lg font-black text-slate-700">
                                            {{ data.submissions_count ?? 0 }}
                                        </span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">
                                            Soumissions
                                        </span>
                                    </div>
                                </template>
                            </Column>

                            <!-- Colonne: Activité Réseau -->
                            <Column header="Activité Réseau" style="min-width: 150px">
                                <template #body="{ data }">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2 text-sm font-bold text-slate-600">
                                            <i class="pi pi-clock text-slate-400 text-xs"></i>
                                            {{ data.last_active_human || 'Jamais connecté' }}
                                        </div>
                                        <div v-if="data.ip_address" class="flex items-center gap-1 opacity-60">
                                            <i class="pi pi-globe text-[9px]"></i>
                                            <span class="text-[10px] font-mono">{{ data.ip_address }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- Colonne: Actions -->
                            <Column header="" bodyClass="text-right" style="width: 150px">
                                <template #body="{ data }">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            icon="pi pi-eye"
                                            class="p-button-rounded p-button-text p-button-secondary hover:bg-slate-100"
                                            v-tooltip.top="'Aperçu technique'"
                                            @click="openQuickView(data)"
                                        />
                                        <!-- Lien de routing vers la vue détaillée complète -->
                                        <Link :href="route('student-reports.show', data.id)">
                                            <Button
                                                icon="pi pi-arrow-right"
                                                class="p-button-rounded p-button-text text-indigo-600 hover:bg-indigo-50"
                                                v-tooltip.top="'Dossier complet'"
                                            />
                                        </Link>
                                        <Button
                                            icon="pi pi-ellipsis-v"
                                            class="p-button-rounded p-button-text p-button-secondary"
                                            @click="(e) => toggleContextMenu(e, data)"
                                            aria-haspopup="true"
                                        />
                                    </div>
                                </template>
                            </Column>

                            <!-- État Vide (Empty State) -->
                            <template #empty>
                                <div class="py-24 text-center flex flex-col items-center">
                                    <div class="p-6 bg-slate-50 rounded-full mb-6">
                                        <i class="pi pi-search-minus text-5xl text-slate-300"></i>
                                    </div>
                                    <h3 class="text-xl font-black text-slate-800">Aucune donnée trouvée</h3>
                                    <p class="text-slate-500 max-w-sm mt-2 text-sm font-medium">
                                        Le contrôleur n'a renvoyé aucun apprenant correspondant aux paramètres actuels.
                                    </p>
                                    <Button
                                        label="Effacer les filtres"
                                        icon="pi pi-refresh"
                                        class="mt-6 p-button-outlined p-button-secondary rounded-xl"
                                        @click="resetFilters"
                                    />
                                </div>
                            </template>
                        </DataTable>

                        <!-- Footer de Pagination personnalisé -->
                        <div
                            v-if="students.total > 0"
                            class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 mt-auto"
                        >
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Affichage de <span class="text-indigo-600">{{ (students.current_page - 1) * students.per_page + 1 }}</span>
                                à <span class="text-indigo-600">{{ Math.min(students.current_page * students.per_page, students.total) }}</span>
                                sur <span class="text-indigo-600">{{ students.total }}</span> entrées
                            </p>

                            <Paginator
                                :rows="students.per_page"
                                :totalRecords="students.total"
                                :first="(students.current_page - 1) * students.per_page"
                                @page="onPageChange"
                                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                                class="bg-transparent border-none p-0 custom-paginator"
                            />
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!-- MODE VUE GRILLE (Cards)        -->
                    <!-- ============================== -->
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
                    >
                        <!-- Rendu conditionnel si données existantes -->
                        <template v-if="students.data.length > 0">
                            <div
                                v-for="student in students.data"
                                :key="student.id"
                                class="bg-white rounded-[2rem] p-6 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 relative group flex flex-col h-full"
                            >
                                <!-- Bouton Action absolue -->
                                <div class="absolute top-4 right-4">
                                    <Button
                                        icon="pi pi-ellipsis-h"
                                        class="p-button-rounded p-button-text p-button-secondary"
                                        @click="(e) => toggleContextMenu(e, student)"
                                    />
                                </div>

                                <div class="flex flex-col items-center text-center flex-grow">
                                    <Avatar
                                        :image="student.avatar"
                                        :icon="!student.avatar ? 'pi pi-user' : undefined"
                                        size="xlarge"
                                        shape="circle"
                                        class="w-20 h-20 shadow-lg border-4 border-white mb-4 bg-slate-100 text-slate-400"
                                    />
                                    <h3 class="text-lg font-black text-slate-800 leading-tight">
                                        {{ student.name || 'Inconnu' }}
                                    </h3>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1 mb-5">
                                        ID: {{ student.id }}
                                    </span>

                                    <!-- Mini stats grille basées strictement sur backend -->
                                    <div class="w-full grid grid-cols-2 gap-3 mb-6 mt-auto">
                                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Score</p>
                                            <p :class="`text-base font-black text-${getScoreSeverity(student.avg_score)}-600`">
                                                {{ student.avg_score !== null ? student.avg_score + '%' : '-' }}
                                            </p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mb-1">Activité</p>
                                            <p class="text-base font-black text-slate-700">
                                                {{ student.submissions_count ?? 0 }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Actions Grille -->
                                    <div class="w-full flex gap-2">
                                        <Button
                                            icon="pi pi-eye"
                                            class="p-button-outlined p-button-secondary w-12 rounded-xl"
                                            @click="openQuickView(student)"
                                            v-tooltip.bottom="'Aperçu'"
                                        />
                                        <Link :href="route('student-reports.show', student.id)" class="flex-1">
                                            <Button
                                                label="Dossier"
                                                class="w-full font-black rounded-xl bg-slate-900 border-none hover:bg-slate-800 transition-colors"
                                            />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State de la grille -->
                        <div v-else class="col-span-full py-20 text-center bg-white rounded-[2rem] border border-slate-200 border-dashed">
                            <i class="pi pi-box text-4xl text-slate-300 mb-4"></i>
                            <p class="text-slate-500 font-medium">Aucun résultat à afficher sous forme de grille.</p>
                        </div>

                        <!-- Pagination pour la grille -->
                        <div class="col-span-full mt-4 flex justify-center" v-if="students.total > students.per_page">
                            <Paginator
                                :rows="students.per_page"
                                :totalRecords="students.total"
                                :first="(students.current_page - 1) * students.per_page"
                                @page="onPageChange"
                                class="bg-white rounded-2xl border border-slate-200 shadow-sm"
                            />
                        </div>
                    </div>
                </div>
            </main>

            <!-- ============================================================== -->
            <!-- DIALOG : VUE DÉTAILLÉE (REMPLACE LE SIDEBAR)                   -->
            <!-- ============================================================== -->
            <Dialog
                v-model:visible="showQuickView"
                modal
                header="Analyse Profil Apprenant"
                :style="{ width: '90vw', maxWidth: '600px' }"
                :breakpoints="{ '960px': '75vw', '641px': '100vw' }"
                class="enterprise-dialog"
                :dismissableMask="true"
                maximizable
            >
                <div v-if="selectedStudent" class="py-2 space-y-6">

                    <!-- En-tête du profil (Hero Section) -->
                    <div class="bg-slate-900 p-8 rounded-3xl text-white relative overflow-hidden flex items-center gap-6 shadow-xl shadow-slate-200">
                        <Avatar
                            :image="selectedStudent.avatar"
                            :icon="!selectedStudent.avatar ? 'pi pi-user' : undefined"
                            size="xlarge"
                            shape="circle"
                            class="w-24 h-24 border-4 border-white/10 flex-shrink-0 bg-slate-800"
                        />
                        <div class="z-10">
                            <h2 class="text-2xl font-black tracking-tight mb-1">{{ selectedStudent.name || 'Nom Inconnu' }}</h2>
                            <p class="text-slate-400 font-medium text-sm flex items-center gap-2">
                                <i class="pi pi-envelope text-xs"></i>
                                {{ selectedStudent.email || 'Email non fourni' }}
                            </p>
                            <div class="mt-4 flex gap-2">
                                <Tag
                                    :severity="selectedStudent.is_online ? 'success' : 'secondary'"
                                    :value="selectedStudent.is_online ? 'En Ligne' : 'Hors Ligne'"
                                    class="text-[10px] font-black tracking-widest px-2 py-1 rounded-md"
                                />
                                <Tag
                                    severity="info"
                                    :value="`ID: ${selectedStudent.id}`"
                                    class="text-[10px] font-black tracking-widest px-2 py-1 rounded-md bg-slate-800 text-slate-300"
                                />
                            </div>
                        </div>
                        <!-- Décoration de fond -->
                        <div class="absolute -top-12 -right-12 text-slate-800/50">
                            <i class="pi pi-chart-pie text-[150px]"></i>
                        </div>
                    </div>

                    <!-- 1. Analyse des compétences (Strictement depuis Controller via student.skills) -->
                    <div class="space-y-3">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest px-2 flex items-center gap-2">
                            <i class="pi pi-star text-indigo-500"></i> Métriques de Compétences
                        </h4>

                        <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6 space-y-5">
                            <template v-if="selectedStudent.skills && selectedStudent.skills.length > 0">
                                <div
                                    v-for="(skill, index) in selectedStudent.skills"
                                    :key="index"
                                >
                                    <div class="flex justify-between mb-2">
                                        <span class="font-bold text-slate-700 text-sm">
                                            {{ skill.name }}
                                        </span>
                                        <span class="font-black text-slate-900 text-sm">
                                            {{ skill.score }}%
                                        </span>
                                    </div>
                                    <ProgressBar
                                        :value="skill.score"
                                        :showValue="false"
                                        :style="{height: '6px'}"
                                        :class="`perf-bar-${getScoreSeverity(skill.score)} bg-slate-200`"
                                    />
                                </div>
                            </template>
                            <div v-else class="text-center py-4">
                                <span class="text-sm text-slate-500 italic">
                                    Aucune donnée de compétence fournie par l'API pour cet étudiant.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Historique d'activité (Strictement depuis Controller via student.recent_activities) -->
                    <div class="space-y-3">
                        <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest px-2 flex items-center gap-2">
                            <i class="pi pi-history text-indigo-500"></i> Historique d'activité récent
                        </h4>

                        <div class="border border-slate-200 rounded-3xl overflow-hidden bg-white">
                            <template v-if="selectedStudent.recent_activities && selectedStudent.recent_activities.length > 0">
                                <div class="flex flex-col">
                                    <div
                                        v-for="(activity, index) in selectedStudent.recent_activities"
                                        :key="index"
                                        class="flex gap-4 p-4 hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0"
                                    >
                                        <div class="h-10 w-10 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                                            <!-- Fallback icon si backend ne fournit pas d'icon spécifique -->
                                            <i :class="activity.icon || 'pi pi-check-circle'"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-800 leading-tight truncate">
                                                {{ activity.description }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[11px] text-slate-500 font-medium">
                                                    {{ activity.date_human }}
                                                </span>
                                                <span v-if="activity.score" class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded">
                                                    Note: {{ activity.score }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="p-6 text-center">
                                <p class="text-sm text-slate-500 italic">
                                    Aucune activité récente enregistrée par le système.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Skeleton loader de sécurité si sélection null -->
                <div v-else class="p-4 space-y-4">
                    <Skeleton width="100%" height="100px" borderRadius="16px"></Skeleton>
                    <Skeleton width="100%" height="200px" borderRadius="16px"></Skeleton>
                </div>

                <template #footer>
                    <div class="flex items-center justify-between w-full pt-4 border-t border-slate-100">
                        <Button
                            label="Fermer"
                            icon="pi pi-times"
                            @click="showQuickView = false"
                            class="p-button-text p-button-secondary font-bold"
                        />
                        <Link :href="selectedStudent ? route('student-reports.show', selectedStudent.id) : '#'">
                            <Button
                                label="Ouvrir le dossier complet"
                                icon="pi pi-external-link"
                                class="font-black rounded-xl shadow-lg shadow-indigo-100 px-6"
                                :disabled="!selectedStudent"
                            />
                        </Link>
                    </div>
                </template>
            </Dialog>

            <!-- ============================================================== -->
            <!-- MENU CONTEXTUEL (Overlay Panel) POUR LES LIGNES                -->
            <!-- ============================================================== -->
            <OverlayPanel
                ref="op"
                class="enterprise-overlay"
                :showCloseIcon="false"
            >
                <div class="flex flex-col w-56 p-1">
                    <div class="px-3 py-2 border-b border-slate-100 mb-1">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                            Actions système
                        </span>
                    </div>
                    <!-- Note: Les actions réelles dépendent des capacités du Controller -->
                    <Button
                        icon="pi pi-envelope"
                        label="Contacter l'apprenant"
                        class="p-button-text p-button-sm justify-start text-slate-700 font-semibold hover:bg-slate-50"
                    />
                    <Button
                        icon="pi pi-chart-line"
                        label="Forcer recalcul stats"
                        class="p-button-text p-button-sm justify-start text-slate-700 font-semibold hover:bg-slate-50"
                    />
                    <div class="h-[1px] bg-slate-100 my-1"></div>
                    <Button
                        icon="pi pi-ban"
                        label="Suspendre l'accès"
                        class="p-button-text p-button-sm justify-start text-rose-600 font-bold hover:bg-rose-50"
                    />
                </div>
            </OverlayPanel>

        </div>
    </AppLayout>
</template>

<style scoped>
/**
 * ======================================================================================
 * SYSTEM DESIGN - STYLES SPÉCIFIQUES (SCOPED)
 * ======================================================================================
 * Définition des règles CSS pour personnaliser l'interface Enterprise,
 * surchargeant les styles de base de PrimeVue pour correspondre à la charte graphique.
 */

/* Typographie globale de la vue */
.layout-wrapper {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

/* ================================================================================== */
/* DATATABLE CUSTOMIZATION (Enterprise Look & Feel)                                   */
/* ================================================================================== */
:deep(.enterprise-table) {
    border: none;
}

/* En-tête du tableau */
:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc; /* slate-50 */
    color: #64748b;      /* slate-500 */
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    padding: 1.25rem 1.5rem;
    border-bottom: 2px solid #e2e8f0; /* slate-200 */
    border-top: none;
    border-left: none;
    border-right: none;
}

/* Lignes du tableau */
:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.p-datatable-tbody > tr:nth-child(even)) {
    background: #f8fafc; /* slate-50 pour striped effect doux */
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9 !important; /* slate-100 */
    box-shadow: inset 4px 0 0 #4f46e5; /* indigo-600 accent */
}

/* Cellules du tableau */
:deep(.p-datatable-tbody > tr > td) {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9; /* slate-100 */
    border-top: none;
    border-left: none;
    border-right: none;
    color: #334155; /* slate-700 */
}

/* Checkboxes dans le tableau */
:deep(.p-checkbox .p-checkbox-box) {
    border-radius: 6px;
    border-color: #cbd5e1;
    transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background: #4f46e5; /* indigo-600 */
    border-color: #4f46e5;
}

/* ================================================================================== */
/* PAGINATION PERSONNALISÉE                                                           */
/* ================================================================================== */
:deep(.custom-paginator.p-paginator) {
    border: none;
    background: transparent;
}

:deep(.custom-paginator .p-paginator-pages .p-paginator-page) {
    min-width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem; /* rounded-xl */
    font-weight: 800;
    color: #64748b; /* slate-500 */
    transition: all 0.2s ease;
    border: none;
}

:deep(.custom-paginator .p-paginator-pages .p-paginator-page:hover) {
    background: #e2e8f0; /* slate-200 */
    color: #0f172a; /* slate-900 */
}

:deep(.custom-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: #4f46e5; /* indigo-600 */
    color: white;
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
}

:deep(.custom-paginator .p-paginator-first),
:deep(.custom-paginator .p-paginator-prev),
:deep(.custom-paginator .p-paginator-next),
:deep(.custom-paginator .p-paginator-last) {
    color: #94a3b8; /* slate-400 */
    border-radius: 0.75rem;
    height: 2.5rem;
    min-width: 2.5rem;
    border: none;
}

/* ================================================================================== */
/* COMPOSANTS DE FORMULAIRES ET BOUTONS                                               */
/* ================================================================================== */
:deep(.p-inputtext),
:deep(.p-dropdown),
:deep(.p-multiselect) {
    box-shadow: none !important;
}

:deep(.p-dropdown .p-dropdown-label),
:deep(.p-multiselect .p-multiselect-label) {
    font-size: 0.875rem; /* text-sm */
    color: #334155;
    font-weight: 500;
}

/* ================================================================================== */
/* DIALOG & OVERLAY CUSTOMIZATION                                                     */
/* ================================================================================== */
:deep(.enterprise-dialog .p-dialog-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem 2rem;
    background: #ffffff;
}

:deep(.enterprise-dialog .p-dialog-title) {
    font-weight: 900;
    font-size: 1.25rem;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: -0.025em;
}

:deep(.enterprise-dialog .p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
    background: #ffffff;
}

:deep(.enterprise-dialog .p-dialog-footer) {
    border-top: none;
    padding: 1rem 2rem 1.5rem 2rem;
    background: #ffffff;
}

:deep(.enterprise-overlay.p-overlaypanel) {
    border-radius: 1rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

/* ================================================================================== */
/* PROGRESS BARS - INJECTION DYNAMIQUE DE COULEURS SELON SÉVÉRITÉ                     */
/* ================================================================================== */
:deep(.perf-bar-success .p-progressbar-value) { background: #10b981 !important; } /* emerald-500 */
:deep(.perf-bar-warning .p-progressbar-value) { background: #f59e0b !important; } /* amber-500 */
:deep(.perf-bar-danger .p-progressbar-value)  { background: #ef4444 !important; } /* red-500 */
:deep(.perf-bar-secondary .p-progressbar-value) { background: #94a3b8 !important; } /* slate-400 */

/* ================================================================================== */
/* ANIMATIONS ET UTILITAIRES                                                          */
/* ================================================================================== */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 100px;
    opacity: 1;
    overflow: hidden;
}

.slide-down-enter-from,
.slide-down-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

/* Custom Scrollbar pour les zones défilantes (ex: dialog) */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* ================================================================================== */
/* MEDIA QUERIES (IMPRESSION)                                                         */
/* ================================================================================== */
@media print {
    header,
    .p-paginator,
    .bg-indigo-900,
    .p-dialog,
    .p-toast {
        display: none !important;
    }

    .layout-wrapper {
        background: white !important;
    }

    .enterprise-table {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
}
</style>
