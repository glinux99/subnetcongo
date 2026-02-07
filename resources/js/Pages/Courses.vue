<script setup>
/**
 * COURSE MANAGEMENT SYSTEM - SUBNETCONGO LMS
 * Version: 5.0.0
 * Description: Gestionnaire complet du catalogue de cours et constructeur de curriculum.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

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
import FileUpload from 'primevue/fileupload';
import InputSwitch from 'primevue/inputswitch';
import SelectButton from 'primevue/selectbutton';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import Menu from 'primevue/menu';
import Editor from 'primevue/editor';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import Slider from 'primevue/slider';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import PickList from 'primevue/picklist';
import OverlayPanel from 'primevue/overlaypanel';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const courseDialog = ref(false);
const previewSidebar = ref(false);
const deleteDialog = ref(false);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Gestion E-learning' },
    { label: 'Catalogue de Cours', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const categories = ref([
    { label: 'Développement Web', value: 'web' },
    { label: 'Design UI/UX', value: 'design' },
    { label: 'Intelligence Artificielle', value: 'ai' },
    { label: 'CyberSécurité', value: 'cyber' },
    { label: 'Marketing Digital', value: 'marketing' }
]);

const levels = ref([
    { label: 'Débutant', value: 'beginner' },
    { label: 'Intermédiaire', value: 'intermediate' },
    { label: 'Avancé', value: 'advanced' },
    { label: 'Expert', value: 'expert' }
]);

const instructors = ref([
    { name: 'Daniel Kikimba', avatar: 'https://i.pravatar.cc/150?u=dk', id: 1 },
    { name: 'Sarah Loft', avatar: 'https://i.pravatar.cc/150?u=sl', id: 2 },
    { name: 'Patrick Tshibala', avatar: 'https://i.pravatar.cc/150?u=pt', id: 3 }
]);

// --- MOCK DATA: COURSE LIST ---
const courses = ref([
    {
        id: 1,
        title: 'Mastering Laravel 11 & Vue 3',
        slug: 'mastering-laravel-vue-3',
        thumbnail: 'https://picsum.photos/seed/lar/400/225',
        instructor: 'Daniel Kikimba',
        category: 'web',
        level: 'advanced',
        price: 149.99,
        promo_price: 99.00,
        status: 'published',
        students_count: 1250,
        rating: 4.9,
        lessons_count: 45,
        total_duration: '18h 30m',
        last_update: '2024-02-01',
        revenue: 124000,
        sections: [
            { id: 1, title: 'Introduction au Framework', lessons: [
                { id: 101, title: 'Installation et Setup', type: 'video', duration: '12m' },
                { id: 102, title: 'Architecture MVC', type: 'document', duration: '5m' }
            ]},
            { id: 2, title: 'Gestion de la Base de données', lessons: [
                { id: 201, title: 'Migrations & Seeders', type: 'video', duration: '25m' },
                { id: 202, title: 'Eloquent ORM', type: 'quiz', duration: '10m' }
            ]}
        ]
    },
    {
        id: 2,
        title: 'UI/UX Design Specialist',
        slug: 'ui-ux-design-specialist',
        thumbnail: 'https://picsum.photos/seed/ui/400/225',
        instructor: 'Sarah Loft',
        category: 'design',
        level: 'beginner',
        price: 199.00,
        promo_price: null,
        status: 'draft',
        students_count: 0,
        rating: 0,
        lessons_count: 32,
        total_duration: '12h 00m',
        last_update: '2024-01-20',
        revenue: 0,
        sections: []
    }
]);

// --- UI STATE ---
const selectedCourses = ref(null);
const currentCourse = ref({});
const submitted = ref(false);
const activeTab = ref(0);

// --- FILTERS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    category: { value: null, matchMode: FilterMatchMode.IN },
    level: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- METHODS ---
const openNew = () => {
    currentCourse.value = {
        title: '',
        status: 'draft',
        level: 'beginner',
        category: 'web',
        price: 0,
        sections: [],
        seo: { title: '', description: '' }
    };
    submitted.value = false;
    activeTab.value = 0;
    courseDialog.value = true;
};

const editCourse = (course) => {
    currentCourse.value = { ...course };
    courseDialog.value = true;
};

const previewCourse = (course) => {
    currentCourse.value = { ...course };
    previewSidebar.value = true;
};

const saveCourse = () => {
    submitted.value = true;
    if (currentCourse.value.title.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Le cours a été enregistré avec succès.', life: 3000 });
        courseDialog.value = false;
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'published': return 'success';
        case 'draft': return 'secondary';
        case 'archived': return 'danger';
        default: return 'info';
    }
};

const generateSlug = () => {
    if (currentCourse.value.title) {
        currentCourse.value.slug = currentCourse.value.title.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
    }
};

const addSection = () => {
    currentCourse.value.sections.push({
        id: Date.now(),
        title: 'Nouvelle Section',
        lessons: []
    });
};

const removeSection = (id) => {
    currentCourse.value.sections = currentCourse.value.sections.filter(s => s.id !== id);
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 1000);
});

// --- STATS COMPUTED ---
const totalRevenue = computed(() => courses.value.reduce((acc, c) => acc + c.revenue, 0));
const totalStudents = computed(() => courses.value.reduce((acc, c) => acc + c.students_count, 0));

</script>

<template>
    <AppLayout>
        <Head title="Gestion du Catalogue | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2rem] shadow-2xl shadow-indigo-100 transition-transform hover:scale-105 cursor-pointer">
                            <i class="pi pi-book text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Gestion du Catalogue</h1>
                            <p class="text-slate-500 font-medium italic">Pilotez votre offre pédagogique et vos contenus multimédias.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button label="Exporter" icon="pi pi-upload" class="p-button-outlined p-button-secondary bg-white rounded-2xl" />
                    <Button label="Catégories" icon="pi pi-tags" class="p-button-outlined p-button-indigo rounded-2xl" />
                    <Button label="Créer un Cours" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-2xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- KPI DASHBOARD --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'Cours Actifs', val: courses.length, icon: 'pi-book', color: 'indigo', trend: '+2 ce mois' },
                    { label: 'Total Apprenants', val: totalStudents.toLocaleString(), icon: 'pi-users', color: 'emerald', trend: '+124 nouveaux' },
                    { label: 'Revenus Globaux', val: '$' + totalRevenue.toLocaleString(), icon: 'pi-dollar', color: 'amber', trend: '+15.2% croissance' },
                    { label: 'Taux de Complétion', val: '68%', icon: 'pi-verified', color: 'rose', trend: 'Objectif : 75%' }
                ]" :key="stat.label" class="bg-white p-7 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ stat.label }}</p>
                        <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ stat.val }}</h3>
                        <p class="text-xs font-bold mt-2" :class="`text-${stat.color}-500` ">{{ stat.trend }}</p>
                    </div>
                    <div :class="`absolute -right-4 -bottom-4 text-7xl text-${stat.color}-50 opacity-0 group-hover:opacity-100 transition-opacity` ">
                        <i :class="`pi ${stat.icon}` "></i>
                    </div>
                </div>
            </div>

            <!-- --- MAIN DATA TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">

                <Toolbar class="bg-white/50 border-none p-8">
                    <template #start>
                        <div class="flex flex-wrap gap-4 items-center">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher un cours..." class="w-full md:w-80 rounded-2xl border-none ring-1 ring-slate-200" />
                            </IconField>
                            <MultiSelect v-model="filters['category'].value" :options="categories" optionLabel="label" optionValue="value" placeholder="Toutes les catégories" class="w-64 rounded-xl" />
                        </div>
                    </template>
                    <template #end>
                        <div class="flex gap-2">
                            <SelectButton v-model="filters['status'].value" :options="[{label: 'Tous', value: null}, {label: 'Publiés', value: 'published'}, {label: 'Brouillons', value: 'draft'}]" />
                        </div>
                    </template>
                </Toolbar>

                <DataTable
                    :value="courses"
                    v-model:selection="selectedCourses"
                    :paginator="true"
                    :rows="10"
                    dataKey="id"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    class="p-datatable-custom"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} cours"
                >
                    <template #empty> <div class="p-10 text-center text-slate-400 font-bold italic">Aucun cours ne correspond à vos critères.</div> </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column header="Cours & Instructeur" style="min-width: 25rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <img :src="data.thumbnail" class="w-20 h-12 rounded-xl object-cover shadow-md border border-slate-100" />
                                    <Badge v-if="data.promo_price" value="PROMO" severity="danger" class="absolute -top-2 -right-2 text-[8px]" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 tracking-tight leading-tight line-clamp-1">{{ data.title }}</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Avatar icon="pi pi-user" size="small" shape="circle" class="bg-slate-100 text-[8px]" />
                                        <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.instructor }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État" sortable>
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] font-black uppercase tracking-[0.2em]" />
                        </template>
                    </Column>

                    <Column field="price" header="Tarification" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span v-if="data.promo_price" class="text-rose-500 font-black">${{ data.promo_price }}</span>
                                <span :class="{'line-through text-slate-300 text-xs': data.promo_price}" class="font-bold text-slate-700">${{ data.price }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="students_count" header="Inscrits" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-indigo-400"></i>
                                <span class="font-black text-slate-700">{{ data.students_count.toLocaleString() }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="rating" header="Avis" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-1">
                                <i class="pi pi-star-fill text-amber-400 text-xs"></i>
                                <span class="font-bold text-slate-800">{{ data.rating > 0 ? data.rating : 'N/A' }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Aperçu Apprenant'" @click="previewCourse(data)" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Éditer le cours'" @click="editCourse(data)" />
                                <Button icon="pi pi-copy" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Dupliquer'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Supprimer'" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- MODAL D'ÉDITION COMPLEXE (MULTI-TAB) --- -->
        <Dialog v-model:visible="courseDialog" :style="{width: '1200px'}" header="Configuration Avancée du Cours" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <TabView v-model:activeIndex="activeTab" class="custom-tabs mt-4">

                <!-- ONGLET 1 : INFORMATIONS GÉNÉRALES -->
                <TabPanel>
                    <template #header><i class="pi pi-info-circle mr-2"></i> Basique</template>
                    <div class="grid grid-cols-12 gap-8 pt-6">
                        <div class="col-span-12 lg:col-span-8 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre du cours</label>
                                <InputText v-model="currentCourse.title" @input="generateSlug" placeholder="Ex: Mastering Vue 3 & Composition API" class="text-xl font-bold p-4 rounded-2xl border-slate-200" />
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Description détaillée</label>
                                <Editor v-model="currentCourse.description" editorStyle="height: 320px" />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 space-y-6 bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Image de couverture</label>
                                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-white group hover:border-indigo-400 transition-colors cursor-pointer">
                                    <img v-if="currentCourse.thumbnail" :src="currentCourse.thumbnail" class="w-full rounded-lg mb-4" />
                                    <i v-else class="pi pi-image text-4xl text-slate-200 mb-2"></i>
                                    <FileUpload mode="basic" chooseLabel="Changer la vignette" class="p-button-sm p-button-outlined w-full" />
                                </div>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Catégorie Principale</label>
                                <Dropdown v-model="currentCourse.category" :options="categories" optionLabel="label" optionValue="value" class="rounded-xl" />
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Niveau de difficulté</label>
                                <SelectButton v-model="currentCourse.level" :options="levels" optionLabel="label" optionValue="value" />
                            </div>

                            <div class="p-5 bg-indigo-900 rounded-2xl text-white shadow-xl">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold uppercase">Statut Publication</span>
                                    <Tag :value="currentCourse.status" severity="warning" />
                                </div>
                                <Dropdown v-model="currentCourse.status" :options="['draft', 'published', 'archived']" class="w-full bg-white/10 border-none text-white rounded-xl" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 2 : CURRICULUM BUILDER (DRAG & DROP SIMULATED) -->
                <TabPanel>
                    <template #header><i class="pi pi-list mr-2"></i> Curriculum</template>
                    <div class="pt-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 tracking-tight">Constructeur de Programme</h3>
                                <p class="text-sm text-slate-400">Organisez vos leçons par sections logiques.</p>
                            </div>
                            <Button label="Nouvelle Section" icon="pi pi-plus" class="p-button-outlined rounded-xl" @click="addSection" />
                        </div>

                        <div class="space-y-6 max-w-4xl mx-auto pb-10">
                            <div v-for="(section, sIdx) in currentCourse.sections" :key="section.id" class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                                <div class="p-5 bg-slate-50 flex justify-between items-center border-b border-slate-100">
                                    <div class="flex items-center gap-3 flex-1">
                                        <i class="pi pi-bars text-slate-300 cursor-move"></i>
                                        <InputText v-model="section.title" class="font-black text-slate-700 bg-transparent border-none p-0 focus:ring-0 text-lg w-full" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button icon="pi pi-plus-circle" label="Leçon" class="p-button-text p-button-sm p-button-success" />
                                        <Button icon="pi pi-trash" class="p-button-text p-button-sm p-button-danger" @click="removeSection(section.id)" />
                                    </div>
                                </div>

                                <div class="p-4 space-y-2 min-h-[50px]">
                                    <div v-for="(lesson, lIdx) in section.lessons" :key="lesson.id" class="flex items-center justify-between p-4 bg-slate-50/30 border border-slate-100 rounded-2xl hover:bg-slate-50 transition-colors group">
                                        <div class="flex items-center gap-4">
                                            <i :class="['pi', lesson.type === 'video' ? 'pi-play-circle text-indigo-500' : lesson.type === 'quiz' ? 'pi-question-circle text-rose-500' : 'pi-file text-emerald-500']"></i>
                                            <span class="text-sm font-bold text-slate-600">{{ lesson.title }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-black text-slate-300 uppercase">{{ lesson.duration }}</span>
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    </div>
                                    <div v-if="section.lessons.length === 0" class="text-center py-6 text-slate-400 text-xs italic">Aucune leçon dans cette section.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 3 : TARIFICATION & ACCÈS -->
                <TabPanel>
                    <template #header><i class="pi pi-dollar mr-2"></i> Tarification</template>
                    <div class="pt-10 max-w-3xl mx-auto space-y-10">
                        <div class="grid grid-cols-2 gap-10">
                            <div class="space-y-6">
                                <h4 class="text-lg font-black text-slate-800 flex items-center gap-3">
                                    <span class="w-1 h-6 bg-indigo-600 rounded-full"></span>
                                    Vente Directe
                                </h4>
                                <div class="field">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Prix Standard ($)</label>
                                    <InputNumber v-model="currentCourse.price" mode="currency" currency="USD" locale="en-US" class="rounded-xl" />
                                </div>
                                <div class="field">
                                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-2">Prix Promotionnel ($)</label>
                                    <InputNumber v-model="currentCourse.promo_price" mode="currency" currency="USD" locale="en-US" class="rounded-xl" />
                                    <small class="text-slate-400 mt-2 block italic">Laissez vide si aucune promotion n'est active.</small>
                                </div>
                            </div>

                            <div class="space-y-6 border-l border-slate-100 pl-10">
                                <h4 class="text-lg font-black text-slate-800 flex items-center gap-3">
                                    <span class="w-1 h-6 bg-emerald-600 rounded-full"></span>
                                    Accès & Privilèges
                                </h4>
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-700">Cours Gratuit</span>
                                        <span class="text-[10px] text-slate-400">Accessible par tout le monde.</span>
                                    </div>
                                    <InputSwitch :modelValue="currentCourse.price === 0" />
                                </div>
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-700">Drip Content</span>
                                        <span class="text-[10px] text-slate-400">Libération progressive des leçons.</span>
                                    </div>
                                    <InputSwitch :modelValue="false" />
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <div class="bg-amber-50 p-6 rounded-[2rem] border border-amber-100 flex gap-6 items-center">
                            <div class="w-16 h-16 bg-amber-400 rounded-2xl flex items-center justify-center text-white shadow-lg">
                                <i class="pi pi-percentage text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="font-black text-amber-900">Affiliation & Coupons</h4>
                                <p class="text-sm text-amber-700">Vous pouvez générer des codes promos spécifiques pour ce cours dans le module Marketing.</p>
                                <Button label="Gérer les coupons" class="p-button-text p-button-sm p-0 mt-2 font-bold" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET 4 : SEO & AVANCÉ -->
                <TabPanel>
                    <template #header><i class="pi pi-google mr-2"></i> SEO & Visibilité</template>
                    <div class="pt-8 grid grid-cols-12 gap-10">
                        <div class="col-span-12 lg:col-span-7 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Slug d'URL personnalisé</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon bg-slate-100 border-none font-bold text-slate-400">/courses/</span>
                                    <InputText v-model="currentCourse.slug" class="border-none ring-1 ring-slate-200" />
                                </div>
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Meta Title (SEO)</label>
                                <InputText v-model="currentCourse.title" placeholder="Titre pour les moteurs de recherche" />
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Meta Description (SEO)</label>
                                <Textarea v-model="currentCourse.seo_description" rows="4" placeholder="Résumé pour Google..." />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-5">
                            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-xl relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-10"><i class="pi pi-google text-6xl"></i></div>
                                <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">Aperçu Google</h4>
                                <div class="space-y-1">
                                    <p class="text-indigo-700 text-lg font-medium hover:underline cursor-pointer">{{ currentCourse.title || 'Titre du cours' }}</p>
                                    <p class="text-emerald-700 text-xs">subnetcongo.cd › courses › {{ currentCourse.slug || 'slug' }}</p>
                                    <p class="text-slate-600 text-sm line-clamp-2">{{ currentCourse.seo_description || 'Rédigez une description pour voir l\'aperçu du référencement s\'afficher ici.' }}</p>
                                </div>
                                <Divider class="my-6" />
                                <div class="flex items-center gap-3">
                                    <InputSwitch :modelValue="true" />
                                    <span class="text-xs font-bold text-slate-500">Indexer ce cours sur les moteurs de recherche</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full px-6 pb-6 border-t border-slate-50 pt-4">
                    <span class="text-xs text-slate-400 italic">Dernière modification : {{ currentCourse.last_update || 'Maintenant' }}</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="courseDialog = false" />
                        <Button label="Enregistrer le Cours" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-200" @click="saveCourse" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- SIDEBAR : APERÇU ÉTUDIANT --- -->
        <Sidebar v-model:visible="previewSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div v-if="currentCourse" class="flex flex-col h-full bg-white">
                <div class="relative aspect-video">
                    <img :src="currentCourse.thumbnail" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center group cursor-pointer">
                        <i class="pi pi-play-circle text-white text-6xl opacity-80 hover:opacity-100 transition-all scale-100 hover:scale-110"></i>
                    </div>
                </div>

                <ScrollPanel class="flex-1 p-8">
                    <div class="space-y-6">
                        <div class="flex justify-between items-start">
                            <Tag :value="currentCourse.category" severity="info" class="rounded-lg uppercase text-[9px] font-black" />
                            <div class="flex items-center gap-1">
                                <i class="pi pi-star-fill text-amber-400 text-sm"></i>
                                <span class="font-bold">{{ currentCourse.rating }}</span>
                            </div>
                        </div>

                        <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-tight">{{ currentCourse.title }}</h2>

                        <div class="flex items-center gap-4 py-4 border-y border-slate-100">
                            <Avatar :image="instructors[0].avatar" shape="circle" size="large" />
                            <div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Instructeur</p>
                                <p class="font-bold text-slate-800">{{ currentCourse.instructor }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest">Contenu du programme</h4>
                            <Accordion :multiple="true">
                                <AccordionTab v-for="section in currentCourse.sections" :key="section.id" :header="section.title">
                                    <div class="space-y-3">
                                        <div v-for="lesson in section.lessons" :key="lesson.id" class="flex items-center justify-between text-xs text-slate-600">
                                            <div class="flex items-center gap-2">
                                                <i class="pi pi-lock text-[10px] text-slate-300"></i>
                                                <span>{{ lesson.title }}</span>
                                            </div>
                                            <span class="text-slate-400">{{ lesson.duration }}</span>
                                        </div>
                                    </div>
                                </AccordionTab>
                            </Accordion>
                        </div>
                    </div>
                </ScrollPanel>

                <div class="p-8 border-t border-slate-100 bg-slate-50/50">
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-4xl font-black text-slate-900">${{ currentCourse.promo_price || currentCourse.price }}</span>
                        <span v-if="currentCourse.promo_price" class="text-slate-400 line-through font-bold">${{ currentCourse.price }}</span>
                    </div>
                    <Button label="Accéder au cours (Admin Mode)" class="w-full p-button-lg rounded-[2rem] font-black" />
                </div>
            </div>
        </Sidebar>


</AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO COURSES ENGINE
 */

/* DataTable Styling */
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
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #f8fafc !important;
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

/* Sidebar Customization */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
}

/* Editor Styling */
:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
}

:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
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
