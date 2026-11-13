<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ProgressBar from 'primevue/progressbar';
import Button from 'primevue/button';
import SncDialog from '@/Components/SncDialog.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    course: Object,
    lesson: Object,
    module: Object,
    moduleGroups: Array,
    allLessonsCompleted: Boolean,
    finalExam: Object,
    quiz: Object,
    assignment: Object,
    moduleQuizAttempts: Array,
    lessons: Array,
    progress: Object,
    courseResources: Object,
});

const toast = useToast();

// ----- Résultat d'examen renvoyé par le flash (retour après validation) -----
const examResult = ref(usePage().props.flash?.exam_result ?? null);
const dismissExamResult = () => { examResult.value = null; };

// ----- Métadonnées de types de leçons -----
const typeMeta = {
    video: { icon: 'pi pi-play-circle', badge: 'bg-blue-100 text-blue-700', key: 'typeVideo' },
    text: { icon: 'pi pi-book', badge: 'bg-indigo-100 text-indigo-700', key: 'typeText' },
    document: { icon: 'pi pi-file-pdf', badge: 'bg-rose-100 text-rose-700', key: 'typeDocument' },
    quiz: { icon: 'pi pi-check-square', badge: 'bg-emerald-100 text-emerald-700', key: 'typeQuiz' },
    assignment: { icon: 'pi pi-pencil', badge: 'bg-amber-100 text-amber-700', key: 'typeAssignment' },
};
const typeIcon = (type) => typeMeta[type]?.icon ?? 'pi pi-circle';
const typeLabel = (type) => t(`pages.lesson_player.${typeMeta[type]?.key ?? 'typeText'}`);
const headerIcon = computed(() => ({ video: 'pi-play-circle', text: 'pi-book', document: 'pi-file-pdf', quiz: 'pi-check-square', assignment: 'pi-pencil' })[props.lesson?.type] ?? 'pi-book');

// ----- Helpers médias -----
const getYouTubeEmbedUrl = (url) => {
    if (!url) return null;
    const regExp = /^.*(youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    if (match && match[2].length === 11) {
        return `https://www.youtube.com/embed/${match[2]}`;
    }
    return null;
};
const isYouTubeUrl = (url) => getYouTubeEmbedUrl(url) !== null;

// Les vidéos importées depuis l'appareil sont stockées en chemin relatif
// (ex. « uploads/202611/xxx.mp4 ») : on les sert depuis le disque media.
const videoSrc = computed(() => {
    const url = props.lesson?.video_url;
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;
    return `/media/${url.replace(/^\/+/, '')}`;
});

const downloadMaterial = (m) => m.download_url && window.open(m.download_url, '_blank', 'noopener');
const formatFileSize = (b) => b < 1024 ? t('pages.lesson_player.sizeBytes', { size: b }) : b < 1048576 ? t('pages.lesson_player.sizeKb', { size: (b / 1024).toFixed(1) }) : t('pages.lesson_player.sizeMb', { size: (b / 1048576).toFixed(1) });

const completedCount = computed(() => (props.lessons ?? []).filter(l => l.is_completed).length);
const totalCount = computed(() => (props.lessons ?? []).length);
const overallPercent = computed(() => totalCount.value ? Math.round((completedCount.value / totalCount.value) * 100) : 0);

// ----- Navigation leçons -----
const nextLesson = computed(() => {
    const list = props.lessons ?? [];
    const idx = list.findIndex(l => l.id === props.lesson?.id);
    return idx >= 0 && idx < list.length - 1 ? list[idx + 1] : null;
});
const prevLesson = computed(() => {
    const list = props.lessons ?? [];
    const idx = list.findIndex(l => l.id === props.lesson?.id);
    return idx > 0 ? list[idx - 1] : null;
});

// ----- Progression / suivi -----
const videoRef = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const percent = ref(props.progress?.percent ?? 0);
const timeSpent = ref(props.progress?.time_spent ?? 0);
const isCompleted = computed(() => percent.value >= 99.5);
const courseMaterials = computed(() => props.courseResources?.[props.course?.id]?.materials ?? []);

const sendHeartbeat = async () => {
    if (!props.lesson?.id) return;
    try {
        const r = await window.axios?.post?.(route('student.lessons.update-progress', { course: props.course.id, lesson: props.lesson.id }), {
            progress_percent: percent.value,
            time_spent_seconds: Math.min(60, timeSpent.value % 60 || 30),
            current_position_seconds: Math.floor(currentTime.value),
        });
        if (r?.data?.is_completed) {
            percent.value = 100;
        }
    } catch (e) {
        // silencieux
    }
};

const markComplete = async () => {
    try {
        await router.post(route('student.lessons.complete', { course: props.course.id, lesson: props.lesson.id }), {}, {
            preserveScroll: true,
            onSuccess: () => {
                percent.value = 100;
                toast.add({ severity: 'success', summary: t('pages.lesson_player.lessonDone'), detail: t('pages.lesson_player.progressSaved'), life: 3000 });
                if (nextLesson.value) {
                    setTimeout(() => router.visit(route('student.lesson-player', { course: props.course.id, lesson: nextLesson.value.id })), 1200);
                }
            }
        });
    } catch (e) {
        toast.add({ severity: 'error', summary: t('pages.lesson_player.errorSummary'), detail: t('pages.lesson_player.markError'), life: 3000 });
    }
};

const onTimeUpdate = () => {
    if (!videoRef.value) return;
    currentTime.value = videoRef.value.currentTime;
    if (duration.value > 0) {
        const p = Math.min(100, (currentTime.value / duration.value) * 100);
        if (p > percent.value) percent.value = p;
    }
};
const onLoadedMeta = () => {
    if (videoRef.value) {
        duration.value = videoRef.value.duration || 0;
        if (props.progress?.current_position_seconds) {
            videoRef.value.currentTime = Math.min(props.progress.current_position_seconds, duration.value - 5);
        }
    }
};
const onPlay = () => { isPlaying.value = true; };
const onPause = () => { isPlaying.value = false; };
const onEnded = () => {
    percent.value = 100;
    sendHeartbeat();
    toast.add({ severity: 'success', summary: t('pages.lesson_player.videoDone'), detail: t('pages.lesson_player.videoDoneDetail'), life: 4000 });
};

let heartbeat = null;
onMounted(() => {
    heartbeat = setInterval(() => {
        if (isPlaying.value) {
            timeSpent.value += 1;
            if (timeSpent.value % 30 === 0) sendHeartbeat();
        }
    }, 1000);
});
onBeforeUnmount(() => {
    if (heartbeat) clearInterval(heartbeat);
    sendHeartbeat();
});

// ----- Quiz en ligne (JSON créé depuis l'éditeur de leçon) -----
const parsedQuiz = computed(() => {
    if (props.quiz) return null; // un quiz lié à un examen a priorité
    if (props.lesson?.type !== 'quiz') return null;
    try {
        const raw = props.lesson?.content;
        if (!raw || typeof raw !== 'string') return null;
        const data = JSON.parse(raw);
        if (data && data.created_inline && Array.isArray(data.questions)) {
            return data;
        }
    } catch (e) {
        // not JSON
    }
    return null;
});

const quizAnswers = ref({});
const quizSubmitted = ref(false);
const quizScore = ref(null);

const submitQuiz = () => {
    const questions = parsedQuiz.value?.questions || [];
    let correct = 0;
    questions.forEach((q, idx) => {
        const answer = quizAnswers.value[idx];
        if (q.type === 'true_false') {
            if (answer === q.correct_answer) correct++;
        } else if (q.type === 'short_answer') {
            if (String(answer).trim().toLowerCase() === String(q.correct_answer).trim().toLowerCase()) correct++;
        } else {
            if (String(answer) === String(q.correct_answer)) correct++;
        }
    });
    quizScore.value = questions.length > 0 ? Math.round((correct / questions.length) * 100) : 0;
    quizSubmitted.value = true;
    if (quizScore.value >= 70) percent.value = 100;
};

const resetQuiz = () => {
    quizAnswers.value = {};
    quizSubmitted.value = false;
    quizScore.value = null;
};

// ----- Devoir (assignment) -----
const assignmentForm = ref({ content: '' });
const assignmentFile = ref(null);
const assignmentSubmitting = ref(false);
const submission = computed(() => props.assignment?.submission ?? null);

const onAssignmentFileChange = (e) => {
    assignmentFile.value = (e.target.files ?? [])[0] ?? null;
};

const submitAssignment = () => {
    if (!assignmentForm.value.content.trim() && !assignmentFile.value) {
        toast.add({ severity: 'warn', summary: t('pages.lesson_player.errorSummary'), detail: t('pages.lesson_player.assignmentRequired'), life: 3000 });
        return;
    }
    assignmentSubmitting.value = true;
    router.post(route('student.lessons.submit-assignment', { course: props.course.id, lesson: props.lesson.id }), {
        content: assignmentForm.value.content,
        file: assignmentFile.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            assignmentSubmitting.value = false;
            assignmentFile.value = null;
            assignmentForm.value.content = '';
            toast.add({ severity: 'success', summary: t('pages.lesson_player.assignmentSubmitted'), detail: t('pages.lesson_player.assignmentSubmittedDetail'), life: 3500 });
        },
        onError: () => { assignmentSubmitting.value = false; },
    });
};

// ----- Contenu textuel (texte / HTML, hors quiz JSON) -----
const textContent = computed(() => {
    if (parsedQuiz.value || props.quiz) return null;
    const raw = props.lesson?.content_html || props.lesson?.content || '';
    if (!raw || typeof raw !== 'string') return null;
    try {
        const data = JSON.parse(raw);
        if (data && typeof data === 'object') return null;
    } catch (e) {
        return raw;
    }
    return null;
});

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(locale.value, { day: '2-digit', month: 'long', year: 'numeric' }) : '';
const formatDateTime = (iso) => iso ? new Date(iso).toLocaleDateString(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

// ----- Q&A -----
const showAskDialog = ref(false);
const askForm = ref({ subject: '', body: '', priority: 'normal' });
const askFiles = ref([]);
const askSubmitting = ref(false);
const askErrors = ref({});

const onAskFileChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    askFiles.value = [...askFiles.value, ...files].slice(0, 5);
    e.target.value = '';
};
const removeAskFile = (i) => askFiles.value.splice(i, 1);

const priorityOptions = computed(() => [
    { label: t('pages.lesson_player.priorityLow'), value: 'low' },
    { label: t('pages.lesson_player.priorityNormal'), value: 'normal' },
    { label: t('pages.lesson_player.priorityHigh'), value: 'high' },
    { label: t('pages.lesson_player.priorityUrgent'), value: 'urgent' },
]);

const openAsk = () => {
    askForm.value = { subject: t('pages.lesson_player.questionAbout', { title: props.lesson?.title ?? '' }), body: '', priority: 'normal' };
    askFiles.value = [];
    askErrors.value = {};
    showAskDialog.value = true;
};

const submitAsk = () => {
    if (!askForm.value.subject.trim() || !askForm.value.body.trim()) {
        askErrors.value = { form: t('pages.lesson_player.errRequired') };
        return;
    }
    askSubmitting.value = true;
    router.post(route('student.questions.store'), {
        course_id: props.course.id,
        lesson_id: props.lesson.id,
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
            toast.add({ severity: 'success', summary: t('pages.lesson_player.questionSent'), detail: page.props?.flash?.success || t('pages.lesson_player.questionSentDetail'), life: 3500 });
        },
        onError: (e) => { askSubmitting.value = false; askErrors.value = e; },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="lesson?.title || t('pages.lesson_player.headFallback')" />
        <!-- Skip link pour accessibilité -->
        <a href="#lesson-content" class="sr-only-focusable absolute top-4 left-4 z-50 px-4 py-2 bg-blue-600 text-white rounded-lg font-bold">
            {{ t('pages.lesson_player.skipLink') }}
        </a>

        <div class="page-bg min-h-screen font-sans pb-24">
            <PageHeader
                :title="lesson?.title ?? t('pages.lesson_player.headFallback')"
                :subtitle="[course?.title, module?.title].filter(Boolean).join(' · ')"
                :badge="typeLabel(lesson?.type)"
                :icon="headerIcon"
                accent="red"
            >
                <template #meta>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-[11px] font-bold text-white/60">
                        <span v-if="lesson?.duration" class="inline-flex items-center gap-1.5">
                            <i class="pi pi-clock text-red-300" aria-hidden="true"></i>{{ lesson.duration }} min
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <i class="pi pi-stopwatch text-red-300" aria-hidden="true"></i>{{ t('pages.lesson_player.timeSpent', { time: `${Math.floor(timeSpent / 60)}m ${timeSpent % 60}s` }) }}
                        </span>
                        <span v-if="isCompleted" class="inline-flex items-center gap-1.5 text-emerald-300">
                            <i class="pi pi-verified" aria-hidden="true"></i>{{ t('pages.lesson_player.completedLabel') }}
                        </span>
                    </div>
                </template>
            </PageHeader>

            <main id="lesson-content" class="page-container page-section" role="main">
                <!-- Fil d'Ariane leçon -->
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500 mb-5">
                    <Link :href="route('student.my-courses')" class="hover:text-red-600 font-bold inline-flex items-center gap-1">
                        <i class="pi pi-arrow-left text-[10px]" aria-hidden="true"></i>{{ t('pages.lesson_player.myCourses') }}
                    </Link>
                    <i class="pi pi-angle-right text-[9px]" aria-hidden="true"></i>
                    <span class="truncate max-w-[220px]">{{ course?.title }}</span>
                    <i class="pi pi-angle-right text-[9px]" aria-hidden="true"></i>
                    <span class="text-slate-700 font-bold truncate">{{ lesson?.title }}</span>
                </nav>

                <!-- Résultat d'examen : score affiché immédiatement, retour dans le parcours -->
                <div v-if="examResult" class="mb-6 rounded-xl border shadow-sm px-5 py-4 flex flex-wrap items-center gap-4"
                    :class="examResult.is_passed ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'"
                    role="status" aria-live="polite">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                        :class="examResult.is_passed ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                        <i class="pi text-lg" :class="examResult.is_passed ? 'pi-check-circle' : 'pi-chart-line'" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black" :class="examResult.is_passed ? 'text-emerald-800' : 'text-amber-800'">
                            {{ examResult.is_passed ? t('pages.lesson_player.examPassedTitle') : t('pages.lesson_player.examNotPassedTitle') }}
                            <span class="tabular-nums">{{ Math.round(examResult.score) }}%</span>
                        </p>
                        <p class="text-[11px] font-bold" :class="examResult.is_passed ? 'text-emerald-600' : 'text-amber-600'">
                            {{ examResult.needs_review
                                ? t('pages.lesson_player.examReviewPending', { title: examResult.exam_title })
                                : t('pages.lesson_player.examScoreDetail', { title: examResult.exam_title, score: Math.round(examResult.score), pass: examResult.pass_percentage }) }}
                        </p>
                    </div>
                    <Link :href="examResult.result_url"
                        class="px-3.5 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest shadow-sm transition hover:shadow"
                        :class="examResult.is_passed ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white'">
                        {{ t('pages.lesson_player.viewResultDetail') }}
                    </Link>
                    <button type="button" @click="dismissExamResult"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-white/60 transition"
                        :aria-label="t('pages.lesson_player.closeBannerAria')">
                        <i class="pi pi-times" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Progression du cours -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 mb-6" role="group" :aria-label="t('pages.lesson_player.courseProgressAria')">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest inline-flex items-center gap-2">
                            <i class="pi pi-chart-line text-red-500" aria-hidden="true"></i>{{ t('pages.lesson_player.courseProgress') }}
                        </span>
                        <span class="text-xs font-black text-slate-700">{{ t('pages.lesson_player.lessonsCount', { done: completedCount, total: totalCount }) }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <ProgressBar :value="overallPercent" :showValue="false" style="height: 8px" :pt="{ root: { class: 'rounded-full overflow-hidden bg-slate-100' } }" />
                        </div>
                        <span class="text-sm font-black text-slate-800 tabular-nums w-12 text-right">{{ overallPercent }}%</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 space-y-5">
                        <!-- Contexte du module -->
                        <div v-if="module?.description" class="flex items-start gap-3 bg-blue-50/60 border border-blue-100 rounded-xl px-4 py-3">
                            <i class="pi pi-info-circle text-blue-500 mt-0.5" aria-hidden="true"></i>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ module.description }}</p>
                        </div>

                        <!-- ===== Leçon vidéo ===== -->
                        <section v-if="lesson?.type === 'video'" aria-label="Vidéo" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <header class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                    <i class="pi pi-play-circle" aria-hidden="true"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-xs font-black text-slate-700 uppercase tracking-widest truncate">{{ typeLabel('video') }}</h2>
                                    <p class="text-[10px] text-slate-400 font-bold">{{ module?.title ?? course?.title }}</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    <i class="pi pi-clock" aria-hidden="true"></i>{{ t('pages.lesson_player.timeSpent', { time: `${Math.floor(timeSpent / 60)}m ${timeSpent % 60}s` }) }}
                                </span>
                            </header>
                            <div class="bg-slate-950 aspect-video flex items-center justify-center">
                                <iframe
                                    v-if="isYouTubeUrl(lesson?.video_url)"
                                    :src="getYouTubeEmbedUrl(lesson.video_url)"
                                    class="w-full h-full"
                                    :title="lesson?.title"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy"
                                ></iframe>
                                <video
                                    v-else-if="lesson?.video_url"
                                    ref="videoRef"
                                    :src="videoSrc"
                                    controls
                                    class="w-full h-full"
                                    :title="lesson?.title"
                                    @timeupdate="onTimeUpdate"
                                    @loadedmetadata="onLoadedMeta"
                                    @play="onPlay"
                                    @pause="onPause"
                                    @ended="onEnded"
                                ></video>
                                <div v-else class="text-center text-white/60 p-8" role="alert">
                                    <i class="pi pi-video text-5xl mb-3 block" aria-hidden="true"></i>
                                    <p class="text-sm">{{ t('pages.lesson_player.videoUnavailable') }}</p>
                                </div>
                            </div>
                        </section>

                        <!-- ===== Leçon document (PDF) ===== -->
                        <section v-else-if="lesson?.type === 'document'" aria-label="Document" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <header v-if="lesson?.document_url" class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                                    <i class="pi pi-file-pdf" aria-hidden="true"></i>
                                </div>
                                <h2 class="text-xs font-black text-slate-700 uppercase tracking-widest truncate flex-1">{{ lesson?.document_title || t('pages.lesson_player.documentFallback') }}</h2>
                                <a
                                    :href="lesson.document_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-500 text-white text-[10px] font-black uppercase tracking-wider hover:bg-rose-600 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-400"
                                >
                                    <i class="pi pi-download" aria-hidden="true"></i>{{ t('pages.lesson_player.download') }}
                                </a>
                            </header>
                            <iframe
                                v-if="lesson?.document_url"
                                :src="lesson.document_url"
                                class="w-full h-[640px] bg-slate-100"
                                :title="lesson?.document_title || t('pages.lesson_player.documentFallback')"
                                loading="lazy"
                            ></iframe>
                            <div v-else class="p-10 text-center" role="alert">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-300 flex items-center justify-center mx-auto mb-3">
                                    <i class="pi pi-file text-2xl" aria-hidden="true"></i>
                                </div>
                                <p class="text-sm text-slate-400 font-bold">{{ t('pages.lesson_player.noDocument') }}</p>
                            </div>
                        </section>

                        <!-- ===== Leçon quiz ===== -->
                        <section v-else-if="lesson?.type === 'quiz'" aria-label="Quiz">
                            <!-- Quiz lié à un examen de la plateforme -->
                            <div v-if="quiz" class="bg-white rounded-2xl border border-emerald-200 shadow-sm overflow-hidden">
                                <header class="bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-4 flex items-center gap-3">
                                    <i class="pi pi-check-square text-white text-xl" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-sm font-black text-white uppercase tracking-wider truncate">{{ quiz.title }}</h2>
                                        <p class="text-[10px] text-white/80">{{ t('pages.lesson_player.quizMeta', { questions: quiz.question_count ?? 0, duration: quiz.duration_minutes ?? 0 }) }}</p>
                                    </div>
                                    <span v-if="quiz.is_passed" class="px-2.5 py-1 rounded-lg bg-white/20 text-white text-[10px] font-black uppercase tracking-widest">
                                        <i class="pi pi-check mr-1" aria-hidden="true"></i>{{ t('pages.lesson_player.quizPassed') }}
                                    </span>
                                </header>
                                <div class="p-5 space-y-4">
                                    <p v-if="quiz.description" class="text-sm text-slate-600 leading-relaxed">{{ quiz.description }}</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-center">
                                            <div class="text-lg font-black text-slate-800">{{ quiz.question_count ?? 0 }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ t('pages.lesson_player.questionsCount', { count: quiz.question_count ?? 0 }) }}</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-center">
                                            <div class="text-lg font-black text-slate-800">{{ quiz.duration_minutes ?? '—' }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ t('pages.lesson_player.minutesLabel') }}</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-center">
                                            <div class="text-lg font-black text-emerald-600">{{ quiz.pass_percentage }}%</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ t('pages.lesson_player.toPass') }}</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-center">
                                            <div class="text-lg font-black text-slate-800">{{ quiz.attempts_used }}/{{ quiz.attempts_allowed || '∞' }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ t('pages.lesson_player.attempts') }}</div>
                                        </div>
                                    </div>
                                    <div v-if="quiz.best_score !== null && quiz.best_score !== undefined" class="flex items-center gap-2 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5">
                                        <i class="pi pi-star text-emerald-500" aria-hidden="true"></i>
                                        <span class="text-xs font-bold text-emerald-700">{{ t('pages.lesson_player.bestScore', { score: quiz.best_score }) }}</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <Link :href="route('exams.take', quiz.uuid)">
                                            <Button
                                                :label="quiz.has_active_attempt ? t('pages.lesson_player.resumeQuiz') : (quiz.is_passed ? t('pages.lesson_player.retryQuiz') : t('pages.lesson_player.startQuiz'))"
                                                icon="pi pi-play"
                                                class="!bg-emerald-500 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest"
                                            />
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Quiz créé inline dans la leçon -->
                            <div v-else-if="parsedQuiz" class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden">
                                <header class="bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-3 flex items-center gap-3">
                                    <i class="pi pi-check-square text-white text-lg" aria-hidden="true"></i>
                                    <div>
                                        <h2 class="text-sm font-black text-white uppercase tracking-wider">{{ parsedQuiz.quiz_title || t('pages.lesson_player.quizFallback') }}</h2>
                                        <p class="text-[10px] text-white/80">{{ t('pages.lesson_player.questionsCount', { count: parsedQuiz.questions?.length || 0 }) }}</p>
                                    </div>
                                </header>
                                <div class="p-6 space-y-6">
                                    <form v-if="!quizSubmitted" @submit.prevent="submitQuiz" class="space-y-6">
                                        <fieldset v-for="(q, qIdx) in parsedQuiz.questions" :key="qIdx" class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                                            <legend class="sr-only">{{ t('pages.lesson_player.questionLegend', { n: qIdx + 1 }) }}</legend>
                                            <div class="flex items-start gap-3 mb-4">
                                                <span class="text-xs font-black text-emerald-600 bg-emerald-50 rounded-lg px-2 py-1 border border-emerald-100" aria-hidden="true">Q{{ qIdx + 1 }}</span>
                                                <p class="text-sm font-bold text-slate-800 flex-1" :id="`question-${qIdx}`">{{ q.text }}</p>
                                            </div>
                                            <div v-if="q.type === 'multiple_choice'" class="space-y-2" role="radiogroup" :aria-labelledby="`question-${qIdx}`">
                                                <label
                                                    v-for="(opt, oIdx) in q.options"
                                                    :key="oIdx"
                                                    class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2"
                                                >
                                                    <RadioButton v-model="quizAnswers[qIdx]" :value="String(oIdx)" :inputId="`q${qIdx}-opt${oIdx}`" :name="`question-${qIdx}`" />
                                                    <span class="text-sm text-slate-700">{{ opt }}</span>
                                                </label>
                                            </div>
                                            <div v-else-if="q.type === 'true_false'" class="flex gap-3" role="radiogroup" :aria-labelledby="`question-${qIdx}`">
                                                <label
                                                    class="flex-1 flex items-center justify-center gap-2 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2"
                                                    :class="{'border-emerald-500 bg-emerald-50': quizAnswers[qIdx] === 'true'}"
                                                >
                                                    <RadioButton v-model="quizAnswers[qIdx]" value="true" :inputId="`q${qIdx}-true`" :name="`question-${qIdx}`" />
                                                    <span class="text-sm font-bold text-slate-700">{{ t('pages.lesson_player.trueLabel') }}</span>
                                                </label>
                                                <label
                                                    class="flex-1 flex items-center justify-center gap-2 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors focus-within:ring-2 focus-within:ring-emerald-500 focus-within:ring-offset-2"
                                                    :class="{'border-emerald-500 bg-emerald-50': quizAnswers[qIdx] === 'false'}"
                                                >
                                                    <RadioButton v-model="quizAnswers[qIdx]" value="false" :inputId="`q${qIdx}-false`" :name="`question-${qIdx}`" />
                                                    <span class="text-sm font-bold text-slate-700">{{ t('pages.lesson_player.falseLabel') }}</span>
                                                </label>
                                            </div>
                                            <div v-else-if="q.type === 'short_answer'" class="space-y-1">
                                                <label :for="`q${qIdx}-answer`" class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.lesson_player.yourAnswer') }}</label>
                                                <InputText
                                                    v-model="quizAnswers[qIdx]"
                                                    :id="`q${qIdx}-answer`"
                                                    class="w-full"
                                                    :placeholder="t('pages.lesson_player.answerPlaceholder')"
                                                    :aria-labelledby="`question-${qIdx}`"
                                                />
                                            </div>
                                        </fieldset>
                                        <Button type="submit" :label="t('pages.lesson_player.submitQuiz')" icon="pi pi-check" class="!bg-emerald-500 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest" />
                                    </form>
                                    <div v-else class="text-center space-y-4" role="alert" aria-live="polite">
                                        <div class="w-20 h-20 rounded-full mx-auto flex items-center justify-center text-2xl font-black" :class="quizScore >= 70 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'">
                                            {{ quizScore }}%
                                        </div>
                                        <p class="text-sm font-bold text-slate-700">{{ quizScore >= 70 ? t('pages.lesson_player.quizPass') : t('pages.lesson_player.quizFail') }}</p>
                                        <Button :label="t('pages.lesson_player.retry')" icon="pi pi-redo" class="!bg-slate-100 !text-slate-700 !border-slate-200 !rounded-xl" @click="resetQuiz" />
                                    </div>
                                </div>
                            </div>

                            <!-- Aucun quiz disponible -->
                            <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center space-y-4" role="alert">
                                <i class="pi pi-check-square text-4xl block text-slate-300" aria-hidden="true"></i>
                                <p class="text-sm text-slate-500 font-bold">{{ t('pages.lesson_player.noQuiz') }}</p>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto">{{ t('pages.lesson_player.noQuizHelp') }}</p>
                                <div class="flex flex-wrap justify-center gap-2 pt-1">
                                    <Link :href="route('exams.learner.index')">
                                        <Button :label="t('pages.lesson_player.myExams')" icon="pi pi-briefcase" outlined class="!rounded-xl" />
                                    </Link>
                                    <Button :label="t('pages.lesson_player.askQuestion')" icon="pi pi-plus" outlined severity="warning" class="!rounded-xl" @click="openAsk" />
                                </div>
                            </div>
                        </section>

                        <!-- ===== Leçon devoir ===== -->
                        <section v-else-if="lesson?.type === 'assignment'" aria-label="Devoir">
                            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
                                <header class="bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-4 flex items-center gap-3">
                                    <i class="pi pi-pencil text-white text-xl" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-sm font-black text-white uppercase tracking-wider">{{ t('pages.lesson_player.assignmentTitle') }}</h2>
                                        <p class="text-[10px] text-white/80">
                                            <span v-if="assignment?.max_points">{{ t('pages.lesson_player.assignmentMaxPoints', { points: assignment.max_points }) }}</span>
                                            <span v-if="assignment?.due_at"> • {{ t('pages.lesson_player.assignmentDue', { date: formatDate(assignment.due_at) }) }}</span>
                                        </p>
                                    </div>
                                </header>
                                <div class="p-5 space-y-5">
                                    <!-- Consignes -->
                                    <div v-if="textContent" class="prose prose-sm max-w-none text-slate-700" v-html="textContent"></div>
                                    <p v-else-if="assignment?.description" class="text-sm text-slate-600 leading-relaxed">{{ assignment.description }}</p>
                                    <p v-else class="text-sm text-slate-400 italic">{{ t('pages.lesson_player.noTextContent') }}</p>

                                    <!-- Soumission déjà envoyée -->
                                    <div v-if="submission && !submission.is_graded" class="flex items-center gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                                        <i class="pi pi-send text-blue-500" aria-hidden="true"></i>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-blue-800">{{ t('pages.lesson_player.assignmentWaiting') }}</p>
                                            <p class="text-[10px] text-blue-600">{{ t('pages.lesson_player.assignmentSubmittedAt', { date: formatDateTime(submission.submitted_at) }) }}</p>
                                        </div>
                                    </div>

                                    <!-- Devoir corrigé -->
                                    <div v-if="submission?.is_graded" class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 space-y-2" role="alert" aria-live="polite">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-star text-emerald-500" aria-hidden="true"></i>
                                            <p class="text-xs font-black text-emerald-800">{{ t('pages.lesson_player.assignmentGrade', { grade: submission.grade, points: assignment?.max_points ?? 20 }) }}</p>
                                        </div>
                                        <p v-if="submission.feedback" class="text-xs text-emerald-700 leading-relaxed">{{ submission.feedback }}</p>
                                    </div>

                                    <!-- Fichier déjà envoyé -->
                                    <div v-if="submission?.file_url" class="flex items-center gap-2 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5">
                                        <i class="pi pi-paperclip text-slate-400" aria-hidden="true"></i>
                                        <a :href="submission.file_url" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-blue-600 hover:underline truncate">{{ submission.file_name }}</a>
                                    </div>

                                    <!-- Formulaire de soumission -->
                                    <form @submit.prevent="submitAssignment" class="space-y-3 border-t border-slate-100 pt-4">
                                        <label for="assignment-content" class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">{{ t('pages.lesson_player.assignmentAnswer') }}</label>
                                        <Textarea
                                            id="assignment-content"
                                            v-model="assignmentForm.content"
                                            rows="6"
                                            class="w-full"
                                            :placeholder="t('pages.lesson_player.assignmentPlaceholder')"
                                            autoResize
                                        />
                                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                                            <i class="pi pi-paperclip text-slate-500"></i>
                                            <span class="font-bold">{{ assignmentFile ? assignmentFile.name : t('pages.lesson_player.addFile') }}</span>
                                            <input type="file" class="hidden" @change="onAssignmentFileChange" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.7z,.ppt,.pptx,.xls,.xlsx,.csv,image/*" />
                                        </label>
                                        <div class="flex justify-end">
                                            <Button
                                                type="submit"
                                                :label="t('pages.lesson_player.assignmentSubmit')"
                                                icon="pi pi-upload"
                                                :loading="assignmentSubmitting"
                                                class="!bg-amber-500 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest"
                                            />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </section>

                        <!-- ===== Leçon texte ===== -->
                        <section v-else aria-label="Contenu" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <header class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center shrink-0">
                                    <i class="pi pi-book" aria-hidden="true"></i>
                                </div>
                                <h2 class="text-xs font-black text-slate-700 uppercase tracking-widest truncate">{{ typeLabel('text') }}</h2>
                            </header>
                            <div class="p-6">
                                <div v-if="textContent" class="prose prose-sm max-w-none text-slate-700" v-html="textContent"></div>
                                <div v-else class="text-sm text-slate-400 italic text-center py-8" role="alert">
                                    <i class="pi pi-book text-4xl mb-3 block text-slate-300" aria-hidden="true"></i>
                                    {{ t('pages.lesson_player.noTextContent') }}
                                </div>
                            </div>
                        </section>

                        <!-- Lecture complémentaire (vidéo / document + texte à lire) -->
                        <section
                            v-if="(lesson?.type === 'video' || lesson?.type === 'document') && textContent"
                            aria-label="Lecture" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
                        >
                            <header class="bg-indigo-50 px-5 py-3 border-b border-indigo-100 flex items-center gap-2.5">
                                <i class="pi pi-book text-indigo-500" aria-hidden="true"></i>
                                <h2 class="text-xs font-black text-indigo-700 uppercase tracking-widest">{{ t('pages.lesson_player.readingTitle') }}</h2>
                            </header>
                            <div class="p-6">
                                <div class="prose prose-sm max-w-none text-slate-700" v-html="textContent"></div>
                            </div>
                        </section>

                        <!-- Pièces jointes de la leçon -->
                        <div v-if="lesson?.attachments?.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <header class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center gap-2">
                                <i class="pi pi-paperclip text-blue-500" aria-hidden="true"></i>
                                <h2 class="text-xs font-black text-slate-600 uppercase tracking-widest">{{ t('pages.lesson_player.attachmentsTitle', { count: lesson.attachments.length }) }}</h2>
                            </header>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <a
                                    v-for="(a, i) in lesson.attachments"
                                    :key="i"
                                    :href="a.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 p-3 bg-slate-50 hover:bg-blue-50 rounded-xl border border-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-400"
                                >
                                    <i class="pi pi-file text-blue-500" aria-hidden="true"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate flex-1">{{ a.name }}</span>
                                    <i class="pi pi-download text-slate-400 text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Examen final débloqué -->
                        <div v-if="allLessonsCompleted && finalExam" class="bg-gradient-to-r from-red-600 to-red-800 rounded-2xl shadow-lg px-6 py-5 flex flex-wrap items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white/15 flex items-center justify-center">
                                <i class="pi pi-graduation-cap text-white text-xl" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-[240px]">
                                <h2 class="text-sm font-black text-white uppercase tracking-widest">{{ t('pages.lesson_player.finalExamReady') }}</h2>
                                <p class="text-xs text-blue-100 mt-0.5">{{ t('pages.lesson_player.finalExamReadyDesc', { title: finalExam.title, questions: finalExam.question_count ?? 0 }) }}</p>
                            </div>
                            <Link :href="route('exams.take', finalExam.uuid)">
                                <Button :label="t('pages.lesson_player.startExam')" icon="pi pi-play" class="!bg-white !text-red-700 !border-none !rounded-xl !font-black !uppercase !tracking-widest" />
                            </Link>
                        </div>

                        <!-- Navigation leçons -->
                        <nav class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center justify-between gap-3" :aria-label="t('pages.lesson_player.lessonNavAria')">
                            <Link v-if="prevLesson" :href="route('student.lesson-player', { course: course.id, lesson: prevLesson.id })">
                                <Button :label="t('pages.lesson_player.prevLesson')" icon="pi pi-arrow-left" iconPos="left" outlined class="!rounded-xl" />
                            </Link>
                            <span v-else></span>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="!isCompleted"
                                    :label="t('pages.lesson_player.markComplete')"
                                    icon="pi pi-check-circle"
                                    class="!bg-red-600 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest"
                                    @click="markComplete"
                                />
                                <span v-else class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black uppercase tracking-widest">
                                    <i class="pi pi-check-circle" aria-hidden="true"></i>{{ t('pages.lesson_player.completedLabel') }}
                                </span>
                                <Link v-if="nextLesson" :href="route('student.lesson-player', { course: course.id, lesson: nextLesson.id })">
                                    <Button :label="t('pages.lesson_player.nextLesson')" icon="pi pi-arrow-right" iconPos="right" class="!bg-slate-800 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest" />
                                </Link>
                            </div>
                        </nav>
                    </div>

                    <!-- ===== Sidebar ===== -->
                    <aside class="lg:col-span-4 space-y-5 lg:sticky lg:top-6 self-start">
                        <!-- Q&A du cours -->
                        <div class="bg-gradient-to-br from-amber-50 to-red-50 rounded-xl p-5 border border-amber-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-red-500 rounded-xl flex items-center justify-center text-white shadow-md">
                                    <i class="pi pi-question-circle text-lg" aria-hidden="true"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.lesson_player.helpTitle') }}</h3>
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">{{ t('pages.lesson_player.helpSub') }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 mb-3">{{ t('pages.lesson_player.helpDesc') }}</p>
                            <div class="flex gap-2">
                                <Button :label="t('pages.lesson_player.askQuestion')" icon="pi pi-plus" @click="openAsk" class="!flex-1 !bg-gradient-to-r !from-amber-500 !to-red-500 !border-none !rounded-xl !text-white !text-[10px] !font-black !uppercase !tracking-widest !py-2.5" />
                                <Link :href="route('student.questions.index')" class="inline-flex">
                                    <Button icon="pi pi-list" outlined class="!rounded-xl !text-amber-700 !border-amber-200" v-tooltip.bottom="t('pages.lesson_player.myQuestions')" />
                                </Link>
                            </div>
                        </div>

                        <!-- Plan du cours groupé par module -->
                        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center"><i class="pi pi-list text-lg" aria-hidden="true"></i></div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.lesson_player.coursePlan') }}</h3>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.lesson_player.lessonsCount', { done: completedCount, total: totalCount }) }}</p>
                                </div>
                            </div>
                            <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1">
                                <div v-for="g in moduleGroups" :key="g.id">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <i class="pi pi-folder-open text-[10px] text-slate-400" aria-hidden="true"></i>
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest truncate">{{ g.title }}</span>
                                    </div>
                                    <div class="space-y-1">
                                        <Link
                                            v-for="l in g.lessons"
                                            :key="l.id"
                                            :href="route('student.lesson-player', { course: course.id, lesson: l.id })"
                                            :class="['flex items-center gap-2 p-2.5 rounded-xl text-xs font-bold transition-colors border', l.id === lesson?.id ? 'bg-red-50 text-red-700 border-red-100' : 'hover:bg-slate-50 text-slate-700 border-transparent']"
                                        >
                                            <i :class="l.is_completed ? 'pi pi-check-circle text-emerald-500' : (l.id === lesson?.id ? 'pi pi-play-circle text-red-500' : typeIcon(l.type))" class="text-xs shrink-0" :aria-hidden="true"></i>
                                            <span class="truncate flex-1">{{ l.title }}</span>
                                            <span v-if="l.duration" class="text-[9px] text-slate-400 font-mono shrink-0">{{ l.duration }}m</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Module Quiz Attempts -->
                        <div v-if="module && module.has_quiz && moduleQuizAttempts && moduleQuizAttempts.length > 0" class="bg-white rounded-xl p-5 border border-emerald-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center"><i class="pi pi-check-square text-lg" aria-hidden="true"></i></div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.lesson_player.quizAttemptsTitle') }}</h3>
                                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">{{ t('pages.lesson_player.passingScore', { score: module.quiz_passing_score }) }}</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div v-for="attempt in moduleQuizAttempts" :key="attempt.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="attempt.score === null ? 'bg-slate-100 text-slate-400' : (attempt.score >= 50 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600')">
                                            <span class="text-xs font-black">{{ attempt.score === null ? '?' : attempt.score + '%' }}</span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">
                                                <template v-if="attempt.score !== null">{{ attempt.score }}% ({{ attempt.score_out_of_20 }}/20)</template>
                                                <template v-else>{{ t('pages.lesson_player.noScoreYet') }}</template>
                                            </div>
                                            <div class="text-[9px] text-slate-400">{{ formatDateTime(attempt.date) }}</div>
                                        </div>
                                    </div>
                                    <span v-if="attempt.score !== null" class="text-[9px] font-black" :class="attempt.score >= 50 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ attempt.score >= 50 ? t('pages.lesson_player.quizPassed') : t('pages.lesson_player.quizFailed') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Supports de cours -->
                        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center"><i class="pi pi-file text-lg" aria-hidden="true"></i></div>
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ t('pages.lesson_player.materialsTitle', { count: courseMaterials.length }) }}</h3>
                            </div>
                            <div v-if="courseMaterials.length === 0" class="text-xs text-slate-400 italic text-center py-4">{{ t('pages.lesson_player.noMaterials') }}</div>
                            <div v-else class="space-y-2">
                                <button v-for="m in courseMaterials" :key="m.id" type="button" class="flex items-center gap-2 p-2.5 hover:bg-slate-50 rounded-xl w-full text-left transition-colors" @click="downloadMaterial(m)">
                                    <i class="pi pi-download text-blue-500 text-xs" aria-hidden="true"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate flex-1">{{ m.title }}</span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ m.type }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </main>
        </div>

        <SncDialog v-model:visible="showAskDialog" :title="t('pages.lesson_player.askDialogTitle')" icon="pi-question-circle" icon-class="from-amber-500 to-red-500" width="600px" :closable="!askSubmitting">
            <div class="space-y-4">
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3 flex items-center gap-3">
                    <i class="pi pi-info-circle text-amber-500" aria-hidden="true"></i>
                    <p class="text-xs text-amber-700">{{ t('pages.lesson_player.askDialogInfo') }}</p>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">{{ t('pages.lesson_player.fieldSubject') }}</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">{{ t('pages.lesson_player.fieldBody') }}</label>
                    <Textarea v-model="askForm.body" rows="5" class="w-full !rounded-xl" :placeholder="t('pages.lesson_player.bodyPlaceholder')" autoResize />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">{{ t('pages.lesson_player.fieldPriority') }}</label>
                    <Select v-model="askForm.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">{{ t('pages.lesson_player.fieldAttachments') }}</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">{{ t('pages.lesson_player.addFile') }}</span>
                        <input type="file" multiple class="hidden" @change="onAskFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="askFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in askFiles" :key="i" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 text-xs">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-blue-500' : 'pi pi-file text-blue-500'" aria-hidden="true"></i>
                            <span class="flex-1 truncate font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ formatFileSize(f.size) }}</span>
                            <button type="button" @click="removeAskFile(i)" class="w-5 h-5 rounded-full bg-red-100 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times text-[9px]" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="askErrors.form || askErrors.subject || askErrors.body" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3">
                    {{ askErrors.form || askErrors.subject || askErrors.body }}
                </div>
            </div>
            <template #footer>
                <Button :label="t('pages.lesson_player.cancel')" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl" />
                <Button :label="askSubmitting ? t('pages.lesson_player.sending') : t('pages.lesson_player.sendQuestion')" icon="pi pi-send" :loading="askSubmitting" @click="submitAsk" class="!bg-gradient-to-r !from-amber-500 !to-red-500 !border-none !rounded-xl" />
            </template>
        </SncDialog>
    </AppLayout>
</template>
