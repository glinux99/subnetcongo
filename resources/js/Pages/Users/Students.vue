<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import Calendar from 'primevue/calendar';
import Slider from 'primevue/slider';
import Rating from 'primevue/rating';
import Chart from 'primevue/chart';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import Checkbox from 'primevue/checkbox';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';
import Badge from 'primevue/badge';

const { t } = useI18n();
const toast = useToast();

// --- CONSTANTES & DATA ---
const statuses = ref(['Actif', 'Inactif', 'Suspendu', 'Diplômé']);
const levels = ref(['Débutant', 'Intermédiaire', 'Avancé', 'Expert']);

// Génération de données massives (Simulation de 50 étudiants)
const generateMockData = () => {
    const names = ["Jean Kabamba", "Marie Mwenza", "Patrick Tshibala", "Sarah Loft", "Kevin Ndongala", "Arnaud Ngoy", "Clarisse Meta", "Davy Maloba"];
    const coursesList = ["Laravel Mastery", "Vue 3 Advanced", "UI/UX Fundamentals", "DevOps Pro", "CyberSec 101"];

    return Array.from({ length: 50 }, (_, i) => ({
        id: 1000 + i,
        name: names[i % names.length] + " " + (i > 7 ? i : ""),
        email: `student${i}@subnetcongo.com`,
        avatar: `https://i.pravatar.cc/150?u=${i}`,
        status: statuses.value[Math.floor(Math.random() * statuses.value.length)],
        progress: Math.floor(Math.random() * 100),
        score: Math.floor(Math.random() * 100),
        joinedDate: new Date(2023, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28)),
        lastActivity: new Date(2024, 0, Math.floor(Math.random() * 30)),
        courses: Array.from({ length: Math.floor(Math.random() * 4) + 1 }, () => coursesList[Math.floor(Math.random() * coursesList.length)]),
        level: levels.value[Math.floor(Math.random() * levels.value.length)],
        bio: "Passionné par le développement web et les nouvelles technologies en RDC.",
        phone: "+243 81 000 000" + i,
        address: "Kinshasa, Gombe",
        certificates: Math.floor(Math.random() * 5),
        averageAttendance: Math.floor(Math.random() * 30) + 70
    }));
};

// --- STATE MANAGEMENT ---
const students = ref(generateMockData());
const selectedStudents = ref(null);
const loading = ref(false);
const studentDialog = ref(false);
const deleteDialog = ref(false);
const deleteSelectedDialog = ref(false);
const profileSidebar = ref(false);
const student = ref({});
const submitted = ref(false);
const expandedRows = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    progress: { value: [0, 100], matchMode: FilterMatchMode.BETWEEN },
    level: { value: null, matchMode: FilterMatchMode.IN },
    joinedDate: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] }
});

const menu = ref();
const bulkActionsMenu = [
    { label: 'Envoyer un Email', icon: 'pi pi-envelope', command: () => notify('Email envoyé') },
    { label: 'Exporter PDF', icon: 'pi pi-file-pdf', command: () => notify('Génération PDF...') },
    { separator: true },
    { label: 'Suspendre Accès', icon: 'pi pi-lock', command: () => notify('Comptes suspendus') }
];

// --- COMPUTED ---
const activeStudentsCount = computed(() => students.value.filter(s => s.status === 'Actif').length);
const avgGlobalProgress = computed(() => {
    const sum = students.value.reduce((acc, curr) => acc + curr.progress, 0);
    return Math.round(sum / students.value.length);
});

// --- INDIVIDUAL CHART CONFIG ---
const getStudentChartData = (val) => ({
    labels: ['Module 1', 'Module 2', 'Module 3', 'Module 4', 'Module 5'],
    datasets: [{
        label: 'Progression par Module (%)',
        data: [val * 0.8, val, val * 0.9, val * 0.5, val * 0.7],
        borderColor: '#6366F1',
        tension: 0.4,
        fill: true,
        backgroundColor: 'rgba(99, 102, 241, 0.1)'
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100 } }
};

// --- METHODS ---
const openNew = () => {
    student.value = {
        name: '',
        email: '',
        status: 'Actif',
        progress: 0,
        score: 0,
        joinedDate: new Date(),
        level: 'Débutant'
    };
    submitted.value = false;
    studentDialog.value = true;
};

const hideDialog = () => {
    studentDialog.value = false;
    submitted.value = false;
};

const saveStudent = () => {
    submitted.value = true;

    if (student.value.name.trim()) {
        if (student.value.id) {
            const index = students.value.findIndex(s => s.id === student.value.id);
            students.value[index] = student.value;
            toast.add({ severity: 'success', summary: 'Mis à jour', detail: 'Étudiant actualisé avec succès', life: 3000 });
        } else {
            student.value.id = Math.floor(Math.random() * 10000);
            student.value.avatar = `https://i.pravatar.cc/150?u=${student.value.id}`;
            students.value.unshift(student.value);
            toast.add({ severity: 'success', summary: 'Créé', detail: 'Nouvel étudiant inscrit', life: 3000 });
        }
        studentDialog.value = false;
        student.value = {};
    }
};

const editStudent = (s) => {
    student.value = { ...s };
    studentDialog.value = true;
};

const viewProfile = (s) => {
    student.value = { ...s };
    profileSidebar.value = true;
};

const confirmDeleteStudent = (s) => {
    student.value = s;
    deleteDialog.value = true;
};

const deleteStudent = () => {
    students.value = students.value.filter(val => val.id !== student.value.id);
    deleteDialog.value = false;
    student.value = {};
    toast.add({ severity: 'error', summary: 'Supprimé', detail: 'Compte étudiant supprimé', life: 3000 });
};

const confirmDeleteSelected = () => {
    deleteSelectedDialog.value = true;
};

const deleteSelectedStudents = () => {
    students.value = students.value.filter(val => !selectedStudents.value.includes(val));
    deleteSelectedDialog.value = false;
    selectedStudents.value = null;
    toast.add({ severity: 'warn', summary: 'Suppression groupée', detail: 'Sélection supprimée', life: 3000 });
};

const getSeverity = (status) => {
    switch (status) {
        case 'Actif': return 'success';
        case 'Inactif': return 'danger';
        case 'Suspendu': return 'warning';
        case 'Diplômé': return 'info';
        default: return null;
    }
};

const formatDate = (value) => {
    return value.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const notify = (msg) => {
    toast.add({ severity: 'info', summary: 'Action', detail: msg, life: 2000 });
};

onMounted(() => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
    }, 1000);
});
</script>

<template>
    <AppLayout>
        <Head title="Base de données Étudiants" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER DE LA PAGE --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div>
                    <div class="flex items-center gap-2 text-indigo-600 font-bold mb-1">
                        <i class="pi pi-users"></i>
                        <span class="uppercase tracking-widest text-xs">Gestion Scolaire</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight">Annuaire Apprenants</h1>
                    <p class="text-slate-500 font-medium">Contrôlez les accès, analysez la progression et gérez les certifications.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button label="Exporter Data" icon="pi pi-download" class="p-button-outlined p-button-secondary bg-white border-slate-200" />
                    <Button label="Inscrire un Étudiant" icon="pi pi-plus" class="p-button-primary shadow-lg shadow-indigo-200" @click="openNew" />
                </div>
            </header>

            <!-- --- STATS GÉNÉRALES --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Effectif Total</span>
                            <h2 class="text-3xl font-black text-slate-800 mt-1">{{ students.length }}</h2>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                            <i class="pi pi-users text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-xs font-bold text-emerald-500">
                        <i class="pi pi-arrow-up"></i>
                        <span>+5 inscrits aujourd'hui</span>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Apprenants Actifs</span>
                            <h2 class="text-3xl font-black text-slate-800 mt-1">{{ activeStudentsCount }}</h2>
                        </div>
                        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl">
                            <i class="pi pi-bolt text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full" :style="{ width: (activeStudentsCount/students.length*100) + '%' }"></div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Moy. Progression</span>
                            <h2 class="text-3xl font-black text-slate-800 mt-1">{{ avgGlobalProgress }}%</h2>
                        </div>
                        <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                            <i class="pi pi-chart-bar text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-xs font-bold text-slate-400">
                        <span>Objectif mensuel: 80%</span>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-bold text-slate-400 uppercase tracking-wider">Certifiés 2024</span>
                            <h2 class="text-3xl font-black text-slate-800 mt-1">12</h2>
                        </div>
                        <div class="p-3 bg-rose-50 text-rose-600 rounded-2xl">
                            <i class="pi pi-verified text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-xs font-bold text-rose-500">
                        <i class="pi pi-star-fill"></i>
                        <span>Félicitations aux diplômés</span>
                    </div>
                </div>
            </div>

            <!-- --- TABLEAU PRINCIPAL --- -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">

                <!-- Toolbar Action Group -->
                <Toolbar class="bg-white border-none p-6">
                    <template #start>
                        <div class="flex flex-wrap gap-3">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Recherche globale..." class="w-full md:w-80 border-slate-200 bg-slate-50 focus:bg-white transition-all rounded-xl" />
                            </IconField>
                            <Button type="button" icon="pi pi-filter-slash" label="Réinitialiser" class="p-button-outlined p-button-secondary rounded-xl" @click="filters['global'].value = null" />
                        </div>
                    </template>
                    <template #end>
                        <div class="flex items-center gap-3">
                            <Button v-if="selectedStudents && selectedStudents.length" icon="pi pi-trash" class="p-button-danger p-button-rounded animate-bounce" @click="confirmDeleteSelected" />
                            <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-secondary p-button-rounded" @click="menu.toggle($event)" aria-haspopup="true" aria-controls="overlay_menu" />
                            <Menu ref="menu" id="overlay_menu" :model="bulkActionsMenu" :popup="true" />
                        </div>
                    </template>
                </Toolbar>

                <DataTable
                    v-model:selection="selectedStudents"
                    v-model:expandedRows="expandedRows"
                    :value="students"
                    :paginator="true"
                    :rows="10"
                    dataKey="id"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    :globalFilterFields="['name', 'email', 'status', 'level']"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords}"
                    responsiveLayout="stack"
                    breakpoint="960px"
                    class="p-datatable-modern"
                >
                    <template #empty> <div class="p-8 text-center text-slate-400 font-bold">Aucun étudiant trouvé.</div> </template>
                    <template #loading> Chargement des données... Veuillez patienter. </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <!-- Colonne Avatar + Nom -->
                    <Column field="name" header="Apprenant" sortable style="min-width: 16rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <Avatar :image="data.avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                    <span :class="['absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white', data.status === 'Actif' ? 'bg-emerald-400' : 'bg-slate-300']"></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-700 leading-tight">{{ data.name }}</span>
                                    <span class="text-xs text-slate-400">{{ data.email }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <!-- Colonne Status -->
                    <Column field="status" header="Statut" sortable style="min-width: 8rem">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getSeverity(data.status)" class="px-3 text-[10px] font-black uppercase tracking-widest" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="statuses" placeholder="Filtrer par statut" class="p-column-filter" :showClear="true">
                                <template #option="slotProps">
                                    <Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
                                </template>
                            </Dropdown>
                        </template>
                    </Column>

                    <!-- Colonne Progression -->
                    <Column field="progress" header="Progression" sortable style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex flex-col w-full gap-2">
                                <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                    <span>{{ data.progress }}% COMPLÉTÉ</span>
                                    <span>{{ data.score }} PTS</span>
                                </div>
                                <ProgressBar :value="data.progress" :showValue="false" style="height: 6px" class="rounded-full overflow-hidden" />
                            </div>
                        </template>
                    </Column>

                    <!-- Colonne Niveau -->
                    <Column field="level" header="Niveau" sortable style="min-width: 8rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i :class="['pi pi-circle-fill text-[8px]',
                                    data.level === 'Expert' ? 'text-indigo-500' :
                                    data.level === 'Avancé' ? 'text-rose-500' : 'text-slate-300']"></i>
                                <span class="text-sm font-medium text-slate-600">{{ data.level }}</span>
                            </div>
                        </template>
                    </Column>

                    <!-- Colonne Date -->
                    <Column field="joinedDate" header="Inscription" sortable dataType="date" style="min-width: 10rem">
                        <template #body="{ data }">
                            <span class="text-slate-500 text-sm font-medium">{{ formatDate(data.joinedDate) }}</span>
                        </template>
                    </Column>

                    <!-- Actions -->
                    <Column header="Actions" headerStyle="min-width: 10rem" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Profil'" @click="viewProfile(data)" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Modifier'" @click="editStudent(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Supprimer'" @click="confirmDeleteStudent(data)" />
                            </div>
                        </template>
                    </Column>

                    <!-- Expansion de ligne pour détails rapides -->
                    <template #expansion="{ data }">
                        <div class="p-6 bg-slate-50 rounded-2xl mx-4 mb-4 border border-slate-100 shadow-inner">
                            <h5 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-widest">Dernière activité: {{ formatDate(data.lastActivity) }}</h5>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="course in data.courses" :key="course" class="bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                    <span class="text-sm font-bold text-slate-700">{{ course }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- --- MODAL DE CRÉATION / ÉDITION --- -->
        <Dialog v-model:visible="studentDialog" :style="{width: '550px'}" header="Gestion Étudiant" :modal="true" class="p-fluid rounded-3xl" :breakpoints="{'960px': '75vw', '640px': '90vw'}">
            <div class="flex flex-col gap-6 py-4">
                <div class="flex flex-col items-center gap-4 mb-4">
                    <Avatar :image="student.avatar" size="xlarge" shape="circle" class="shadow-xl ring-4 ring-indigo-50" />
                    <Button label="Changer la photo" class="p-button-text p-button-sm font-bold" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nom Complet</label>
                        <InputText v-model.trim="student.name" required="true" :class="{'p-invalid': submitted && !student.name}" placeholder="Ex: Jean Mwamba" />
                        <small class="p-error font-bold" v-if="submitted && !student.name">Le nom est obligatoire.</small>
                    </div>
                    <div class="field">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Email</label>
                        <InputText v-model.trim="student.email" required="true" placeholder="contact@exemple.cd" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="field">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Statut de l'apprenant</label>
                        <Dropdown v-model="student.status" :options="statuses" placeholder="Sélectionner..." />
                    </div>
                    <div class="field">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Niveau académique</label>
                        <SelectButton v-model="student.level" :options="levels" />
                    </div>
                </div>

                <div class="field">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Progression (%)</label>
                    <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <Slider v-model="student.progress" class="flex-1" />
                        <span class="w-12 text-center font-black text-indigo-600">{{ student.progress }}%</span>
                    </div>
                </div>

                <div class="field">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Bio / Notes Admin</label>
                    <textarea v-model="student.bio" rows="3" class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2">
                    <span class="text-xs text-slate-400 font-medium italic">* Tous les champs sont modifiables par l'admin</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="hideDialog" />
                        <Button label="Enregistrer" icon="pi pi-check" class="p-button-primary px-6" @click="saveStudent" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- SIDEBAR PROFIL DÉTAILLÉ --- -->
        <Sidebar v-model:visible="profileSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div v-if="student" class="flex flex-col h-full">
                <!-- Header Profil -->
                <div class="bg-indigo-600 p-8 text-white rounded-b-[40px] shadow-lg relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="relative z-10 flex flex-col items-center">
                        <Avatar :image="student.avatar" size="xlarge" shape="circle" class="ring-4 ring-white/30 mb-4 w-24 h-24 shadow-2xl" />
                        <h2 class="text-2xl font-black">{{ student.name }}</h2>
                        <Tag :value="student.level" severity="warning" class="mt-2" />

                        <div class="grid grid-cols-3 w-full mt-8 text-center gap-4">
                            <div>
                                <p class="text-xs text-indigo-200 uppercase font-bold tracking-widest">Score</p>
                                <p class="text-xl font-black">{{ student.score }}</p>
                            </div>
                            <div class="border-x border-white/20">
                                <p class="text-xs text-indigo-200 uppercase font-bold tracking-widest">Certifs</p>
                                <p class="text-xl font-black">{{ student.certificates }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-indigo-200 uppercase font-bold tracking-widest">Présence</p>
                                <p class="text-xl font-black">{{ student.averageAttendance }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu Sidebar -->
                <div class="flex-1 overflow-y-auto p-6">
                    <TabView class="custom-tabview">
                        <TabPanel header="Overview">
                            <div class="space-y-6 pt-4">
                                <div class="bg-slate-50 p-4 rounded-2xl">
                                    <h4 class="text-xs font-black text-slate-400 uppercase mb-3">Performance Récente</h4>
                                    <div class="h-48">
                                        <Chart type="line" :data="getStudentChartData(student.progress)" :options="chartOptions" class="h-full" />
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-xs font-black text-slate-400 uppercase mb-3">Informations de contact</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3 text-slate-600">
                                            <i class="pi pi-envelope text-indigo-500"></i>
                                            <span class="text-sm font-medium">{{ student.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-slate-600">
                                            <i class="pi pi-phone text-indigo-500"></i>
                                            <span class="text-sm font-medium">{{ student.phone }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-slate-600">
                                            <i class="pi pi-map-marker text-indigo-500"></i>
                                            <span class="text-sm font-medium">{{ student.address }}</span>
                                        </div>
                                    </div>
                                </div>

                                <Divider />

                                <div>
                                    <h4 class="text-xs font-black text-slate-400 uppercase mb-3">Biographie</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed italic">" {{ student.bio }} "</p>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel header="Cours">
                            <div class="pt-4 space-y-4">
                                <div v-for="(c, idx) in student.courses" :key="idx" class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                            <i class="pi pi-book"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm">{{ c }}</p>
                                            <p class="text-xs text-slate-400">Inscrit en Dec 2023</p>
                                        </div>
                                    </div>
                                    <Badge value="En cours" severity="info" />
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>

                <!-- Footer Sidebar -->
                <div class="p-6 border-t border-slate-100 flex gap-3">
                    <Button label="Message" icon="pi pi-send" class="flex-1 p-button-outlined" />
                    <Button label="Réinitialiser MDP" icon="pi pi-key" class="flex-1 p-button-outlined p-button-warning" />
                </div>
            </div>
        </Sidebar>

        <!-- --- DIALOGUES DE SUPPRESSION --- -->
        <Dialog v-model:visible="deleteDialog" :style="{width: '450px'}" header="Confirmation" :modal="true">
            <div class="flex items-center justify-center gap-4 py-4 text-slate-700">
                <i class="pi pi-exclamation-triangle text-rose-500 text-5xl" />
                <div v-if="student">
                    <p class="text-lg font-bold">Supprimer cet étudiant ?</p>
                    <p class="text-sm opacity-70">Cette action supprimera toutes les données liées à <b>{{student.name}}</b>.</p>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="deleteDialog = false" />
                <Button label="Supprimer définitivement" icon="pi pi-trash" class="p-button-danger rounded-xl px-4" @click="deleteStudent" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSelectedDialog" :style="{width: '450px'}" header="Attention" :modal="true">
            <div class="flex flex-col items-center justify-center gap-4 py-4 text-center">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mb-2">
                    <i class="pi pi-trash text-4xl" />
                </div>
                <p class="text-xl font-bold text-slate-800">Suppression groupée</p>
                <p class="text-slate-500">Êtes-vous certain de vouloir supprimer les <b>{{selectedStudents ? selectedStudents.length : 0}}</b> étudiants sélectionnés ? Cette opération est irréversible.</p>
            </div>
            <template #footer>
                <div class="flex gap-3 justify-center w-full pb-4">
                    <Button label="Garder les données" class="p-button-secondary p-button-text font-bold" @click="deleteSelectedDialog = false" />
                    <Button label="Confirmer la suppression" class="p-button-danger rounded-2xl px-6 shadow-lg shadow-rose-200" @click="deleteSelectedStudents" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* --- CUSTOM PRIME VUE STYLING --- */

:deep(.p-datatable-modern) {
    border-radius: 1.5rem;
}

:deep(.p-datatable-modern .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 1.25rem 1.5rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-modern .p-datatable-tbody > tr) {
    transition: all 0.2s ease;
    cursor: pointer;
}

:deep(.p-datatable-modern .p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
    transform: scale(0.998);
}

:deep(.p-datatable-modern .p-datatable-tbody > tr > td) {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f8fafc;
}

/* Pagination */
:deep(.p-paginator) {
    background: white;
    border: none;
    padding: 1.5rem;
    justify-content: flex-end;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: #6366F1;
    color: white;
    border-radius: 0.75rem;
}

/* Modals & Sidebar */
:deep(.p-dialog-header) {
    border-radius: 1.5rem 1.5rem 0 0;
    padding: 1.5rem;
}

:deep(.p-sidebar-custom) {
    border-radius: 40px 0 0 40px;
}

/* TabView */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent;
    border: none;
    justify-content: center;
}

:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #94a3b8;
}

:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

/* Input Styles */
:deep(.p-inputtext) {
    padding: 0.75rem 1rem;
    font-weight: 500;
}

:deep(.p-progressbar) {
    background: #F1F5F9;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Responsivité Mobile */
@media screen and (max-width: 960px) {
    :deep(.p-datatable-modern .p-datatable-tbody > tr > td) {
        text-align: left !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9 !important;
    }
}
</style>
