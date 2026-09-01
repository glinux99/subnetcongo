<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - STUDENT LEARNING COCKPIT v4.5.0 (Pro Edition)
 * ======================================================================================
 * 
 * Interface centreé sur l'apprenant :
 * - Suivi de progression dynamique
 * - Moteur de gamification (XP & Streaks)
 * - Timeline d'activité réelle
 */

import { ref, computed, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';

// --- PRIMEVUE COMPONENTS ---
import Chart from 'primevue/chart';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Knob from 'primevue/knob';
import Timeline from 'primevue/timeline';
import { useToast } from "primevue/usetoast";

// ======================================================================================
// 1. PROPS (Synchronisées avec DashboardController.php)
// ======================================================================================
const props = defineProps({
    auth: Object,
    meta: Object,           // { greeting, message }
    stats: Object,          // { xp, streak, certificates, hours_studied, rank, completion_rate }
    myCourses: Array,       // [{ id, title, instructor, progress, last_accessed, image, next_lesson }]
    activityChart: Object,  // { labels: [], data: [] }
    recentActivities: Array,// [{ event, detail, time, type, icon }]
    leaderboard: Array      // [{ rank, name, avatar, score, trend }]
});

// ======================================================================================
// 2. ÉTAT ET LOGIQUE
// ======================================================================================
const toast = useToast();
const isLoading = ref(true);

/**
 * Calcul du cours prioritaire (le premier de la liste fournie par le controller)
 */
const continueLearning = computed(() => {
    if (!props.myCourses || props.myCourses.length === 0) return null;
    return props.myCourses[0]; 
});

/**
 * Configuration du graphique d'assiduité
 */
const activityData = computed(() => ({
    labels: props.activityChart?.labels || [],
    datasets: [{
        label: 'Heures d\'étude',
        data: props.activityChart?.data || [],
        fill: true,
        borderColor: '#8B5CF6',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        tension: 0.4,
        pointRadius: 2
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { 
        y: { display: false, min: 0 },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10, weight: '700' } } }
    }
};

/**
 * Helper pour les images (Storage Laravel)
 */
const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

onMounted(() => {
    setTimeout(() => isLoading.value = false, 600);
});
</script>

<template>
    <AppLayout>
        <Head title="Mon Cockpit d'Apprentissage" />
        <Toast />

        <div class="min-h-screen bg-[#F8FAFC] pb-24 font-sans">
            
            <!-- --- [SECTION 1] HEADER ÉLÉGANT --- -->
            <header class="bg-white border-b border-slate-200 sticky top-0 z-40 backdrop-blur-md bg-white/80">
                <div class="max-w-[1600px] mx-auto px-6 py-6 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="relative">
                            <Avatar :image="getImageUrl(auth.user.avatar)" shape="circle" size="xlarge" class="shadow-xl border-2 border-violet-100" />
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 border-4 border-white rounded-full"></div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-slate-800 tracking-tight leading-none">
                                {{ props.meta.greeting }}, {{ auth.user.name.split(' ')[0] }} !
                            </h1>
                            <p class="text-sm font-bold text-slate-400 mt-2 italic flex items-center gap-2">
                                <i class="pi pi-sparkles text-violet-500"></i>
                                {{ props.meta.message }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Points XP & Streaks -->
                        <div class="flex items-center bg-slate-900 px-5 py-3 rounded-2xl shadow-lg shadow-slate-200">
                            <div class="flex items-center gap-3 pr-5 border-r border-slate-700">
                                <i class="pi pi-bolt text-amber-400 text-lg animate-pulse"></i>
                                <div class="flex flex-col">
                                    <span class="text-white font-black leading-none">{{ props.stats.xp }}</span>
                                    <span class="text-[9px] text-slate-500 uppercase font-bold tracking-widest">Total XP</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 pl-5">
                                <i class="pi pi-calendar text-rose-500 text-lg"></i>
                                <div class="flex flex-col">
                                    <span class="text-white font-black leading-none">{{ props.stats.streak }}</span>
                                    <span class="text-[9px] text-slate-500 uppercase font-bold tracking-widest">Jours</span>
                                </div>
                            </div>
                        </div>
                        <Button icon="pi pi-bell" class="p-button-rounded p-button-secondary p-button-outlined border-slate-200 bg-white" />
                    </div>
                </div>
            </header>

            <div class="max-w-[1600px] mx-auto px-6 mt-10">
                
                <!-- --- [SECTION 2] KPI GRID --- -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                    <div v-for="(stat, key) in [
                        {label: 'Cours en cours', val: props.myCourses.length, icon: 'pi-book', color: 'violet'},
                        {label: 'Certificats obtenus', val: props.stats.certificates, icon: 'pi-verified', color: 'emerald'},
                        {label: 'Heures d\'étude', val: props.stats.hours_studied + 'h', icon: 'pi-clock', color: 'amber'},
                        {label: 'Rang global', val: '#' + props.stats.rank, icon: 'pi-trophy', color: 'rose'}
                    ]" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center gap-6 group hover:border-violet-300 transition-colors">
                        <div :class="`w-14 h-14 bg-${stat.color}-50 text-${stat.color}-600 rounded-2xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform`">
                            <i :class="`pi ${stat.icon} text-2xl`"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">{{ stat.label }}</p>
                            <h3 class="text-2xl font-black text-slate-800 italic">{{ stat.val }}</h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <!-- --- [COLONNE GAUCHE] --- -->
                    <div class="lg:col-span-8 space-y-10">
                        
                        <!-- HERO : CONTINUE LEARNING -->
                        <div v-if="continueLearning" class="bg-slate-900 rounded-[3rem] p-10 text-white relative overflow-hidden group shadow-2xl min-h-[400px] flex flex-col justify-end">
                            <img :src="getImageUrl(continueLearning.image)" class="absolute inset-0 w-full h-full object-cover opacity-30 group-hover:scale-105 transition-transform duration-1000" />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                            
                            <div class="relative z-10 space-y-8">
                                <div class="flex items-center gap-4">
                                    <Tag value="REPRENDRE" severity="info" class="text-[10px] font-black px-4 py-2 bg-violet-600 border-none" />
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Dernier accès : {{ continueLearning.last_accessed }}</span>
                                </div>
                                
                                <h2 class="text-5xl font-black italic tracking-tighter max-w-2xl leading-none">{{ continueLearning.title }}</h2>
                                
                                <div class="p-8 bg-white/5 backdrop-blur-xl rounded-[2rem] border border-white/10 max-w-2xl">
                                    <div class="flex justify-between items-center mb-4">
                                        <div class="space-y-1">
                                            <p class="text-[10px] font-black text-violet-400 uppercase tracking-widest">Prochaine étape</p>
                                            <p class="text-lg font-bold italic text-white line-clamp-1">{{ continueLearning.next_lesson }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-3xl font-black italic">{{ continueLearning.progress }}%</span>
                                        </div>
                                    </div>
                                    <ProgressBar :value="continueLearning.progress" :showValue="false" class="h-2 bg-white/10 border-none" color="#8B5CF6" />
                                </div>

                                <Link :href="route('courses.show', continueLearning.id)" class="inline-block">
                                    <Button label="Lancer le player" icon="pi pi-play-circle" class="p-button-lg p-button-primary rounded-2xl font-black shadow-xl px-10 py-5" />
                                </Link>
                            </div>
                        </div>

                        <!-- LISTE DES COURS ACTIFS -->
                        <section>
                            <div class="flex justify-between items-center mb-8">
                                <h2 class="text-2xl font-black text-slate-800 italic flex items-center gap-3">
                                    <span class="w-2 h-8 bg-violet-600 rounded-full"></span>
                                    Mes Formations
                                </h2>
                                <Link  class="text-sm font-black text-violet-600 uppercase tracking-widest hover:underline">Tout voir</Link>
                            </div>
                            
                            <div v-if="props.myCourses.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div v-for="course in props.myCourses" :key="course.id" 
                                     class="bg-white p-6 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-2xl transition-all duration-500 group relative">
                                    <div class="flex gap-6">
                                        <div class="w-24 h-24 rounded-3xl overflow-hidden shadow-lg flex-shrink-0">
                                            <img :src="getImageUrl(course.image)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-black text-slate-800 text-base leading-tight mb-1 truncate italic" :title="course.title">{{ course.title }}</h4>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-4">{{ course.instructor }}</p>
                                            
                                            <div class="space-y-2">
                                                <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase tracking-tighter">
                                                    <span>Progression</span>
                                                    <span class="text-violet-600">{{ course.progress }}%</span>
                                                </div>
                                                <ProgressBar :value="course.progress" :showValue="false" class="h-1.5 bg-slate-100" color="#8B5CF6" />
                                            </div>
                                        </div>
                                    </div>
                                    <Link :href="route('courses.show', course.id)" class="absolute inset-0 z-10"></Link>
                                </div>
                            </div>
                            <div v-else class="bg-slate-100/50 border-2 border-dashed border-slate-200 rounded-[3rem] p-20 text-center">
                                <i class="pi pi-folder-open text-5xl text-slate-300 mb-4"></i>
                                <p class="text-slate-500 font-bold italic">Vous n'avez pas encore de cours inscrits.</p>
                                <Button label="Explorer le catalogue" class="mt-6 p-button-text font-black" />
                            </div>
                        </section>

                        <!-- ASSIDUITÉ CHART -->
                        <div class="bg-white rounded-[3rem] p-10 border border-slate-200 shadow-sm">
                            <h2 class="text-xl font-black text-slate-800 italic mb-10 flex items-center gap-3">
                                <i class="pi pi-chart-line text-emerald-500"></i>
                                Mon Assiduité (7 derniers jours)
                            </h2>
                            <div class="h-56 w-full">
                                <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" />
                            </div>
                        </div>

                    </div>

                    <!-- --- [COLONNE DROITE] --- -->
                    <aside class="lg:col-span-4 space-y-10">
                        
                        <!-- SCORE DE MAÎTRISE -->
                        <div class="bg-white rounded-[3rem] p-10 border border-slate-200 shadow-sm text-center">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-10">Maîtrise Globale</h3>
                            <div class="flex justify-center mb-8 relative">
                                <Knob v-model="props.stats.completion_rate" :size="180" valueColor="#8B5CF6" rangeColor="#F1F5F9" :strokeWidth="12" readonly valueTemplate="{value}%" />
                                <!-- Petit badge flottant -->
                                <div class="absolute -top-2 right-1/4 bg-violet-600 text-white w-10 h-10 rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                                    <i class="pi pi-bolt text-xs"></i>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-slate-500 italic px-4 leading-relaxed">
                                "L'éducation est l'arme la plus puissante pour changer le monde."
                            </p>
                        </div>

                        <!-- CLASSEMENT ÉLITE (Leaderboard) -->
                        <div class="bg-slate-900 rounded-[3rem] p-10 text-white shadow-2xl">
                            <h3 class="text-lg font-black italic mb-8 flex items-center gap-3">
                                <i class="pi pi-trophy text-amber-400"></i> Hall of Fame SNC
                            </h3>
                            <div class="space-y-6">
                                <div v-for="(student, idx) in props.leaderboard" :key="idx" 
                                     class="flex items-center justify-between p-4 rounded-3xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all cursor-pointer group">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm font-black italic text-slate-600 w-4">{{ student.rank }}</span>
                                        <Avatar :image="getImageUrl(student.avatar)" shape="circle" size="large" class="border border-white/20" />
                                        <div>
                                            <p class="text-sm font-black italic group-hover:text-violet-400 transition-colors">{{ student.name }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <i :class="`pi pi-arrow-${student.trend} text-[8px] text-${student.trend === 'up' ? 'emerald' : 'rose'}-500`"></i>
                                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">SNC Rank</span>
                                            </div>
                                        </div>
                                    </div>
                                    <Badge :value="student.score + ' XP'" severity="info" class="text-[10px] font-black py-2 px-3 bg-violet-900/40 border-none text-violet-300" />
                                </div>
                            </div>
                        </div>

                        <!-- TIMELINE ACTIVITÉ -->
                        <div class="bg-white rounded-[3rem] p-10 border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-black text-slate-800 italic mb-10 flex justify-between items-center">
                                Flux d'activité
                                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></span>
                            </h3>
                            <Timeline :value="props.recentActivities" class="custom-timeline">
                                <template #marker="slotProps">
                                    <span class="flex w-10 h-10 items-center justify-center text-white rounded-2xl shadow-lg rotate-12 group-hover:rotate-0 transition-transform" :class="`bg-${slotProps.item.type === 'success' ? 'emerald' : slotProps.item.type === 'info' ? 'violet' : 'amber'}-500`">
                                        <i :class="`pi ${slotProps.item.icon} text-xs`"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-10 ml-4">
                                        <p class="text-sm font-black text-slate-800 leading-tight">{{ slotProps.item.event }}</p>
                                        <p class="text-[11px] text-slate-400 mt-1 font-medium italic line-clamp-2">{{ slotProps.item.detail }}</p>
                                        <span class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em]">{{ slotProps.item.time }}</span>
                                    </div>
                                </template>
                            </Timeline>
                        </div>

                    </aside>
                </div>

            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
/* --- CUSTOM STYLES FOR STUDENT PRO --- */

/* Timeline student overrides */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    padding-left: 0;
}

/* Knob Center Text Styling */
:deep(.p-knob-text) {
    font-weight: 900 !important;
    font-style: italic !important;
    font-family: 'Inter', sans-serif !important;
    fill: #1e293b !important;
}

/* Tracking tighter for italics */
.tracking-tighter { letter-spacing: -0.06em; }

/* Pulse animation for XP Bolt */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1.1); }
    50% { opacity: .7; transform: scale(1); }
}

/* Scrollbar clean */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>