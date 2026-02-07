<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- PRIME VUE IMPORTS ---
import Card from 'primevue/card';
import Chart from 'primevue/chart';
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
import Editor from 'primevue/editor';
import InputSwitch from 'primevue/inputswitch';
import ProgressBar from 'primevue/progressbar';
import MultiSelect from 'primevue/multiselect';
import Toast from 'primevue/toast';
import Menu from 'primevue/menu';
import InlineMessage from 'primevue/inlinemessage';
import Divider from 'primevue/divider';
import Calendar from 'primevue/calendar';
import OverlayPanel from 'primevue/overlaypanel';
import PickList from 'primevue/picklist';
import ScrollPanel from 'primevue/scrollpanel';
import Avatar from 'primevue/avatar';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const activeTab = ref(0);
const displayAutomationDialog = ref(false);
const displayTemplateDialog = ref(false);
const displayPreviewDialog = ref(false);
const submitted = ref(false);

// --- DONNEES SIMULEES (Automation & Campaigns) ---
const automations = ref([]);
const templates = ref([]);
const logs = ref([]);

const triggerOptions = ref([
    { label: 'Nouvelle Inscription', value: 'on_signup', icon: 'pi pi-user-plus' },
    { label: 'Cours Terminé', value: 'course_completed', icon: 'pi pi-check-circle' },
    { label: 'Inactivité (7 jours)', value: 'inactivity_7d', icon: 'pi pi-clock' },
    { label: 'Obtention Certificat', value: 'certificate_earned', icon: 'pi pi-verified' },
    { label: 'Paiement Échoué', value: 'payment_failed', icon: 'pi pi-exclamation-triangle' }
]);

const currentAutomation = ref({
    id: null,
    name: '',
    trigger: 'on_signup',
    template_id: null,
    delay: 0,
    status: true,
    stats: { sent: 0, opened: 0, clicked: 0 }
});

const currentTemplate = ref({
    id: null,
    name: '',
    subject: '',
    content: '<h1>Bonjour {user_name},</h1><p>Bienvenue sur SubNetCongo !</p>',
    category: 'Transactionnel'
});

// --- CHARTS CONFIG ---
const emailStatsData = ref({
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
        {
            label: 'Emails Envoyés',
            data: [1200, 1500, 1100, 1800, 2200, 900, 800],
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        },
        {
            label: 'Ouvertures',
            data: [800, 1100, 750, 1300, 1600, 600, 550],
            borderColor: '#10B981',
            fill: false,
            tension: 0.4
        }
    ]
});

const deliveryChartData = ref({
    labels: ['Délivrés', 'Brebis (Bounce)', 'Spam'],
    datasets: [{
        data: [98.2, 1.5, 0.3],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
        hoverOffset: 10
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } }
    },
    scales: {
        x: { display: true, grid: { display: false } },
        y: { display: true, grid: { color: '#f1f5f9' } }
    }
};

// --- INITIALISATION DES DONNEES ---
const initData = () => {
    loading.value = true;
    setTimeout(() => {
        // Automatisations
        automations.value = [
            { id: 1, name: 'Bienvenue Élèves', trigger: 'on_signup', template: 'Welcome Email', delay: 0, status: true, stats: { sent: 4500, opened: 3800, clicked: 1200 } },
            { id: 2, name: 'Relance Inactivité', trigger: 'inactivity_7d', template: 'We miss you', delay: 24, status: true, stats: { sent: 1200, opened: 450, clicked: 80 } },
            { id: 3, name: 'Félicitations Certificat', trigger: 'certificate_earned', template: 'Diploma Issued', delay: 0, status: false, stats: { sent: 850, opened: 820, clicked: 300 } },
            { id: 4, name: 'Alerte Paiement', trigger: 'payment_failed', template: 'Billing Issue', delay: 0, status: true, stats: { sent: 120, opened: 115, clicked: 95 } }
        ];

        // Templates
        templates.value = [
            { id: 101, name: 'Welcome Email', subject: 'Bienvenue dans l\'aventure SubNetCongo !', category: 'Onboarding', last_mod: '2025-01-15' },
            { id: 102, name: 'Course Update', subject: 'Nouveau contenu disponible dans votre cours', category: 'Education', last_mod: '2025-02-01' },
            { id: 103, name: 'Monthly Newsletter', subject: 'Les actualités E-learning du mois de Février', category: 'Marketing', last_mod: '2025-02-05' },
            { id: 104, name: 'Password Reset', subject: 'Réinitialisation de votre mot de passe', category: 'System', last_mod: '2024-12-10' }
        ];

        // Logs
        const statuses = ['delivered', 'opened', 'clicked', 'bounced'];
        for (let i = 0; i < 25; i++) {
            logs.value.push({
                id: 5000 + i,
                recipient: `student_${i}@email.cd`,
                subject: 'Rappel de votre session live',
                sent_at: '2025-02-07 10:30',
                status: statuses[Math.floor(Math.random() * 4)],
                device: Math.random() > 0.5 ? 'Desktop' : 'Mobile'
            });
        }
        loading.value = false;
    }, 1500);
};

// --- ACTIONS ET LOGIQUE ---
const openNewAutomation = () => {
    currentAutomation.value = { id: null, name: '', trigger: 'on_signup', template_id: null, delay: 0, status: true };
    displayAutomationDialog.value = true;
};

const openNewTemplate = () => {
    currentTemplate.value = { id: null, name: '', subject: '', content: '', category: 'Marketing' };
    displayTemplateDialog.value = true;
};

const saveAutomation = () => {
    submitted.value = true;
    if (currentAutomation.value.name.trim()) {
        toast.add({ severity: 'success', summary: 'Automation Sauvegardée', detail: 'Le workflow a été mis à jour.', life: 3000 });
        displayAutomationDialog.value = false;
    }
};

const deleteAutomation = (id) => {
    automations.value = automations.value.filter(a => a.id !== id);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'L\'automatisation a été retirée.', life: 3000 });
};

const toggleStatus = (id) => {
    const auto = automations.value.find(a => a.id === id);
    if (auto) auto.status = !auto.status;
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'delivered': return 'info';
        case 'opened': return 'success';
        case 'clicked': return 'warning';
        case 'bounced': return 'danger';
        default: return 'secondary';
    }
};

const getTriggerIcon = (val) => {
    return triggerOptions.value.find(o => o.value === val)?.icon || 'pi pi-bolt';
};

const testEmail = () => {
    toast.add({ severity: 'info', summary: 'Envoi en cours', detail: 'Un email de test a été envoyé à votre adresse admin.', life: 3000 });
};

onMounted(() => {
    initData();
});
</script>

<template>
    <AppLayout>
        <Head title="Email Automation - SubNetCongo" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">
            
            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-5">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 rotate-2">
                        <i class="pi pi-envelope text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Email Marketing & Automation</h1>
                        <p class="text-slate-500 font-medium">Automatisez vos communications pour engager vos élèves</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Nouvelle Campagne" icon="pi pi-megaphone" class="p-button-outlined p-button-indigo border-2 font-bold" />
                    <Button label="Créer Automation" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" @click="openNewAutomation" />
                </div>
            </header>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Total Envoyés', val: '24,850', color: 'indigo', icon: 'pi-send', trend: '+14%' },
                    { label: 'Taux d\'Ouverture', val: '42.8%', color: 'emerald', icon: 'pi-eye', trend: '+5.2%' },
                    { label: 'Taux de Clic', val: '12.4%', color: 'amber', icon: 'pi-external-link', trend: '+2.1%' },
                    { label: 'Délivrabilité', val: '98.2%', color: 'rose', icon: 'pi-verified', trend: 'Stable' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex items-center gap-5 hover:shadow-md transition-all">
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-600` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-800">{{ kpi.val }}</span>
                            <span :class="`text-[10px] font-bold ${kpi.trend.includes('+') ? 'text-emerald-500' : 'text-slate-400'}` ">{{ kpi.trend }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS VISUALS -->
            <div class="grid grid-cols-12 gap-8 mb-10">
                <div class="col-span-12 lg:col-span-8">
                    <Card class="border-none shadow-sm rounded-3xl overflow-hidden h-full">
                        <template #title>
                            <div class="flex justify-between items-center px-4 pt-2">
                                <span class="text-xl font-black text-slate-800 tracking-tight">Volume & Engagement Hebdomadaire</span>
                                <SelectButton :options="['7J', '30J', '12M']" modelValue="7J" class="p-button-sm" />
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[320px]">
                                <Chart type="line" :data="emailStatsData" :options="chartOptions" class="h-full" />
                            </div>
                        </template>
                    </Card>
                </div>
                <div class="col-span-12 lg:col-span-4">
                    <Card class="border-none shadow-sm rounded-3xl h-full flex flex-col justify-center">
                        <template #title><span class="text-xl font-black text-slate-800 px-4">Statut de Délivrance</span></template>
                        <template #content>
                            <div class="h-[250px] relative">
                                <Chart type="doughnut" :data="deliveryChartData" :options="{ ...chartOptions, cutout: '75%' }" class="h-full" />
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-4xl font-black text-emerald-500">98.2%</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">SANTÉ ENVOI</span>
                                </div>
                            </div>
                            <div class="mt-6 space-y-3 px-4">
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-slate-500"><i class="pi pi-circle-fill text-emerald-500 mr-2 text-[8px]"></i> Succès</span>
                                    <span class="font-black text-slate-800">24,380 emails</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="font-bold text-slate-500"><i class="pi pi-circle-fill text-rose-500 mr-2 text-[8px]"></i> Rejetés</span>
                                    <span class="font-black text-slate-800">470 emails</span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- MAIN CONTENT TABS -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-50 p-6">
                <TabView v-model:activeIndex="activeTab" class="custom-tabview">
                    
                    <!-- TAB 1: AUTOMATIONS -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-bolt"></i>
                                <span>Workflows Automatisés</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="automations" class="p-datatable-sm" responsiveLayout="scroll">
                                <template #header>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-black text-slate-700">Flux de communication actifs</h3>
                                        <div class="p-input-icon-left">
                                            <i class="pi pi-search" />
                                            <InputText placeholder="Filtrer les automations..." class="p-inputtext-sm rounded-xl" />
                                        </div>
                                    </div>
                                </template>
                                <Column field="name" header="NOM DE L'AUTOMATION" style="min-width: 15rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col">
                                            <span class="font-black text-slate-800">{{ data.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ID: #AUTO-{{ data.id }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="trigger" header="DÉCLENCHEUR" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full w-fit">
                                            <i :class="getTriggerIcon(data.trigger)" class="text-xs"></i>
                                            <span class="text-[10px] font-black uppercase">{{ data.trigger.replace('_', ' ') }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="stats" header="PERFORMANCE" style="min-width: 18rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-4">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-bold text-slate-400">ENVOYÉS</span>
                                                <span class="font-black text-slate-700 text-sm">{{ data.stats.sent }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-bold text-slate-400">OUVERTURE</span>
                                                <span class="font-black text-emerald-500 text-sm">{{ ((data.stats.opened / data.stats.sent) * 100).toFixed(1) }}%</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-bold text-slate-400">CLICS</span>
                                                <span class="font-black text-amber-500 text-sm">{{ ((data.stats.clicked / data.stats.sent) * 100).toFixed(1) }}%</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="status" header="ÉTAT" style="width: 8rem">
                                    <template #body="{ data }">
                                        <InputSwitch v-model="data.status" @change="toggleStatus(data.id)" />
                                    </template>
                                </Column>
                                <Column header="ACTIONS" style="width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-pencil" class="p-button-text p-button-secondary p-button-sm p-button-rounded" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm p-button-rounded" @click="deleteAutomation(data.id)" />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: TEMPLATES -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-clone"></i>
                                <span>Bibliothèque de Modèles</span>
                            </div>
                        </template>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-8">
                            <div @click="openNewTemplate" class="border-2 border-dashed border-slate-200 rounded-3xl flex flex-col items-center justify-center p-8 hover:bg-slate-50 cursor-pointer transition-colors group">
                                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-indigo-100 group-hover:text-indigo-500 transition-all">
                                    <i class="pi pi-plus text-2xl"></i>
                                </div>
                                <span class="mt-4 font-black text-slate-500 group-hover:text-indigo-600">Nouveau Modèle</span>
                            </div>

                            <div v-for="tpl in templates" :key="tpl.id" class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden group hover:shadow-xl transition-all">
                                <div class="h-40 bg-slate-100 relative overflow-hidden flex items-center justify-center text-slate-300">
                                    <i class="pi pi-envelope text-6xl opacity-20"></i>
                                    <div class="absolute inset-0 bg-indigo-600/80 flex items-center justify-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Button icon="pi pi-eye" class="p-button-rounded p-button-sm p-button-white" @click="displayPreviewDialog = true" />
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-sm p-button-white" />
                                    </div>
                                </div>
                                <div class="p-5">
                                    <Tag :value="tpl.category" severity="info" class="text-[9px] mb-2 px-3 py-1" />
                                    <h4 class="font-black text-slate-800 text-sm truncate">{{ tpl.name }}</h4>
                                    <p class="text-xs text-slate-400 truncate mb-4">{{ tpl.subject }}</p>
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 pt-3 border-t border-slate-50">
                                        <span>MAJ: {{ tpl.last_mod }}</span>
                                        <Button icon="pi pi-copy" class="p-button-text p-button-sm p-button-secondary p-0" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 3: LOGS & TRACKING -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-list"></i>
                                <span>Journal des Envois</span>
                            </div>
                        </template>
                        <div class="pt-6">
                            <DataTable :value="logs" :paginator="true" :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
                                <Column field="recipient" header="DESTINATAIRE" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <Avatar icon="pi pi-user" shape="circle" size="small" class="bg-slate-100" />
                                            <span class="font-bold text-slate-700">{{ data.recipient }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="subject" header="OBJET" style="min-width: 18rem"></Column>
                                <Column field="sent_at" header="DATE D'ENVOI" sortable></Column>
                                <Column field="status" header="STATUT">
                                    <template #body="{ data }">
                                        <Badge :value="data.status" :severity="getStatusSeverity(data.status)" class="uppercase text-[9px] px-3 py-1" />
                                    </template>
                                </Column>
                                <Column field="device" header="CANAL">
                                    <template #body="{ data }">
                                        <i :class="data.device === 'Desktop' ? 'pi pi-desktop' : 'pi pi-mobile'" class="text-slate-400"></i>
                                    </template>
                                </Column>
                                <Column header="TRACK" style="width: 5rem">
                                    <template #body>
                                        <Button icon="pi pi-info-circle" class="p-button-text p-button-secondary p-button-sm" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </TabPanel>

                </TabView>
            </div>

            <!-- MODAL: CREATION AUTOMATION -->
            <Dialog v-model:visible="displayAutomationDialog" :modal="true" :style="{ width: '600px' }" header="⚙️ Configurer une Automation" class="p-fluid email-modal">
                <div class="space-y-6 pt-4">
                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Nom de l'automation</label>
                        <InputText v-model="currentAutomation.name" placeholder="ex: Relance de bienvenue J+1" class="rounded-xl border-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Déclencheur (Trigger)</label>
                            <Dropdown v-model="currentAutomation.trigger" :options="triggerOptions" optionLabel="label" optionValue="value" class="rounded-xl">
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <i :class="slotProps.option.icon"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="field">
                            <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Délai d'envoi (Heures)</label>
                            <InputNumber v-model="currentAutomation.delay" showButtons :min="0" suffix=" h" />
                        </div>
                    </div>

                    <div class="field">
                        <label class="font-black text-slate-700 text-sm mb-2 block uppercase tracking-wide">Modèle d'email à envoyer</label>
                        <Dropdown v-model="currentAutomation.template_id" :options="templates" optionLabel="name" optionValue="id" placeholder="Choisir un template..." class="rounded-xl" />
                    </div>

                    <div class="p-6 bg-indigo-50 rounded-3xl border border-indigo-100 flex items-center gap-4">
                        <i class="pi pi-info-circle text-2xl text-indigo-500"></i>
                        <p class="text-xs font-medium text-indigo-700 leading-relaxed">
                            Cette automation sera active immédiatement. Tous les futurs utilisateurs correspondant au déclencheur recevront l'email après le délai défini.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full pt-4 border-t border-slate-50">
                        <Button label="Envoyer Test" icon="pi pi-send" class="p-button-text p-button-secondary font-bold" @click="testEmail" />
                        <div class="flex gap-2">
                            <Button label="Annuler" class="p-button-text p-button-secondary" @click="displayAutomationDialog = false" />
                            <Button label="Enregistrer Automation" icon="pi pi-check" class="p-button-primary px-8 shadow-lg shadow-indigo-100" @click="saveAutomation" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: EDITEUR DE TEMPLATE -->
            <Dialog v-model:visible="displayTemplateDialog" :modal="true" :maximized="true" header="🎨 Éditeur de Modèle Email" class="p-fluid">
                <div class="grid grid-cols-12 gap-8 h-full py-4">
                    <!-- Config Sidebar -->
                    <div class="col-span-12 lg:col-span-3 space-y-6 bg-slate-50 p-6 rounded-[2rem] border border-slate-200">
                        <h3 class="text-lg font-black text-slate-800 mb-6">Paramètres Généraux</h3>
                        <div class="field">
                            <label class="font-bold text-slate-500 text-xs uppercase mb-2 block">Nom interne</label>
                            <InputText v-model="currentTemplate.name" class="border-none shadow-sm" />
                        </div>
                        <div class="field">
                            <label class="font-bold text-slate-500 text-xs uppercase mb-2 block">Objet de l'email</label>
                            <InputText v-model="currentTemplate.subject" placeholder="L'élève verra ceci..." class="border-none shadow-sm" />
                        </div>
                        <div class="field">
                            <label class="font-bold text-slate-500 text-xs uppercase mb-2 block">Catégorie</label>
                            <Dropdown v-model="currentTemplate.category" :options="['Marketing', 'Système', 'Education', 'Vente']" class="border-none shadow-sm" />
                        </div>

                        <Divider />

                        <div class="p-4 bg-white rounded-2xl shadow-sm space-y-4">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase">Variables dynamiques</h4>
                            <div class="flex flex-wrap gap-2">
                                <Tag v-for="tag in ['{user_name}', '{course_title}', '{reset_link}', '{cert_url}']" :key="tag" :value="tag" class="cursor-pointer hover:bg-indigo-500" />
                            </div>
                        </div>

                        <div class="mt-auto pt-10">
                            <Button label="Sauvegarder Modèle" icon="pi pi-save" class="p-button-indigo w-full shadow-lg" />
                        </div>
                    </div>

                    <!-- Editor Space -->
                    <div class="col-span-12 lg:col-span-9 flex flex-col gap-4">
                        <div class="flex-1 rounded-3xl overflow-hidden border border-slate-200">
                            <Editor v-model="currentTemplate.content" editorStyle="height: 60vh" />
                        </div>
                        <div class="bg-slate-900 p-6 rounded-3xl text-white flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-sm font-bold">Aperçu en temps réel activé</span>
                            </div>
                            <div class="flex gap-2">
                                <Button label="Prévisualiser" icon="pi pi-eye" class="p-button-text p-button-white p-button-sm" @click="displayPreviewDialog = true" />
                                <Button label="Code HTML" icon="pi pi-code" class="p-button-text p-button-white p-button-sm" />
                            </div>
                        </div>
                    </div>
                </div>
            </Dialog>

            <!-- MODAL: PREVIEW -->
            <Dialog v-model:visible="displayPreviewDialog" :modal="true" :style="{ width: '500px' }" header="Aperçu Mobile/Desktop" class="p-fluid">
                <div class="bg-slate-100 p-8 flex justify-center">
                    <div class="w-full bg-white shadow-2xl rounded-xl overflow-hidden max-w-[400px] border border-slate-200">
                        <div class="bg-slate-50 p-3 border-b border-slate-100 flex items-center gap-2">
                            <div class="flex gap-1">
                                <div class="w-2 h-2 rounded-full bg-rose-400"></div>
                                <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                            </div>
                            <span class="text-[8px] font-mono text-slate-400">https://subnetcongo.cd/email-preview</span>
                        </div>
                        <div class="p-6">
                            <div class="flex justify-center mb-6">
                                <img src="/logo-subnet.png" class="h-8 opacity-50" />
                            </div>
                            <div v-html="currentTemplate.content" class="text-slate-700 leading-relaxed text-sm"></div>
                            <Divider />
                            <div class="text-[10px] text-center text-slate-400">
                                © 2026 SubNetCongo. Vous recevez cet email car vous êtes inscrit sur notre plateforme.<br/>
                                <a href="#" class="underline">Se désabonner</a>
                            </div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button label="Fermer" @click="displayPreviewDialog = false" class="p-button-text p-button-secondary" />
                </template>
            </Dialog>

            <footer class="mt-12 text-center text-slate-400 text-sm pb-8 font-medium">
                © 2026 SubNetCongo CRM - Système d'Automatisation des Emails v2.4.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom Scrollbars */
:deep(.p-scrollpanel-bar) {
    background: #e2e8f0;
    opacity: 1;
}

/* TabView Customization */
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent;
    border-bottom: 2px solid #f1f5f9;
}

:deep(.custom-tabview .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    padding: 1.5rem 2rem;
    transition: all 0.3s ease;
}

:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1;
    border-bottom: 2px solid #6366f1;
}

:deep(.custom-tabview .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* DataTable Customization */
:deep(.p-datatable-header) {
    background: transparent;
    border: none;
    padding: 2rem 0;
}

:deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}

:deep(.p-datatable-tbody > tr) {
    background: white;
    transition: background 0.2s;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #f1f5f9/50 !important;
}

/* InputSwitch Customization */
:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background: #10B981;
}

/* Editor Styling */
:deep(.p-editor-container .p-editor-toolbar) {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
    background: #f8fafc;
}

:deep(.p-editor-container .p-editor-content) {
    border-bottom-left-radius: 1.5rem;
    border-bottom-right-radius: 1.5rem;
    background: white;
}

/* Card Overrides */
:deep(.p-card-body) {
    padding: 1.5rem;
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
</style>