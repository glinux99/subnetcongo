<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import Editor from 'primevue/editor'; // Pour le contenu de l'article
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

const toast = useToast();

// --- DONNÉES DE RÉFÉRENCE ---
const categories = ref(['Technologie', 'E-learning', 'Nouveautés', 'Tutoriels', 'Carrière']);
const statuses = ref([
    { label: 'Publié', value: 'published' },
    { label: 'Brouillon', value: 'draft' },
    { label: 'Planifié', value: 'scheduled' }
]);

const posts = ref([
    {
        id: 1,
        title: 'Comment Laravel révolutionne le web en RDC',
        author: 'Daniel Kikimba',
        category: 'Technologie',
        status: 'published',
        views: 1250,
        date: '2024-02-01',
        image: 'https://picsum.photos/seed/post1/400/200'
    },
    {
        id: 2,
        title: 'Top 10 des extensions VS Code pour Vue 3',
        author: 'Sarah Loft',
        category: 'Tutoriels',
        status: 'draft',
        views: 0,
        date: '2024-02-05',
        image: 'https://picsum.photos/seed/post2/400/200'
    },
    {
        id: 3,
        title: 'Lancement de notre nouvelle application Mobile',
        author: 'Admin Subnet',
        category: 'Nouveautés',
        status: 'scheduled',
        views: 0,
        date: '2024-03-15',
        image: 'https://picsum.photos/seed/post3/400/200'
    }
]);

// --- ÉTAT DE L'INTERFACE ---
const postDialog = ref(false);
const deleteDialog = ref(false);
const post = ref({
    title: '',
    content: '',
    category: null,
    status: 'draft',
    tags: [],
    seo_title: '',
    seo_description: '',
    allow_comments: true,
    publish_date: null
});
const submitted = ref(false);
const filters = ref({
    global: { value: null }
});

// --- ACTIONS ---
const openNew = () => {
    post.value = {
        title: '',
        content: '',
        status: 'draft',
        allow_comments: true,
        publish_date: new Date()
    };
    submitted.value = false;
    postDialog.value = true;
};

const editPost = (p) => {
    post.value = { ...p };
    postDialog.value = true;
};

const savePost = () => {
    submitted.value = true;
    if (post.value.title.trim()) {
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Article enregistré', life: 3000 });
        postDialog.value = false;
    }
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'published': return 'success';
        case 'draft': return 'secondary';
        case 'scheduled': return 'info';
        default: return null;
    }
};

const getStatusLabel = (status) => {
    return statuses.value.find(s => s.value === status)?.label || status;
};

</script>

<template>
    <AppLayout>
        <Head title="Gestion du Blog" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div>
                    <div class="flex items-center gap-2 text-indigo-600 font-bold mb-1">
                        <i class="pi pi-pencil"></i>
                        <span class="uppercase tracking-widest text-xs">Content Management</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tight">Articles de Blog</h1>
                    <p class="text-slate-500 font-medium">Rédigez, planifiez et analysez vos publications publiques.</p>
                </div>
                <Button label="Nouvel Article" icon="pi pi-plus" class="p-button-primary shadow-lg shadow-indigo-200 px-6 rounded-2xl" @click="openNew" />
            </header>

            <!-- --- DASHBOARD MINI STATS --- -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Articles Publiés</p>
                        <p class="text-3xl font-black text-slate-800">42</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                        <i class="pi pi-check-circle text-xl"></i>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Vues Totales</p>
                        <p class="text-3xl font-black text-slate-800">12.8k</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <i class="pi pi-eye text-xl"></i>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Commentaires</p>
                        <p class="text-3xl font-black text-slate-800">156</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                        <i class="pi pi-comments text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- --- DATA TABLE --- -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <DataTable :value="posts" :paginator="true" :rows="10" class="p-datatable-modern"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['title', 'category']">

                    <template #header>
                        <div class="flex justify-between items-center p-2">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher un article..." class="w-80 rounded-xl bg-slate-50 border-none ring-1 ring-slate-200" />
                            </IconField>
                        </div>
                    </template>

                    <Column field="title" header="Article" style="min-width: 25rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <img :src="data.image" class="w-16 h-12 rounded-lg object-cover border border-slate-100 shadow-sm" />
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-800 line-clamp-1">{{ data.title }}</span>
                                    <span class="text-xs text-slate-400">Par {{ data.author }} • {{ data.date }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="category" header="Catégorie" sortable>
                        <template #body="{ data }">
                            <span class="text-sm font-medium text-slate-600 bg-slate-100 px-3 py-1 rounded-full">{{ data.category }}</span>
                        </template>
                    </Column>

                    <Column field="status" header="État" sortable>
                        <template #body="{ data }">
                            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" class="uppercase text-[10px]" />
                        </template>
                    </Column>

                    <Column field="views" header="Impact" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-line text-indigo-400"></i>
                                <span class="text-sm font-bold text-slate-700">{{ data.views }} vues</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editPost(data)" />
                                <Button icon="pi pi-external-link" class="p-button-rounded p-button-text p-button-info" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- MODAL ÉDITEUR --- -->
        <Dialog v-model:visible="postDialog" :style="{width: '1000px'}" header="Rédaction de l'article" :modal="true" class="p-fluid">
            <TabView class="mt-4">
                <!-- Onglet Contenu -->
                <TabPanel header="Contenu Principal">
                    <div class="grid grid-cols-12 gap-6 pt-4">
                        <div class="col-span-12 lg:col-span-8 space-y-4">
                            <div class="field">
                                <label class="font-bold text-slate-700 block mb-2">Titre de l'article</label>
                                <InputText v-model="post.title" placeholder="Titre accrocheur..." class="text-lg font-bold py-3" />
                            </div>
                            <div class="field">
                                <label class="font-bold text-slate-700 block mb-2">Corps de l'article</label>
                                <Editor v-model="post.content" editorStyle="height: 400px" placeholder="Commencez à écrire votre histoire..." />
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 space-y-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                            <div>
                                <label class="font-bold text-slate-700 block mb-2 text-sm uppercase">Image à la une</label>
                                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center bg-white">
                                    <i class="pi pi-image text-3xl text-slate-300 mb-2"></i>
                                    <FileUpload mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" class="p-button-sm p-button-outlined w-full" label="Remplacer l'image" />
                                </div>
                            </div>

                            <div class="field">
                                <label class="font-bold text-slate-700 block mb-2 text-sm uppercase">Catégorie</label>
                                <Dropdown v-model="post.category" :options="categories" placeholder="Choisir..." />
                            </div>

                            <div class="field">
                                <label class="font-bold text-slate-700 block mb-2 text-sm uppercase">Publication</label>
                                <Dropdown v-model="post.status" :options="statuses" optionLabel="label" optionValue="value" />
                                <Calendar v-if="post.status === 'scheduled'" v-model="post.publish_date" showTime hourFormat="24" class="mt-2" placeholder="Choisir date et heure" />
                            </div>

                            <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200">
                                <span class="text-sm font-bold text-slate-600">Autoriser commentaires</span>
                                <InputSwitch v-model="post.allow_comments" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- Onglet SEO -->
                <TabPanel header="Optimisation SEO">
                    <div class="pt-4 space-y-6 max-w-2xl">
                        <div class="field">
                            <label class="font-bold text-slate-700 block mb-2">Balise Meta Title</label>
                            <InputText v-model="post.seo_title" placeholder="Titre pour Google..." />
                            <small class="text-slate-400">Conseillé : moins de 60 caractères.</small>
                        </div>
                        <div class="field">
                            <label class="font-bold text-slate-700 block mb-2">Meta Description</label>
                            <Textarea v-model="post.seo_description" rows="3" placeholder="Résumé pour les résultats de recherche..." />
                        </div>

                        <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                            <p class="text-xs font-bold text-blue-600 uppercase mb-2">Aperçu Google</p>
                            <p class="text-blue-800 font-bold mb-1">{{ post.seo_title || post.title || 'Titre de l\'article' }}</p>
                            <p class="text-green-700 text-xs mb-1">subnetcongo.cd > blog > {{ post.title?.toLowerCase().replace(/ /g, '-') }}</p>
                            <p class="text-slate-600 text-sm line-clamp-2">{{ post.seo_description || 'Rédigez une description pour voir l\'aperçu ici...' }}</p>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="postDialog = false" />
                    <Button label="Enregistrer l'article" icon="pi pi-check" class="p-button-primary px-6 rounded-xl shadow-lg shadow-indigo-100" @click="savePost" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
:deep(.p-datatable-modern .p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 1.5rem 1rem;
    border-bottom: 1px solid #e2e8f0;
}

:deep(.p-datatable-modern .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-editor-container .p-editor-content) {
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
}

:deep(.p-tabview .p-tabview-nav) {
    border: none;
    background: transparent;
}

:deep(.p-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 700;
    color: #94a3b8;
}

:deep(.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    border-bottom: 2px solid #6366F1;
}
</style>
