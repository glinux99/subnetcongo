<script setup>
/**
 * VIRTUAL CLASSROOM & LIVE SESSIONS MANAGER - SUBNETCONGO LMS
 * Version: 7.1.0
 * Description: Interface de pilotage pour l'enseignement en temps réel et gestion de replay.
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
import Calendar from 'primevue/calendar';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import MultiSelect from 'primevue/multiselect';
import Knob from 'primevue/knob';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';

// --- INITIALIZATION ---
const toast = useToast();
const loading = ref(true);
const sessionDialog = ref(false);
const recordSidebar = ref(false);
const activeTab = ref(0);

// --- BREADCRUMBS ---
const home = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'E-learning' },
    { label: 'Classes Virtuelles', class: 'font-bold text-indigo-600' }
]);

// --- REFERENCE DATA ---
const providers = ref([
    { label: 'Zoom Video', value: 'zoom', icon: 'pi pi-video' },
    { label: 'Google Meet', value: 'meet', icon: 'pi pi-google' },
    { label: 'BigBlueButton', value: 'bbb', icon: 'pi pi-users' },
    { label: 'Jitsi Meet (Open Source)', value: 'jitsi', icon: 'pi pi-comments' }
]);

const instructors = ref([
    { name: 'Daniel Kikimba', id: 1, avatar: 'https://i.pravatar.cc/150?u=1' },
    { name: 'Sarah Loft', id: 2, avatar: 'https://i.pravatar.cc/150?u=2' },
    { name: 'Kevin Ndongala', id: 3, avatar: 'https://i.pravatar.cc/150?u=3' }
]);

// --- MOCK DATA: LIVE SESSIONS ---
const sessions = ref([
    {
        id: 1,
        title: 'Atelier Pratique : Laravel Eloquent Avançé',
        course: 'Mastering Laravel 11',
        instructor: 'Daniel Kikimba',
        provider: 'zoom',
        start_at: '2024-02-10 18:30',
        duration: 90,
        status: 'Live', // Live, Scheduled, Ended, Cancelled
        participants: 45,
        max_participants: 100,
        meeting_id: '854-965-7412',
        passcode: 'SUB-2024',
        is_recorded: true
    },
    {
        id: 2,
        title: 'Q&A hebdomadaire UI/UX Design',
        course: 'UI/UX Masterclass',
        instructor: 'Sarah Loft',
        provider: 'meet',
        start_at: '2024-02-12 14:00',
        duration: 60,
        status: 'Scheduled',
        participants: 0,
        max_participants: 50,
        meeting_id: 'meet.google.com/abc-defg-hij',
        passcode: null,
        is_recorded: false
    }
]);

// --- UI STATE ---
const session = ref({
    title: '',
    course: null,
    instructor: null,
    provider: 'zoom',
    start_at: null,
    duration: 60,
    status: 'Scheduled',
    max_participants: 100,
    is_recorded: true,
    settings: {
        waiting_room: true,
        mute_on_entry: true,
        allow_chat: true
    }
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- METHODS ---
const openNew = () => {
    session.value = {
        provider: 'zoom',
        status: 'Scheduled',
        duration: 60,
        max_participants: 100,
        is_recorded: true,
        settings: { waiting_room: true, mute_on_entry: true, allow_chat: true }
    };
    activeTab.value = 0;
    sessionDialog.value = true;
};

const editSession = (data) => {
    session.value = JSON.parse(JSON.stringify(data));
    sessionDialog.value = true;
};

const startLive = (id) => {
    toast.add({ severity: 'info', summary: 'Démarrage du Live', detail: 'Ouverture de la passerelle vidéo...', life: 3000 });
    // Logique d'ouverture de l'API de streaming
};

const saveSession = () => {
    toast.add({ severity: 'success', summary: 'Session Planifiée', detail: 'Les invitations ont été envoyées aux inscrits.', life: 3000 });
    sessionDialog.value = false;
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Live': return 'danger';
        case 'Scheduled': return 'success';
        case 'Ended': return 'secondary';
        case 'Cancelled': return 'warning';
        default: return 'info';
    }
};

onMounted(() => {
    setTimeout(() => { loading.value = false; }, 800);
});

// --- CHART DATA: LIVE ENGAGEMENT ---
const engagementChart = ref({
    labels: ['18:30', '18:45', '19:00', '19:15', '19:30', '19:45', '20:00'],
    datasets: [{
        label: 'Apprenants Connectés',
        data: [10, 35, 45, 42, 48, 44, 40],
        borderColor: '#EF4444',
        tension: 0.4,
        fill: true,
        backgroundColor: 'rgba(239, 68, 68, 0.1)'
    }]
});

</script>

<template>
    <AppLayout>
        <Head title="Classes Virtuelles | SubnetCongo" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <Breadcrumb :home="home" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-5 rounded-[2.2rem] shadow-2xl shadow-indigo-100 relative overflow-hidden group">
                            <i class="pi pi-video text-white text-3xl z-10 relative"></i>
                            <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">Classes Virtuelles</h1>
                            <p class="text-slate-500 font-medium italic">Pilotez vos sessions interactives et webinaires <span class="text-indigo-600 font-bold">SubnetCongo</span>.</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Calendrier" icon="pi pi-calendar" class="p-button-text p-button-secondary rounded-xl" />
                    <Divider layout="vertical" />
                    <Button label="Nouvelle Session" icon="pi pi-plus" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="openNew" />
                </div>
            </header>

            <!-- --- MONITORING KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">

                <!-- Live Now Card with Pulse -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            </span>
                            <span class="text-xs font-black text-rose-500 uppercase tracking-widest">En Direct</span>
                        </div>
                        <Badge value="1 Session" severity="danger" />
                    </div>
                    <h3 class="text-xl font-black text-slate-800 line-clamp-1">Laravel Eloquent Avançé</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-2">Démarré il y a 24 minutes</p>
                    <Button label="Rejoindre" class="w-full mt-6 p-button-sm p-button-danger rounded-xl font-bold" />
                </div>

                <div v-for="stat in [
                    { label: 'Prévus aujourd\'hui', val: '4', icon: 'pi-calendar-plus', color: 'indigo' },
                    { label: 'Moy. Présence', val: '88%', icon: 'pi-users', color: 'emerald' },
                    { label: 'Stockage Cloud', val: '124 GB', icon: 'pi-cloud', color: 'amber' }
                ]" :key="stat.label" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col justify-center">
                    <div class="flex items-center gap-4">
                        <div :class="`w-12 h-12 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center shadow-inner` ">
                            <i :class="['pi text-xl', stat.icon]"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                            <p class="text-3xl font-black text-slate-800 tracking-tight">{{ stat.val }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- --- SESSIONS DATA TABLE --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-12">
                <div class="p-10 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-6 bg-slate-50/20">
                    <div>
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">Liste des Sessions</h2>
                        <p class="text-sm text-slate-400 font-medium">Recherchez et gérez les classes virtuelles passées et futures.</p>
                    </div>
                    <div class="flex gap-4">
                        <SelectButton v-model="filters['status'].value" :options="['Live', 'Scheduled', 'Ended']" class="custom-selectbutton" />
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters['global'].value" placeholder="Rechercher..." class="w-72 rounded-2xl border-none ring-1 ring-slate-200 focus:ring-2 focus:ring-indigo-600 transition-all" />
                        </IconField>
                    </div>
                </div>

                <DataTable
                    :value="sessions"
                    :paginator="true"
                    :rows="10"
                    :filters="filters"
                    class="p-datatable-custom"
                    responsiveLayout="stack"
                >
                    <Column field="title" header="Session & Instructeur" style="min-width: 20rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-100">
                                    <i :class="[providers.find(p => p.value === data.provider)?.icon, 'text-2xl text-slate-400']"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 leading-tight">{{ data.title }}</span>
                                    <span class="text-[10px] text-indigo-500 font-bold uppercase tracking-tighter mt-1">{{ data.instructor }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="start_at" header="Date & Heure" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700">{{ data.start_at }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase">{{ data.duration }} Minutes</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="text-[10px] font-black uppercase px-3 py-1 tracking-widest rounded-lg" />
                        </template>
                    </Column>

                    <Column field="participants" header="Participants">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1 w-24">
                                <div class="flex justify-between text-[10px] font-bold text-slate-500">
                                    <span>{{ data.participants }} / {{ data.max_participants }}</span>
                                </div>
                                <ProgressBar :value="(data.participants / data.max_participants) * 100" :showValue="false" style="height: 4px" class="rounded-full" />
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" class="text-right" alignFrozen="right" :frozen="true">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button v-if="data.status === 'Live'" label="Démarrer" icon="pi pi-external-link" class="p-button-danger p-button-sm rounded-xl font-bold px-4" @click="startLive(data.id)" />
                                <Button v-else icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="editSession(data)" />
                                <Button icon="pi pi-chart-line" class="p-button-rounded p-button-text p-button-info" v-tooltip.top="'Rapport'" @click="recordSidebar = true" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- --- SESSION EDITOR DIALOG --- -->
        <Dialog v-model:visible="sessionDialog" :style="{width: '1000px'}" header="Paramétrage Classe Virtuelle" :modal="true" class="p-fluid rounded-3xl overflow-hidden shadow-2xl">
            <TabView v-model:activeIndex="activeTab" class="custom-tabs mt-4">

                <!-- TAB 1: SCHEDULE & PROVIDER -->
                <TabPanel>
                    <template #header><i class="pi pi-calendar mr-2"></i> Planification</template>
                    <div class="grid grid-cols-12 gap-8 pt-6">
                        <div class="col-span-12 lg:col-span-8 space-y-6">
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Titre de la session</label>
                                <InputText v-model="session.title" placeholder="Ex: Revue de code hebdomadaire" class="text-xl font-bold p-4 rounded-2xl border-slate-200 shadow-inner" />
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Date et Heure</label>
                                    <Calendar v-model="session.start_at" showTime hourFormat="24" class="rounded-xl shadow-inner" placeholder="Sélectionner..." />
                                </div>
                                <div class="field">
                                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Durée estimée (minutes)</label>
                                    <InputNumber v-model="session.duration" showButtons suffix=" min" class="rounded-xl" />
                                </div>
                            </div>
                            <div class="field">
                                <label class="text-xs font-black text-slate-400 uppercase tracking-widest block mb-2">Plateforme Vidéo</label>
                                <SelectButton v-model="session.provider" :options="providers" optionLabel="label" optionValue="value" class="custom-provider-select">
                                    <template #option="{ option }">
                                        <div class="flex flex-col items-center gap-2 p-2">
                                            <i :class="[option.icon, 'text-2xl']"></i>
                                            <span class="text-[9px] font-black uppercase">{{ option.label }}</span>
                                        </div>
                                    </template>
                                </SelectButton>
                            </div>
                        </div>

                        <div class="col-span-12 lg:col-span-4 bg-slate-50 p-8 rounded-[2.5rem] border border-slate-100 space-y-6">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <i class="pi pi-lock text-indigo-500"></i> Contrôle d'accès
                            </h4>
                            <div class="field">
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-2">Nombre max d'étudiants</label>
                                <Slider v-model="session.max_participants" :min="10" :max="500" class="mt-4" />
                                <div class="flex justify-between mt-2 text-xs font-black text-indigo-600">
                                    <span>10</span>
                                    <span>{{ session.max_participants }}</span>
                                    <span>500</span>
                                </div>
                            </div>
                            <Divider />
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-700">Enregistrement Auto</span>
                                    <InputSwitch v-model="session.is_recorded" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-700">Salle d'attente</span>
                                    <InputSwitch v-model="session.settings.waiting_room" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-700">Micro éteint à l'entrée</span>
                                    <InputSwitch v-model="session.settings.mute_on_entry" />
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- TAB 2: INVITATIONS & NOTIFICATIONS -->
                <TabPanel>
                    <template #header><i class="pi pi-send mr-2"></i> Diffusion</template>
                    <div class="pt-8 max-w-3xl mx-auto space-y-10">
                        <div class="bg-indigo-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden">
                            <i class="pi pi-envelope absolute -right-6 -bottom-6 text-9xl text-white/5"></i>
                            <div class="relative z-10">
                                <h3 class="text-2xl font-black italic mb-2 tracking-tight">Notification de Classe</h3>
                                <p class="text-indigo-200 text-sm font-medium leading-relaxed mb-8">Un email contenant le lien direct et les codes d'accès sera envoyé à tous les apprenants inscrits au cours associé.</p>
                                <div class="flex gap-4">
                                    <div class="flex-1 bg-white/10 p-4 rounded-2xl border border-white/20">
                                        <p class="text-[10px] font-black text-indigo-300 uppercase mb-1">Cible</p>
                                        <p class="text-lg font-bold">128 Apprenants</p>
                                    </div>
                                    <div class="flex-1 bg-white/10 p-4 rounded-2xl border border-white/20">
                                        <p class="text-[10px] font-black text-indigo-300 uppercase mb-1">Canal</p>
                                        <p class="text-lg font-bold">Email + Push In-App</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h4 class="text-lg font-black text-slate-800">Paramètres de rappel</h4>
                            <div class="flex gap-4">
                                <div v-for="t in ['1h avant', '15 min avant', 'Au démarrage']" :key="t" class="flex-1 p-4 bg-white border border-slate-200 rounded-2xl flex items-center justify-between group cursor-pointer hover:border-indigo-400 transition-all">
                                    <span class="text-sm font-bold text-slate-600">{{ t }}</span>
                                    <i class="pi pi-check-circle text-emerald-500"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <!-- TAB 3: RESOURCES & ATTACHMENTS -->
                <TabPanel>
                    <template #header><i class="pi pi-file mr-2"></i> Documents de support</template>
                    <div class="pt-8">
                        <div class="p-10 border-2 border-dashed border-slate-200 rounded-[3rem] bg-slate-50 text-center flex flex-col items-center justify-center space-y-4 hover:bg-white transition-colors cursor-pointer">
                            <div class="w-20 h-20 bg-indigo-100 rounded-3xl flex items-center justify-center text-indigo-600 shadow-inner">
                                <i class="pi pi-upload text-4xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-800">Uploader les supports de cours</h3>
                                <p class="text-slate-400 text-sm italic">PDF, Slides, Code source (Max 50MB)</p>
                            </div>
                            <FileUpload mode="basic" chooseLabel="Parcourir les fichiers" class="p-button-outlined px-8 rounded-xl font-bold" />
                        </div>

                        <div class="mt-8 space-y-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Fichiers rattachés</h4>
                            <div v-for="i in 2" :key="i" class="p-4 bg-white border border-slate-100 rounded-2xl flex items-center justify-between shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center gap-4">
                                    <i class="pi pi-file-pdf text-rose-500 text-xl"></i>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Support_Eloquent_v{{ i }}.pdf</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">1.2 MB • PDF Document</p>
                                    </div>
                                </div>
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" />
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>

            <template #footer>
                <div class="flex justify-between items-center w-full px-6 pb-6 border-t border-slate-50 pt-4">
                    <span class="text-xs text-slate-400 italic">API Provider Status: <span class="text-emerald-500 font-bold">Connecté</span></span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="sessionDialog = false" />
                        <Button label="Valider la Session" icon="pi pi-check" class="p-button-primary px-8 rounded-2xl shadow-lg shadow-indigo-100" @click="saveSession" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- --- RECORDING & ANALYTICS SIDEBAR --- -->
        <Sidebar v-model:visible="recordSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <div class="p-8 space-y-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-rose-600 rounded-2xl shadow-lg shadow-rose-100">
                        <i class="pi pi-chart-bar text-white"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Rapport de Session</h2>
                </div>

                <!-- Live Performance Chart -->
                <div class="space-y-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Engagement en Direct</h3>
                    <div class="h-48">
                        <Chart type="line" :data="engagementChart" :options="{ maintainAspectRatio: false, plugins: { legend: { display: false } } }" class="h-full" />
                    </div>
                </div>

                <Divider />

                <!-- Statistics Grid -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Durée réelle</p>
                        <h4 class="text-xl font-black text-slate-800">92 Min</h4>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Messages Chat</p>
                        <h4 class="text-xl font-black text-slate-800">142</h4>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Sondages émis</p>
                        <h4 class="text-xl font-black text-slate-800">3</h4>
                    </div>
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 text-center">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Questions (Q&A)</p>
                        <h4 class="text-xl font-black text-slate-800">18</h4>
                    </div>
                </div>

                <Divider />

                <!-- Recording Management -->
                <div class="space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Gérer l'enregistrement</h3>
                    <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-play-circle text-indigo-600 text-2xl"></i>
                            <div>
                                <p class="text-sm font-bold text-indigo-900 leading-tight">Replay Disponible</p>
                                <p class="text-[10px] text-indigo-400 font-bold uppercase">Qualité 1080p • 850 MB</p>
                            </div>
                        </div>
                        <Button icon="pi pi-download" class="p-button-text p-button-indigo" />
                    </div>
                    <div class="flex items-center justify-between p-2">
                        <span class="text-sm font-bold text-slate-600">Visibilité pour les élèves</span>
                        <InputSwitch :modelValue="true" />
                    </div>
                </div>

                <div class="pt-10">
                    <Button label="Télécharger Rapport PDF Complet" icon="pi pi-file-pdf" class="w-full p-button-outlined rounded-2xl font-bold p-button-lg" />
                </div>
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/**
 * CUSTOM STYLES - SUBNETCONGO LIVE SESSION MODULE
 */

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
    transition: all 0.3s;
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

/* Provider SelectButton */
:deep(.custom-provider-select .p-button) {
    background: white;
    border: 1px solid #f1f5f9;
    flex: 1;
    transition: all 0.2s;
}

:deep(.custom-provider-select .p-button.p-highlight) {
    background: #6366F1;
    color: white;
    border-color: #6366F1;
}

/* Progress bar & Inputs */
:deep(.p-progressbar) {
    background: #f1f5f9;
    border-radius: 10px;
}

:deep(.p-inputtext), :deep(.p-inputnumber-input), :deep(.p-dropdown) {
    border-radius: 12px;
    border-color: #e2e8f0;
}

/* Scrollbar Custom */
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
