<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import TreeTable from 'primevue/treetable'; // Pour la hiérarchie des menus
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import Toolbar from 'primevue/toolbar';
import SelectButton from 'primevue/selectbutton';
import InputSwitch from 'primevue/inputswitch';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Sidebar from 'primevue/sidebar';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();

// --- DONNÉES DE RÉFÉRENCE ---
const menuLocations = ref([
    { label: 'Navigation Principale (Header)', value: 'main_header', icon: 'pi pi-external-link' },
    { label: 'Pied de page (Footer)', value: 'footer', icon: 'pi pi-arrow-down' },
    { label: 'Menu Mobile', value: 'mobile', icon: 'pi pi-mobile' },
    { label: 'Sidebar Administration', value: 'admin_sidebar', icon: 'pi pi-cog' }
]);

const linkTypes = ref([
    { label: 'Lien Interne (Page)', value: 'internal' },
    { label: 'URL Externe', value: 'external' },
    { label: 'Ancre (#)', value: 'anchor' },
    { label: 'Catégorie de cours', value: 'course_cat' }
]);

const userRoles = ref([
    { name: 'Tous les visiteurs', code: 'public' },
    { name: 'Utilisateurs connectés', code: 'auth' },
    { name: 'Étudiants uniquement', code: 'student' },
    { name: 'Enseignants uniquement', code: 'instructor' },
    { name: 'Administrateurs', code: 'admin' }
]);

const internalPages = ref([
    { name: 'Accueil', url: '/' },
    { name: 'Tous les cours', url: '/courses' },
    { name: 'À propos', url: '/about' },
    { name: 'Contact', url: '/contact' },
    { name: 'Blog', url: '/blog' },
    { name: 'FAQ', url: '/faq' }
]);

// --- ÉTAT DU COMPOSANT ---
const selectedLocation = ref('main_header');
const menuItems = ref([
    {
        key: '0',
        data: { label: 'Accueil', url: '/', type: 'internal', visibility: ['public'], status: true, icon: 'pi pi-home' },
        children: []
    },
    {
        key: '1',
        data: { label: 'Nos Formations', url: '/courses', type: 'internal', visibility: ['public'], status: true, icon: 'pi pi-book' },
        children: [
            {
                key: '1-0',
                data: { label: 'Développement Web', url: '/courses/web', type: 'course_cat', visibility: ['public'], status: true, icon: 'pi pi-code' }
            },
            {
                key: '1-1',
                data: { label: 'Design UI/UX', url: '/courses/design', type: 'course_cat', visibility: ['public'], status: true, icon: 'pi pi-palette' }
            }
        ]
    },
    {
        key: '2',
        data: { label: 'Espace Étudiant', url: '/dashboard', type: 'internal', visibility: ['student', 'admin'], status: true, icon: 'pi pi-user' },
        children: [
            {
                key: '2-0',
                data: { label: 'Mes Inscriptions', url: '/my-courses', type: 'internal', visibility: ['student'], status: true, icon: 'pi pi-list' }
            }
        ]
    }
]);

const editDialog = ref(false);
const item = ref({});
const submitted = ref(false);
const loading = ref(false);
const expandedKeys = ref({ '1': true, '2': true });

// --- MÉTHODES DE LOGIQUE ---

const onNodeExpand = (node) => {
    expandedKeys.value[node.key] = true;
};

const openNewItem = (parent = null) => {
    item.value = {
        label: '',
        url: '',
        type: 'internal',
        visibility: ['public'],
        status: true,
        icon: 'pi pi-link',
        target: '_self',
        parentKey: parent ? parent.key : null
    };
    submitted.value = false;
    editDialog.value = true;
};

const editItem = (node) => {
    item.value = { ...node.data, key: node.key };
    editDialog.value = true;
};

const deleteItem = (node) => {
    // Logique récursive pour supprimer dans la structure TreeTable
    const removeNode = (list, key) => {
        for (let i = 0; i < list.length; i++) {
            if (list[i].key === key) {
                list.splice(i, 1);
                return true;
            }
            if (list[i].children && removeNode(list[i].children, key)) return true;
        }
        return false;
    };

    removeNode(menuItems.value, node.key);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Élément retiré du menu', life: 3000 });
};

const saveItem = () => {
    submitted.value = true;
    if (item.value.label.trim()) {
        // Logique pour mettre à jour ou ajouter
        if (item.value.key) {
            updateNode(menuItems.value, item.value.key, { ...item.value });
        } else {
            const newNode = {
                key: Math.random().toString(36).substr(2, 9),
                data: { ...item.value },
                children: []
            };
            if (item.value.parentKey) {
                addNodeAsChild(menuItems.value, item.value.parentKey, newNode);
            } else {
                menuItems.value.push(newNode);
            }
        }

        toast.add({ severity: 'success', summary: 'Succès', detail: 'Menu mis à jour', life: 3000 });
        editDialog.value = false;
    }
};

const updateNode = (list, key, newData) => {
    for (let node of list) {
        if (node.key === key) {
            node.data = newData;
            return;
        }
        if (node.children) updateNode(node.children, key, newData);
    }
};

const addNodeAsChild = (list, parentKey, newNode) => {
    for (let node of list) {
        if (node.key === parentKey) {
            if (!node.children) node.children = [];
            node.children.push(newNode);
            return;
        }
        if (node.children) addNodeAsChild(node.children, parentKey, newNode);
    }
};

const getVisibilityLabel = (codes) => {
    if (!codes || codes.length === 0) return 'Tous';
    return codes.map(c => userRoles.value.find(r => r.code === c)?.name).join(', ');
};

const switchMenu = () => {
    loading.value = true;
    setTimeout(() => { loading.value = false; }, 500);
};

const moveUp = (node) => { /* Logique de réorganisation */ };
const moveDown = (node) => { /* Logique de réorganisation */ };

</script>

<template>
    <AppLayout>
        <Head title="Configuration des Menus" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER DE LA PAGE --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-5">
                    <div class="bg-slate-900 p-4 rounded-2xl shadow-xl">
                        <i class="pi pi-map text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Gestion de la Navigation</h1>
                        <p class="text-slate-500 font-medium italic">Configurez l'ossature et les liens de SubnetCongo</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-2 bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <span class="text-xs font-black text-slate-400 uppercase px-3">Emplacement :</span>
                    <Dropdown v-model="selectedLocation" :options="menuLocations" optionLabel="label" optionValue="value"
                        @change="switchMenu" class="w-72 border-none bg-slate-50 font-bold text-indigo-600 rounded-xl" />
                    <Button icon="pi pi-save" label="Enregistrer l'ordre" class="p-button-primary rounded-xl px-4" />
                </div>
            </header>

            <div class="grid grid-cols-12 gap-8">

                <!-- --- COLONNE GAUCHE : ÉDITEUR DE STRUCTURE --- -->
                <div class="col-span-12 lg:col-span-8">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">

                        <Toolbar class="bg-white/50 border-none p-6">
                            <template #start>
                                <div class="flex gap-2">
                                    <Button label="Ajouter à la racine" icon="pi pi-plus" class="p-button-outlined rounded-xl" @click="openNewItem()" />
                                    <Button label="Tout replier" icon="pi pi-minus-circle" class="p-button-text p-button-secondary rounded-xl" @click="expandedKeys = {}" />
                                </div>
                            </template>
                            <template #end>
                                <Tag severity="info" class="rounded-lg px-3">
                                    <i class="pi pi-info-circle mr-2"></i> Glisser-déposer bientôt disponible
                                </Tag>
                            </template>
                        </Toolbar>

                        <TreeTable :value="menuItems" :expandedKeys="expandedKeys" class="p-treetable-modern" :loading="loading">
                            <Column field="label" header="Libellé du lien" expander style="width: 30%">
                                <template #body="{ node }">
                                    <div class="flex items-center gap-3">
                                        <i :class="node.data.icon" class="text-slate-400"></i>
                                        <span class="font-bold text-slate-700">{{ node.data.label }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column field="url" header="Destination" style="width: 25%">
                                <template #body="{ node }">
                                    <code class="text-[10px] bg-slate-100 px-2 py-1 rounded text-indigo-600 font-bold">{{ node.data.url }}</code>
                                </template>
                            </Column>

                            <Column field="visibility" header="Visibilité" style="width: 25%">
                                <template #body="{ node }">
                                    <div class="flex flex-wrap gap-1">
                                        <Tag v-for="role in node.data.visibility" :key="role" :value="role" severity="secondary" class="text-[9px] uppercase" />
                                    </div>
                                </template>
                            </Column>

                            <Column header="Actions" class="text-right" style="width: 20%">
                                <template #body="{ node }">
                                    <div class="flex justify-end gap-1">
                                        <Button icon="pi pi-plus-circle" class="p-button-rounded p-button-text p-button-success" v-tooltip.top="'Sous-menu'" @click="openNewItem(node)" />
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editItem(node)" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="deleteItem(node)" />
                                    </div>
                                </template>
                            </Column>
                        </TreeTable>
                    </div>

                    <!-- Aide & Astuces -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-indigo-900 rounded-3xl p-6 text-white relative overflow-hidden">
                            <i class="pi pi-lightbulb absolute -right-4 -bottom-4 text-8xl opacity-10 rotate-12"></i>
                            <h3 class="text-lg font-bold mb-2">Conseil SEO</h3>
                            <p class="text-indigo-200 text-sm leading-relaxed">
                                Utilisez des libellés courts et explicites. Les menus hiérarchiques aident Google à comprendre la structure de votre plateforme SubnetCongo.
                            </p>
                        </div>
                        <div class="bg-emerald-50 rounded-3xl p-6 border border-emerald-100">
                            <h3 class="text-lg font-bold text-emerald-900 mb-2">Liens Dynamiques</h3>
                            <p class="text-emerald-700 text-sm leading-relaxed">
                                Les liens de type "Catégorie de cours" se mettent à jour automatiquement si vous renommez une catégorie dans le LMS.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- --- COLONNE DROITE : PRÉVIEW & RÉGLAGES --- -->
                <div class="col-span-12 lg:col-span-4 space-y-8">

                    <!-- Prévisualisation Visuelle -->
                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Live Preview</span>
                            <Badge value="SubnetCongo UI" severity="danger" />
                        </div>

                        <div class="space-y-4 border-l-2 border-slate-800 pl-6">
                            <div v-for="node in menuItems" :key="node.key" class="group cursor-default">
                                <div class="flex items-center gap-3 text-slate-400 group-hover:text-white transition-colors">
                                    <i :class="node.data.icon" class="text-sm"></i>
                                    <span class="font-bold tracking-tight">{{ node.data.label }}</span>
                                </div>
                                <div v-if="node.children && node.children.length > 0" class="mt-3 ml-6 space-y-2 border-l border-slate-800 pl-4">
                                    <div v-for="child in node.children" :key="child.key" class="text-xs text-slate-500 hover:text-indigo-400 cursor-pointer">
                                        {{ child.data.label }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Divider class="border-slate-800 my-8" />

                        <div class="flex flex-col gap-4">
                            <p class="text-xs text-slate-500 font-medium">Ce menu s'affichera pour les rôles :</p>
                            <div class="flex gap-2">
                                <Avatar icon="pi pi-user" shape="circle" class="bg-slate-800" />
                                <Avatar icon="pi pi-users" shape="circle" class="bg-indigo-600" />
                                <Avatar icon="pi pi-shield" shape="circle" class="bg-slate-800" />
                            </div>
                        </div>
                    </div>

                    <!-- Paramètres Globaux -->
                    <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                        <h3 class="text-lg font-black text-slate-800 mb-6">Configuration</h3>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Menu épinglé</p>
                                    <p class="text-[10px] text-slate-400">Reste visible au scroll</p>
                                </div>
                                <InputSwitch :modelValue="true" />
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Icônes visibles</p>
                                    <p class="text-[10px] text-slate-400">Afficher les pictos PrimeIcons</p>
                                </div>
                                <InputSwitch :modelValue="true" />
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Comptes à rebours</p>
                                    <p class="text-[10px] text-slate-400">Pour les promotions limitées</p>
                                </div>
                                <InputSwitch :modelValue="false" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- --- MODAL D'ÉDITION D'ÉLÉMENT --- -->
        <Dialog v-model:visible="editDialog" :style="{width: '500px'}" header="Propriétés du lien" :modal="true" class="p-fluid">
            <div class="space-y-6 pt-4">

                <div class="field">
                    <label class="font-black text-xs text-slate-400 uppercase tracking-widest block mb-2">Titre du lien</label>
                    <InputText v-model="item.label" placeholder="Ex: Boutique" required autofocus />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label class="font-black text-xs text-slate-400 uppercase tracking-widest block mb-2">Type</label>
                        <Dropdown v-model="item.type" :options="linkTypes" optionLabel="label" optionValue="value" />
                    </div>
                    <div class="field">
                        <label class="font-black text-xs text-slate-400 uppercase tracking-widest block mb-2">Icône</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="item.icon" placeholder="pi pi-link" />
                        </IconField>
                    </div>
                </div>

                <div class="field">
                    <label class="font-black text-xs text-slate-400 uppercase tracking-widest block mb-2">Destination (URL)</label>
                    <div v-if="item.type === 'internal'" class="flex gap-2">
                        <Dropdown v-model="item.url" :options="internalPages" optionLabel="name" optionValue="url" placeholder="Choisir une page" class="flex-1" />
                    </div>
                    <InputText v-else v-model="item.url" placeholder="https://..." />
                </div>

                <div class="field">
                    <label class="font-black text-xs text-slate-400 uppercase tracking-widest block mb-2">Cible de visibilité</label>
                    <MultiSelect v-model="item.visibility" :options="userRoles" optionLabel="name" optionValue="code" placeholder="Tous les publics" display="chip" />
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-bold text-slate-600">Ouvrir dans un nouvel onglet</span>
                        <SelectButton v-model="item.target" :options="[{label: 'Non', value: '_self'}, {label: 'Oui', value: '_blank'}]" optionLabel="label" optionValue="value" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-600">Activer le lien</span>
                        <InputSwitch v-model="item.status" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full pt-4">
                    <Button label="Supprimer" icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" v-if="item.key" @click="deleteItem({key: item.key})" />
                    <div class="flex gap-2 ml-auto">
                        <Button label="Annuler" class="p-button-text p-button-secondary" @click="editDialog = false" />
                        <Button label="Appliquer" icon="pi pi-check" class="p-button-primary px-6 rounded-xl" @click="saveItem" />
                    </div>
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* --- STYLISATION DU TREETABLE --- */

:deep(.p-treetable-modern) {
    border: none;
}

:deep(.p-treetable-modern .p-treetable-thead > tr > th) {
    background: #FDFDFF;
    padding: 1.5rem 1rem;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.p-treetable-modern .p-treetable-tbody > tr) {
    background: white;
    transition: all 0.2s;
}

:deep(.p-treetable-modern .p-treetable-tbody > tr:hover) {
    background: #f8fafc !important;
}

:deep(.p-treetable-modern .p-treetable-tbody > tr > td) {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f8fafc;
}

:deep(.p-treetable-toggler) {
    width: 2rem;
    height: 2rem;
    color: #6366f1 !important;
}

/* --- DIALOG & INPUTS --- */

:deep(.p-dialog-header) {
    border-radius: 2rem 2rem 0 0;
    padding: 1.5rem;
}

:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-multiselect) {
    border-radius: 12px;
    border-color: #e2e8f0;
}

:deep(.p-selectbutton .p-button) {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
}

:deep(.p-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    border-color: #6366F1;
}

/* Scrollbar Smooth */
::-webkit-scrollbar {
    width: 5px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
