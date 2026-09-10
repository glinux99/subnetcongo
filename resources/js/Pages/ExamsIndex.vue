<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT EXAM CENTER v3.0 (Ultimate Edition)
 * ======================================================================================
 */
import { ref, computed } from 'vue';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    exams: Array
});

// Filtres
const search = ref('');
const activeFilter = ref('all');

const filteredExams = computed(() => {
    let list = props.exams || [];
    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(e => e.title?.toLowerCase().includes(s) || e.course?.title?.toLowerCase().includes(s));
    }
    if (activeFilter.value === 'available') list = list.filter(e => !e.is_passed && e.remaining_attempts !== 0);
    else if (activeFilter.value === 'passed') list = list.filter(e => e.is_passed);
    else if (activeFilter.value === 'failed') list = list.filter(e => !e.is_passed && e.attempts_count > 0 && e.remaining_attempts === 0);
    return list;
});

const stats = computed(() => ({
    total: props.exams?.length ?? 0,
    available: props.exams?.filter(e => !e.is_passed && e.remaining_attempts !== 0).length ?? 0,
    passed: props.exams?.filter(e => e.is_passed).length ?? 0,
    locked: props.exams?.filter(e => e.attempts_count > 0 && e.remaining_attempts === 0).length ?? 0,
}));

const getStatus = (exam) => {
    if (exam.is_passed) return { label: 'Réussi', sev: 'success', icon: 'pi-verified', bg: 'bg-emerald-500' };
    if (exam.active_attempt_uuid) return { label: 'En cours', sev: 'warn', icon: 'pi-spin pi-spinner', bg: 'bg-amber-500' };
    if (exam.remaining_attempts === 0) return { label: 'Verrouillé', sev: 'danger', icon: 'pi-lock', bg: 'bg-rose-600' };
    return { label: 'Disponible', sev: 'info', icon: 'pi-play', bg: 'bg-red-600' };
};

const formatDuration = (min) => min ? (min < 60 ? `${min} min` : `${Math.floor(min/60)}h${min%60 || ''}`) : '—';
</script>

<template>
    <AppLayout>
        <Head title="Centre d'Évaluation | SubnetCongo" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ── HEADER ULTIMATE V30 (Mesh Gradient) ── -->
            <div class="bg-[#0F172A] pt-12 pb-24 px-6 lg:px-12 relative overflow-hidden">
                <div class="absolute top-[-10%] right-[-10%] w-[600px] h-[600px] bg-red-600/15 rounded-full blur-[120px] pointer-events-none"></div>
                <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-red-900/10 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-red-500/20">Evaluation Center</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic uppercase leading-none">
                            Centre <span class="text-red-600">d'Évaluation</span>
                        </h1>
                        <p class="text-slate-400 mt-4 max-w-lg text-sm font-medium leading-relaxed italic">
                            Validez vos compétences, obtenez vos certifications et suivez votre progression académique.
                        </p>
                    </div>

                    <!-- Search Pro -->
                    <div class="w-full md:w-96">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search text-slate-500" />
                            <InputText v-model="search" placeholder="Rechercher un examen..." 
                                class="w-full !bg-white/5 !backdrop-blur-md !border-white/10 !rounded-xl !py-4 !pl-12 !text-white placeholder:!text-slate-500 focus:!border-red-500/50 transition-all" />
                        </IconField>
                    </div>
                </div>
            </div>

            <div class="max-w-11xl mx-auto px-6 -mt-12 relative z-20">
                
                <!-- ── KPI CARDS ── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <div v-for="kpi in [
                        { label: 'Total Examens', val: stats.total, icon: 'pi-file', bg: 'bg-slate-100 text-slate-600' },
                        { label: 'Disponibles', val: stats.available, icon: 'pi-play-circle', bg: 'bg-red-50 text-red-600' },
                        { label: 'Certifications', val: stats.passed, icon: 'pi-verified', bg: 'bg-emerald-50 text-emerald-600' },
                        { label: 'Verrouillés', val: stats.locked, icon: 'pi-lock', bg: 'bg-rose-50 text-rose-600' },
                    ]" :key="kpi.label" class="bg-white p-6 rounded-[1rem] border border-slate-200/50 shadow-sm hover:shadow-xl transition-all group">
                        <div :class="kpi.bg" class="w-10 h-10 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i :class="`pi ${kpi.icon}`"></i>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ kpi.label }}</p>
                        <h3 class="text-2xl font-black text-slate-900">{{ kpi.val }}</h3>
                    </div>
                </div>

                <!-- ── FILTRES ── -->
                <div class="flex gap-2 mb-8 bg-white p-2 rounded-xl border border-slate-200 w-fit shadow-sm">
                    <button v-for="f in [{k:'all',l:'Tous'}, {k:'available',l:'À passer'}, {k:'passed',l:'Réussis'}, {k:'failed',l:'Échoués'}]" :key="f.k"
                        @click="activeFilter = f.k"
                        :class="['px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', 
                        activeFilter === f.k ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'text-slate-500 hover:bg-slate-50']">
                        {{ f.l }}
                    </button>
                </div>

                <!-- ── GRILLE EXAMENS ── -->
                <div v-if="filteredExams.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="exam in filteredExams" :key="exam.id" 
                        class="group bg-white rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:border-red-100 transition-all duration-500 flex flex-col overflow-hidden">
                        
                        <!-- Status Header -->
                        <div :class="getStatus(exam).bg" class="h-1.5 w-full"></div>

                        <div class="p-8 flex-1 space-y-6">
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-red-600 uppercase tracking-[0.2em]">{{ exam.type || 'EXAMEN' }}</span>
                                    <h3 class="text-lg font-black text-slate-800 leading-tight italic tracking-tight line-clamp-2">{{ exam.title }}</h3>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 shadow-inner">
                                    <i :class="getStatus(exam).icon" class="text-slate-400"></i>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-xs font-bold text-slate-500 italic">
                                <i class="pi pi-book text-red-500"></i>
                                <span class="truncate">{{ exam.course?.title || 'Formation générale' }}</span>
                            </div>

                            <!-- Meta Grid -->
                            <div class="grid grid-cols-3 gap-4 py-4 border-y border-slate-50">
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Durée</p>
                                    <p class="text-xs font-black text-slate-900">{{ formatDuration(exam.duration_minutes) }}</p>
                                </div>
                                <div class="text-center border-x border-slate-50">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Pass</p>
                                    <p class="text-xs font-black text-slate-900">{{ exam.passing_score ?? 70 }}%</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Tentatives</p>
                                    <p class="text-xs font-black text-slate-900">{{ exam.remaining_attempts }}</p>
                                </div>
                            </div>

                            <!-- Score Visual -->
                            <div v-if="exam.best_score !== null">
                                <div class="flex justify-between items-end mb-2">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Performance</p>
                                    <span :class="exam.is_passed ? 'text-emerald-500' : 'text-red-600'" class="text-sm font-black italic tracking-tighter">{{ exam.best_score }}%</span>
                                </div>
                                <ProgressBar :value="exam.best_score" :showValue="false" class="h-1.5" 
                                    :style="`--p-progressbar-value-background: ${exam.is_passed ? '#10B981' : '#E11D48'}`" />
                            </div>
                        </div>

                        <!-- ── ACTIONS ── -->
                        <div class="p-6 bg-slate-50/50 border-t border-slate-50 flex gap-3">
                            <Link v-if="exam.active_attempt_uuid" :href="route('exams.take', exam.id)" class="flex-1">
                                <Button label="Reprendre" icon="pi pi-spin pi-spinner" class="w-full !bg-amber-500 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            </Link>

                            <Link v-else-if="exam.remaining_attempts !== 0 && !exam.is_passed" :href="route('exams.take', exam.id)" class="flex-1">
                                <Button label="Lancer l'examen" icon="pi pi-play-circle" class="w-full !bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest transition-all active:scale-95 shadow-lg shadow-red-600/20" />
                            </Link>

                            <div v-else class="flex-1">
                                <Button :label="exam.is_passed ? 'Déjà Réussi' : 'Verrouillé'" icon="pi pi-lock" disabled 
                                    class="w-full !bg-slate-200 !text-slate-500 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest opacity-60" />
                            </div>

                            <Link v-if="exam.results?.length" :href="route('exams.attempts', exam.id)">
                                <Button icon="pi pi-history" text class="!text-slate-400 hover:!text-indigo-600" v-tooltip="'Historique des tentatives'" />
                            </Link>
                            <Link v-if="exam.results?.length" :href="route('exams.results', exam.results[0].id)">
                                <Button icon="pi pi-chart-bar" text class="!text-slate-400 hover:!text-red-600" v-tooltip="'Mes performances'" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ── EMPTY STATE ── -->
                <div v-else class="py-24 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-200 shadow-inner">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                        <i class="pi pi-file-edit text-slate-300 text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight italic uppercase">Aucun examen</h3>
                    <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto font-medium leading-relaxed">
                        {{ search ? `Aucune évaluation ne correspond à "${search}".` : 'Inscrivez-vous à des formations pour débloquer vos examens.' }}
                    </p>
                    <Link :href="route('student.my-courses')" class="mt-8 inline-block">
                        <Button label="Voir mes formations" icon="pi pi-book" class="!bg-red-600 !border-none !rounded-xl !px-10 !py-4 !text-xs !font-black !uppercase !tracking-widest shadow-xl" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar) { background: #F1F5F9; border-radius: 99px; }
:deep(.p-progressbar-value) { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
</style>