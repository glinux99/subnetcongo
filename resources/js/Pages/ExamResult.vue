<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Knob from 'primevue/knob';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Avatar from 'primevue/avatar';

const props = defineProps({
    result: {
        type: Object,
        required: true,
    }
});

const isPassed = computed(() => props.result.is_passed);

const scoreColor = computed(() => {
    if (isPassed.value) return '#10B981'; // Emerald 500
    if (props.result.score_percentage > 40) return '#F59E0B'; // Amber 500
    return '#EF4444'; // Rose 500
});

const formattedTimeSpent = computed(() => {
    const totalSeconds = props.result.attempt?.time_spent_seconds || 0;
    if (totalSeconds === 0) return 'N/A';
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const formattedCompletionDate = computed(() => {
    if (!props.result.attempt?.completed_at) return 'N/A';
    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'full',
        timeStyle: 'medium'
    }).format(new Date(props.result.attempt.completed_at));
});

// Placeholder - Dans une application réelle, cela viendrait du backend.
const totalQuestions = computed(() => {
    return Object.keys(props.result.attempt?.answers || {}).length;
});

// Placeholder - Ceci est un calcul simplifié. Une application réelle nécessiterait les réponses correctes du backend.
const correctAnswersCount = computed(() => {
    // Ceci est une estimation approximative basée sur le score.
    // Une implémentation correcte nécessite que le backend envoie des résultats de correction détaillés.
    if (totalQuestions.value === 0) return 0;
    return Math.round((props.result.score_percentage / 100) * totalQuestions.value);
});

</script>

<template>
    <AppLayout>
        <Head :title="`Résultat - ${result.exam.title}`" />

        <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">

                <Card class="shadow-2xl rounded-[2.5rem] overflow-hidden border-none animate-fade-in">
                    <template #header>
                        <div class="p-12 text-center relative overflow-hidden" :class="isPassed ? 'bg-emerald-600' : 'bg-rose-600'">
                             <div class="absolute inset-0 opacity-10 pattern-dots scale-150"></div>
                            <div class="relative z-10">
                                <i :class="['text-7xl mb-4 text-white/50', isPassed ? 'pi pi-verified' : 'pi pi-times-circle']"></i>
                                <h1 class="text-5xl font-black text-white tracking-tighter mb-2">
                                    {{ isPassed ? 'Certification Obtenue !' : 'Tentative Terminée' }}
                                </h1>
                                <p class="text-xl font-semibold text-white/80">{{ result.exam.title }}</p>
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <div class="p-6 md:p-12 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                            <!-- Score Section -->
                            <div class="flex flex-col items-center justify-center text-center">
                                <Knob
                                    v-model="result.score_percentage"
                                    :size="220"
                                    readonly
                                    :valueColor="scoreColor"
                                    rangeColor="#e2e8f0"
                                    :strokeWidth="20"
                                >
                                    <template #value>
                                        <div class="flex flex-col items-center">
                                            <span class="text-5xl font-black" :style="{ color: scoreColor }">{{ result.score_percentage }}<span class="text-3xl">%</span></span>
                                            <span class="text-sm font-bold text-slate-500">Votre Score</span>
                                        </div>
                                    </template>
                                </Knob>
                                <div class="mt-6">
                                    <Tag :severity="isPassed ? 'success' : 'danger'" class="px-4 py-2">
                                        <span class="text-sm font-bold">Seuil de réussite : {{ result.exam.pass_percentage }}%</span>
                                    </Tag>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="space-y-8">
                                <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Résumé de la performance</h3>
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-slate-600 flex items-center gap-2"><i class="pi pi-check-circle text-emerald-500"></i> Réponses Correctes</span>
                                            <span class="text-lg font-black text-slate-800">{{ correctAnswersCount }} / {{ totalQuestions }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-slate-600 flex items-center gap-2"><i class="pi pi-stopwatch text-indigo-500"></i> Temps Utilisé</span>
                                            <span class="text-lg font-black text-slate-800 font-mono">{{ formattedTimeSpent }}</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-slate-600 flex items-center gap-2"><i class="pi pi-calendar text-amber-500"></i> Date de soumission</span>
                                            <span class="text-sm font-bold text-slate-800">{{ new Date(result.attempt.completed_at).toLocaleDateString('fr-FR') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 bg-blue-50/50 rounded-3xl border border-blue-100/50">
                                    <h3 class="text-xs font-black text-blue-800 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="pi pi-comment"></i> Feedback de l'examinateur</h3>
                                    <p class="text-sm text-blue-900/80 leading-relaxed font-medium">
                                        {{ result.feedback || 'Aucun feedback spécifique pour cette tentative.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #footer>
                        <div class="px-6 md:px-12 py-8 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                             <Link :href="route('exams.learner.index')">
                                <Button label="Retour à mes évaluations" icon="pi pi-arrow-left" text class="font-bold text-slate-600" />
                            </Link>
                            <div class="flex items-center gap-3">
                                <Button label="Réviser mes réponses" icon="pi pi-search" severity="secondary" outlined class="font-bold" disabled v-tooltip.top="'Fonctionnalité bientôt disponible'" />
                                <Button v-if="isPassed" label="Télécharger le certificat" icon="pi pi-download" severity="success" class="font-black shadow-lg shadow-emerald-100" />
                            </div>
                        </div>
                    </template>
                </Card>

                <div class="text-center mt-8">
                     <p class="text-xs text-slate-400 font-bold">ID de Résultat: <span class="font-mono">{{ result.id }}</span> | ID de Tentative: <span class="font-mono">{{ result.attempt.uuid }}</span></p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.pattern-dots {
    background-image: radial-gradient(currentColor 1px, transparent 1px);
    background-size: 20px 20px;
}
</style>
