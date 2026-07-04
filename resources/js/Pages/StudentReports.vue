<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE ANALYTICS: STUDENT INTELLIGENCE HUB
 * ======================================================================================
 * Orchestrateur de données haute performance pour le monitoring des apprenants.
 *
 * @version 12.4.0 (Build 2024.07)
 * @author  SubnetCongo Framework Division <engineering@subnetcongo.com>
 * @license Enterprise Proprietary
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import debounce from 'lodash/debounce';

// --- PRIME VUE CORE ---
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
import Calendar from 'primevue/calendar';
import OverlayPanel from 'primevue/overlaypanel';
import Sidebar from 'primevue/sidebar';
import Menu from 'primevue/menu';
import Tooltip from 'primevue/tooltip';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

// --- PROPS & STATE ---
const props = defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    courses: { type: Array, default: () => [] }, // Liste des cours pour le filtre
    stats: { type: Object, default: () => ({
        avg_score: 78,
        active_now: 45,
        total_certifications: 124,
        at_risk_students: 12
    })}
});

const toast = useToast();
const isLoading = ref(false);
const displayMode = ref('table'); // 'table' | 'grid'
const showQuickView = ref(false);
const selectedStudent = ref(null);
const selectedStudents = ref([]); // Pour Bulk Actions
const op = ref(); // OverlayPanel ref

// --- FILTERS STATE ---
const filters = ref({
    search: props.filters.search || '',
    course: props.filters.course || null,
    status: props.filters.status || null,
    dateRange: props.filters.dateRange || null,
    performance: props.filters.performance || null
});

// --- CONSTANTES ---
const statusOptions = [
    { label: 'Actif', value: 'active', severity: 'success' },
    { label: 'Inactif', value: 'inactive', severity: 'danger' },
    { label: 'En pause', value: 'paused', severity: 'warning' }
];

const perfOptions = [
    { label: 'Excellence (>90%)', value: 'high' },
    { label: 'Moyenne (50-80%)', value: 'mid' },
    { label: 'En danger (<50%)', value: 'low' }
];

// --- BUSINESS LOGIC ---

/**
 * Synchronisation globale de l'URL avec les filtres (Debounced)
 */
const syncData = debounce(() => {
    isLoading.value = true;
    router.get(route('student-reports.index'), {
        ...filters.value,
        page: props.students.current_page
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
}, 400);

watch(filters, () => syncData(), { deep: true });

/**
 * Gestion du QuickView (Aperçu latéral)
 */
const openQuickView = (student) => {
    selectedStudent.value = student;
    showQuickView.value = true;
};

/**
 * Export de données (Mock)
 */
const exportCSV = () => {
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'La génération du rapport Excel a commencé...', life: 3000 });
};

/**
 * Logique de calcul des couleurs de performance
 */
const getScoreSeverity = (score) => {
    if (score >= 80) return 'success';
    if (score >= 50) return 'warning';
    return 'danger';
};

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const items = ref([
    { label: 'Intelligence Engine', disabled: true },
    { label: 'Student Analytics', active: true }
]);

</script>

<template>
    <AppLayout>
        <Head title="Intelligence Hub - Rapports Étudiants" />
        <Toast />

        <div class="min-h-screen bg-[#F8FAFC] pb-20">

            <!-- HEADER SECTION (Glassmorphism Effect) -->
            <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200 px-6 py-4">
                <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <Breadcrumb :home="home" :model="items" class="p-0 bg-transparent mb-2 text-xs" />
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-200">
                                <i class="pi pi-chart-bar text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Student Intelligence Hub</h1>
                                <p class="text-slate-500 text-sm font-medium mt-1">Surveillance analytique en temps réel des cohortes</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-file-export" label="Exporter" class="p-button-outlined p-button-secondary font-bold" @click="exportCSV" />
                        <Button icon="pi pi-plus" label="Nouvelle Analyse" class="font-black shadow-xl shadow-indigo-100" />
                    </div>
                </div>
            </header>

            <main class="max-w-[1600px] mx-auto p-6 space-y-6">

                <!-- 1. KPI STATS GRID -->
                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(stat, i) in [
                        { label: 'Moyenne Globale', val: stats.avg_score + '%', icon: 'pi-percentage', color: 'indigo', trend: '+2.4%' },
                        { label: 'Apprenants Actifs', val: stats.active_now, icon: 'pi-users', color: 'emerald', trend: '+12' },
                        { label: 'Certifications', val: stats.total_certifications, icon: 'pi-verified', color: 'blue', trend: 'Record' },
                        { label: 'Profils à Risque', val: stats.at_risk_students, icon: 'pi-exclamation-triangle', color: 'rose', trend: '-3%' }
                    ]" :key="i" class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div :class="`p-4 bg-${stat.color}-50 rounded-2xl text-${stat.color}-600 transition-transform group-hover:scale-110` ">
                                <i :class="`pi ${stat.icon} text-xl`"></i>
                            </div>
                            <Tag :value="stat.trend" :severity="stat.color === 'rose' ? 'danger' : 'success'" class="font-bold" />
                        </div>
                        <p class="text-slate-500 font-bold text-xs uppercase tracking-widest">{{ stat.label }}</p>
                        <h2 class="text-3xl font-black text-slate-900 mt-1">{{ stat.val }}</h2>
                    </div>
                </section>

                <!-- 2. ADVANCED FILTERS BAR -->
                <section class="bg-white p-4 rounded-[1.5rem] border border-slate-200 flex flex-wrap items-center gap-4 shadow-sm">
                    <div class="flex-1 min-w-[280px]">
                        <span class="p-input-icon-left w-full">
                            <i class="pi pi-search text-slate-400" />
                            <InputText v-model="filters.search" placeholder="Rechercher un ID, un nom ou une promotion..." class="w-full border-none bg-slate-50 rounded-xl focus:ring-2 focus:ring-indigo-500" />
                        </span>
                    </div>

                    <Dropdown v-model="filters.course" :options="courses" optionLabel="name" placeholder="Filtrer par cours" class="w-full md:w-56 bg-slate-50 border-none rounded-xl" />

                    <MultiSelect v-model="filters.status" :options="statusOptions" optionLabel="label" placeholder="Statut" class="w-full md:w-48 bg-slate-50 border-none rounded-xl" />

                    <div class="h-8 w-[1px] bg-slate-200 hidden lg:block"></div>

                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-filter-slash" class="p-button-text p-button-secondary" v-tooltip.bottom="'Réinitialiser'" />
                        <div class="flex bg-slate-100 p-1 rounded-xl">
                            <Button icon="pi pi-list" :class="displayMode === 'table' ? 'bg-white shadow-sm' : 'p-button-text'" @click="displayMode = 'table'" />
                            <Button icon="pi pi-th-large" :class="displayMode === 'grid' ? 'bg-white shadow-sm' : 'p-button-text'" @click="displayMode = 'grid'" />
                        </div>
                    </div>
                </section>

                <!-- 3. DATA TABLE MODE -->
                <div v-if="displayMode === 'table'" class="bg-white rounded-[2rem] border border-slate-200 shadow-xl overflow-hidden relative">

                    <!-- Loading Overlay -->
                    <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex items-center justify-center">
                        <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
                    </div>

                    <!-- Batch Action Bar (Visible if selection) -->
                    <div v-if="selectedStudents.length > 0" class="bg-indigo-900 p-4 flex items-center justify-between animate-fade-in">
                        <span class="text-white font-bold">{{ selectedStudents.length }} Étudiants sélectionnés</span>
                        <div class="flex gap-2">
                            <Button label="Envoyer Message" icon="pi pi-envelope" class="p-button-sm p-button-success" />
                            <Button label="Certifier" icon="pi pi-verified" class="p-button-sm p-button-info" />
                            <Button label="Désinscrire" icon="pi pi-trash" class="p-button-sm p-button-danger" />
                        </div>
                    </div>

                    <DataTable
                        :value="students.data"
                        v-model:selection="selectedStudents"
                        dataKey="id"
                        responsiveLayout="stack"
                        breakpoint="960px"
                        class="enterprise-table"
                        :rows="10"
                    >
                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column header="Profil Étudiant" class="py-4">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4 group cursor-pointer" @click="openQuickView(data)">
                                    <div class="relative">
                                        <Avatar :image="data.avatar || `https://ui-avatars.com/api/?name=${data.name}&background=random`" shape="circle" size="xlarge" class="shadow-md border-2 border-white group-hover:border-indigo-400 transition-all" />
                                        <div class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white bg-emerald-500" v-if="data.is_online"></div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-800 text-lg group-hover:text-indigo-600">{{ data.name }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs font-mono text-slate-400">ID: #{{ data.id.toString().padStart(5, '0') }}</span>
                                            <span class="h-1 w-1 bg-slate-300 rounded-full"></span>
                                            <span class="text-xs text-slate-500 font-bold uppercase tracking-tighter">{{ data.email }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Performance Globale" style="width: 250px">
                            <template #body="{ data }">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-black text-slate-400 uppercase">Score Moyen</span>
                                        <span :class="`font-black text-${getScoreSeverity(data.avg_score)}-600` ">{{ data.avg_score }}%</span>
                                    </div>
                                    <ProgressBar :value="data.avg_score" :showValue="false" :style="{height: '8px'}" :class="`perf-bar-${getScoreSeverity(data.avg_score)}` " />
                                </div>
                            </template>
                        </Column>

                        <Column header="Engagement" class="text-center">
                            <template #body="{ data }">
                                <div class="flex flex-col items-center">
                                    <span class="text-xl font-black text-slate-700">{{ data.submissions_count }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Activité</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Dernière Connexion">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-600">{{ data.last_active_human || 'Il y a 2h' }}</span>
                                    <Tag severity="info" value="IP: 192.168.1.1" class="text-[9px] w-fit mt-1 opacity-50" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" bodyClass="text-right">
                            <template #body="{ data }">
                                <div class="flex justify-end gap-2">
                                    <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Aperçu rapide'" @click="openQuickView(data)" />
                                    <Link :href="route('student-reports.show', data.id)">
                                        <Button icon="pi pi-chart-line" label="Dossier Complet" class="p-button-rounded p-button-outlined p-button-sm font-black" />
                                    </Link>
                                    <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-secondary" @click="(e) => op.toggle(e)" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="py-20 text-center">
                                <div class="relative inline-block mb-6">
                                    <i class="pi pi-users text-8xl text-slate-100"></i>
                                    <i class="pi pi-search absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-3xl text-slate-300"></i>
                                </div>
                                <h3 class="text-2xl font-black text-slate-800">Aucun étudiant ne correspond</h3>
                                <p class="text-slate-500 max-w-sm mx-auto mt-2 font-medium">Modifiez vos filtres ou effectuez une recherche globale pour trouver des apprenants.</p>
                                <Button label="Réinitialiser tout" class="mt-6 p-button-outlined" />
                            </div>
                        </template>
                    </DataTable>

                    <!-- Custom Pagination Footer -->
                    <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">
                            Affichage de {{ (students.current_page - 1) * students.per_page + 1 }} à {{ Math.min(students.current_page * students.per_page, students.total) }} sur {{ students.total }} enregistrements
                        </p>
                        <Paginator
                            :rows="students.per_page"
                            :totalRecords="students.total"
                            :first="(students.current_page - 1) * students.per_page"
                            @page="(e) => router.get(students.path, { page: e.page + 1, ...filters })"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                            class="bg-transparent border-none"
                        />
                    </div>
                </div>

                <!-- 4. GRID MODE (Alternative view) -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 animate-fade-in">
                    <div v-for="student in students.data" :key="student.id" class="bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all relative group overflow-hidden">
                        <div class="absolute top-0 right-0 p-4">
                             <Button icon="pi pi-ellipsis-h" class="p-button-text p-button-secondary" />
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <Avatar :image="student.avatar" size="xlarge" shape="circle" class="w-24 h-24 shadow-2xl border-4 border-white mb-4" />
                            <h3 class="text-xl font-black text-slate-800">{{ student.name }}</h3>
                            <span class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-6">Expert UI/UX Designer</span>

                            <div class="w-full grid grid-cols-2 gap-4 mb-8">
                                <div class="bg-slate-50 p-3 rounded-2xl">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Progrès</p>
                                    <p class="text-lg font-black text-slate-700">{{ student.avg_score }}%</p>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-2xl">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">Badges</p>
                                    <p class="text-lg font-black text-slate-700">12</p>
                                </div>
                            </div>

                            <Link :href="route('student-reports.show', student.id)" class="w-full">
                                <Button label="Rapport de Performance" class="w-full p-4 font-black rounded-2xl shadow-lg shadow-indigo-50" />
                            </Link>
                        </div>
                    </div>
                </div>

            </main>

            <!-- QUICK VIEW SIDEBAR -->
            <Sidebar v-model:visible="showQuickView" position="right" class="w-full md:w-[500px] p-sidebar-lg border-l border-slate-200">
                <template #header>
                    <div class="flex items-center gap-3">
                        <i class="pi pi-user-edit text-indigo-600 text-xl"></i>
                        <span class="font-black text-xl uppercase tracking-tighter">Student Profile Preview</span>
                    </div>
                </template>
                <div v-if="selectedStudent" class="py-6 space-y-8">
                    <!-- Bio Section -->
                    <div class="bg-slate-900 p-8 rounded-[2rem] text-white relative overflow-hidden">
                        <div class="relative z-10 flex flex-col items-center">
                            <Avatar :image="selectedStudent.avatar" size="xlarge" shape="circle" class="w-32 h-32 border-4 border-white/20 mb-4" />
                            <h2 class="text-2xl font-black">{{ selectedStudent.name }}</h2>
                            <p class="text-slate-400 font-medium">{{ selectedStudent.email }}</p>
                        </div>
                        <div class="absolute top-0 left-0 w-full h-full opacity-10 pattern-dots translate-x-1 translate-y-1"></div>
                    </div>

                    <!-- Skills / Performance Radars -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest px-4">Analyse des compétences</h4>
                        <div class="bg-white border border-slate-200 rounded-3xl p-6 space-y-6">
                            <div v-for="skill in [
                                { label: 'Théorie & Concepts', val: 85, col: 'indigo' },
                                { label: 'Travaux Pratiques', val: 42, col: 'rose' },
                                { label: 'Assiduité', val: 98, col: 'emerald' }
                            ]" :key="skill.label">
                                <div class="flex justify-between mb-2">
                                    <span class="font-bold text-slate-700 text-sm">{{ skill.label }}</span>
                                    <span class="font-black text-slate-900">{{ skill.val }}%</span>
                                </div>
                                <ProgressBar :value="skill.val" :showValue="false" :style="{height: '6px'}" :class="`perf-bar-${skill.col}` " />
                            </div>
                        </div>
                    </div>

                    <!-- Recent Timeline -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest px-4">Activité récente</h4>
                        <div class="flex flex-col gap-4">
                            <div v-for="i in 3" :key="i" class="flex gap-4 p-4 hover:bg-slate-50 rounded-2xl transition-colors">
                                <div class="h-10 w-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-indigo-500 shadow-sm">
                                    <i class="pi pi-check"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800 leading-tight">Examen Complété : Intelligence Artificielle</p>
                                    <p class="text-xs text-slate-400 mt-1">Le 02 Juillet 2024 • Score 18/20</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-8">
                        <Button label="Imprimer le bulletin officiel" icon="pi pi-print" class="w-full py-5 text-lg font-black rounded-3xl shadow-xl shadow-slate-100" />
                    </div>
                </div>
            </Sidebar>

            <!-- CONTEXT MENU FOR ROW -->
            <OverlayPanel ref="op">
                <div class="flex flex-col gap-1 w-48">
                    <Button icon="pi pi-envelope" label="Envoyer Email" class="p-button-text p-button-sm justify-start text-slate-600 font-bold" />
                    <Button icon="pi pi-bell" label="Notifier" class="p-button-text p-button-sm justify-start text-slate-600 font-bold" />
                    <Button icon="pi pi-lock" label="Bloquer l'accès" class="p-button-text p-button-sm justify-start p-button-danger font-bold" />
                </div>
            </OverlayPanel>

        </div>
    </AppLayout>
</template>

<style scoped>
/*
 * ======================================================================================
 * SYSTEM DESIGN SYSTEM CUSTOM OVERRIDES
 * ======================================================================================
 */

/* Enterprise DataTable Styles */
:deep(.enterprise-table) {
    border: none;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: all 0.2s ease;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #fdfdff !important;
    transform: scale(0.995);
    box-shadow: inset 4px 0 0 #6366f1;
}

:deep(.p-datatable-tbody > tr > td) {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

/* Custom Progress Bars Colors */
:deep(.perf-bar-success .p-progressbar-value) { background: #10b981; }
:deep(.perf-bar-warning .p-progressbar-value) { background: #f59e0b; }
:deep(.perf-bar-danger .p-progressbar-value) { background: #ef4444; }
:deep(.perf-bar-indigo .p-progressbar-value) { background: #6366f1; }
:deep(.perf-bar-rose .p-progressbar-value) { background: #f43f5e; }
:deep(.perf-bar-emerald .p-progressbar-value) { background: #10b981; }

/* Animation Keyframes */
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Scrollbar Customization */
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

/* Input Fields Styles */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-multiselect) {
    box-shadow: none !important;
}

:deep(.p-paginator) {
    border: none;
    background: transparent;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: #6366f1;
    color: white;
    border-radius: 12px;
}

:deep(.p-paginator .p-paginator-page) {
    min-width: 40px;
    height: 40px;
    border-radius: 12px;
    font-weight: 800;
}
</style>
