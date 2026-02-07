<script setup>
/**
 * MANUAL GRADING & FEEDBACK CONSOLE - SUBNETCONGO LMS
 * Version: 5.5.0
 * Description: Interface haute performance pour la correction des examens et le tutorat.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import InputSwitch from 'primevue/inputswitch';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import Rating from 'primevue/rating';
import Knob from 'primevue/knob';
import ScrollPanel from 'primevue/scrollpanel';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import Editor from 'primevue/editor';
import SelectButton from 'primevue/selectbutton';
import InlineMessage from 'primevue/inlinemessage';
import Slider from 'primevue/slider';
import Fieldset from 'primevue/fieldset';
import Chart from 'primevue/chart';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const gradingDialog = ref(false);
const statisticsSidebar = ref(false);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Examens & Suivi' },
    { label: 'Corrections Manuelles', class: 'font-bold text-indigo-600' }
]);

// --- MOCK DATA: PENDING SUBMISSIONS ---
const submissions = ref([
    {
        id: 101,
        student: { name: 'Jean Mwamba', avatar: 'https://i.pravatar.cc/150?u=1' },
        course: 'Laravel Advanced',
        exam: 'Certification Finale',
        submitted_at: '2024-02-05 14:20',
        urgency: 'high',
        status: 'pending',
        progress: 0,
        questions_to_grade: 3,
        similarity_score: 12, // % de plagiat détecté
    },
    {
        id: 102,
        student: { name: 'Sarah Loft', avatar: 'https://i.pravatar.cc/150?u=2' },
        course: 'UI/UX Design',
        exam: 'Projet de Mi-Parcours',
        submitted_at: '2024-02-06 09:10',
        urgency: 'medium',
        status: 'in_progress',
        progress: 65,
        questions_to_grade: 1,
        similarity_score: 5,
    }
]);

// --- GRADING WORKSPACE STATE ---
const activeSubmission = ref(null);
const currentGrade = reactive({
    points: 0,
    feedback: '',
    rubrics: {
        clarity: 5,
        technical: 4,
        innovation: 3
    }
});

const questions = ref([
    {
        id: 1,
        text: "Expliquez l'utilité des 'Service Providers' dans Laravel.",
        student_answer: "Les service providers sont le point central de tout le démarrage de l'application Laravel. Votre propre application, ainsi que tous les services principaux de Laravel, sont démarrés via des fournisseurs de services.",
        ideal_answer: "Les fournisseurs de services sont l'endroit central pour configurer votre application. Ils permettent de lier des services dans le conteneur de services et d'enregistrer des écouteurs d'événements, des middlewares, etc.",
        max_points: 10,
        points_assigned: 0
    }
]);

// --- FILTERS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- METHODS ---
const openGrading = (data) => {
    activeSubmission.value = data;
    gradingDialog.value = true;
};

const saveGrade = () => {
    toast.add({ severity: 'success', summary: 'Correction Enregistrée', detail: 'La note a été publiée et l\'élève a été notifié.', life: 3000 });
    gradingDialog.value = false;
};

const getUrgencyColor = (u) => {
    return u === 'high' ? 'danger' : u === 'medium' ? 'warning' : 'info';
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- CHART DATA FOR STATISTICS ---
const scoreDistribution = ref({
    labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
    datasets: [{
        label: 'Nombre d\'élèves',
        data: [2, 5, 15, 45, 12],
        backgroundColor: '#6366F1'
    }]
});

</script>

<template>
    <AppLayout>
        <Head title="Console de Correction | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2rem] shadow-2xl shadow-indigo-100">
                            <i class="pi pi-check-square text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Corrections & Tutorat</h1>
                            <p class="text-slate-500 font-medium italic">Évaluez les compétences et guidez vos apprenants vers la réussite.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Statistiques Globales" icon="pi pi-chart-bar" class="p-button-text p-button-secondary rounded-xl" @click="statisticsSidebar = true" />
                    <Divider layout="vertical" />
                    <Button label="Publier toutes les notes" icon="pi pi-send" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" />
                </div>
            </header>

            <!-- --- KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'À corriger', val: '12', icon: 'pi-clock', color: 'rose' },
                    { label: 'Moyenne de classe', val: '74/100', icon: 'pi-percentage', color: 'indigo' },
                    { label: 'Temps de réponse', val: '4.5h', icon: 'pi-bolt', color: 'emerald' },
                    { label: 'Feedbacks émis', val: '142', icon: 'pi-comments', color: 'amber' }
                ]" :key="stat.label" class="bg-white p-7 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-5 hover:shadow-lg transition-all">
                    <div :class="`w-14 h-14 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center` ">
                        <i :class="['pi text-2xl', stat.icon]"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-2xl font-black text-slate-800 tracking-tight">{{ stat.val }}</p>
                    </div>
                </div>
            </div>

            <!-- --- MAIN QUEUE TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">File d'attente des copies</h3>
                    <div class="flex gap-4">
                        <Dropdown v-model="filters['status'].value" :options="['pending', 'in_progress']" placeholder="Filtrer par statut" class="w-48 rounded-xl" showClear />
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Élève ou examen..." class="w-64 rounded-2xl" />
                        </IconField>
                    </div>
                </div>

                <DataTable :value="submissions" :paginator="true" :rows="10" :filters="filters" class="p-datatable-custom" responsiveLayout="stack">
                    <Column field="student.name" header="Apprenant" style="min-width: 18rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :image="data.student.avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ data.student.name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Remis le {{ data.submitted_at }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="exam" header="Examen / Projet">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-700 text-sm italic line-clamp-1">{{ data.exam }}</span>
                                <span class="text-[10px] text-indigo-500 font-bold uppercase">{{ data.course }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="urgency" header="Urgence">
                        <template #body="{ data }">
                            <Tag :value="data.urgency" :severity="getUrgencyColor(data.urgency)" class="text-[9px] font-black uppercase tracking-widest" />
                        </template>
                    </Column>
                    <Column field="progress" header="Progression">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3 w-32">
                                <ProgressBar :value="data.progress" :showValue="false" style="height: 6px" class="flex-1" />
                                <span class="text-[10px] font-bold text-slate-500">{{ data.progress }}%</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Actions" class="text-right">
                        <template #body="{ data }">
                            <Button label="Corriger" icon="pi pi-pencil" class="p-button-outlined p-button-sm rounded-xl px-4 font-bold" @click="openGrading(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- GRADING WORKSPACE DIALOG (THE BIG ONE) --- -->
        <Dialog v-model:visible="gradingDialog" :style="{width: '1300px'}" :modal="true" class="p-fluid grading-dialog" :closable="false">
            <template #header>
                <div class="flex justify-between items-center w-full">
                    <div class="flex items-center gap-4">
                        <Avatar :image="activeSubmission?.student.avatar" shape="circle" size="large" />
                        <div>
                            <h2 class="text-xl font-black text-slate-900">Correction : {{ activeSubmission?.student.name }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ activeSubmission?.exam }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary" @click="gradingDialog = false" />
                    </div>
                </div>
            </template>

            <div class="flex flex-col lg:flex-row h-[750px] overflow-hidden -mx-6">
                <!-- LEFT SIDE: STUDENT CONTENT -->
                <div class="flex-1 border-r border-slate-100 bg-slate-50/50 p-8 overflow-y-auto">
                    <div class="space-y-10">
                        <div v-for="q in questions" :key="q.id" class="space-y-6">
                            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 relative">
                                <Badge value="Question Ouverte" severity="info" class="absolute -top-3 left-6" />
                                <h4 class="text-lg font-black text-slate-800 mb-4">{{ q.text }}</h4>

                                <Divider align="left"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Réponse de l'élève</span></Divider>
                                <p class="text-slate-600 leading-relaxed font-medium bg-indigo-50/30 p-4 rounded-2xl italic">" {{ q.student_answer }} "</p>

                                <div class="mt-6 flex gap-4">
                                    <Tag icon="pi pi-copy" :value="activeSubmission?.similarity_score + '% de similarité'" severity="danger" v-tooltip.top="'Risque de plagiat'" />
                                    <Tag icon="pi pi-language" value="Sentiment Positif" severity="success" />
                                </div>
                            </div>

                            <Fieldset legend="Réponse Idéale (Instructeur)" :toggleable="true" :collapsed="true">
                                <p class="text-sm text-slate-500 leading-relaxed">{{ q.ideal_answer }}</p>
                            </Fieldset>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: GRADING CONTROLS -->
                <aside class="w-full lg:w-96 p-8 bg-white flex flex-col">
                    <ScrollPanel class="flex-1">
                        <div class="space-y-8 pb-10">
                            <div>
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Attribution des points</h4>
                                <div class="flex flex-col items-center gap-4 bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                                    <Knob v-model="currentGrade.points" :max="100" :size="150" valueColor="#6366F1" rangeColor="#E2E8F0" />
                                    <span class="text-xs font-bold text-slate-400">SCORE GLOBAL / 100</span>
                                </div>
                            </div>

                            <Divider />

                            <div>
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4">Critères de réussite</h4>
                                <div class="space-y-6">
                                    <div class="field">
                                        <div class="flex justify-between mb-2">
                                            <label class="text-xs font-bold text-slate-500">Clarté de l'argumentation</label>
                                            <span class="text-xs font-black text-indigo-600">{{ currentGrade.rubrics.clarity }}/5</span>
                                        </div>
                                        <Slider v-model="currentGrade.rubrics.clarity" :min="1" :max="5" />
                                    </div>
                                    <div class="field">
                                        <div class="flex justify-between mb-2">
                                            <label class="text-xs font-bold text-slate-500">Maîtrise Technique</label>
                                            <span class="text-xs font-black text-indigo-600">{{ currentGrade.rubrics.technical }}/5</span>
                                        </div>
                                        <Slider v-model="currentGrade.rubrics.technical" :min="1" :max="5" />
                                    </div>
                                </div>
                            </div>

                            <Divider />

                            <div class="field">
                                <label class="text-xs font-black text-slate-800 uppercase tracking-widest block mb-4">Feedback Personnalisé</label>
                                <div class="p-4 bg-slate-900 rounded-3xl text-white mb-4 flex items-center justify-between group cursor-pointer hover:bg-indigo-600 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-microphone animate-pulse"></i>
                                        <span class="text-[10px] font-black">ENREGISTRER FEEDBACK VOCAL</span>
                                    </div>
                                    <i class="pi pi-circle-fill text-rose-500 text-[8px]"></i>
                                </div>
                                <Editor v-model="currentGrade.feedback" placeholder="Conseils pour s'améliorer..." editorStyle="height: 200px" />
                            </div>

                            <div class="flex items-center justify-between p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                <span class="text-xs font-bold text-amber-700">Publier immédiatement</span>
                                <InputSwitch :modelValue="true" />
                            </div>
                        </div>
                    </ScrollPanel>

                    <div class="pt-6 border-t border-slate-50 flex gap-3">
                        <Button label="Brouillon" class="p-button-text p-button-secondary font-bold flex-1" />
                        <Button label="Valider la note" icon="pi pi-check" class="p-button-primary rounded-2xl px-6 shadow-xl shadow-indigo-100 flex-1" @click="saveGrade" />
                    </div>
                </aside>
            </div>
        </Dialog>

        <!-- --- SIDEBAR : STATISTICS --- -->
        <Sidebar v-model:visible="statisticsSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div class="p-8 space-y-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100">
                        <i class="pi pi-chart-line text-white"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Analytique Correction</h2>
                </div>

                <div class="space-y-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Distribution des notes</h3>
                    <div class="h-64">
                        <Chart type="bar" :data="scoreDistribution" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" class="h-full" />
                    </div>
                </div>

                <Divider />

                <div class="space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Performances par cours</h3>
                    <div v-for="c in ['Laravel', 'UX Design', 'React Native']" :key="c" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                        <span class="font-bold text-slate-700">{{ c }}</span>
                        <Badge :value="'Moy: 78%'" severity="info" />
                    </div>
                </div>

                <div class="p-6 bg-rose-50 border border-rose-100 rounded-[2.5rem] mt-10">
                    <div class="flex gap-4">
                        <i class="pi pi-exclamation-triangle text-rose-500 text-2xl"></i>
                        <div>
                            <h4 class="font-bold text-rose-900">Alerte Retard</h4>
                            <p class="text-xs text-rose-700 leading-relaxed mt-1">
                                4 copies attendent une correction depuis plus de 48 heures. Pensez à relancer les instructeurs concernés.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO GRADING CONSOLE
 */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 2rem 1.5rem;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #fcfcff !important;
}

/* Modal Styling */
:deep(.grading-dialog .p-dialog-header) {
    padding: 1.5rem 2rem;
    background: white;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.grading-dialog .p-dialog-content) {
    padding: 0 1.5rem;
}

/* Knob & Sliders */
:deep(.p-knob-text) {
    font-weight: 900;
    font-size: 1.5rem;
    fill: #1e293b;
}

:deep(.p-slider .p-slider-handle) {
    background: #6366F1;
    border: 2px solid white;
}

:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
}

:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
}

/* Sidebar & ScrollPanel */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
}

:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
}

/* Mobile Responsiveness */
@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
