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
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
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

// ── Q&A inline (depuis Dashboard) ─────────────────────────────────────
const showAskDialog = ref(false);
const askContext = ref({ course: null, lesson: null });
const askForm = ref({ subject: '', body: '', priority: 'normal' });
const askFiles = ref([]);
const askSubmitting = ref(false);
const askErrors = ref({});

const askQuestion = (course, lesson = null) => {
    askContext.value = { course, lesson };
    askForm.value = { subject: '', body: '', priority: 'normal' };
    askFiles.value = [];
    askErrors.value = {};
    showAskDialog.value = true;
};

const onAskFileChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    askFiles.value = [...askFiles.value, ...files].slice(0, 5);
    e.target.value = '';
};
const removeAskFile = (i) => askFiles.value.splice(i, 1);
const formatFileSize = (b) => b < 1024 ? `${b} o` : b < 1048576 ? `${(b / 1024).toFixed(1)} Ko` : `${(b / 1048576).toFixed(1)} Mo`;

const submitAskQuestion = () => {
    if (!askForm.value.subject.trim() || !askForm.value.body.trim()) {
        askErrors.value = { form: 'Le sujet et le message sont obligatoires.' };
        return;
    }
    askSubmitting.value = true;
    router.post(route('student.questions.store'), {
        course_id: askContext.value.course?.id,
        lesson_id: askContext.value.lesson?.id ?? null,
        subject: askForm.value.subject,
        body: askForm.value.body,
        priority: askForm.value.priority,
        attachments: askFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            showAskDialog.value = false;
            askSubmitting.value = false;
            askFiles.value = [];
            const flash = page.props?.flash?.success;
            toast.add({ severity: 'success', summary: 'Question envoyée', detail: flash || 'Votre instructeur répondra sous 24-48h.', life: 3500 });
        },
        onError: (e) => {
            askSubmitting.value = false;
            askErrors.value = e;
        },
    });
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

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <!-- ═══════════════════════════════════════════════════
                 HEADER GLOBAL — DARK ENTERPRISE
            ═══════════════════════════════════════════════════ -->
            <div class="bg-slate-900 pt-8 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>

                <div class="max-w-[1600px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="relative">
                            <Avatar
                                :label="auth?.user?.name?.charAt(0)?.toUpperCase()"
                                :image="auth?.user?.profile_photo_url"
                                shape="circle"
                                size="large"
                                class="w-16 h-16 border-2 border-white/10 ring-2 ring-indigo-500/30 bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-2xl font-black"
                            />
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-slate-900 rounded-full" aria-hidden="true"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">
                                    {{ meta?.greeting || 'Bonjour' }}, {{ auth?.user?.name?.split(' ')[0] }} <span class="inline-block animate-pulse">👋</span>
                                </h1>
                                <Tag value="Apprenant" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ meta?.message || 'Reprenons où vous en étiez.' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <Link :href="route('student.my-courses')" class="px-4 py-2 bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700/50 rounded-xl text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-book text-sm"></i> Mes cours
                        </Link>
                        <Link :href="route('exams.learner.index')" class="px-4 py-2 bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700/50 rounded-xl text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-pencil text-sm"></i> Examens
                        </Link>
                        <Link :href="route('student.certificates')" class="px-4 py-2 bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700/50 rounded-xl text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-verified text-sm"></i> Certificats
                        </Link>
                        <Link :href="route('student.progress')" class="px-4 py-2 bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700/50 rounded-xl text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-chart-line text-sm"></i> Progression
                        </Link>
                        <div class="flex items-stretch bg-white/5 backdrop-blur-md rounded-xl overflow-hidden border border-white/10 font-mono text-sm">
                            <div class="flex items-center gap-2 px-3.5 py-2 border-r border-white/10" v-tooltip.bottom="'Points d\'expérience'">
                                <i class="pi pi-bolt text-amber-400 text-xs"></i>
                                <span class="text-white font-semibold">{{ stats?.xp ?? 0 }}</span>
                                <span class="text-white/40 text-[11px]">xp</span>
                            </div>
                            <div class="flex items-center gap-2 px-3.5 py-2" v-tooltip.bottom="'Jours consécutifs'">
                                <i class="pi pi-calendar text-emerald-400 text-xs"></i>
                                <span class="text-white font-semibold">{{ stats?.streak ?? 0 }}</span>
                                <span class="text-white/40 text-[11px]">jours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 MAIN — WHITE FLOATING CARD
            ═══════════════════════════════════════════════════ -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <div class="bg-white rounded-xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[600px]">

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
                        { label: 'Cours inscrits', val: myCourses?.length ?? 0, icon: 'pi-book', border: 'hover:border-indigo-300', iconBg: 'bg-indigo-50', iconText: 'text-indigo-600', href: route('student.my-courses') },
                        { label: 'Supports', val: totalMaterials, icon: 'pi-file', border: 'hover:border-blue-300', iconBg: 'bg-blue-50', iconText: 'text-blue-600', action: 'materials' },
                        { label: 'Vidéos', val: totalVideos, icon: 'pi-video', border: 'hover:border-rose-300', iconBg: 'bg-rose-50', iconText: 'text-rose-500', action: 'materials' },
                        { label: 'Certificats', val: stats?.certificates ?? 0, icon: 'pi-verified', border: 'hover:border-emerald-300', iconBg: 'bg-emerald-50', iconText: 'text-emerald-600', href: route('student.certificates') },
                        { label: 'Heures d\'étude', val: (stats?.hours_studied ?? 0) + 'h', icon: 'pi-clock', border: 'hover:border-amber-300', iconBg: 'bg-amber-50', iconText: 'text-amber-500', href: route('student.progress') },
                        { label: 'Rang', val: '#' + (stats?.rank ?? '—'), icon: 'pi-trophy', border: 'hover:border-violet-300', iconBg: 'bg-violet-50', iconText: 'text-violet-600', href: '#' }
                    ]" :key="stat.label">
                        <component :is="stat.href ? 'Link' : 'button'"
                                   :href="stat.href"
                                   @click="stat.action === 'materials' && continueLearning && openCourseHub(continueLearning)"
                                   :class="`group bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between min-h-[110px] ${stat.border} transition-colors text-left`">
                            <div class="flex justify-between items-start mb-3">
                                <div :class="`w-11 h-11 ${stat.iconBg} ${stat.iconText} rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform`">
                                    <i :class="`pi ${stat.icon} text-lg`" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</h4>
                                <h2 class="text-3xl font-black text-slate-800 font-mono tabular-nums">{{ stat.val }}</h2>
                            </div>
                        </component>
                    </template>
                </div>

                <!-- ═══════════════════════════════════════════════════
                     LAYOUT PRINCIPAL
                ═══════════════════════════════════════════════════ -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- ── COLONNE GAUCHE ── -->
                    <div class="lg:col-span-8 space-y-8">

                        <!-- HERO : REPRENDRE LE COURS -->
                        <div v-if="continueLearning" class="bg-slate-900 rounded-xl relative overflow-hidden shadow-xl border border-slate-800">
                            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-600/20 rounded-full blur-[80px] pointer-events-none"></div>
                            <div class="absolute bottom-0 left-0 w-72 h-72 bg-emerald-500/10 rounded-full blur-[60px] pointer-events-none"></div>

                            <div class="relative z-10 p-7 flex flex-col gap-5">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <span class="inline-flex items-center gap-2 text-[10px] font-black text-emerald-400 uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        Dernier accès : {{ continueLearning.last_accessed || '—' }}
                                    </span>
                                    <Tag value="En cours" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                                </div>

                                <div class="flex items-start gap-5 flex-wrap md:flex-nowrap">
                                    <div v-if="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                         class="hidden sm:block w-20 h-20 rounded-xl overflow-hidden shrink-0 ring-1 ring-white/10">
                                        <img :src="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                             :alt="continueLearning.title" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight leading-tight">
                                            {{ continueLearning.title }}
                                        </h2>
                                        <p class="text-sm text-slate-400 mt-1 font-medium">{{ continueLearning.instructor }}</p>
                                    </div>
                                </div>

                                <div class="bg-white/[0.04] backdrop-blur-md rounded-xl border border-white/10 p-5">
                                    <div class="flex justify-between items-start gap-4 mb-3">
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Prochaine étape</p>
                                            <p class="text-sm font-bold text-white truncate">
                                                {{ continueLearning.next_lesson?.title ?? continueLearning.next_lesson ?? 'Cours terminé' }}
                                            </p>
                                        </div>
                                        <span class="text-3xl font-black text-white shrink-0 font-mono">{{ Math.round(continueLearning.progress ?? 0) }}%</span>
                                    </div>
                                    <ProgressBar
                                        :value="continueLearning.progress ?? 0"
                                        :showValue="false"
                                        style="height: 6px"
                                        class="bg-white/10 rounded-full overflow-hidden [&_.p-progressbar-value]:bg-gradient-to-r [&_.p-progressbar-value]:from-indigo-500 [&_.p-progressbar-value]:to-purple-500"
                                    />
                                </div>

                                <div class="flex gap-2.5 flex-wrap">
                                    <Link v-if="getLessonPlayerUrl(continueLearning)" :href="getLessonPlayerUrl(continueLearning)">
                                        <Button label="Reprendre la leçon" icon="pi pi-play" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-5 !py-3 shadow-lg shadow-indigo-500/30" />
                                    </Link>
                                    <Button label="Ressources" icon="pi pi-folder-open" class="!bg-white/10 hover:!bg-white/15 !border !border-white/15 !text-white !rounded-xl !font-bold !text-xs !px-4 !py-3" @click="openCourseHub(continueLearning)" />
                                    <Button label="Poser une question" icon="pi pi-question-circle" class="!bg-amber-500/15 hover:!bg-amber-500/25 !border !border-amber-500/30 !text-amber-300 !rounded-xl !font-bold !text-xs !px-4 !py-3" @click="askQuestion(continueLearning)" />
                                    <Link :href="route('student.my-courses')">
                                        <Button label="Tous mes cours" icon="pi pi-th-large" class="!bg-transparent hover:!bg-white/5 !border !border-white/15 !text-slate-300 !rounded-xl !font-bold !text-xs !px-4 !py-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Aucun cours inscrit -->
                        <div v-else class="bg-white border-2 border-dashed border-slate-200 rounded-xl p-14 text-center">
                            <div class="w-20 h-20 bg-indigo-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i class="pi pi-folder-open text-3xl text-indigo-400"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">Aucune formation en cours</h3>
                            <p class="text-slate-500 text-sm mb-6">Vous n'avez pas encore de cours. Explorez le catalogue pour commencer.</p>
                            <Link :href="route('student.my-courses')">
                                <Button label="Découvrir les formations" icon="pi pi-arrow-right" iconPos="right" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-5 !py-3" />
                            </Link>
                        </div>

                        <!-- EXAMENS -->
                        <section v-if="(upcomingExams?.length || inProgressExams?.length) > 0"
                                 class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100">
                                <h2 class="text-lg font-black text-slate-800 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-pencil text-lg"></i>
                                    </div>
                                    Examens
                                </h2>
                                <Link :href="route('exams.learner.index')" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">
                                    Tout voir →
                                </Link>
                            </div>

                            <div v-if="inProgressExams?.length" class="p-6 bg-amber-50/40 border-b border-amber-100">
                                <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                    Reprendre où vous étiez
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="exam in inProgressExams" :key="exam.attempt_uuid"
                                         class="bg-white border border-amber-200 rounded-xl p-4 flex items-center justify-between gap-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center shrink-0">
                                                <i class="pi pi-pencil text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-800 truncate">{{ exam.title }}</p>
                                                <p class="text-xs text-slate-500 truncate font-medium">{{ exam.course_title }}</p>
                                            </div>
                                        </div>
                                        <Button label="Reprendre" icon="pi pi-play" size="small" class="!bg-amber-500 hover:!bg-amber-600 !border-none !text-white !font-black !uppercase !tracking-widest !text-[10px] !px-4 !py-2.5 shrink-0" @click="resumeExam(exam)" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="upcomingExams?.length" class="p-6">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">À passer</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div v-for="exam in upcomingExams" :key="exam.id"
                                         class="bg-slate-50/50 hover:bg-white border border-slate-200 hover:border-indigo-300 rounded-xl p-4 transition-all group">
                                        <div class="flex justify-between items-start mb-3 gap-2">
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-800 truncate">{{ exam.title }}</p>
                                                <p class="text-xs text-slate-500 truncate font-medium">{{ exam.course_title }}</p>
                                            </div>
                                            <Tag :value="examStatusLabel(exam.status)" :severity="exam.status === 'ready' ? 'success' : 'info'" class="!text-[10px] !font-black !uppercase !tracking-widest shrink-0" />
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-slate-500 font-mono mb-3 flex-wrap">
                                            <span class="flex items-center gap-1.5"><i class="pi pi-clock text-[10px]"></i>{{ exam.duration_min }}min</span>
                                            <span class="flex items-center gap-1.5"><i class="pi pi-list text-[10px]"></i>{{ exam.questions_count }}q</span>
                                            <span class="flex items-center gap-1.5"><i class="pi pi-calendar text-[10px]"></i>{{ formatExamDate(exam.scheduled_at) }}</span>
                                        </div>
                                        <Button label="Démarrer l'examen" icon="pi pi-arrow-right" iconPos="right" size="small" class="!bg-slate-900 hover:!bg-slate-800 w-full !border-none !font-black !uppercase !tracking-widest !text-[10px] !py-2.5" :disabled="exam.status === 'locked'" @click="startExam(exam)" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- AUTRES COURS -->
                        <section v-if="otherCourses.length > 0">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-black text-slate-800 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-book text-lg"></i>
                                    </div>
                                    Autres formations
                                </h2>
                                <Link :href="route('student.my-courses')" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">
                                    Tout voir →
                                </Link>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="course in otherCourses" :key="course.id"
                                     class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all group relative">
                                    <div class="flex gap-4">
                                        <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-indigo-50 flex items-center justify-center">
                                            <img v-if="getImageUrl(course.image || course.thumbnail)"
                                                 :src="getImageUrl(course.image || course.thumbnail)" :alt="course.title"
                                                 class="w-full h-full object-cover" />
                                            <i v-else class="pi pi-book text-indigo-500 text-xl"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <Tag :value="getLevelLabel(course.level)" severity="secondary" class="!text-[10px] !font-black !uppercase !tracking-widest mb-1.5" />
                                            <h4 class="font-black text-slate-800 text-sm leading-tight mb-0.5 truncate" :title="course.title">{{ course.title }}</h4>
                                            <p class="text-xs text-slate-500 mb-2.5 truncate font-medium">{{ course.instructor }}</p>
                                            <div class="space-y-1">
                                                <div class="flex justify-between text-xs font-mono">
                                                    <span class="text-slate-400">Progression</span>
                                                    <span class="font-black" :style="`color:${getProgressColor(course.progress)}`">{{ Math.round(course.progress ?? 0) }}%</span>
                                                </div>
                                                <ProgressBar :value="course.progress ?? 0" :showValue="false" style="height: 4px" class="bg-slate-100 rounded-full overflow-hidden" :style="`--p-progressbar-value-background:${getProgressColor(course.progress)}`" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex gap-2 relative z-20">
                                        <Button v-if="getLessonPlayerUrl(course)" label="Continuer" icon="pi pi-play" size="small" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !font-black !uppercase !tracking-widest !text-[10px] !px-4 !py-2.5" @click.stop="router.visit(getLessonPlayerUrl(course))" />
                                        <Button label="Supports" icon="pi pi-download" size="small" outlined class="!border-slate-200 !text-slate-600 !font-bold !text-[10px] !uppercase !tracking-widest !px-3 !py-2.5" @click.stop="openCourseHub(course)" />
                                        <Button label="Question" icon="pi pi-question-circle" size="small" outlined class="!border-amber-200 !text-amber-600 !font-bold !text-[10px] !uppercase !tracking-widest !px-3 !py-2.5" @click.stop="askQuestion(course)" />
                                    </div>
                                    <Link v-if="getLessonPlayerUrl(course)" :href="getLessonPlayerUrl(course)"
                                          class="absolute inset-0 z-10" :aria-label="`Ouvrir ${course.title}`"></Link>
                                </div>
                            </div>
                        </section>

                        <!-- ASSIDUITÉ -->
                        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                            <div class="flex justify-between items-center mb-5">
                                <h2 class="text-lg font-black text-slate-800 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-chart-line text-lg"></i>
                                    </div>
                                    Assiduité
                                </h2>
                                <Tag value="7 Jours" class="!bg-slate-100 !text-slate-600 !border-none !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <div class="h-48 w-full">
                                <Chart type="line" :data="activityData" :options="chartOptions" class="h-full" />
                            </div>
                            
                        </div>
                        
                        <!-- CLASSEMENT -->
                   <div v-if="leaderboard && leaderboard.length > 0" class="bg-white rounded-[1rem] p-8 border border-slate-100 shadow-sm overflow-hidden">
    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 mb-6 flex items-center gap-3">
        <div class="w-9 h-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center italic shadow-inner border border-red-100/50">
            <i class="pi pi-trophy text-xs"></i>
        </div>
        Classement 
    </h3>

    <!-- Conteneur avec scrollbar si plus de 3 éléments (Hauteur ajustée pour 3.5 items pour inciter au scroll) -->
    <div class="max-h-[240px] overflow-y-auto pr-2 custom-scrollbar">
        <ol class="space-y-2">
            <li v-for="(student, idx) in leaderboard" :key="idx"
                class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-all group border border-transparent hover:border-slate-100">
                
                <div class="flex items-center gap-4">
                    <!-- Rang avec indicateur de style -->
                    <span class="text-[10px] font-mono font-black text-slate-300 w-5 text-center group-hover:text-red-600 transition-colors">
                        #{{ student.rank }}
                    </span>
                    
                    <!-- Avatar Pro -->
                    <Avatar 
                        :label="student.name?.charAt(0)" 
                        :image="getImageUrl(student.avatar)" 
                        shape="circle" 
                        class="!bg-slate-100 !text-slate-800 !font-black !w-9 !h-9 border-2 border-white shadow-sm" 
                    />
                    
                    <div class="min-w-0">
                        <p class="text-[11px] font-black text-slate-800 truncate italic w-28 tracking-tighter uppercase leading-none mb-1">
                            {{ student.name }}
                        </p>
                        <span class="inline-flex items-center gap-1.5 text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                            <i :class="`pi pi-arrow-${student.trend === 'up' ? 'up text-emerald-500' : 'down text-red-600'} text-[7px]`"></i>
                            {{ student.trend === 'up' ? 'Ascendant' : 'Stable' }}
                        </span>
                    </div>
                </div>

                <!-- Badge Score V40 -->
                <div class="flex flex-col items-end">
                     <Badge :value="student.score + ' xp'" class="text-[10px] font-black text-red-600 font-mono italic tracking-tighter" />
                   
                    <div v-if="student.rank <= 3" class="w-1 h-1 rounded-full bg-red-500 animate-pulse"></div>
                </div>
            </li>
        </ol>
    </div>

    <!-- Petit indicateur visuel si le contenu dépasse -->
    <div v-if="leaderboard.length > 3" class="mt-4 pt-2 border-t border-slate-50 flex justify-center opacity-30">
        <i class="pi pi-angle-down text-[10px] animate-bounce"></i>
    </div>
 </div></div>

                    <!-- ── COLONNE DROITE ── -->
                    <aside class="lg:col-span-4 space-y-6">

                        <!-- MAÎTRISE GLOBALE -->
                        <div class="bg-white rounded-xl p-7 border border-slate-200 shadow-sm text-center">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Maîtrise globale</h3>
                            <div class="flex justify-center mb-5">
                                <Knob
                                    v-model="completionRate"
                                    :size="150"
                                    valueColor="#6366f1"
                                    rangeColor="#F1F5F9"
                                    :strokeWidth="10"
                                    :readonly="true"
                                    valueTemplate="{value}%"
                                />
                            </div>
                            <div class="flex justify-center divide-x divide-slate-100">
                                <div class="px-6">
                                    <p class="text-2xl font-black font-mono text-slate-800">{{ stats?.certificates ?? 0 }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Certificats</p>
                                </div>
                                <div class="px-6">
                                    <p class="text-2xl font-black font-mono text-slate-800">{{ stats?.hours_studied ?? 0 }}h</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Heures</p>
                                </div>
                            </div>
                        </div>

                        <!-- PROGRESSION PAR COURS -->
                        <div v-if="courseProgressBreakdown.length > 0" class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Progression par cours</h3>
                                <Link :href="route('student.progress')" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">Détails →</Link>
                            </div>
                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                <div v-for="c in courseProgressBreakdown" :key="c.id" class="flex items-center gap-3">
                                    <div class="relative w-10 h-10 shrink-0">
                                        <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90" aria-hidden="true">
                                            <path class="text-slate-100" stroke-width="4" stroke="currentColor" fill="none"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path :stroke="getProgressColor(c.progress)" stroke-width="4" stroke-linecap="round" fill="none"
                                                  :stroke-dasharray="`${c.progress}, 100`"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[9px] font-mono font-black text-slate-700">
                                            {{ c.progress }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-black text-slate-800 truncate" :title="c.title">{{ c.title }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                            <span v-if="c.status === 'completed'">Terminé</span>
                                            <span v-else-if="c.status === 'advanced'">Avancé</span>
                                            <span v-else>En cours</span>
                                            <span v-if="c.lessonsTotal"> · {{ c.lessonsDone }}/{{ c.lessonsTotal }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RÉPARTITION PAR NIVEAU -->
                        <div v-if="Object.values(skillDistribution).some(v => v > 0)"
                             class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Répartition par niveau</h3>
                            <div class="h-40">
                                <Chart type="doughnut" :data="skillChartData" :options="doughnutOptions" class="h-full" />
                            </div>
                        </div>

                        <!-- CERTIFICATS -->
                        <div v-if="allCertificates.length > 0" class="bg-slate-900 rounded-xl p-6 text-white relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-amber-500/10 rounded-full blur-[60px]"></div>
                            <div class="relative z-10">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-black flex items-center gap-2 uppercase tracking-widest">
                                        <i class="pi pi-verified text-amber-400"></i> Mes certificats
                                    </h3>
                                    <Link :href="route('student.certificates')" class="text-[10px] font-black text-amber-400 uppercase tracking-widest hover:underline">Tout voir →</Link>
                                </div>
                                <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                                    <div v-for="cert in allCertificates" :key="cert.id"
                                         class="bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl p-3 flex items-center justify-between gap-3 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center shrink-0">
                                                <i class="pi pi-award text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-black truncate">{{ cert.course_title }}</p>
                                                <p class="text-[10px] text-slate-400 font-mono uppercase tracking-widest">{{ cert.score }}% · {{ cert.issued_at }}</p>
                                            </div>
                                        </div>
                                        <button v-tooltip.left="'Télécharger le PDF'"
                                                class="w-9 h-9 bg-amber-500 text-slate-900 rounded-xl flex items-center justify-center shrink-0 hover:bg-amber-400 transition-colors shadow-lg shadow-amber-500/30"
                                                @click="downloadCertificate(cert)"
                                                :aria-label="`Télécharger le certificat ${cert.course_title}`">
                                            <i class="pi pi-download text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACCÈS RAPIDE -->
                        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Accès rapide</h3>
                            <nav class="space-y-1">
                                <Link :href="route('student.my-courses')" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center"><i class="pi pi-book text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Mes formations</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-[10px] ml-auto group-hover:text-indigo-500"></i>
                                </Link>
                                <Link :href="route('exams.learner.index')" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center"><i class="pi pi-pencil text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Passer un examen</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-[10px] ml-auto group-hover:text-amber-500"></i>
                                </Link>
                                <Link :href="route('student.certificates')" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center"><i class="pi pi-verified text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Mes certificats</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-[10px] ml-auto group-hover:text-emerald-500"></i>
                                </Link>
                                <Link :href="route('student.progress')" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-emerald-50 text-emerald-500 rounded-lg flex items-center justify-center"><i class="pi pi-chart-line text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Ma progression</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-[10px] ml-auto group-hover:text-emerald-500"></i>
                                </Link>
                            </nav>
                        </div>


                        <!-- ACTIVITÉ -->
                        <div v-if="recentActivities && recentActivities.length > 0" class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center justify-between">
                                Activité récente
                                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                            </h3>
                            <Timeline :value="recentActivities" class="custom-timeline">
                                <template #marker="slotProps">
                                    <span
                                        class="flex w-8 h-8 items-center justify-center text-white rounded-xl shadow-md"
                                        :class="{
                                            'bg-emerald-500 shadow-emerald-500/30': slotProps.item.type === 'success',
                                            'bg-indigo-500 shadow-indigo-500/30': slotProps.item.type === 'info',
                                            'bg-amber-500 shadow-amber-500/30': slotProps.item.type === 'warning',
                                            'bg-slate-400': !['success','info','warning'].includes(slotProps.item.type)
                                        }">
                                        <i :class="`pi ${slotProps.item.icon || 'pi-info-circle'} text-xs`"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-5 ml-3">
                                        <p class="text-xs font-black text-slate-800 leading-snug">{{ slotProps.item.event }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ slotProps.item.detail }}</p>
                                        <span class="text-[10px] font-mono text-indigo-600 font-black uppercase tracking-widest">{{ slotProps.item.time }}</span>
                                    </div>
                                </template>
                            </Timeline>
                        </div>

                    </aside>
                </div>

                </template>
            </div>
            </div>
        </div>

        <!-- Q&A Dialog -->
        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '600px' }" header="Poser une question" class="!rounded-xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex items-center gap-3">
                    <i class="pi pi-book text-indigo-500"></i>
                    <div class="text-xs">
                        <p class="font-black text-slate-800">{{ askContext.course?.title }}</p>
                        <p v-if="askContext.lesson" class="text-slate-500">Leçon : {{ askContext.lesson.title }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Sujet</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" placeholder="Ex: Je ne comprends pas le subnetting VLSM" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Message détaillé</label>
                    <Textarea v-model="askForm.body" rows="5" class="w-full !rounded-xl" placeholder="Décrivez votre question ou votre blocage..." autoResize />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Priorité</label>
                    <Select v-model="askForm.priority" :options="[{label:'Normale', value:'normal'},{label:'Importante', value:'high'},{label:'Bloquante', value:'urgent'}]" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Pièces jointes (capture d'écran, fichier… · 5 max · 10Mo)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">Ajouter un fichier</span>
                        <input type="file" multiple class="hidden" @change="onAskFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="askFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in askFiles" :key="i" class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-xs">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-indigo-500' : 'pi pi-file text-indigo-500'"></i>
                            <span class="flex-1 truncate font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ formatFileSize(f.size) }}</span>
                            <button type="button" @click="removeAskFile(i)" class="w-5 h-5 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="askErrors.form || askErrors.subject || askErrors.body" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl p-3">
                    {{ askErrors.form || askErrors.subject || askErrors.body }}
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl" />
                <Button :label="askSubmitting ? 'Envoi...' : 'Envoyer la question'" icon="pi pi-send" :loading="askSubmitting" @click="submitAskQuestion" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl" />
            </template>
        </Dialog>
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
/* Custom Scrollbar Elite Pro v40 */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px; /* Très fin pour la discrétion */
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #F8FAFC; /* Couleur de fond de la piste (Slate 50) */
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #E2E8F0; /* Couleur du curseur par défaut */
    border-radius: 10px;
    transition: background 0.3s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #E11D48; /* Devient Rouge SubnetCongo au survol */
}

/* Firefox support */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #E2E8F0 #F8FAFC;
}
</style>
