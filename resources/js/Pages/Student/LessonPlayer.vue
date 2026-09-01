<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const props = defineProps({
    course: Object,
    lesson: Object,
    lessons: Array,
    progress: Object,
    courseResources: Object,
});

const courseMaterials = computed(() => props.courseResources?.[props.course?.id]?.materials ?? []);
const courseVideos = computed(() => props.courseResources?.[props.course?.id]?.videos ?? []);

const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const downloadMaterial = (m) => {
    if (!m.download_url) return;
    window.open(m.download_url, '_blank', 'noopener');
};
</script>

<template>
    <AppLayout>
        <Head :title="lesson?.title || 'Leçon'" />

        <div class="min-h-screen bg-[#F8FAFC] py-8 px-6 font-sans">
            <div class="max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Main player -->
                <div class="lg:col-span-8 space-y-5">
                    <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-2xl aspect-video flex items-center justify-center">
                        <video v-if="lesson?.video_url" :src="lesson.video_url" controls autoplay
                               class="w-full h-full bg-black" />
                        <div v-else class="text-center text-white/70 p-8">
                            <i class="pi pi-video text-5xl mb-3 block"></i>
                            <p class="text-sm">Contenu de la leçon (type : {{ lesson?.type }})</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                        <div class="flex justify-between items-start gap-4 mb-4">
                            <div>
                                <Tag :value="lesson?.type" severity="info" class="text-[10px] uppercase font-black mb-2" />
                                <h1 class="text-2xl font-black text-slate-800 italic">{{ lesson?.title }}</h1>
                                <p class="text-xs text-slate-400 mt-1">
                                    Cours : <strong>{{ course?.title }}</strong> · {{ course?.instructor }}
                                </p>
                            </div>
                            <Button label="Marquer comme terminé" icon="pi pi-check"
                                    class="p-button-success font-black"
                                    disabled />
                        </div>

                        <div v-if="progress" class="mb-4">
                            <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase mb-1">
                                <span>Votre progression sur cette leçon</span>
                                <span>{{ Math.round(progress.percent) }}%</span>
                            </div>
                            <ProgressBar :value="progress.percent" :showValue="false" class="h-1.5 bg-slate-100" />
                        </div>

                        <div v-if="lesson?.content" class="prose prose-sm max-w-none text-slate-700" v-html="lesson.content"></div>
                        <div v-else class="text-sm text-slate-400 italic">Aucun contenu textuel pour cette leçon.</div>
                    </div>
                </div>

                <!-- Sidebar: lessons + resources -->
                <aside class="lg:col-span-4 space-y-5">
                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
                        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="pi pi-list text-violet-500"></i> Plan du cours
                        </h3>
                        <div class="space-y-1 max-h-96 overflow-y-auto">
                            <Link v-for="l in lessons" :key="l.id"
                                  :href="route('student.lesson-player', { course: course.id, lesson: l.id })"
                                  :class="[
                                      'flex items-center justify-between p-2 rounded-lg text-xs font-bold transition-colors',
                                      l.id === lesson?.id ? 'bg-violet-50 text-violet-700' : 'hover:bg-slate-50 text-slate-700'
                                  ]">
                                <span class="flex items-center gap-2 min-w-0">
                                    <i :class="l.is_completed ? 'pi pi-check-circle text-emerald-500' : 'pi pi-circle text-slate-300'" class="text-xs"></i>
                                    <span class="truncate">{{ l.title }}</span>
                                </span>
                                <span class="text-[9px] text-slate-400">{{ l.duration }}m</span>
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
                        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="pi pi-file text-rose-500"></i> Supports ({{ courseMaterials.length }})
                        </h3>
                        <div v-if="courseMaterials.length === 0" class="text-xs text-slate-400 italic text-center py-4">
                            Aucun support pour ce cours.
                        </div>
                        <div v-else class="space-y-2">
                            <button v-for="m in courseMaterials" :key="m.id" type="button"
                                    class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded-lg w-full text-left"
                                    @click="downloadMaterial(m)">
                                <i class="pi pi-download text-violet-500 text-xs"></i>
                                <span class="text-xs font-bold text-slate-700 truncate flex-1">{{ m.title }}</span>
                                <span class="text-[9px] text-slate-400 uppercase">{{ m.type }}</span>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>