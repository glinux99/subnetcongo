<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import ProgressBar from 'primevue/progressbar';
import Menu from 'primevue/menu';
import Checkbox from 'primevue/checkbox';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Badge from 'primevue/badge';

const { t } = useI18n();

// --- PROPS & FAKE DATA GENERATION ---
const props = defineProps({
    studentsCount: { type: Number, default: 12840 },
    coursesCount: { type: Number, default: 156 },
    avgCompletionRate: { type: Number, default: 68 },
    avgScore: { type: Number, default: 74 },
    filters: { type: Object, default: () => ({ course_id: null }) }
});

// --- STATE ---
const isLoading = ref(false);
const selectedCourseFilter = ref(null);
const menu = ref();

// --- DASHBOARD SECTIONS CONFIGURATION ---
const sections = ref({
    kpis: { label: 'Indicateurs Clés', visible: true },
    sparklines: { label: 'Tendances Rapides', visible: true },
    enrollmentChart: { label: 'Analyse des Inscriptions', visible: true },
    popularCourses: { label: 'Palmarès des Cours', visible: true },
    leaderboard: { label: 'Classement Apprenants', visible: true },
    completionStatus: { label: 'Statut de Complétion', visible: true },
    upcomingDeadlines: { label: 'Calendrier & Échéances', visible: true },
    recentActivity: { label: 'Activités Récentes', visible: true }
});

// --- FAKE DATA GENERATOR (Simulant un Backend lourd) ---
const monthlyEnrollmentData = ref({
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
        {
            label: 'Inscriptions 2025',
            data: [450, 520, 380, 610, 720, 500, 420, 300, 850, 920, 1100, 980],
            backgroundColor: '#6366F1',
            borderRadius: 8,
        },
        {
            label: 'Inscriptions 2026',
            data: [580, 640, 490, 800, 950, 710, 600, 550, 1200, 1350, 1400, 1250],
            backgroundColor: '#E0E7FF',
            borderRadius: 8,
        }
    ]
});

const sparklineData = ref({
    activeStudents: [30, 45, 32, 50, 42, 60, 55],
    newEnrollments: [10, 15, 8, 12, 20, 18, 25],
    timeSpent: [120, 140, 110, 160, 180, 150, 210],
    certifications: [2, 5, 3, 8, 4, 10, 12]
});

const leaderboardData = ref([
    { id: 1, name: 'Alice Vasseur', score: 980, avatar: 'https://i.pravatar.cc/150?u=1', rank: 1, trend: 'up' },
    { id: 2, name: 'Thomas Legrand', score: 945, avatar: 'https://i.pravatar.cc/150?u=2', rank: 2, trend: 'stable' },
    { id: 3, name: 'Sophie Martin', score: 912, avatar: 'https://i.pravatar.cc/150?u=3', rank: 3, trend: 'down' },
    { id: 4, name: 'Lucas Bernard', score: 885, avatar: 'https://i.pravatar.cc/150?u=4', rank: 4, trend: 'up' },
    { id: 5, name: 'Emma Durand', score: 870, avatar: 'https://i.pravatar.cc/150?u=5', rank: 5, trend: 'up' }
]);

const upcomingDeadlines = ref([
    { id: 1, title: 'Examen Final Laravel', date: '12 Fév', type: 'Exam', color: 'bg-red-500' },
    { id: 2, title: 'Projet UI/UX Design', date: '15 Fév', type: 'Projet', color: 'bg-amber-500' },
    { id: 3, title: 'Quiz Securité Web', date: '20 Fév', type: 'Quiz', color: 'bg-emerald-500' }
]);

// --- CHART OPTIONS ---
const globalChartOptions = computed(() => ({
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } },
        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
    },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } }
    }
}));

const sparklineOptions = {
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    maintainAspectRatio: false,
    scales: { x: { display: false }, y: { display: false } }
};

// --- METHODS ---
const toggleSection = (key) => {
    sections.value[key].visible = !sections.value[key].visible;
    localStorage.setItem('elearning_dashboard_layout', JSON.stringify(sections.value));
};

const resetSections = () => {
    Object.keys(sections.value).forEach(key => sections.value[key].visible = true);
    localStorage.removeItem('elearning_dashboard_layout');
};

const sectionMenuItems = computed(() => [
    ...Object.entries(sections.value).map(([key, value]) => ({
        label: value.label,
        key: key,
        command: () => toggleSection(key)
    })),
    { separator: true },
    { label: 'Réinitialiser', icon: 'pi pi-refresh', command: resetSections }
]);

const applyFilters = () => {
    isLoading.value = true;
    setTimeout(() => { isLoading.value = false; }, 800);
};

onMounted(() => {
    const saved = localStorage.getItem('elearning_dashboard_layout');
    if (saved) sections.value = JSON.parse(saved);
});

const toggleMenu = (event) => menu.value.toggle(event);

// Helper for sparklines
const getSparkData = (data, color) => ({
    labels: Array(data.length).fill(''),
    datasets: [{
        data: data,
        borderColor: color,
        backgroundColor: color + '10',
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 0
    }]
});
</script>

<template>
    <AppLayout>
        <Head title="E-learning Analytics" />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-2xl shadow-lg shadow-indigo-200">
                        <i class="pi pi-graduation-cap text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Plateforme LMS Analytics</h1>
                        <p class="text-slate-500 font-medium">Suivi des performances pédagogiques en temps réel</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2 bg-white rounded-2xl shadow-sm border border-slate-200">
                    <Dropdown v-model="selectedCourseFilter" :options="[]" placeholder="Tous les cours" class="w-48 border-none ring-0" />
                    <Button icon="pi pi-sync" @click="applyFilters" :loading="isLoading" class="p-button-text p-button-rounded" />
                    <Divider layout="vertical" />
                    <Button icon="pi pi-cog" @click="toggleMenu" class="p-button-text p-button-rounded p-button-secondary" />
                    <Menu ref="menu" :model="sectionMenuItems" :popup="true">
                        <template #item="{ item }">
                            <div class="flex items-center px-4 py-2 gap-3 cursor-pointer hover:bg-slate-50" @click="item.command">
                                <Checkbox v-if="item.key" :modelValue="sections[item.key].visible" :binary="true" />
                                <span :class="{'font-bold text-primary': !item.key}">{{ item.label }}</span>
                            </div>
                        </template>
                    </Menu>
                </div>
            </header>

            <div v-if="sections.kpis.visible" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Total Étudiants', val: studentsCount, color: 'indigo', icon: 'pi-users' },
                    { label: 'Cours Actifs', val: coursesCount, color: 'emerald', icon: 'pi-book' },
                    { label: 'Complétion', val: avgCompletionRate + '%', color: 'amber', icon: 'pi-chart-line' },
                    { label: 'Score Moyen', val: avgScore + '/100', color: 'rose', icon: 'pi-star' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">{{ kpi.label }}</span>
                        <div :class="`p-2 rounded-lg bg-${kpi.color}-50 text-${kpi.color}-600`">
                            <i :class="`pi ${kpi.icon}`"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-slate-800">{{ kpi.val }}</div>
                    <div class="mt-2 text-xs text-emerald-500 font-bold flex items-center gap-1">
                        <i class="pi pi-arrow-up"></i> +12% depuis le mois dernier
                    </div>
                </div>
            </div>

            <div v-if="sections.sparklines.visible" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="s in [
                    { label: 'Apprenants/Jour', data: sparklineData.activeStudents, color: '#6366F1' },
                    { label: 'Inscriptions/Sem', data: sparklineData.newEnrollments, color: '#10B981' },
                    { label: 'Volume Horaire', data: sparklineData.timeSpent, color: '#F59E0B' },
                    { label: 'Diplômes émis', data: sparklineData.certifications, color: '#EF4444' }
                ]" :key="s.label" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <p class="text-xs font-bold text-slate-500 mb-2">{{ s.label }}</p>
                    <div class="h-16">
                        <Chart type="line" :data="getSparkData(s.data, s.color)" :options="sparklineOptions" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8 mb-10">
                <div v-if="sections.enrollmentChart.visible" class="col-span-12 lg:col-span-8">
                    <Card class="h-full border-none shadow-sm rounded-2xl overflow-hidden">
                        <template #title>
                            <div class="flex justify-between items-center px-4">
                                <span class="text-xl font-bold">Flux des Inscriptions</span>
                                <Badge value="2026" severity="info" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[350px]">
                                <Chart type="bar" :data="monthlyEnrollmentData" :options="globalChartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <div v-if="sections.leaderboard.visible" class="col-span-12 lg:col-span-4">
                    <Card class="h-full border-none shadow-sm rounded-2xl">
                        <template #title><span class="text-xl font-bold px-4">Top Apprenants</span></template>
                        <template #content>
                            <div class="flex flex-col gap-4">
                                <div v-for="user in leaderboardData" :key="user.id" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="relative">
                                            <Avatar :image="user.avatar" shape="circle" size="large" />
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-amber-400 border-2 border-white rounded-full flex items-center justify-center text-[10px] font-bold text-white">
                                                {{ user.rank }}
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800">{{ user.name }}</p>
                                            <p class="text-xs text-slate-500">{{ user.score }} Points d'expérience</p>
                                        </div>
                                    </div>
                                    <i :class="['pi', user.trend === 'up' ? 'pi-arrow-up text-emerald-500' : 'pi-minus text-slate-300']"></i>
                                </div>
                                <Button label="Voir tout le classement" class="p-button-outlined p-button-sm mt-4" />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div v-if="sections.completionStatus.visible" class="col-span-12 md:col-span-4">
                    <Card class="border-none shadow-sm rounded-2xl overflow-hidden">
                        <template #title><span class="text-lg font-bold">Progression Globale</span></template>
                        <template #content>
                            <div class="h-[250px] relative">
                                <Chart type="doughnut" :data="{
                                    labels: ['Terminés', 'En cours', 'Échecs'],
                                    datasets: [{ data: [55, 30, 15], backgroundColor: ['#10B981', '#6366F1', '#F43F5E'], cutout: '75%' }]
                                }" :options="{ ...globalChartOptions, cutout: '75%' }" class="h-full" />
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-4xl font-black text-slate-800">85%</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Succès</span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div v-if="sections.upcomingDeadlines.visible" class="col-span-12 md:col-span-4">
                    <Card class="border-none shadow-sm rounded-2xl h-full">
                        <template #title><span class="text-lg font-bold">Échéances Proches</span></template>
                        <template #content>
                            <div class="space-y-4">
                                <div v-for="item in upcomingDeadlines" :key="item.id" class="flex items-center gap-4 p-3 border-l-4 rounded-r-xl bg-slate-50" :class="`border-${item.color.split('-')[1]}-500 text-slate-800` ">
                                    <div class="flex-1">
                                        <p class="text-sm font-black">{{ item.title }}</p>
                                        <p class="text-xs text-slate-500">{{ item.type }} • {{ item.date }}</p>
                                    </div>
                                    <Button icon="pi pi-bell" class="p-button-rounded p-button-text p-button-secondary" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div v-if="sections.popularCourses.visible" class="col-span-12 md:col-span-4">
                    <Card class="border-none shadow-sm rounded-2xl overflow-hidden h-full">
                        <template #title><span class="text-lg font-bold">Cours les plus suivis</span></template>
                        <template #content>
                            <DataTable :value="[
                                { title: 'Mastering Vue 3', students: 1250, rating: 4.9 },
                                { title: 'Laravel Advanced', students: 980, rating: 4.7 },
                                { title: 'UI Design Patterns', students: 850, rating: 4.8 },
                                { title: 'Node.js Microservices', students: 720, rating: 4.5 }
                            ]" responsiveLayout="scroll" class="p-datatable-sm">
                                <Column field="title" header="Titre"></Column>
                                <Column field="students" header="Élèves" class="text-right">
                                    <template #body="slotProps">
                                        <Tag :value="slotProps.data.students" severity="info" />
                                    </template>
                                </Column>
                                <Column field="rating" header="Note">
                                    <template #body="slotProps">
                                        <span class="text-amber-500 font-bold"><i class="pi pi-star-fill"></i> {{ slotProps.data.rating }}</span>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-card) {
    background: white;
}
:deep(.p-card-body) {
    padding: 1.5rem;
}
.p-datatable-sm {
    font-size: 0.875rem;
}
</style>
