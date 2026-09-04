<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Button from 'primevue/button';

const props = defineProps({ courses: Array, meta: Object });
const filter = ref('all');
const filteredCourses = computed(() => {
    const all = props.courses ?? [];
    if (filter.value === 'all')         return all;
    if (filter.value === 'in_progress') return all.filter(c => c.progress < 100);
    if (filter.value === 'completed')   return all.filter(c => c.progress >= 100);
    if (filter.value === 'soldee')      return all.filter(c => c.is_soldee);
    if (filter.value === 'pending')     return all.filter(c => c.status === 'pending');
    return all;
});
const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600' : (path.startsWith('http') ? path : `/storage/${path}`);
const getLevelLabel = (level) => ({ beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé' }[level] || level);
const getProgressColor = (p) => p >= 80 ? '#10B981' : p >= 50 ? '#F59E0B' : '#8B5CF6';
</script>

<template>
    <AppLayout>
        <Head title="Mes formations" />
        <div class="page-bg min-h-screen font-sans pb-20">

            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-book text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">Mes formations</h1>
                                <Tag :value="`${meta?.total ?? 0} cours`" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">
                                <span class="text-emerald-400 font-bold">{{ meta?.completed ?? 0 }} terminés</span> ·
                                <span class="text-indigo-300 font-bold">{{ meta?.in_progress ?? 0 }} en cours</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 bg-slate-800/60 p-1 rounded-xl border border-slate-700/50 flex-wrap">
                        <button v-for="f in [
                            { id: 'all', label: 'Tous' },
                            { id: 'in_progress', label: 'En cours' },
                            { id: 'soldee', label: '✓ Soldés' },
                            { id: 'completed', label: 'Terminés' },
                            { id: 'pending', label: 'En attente' },
                        ]" :key="f.id" @click="filter = f.id" :class="['px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-colors', filter === f.id ? 'bg-indigo-500 text-white' : 'text-slate-300 hover:bg-slate-700/50']">
                            {{ f.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div v-if="filteredCourses.length === 0" class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-folder-open text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-700">Aucune formation dans cette catégorie</h3>
                        <Link :href="route('marketplace.index')" class="text-indigo-600 font-black uppercase tracking-widest text-xs hover:underline">Explorer le catalogue →</Link>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="course in filteredCourses" :key="course.id" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-indigo-300 transition-all">
                            <div class="relative h-40 overflow-hidden">
                                <img :src="getImageUrl(course.image)" class="w-full h-full object-cover" />
                                <Tag :value="getLevelLabel(course.level)" class="!absolute !top-3 !left-3 !bg-white/95 !text-indigo-700 !border-none !text-[10px] !font-black !uppercase !tracking-widest" />
                                <div class="!absolute !top-3 !right-3 flex flex-col gap-1 items-end">
                                    <Tag v-if="course.is_soldee" value="✓ Soldé" severity="success" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest" />
                                    <Tag v-if="course.status === 'pending'" value="En attente" severity="warn" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest" />
                                    <Tag v-else-if="course.progress >= 100" value="Terminé" severity="info" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest" />
                                </div>
                            </div>
                            <div class="p-5 space-y-3">
                                <h3 class="font-black text-slate-800 line-clamp-2 tracking-tight">{{ course.title }}</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    <i class="pi pi-user text-indigo-500"></i> {{ course.instructor }}
                                    <span v-if="course.category"> · <i class="pi pi-tag text-amber-500"></i> {{ course.category }}</span>
                                </p>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        <span>{{ course.lessons_done }}/{{ course.lessons_total }} leçons · {{ course.resources_count }} supports</span>
                                        <span :class="course.progress >= 80 ? 'text-emerald-600' : course.progress >= 50 ? 'text-amber-600' : 'text-indigo-600'">{{ Math.round(course.progress) }}%</span>
                                    </div>
                                    <ProgressBar :value="course.progress" :showValue="false" class="!h-1.5 !bg-slate-100" :style="`--p-progressbar-value-background: ${getProgressColor(course.progress)}`" />
                                </div>
                                <div class="flex gap-2 pt-2">
                                    <Link v-if="course.next_lesson" :href="route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id })" class="flex-1">
                                        <Button :label="course.progress >= 100 ? 'Revoir' : 'Reprendre'" icon="pi pi-play" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-2.5 w-full" />
                                    </Link>
                                    <Button icon="pi pi-folder-open" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-bold" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>