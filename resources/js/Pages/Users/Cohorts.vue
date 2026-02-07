<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import ProgressBar from 'primevue/progressbar';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import PickList from 'primevue/picklist';
import InlineMessage from 'primevue/inlinemessage';
import Textarea from 'primevue/textarea';
import Menu from 'primevue/menu';
import Chip from 'primevue/chip';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const cohorts = ref([]);
const selectedCohorts = ref(null);
const displayCohortDialog = ref(false);
const displayMembersDialog = ref(false);
const displayMessageDialog = ref(false);
const submitted = ref(false);
const activeTab = ref(0);

// --- MODÈLE DE DONNÉES COHORTE ---
const currentCohort = ref({
    id: null,
    name: '',
    description: '',
    instructor: null,
    start_date: null,
    end_date: null,
    status: 'active',
    max_students: 50,
    courses: [],
    members: []
});

// --- OPTIONS & MOCK DATA ---
const instructors = ref([
    { name: 'Daniel Kikimba', id: 1, avatar: 'https://i.pravatar.cc/150?u=daniel' },
    { name: 'Sarah Mwamba', id: 2, avatar: 'https://i.pravatar.cc/150?u=sarah' },
    { name: 'Thomas Legrand', id: 3, avatar: 'https://i.pravatar.cc/150?u=thomas' }
]);

const courseOptions = ref([
    { label: 'Mastering Vue 3', value: 101 },
    { label: 'Laravel Advanced', value: 102 },
    { label: 'UI/UX Design', value: 103 },
    { label: 'Cybersécurité', value: 104 }
]);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// --- GENERATION DES DONNÉES ---
const loadCohorts = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const names = ['Promotion Kinshasa 2026', 'Classe Fullstack B', 'Entreprise SNEL Training', 'Design Squad Alpha', 'Groupe React Junior'];

        for (let i = 0; i < 5; i++) {
            const memberCount = Math.floor(Math.random() * 40) + 10;
            data.push({
                id: i + 1,
                name: names[i],
                description: 'Description détaillée de la cohorte ' + names[i],
                instructor: instructors.value[i % 3],
                members_count: memberCount,
                avg_completion: Math.floor(Math.random() * 40) + 60,
                avg_score: Math.floor(Math.random() * 20) + 75,
                status: i === 4 ? 'scheduled' : 'active',
                created_at: '2025-01-15',
                courses_count: Math.floor(Math.random() * 3) + 1,
                members: Array.from({ length: 5 }, (_, k) => ({
                    id: k, name: `Élève ${k}`, avatar: `https://i.pravatar.cc/150?u=m${i}${k}`, email: `user${k}@subnet.cd`
                }))
            });
        }
        cohorts.value = data;
        loading.value = false;
    }, 1200);
};

// --- CHARTS CONFIG ---
const performanceChartData = ref({
    labels: ['Promotion Kinshasa', 'Classe Fullstack', 'SNEL Training', 'Design Squad', 'React Junior'],
    datasets: [
        {
            label: 'Score Moyen (%)',
            data: [82, 78, 91, 85, 74],
            backgroundColor: '#6366F1',
            borderRadius: 8
        },
        {
            label: 'Complétion (%)',
            data: [75, 62, 88, 80, 55],
            backgroundColor: '#E0E7FF',
            borderRadius: 8
        }
    ]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
    scales: { x: { grid: { display: false } }, y: { max: 100, grid: { color: '#f1f5f9' } } }
};

// --- ACTIONS ---
const openNew = () => {
    currentCohort.value = { id: null, name: '', description: '', instructor: null, start_date: null, status: 'active', members: [], courses: [] };
    submitted.value = false;
    displayCohortDialog.value = true;
};

const viewMembers = (cohort) => {
    currentCohort.value = { ...cohort };
    displayMembersDialog.value = true;
};

const saveCohort = () => {
    submitted.value = true;
    if (currentCohort.value.name.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Cohorte enregistrée avec succès', life: 3000 });
        displayCohortDialog.value = false;
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'active': return 'success';
        case 'scheduled': return 'info';
        case 'completed': return 'secondary';
        default: return 'warning';
    }
};

const sendMessage = () => {
    toast.add({ severity: 'info', summary: 'Message envoyé', detail: 'Annonce publiée pour tous les membres de la cohorte.', life: 3000 });
    displayMessageDialog.value = false;
};

onMounted(() => {
    loadCohorts();
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Cohortes - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 rotate-2">
                        <i class="pi pi-users text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Cohortes & Groupes</h1>
                        <p class="text-slate-500 font-medium">Gérez vos promotions d'étudiants et leurs parcours groupés</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Rapports par Classe" icon="pi pi-file-pdf" class="p-button-outlined p-button-secondary border-2 font-bold" />
                    <Button label="Créer une Cohorte" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" @click="openNew" />
                </div>
            </header>

            <!-- KPI SECTION -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Total Cohortes', val: cohorts.length, icon: 'pi-folder', color: 'indigo' },
                    { label: 'Élèves en Groupes', val: '412', icon: 'pi-users', color: 'emerald' },
                    { label: 'Cours Assignés', val: '18', icon: 'pi-book', color: 'amber' },
                    { label: 'Performance Moy.', val: '82%', icon: 'pi-chart-bar', color: 'rose' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500 group-hover:scale-110 transition-transform` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <!-- PERFORMANCE CHART -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-2">
                                <span class="text-xl font-black text-slate-800">Comparatif de Performance</span>
                                <Button icon="pi pi-refresh" class="p-button-rounded p-button-text p-button-secondary" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[350px] p-2">
                                <Chart type="bar" :data="performanceChartData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-span-12 lg:col-span-4">
                    <Card class="border-none shadow-sm rounded-[2.5rem] h-full bg-slate-900 text-white p-4">
                        <template #title><span class="text-xl font-black text-indigo-400 px-2">Répartition Statuts</span></template>
                        <template #content>
                            <div class="h-[250px] relative">
                                <Chart type="doughnut" :data="{
                                    labels: ['Actif', 'Programmé', 'Terminé'],
                                    datasets: [{ data: [65, 20, 15], backgroundColor: ['#6366F1', '#10B981', '#F59E0B'], cutout: '75%' }]
                                }" :options="{ ...chartOptions, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { color: '#fff' } } } }" class="h-full" />
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-4xl font-black text-white">85%</span>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">ENGAGEMENT</span>
                                </div>
                            </div>
                            <div class="mt-6 space-y-3 px-4">
                                <p class="text-xs text-slate-400 text-center italic">Les cohortes "Entreprise" affichent le taux de complétion le plus élevé cette semaine.</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- MAIN DATA TABLE -->
            <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden">
                <template #content>
                    <DataTable
                        v-model:filters="filters"
                        :value="cohorts"
                        :paginator="true"
                        :rows="10"
                        dataKey="id"
                        :loading="loading"
                        stripedRows
                        filterDisplay="menu"
                        :globalFilterFields="['name', 'instructor.name']"
                        class="p-datatable-lg custom-cohort-table"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-4 px-2">
                                <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">Liste des Cohortes</h2>
                                <span class="p-input-icon-left w-full md:w-80">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Rechercher une cohorte..." class="w-full rounded-2xl border-slate-200" />
                                </span>
                            </div>
                        </template>

                        <Column field="name" header="COHORTE / GROUPE" sortable style="min-width: 18rem">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <span class="font-black text-slate-800 text-lg hover:text-indigo-600 cursor-pointer transition-colors" @click="viewMembers(data)">{{ data.name }}</span>
                                    <span class="text-xs text-slate-400 font-medium italic">ID: #CH-{{ data.id }}-2026</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="instructor" header="INSTRUCTEUR RÉFÉRENT" style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar :image="data.instructor.avatar" shape="circle" size="medium" />
                                    <span class="font-bold text-slate-700">{{ data.instructor.name }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="members_count" header="ÉLÈVES" sortable class="text-center">
                            <template #body="{ data }">
                                <div class="flex flex-col items-center">
                                    <AvatarGroup class="mb-2">
                                        <Avatar v-for="m in data.members" :key="m.id" :image="m.avatar" shape="circle" />
                                        <Avatar label="+15" shape="circle" class="bg-slate-100 text-[10px] font-bold" />
                                    </AvatarGroup>
                                    <span class="font-black text-indigo-600">{{ data.members_count }} inscrits</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="avg_completion" header="PROGRESSION MOY." sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-2">
                                    <div class="flex justify-between text-[10px] font-bold">
                                        <span>{{ data.avg_completion }}% Complété</span>
                                        <span class="text-emerald-500">Score: {{ data.avg_score }}%</span>
                                    </div>
                                    <ProgressBar :value="data.avg_completion" :showValue="false" style="height: 6px"
                                        :color="data.avg_completion > 80 ? '#10B981' : '#6366F1'" />
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase px-4 py-1 text-[10px] font-black" />
                            </template>
                        </Column>

                        <Column header="ACTIONS" alignFrozen="right" frozen style="width: 10rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Modifier'" @click="viewMembers(data)" />
                                    <Button icon="pi pi-megaphone" class="p-button-rounded p-button-text p-button-indigo" v-tooltip.top="'Annonce'" @click="displayMessageDialog = true" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- MODAL: CREATE/EDIT COHORT -->
            <Dialog v-model:visible="displayCohortDialog" :modal="true" :style="{ width: '650px' }" header="🛠️ Configuration de la Cohorte" class="p-fluid cohort-modal">
                <div class="grid grid-cols-12 gap-6 pt-4">
                    <div class="col-span-12 space-y-6">
                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Nom du Groupe</label>
                            <InputText v-model="currentCohort.name" placeholder="ex: Promo Fullstack 2026-B" class="rounded-xl border-2 py-3" :class="{'p-invalid': submitted && !currentCohort.name}" />
                            <small class="p-error" v-if="submitted && !currentCohort.name">Le nom est obligatoire.</small>
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Description / Objectif</label>
                            <Textarea v-model="currentCohort.description" rows="3" class="rounded-xl border-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Instructeur Référent</label>
                                <Dropdown v-model="currentCohort.instructor" :options="instructors" optionLabel="name" placeholder="Choisir un responsable" class="rounded-xl" />
                            </div>
                            <div class="field">
                                <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Capacité Max</label>
                                <InputNumber v-model="currentCohort.max_students" showButtons :min="1" suffix=" élèves" />
                            </div>
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Cours assignés au groupe</label>
                            <MultiSelect v-model="currentCohort.courses" :options="courseOptions" optionLabel="label" placeholder="Sélectionner les cours" display="chip" class="rounded-xl" />
                        </div>

                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100 flex items-start gap-4">
                            <i class="pi pi-info-circle text-indigo-500 mt-1"></i>
                            <p class="text-xs font-medium text-indigo-700 leading-relaxed">
                                Les membres ajoutés à cette cohorte recevront automatiquement un accès à tous les cours sélectionnés ci-dessus.
                            </p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full pt-4 border-t border-slate-50 px-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary" @click="displayCohortDialog = false" />
                        <Button label="Créer la cohorte" icon="pi pi-check" class="p-button-primary px-10 shadow-lg shadow-indigo-100 font-black rounded-xl" @click="saveCohort" />
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: DETAILED VIEW (MEMBERS & PROGRESS) -->
            <Dialog v-model:visible="displayMembersDialog" :modal="true" :maximized="true" header="🎓 Gestion Approfondie de la Cohorte" class="p-fluid">
                <div class="grid grid-cols-12 gap-8 h-full py-4">
                    <!-- Sidebar: Analytics Cohorte -->
                    <div class="col-span-12 lg:col-span-3 space-y-6 bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-indigo-600 rounded-3xl mx-auto flex items-center justify-center text-white text-3xl font-black mb-4">
                                {{ currentCohort.name ? currentCohort.name.charAt(0) : 'C' }}
                            </div>
                            <h3 class="text-xl font-black text-slate-800">{{ currentCohort.name }}</h3>
                            <p class="text-xs font-bold text-slate-400 uppercase mt-1 tracking-widest">Actuellement Active</p>
                        </div>

                        <Divider />

                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Performance Groupe</label>
                                <div class="bg-white p-5 rounded-2xl shadow-sm space-y-4">
                                    <div class="flex justify-between items-end">
                                        <span class="text-xs font-bold text-slate-500">Taux de réussite</span>
                                        <span class="text-xl font-black text-emerald-500">92%</span>
                                    </div>
                                    <ProgressBar :value="92" :showValue="false" style="height: 6px" color="#10B981" />
                                    <div class="flex justify-between items-end">
                                        <span class="text-xs font-bold text-slate-500">Complétion moyenne</span>
                                        <span class="text-xl font-black text-indigo-600">68%</span>
                                    </div>
                                    <ProgressBar :value="68" :showValue="false" style="height: 6px" color="#6366F1" />
                                </div>
                            </div>

                            <div class="bg-indigo-900 p-6 rounded-3xl text-white relative overflow-hidden">
                                <i class="pi pi-shield absolute -bottom-5 -right-5 text-8xl text-white/10"></i>
                                <h4 class="text-sm font-bold mb-4">Actions de cohorte</h4>
                                <div class="flex flex-col gap-2 relative z-10">
                                    <Button label="Exporter liste Excel" icon="pi pi-file-excel" class="p-button-text p-button-white p-button-sm text-left px-0" />
                                    <Button label="Certifier tout le groupe" icon="pi pi-verified" class="p-button-text p-button-white p-button-sm text-left px-0" />
                                    <Button label="Synchroniser Calendrier" icon="pi pi-calendar" class="p-button-text p-button-white p-button-sm text-left px-0" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content: Tabs -->
                    <div class="col-span-12 lg:col-span-9 flex flex-col h-full overflow-hidden">
                        <TabView v-model:activeIndex="activeTab" class="custom-cohort-tabs flex-1 flex flex-col overflow-hidden">

                            <!-- TAB 1: MEMBRES -->
                            <TabPanel>
                                <template #header><i class="pi pi-users mr-2"></i> Étudiants ({{ currentCohort.members_count }})</template>
                                <div class="pt-6">
                                    <DataTable :value="currentCohort.members" class="p-datatable-sm" responsiveLayout="scroll">
                                        <template #header>
                                            <div class="flex justify-between items-center mb-4">
                                                <div class="flex gap-2">
                                                    <Button label="Ajouter un étudiant" icon="pi pi-user-plus" class="p-button-sm p-button-indigo" />
                                                    <Button label="Importation en masse" icon="pi pi-upload" class="p-button-text p-button-secondary p-button-sm" />
                                                </div>
                                                <span class="p-input-icon-left">
                                                    <i class="pi pi-search" />
                                                    <InputText placeholder="Chercher un membre..." class="p-inputtext-sm rounded-xl" />
                                                </span>
                                            </div>
                                        </template>
                                        <Column field="name" header="NOM DE L'ÉTUDIANT" style="min-width: 15rem">
                                            <template #body="{ data }">
                                                <div class="flex items-center gap-3">
                                                    <Avatar :image="data.avatar" shape="circle" />
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-slate-800">{{ data.name }}</span>
                                                        <span class="text-[10px] text-slate-400">{{ data.email }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </Column>
                                        <Column field="progress" header="PROGRESSION">
                                            <template #body>
                                                <ProgressBar :value="Math.floor(Math.random()*100)" :showValue="false" style="height: 5px" />
                                            </template>
                                        </Column>
                                        <Column header="DÉNIER ACCÈS" style="width: 10rem">
                                            <template #body>
                                                <span class="text-xs text-slate-400">Il y a 2h</span>
                                            </template>
                                        </Column>
                                        <Column header="ACTION" style="width: 5rem">
                                            <template #body>
                                                <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm p-button-rounded" v-tooltip="'Retirer du groupe'" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <!-- TAB 2: CURRICULUM -->
                            <TabPanel>
                                <template #header><i class="pi pi-book mr-2"></i> Parcours de formation</template>
                                <div class="pt-8 space-y-6">
                                    <InlineMessage severity="info" class="w-full">Les modifications de parcours s'appliquent en temps réel à tous les membres du groupe.</InlineMessage>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div v-for="i in 3" :key="i" class="p-6 bg-white border border-slate-100 rounded-3xl shadow-sm flex flex-col justify-between hover:border-indigo-500 transition-colors group">
                                            <div>
                                                <div class="flex justify-between items-start mb-4">
                                                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                        <i class="pi pi-play text-xl"></i>
                                                    </div>
                                                    <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-secondary p-button-sm" />
                                                </div>
                                                <h4 class="font-black text-slate-800 text-sm mb-2">Formation Laravel & Vue JS #{{ i }}</h4>
                                                <div class="flex items-center gap-2 mb-4">
                                                    <Tag value="24 Modules" severity="info" class="text-[9px]" />
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase">45h de contenu</span>
                                                </div>
                                            </div>
                                            <div class="pt-4 border-t border-slate-50 flex justify-between items-center">
                                                <span class="text-[10px] font-black text-slate-400 uppercase">Statut: Actif</span>
                                                <Button icon="pi pi-calendar-plus" label="Drip Content" class="p-button-text p-button-secondary p-button-sm text-[10px] font-bold" />
                                            </div>
                                        </div>

                                        <div class="border-2 border-dashed border-slate-200 rounded-3xl flex flex-col items-center justify-center p-8 hover:bg-slate-50 cursor-pointer transition-colors group">
                                            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-all">
                                                <i class="pi pi-plus text-2xl"></i>
                                            </div>
                                            <span class="mt-4 font-black text-slate-400 group-hover:text-indigo-800">Assigner un cours</span>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 3: CALENDRIER & DRIP -->
                            <TabPanel>
                                <template #header><i class="pi pi-calendar mr-2"></i> Planification</template>
                                <div class="py-8 grid grid-cols-12 gap-8">
                                    <div class="col-span-12 lg:col-span-7">
                                        <h3 class="text-xl font-black text-slate-800 mb-6">Planning de déblocage (Drip)</h3>
                                        <div class="space-y-4">
                                            <div v-for="mod in [
                                                { title: 'Introduction & Setup', date: 'Immédiat', status: 'unlocked' },
                                                { title: 'Architecture Backend', date: 'J + 7', status: 'scheduled' },
                                                { title: 'Frontend Avancé', date: 'J + 14', status: 'scheduled' },
                                                { title: 'Déploiement & DevOps', date: 'J + 30', status: 'scheduled' }
                                            ]" :key="mod.title" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border-l-4" :class="mod.status === 'unlocked' ? 'border-indigo-500' : 'border-slate-300'">
                                                <div class="flex items-center gap-4">
                                                    <i :class="mod.status === 'unlocked' ? 'pi pi-unlock text-indigo-500' : 'pi pi-lock text-slate-300'"></i>
                                                    <div>
                                                        <p class="font-bold text-slate-800 text-sm">{{ mod.title }}</p>
                                                        <p class="text-[10px] text-slate-400 font-black uppercase">Accès: {{ mod.date }}</p>
                                                    </div>
                                                </div>
                                                <Button icon="pi pi-pencil" class="p-button-text p-button-secondary p-button-sm" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-12 lg:col-span-5 space-y-6">
                                        <div class="p-6 bg-slate-900 rounded-[2rem] text-white">
                                            <h4 class="font-black text-indigo-400 uppercase text-xs tracking-widest mb-4">Aperçu du Cycle</h4>
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs">Date de début</span>
                                                <span class="font-bold">15/02/2026</span>
                                            </div>
                                            <div class="flex items-center justify-between mb-8">
                                                <span class="text-xs">Date d'examen final</span>
                                                <span class="font-bold">15/04/2026</span>
                                            </div>
                                            <Button label="Modifier les dates clés" icon="pi pi-calendar" class="p-button-outlined p-button-white p-button-sm w-full font-bold" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>
            </Dialog>

            <!-- MODAL: SEND ANNOUNCEMENT -->
            <Dialog v-model:visible="displayMessageDialog" :modal="true" :style="{ width: '500px' }" header="📢 Annonce Groupée" class="p-fluid">
                <div class="space-y-6 pt-4">
                    <InlineMessage severity="warn" class="w-full">Ce message sera envoyé par email et notification push à tous les membres du groupe.</InlineMessage>
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Sujet de l'annonce</label>
                        <InputText placeholder="ex: Rappel : Session Live demain à 14h" class="rounded-xl border-2" />
                    </div>
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Message détaillé</label>
                        <Textarea rows="5" placeholder="Écrivez votre message ici..." class="rounded-xl border-2" />
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-end gap-2 border-t border-slate-50 pt-4 px-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="displayMessageDialog = false" />
                        <Button label="Diffuser l'annonce" icon="pi pi-send" class="p-button-indigo px-8 shadow-lg shadow-indigo-100 font-black rounded-xl" @click="sendMessage" />
                    </div>
                </template>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo - Moteur de gestion des cohortes v2.4.
            </footer>
        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo Cohorts */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-cohort-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 1rem;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9/50 !important;
}

:deep(.p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    padding: 1.5rem 2rem;
    transition: all 0.3s ease;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom: 2px solid #6366f1 !important;
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Color Overrides */
.p-button-indigo {
    background: #6366f1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4f46e5;
}

/* Transitions */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}

/* Scroll Panel UI */
:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
    opacity: 1;
}
</style>
