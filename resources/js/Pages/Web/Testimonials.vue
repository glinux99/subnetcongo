<script setup>
/**
 * TESTIMONIALS MANAGEMENT SYSTEM - SUBNETCONGO LMS
 * Version: 2.0.0
 * Description: Gestion avancée de la preuve sociale et de la satisfaction client.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import InputSwitch from 'primevue/inputswitch';
import FileUpload from 'primevue/fileupload';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ProgressBar from 'primevue/progressbar';
import Divider from 'primevue/divider';
import SelectButton from 'primevue/selectbutton';
import Chart from 'primevue/chart';
import Sidebar from 'primevue/sidebar';
import Calendar from 'primevue/calendar';
import MultiSelect from 'primevue/multiselect';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import Toolbar from 'primevue/toolbar';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';
import Breadcrumb from 'primevue/breadcrumb';
import Tooltip from 'primevue/tooltip';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const exportMenu = ref();

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Marketing' },
    { label: 'Témoignages', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const courses = ref([
    { label: 'Mastering Vue 3', value: 'vue3', category: 'Dev' },
    { label: 'Laravel Fullstack', value: 'laravel', category: 'Dev' },
    { label: 'UI/UX Design Specialist', value: 'uiux', category: 'Design' },
    { label: 'CyberSecurité Pro', value: 'cyber', category: 'Network' },
    { label: 'Mobile App React Native', value: 'mobile', category: 'Dev' }
]);

const statuses = ref([
    { label: 'Publié', value: 'active', severity: 'success', icon: 'pi pi-check-circle' },
    { label: 'En attente', value: 'pending', severity: 'warning', icon: 'pi pi-clock' },
    { label: 'Refusé', value: 'rejected', severity: 'danger', icon: 'pi pi-times-circle' },
    { label: 'Archivé', value: 'archived', severity: 'secondary', icon: 'pi pi-box' }
]);

// --- GENERATION DE DONNÉES MASSIVES (Simulation de 40 entrées) ---
const generateMockTestimonials = () => {
    const data = [];
    const names = ['Arnaud Ngoy', 'Clarisse Meta', 'Kevin Ndongala', 'Sarah Loft', 'Jean Mwamba', 'Marie Kabamba', 'Patrick Tshibala', 'Sophie Martin'];
    const comments = [
        "La meilleure plateforme en RDC !",
        "Grâce à Subnet, j'ai trouvé un emploi de dev.",
        "Les modules sont très bien structurés.",
        "Le support technique est incroyablement rapide.",
        "Contenu de haute qualité pédagogique."
    ];

    for (let i = 1; i <= 40; i++) {
        data.push({
            id: i,
            student_name: names[i % names.length] + (i > 8 ? ' ' + i : ''),
            student_avatar: `https://i.pravatar.cc/150?u=${i}`,
            course: courses.value[i % courses.value.length].value,
            rating: Math.floor(Math.random() * 2) + 4, // Entre 4 et 5 étoiles
            comment: comments[i % comments.length] + " Je recommande vivement l'accompagnement personnalisé de SubnetCongo.",
            status: i % 5 === 0 ? 'pending' : 'active',
            date: new Date(2024, 0, i).toLocaleDateString('fr-FR'),
            is_featured: i % 10 === 0,
            verified_purchase: true,
            sentiment_score: 95
        });
    }
    return data;
};

const testimonials = ref(generateMockTestimonials());

// --- STATES ---
const selectedTestimonials = ref(null);
const testimonialDialog = ref(false);
const previewSidebar = ref(false);
const deleteDialog = ref(false);
const submitted = ref(false);
const testimonial = ref({});

// --- FILTERS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    student_name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    course: { value: null, matchMode: FilterMatchMode.IN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    rating: { value: null, matchMode: FilterMatchMode.EQUALS },
    date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] }
});

// --- CHART DATA (DISTRIBUTION) ---
const chartData = ref({
    labels: ['5 Étoiles', '4 Étoiles', '3 Étoiles', '2 Étoiles', '1 Étoile'],
    datasets: [{
        data: [25, 12, 3, 0, 0],
        backgroundColor: ['#6366F1', '#818CF8', '#A5B4FC', '#C7D2FE', '#E0E7FF'],
        borderRadius: 8
    }]
});

const chartOptions = ref({
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, grid: { display: false } } }
});

// --- COMPUTED ---
const avgRating = computed(() => {
    const sum = testimonials.value.reduce((acc, curr) => acc + curr.rating, 0);
    return (sum / testimonials.value.length).toFixed(1);
});

const pendingCount = computed(() => testimonials.value.filter(t => t.status === 'pending').length);

// --- METHODS ---
const openNew = () => {
    testimonial.value = {
        rating: 5,
        status: 'pending',
        is_featured: false,
        verified_purchase: true,
        date: new Date().toLocaleDateString('fr-FR')
    };
    submitted.value = false;
    testimonialDialog.value = true;
};

const editTestimonial = (data) => {
    testimonial.value = { ...data };
    testimonialDialog.value = true;
};

const viewInSidebar = (data) => {
    testimonial.value = { ...data };
    previewSidebar.value = true;
};

const saveTestimonial = () => {
    submitted.value = true;
    if (testimonial.value.student_name?.trim() && testimonial.value.comment?.trim()) {
        if (testimonial.value.id) {
            const index = testimonials.value.findIndex(t => t.id === testimonial.value.id);
            testimonials.value[index] = testimonial.value;
            toast.add({ severity: 'success', summary: 'Mis à jour', detail: 'Témoignage actualisé', life: 3000 });
        } else {
            testimonial.value.id = Date.now();
            testimonials.value.unshift(testimonial.value);
            toast.add({ severity: 'success', summary: 'Créé', detail: 'Témoignage ajouté manuellement', life: 3000 });
        }
        testimonialDialog.value = false;
    }
};

const bulkApprove = () => {
    selectedTestimonials.value.forEach(t => t.status = 'active');
    toast.add({ severity: 'success', summary: 'Approbation groupée', detail: `${selectedTestimonials.value.length} avis approuvés`, life: 3000 });
    selectedTestimonials.value = null;
};

const confirmDelete = (data) => {
    testimonial.value = data;
    deleteDialog.value = true;
};

const deleteEntry = () => {
    testimonials.value = testimonials.value.filter(t => t.id !== testimonial.value.id);
    deleteDialog.value = false;
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Le témoignage a été retiré' });
};

const getStatusSeverity = (val) => statuses.value.find(s => s.value === val)?.severity;

const exportCSV = () => {
    // Logique export CSV
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'Le fichier CSV est en cours de génération...' });
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 1000);
});

const exportMenuItems = [
    { label: 'Format CSV', icon: 'pi pi-file-excel', command: exportCSV },
    { label: 'Format PDF', icon: 'pi pi-file-pdf' },
    { separator: true },
    { label: 'Google Sheets', icon: 'pi pi-external-link' }
];

</script>

<template>
    <AppLayout>
        <Head title="Marketing & Témoignages" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen font-sans">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs mb-2" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-4 rounded-[2rem] shadow-2xl shadow-indigo-100 rotate-3 hover:rotate-0 transition-transform cursor-pointer">
                            <i class="pi pi-comments text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-5xl font-black text-slate-900 tracking-tighter italic">Réputation & Avis</h1>
                            <p class="text-slate-500 font-medium">Gérez la voix de vos apprenants et valorisez l'excellence de <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button label="Exporter" icon="pi pi-download" class="p-button-outlined p-button-secondary bg-white rounded-2xl" @click="exportMenu.toggle($event)" />
                    <Menu ref="exportMenu" :model="exportMenuItems" :popup="true" />
                    <Button label="Nouveau Témoignage" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-2xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- ANALYTICS DASHBOARD --- -->
            <div class="grid grid-cols-12 gap-8 mb-12">

                <!-- Distribution Chart -->
                <div class="col-span-12 lg:col-span-5 bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-lg font-black text-slate-800 mb-6 uppercase tracking-widest">Distribution des Notes</h3>
                        <div class="h-48">
                            <Chart type="bar" :data="chartData" :options="chartOptions" class="h-full" />
                        </div>
                    </div>
                    <i class="pi pi-chart-bar absolute -right-6 -bottom-6 text-9xl text-slate-50 opacity-10"></i>
                </div>

                <!-- KPI Cards -->
                <div class="col-span-12 lg:col-span-7 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-indigo-900 rounded-[3rem] p-8 text-white relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-6 opacity-20 group-hover:scale-125 transition-transform">
                            <i class="pi pi-star-fill text-6xl"></i>
                        </div>
                        <p class="text-indigo-200 text-xs font-black uppercase tracking-[0.2em] mb-2">Satisfaction Globale</p>
                        <div class="flex items-baseline gap-3">
                            <h2 class="text-6xl font-black">{{ avgRating }}</h2>
                            <span class="text-indigo-400 font-bold">/ 5.0</span>
                        </div>
                        <div class="mt-6 flex items-center gap-2">
                            <Rating :modelValue="parseFloat(avgRating)" readonly :cancel="false" />
                            <span class="text-xs text-indigo-300 font-bold">+0.2 ce mois</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-[3rem] p-8 border border-slate-100 shadow-sm flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-black uppercase tracking-[0.2em] mb-1">Avis en attente</p>
                                <h2 class="text-5xl font-black text-amber-500">{{ pendingCount }}</h2>
                            </div>
                            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500">
                                <i class="pi pi-clock text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <ProgressBar :value="pendingCount * 5" :showValue="false" class="flex-1" style="height: 8px" />
                            <span class="text-[10px] font-bold text-slate-400">Action requise</span>
                        </div>
                    </div>

                    <div class="col-span-full bg-emerald-500 rounded-[3rem] p-6 text-white flex items-center justify-between shadow-xl shadow-emerald-100">
                        <div class="flex items-center gap-6">
                            <div class="bg-white/20 p-4 rounded-2xl">
                                <i class="pi pi-verified text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-black italic">Taux de recommandation</h4>
                                <p class="text-emerald-100 text-sm font-medium">98% des apprenants recommandent SubnetCongo en 2024.</p>
                            </div>
                        </div>
                        <Button label="Voir le rapport" class="p-button-sm p-button-white p-button-text font-bold" />
                    </div>
                </div>
            </div>

            <!-- --- DATA TABLE SECTION --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">

                <Toolbar class="bg-white border-none p-8">
                    <template #start>
                        <div class="flex flex-wrap gap-4 items-center">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Chercher par nom ou contenu..." class="w-full md:w-96 rounded-2xl border-slate-200 bg-slate-50 focus:bg-white transition-all shadow-inner" />
                            </IconField>
                            <MultiSelect v-model="filters['course'].value" :options="courses" optionLabel="label" optionValue="value" placeholder="Filtrer par cours" class="w-64 rounded-xl border-slate-200" display="chip" />
                        </div>
                    </template>
                    <template #end>
                        <div class="flex gap-2">
                            <Button v-if="selectedTestimonials?.length" label="Approuver la sélection" icon="pi pi-check" class="p-button-success p-button-sm rounded-xl" @click="bulkApprove" />
                            <Button type="button" icon="pi pi-filter-slash" label="Réinitialiser" class="p-button-text p-button-secondary font-bold" @click="filters['global'].value = null" />
                        </div>
                    </template>
                </Toolbar>

                <DataTable
                    v-model:selection="selectedTestimonials"
                    :value="testimonials"
                    :paginator="true"
                    :rows="8"
                    dataKey="id"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    class="p-datatable-custom"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} avis"
                >
                    <template #empty> <div class="p-10 text-center text-slate-400 font-bold">Aucun témoignage ne correspond à votre recherche.</div> </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="student_name" header="Apprenant" sortable style="min-width: 18rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <Avatar :image="data.student_avatar" shape="circle" size="xlarge" class="border-2 border-white shadow-md" />
                                    <div v-if="data.verified_purchase" class="absolute -bottom-1 -right-1 bg-emerald-500 text-white rounded-full p-1 border-2 border-white" v-tooltip.top="'Achat vérifié'">
                                        <i class="pi pi-check text-[8px] font-bold"></i>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 leading-tight">{{ data.student_name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Inscrit le {{ data.date }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="rating" header="Note" sortable style="min-width: 10rem">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <Rating :modelValue="data.rating" readonly :cancel="false" class="text-xs" />
                                <span class="text-[10px] font-bold text-slate-400">{{ data.rating === 5 ? 'Excellent' : 'Très bien' }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="comment" header="Contenu" style="min-width: 25rem">
                        <template #body="{ data }">
                            <p class="text-sm text-slate-600 line-clamp-2 italic font-medium leading-relaxed">" {{ data.comment }} "</p>
                            <div class="mt-2 flex gap-2">
                                <Badge :value="courses.find(c => c.value === data.course)?.label" severity="info" class="text-[9px] uppercase font-bold" />
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" sortable>
                        <template #body="{ data }">
                            <Tag :value="statuses.find(s => s.value === data.status)?.label" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 py-1 text-[9px] font-black uppercase tracking-widest" />
                        </template>
                    </Column>

                    <Column header="Featured" class="text-center">
                        <template #body="{ data }">
                            <i :class="['pi text-xl', data.is_featured ? 'pi-star-fill text-amber-400 animate-pulse' : 'pi-star text-slate-200']"></i>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Aperçu rapide'" @click="viewInSidebar(data)" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Modifier'" @click="editTestimonial(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Supprimer'" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- --- FOOTER INFO --- -->
            <footer class="mt-12 flex flex-col md:flex-row justify-between items-center text-slate-400 gap-4">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield"></i>
                    <span class="text-xs font-bold uppercase tracking-widest">RGPD & Protection des données activées</span>
                </div>
                <div class="flex gap-6">
                    <span class="text-xs font-medium">© 2024 SubnetCongo LMS - Administration Marketing</span>
                    <span class="text-xs font-medium">Documentation API</span>
                </div>
            </footer>
        </div>

        <!-- --- EDIT DIALOG (MULTI-TAB) --- -->
        <Dialog v-model:visible="testimonialDialog" :style="{width: '800px'}" header="Gestion du Témoignage" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <TabView class="custom-tabs">
                <TabPanel header="Général">
                    <div class="grid grid-cols-12 gap-6 pt-4">
                        <div class="col-span-12 lg:col-span-4 flex flex-col items-center gap-6 border-r border-slate-100 pr-6">
                            <div class="relative group">
                                <Avatar :image="testimonial.student_avatar" size="xlarge" shape="circle" class="w-32 h-32 border-4 border-indigo-50 shadow-xl" />
                                <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all cursor-pointer">
                                    <i class="pi pi-camera text-white text-2xl"></i>
                                </div>
                            </div>
                            <FileUpload mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" label="Photo Profil" class="p-button-sm p-button-outlined" />

                            <div class="w-full space-y-4">
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-xs font-bold text-slate-600">Achat vérifié</span>
                                    <InputSwitch v-model="testimonial.verified_purchase" />
                                </div>
                                <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl border border-amber-100">
                                    <span class="text-xs font-bold text-amber-700">Mettre en avant</span>
                                    <InputSwitch v-model="testimonial.is_featured" />
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-8 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Nom Complet de l'apprenant</label>
                                <InputText v-model="testimonial.student_name" placeholder="Ex: Jean Mwamba" class="p-3 rounded-xl border-slate-200" :class="{'p-invalid': submitted && !testimonial.student_name}" />
                                <small class="p-error" v-if="submitted && !testimonial.student_name">Le nom est requis.</small>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Cours concerné</label>
                                    <Dropdown v-model="testimonial.course" :options="courses" optionLabel="label" optionValue="value" placeholder="Choisir un cours..." class="rounded-xl border-slate-200" />
                                </div>
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Note (Évaluation)</label>
                                    <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-xl border border-slate-200">
                                        <Rating v-model="testimonial.rating" :cancel="false" />
                                        <span class="text-sm font-black text-indigo-600">{{ testimonial.rating }}/5</span>
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Commentaire détaillé</label>
                                <Textarea v-model="testimonial.comment" rows="5" placeholder="Saisissez ici l'avis de l'apprenant..." class="rounded-xl p-4 border-slate-200 italic" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <TabPanel header="Réglages & Statut">
                    <div class="space-y-8 pt-4">
                        <div class="field">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-4">État de l'avis</label>
                            <SelectButton v-model="testimonial.status" :options="statuses" optionLabel="label" optionValue="value" class="custom-selectbutton">
                                <template #option="slotProps">
                                    <div class="flex flex-col items-center gap-2 p-2">
                                        <i :class="slotProps.option.icon" class="text-xl"></i>
                                        <span class="text-[10px] font-bold uppercase">{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </SelectButton>
                        </div>

                        <Divider />

                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 mb-4">Date de publication</h4>
                                <Calendar v-model="testimonial.date" dateFormat="dd/mm/yy" icon="pi pi-calendar" showIcon class="rounded-xl" />
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800 mb-4">Score de sentiment (AI)</h4>
                                <div class="flex items-center gap-4">
                                    <ProgressBar :value="testimonial.sentiment_score || 0" class="flex-1" :color="testimonial.sentiment_score > 70 ? '#10B981' : '#F59E0B'" />
                                    <span class="text-xs font-bold">{{ testimonial.sentiment_score || 0 }}% Positif</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-4">
                            <i class="pi pi-info-circle text-blue-500 text-xl"></i>
                            <p class="text-xs text-blue-700 font-medium leading-relaxed">
                                Les témoignages avec un statut "En attente" ne seront pas visibles sur le site public de SubnetCongo. Assurez-vous d'avoir vérifié l'identité de l'apprenant avant d'approuver.
                            </p>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full pt-4 px-4 border-t border-slate-50">
                    <span class="text-xs text-slate-400 font-medium italic">Dernière modification : {{ new Date().toLocaleTimeString() }}</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="testimonialDialog = false" />
                        <Button label="Enregistrer l'avis" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="saveTestimonial" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- PREVIEW SIDEBAR --- -->
        <Sidebar v-model:visible="previewSidebar" position="right" class="w-full md:w-[450px] p-sidebar-custom">
            <div v-if="testimonial" class="flex flex-col h-full bg-slate-50">
                <div class="bg-indigo-600 p-10 text-white rounded-b-[3rem] shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <Avatar :image="testimonial.student_avatar" size="xlarge" shape="circle" class="w-24 h-24 border-4 border-white/20 mb-4" />
                        <h2 class="text-2xl font-black italic">{{ testimonial.student_name }}</h2>
                        <Rating :modelValue="testimonial.rating" readonly :cancel="false" class="mt-2" />
                    </div>
                </div>

                <div class="flex-1 p-8 space-y-8">
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative">
                        <i class="pi pi-quote-left absolute -top-4 left-6 text-3xl text-indigo-500 bg-white px-2"></i>
                        <p class="text-lg text-slate-700 font-serif leading-relaxed italic text-center pt-4">
                            {{ testimonial.comment }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-2xl text-center border border-slate-100">
                            <p class="text-[10px] text-slate-400 font-black uppercase mb-1">Cours Suivi</p>
                            <p class="text-sm font-bold text-indigo-600">{{ courses.find(c => c.value === testimonial.course)?.label }}</p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl text-center border border-slate-100">
                            <p class="text-[10px] text-slate-400 font-black uppercase mb-1">Publication</p>
                            <p class="text-sm font-bold text-slate-800">{{ testimonial.date }}</p>
                        </div>
                    </div>

                    <Divider />

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Métadonnées Techniques</h4>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>ID Unique:</span>
                            <span class="font-mono">{{ testimonial.id }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Sentiment Score:</span>
                            <span class="font-bold text-emerald-500">{{ testimonial.sentiment_score }}% Positif</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Verified Badge:</span>
                            <i :class="testimonial.verified_purchase ? 'pi pi-check-circle text-emerald-500' : 'pi pi-times-circle text-rose-500'"></i>
                        </div>
                    </div>
                </div>

                <div class="p-8 border-t border-slate-200 flex gap-4 bg-white">
                    <Button label="Partager sur LinkedIn" icon="pi pi-linkedin" class="flex-1 p-button-outlined rounded-xl" />
                    <Button label="Modifier" icon="pi pi-pencil" class="flex-1 p-button-primary rounded-xl" @click="editTestimonial(testimonial)" />
                </div>
            </div>
        </Sidebar>

        <!-- --- DELETE CONFIRMATION --- -->
        <Dialog v-model:visible="deleteDialog" :style="{width: '450px'}" header="Suppression" :modal="true">
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <i class="pi pi-trash text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Êtes-vous sûr ?</h3>
                <p class="text-slate-500 text-sm">Vous allez supprimer le témoignage de <b>{{ testimonial.student_name }}</b>. Cette action est irréversible.</p>
            </div>
            <template #footer>
                <div class="flex gap-2 justify-center pb-4">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="deleteDialog = false" />
                    <Button label="Confirmer la suppression" class="p-button-danger rounded-xl px-6" @click="deleteEntry" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO LMS UI
 * Ces styles surchargent les composants PrimeVue pour une esthétique ultra-moderne.
 */

:deep(.p-datatable-custom) {
    border: none;
}

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
    transform: scale(0.998);
}

:deep(.p-datatable-custom .p-datatable-tbody > tr > td) {
    padding: 1.5rem;
    border-bottom: 1px solid #f8fafc;
}

/* Paginator styling */
:deep(.p-paginator) {
    background: #fdfdff;
    border: none;
    padding: 2rem;
    border-radius: 0 0 3.5rem 3.5rem;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: #6366F1;
    color: white;
    border-radius: 12px;
}

/* Tabs styling */
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
    transition: all 0.3s;
}

:deep(.custom-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

/* Rating stars */
:deep(.p-rating .p-rating-item .p-rating-icon) {
    color: #FBBF24;
}

/* SelectButton customization */
:deep(.custom-selectbutton .p-button) {
    background: white;
    border-color: #f1f5f9;
    transition: all 0.2s;
}

:deep(.custom-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    border-color: #6366F1;
    color: white;
}

/* Sidebar customization */
:deep(.p-sidebar-custom) {
    border-radius: 3rem 0 0 3rem;
    box-shadow: -20px 0 50px rgba(0,0,0,0.1);
}

/* Progress bar */
:deep(.p-progressbar) {
    background: #f1f5f9;
    border-radius: 10px;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Input elements */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-multiselect) {
    border-color: #f1f5f9;
    box-shadow: none !important;
}

:deep(.p-inputtext:focus), :deep(.p-dropdown:focus) {
    border-color: #6366f1;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.p-datatable-tbody > tr {
    animation: fadeIn 0.5s ease forwards;
}
</style>
