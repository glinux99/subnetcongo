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
import ProgressBar from 'primevue/progressbar';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import Rating from 'primevue/rating';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import InlineMessage from 'primevue/inlinemessage';
import Menu from 'primevue/menu';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const instructors = ref([]);
const selectedInstructors = ref(null);
const displayInstructorDialog = ref(false);
const displayPerformanceDialog = ref(false);
const submitted = ref(false);
const activeTab = ref(0);

// --- MODÈLE DE DONNÉES INSTRUCTEUR ---
const currentInstructor = ref({
    id: null,
    name: '',
    email: '',
    specialties: [],
    bio: '',
    commission_rate: 70,
    status: 'active',
    avatar: null,
    social: { linkedin: '', twitter: '', website: '' }
});

// --- OPTIONS & MOCK DATA ---
const specialtyOptions = ref([
    { label: 'Développement Web', value: 'Web' },
    { label: 'Design UI/UX', value: 'Design' },
    { label: 'Data Science', value: 'Data' },
    { label: 'Cybersécurité', value: 'Security' },
    { label: 'Intelligence Artificielle', value: 'AI' },
    { label: 'Mobile App', value: 'Mobile' }
]);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// --- GÉNÉRATION DES DONNÉES (MOCK API) ---
const loadInstructors = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const names = ['David Kimimba', 'Sarah Mwamba', 'Alphonse Kabongo', 'Fatima Traoré', 'Jean-Luc Mpoyi', 'Marie Curie'];
        const specialties = ['Laravel Expert', 'UI Specialist', 'Python Dev', 'Security Architect', 'Vue.js Master', 'AI Researcher'];

        for (let i = 0; i < names.length; i++) {
            data.push({
                id: i + 1,
                name: names[i],
                email: names[i].toLowerCase().replace(' ', '.') + '@subnet.cd',
                specialty: specialties[i],
                tags: [specialties[i].split(' ')[0]],
                bio: `Expert passionné avec plus de 10 ans d'expérience dans le domaine de ${specialties[i]}.`,
                avatar: `https://i.pravatar.cc/150?u=inst${i}`,
                courses_count: Math.floor(Math.random() * 8) + 2,
                students_count: Math.floor(Math.random() * 2000) + 500,
                rating: (Math.random() * (5 - 4.2) + 4.2).toFixed(1),
                commission: Math.floor(Math.random() * 20) + 60,
                revenue: (Math.random() * 15000 + 2000).toFixed(2),
                status: i === 5 ? 'onboarding' : 'active',
                joined_at: '2024-06-12'
            });
        }
        instructors.value = data;
        loading.value = false;
    }, 1500);
};

// --- CHARTS CONFIG ---
const instructorPerformanceData = ref({
    labels: ['D. Kimimba', 'S. Mwamba', 'A. Kabongo', 'F. Traoré', 'J-L. Mpoyi', 'M. Curie'],
    datasets: [
        {
            label: 'Revenu Généré ($)',
            data: [12500, 8900, 15600, 11200, 7500, 4200],
            backgroundColor: '#6366F1',
            borderRadius: 12
        },
        {
            label: 'Nouveaux Inscrits',
            data: [450, 320, 610, 280, 190, 110],
            backgroundColor: '#E0E7FF',
            borderRadius: 12
        }
    ]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } }
};

// --- ACTIONS ---
const openNew = () => {
    currentInstructor.value = { id: null, name: '', email: '', specialties: [], bio: '', commission_rate: 70, status: 'active', social: {} };
    submitted.value = false;
    displayInstructorDialog.value = true;
};

const viewPerformance = (instructor) => {
    currentInstructor.value = { ...instructor };
    displayPerformanceDialog.value = true;
};

const editInstructor = (instructor) => {
    currentInstructor.value = { ...instructor };
    displayInstructorDialog.value = true;
};

const saveInstructor = () => {
    submitted.value = true;
    if (currentInstructor.value.name.trim() && currentInstructor.value.email.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Fiche instructeur mise à jour', life: 3000 });
        displayInstructorDialog.value = false;
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'active': return 'success';
        case 'onboarding': return 'warning';
        case 'inactive': return 'danger';
        default: return 'info';
    }
};

const exportProfile = () => {
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'Le rapport PDF de l\'instructeur est en cours de génération.', life: 3000 });
};

onMounted(() => {
    loadInstructors();
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion du Corps Enseignant - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER SECTION -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 -rotate-2">
                        <i class="pi pi-briefcase text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Corps Enseignant</h1>
                        <p class="text-slate-500 font-medium italic">Gérez les experts et suivez l'impact de leurs formations</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Rapport de Paie" icon="pi pi-dollar" class="p-button-outlined p-button-secondary border-2 font-bold" />
                    <Button label="Ajouter un Instructeur" icon="pi pi-user-plus" class="p-button-primary shadow-lg px-6 font-bold" @click="openNew" />
                </div>
            </header>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Total Formateurs', val: instructors.length, icon: 'pi-users', color: 'indigo' },
                    { label: 'Cours en Ligne', val: '45', icon: 'pi-book', color: 'emerald' },
                    { label: 'Note Moyenne', val: '4.85/5', icon: 'pi-star-fill', color: 'amber' },
                    { label: 'Total Commissions', val: '124,500 $', icon: 'pi-wallet', color: 'rose' }
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

            <!-- PERFORMANCE SECTION -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-2">
                                <span class="text-xl font-black text-slate-800 tracking-tighter">Comparatif de Performance Financière</span>
                                <Tag value="Février 2026" severity="info" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[350px] p-2">
                                <Chart type="bar" :data="instructorPerformanceData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <Card class="border-none shadow-sm rounded-3xl h-full bg-slate-900 text-white overflow-hidden relative">
                        <template #title><span class="text-lg font-black text-indigo-400 px-4 pt-4 block uppercase tracking-widest">Top Instructeur</span></template>
                        <template #content>
                            <div class="relative z-10 text-center py-6">
                                <div class="relative inline-block mb-6">
                                    <Avatar image="https://i.pravatar.cc/150?u=inst2" size="xlarge" shape="circle" class="w-32 h-32 border-4 border-indigo-500 shadow-2xl" />
                                    <div class="absolute -bottom-2 -right-2 bg-amber-400 p-2 rounded-full border-4 border-slate-900">
                                        <i class="pi pi-crown text-slate-900 text-xl"></i>
                                    </div>
                                </div>
                                <h3 class="text-2xl font-black mb-1">Alphonse Kabongo</h3>
                                <p class="text-indigo-400 font-bold uppercase text-[10px] tracking-widest mb-6 italic">Master Laravel Expert</p>

                                <div class="grid grid-cols-2 gap-4 px-4">
                                    <div class="bg-slate-800 p-4 rounded-2xl">
                                        <span class="text-[10px] text-slate-400 uppercase font-bold">Ventes</span>
                                        <p class="text-xl font-black">610</p>
                                    </div>
                                    <div class="bg-slate-800 p-4 rounded-2xl">
                                        <span class="text-[10px] text-slate-400 uppercase font-bold">Revenue</span>
                                        <p class="text-xl font-black">15,6k $</p>
                                    </div>
                                </div>
                                <Button label="Voir le profil expert" class="p-button-text p-button-white p-button-sm mt-8 font-bold" icon="pi pi-arrow-right" iconPos="right" />
                            </div>
                            <!-- Background Decoration -->
                            <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-indigo-500/10 blur-[80px] rounded-full"></div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- MAIN DATA TABLE -->
            <Card class="border-none shadow-sm rounded-[2.5rem] overflow-hidden">
                <template #content>
                    <DataTable
                        v-model:filters="filters"
                        :value="instructors"
                        :paginator="true"
                        :rows="10"
                        dataKey="id"
                        :loading="loading"
                        stripedRows
                        filterDisplay="menu"
                        :globalFilterFields="['name', 'email', 'specialty']"
                        class="p-datatable-lg custom-instructor-table"
                    >
                        <template #header>
                            <div class="flex flex-col md:flex-row justify-between items-center gap-6 px-2">
                                <div class="flex items-center gap-4">
                                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">Annuaire des Experts</h2>
                                    <Badge :value="instructors.length" severity="warning" class="px-3" />
                                </div>
                                <span class="p-input-icon-left w-full md:w-80">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Recherche par nom ou expertise..." class="w-full rounded-2xl border-slate-200" />
                                </span>
                            </div>
                        </template>

                        <Column field="name" header="INSTRUCTEUR" sortable style="min-width: 18rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4">
                                    <Avatar :image="data.avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-800 text-base leading-tight">{{ data.name }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ data.specialty }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="courses_count" header="COURS" sortable class="text-center">
                            <template #body="{ data }">
                                <span class="px-4 py-1.5 bg-indigo-50 text-indigo-600 rounded-xl font-black text-sm">{{ data.courses_count }}</span>
                            </template>
                        </Column>

                        <Column field="rating" header="EVALUATION" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Rating v-model="data.rating" readonly :cancel="false" />
                                    <span class="text-xs font-black text-slate-800">{{ data.rating }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="revenue" header="REVENU TOTAL" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-base">{{ data.revenue }} $</span>
                                    <span class="text-[9px] text-slate-400 font-bold">Commission: {{ data.commission }}%</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="STATUT" style="min-width: 10rem">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase px-4 py-1 text-[10px] font-black" />
                            </template>
                        </Column>

                        <Column header="ACTIONS" alignFrozen="right" frozen style="width: 10rem">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button icon="pi pi-chart-line" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Performance'" @click="viewPerformance(data)" />
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-indigo" v-tooltip.top="'Editer'" @click="editInstructor(data)" />
                                    <Button icon="pi pi-envelope" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Contacter'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- MODAL: ADD / EDIT INSTRUCTOR -->
            <Dialog v-model:visible="displayInstructorDialog" :modal="true" :style="{ width: '800px' }" header="🛠️ Configuration Profil Instructeur" class="p-fluid instructor-modal">
                <div class="grid grid-cols-12 gap-8 pt-4">
                    <!-- Left: Profile Setup -->
                    <div class="col-span-12 lg:col-span-4 space-y-6">
                        <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100 flex flex-col items-center">
                            <div class="relative group cursor-pointer mb-6">
                                <Avatar :image="currentInstructor.avatar || 'https://i.pravatar.cc/150?u=new'" size="xlarge" shape="circle" class="w-32 h-32 border-4 border-white shadow-xl" />
                                <div class="absolute inset-0 bg-indigo-600/60 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="pi pi-camera text-white text-2xl"></i>
                                </div>
                            </div>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-1">Photo de profil</h4>
                            <p class="text-[10px] text-slate-400 text-center px-4">Utilisez une image pro (PNG ou JPG, max 2Mo)</p>

                            <Divider />

                            <div class="w-full space-y-4">
                                <div class="field">
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Taux de Commission (%)</label>
                                    <InputNumber v-model="currentInstructor.commission_rate" suffix="%" :min="0" :max="100" showButtons />
                                </div>
                                <div class="field">
                                    <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Statut du contrat</label>
                                    <Dropdown v-model="currentInstructor.status" :options="['active', 'onboarding', 'inactive']" class="rounded-xl" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Personal Info & Bio -->
                    <div class="col-span-12 lg:col-span-8 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="field">
                                <label class="font-black text-slate-700 text-xs mb-2 block uppercase tracking-wide">Nom Complet</label>
                                <InputText v-model="currentInstructor.name" placeholder="ex: Daniel Kikimba" class="rounded-xl border-2 py-3" :class="{'p-invalid': submitted && !currentInstructor.name}" />
                            </div>
                            <div class="field">
                                <label class="font-black text-slate-700 text-xs mb-2 block uppercase tracking-wide">Email Professionnel</label>
                                <InputText v-model="currentInstructor.email" placeholder="email@subnet.cd" class="rounded-xl border-2 py-3" :class="{'p-invalid': submitted && !currentInstructor.email}" />
                            </div>
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-xs mb-2 block uppercase tracking-wide">Domaines d'Expertise (Spécialités)</label>
                            <MultiSelect v-model="currentInstructor.specialties" :options="specialtyOptions" optionLabel="label" placeholder="Sélectionner les compétences" display="chip" class="rounded-xl" />
                        </div>

                        <div class="field">
                            <label class="font-black text-slate-700 text-xs mb-2 block uppercase tracking-wide">Bio & Expérience</label>
                            <Textarea v-model="currentInstructor.bio" rows="5" class="rounded-xl border-2" placeholder="Décrivez le parcours de l'instructeur..." />
                        </div>

                        <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                            <h5 class="text-xs font-black text-indigo-700 uppercase mb-4 tracking-widest">Liens Sociaux (Portfolio)</h5>
                            <div class="space-y-4">
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-linkedin text-indigo-400" />
                                    <InputText v-model="currentInstructor.social.linkedin" placeholder="Lien LinkedIn" class="w-full bg-white border-none rounded-xl" />
                                </span>
                                <span class="p-input-icon-left w-full">
                                    <i class="pi pi-globe text-indigo-400" />
                                    <InputText v-model="currentInstructor.social.website" placeholder="Site web / Portfolio" class="w-full bg-white border-none rounded-xl" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full pt-4 border-t border-slate-50 px-2">
                        <Button label="Révoquer l'accès" icon="pi pi-ban" class="p-button-text p-button-danger font-bold" v-if="currentInstructor.id" />
                        <div class="flex gap-2 ml-auto">
                            <Button label="Annuler" class="p-button-text p-button-secondary" @click="displayInstructorDialog = false" />
                            <Button label="Enregistrer l'Expert" icon="pi pi-check" class="p-button-primary px-10 shadow-lg shadow-indigo-100 font-black rounded-xl" @click="saveInstructor" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: PERFORMANCE INSIGHTS -->
            <Dialog v-model:visible="displayPerformanceDialog" :modal="true" :style="{ width: '850px' }" header="📊 Analyse de Performance Instructeur" class="p-fluid" v-if="currentInstructor">
                <div class="p-2 space-y-8">
                    <!-- Top Summary -->
                    <div class="flex items-center gap-6 bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100">
                        <Avatar :image="currentInstructor.avatar" size="xlarge" shape="circle" class="w-20 h-20 border-4 border-white shadow-md" />
                        <div class="flex-1">
                            <h2 class="text-2xl font-black text-slate-800">{{ currentInstructor.name }}</h2>
                            <p class="text-indigo-600 font-bold uppercase text-[10px] tracking-widest italic">{{ currentInstructor.specialty }}</p>
                            <div class="flex gap-4 mt-3">
                                <div class="flex items-center gap-2"><i class="pi pi-calendar text-slate-300"></i> <span class="text-xs font-bold text-slate-500">Inscrit le 12/06/2024</span></div>
                                <div class="flex items-center gap-2"><i class="pi pi-map-marker text-slate-300"></i> <span class="text-xs font-bold text-slate-500">Kinshasa, RDC</span></div>
                            </div>
                        </div>
                        <Button icon="pi pi-download" label="Export PDF" class="p-button-outlined p-button-secondary font-bold" @click="exportProfile" />
                    </div>

                    <!-- Detailed Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="s in [
                            { label: 'Cours Actifs', val: currentInstructor.courses_count, color: 'indigo', icon: 'pi-book' },
                            { label: 'Élèves Inscrits', val: currentInstructor.students_count, color: 'emerald', icon: 'pi-users' },
                            { label: 'Gains 30j', val: '2,840 $', color: 'amber', icon: 'pi-wallet' }
                        ]" :key="s.label" class="p-6 bg-white border border-slate-100 rounded-3xl shadow-sm text-center">
                            <div :class="`w-10 h-10 rounded-xl bg-${s.color}-50 text-${s.color}-500 flex items-center justify-center mx-auto mb-4` ">
                                <i :class="`pi ${s.icon}` "></i>
                            </div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">{{ s.label }}</span>
                            <span class="text-2xl font-black text-slate-800">{{ s.val }}</span>
                        </div>
                    </div>

                    <!-- Courses List & Ratings -->
                    <Card class="border-none shadow-sm rounded-3xl overflow-hidden">
                        <template #title><span class="text-base font-black text-slate-800 px-4">Performance par Formation</span></template>
                        <template #content>
                            <DataTable :value="[
                                { title: 'Mastering Vue 3', students: 850, rating: 4.9, sales: '4,250 $' },
                                { title: 'Laravel Architecture', students: 420, rating: 4.7, sales: '2,100 $' },
                                { title: 'UI Design Fundamentals', students: 310, rating: 4.8, sales: '1,550 $' }
                            ]" class="p-datatable-sm">
                                <Column field="title" header="TITRE DU COURS"></Column>
                                <Column field="students" header="ÉLÈVES" class="text-center"></Column>
                                <Column field="rating" header="AVIS">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-star-fill text-amber-400 text-xs"></i>
                                            <span class="text-xs font-black">{{ data.rating }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="sales" header="REVENU" class="text-right font-black text-emerald-600"></Column>
                            </DataTable>
                        </template>
                    </Card>

                    <div class="p-6 bg-slate-900 rounded-[2.5rem] text-white flex justify-between items-center">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center">
                                <i class="pi pi-info-circle text-indigo-400 text-2xl"></i>
                            </div>
                            <p class="text-sm font-medium max-w-md">L'instructeur a atteint l'objectif de vente trimestriel. Il est éligible au <b>Bonus de Performance (+5%)</b> pour le mois prochain.</p>
                        </div>
                        <Button label="Appliquer Bonus" class="p-button-indigo p-button-sm font-black px-6" />
                    </div>
                </div>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo - Moteur de gestion des instructeurs v3.0.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo Instructors */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-instructor-table) {
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

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Rating Styling */
:deep(.p-rating .p-rating-item .p-rating-icon) {
    font-size: 0.75rem;
    color: #f59e0b;
}

/* Scroll Panel UI */
:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
    opacity: 1;
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

/* Animations */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
