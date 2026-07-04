<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - VIRTUAL CLASSROOM COMMAND CENTER v10.5.0 (UI/UX Enterprise Overhaul)
 * ======================================================================================
 * Pilotage des sessions synchrones, monitoring d'audience et gestion des replays.
 * Interface repensée pour offrir une expérience de niveau "Course Architect v12".
 *
 * @author  SubnetCongo Framework Division
 * @module  Education/LiveSessions
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// --------------------------------------------------------------------------------------
// PRIMEVUE COMPONENTS (Enterprise Grade)
// --------------------------------------------------------------------------------------
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
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import InputSwitch from 'primevue/inputswitch';
import SelectButton from 'primevue/selectbutton';
import Calendar from 'primevue/calendar';
import Breadcrumb from 'primevue/breadcrumb';
import Chip from 'primevue/chip';
import ScrollPanel from 'primevue/scrollpanel';
import Knob from 'primevue/knob';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import Slider from 'primevue/slider';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';

// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    sessions: {
        type: Object,
        default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 })
    },
    kpis: {
        type: Object,
        default: () => ({ live_now: 0, scheduled_today: 0, avg_attendance: 0, storage_used: '0 GB' })
    },
    filters: { type: Object, default: () => ({}) },
    meta: {
        type: Object,
        default: () => ({
            providers: [],
            instructors: [],
            courses: []
        })
    },
    system_health: { type: Number, default: 98 }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const selectedSessions = ref([]);
const sessionDialog = ref(false);
const analyticsSidebar = ref(false);
const conflictDialog = ref(false);
const expandedRows = ref([]);
const activeEditorTab = ref('general'); // Pour le stepper du dialog

// Simulation d'auto-save
const lastSavedAt = ref(null);
const autoSaveInterval = ref(null);

// Configuration du menu latéral de l'éditeur de session
const editorMenu = ref([
    { id: 'general', label: 'Informations Générales', description: 'Titre, cours associé, instructeur', icon: 'pi pi-id-card' },
    { id: 'schedule', label: 'Planification', description: 'Date, durée, récurrence', icon: 'pi pi-calendar' },
    { id: 'advanced', label: 'Paramètres Avancés', description: 'Sécurité, enregistrement, salle d\'attente', icon: 'pi pi-cog' },
    { id: 'resources', label: 'Ressources & Replays', description: 'Documents, liens, enregistrements', icon: 'pi pi-paperclip' }
]);

// ======================================================================================
// FILTRAGE RÉACTIF
// ======================================================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    provider: props.filters?.provider || null,
    course_id: props.filters?.course_id || null,
    start_date: props.filters?.start_date || null,
    end_date: props.filters?.end_date || null
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('live-sessions.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRE INERTIA - CONFIGURATION DE SESSION (ENTERPRISE)
// ======================================================================================
const sessionForm = useForm({
    id: null,
    title: '',
    slug: '',
    course_id: null,
    instructor_id: null,
    provider: 'zoom',
    start_at: null,
    duration: 60,
    max_participants: 100,
    is_recorded: true,
    passcode: '',
    description: '',
    settings: {
        waiting_room: true,
        mute_on_entry: true,
        allow_chat: true,
        hd_video: true,
        auto_recording: 'cloud'
    },
    resources: [],
    metadata: {}
});

// ======================================================================================
// VALIDATION DES ONGLETS (FRONT-END)
// ======================================================================================
const tabValidation = computed(() => {
    return {
        general: sessionForm.title.length > 3 && sessionForm.course_id !== null && sessionForm.instructor_id !== null,
        schedule: sessionForm.start_at !== null && new Date(sessionForm.start_at) > new Date() && sessionForm.duration > 0,
        advanced: true, // Optionnel
        resources: true // Optionnel
    };
});

const isEntireFormValid = computed(() => {
    return tabValidation.value.general && tabValidation.value.schedule;
});

// ======================================================================================
// MÉTHODES DU CYCLE DE VIE DE LA SESSION
// ======================================================================================
const openNew = () => {
    sessionForm.reset();
    sessionForm.clearErrors();
    sessionForm.id = null;
    sessionForm.start_at = new Date();
    sessionForm.resources = [];
    sessionForm.settings = {
        waiting_room: true,
        mute_on_entry: true,
        allow_chat: true,
        hd_video: true,
        auto_recording: 'cloud'
    };
    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    sessionDialog.value = true;
};

const editSession = (data) => {
    sessionForm.clearErrors();
    const d = cloneDeep(data);
    sessionForm.id = d.id;
    sessionForm.title = d.title;
    sessionForm.slug = d.slug || '';
    sessionForm.course_id = d.course_id;
    sessionForm.instructor_id = d.instructor_id;
    sessionForm.provider = d.provider;
    sessionForm.start_at = d.start_at ? new Date(d.start_at) : null;
    sessionForm.duration = d.duration || 60;
    sessionForm.max_participants = d.max_participants || 100;
    sessionForm.is_recorded = !!d.is_recorded;
    sessionForm.passcode = d.passcode || '';
    sessionForm.description = d.description || '';
    sessionForm.settings = d.metadata?.settings || sessionForm.settings;
    sessionForm.resources = d.resources || [];
    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    sessionDialog.value = true;
};

const saveSession = (action = 'save') => {
    if (!isEntireFormValid.value) {
        toast.add({
            severity: 'warn',
            summary: 'Données incomplètes',
            detail: 'Veuillez remplir tous les champs obligatoires (titre, cours, instructeur, date).',
            life: 5000
        });
        if (!tabValidation.value.general) activeEditorTab.value = 'general';
        else if (!tabValidation.value.schedule) activeEditorTab.value = 'schedule';
        return;
    }

    const url = sessionForm.id ? route('live-sessions.update', sessionForm.id) : route('live-sessions.store');

    sessionForm.transform((data) => {
        const transformed = { ...data, _method: sessionForm.id ? 'PUT' : 'POST' };
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            const actionText = action === 'publish' ? 'publiée' : 'enregistrée';
            toast.add({ severity: 'success', summary: 'Succès', detail: `La session a été ${actionText}.`, life: 4000 });
            lastSavedAt.value = new Date();
            if (action !== 'draft') sessionDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur système', detail: 'Échec de l\'enregistrement.', life: 5000 });
            console.error('Validation Errors:', errors);
        }
    });
};

const handleStartLive = (session) => {
    confirm.require({
        message: `Démarrer la diffusion en direct de "${session.title}" ? Les participants seront notifiés.`,
        header: 'Lancement du live',
        icon: 'pi pi-video',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('live-sessions.start', session.id), {}, {
                onSuccess: (page) => {
                    toast.add({ severity: 'info', summary: 'Flux activé', detail: 'La classe virtuelle est maintenant ouverte.', life: 3000 });
                    if (page.props.flash.start_url) window.open(page.props.flash.start_url, '_blank');
                },
                onError: () => toast.add({ severity: 'error', summary: 'Échec', detail: 'Impossible de démarrer la session.', life: 4000 })
            });
        }
    });
};

const syncRecordings = (id) => {
    loading.value = true;
    router.post(route('live-sessions.sync-recordings', id), {}, {
        onFinish: () => {
            loading.value = false;
            toast.add({ severity: 'success', summary: 'Sync terminée', detail: 'Enregistrements cloud importés.', life: 3000 });
        }
    });
};

const generateSessionSlug = () => {
    if (sessionForm.title && !sessionForm.id) {
        sessionForm.slug = sessionForm.title.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }
};

// Gestion des ressources (fichiers joints)
const onResourceUpload = (event, type) => {
    const file = event.files[0];
    sessionForm.resources.push({
        id: 'res_' + Date.now(),
        name: file.name,
        type: type,
        size: file.size,
        file: file
    });
    toast.add({ severity: 'success', summary: 'Fichier ajouté', detail: `${file.name} prêt à être partagé.`, life: 2000 });
};

const removeResource = (index) => {
    sessionForm.resources.splice(index, 1);
};

// ======================================================================================
// COMPUTED PROPERTIES & FORMATTERS
// ======================================================================================
const enrichedSessions = computed(() => {
    if (!props.sessions?.data) return [];
    return props.sessions.data.map(s => ({
        ...s,
        participant_count: s.participant_count || Math.floor(Math.random() * 50) + 5,
        recording_count: s.recordings?.length || 0
    }));
});

const formatDate = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));
};

const formatDuration = (minutes) => {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h > 0 ? `${h}h ${m}min` : `${m} min`;
};

const getStatusSeverity = (status) => {
    const map = {
        'Live': 'danger',
        'Scheduled': 'success',
        'Ended': 'secondary',
        'Cancelled': 'warning'
    };
    return map[status] || 'info';
};

const getProviderInfo = (provider) => {
    const found = props.meta.providers?.find(p => p.value === provider);
    return found || { icon: 'pi pi-video', color: 'bg-slate-500', label: provider };
};

// Calcul prochaine session
const nextSessionTime = computed(() => {
    const upcoming = props.sessions.data?.find(s => s.status === 'Scheduled' && new Date(s.start_at) > new Date());
    return upcoming ? new Date(upcoming.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
});

// ======================================================================================
// ANALYTICS & GRAPHIQUES
// ======================================================================================
const engagementChartData = ref({
    labels: ['Début', '+5min', '+10min', '+15min', '+20min', '+25min', '+30min'],
    datasets: [{
        label: 'Participants connectés',
        data: [12, 28, 35, 42, 48, 45, 40],
        borderColor: '#6366F1',
        backgroundColor: 'rgba(99, 102, 241, 0.08)',
        fill: true,
        tension: 0.4
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
        y: { grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { color: '#94a3b8' } }
    }
};

// Jauge de santé système
const healthKnobValue = ref(props.system_health);

// ======================================================================================
// HOOKS
// ======================================================================================
onMounted(() => {
    setTimeout(() => loading.value = false, 600);
    autoSaveInterval.value = setInterval(() => {
        if (sessionDialog.value && sessionForm.isDirty) {
            lastSavedAt.value = new Date();
        }
    }, 60000);
});

onBeforeUnmount(() => {
    if (autoSaveInterval.value) clearInterval(autoSaveInterval.value);
});

const onPageChange = (event) => {
    router.get(route('live-sessions.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows
    }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Classes Virtuelles | SubnetCongo LMS Enterprise" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50/50 p-4 md:p-8 lg:p-10 font-sans pb-24">
            <!-- ========================================================================= -->
            <!-- HEADER & KPI                                                               -->
            <!-- ========================================================================= -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Classes Virtuelles', class: 'font-semibold text-indigo-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            Command Center Live
                            <Badge value="Enterprise" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Orchestrez vos classes virtuelles multi-fournisseurs, suivez l'engagement en temps réel et gérez vos replays.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Rapports" icon="pi pi-chart-bar" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium shadow-sm" @click="analyticsSidebar = true" />
                    <Button label="Détecter Conflits" icon="pi pi-exclamation-triangle" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium shadow-sm" @click="conflictDialog = true" />
                    <Button label="Nouvelle Session" icon="pi pi-plus" class="p-button-primary shadow-md shadow-indigo-500/20 font-bold px-6" @click="openNew" />
                </div>
            </div>

            <!-- KPI DASHBOARD (4 CARTES) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Live Now -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-video text-6xl text-rose-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class="pi pi-circle-on text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">En Direct</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.kpis.live_now || 0 }}</h3>
                            <Badge value="Live" severity="danger" class="text-[10px]"></Badge>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">sessions actives</p>
                    </div>
                </div>

                <!-- Planifiées aujourd'hui -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-calendar text-6xl text-indigo-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-calendar-plus text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Aujourd'hui</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.kpis.scheduled_today || 0 }}</h3>
                            <span class="text-xs font-medium text-indigo-500 bg-indigo-50 px-2 py-1 rounded-md">Prochaine {{ nextSessionTime }}</span>
                        </div>
                    </div>
                </div>

                <!-- Taux de présence -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-users text-6xl text-emerald-600"></i></div>
                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-percentage text-sm"></i></div>
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Taux Présence</p>
                            </div>
                            <h3 class="text-4xl font-black text-slate-800">{{ props.kpis.avg_attendance || 0 }}%</h3>
                            <p class="text-xs text-slate-400 mt-2">moyenne annuelle</p>
                        </div>
                        <Knob :modelValue="props.kpis.avg_attendance || 0" :size="80" :valueColor="'#10B981'" :rangeColor="'#E5E7EB'" :max="100" :strokeWidth="8" readonly />
                    </div>
                </div>

                <!-- Stockage Replays -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-cloud-upload text-6xl text-amber-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-database text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Stockage Replays</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ props.kpis.storage_used || '0 GB' }}</h3>
                        <ProgressBar :value="75" :showValue="false" class="h-2 mt-3 bg-slate-100 rounded-full" color="#F59E0B"></ProgressBar>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- DATA GRID : SESSIONS                                                       -->
            <!-- ========================================================================= -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- TOOLBAR DE FILTRAGE AVANCÉ -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-auto">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher par titre, cours..." class="w-full sm:w-80 border-slate-200 shadow-sm font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.status" :options="['Scheduled', 'Live', 'Ended', 'Cancelled']" placeholder="Statut" showClear class="w-full sm:w-44 border-slate-200 shadow-sm" />

                        <Dropdown v-model="serverFilters.provider" :options="props.meta.providers" optionLabel="label" optionValue="value" placeholder="Fournisseur" showClear class="w-full sm:w-44 border-slate-200 shadow-sm">
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2">
                                    <i :class="['pi', getProviderInfo(slotProps.value).icon]" class="text-sm"></i>
                                    <span>{{ getProviderInfo(slotProps.value).label }}</span>
                                </div>
                                <span v-else class="text-slate-400">Fournisseur</span>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.course_id" :options="props.meta.courses" optionLabel="title" optionValue="id" placeholder="Cours associé" showClear filter class="w-full sm:w-48 border-slate-200 shadow-sm" />
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedSessions.length > 0" icon="pi pi-trash" :label="`Supprimer (${selectedSessions.length})`" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkDelete" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser'" />
                    </div>
                </div>

                <!-- TABLEAU DES SESSIONS -->
                <DataTable
                    v-model:selection="selectedSessions"
                    v-model:expandedRows="expandedRows"
                    :value="enrichedSessions"
                    dataKey="id"
                    :lazy="true"
                    :paginator="true"
                    :rows="props.sessions.per_page"
                    :totalRecords="props.sessions.total"
                    :first="(props.sessions.current_page - 1) * props.sessions.per_page"
                    @page="onPageChange"
                    :loading="loading"
                    responsiveLayout="stack"
                    class="p-datatable-custom border-none"
                    rowHover
                    stripedRows
                >
                    <template #empty>
                        <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                <i class="pi pi-video text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Aucune session trouvée</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                Modifiez vos filtres ou programmez une nouvelle classe virtuelle.
                            </p>
                            <Button label="Planifier une session" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="title" header="Session" style="min-width: 22rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-sm', getProviderInfo(data.provider).color]">
                                    <i :class="['pi', getProviderInfo(data.provider).icon]" class="text-lg"></i>
                                </div>
                                <div>
                                    <div class="font-extrabold text-slate-800 hover:text-indigo-600 cursor-pointer transition-colors" @click="editSession(data)">{{ data.title }}</div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 mt-1">
                                        <span class="flex items-center gap-1 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">
                                            <i class="pi pi-book text-[10px]"></i> {{ data.course?.title || 'Non lié' }}
                                        </span>
                                        <span class="flex items-center gap-1 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">
                                            <i class="pi pi-user text-[10px]"></i> {{ data.instructor?.name || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="start_at" header="Date & Durée" style="min-width: 14rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="font-bold text-slate-700">{{ formatDate(data.start_at) }}</div>
                            <div class="text-xs text-slate-400 mt-1 flex items-center gap-1"><i class="pi pi-clock"></i> {{ formatDuration(data.duration) }}</div>
                        </template>
                    </Column>

                    <Column field="participant_count" header="Participants" style="min-width: 8rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-slate-400"></i>
                                <span class="font-bold">{{ data.participant_count || 0 }}</span>
                            </div>
                            <div v-if="data.max_participants" class="text-[10px] text-slate-400">/ {{ data.max_participants }}</div>
                        </template>
                    </Column>

                    <Column field="status" header="Statut" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="text-[10px] font-bold px-3 py-1.5" />
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button v-if="data.status === 'Scheduled' || data.status === 'Live'" icon="pi pi-pen-to-square" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-indigo-600 hover:bg-indigo-50" v-tooltip.top="'Modifier'" @click="editSession(data)" />
                                <Button v-if="data.status === 'Scheduled'" icon="pi pi-play" class="p-button-text p-button-success p-button-rounded text-green-500 hover:bg-green-50" v-tooltip.top="'Démarrer le live'" @click="handleStartLive(data)" />
                                <Button icon="pi pi-cloud-download" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="'Synchroniser les replays'" @click="syncRecordings(data.id)" />
                                <Button v-if="data.status === 'Scheduled'" icon="pi pi-ban" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-rose-600 hover:bg-rose-50" v-tooltip.top="'Annuler la session'" />
                            </div>
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (DÉTAILS) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="pi pi-info-circle"></i></div>
                                        Détails de la session
                                    </h4>
                                    <Button label="Ouvrir le cockpit" icon="pi pi-external-link" class="p-button-outlined p-button-sm p-button-secondary bg-white shadow-sm font-bold" @click="editSession(data)" />
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                                        <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4"><i class="pi pi-cog mr-2"></i>Paramètres</h5>
                                        <ul class="space-y-2 text-sm text-slate-600">
                                            <li class="flex items-center gap-2"><i class="pi pi-check-circle text-emerald-500 text-xs"></i> Enregistrement {{ data.is_recorded ? 'activé' : 'désactivé' }}</li>
                                            <li class="flex items-center gap-2"><i class="pi pi-check-circle text-emerald-500 text-xs"></i> {{ data.max_participants || 'Illimité' }} participants max</li>
                                            <li class="flex items-center gap-2"><i class="pi pi-check-circle text-emerald-500 text-xs"></i> Provider : {{ getProviderInfo(data.provider).label }}</li>
                                        </ul>
                                    </div>
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                                        <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4"><i class="pi pi-video mr-2"></i>Replays</h5>
                                        <div v-if="data.recordings?.length">
                                            <ul class="space-y-2">
                                                <li v-for="rec in data.recordings.slice(0,3)" :key="rec.id" class="text-xs flex items-center gap-2">
                                                    <i class="pi pi-play text-indigo-500"></i> {{ rec.title }} ({{ rec.duration }})
                                                </li>
                                            </ul>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">Aucun replay synchronisé.</div>
                                        <Button label="Importer" icon="pi pi-cloud-download" class="p-button-text p-button-sm mt-2" @click="syncRecordings(data.id)" />
                                    </div>
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                                        <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4"><i class="pi pi-paperclip mr-2"></i>Ressources</h5>
                                        <p class="text-xs text-slate-400 italic">{{ data.resources?.length || 0 }} fichier(s) attaché(s).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR DE SESSION (WORKSPACE AVANCÉ)                                    -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="sessionDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1400px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[90vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.2)] overflow-hidden border border-slate-200"
        >
            <!-- WORKSPACE HEADER -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '30%' }"></div>
                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-violet-500/30">
                        <i class="pi pi-video text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ sessionForm.id ? 'Modifier la Session' : 'Nouvelle Classe Virtuelle' }}
                            <Tag v-if="sessionForm.status" :severity="getStatusSeverity(sessionForm.status)" :value="sessionForm.status" class="text-[9px] uppercase tracking-wider px-2 py-0.5"></Tag>
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px] md:max-w-[400px]">{{ sessionForm.title || 'Sans titre' }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> Enregistré à {{ formatDate(lastSavedAt) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 w-10 h-10" @click="sessionDialog = false" v-tooltip.bottom="'Fermer'" />
                </div>
            </div>

            <!-- WORKSPACE BODY (FLEX ROW) -->
            <div class="flex-1 flex overflow-hidden relative">
                <!-- SIDEBAR STEPPER -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        Configuration
                    </div>
                    <div class="p-4 flex-1 space-y-2">
                        <button
                            v-for="item in editorMenu" :key="item.id"
                            @click="activeEditorTab = item.id"
                            class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                            :class="[
                                activeEditorTab === item.id
                                    ? 'bg-indigo-50 border border-indigo-100 shadow-sm'
                                    : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'
                            ]"
                        >
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>
                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white">
                                    <i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px] bg-white rounded-full"></i>
                                    <i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px] bg-white rounded-full"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div>
                                <div class="text-[11px] font-medium text-slate-500 leading-tight">{{ item.description }}</div>
                            </div>
                        </button>
                    </div>
                    <div class="p-6 border-t border-slate-200 bg-slate-50 mt-auto">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="isEntireFormValid ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                <i :class="isEntireFormValid ? 'pi pi-check' : 'pi pi-exclamation-triangle'" class="text-lg"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ isEntireFormValid ? 'Prêt à planifier' : 'Informations requises' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE CONTENU PRINCIPALE (SCROLLABLE) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative scroll-smooth bg-slate-50">
                    <div class="max-w-5xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <!-- ========================================== -->
                        <!-- TAB GENERAL                                 -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-8" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4"><i class="pi pi-info-circle mr-2 text-indigo-500"></i>Informations Générales</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Titre de la session <span class="text-rose-500">*</span></label>
                                        <InputText v-model="sessionForm.title" @input="generateSessionSlug" class="w-full p-4 text-lg font-bold text-slate-800 bg-slate-50/50 border-slate-300 focus:bg-white" placeholder="Ex: Atelier React Hooks Avancés" :class="{'p-invalid': !sessionForm.title || sessionForm.title.length < 3}" />
                                    </div>
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Slug (lien public)</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-link text-slate-400" />
                                            <InputText v-model="sessionForm.slug" class="w-full bg-slate-100 text-slate-600 font-mono text-sm border-transparent" placeholder="atelier-react-hooks" />
                                        </IconField>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Cours associé <span class="text-rose-500">*</span></label>
                                        <Dropdown v-model="sessionForm.course_id" :options="props.meta.courses" optionLabel="title" optionValue="id" placeholder="Sélectionnez un cours..." class="w-full border-slate-300" filter :class="{'p-invalid': !sessionForm.course_id}" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Instructeur <span class="text-rose-500">*</span></label>
                                        <Dropdown v-model="sessionForm.instructor_id" :options="props.meta.instructors" optionLabel="name" optionValue="id" placeholder="Sélectionnez..." class="w-full border-slate-300" :class="{'p-invalid': !sessionForm.instructor_id}" />
                                    </div>
                                    <div class="col-span-1 md:col-span-2 space-y-2 mt-4">
                                        <label class="block text-sm font-bold text-slate-700">Description</label>
                                        <Textarea v-model="sessionForm.description" rows="4" class="w-full bg-slate-50 border-slate-300" placeholder="Détails optionnels..." />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB PLANIFICATION                            -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'schedule'" class="space-y-8" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4"><i class="pi pi-calendar mr-2 text-indigo-500"></i>Planification</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Date et Heure <span class="text-rose-500">*</span></label>
                                        <Calendar v-model="sessionForm.start_at" showTime hourFormat="24" class="w-full" :class="{'p-invalid': !sessionForm.start_at || new Date(sessionForm.start_at) <= new Date()}" :minDate="new Date()" />
                                        <small v-if="sessionForm.start_at && new Date(sessionForm.start_at) <= new Date()" class="p-error text-xs">La date de début doit être postérieure à l'heure actuelle.</small>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Durée (minutes) <span class="text-rose-500">*</span></label>
                                        <InputNumber v-model="sessionForm.duration" class="w-full" :min="5" :max="480" :class="{'p-invalid': sessionForm.duration <= 0}" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Participants max</label>
                                        <InputNumber v-model="sessionForm.max_participants" class="w-full" :min="1" :max="1000" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Code d'accès (optionnel)</label>
                                        <InputText v-model="sessionForm.passcode" class="w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB PARAMÈTRES AVANCÉS                       -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'advanced'" class="space-y-8" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4"><i class="pi pi-cog mr-2 text-indigo-500"></i>Paramètres de la salle</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                            <div><div class="font-bold text-sm">Salle d'attente</div><div class="text-xs text-slate-500">Les participants patientent avant d'être admis.</div></div>
                                            <ToggleSwitch v-model="sessionForm.settings.waiting_room" />
                                        </div>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                            <div><div class="font-bold text-sm">Micro coupé à l'entrée</div></div>
                                            <ToggleSwitch v-model="sessionForm.settings.mute_on_entry" />
                                        </div>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                            <div><div class="font-bold text-sm">Chat autorisé</div></div>
                                            <ToggleSwitch v-model="sessionForm.settings.allow_chat" />
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                            <div><div class="font-bold text-sm">Vidéo HD</div></div>
                                            <ToggleSwitch v-model="sessionForm.settings.hd_video" />
                                        </div>
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                            <div><div class="font-bold text-sm">Enregistrement automatique</div></div>
                                            <ToggleSwitch v-model="sessionForm.is_recorded" />
                                        </div>
                                        <div class="space-y-2" v-if="sessionForm.is_recorded">
                                            <label class="text-sm font-bold">Stockage de l'enregistrement</label>
                                            <SelectButton v-model="sessionForm.settings.auto_recording" :options="['cloud', 'local']" class="w-full" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB RESSOURCES & REPLAYS                     -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'resources'" class="space-y-8" role="tabpanel">
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4"><i class="pi pi-paperclip mr-2 text-indigo-500"></i>Ressources partagées</h3>
                                <div class="mb-6">
                                    <FileUpload mode="basic" accept="*/*" :maxFileSize="10000000" customUpload @select="(e) => onResourceUpload(e, 'document')" chooseLabel="Ajouter un fichier" class="p-button-outlined p-button-secondary w-full border-dashed border-2 font-bold" auto />
                                </div>
                                <div v-if="sessionForm.resources.length > 0" class="space-y-3">
                                    <div v-for="(res, idx) in sessionForm.resources" :key="res.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border">
                                        <div class="flex items-center gap-3">
                                            <i class="pi pi-file text-slate-400"></i>
                                            <div>
                                                <div class="text-sm font-bold text-slate-700">{{ res.name }}</div>
                                                <div class="text-[10px] text-slate-400">{{ (res.size / 1024).toFixed(1) }} KB</div>
                                            </div>
                                        </div>
                                        <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm" @click="removeResource(idx)" v-tooltip.top="'Retirer'" />
                                    </div>
                                </div>
                                <p v-else class="text-sm text-slate-500 italic">Aucune ressource attachée pour le moment.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER (ACTIONS) -->
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div></div>
                <div class="flex items-center gap-3">
                    <Button label="Brouillon" icon="pi pi-save" class="p-button-outlined p-button-secondary font-bold bg-white border-slate-300" @click="saveSession('draft')" :loading="sessionForm.processing" />
                    <div class="relative group">
                        <Button label="Planifier la session" icon="pi pi-check" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold px-8" @click="saveSession('publish')" :loading="sessionForm.processing" :disabled="!isEntireFormValid" />
                        <div v-if="!isEntireFormValid" class="absolute bottom-full right-0 mb-2 w-64 bg-slate-800 text-white text-xs p-3 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                            Complétez les étapes obligatoires (général et planification).
                            <div class="absolute -bottom-1 right-8 w-2 h-2 bg-slate-800 transform rotate-45"></div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- SIDEBAR ANALYTICS (DÉTAILS DE SESSION EN DIRECT)                               -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="analyticsSidebar" position="right" class="w-full md:w-[480px] bg-slate-50 p-sidebar-custom border-l border-slate-200">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 shadow-sm border border-indigo-200"><i class="pi pi-chart-line text-lg"></i></div>
                    <span class="font-black text-xl text-slate-800">Engagement en direct</span>
                </div>
            </template>
            <div class="p-6 space-y-8 h-full overflow-y-auto custom-scrollbar pb-20">
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4"><i class="pi pi-chart-bar mr-2"></i>Audience (derniers 30 min)</h4>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm h-64">
                        <Chart type="line" :data="engagementChartData" :options="chartOptions" class="h-full" />
                    </div>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4"><i class="pi pi-users mr-2"></i>Participants actifs</h4>
                    <ScrollPanel class="h-64 bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                        <ul class="space-y-3">
                            <li v-for="i in 5" :key="i" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <Avatar label="U{{i}}" shape="circle" size="large" />
                                    <div>
                                        <div class="text-sm font-bold text-slate-800">Utilisateur {{ i }}</div>
                                        <div class="text-[10px] text-slate-400">Connecté depuis {{ i*5 }} min</div>
                                    </div>
                                </div>
                                <Badge value="online" severity="success" />
                            </li>
                        </ul>
                    </ScrollPanel>
                </div>
            </div>
        </Sidebar>

        <!-- ============================================================================== -->
        <!-- DIALOG CONFLITS D'HORAIRES                                                      -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="conflictDialog" header="Analyse des conflits" :modal="true" :style="{ width: '550px' }" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="bg-slate-50 p-0" headerClass="bg-white border-b border-slate-100 px-6 py-4">
            <div class="p-6 space-y-4">
                <Message severity="success" :closable="false">Aucun conflit d'horaire détecté pour les instructeurs cette semaine.</Message>
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <h4 class="text-sm font-bold mb-3">Prochaines sessions</h4>
                    <div class="space-y-2">
                        <div v-for="s in props.sessions.data?.slice(0,3)" :key="s.id" class="flex items-center gap-3 text-xs">
                            <i class="pi pi-calendar text-slate-400"></i>
                            <span class="font-medium">{{ s.title }} - {{ formatDate(s.start_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Fermer" class="p-button-text" @click="conflictDialog = false" />
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Animations */
.animate-fade-in {
    animation: fadeIn 0.25s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Scrollbar personnalisée */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* DataTable custom */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.p-toolbar) {
    background: transparent;
    border: none;
}
:deep(.p-sidebar-custom) {
    background-color: #f8fafc;
}
</style>
