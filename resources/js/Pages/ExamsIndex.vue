<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT EXAM CENTER v3.0
 * ======================================================================================
 * Centre d'évaluation de l'étudiant : liste des examens disponibles,
 * statuts, scores, tentatives et navigation vers le player.
 */
import { ref, computed } from 'vue';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import InputText from 'primevue/inputtext';

const props = defineProps({
    exams: Array
});

// Filtre local
const search = ref('');
const activeFilter = ref('all'); // 'all', 'available', 'passed', 'failed'

const filteredExams = computed(() => {
    let list = props.exams || [];

    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(e => e.title?.toLowerCase().includes(s) || e.course?.title?.toLowerCase().includes(s));
    }

    if (activeFilter.value === 'available') {
        list = list.filter(e => !e.is_passed && e.remaining_attempts !== 0);
    } else if (activeFilter.value === 'passed') {
        list = list.filter(e => e.is_passed);
    } else if (activeFilter.value === 'failed') {
        list = list.filter(e => !e.is_passed && e.attempts_count > 0 && e.remaining_attempts === 0);
    }

    return list;
});

const stats = computed(() => ({
    total: props.exams?.length ?? 0,
    available: props.exams?.filter(e => !e.is_passed && e.remaining_attempts !== 0).length ?? 0,
    passed: props.exams?.filter(e => e.is_passed).length ?? 0,
    in_progress: props.exams?.filter(e => e.active_attempt_uuid).length ?? 0,
}));

const getStatus = (exam) => {
    if (exam.is_passed) return { label: 'Réussi', sev: 'success', icon: 'pi-check-circle', color: 'emerald' };
    if (exam.active_attempt_uuid) return { label: 'En cours', sev: 'warning', icon: 'pi-spin pi-spinner', color: 'amber' };
    if (exam.attempts_count > 0 && exam.remaining_attempts === 0) return { label: 'Limite atteinte', sev: 'danger', icon: 'pi-lock', color: 'rose' };
    if (exam.attempts_count > 0) return { label: 'Échoué', sev: 'danger', icon: 'pi-times-circle', color: 'rose' };
    return { label: 'Disponible', sev: 'info', icon: 'pi-circle', color: 'violet' };
};

const getTypeIcon = (type) => {
    const map = { 'Certification': 'pi-verified', 'Quiz': 'pi-question-circle', 'Examen': 'pi-pencil', 'Pratique': 'pi-code' };
    return map[type] || 'pi-file';
};

const formatDuration = (minutes) => {
    if (!minutes) return 'N/A';
    if (minutes < 60) return `${minutes} min`;
    return `${Math.floor(minutes / 60)}h${minutes % 60 > 0 ? String(minutes % 60).padStart(2, '0') : ''}`;
};
</script>

<template>
    <AppLayout>
        <Head title="Centre d'Évaluation" />

        <div class="min-h-screen bg-[#F8FAFC] pb-16">

            <!-- ── HEADER ── -->
            <div class="bg-white border-b border-slate-100 sticky top-0 z-40">
                <div class="max-w-6xl mx-auto px-6 py-5">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <Link :href="route('dashboard')" class="text-slate-400 hover:text-slate-600 transition-colors">
                                    <i class="pi pi-home text-sm"></i>
                                </Link>
                                <i class="pi pi-chevron-right text-slate-300 text-xs"></i>
                                <span class="text-sm font-bold text-slate-500">Centre d'Évaluation</span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-800 italic">Mon Centre d'Évaluation</h1>
                            <p class="text-slate-400 text-sm mt-1">Passez vos examens et certifications. Suivez vos résultats.</p>
                        </div>
                        <!-- Barre de recherche -->
                        <div class="relative">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <InputText v-model="search" placeholder="Rechercher un examen..." class="pl-9 text-sm rounded-xl border-slate-200 w-64" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-6 mt-6">

                <!-- ── KPI CARDS ── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Total examens', val: stats.total, icon: 'pi-file', color: 'slate', filter: 'all' },
                        { label: 'Disponibles', val: stats.available, icon: 'pi-circle', color: 'violet', filter: 'available' },
                        { label: 'Réussis', val: stats.passed, icon: 'pi-check-circle', color: 'emerald', filter: 'passed' },
                        { label: 'En cours', val: stats.in_progress, icon: 'pi-clock', color: 'amber', filter: 'in_progress' },
                    ]" :key="kpi.label"
                        @click="activeFilter = kpi.filter === 'in_progress' ? 'all' : kpi.filter"
                        :class="[
                            'bg-white p-4 rounded-2xl border shadow-sm flex items-center gap-3 cursor-pointer transition-all duration-200',
                            activeFilter === kpi.filter ? 'border-violet-300 ring-2 ring-violet-100' : 'border-slate-100 hover:border-slate-200 hover:shadow-md'
                        ]">
                        <div :class="`w-10 h-10 bg-${kpi.color}-50 text-${kpi.color}-600 rounded-xl flex items-center justify-center flex-shrink-0`">
                            <i :class="`pi ${kpi.icon} text-base`"></i>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ kpi.label }}</p>
                            <h3 class="text-xl font-black text-slate-800">{{ kpi.val }}</h3>
                        </div>
                    </div>
                </div>

                <!-- ── FILTRES PILL ── -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button v-for="f in [
                        { key: 'all', label: 'Tous' },
                        { key: 'available', label: 'Disponibles' },
                        { key: 'passed', label: 'Réussis' },
                        { key: 'failed', label: 'Échoués' }
                    ]" :key="f.key"
                        @click="activeFilter = f.key"
                        :class="[
                            'px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest transition-all',
                            activeFilter === f.key
                                ? 'bg-violet-600 text-white shadow-md shadow-violet-200'
                                : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300'
                        ]">
                        {{ f.label }}
                    </button>
                </div>

                <!-- ── GRILLE D'EXAMENS ── -->
                <div v-if="filteredExams.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="exam in filteredExams" :key="exam.id"
                         class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden group">

                        <!-- Card Header coloré selon status -->
                        <div :class="[
                            'h-2',
                            exam.is_passed ? 'bg-emerald-500' :
                            exam.active_attempt_uuid ? 'bg-amber-400' :
                            exam.attempts_count > 0 && exam.remaining_attempts === 0 ? 'bg-rose-500' :
                            'bg-violet-500'
                        ]"></div>

                        <div class="p-5 flex-1">
                            <!-- Badges -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-2">
                                    <div :class="`w-8 h-8 rounded-lg flex items-center justify-center text-white bg-violet-500`">
                                        <i :class="`pi ${getTypeIcon(exam.type)} text-sm`"></i>
                                    </div>
                                    <Tag :value="exam.type || 'Examen'" severity="secondary" class="text-[9px] font-bold" />
                                </div>
                                <Tag
                                    :value="getStatus(exam).label"
                                    :severity="getStatus(exam).sev"
                                    :icon="`pi ${getStatus(exam).icon}`"
                                    class="text-[9px] font-bold"
                                />
                            </div>

                            <!-- Titre & cours -->
                            <h3 class="text-base font-black text-slate-800 italic mb-1 leading-tight">{{ exam.title }}</h3>
                            <p class="text-xs text-slate-400 mb-4 flex items-center gap-1">
                                <i class="pi pi-book text-violet-400 text-[10px]"></i>
                                {{ exam.course?.title || 'Formation générale' }}
                            </p>

                            <!-- Metadata -->
                            <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-50 mb-4">
                                <div class="text-center">
                                    <span class="text-[9px] uppercase font-bold text-slate-400 block mb-0.5">Durée</span>
                                    <span class="text-xs font-black text-slate-700">{{ formatDuration(exam.duration_minutes) }}</span>
                                </div>
                                <div class="text-center border-x border-slate-50">
                                    <span class="text-[9px] uppercase font-bold text-slate-400 block mb-0.5">Tentatives</span>
                                    <span class="text-xs font-black text-slate-700">{{ exam.remaining_attempts }}</span>
                                </div>
                                <div class="text-center">
                                    <span class="text-[9px] uppercase font-bold text-slate-400 block mb-0.5">Passantes</span>
                                    <span class="text-xs font-black text-slate-700">{{ exam.passing_score ?? 70 }}%</span>
                                </div>
                            </div>

                            <!-- Score si existant -->
                            <div v-if="exam.best_score !== null && exam.best_score !== undefined" class="mb-3">
                                <div class="flex justify-between text-[10px] font-black mb-1.5">
                                    <span class="text-slate-500">Meilleur Score</span>
                                    <span :class="exam.is_passed ? 'text-emerald-600' : 'text-rose-600'">{{ exam.best_score }}%</span>
                                </div>
                                <ProgressBar
                                    :value="exam.best_score"
                                    :showValue="false"
                                    class="h-1.5"
                                    :style="`--p-progressbar-value-background: ${exam.is_passed ? '#10B981' : '#F43F5E'}`"
                                />
                            </div>
                        </div>

                        <!-- ── FOOTER ACTIONS ── -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                            <!-- Reprendre -->
                            <Link v-if="exam.active_attempt_uuid" :href="route('exams.take', exam.id)" class="flex-1">
                                <Button label="Reprendre" icon="pi pi-play" class="w-full p-button-warning font-bold text-sm" />
                            </Link>

                            <!-- Commencer -->
                            <Link v-else-if="exam.remaining_attempts !== 0 && !exam.is_passed" :href="route('exams.take', exam.id)" class="flex-1">
                                <Button label="Commencer" icon="pi pi-external-link" class="w-full font-bold text-sm" />
                            </Link>

                            <!-- Déjà passé -->
                            <div v-else class="flex-1">
                                <Button
                                    :label="exam.is_passed ? '✓ Certifié' : 'Limite atteinte'"
                                    disabled
                                    icon="pi pi-lock"
                                    :class="[
                                        'w-full text-sm',
                                        exam.is_passed ? 'p-button-success p-button-outlined' : 'p-button-secondary opacity-60'
                                    ]"
                                />
                            </div>

                            <!-- Voir résultats -->
                            <Link v-if="exam.results && exam.results.length > 0" :href="route('exams.results', exam.results[0].id)">
                                <Button icon="pi pi-chart-bar" severity="secondary" text
                                        v-tooltip.top="'Voir mes résultats'"
                                        class="text-sm" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ── ÉTAT VIDE ── -->
                <div v-else class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="w-20 h-20 rounded-3xl bg-violet-50 flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-pencil text-4xl text-violet-300"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-700 italic mb-2">
                        {{ search ? 'Aucun résultat trouvé' : 'Aucun examen disponible' }}
                    </h3>
                    <p class="text-slate-400 text-sm mb-6">
                        {{ search ? `Aucun examen ne correspond à "${search}"` : 'Vous n\'êtes inscrit à aucun cours avec évaluation pour le moment.' }}
                    </p>
                    <Link :href="route('student.my-courses')">
                        <Button label="Voir mes formations" icon="pi pi-book" iconPos="left" class="p-button-primary font-bold" />
                    </Link>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar-value) { transition: width 0.5s ease; }
</style>
