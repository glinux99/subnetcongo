<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({ courses: Array, meta: Object });

const filters = computed(() => [
    { id: 'all', label: t('pages.my_courses.filterAll') },
    { id: 'in_progress', label: t('pages.my_courses.filterInProgress') },
    { id: 'completed', label: t('pages.my_courses.filterCompleted') },
    { id: 'soldee', label: t('pages.my_courses.filterSoldee') },
    { id: 'pending', label: t('pages.my_courses.filterPending') },
]);
const filter = ref('all');

const matches = (c, id) => ({
    all: true,
    in_progress: c.progress < 100,
    completed: c.progress >= 100,
    soldee: !!c.is_soldee,
    pending: c.status === 'pending',
}[id] ?? true);

const counts = computed(() => Object.fromEntries(filters.value.map(f => [f.id, (props.courses ?? []).filter(c => matches(c, f.id)).length])));
const filteredCourses = computed(() => (props.courses ?? []).filter(c => matches(c, filter.value)));

const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600' : (path.startsWith('http') ? path : `/storage/${path}`);
const getLevelLabel = (level) => ({ beginner: t('pages.my_courses.levelBeginner'), intermediate: t('pages.my_courses.levelIntermediate'), advanced: t('pages.my_courses.levelAdvanced') }[level] || level);
const progressTone = (p) => p >= 80 ? { bar: '#10B981', text: 'text-emerald-600 dark:text-emerald-400' } : p >= 50 ? { bar: '#F59E0B', text: 'text-amber-600 dark:text-amber-400' } : { bar: '#2563EB', text: 'text-blue-600 dark:text-blue-400' };
const ctaLabel = (c) => c.progress >= 100 ? t('pages.my_courses.ctaReview') : c.progress > 0 ? t('pages.my_courses.ctaResume') : t('pages.my_courses.ctaStart');
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.my_courses.headTitle')" />
        <div class="page-bg min-h-screen">
            <PageHeader
                :title="t('pages.my_courses.pageTitle')"
                icon="pi-book"
                :badge="t('pages.my_courses.coursesBadge', { count: meta?.total ?? 0 })"
                :subtitle="t('pages.my_courses.pageSubtitle', { completed: meta?.completed ?? 0, in_progress: meta?.in_progress ?? 0 })"
                :actions="[{ label: t('pages.my_courses.actionExplore'), icon: 'pi-search', href: route('marketplace.index') }]"
            />

            <div class="page-container space-y-6">
                <div class="page-card-flush p-2 flex gap-1 overflow-x-auto" role="tablist" :aria-label="t('pages.my_courses.filterAria')">
                    <button
                        v-for="f in filters"
                        :key="f.id"
                        type="button"
                        role="tab"
                        :aria-selected="filter === f.id"
                        @click="filter = f.id"
                        :class="[
                            'shrink-0 px-3 py-2 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-colors inline-flex items-center gap-2 min-h-[2.75rem]',
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
                        :title="filter === 'all' ? t('pages.my_courses.emptyTitleAll') : t('pages.my_courses.emptyTitleFiltered')"
                        :description="filter === 'all' ? t('pages.my_courses.emptyDescAll') : t('pages.my_courses.emptyDescFiltered')"
                        :action-label="t('pages.my_courses.actionExplore')"
                        :action-href="route('marketplace.index')"
                    >
                        <button v-if="filter !== 'all'" type="button" class="btn-action-secondary" @click="filter = 'all'">{{ t('pages.my_courses.viewAllBtn') }}</button>
                    </EmptyState>
                </div>

                <ul v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6" :aria-label="t('pages.my_courses.listAria')">
                    <li v-for="course in filteredCourses" :key="course.id" class="page-card-flush flex flex-col group hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200">
                        <div class="relative h-32 sm:h-40 overflow-hidden rounded-t-[1rem] bg-slate-100 dark:bg-slate-800">
                            <img :src="getImageUrl(course.image)" :alt="''" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" />
                            <span class="absolute top-2 sm:top-3 left-2 sm:left-3 px-2 py-0.5 sm:py-1 rounded-md bg-white/95 text-slate-800 text-[10px] sm:text-xs font-semibold shadow-sm">{{ getLevelLabel(course.level) }}</span>
                            <div class="absolute top-2 sm:top-3 right-2 sm:right-3 flex flex-col gap-1 items-end">
                                <Tag v-if="course.is_soldee" :value="t('pages.my_courses.tagSoldee')" icon="pi pi-check" severity="success" class="!text-[9px] sm:!text-xs" />
                                <Tag v-if="course.status === 'pending'" :value="t('pages.my_courses.tagPending')" severity="warn" class="!text-[9px] sm:!text-xs" />
                                <Tag v-else-if="course.progress >= 100" :value="t('pages.my_courses.tagCompleted')" severity="info" class="!text-[9px] sm:!text-xs" />
                            </div>
                        </div>
                        <div class="p-4 sm:p-5 flex flex-col gap-2 sm:gap-3 flex-1">
                            <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-slate-100 line-clamp-2 leading-snug">{{ course.title }}</h3>
                            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 flex flex-wrap gap-x-2 sm:gap-x-3 gap-y-1">
                                <span><i class="pi pi-user text-[0.6rem] sm:text-[0.7rem] mr-1" aria-hidden="true"></i>{{ course.instructor }}</span>
                                <span v-if="course.category"><i class="pi pi-tag text-[0.6rem] sm:text-[0.7rem] mr-1" aria-hidden="true"></i>{{ course.category }}</span>
                            </p>
                            <div class="mt-auto space-y-1.5">
                                <div class="flex justify-between text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">
                                    <span>{{ t('pages.my_courses.lessonsSummary', { done: course.lessons_done, total: course.lessons_total, count: course.resources_count }) }}</span>
                                    <span :class="['font-bold', progressTone(course.progress).text]">{{ Math.round(course.progress) }} %</span>
                                </div>
                                <ProgressBar
                                    :value="course.progress"
                                    :showValue="false"
                                    class="!h-1.5"
                                    :aria-label="t('pages.my_courses.progressAria', { percent: Math.round(course.progress) })"
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
                            <p v-else-if="course.status === 'pending'" class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/10 rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 mt-2">
                                {{ t('pages.my_courses.pendingNote') }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
