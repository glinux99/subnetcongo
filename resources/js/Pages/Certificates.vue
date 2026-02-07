<script setup>
/**
 * CERTIFICATE MANAGEMENT & DESIGNER - SUBNETCONGO LMS
 * Version: 4.2.0
 * Description: Gestionnaire complet des titres certifiants et éditeur visuel.
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
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
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
import OverlayPanel from 'primevue/overlaypanel';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import PickList from 'primevue/picklist';
import Skeleton from 'primevue/skeleton';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const exportMenu = ref();
const designerSidebar = ref(false);
const previewDialog = ref(false);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Gestion E-learning' },
    { label: 'Certificats & Diplômes', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const courses = ref([
    { name: 'Laravel Mastery', id: 'lar-01' },
    { name: 'Vue 3 Advanced', id: 'vue-02' },
    { name: 'UI/UX Design Specialist', id: 'ui-03' },
    { name: 'CyberSecurité Pro', id: 'cyb-04' }
]);

const fonts = ref(['Inter', 'Playfair Display', 'Montserrat', 'Roboto', 'Great Vibes']);

// --- MOCK DATA: ISSUED CERTIFICATES ---
const certificates = ref(Array.from({ length: 50 }, (_, i) => ({
    id: `CERT-${2025}-${1000 + i}`,
    student_name: ['Jean Mwamba', 'Sarah Loft', 'Patrick Tshibala', 'Marie Kabamba'][i % 4],
    student_avatar: `https://i.pravatar.cc/150?u=${i}`,
    course_name: ['Laravel Mastery', 'Vue 3 Advanced', 'UI/UX Design Specialist'][i % 3],
    issue_date: '2024-02-0' + (i % 9 + 1),
    score: 85 + (i % 15),
    status: i % 10 === 0 ? 'Revoked' : 'Active',
    verification_link: `https://verify.subnetcongo.cd/v/${1000 + i}`
})));

// --- DESIGNER STATE (REACTIVE THEME) ---
const activeTemplate = reactive({
    name: 'Template Officiel Subnet',
    background_color: 'ffffff',
    border_color: '6366F1',
    border_width: 10,
    primary_font: 'Inter',
    accent_font: 'Playfair Display',
    text_color: '1E293B',
    show_qr: true,
    show_signature: true,
    signature_img: null,
    logo_pos: 'center',
    elements: {
        title: { text: 'CERTIFICAT DE RÉUSSITE', size: 42, top: 15 },
        subtitle: { text: 'Décerné avec mention à :', size: 18, top: 30 },
        body: { text: 'Pour avoir complété avec succès le programme de formation intensive de SubnetCongo.', size: 16, top: 60 }
    }
});

// --- FILTERS & TABLE LOGIC ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    student_name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    course_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const selectedCertificates = ref(null);

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Active': return 'success';
        case 'Revoked': return 'danger';
        default: return 'info';
    }
};

// --- METHODS ---
const openDesigner = (template = null) => {
    designerSidebar.value = true;
};

const revokeCertificate = (data) => {
    data.status = 'Revoked';
    toast.add({ severity: 'warn', summary: 'Certificat Révoqué', detail: `Le certificat ${data.id} n'est plus valide publicment.`, life: 3000 });
};

const saveTemplate = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        toast.add({ severity: 'success', summary: 'Template Sauvegardé', detail: 'Le nouveau design est maintenant actif pour les futurs diplômés.', life: 3000 });
        designerSidebar.value = false;
    }, 1200);
};

const exportData = () => {
    toast.add({ severity: 'info', summary: 'Exportation', detail: 'Le fichier CSV des diplômés est en cours de génération.' });
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- COMPUTED PREVIEW STYLE ---
const certificatePreviewStyle = computed(() => ({
    backgroundColor: `#${activeTemplate.background_color}`,
    border: `${activeTemplate.border_width}px solid #${activeTemplate.border_color}`,
    color: `#${activeTemplate.text_color}`,
    fontFamily: activeTemplate.primary_font
}));

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Certifications" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 rotate-2 hover:rotate-0 transition-all duration-500">
                            <i class="pi pi-verified text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Certifications & Diplômes</h1>
                            <p class="text-slate-500 font-medium italic">Gérez la valeur académique délivrée par <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button label="Exporter" icon="pi pi-download" class="p-button-outlined p-button-secondary bg-white rounded-2xl" @click="exportData" />
                    <Button label="Designer un Template" icon="pi pi-palette" class="p-button-outlined p-button-indigo rounded-2xl" @click="openDesigner()" />
                    <Button label="Délivrer Manuellement" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-2xl font-bold" />
                </div>
            </header>

            <!-- --- ANALYTICS QUICK VIEW --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div v-for="stat in [
                    { label: 'Émis au Total', val: '1,284', icon: 'pi-copy', color: 'indigo', trend: '+45 ce mois' },
                    { label: 'Taux de réussite', val: '72%', icon: 'pi-chart-line', color: 'emerald', trend: 'Stable' },
                    { label: 'Vérifications Publiques', val: '4,520', icon: 'pi-search', color: 'amber', trend: '+12% de clics' },
                    { label: 'Templates Actifs', val: '12', icon: 'pi-palette', color: 'rose', trend: '3 en attente' }
                ]" :key="stat.label" class="bg-white p-7 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <h3 class="text-3xl font-black text-slate-800 tracking-tighter">{{ stat.val }}</h3>
                        <p class="text-xs font-bold mt-2" :class="`text-${stat.color}-500` ">{{ stat.trend }}</p>
                    </div>
                    <div :class="`absolute -right-4 -bottom-4 text-7xl text-${stat.color}-50 opacity-0 group-hover:opacity-100 transition-opacity` ">
                        <i :class="`pi ${stat.icon}` "></i>
                    </div>
                </div>
            </div>

            <!-- --- MAIN TABLE: ISSUED CERTIFICATES --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">

                <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                    <h2 class="text-xl font-black text-slate-800 tracking-tight italic">Journal des Émissions</h2>
                    <div class="flex gap-4">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher un élève ou ID..." class="w-80 rounded-2xl border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all" />
                        </IconField>
                        <Dropdown v-model="filters['course_name'].value" :options="courses" optionLabel="name" optionValue="name" placeholder="Filtrer par cours" class="w-64 rounded-xl" showClear />
                    </div>
                </div>

                <DataTable
                    :value="certificates"
                    v-model:selection="selectedCertificates"
                    :paginator="true"
                    :rows="10"
                    dataKey="id"
                    :filters="filters"
                    filterDisplay="menu"
                    :loading="loading"
                    class="p-datatable-custom"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords}"
                >
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="id" header="ID Certificat" sortable style="min-width: 12rem">
                        <template #body="{ data }">
                            <span class="font-mono text-xs font-black text-indigo-600">{{ data.id }}</span>
                        </template>
                    </Column>

                    <Column field="student_name" header="Apprenant" sortable style="min-width: 16rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :image="data.student_avatar" shape="circle" size="large" class="border-2 border-white shadow-sm" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ data.student_name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Diplômé le {{ data.issue_date }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="course_name" header="Formation" style="min-width: 15rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-book text-indigo-400 text-xs"></i>
                                <span class="text-sm font-medium text-slate-700">{{ data.course_name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="score" header="Score Final" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <span class="font-black text-slate-800">{{ data.score }}%</span>
                                <ProgressBar :value="data.score" :showValue="false" style="height: 4px; width: 40px" />
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[9px] font-black uppercase tracking-[0.2em]" />
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-eye" class="p-button-rounded p-button-text p-button-secondary" v-tooltip.top="'Prévisualiser'" />
                                <Button icon="pi pi-link" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Lien de vérification'" />
                                <Button icon="pi pi-ban" class="p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Révoquer'" @click="revokeCertificate(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- --- SECTION: AUTOMATION RULES --- -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-indigo-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black italic mb-6">Règles de délivrance auto</h3>
                        <div class="space-y-6">
                            <div v-for="rule in [
                                { label: 'Taux de complétion min.', val: '90%', icon: 'pi-check-circle' },
                                { label: 'Score minimal examen', val: '75%', icon: 'pi-verified' },
                                { label: 'Période de validité', val: 'Illimitée', icon: 'pi-history' }
                            ]" :key="rule.label" class="flex items-center justify-between p-4 bg-white/10 rounded-2xl border border-white/10 backdrop-blur-md">
                                <div class="flex items-center gap-4">
                                    <i :class="['pi text-xl text-indigo-300', rule.icon]"></i>
                                    <span class="text-sm font-bold">{{ rule.label }}</span>
                                </div>
                                <span class="text-xl font-black">{{ rule.val }}</span>
                            </div>
                        </div>
                        <Button label="Modifier les paramètres globaux" icon="pi pi-cog" class="p-button-text p-button-plain text-indigo-300 font-bold mt-8" />
                    </div>
                    <i class="pi pi-bolt absolute -right-6 -bottom-6 text-[10rem] text-white/5"></i>
                </div>

                <div class="bg-white rounded-[3rem] p-10 border border-slate-100 shadow-sm">
                    <h3 class="text-2xl font-black text-slate-800 mb-6">Templates les plus utilisés</h3>
                    <div class="space-y-4">
                        <div v-for="i in 3" :key="i" class="group flex items-center justify-between p-4 border border-slate-50 rounded-2xl hover:bg-slate-50 transition-all cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-12 bg-slate-100 rounded-lg flex items-center justify-center border border-slate-200">
                                    <i class="pi pi-palette text-slate-300"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">Template Pro #{{ i }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Utilisé pour {{ [450, 210, 120][i-1] }} étudiants</p>
                                </div>
                            </div>
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    </div>
                    <Divider />
                    <Button label="Voir tous les templates" class="w-full p-button-outlined rounded-2xl font-bold" />
                </div>
            </div>
        </div>

        <!-- --- DESIGNER SIDEBAR: THE VISUAL BUILDER --- -->
        <Sidebar v-model:visible="designerSidebar" position="right" class="w-full md:w-[900px] p-sidebar-custom">
            <div class="flex flex-col h-full bg-slate-50">

                <!-- Designer Header -->
                <div class="p-8 bg-white border-b border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-600 rounded-2xl shadow-lg">
                            <i class="pi pi-palette text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 italic">Certificate Designer</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Édition en temps réel</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button label="Aperçu PDF" icon="pi pi-file-pdf" class="p-button-outlined p-button-secondary rounded-xl" />
                        <Button label="Publier le Template" icon="pi pi-save" class="p-button-primary rounded-xl px-6" @click="saveTemplate" />
                    </div>
                </div>

                <div class="flex-1 overflow-hidden flex flex-col lg:flex-row">

                    <!-- LEFT: THE CANVAS -->
                    <div class="flex-1 p-10 flex items-center justify-center overflow-auto bg-slate-200 shadow-inner">
                        <div id="certificate-canvas" class="relative shadow-2xl transition-all duration-500 origin-center" :style="certificatePreviewStyle" style="width: 800px; height: 565px;">

                            <!-- Dynamic Elements -->
                            <div class="absolute w-full text-center" :style="{ top: activeTemplate.elements.title.top + '%' }">
                                <h1 class="font-black italic" :style="{ fontSize: activeTemplate.elements.title.size + 'px', fontFamily: activeTemplate.accent_font }">
                                    {{ activeTemplate.elements.title.text }}
                                </h1>
                            </div>

                            <div class="absolute w-full text-center" :style="{ top: activeTemplate.elements.subtitle.top + '%' }">
                                <p class="uppercase font-bold tracking-[0.3em]" :style="{ fontSize: activeTemplate.elements.subtitle.size + 'px' }">
                                    {{ activeTemplate.elements.subtitle.text }}
                                </p>
                            </div>

                            <div class="absolute w-full text-center" style="top: 42%">
                                <h2 class="text-5xl font-black italic text-indigo-600 border-b-2 border-indigo-100 inline-block px-10 pb-2">NOM DE L'APPRENANT</h2>
                            </div>

                            <div class="absolute w-full px-20 text-center leading-relaxed" :style="{ top: activeTemplate.elements.body.top + '%' }">
                                <p :style="{ fontSize: activeTemplate.elements.body.size + 'px' }">
                                    {{ activeTemplate.elements.body.text }}
                                </p>
                                <p class="mt-4 font-black text-xl italic">[NOM DU COURS]</p>
                            </div>

                            <!-- Footer Elements -->
                            <div class="absolute bottom-12 left-12 flex flex-col items-center">
                                <div class="w-32 h-1 bg-slate-300 mb-2"></div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Directeur Pédagogique</span>
                            </div>

                            <div v-if="activeTemplate.show_qr" class="absolute bottom-12 right-12 text-center">
                                <div class="w-20 h-20 bg-white border border-slate-100 p-2 shadow-sm rounded-lg flex items-center justify-center">
                                    <i class="pi pi-qrcode text-4xl text-slate-800"></i>
                                </div>
                                <span class="text-[8px] font-black text-slate-400 uppercase mt-2 block">ID: SC-2025-XXXX</span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: THE CONTROLS -->
                    <aside class="w-full lg:w-80 bg-white border-l border-slate-100 overflow-y-auto">
                        <Accordion :multiple="true" :activeIndex="[0, 1]">
                            <AccordionTab header="Mise en page & Couleurs">
                                <div class="space-y-6">
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3">Couleur de fond</label>
                                        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                            <ColorPicker v-model="activeTemplate.background_color" />
                                            <code class="text-xs font-bold">#{{ activeTemplate.background_color }}</code>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-3">Bordure Décorative</label>
                                        <div class="flex items-center gap-3 mb-4">
                                            <ColorPicker v-model="activeTemplate.border_color" />
                                            <Slider v-model="activeTemplate.border_width" :min="0" :max="30" class="flex-1" />
                                        </div>
                                    </div>
                                    <Divider />
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-bold text-slate-700">Afficher le QR Code</span>
                                        <InputSwitch v-model="activeTemplate.show_qr" />
                                    </div>
                                </div>
                            </AccordionTab>

                            <AccordionTab header="Textes & Typographie">
                                <div class="space-y-6">
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Police des Titres</label>
                                        <Dropdown v-model="activeTemplate.accent_font" :options="fonts" class="w-full rounded-xl" />
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre du Certificat</label>
                                        <InputText v-model="activeTemplate.elements.title.text" class="rounded-xl" />
                                        <div class="mt-3 flex items-center gap-4">
                                            <i class="pi pi-text-height text-slate-400 text-xs"></i>
                                            <Slider v-model="activeTemplate.elements.title.size" :min="12" :max="64" class="flex-1" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Position verticale (%)</label>
                                        <Slider v-model="activeTemplate.elements.title.top" :min="0" :max="90" />
                                    </div>
                                </div>
                            </AccordionTab>

                            <AccordionTab header="Règles & Identifiants">
                                <div class="space-y-4">
                                    <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                                        <p class="text-xs font-bold text-indigo-700 leading-relaxed italic">
                                            " Utilisez les variables dynamiques comme {apprenant_nom}, {cours_titre} ou {date_issue} dans vos textes. "
                                        </p>
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-bold text-slate-500 mb-2 block">Préfixe d'ID unique</label>
                                        <InputText value="SC-{{YEAR}}-" class="font-mono text-sm" />
                                    </div>
                                </div>
                            </AccordionTab>
                        </Accordion>
                    </aside>
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/* --- CUSTOM STYLING FOR CERTIFICATES MODULE --- */

:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background: #FDFDFF;
    padding: 1.5rem 1rem;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.2s;
    cursor: pointer;
}

:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background: #f8fafc !important;
}

/* Designer Overrides */
:deep(.p-sidebar-custom) {
    border-radius: 4rem 0 0 4rem;
    box-shadow: -20px 0 60px rgba(0,0,0,0.1);
}

:deep(.p-accordion .p-accordion-header .p-accordion-header-link) {
    background: transparent;
    border: none;
    padding: 1.5rem;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: #475569;
}

:deep(.p-accordion .p-accordion-content) {
    background: transparent;
    border: none;
    padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

/* Animation for the certificate canvas preview */
#certificate-canvas {
    box-shadow: 0 40px 100px -20px rgba(99, 102, 241, 0.2);
}

/* Layout stacking for mobile */
@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
