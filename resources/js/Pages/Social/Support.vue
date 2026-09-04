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
import Avatar from 'primevue/avatar';
import Editor from 'primevue/editor';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import MultiSelect from 'primevue/multiselect';
import Divider from 'primevue/divider';
import ScrollPanel from 'primevue/scrollpanel';
import ProgressBar from 'primevue/progressbar';
import Rating from 'primevue/rating';
import FileUpload from 'primevue/fileupload';
import InlineMessage from 'primevue/inlinemessage';
import Menu from 'primevue/menu';

const { t } = useI18n();
const toast = useToast();

// --- ETATS DE GESTION ---
const loading = ref(true);
const tickets = ref([]);
const selectedTickets = ref(null);
const displayTicketDialog = ref(false);
const displayMacrosDialog = ref(false);
const currentTicket = ref(null);
const responseText = ref('');
const internalNote = ref('');
const activeTab = ref(0);

// --- CONFIGURATION OPTIONS ---
const priorities = ref([
    { label: 'Urgente', value: 'URGENT', color: 'rose' },
    { label: 'Haute', value: 'HIGH', color: 'orange' },
    { label: 'Moyenne', value: 'MEDIUM', color: 'indigo' },
    { label: 'Basse', value: 'LOW', color: 'slate' }
]);

const statuses = ref([
    { label: 'Nouveau', value: 'NEW', severity: 'info' },
    { label: 'En cours', value: 'OPEN', severity: 'warning' },
    { label: 'En attente', value: 'PENDING', severity: 'help' },
    { label: 'Résolu', value: 'RESOLVED', severity: 'success' },
    { label: 'Fermé', value: 'CLOSED', severity: 'secondary' }
]);

const categories = ref([
    { label: 'Accès aux cours', value: 'access' },
    { label: 'Paiement / Facturation', value: 'billing' },
    { label: 'Bug Technique', value: 'bug' },
    { label: 'Contenu pédagogique', value: 'content' },
    { label: 'Autre', value: 'other' }
]);

const agents = ref([
    { name: 'David Kimimba', id: 1, avatar: 'https://i.pravatar.cc/150?u=david' },
    { name: 'Sarah Mwamba', id: 2, avatar: 'https://i.pravatar.cc/150?u=sarah' },
    { name: 'Support Auto', id: 0, avatar: 'https://i.pravatar.cc/150?u=bot' }
]);

// --- FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
    priority: { value: null, matchMode: FilterMatchMode.EQUALS },
    category: { value: null, matchMode: FilterMatchMode.EQUALS }
});

// --- MOCK DATA GENERATION ---
const loadTickets = () => {
    loading.value = true;
    setTimeout(() => {
        const data = [];
        const subjects = [
            'Mon paiement M-Pesa n\'a pas débloqué le cours',
            'Erreur 500 lors de la lecture de la vidéo',
            'Demande de remboursement - Masterclass Laravel',
            'Impossible de télécharger mon certificat',
            'Question sur le chapitre 4 du cours UI/UX',
            'Lien de réinitialisation de mot de passe non reçu'
        ];

        for (let i = 1; i <= 40; i++) {
            const statusIdx = Math.floor(Math.random() * statuses.value.length);
            const priorityIdx = Math.floor(Math.random() * priorities.value.length);

            data.push({
                id: 1000 + i,
                ref: `TK-${2025}-${500 + i}`,
                user: {
                    name: `Étudiant ${i}`,
                    email: `student${i}@subnet.cd`,
                    avatar: `https://i.pravatar.cc/150?u=student${i}`,
                    is_premium: i % 5 === 0
                },
                subject: subjects[i % subjects.length],
                category: categories.value[i % categories.value.length].value,
                priority: priorities.value[priorityIdx].value,
                status: statuses.value[statusIdx].value,
                assigned_to: agents.value[i % agents.value.length],
                created_at: 'Il y a ' + (i % 24) + 'h',
                last_update: 'Il y a 10 min',
                messages: [
                    { id: 1, sender: 'student', text: 'Bonjour, j\'ai payé par M-Pesa mais le cours est toujours verrouillé.', date: '2025-02-07 09:00' },
                    { id: 2, sender: 'agent', text: 'Bonjour ! Pouvez-vous me transmettre la référence de transaction SMS ?', date: '2025-02-07 09:15' }
                ],
                satisfaction: i % 3 === 0 ? Math.floor(Math.random() * 2) + 4 : null
            });
        }
        tickets.value = data;
        loading.value = false;
    }, 1500);
};

// --- CHARTS CONFIG ---
const ticketAnalyticsData = ref({
    labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
    datasets: [
        {
            label: 'Tickets entrants',
            data: [12, 45, 30, 65, 80, 50, 20],
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }
    ]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } }
};

// --- ACTIONS ---
const viewTicket = (data) => {
    currentTicket.value = data;
    displayTicketDialog.value = true;
};

const getStatusLabel = (val) => statuses.value.find(s => s.value === val)?.label || val;
const getStatusSeverity = (val) => statuses.value.find(s => s.value === val)?.severity || 'info';

const getPriorityColor = (val) => priorities.value.find(p => p.value === val)?.color || 'slate';

const sendReply = () => {
    if (!responseText.value) return;
    currentTicket.value.messages.push({
        id: Date.now(),
        sender: 'agent',
        text: responseText.value,
        date: new Date().toLocaleString()
    });
    responseText.value = '';
    toast.add({ severity: 'success', summary: 'Envoyé', detail: 'Votre réponse a été transmise à l\'élève.', life: 3000 });
};

const resolveTicket = () => {
    currentTicket.value.status = 'RESOLVED';
    displayTicketDialog.value = false;
    toast.add({ severity: 'success', summary: 'Ticket Résolu', detail: 'Le ticket a été marqué comme terminé.', life: 3000 });
};

// --- CALCULS KPI ---
const openTicketsCount = computed(() => tickets.value.filter(t => t.status !== 'RESOLVED' && t.status !== 'CLOSED').length);
const avgResponseTime = ref('1h 15m');

onMounted(() => {
    loadTickets();
});

</script>

<template>
    <AppLayout>
        <Head title="Centre de Support - SubNetCongo" />
        <Toast />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 -rotate-3 transition-transform hover:rotate-0">
                        <i class="pi pi-headphones text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Assistance & Support</h1>
                        <p class="text-slate-500 font-medium italic">Gérez les demandes de vos élèves et maintenez un haut taux de satisfaction</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Réponses Types (Macros)" icon="pi pi-bolt" class="p-button-outlined p-button-indigo border-2 font-bold" @click="displayMacrosDialog = true" />
                    <Button label="Nouveau Ticket" icon="pi pi-plus" class="p-button-primary shadow-lg px-6 font-bold" />
                </div>
            </header>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div v-for="kpi in [
                    { label: 'Tickets Ouverts', val: openTicketsCount, icon: 'pi-envelope', color: 'indigo', trend: 'En baisse' },
                    { label: 'Temps de réponse', val: avgResponseTime, icon: 'pi-clock', color: 'amber', trend: '-10 min' },
                    { label: 'Satisfaction (CSAT)', val: '4.8/5', icon: 'pi-star-fill', color: 'emerald', trend: '+0.2' },
                    { label: 'Taux de Résolution', val: '94%', icon: 'pi-check-circle', color: 'rose', trend: 'Stable' }
                ]" :key="kpi.label" class="bg-white p-6 rounded-[1.5rem] border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-xl transition-all duration-300">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <div class="text-3xl font-black text-slate-800 tracking-tighter">{{ kpi.val }}</div>
                        <div class="mt-2 text-[10px] font-bold text-emerald-500 uppercase">{{ kpi.trend }}</div>
                    </div>
                    <div :class="`p-4 rounded-2xl bg-${kpi.color}-50 text-${kpi.color}-500 group-hover:scale-110 transition-transform` ">
                        <i :class="`pi ${kpi.icon} text-2xl` "></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">

                <!-- MAIN TICKETS TABLE -->
                <div class="col-span-12 lg:col-span-9">
                    <Card class="border-none shadow-sm rounded-[1.5rem] overflow-hidden">
                        <template #content>
                            <DataTable
                                v-model:filters="filters"
                                :value="tickets"
                                :paginator="true"
                                :rows="10"
                                dataKey="id"
                                :loading="loading"
                                stripedRows
                                filterDisplay="menu"
                                :globalFilterFields="['user.name', 'subject', 'ref']"
                                class="p-datatable-lg custom-support-table"
                            >
                                <template #header>
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tighter">File d'attente</h2>
                                            <Badge :value="tickets.length" severity="info" />
                                        </div>
                                        <div class="flex gap-3 w-full md:w-auto">
                                            <span class="p-input-icon-left flex-1 md:w-80">
                                                <i class="pi pi-search" />
                                                <InputText v-model="filters['global'].value" placeholder="Chercher par nom, sujet ou TK-..." class="w-full rounded-2xl border-slate-200" />
                                            </span>
                                            <Dropdown v-model="filters['status'].value" :options="statuses" optionLabel="label" optionValue="value" placeholder="Statut" class="w-40 rounded-2xl" />
                                        </div>
                                    </div>
                                </template>

                                <Column field="ref" header="RÉF" style="width: 8rem">
                                    <template #body="{ data }">
                                        <span class="font-mono font-bold text-slate-400 text-xs">{{ data.ref }}</span>
                                    </template>
                                </Column>

                                <Column field="user.name" header="ÉLÈVE" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <Avatar :image="data.user.avatar" shape="circle" size="medium" />
                                                <div v-if="data.user.is_premium" class="absolute -top-1 -right-1 bg-amber-400 w-3 h-3 rounded-full border-2 border-white" v-tooltip="'Élève Premium'"></div>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 text-sm leading-tight">{{ data.user.name }}</span>
                                                <span class="text-[10px] text-slate-400">{{ data.user.email }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="subject" header="SUJET DU TICKET" style="min-width: 20rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-black text-slate-700 text-sm hover:text-indigo-600 cursor-pointer transition-colors" @click="viewTicket(data)">{{ data.subject }}</span>
                                            <div class="flex items-center gap-2">
                                                <Tag :value="data.category" class="bg-slate-100 text-slate-500 uppercase font-black text-[8px] px-2" />
                                                <span class="text-[9px] text-slate-400 italic">Créé {{ data.created_at }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="priority" header="PRIORITÉ" style="width: 8rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-2">
                                            <div :class="`w-2 h-2 rounded-full bg-${getPriorityColor(data.priority)}-500 animate-pulse` "></div>
                                            <span :class="`text-[10px] font-black text-${getPriorityColor(data.priority)}-600 uppercase tracking-widest` ">{{ data.priority }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="status" header="STATUT" style="width: 10rem">
                                    <template #body="{ data }">
                                        <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" class="px-4 py-1 uppercase text-[10px] font-black" />
                                    </template>
                                </Column>

                                <Column header="ACTION" alignFrozen="right" frozen style="width: 5rem">
                                    <template #body="{ data }">
                                        <Button icon="pi pi-chevron-right" class="p-button-rounded p-button-text p-button-secondary" @click="viewTicket(data)" />
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </div>

                <!-- SIDEBAR ANALYTICS -->
                <div class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- LOAD CHART -->
                    <Card class="border-none shadow-sm rounded-3xl bg-slate-900 text-white overflow-hidden">
                        <template #title>
                            <div class="px-2 pt-2">
                                <span class="text-xs font-black uppercase tracking-widest text-indigo-400">Charge de Support</span>
                                <p class="text-xl font-black text-white">Activité en direct</p>
                            </div>
                        </template>
                        <template #content>
                            <div class="h-[180px] mt-4">
                                <Chart type="line" :data="ticketAnalyticsData" :options="chartOptions" class="h-full" />
                            </div>
                            <div class="mt-6 p-4 bg-slate-800 rounded-2xl flex items-center justify-between">
                                <div class="text-center">
                                    <p class="text-[9px] text-slate-400 uppercase font-bold">En ligne</p>
                                    <p class="text-lg font-black text-emerald-400">4 Agents</p>
                                </div>
                                <Divider layout="vertical" />
                                <div class="text-center">
                                    <p class="text-[9px] text-slate-400 uppercase font-bold">Attente moy.</p>
                                    <p class="text-lg font-black text-amber-400">12 min</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- AGENTS LIST -->
                    <Card class="border-none shadow-sm rounded-3xl">
                        <template #title><span class="text-base font-black text-slate-800 px-4">Équipe Support</span></template>
                        <template #content>
                            <div class="space-y-4 px-2">
                                <div v-for="agent in agents" :key="agent.id" class="flex items-center gap-4">
                                    <Avatar :image="agent.avatar" shape="circle" size="medium" />
                                    <div class="flex-1">
                                        <p class="text-sm font-black text-slate-800">{{ agent.name }}</p>
                                        <ProgressBar :value="Math.floor(Math.random() * 60) + 20" :showValue="false" style="height: 4px" class="mt-1" />
                                    </div>
                                    <Badge value="Active" severity="success" class="text-[8px]" />
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- QUICK TIPS -->
                    <div class="p-6 bg-indigo-50 rounded-[1rem] border border-indigo-100 space-y-4">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-lightbulb text-indigo-500 text-2xl"></i>
                            <h4 class="font-black text-indigo-900 text-lg">Conseils</h4>
                        </div>
                        <p class="text-xs text-indigo-700 font-medium leading-relaxed">
                            Les tickets <b>URGENTS</b> concernant les paiements sont traités en priorité haute par le système automatique de SubNetCongo.
                        </p>
                    </div>

                </div>
            </div>

            <!-- MODAL: TICKET DETAIL & MESSAGING -->
            <Dialog v-model:visible="displayTicketDialog" :modal="true" :style="{ width: '950px' }" header="Gestion du Ticket d'Assistance" class="p-fluid support-modal" v-if="currentTicket">
                <div class="grid grid-cols-12 gap-8 pt-4 h-[70vh]">

                    <!-- Sidebar: Ticket Info -->
                    <div class="col-span-12 lg:col-span-4 bg-slate-50 p-6 rounded-[1rem] border border-slate-100 overflow-y-auto">
                        <div class="text-center mb-8">
                            <Avatar :image="currentTicket.user.avatar" size="xlarge" shape="circle" class="border-4 border-white shadow-xl mb-4" />
                            <h3 class="text-xl font-black text-slate-800">{{ currentTicket.user.name }}</h3>
                            <p class="text-slate-500 text-sm font-medium">{{ currentTicket.user.email }}</p>
                            <Tag v-if="currentTicket.user.is_premium" value="ÉLÈVE PREMIUM" severity="warning" class="mt-2 px-4 py-1 rounded-full text-[9px]" />
                        </div>

                        <Divider />

                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Détails Techniques</label>
                                <div class="bg-white p-4 rounded-2xl shadow-sm space-y-3">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-400">Navigateur</span>
                                        <span class="text-slate-700">Chrome 121 (Windows)</span>
                                    </div>
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-400">ID Cours</span>
                                        <span class="text-indigo-600 underline">#LMS-2849</span>
                                    </div>
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-400">ID Commande</span>
                                        <span class="text-slate-700">#ORD-99203</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Configuration du Ticket</label>
                                <div class="space-y-4">
                                    <div class="field">
                                        <label class="text-xs font-bold text-slate-600">Assigner à</label>
                                        <Dropdown v-model="currentTicket.assigned_to" :options="agents" optionLabel="name" class="rounded-xl border-none shadow-sm" />
                                    </div>
                                    <div class="field">
                                        <label class="text-xs font-bold text-slate-600">Priorité</label>
                                        <Dropdown v-model="currentTicket.priority" :options="priorities" optionLabel="label" optionValue="value" class="rounded-xl border-none shadow-sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main: Messaging Center -->
                    <div class="col-span-12 lg:col-span-8 flex flex-col h-full overflow-hidden">
                        <TabView v-model:activeIndex="activeTab" class="custom-support-tabs flex-1 flex flex-col overflow-hidden">
                            <TabPanel>
                                <template #header><i class="pi pi-comments mr-2"></i> Conversation</template>
                                <ScrollPanel style="width: 100%; height: 350px" class="pr-3">
                                    <div class="space-y-6 py-4">
                                        <div v-for="msg in currentTicket.messages" :key="msg.id" :class="`flex flex-col ${msg.sender === 'agent' ? 'items-end' : 'items-start'}` ">
                                            <div :class="`max-w-[80%] p-4 rounded-3xl shadow-sm text-sm ${msg.sender === 'agent' ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-slate-100 text-slate-700 rounded-tl-none'}` ">
                                                {{ msg.text }}
                                            </div>
                                            <span class="text-[9px] font-bold text-slate-400 mt-2 uppercase tracking-tighter">{{ msg.sender === 'agent' ? 'Vous' : currentTicket.user.name }} • {{ msg.date }}</span>
                                        </div>
                                    </div>
                                </ScrollPanel>

                                <Divider />

                                <div class="bg-white p-4 rounded-3xl border-2 border-slate-100">
                                    <Editor v-model="responseText" editorStyle="height: 100px" placeholder="Écrire une réponse à l'élève..." class="border-none" />
                                    <div class="flex justify-between items-center mt-3">
                                        <div class="flex gap-2">
                                            <Button icon="pi pi-paperclip" class="p-button-text p-button-secondary p-button-sm" />
                                            <Button icon="pi pi-bolt" label="Macros" class="p-button-text p-button-indigo p-button-sm font-black" @click="displayMacrosDialog = true" />
                                        </div>
                                        <Button label="Envoyer la réponse" icon="pi pi-send" class="p-button-primary px-8 shadow-lg shadow-indigo-100 font-black rounded-xl" @click="sendReply" />
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel>
                                <template #header><i class="pi pi-lock mr-2"></i> Notes Internes</template>
                                <div class="py-4 space-y-6">
                                    <InlineMessage severity="warn" class="w-full">Les notes internes ne sont visibles que par les agents de SubNetCongo.</InlineMessage>
                                    <Editor v-model="internalNote" editorStyle="height: 200px" placeholder="Ajouter une note pour vos collègues..." />
                                    <Button label="Enregistrer la note" class="p-button-warning w-full font-black rounded-xl" />

                                    <div class="space-y-4">
                                        <div class="p-4 bg-slate-50 rounded-2xl border-l-4 border-amber-400">
                                            <p class="text-xs font-bold text-slate-700">David: J'ai vérifié sur Stripe, le paiement est bien passé. Sûrement un délai de synchro.</p>
                                            <p class="text-[10px] text-slate-400 mt-1 uppercase">Il y a 2h</p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel>
                                <template #header><i class="pi pi-history mr-2"></i> Activité</template>
                                <div class="py-4 space-y-4">
                                    <div v-for="i in 5" :key="i" class="flex gap-4 items-start border-l-2 border-slate-100 pl-4 relative">
                                        <div class="absolute -left-1.5 top-2 w-3 h-3 rounded-full bg-slate-200"></div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Aujourd'hui, 14:20</p>
                                            <p class="text-sm font-bold text-slate-700">Ticket assigné à Sarah Mwamba par Admin</p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-between items-center w-full border-t border-slate-100 pt-6 px-2">
                        <Button label="Supprimer le Ticket" icon="pi pi-trash" class="p-button-text p-button-danger font-bold" />
                        <div class="flex gap-2">
                            <Button label="Mettre en Attente" class="p-button-text p-button-secondary font-bold" @click="displayTicketDialog = false" />
                            <Button label="Marquer comme Résolu" icon="pi pi-check-circle" class="p-button-success px-8 shadow-lg shadow-emerald-100 font-black rounded-xl" @click="resolveTicket" />
                        </div>
                    </div>
                </template>
            </Dialog>

            <!-- MODAL: MACROS (Quick Replies) -->
            <Dialog v-model:visible="displayMacrosDialog" :modal="true" :style="{ width: '500px' }" header="⚡ Réponses Préenregistrées" class="p-fluid">
                <div class="space-y-4 pt-4">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText placeholder="Rechercher une macro..." class="rounded-xl" />
                    </span>
                    <div class="max-h-[350px] overflow-y-auto pr-2 space-y-2">
                        <div v-for="macro in [
                            { title: 'Validation Paiement', text: 'Bonjour, votre paiement a bien été validé...' },
                            { title: 'Demande de Remboursement', text: 'Nous avons bien reçu votre demande de remboursement...' },
                            { title: 'Problème Technique Vidéo', text: 'Veuillez vider le cache de votre navigateur...' }
                        ]" :key="macro.title" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 hover:border-indigo-400 cursor-pointer transition-colors group" @click="responseText = macro.text; displayMacrosDialog = false">
                            <div class="flex justify-between items-center">
                                <span class="font-black text-slate-800 text-sm tracking-tight">{{ macro.title }}</span>
                                <i class="pi pi-chevron-right text-slate-300 group-hover:text-indigo-500 transition-colors"></i>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 truncate">{{ macro.text }}</p>
                        </div>
                    </div>
                    <Button label="Gérer les macros" icon="pi pi-cog" class="p-button-outlined p-button-indigo w-full border-2 border-dashed font-black" />
                </div>
            </Dialog>

            <footer class="mt-16 text-center text-slate-400 text-sm pb-10 font-medium">
                © 2026 SubNetCongo Customer Success Module v3.4.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PrimeVue Overrides pour design SubNetCongo Support */
:deep(.p-card) {
    background: white;
}

:deep(.p-datatable.custom-support-table) {
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
    cursor: pointer;
}

:deep(.p-datatable-tbody > tr:hover) {
    background: #F8FAFC !important;
}

:deep(.p-tabview-nav) {
    border-bottom: 2px solid #F1F5F9;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #94A3B8;
    padding: 1.25rem 1.5rem;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1 !important;
    border-bottom: 2px solid #6366F1 !important;
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
}

:deep(.p-tabpanel) {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Modal styling */
:deep(.p-dialog-header) {
    background: white;
    padding: 2rem;
    border-bottom: 1px solid #F1F5F9;
}

:deep(.p-dialog-content) {
    padding: 0 2rem 1.5rem 2rem;
}

/* Editor Styling */
:deep(.p-editor-container .p-editor-toolbar) {
    border: none;
    background: #f8fafc;
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
}

:deep(.p-editor-container .p-editor-content) {
    border: none;
    font-size: 0.85rem;
}

/* Colors Priority */
.bg-rose-500 { background-color: #f43f5e; }
.text-rose-600 { color: #e11d48; }
.bg-orange-500 { background-color: #f97316; }
.text-orange-600 { color: #ea580c; }
.bg-indigo-500 { background-color: #6366f1; }
.text-indigo-600 { color: #4f46e5; }

/* Custom ScrollPanel */
:deep(.p-scrollpanel-bar) {
    background: #cbd5e1;
    opacity: 1;
}

/* Animations */
.group:hover .group-hover\:scale-110 {
    transform: scale(1.1);
}
</style>
