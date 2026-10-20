<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - INTERNATIONALIZATION (i18n) MANAGER v40.0
 * ======================================================================================
 * Interface Ultimate : Design épuré, UX optimisée, Sticky Header, Seamless Editor.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';
import InputSwitch from 'primevue/inputswitch';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Chart from 'primevue/chart'; // <-- CORRECTION: Import ajouté

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const displayLangDialog = ref(false);
const displayKeyDialog = ref(false);
const displayImportDialog = ref(false);
const submitted = ref(false);
const viewMode = ref('list'); // 'list' ou 'editor'

// --- DONNEES DES LANGUES ---
const languages = ref([
    { id: 1, name: 'Français', code: 'fr', flag: '🇫🇷', is_default: true, status: true, progress: 100, keys: 2450 },
    { id: 2, name: 'English', code: 'en', flag: '🇬🇧', is_default: false, status: true, progress: 92, keys: 2254 },
    { id: 3, name: 'Lingala', code: 'ln', flag: '🇨🇩', is_default: false, status: true, progress: 65, keys: 1590 },
    { id: 4, name: 'Swahili', code: 'sw', flag: '🇹🇿', is_default: false, status: false, progress: 40, keys: 980 },
    { id: 5, name: 'Español', code: 'es', flag: '🇪🇸', is_default: false, status: false, progress: 0, keys: 0 }
]);

// --- SYSTEME DE TRADUCTION ---
const translationKeys = ref([
    { key: 'auth.login', group: 'Auth', fr: 'Connexion', en: 'Login', ln: 'Kokota', sw: 'Ingia' },
    { key: 'auth.register', group: 'Auth', fr: 'Inscription', en: 'Sign Up', ln: 'Komisa', sw: 'Jisajili' },
    { key: 'dashboard.welcome', group: 'Dashboard', fr: 'Bienvenue', en: 'Welcome', ln: 'Boyei bolamu', sw: 'Karibu' },
    { key: 'course.start', group: 'Courses', fr: 'Commencer le cours', en: 'Start Course', ln: 'Banda liteya', sw: 'Anza somo' },
    { key: 'system.save', group: 'Common', fr: 'Enregistrer', en: 'Save Changes', ln: 'Bombisa', sw: 'Hifadhi' },
    { key: 'system.cancel', group: 'Common', fr: 'Annuler', en: 'Cancel', ln: 'Tika', sw: 'Ghairi' },
    { key: 'notif.success', group: 'Notifications', fr: 'Opération réussie', en: 'Operation successful', ln: 'Ekosimba', sw: 'Mafanikio' },
    { key: 'footer.copy', group: 'Footer', fr: 'Tous droits réservés', en: 'All rights reserved', ln: 'Mansu mazali ya biso', sw: 'Haki zote zimehifadhiwa' }
]);

// --- FILTRES ---
const langFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const transFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    group: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const groups = ref(['Auth', 'Dashboard', 'Courses', 'Common', 'Notifications', 'Footer']);

// --- FORM STATES ---
const newLang = ref({ name: '', code: '', flag: '', status: true });
const newKey = ref({ key: '', group: 'Common', fr: '', en: '' });

// --- CHARTS CONFIG ---
const coverageData = ref({
    labels: ['Français', 'English', 'Lingala', 'Swahili'],
    datasets: [{
        data: [100, 92, 65, 40],
        backgroundColor: ['#4f46e5', '#10B981', '#F59E0B', '#EF4444'],
        borderRadius: 6
    }]
});

const chartOptions = {
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { 
        x: { max: 100, grid: { display: false }, border: { display: false } }, 
        y: { grid: { display: false }, border: { display: false } } 
    }
};

// --- ACTIONS ---
const editTranslations = (lang) => {
    viewMode.value = 'editor';
    toast.add({ severity: 'info', summary: 'Mode Éditeur', detail: `Modification du dictionnaire en cours.`, life: 2000 });
};

const saveLanguage = () => {
    submitted.value = true;
    if (newLang.value.name && newLang.value.code) {
        languages.value.push({ ...newLang.value, id: Date.now(), progress: 0, keys: 0 });
        toast.add({ severity: 'success', summary: 'Langue ajoutée', detail: 'La nouvelle locale est prête à être traduite.', life: 3000 });
        displayLangDialog.value = false;
        newLang.value = { name: '', code: '', flag: '', status: true };
    }
};

const updateTranslation = (data, field) => {
    toast.add({ severity: 'success', summary: 'Sauvegarde auto', detail: `Clé mise à jour avec succès.`, life: 1500 });
};

const autoTranslateMissing = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        toast.add({ severity: 'success', summary: 'Traduction IA terminée', detail: 'Les clés manquantes ont été générées.', life: 3000 });
    }, 1500);
};

const exportTranslations = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(translationKeys.value));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "subnet_translations.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});
</script>

<template>
    <AppLayout>
        <Head title="Internationalisation - SubNetCongo" />

        <div class="bg-slate-50/50 min-h-screen pb-20">

            <!-- --- STICKY HEADER --- -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl shadow-inner flex items-center justify-center">
                        <i class="pi pi-globe text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Internationalisation (i18n)</h1>
                            <Badge value="Multilang" severity="info" class="!text-[10px] !px-2 uppercase" />
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-0.5">Gérez le multilinguisme du système ERP</p>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <template v-if="viewMode === 'list'">
                        <Button label="Importer" icon="pi pi-upload" class="p-button-outlined p-button-secondary font-bold !rounded-xl" @click="displayImportDialog = true" />
                        <Button label="Nouvelle Langue" icon="pi pi-plus" class="p-button-primary font-bold shadow-md !rounded-xl" @click="displayLangDialog = true" />
                    </template>
                    <template v-else>
                        <Button label="Retour" icon="pi pi-arrow-left" class="p-button-text p-button-secondary font-bold" @click="viewMode = 'list'" />
                        <Button label="Nouvelle Clé" icon="pi pi-plus" class="p-button-outlined p-button-secondary font-bold !rounded-xl" @click="displayKeyDialog = true" />
                        <Button label="Traduction IA (SubNet)" icon="pi pi-bolt" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none font-bold shadow-md !rounded-xl" @click="autoTranslateMissing" />
                    </template>
                </div>
            </header>

            <div class="max-w-screen-2xl mx-auto px-6">
                <!-- --- KPI SECTION --- -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div v-for="kpi in [
                        { label: 'Langues Actives', val: languages.filter(l => l.status).length, icon: 'pi-flag', color: 'indigo' },
                        { label: 'Total Clés', val: '2,450', icon: 'pi-key', color: 'emerald' },
                        { label: 'Complétion', val: '74%', icon: 'pi-chart-line', color: 'amber' },
                        { label: 'Langue Principale', val: 'Français (FR)', icon: 'pi-star', color: 'rose' }
                    ]" :key="kpi.label" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ kpi.label }}</p>
                            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ kpi.val }}</div>
                        </div>
                        <div :class="`w-12 h-12 rounded-xl bg-${kpi.color}-50 flex items-center justify-center text-${kpi.color}-600`">
                            <i :class="`pi ${kpi.icon} text-xl`"></i>
                        </div>
                    </div>
                </div>

                <!-- --- MAIN VIEW CONTENT --- -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- LEFT PANEL (TABLES) -->
                    <div class="lg:col-span-9">

                        <!-- MODE: LISTE DES LANGUES -->
                        <div v-if="viewMode === 'list'" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            
                            <!-- Header Tableau -->
                            <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                                <h2 class="text-lg font-bold text-slate-800 tracking-tight">Locales Installées</h2>
                                <span class="relative w-full sm:w-72">
                                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                    <InputText v-model="langFilters['global'].value" placeholder="Rechercher une langue..." class="w-full pl-10 !rounded-lg" />
                                </span>
                            </div>

                            <DataTable v-model:filters="langFilters" :value="languages" :loading="loading" class="ultimate-table">
                                <Column field="name" header="Langue" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-4">
                                            <span class="text-3xl">{{ data.flag }}</span>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900">{{ data.name }}</span>
                                                    <Badge v-if="data.is_default" value="Défaut" severity="secondary" class="!text-[9px] uppercase tracking-wider" />
                                                </div>
                                                <span class="text-xs font-mono text-slate-400">{{ data.code }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="progress" header="Complétion" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1.5 pr-8">
                                            <div class="flex justify-between text-xs font-semibold">
                                                <span class="text-slate-600">{{ data.progress }}% Traduit</span>
                                                <span class="text-slate-400">{{ data.keys }} clés</span>
                                            </div>
                                            <ProgressBar :value="data.progress" :showValue="false" style="height: 6px" 
                                                :color="data.progress === 100 ? '#10B981' : (data.progress > 50 ? '#4f46e5' : '#F59E0B')" />
                                        </div>
                                    </template>
                                </Column>

                                <Column field="status" header="Visibilité" style="width: 8rem">
                                    <template #body="{ data }">
                                        <InputSwitch v-model="data.status" />
                                    </template>
                                </Column>

                                <Column alignFrozen="right" frozen style="width: 8rem">
                                    <template #body="{ data }">
                                        <Button label="Éditer" icon="pi pi-pencil" class="p-button-text p-button-sm font-bold text-indigo-600" @click="editTranslations(data)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- MODE: ÉDITEUR DE TRADUCTIONS -->
                        <div v-else class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            
                            <!-- Header Éditeur -->
                            <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                                <h2 class="text-lg font-bold text-slate-800 tracking-tight">Dictionnaire de traduction</h2>
                                <div class="flex items-center gap-2 w-full md:w-auto">
                                    <Dropdown v-model="transFilters['group'].value" :options="groups" placeholder="Groupe" class="w-40 !rounded-lg" showClear />
                                    <span class="relative w-full sm:w-64">
                                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                        <InputText v-model="transFilters['global'].value" placeholder="Chercher une clé..." class="w-full pl-10 !rounded-lg" />
                                    </span>
                                </div>
                            </div>

                            <DataTable v-model:filters="transFilters" :value="translationKeys" :paginator="true" :rows="15" editMode="cell" @cell-edit-complete="updateTranslation" class="ultimate-table ultimate-editor">
                                <Column field="key" header="Clé & Référence" sortable style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs text-slate-900 font-semibold">{{ data.key }}</span>
                                            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mt-0.5">{{ data.group }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="fr" header="Français 🇫🇷" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full !border-none !shadow-none focus:ring-0 !px-0 font-medium" />
                                    </template>
                                </Column>

                                <Column field="en" header="English 🇬🇧" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full !border-none !shadow-none focus:ring-0 !px-0 font-medium" />
                                    </template>
                                </Column>
                                
                                <Column field="ln" header="Lingala 🇨🇩" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full !border-none !shadow-none focus:ring-0 !px-0 font-medium" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>

                    <!-- RIGHT SIDEBAR (STATS & ACTIONS) -->
                    <div class="lg:col-span-3 space-y-6">

                        <!-- CHART -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900 mb-4 tracking-tight">Couverture Linguistique</h3>
                            <div class="h-40">
                                <Chart type="bar" :data="coverageData" :options="chartOptions" class="h-full" />
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                                <div class="flex justify-between items-center text-xs font-semibold">
                                    <span class="text-slate-500">Clés orphelines</span>
                                    <span class="text-rose-500 font-bold bg-rose-50 px-2 py-0.5 rounded">124 alertes</span>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3">
                            <Button label="Télécharger JSON" icon="pi pi-download" class="p-button-outlined p-button-secondary w-full !rounded-xl font-bold" @click="exportTranslations" />
                            <Button label="Scanner le code (Sync)" icon="pi pi-sync" class="p-button-text p-button-secondary w-full font-bold" v-tooltip="'Recherche de nouvelles clés dans les vues Blade/Vue'" />
                        </div>

                        <!-- LOGS -->
                        <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-lg">
                            <h3 class="text-sm font-bold mb-4 tracking-tight">Journal d'activité</h3>
                            <ScrollPanel style="height: 180px" class="custom-dark-scroll">
                                <div class="space-y-5 mt-2">
                                    <div v-for="log in [
                                        { user: 'Admin', action: 'Export JSON', date: 'Il y a 2h' },
                                        { user: 'Sarah', action: 'Traduit auth.login', date: 'Il y a 4h' },
                                        { user: 'AI Bot', action: 'Auto-traduction', date: 'Hier' }
                                    ]" :key="log.date" class="relative pl-5 border-l border-slate-700">
                                        <div class="absolute -left-1.5 top-1.5 w-2.5 h-2.5 rounded-full bg-indigo-500 border-2 border-slate-900"></div>
                                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">{{ log.date }}</p>
                                        <p class="text-xs font-medium text-slate-200 mt-0.5"><span class="text-indigo-300 font-bold">{{ log.user }}</span> {{ log.action }}</p>
                                    </div>
                                </div>
                            </ScrollPanel>
                        </div>

                    </div>
                </div>
            </div>

            <!-- --- MODALS (v40 Standard) --- -->
            
            <!-- Modal Add Language -->
            <Dialog v-model:visible="displayLangDialog" modal :style="{ width: '450px' }" header="Ajouter une Locale">
                <div class="pt-4 space-y-5">
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Nom de la langue</label>
                        <InputText v-model="newLang.name" placeholder="ex: Portugais" class="w-full !rounded-lg" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="field">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Code ISO</label>
                            <InputText v-model="newLang.code" placeholder="ex: pt" maxlength="2" class="w-full !rounded-lg font-mono" />
                        </div>
                        <div class="field">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Drapeau</label>
                            <InputText v-model="newLang.flag" placeholder="ex: 🇵🇹" class="w-full !rounded-lg text-center" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-100 rounded-xl">
                        <span class="text-sm font-semibold text-slate-700">Activer immédiatement</span>
                        <InputSwitch v-model="newLang.status" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Annuler" @click="displayLangDialog = false" class="p-button-text p-button-secondary font-bold" />
                    <Button label="Ajouter la langue" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none !rounded-lg font-bold shadow-md" @click="saveLanguage" />
                </template>
            </Dialog>

            <!-- Modal Add Key -->
            <Dialog v-model:visible="displayKeyDialog" modal :style="{ width: '500px' }" header="Créer une Clé">
                <div class="pt-4 space-y-5">
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Identifiant (Notation Point)</label>
                        <InputText v-model="newKey.key" placeholder="ex: auth.errors.password" class="w-full !rounded-lg font-mono text-sm" />
                    </div>
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Groupe</label>
                        <Dropdown v-model="newKey.group" :options="groups" class="w-full !rounded-lg" />
                    </div>
                    <div class="field">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Valeur Initiale (FR)</label>
                        <Textarea v-model="newKey.fr" rows="3" class="w-full !rounded-lg" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Annuler" @click="displayKeyDialog = false" class="p-button-text p-button-secondary font-bold" />
                    <Button label="Enregistrer" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none !rounded-lg font-bold shadow-md" @click="displayKeyDialog = false" />
                </template>
            </Dialog>

            <!-- Modal Import -->
            <Dialog v-model:visible="displayImportDialog" modal :style="{ width: '500px' }" header="Importer Traductions (JSON)">
                <div class="pt-4 space-y-4">
                    <FileUpload mode="basic" accept="application/json" :maxFileSize="1000000" chooseLabel="Parcourir le fichier" class="w-full p-button-outlined !rounded-lg font-bold" />
                    <Message severity="warn" class="!rounded-lg text-sm mt-4">Les clés existantes seront écrasées par les nouvelles valeurs du fichier.</Message>
                </div>
            </Dialog>

        </div>
    </AppLayout>
</template>

<style scoped>
/* 
 * ULTIMATE DATATABLE DESIGN (Style SaaS Moderne : Notion, Linear, Stripe)
 */
:deep(.ultimate-table .p-datatable-header) {
    display: none; /* Remplacé par notre div custom pour plus de flexibilité */
}

:deep(.ultimate-table .p-datatable-thead > tr > th) {
    background: #ffffff;
    color: #64748b;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}

:deep(.ultimate-table .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.25rem;
    color: #334155;
    transition: background-color 0.2s ease;
}

:deep(.ultimate-table .p-datatable-tbody > tr:hover > td) {
    background-color: #f8fafc;
}

/* 
 * EDITOR SPECIFIC STYLING (Seamless Edition)
 */
:deep(.ultimate-editor .p-editable-column) {
    cursor: text;
}

:deep(.ultimate-editor .p-cell-editing) {
    background-color: #f1f5f9 !important;
    padding: 0 !important;
}

:deep(.ultimate-editor .p-cell-editing input) {
    background-color: #f1f5f9;
    border-radius: 0;
    height: 100%;
    padding: 1rem 1.25rem;
}

/* Progress Bar Overrides */
:deep(.p-progressbar) {
    background: #f1f5f9;
    border-radius: 100px;
}
:deep(.p-progressbar .p-progressbar-value) {
    border-radius: 100px;
}

/* Modals Consistency */
:deep(.p-dialog-header) {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.p-dialog-content) {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
}
:deep(.p-dialog-footer) {
    border-top: none;
    padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Scrollbar Log Sombre (Esthétique Hacker/Terminal) */
.custom-dark-scroll::-webkit-scrollbar {
    width: 6px;
}
.custom-dark-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-dark-scroll::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}
.custom-dark-scroll::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>