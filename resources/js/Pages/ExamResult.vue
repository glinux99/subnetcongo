<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Knob from 'primevue/knob';
import Divider from 'primevue/divider';
import Timeline from 'primevue/timeline';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Card from 'primevue/card';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    result: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
    attempt: { type: Object, default: null },
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

const events = computed(() => [
    { label: t('pages.exam_result.event_started'), time: props.attempt?.started_at, icon: 'pi pi-play' },
    { label: t('pages.exam_result.event_completed'), time: props.attempt?.completed_at, icon: 'pi pi-check' },
    { label: t('pages.exam_result.event_published'), time: props.result?.passed_at, icon: 'pi pi-verified' },
]);

const activeIndex = ref(0);

const getOptionText = (options, answer) => {
    if (!options || !answer) return '—';
    if (Array.isArray(answer)) {
        return options.filter(o => answer.includes(o.id)).map(o => o.option_text).join(', ');
    }
    const opt = options.find(o => String(o.id) === String(answer));
    return opt ? opt.option_text : String(answer);
};

const getCorrectOptionTexts = (options) => {
    if (!options) return '—';
    return options.filter(o => o.is_correct).map(o => o.option_text).join(', ');
};
</script>

<template>
    <AppLayout>
        <Head :title="`${t('pages.exam_result.head_prefix')} — ${result?.exam?.title ?? t('pages.exam_result.exam_fallback')}`" />

        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full blur-[100px] pointer-events-none"
                     :class="passed ? 'bg-emerald-600/15' : 'bg-red-600/15'"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[900px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg border border-white/10"
                         :class="passed ? 'bg-gradient-to-br from-emerald-600 to-blue-600' : 'bg-gradient-to-br from-red-600 to-red-800'">
                        <i :class="passed ? 'pi pi-check-circle text-white text-3xl' : 'pi pi-times-circle text-white text-3xl'"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">{{ passed ? t('pages.exam_result.congrats') : t('pages.exam_result.failed_title') }}</h1>
                            <Tag :value="passed ? t('pages.exam_result.status_passed') : t('pages.exam_result.status_failed')" :severity="passed ? 'success' : 'danger'"
                                 class="!text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">{{ result?.exam?.title ?? t('pages.exam_result.result_fallback') }}</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 text-center">
                            <Knob v-model="score" :size="140" :strokeWidth="10" :readonly="true"
                                  :valueColor="passed ? '#059669' : '#DC2626'" rangeColor="#F1F5F9" />
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-3">{{ t('pages.exam_result.final_score') }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.exam_result.grade') }}</p>
                            <p class="text-5xl font-black text-slate-800 font-mono">{{ grade }}</p>
                            <p class="text-sm text-slate-500 mt-2">{{ result?.feedback }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ t('pages.exam_result.points') }}</p>
                            <p class="text-5xl font-black text-slate-800 font-mono">{{ result?.total_points ?? 0 }}</p>
                            <p class="text-sm text-slate-500 mt-2">{{ t('pages.exam_result.time_spent') }} : {{ attempt?.formatted_time_spent ?? '—' }}</p>
                        </div>
                    </div>

                    <Divider />

                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-widest mb-4">{{ t('pages.exam_result.history') }}</h2>
                    <Timeline :value="events" align="left">
                        <template #marker="slotProps">
                            <span class="flex w-10 h-10 items-center justify-center text-white bg-blue-500 rounded-2xl shadow-md shadow-blue-500/30">
                                <i :class="slotProps.item.icon" class="text-sm"></i>
                            </span>
                        </template>
                        <template #content="slotProps">
                            <p class="font-black text-slate-800 text-sm">{{ slotProps.item.label }}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ slotProps.item.time || '—' }}</p>
                        </template>
                    </Timeline>

                    <Divider />

                    <!-- Questions & Answers Review -->
                    <div v-if="questions.length > 0" class="space-y-4">
                        <h2 class="text-lg font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i class="pi pi-list-check text-red-600"></i>
                            {{ t('pages.exam_result.review_title', { count: questions.length }) }}
                        </h2>
                        <Accordion :multiple="true" v-model:activeIndex="activeIndex">
                            <AccordionTab v-for="(q, idx) in questions" :key="q.id">
                                <template #header>
                                    <div class="flex items-center justify-between gap-4 w-full">
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <span class="text-xs font-black px-2 py-1 rounded-lg border"
                                                  :class="q.is_correct ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200'">
                                                {{ q.is_correct ? `✓ ${t('pages.exam_result.correct')}` : `✗ ${t('pages.exam_result.incorrect')}` }}
                                            </span>
                                            <span class="text-sm font-bold text-slate-800 truncate">Q{{ idx + 1 }} : {{ q.text }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ q.points }} pts</span>
                                        </div>
                                    </div>
                                </template>
                                <template #content>
                                    <div class="space-y-4 pt-2">
                                        <!-- User Answer -->
                                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                                            <div class="flex items-center gap-2 mb-2">
                                                <i class="pi pi-user text-slate-500"></i>
                                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ t('pages.exam_result.your_answer') }}</span>
                                                <span v-if="!q.is_correct" class="text-[9px] font-black text-red-600 bg-red-50 px-2 py-0.5 rounded">{{ t('pages.exam_result.incorrect') }}</span>
                                                <span v-else-if="q.user_answer !== null && q.user_answer !== ''" class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">{{ t('pages.exam_result.correct') }}</span>
                                                <span v-else class="text-[9px] font-black text-slate-400 bg-slate-100 px-2 py-0.5 rounded">{{ t('pages.exam_result.unanswered') }}</span>
                                            </div>
                                            <div class="text-sm text-slate-700 font-mono pl-6">
                                                <template v-if="q.type === 'multiple_choice' || q.type === 'true_false'">
                                                    {{ getOptionText(q.options, q.user_answer) }}
                                                </template>
                                                <template v-else-if="q.type === 'multiple_choice_multiple' && Array.isArray(q.user_answer)">
                                                    {{ q.user_answer.length === 0 ? t('pages.exam_result.no_selection') : getOptionText(q.options, q.user_answer) }}
                                                </template>
                                                <template v-else-if="q.type === 'short_answer' || q.type === 'essay'">
                                                    {{ q.user_answer || '—' }}
                                                </template>
                                                <template v-else-if="q.type === 'ordering' && Array.isArray(q.user_answer)">
                                                    {{ q.user_answer.map((id, i) => {
                                                        const opt = q.options.find(o => String(o.id) === String(id));
                                                        return `${i + 1}. ${opt ? opt.option_text : id}`;
                                                    }).join(' → ') }}
                                                </template>
                                                <template v-else-if="q.type === 'matching' && Array.isArray(q.user_answer)">
                                                    {{ q.user_answer.map((id, i) => {
                                                        const left = q.options.find(o => o.is_match_source && String(o.match_id) === String(id));
                                                        const right = q.options.find(o => String(o.id) === String(id));
                                                        return `${left ? left.option_text : '?' } → ${right ? right.option_text : id}`;
                                                    }).join('; ') }}
                                                </template>
                                                <template v-else>
                                                    {{ q.user_answer !== null && q.user_answer !== '' ? q.user_answer : '—' }}
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Correct Answer -->
                                        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
                                            <div class="flex items-center gap-2 mb-2">
                                                <i class="pi pi-check-circle text-emerald-600"></i>
                                                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">{{ t('pages.exam_result.correct_answer') }}</span>
                                            </div>
                                            <div class="text-sm text-slate-700 font-mono pl-6">
                                                <template v-if="q.type === 'multiple_choice' || q.type === 'true_false'">
                                                    {{ getCorrectOptionTexts(q.options) }}
                                                </template>
                                                <template v-else-if="q.type === 'multiple_choice_multiple'">
                                                    {{ getCorrectOptionTexts(q.options) }}
                                                </template>
                                                <template v-else-if="q.type === 'short_answer' || q.type === 'essay'">
                                                    {{ q.correct_answer || t('pages.exam_result.free_answer') }}
                                                </template>
                                                <template v-else-if="q.type === 'ordering'">
                                                    {{ q.options.filter(o => o.order !== undefined).sort((a,b) => a.order - b.order).map((o, i) => `${i + 1}. ${o.option_text}`).join(' → ') }}
                                                </template>
                                                <template v-else-if="q.type === 'matching'">
                                                    {{ q.options.filter(o => o.is_match_source).map(l => {
                                                        const r = q.options.find(o => String(o.id) === String(l.match_id));
                                                        return `${l.option_text} → ${r ? r.option_text : '?'}`;
                                                    }).join('; ') }}
                                                </template>
                                                <template v-else>
                                                    {{ q.correct_answer || '—' }}
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Options Display for MCQ -->
                                        <div v-if="q.type === 'multiple_choice' || q.type === 'multiple_choice_multiple' || q.type === 'true_false'" class="space-y-2 pl-6">
                                            <label v-for="opt in q.options" :key="opt.id" class="flex items-center gap-3 p-2.5 rounded-xl border transition-colors"
                                                   :class="[
                                                       opt.is_correct ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-slate-200',
                                                       q.user_answer !== null && String(q.user_answer).includes(String(opt.id)) ? 'ring-2' : '',
                                                       q.user_answer !== null && String(q.user_answer).includes(String(opt.id)) && opt.is_correct ? 'ring-emerald-500' : 'ring-red-500'
                                                   ]">
                                                <RadioButton v-if="q.type === 'multiple_choice' || q.type === 'true_false'" 
                                                             :value="String(opt.id)" 
                                                             :disabled="true" 
                                                             :modelValue="String(q.user_answer)" 
                                                             class="text-red-600" />
                                                <Checkbox v-else-if="q.type === 'multiple_choice_multiple'" 
                                                          :value="String(opt.id)" 
                                                          :disabled="true" 
                                                          :modelValue="Array.isArray(q.user_answer) ? q.user_answer.map(String) : []" 
                                                          class="text-red-600" />
                                                <span class="text-sm text-slate-700 flex-1">{{ opt.option_text }}</span>
                                                <span v-if="opt.is_correct" class="text-[10px] font-black text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded">{{ t('pages.exam_result.correct') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </AccordionTab>
                        </Accordion>
                    </div>

                    <div v-else class="text-center py-8 text-slate-400">
                        <i class="pi pi-info-circle text-3xl mb-2 block"></i>
                        <p>{{ t('pages.exam_result.no_detail') }}</p>
                    </div>

                    <Divider />

                    <div class="flex gap-3">
                        <Link :href="route('student.my-courses')">
                            <Button :label="t('pages.exam_result.btn_my_courses')" icon="pi pi-book" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3" />
                        </Link>
                        <Link :href="route('exams.learner.index')">
                            <Button :label="t('pages.exam_result.btn_all_exams')" icon="pi pi-list" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.p-progressbar) { background: #F1F5F9; border-radius: 99px; }
:deep(.p-progressbar-value) { transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
:deep(.p-accordion-tab) { border: none !important; }
:deep(.p-accordion-header) { background: transparent !important; padding: 0 !important; }
:deep(.p-accordion-content) { padding: 0 !important; border: none !important; }
</style>