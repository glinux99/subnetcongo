<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ProgressBar from 'primevue/progressbar';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import InlineMessage from 'primevue/inlinemessage';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Menu from 'primevue/menu';
import Message from 'primevue/message';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const activeTab = ref(0);
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

// --- SYSTEME DE TRADUCTION (MOCK DATABASE) ---
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
        backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#EF4444'],
        borderRadius: 10
    }]
});

const chartOptions = {
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { max: 100, grid: { display: false } }, y: { grid: { display: false } } }
};

// --- ACTIONS ---
const editTranslations = (lang) => {
    viewMode.value = 'editor';
    toast.add({ severity: 'info', summary: 'Éditeur chargé', detail: `Modification des clés pour : ${lang.name}`, life: 2000 });
};

const saveLanguage = () => {
    submitted.value = true;
    if (newLang.value.name && newLang.value.code) {
        languages.value.push({ ...newLang.value, id: Date.now(), progress: 0, keys: 0 });
        toast.add({ severity: 'success', summary: 'Langue ajoutée', detail: 'La nouvelle locale est prête.', life: 3000 });
        displayLangDialog.value = false;
        newLang.value = { name: '', code: '', flag: '', status: true };
    }
};

const updateTranslation = (data, field) => {
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: `Clé "${data.key}" mise à jour en ${field.toUpperCase()}`, life: 1500 });
};

const autoTranslateMissing = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        toast.add({ severity: 'success', summary: 'IA Traduction', detail: 'Toutes les clés vides ont été traduites via SubNet-AI.', life: 4000 });
    }, 2000);
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
    setTimeout(() => { loading.value = false; }, 1000);
});

</script>

<template>
    <AppLayout>
        <Head title="Gestion des Langues - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 rotate-3 hover:rotate-0 transition-transform cursor-pointer">
                        <i class="pi pi-globe text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Internationalisation (i18n)</h1>
                        <p class="text-slate-500 font-medium">Gérez le multilinguisme et les traductions de SubNetCongo</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <template v-if="viewMode === 'list'">
                        <Button label="Importer JSON" icon="pi pi-upload" class="p-button-outlined p-button-secondary border-2 font-bold" @click="displayImportDialog = true" />
                        <Button label="Nouvelle Langue" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" @click="displayLangDialog = true" />
                    </template>
                    <template v-else>
                        <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-text p-button-secondary font-bold" @click="viewMode = 'list'" />
                        <Button label="Ajouter une Clé" icon="pi pi-plus-circle" class="p-button-outlined p-button-indigo border-2 font-bold" @click="displayKeyDialog = true" />
                        <Button label="Auto-Traduction (AI)" icon="pi pi-bolt" class="p-button-warning shadow-lg px-6 font-bold" @click="autoTranslateMissing" />
                    </template>
                </div>
            </header>

            <!-- KPI SECTION -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Langues Actives', val: languages.filter(l => l.status).length, icon: 'pi-flag', color: 'indigo' },
                    { label: 'Total Clés', val: '2,450', icon: 'pi-key', color: 'emerald' },
                    { label: 'Complétion Moyenne', val: '74%', icon: 'pi-chart-line', color: 'amber' },
                    { label: 'Langue par défaut', val: 'Français (FR)', icon: 'pi-star', color: 'rose' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[1rem] border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500 group-hover:scale-110 transition-transform` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <!-- MAIN VIEW TOGGLE -->
            <div class="grid grid-cols-12 gap-8">

                <!-- LEFT CONTENT: TABLE OR EDITOR -->
                <div class="col-span-12 lg:col-span-9">

                    <!-- MODE: LISTE DES LANGUES -->
                    <Card v-if="viewMode === 'list'" class="border-none shadow-sm rounded-[1.5rem] overflow-hidden">
                        <template #content>
                            <DataTable
                                v-model:filters="langFilters"
                                :value="languages"
                                :paginator="true"
                                :rows="10"
                                :loading="loading"
                                class="p-datatable-lg custom-lang-table"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                                        <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter px-2">Locales Installées</h2>
                                        <span class="p-input-icon-left w-full md:w-80">
                                            <i class="pi pi-search" />
                                            <InputText v-model="langFilters['global'].value" placeholder="Rechercher une langue..." class="w-full rounded-2xl border-slate-200" />
                                        </span>
                                    </div>
                                </template>

                                <Column field="name" header="LANGUE" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-4">
                                            <span class="text-3xl shadow-sm">{{ data.flag }}</span>
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-black text-slate-800 text-lg">{{ data.name }}</span>
                                                    <Badge v-if="data.is_default" value="DEFAULT" severity="warning" class="text-[8px] font-black" />
                                                </div>
                                                <span class="text-xs font-mono text-slate-400 uppercase">{{ data.code }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="progress" header="COMPLÉTION" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex justify-between text-[10px] font-bold">
                                                <span class="text-slate-500">{{ data.progress }}% Traduit</span>
                                                <span class="text-slate-400">{{ data.keys }} clés</span>
                                            </div>
                                            <ProgressBar :value="data.progress" :showValue="false" style="height: 6px" class="rounded-full"
                                                :color="data.progress === 100 ? '#10B981' : (data.progress > 50 ? '#6366F1' : '#F59E0B')" />
                                        </div>
                                    </template>
                                </Column>

                                <Column field="status" header="ÉTAT" style="width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <InputSwitch v-model="data.status" />
                                            <span class="text-[9px] font-bold text-slate-400 uppercase text-center">{{ data.status ? 'Visible' : 'Masqué' }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="ACTION" alignFrozen="right" frozen style="width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <Button label="Éditer" icon="pi pi-pencil" class="p-button-text p-button-indigo p-button-sm font-black" @click="editTranslations(data)" />
                                            <Button icon="pi pi-ellipsis-v" class="p-button-rounded p-button-text p-button-secondary" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <!-- MODE: ÉDITEUR DE TRADUCTIONS -->
                    <Card v-else class="border-none shadow-sm rounded-[1.5rem] overflow-hidden">
                        <template #content>
                            <DataTable
                                v-model:filters="transFilters"
                                :value="translationKeys"
                                :paginator="true"
                                :rows="15"
                                editMode="cell"
                                @cell-edit-complete="updateTranslation"
                                class="p-datatable-sm custom-trans-table"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <Button icon="pi pi-filter-slash" class="p-button-outlined p-button-secondary p-button-sm" @click="transFilters['global'].value = null" />
                                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Éditeur de Clés</h2>
                                        </div>
                                        <div class="flex gap-2">
                                            <Dropdown v-model="transFilters['group'].value" :options="groups" placeholder="Groupe" class="w-40 rounded-xl" showClear />
                                            <span class="p-input-icon-left w-full md:w-64">
                                                <i class="pi pi-search" />
                                                <InputText v-model="transFilters['global'].value" placeholder="Chercher une clé..." class="w-full rounded-2xl border-slate-200" />
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                <Column field="key" header="CLÉ (LOCALE)" sortable style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs text-indigo-500 font-bold">{{ data.key }}</span>
                                            <span class="text-[9px] text-slate-300 font-bold uppercase">{{ data.group }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="fr" header="FRANÇAIS 🇫🇷" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full font-bold" />
                                    </template>
                                </Column>

                                <Column field="en" header="ENGLISH 🇬🇧" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full font-bold" />
                                    </template>
                                </Column>

                                <Column field="ln" header="LINGALA 🇨🇩" style="min-width: 12rem">
                                    <template #editor="{ data, field }">
                                        <InputText v-model="data[field]" class="w-full font-bold" />
                                    </template>
                                </Column>

                                <Column style="width: 4rem">
                                    <template #body>
                                        <i class="pi pi-pencil text-slate-300"></i>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </div>

                <!-- RIGHT SIDEBAR: TOOLS & STATS -->
                <div class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- COVERAGE CHART -->
                    <Card class="border-none shadow-sm rounded-3xl">
                        <template #title>
                            <div class="px-2 pt-2">
                                <span class="text-xs font-black uppercase tracking-widest text-indigo-400">Couverture Globale</span>
                                <h3 class="text-xl font-black text-slate-800">Santé i18n</h3>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-48 mt-4">
                                <Chart type="bar" :data="coverageData" :options="chartOptions" class="h-full" />
                            </div>
                            <Divider />
                            <div class="space-y-3">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-400">Clés orphelines</span>
                                    <span class="text-rose-500">124</span>
                                </div>
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-400">Dernier export</span>
                                    <span class="text-slate-800">Hier, 14:20</span>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- QUICK ACTIONS -->
                    <div class="space-y-4">
                        <Button label="Exporter JSON complet" icon="pi pi-download" class="p-button-indigo w-full shadow-lg rounded-2xl py-4 font-black uppercase tracking-widest text-sm" @click="exportTranslations" />
                        <Button label="Scanner le code (Sync)" icon="pi pi-search-plus" class="p-button-outlined p-button-indigo w-full border-2 rounded-2xl py-3 font-bold" v-tooltip="'Trouve les nouvelles clés dans le code source'" />
                    </div>

                    <!-- HELP BOX -->
                    <div class="p-6 bg-amber-50 rounded-[1rem] border border-amber-100 flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-lightbulb text-amber-500 text-2xl"></i>
                            <h4 class="font-black text-amber-900 text-lg">Le saviez-vous ?</h4>
                        </div>
                        <p class="text-xs text-amber-700 font-medium leading-relaxed">
                            SubNetCongo supporte les **Pluralisations** et les **Paramètres dynamiques** dans les clés. Utilisez `{n}` pour les nombres et `{name}` pour les variables.
                        </p>
                        <Button label="Guide Développeur" class="p-button-text p-button-warning p-0 text-xs font-black underline decoration-2 underline-offset-4" />
                    </div>

                    <!-- ACTIVITY LOG -->
                    <Card class="border-none shadow-sm rounded-3xl bg-slate-900 text-white overflow-hidden">
                        <template #title><span class="text-base font-black px-4 pt-4">Historique</span></template>
                        <template #content>
                            <ScrollPanel style="height: 200px" class="px-2">
                                <div class="space-y-4 py-2">
                                    <div v-for="log in [
                                        { user: 'Admin', action: 'Export JSON', date: 'Il y a 2h' },
                                        { user: 'Sarah', action: 'Traduit auth.login (LN)', date: 'Il y a 4h' },
                                        { user: 'AI Bot', action: 'Auto-traduction (SW)', date: 'Hier' }
                                    ]" :key="log.date" class="flex gap-4 items-start border-l border-slate-700 pl-4 relative">
                                        <div class="absolute -left-1.5 top-2 w-3 h-3 rounded-full bg-indigo-500"></div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">{{ log.date }}</p>
                                            <p class="text-xs font-bold"><span class="text-indigo-400">{{ log.user }}</span> {{ log.action }}</p>
                                        </div>
                                    </div>
                                </div>
                            </ScrollPanel>
                        </template>
                    </Card>

                </div>
            </div>

            <!-- MODAL: ADD LANGUAGE -->
            <Dialog v-model:visible="displayLangDialog" :modal="true" :style="{ width: '500px' }" header="🌍 Ajouter une Locale" class="p-fluid">
                <div class="space-y-6 pt-4">
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Nom de la langue</label>
                        <InputText v-model="newLang.name" placeholder="ex: Portugais" class="rounded-xl border-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Code ISO (2 chars)</label>
                            <InputText v-model="newLang.code" placeholder="ex: pt" maxlength="2" class="rounded-xl font-mono" />
                        </div>
                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Emoji Drapeau</label>
                            <InputText v-model="newLang.flag" placeholder="ex: 🇵🇹" class="rounded-xl" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                        <span class="text-sm font-bold text-slate-700">Activer immédiatement</span>
                        <InputSwitch v-model="newLang.status" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Annuler" @click="displayLangDialog = false" class="p-button-text p-button-secondary font-bold" />
                    <Button label="Installer la langue" icon="pi pi-check" class="p-button-primary shadow-lg shadow-indigo-100 font-black rounded-xl" @click="saveLanguage" />
                </template>
            </Dialog>

            <!-- MODAL: ADD KEY -->
            <Dialog v-model:visible="displayKeyDialog" :modal="true" :style="{ width: '550px' }" header="🆕 Créer une Clé de Traduction" class="p-fluid">
                <div class="space-y-6 pt-4">
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Identifiant de la clé</label>
                        <InputText v-model="newKey.key" placeholder="ex: common.errors.timeout" class="rounded-xl font-mono border-2" />
                        <small class="text-slate-400">Utilisez la notation par points pour organiser vos clés.</small>
                    </div>
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Groupe de destination</label>
                        <Dropdown v-model="newKey.group" :options="groups" class="rounded-xl shadow-sm" />
                    </div>
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Valeur par défaut (FR)</label>
                        <Textarea v-model="newKey.fr" rows="3" class="rounded-xl" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Fermer" @click="displayKeyDialog = false" class="p-button-text p-button-secondary font-bold" />
                    <Button label="Enregistrer la clé" icon="pi pi-save" class="p-button-primary font-black rounded-xl" />
                </template>
            </Dialog>

            <!-- MODAL: IMPORT -->
            <Dialog v-model:visible="displayImportDialog" :modal="true" :style="{ width: '600px' }" header="📥 Importation de Traductions" class="p-fluid">
                <div class="py-4 space-y-6">
                    <FileUpload name="demo[]" url="./upload" @upload="onUpload" :multiple="true" accept="application/json" :maxFileSize="1000000" class="custom-upload">
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-10 border-2 border-dashed border-slate-200 rounded-3xl">
                                <i class="pi pi-cloud-upload text-5xl text-slate-300 mb-4"></i>
                                <p class="text-slate-500 font-bold">Glissez-déposez vos fichiers .json ici</p>
                            </div>
                        </template>
                    </FileUpload>
                    <Message severity="warn" class="rounded-2xl">L'importation remplacera les clés existantes ayant le même nom.</Message>
                </div>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Localization Engine. i18n Toolkit v5.2.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo Languages */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-lang-table) {
    border: none;
}

:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2.5rem 1.5rem;
}

:deep(.p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    text-transform: uppercase;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
}

:deep(.p-datatable.custom-trans-table .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #F8FAFC;
    padding: 1.25rem 1rem;
}

:deep(.p-datatable-sm .p-datatable-thead > tr > th) {
    padding: 1rem;
    font-size: 0.65rem;
}

:deep(.p-progressbar .p-progressbar-value) {
    border-radius: 10px;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem 2rem 1rem 2rem;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 2rem 2rem;
}

/* Editor Cell Styling */
:deep(.p-editable-column.p-cell-editing) {
    background: #F5F3FF !important;
    padding: 0 !important;
}

:deep(.p-editable-column input) {
    border: none !important;
    box-shadow: none !important;
    padding: 1.25rem 1rem !important;
    background: transparent !important;
}

/* Tabs UI */
:deep(.p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #F1F5F9;
}

/* Transitions */
.p-button-indigo {
    background: #6366F1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4F46E5;
}

/* File Upload */
:deep(.custom-upload .p-fileupload-buttonbar) {
    background: transparent;
    border: none;
    padding: 0;
}

:deep(.custom-upload .p-fileupload-content) {
    padding: 1rem 0;
    border: none;
}
</style>
