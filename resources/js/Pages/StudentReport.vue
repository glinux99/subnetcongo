<script setup lang="ts">
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ADVANCED STUDENT ANALYTICS DASHBOARD v2.0
 * ======================================================================================
 * Tableau de bord analytique Premium.
 * UI/UX améliorée avec : Onglets, Timeline, Radar Chart, Filtrage, Tendances, Skeletons.
 *
 * @author SubnetCongo Framework Division
 */

import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// Composants de Layout & UI
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Breadcrumb from 'primevue/breadcrumb';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import InputText from 'primevue/inputtext';
import Timeline from 'primevue/timeline';
import Skeleton from 'primevue/skeleton';

// --- INTERFACES TYPESCRIPT ---
interface Exam {
    id: number;
    title: string;
    status: 'Réussi' | 'Échoué' | 'En attente';
    score: number | null;
    attempted_at: string;
}

interface Course {
    id: number;
    title: string;
    exams: Exam[];
}

interface StudentProps {
    student: { id: number; name: string; avatar?: string; email: string; enrollment_date: string };
    globalStats: {
        average_score: number; score_trend: number;
        success_rate: number; success_trend: number;
        completed_exams: number;
        total_attempts: number;
    };
    coursesData: Course[];
    performanceOverTime: { labels: string[]; scores: number[] };
    skillsData: { labels: string[]; scores: number[] }; // Nouveau pour le Radar Chart
    activityLog: { status: string; date: string; icon: string; color: string; description: string }[]; // Nouveau pour Timeline
}

const props = defineProps<StudentProps>();
const toast = useToast();

// --- STATE ---
const isLoading = ref(true); // Simule le chargement pour l'UX
const searchQuery = ref('');
const exportMenu = ref();

// --- COMPOSANTS & LOGIQUE ---
onMounted(() => {
    // Simule un délai réseau pour montrer l'animation de chargement (UX Premium)
    setTimeout(() => { isLoading.value = false; }, 800);
});

// Menu d'exportation
const exportItems = ref([
    { label: 'Exporter en PDF', icon: 'pi pi-file-pdf', command: () => triggerExport('PDF') },
    { label: 'Exporter en Excel', icon: 'pi pi-file-excel', command: () => triggerExport('Excel') }
]);

const toggleExportMenu = (event: Event) => { exportMenu.value.toggle(event); };

const triggerExport = (format: string) => {
    toast.add({ severity: 'info', summary: 'Génération en cours', detail: `Le fichier ${format} est en cours de création...`, life: 3000 });
};

// Filtrage intelligent des cours et examens
const filteredCourses = computed(() => {
    if (!searchQuery.value) return props.coursesData;
    const lowerQuery = searchQuery.value.toLowerCase();

    return props.coursesData.map(course => {
        const filteredExams = course.exams.filter(exam => exam.title.toLowerCase().includes(lowerQuery));
        return { ...course, exams: filteredExams };
    }).filter(course => course.exams.length > 0 || course.title.toLowerCase().includes(lowerQuery));
});

// --- CONFIGURATION DES GRAPHIQUES (CHART.JS) ---

// 1. Graphique d'évolution (Line Chart avec dégradé simulé)
const lineChartData = computed(() => ({
    labels: props.performanceOverTime.labels,
    datasets: [{
        label: 'Score Obtenu (%)',
        data: props.performanceOverTime.scores,
        fill: true,
        borderColor: '#4F46E5', // Indigo 600
        backgroundColor: 'rgba(79, 70, 229, 0.08)',
        tension: 0.4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#4F46E5',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
    }]
}));

const lineChartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false, backgroundColor: 'rgba(15, 23, 42, 0.9)', titleColor: '#fff', padding: 12 }
    },
    scales: {
        y: { min: 0, max: 100, grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { color: '#64748b' } },
        x: { grid: { display: false }, ticks: { color: '#64748b' } }
    },
    interaction: { mode: 'nearest', axis: 'x', intersect: false }
};

// 2. Graphique Radar des compétences (Nouveau)
const radarChartData = computed(() => ({
    labels: props.skillsData?.labels || ['Prog.', 'Réseau', 'Sécurité', 'Cloud', 'BDD', 'Web'],
    datasets: [{
        label: 'Niveau de maîtrise',
        data: props.skillsData?.scores || [80, 65, 90, 75, 85, 95],
        backgroundColor: 'rgba(16, 185, 129, 0.2)', // Emerald 500
        borderColor: '#10B981',
        pointBackgroundColor: '#10B981',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#10B981'
    }]
}));

const radarChartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { r: { angleLines: { color: '#f1f5f9' }, grid: { color: '#f1f5f9' }, pointLabels: { color: '#64748b', font: { size: 11 } }, ticks: { display: false, min: 0, max: 100 } } }
};

// --- UTILITAIRES ---
const getStatusProps = (status: string) => {
    const map: Record<string, { severity: string, icon: string }> = {
        'Réussi': { severity: 'success', icon: 'pi pi-check-circle' },
        'Échoué': { severity: 'danger', icon: 'pi pi-times-circle' },
        'En attente': { severity: 'warning', icon: 'pi pi-clock' }
    };
    return map[status] || { severity: 'secondary', icon: 'pi pi-info-circle' };
};
</script>

<template>
    <AppLayout>
        <Head :title="`Analytics | ${student.name}`" />

        <div class="min-h-screen bg-slate-50 p-4 md:p-6 lg:p-8 font-sans">
            <div class="max-w-[1400px] mx-auto">

                <!-- EN-TÊTE DU PROFIL (HEADER) -->
                <div class="mb-8">
                    <Breadcrumb :model="[{ label: 'Analytics', icon: 'pi pi-chart-bar' }, { label: 'Apprenants' }, { label: student.name }]" class="bg-transparent p-0 text-sm mb-4" />

                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col md:flex-row items-center md:items-start justify-between gap-6 relative overflow-hidden">
                        <!-- Décoration de fond -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-50 rounded-full blur-3xl -mr-20 -mt-20 opacity-60"></div>

                        <div class="flex flex-col md:flex-row items-center md:items-start gap-6 z-10">
                            <div class="relative">
                                <Avatar :image="student.avatar || `https://ui-avatars.com/api/?name=${student.name}&background=4F46E5&color=fff`" size="xlarge" shape="circle" class="w-24 h-24 shadow-md border-4 border-white" />
                                <div class="absolute bottom-1 right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full" v-tooltip.bottom="'Étudiant Actif'"></div>
                            </div>
                            <div class="text-center md:text-left">
                                <h1 class="text-3xl font-black text-slate-800 tracking-tight">{{ student.name }}</h1>
                                <p class="text-slate-500 font-medium flex items-center justify-center md:justify-start gap-2 mt-1">
                                    <i class="pi pi-envelope text-sm"></i> {{ student.email }}
                                </p>
                                <div class="flex gap-2 mt-4 justify-center md:justify-start">
                                    <Tag severity="info" value="Inscrit en 2024" rounded></Tag>
                                    <Tag severity="success" value="Parcours Premium" rounded></Tag>
                                </div>
                            </div>
                        </div>

                        <!-- Actions d'export -->
                        <div class="z-10 w-full md:w-auto flex justify-center">
                            <Button type="button" label="Exporter le rapport" icon="pi pi-cloud-download" @click="toggleExportMenu" aria-haspopup="true" aria-controls="overlay_menu" class="p-button-outlined p-button-secondary rounded-xl font-bold w-full md:w-auto" />
                            <Menu ref="exportMenu" id="overlay_menu" :model="exportItems" :popup="true" />
                        </div>
                    </div>
                </div>

                <!-- ETAT DE CHARGEMENT SKELETON (UX) -->
                <div v-if="isLoading" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <Skeleton height="8rem" borderRadius="16px" v-for="i in 4" :key="`skel-kpi-${i}`"></Skeleton>
                    </div>
                    <Skeleton height="24rem" borderRadius="16px"></Skeleton>
                </div>

                <!-- CONTENU PRINCIPAL (TAB SYSTEM) -->
                <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <TabView class="custom-tabview">

                        <!-- ONGLET 1 : VUE D'ENSEMBLE (DASHBOARD) -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-2 py-1"><i class="pi pi-th-large"></i><span class="font-bold">Vue d'ensemble</span></div>
                            </template>

                            <div class="p-4 md:p-6 bg-slate-50/50">
                                <!-- Ligne des KPIs -->
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

                                    <!-- Score Moyen -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] hover:-translate-y-1 transition-transform duration-300">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl"><i class="pi pi-percentage"></i></div>
                                            <Tag :severity="globalStats.score_trend > 0 ? 'success' : 'danger'" rounded>
                                                <i :class="globalStats.score_trend > 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-xs mr-1"></i> {{ Math.abs(globalStats.score_trend) }}%
                                            </Tag>
                                        </div>
                                        <h4 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Score Moyen</h4>
                                        <div class="text-3xl font-black text-slate-800 mt-1">{{ globalStats.average_score }} <span class="text-lg text-slate-400 font-medium">/ 100</span></div>
                                    </div>

                                    <!-- Taux de réussite -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] hover:-translate-y-1 transition-transform duration-300">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl"><i class="pi pi-check-circle"></i></div>
                                            <Tag :severity="globalStats.success_trend >= 0 ? 'success' : 'warning'" rounded>
                                                <i :class="globalStats.success_trend >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" class="text-xs mr-1"></i> {{ Math.abs(globalStats.success_trend) }}%
                                            </Tag>
                                        </div>
                                        <h4 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Taux de Réussite</h4>
                                        <div class="text-3xl font-black text-slate-800 mt-1">{{ globalStats.success_rate }}%</div>
                                    </div>

                                    <!-- Examens passés -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] hover:-translate-y-1 transition-transform duration-300">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl"><i class="pi pi-book"></i></div>
                                        </div>
                                        <h4 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Examens Validés</h4>
                                        <div class="text-3xl font-black text-slate-800 mt-1">{{ globalStats.completed_exams }}</div>
                                    </div>

                                    <!-- Tentatives -->
                                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] hover:-translate-y-1 transition-transform duration-300">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xl"><i class="pi pi-sync"></i></div>
                                        </div>
                                        <h4 class="text-slate-400 text-sm font-bold uppercase tracking-wider">Total Tentatives</h4>
                                        <div class="text-3xl font-black text-slate-800 mt-1">{{ globalStats.total_attempts }}</div>
                                    </div>

                                </div>

                                <!-- Section Graphiques -->
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Courbe de progression (Prend 2/3 de l'espace) -->
                                    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                        <div class="flex justify-between items-center mb-6">
                                            <h3 class="text-lg font-bold text-slate-800">Évolution Globale</h3>
                                            <Button icon="pi pi-external-link" text rounded aria-label="Agrandir" />
                                        </div>
                                        <div class="h-[300px]">
                                            <Chart type="line" :data="lineChartData" :options="lineChartOptions" class="h-full" />
                                        </div>
                                    </div>

                                    <!-- Radar de compétences (Prend 1/3) -->
                                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                        <h3 class="text-lg font-bold text-slate-800 mb-6 text-center">Cartographie des Compétences</h3>
                                        <div class="h-[280px] flex items-center justify-center">
                                            <Chart type="radar" :data="radarChartData" :options="radarChartOptions" class="w-full h-full" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- ONGLET 2 : RÉSULTATS DÉTAILLÉS (DATATABLES) -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-2 py-1"><i class="pi pi-list"></i><span class="font-bold">Relevé de notes</span></div>
                            </template>

                            <div class="p-4 md:p-6">
                                <!-- Barre d'outils -->
                                <div class="flex flex-col md:flex-row justify-between items-center bg-slate-50 p-4 rounded-xl mb-6 gap-4 border border-slate-100">
                                    <span class="p-input-icon-left w-full md:w-96">
                                        <i class="pi pi-search text-slate-400" />
                                        <InputText v-model="searchQuery" placeholder="Rechercher un examen ou un cours..." class="w-full rounded-lg border-slate-200" />
                                    </span>
                                    <Button icon="pi pi-filter" label="Filtres avancés" outlined class="p-button-secondary rounded-lg w-full md:w-auto" />
                                </div>

                                <!-- Liste des cours et examens -->
                                <div class="space-y-8">
                                    <div v-for="course in filteredCourses" :key="course.id" class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

                                        <!-- Header du cours -->
                                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                                            <h4 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                                <div class="w-8 h-8 rounded bg-indigo-600 text-white flex items-center justify-center text-sm shadow-sm"><i class="pi pi-folder-open"></i></div>
                                                {{ course.title }}
                                            </h4>
                                            <Tag :value="`${course.exams.length} examens`" severity="secondary" rounded />
                                        </div>

                                        <!-- Tableau des examens -->
                                        <DataTable :value="course.exams" responsiveLayout="stack" breakpoint="768px" class="p-datatable-lg border-none" hoverRows>

                                            <Column field="title" header="Évaluation">
                                                <template #body="{ data }">
                                                    <span class="font-bold text-slate-700">{{ data.title }}</span>
                                                </template>
                                            </Column>

                                            <Column field="status" header="Statut">
                                                <template #body="{ data }">
                                                    <Tag :severity="getStatusProps(data.status).severity" :icon="getStatusProps(data.status).icon" :value="data.status" rounded />
                                                </template>
                                            </Column>

                                            <Column field="score" header="Score Obtenu">
                                                <template #body="{ data }">
                                                    <div v-if="data.score !== null" class="flex items-center gap-3">
                                                        <ProgressBar :value="data.score" :showValue="false" class="h-2 w-32" :color="data.score >= 50 ? '#10B981' : '#EF4444'" />
                                                        <span class="font-bold text-slate-700 w-10">{{ data.score }}%</span>
                                                    </div>
                                                    <span v-else class="text-slate-400 italic text-sm">Non noté</span>
                                                </template>
                                            </Column>

                                            <Column field="attempted_at" header="Passé le">
                                                <template #body="{ data }">
                                                    <span class="text-sm font-medium text-slate-500">
                                                        <i class="pi pi-calendar mr-1 text-xs"></i> {{ data.attempted_at || '---' }}
                                                    </span>
                                                </template>
                                            </Column>

                                            <Column headerStyle="width: 5rem" bodyStyle="text-align: center">
                                                <template #body>
                                                    <Button icon="pi pi-angle-right" text rounded class="text-slate-400 hover:text-indigo-600" v-tooltip.left="'Voir la copie'" />
                                                </template>
                                            </Column>

                                            <template #empty>
                                                <div class="p-6 text-center text-slate-500">Aucun examen trouvé pour les critères actuels.</div>
                                            </template>

                                        </DataTable>
                                    </div>

                                    <!-- Empty State Global -->
                                    <div v-if="filteredCourses.length === 0" class="text-center py-16 px-4">
                                        <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="pi pi-search text-4xl text-slate-300"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-700">Aucun résultat</h3>
                                        <p class="text-slate-500 mt-2">Modifiez votre recherche pour trouver un cours ou un examen.</p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- ONGLET 3 : CHRONOLOGIE (TIMELINE) -->
                        <TabPanel>
                            <template #header>
                                <div class="flex items-center gap-2 px-2 py-1"><i class="pi pi-history"></i><span class="font-bold">Journal d'activité</span></div>
                            </template>

                            <div class="p-6 max-w-3xl mx-auto">
                                <Timeline :value="activityLog" class="w-full">
                                    <template #marker="slotProps">
                                        <span class="flex w-10 h-10 items-center justify-center text-white rounded-full z-10 shadow-sm" :style="{ backgroundColor: slotProps.item.color }">
                                            <i :class="slotProps.item.icon"></i>
                                        </span>
                                    </template>
                                    <template #content="slotProps">
                                        <Card class="mt-0 mb-8 shadow-sm border border-slate-100 rounded-2xl">
                                            <template #title>
                                                <div class="text-lg font-bold text-slate-800">{{ slotProps.item.status }}</div>
                                            </template>
                                            <template #subtitle>
                                                <div class="text-sm text-slate-500 font-medium"><i class="pi pi-clock mr-1"></i> {{ slotProps.item.date }}</div>
                                            </template>
                                            <template #content>
                                                <p class="text-slate-600 leading-relaxed m-0">{{ slotProps.item.description }}</p>
                                            </template>
                                        </Card>
                                    </template>
                                </Timeline>
                            </div>
                        </TabPanel>

                    </TabView>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Design personnalisé pour surcharger légèrement PrimeVue et obtenir le look "Premium" */

/* Tabs Styling */
:deep(.custom-tabview .p-tabview-nav) {
    border-bottom: 1px solid #e2e8f0;
    background: transparent;
    padding: 0 1rem;
}
:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: #64748b;
    padding: 1.25rem 1rem;
    font-weight: 600;
    transition: all 0.2s ease;
}
:deep(.custom-tabview .p-tabview-nav li:not(.p-highlight):hover .p-tabview-nav-link) {
    background: transparent;
    border-color: #cbd5e1;
    color: #334155;
}
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    background: transparent;
    border-color: #4f46e5;
    color: #4f46e5;
}

/* Timeline Customization */
:deep(.p-timeline-event-opposite) {
    display: none; /* Cache la colonne de gauche vide de la timeline par défaut pour centrer le focus */
}
@media (min-width: 768px) {
    :deep(.p-timeline-event-opposite) {
        display: block;
        flex: 0 0 15%; /* Réduit l'espace pris par la date à gauche */
        padding-right: 2rem;
        text-align: right;
    }
}
</style>
