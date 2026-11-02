<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - DASHBOARD ADMINISTRATEUR
 * ======================================================================================
 *
 * Tableau de bord administrateur avec design system SUBNETCONGO.
 * Interface claire et accessible pour la gestion de la plateforme.
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import debounce from 'lodash/debounce';

// Composants PrimeVue nécessaires
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import Chart from 'primevue/chart';
import Tag from 'primevue/tag';
import Knob from 'primevue/knob';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ConfirmDialog from 'primevue/confirmdialog';
import Timeline from 'primevue/timeline';
import ScrollPanel from 'primevue/scrollpanel';
import Badge from 'primevue/badge';
import OverlayBadge from 'primevue/overlaybadge';

// Composants du design system
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';
import PageSection from '@/Components/PageSection.vue';
import SncSkeleton from '@/Components/SncSkeleton.vue';
import SncAlert from '@/Components/SncAlert.vue';

// ======================================================================================
// PROPS
// ======================================================================================
const props = defineProps({
    meta: Object,
    availableFilters: Object,
    kpis: Object,
    chartData: Object,
    cohorts: Object,
    leaderboard: Array,
    activities: Array,
    supportStatus: Object,
    infra: Object
});

// ======================================================================================
// ÉTAT RÉACTIF
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const isLoading = ref(true);

const activeFilters = reactive({
    year: props.availableFilters?.years?.[0] || new Date().getFullYear(),
    month: props.availableFilters?.months?.[new Date().getMonth()] || 1,
    category: 'all',
    search: '',
});

// État infrastructure
const infraState = reactive({
    cpu: props.infra?.cpu || 0,
    ram: props.infra?.ram || 0,
    disk: props.infra?.disk || 0,
    latency: props.infra?.db_latency || 0,
    status: props.infra?.status || 'unknown',
    platform: props.infra?.platform || 'unknown'
});

let infraPollingInterval = null;

// ======================================================================================
// MÉTHODES
// ======================================================================================
const startInfraPolling = () => {
    infraPollingInterval = setInterval(() => {
        router.reload({
            only: ['infra', 'meta'],
            onSuccess: (page) => {
                if (page.props.infra) {
                    infraState.cpu = page.props.infra.cpu;
                    infraState.ram = page.props.infra.ram;
                    infraState.disk = page.props.infra.disk;
                    infraState.latency = page.props.infra.db_latency;
                    infraState.status = page.props.infra.status;
                }
            }
        });
    }, 30000);
};

const applyFilters = debounce(() => {
    isLoading.value = true;
    router.get(route('admin.dashboard'), activeFilters, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    });
}, 400);

const purgeCache = () => {
    confirm.require({
        message: 'Voulez-vous forcer le recalcul de toute l\'intelligence analytique ?',
        header: 'Purge du Cache',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.post(route('admin.analytics.purge'), {}, {
                onSuccess: () => toast.add({ severity: 'warn', summary: 'Cache Purgé', detail: 'Les données sont maintenant fraîches.' })
            });
        }
    });
};

// Options graphiques
const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#475569', font: { family: 'Inter', weight: '600' } } },
        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
        y: { grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { color: '#94a3b8' } }
    }
}));

const radarOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        r: {
            grid: { color: '#e2e8f0' },
            pointLabels: { font: { size: 10, weight: 'bold' }, color: '#475569' }
        }
    }
}));

// Helpers
const formatCurrency = (val) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(val);
const formatNumber = (val) => new Intl.NumberFormat('fr-FR').format(val);

onMounted(() => {
    setTimeout(() => isLoading.value = false, 500);
    startInfraPolling();
});

onBeforeUnmount(() => {
    if (infraPollingInterval) clearInterval(infraPollingInterval);
});
</script>

<template>
    <AppLayout>
        <Head title="Tableau de bord administrateur" />
        <ConfirmDialog />

        <div class="page-bg min-h-screen">

            <!-- Header avec PageHeader -->
            <PageHeader
                :title="props.meta?.greeting || 'Tableau de bord'"
                subtitle="Vue d'ensemble de la plateforme SUBNETCONGO"
                icon="pi-chart-bar"
                badge="Admin"
                accent="blue"
                :actions="[
                    { label: 'Actualiser', icon: 'pi-refresh', onClick: () => router.reload() },
                    { label: 'Purger cache', icon: 'pi-trash', onClick: purgeCache }
                ]"
            />

            <div class="page-container">
                <!-- Filtres rapides -->
                <div class="page-card-flush p-4 mb-6 flex flex-wrap gap-4 items-center">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText
                            v-model="activeFilters.search"
                            @input="applyFilters"
                            placeholder="Rechercher..."
                            class="w-full sm:w-64 bg-slate-50 border-none rounded-xl font-bold focus:ring-blue-500"
                        />
                    </IconField>
                    <Tag :value="`Année: ${activeFilters.year}`" class="!rounded-lg" />
                    <Tag :value="`Mois: ${activeFilters.month}`" class="!rounded-lg" />
                </div>

                <!-- Skeleton loader -->
                <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <SncSkeleton v-for="i in 4" :key="i" variant="rectangular" class="h-40" />
                </div>

                <!-- KPIs avec PageKpi -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <PageKpi
                        label="Chiffre d'affaires"
                        :value="formatCurrency(props.kpis?.revenue?.total || 0)"
                        icon="pi-dollar"
                        color="blue"
                        :trend="props.kpis?.revenue?.progress || 0"
                        :subtext="`Prédiction M+1: ${formatCurrency(props.kpis?.revenue?.predicted || 0)}`"
                    />
                    <PageKpi
                        label="Étudiants actifs"
                        :value="formatNumber(props.kpis?.students?.total || 0)"
                        icon="pi-users"
                        color="indigo"
                        :trend="props.kpis?.students?.new_month || 0"
                        :subtext="`Taux d'attrition: ${props.kpis?.students?.churn || 0}%`"
                    />
                    <PageKpi
                        label="Taux de complétion"
                        :value="`${props.kpis?.academic?.completion || 0}%`"
                        icon="pi-graduation-cap"
                        color="emerald"
                        :subtext="`Diplômes: ${props.kpis?.academic?.certificates_issued || 0}`"
                    />
                    <PageKpi
                        label="Santé système"
                        :value="`${infraState.cpu}%`"
                        icon="pi-server"
                        color="violet"
                        :subtext="`Latence: ${infraState.latency}ms`"
                    />
                </div>

                <!-- Graphiques -->
                <div v-if="!isLoading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Évolution des revenus -->
                    <PageCard title="Évolution financière" subtitle="Comparatif annuel" class="lg:col-span-2">
                        <div class="h-[350px]">
                            <Chart
                                v-if="props.chartData?.revenue_evolution"
                                type="line"
                                :data="props.chartData.revenue_evolution"
                                :options="chartOptions"
                                class="h-full w-full"
                            />
                            <div v-else class="flex items-center justify-center h-full text-slate-400">
                                Données non disponibles
                            </div>
                        </div>
                    </PageCard>

                    <!-- Radar compétences -->
                    <PageCard title="Offre vs Demande" subtitle="Équilibre des compétences">
                        <div class="h-[350px]">
                            <Chart
                                v-if="props.chartData?.skills_radar"
                                type="radar"
                                :data="props.chartData.skills_radar"
                                :options="radarOptions"
                                class="h-full w-full"
                            />
                            <div v-else class="flex items-center justify-center h-full text-slate-400">
                                Données non disponibles
                            </div>
                        </div>
                    </PageCard>
                </div>

                <!-- Détails infrastructure -->
                <PageSection v-if="!isLoading" title="Infrastructure" subtitle="État des serveurs en temps réel">
                    <PageCard>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="flex justify-center mb-4">
                                    <Knob
                                        v-model="infraState.cpu"
                                        :size="100"
                                        :strokeWidth="12"
                                        valueColor="#6366F1"
                                        rangeColor="#F1F5F9"
                                        readonly
                                        valueTemplate="{value}%"
                                    />
                                </div>
                                <p class="text-sm font-bold text-slate-800">CPU</p>
                            </div>
                            <div class="text-center">
                                <div class="flex justify-center mb-4">
                                    <Knob
                                        v-model="infraState.ram"
                                        :size="100"
                                        :strokeWidth="12"
                                        valueColor="#10B981"
                                        rangeColor="#F1F5F9"
                                        readonly
                                        valueTemplate="{value}%"
                                    />
                                </div>
                                <p class="text-sm font-bold text-slate-800">RAM</p>
                            </div>
                            <div class="text-center">
                                <div class="flex justify-center mb-4">
                                    <Knob
                                        v-model="infraState.disk"
                                        :size="100"
                                        :strokeWidth="12"
                                        valueColor="#F59E0B"
                                        rangeColor="#F1F5F9"
                                        readonly
                                        valueTemplate="{value}%"
                                    />
                                </div>
                                <p class="text-sm font-bold text-slate-800">Disque</p>
                            </div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-slate-600">Latence DB:</span>
                                <Tag :value="`${infraState.latency}ms`" severity="info" />
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-slate-600">Plateforme:</span>
                                <Tag :value="infraState.platform" severity="secondary" />
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-slate-600">Uptime:</span>
                                <Tag :value="props.infra?.uptime || 'En cours'" severity="success" />
                            </div>
                        </div>
                    </PageCard>
                </PageSection>

                <!-- Activités récentes et Support -->
                <div v-if="!isLoading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Journal d'audit -->
                    <PageCard title="Journal d'audit" :subtitle="`${props.activities?.length || 0} actions récentes`">
                        <ScrollPanel style="height: 300px" class="pr-4">
                            <Timeline v-if="props.activities?.length" :value="props.activities">
                                <template #marker="slotProps">
                                    <span class="flex w-8 h-8 items-center justify-center text-white rounded-full shadow-lg" :class="`bg-${slotProps.item.type === 'error' ? 'rose' : slotProps.item.type === 'success' ? 'emerald' : 'indigo'}-500`">
                                        <i :class="`pi ${slotProps.item.type === 'error' ? 'pi-times' : 'pi-check'} text-[10px]`"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-6">
                                        <h4 class="text-xs font-black text-slate-800 leading-tight mb-1">{{ slotProps.item.action }}</h4>
                                        <p class="text-[10px] font-medium text-slate-400 italic mb-2">{{ slotProps.item.description }}</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[8px] font-black uppercase tracking-widest text-blue-500">{{ slotProps.item.user }}</span>
                                            <span class="text-[8px] font-bold text-slate-300">• {{ slotProps.item.time }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Timeline>
                            <div v-else class="flex items-center justify-center h-full text-slate-400 py-8">
                                Aucune activité récente
                            </div>
                        </ScrollPanel>
                    </PageCard>

                    <!-- Tickets support -->
                    <PageCard title="Tickets de support" :subtitle="`${props.supportStatus?.open_tickets || 0} ouverts`">
                        <div v-if="props.supportStatus?.recent_tickets?.length" class="space-y-3 max-h-[300px] overflow-y-auto pr-2">
                            <div
                                v-for="ticket in props.supportStatus.recent_tickets"
                                :key="ticket.id"
                                class="p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-blue-300 transition-all cursor-pointer group"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[9px] font-black text-slate-400 font-mono">#{{ ticket.id }}</span>
                                    <Tag :value="ticket.priority" :severity="ticket.priority === 'High' ? 'danger' : 'info'" class="text-[8px] uppercase font-black" />
                                </div>
                                <p class="text-xs font-bold text-slate-700 leading-tight group-hover:text-blue-600 mb-2">{{ ticket.subject }}</p>
                                <Tag :value="ticket.status" severity="secondary" class="text-[8px] font-black" />
                            </div>
                        </div>
                        <div v-else class="flex items-center justify-center py-8 text-slate-400">
                            Aucun ticket ouvert
                        </div>
                    </PageCard>
                </div>

                <!-- Leaderboard -->
                <PageSection v-if="!isLoading && props.leaderboard?.length" title="Hall of Fame" subtitle="Top apprenants">
                    <PageCard>
                        <div class="space-y-4">
                            <div
                                v-for="(student, index) in props.leaderboard.slice(0, 5)"
                                :key="student.id"
                                class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 hover:bg-slate-100 transition-all"
                            >
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm" :class="index === 0 ? 'bg-amber-500 text-white' : index === 1 ? 'bg-slate-400 text-white' : index === 2 ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-600'">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-800">{{ student.name }}</p>
                                    <p class="text-xs text-slate-500">{{ student.points || 0 }} points</p>
                                </div>
                                <Tag :value="student.courses_completed || 0" severity="info" class="text-[8px]" />
                            </div>
                        </div>
                    </PageCard>
                </PageSection>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar */
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
:deep(.custom-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5; border-bottom: 3px solid #4f46e5;
}
:deep(.custom-tabview .p-tabview-panels) { padding: 0; }

/* DataTable Enterprise Edition */
:deep(.custom-datatable .p-datatable-header) { background: transparent; border: none; padding: 0; }
:deep(.custom-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc; color: #64748b; font-size: 0.65rem; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.1em; padding: 1rem;
    border-bottom: 2px solid #e2e8f0; border-top: 1px solid #e2e8f0;
}
:deep(.custom-datatable .p-datatable-tbody > tr) { transition: background-color 0.2s; }
:deep(.custom-datatable .p-datatable-tbody > tr:hover) { background-color: #f8fafc; }
:deep(.custom-datatable .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9; padding: 1rem;
}
:deep(.custom-datatable .p-paginator) { border: none; background: transparent; padding-top: 1rem; }

/* Timeline Customization */
:deep(.p-timeline-event-opposite) { display: none; } /* Enlever la colonne vide à gauche */
:deep(.p-timeline-event-content) { padding-left: 1rem; }

/* Custom Overlays & Modals */
:deep(.custom-dialog .p-dialog-header) { border-bottom: 1px solid #f1f5f9; padding: 1rem; }
:deep(.custom-dialog .p-dialog-content) { padding: 0; }
:deep(.p-sidebar-custom) { border-left: 1px solid #e2e8f0; box-shadow: -10px 0 30px rgba(0,0,0,0.05); }

/* Utilities */
.inner-shadow { box-shadow: inset 0 2px 10px rgba(0,0,0,0.02); }

/* Transition pour le message de bienvenue */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
