<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT LEARNING COCKPIT (Ultra Pro V50)
 * ======================================================================================
 * Tableau de bord centralisé pour l'apprenant, harmonisé à 100% avec le format
 * du tableau de bord administrateur (PageHeader, PageContainer, PageKpi, PageCard).
 *
 * Couleurs de marque SubnetCongo :
 * - Bleu (#2563EB) en couleur primaire
 * - Rouge (#DC2626) en couleur d'accentuation
 * - Blanc / Slate pour les surfaces et fonds épurés
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
import Tooltip from 'primevue/tooltip';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';

// --- DESIGN SYSTEM SUBNETCONGO ---
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';
import PageSection from '@/Components/PageSection.vue';
import SncSkeleton from '@/Components/SncSkeleton.vue';

const vTooltip = Tooltip;

// ======================================================================================
// 1. PROPS
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
    upcomingExams: { type: Array, default: () => [] },
    inProgressExams: { type: Array, default: () => [] },
    courseResources: { type: Object, default: () => ({}) },
});

// ======================================================================================
// 2. ÉTAT ET LOGIQUE
// ======================================================================================
const toast = useToast();
const isLoading = ref(true);
const completionRate = ref(0);
const selectedCourse = ref(null);
const showResourceDialog = ref(false);
const showVideoDialog = ref(false);
const activeVideo = ref(null);

const continueLearning = computed(() => {
    if (!props.myCourses || props.myCourses.length === 0) return null;
    const inProgress = props.myCourses.find(c => (c.progress ?? 0) < 100 && c.next_lesson);
    return inProgress || props.myCourses[0];
});

const otherCourses = computed(() => {
    if (!continueLearning.value) return props.myCourses ?? [];
    return (props.myCourses ?? []).filter(c => c.id !== continueLearning.value.id).slice(0, 4);
});

const activityData = computed(() => ({
    labels: props.activityChart?.labels || [],
    datasets: [{
        label: 'Heures d\'étude',
        data: props.activityChart?.data || [],
        fill: true,
        borderColor: '#2563EB',
        backgroundColor: 'rgba(37, 99, 235, 0.10)',
        tension: 0.35,
        borderWidth: 2,
        pointBackgroundColor: '#2563EB',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 1.5,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#0F172A', padding: 10, cornerRadius: 8 }
    },
    scales: {
        y: { display: false, min: 0 },
        x: {
            grid: { display: false },
            ticks: { color: '#94A3B8', font: { size: 11, family: 'Inter, sans-serif' } }
        }
    }
};

const getLessonPlayerUrl = (course) => {
    if (!course || !course.next_lesson) return null;
    return route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id ?? course.next_lesson });
};

const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const getProgressColor = (p) => {
    if (p >= 80) return '#059669';
    if (p >= 50) return '#D97706';
    return '#2563EB';
};

const getLevelLabel = (level) => {
    const map = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', all: 'Tous niveaux' };
    return map[level] || level || 'Général';
};

const getResourceTypeIcon = (type) => {
    const map = {
        pdf: 'pi-file-pdf text-red-500',
        doc: 'pi-file-word text-blue-500',
        docx: 'pi-file-word text-blue-500',
        xls: 'pi-file-excel text-emerald-500',
        xlsx: 'pi-file-excel text-emerald-500',
        ppt: 'pi-file-powerpoint text-amber-500',
        pptx: 'pi-file-powerpoint text-amber-500',
        zip: 'pi-box text-blue-500',
        mp4: 'pi-video text-red-500',
        video: 'pi-video text-red-500',
    };
    return map[(type || '').toLowerCase()] || 'pi-file text-slate-400';
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
        backgroundColor: ['#93C5FD', '#F59E0B', '#2563EB', '#059669'],
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

// ── Q&A inline ────────────────────────────────────────────────────────
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
    }, 400);
});

watch(() => props.stats?.completion_rate, (v) => {
    completionRate.value = Math.round(v ?? 0);
});
</script>

<template>
    <AppLayout>
        <Head title="Mon tableau de bord" />
        <Toast />

        <div class="page-bg min-h-screen">

            <!-- ============================================================================== -->
            <!-- HEADER GLOBAL HARMONISÉ (Même format que Dashboard.vue)                       -->
            <!-- ============================================================================== -->
            <PageHeader
                :title="`${meta?.greeting || 'Bonjour'}, ${auth?.user?.name?.split(' ')[0]}`"
                :subtitle="meta?.message || 'Reprenons où vous en étiez.'"
                icon="pi-compass"
                badge="Apprenant"
                accent="blue"
                :actions="[
                    { label: 'Mes cours', icon: 'pi-book', href: route('student.my-courses'), variant: 'primary' },
                    { label: 'Examens', icon: 'pi-pencil', href: route('exams.learner.index') },
                    { label: 'Certificats', icon: 'pi-verified', href: route('student.certificates') },
                    { label: 'Progression', icon: 'pi-chart-line', href: route('student.progress') },
                ]"
            >
                <template #meta>
                    <div class="flex items-center gap-3 mt-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 text-xs font-mono text-white shadow-sm" v-tooltip.bottom="'Points d\'expérience'">
                            <i class="pi pi-bolt text-amber-400 text-xs"></i>
                            <span class="font-bold tracking-tight">{{ stats?.xp ?? 0 }}</span>
                            <span class="text-white/50 text-[10px] font-sans font-bold">XP</span>
                        </div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15 text-xs font-mono text-white shadow-sm" v-tooltip.bottom="'Jours consécutifs'">
                            <i class="pi pi-calendar text-emerald-400 text-xs"></i>
                            <span class="font-bold tracking-tight">{{ stats?.streak ?? 0 }}</span>
                            <span class="text-white/50 text-[10px] font-sans font-bold">JOURS</span>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- ============================================================================== -->
            <!-- PAGE CONTAINER                                                                -->
            <!-- ============================================================================== -->
            <div class="page-container">

                <!-- ÉTAT DE CHARGEMENT SKELETON -->
                <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                    <SncSkeleton v-for="n in 6" :key="n" variant="rectangular" class="h-28" />
                </div>

                <!-- KPI STRIP HARMONISÉ -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                    <PageKpi
                        label="Cours inscrits"
                        :value="myCourses?.length ?? 0"
                        icon="pi-book"
                        color="blue"
                        subtext="Formations actives"
                    />
                    <PageKpi
                        label="Supports"
                        :value="totalMaterials"
                        icon="pi-file"
                        color="blue"
                        subtext="Ressources d'étude"
                    />
                    <PageKpi
                        label="Vidéos"
                        :value="totalVideos"
                        icon="pi-video"
                        color="red"
                        subtext="Contenus multimédias"
                    />
                    <PageKpi
                        label="Certificats"
                        :value="stats?.certificates ?? 0"
                        icon="pi-verified"
                        color="emerald"
                        subtext="Titres obtenus"
                    />
                    <PageKpi
                        label="Heures d'étude"
                        :value="`${stats?.hours_studied ?? 0}h`"
                        icon="pi-clock"
                        color="amber"
                        subtext="Temps investi"
                    />
                    <PageKpi
                        label="Classement"
                        :value="`#${stats?.rank ?? '—'}`"
                        icon="pi-trophy"
                        color="violet"
                        subtext="Position de cohorte"
                    />
                </div>

                <!-- LAYOUT PRINCIPAL (2 COLONNES) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- ── COLONNE GAUCHE (8 COLS) ── -->
                    <div class="lg:col-span-8 space-y-8">

                        <!-- HERO : REPRENDRE LE COURS -->
                        <div v-if="continueLearning" class="page-card-flush relative overflow-hidden bg-slate-900 border border-slate-800 shadow-xl group">
                            <!-- Background glow effets SubnetCongo (Bleu & Rouge) -->
                            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/20 rounded-full blur-[80px] pointer-events-none"></div>
                            <div class="absolute bottom-0 left-0 w-72 h-72 bg-red-600/10 rounded-full blur-[60px] pointer-events-none"></div>

                            <div class="relative z-10 p-7 flex flex-col gap-6">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <span class="inline-flex items-center gap-2 text-xs font-black text-emerald-400 uppercase tracking-widest">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        Dernier accès : {{ continueLearning.last_accessed || 'Récemment' }}
                                    </span>
                                    <Tag value="En cours" class="!bg-blue-500/20 !text-blue-300 !border !border-blue-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                                </div>

                                <div class="flex items-start gap-5 flex-wrap md:flex-nowrap">
                                    <div v-if="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                         class="hidden sm:block w-24 h-24 rounded-2xl overflow-hidden shrink-0 ring-1 ring-white/10 shadow-lg">
                                        <img :src="getImageUrl(continueLearning.image || continueLearning.thumbnail)"
                                             :alt="continueLearning.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight leading-snug">
                                            {{ continueLearning.title }}
                                        </h2>
                                        <p class="text-sm text-slate-400 mt-1 font-medium">{{ continueLearning.instructor }}</p>
                                    </div>
                                </div>

                                <!-- Barre de progression SubnetCongo -->
                                <div class="bg-white/[0.06] backdrop-blur-md rounded-2xl border border-white/10 p-5">
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
                                        class="bg-white/10 rounded-full overflow-hidden [&_.p-progressbar-value]:bg-gradient-to-r [&_.p-progressbar-value]:from-blue-600 [&_.p-progressbar-value]:to-blue-400"
                                    />
                                </div>

                                <!-- Boutons d'actions SubnetCongo (Bleu & Rouge) -->
                                <div class="flex gap-3 flex-wrap">
                                    <Link v-if="getLessonPlayerUrl(continueLearning)" :href="getLessonPlayerUrl(continueLearning)">
                                        <Button label="Reprendre la leçon" icon="pi pi-play" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-6 !py-3 shadow-lg shadow-blue-500/30 text-white" />
                                    </Link>
                                    <Button label="Supports" icon="pi pi-folder-open" class="!bg-white/10 hover:!bg-white/20 !border !border-white/15 !text-white !rounded-xl !font-bold !text-xs !px-4 !py-3" @click="openCourseHub(continueLearning)" />
                                    <Button label="Poser une question" icon="pi pi-question-circle" class="!bg-red-500/15 hover:!bg-red-500/25 !border !border-red-500/30 !text-red-300 !rounded-xl !font-bold !text-xs !px-4 !py-3" @click="askQuestion(continueLearning)" />
                                    <Link :href="route('student.my-courses')">
                                        <Button label="Tous mes cours" icon="pi pi-th-large" class="!bg-transparent hover:!bg-white/5 !border !border-white/15 !text-slate-300 !rounded-xl !font-bold !text-xs !px-4 !py-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Aucun cours inscrit -->
                        <div v-else class="page-card text-center p-14">
                            <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i class="pi pi-book text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-black text-slate-800 mb-2">Aucune formation active</h3>
                            <p class="text-slate-500 text-sm mb-6 max-w-md mx-auto">Vous n'avez pas encore de formation en cours. Explorez le catalogue pour développer vos compétences dès aujourd'hui.</p>
                            <Link :href="route('student.my-courses')">
                                <Button label="Découvrir le catalogue" icon="pi pi-arrow-right" iconPos="right" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-6 !py-3 shadow-lg shadow-blue-500/20 text-white" />
                            </Link>
                        </div>

                        <!-- SECTION EXAMENS -->
                        <PageSection v-if="(upcomingExams?.length || inProgressExams?.length) > 0" title="Examens & Évaluations" subtitle="Sessions planifiées et en cours">
                            <div class="page-card-flush">
                                <div v-if="inProgressExams?.length" class="p-6 bg-amber-50/50 border-b border-amber-100">
                                    <p class="text-xs font-black text-amber-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                                        <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
                                        Examen en cours
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div v-for="exam in inProgressExams" :key="exam.attempt_uuid"
                                             class="bg-white border border-amber-200 rounded-xl p-4 flex items-center justify-between gap-4 hover:shadow-md transition-shadow">
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-slate-800 truncate">{{ exam.title }}</p>
                                                <p class="text-xs text-slate-500 truncate font-medium">{{ exam.course_title }}</p>
                                            </div>
                                            <Button label="Reprendre" icon="pi pi-play" size="small" class="!bg-amber-500 hover:!bg-amber-600 !border-none !text-white !font-black !uppercase !tracking-widest !text-[10px] !px-4 !py-2.5 shrink-0" @click="resumeExam(exam)" />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="upcomingExams?.length" class="p-6">
                                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">À passer</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div v-for="exam in upcomingExams" :key="exam.id"
                                             class="bg-slate-50/60 hover:bg-white border border-slate-200 hover:border-blue-400 rounded-xl p-4 transition-all group">
                                            <div class="flex justify-between items-start mb-3 gap-2">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-black text-slate-800 truncate">{{ exam.title }}</p>
                                                    <p class="text-xs text-slate-500 truncate font-medium">{{ exam.course_title }}</p>
                                                </div>
                                                <Tag :value="examStatusLabel(exam.status)" :severity="exam.status === 'ready' ? 'success' : 'info'" class="!text-[10px] !font-black !uppercase !tracking-widest shrink-0" />
                                            </div>
                                            <div class="flex items-center gap-4 text-xs text-slate-500 font-mono mb-4 flex-wrap">
                                                <span class="flex items-center gap-1.5"><i class="pi pi-clock text-[10px] text-blue-600"></i>{{ exam.duration_min }} min</span>
                                                <span class="flex items-center gap-1.5"><i class="pi pi-list text-[10px] text-red-600"></i>{{ exam.questions_count }} questions</span>
                                                <span class="flex items-center gap-1.5"><i class="pi pi-calendar text-[10px]"></i>{{ formatExamDate(exam.scheduled_at) }}</span>
                                            </div>
                                            <Button label="Démarrer l'examen" icon="pi pi-arrow-right" iconPos="right" size="small" class="!bg-blue-600 hover:!bg-blue-700 w-full !border-none !font-black !uppercase !tracking-widest !text-xs !py-2.5 text-white" :disabled="exam.status === 'locked'" @click="startExam(exam)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </PageSection>

                        <!-- AUTRES FORMATIONS -->
                        <PageSection v-if="otherCourses.length > 0" title="Autres formations inscrites" subtitle="Poursuivez votre apprentissage">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="course in otherCourses" :key="course.id"
                                     class="page-card hover:border-blue-300 hover:shadow-md transition-all group relative p-5 flex flex-col justify-between">
                                    <div class="flex gap-4">
                                        <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 bg-blue-50 flex items-center justify-center">
                                            <img v-if="getImageUrl(course.image || course.thumbnail)"
                                                 :src="getImageUrl(course.image || course.thumbnail)" :alt="course.title"
                                                 class="w-full h-full object-cover" />
                                            <i v-else class="pi pi-book text-blue-600 text-xl"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <Tag :value="getLevelLabel(course.level)" severity="secondary" class="!text-[10px] !font-black !uppercase !tracking-widest mb-1.5" />
                                            <h4 class="font-black text-slate-800 text-sm leading-tight mb-1 truncate" :title="course.title">{{ course.title }}</h4>
                                            <p class="text-xs text-slate-500 mb-3 truncate font-medium">{{ course.instructor }}</p>
                                            <div class="space-y-1">
                                                <div class="flex justify-between text-xs font-mono">
                                                    <span class="text-slate-400">Progression</span>
                                                    <span class="font-black" :style="`color:${getProgressColor(course.progress)}`">{{ Math.round(course.progress ?? 0) }}%</span>
                                                </div>
                                                <ProgressBar :value="course.progress ?? 0" :showValue="false" style="height: 4px" class="bg-slate-100 rounded-full overflow-hidden" :style="`--p-progressbar-value-background:${getProgressColor(course.progress)}`" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex gap-2 relative z-20">
                                        <Button v-if="getLessonPlayerUrl(course)" label="Continuer" icon="pi pi-play" size="small" class="!bg-blue-600 hover:!bg-blue-700 !border-none !font-black !uppercase !tracking-widest !text-[10px] !px-4 !py-2 text-white" @click.stop="router.visit(getLessonPlayerUrl(course))" />
                                        <Button label="Supports" icon="pi pi-download" size="small" outlined class="!border-slate-200 !text-slate-700 !font-bold !text-[10px] !uppercase !tracking-widest !px-3 !py-2" @click.stop="openCourseHub(course)" />
                                        <Button label="Question" icon="pi pi-question-circle" size="small" outlined class="!border-red-200 !text-red-600 !font-bold !text-[10px] !uppercase !tracking-widest !px-3 !py-2" @click.stop="askQuestion(course)" />
                                    </div>
                                </div>
                            </div>
                        </PageSection>

                        <!-- GRAPHIQUE D'ASSIDUITÉ -->
                        <PageCard title="Assiduité d'étude" subtitle="Heures investies durant les 7 derniers jours">
                            <div class="h-52 w-full pt-2">
                                <Chart type="line" :data="activityData" :options="chartOptions" class="h-full w-full" />
                            </div>
                        </PageCard>

                        <!-- CLASSEMENT DE LA PROMOTION -->
                        <PageCard v-if="leaderboard && leaderboard.length > 0" title="Classement de la promotion" subtitle="Top apprenants engagés">
                            <div class="max-h-72 overflow-y-auto pr-2 divide-y divide-slate-100">
                                <div v-for="(student, idx) in leaderboard" :key="idx"
                                     class="flex items-center justify-between py-3 hover:bg-slate-50/80 px-2 rounded-xl transition-all group">
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs font-mono font-black text-slate-400 w-6 text-center group-hover:text-blue-600 transition-colors">
                                            #{{ student.rank }}
                                        </span>
                                        <Avatar
                                            :label="student.name?.charAt(0)"
                                            :image="getImageUrl(student.avatar)"
                                            shape="circle"
                                            class="!bg-blue-50 !text-blue-700 !font-black !w-9 !h-9 border border-blue-200 shadow-sm"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-800 truncate uppercase tracking-tight">
                                                {{ student.name }}
                                            </p>
                                            <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                                                <i :class="`pi pi-arrow-${student.trend === 'up' ? 'up text-emerald-500' : 'down text-red-500'} text-[8px]`"></i>
                                                {{ student.trend === 'up' ? 'En hausse' : 'Stable' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge :value="student.score + ' XP'" class="!bg-blue-50 !text-blue-700 !border !border-blue-200 text-[10px] font-mono font-bold" />
                                        <div v-if="student.rank <= 3" class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </PageCard>

                    </div>

                    <!-- ── COLONNE DROITE (4 COLS) ── -->
                    <aside class="lg:col-span-4 space-y-6">

                        <!-- MAÎTRISE GLOBALE -->
                        <PageCard title="Maîtrise globale" subtitle="Taux de complétion moyen">
                            <div class="flex flex-col items-center py-4">
                                <Knob
                                    v-model="completionRate"
                                    :size="160"
                                    valueColor="#2563EB"
                                    rangeColor="#E2E8F0"
                                    :strokeWidth="10"
                                    :readonly="true"
                                    valueTemplate="{value}%"
                                />
                                <div class="flex justify-center divide-x divide-slate-200 mt-6 w-full text-center">
                                    <div class="px-6 flex-1">
                                        <p class="text-2xl font-black font-mono text-slate-800">{{ stats?.certificates ?? 0 }}</p>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Certificats</p>
                                    </div>
                                    <div class="px-6 flex-1">
                                        <p class="text-2xl font-black font-mono text-slate-800">{{ stats?.hours_studied ?? 0 }}h</p>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Heures</p>
                                    </div>
                                </div>
                            </div>
                        </PageCard>

                        <!-- PROGRESSION PAR COURS -->
                        <PageCard v-if="courseProgressBreakdown.length > 0" title="Progression par cours" subtitle="Avancement modulaire">
                            <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
                                <div v-for="c in courseProgressBreakdown" :key="c.id" class="flex items-center gap-3">
                                    <div class="relative w-10 h-10 shrink-0">
                                        <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90">
                                            <path class="text-slate-100" stroke-width="4" stroke="currentColor" fill="none"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path :stroke="getProgressColor(c.progress)" stroke-width="4" stroke-linecap="round" fill="none"
                                                  :stroke-dasharray="`${c.progress}, 100`"
                                                  d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[9px] font-mono font-black text-slate-700">
                                            {{ c.progress }}%
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-black text-slate-800 truncate" :title="c.title">{{ c.title }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                            <span v-if="c.status === 'completed'" class="text-emerald-600">Terminé</span>
                                            <span v-else-if="c.status === 'advanced'" class="text-amber-600">Avancé</span>
                                            <span v-else class="text-blue-600">En cours</span>
                                            <span v-if="c.lessonsTotal"> · {{ c.lessonsDone }}/{{ c.lessonsTotal }} leçons</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </PageCard>

                        <!-- RÉPARTITION PAR NIVEAU -->
                        <PageCard v-if="Object.values(skillDistribution).some(v => v > 0)" title="Répartition par niveau" subtitle="Profil de compétences">
                            <div class="h-44">
                                <Chart type="doughnut" :data="skillChartData" :options="doughnutOptions" class="h-full" />
                            </div>
                        </PageCard>

                        <!-- CERTIFICATS OBTENUS -->
                        <PageCard v-if="allCertificates.length > 0" title="Mes certificats" subtitle="Validations officielles">
                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                <div v-for="cert in allCertificates" :key="cert.id"
                                     class="p-3 rounded-xl border border-slate-200 bg-slate-50/60 flex items-center justify-between gap-3 hover:border-red-300 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                                            <i class="pi pi-verified text-base"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-800 truncate">{{ cert.course_title }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ cert.score }}% · {{ cert.issued_at }}</p>
                                        </div>
                                    </div>
                                    <Button
                                        icon="pi pi-download"
                                        size="small"
                                        class="!bg-red-600 hover:!bg-red-700 !border-none !w-8 !h-8 !rounded-lg text-white shrink-0"
                                        v-tooltip.left="'Télécharger le PDF'"
                                        @click="downloadCertificate(cert)"
                                    />
                                </div>
                            </div>
                        </PageCard>

                        <!-- ACCÈS RAPIDE -->
                        <PageCard title="Navigation rapide" subtitle="Raccourcis apprenant">
                            <nav class="space-y-1.5">
                                <Link :href="route('student.my-courses')" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center"><i class="pi pi-book text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Mes formations</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-xs ml-auto group-hover:text-blue-600"></i>
                                </Link>
                                <Link :href="route('exams.learner.index')" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-red-50 text-red-600 rounded-lg flex items-center justify-center"><i class="pi pi-pencil text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Passer un examen</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-xs ml-auto group-hover:text-red-600"></i>
                                </Link>
                                <Link :href="route('student.certificates')" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center"><i class="pi pi-verified text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Mes certificats</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-xs ml-auto group-hover:text-emerald-600"></i>
                                </Link>
                                <Link :href="route('student.progress')" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                                    <div class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center"><i class="pi pi-chart-line text-xs"></i></div>
                                    <span class="text-xs font-bold text-slate-700">Ma progression</span>
                                    <i class="pi pi-chevron-right text-slate-300 text-xs ml-auto group-hover:text-amber-600"></i>
                                </Link>
                            </nav>
                        </PageCard>

                        <!-- ACTIVITÉ RÉCENTE -->
                        <PageCard v-if="recentActivities && recentActivities.length > 0" title="Activité récente" subtitle="Journal de bord">
                            <Timeline :value="recentActivities" class="custom-timeline">
                                <template #marker="slotProps">
                                    <span
                                        class="flex w-7 h-7 items-center justify-center rounded-xl shadow-sm"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': slotProps.item.type === 'success',
                                            'bg-blue-100 text-blue-700': slotProps.item.type === 'info',
                                            'bg-amber-100 text-amber-700': slotProps.item.type === 'warning',
                                            'bg-slate-100 text-slate-600': !['success','info','warning'].includes(slotProps.item.type)
                                        }">
                                        <i :class="`pi ${slotProps.item.icon || 'pi-info-circle'} text-[10px]`"></i>
                                    </span>
                                </template>
                                <template #content="slotProps">
                                    <div class="mb-4 ml-3">
                                        <p class="text-xs font-black text-slate-800 leading-snug">{{ slotProps.item.event }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ slotProps.item.detail }}</p>
                                        <span class="text-[10px] font-mono text-blue-600 font-bold">{{ slotProps.item.time }}</span>
                                    </div>
                                </template>
                            </Timeline>
                        </PageCard>

                    </aside>
                </div>
            </div>
        </div>

        <!-- DIALOG QUESTION AU FORMATEUR -->
        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '580px' }" header="Poser une question" class="!rounded-2xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center gap-3">
                    <i class="pi pi-book text-blue-600"></i>
                    <div class="text-xs">
                        <p class="font-black text-slate-800">{{ askContext.course?.title }}</p>
                        <p v-if="askContext.lesson" class="text-slate-500">Leçon : {{ askContext.lesson.title }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Sujet de la question</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" placeholder="Ex: Précision sur l'adressage IP" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Message détaillé</label>
                    <Textarea v-model="askForm.body" rows="4" class="w-full !rounded-xl" placeholder="Expliquez ce que vous souhaitez comprendre..." autoResize />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Niveau d'urgence</label>
                    <Select v-model="askForm.priority" :options="[{label:'Normale', value:'normal'},{label:'Importante', value:'high'},{label:'Bloquante', value:'urgent'}]" optionLabel="label" optionValue="value" class="w-full !rounded-xl" />
                </div>
                <div v-if="askErrors.form || askErrors.subject || askErrors.body" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3">
                    {{ askErrors.form || askErrors.subject || askErrors.body }}
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-3 pt-2">
                    <Button label="Annuler" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl !text-xs font-bold" />
                    <Button :label="askSubmitting ? 'Envoi...' : 'Envoyer la question'" icon="pi pi-send" :loading="askSubmitting" @click="submitAskQuestion" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                </div>
            </template>
        </Dialog>

        <!-- DIALOG SUPPORTS DE COURS -->
        <Dialog v-model:visible="showResourceDialog" modal :style="{ width: '640px' }" header="Supports et Ressources" class="!rounded-2xl">
            <div class="space-y-4 p-2">
                <div v-if="selectedCourse" class="bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-center gap-3">
                    <i class="pi pi-folder-open text-blue-600"></i>
                    <div>
                        <h4 class="text-sm font-black text-slate-800">{{ selectedCourse.title }}</h4>
                        <p class="text-xs text-slate-500">Ressources téléchargeables pour cette formation</p>
                    </div>
                </div>

                <div v-if="getResources(selectedCourse?.id)?.materials?.length" class="space-y-2">
                    <div v-for="mat in getResources(selectedCourse?.id).materials" :key="mat.id"
                         class="p-3 bg-white border border-slate-200 rounded-xl flex items-center justify-between gap-3 hover:border-blue-300 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <i :class="['pi', getResourceTypeIcon(mat.type), 'text-lg']"></i>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-800 truncate">{{ mat.title }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ mat.size || 'Fichier' }}</p>
                            </div>
                        </div>
                        <Button icon="pi pi-download" size="small" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-lg text-white" @click="downloadMaterial(mat)" />
                    </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400 text-xs">
                    <i class="pi pi-inbox text-3xl mb-2 block"></i>
                    Aucun support de cours additionnel disponible pour le moment.
                </div>
            </div>
            <template #footer>
                <Button label="Fermer" outlined @click="showResourceDialog = false" class="!rounded-xl !text-xs font-bold" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
:deep(.custom-timeline .p-timeline-event-opposite) { display: none; }
:deep(.custom-timeline .p-timeline-event-content) { padding-left: 0; }
</style>
