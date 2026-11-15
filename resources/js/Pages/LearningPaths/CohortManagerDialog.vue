<script setup>
/**
 * ====================================================================================================
 * SUBNETCONGO LMS - COHORT MANAGER DIALOG (EXTRACTED FROM LearningPaths.vue)
 * ====================================================================================================
 * Composant Dialog de gestion des cohortes d'apprenants : liste des inscrits,
 * ajout/retrait dual-pane, synchronisation serveur et mini-analytics.
 * ====================================================================================================
 */

import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirmAction } from "@/composables/useConfirmAction";
import { useI18n } from 'vue-i18n';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// IMPORTATION DES COMPOSANTS PRIMEVUE
// ======================================================================================
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Skeleton from 'primevue/skeleton';
import Chart from 'primevue/chart';
import Tooltip from 'primevue/tooltip';

import CohortUserItem from '../CohortUserItem.vue';

// ======================================================================================
// PROPS & EMITS
// ======================================================================================
const props = defineProps({
    /** Contrôle la visibilité du dialog (v-model:visible) */
    visible: { type: Boolean, default: false },
    /** Base de données des utilisateurs/étudiants pour la gestion de cohorte */
    users: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:visible', 'saved']);

const toast = useToast();
const confirmAction = useConfirmAction();
const { t } = useI18n();

// Proxy v-model:visible (conserve le nom `cohortDialog` utilisé dans le template)
const cohortDialog = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// ======================================================================================
// GESTIONNAIRE DE COHORTE (COHORT MANAGER)
// ======================================================================================

const selectedPath = ref(null);
const cohortActiveTab = ref('inscrits'); // 'inscrits' ou 'ajouter'
const cohortSearchQuery = ref('');
const globalUserSearchQuery = ref('');
const loadingCohort = ref(false);
const cohortEnrolledUsers = ref([]);
const cohortAvailableUsers = ref([]);

// Formulaire pour soumission massive
const cohortForm = useForm({
    user_ids: [],     // Utilisateurs à ajouter
    remove_ids: []    // Utilisateurs à retirer
});

/**
 * Ouvre le gestionnaire de cohorte pour un parcours spécifique.
 * Prépare les listes d'étudiants inscrits vs disponibles.
 */
const openCohortManager = (path) => {
    loadingCohort.value = true;
    selectedPath.value = cloneDeep(path);
    cohortActiveTab.value = 'inscrits';
    cohortSearchQuery.value = '';
    globalUserSearchQuery.value = '';

    // Réinitialisation du form
    cohortForm.user_ids = [];
    cohortForm.remove_ids = [];

    // On utilise directement les données synchronisées par le contrôleur (path.enrollments)
    // On mappe les données pivot réelles (enrolled_at) au lieu de générer du faux
    cohortEnrolledUsers.value = (path.enrollments || []).map(user => ({
        ...user,
        enrolled_at: user.pivot?.enrolled_at ? new Date(user.pivot.enrolled_at).toLocaleDateString() : 'N/A',
        progress: user.pivot?.progress || 0,
        status: user.pivot?.status || 'Active',
        is_staged: false // Utilisateurs déjà en base
    }));

    // On filtre les utilisateurs disponibles en excluant ceux déjà présents dans la cohorte
    const enrolledIds = cohortEnrolledUsers.value.map(u => u.id);
    cohortAvailableUsers.value = props.users.filter(u => !enrolledIds.includes(u.id));

    setTimeout(() => { loadingCohort.value = false; cohortDialog.value = true; }, 400); // Fake delay for UX
};

/**
 * Computed: Filtre les étudiants inscrits selon la recherche interne de la cohorte
 */
const filteredEnrolledUsers = computed(() => {
    if (!cohortSearchQuery.value) return cohortEnrolledUsers.value;
    const q = cohortSearchQuery.value.toLowerCase();
    return cohortEnrolledUsers.value.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

/**
 * Computed: Filtre les utilisateurs globaux disponibles pour l'ajout
 */
const filteredAvailableUsers = computed(() => {
    if (!globalUserSearchQuery.value) return cohortAvailableUsers.value;
    const q = globalUserSearchQuery.value.toLowerCase();
    return cohortAvailableUsers.value.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

/**
 * Action UI : Transfère un étudiant de la liste des "disponibles" vers les "inscrits" (Localement)
 */
const stageUserForEnrollment = (user) => {
    // Retirer de la liste dispo
    cohortAvailableUsers.value = cohortAvailableUsers.value.filter(u => u.id !== user.id);

    // Ajouter à la liste des inscrits (avec status en attente de sauvegarde)
    cohortEnrolledUsers.value.unshift({
        ...user,
        enrolled_at: t('pages.learning_paths.enrolledAtStaged'),
        progress: 0,
        status: 'Staged',
        is_staged: true // Flag pour l'UI
    });
};

/**
 * Action UI : Retire un étudiant de la cohorte (Demande confirmation)
 */
const stageUserForRemoval = (user) => {
    confirmAction({
        message: t('pages.learning_paths.removeStudentMessage', { name: user.name }),
        header: t('pages.learning_paths.removeStudentHeader'),
        icon: 'pi pi-user-minus',
        danger: true,
        acceptLabel: t('pages.learning_paths.removeStudentAccept'),
        rejectLabel: t('pages.learning_paths.removeStudentReject'),
        accept: () => {
            // Si c'était juste un staging local (pas encore sauvé en BDD)
            if (user.is_staged) {
                cohortEnrolledUsers.value = cohortEnrolledUsers.value.filter(u => u.id !== user.id);
                cohortAvailableUsers.value.unshift(user);
            } else {
                // Vrai retrait
                cohortEnrolledUsers.value = cohortEnrolledUsers.value.filter(u => u.id !== user.id);
                cohortAvailableUsers.value.unshift(user);
                // On garde l'ID pour la soumission serveur
                cohortForm.remove_ids.push(user.id);
            }
            toast.add({ severity: 'info', summary: t('pages.learning_paths.studentRemovedSummary'), detail: t('pages.learning_paths.studentRemovedDetail', { name: user.name }), life: 3000 });
        }
    });
};

/**
 * Action UI : Ajoute TOUS les utilisateurs filtrés
 */
const stageAllFilteredUsers = () => {
    const usersToMove = [...filteredAvailableUsers.value];
    usersToMove.forEach(u => stageUserForEnrollment(u));
    toast.add({ severity: 'success', summary: t('pages.learning_paths.bulkAddSummary'), detail: t('pages.learning_paths.bulkAddDetail', { count: usersToMove.length }), life: 3000 });
};

/**
 * Soumet les modifications de la cohorte au serveur
 */
const saveCohortChanges = () => {
    // Rassembler les IDs des utilisateurs actuellement dans la liste locale des inscrits
    // On ne soumet que ceux qui sont "Staged" (nouveaux) pour l'ajout.
    const newUsers = cohortEnrolledUsers.value.filter(u => u.is_staged).map(u => u.id);

    cohortForm.user_ids = newUsers;

    // Si aucun changement
    if (cohortForm.user_ids.length === 0 && cohortForm.remove_ids.length === 0) {
        toast.add({ severity: 'info', summary: t('pages.learning_paths.noChangesSummary'), detail: t('pages.learning_paths.noChangesDetail'), life: 3000 });
        cohortDialog.value = false;
        return;
    }

    cohortForm.post(route('learning-paths.sync-cohort', selectedPath.value.slug), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.learning_paths.cohortSyncedSummary'), detail: t('pages.learning_paths.cohortSyncedDetail'), life: 4000 });
            cohortDialog.value = false;
            emit('saved');
        },
        onError: (err) => {
            console.error("Sync Error", err);
            toast.add({ severity: 'error', summary: t('pages.learning_paths.syncErrorSummary'), detail: t('pages.learning_paths.syncErrorDetail'), life: 6000 });
        }
    });
};

/**
 * Helper : Formatage du statut de progression d'un étudiant
 */
const getStudentProgressStatus = (status, progress) => {
    if (status === 'Staged') return { label: t('pages.learning_paths.progressPendingAdd'), sev: 'warn', icon: 'pi-clock' };
    if (status === 'Completed' || progress === 100) return { label: t('pages.learning_paths.progressGraduated'), sev: 'success', icon: 'pi-verified' };
    if (status === 'Suspended') return { label: t('pages.learning_paths.progressSuspended'), sev: 'danger', icon: 'pi-lock' };
    if (progress > 0) return { label: t('pages.learning_paths.progressInProgress'), sev: 'info', icon: 'pi-spinner pi-spin' };
    return { label: t('pages.learning_paths.progressNotStarted'), sev: 'secondary', icon: 'pi-circle' };
};

// ======================================================================================
// ANALYTICS COHORTE (GRAPHIQUE)
// ======================================================================================
const cohortChartData = computed(() => ({
    labels: [t('pages.learning_paths.chartWeek', { n: 1 }), t('pages.learning_paths.chartWeek', { n: 2 }), t('pages.learning_paths.chartWeek', { n: 3 }), t('pages.learning_paths.chartWeek', { n: 4 })],
    datasets: [{
        label: t('pages.learning_paths.chartCompletion'),
        data: [10, 25, 45, 78],
        backgroundColor: '#10b981',
        borderRadius: 4
    }]
}));
const cohortChartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { min: 0, max: 100, grid: { color: '#f1f5f9' } } }
};

// ======================================================================================
// EXPOSITION VERS LE PARENT (ORCHESTRATION)
// ======================================================================================
defineExpose({
    open: openCohortManager
});
</script>

<template>
        <Dialog
            v-model:visible="cohortDialog"
            :modal="true"
            :closable="false"
            :draggable="false"
            :style="{ width: '90vw', maxWidth: '1200px' }"
            contentClass="p-0 flex flex-col bg-slate-50 h-[85vh] overflow-hidden rounded-b-2xl"
            headerClass="hidden"
            class="rounded-2xl shadow-2xl border border-slate-200 cohort-dialog"
        >
            <!-- HEADER COHORT MANAGER -->
            <div class="flex-none h-[80px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 relative overflow-hidden">
                <!-- Déco fond -->
                <div class="absolute right-0 top-0 bottom-0 w-64 bg-gradient-to-l from-blue-50 to-transparent pointer-events-none"></div>

                <div class="flex items-center gap-5 relative z-10">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md shadow-blue-500/30">
                        <i class="pi pi-users text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ t('pages.learning_paths.cohortTitle', { name: selectedPath?.name || '' }) }}
                            <Badge :value="cohortEnrolledUsers.length" class="bg-blue-100 text-blue-800 font-black border border-blue-200"></Badge>
                        </h2>
                        <div class="text-xs text-slate-500 mt-1 font-medium flex items-center gap-2">
                            <span>{{ t('pages.learning_paths.cohortSubtitle') }}</span>
                        </div>
                    </div>
                </div>
                <div class="relative z-10">
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text bg-slate-100 hover:bg-slate-200 text-slate-600 w-10 h-10 transition-colors" @click="cohortDialog = false" />
                </div>
            </div>

            <!-- BODY COHORT MANAGER (Tabs Navigation) -->
            <div class="bg-white px-6 border-b border-slate-200 flex items-center gap-6">
                <button @click="cohortActiveTab = 'inscrits'" class="py-4 border-b-2 font-bold text-sm transition-colors relative flex items-center gap-2" :class="cohortActiveTab === 'inscrits' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="pi pi-list"></i> {{ t('pages.learning_paths.tabEnrolled') }}
                </button>
                <button @click="cohortActiveTab = 'ajouter'" class="py-4 border-b-2 font-bold text-sm transition-colors relative flex items-center gap-2" :class="cohortActiveTab === 'ajouter' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="pi pi-user-plus"></i> {{ t('pages.learning_paths.tabAddUsers') }}
                    <span v-if="cohortForm.user_ids.length > 0" class="absolute -top-1 -right-4 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                </button>
                <button @click="cohortActiveTab = 'analytics'" class="py-4 border-b-2 font-bold text-sm transition-colors relative flex items-center gap-2" :class="cohortActiveTab === 'analytics' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                    <i class="pi pi-chart-line"></i> {{ t('pages.learning_paths.tabAnalytics') }}
                </button>
            </div>

            <!-- TAB CONTENT CONTAINER -->
            <div class="flex-1 overflow-hidden relative">

                <!-- SKELETON LOADER -->
                <div v-if="loadingCohort" class="absolute inset-0 z-50 bg-slate-50 p-6 flex flex-col gap-4">
                    <div class="flex justify-between"><Skeleton width="200px" height="2rem" /><Skeleton width="150px" height="2rem" /></div>
                    <Skeleton width="100%" height="80px" class="mt-4 rounded-xl" />
                    <Skeleton width="100%" height="80px" class="rounded-xl" />
                    <Skeleton width="100%" height="80px" class="rounded-xl" />
                </div>

                <!-- TAB 1: LISTE DES INSCRITS (DATATABLE) -->
                <div v-show="cohortActiveTab === 'inscrits' && !loadingCohort" class="h-full flex flex-col bg-slate-50 p-4 md:p-6">
                    <div class="bg-white flex-1 rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">

                        <!-- Toolbar Cohorte -->
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                            <IconField iconPosition="left" class="w-full sm:w-72">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="cohortSearchQuery" :placeholder="t('pages.learning_paths.searchStudentPlaceholder')" class="w-full h-11 bg-white border-slate-200" />
                            </IconField>
                            <Button :label="t('pages.learning_paths.btnEnrollStudent')" icon="pi pi-plus" class="p-button-outlined p-button-sm p-button-info font-bold bg-white" @click="cohortActiveTab = 'ajouter'" />
                        </div>

                        <!-- Tableau des inscrits -->
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <DataTable :value="filteredEnrolledUsers" dataKey="id" class="p-datatable-custom border-none h-full" scrollable scrollHeight="flex">
                                <template #empty>
                                    <div class="p-16 text-center text-slate-500 flex flex-col items-center">
                                        <i class="pi pi-inbox text-5xl text-slate-300 mb-4"></i>
                                        <p class="font-bold">{{ t('pages.learning_paths.emptyStudentsTitle') }}</p>
                                        <p class="text-sm mt-1">{{ t('pages.learning_paths.emptyStudentsHint') }}</p>
                                    </div>
                                </template>

                                <Column :header="t('pages.learning_paths.colLearner')" style="min-width: 20rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3 py-1">
                                            <Avatar :image="data.avatar || `https://ui-avatars.com/api/?name=${data.name}&background=random`" shape="circle" size="large" class="shadow-sm border border-slate-100" />
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                                    {{ data.name }}
                                                    <Tag v-if="data.is_staged" :value="t('pages.learning_paths.badgeNew')" severity="warning" class="text-[8px] px-1 py-0" />
                                                </div>
                                                <div class="text-[11px] text-slate-500">{{ data.email }}</div>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column :header="t('pages.learning_paths.colProgress')" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="w-full max-w-[150px]">
                                            <div class="flex justify-between text-[10px] font-bold text-slate-600 mb-1">
                                                <span>{{ t('pages.learning_paths.progressCompleted', { progress: data.progress }) }}</span>
                                            </div>
                                            <ProgressBar :value="data.progress" :showValue="false" class="h-1.5" :color="data.progress === 100 ? '#10b981' : '#3b82f6'" />
                                        </div>
                                    </template>
                                </Column>

                                <Column :header="t('pages.learning_paths.colStatus')" style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <Tag :severity="getStudentProgressStatus(data.status, data.progress).sev" :icon="`pi ${getStudentProgressStatus(data.status, data.progress).icon}`" :value="getStudentProgressStatus(data.status, data.progress).label" class="text-[10px] font-bold uppercase" />
                                    </template>
                                </Column>

                                <Column headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" :header="t('pages.learning_paths.colEnrollDate')" style="min-width: 10rem">
                                    <template #body="{ data }">
                                        <span class="text-xs text-slate-600 font-medium flex items-center gap-1.5"><i class="pi pi-calendar text-slate-400"></i> {{ data.enrolled_at }}</span>
                                    </template>
                                </Column>

                                <Column style="width: 4rem; text-align: center">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger p-button-sm opacity-50 hover:opacity-100 hover:bg-red-50" v-tooltip.left="t('pages.learning_paths.tooltipRemoveAccess')" @click="stageUserForRemoval(data)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: AJOUTER DES ÉTUDIANTS (DUAL PANE TRANSFER LIST) -->
                <div v-show="cohortActiveTab === 'ajouter' && !loadingCohort" class="h-full flex flex-col p-4 md:p-6 bg-slate-50">

                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-4 flex items-start gap-4 shadow-sm flex-shrink-0">
                        <i class="pi pi-info-circle text-blue-500 text-xl mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-blue-900 text-sm">{{ t('pages.learning_paths.transferTitle') }}</h4>
                            <p class="text-xs text-blue-700 mt-1 leading-relaxed" v-html="t('pages.learning_paths.transferDesc')"></p>
                        </div>
                    </div>

                    <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-0">
                        <!-- Pane Gauche : Base Globale -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                            <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                                <h3 class="font-bold text-slate-700 text-sm"><i class="pi pi-database mr-2 text-slate-400"></i>{{ t('pages.learning_paths.userDbTitle') }}</h3>
                                <Button :label="t('pages.learning_paths.btnAddAll')" icon="pi pi-check-circle" class="p-button-text p-button-sm p-0 text-xs font-bold" @click="stageAllFilteredUsers" v-if="filteredAvailableUsers.length > 0" />
                            </div>
                            <div class="p-3 border-b border-slate-100">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search text-slate-400" />
                                    <InputText v-model="globalUserSearchQuery" :placeholder="t('pages.learning_paths.searchUserPlaceholder')" class="w-full h-11 text-sm bg-slate-50 border-slate-200" />
                                </IconField>
                            </div>
                            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50/30 custom-scrollbar">
                                <div v-if="filteredAvailableUsers.length === 0" class="text-center p-6 text-sm text-slate-400 italic">{{ t('pages.learning_paths.noAvailableUsers') }}</div>

                                <CohortUserItem
                                    v-for="user in filteredAvailableUsers"
                                    :key="'avail_'+user.id"
                                    :user="user"
                                    @add="stageUserForEnrollment"
                                />
                            </div>
                        </div>

                        <!-- Pane Droite : Inscrits Staged -->
                        <div class="bg-white rounded-2xl border-2 border-blue-200 shadow-sm flex flex-col overflow-hidden relative">
                            <div class="p-4 border-b border-slate-100 bg-blue-50 flex justify-between items-center">
                                <h3 class="font-black text-blue-900 text-sm"><i class="pi pi-users mr-2"></i>{{ t('pages.learning_paths.targetCohort') }}</h3>
                                <Badge :value="cohortEnrolledUsers.length" severity="info" class="bg-blue-200 text-blue-800" />
                            </div>
                            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50 custom-scrollbar relative">
                                <div v-if="cohortEnrolledUsers.length === 0" class="h-full flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-slate-300 rounded-xl m-2">
                                    <i class="pi pi-user-plus text-4xl text-slate-300 mb-3"></i>
                                    <p class="font-bold text-slate-600 text-sm">{{ t('pages.learning_paths.emptyCohort') }}</p>
                                </div>

                                <CohortUserItem
                                    v-for="user in cohortEnrolledUsers"
                                    :key="'staged_'+user.id"
                                    :user="user"
                                    type="enrolled"
                                    @remove="stageUserForRemoval"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ANALYTICS (Vue simplifiée pour le volume de lignes) -->
                <div v-show="cohortActiveTab === 'analytics' && !loadingCohort" class="h-full overflow-y-auto p-6 bg-slate-50 custom-scrollbar">
                    <div class="max-w-4xl mx-auto space-y-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm text-center">
                                <div class="text-2xl sm:text-3xl font-black text-blue-600 mb-1">{{ cohortEnrolledUsers.length }}</div>
                                <div class="text-xs font-bold text-slate-500 uppercase">{{ t('pages.learning_paths.analyticsTotalStudents') }}</div>
                            </div>
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm text-center">
                                <div class="text-2xl sm:text-3xl font-black text-emerald-500 mb-1">
                                    {{ Math.round(cohortEnrolledUsers.filter(u => u.progress === 100).length / (cohortEnrolledUsers.length || 1) * 100) }}%
                                </div>
                                <div class="text-xs font-bold text-slate-500 uppercase">{{ t('pages.learning_paths.analyticsGraduatesRate') }}</div>
                            </div>
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm text-center">
                                <div class="text-2xl sm:text-3xl font-black text-amber-500 mb-1">45h</div>
                                <div class="text-xs font-bold text-slate-500 uppercase">{{ t('pages.learning_paths.analyticsAvgTime') }}</div>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-[300px]">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-4">{{ t('pages.learning_paths.cohortProgressTitle') }}</h4>
                            <Chart type="bar" :data="cohortChartData" :options="cohortChartOptions" class="h-[220px]" />
                        </div>
                    </div>
                </div>

            </div>

            <!-- FOOTER COHORT MANAGER -->
            <div class="flex-none h-[80px] bg-white border-t border-slate-200 flex justify-between items-center px-6 z-30 shadow-[0_-5px_15px_rgba(0,0,0,0.03)]">
                <div class="text-xs text-slate-500 font-medium flex items-center gap-2">
                    <i class="pi pi-info-circle"></i>
                    <span v-if="cohortForm.user_ids.length > 0 || cohortForm.remove_ids.length > 0" class="text-amber-600 font-bold">{{ t('pages.learning_paths.pendingChanges') }}</span>
                    <span v-else>{{ t('pages.learning_paths.cohortUpToDate') }}</span>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('pages.learning_paths.btnClose')" class="p-button-text p-button-secondary font-bold" @click="cohortDialog = false" />
                    <Button
                        :label="t('pages.learning_paths.btnSaveAccess')"
                        icon="pi pi-save"
                        class="p-button-primary bg-blue-600 border-blue-600 hover:bg-blue-700 font-bold px-6 shadow-md transition-transform hover:-translate-y-0.5"
                        @click="saveCohortChanges"
                        :loading="cohortForm.processing"

                    />
                </div>
            </div>
        </Dialog>
</template>
