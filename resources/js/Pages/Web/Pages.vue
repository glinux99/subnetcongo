<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import SelectButton from 'primevue/selectbutton';
import FileUpload from 'primevue/fileupload';
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Calendar from 'primevue/calendar';
import InlineMessage from 'primevue/inlinemessage';
import OverlayPanel from 'primevue/overlaypanel';
import Chip from 'primevue/chip';

const toast = useToast();

// --- CONFIGURATIONS & OPTIONS ---
const templates = ref([
    { label: 'Page Standard', value: 'default', icon: 'pi pi-file' },
    { label: 'Landing Page', value: 'landing', icon: 'pi pi-desktop' },
    { label: 'Centre d\'aide', value: 'helpcenter', icon: 'pi pi-question-circle' },
    { label: 'Pleine Largeur', value: 'fullwidth', icon: 'pi pi-arrows-h' }
]);

const pageStatuses = ref([
    { label: 'Publiée', value: 'published', severity: 'success' },
    { label: 'Brouillon', value: 'draft', severity: 'secondary' },
    { label: 'Privée', value: 'private', severity: 'warning' },
    { label: 'Archivée', value: 'archived', severity: 'danger' }
]);

const accessLevels = ref([
    { label: 'Tout le monde', value: 'all' },
    { label: 'Apprenants uniquement', value: 'students' },
    { label: 'Enseignants', value: 'teachers' },
    { label: 'Administrateurs', value: 'admins' }
]);

// --- MOCK DATA ---
const pages = ref([
    { id: 1, title: 'À propos de SubnetCongo', slug: 'a-propos', template: 'default', status: 'published', author: 'Daniel Kikimba', lastUpdate: '2024-01-15', views: 2450, access: 'all' },
    { id: 2, title: 'Conditions Générales d\'Utilisation', slug: 'cgu', template: 'fullwidth', status: 'published', author: 'Service Juridique', lastUpdate: '2023-12-10', views: 890, access: 'all' },
    { id: 3, title: 'Guide de bienvenue Apprenants', slug: 'guide-bienvenue', template: 'landing', status: 'draft', author: 'Sarah Loft', lastUpdate: '2024-02-01', views: 0, access: 'students' },
    { id: 4, title: 'Mentions Légales', slug: 'mentions-legales', template: 'default', status: 'published', author: 'Admin', lastUpdate: '2023-11-20', views: 120, access: 'all' },
    { id: 5, title: 'Politique de Confidentialité', slug: 'confidentialite', template: 'default', status: 'published', author: 'Daniel Kikimba', lastUpdate: '2024-01-10', views: 450, access: 'all' },
]);

// --- UI STATE ---
const pageDialog = ref(false);
const deleteDialog = ref(false);
const selectedPages = ref(null);
const loading = ref(false);
const submitted = ref(false);
const op = ref(); // Pour l'overlay de prévisualisation rapide

const page = ref({
    title: '',
    slug: '',
    content: '',
    template: 'default',
    status: 'draft',
    access: 'all',
    seo: {
        title: '',
        description: '',
        keywords: '',
        og_image: null,
        no_index: false
    },
    settings: {
        show_breadcrumb: true,
        show_footer: true,
        is_homepage: false
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// --- LOGIQUE ---
const openNew = () => {
    page.value = {
        title: '',
        slug: '',
        content: '',
        template: 'default',
        status: 'draft',
        access: 'all',
        seo: { title: '', description: '', no_index: false },
        settings: { show_breadcrumb: true, show_footer: true, is_homepage: false }
    };
    submitted.value = false;
    pageDialog.value = true;
};

const editPage = (p) => {
    page.value = { ...p, seo: p.seo || { title: '', description: '' }, settings: p.settings || { show_breadcrumb: true } };
    pageDialog.value = true;
};

const generateSlug = () => {
    if (page.value.title) {
        page.value.slug = page.value.title
            .toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-');
    }
};

const savePage = () => {
    submitted.value = true;
    if (page.value.title.trim() && page.value.slug.trim()) {
        loading.value = true;
        setTimeout(() => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Configuration de la page enregistrée', life: 3000 });
            pageDialog.value = false;
            loading.value = false;
        }, 800);
    }
};

const getStatusSeverity = (status) => {
    return pageStatuses.value.find(s => s.value === status)?.severity || 'info';
};

const getStatusLabel = (status) => {
    return pageStatuses.value.find(s => s.value === status)?.label || status;
};

const togglePreview = (event, data) => {
    page.value = data;
    op.value.toggle(event);
};

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Pages" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div>
                    <div class="flex items-center gap-2 text-indigo-600 font-bold mb-1">
                        <i class="pi pi-clone"></i>
                        <span class="uppercase tracking-widest text-xs">Site Web Architecture</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight">Pages Statiques</h1>
                    <p class="text-slate-500 font-medium">Structurez le contenu institutionnel et informatif de votre plateforme.</p>
                </div>

                <div class="flex gap-3">
                    <Button label="Réorganiser l'ordre" icon="pi pi-sort-alt" class="p-button-outlined p-button-secondary bg-white rounded-2xl" />
                    <Button label="Créer une Page" icon="pi pi-plus" class="p-button-primary shadow-lg shadow-indigo-200 px-6 rounded-2xl" @click="openNew" />
                </div>
            </header>

            <!-- --- DASHBOARD MINI STATS --- -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div v-for="stat in [
                    { label: 'Total Pages', val: '24', icon: 'pi-copy', color: 'indigo' },
                    { label: 'Vues (30j)', val: '4.8k', icon: 'pi-chart-line', color: 'emerald' },
                    { label: 'Temps Moyen', val: '2m 14s', icon: 'pi-clock', color: 'amber' },
                    { label: 'Brouillons', val: '3', icon: 'pi-pencil', color: 'rose' }
                ]" :key="stat.label" class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-4">
                    <div :class="`w-12 h-12 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center` ">
                        <i :class="`pi ${stat.icon} text-xl` "></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ stat.label }}</p>
                        <p class="text-xl font-black text-slate-800">{{ stat.val }}</p>
                    </div>
                </div>
            </div>

            <!-- --- MAIN TABLE --- -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                <DataTable :value="pages" v-model:selection="selectedPages" :paginator="true" :rows="10"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['title', 'slug']"
                    class="p-datatable-modern">

                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher par titre ou URL..." class="w-full md:w-80 rounded-xl bg-slate-50 border-none ring-1 ring-slate-200" />
                            </IconField>
                            <div class="flex gap-2">
                                <Button v-if="selectedPages?.length" icon="pi pi-trash" class="p-button-danger p-button-sm animate-pulse" label="Supprimer la sélection" />
                                <Button type="button" icon="pi pi-filter-slash" label="Effacer filtres" class="p-button-text p-button-sm" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="title" header="Titre de la Page" sortable style="min-width: 20rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-slate-50 rounded-lg text-slate-400">
                                    <i :class="templates.find(t => t.value === data.template)?.icon || 'pi pi-file'"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800">{{ data.title }}</span>
                                    <span class="text-[10px] text-indigo-500 font-mono">/{{ data.slug }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État" sortable>
                        <template #body="{ data }">
                            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 py-1 text-[9px] font-black uppercase tracking-widest" />
                        </template>
                    </Column>

                    <Column field="access" header="Accès" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i :class="['pi', data.access === 'all' ? 'pi-globe text-emerald-500' : 'pi-lock text-amber-500']"></i>
                                <span class="text-xs font-bold text-slate-600">{{ accessLevels.find(a => a.value === data.access)?.label }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="views" header="Performance" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-black text-slate-700">{{ data.views }} vues</span>
                                <ProgressBar :value="Math.min(data.views / 25, 100)" :showValue="false" style="height: 4px" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex gap-1 justify-end">
                                <Button icon="pi pi-search-plus" class="p-button-rounded p-button-text p-button-secondary" @click="togglePreview($event, data)" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info" @click="editPage(data)" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- MODAL D'ÉDITION --- -->
        <Dialog v-model:visible="pageDialog" :style="{width: '1100px'}" header="Configuration de la Page" :modal="true" class="p-fluid rounded-3xl overflow-hidden" :breakpoints="{'960px': '95vw'}">
            <TabView class="mt-4 custom-tabview">

                <!-- ONGLET : CONTENU -->
                <TabPanel>
                    <template #header><i class="pi pi-align-left mr-2"></i> Contenu</template>
                    <div class="grid grid-cols-12 gap-6 pt-4">
                        <div class="col-span-12 lg:col-span-8 space-y-5">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre Public</label>
                                <InputText v-model="page.title" @input="generateSlug" placeholder="Ex: Notre Vision & Mission" class="text-lg font-bold p-3" />
                                <small class="text-slate-400 mt-1 block">Le titre s'affiche dans l'onglet du navigateur et les menus.</small>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Contenu Riche (HTML)</label>
                                <Editor v-model="page.content" editorStyle="height: 450px" placeholder="Construisez votre page..." />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 space-y-6 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">URL (Slug)</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon bg-slate-200 text-slate-500 font-bold border-none">/</span>
                                    <InputText v-model="page.slug" placeholder="lien-de-la-page" class="border-none ring-1 ring-slate-300" />
                                </div>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Template visuel</label>
                                <Dropdown v-model="page.template" :options="templates" optionLabel="label" optionValue="value" class="rounded-xl">
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-3">
                                            <i :class="slotProps.option.icon"></i>
                                            <span>{{ slotProps.option.label }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <Divider />

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Statut & Visibilité</label>
                                <SelectButton v-model="page.status" :options="pageStatuses" optionLabel="label" optionValue="value" class="mb-4" />
                                <Dropdown v-model="page.access" :options="accessLevels" optionLabel="label" optionValue="value" placeholder="Niveau d'accès" />
                            </div>

                            <div class="space-y-4 pt-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-600">Fil d'ariane</span>
                                    <InputSwitch v-model="page.settings.show_breadcrumb" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-600">Pied de page</span>
                                    <InputSwitch v-model="page.settings.show_footer" />
                                </div>
                                <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                                    <span class="text-sm font-bold text-indigo-700">Définir comme Accueil</span>
                                    <InputSwitch v-model="page.settings.is_homepage" />
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET : SEO & SOCIAL -->
                <TabPanel>
                    <template #header><i class="pi pi-google mr-2"></i> SEO & Référencement</template>
                    <div class="grid grid-cols-12 gap-8 pt-6">
                        <div class="col-span-12 lg:col-span-6 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">SEO Title (Tag)</label>
                                <InputText v-model="page.seo.title" :placeholder="page.title" />
                                <ProgressBar :value="Math.min(page.seo.title.length / 0.6, 100)" :showValue="false" class="mt-2" :color="page.seo.title.length > 60 ? '#EF4444' : '#6366F1'" style="height: 4px" />
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Meta Description</label>
                                <Textarea v-model="page.seo.description" rows="4" placeholder="Résumé pour les moteurs de recherche..." />
                                <small class="text-slate-400 italic">Recommandé : 150-160 caractères.</small>
                            </div>

                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Image de partage (OG Image)</label>
                                <div class="border-2 border-dashed border-slate-200 rounded-3xl p-8 text-center hover:bg-slate-50 transition-all">
                                    <i class="pi pi-cloud-upload text-4xl text-slate-300 mb-4"></i>
                                    <FileUpload mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" label="Uploader une bannière" class="p-button-outlined" />
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-6">
                            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-xl">
                                <h4 class="text-sm font-black text-slate-800 uppercase mb-6 flex items-center gap-2">
                                    <i class="pi pi-eye text-indigo-500"></i> Aperçu Google
                                </h4>
                                <div class="space-y-1">
                                    <p class="text-[14px] text-indigo-700 hover:underline cursor-pointer font-medium truncate">
                                        {{ page.seo.title || page.title || 'Titre de votre page SubnetCongo' }}
                                    </p>
                                    <p class="text-[13px] text-emerald-700 flex items-center gap-1">
                                        https://subnetcongo.cd <i class="pi pi-angle-right text-[10px]"></i> {{ page.slug || '...' }}
                                    </p>
                                    <p class="text-[13px] text-slate-600 line-clamp-2">
                                        {{ page.seo.description || 'Veuillez rédiger une meta description pour améliorer votre taux de clic dans les résultats de recherche Google.' }}
                                    </p>
                                </div>

                                <Divider class="my-8" />

                                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                    <div class="flex items-center gap-3 mb-2">
                                        <InputSwitch v-model="page.seo.no_index" />
                                        <span class="text-sm font-bold text-amber-800">Désactiver l'indexation (No Index)</span>
                                    </div>
                                    <p class="text-[11px] text-amber-600 font-medium">Cochez cette case si vous ne souhaitez pas que cette page apparaisse dans les moteurs de recherche (ex: page de remerciement).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- ONGLET : RÉVISIONS -->
                <TabPanel>
                    <template #header><i class="pi pi-history mr-2"></i> Historique</template>
                    <div class="pt-6">
                        <InlineMessage severity="info" class="w-full justify-start mb-6">Chaque modification génère une sauvegarde automatique. Vous pouvez restaurer une version précédente à tout moment.</InlineMessage>
                        <div class="space-y-4">
                            <div v-for="rev in [
                                { date: 'Aujourd\'hui, 10:45', author: 'Daniel Kikimba', msg: 'Correction fautes de frappe' },
                                { date: 'Hier, 18:20', author: 'Daniel Kikimba', msg: 'Mise à jour SEO' },
                                { date: '12 Jan 2024', author: 'Sarah Loft', msg: 'Création initiale' }
                            ]" :key="rev.date" class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm hover:border-indigo-200 transition-all cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <Avatar :label="rev.author[0]" shape="circle" class="bg-indigo-100 text-indigo-700 font-bold" />
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ rev.msg }}</p>
                                        <p class="text-xs text-slate-400">{{ rev.date }} • Par {{ rev.author }}</p>
                                    </div>
                                </div>
                                <Button label="Restaurer" icon="pi pi-refresh" class="p-button-text p-button-sm font-bold" />
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full px-4 pb-4">
                    <span class="text-xs text-slate-400 flex items-center gap-2">
                        <i class="pi pi-info-circle"></i> Dernière sauvegarde il y a 2 minutes
                    </span>
                    <div class="flex gap-2">
                        <Button label="Aperçu Live" icon="pi pi-external-link" class="p-button-text p-button-secondary font-bold" />
                        <Button label="Enregistrer & Quitter" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="savePage" :loading="loading" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- OVERLAY : PRÉVISUALISATION RAPIDE --- -->
        <OverlayPanel ref="op" class="w-[400px] shadow-2xl rounded-3xl border border-slate-100">
            <div v-if="page" class="p-2">
                <div class="aspect-video bg-slate-50 rounded-2xl mb-4 flex items-center justify-center border border-slate-100">
                    <i class="pi pi-file text-5xl text-slate-200"></i>
                </div>
                <h3 class="text-lg font-black text-slate-800 mb-2">{{ page.title }}</h3>
                <div class="flex gap-2 mb-4">
                    <Tag :value="page.template" severity="secondary" class="uppercase text-[9px]" />
                    <Tag :value="page.access" severity="info" class="uppercase text-[9px]" />
                </div>
                <Divider />
                <div class="flex justify-between items-center mt-4">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-slate-400 uppercase font-bold">Dernière MaJ</span>
                        <span class="text-sm font-bold text-slate-700">{{ page.lastUpdate }}</span>
                    </div>
                    <Button label="Voir la page" icon="pi pi-chevron-right" iconPos="right" class="p-button-sm p-button-outlined rounded-xl" />
                </div>
            </div>
        </OverlayPanel>

    </AppLayout>
</template>

<style scoped>
/* Custom PrimeVue Overrides to match SubnetCongo UI */

:deep(.p-datatable-modern .p-datatable-thead > tr > th) {
    background: #fdfdff;
    padding: 1.5rem 1rem;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-datatable-modern .p-datatable-tbody > tr) {
    background: white;
    transition: all 0.2s ease;
}

:deep(.p-datatable-modern .p-datatable-tbody > tr:hover) {
    background: #f8fafc !important;
    transform: scale(0.998);
}

:deep(.p-tabview .p-tabview-nav) {
    background: transparent;
    border: none;
    padding: 0 1rem;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #94a3b8;
    transition: all 0.3s;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 3px solid #6366F1;
}

:deep(.p-editor-container .p-editor-toolbar) {
    border-radius: 1rem 1rem 0 0;
    background: #F8FAFC;
    border-color: #e2e8f0;
}

:deep(.p-editor-container .p-editor-content) {
    border-radius: 0 0 1rem 1rem;
    border-color: #e2e8f0;
}

:deep(.p-dialog-header) {
    padding: 2rem;
    background: white;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
