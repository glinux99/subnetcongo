<script setup>
/**
 * LEARNING PATHS & CAREER TRACKS MANAGER - SUBNETCONGO LMS
 * Version: 6.0.0
 * Description: Système de conception de parcours certifiants et bundles de cours.
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
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import InputSwitch from 'primevue/inputswitch';
import SelectButton from 'primevue/selectbutton';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import OrderList from 'primevue/orderlist';
import PickList from 'primevue/picklist';
import OverlayPanel from 'primevue/overlaypanel';
import Fieldset from 'primevue/fieldset';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const pathDialog = ref(false);
const statsSidebar = ref(false);
const activeTab = ref(0);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Gestion E-learning' },
    { label: 'Parcours d\'Apprentissage', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const availableCourses = ref([
    { id: 1, title: 'HTML/CSS Fondamentaux', duration: '5h', level: 'Beginner' },
    { id: 2, title: 'JavaScript Moderne', duration: '12h', level: 'Beginner' },
    { id: 3, title: 'Vue.js 3 Pro', duration: '15h', level: 'Intermediate' },
    { id: 4, title: 'Laravel 11 Backend', duration: '20h', level: 'Advanced' },
    { id: 5, title: 'Déploiement AWS/Docker', duration: '8h', level: 'Expert' }
]);

// --- MOCK DATA: LEARNING PATHS ---
const learningPaths = ref([
    {
        id: 1,
        name: 'Expert Développeur Fullstack PHP',
        slug: 'expert-fullstack-php',
        category: 'Développement',
        status: 'Active',
        total_courses: 5,
        total_duration: '60h',
        students_count: 850,
        completion_rate: 42,
        price: 450,
        badge_icon: 'pi pi-bolt',
        instructor: 'Daniel Kikimba',
        courses: [
            { id: 1, title: 'HTML/CSS Fondamentaux', order: 1, is_mandatory: true },
            { id: 2, title: 'JavaScript Moderne', order: 2, is_mandatory: true },
            { id: 4, title: 'Laravel 11 Backend', order: 3, is_mandatory: true }
        ]
    },
    {
        id: 2,
        name: 'Mastering Digital Marketing',
        slug: 'mastering-marketing',
        category: 'Business',
        status: 'Draft',
        total_courses: 3,
        total_duration: '25h',
        students_count: 0,
        completion_rate: 0,
        price: 299,
        badge_icon: 'pi pi-chart-line',
        instructor: 'Sarah Loft',
        courses: []
    }
]);

// --- UI STATE ---
const path = ref({
    name: '',
    slug: '',
    courses: [],
    settings: {
        is_linear: true,
        allow_skip: false,
        show_certificate: true
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// --- METHODS ---
const openNew = () => {
    path.value = {
        name: '',
        slug: '',
        category: 'Développement',
        status: 'Draft',
        price: 0,
        courses: [],
        settings: { is_linear: true, show_certificate: true }
    };
    activeTab.value = 0;
    pathDialog.value = true;
};

const editPath = (data) => {
    path.value = JSON.parse(JSON.stringify(data));
    pathDialog.value = true;
};

const generateSlug = () => {
    if (path.value.name) {
        path.value.slug = path.value.name.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }
};

const savePath = () => {
    toast.add({ severity: 'success', summary: 'Parcours Enregistré', detail: 'La structure du parcours a été mise à jour.', life: 3000 });
    pathDialog.value = false;
};

const removeCourseFromPath = (id) => {
    path.value.courses = path.value.courses.filter(c => c.id !== id);
};

// --- CHART DATA FOR STATISTICS ---
const completionChartData = ref({
    labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'],
    datasets: [{
        label: 'Taux de progression (%)',
        data: [10, 25, 45, 70],
        borderColor: '#6366F1',
        tension: 0.4,
        fill: true,
        backgroundColor: 'rgba(99, 102, 241, 0.1)'
    }]
});

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 1000);
});

</script>

<template>
    <AppLayout>
        <Head title="Parcours d'Apprentissage | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100">
                            <i class="pi pi-directions text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Career Path Builder</h1>
                            <p class="text-slate-500 font-medium italic">Créez des parcours guidés pour transformer vos élèves en experts.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Analytique Cohorte" icon="pi pi-chart-line" class="p-button-text p-button-secondary rounded-xl" @click="statsSidebar = true" />
                    <Divider layout="vertical" />
                    <Button label="Créer un Parcours" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div v-for="stat in [
                    { label: 'Parcours Actifs', val: '8', icon: 'pi-map', color: 'indigo' },
                    { label: 'Élèves Engagés', val: '2,450', icon: 'pi-users', color: 'emerald' },
                    { label: 'Temps Moyen d\'Achèvement', val: '4.2 mois', icon: 'pi-clock', color: 'amber' },
                    { label: 'CA Bundles', val: '$124,000', icon: 'pi-wallet', color: 'rose' }
                ]" :key="stat.label" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-6 group hover:shadow-xl transition-all duration-500">
                    <div :class="`w-16 h-16 bg-${stat.color}-50 text-${stat.color}-600 rounded-[1.5rem] flex items-center justify-center group-hover:rotate-12 transition-transform` ">
                        <i :class="['pi text-3xl', stat.icon]"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="text-3xl font-black text-slate-800 tracking-tight">{{ stat.val }}</p>
                    </div>
                </div>
            </div>

            <!-- --- PATHS DATA TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-8 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-xl font-black text-slate-800 tracking-tight italic">Catalogue des Parcours</h3>
                    <div class="flex gap-4">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher un parcours..." class="w-80 rounded-2xl" />
                        </IconField>
                    </div>
                </div>

                <DataTable :value="learningPaths" :paginator="true" :rows="10" :filters="filters" class="p-datatable-custom" responsiveLayout="stack">
                    <Column field="name" header="Nom du Parcours" style="min-width: 22rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center shadow-sm">
                                    <i :class="data.badge_icon" class="text-xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 line-clamp-1">{{ data.name }}</span>
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-tighter">{{ data.category }} • {{ data.total_courses }} Cours</span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="students_count" header="Inscrits" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-indigo-400"></i>
                                <span class="font-bold text-slate-700">{{ data.students_count.toLocaleString() }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="completion_rate" header="Taux de réussite">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3 w-32">
                                <ProgressBar :value="data.completion_rate" :showValue="false" style="height: 6px" class="flex-1" />
                                <span class="text-[10px] font-bold text-slate-500">{{ data.completion_rate }}%</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="price" header="Tarif Bundle">
                        <template #body="{ data }">
                            <span class="font-black text-slate-900">${{ data.price }}</span>
                        </template>
                    </Column>
                    <Column field="status" header="Statut">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="data.status === 'Active' ? 'success' : 'secondary'" class="text-[9px] font-black uppercase tracking-widest" />
                        </template>
                    </Column>
                    <Column header="Actions" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Prévisualisation'" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editPath(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- PATH BUILDER DIALOG (THE BIG ONE) --- -->
        <Dialog v-model:visible="pathDialog" :style="{width: '1200px'}" header="Conception du Parcours d'Apprentissage" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <TabView v-model:activeIndex="activeTab" class="custom-tabs mt-4">

                <!-- ONGLET 1 : CONFIGURATION GLOBALE -->
                <TabPanel>
                    <template #header><i class="pi pi-info-circle mr-2"></i> Identité & Vente</template>
                    <div class="grid grid-cols-12 gap-10 pt-6">
                        <div class="col-span-12 lg:col-span-8 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Nom du Parcours</label>
                                <InputText v-model="path.name" @input="generateSlug" placeholder="Ex: Devenir Expert Mobile" class="text-xl font-bold p-4 rounded-2xl" />
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Description Marketing (Landing Page)</label>
                                <Textarea v-model="path.description" rows="5" placeholder="Décrivez les objectifs de ce parcours..." class="rounded-2xl p-4" />
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Prix du Bundle ($)</label>
                                    <InputNumber v-model="path.price" mode="currency" currency="USD" locale="en-US" class="rounded-2xl" />
                                    <small class="text-slate-400 mt-2 block italic">Ce prix remplace le prix individuel des cours.</small>
                                </div>
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Catégorie</label>
                                    <Dropdown v-model="path.category" :options="['Développement', 'Design', 'Business']" class="rounded-xl" />
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 space-y-6 bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4">Paramètres du Parcours</h4>
                            <div class="bg-white p-4 rounded-2xl border border-slate-100 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-700">Progression Linéaire</p>
                                        <p class="text-[9px] text-slate-400 uppercase">Oblige l'ordre des cours</p>
                                    </div>
                                    <InputSwitch v-model="path.settings.is_linear" />
                                </div>
                                <Divider />
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-slate-700">Certificat Global</p>
                                        <p class="text-[9px] text-slate-400 uppercase">Délivré en fin de parcours</p>
                                    </div>
                                    <InputSwitch v-model="path.settings.show_certificate" />
                                </div>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Icône du Badge</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-lg">
                                        <i :class="path.badge_icon || 'pi pi-star'" class="text-2xl"></i>
                                    </div>
                                    <InputText v-model="path.badge_icon" placeholder="pi pi-star" class="flex-1" />
                                </div>
                            </div>

                            <div class="p-5 bg-slate-900 rounded-2xl text-white shadow-xl relative overflow-hidden">
                                <i class="pi pi-shield absolute -right-4 -bottom-4 text-6xl text-white/5"></i>
                                <label class="text-[10px] font-black uppercase text-slate-500 block mb-3">Statut de Visibilité</label>
                                <SelectButton v-model="path.status" :options="['Draft', 'Active', 'Archived']" class="custom-select-dark" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 2 : SEQUENCE BUILDER (DRAG & DROP LIST) -->
                <TabPanel>
                    <template #header><i class="pi pi-sort-amount-down mr-2"></i> Séquence de cours</template>
                    <div class="grid grid-cols-12 gap-10 pt-6">
                        <!-- Left: Available Courses -->
                        <div class="col-span-12 lg:col-span-5 space-y-4">
                            <h3 class="text-lg font-black text-slate-800">Bibliothèque de Cours</h3>
                            <div class="p-4 bg-slate-50 rounded-[2rem] border border-slate-100 max-h-[500px] overflow-y-auto space-y-3">
                                <div v-for="course in availableCourses" :key="course.id" class="p-4 bg-white border border-slate-100 rounded-2xl flex items-center justify-between group hover:border-indigo-400 transition-all shadow-sm">
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">{{ course.title }}</p>
                                        <p class="text-[10px] text-slate-400 font-black uppercase">{{ course.level }} • {{ course.duration }}</p>
                                    </div>
                                    <Button icon="pi pi-plus" class="p-button-rounded p-button-text p-button-indigo opacity-0 group-hover:opacity-100" />
                                </div>
                            </div>
                        </div>

                        <!-- Right: Path Structure -->
                        <div class="col-span-12 lg:col-span-7 space-y-4">
                            <h3 class="text-lg font-black text-slate-800">Structure du Parcours</h3>
                            <div class="relative pl-10 space-y-6 before:content-[''] before:absolute before:left-[1.25rem] before:top-4 before:bottom-4 before:w-1 before:bg-indigo-100 before:rounded-full">
                                <div v-for="(c, index) in path.courses" :key="c.id" class="relative group">
                                    <!-- Timeline Bullet -->
                                    <div class="absolute -left-[2.1rem] top-1/2 -translate-y-1/2 w-6 h-6 bg-white border-4 border-indigo-600 rounded-full z-10"></div>

                                    <div class="p-5 bg-white border border-slate-200 rounded-3xl shadow-sm flex items-center justify-between group-hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-4">
                                            <Badge :value="index + 1" severity="info" class="rounded-lg" />
                                            <div>
                                                <span class="font-black text-slate-800 text-sm leading-tight">{{ c.title }}</span>
                                                <div class="flex items-center gap-3 mt-1">
                                                    <Tag icon="pi pi-lock" value="Prerequis" v-if="index > 0" severity="warning" class="text-[8px]" />
                                                    <span class="text-[9px] font-bold text-slate-400 uppercase">Étape {{ index + 1 }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex gap-1">
                                            <Button icon="pi pi-arrow-up" class="p-button-text p-button-secondary p-button-sm" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="removeCourseFromPath(c.id)" />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="path.courses.length === 0" class="flex flex-col items-center justify-center py-20 bg-slate-50 rounded-[2.5rem] border border-dashed border-slate-200 text-slate-400">
                                    <i class="pi pi-plus-circle text-4xl mb-4"></i>
                                    <p class="font-black italic">Glissez des cours ici pour construire la carrière de vos élèves.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 3 : SEO & MARKETING -->
                <TabPanel>
                    <template #header><i class="pi pi-google mr-2"></i> SEO & Visibilité</template>
                    <div class="pt-8 grid grid-cols-12 gap-10">
                        <div class="col-span-12 lg:col-span-7 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Slug d'URL personnalisé</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon bg-slate-100 border-none font-bold text-slate-400">/track/</span>
                                    <InputText v-model="path.slug" class="border-none ring-1 ring-slate-200" />
                                </div>
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Meta Title (SEO)</label>
                                <InputText v-model="path.name" placeholder="Titre pour Google" />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-5">
                            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-xl relative overflow-hidden">
                                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">Aperçu Google Search</h4>
                                <div class="space-y-1">
                                    <p class="text-indigo-700 text-lg font-medium hover:underline cursor-pointer">{{ path.name || 'Titre du parcours' }}</p>
                                    <p class="text-emerald-700 text-xs">subnetcongo.cd › tracks › {{ path.slug || 'slug' }}</p>
                                    <p class="text-slate-600 text-sm line-clamp-2">Découvrez le parcours complet pour devenir un expert certifié en {{ path.name }} chez SubnetCongo.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full px-6 pb-6 border-t border-slate-50 pt-4">
                    <span class="text-xs text-slate-400 italic">ID Parcours : PATH-{{ path.id || 'NEW' }}</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="pathDialog = false" />
                        <Button label="Enregistrer le Parcours" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="savePath" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- SIDEBAR: ADVANCED ANALYTICS --- -->
        <Sidebar v-model:visible="statsSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div class="p-10 space-y-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100">
                        <i class="pi pi-chart-line text-white"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Performance Parcours</h2>
                </div>

                <div class="space-y-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Rétention moyenne des élèves</h3>
                    <div class="h-64">
                        <Chart type="line" :data="completionChartData" :options="{ maintainAspectRatio: false }" class="h-full" />
                    </div>
                </div>

                <Divider />

                <div class="space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Étape la plus difficile (Taux d'abandon)</h3>
                    <div v-for="c in ['HTML Basics', 'JS Advanced', 'Laravel API']" :key="c" class="p-4 bg-slate-50 rounded-2xl flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-slate-700 text-sm">{{ c }}</span>
                            <Badge value="Haut risque" severity="danger" v-if="c === 'Laravel API'" />
                        </div>
                        <ProgressBar :value="c === 'HTML Basics' ? 95 : c === 'JS Advanced' ? 65 : 32" :showValue="false" style="height: 4px" />
                    </div>
                </div>
            </div>
        </Sidebar>


</AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO CAREER PATH BUILDER
 */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 2rem 1.5rem;
    color: #475569;
    font-size: 0.7rem;
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

/* Tabs Styling */
:deep(.custom-tabs .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 2rem;
}

:deep(.custom-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #94a3b8;
    padding: 1.5rem;
}

:deep(.custom-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

/* Timeline/Builder Effect */
.before\:bg-indigo-100::before {
    background-color: #e0e7ff;
}

/* Sidebar & ScrollPanel */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
}

:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 12px;
    border-color: #e2e8f0;
}

/* SelectButton customization */
:deep(.custom-select-dark .p-button) {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
    font-size: 0.7rem;
    font-weight: 700;
}

:deep(.custom-select-dark .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

/* Scrollbar Smooth */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
