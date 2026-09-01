<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT LEARNING COCKPIT v7.0
 * ======================================================================================
 * Dashboard centralisé pour l'apprenant :
 * - Suivi de progression réelle depuis BDD
 * - Téléchargement des supports de cours
 * - Lecture des contenus vidéo (player intégré / redirection)
 * - Modules d'examens (à venir, en cours, terminés)
 * - Téléchargement des certificats
 * - Hub de ressources par cours + timeline d'activité
 *
 * Refonte visuelle : identité "réseau / topologie" adaptée au sujet (formation
 * réseaux & IT). Un seul accent fort (bleu réseau) porte la hiérarchie ; le reste
 * reste sobre. Les chiffres (XP, %, durées) utilisent une police mono, le texte
 * courant une police humaniste. Pas de logique métier modifiée — uniquement la
 * structure visuelle, l'accessibilité et l'état de chargement.
 */

import { ref, computed, onMounted, watch } from 'vue';
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
import Toast from 'primevue/toast';
import Skeleton from 'primevue/skeleton';
import Tooltip from 'primevue/tooltip';
import { useToast } from 'primevue/usetoast';

const vTooltip = Tooltip;

// ======================================================================================
// 1. PROPS (Synchronisées avec DashboardController::studentDashboard())
// ======================================================================================
const props = defineProps({
    auth: Object,
    meta: Object,           // { greeting, message }
    stats: Object,          // { xp, streak, certificates, hours_studied, rank, completion_rate }
    myCourses: Array,       // [{ id, title, instructor, progress, last_accessed, image, next_lesson, enrollment_id, status, level }]
    activityChart: Object,  // { labels: [], data: [] }
    recentActivities: Array,// [{ event, detail, time, type, icon }]
    leaderboard: Array,     // [{ rank, name, avatar, score, trend }]
    certificates: Array,    // [{ id, course_title, score, issued_at, download_url }]
    upcomingExams: { type: Array, default: () => [] },        // [{ id, title, course_id, course_title, duration_min, questions_count, status, scheduled_at }]
    inProgressExams: { type: Array, default: () => [] },      // [{ id, title, course_title, attempt_uuid }]
    courseResources: { type: Object, default: () => ({}) },   // { [courseId]: { materials: [], videos: [] } }
});

// ======================================================================================
// 2. ÉTAT ET LOGIQUE (inchangée — voir styles/template pour la refonte)
// ======================================================================================
const toast = useToast();
const isLoading = ref(true);
const completionRate = ref(0);
const selectedCourse = ref(null);
const showResourceDialog = ref(false);
const showVideoDialog = ref(false);
const activeVideo = ref(null);

/**
 * Cours prioritaire à reprendre (le premier avec progression non-complète)
 */
const continueLearning = computed(() => {
    if (!props.myCourses || props.myCourses.length === 0) return null;
    const inProgress = props.myCourses.find(c => (c.progress ?? 0) < 100 && c.next_lesson);
    return inProgress || props.myCourses[0];
});

/**
 * Cours restants (sans le hero)
 */
const otherCourses = computed(() => {
    if (!continueLearning.value) return props.myCourses ?? [];
    return (props.myCourses ?? []).filter(c => c.id !== continueLearning.value.id).slice(0, 4);
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
        borderColor: '#3E5EFF',
        backgroundColor: 'rgba(62, 94, 255, 0.10)',
        tension: 0.35,
        borderWidth: 2,
        pointBackgroundColor: '#3E5EFF',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 1.5,
        pointRadius: 3,
        pointHoverRadius: 5,
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
        y: { display: false, min: 0 },
        x: {
            grid: { display: false },
            ticks: { color: '#8A93A6', font: { size: 11, family: 'Inter, sans-serif' } }
        }
    }
};

/**
 * Détermine l'URL correcte d'une leçon
 */
const getLessonPlayerUrl = (course) => {
    if (!course || !course.next_lesson) return null;
    return route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id ?? course.next_lesson });
};

/**
 * Helper pour les images (Storage Laravel)
 */
const getImageUrl = (path) => {
    if (!path) return null;
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const getProgressColor = (p) => {
    if (p >= 80) return '#2FBF71'; // succès
    if (p >= 50) return '#F2A93B'; // en cours avancé
    return '#3E5EFF'; // en cours
};

const getLevelLabel = (level) => {
    const map = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', all: 'Tous niveaux' };
    return map[level] || level || 'Général';
};

const getResourceTypeIcon = (type) => {
    const map = {
        pdf: 'pi-file-pdf text-rose-500',
        doc: 'pi-file-word text-sky-500',
        docx: 'pi-file-word text-sky-500',
        xls: 'pi-file-excel text-emerald-500',
        xlsx: 'pi-file-excel text-emerald-500',
        ppt: 'pi-file-powerpoint text-amber-500',
        pptx: 'pi-file-powerpoint text-amber-500',
        zip: 'pi-box text-indigo-500',
        mp4: 'pi-video text-indigo-500',
        video: 'pi-video text-indigo-500',
    };
    return map[(type || '').toLowerCase()] || 'pi-file text-slate-400';
};

const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) return '—';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
};

const formatExamDate = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const getResources = (courseId) => props.courseResources?.[courseId] ?? { materials: [], videos: [] };

const allCertificates = computed(() => props.certificates ?? []);

const totalMaterials = computed(() => Object.values(props.courseResources ?? {}).reduce(
    (acc, res) => acc + (res?.materials?.length || 0), 0
));

const totalVideos = computed(() => Object.values(props.courseResources ?? {}).reduce(
    (acc, res) => acc + (res?.videos?.length || 0), 0
));

const courseProgressBreakdown = computed(() => (props.myCourses ?? []).map(c => ({
    id: c.id,
    title: c.title,
    progress: Math.min(100, Math.max(0, Math.round(c.progress ?? 0))),
    status: (c.progress ?? 0) >= 100 ? 'completed' : (c.progress ?? 0) >= 50 ? 'advanced' : 'in-progress',
    lessonsDone: c.lessons_done ?? null,
    lessonsTotal: c.lessons_total ?? null,
})));

const skillDistribution = computed(() => {
    const buckets = { 'Débutant': 0, 'Intermédiaire': 0, 'Avancé': 0, 'Terminé': 0 };
    (props.myCourses ?? []).forEach(c => {
        const p = c.progress ?? 0;
        if (p >= 100) buckets['Terminé']++;
        else if (p >= 70) buckets['Avancé']++;
        else if (p >= 30) buckets['Intermédiaire']++;
        else buckets['Débutant']++;
    });
    return buckets;
});

const skillChartData = computed(() => ({
    labels: Object.keys(skillDistribution.value),
    datasets: [{
        data: Object.values(skillDistribution.value),
        backgroundColor: ['#C7D2FE', '#F2A93B', '#3E5EFF', '#2FBF71'],
        borderWidth: 0,
    }]
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
        legend: { position: 'bottom', labels: { font: { size: 11, family: 'Inter, sans-serif' }, color: '#475569', boxWidth: 8, usePointStyle: true, pointStyle: 'circle' } }
    }
};

// Statut lisible pour les examens (évite d'exposer les valeurs techniques brutes)
const examStatusLabel = (status) => ({
    ready: 'Prêt',
    locked: 'Verrouillé',
    pending: 'En attente',
}[status] || status);

// --- ACTIONS ---

const downloadMaterial = (material) => {
    const url = material?.download_url ?? material?.url;
    if (!url) {
        toast.add({ severity: 'warn', summary: 'Support indisponible', detail: 'Ce fichier n\'est pas encore téléchargeable.', life: 3500 });
        return;
    }
    const a = document.createElement('a');
    a.href = url;
    a.target = '_blank';
    a.rel = 'noopener';
    a.download = material?.filename ?? material?.title ?? 'support';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast.add({ severity: 'success', summary: 'Téléchargement lancé', detail: material?.title ?? 'Support de cours', life: 2500 });
};

const openCourseHub = (course) => {
    selectedCourse.value = course;
    showResourceDialog.value = true;
};

const playVideo = (video, course = null) => {
    activeVideo.value = { ...video, course };
    showVideoDialog.value = true;
};

const openVideoInPlayer = (video, course) => {
    showVideoDialog.value = false;
    const lessonId = video?.lesson_id ?? video?.id;
    if (!course || !lessonId) {
        toast.add({ severity: 'warn', summary: 'Vidéo indisponible', life: 2500 });
        return;
    }
    router.visit(route('student.lesson-player', { course: course.id, lesson: lessonId }));
};

const downloadCertificate = (cert) => {
    const url = cert?.download_url ?? (cert?.id ? route('certificates.download', cert.id) : null);
    if (!url) {
        toast.add({ severity: 'warn', summary: 'Certificat indisponible', life: 2500 });
        return;
    }
    window.open(url, '_blank', 'noopener');
    toast.add({ severity: 'success', summary: 'Certificat téléchargé', detail: cert?.course_title ?? 'Certificat', life: 2500 });
};

const startExam = (exam) => {
    if (!exam?.id) return;
    if (exam?.attempt_uuid) {
        router.visit(route('exams.take', exam.id));
    } else {
        router.post(route('attempts.start', exam.id));
    }
};

const resumeExam = (exam) => {
    if (!exam?.id) return;
    router.visit(route('exams.take', exam.id));
};

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
        completionRate.value = Math.round(props.stats?.completion_rate ?? 0);
    }, 350);
});

watch(() => props.stats?.completion_rate, (v) => {
    completionRate.value = Math.round(v ?? 0);
});
</script>

<template>
    <AppLayout>
        <Head title="Mon tableau de bord" />
        <Toast />

        <div class="min-h-screen bg-[#F5F6FA] pb-24 font-sans text-[#12172B]">

            <!-- ═══════════════════════════════════════════════════
                 HEADER
            ═══════════════════════════════════════════════════ -->
            <header class="bg-white border-b border-[#E4E7F0] sticky top-0 z-40">
                <div class="max-w-[1600px] mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="relative shrink-0">
                            <Avatar
                                :label="auth?.user?.name?.charAt(0)?.toUpperCase()"
                                :image="auth?.user?.profile_photo_url"
                                shape="circle"
                                size="large"
                                class="border-2 border-[#E4E7F0] bg-[#EEF1FF] text-[#3E5EFF] font-semibold"
                            />
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-[#2FBF71] border-2 border-white rounded-full" aria-hidden="true"></span>
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl font-semibold text-[#12172B] tracking-tight leading-tight truncate font-display">
                                {{ meta?.greeting || 'Bonjour' }}, {{ auth?.user?.name?.split(' ')[0] }}
                            </h1>
                            <p class="text-sm text-[#6B7280] mt-0.5 truncate">
                                {{ meta?.message || 'Reprenons où vous en étiez.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Nav rapide -->
                    <nav class="flex items-center gap-1.5 flex-wrap justify-center" aria-label="Navigation rapide">
                        <Link :href="route('student.my-courses')" class="quick-nav-link">
                            <i class="pi pi-book text-sm" aria-hidden="true"></i> Mes cours
                        </Link>
                        <Link :href="route('exams.learner.index')" class="quick-nav-link">
                            <i class="pi pi-pencil text-sm" aria-hidden="true"></i> Examens
                        </Link>
                        <Link :href="route('student.certificates')" class="quick-nav-link">
                            <i class="pi pi-verified text-sm" aria-hidden="true"></i> Certificats
                        </Link>
                        <Link :href="route('student.progress')" class="quick-nav-link">
                            <i class="pi pi-chart-line text-sm" aria-hidden="true"></i> Progression
                        </Link>

                        <!-- XP & Streak -->
                        <div class="flex items-stretch bg-[#12172B] rounded-lg overflow-hidden ml-2 font-mono text-sm">
                            <div class="flex items-center gap-2 px-3.5 py-2 border-r border-white/10" v-tooltip.bottom="'Points d\'expérience'">
                                <i class="pi pi-bolt text-[#F2A93B] text-xs" aria-hidden="true"></i>
                                <span class="text-white font-semibold">{{ stats?.xp ?? 0 }}</span>
                                <span class="text-white/40 text-[11px]">xp</span>
                            </div>
                            <div class="flex items-center gap-2 px-3.5 py-2" v-tooltip.bottom="'Jours consécutifs'">
                                <i class="pi pi-calendar text-[#17B8A6] text-xs" aria-hidden="true"></i>
                                <span class="text-white font-semibold">{{ stats?.streak ?? 0 }}</span>
                                <span class="text-white/40 text-[11px]">jours</span>
                            </div>
                        </div>
                    </nav>
                </div>
            </header>

            <div class="max-w-[1600px] mx-auto px-6 mt-8">

                <!-- ═══════════════════════════════════════════════════
                     ÉTAT DE CHARGEMENT
                ═══════════════════════════════════════════════════ -->
                <div v-if="isLoading" class="space-y-8" aria-busy="true" aria-label="Chargement du tableau de bord">
                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                        <Skeleton v-for="n in 6" :key="n" height="88px" borderRadius="12px" />
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <div class="lg:col-span-8 space-y-8">
                            <Skeleton height="340px" borderRadius="16px" />
                            <Skeleton height="220px" borderRadius="16px" />
                        </div>
                        <div class="lg:col-span-4 space-y-6">
                            <Skeleton height="260px" borderRadius="16px" />
                            <Skeleton height="200px" borderRadius="16px" />
                        </div>
                    </div>
                </div>

                <template v-else>

                <!-- ═══════════════════════════════════════════════════
                     KPI STRIP
                ═══════════════════════════════════════════════════ -->
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
                    <template v-for="stat in [
                        { label: 'Cours inscrits', val: myCourses?.length ?? 0, icon: 'pi-book', href: route('student.my-courses') },
                        { label: 'Supports', val: totalMaterials, icon: 'pi-file', action: 'materials' },
                        { label: 'Vidéos', val: totalVideos, icon: 'pi-video', action: 'materials' },
                        { label: 'Certificats', val: stats?.certificates ?? 0, icon: 'pi-verified', href: route('student.certificates') },
                        { label: 'Heures d\'étude', val: (stats?.hours_studied ?? 0) + 'h', icon: 'pi-clock', href: route('student.progress') },
                        { label: 'Rang', val: '#' + (stats?.rank ?? '—'), icon: 'pi-trophy', href: '#' }
                    ]" :key="stat.label">
                        <Link v-if="stat.href" :href="stat.href" class="kpi-card group">
                            <span class="kpi-icon"><i :class="`pi ${stat.icon}`" aria-hidden="true"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs text-[#6B7280]">{{ stat.label }}</span>
                                <span class="block text-xl font-semibold font-mono tabular-nums">{{ stat.val }}</span>
                            </span>
                        </Link>
                        <button v-else type="button"
                                @click="stat.action === 'materials' && continueLearning && openCourseHub(continueLearning)"
                                class="kpi-card group text-left w-full">
                            <span class="kpi-icon"><i :class="`pi ${stat.icon}`" aria-hidden="true"></i></span>
                            <span class="min-w-0">
                                <span class="block text-xs text-[#6B7280]">{{ stat.label }}</span>
                                <span class="block text-xl font-semibold font-mono tabular-nums">{{ stat.val }}</span>
                            </span>
                        </button>
                    </template>
                </div>

                <!-- ═══════════════════════════════════════════════════
                     LAYOUT PRINCIPAL
                ═══════════════════════════════════════════════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- ── COLONNE GAUCHE ── -->
                    <div class="lg:col-span-8 space-y-8">

                        <!-- HERO : REPRENDRE LE COURS -->
                        <div v-if="continueLearning" class="hero-panel">
                            <div class="hero-grid" aria-hidden="true"></div>

                            <div class="relative z-10 p-8 flex flex-col gap-6">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <span class="inline-flex items-center gap-2 text-[11px] font-medium text-white/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#2FBF71]" aria-hidden="true"></span>
                                        Dernier accès&nbsp;: {{ continueLearning.last_accessed || '—' }}
                                    </span>
                                    <Tag value="En cours" class="!bg-[#3E5EFF] !border-none !text-white !text-[11px] !font-medium" />
                                </div>

                                <div class="flex items-start gap-5 flex-wrap md:flex-nowrap">
                                    <div v-if="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                         class="hidden sm:block w-20 h-20 rounded-xl overflow-hidden shrink-0 ring-1 ring-white/10">
                                        <img :src="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                             :alt="continueLearning.title" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-2xl md:text-3xl font-semibold tracking-tight leading-snug text-white font-display">
                                            {{ continueLearning.title }}
                                        </h2>
                                        <p class="text-sm text-white/50 mt-1">{{ continueLearning.instructor }}</p>
                                    </div>
                                </div>

                                <div class="max-w-lg bg-white/[0.06] rounded-xl border border-white/10 p-5">
                                    <div class="flex justify-between items-start gap-4 mb-3">
                                        <div class="min-w-0">
                                            <p class="text-xs text-white/45 mb-1">Prochaine étape</p>
                                            <p class="text-sm font-medium text-white truncate">
                                                {{ continueLearning.next_lesson?.title ?? continueLearning.next_lesson ?? 'Cours terminé' }}
                                            </p>
                                        </div>
                                        <span class="text-2xl font-semibold font-mono text-white shrink-0">{{ Math.round(continueLearning.progress ?? 0) }}%</span>
                                    </div>
                                    <ProgressBar
                                        :value="continueLearning.progress ?? 0"
                                        :showValue="false"
                                        style="height: 6px"
                                        class="bg-white/10 rounded-full overflow-hidden [&_.p-progressbar-value]:bg-[#3E5EFF]"
                                    />
                                </div>

                                <div class="flex gap-2.5 flex-wrap">
                                    <Link v-if="getLessonPlayerUrl(continueLearning)" :href="getLessonPlayerUrl(continueLearning)">
                                        <Button label="Reprendre la leçon" icon="pi pi-play"
                                                class="!bg-[#3E5EFF] !border-none !rounded-lg !font-medium !px-5 !py-2.5 !text-sm hover:!bg-[#3450DB]" />
                                    </Link>
                                    <Button label="Ressources" icon="pi pi-folder-open"
                                            class="!bg-white/10 !border !border-white/15 !text-white !rounded-lg !font-medium !px-4 !py-2.5 !text-sm hover:!bg-white/15"
                                            @click="openCourseHub(continueLearning)" />
                                    <Link :href="route('student.my-courses')">
                                        <Button label="Tous mes cours" icon="pi pi-th-large"
                                                class="!bg-transparent !border !border-white/15 !text-white/70 !rounded-lg !font-medium !px-4 !py-2.5 !text-sm hover:!bg-white/5" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Aucun cours inscrit -->
                        <div v-else class="bg-white border border-dashed border-[#D7DCE8] rounded-2xl p-14 text-center">
                            <i class="pi pi-folder-open text-4xl text-[#C7CDDB] mb-4 block" aria-hidden="true"></i>
                            <h3 class="text-lg font-semibold text-[#12172B] mb-1.5 font-display">Aucune formation en cours</h3>
                            <p class="text-[#6B7280] text-sm mb-6">Vous n'avez pas encore de cours. Explorez le catalogue pour commencer.</p>
                            <Link :href="route('student.my-courses')">
                                <Button label="Découvrir les formations" icon="pi pi-arrow-right" iconPos="right"
                                        class="!bg-[#3E5EFF] !border-none !rounded-lg !font-medium !text-sm" />
                            </Link>
                        </div>

                        <!-- EXAMENS -->
                        <section v-if="(upcomingExams?.length || inProgressExams?.length) > 0"
                                 class="bg-white rounded-2xl border border-[#E4E7F0] overflow-hidden">
                            <div class="flex justify-between items-center px-6 py-4 border-b border-[#E4E7F0]">
                                <h2 class="text-base font-semibold text-[#12172B] flex items-center gap-2.5 font-display">
                                    <span class="w-1 h-5 bg-[#F2A93B] rounded-full" aria-hidden="true"></span>
                                    Examens
                                </h2>
                                <Link :href="route('exams.learner.index')" class="text-xs font-medium text-[#3E5EFF] hover:underline">
                                    Tout voir
                                </Link>
                            </div>

                            <div v-if="inProgressExams?.length" class="p-6 bg-[#FEF9F0] border-b border-[#F5E6CB]">
                                <p class="text-xs font-medium text-[#8A6A1F] mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-[#F2A93B] rounded-full" aria-hidden="true"></span>
                                    Reprendre où vous étiez
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="exam in inProgressExams" :key="exam.attempt_uuid"
                                         class="bg-white border border-[#F0DFB8] rounded-xl p-4 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 bg-[#FEF3DD] text-[#B8841E] rounded-lg flex items-center justify-center shrink-0">
                                                <i class="pi pi-pencil text-sm" aria-hidden="true"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-[#12172B] truncate">{{ exam.title }}</p>
                                                <p class="text-xs text-[#8A93A6] truncate">{{ exam.course_title }}</p>
                                            </div>
                                        </div>
                                        <Button label="Reprendre" icon="pi pi-play" size="small"
                                                class="!bg-[#F2A93B] !border-none !text-white !font-medium !text-xs shrink-0"
                                                @click="resumeExam(exam)" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="upcomingExams?.length" class="p-6">
                                <p class="text-xs font-medium text-[#6B7280] mb-3">À passer</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="exam in upcomingExams" :key="exam.id"
                                         class="bg-[#FAFBFD] hover:bg-white border border-[#E4E7F0] hover:border-[#C7D2FE] rounded-xl p-4 transition-colors">
                                        <div class="flex justify-between items-start mb-3 gap-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-[#12172B] truncate">{{ exam.title }}</p>
                                                <p class="text-xs text-[#8A93A6] truncate">{{ exam.course_title }}</p>
                                            </div>
                                            <Tag :value="examStatusLabel(exam.status)"
                                                 :severity="exam.status === 'ready' ? 'success' : 'info'" class="!text-[10px] shrink-0" />
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-[#6B7280] font-mono mb-3 flex-wrap">
                                            <span class="flex items-center gap-1"><i class="pi pi-clock text-[10px]" aria-hidden="true"></i>{{ exam.duration_min }}min</span>
                                            <span class="flex items-center gap-1"><i class="pi pi-list text-[10px]" aria-hidden="true"></i>{{ exam.questions_count }}q</span>
                                            <span class="flex items-center gap-1"><i class="pi pi-calendar text-[10px]" aria-hidden="true"></i>{{ formatExamDate(exam.scheduled_at) }}</span>
                                        </div>
                                        <Button label="Démarrer l'examen" icon="pi pi-arrow-right" iconPos="right" size="small"
                                                class="!bg-[#12172B] w-full !border-none !font-medium !text-xs"
                                                :disabled="exam.status === 'locked'"
                                                @click="startExam(exam)" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- AUTRES COURS -->
                        <section v-if="otherCourses.length > 0">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-base font-semibold text-[#12172B] flex items-center gap-2.5 font-display">
                                    <span class="w-1 h-5 bg-[#3E5EFF] rounded-full" aria-hidden="true"></span>
                                    Autres formations
                                </h2>
                                <Link :href="route('student.my-courses')" class="text-xs font-medium text-[#3E5EFF] hover:underline">
                                    Tout voir
                                </Link>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="course in otherCourses" :key="course.id"
                                     class="bg-white p-4 rounded-xl border border-[#E4E7F0] hover:border-[#C7D2FE] transition-colors group relative">
                                    <div class="flex gap-4">
                                        <div class="w-16 h-16 rounded-lg overflow-hidden shrink-0 bg-[#EEF1FF] flex items-center justify-center">
                                            <img v-if="getImageUrl(course.image || course.thumbnail)"
                                                 :src="getImageUrl(course.image || course.thumbnail)" :alt="course.title"
                                                 class="w-full h-full object-cover" />
                                            <i v-else class="pi pi-book text-[#3E5EFF] text-xl" aria-hidden="true"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <Tag :value="getLevelLabel(course.level)" severity="secondary" class="!text-[10px] mb-1.5" />
                                            <h4 class="font-medium text-[#12172B] text-sm leading-tight mb-0.5 truncate" :title="course.title">
                                                {{ course.title }}
                                            </h4>
                                            <p class="text-xs text-[#8A93A6] mb-2.5 truncate">{{ course.instructor }}</p>
                                            <div class="space-y-1">
                                                <div class="flex justify-between text-xs font-mono">
                                                    <span class="text-[#8A93A6]">Progression</span>
                                                    <span class="font-medium" :style="`color:${getProgressColor(course.progress)}`">{{ Math.round(course.progress ?? 0) }}%</span>
                                                </div>
                                                <ProgressBar :value="course.progress ?? 0" :showValue="false" style="height: 4px"
                                                             class="bg-[#EDEFF5] rounded-full overflow-hidden"
                                                             :style="`--p-progressbar-value-background:${getProgressColor(course.progress)}`" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex gap-2 relative z-20">
                                        <Button v-if="getLessonPlayerUrl(course)" label="Continuer" icon="pi pi-play" size="small"
                                                class="!bg-[#3E5EFF] !border-none !font-medium !text-xs"
                                                @click.stop="router.visit(getLessonPlayerUrl(course))" />
                                        <Button label="Supports" icon="pi pi-download" size="small" outlined
                                                class="!font-medium !text-xs !border-[#D7DCE8] !text-[#475569]"
                                                @click.stop="openCourseHub(course)" />
                                    </div>
                                    <Link v-if="getLessonPlayerUrl(course)" :href="getLessonPlayerUrl(course)"
                                          class="absolute inset-0 z-10" :aria-label="`Ouvrir ${course.title}`"></Link>
                                </div>
                            </div>
                        </section>

                        <!-- ASSIDUITÉ -->
                        <div class="bg-white rounded-2xl p-6 border border-[#E4E7F0]">
                            <h2 class="text-base font-semibold text-[#12172B] mb-5 flex items-center gap-2.5 font-display">
                                <span class="w-1 h-5 bg-[#17B8A6] rounded-full" aria-hidden="true"></span>
                                Assiduité — 7 derniers jours
                            </h2>
                            <div class="h-48 w-full">
                                <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" />
                            </div>
                        </div>
                    </div>

                    <!-- ── COLONNE DROITE ── -->
                    <aside class="lg:col-span-4 space-y-6">

                        <!-- MAÎTRISE GLOBALE -->
                        <div class="bg-white rounded-2xl p-7 border border-[#E4E7F0] text-center">
                            <h3 class="text-sm font-medium text-[#6B7280] mb-5">Maîtrise globale</h3>
                            <div class="flex justify-center mb-5">
                                <Knob
                                    v-model="completionRate"
                                    :size="150"
                                    valueColor="#3E5EFF"
                                    rangeColor="#EDEFF5"
                                    :strokeWidth="10"
                                    :readonly="true"
                                    valueTemplate="{value}%"
                                />
                            </div>
                            <div class="flex justify-center divide-x divide-[#E4E7F0]">
                                <div class="px-6">
                                    <p class="text-lg font-semibold font-mono">{{ stats?.certificates ?? 0 }}</p>
                                    <p class="text-xs text-[#8A93A6]">Certificats</p>
                                </div>
                                <div class="px-6">
                                    <p class="text-lg font-semibold font-mono">{{ stats?.hours_studied ?? 0 }}h</p>
                                    <p class="text-xs text-[#8A93A6]">Heures</p>
                                </div>
                            </div>
                        </div>

                        <!-- PROGRESSION PAR COURS -->
                        <div v-if="courseProgressBreakdown.length > 0" class="bg-white rounded-2xl p-6 border border-[#E4E7F0]">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-semibold text-[#12172B] font-display">Progression par cours</h3>
                                <Link :href="route('student.progress')" class="text-xs font-medium text-[#3E5EFF] hover:underline">
                                    Détails
                                </Link>
                            </div>
                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                <div v-for="c in courseProgressBreakdown" :key="c.id" class="flex items-center gap-3">
                                    <div class="relative w-9 h-9 shrink-0">
                                        <svg viewBox="0 0 36 36" class="w-9 h-9 -rotate-90" aria-hidden="true">
                                            <path class="text-[#EDEFF5]" stroke-width="4" stroke="currentColor" fill="none"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path :stroke="getProgressColor(c.progress)" stroke-width="4" stroke-linecap="round" fill="none"
                                                  :stroke-dasharray="`${c.progress}, 100`"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[9px] font-mono font-medium">
                                            {{ c.progress }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-[#12172B] truncate" :title="c.title">{{ c.title }}</p>
                                        <p class="text-[11px] text-[#8A93A6]">
                                            <span v-if="c.status === 'completed'">Terminé</span>
                                            <span v-else-if="c.status === 'advanced'">Avancé</span>
                                            <span v-else>En cours</span>
                                            <span v-if="c.lessonsTotal"> · {{ c.lessonsDone }}/{{ c.lessonsTotal }} leçons</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RÉPARTITION PAR NIVEAU -->
                        <div v-if="Object.values(skillDistribution).some(v => v > 0)"
                             class="bg-white rounded-2xl p-6 border border-[#E4E7F0]">
                            <h3 class="text-sm font-semibold text-[#12172B] mb-4 font-display">Répartition par niveau</h3>
                            <div class="h-40">
                                <Chart type="doughnut" :data="skillChartData" :options="doughnutOptions" class="h-full" />
                            </div>
                        </div>

                        <!-- CERTIFICATS -->
                        <div v-if="allCertificates.length > 0" class="bg-[#12172B] rounded-2xl p-6 text-white">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-semibold flex items-center gap-2 font-display">
                                    <i class="pi pi-verified text-[#F2A93B]" aria-hidden="true"></i> Mes certificats
                                </h3>
                                <Link :href="route('student.certificates')" class="text-xs font-medium text-white/60 hover:text-white">
                                    Tout voir
                                </Link>
                            </div>
                            <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                                <div v-for="cert in allCertificates" :key="cert.id"
                                     class="bg-white/[0.06] hover:bg-white/10 border border-white/10 rounded-xl p-3 flex items-center justify-between gap-3 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 bg-[#F2A93B] text-[#12172B] rounded-lg flex items-center justify-center shrink-0">
                                            <i class="pi pi-award text-sm" aria-hidden="true"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium truncate">{{ cert.course_title }}</p>
                                            <p class="text-[11px] text-white/45 font-mono">
                                                {{ cert.score }}% · {{ cert.issued_at }}
                                            </p>
                                        </div>
                                    </div>
                                    <button v-tooltip.left="'Télécharger le PDF'"
                                            class="w-8 h-8 bg-white text-[#12172B] rounded-lg flex items-center justify-center shrink-0 hover:bg-white/90 transition-colors"
                                            @click="downloadCertificate(cert)"
                                            :aria-label="`Télécharger le certificat ${cert.course_title}`">
                                        <i class="pi pi-download text-sm" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ACCÈS RAPIDE -->
                        <div class="bg-white rounded-2xl p-5 border border-[#E4E7F0]">
                            <h3 class="text-sm font-semibold text-[#12172B] mb-2 font-display">Accès rapide</h3>
                            <nav class="space-y-0.5">
                                <Link :href="route('student.my-courses')" class="quick-access-link">
                                    <i class="pi pi-book text-[#3E5EFF] w-5 text-center" aria-hidden="true"></i>
                                    Mes formations
                                    <i class="pi pi-chevron-right text-[#C7CDDB] text-xs ml-auto" aria-hidden="true"></i>
                                </Link>
                                <Link :href="route('exams.learner.index')" class="quick-access-link">
                                    <i class="pi pi-pencil text-[#F2A93B] w-5 text-center" aria-hidden="true"></i>
                                    Passer un examen
                                    <i class="pi pi-chevron-right text-[#C7CDDB] text-xs ml-auto" aria-hidden="true"></i>
                                </Link>
                                <Link :href="route('student.certificates')" class="quick-access-link">
                                    <i class="pi pi-verified text-[#2FBF71] w-5 text-center" aria-hidden="true"></i>
                                    Mes certificats
                                    <i class="pi pi-chevron-right text-[#C7CDDB] text-xs ml-auto" aria-hidden="true"></i>
                                </Link>
                                <Link :href="route('student.progress')" class="quick-access-link">
                                    <i class="pi pi-chart-line text-[#17B8A6] w-5 text-center" aria-hidden="true"></i>
                                    Ma progression
                                    <i class="pi pi-chevron-right text-[#C7CDDB] text-xs ml-auto" aria-hidden="true"></i>
                                </Link>
                            </nav>
                        </div>

                        <!-- CLASSEMENT -->
                        <div v-if="leaderboard && leaderboard.length > 0" class="bg-white rounded-2xl p-6 border border-[#E4E7F0]">
                            <h3 class="text-sm font-semibold text-[#12172B] mb-4 flex items-center gap-2 font-display">
                                <i class="pi pi-trophy text-[#F2A93B]" aria-hidden="true"></i> Classement
                            </h3>
                            <ol class="space-y-1">
                                <li v-for="(student, idx) in leaderboard" :key="idx"
                                     class="flex items-center justify-between p-2.5 rounded-lg hover:bg-[#F5F6FA] transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-mono text-[#8A93A6] w-4 text-center">{{ student.rank }}</span>
                                        <Avatar :label="student.name?.charAt(0)" :image="getImageUrl(student.avatar)"
                                                shape="circle" size="small" />
                                        <div>
                                            <p class="text-xs font-medium text-[#12172B]">{{ student.name }}</p>
                                            <span class="inline-flex items-center gap-1 text-[10px] text-[#8A93A6]">
                                                <i :class="`pi pi-arrow-${student.trend === 'up' ? 'up text-[#2FBF71]' : 'down text-[#E0585F]'} text-[8px]`" aria-hidden="true"></i>
                                                variation
                                            </span>
                                        </div>
                                    </div>
                                    <Badge :value="student.score + ' xp'" class="!bg-[#EEF1FF] !text-[#3E5EFF] font-mono !text-[10px]" />
                                </li>
                            </ol>
                        </div>

                        <!-- ACTIVITÉ -->
                        <div v-if="recentActivities && recentActivities.length > 0" class="bg-white rounded-2xl p-6 border border-[#E4E7F0]">
                            <h3 class="text-sm font-semibold text-[#12172B] mb-4 flex items-center justify-between font-display">
                                Activité récente
                                <span class="w-1.5 h-1.5 bg-[#2FBF71] rounded-full" aria-hidden="true"></span>
                            </h3>
                            <Timeline :value="recentActivities" class="custom-timeline">
                                <template #marker="slotProps">
                                    <span
                                        class="flex w-7 h-7 items-center justify-center text-white rounded-lg"
                                        :class="{
                                            'bg-[#2FBF71]': slotProps.item.type === 'success',
                                            'bg-[#3E5EFF]': slotProps.item.type === 'info',
                                            'bg-[#F2A93B]': slotProps.item.type === 'warning',
                                            'bg-[#B8BFCE]': !['success','info','warning'].includes(slotProps.item.type)
                                        }">
                                        <i :class="`pi ${slotProps.item.icon || 'pi-info-circle'} text-xs`" aria-hidden="true"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-5 ml-3">
                                        <p class="text-xs font-medium text-[#12172B] leading-snug">{{ slotProps.item.event }}</p>
                                        <p class="text-[11px] text-[#8A93A6] mt-0.5 line-clamp-2">{{ slotProps.item.detail }}</p>
                                        <span class="text-[11px] font-mono text-[#3E5EFF]">{{ slotProps.item.time }}</span>
                                    </div>
                                </template>
                            </Timeline>
                        </div>

                    </aside>
                </div>

                </template>
            </div>
        </div>

    </AppLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;600&display=swap');

.font-sans { font-family: 'Inter', system-ui, sans-serif; }
.font-display { font-family: 'Space Grotesk', 'Inter', sans-serif; }
.font-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }

/* ── Nav rapide (header) ── */
.quick-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #475569;
    transition: background-color 0.15s ease, color 0.15s ease;
}
.quick-nav-link:hover { background: #F5F6FA; color: #3E5EFF; }
.quick-nav-link:focus-visible { outline: 2px solid #3E5EFF; outline-offset: 2px; }

/* ── KPI cards ── */
.kpi-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    background: #fff;
    padding: 1.125rem 1.125rem;
    border-radius: 0.75rem;
    border: 1px solid #E4E7F0;
    transition: border-color 0.15s ease, transform 0.15s ease;
}
.kpi-card:hover { border-color: #C7D2FE; transform: translateY(-1px); }
.kpi-card:focus-visible { outline: 2px solid #3E5EFF; outline-offset: 2px; }
.kpi-icon {
    width: 2.5rem;
    height: 2.5rem;
    flex-shrink: 0;
    border-radius: 0.625rem;
    background: #EEF1FF;
    color: #3E5EFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
}

/* ── Hero (panneau sombre + trame réseau) ── */
.hero-panel {
    position: relative;
    background: #10131C;
    border-radius: 1.25rem;
    overflow: hidden;
    isolation: isolate;
}
.hero-grid {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0);
    background-size: 26px 26px;
    -webkit-mask-image: radial-gradient(ellipse 80% 100% at 100% 0%, #000 20%, transparent 75%);
    mask-image: radial-gradient(ellipse 80% 100% at 100% 0%, #000 20%, transparent 75%);
}

/* ── Accès rapide ── */
.quick-access-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #334155;
    transition: background-color 0.15s ease;
}
.quick-access-link:hover { background: #F5F6FA; }
.quick-access-link:focus-visible { outline: 2px solid #3E5EFF; outline-offset: -2px; }

/* Timeline overrides */
:deep(.custom-timeline .p-timeline-event-opposite) { display: none; }
:deep(.custom-timeline .p-timeline-event-content) { padding-left: 0; }

/* Knob text styling */
:deep(.p-knob-text) {
    font-weight: 600 !important;
    font-family: 'JetBrains Mono', monospace !important;
    fill: #12172B !important;
}

/* Scrollbar discrète */
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #E4E7F0; border-radius: 10px; }

/* Focus visible global (accessibilité clavier) */
a:focus-visible, button:focus-visible {
    outline: 2px solid #3E5EFF;
    outline-offset: 2px;
}

/* Respect des préférences de mouvement réduit */
@media (prefers-reduced-motion: reduce) {
    * { transition: none !important; animation: none !important; }
}
</style>
