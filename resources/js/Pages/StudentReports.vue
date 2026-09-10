<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO ENTERPRISE - STUDENT INTELLIGENCE HUB v40.0 (Prestige Edition)
 * ======================================================================================
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

// PRIMEVUE CORE
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import Dialog from 'primevue/dialog';
import Tooltip from 'primevue/tooltip';
import Toast from 'primevue/toast';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    students: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    courses: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    availableStatuses: { type: Array, default: () => [] }
});

const toast = useToast();
const isLoading = ref(false);
const displayMode = ref('table'); 
const showQuickView = ref(false);
const selectedStudent = ref(null);
const selectedStudents = ref([]);

// FILTERS
const activeFilters = ref({
    search: props.filters.search || '',
    course: props.filters.course || null,
    status: props.filters.status || null,
});

const syncData = debounce(() => {
    isLoading.value = true;
    router.get(route('student-reports.index'), { ...activeFilters.value, page: 1 }, {
        preserveState: true, preserveScroll: true, replace: true,
        onFinish: () => isLoading.value = false
    });
}, 400);

watch(activeFilters, () => syncData(), { deep: true });

const onPageChange = (event) => {
    isLoading.value = true;
    router.get(props.students.path, { page: event.page + 1, ...activeFilters.value }, {
        preserveState: true, preserveScroll: true, onFinish: () => isLoading.value = false
    });
};

const getScoreSeverity = (s) => s >= 80 ? 'success' : s >= 50 ? 'warn' : 'danger';
const openQuickView = (s) => { selectedStudent.value = s; showQuickView.value = true; };
</script>

<template>
    <AppLayout>
        <Head title="Intelligence Hub | SubnetCongo Analytics" />
        <Toast />

        <div class="min-h-screen bg-[#F8FAFC] pb-20 font-sans">

            <!-- ── HEADER : COMMAND CENTER (Mesh Gradient V40) ── -->
            <header class="bg-[#0F172A] pt-12 pb-28 px-8 relative overflow-hidden">
                <!-- Mesh Background Effects -->
                <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] bg-red-600/15 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute bottom-[-20%] left-[-5%] w-[400px] h-[400px] bg-red-900/10 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-[1700px] mx-auto relative z-10 flex flex-col lg:flex-row justify-between items-center gap-8">
                    <div class="text-center lg:text-left">
                        <div class="flex items-center justify-center lg:justify-start gap-3 mb-4">
                            <span class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-red-500/20">Enterprise Intelligence</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic uppercase leading-none">
                            Student <span class="text-red-600">Intelligence</span> Hub
                        </h1>
                        <p class="text-slate-400 mt-4 max-w-xl text-sm font-medium leading-relaxed italic">
                            Monitoring analytique en temps réel. Analyse prédictive des cohortes et tracking de performance certifié SubnetCongo.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button label="Export Global" icon="pi pi-download" class="!bg-white/5 hover:!bg-white/10 !border-white/10 !text-white !rounded-xl !px-6 !py-4 backdrop-blur-xl" />
                        <Button label="Nouvelle Analyse" icon="pi pi-plus" class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-8 !py-4 shadow-xl shadow-red-600/20 active:scale-95 transition-all" />
                    </div>
                </div>
            </header>

            <!-- ── MAIN ANALYTICS CONSOLE ── -->
            <main class="max-w-[1700px] mx-auto px-8 -mt-16 relative z-20 space-y-8">

                <!-- 1. KPI INSIGHT CARDS -->
                <section v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="kpi in [
                        { l: 'Moyenne Cohorte', v: stats.avg_score + '%', i: 'pi-percentage', c: 'text-red-600', bg: 'bg-red-50' },
                        { l: 'Actifs (Real-time)', v: stats.active_now, i: 'pi-bolt', c: 'text-emerald-600', bg: 'bg-emerald-50' },
                        { l: 'Certifications', v: stats.total_certifications, i: 'pi-verified', c: 'text-blue-600', bg: 'bg-blue-50' },
                        { l: 'Profils à risque', v: stats.at_risk_students, i: 'pi-exclamation-triangle', c: 'text-rose-600', bg: 'bg-rose-50' }
                    ]" :key="kpi.l" class="bg-white p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-2xl transition-all group overflow-hidden relative">
                        <div class="relative z-10">
                            <div :class="kpi.bg" class="w-12 h-12 rounded-xl flex items-center justify-center mb-6 group-hover:rotate-6 transition-transform">
                                <i :class="['pi', kpi.i, kpi.c, 'text-xl']"></i>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ kpi.l }}</p>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tighter italic">{{ kpi.v }}</h2>
                        </div>
                        <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform">
                            <i :class="['pi', kpi.i, 'text-8xl text-slate-900']"></i>
                        </div>
                    </div>
                </section>

                <!-- 2. FILTER CONSOLE (Glassmorphism) -->
                <section class="bg-white/80 backdrop-blur-xl p-4 rounded-[1rem] border border-white/50 shadow-xl flex flex-wrap items-center justify-between gap-6 sticky top-4 z-40">
                    <div class="flex flex-wrap items-center gap-4 flex-1">
                        <IconField iconPosition="left" class="flex-1 max-w-md">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="activeFilters.search" placeholder="Rechercher un ID, nom ou email..." 
                                class="w-full !rounded-xl !bg-slate-50 border-none !py-3.5 focus:!ring-2 focus:!ring-red-500/20 transition-all" />
                        </IconField>

                        <Select v-model="activeFilters.course" :options="courses" optionLabel="name" optionValue="id" 
                            placeholder="Tous les cours" class="!rounded-xl !bg-slate-50 border-none !min-w-[200px]" showClear />
                        
                        <MultiSelect v-model="activeFilters.status" :options="availableStatuses" optionLabel="label" optionValue="value" 
                            placeholder="Statuts" class="!rounded-xl !bg-slate-50 border-none !min-w-[180px]" />
                    </div>

                    <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
                        <Button icon="pi pi-table" :class="displayMode === 'table' ? '!bg-white shadow-md !text-red-600' : '!text-slate-400'" 
                            @click="displayMode = 'table'" text class="!rounded-xl" />
                        <Button icon="pi pi-grid" :class="displayMode === 'grid' ? '!bg-white shadow-md !text-red-600' : '!text-slate-400'" 
                            @click="displayMode = 'grid'" text class="!rounded-xl" />
                        <div class="w-[1px] h-6 bg-slate-200 mx-1"></div>
                        <Button icon="pi pi-refresh" text @click="syncData" class="!text-slate-400 hover:!text-red-600" />
                    </div>
                </section>

                <!-- 3. DATA INTERFACE -->
                <div class="relative min-h-[600px]">
                    <!-- Loading Overlay -->
                    <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-30 flex items-center justify-center rounded-[1rem]">
                        <div class="flex flex-col items-center gap-4">
                            <i class="pi pi-spin pi-spinner text-4xl text-red-600"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-900 animate-pulse">Syncing Intelligence...</span>
                        </div>
                    </div>

                    <!-- TABLE VIEW V40 -->
                    <div v-if="displayMode === 'table'" class="bg-white rounded-[1rem] border border-slate-200/60 shadow-2xl overflow-hidden">
                        
                        <!-- Bulk Action Bar -->
                        <div v-if="selectedStudents.length" class="bg-[#0F172A] p-4 flex justify-between items-center animate-fade-in">
                            <span class="text-white font-black text-xs px-4">{{ selectedStudents.length }} Profils sélectionnés</span>
                            <div class="flex gap-2">
                                <Button label="Notifier" severity="success" size="small" class="!rounded-xl !text-[10px] !font-black !px-6" />
                                <Button label="Désactiver" severity="danger" size="small" class="!rounded-xl !text-[10px] !font-black !px-6" />
                            </div>
                        </div>

                        <DataTable :value="students.data" v-model:selection="selectedStudents" dataKey="id" 
                                   stripedRows paginator :rows="15" responsiveLayout="scroll" class="enterprise-datatable">
                            
                            <Column selectionMode="multiple" headerStyle="width: 4rem; padding-left: 2rem" />

                            <Column header="Identité Apprenant">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-5 py-2 group cursor-pointer" @click="openQuickView(data)">
                                        <div class="relative">
                                            <Avatar :image="data.avatar" :label="data.name?.charAt(0)" shape="circle" size="xlarge" 
                                                class="!bg-red-50 !text-red-600 !font-black border border-red-100 group-hover:scale-110 transition-transform shadow-sm" />
                                            <div v-if="data.is_online" class="absolute bottom-1 right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 tracking-tight leading-none italic uppercase">{{ data.name }}</p>
                                            <p class="text-[10px] text-slate-400 font-bold mt-1.5 uppercase tracking-widest">{{ data.email }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Métrique Performance">
                                <template #body="{ data }">
                                    <div v-if="data.avg_score !== null" class="w-48">
                                        <div class="flex justify-between items-end mb-2">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Score IA</span>
                                            <span :class="`text-sm font-black text-${getScoreSeverity(data.avg_score)}-600`" class="italic">{{ data.avg_score }}%</span>
                                        </div>
                                        <ProgressBar :value="data.avg_score" :showValue="false" class="h-1.5 bg-slate-100 rounded-full overflow-hidden" 
                                            :style="`--p-progressbar-value-background: ${data.avg_score >= 80 ? '#10B981' : data.avg_score >= 50 ? '#F59E0B' : '#E11D48'}`" />
                                    </div>
                                    <span v-else class="text-[10px] italic text-slate-300 uppercase">Non Évalué</span>
                                </template>
                            </Column>

                            <Column header="Dernier Log" style="width: 200px">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <i class="pi pi-clock text-slate-300 text-xs"></i>
                                        <div>
                                            <p class="text-xs font-bold text-slate-700 leading-none">{{ data.last_active_human }}</p>
                                            <p class="text-[9px] font-mono text-slate-400 mt-1 uppercase">{{ data.ip_address || 'Proxy Local' }}</p>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Actions" alignFrozen="right" frozen>
                                <template #body="{ data }">
                                    <div class="flex gap-1 justify-end px-4">
                                        <Button icon="pi pi-eye" text rounded severity="secondary" @click="openQuickView(data)" v-tooltip="'Aperçu rapide'" />
                                        <Link :href="route('student-reports.show', data.id)">
                                            <Button icon="pi pi-arrow-right" text rounded class="!text-red-600" v-tooltip="'Dossier complet'" />
                                        </Link>
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="py-24 text-center">
                                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                                        <i class="pi pi-search-minus text-3xl text-slate-300"></i>
                                    </div>
                                    <h4 class="text-xl font-black text-slate-900 uppercase italic">Aucun Apprenant Trouvé</h4>
                                    <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto font-medium leading-relaxed">Ajustez vos filtres pour explorer la base de données SubnetCongo.</p>
                                </div>
                            </template>
                        </DataTable>

                        <!-- PAGINATOR V40 -->
                        <div v-if="students.total > 0" class="px-8 py-6 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                Analyse <span class="text-red-600">{{ students.total }}</span> profils au total
                            </p>
                            <Paginator :rows="students.per_page" :totalRecords="students.total" 
                                       :first="(students.current_page - 1) * students.per_page" @page="onPageChange" 
                                       template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" 
                                       class="!bg-transparent !border-none custom-paginator" />
                        </div>
                    </div>

                    <!-- GRID VIEW V40 -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-8">
                        <div v-for="student in students.data" :key="student.id" 
                            class="bg-white p-8 rounded-[1rem] border border-slate-200/60 shadow-sm hover:shadow-2xl transition-all duration-500 relative group overflow-hidden">
                            
                            <div class="absolute top-0 right-0 w-24 h-24 bg-red-600/5 rounded-full blur-2xl group-hover:bg-red-600/20 transition-all"></div>
                            
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <div class="relative mb-6">
                                    <Avatar :image="student.avatar" :label="student.name?.charAt(0)" size="xlarge" shape="circle" 
                                        class="!w-24 !h-24 !bg-red-50 !text-red-600 !font-black border-4 border-white shadow-xl" />
                                    <div v-if="student.is_online" class="absolute bottom-2 right-2 w-4 h-4 bg-emerald-500 border-4 border-white rounded-full"></div>
                                </div>

                                <h3 class="text-lg font-black text-slate-900 italic tracking-tighter uppercase leading-none">{{ student.name }}</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2 mb-6">{{ student.email }}</p>

                                <div class="w-full grid grid-cols-2 gap-4 mb-8">
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Performance</p>
                                        <p :class="`font-black text-sm text-${getScoreSeverity(student.avg_score)}-600 italic`">{{ student.avg_score || 0 }}%</p>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Soumissions</p>
                                        <p class="font-black text-sm text-slate-900 italic">{{ student.submissions_count || 0 }}</p>
                                    </div>
                                </div>

                                <div class="w-full flex gap-3">
                                    <Button icon="pi pi-eye" text class="!text-slate-400 !bg-slate-50 !rounded-xl !px-4" @click="openQuickView(student)" />
                                    <Link :href="route('student-reports.show', student.id)" class="flex-1">
                                        <Button label="Voir Dossier" class="!w-full !bg-[#0F172A] !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-3.5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- ── QUICK VIEW DIALOG (Dossier Mode) ── -->
            <Dialog v-model:visible="showQuickView" modal header="Analytics Profil" :style="{ width: '550px' }" class="!rounded-[1rem]">
                <div v-if="selectedStudent" class="space-y-8 p-4">
                    <!-- Profile Card -->
                    <div class="bg-[#0F172A] p-8 rounded-[1rem] text-white relative overflow-hidden flex items-center gap-6 shadow-2xl">
                        <Avatar :image="selectedStudent.avatar" :label="selectedStudent.name?.charAt(0)" size="xlarge" shape="circle" 
                            class="!w-24 !h-24 !bg-white/10 !text-white !font-black border-4 border-white/20 shadow-2xl" />
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black italic tracking-tighter uppercase leading-tight">{{ selectedStudent.name }}</h2>
                            <p class="text-slate-400 text-xs font-medium">{{ selectedStudent.email }}</p>
                            <div class="flex gap-2 mt-4">
                                <Tag :value="selectedStudent.is_online ? 'Live' : 'Offline'" :severity="selectedStudent.is_online ? 'success' : 'secondary'" class="!text-[8px] !font-black !rounded-lg" />
                                <Tag :value="`ID: ${selectedStudent.id}`" class="!bg-white/10 !text-white/60 !text-[8px] !font-black !rounded-lg" />
                            </div>
                        </div>
                    </div>

                    <!-- Skills Monitoring -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2 px-2">
                            <span class="w-2 h-2 bg-red-600 rounded-full"></span> Skill Acquisition Metrics
                        </h4>
                        <div class="bg-slate-50 p-8 rounded-[1rem] border border-slate-100 space-y-6">
                            <template v-if="selectedStudent.skills?.length">
                                <div v-for="s in selectedStudent.skills" :key="s.name">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-xs font-black text-slate-800 italic">{{ s.name }}</span>
                                        <span :class="`text-xs font-black text-${getScoreSeverity(s.score)}-600`">{{ s.score }}%</span>
                                    </div>
                                    <ProgressBar :value="s.score" :showValue="false" class="h-1.5" 
                                        :style="`--p-progressbar-value-background: ${getScoreSeverity(s.score) === 'success' ? '#10B981' : '#E11D48'}`" />
                                </div>
                            </template>
                            <p v-else class="text-xs text-slate-400 italic text-center">Aucune donnée de compétence pour cet ID.</p>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="flex gap-4 border-t border-slate-100 pt-8">
                        <Button label="Fermer" text @click="showQuickView = false" class="!text-slate-400 !font-bold" />
                        <Link :href="route('student-reports.show', selectedStudent.id)" class="flex-1">
                            <Button label="Dossier Complet" icon="pi pi-external-link" class="!w-full !bg-red-600 !border-none !rounded-xl !py-4 !text-xs !font-black !uppercase !tracking-widest" />
                        </Link>
                    </div>
                </div>
            </Dialog>

        </div>
    </AppLayout>
</template>

<style scoped>
/* PRESTIGE DESIGN UTILS */
.italic { font-style: italic; }
.tracking-tighter { letter-spacing: -0.05em; }

/* DATATABLE OVERRIDES */
:deep(.enterprise-datatable .p-datatable-thead > tr > th) {
    background: #F8FAFC;
    color: #94A3B8;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    border-bottom: 2px solid #F1F5F9;
}
:deep(.enterprise-datatable .p-datatable-tbody > tr) { transition: all 0.2s ease; }
:deep(.enterprise-datatable .p-datatable-tbody > tr:hover) {
    background: #F1F5F9 !important;
    transform: scale(0.998);
}

/* PAGINATOR OVERRIDES */
:deep(.custom-paginator .p-paginator-page) { border-radius: 12px; font-weight: 800; font-size: 12px; min-width: 2.5rem; height: 2.5rem; }
:deep(.custom-paginator .p-paginator-page.p-highlight) { background: #E11D48 !important; color: white; }

/* INPUT OVERRIDES */
:deep(.p-select) { border: none !important; box-shadow: none !important; }
:deep(.p-multiselect) { border: none !important; }
:deep(.p-inputtext:focus) { border-color: #E11D48; box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1); }
</style>