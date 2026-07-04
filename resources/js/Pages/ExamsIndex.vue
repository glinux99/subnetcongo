<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    exams: Array
});

const getStatus = (exam) => {
    if (exam.is_passed) return { label: 'Réussi', sev: 'success', icon: 'pi pi-check-circle' };
    if (exam.active_attempt_uuid) return { label: 'En cours', sev: 'warning', icon: 'pi pi-spinner pi-spin' };
    if (exam.attempts_count > 0) return { label: 'Échoué', sev: 'danger', icon: 'pi pi-times-circle' };
    return { label: 'Disponible', sev: 'info', icon: 'pi pi-circle' };
};
</script>

<template>
    <AppLayout>
        <Head title="Mes Évaluations" />

        <div class="p-6 lg:p-10 max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-slate-800">Mon Centre d'Évaluation</h1>
                <p class="text-slate-500">Consultez vos certifications et quiz disponibles.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="exam in exams" :key="exam.id"
                     class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden">

                    <!-- Header de la carte -->
                    <div class="p-6 flex-1">
                        <div class="flex justify-between items-start mb-4">
                            <Tag :value="exam.type" :severity="exam.type === 'Certification' ? 'primary' : 'secondary'" />
                            <Tag v-bind="getStatus(exam)" :value="getStatus(exam).label" :severity="getStatus(exam).sev" />
                        </div>

                        <h3 class="text-xl font-bold text-slate-800 mb-2">{{ exam.title }}</h3>
                        <p class="text-sm text-slate-500 line-clamp-2 mb-4">{{ exam.course?.title || 'Cours général' }}</p>

                        <div class="grid grid-cols-2 gap-4 py-4 border-y border-slate-50">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Durée</span>
                                <span class="text-sm font-semibold text-slate-700">{{ exam.duration_minutes }} min</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Tentatives</span>
                                <span class="text-sm font-semibold text-slate-700">{{ exam.remaining_attempts }}</span>
                            </div>
                        </div>

                        <!-- Score si existant -->
                        <div v-if="exam.best_score !== null" class="mt-4">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-slate-500">Meilleur Score</span>
                                <span :class="exam.is_passed ? 'text-emerald-600' : 'text-rose-600'">{{ exam.best_score }}%</span>
                            </div>
                            <ProgressBar :value="exam.best_score" :showValue="false" :class="exam.is_passed ? 'bg-emerald-100' : 'bg-rose-100'" style="height: 6px;" />
                        </div>
                    </div>

                    <!-- Footer / Actions -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                        <!-- Cas 1: Reprendre une tentative en cours -->
                        <Link v-if="exam.active_attempt_uuid" :href="route('exams.take', exam.id)" class="w-full">
                            <Button label="Reprendre" icon="pi pi-play" class="w-full p-button-warning font-bold" />
                        </Link>

                        <!-- Cas 2: Commencer une nouvelle tentative -->
                        <Link v-else-if="exam.remaining_attempts !== 0 && !exam.is_passed"
                              :href="route('exams.take', exam.id)" class="w-full">
                            <Button label="Commencer" icon="pi pi-external-link" class="w-full font-bold" />
                        </Link>

                        <!-- Cas 3: Déjà réussi ou plus de tentatives -->
                        <div v-else class="w-full">
                            <Button :label="exam.is_passed ? 'Déjà Obtenu' : 'Limite Atteinte'"
                                    disabled icon="pi pi-lock" class="w-full p-button-secondary opacity-60" />
                        </div>

                        <!-- Bouton voir résultats si une tentative a été faite -->
                        <Link v-if="exam.results.length > 0" :href="route('exams.results', exam.results[0].id)">
                            <Button icon="pi pi-chart-bar" severity="secondary" text v-tooltip.top="'Voir mes résultats'" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-if="exams.length === 0" class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                <i class="pi pi-folder-open text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-700">Aucun examen disponible</h3>
                <p class="text-slate-500">Vous n'êtes inscrit à aucun cours avec évaluation pour le moment.</p>
            </div>
        </div>
    </AppLayout>
</template>
