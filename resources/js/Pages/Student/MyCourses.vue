<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({ courses: Array, meta: Object });

const filters = [
    { id: 'all', label: 'Tous' },
    { id: 'in_progress', label: 'En cours' },
    { id: 'completed', label: 'Terminés' },
    { id: 'soldee', label: 'Soldés' },
    { id: 'pending', label: 'En attente' },
];
const filter = ref('all');

const matches = (c, id) => ({
    all: true,
    in_progress: c.progress < 100,
    completed: c.progress >= 100,
    soldee: !!c.is_soldee,
    pending: c.status === 'pending',
}[id] ?? true);

const counts = computed(() => Object.fromEntries(filters.map(f => [f.id, (props.courses ?? []).filter(c => matches(c, f.id)).length])));
const filteredCourses = computed(() => (props.courses ?? []).filter(c => matches(c, filter.value)));

const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600' : (path.startsWith('http') ? path : `/storage/${path}`);
const getLevelLabel = (level) => ({ beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé' }[level] || level);
const progressTone = (p) => p >= 80 ? { bar: '#10B981', text: 'text-emerald-600 dark:text-emerald-400' } : p >= 50 ? { bar: '#F59E0B', text: 'text-amber-600 dark:text-amber-400' } : { bar: '#2563EB', text: 'text-blue-600 dark:text-blue-400' };
const ctaLabel = (c) => c.progress >= 100 ? 'Revoir le cours' : c.progress > 0 ? 'Reprendre' : 'Commencer';
</script>

<template>
    <AppLayout>
        <Head title="Mes formations" />
        <div class="page-bg min-h-screen">
            <PageHeader
                title="Mes formations"
                icon="pi-book"
                :badge="`${meta?.total ?? 0} cours`"
                :subtitle="`${meta?.completed ?? 0} terminé(s) · ${meta?.in_progress ?? 0} en cours. Reprenez là où vous vous êtes arrêté.`"
                :actions="[{ label: 'Explorer le catalogue', icon: 'pi-search', href: route('marketplace.index') }]"
            />

            <div class="page-container space-y-6">
                <div class="page-card-flush p-2 flex gap-1 overflow-x-auto" role="tablist" aria-label="Filtrer mes formations">
                    <button
                        v-for="f in filters"
                        :key="f.id"
                        type="button"
                        role="tab"
                        :aria-selected="filter === f.id"
                        @click="filter = f.id"
                        :class="[
                            'shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-colors inline-flex items-center gap-2',
                            filter === f.id
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                        ]"
                    >
                        {{ f.label }}
                        <span :class="['text-xs rounded-full px-1.5 min-w-[1.25rem]', filter === f.id ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400']">{{ counts[f.id] }}</span>
                    </button>
                </div>

                <div v-if="filteredCourses.length === 0" class="page-card-flush">
                    <EmptyState
                        icon="pi-folder-open"
                        :title="filter === 'all' ? 'Vous n\'êtes inscrit à aucune formation' : 'Aucune formation dans ce filtre'"
                        :description="filter === 'all' ? 'Parcourez le catalogue pour trouver la formation qui vous correspond.' : 'Essayez un autre filtre ou explorez le catalogue.'"
                        action-label="Explorer le catalogue"
                        :action-href="route('marketplace.index')"
                    >
                        <button v-if="filter !== 'all'" type="button" class="btn-action-secondary" @click="filter = 'all'">Voir toutes mes formations</button>
                    </EmptyState>
                </div>

                <ul v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" aria-label="Liste de mes formations">
                    <li v-for="course in filteredCourses" :key="course.id" class="page-card-flush flex flex-col group hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                        <div class="relative h-40 overflow-hidden rounded-t-[1rem] bg-slate-100 dark:bg-slate-800">
                            <img :src="getImageUrl(course.image)" :alt="''" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" />
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-md bg-white/95 text-slate-800 text-xs font-semibold shadow-sm">{{ getLevelLabel(course.level) }}</span>
                            <div class="absolute top-3 right-3 flex flex-col gap-1 items-end">
                                <Tag v-if="course.is_soldee" value="Soldé" icon="pi pi-check" severity="success" />
                                <Tag v-if="course.status === 'pending'" value="En attente" severity="warn" />
                                <Tag v-else-if="course.progress >= 100" value="Terminé" severity="info" />
                            </div>
                        </div>
                        <div class="p-5 flex flex-col gap-3 flex-1">
                            <h3 class="font-bold text-slate-900 dark:text-slate-100 line-clamp-2 leading-snug">{{ course.title }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 flex flex-wrap gap-x-3 gap-y-1">
                                <span><i class="pi pi-user text-[0.7rem] mr-1" aria-hidden="true"></i>{{ course.instructor }}</span>
                                <span v-if="course.category"><i class="pi pi-tag text-[0.7rem] mr-1" aria-hidden="true"></i>{{ course.category }}</span>
                            </p>
                            <div class="mt-auto space-y-1.5">
                                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ course.lessons_done }}/{{ course.lessons_total }} leçons · {{ course.resources_count }} supports</span>
                                    <span :class="['font-bold', progressTone(course.progress).text]">{{ Math.round(course.progress) }} %</span>
                                </div>
                                <ProgressBar
                                    :value="course.progress"
                                    :showValue="false"
                                    class="!h-1.5"
                                    :aria-label="`Progression : ${Math.round(course.progress)} %`"
                                    :style="`--p-progressbar-value-background: ${progressTone(course.progress).bar}`"
                                />
                            </div>
                            <Link
                                v-if="course.next_lesson"
                                :href="route('student.lesson-player', { course: course.id, lesson: course.next_lesson.id })"
                                class="btn-action-primary w-full mt-2"
                            >
                                <i :class="['pi text-xs', course.progress >= 100 ? 'pi-replay' : 'pi-play']" aria-hidden="true"></i>
                                {{ ctaLabel(course) }}
                            </Link>
                            <p v-else-if="course.status === 'pending'" class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/10 rounded-lg px-3 py-2 mt-2">
                                Votre inscription est en cours de validation.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
