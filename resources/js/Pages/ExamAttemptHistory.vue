<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Divider from 'primevue/divider';

const props = defineProps({
    exam: { type: Object, required: true },
    attempts: { type: Array, default: () => [] },
});

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const formatDuration = (seconds) => {
    if (!seconds) return '—';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m ${s}s`;
};

const getStatus = (attempt) => {
    if (attempt.is_passed) return { label: 'Réussi', severity: 'success', icon: 'pi-verified' };
    return { label: 'Échoué', severity: 'danger', icon: 'pi-times-circle' };
};

const viewAttempt = (attempt) => {
    router.visit(route('exams.results', attempt.result_id ?? attempt.id));
};
</script>

<template>
    <AppLayout>
        <Head :title="`Historique — ${exam?.title ?? 'Examen'}`" />

        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1200px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-history text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">{{ exam?.title ?? 'Examen' }}</h1>
                                <Tag value="Historique des tentatives" class="!bg-indigo-500/20 !text-indigo-300 !border !border-indigo-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ exam?.course?.title ?? 'Cours' }} · Seuil : {{ exam?.pass_percentage ?? 70 }}%</p>
                        </div>
                    </div>
                    <Link :href="route('exams.learner.index')">
                        <Button label="Tous mes examens" icon="pi pi-arrow-left" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </div>
            </div>

            <div class="max-w-[1200px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <!-- Exam Summary -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tentatives totales</p>
                            <p class="text-4xl font-black text-slate-800 font-mono">{{ attempts.length }}</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Meilleur score</p>
                            <p class="text-4xl font-black text-slate-800 font-mono">{{ attempts.length ? Math.max(...attempts.map(a => a.score_percentage)) : 0 }}%</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tentatives restantes</p>
                            <p class="text-4xl font-black text-slate-800 font-mono">{{ exam.attempts_allowed === 0 ? '∞' : Math.max(0, exam.attempts_allowed - attempts.length) }}</p>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Seuil de réussite</p>
                            <p class="text-4xl font-black text-slate-800 font-mono">{{ exam?.pass_percentage ?? 70 }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Attempts History -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-list text-lg"></i></div>
                            <h2 class="text-lg font-black text-slate-800">Tentatives ({{ attempts.length }})</h2>
                        </div>
                    </div>

                    <div v-if="attempts.length === 0" class="p-12 text-center text-slate-400">
                        <i class="pi pi-history text-4xl block mb-3"></i>
                        <p class="text-lg font-medium">Aucune tentative pour cet examen</p>
                        <p class="text-sm mt-1">Lancez l'examen pour commencer votre première tentative.</p>
                        <Link :href="route('exams.take', exam.id)" class="mt-6 inline-block">
                            <Button label="Lancer l'examen" icon="pi pi-play-circle" class="!bg-indigo-600 !border-none !rounded-xl !px-8 !py-3 !text-xs !font-black !uppercase !tracking-widest shadow-xl" />
                        </Link>
                    </div>

                    <DataTable v-else :value="attempts" stripedRows responsiveLayout="scroll" class="text-sm">
                        <Column field="created_at" header="Date" style="min-width: 180px">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-800">{{ formatDate(data.created_at) }}</span>
                                    <span class="text-[10px] text-slate-400">Terminé : {{ formatDate(data.completed_at) }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="score_percentage" header="Score" style="min-width: 120px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <div class="w-20 h-20 rounded-full flex items-center justify-center text-sm font-black"
                                         :class="data.is_passed ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
                                        {{ data.score_percentage }}%
                                    </div>
                                    <div class="flex-1">
                                        <ProgressBar :value="data.score_percentage" :showValue="false" class="h-1.5"
                                            :style="`--p-progressbar-value-background: ${data.is_passed ? '#10B981' : '#E11D48'}`" />
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="is_passed" header="Statut" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag :value="getStatus(data).label" :severity="getStatus(data).severity" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="time_spent_seconds" header="Temps passé" style="min-width: 140px">
                            <template #body="{ data }">
                                <span class="text-sm font-mono text-slate-700">{{ formatDuration(data.time_spent_seconds) }}</span>
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 140px" :exportable="false">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <Button icon="pi pi-eye" text @click="viewAttempt(data)" class="!text-indigo-600 hover:!bg-indigo-50 !rounded-xl" v-tooltip="'Voir le détail'" />
                                    <Button v-if="data.result_id" icon="pi pi-chart-bar" text :href="route('exams.results', data.result_id)" class="!text-emerald-600 hover:!bg-emerald-50 !rounded-xl" v-tooltip="'Voir le résultat'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Start New Attempt -->
                <div class="mt-6 text-center">
                    <Link v-if="exam.attempts_allowed === 0 || attempts.length < exam.attempts_allowed" :href="route('exams.take', exam.id)">
                        <Button label="Nouvelle tentative" icon="pi pi-plus-circle" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !px-8 !py-3 !text-xs !font-black !uppercase !tracking-widest shadow-xl" />
                    </Link>
                    <div v-else class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-slate-600">
                        <i class="pi pi-lock text-2xl mb-2 block"></i>
                        <p class="font-black">Limite de tentatives atteinte</p>
                        <p class="text-sm mt-1">Vous avez utilisé toutes vos {{ exam.attempts_allowed }} tentatives autorisées.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar) { background: #F1F5F9; border-radius: 99px; }
:deep(.p-progressbar-value) { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
</style>