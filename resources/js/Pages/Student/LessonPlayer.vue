<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import RadioButton from 'primevue/radiobutton';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    course: Object,
    lesson: Object,
    module: Object,
    moduleQuizAttempts: Array,
    lessons: Array,
    progress: Object,
    courseResources: Object,
});

const toast = useToast();
const getImageUrl = (p) => !p ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070' : (p.startsWith('http') ? p : `/storage/${p}`);

// Helper pour les URLs de documents : l'API retourne maintenant l'URL complète (media disk + APP_URL)
const getDocumentUrl = (url) => {
    if (!url) return null;
    return url; // L'URL est déjà complète (absolue)
};

// Helper pour extraire l'ID YouTube et créer l'URL embed
const getYouTubeEmbedUrl = (url) => {
    if (!url) return null;
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    if (match && match[2].length === 11) {
        return `https://www.youtube.com/embed/${match[2]}`;
    }
    return null;
};

const downloadMaterial = (m) => m.download_url && window.open(m.download_url, '_blank', 'noopener');
const completedCount = computed(() => (props.lessons ?? []).filter(l => l.is_completed).length);

const videoRef = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const percent = ref(props.progress?.percent ?? 0);
const timeSpent = ref(props.progress?.time_spent_seconds ?? 0);
const isCompleted = computed(() => percent.value >= 99.5);
const courseMaterials = computed(() => props.courseResources?.[props.course?.id]?.materials ?? []);

const parsedQuiz = computed(() => {
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
};

const resetQuiz = () => {
    quizAnswers.value = {};
    quizSubmitted.value = false;
    quizScore.value = null;
};

watch(() => props.lesson?.id, () => {
    percent.value = props.progress?.percent ?? 0;
    currentTime.value = 0;
    resetQuiz();
});

let heartbeat = null;
let startedAt = Date.now();

const sendHeartbeat = async () => {
    if (!props.lesson?.id) return;
    try {
        const r = await window.axios?.post?.(route('student.lessons.update-progress', { course: props.course.id, lesson: props.lesson.id }), {
            progress_percent: percent.value,
            time_spent_seconds: 5,
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
                toast.add({ severity: 'success', summary: 'Leçon terminée', detail: 'Progression enregistrée.', life: 3000 });
                if (nextLesson.value) {
                    setTimeout(() => router.visit(route('student.lesson-player', { course: props.course.id, lesson: nextLesson.value.id })), 1500);
                }
            }
        });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de marquer la leçon.', life: 3000 });
    }
};

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
    toast.add({ severity: 'success', summary: 'Vidéo terminée', detail: 'Cliquez sur "Marquer comme terminé" pour valider.', life: 4000 });
};

onMounted(() => {
    startedAt = Date.now();
    heartbeat = setInterval(() => {
        if (isPlaying.value) {
            timeSpent.value += 1;
            if (Math.floor((Date.now() - startedAt) / 1000) % 30 === 0) {
                sendHeartbeat();
            }
        }
    }, 1000);
    setInterval(sendHeartbeat, 30000);
});

onBeforeUnmount(() => {
    if (heartbeat) clearInterval(heartbeat);
    sendHeartbeat();
});

// Q&A
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
const formatFileSize = (b) => b < 1024 ? `${b} o` : b < 1048576 ? `${(b / 1024).toFixed(1)} Ko` : `${(b / 1048576).toFixed(1)} Mo`;

const openAsk = () => {
    askForm.value = { subject: `Question sur : ${props.lesson?.title ?? ''}`, body: '', priority: 'normal' };
    askFiles.value = [];
    askErrors.value = {};
    showAskDialog.value = true;
};

const submitAsk = () => {
    if (!askForm.value.subject.trim() || !askForm.value.body.trim()) {
        askErrors.value = { form: 'Le sujet et le message sont obligatoires.' };
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
            toast.add({ severity: 'success', summary: 'Question envoyée', detail: page.props?.flash?.success || 'L\'instructeur répondra sous 24-48h.', life: 3500 });
        },
        onError: (e) => { askSubmitting.value = false; askErrors.value = e; },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="lesson?.title || 'Leçon'" />
        <div class="page-bg min-h-screen font-sans pb-20">

            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-play text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">{{ lesson?.title }}</h1>
                                <Tag :value="lesson?.type" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ course?.title }} · {{ course?.instructor }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-white/80 text-[10px] font-black uppercase tracking-widest">
                        <i class="pi pi-clock text-indigo-400"></i>
                        <span>Temps passé : {{ Math.floor(timeSpent / 60) }}m {{ timeSpent % 60 }}s</span>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div class="lg:col-span-8 space-y-5">
                        <!-- Module Intro & Content (affiché avant la leçon) -->
                        <div v-if="module" class="space-y-5">
                            <!-- Module Intro Video -->
                            <div v-if="module.intro_video_url" class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl aspect-video flex items-center justify-center">
                                <div v-if="getYouTubeEmbedUrl(module.intro_video_url)" class="w-full h-full">
                                    <iframe :src="getYouTubeEmbedUrl(module.intro_video_url)" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                                <div v-else-if="module.intro_video_url.startsWith('http')" class="w-full h-full">
                                    <video :src="module.intro_video_url" controls class="w-full h-full bg-black"></video>
                                </div>
                                <div v-else class="text-center text-white/70 p-8">
                                    <i class="pi pi-video text-5xl mb-3 block"></i>
                                    <p class="text-sm">Vidéo d'introduction non disponible.</p>
                                </div>
                            </div>

                            <!-- Module Text Content -->
                            <div v-if="module.text_content || module.description" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
                                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                                    <i class="pi pi-book text-indigo-500"></i>
                                    <h3 class="text-lg font-black text-slate-800">{{ module.title }}</h3>
                                </div>
                                <div v-if="module.description" class="text-slate-600 text-sm leading-relaxed border-l-4 border-indigo-500 pl-4 py-2 bg-indigo-50 rounded-r-xl">
                                    {{ module.description }}
                                </div>
                                <div v-if="module.text_content" class="prose prose-sm max-w-none text-slate-700" v-html="module.text_content"></div>
                            </div>
                        </div>

                        <!-- Video Player -->
                        <div v-if="lesson?.type === 'video'" class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl aspect-video flex items-center justify-center">
                            <video v-if="lesson?.video_url" ref="videoRef" :src="lesson.video_url" controls class="w-full h-full bg-black"
                                @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMeta" @play="onPlay" @pause="onPause" @ended="onEnded" />
                            <div v-else class="text-center text-white/70 p-8">
                                <i class="pi pi-video text-5xl mb-3 block"></i>
                                <p class="text-sm">Vidéo non disponible pour cette leçon.</p>
                            </div>
                        </div>

                        <!-- Document Viewer -->
                        <div v-else-if="lesson?.type === 'document'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center gap-3">
                                <i class="pi pi-file-pdf text-rose-500"></i>
                                <span class="text-sm font-black text-slate-700 uppercase tracking-wider">{{ lesson?.document_title || 'Document' }}</span>
                            </div>
                            <div class="p-6">
                                <div v-if="lesson?.document_url" class="space-y-4">
                                    <a :href="getDocumentUrl(lesson.document_url)" target="_blank" class="flex items-center gap-3 p-4 bg-indigo-50 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-colors">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600"><i class="pi pi-download"></i></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-bold text-slate-800 truncate">Ouvrir le document</div>
                                            <div class="text-[10px] text-slate-500 font-mono truncate">{{ getDocumentUrl(lesson.document_url) }}</div>
                                        </div>
                                        <i class="pi pi-external-link text-slate-400"></i>
                                    </a>
                                </div>
                                <div v-else class="text-sm text-slate-400 italic text-center py-8">
                                    <i class="pi pi-file text-4xl mb-3 block text-slate-300"></i>
                                    Aucun document disponible pour cette leçon.
                                </div>
                            </div>
                        </div>

                        <!-- Quiz Player -->
                        <div v-else-if="parsedQuiz" class="bg-white rounded-3xl border border-emerald-200 shadow-sm overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-3 flex items-center gap-3">
                                <i class="pi pi-check-square text-white text-lg"></i>
                                <div>
                                    <span class="text-sm font-black text-white uppercase tracking-wider">{{ parsedQuiz.quiz_title || 'Quiz' }}</span>
                                    <p class="text-[10px] text-white/80">{{ parsedQuiz.questions?.length || 0 }} questions</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <div v-if="!quizSubmitted" class="space-y-6">
                                    <div v-for="(q, qIdx) in parsedQuiz.questions" :key="qIdx" class="bg-slate-50 rounded-2xl p-5 border border-slate-200">
                                        <div class="flex items-start gap-3 mb-4">
                                            <span class="text-xs font-black text-emerald-600 bg-emerald-50 rounded-lg px-2 py-1 border border-emerald-100">Q{{ qIdx + 1 }}</span>
                                            <p class="text-sm font-bold text-slate-800 flex-1">{{ q.text }}</p>
                                        </div>
                                        
                                        <!-- QCM -->
                                        <div v-if="q.type === 'multiple_choice'" class="space-y-2">
                                            <label v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors">
                                                <RadioButton v-model="quizAnswers[qIdx]" :value="String(oIdx)" />
                                                <span class="text-sm text-slate-700">{{ opt }}</span>
                                            </label>
                                        </div>
                                        
                                        <!-- Vrai/Faux -->
                                        <div v-else-if="q.type === 'true_false'" class="flex gap-3">
                                            <label class="flex-1 flex items-center justify-center gap-2 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors" :class="{'border-emerald-500 bg-emerald-50': quizAnswers[qIdx] === 'true'}">
                                                <RadioButton v-model="quizAnswers[qIdx]" value="true" />
                                                <span class="text-sm font-bold text-slate-700">VRAI</span>
                                            </label>
                                            <label class="flex-1 flex items-center justify-center gap-2 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-300 transition-colors" :class="{'border-emerald-500 bg-emerald-50': quizAnswers[qIdx] === 'false'}">
                                                <RadioButton v-model="quizAnswers[qIdx]" value="false" />
                                                <span class="text-sm font-bold text-slate-700">FAUX</span>
                                            </label>
                                        </div>
                                        
                                        <!-- Réponse courte -->
                                        <div v-else-if="q.type === 'short_answer'" class="space-y-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase">Votre réponse</label>
                                            <InputText v-model="quizAnswers[qIdx]" class="w-full" placeholder="Saisissez votre réponse..." />
                                        </div>
                                    </div>
                                    <Button label="Soumettre le quiz" icon="pi pi-check" class="!bg-emerald-500 !border-none !rounded-xl !text-white !font-black !uppercase !tracking-widest" @click="submitQuiz" />
                                </div>
                                
                                <!-- Quiz Results -->
                                <div v-else class="text-center space-y-4">
                                    <div class="w-20 h-20 rounded-full mx-auto flex items-center justify-center text-2xl font-black" :class="quizScore >= 70 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                        {{ quizScore }}%
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">{{ quizScore >= 70 ? 'Bravo ! Vous avez réussi ce quiz.' : 'Vous n\'avez pas atteint le seuil de réussite.' }}</p>
                                    <Button label="Réessayer" icon="pi pi-redo" class="!bg-slate-100 !text-slate-700 !border-slate-200 !rounded-xl" @click="resetQuiz" />
                                </div>
                            </div>
                        </div>

                        <!-- Text/HTML Content -->
                        <div v-else class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                            <div v-if="lesson?.content" class="prose prose-sm max-w-none text-slate-700" v-html="lesson.content"></div>
                            <div v-else class="text-sm text-slate-400 italic">Aucun contenu textuel pour cette leçon.</div>
                        </div>
                    </div>

                    <aside class="lg:col-span-4 space-y-5">
                        <!-- Q&A du cours -->
                        <div class="bg-gradient-to-br from-amber-50 to-rose-50 rounded-3xl p-5 border border-amber-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-rose-500 rounded-xl flex items-center justify-center text-white shadow-md">
                                    <i class="pi pi-question-circle text-lg"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Besoin d'aide ?</h3>
                                    <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Posez une question à l'instructeur</p>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 mb-3">L'instructeur vous répond généralement sous 24-48h.</p>
                            <div class="flex gap-2">
                                <Button label="Poser une question" icon="pi pi-plus" @click="openAsk" class="!flex-1 !bg-gradient-to-r !from-amber-500 !to-rose-500 !border-none !rounded-xl !text-white !text-[10px] !font-black !uppercase !tracking-widest !py-2.5" />
                                <Link :href="route('student.questions.index')" class="inline-flex">
                                    <Button icon="pi pi-list" outlined class="!rounded-xl !text-amber-700 !border-amber-200" v-tooltip.bottom="'Mes questions'" />
                                </Link>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-list text-lg"></i></div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Plan du cours</h3>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ completedCount }}/{{ lessons?.length ?? 0 }} leçons</p>
                                </div>
                            </div>
                            <div class="space-y-1 max-h-96 overflow-y-auto pr-1">
                                <Link v-for="l in lessons" :key="l.id" :href="route('student.lesson-player', { course: course.id, lesson: l.id })" :class="['flex items-center justify-between p-2.5 rounded-xl text-xs font-bold transition-colors', l.id === lesson?.id ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'hover:bg-slate-50 text-slate-700 border border-transparent']">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <i :class="l.is_completed ? 'pi pi-check-circle text-emerald-500' : 'pi pi-circle text-slate-300'" class="text-xs"></i>
                                        <span class="truncate">{{ l.title }}</span>
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-mono">{{ l.duration }}m</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Module Quiz Attempts -->
                        <div v-if="module && module.has_quiz && moduleQuizAttempts && moduleQuizAttempts.length > 0" class="bg-white rounded-3xl p-5 border border-emerald-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center"><i class="pi pi-check-square text-lg"></i></div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Tentatives Quiz Module</h3>
                                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Score de passage : {{ module.quiz_passing_score }}%</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div v-for="attempt in moduleQuizAttempts" :key="attempt.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="attempt.score >= 50 ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                            <span class="text-xs font-black">{{ attempt.score }}%</span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">{{ attempt.score }}% ({{ attempt.score_out_of_20 }}/20)</div>
                                            <div class="text-[9px] text-slate-400">{{ new Date(attempt.date).toLocaleDateString('fr-FR', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'}) }}</div>
                                        </div>
                                    </div>
                                    <span class="text-[9px] font-black" :class="attempt.score >= 50 ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ attempt.score >= 50 ? 'Réussi' : 'Échoué' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center"><i class="pi pi-file text-lg"></i></div>
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Supports ({{ courseMaterials.length }})</h3>
                            </div>
                            <div v-if="courseMaterials.length === 0" class="text-xs text-slate-400 italic text-center py-4">Aucun support pour ce cours.</div>
                            <div v-else class="space-y-2">
                                <button v-for="m in courseMaterials" :key="m.id" type="button" class="flex items-center gap-2 p-2.5 hover:bg-slate-50 rounded-xl w-full text-left transition-colors" @click="downloadMaterial(m)">
                                    <i class="pi pi-download text-indigo-500 text-xs"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate flex-1">{{ m.title }}</span>
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ m.type }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '600px' }" header="Poser une question sur cette leçon" class="!rounded-3xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3 flex items-center gap-3">
                    <i class="pi pi-info-circle text-amber-500"></i>
                    <p class="text-xs text-amber-700">Votre instructeur vous répondra généralement sous 24-48h.</p>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Sujet</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Message détaillé</label>
                    <Textarea v-model="askForm.body" rows="5" class="w-full !rounded-xl" placeholder="Décrivez votre question ou votre blocage..." autoResize />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Priorité</label>
                    <Select v-model="askForm.priority" :options="[{label:'Faible', value:'low'},{label:'Normale', value:'normal'},{label:'Importante', value:'high'},{label:'Bloquante', value:'urgent'}]" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Pièces jointes (capture, fichier… · 5 max · 10Mo)</label>
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
                <Button :label="askSubmitting ? 'Envoi...' : 'Envoyer la question'" icon="pi pi-send" :loading="askSubmitting" @click="submitAsk" class="!bg-gradient-to-r !from-amber-500 !to-rose-500 !border-none !rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
