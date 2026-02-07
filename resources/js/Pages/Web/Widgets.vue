<script setup>
/**
 * WIDGETS & BLOCKS MANAGER - SUBNETCONGO LMS
 * Version: 2.1.0
 * Description: Système de gestion de composants dynamiques et zones de contenu.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import OrderList from 'primevue/orderlist';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import Slider from 'primevue/slider';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chip from 'primevue/chip';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Breadcrumb from 'primevue/breadcrumb';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ScrollPanel from 'primevue/scrollpanel';
import Tooltip from 'primevue/tooltip';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();
const loading = ref(true);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Site Web' },
    { label: 'Gestion des Widgets', class: 'font-bold text-indigo-600' }
]);

// --- RÉGIONS & EMPLACEMENTS ---
const zones = ref([
    { label: 'Page d\'accueil (Top)', value: 'home_hero', icon: 'pi pi-star' },
    { label: 'Sidebar Apprenant', value: 'student_sidebar', icon: 'pi pi-user' },
    { label: 'Zone de Cours (Sidebar)', value: 'course_sidebar', icon: 'pi pi-book' },
    { label: 'Pied de page (Widgets)', value: 'footer_widgets', icon: 'pi pi-map' }
]);
const selectedZone = ref('home_hero');

// --- BIBLIOTHÈQUE DE WIDGETS DISPONIBLES ---
const widgetLibrary = ref([
    { id: 'w1', name: 'Calendrier des Examens', type: 'system', description: 'Affiche les dates des prochains quiz.', icon: 'pi pi-calendar', color: 'bg-rose-500' },
    { id: 'w2', name: 'Newsletter Subnet', type: 'marketing', description: 'Formulaire de capture d\'emails.', icon: 'pi pi-envelope', color: 'bg-indigo-500' },
    { id: 'w3', name: 'Cours Récents', type: 'learning', description: 'Liste des derniers cours consultés.', icon: 'pi pi-clock', color: 'bg-emerald-500' },
    { id: 'w4', name: 'Support Live Chat', type: 'system', description: 'Bouton de contact direct.', icon: 'pi pi-comment', color: 'bg-amber-500' },
    { id: 'w5', name: 'Bannière Promo', type: 'marketing', description: 'Image cliquable pour offres spéciales.', icon: 'pi pi-megaphone', color: 'bg-purple-500' },
    { id: 'w6', name: 'Statistiques de Progression', type: 'learning', description: 'Graphique circulaire de réussite.', icon: 'pi pi-chart-pie', color: 'bg-blue-500' }
]);

// --- WIDGETS ACTIFS PAR ZONE (Simulé) ---
const activeWidgets = ref([
    { id: 1, widgetId: 'w6', title: 'Ma Progression', visibility: ['student'], isActive: true, config: { style: 'circular' } },
    { id: 2, widgetId: 'w3', title: 'Continuer l\'apprentissage', visibility: ['student', 'teacher'], isActive: true, config: { limit: 3 } },
    { id: 3, widgetId: 'w1', title: 'Échéances Proches', visibility: ['all'], isActive: false, config: { show_exams: true } }
]);

// --- OPTIONS DE CONFIGURATION ---
const roles = ref([
    { name: 'Tous les visiteurs', code: 'all' },
    { name: 'Apprenants', code: 'student' },
    { name: 'Enseignants', code: 'teacher' },
    { name: 'Administrateurs', code: 'admin' }
]);

// --- ÉTAT UI ---
const widgetDialog = ref(false);
const configSidebar = ref(false);
const showAddDialog = ref(false);
const currentWidget = ref(null);
const previewDevice = ref('desktop');

// --- LOGIQUE ---
const openConfig = (widget) => {
    currentWidget.value = { ...widget };
    configSidebar.value = true;
};

const saveConfig = () => {
    const index = activeWidgets.value.findIndex(w => w.id === currentWidget.value.id);
    activeWidgets.value[index] = { ...currentWidget.value };
    configSidebar.value = false;
    toast.add({ severity: 'success', summary: 'Configuré', detail: 'Widget mis à jour avec succès.', life: 3000 });
};

const addWidgetToZone = (libraryWidget) => {
    const newWidget = {
        id: Date.now(),
        widgetId: libraryWidget.id,
        title: libraryWidget.name,
        visibility: ['all'],
        isActive: true,
        config: {}
    };
    activeWidgets.value.push(newWidget);
    showAddDialog.value = false;
    toast.add({ severity: 'success', summary: 'Ajouté', detail: 'Widget ajouté à la zone active.', life: 2000 });
};

const removeWidget = (id) => {
    activeWidgets.value = activeWidgets.value.filter(w => w.id !== id);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'Widget retiré de la zone.', life: 2000 });
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

const getLibraryInfo = (id) => widgetLibrary.value.find(w => w.id === id);

</script>

<template>
    <AppLayout>
        <Head title="Éditeur de Widgets & Zones" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100">
                            <i class="pi pi-th-large text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Widgets & Interface</h1>
                            <p class="text-slate-500 font-medium">Structurez l'expérience interactive de <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button label="Aperçu Frontend" icon="pi pi-external-link" class="p-button-outlined p-button-secondary bg-white rounded-2xl" />
                    <Button label="Sauvegarder la Disposition" icon="pi pi-save" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-2xl font-bold" />
                </div>
            </header>

            <div class="grid grid-cols-12 gap-8">

                <!-- --- COLONNE GAUCHE : ZONES & NAVIGATION --- -->
                <aside class="col-span-12 lg:col-span-3 space-y-6">
                    <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Zones de Contenu</h3>
                        <div class="space-y-2">
                            <div v-for="zone in zones" :key="zone.value"
                                @click="selectedZone = zone.value"
                                :class="['flex items-center gap-4 p-4 rounded-2xl cursor-pointer transition-all',
                                selectedZone === zone.value ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'hover:bg-slate-50 text-slate-600']">
                                <i :class="zone.icon"></i>
                                <span class="text-sm font-bold">{{ zone.label }}</span>
                                <i v-if="selectedZone === zone.value" class="pi pi-chevron-right ml-auto text-xs opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden">
                        <i class="pi pi-info-circle absolute -right-4 -bottom-4 text-8xl opacity-10"></i>
                        <h4 class="text-lg font-bold mb-2">Ordre des Blocs</h4>
                        <p class="text-xs text-slate-400 leading-relaxed mb-4">
                            L'ordre d'affichage sur le site suit scrupuleusement l'ordre défini dans l'éditeur central. Glissez les blocs pour les réorganiser.
                        </p>
                        <Button label="En savoir plus" class="p-button-text p-button-sm p-0 text-indigo-400 font-bold" />
                    </div>
                </aside>

                <!-- --- CENTRE : ÉDITEUR DE ZONE --- -->
                <main class="col-span-12 lg:col-span-6">
                    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                            <div>
                                <h2 class="text-xl font-black text-slate-800 tracking-tight">Configuration de la Zone</h2>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">
                                    {{ zones.find(z => z.value === selectedZone)?.label }}
                                </p>
                            </div>
                            <Button icon="pi pi-plus" label="Ajouter un Widget" class="p-button-sm rounded-xl" @click="showAddDialog = true" />
                        </div>

                        <div class="p-8">
                            <OrderList v-model="activeWidgets" listStyle="height:auto" dataKey="id" class="custom-orderlist">
                                <template #item="{ item }">
                                    <div class="flex items-center p-5 bg-white border border-slate-100 rounded-[2rem] mb-4 hover:shadow-xl hover:shadow-slate-200/50 transition-all group">
                                        <!-- Drag Handle Icon -->
                                        <div class="mr-6 text-slate-300 group-hover:text-indigo-600 cursor-move">
                                            <i class="pi pi-bars text-xl"></i>
                                        </div>

                                        <!-- Widget Info -->
                                        <div class="flex-1 flex items-center gap-5">
                                            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg', getLibraryInfo(item.widgetId)?.color]">
                                                <i :class="[getLibraryInfo(item.widgetId)?.icon, 'text-2xl']"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-black text-slate-800 tracking-tight">{{ item.title }}</span>
                                                    <Badge v-if="!item.isActive" value="Désactivé" severity="danger" />
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                                                    Type: {{ getLibraryInfo(item.widgetId)?.type }} • {{ item.visibility.join(', ') }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="flex gap-2">
                                            <InputSwitch v-model="item.isActive" v-tooltip.top="'Activer/Désactiver'" />
                                            <Divider layout="vertical" />
                                            <Button icon="pi pi-cog" class="p-button-rounded p-button-text p-button-secondary" @click="openConfig(item)" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="removeWidget(item.id)" />
                                        </div>
                                    </div>
                                </template>
                            </OrderList>

                            <div v-if="activeWidgets.length === 0" class="flex flex-col items-center justify-center py-20 text-center opacity-40">
                                <i class="pi pi-plus-circle text-6xl mb-4"></i>
                                <p class="text-xl font-bold">Aucun widget dans cette zone</p>
                                <p class="text-sm">Cliquez sur le bouton "Ajouter" pour commencer.</p>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- --- DROITE : PRÉVISUALISATION MOBILE --- -->
                <aside class="col-span-12 lg:col-span-3 space-y-8">
                    <div class="bg-slate-900 rounded-[3.5rem] p-4 shadow-2xl relative overflow-hidden h-[750px] border-8 border-slate-800">
                        <!-- Phone Notch -->
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-800 rounded-b-2xl z-20"></div>

                        <!-- Screen Content -->
                        <div class="bg-white h-full w-full rounded-[2.5rem] overflow-hidden relative">
                            <ScrollPanel class="h-full">
                                <!-- Fake Header -->
                                <div class="bg-indigo-600 p-6 pt-10 text-white flex justify-between items-center">
                                    <i class="pi pi-bars"></i>
                                    <span class="font-black text-sm italic">SubnetCongo</span>
                                    <Avatar icon="pi pi-user" shape="circle" class="bg-white/20" />
                                </div>

                                <div class="p-4 space-y-4">
                                    <!-- Render Active Widgets (Simulated) -->
                                    <template v-for="w in activeWidgets" :key="w.id">
                                        <div v-if="w.isActive" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <i :class="[getLibraryInfo(w.widgetId)?.icon, 'text-indigo-600 text-xs']"></i>
                                                <span class="text-[10px] font-black uppercase text-slate-800">{{ w.title }}</span>
                                            </div>
                                            <div class="h-12 bg-white/50 border border-dashed border-slate-200 rounded-lg flex items-center justify-center">
                                                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-tighter italic">Composant Dynamique</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </ScrollPanel>
                        </div>
                    </div>

                    <div class="flex justify-center gap-3">
                        <SelectButton v-model="previewDevice" :options="['mobile', 'tablet', 'desktop']" class="custom-selectbutton">
                            <template #option="{ option }">
                                <i :class="['pi', option === 'mobile' ? 'pi-mobile' : option === 'tablet' ? 'pi-tablet' : 'pi-desktop']"></i>
                            </template>
                        </SelectButton>
                    </div>
                </aside>
            </div>
        </div>

        <!-- --- DIALOG : AJOUTER UN WIDGET (LIBRARY) --- -->
        <Dialog v-model:visible="showAddDialog" :style="{width: '850px'}" header="Bibliothèque de Widgets" :modal="true" class="p-fluid">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4 pb-8">
                <div v-for="lib in widgetLibrary" :key="lib.id"
                    @click="addWidgetToZone(lib)"
                    class="group bg-white border border-slate-100 rounded-3xl p-6 hover:shadow-2xl hover:shadow-indigo-100 transition-all cursor-pointer border-t-4"
                    :style="{ borderTopColor: 'rgba(99, 102, 241, 0.5)' }">
                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white mb-6 shadow-lg', lib.color]">
                        <i :class="[lib.icon, 'text-xl']"></i>
                    </div>
                    <h4 class="text-lg font-black text-slate-800 mb-2">{{ lib.name }}</h4>
                    <p class="text-xs text-slate-400 font-medium leading-relaxed">{{ lib.description }}</p>
                    <div class="mt-6 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-[10px] font-black text-indigo-600 uppercase">Ajouter +</span>
                        <Tag :value="lib.type" severity="secondary" class="text-[9px] uppercase" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- --- SIDEBAR : CONFIGURATION DÉTAILLÉE --- -->
        <Sidebar v-model:visible="configSidebar" position="right" class="w-full md:w-[450px] p-sidebar-custom">
            <div v-if="currentWidget" class="flex flex-col h-full bg-white">
                <div class="p-8 bg-slate-50 border-b border-slate-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-xl', getLibraryInfo(currentWidget.widgetId)?.color]">
                            <i :class="getLibraryInfo(currentWidget.widgetId)?.icon"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ currentWidget.title }}</h2>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Réglages du module</p>
                        </div>
                    </div>
                </div>

                <ScrollPanel class="flex-1 p-8">
                    <div class="space-y-8 pb-10">
                        <!-- Section: Identité -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-600 rounded-full"></span> Basique
                            </h4>
                            <div class="field">
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Titre personnalisé</label>
                                <InputText v-model="currentWidget.title" class="p-3 rounded-xl w-full" />
                            </div>
                            <div class="field">
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Visibilité par rôle</label>
                                <MultiSelect v-model="currentWidget.visibility" :options="roles" optionLabel="name" optionValue="code" placeholder="Tous les publics" display="chip" class="rounded-xl" />
                            </div>
                        </div>

                        <Divider />

                        <!-- Section: Configuration Spécifique -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-4 bg-emerald-600 rounded-full"></span> Paramètres Avancés
                            </h4>

                            <!-- Ces champs pourraient varier selon le type de widget -->
                            <div class="field">
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Limite d'affichage</label>
                                <div class="flex items-center gap-4">
                                    <Slider v-model="currentWidget.config.limit" :min="1" :max="10" class="flex-1" />
                                    <Badge :value="currentWidget.config.limit || 5" severity="info" />
                                </div>
                            </div>

                            <div class="bg-indigo-50 p-5 rounded-2xl border border-indigo-100 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-indigo-900">Afficher sur mobile</p>
                                    <p class="text-[10px] text-indigo-700">Optimiser pour les petits écrans.</p>
                                </div>
                                <InputSwitch v-model="currentWidget.config.show_mobile" />
                            </div>
                        </div>

                        <!-- Section: Styles CSS -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1 h-4 bg-slate-800 rounded-full"></span> Design
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="text-xs font-bold text-slate-500 mb-2 block">Padding</label>
                                    <Dropdown v-model="currentWidget.config.padding" :options="['sm', 'md', 'lg']" class="rounded-xl" />
                                </div>
                                <div class="field">
                                    <label class="text-xs font-bold text-slate-500 mb-2 block">Arrondi</label>
                                    <Dropdown v-model="currentWidget.config.radius" :options="['none', 'md', 'full']" class="rounded-xl" />
                                </div>
                            </div>
                        </div>
                    </div>
                </ScrollPanel>

                <div class="p-8 border-t border-slate-100 flex gap-3">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold flex-1" @click="configSidebar = false" />
                    <Button label="Appliquer" icon="pi pi-check" class="p-button-primary px-10 rounded-2xl shadow-xl shadow-indigo-100 flex-1" @click="saveConfig" />
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO LMS UI
 */

/* OrderList Overrides */
:deep(.p-orderlist-controls) {
    display: none; /* Cache les flèches de contrôle standard pour utiliser le drag handle */
}

:deep(.p-orderlist .p-orderlist-list) {
    padding: 0;
    background: transparent;
    border: none;
}

:deep(.p-orderlist .p-orderlist-item) {
    padding: 0;
    margin-bottom: 0.5rem;
    border: none;
    background: transparent;
    transition: all 0.2s;
}

:deep(.p-orderlist .p-orderlist-item.p-highlight) {
    background: transparent;
    color: inherit;
}

/* UI Components Customization */
:deep(.p-dialog-header) {
    padding: 2rem;
    background: #fdfdff;
    border-radius: 2rem 2rem 0 0;
}

:deep(.p-sidebar-custom) {
    border-radius: 3rem 0 0 3rem;
}

:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
}

:deep(.p-selectbutton .p-button) {
    background: white;
    border: 1px solid #e2e8f0;
    padding: 0.75rem 1rem;
}

:deep(.p-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

/* Animations */
.cursor-move {
    cursor: grab;
}
.cursor-move:active {
    cursor: grabbing;
}

/* Phone Simulator Scrollbar */
:deep(.p-scrollpanel-content) {
    padding: 0;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.p-sidebar-custom .field {
    animation: slideIn 0.4s ease-out forwards;
}

/* Tooltip & Badges */
:deep(.p-tooltip-text) {
    font-size: 10px;
    font-weight: 700;
    background: #1e293b;
    border-radius: 8px;
}
</style>
