<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Knob from 'primevue/knob';
import Divider from 'primevue/divider';
import Timeline from 'primevue/timeline';

const props = defineProps({
    result: { type: Object, required: true },
});

const score = computed(() => Number(props.result?.score_percentage ?? 0));
const passed = computed(() => Boolean(props.result?.is_passed));
const grade = computed(() => {
    const s = score.value;
    if (s >= 95) return 'A+';
    if (s >= 90) return 'A';
    if (s >= 85) return 'B+';
    if (s >= 80) return 'B';
    if (s >= 75) return 'C+';
    if (s >= 70) return 'C';
    if (s >= 65) return 'D+';
    if (s >= 60) return 'D';
    return 'F';
});

const events = [
    { label: 'Tentative démarrée', time: props.result?.attempt?.started_at, icon: 'pi pi-play' },
    { label: 'Tentative complétée', time: props.result?.attempt?.completed_at, icon: 'pi pi-check' },
    { label: 'Résultat publié', time: props.result?.passed_at, icon: 'pi pi-verified' },
];
</script>

<template>
    <AppLayout>
        <Head :title="`Résultat — ${result?.exam?.title ?? 'Examen'}`" />

        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full blur-[100px] pointer-events-none"
                     :class="passed ? 'bg-emerald-600/15' : 'bg-rose-600/15'"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[900px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg border border-white/10"
                         :class="passed ? 'bg-gradient-to-br from-emerald-500 to-amber-500' : 'bg-gradient-to-br from-rose-500 to-orange-500'">
                        <i :class="passed ? 'pi pi-check-circle text-white text-3xl' : 'pi pi-times-circle text-white text-3xl'"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">{{ passed ? 'Félicitations !' : 'Vous n\'avez pas réussi' }}</h1>
                            <Tag :value="passed ? 'Réussi' : 'Échoué'" :severity="passed ? 'success' : 'danger'"
                                 class="!text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">{{ result?.exam?.title ?? 'Résultat' }}</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 text-center">
                            <Knob v-model="score" :size="140" :strokeWidth="10" :readonly="true"
                                  :valueColor="passed ? '#10B981' : '#F43F5E'" rangeColor="#F1F5F9" />
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-3">Score final</p>
                        </div>
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Note</p>
                            <p class="text-5xl font-black text-slate-800 font-mono">{{ grade }}</p>
                            <p class="text-sm text-slate-500 mt-2">{{ result?.feedback }}</p>
                        </div>
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Points</p>
                            <p class="text-5xl font-black text-slate-800 font-mono">{{ result?.total_points ?? 0 }}</p>
                            <p class="text-sm text-slate-500 mt-2">Temps passé : {{ result?.attempt?.formatted_time_spent ?? '—' }}</p>
                        </div>
                    </div>

                    <Divider />

                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-widest mb-4">Historique</h2>
                    <Timeline :value="events" align="left">
                        <template #marker="slotProps">
                            <span class="flex w-10 h-10 items-center justify-center text-white bg-indigo-500 rounded-2xl shadow-md shadow-indigo-500/30">
                                <i :class="slotProps.item.icon" class="text-sm"></i>
                            </span>
                        </template>
                        <template #content="slotProps">
                            <p class="font-black text-slate-800 text-sm">{{ slotProps.item.label }}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ slotProps.item.time || '—' }}</p>
                        </template>
                    </Timeline>

                    <Divider />

                    <div class="flex gap-3">
                        <Link :href="route('student.my-courses')">
                            <Button label="Mes formations" icon="pi pi-book" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3" />
                        </Link>
                        <Link :href="route('exams.learner.index')">
                            <Button label="Tous mes examens" icon="pi pi-list" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>