<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Tooltip from 'primevue/tooltip';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const vTooltip = Tooltip;

const props = defineProps({
    questions: Object,
    stats: Object,
    filters: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');

const filterOptions = computed(() => [
    { label: t('pages.instructor_questions.filter_all'), value: 'all' },
    { label: t('pages.instructor_questions.filter_open'), value: 'open' },
    { label: t('pages.instructor_questions.filter_answered'), value: 'answered' },
    { label: t('pages.instructor_questions.filter_resolved'), value: 'resolved' },
]);

const priorityLabel = (p) => ({
    urgent: t('pages.instructor_questions.priority_urgent'), high: t('pages.instructor_questions.priority_high'), normal: t('pages.instructor_questions.priority_normal'), low: t('pages.instructor_questions.priority_low')
}[p] ?? p);

const statusLabel = (s) => ({
    open: t('pages.instructor_questions.status_open'), answered: t('pages.instructor_questions.status_answered'), resolved: t('pages.instructor_questions.status_resolved'), closed: t('pages.instructor_questions.status_closed')
}[s] ?? s);

const prioritySeverity = (p) => ({
    urgent: 'danger', high: 'warn', normal: 'info', low: 'secondary'
}[p] ?? 'info');

const statusSeverity = (s) => ({
    open: 'warn', answered: 'info', resolved: 'success', closed: 'secondary'
}[s] ?? 'info');

const formatDate = (d) => d ? new Date(d).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const applyFilter = () => {
    router.get(route('instructor.questions.index'), { status: statusFilter.value }, { preserveState: true });
};

// Réponse Dialog
const showAnswerDialog = ref(false);
const activeQuestion = ref(null);
const answerText = ref('');
const answerFiles = ref([]);
const submitting = ref(false);

const onFileChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    answerFiles.value = [...answerFiles.value, ...files].slice(0, 5);
    e.target.value = '';
};
const removeFile = (i) => answerFiles.value.splice(i, 1);
const formatSize = (b) => b < 1024 ? `${b} o` : b < 1048576 ? `${(b / 1024).toFixed(1)} Ko` : `${(b / 1048576).toFixed(1)} Mo`;

const openAnswer = (q) => { activeQuestion.value = q; answerText.value = q.answer ?? ''; answerFiles.value = []; showAnswerDialog.value = true; };
const submitAnswer = () => {
    if (!answerText.value.trim()) return;
    submitting.value = true;
    router.post(route('instructor.questions.answer', activeQuestion.value.id), {
        answer: answerText.value,
        attachments: answerFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            showAnswerDialog.value = false;
            submitting.value = false;
            answerFiles.value = [];
            toast.add({ severity: 'success', summary: t('pages.instructor_questions.toast_answered'), detail: page.props?.flash?.success ?? '', life: 2500 });
        },
        onError: () => { submitting.value = false; },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.instructor_questions.head_title')" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ -->
            <PageHeader
                :title="t('pages.instructor_questions.title')"
                :subtitle="t('pages.instructor_questions.subtitle')"
                icon="pi-inbox"
                :badge="t('pages.instructor_questions.badge')"
                accent="red"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <!-- KPI STRIP -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <PageKpi
                        :label="t('pages.instructor_questions.kpi_total')"
                        :value="stats?.total ?? 0"
                        icon="pi-list"
                        color="blue"
                        :subtext="t('pages.instructor_questions.kpi_total_sub')"
                    />
                    <PageKpi
                        :label="t('pages.instructor_questions.kpi_open')"
                        :value="stats?.open ?? 0"
                        icon="pi-clock"
                        color="amber"
                        :subtext="t('pages.instructor_questions.kpi_open_sub')"
                    />
                    <PageKpi
                        :label="t('pages.instructor_questions.kpi_answered')"
                        :value="stats?.answered ?? 0"
                        icon="pi-check-circle"
                        color="emerald"
                        :subtext="t('pages.instructor_questions.kpi_answered_sub')"
                    />
                </div>

                <!-- TABLE DES QUESTIONS -->
                <div class="page-card-flush p-6">
                    <div class="flex items-center gap-3 mb-6 flex-wrap">
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button :label="t('pages.instructor_questions.btn_filter')" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl !text-xs font-bold" />
                        <span class="ml-auto text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">{{ t('pages.instructor_questions.questions_count', { count: questions?.total ?? 0 }) }}</span>
                    </div>

                    <DataTable :value="questions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="questions?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column :header="t('pages.instructor_questions.col_student')">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3 min-w-0">
                                    <Avatar :label="data.user?.name?.charAt(0)" shape="circle" size="normal" class="!bg-blue-100 !text-blue-700 !font-black shrink-0" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-slate-800 truncate">{{ data.user?.name }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ data.user?.email }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="subject" :header="t('pages.instructor_questions.col_question')">
                            <template #body="{ data }">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-800 truncate">{{ data.subject }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate">{{ data.course?.title ?? '—' }}<span v-if="data.lesson"> · {{ data.lesson.title }}</span></p>
                                </div>
                            </template>
                        </Column>
                        <Column :header="t('pages.instructor_questions.col_attachments')" :exportable="false">
                            <template #body="{ data }">
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 uppercase tracking-widest" v-tooltip.bottom="t('pages.instructor_questions.attachments_count', { count: data.attachments.length })">
                                    <i class="pi pi-paperclip"></i> {{ data.attachments.length }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </template>
                        </Column>
                        <Column field="priority" :header="t('pages.instructor_questions.col_priority')" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="priorityLabel(data.priority)" :severity="prioritySeverity(data.priority)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="status" :header="t('pages.instructor_questions.col_status')" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="created_at" :header="t('pages.instructor_questions.col_received')" sortable>
                            <template #body="{ data }"><span class="font-mono text-xs text-slate-500">{{ formatDate(data.created_at) }}</span></template>
                        </Column>
                        <Column :header="t('pages.instructor_questions.col_action')" :exportable="false">
                            <template #body="{ data }">
                                <Button :label="data.answer ? t('pages.instructor_questions.btn_view_edit') : t('pages.instructor_questions.btn_answer')" icon="pi pi-reply" size="small" :severity="data.answer ? 'secondary' : 'primary'" @click="openAnswer(data)" :outlined="!!data.answer" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12 text-slate-400">
                                <i class="pi pi-inbox text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-sm font-bold text-slate-600">{{ t('pages.instructor_questions.empty_title') }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ t('pages.instructor_questions.empty_hint') }}</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- DIALOG RÉPONSE FORMATEUR -->
        <Dialog v-model:visible="showAnswerDialog" modal :style="{ width: '700px' }" :header="activeQuestion?.subject || t('pages.instructor_questions.dialog_answer')" class="!rounded-2xl" :closable="!submitting">
            <div v-if="activeQuestion" class="space-y-4 p-2">
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <div class="flex items-center gap-3 mb-2">
                        <Avatar :label="activeQuestion.user?.name?.charAt(0)" shape="circle" class="!w-9 !h-9 !bg-blue-100 !text-blue-700 !font-black !text-xs shrink-0" />
                        <div>
                            <p class="text-xs font-black text-slate-800">{{ activeQuestion.user?.name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ formatDate(activeQuestion.created_at) }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ activeQuestion.body }}</p>

                    <!-- Pièces jointes de la question (lecture seule) -->
                    <div v-if="activeQuestion.attachments?.length" class="mt-3 pt-3 border-t border-slate-200">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> {{ t('pages.instructor_questions.student_attachments') }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <a v-for="(att, i) in activeQuestion.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg px-2.5 py-1.5 text-xs transition-colors">
                                <i :class="att.is_image ? 'pi pi-image text-blue-500' : 'pi pi-file text-slate-500'"></i>
                                <span class="truncate font-bold text-slate-700 flex-1">{{ att.original_name }}</span>
                                <span class="text-[9px] text-slate-400 font-mono">{{ formatSize(att.size) }}</span>
                                <i class="pi pi-download text-slate-400 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.instructor_questions.answer_label') }}</label>
                    <Textarea v-model="answerText" rows="6" class="w-full !rounded-xl" :placeholder="t('pages.instructor_questions.answer_placeholder')" autoResize />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.instructor_questions.extra_attachments') }}</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-600 transition-colors w-fit">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">{{ t('pages.instructor_questions.add_file') }}</span>
                        <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="answerFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in answerFiles" :key="i" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-1.5 text-xs w-fit">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-blue-500' : 'pi pi-file text-blue-500'"></i>
                            <span class="font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">{{ formatSize(f.size) }}</span>
                            <button type="button" @click="removeFile(i)" class="w-5 h-5 rounded-full bg-red-100 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-3 pt-2">
                    <Button :label="t('pages.instructor_questions.btn_cancel')" outlined :disabled="submitting" @click="showAnswerDialog = false" class="!rounded-xl !text-xs font-bold" />
                    <Button :label="submitting ? t('pages.instructor_questions.btn_sending') : t('pages.instructor_questions.btn_send_answer')" icon="pi pi-send" :loading="submitting" @click="submitAnswer" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
