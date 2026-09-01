<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Button from 'primevue/button';

const props = defineProps({
    courses: Array,
    meta: Object,
});

const filter = ref('all');

const filteredCourses = computed(() => {
    if (filter.value === 'all') return props.courses ?? [];
    if (filter.value === 'in_progress') return (props.courses ?? []).filter(c => c.progress < 100);
    if (filter.value === 'completed') return (props.courses ?? []).filter(c => c.progress >= 100);
    return props.courses ?? [];
});

const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const getLevelLabel = (level) => {
    const map = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', all: 'Tous niveaux' };
    return map[level] || level;
};

const getProgressColor = (p) => {
    if (p >= 80) return '#10B981';
    if (p >= 50) return '#F59E0B';
    return '#8B5CF6';
};
</script>

<template>
    <AppLayout>
        <Head title="Mes formations" />

        <div class="min-h-screen bg-[#F8FAFC] py-10 px-6 font-sans">
            <div class="max-w-[1400px] mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <p class="text-[10px] font-black text-violet-500 uppercase tracking-widest">Espace apprenant</p>
                        <h1 class="text-3xl font-black text-slate-800 italic tracking-tight mt-1">Mes formations</h1>
                        <p class="text-sm text-slate-400 mt-2">
                            {{ meta?.total ?? 0 }} cours ·
                            <span class="text-emerald-600 font-bold">{{ meta?.completed ?? 0 }} terminés</span> ·
                            <span class="text-violet-600 font-bold">{{ meta?.in_progress ?? 0 }} en cours</span>
                        </p>
                    </div>
                    <div class="flex gap-2 bg-white p-1 rounded-xl border border-slate-100 shadow-sm">
                        <button v-for="f in [
                            { id: 'all', label: 'Tous' },
                            { id: 'in_progress', label: 'En cours' },
                            { id: 'completed', label: 'Terminés' },
                        ]" :key="f.id"
                                @click="filter = f.id"
                                :class="[
                                    'px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-colors',
                                    filter === f.id ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-50'
                                ]">
                            {{ f.label }}
                        </button>
                    </div>
                </div>

                <div v-if="filteredCourses.length === 0" class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-16 text-center">
                    <i class="pi pi-folder-open text-5xl text-slate-300 mb-4 block"></i>
                    <h3 class="text-xl font-black text-slate-700 italic mb-2">Aucune formation dans cette catégorie</h3>
                    <Link href="/" class="text-violet-600 font-bold hover:underline">Explorer le catalogue →</Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="course in filteredCourses" :key="course.id"
                         class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative h-40 overflow-hidden">
                            <img :src="getImageUrl(course.image)" class="w-full h-full object-cover" />
                            <div class="absolute top-3 left-3">
                                <Tag :value="getLevelLabel(course.level)" severity="info" class="text-[10px] font-black bg-white/95 text-violet-700 border-none" />
                            </div>
                            <div v-if="course.progress >= 100" class="absolute top-3 right-3">
                                <Tag value="Terminé" severity="success" class="text-[10px] font-black border-none" />
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <h3 class="font-black text-slate-800 italic line-clamp-2">{{ course.title }}</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <i class="pi pi-user text-violet-500"></i> {{ course.instructor }}
                                <span v-if="course.category"> · <i class="pi pi-tag text-amber-500"></i> {{ course.category }}</span>
                            </p>
                            <div class="space-y-1">
                                <div class="flex justify-between text-[10px] font-black text-slate-500 uppercase">
                                    <span>{{ course.lessons_done }}/{{ course.lessons_total }} leçons · {{ course.resources_count }} supports</span>
                                    <span :class="[
                                        course.progress >= 80 ? 'text-emerald-600' :
                                        course.progress >= 50 ? 'text-amber-600' : 'text-violet-600'
                                    ]">{{ Math.round(course.progress) }}%</span>
                                </div>
                                <ProgressBar :value="course.progress" :showValue="false" class="h-1.5 bg-slate-100"
                                             :style="`--p-progressbar-value-background: ${getProgressColor(course.progress)}`" />
                            </div>
                            <div class="flex gap-2 pt-2">
                                <Link v-if="course.next_lesson"
                                      :href="route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id })"
                                      class="flex-1">
                                    <Button :label="course.progress >= 100 ? 'Revoir' : 'Reprendre'" icon="pi pi-play"
                                            class="p-button-primary w-full font-black text-xs" />
                                </Link>
                                <Link :href="route('student.my-courses')" class="flex-none">
                                    <Button icon="pi pi-folder-open" outlined class="font-bold" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>