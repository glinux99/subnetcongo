<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Button from 'primevue/button';
import Card from 'primevue/card';

const props = defineProps({ 
    courses: Array, 
    meta: Object,
    statistics: Object
});

const filter = ref('all');
const filteredCourses = computed(() => {
    const all = props.courses ?? [];
    if (filter.value === 'all')         return all;
    if (filter.value === 'in_progress') return all.filter(c => c.progress < 100 && c.progress > 0);
    if (filter.value === 'not_started') return all.filter(c => c.progress === 0);
    if (filter.value === 'completed')   return all.filter(c => c.progress >= 100);
    if (filter.value === 'soldee')      return all.filter(c => c.is_soldee);
    if (filter.value === 'pending')     return all.filter(c => c.status === 'pending');
    return all;
});

const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600' : (path.startsWith('http') ? path : `/storage/${path}`);
const getLevelLabel = (level) => ({ beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', all: 'Tous niveaux' }[level] || level);
const getProgressColor = (p) => p >= 80 ? '#10B981' : p >= 50 ? '#F59E0B' : p >= 20 ? '#8B5CF6' : '#EF4444';
const getLevelColor = (level) => ({ beginner: 'bg-emerald-100 text-emerald-700', intermediate: 'bg-amber-100 text-amber-700', advanced: 'bg-rose-100 text-rose-700', all: 'bg-slate-100 text-slate-700' }[level] || 'bg-slate-100 text-slate-700');

const stats = computed(() => ({
    total: props.courses?.length ?? 0,
    completed: props.courses?.filter(c => c.progress >= 100).length ?? 0,
    in_progress: props.courses?.filter(c => c.progress > 0 && c.progress < 100).length ?? 0,
    not_started: props.courses?.filter(c => c.progress === 0).length ?? 0,
    total_hours: props.statistics?.total_learning_hours ?? 0,
    certificates: props.statistics?.certificates_count ?? 0
}));
</script>

<template>
    <AppLayout>
        <Head title="Mes formations" />
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/30 to-emerald-50/30">
            
            <!-- Header Section -->
            <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-indigo-700 to-emerald-600 text-white">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
                <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-white/5 rounded-full blur-[120px]"></div>
                <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-emerald-400/10 rounded-full blur-[100px]"></div>
                
                <div class="max-w-[1600px] mx-auto px-4 lg:px-8 py-12 relative z-10">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
                        <div class="flex items-center gap-6">
                            <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/20 shadow-xl">
                                <i class="pi pi-graduation-cap text-4xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-4xl font-black tracking-tight mb-2">Mes formations</h1>
                                <p class="text-indigo-200 font-medium">
                                    <span class="text-emerald-300 font-bold">{{ stats.completed }} terminés</span> ·
                                    <span class="text-amber-300 font-bold">{{ stats.in_progress }} en cours</span> ·
                                    <span class="text-slate-300">{{ stats.not_started }} à démarrer</span>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Stats Cards -->
                        <div class="flex gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 border border-white/20">
                                <div class="text-2xl font-black">{{ stats.total_hours }}h</div>
                                <div class="text-xs text-indigo-200 uppercase tracking-widest">Temps total</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 border border-white/20">
                                <div class="text-2xl font-black">{{ stats.certificates }}</div>
                                <div class="text-xs text-indigo-200 uppercase tracking-widest">Certificats</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-8 -mt-6 relative z-20">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-2 inline-flex gap-2">
                    <button 
                        v-for="f in [
                            { id: 'all', label: 'Tous', count: stats.total },
                            { id: 'in_progress', label: 'En cours', count: stats.in_progress },
                            { id: 'not_started', label: 'À démarrer', count: stats.not_started },
                            { id: 'completed', label: 'Terminés', count: stats.completed },
                            { id: 'soldee', label: '✓ Soldés', count: courses.filter(c => c.is_soldee).length },
                        ]" 
                        :key="f.id" 
                        @click="filter = f.id" 
                        :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all', filter === f.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-slate-500 hover:bg-slate-100']"
                    >
                        {{ f.label }}
                        <span v-if="f.count > 0" class="ml-2 px-2 py-0.5 rounded-full text-[10px]" :class="filter === f.id ? 'bg-white/20' : 'bg-slate-200'">{{ f.count }}</span>
                    </button>
                </div>
            </div>

            <!-- Courses Grid -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-8 py-8">
                <div v-if="filteredCourses.length === 0" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-16 text-center">
                    <div class="w-24 h-24 bg-slate-100 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-folder-open text-5xl text-slate-300"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-700 mb-3">Aucune formation dans cette catégorie</h3>
                    <p class="text-slate-500 mb-6">Explorez notre catalogue pour découvrir de nouvelles formations</p>
                    <Link :href="route('marketplace.index')">
                        <Button label="Explorer le catalogue" icon="pi pi-arrow-right" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 !px-6" />
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="course in filteredCourses" :key="course.id" class="group bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-2xl hover:border-indigo-300 transition-all duration-300">
                        <!-- Thumbnail -->
                        <div class="relative h-48 overflow-hidden">
                            <img :src="getImageUrl(course.thumbnail_path || course.image)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            
                            <div class="absolute top-3 left-3 flex gap-2">
                                <Tag :value="getLevelLabel(course.level)" :class="`!border-none !text-[10px] !font-black !uppercase !tracking-widest ${getLevelColor(course.level)}`" />
                            </div>
                            
                            <div class="absolute top-3 right-3 flex flex-col gap-1 items-end">
                                <Tag v-if="course.is_soldee" value="✓ Soldé" severity="success" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest !bg-emerald-500 !text-white" />
                                <Tag v-if="course.status === 'pending'" value="En attente" severity="warn" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest !bg-amber-500 !text-white" />
                                <Tag v-else-if="course.progress >= 100" value="Terminé" severity="info" class="!border-none !text-[10px] !font-black !uppercase !tracking-widest !bg-indigo-500 !text-white" />
                            </div>

                            <div class="absolute bottom-3 left-3 right-3">
                                <div class="flex items-center gap-2 text-white text-xs font-medium">
                                    <i class="pi pi-user"></i>
                                    <span>{{ course.instructor }}</span>
                                    <span v-if="course.category" class="text-white/70">· {{ course.category }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5 space-y-4">
                            <div>
                                <h3 class="font-black text-slate-800 line-clamp-2 text-lg tracking-tight mb-2">{{ course.title }}</h3>
                                <p class="text-xs text-slate-500 line-clamp-2">{{ course.short_info || course.description }}</p>
                            </div>

                            <!-- Progress -->
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        <i class="pi pi-book text-indigo-500"></i> {{ course.lessons_done || 0 }}/{{ course.lessons_total || 0 }} leçons
                                    </span>
                                    <span :class="['text-xs font-black', course.progress >= 80 ? 'text-emerald-600' : course.progress >= 50 ? 'text-amber-600' : 'text-indigo-600']">
                                        {{ Math.round(course.progress) }}%
                                    </span>
                                </div>
                                <ProgressBar :value="course.progress" :showValue="false" class="!h-2 !bg-slate-100 !rounded-full" :style="`--p-progressbar-value-background: ${getProgressColor(course.progress)}`" />
                            </div>

                            <!-- Action -->
                            <Link v-if="course.next_lesson" :href="route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id })" class="block">
                                <Button 
                                    :label="course.progress >= 100 ? 'Revoir le cours' : course.progress > 0 ? 'Reprendre' : 'Commencer'" 
                                    :icon="course.progress >= 100 ? 'pi pi-refresh' : 'pi pi-play'" 
                                    :class="['w-full !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[11px] !py-3 transition-all', course.progress >= 100 ? '!bg-slate-700 hover:!bg-slate-600' : '!bg-indigo-600 hover:!bg-indigo-500']" 
                                />
                            </Link>
                            <Link v-else :href="route('courses.show', course.id)" class="block">
                                <Button label="Voir le cours" icon="pi pi-eye" class="w-full !bg-slate-100 hover:!bg-slate-200 !text-slate-700 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[11px] !py-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>