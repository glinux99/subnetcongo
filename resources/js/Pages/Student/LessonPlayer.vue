<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    course: Object,
    lesson: Object,
    lessons: Array,
    progress: Object,
    courseResources: Object,
});

const toast = useToast();
const getImageUrl = (p) => !p ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070' : (p.startsWith('http') ? p : `/storage/${p}`);
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

watch(() => props.lesson?.id, () => {
    percent.value = props.progress?.percent ?? 0;
    currentTime.value = 0;
});
</script>

<template>
    <AppLayout>
        <Head :title="lesson?.title || 'Leçon'" />
        <Toast />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
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
                        <div class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl aspect-video flex items-center justify-center">
                            <video v-if="lesson?.video_url" ref="videoRef" :src="lesson.video_url" controls class="w-full h-full bg-black"
                                @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMeta" @play="onPlay" @pause="onPause" @ended="onEnded" />
                            <div v-else class="text-center text-white/70 p-8">
                                <i class="pi pi-video text-5xl mb-3 block"></i>
                                <p class="text-sm">Contenu de la leçon (type : {{ lesson?.type }})</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                            <div class="flex justify-between items-start gap-4 mb-4 flex-wrap">
                                <h1 class="text-2xl font-black text-slate-800 tracking-tight">{{ lesson?.title }}</h1>
                                <div class="flex items-center gap-2">
                                    <Button v-if="prevLesson" as="Link" :href="route('student.lesson-player', { course: course.id, lesson: prevLesson.id })" icon="pi pi-chevron-left" label="Précédent" outlined class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5" />
                                    <Button :label="isCompleted ? 'Terminée' : 'Marquer comme terminé'" :icon="isCompleted ? 'pi pi-check-circle' : 'pi pi-check'" :disabled="isCompleted" @click="markComplete" :class="[isCompleted ? '!bg-emerald-100 !text-emerald-700 !border-emerald-200' : '!bg-emerald-500 !text-white !border-none', '!rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-2.5']" />
                                    <Button v-if="nextLesson" as="Link" :href="route('student.lesson-player', { course: course.id, lesson: nextLesson.id })" label="Suivant" icon="pi pi-chevron-right" iconPos="right" outlined class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5" />
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                                    <span>Votre progression sur cette leçon</span>
                                    <span class="font-mono">{{ Math.round(percent) }}%</span>
                                </div>
                                <ProgressBar :value="percent" :showValue="false" class="!h-1.5 !bg-slate-100" />
                            </div>
                            <div v-if="lesson?.content" class="prose prose-sm max-w-none text-slate-700" v-html="lesson.content"></div>
                            <div v-else class="text-sm text-slate-400 italic">Aucun contenu textuel pour cette leçon.</div>
                        </div>
                    </div>

                    <aside class="lg:col-span-4 space-y-5">
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
    </AppLayout>
</template>
