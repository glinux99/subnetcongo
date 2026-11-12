<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    questions: Object,
    stats: Object,
    courses: Array,
    filters: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');

const filterOptions = computed(() => [
    { label: t('pages.student_questions.optAll'), value: 'all' },
    { label: t('pages.student_questions.optOpen'), value: 'open' },
    { label: t('pages.student_questions.optAnswered'), value: 'answered' },
    { label: t('pages.student_questions.optResolved'), value: 'resolved' },
    { label: t('pages.student_questions.optClosed'), value: 'closed' },
]);

const prioritySeverity = (p) => ({
    urgent: 'danger', high: 'warn', normal: 'info', low: 'secondary'
}[p] ?? 'info');

const priorityLabel = (p) => ({
    urgent: t('pages.student_questions.priorityUrgent'), high: t('pages.student_questions.priorityHigh'), normal: t('pages.student_questions.priorityNormal'), low: t('pages.student_questions.priorityLow')
}[p] ?? p);

const statusLabel = (s) => ({
    open: t('pages.student_questions.statusOpen'), answered: t('pages.student_questions.statusAnswered'), resolved: t('pages.student_questions.statusResolved'), closed: t('pages.student_questions.statusClosed')
}[s] ?? s);

const statusSeverity = (s) => ({
    open: 'warn', answered: 'info', resolved: 'success', closed: 'secondary'
}[s] ?? 'info');

const formatDate = (d) => d ? new Date(d).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const applyFilter = () => {
    router.get(route('student.questions.index'), { status: statusFilter.value }, { preserveState: true });
};

// Q&A Dialog
const showAskDialog = ref(false);
const askForm = ref({ course_id: null, lesson_id: null, subject: '', body: '', priority: 'normal' });
const askFiles = ref([]);
const askSubmitting = ref(false);
const askErrors = ref({});

const openAsk = () => {
    askForm.value = { course_id: props.courses?.[0]?.id ?? null, lesson_id: null, subject: '', body: '', priority: 'normal' };
    askFiles.value = [];
    askErrors.value = {};
    showAskDialog.value = true;
};

const onFileChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    askFiles.value = [...askFiles.value, ...files].slice(0, 5);
    e.target.value = '';
};
const removeFile = (i) => askFiles.value.splice(i, 1);
const formatSize = (b) => b < 1024 ? t('pages.student_questions.sizeBytes', { size: b }) : b < 1048576 ? t('pages.student_questions.sizeKb', { size: (b / 1024).toFixed(1) }) : t('pages.student_questions.sizeMb', { size: (b / 1048576).toFixed(1) });

const priorityOptions = computed(() => [
    { label: t('pages.student_questions.priorityLow'), value: 'low' },
    { label: t('pages.student_questions.priorityNormal'), value: 'normal' },
    { label: t('pages.student_questions.priorityHigh'), value: 'high' },
    { label: t('pages.student_questions.priorityUrgent'), value: 'urgent' },
]);

const submitAsk = () => {
    if (!askForm.value.subject.trim() || !askForm.value.body.trim()) {
        askErrors.value = { form: t('pages.student_questions.errRequired') };
        return;
    }
    askSubmitting.value = true;

    router.post(route('student.questions.store'), {
        course_id: askForm.value.course_id,
        lesson_id: askForm.value.lesson_id,
        subject: askForm.value.subject,
        body: askForm.value.body,
        priority: askForm.value.priority,
        attachments: askFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            showAskDialog.value = false;
            askSubmitting.value = false;
            askFiles.value = [];
            const flash = page.props?.flash?.success;
            toast.add({ severity: 'success', summary: t('pages.student_questions.questionSent'), detail: flash || t('pages.student_questions.questionSentDetail'), life: 3500 });
        },
        onError: (e) => {
            askSubmitting.value = false;
            askErrors.value = e;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.student_questions.headTitle')" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ ULTRA PRO V50 -->
            <PageHeader
                :title="t('pages.student_questions.pageTitle')"
                :subtitle="t('pages.student_questions.pageSubtitle')"
                icon="pi-question-circle"
                :badge="t('pages.student_questions.pageBadge')"
                accent="blue"
                :actions="[
                    { label: t('pages.student_questions.actionAsk'), icon: 'pi-plus', onClick: openAsk, variant: 'primary' }
                ]"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <!-- KPI STRIP -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <PageKpi
                        :label="t('pages.student_questions.kpiTotal')"
                        :value="stats?.total ?? 0"
                        icon="pi-list"
                        color="blue"
                        :subtext="t('pages.student_questions.kpiTotalSub')"
                    />
                    <PageKpi
                        :label="t('pages.student_questions.kpiOpen')"
                        :value="stats?.open ?? 0"
                        icon="pi-clock"
                        color="amber"
                        :subtext="t('pages.student_questions.kpiOpenSub')"
                    />
                    <PageKpi
                        :label="t('pages.student_questions.kpiAnswered')"
                        :value="stats?.answered ?? 0"
                        icon="pi-reply"
                        color="blue"
                        :subtext="t('pages.student_questions.kpiAnsweredSub')"
                    />
                    <PageKpi
                        :label="t('pages.student_questions.kpiResolved')"
                        :value="stats?.resolved ?? 0"
                        icon="pi-check-circle"
                        color="emerald"
                        :subtext="t('pages.student_questions.kpiResolvedSub')"
                    />
                </div>

                <!-- TABLE DES QUESTIONS -->
                <div class="page-card-flush p-6">
                    <div class="flex items-center gap-3 mb-6 flex-wrap">
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button :label="t('pages.student_questions.filterBtn')" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl !text-xs font-bold" />
                        <span class="ml-auto text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">{{ t('pages.student_questions.totalCount', { count: questions?.total ?? 0 }) }}</span>
                    </div>

                    <DataTable :value="questions?.data ?? []" stripedRows paginator :rows="10" :totalRecords="questions?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column field="subject" :header="t('pages.student_questions.colSubject')" sortable>
                            <template #body="{ data }">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-800 truncate">{{ data.subject }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate">{{ data.course?.title ?? '—' }}<span v-if="data.lesson"> · {{ data.lesson.title }}</span></p>
                                </div>
                            </template>
                        </Column>
                        <Column :header="t('pages.student_questions.colAttachments')" :exportable="false">
                            <template #body="{ data }">
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 uppercase tracking-widest">
                                    <i class="pi pi-paperclip"></i> {{ data.attachments.length }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </template>
                        </Column>
                        <Column field="priority" :header="t('pages.student_questions.colPriority')" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="priorityLabel(data.priority)" :severity="prioritySeverity(data.priority)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="status" :header="t('pages.student_questions.colStatus')" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="instructor.name" :header="t('pages.student_questions.colInstructor')">
                            <template #body="{ data }">
                                <span v-if="data.instructor" class="font-medium text-slate-700">{{ data.instructor.name }}</span>
                                <span v-else class="text-slate-400">—</span>
                            </template>
                        </Column>
                        <Column field="created_at" :header="t('pages.student_questions.colDate')" sortable>
                            <template #body="{ data }"><span class="font-mono text-xs text-slate-500">{{ formatDate(data.created_at) }}</span></template>
                        </Column>
                        <Column :header="t('pages.student_questions.colAction')" :exportable="false">
                            <template #body="{ data }">
                                <Link :href="route('student.questions.show', data.uuid)">
                                    <Button :label="t('pages.student_questions.viewBtn')" icon="pi pi-arrow-right" iconPos="right" size="small" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest text-white shadow-sm" />
                                </Link>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12 text-slate-400">
                                <i class="pi pi-question-circle text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-sm font-bold text-slate-600">{{ t('pages.student_questions.emptyTitle') }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ t('pages.student_questions.emptyDesc') }}</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- DIALOG QUESTION -->
        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '580px' }" :header="t('pages.student_questions.dialogTitle')" class="!rounded-2xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.student_questions.fieldCourse') }}</label>
                    <Select v-model="askForm.course_id" :options="courses" optionLabel="title" optionValue="id" :placeholder="t('pages.student_questions.coursePlaceholder')" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.student_questions.fieldSubject') }}</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" :placeholder="t('pages.student_questions.subjectPlaceholder')" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.student_questions.fieldBody') }}</label>
                    <Textarea v-model="askForm.body" rows="4" class="w-full !rounded-xl" :placeholder="t('pages.student_questions.bodyPlaceholder')" autoResize />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.student_questions.fieldPriority') }}</label>
                    <Select v-model="askForm.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">{{ t('pages.student_questions.fieldAttachments') }}</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">{{ t('pages.student_questions.addFile') }}</span>
                        <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="askFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in askFiles" :key="i" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 text-xs">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-blue-500' : 'pi pi-file text-blue-500'"></i>
                            <span class="flex-1 truncate font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ formatSize(f.size) }}</span>
                            <button type="button" @click="removeFile(i)" class="w-5 h-5 rounded-full bg-red-100 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="askErrors.form || askErrors.subject || askErrors.body" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-3 font-medium">
                    {{ askErrors.form || askErrors.subject || askErrors.body }}
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-3 pt-2">
                    <Button :label="t('pages.student_questions.cancel')" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl !text-xs font-bold" />
                    <Button :label="askSubmitting ? t('pages.student_questions.sending') : t('pages.student_questions.sendQuestion')" icon="pi pi-send" :loading="askSubmitting" @click="submitAsk" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
