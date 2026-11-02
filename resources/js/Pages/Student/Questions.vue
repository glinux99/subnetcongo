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

const props = defineProps({
    questions: Object,
    stats: Object,
    courses: Array,
    filters: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');

const filterOptions = [
    { label: 'Toutes les questions', value: 'all' },
    { label: 'En attente', value: 'open' },
    { label: 'Répondues', value: 'answered' },
    { label: 'Résolues', value: 'resolved' },
    { label: 'Fermées', value: 'closed' },
];

const prioritySeverity = (p) => ({
    urgent: 'danger', high: 'warn', normal: 'info', low: 'secondary'
}[p] ?? 'info');

const priorityLabel = (p) => ({
    urgent: 'Bloquante', high: 'Importante', normal: 'Normale', low: 'Faible'
}[p] ?? p);

const statusLabel = (s) => ({
    open: 'En attente', answered: 'Répondue', resolved: 'Résolue', closed: 'Fermée'
}[s] ?? s);

const statusSeverity = (s) => ({
    open: 'warn', answered: 'info', resolved: 'success', closed: 'secondary'
}[s] ?? 'info');

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

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
const formatSize = (b) => b < 1024 ? `${b} o` : b < 1048576 ? `${(b / 1024).toFixed(1)} Ko` : `${(b / 1048576).toFixed(1)} Mo`;

const submitAsk = () => {
    if (!askForm.value.subject.trim() || !askForm.value.body.trim()) {
        askErrors.value = { form: 'Le sujet et le message sont obligatoires.' };
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
            toast.add({ severity: 'success', summary: 'Question envoyée', detail: flash || 'L\'instructeur répondra sous 24-48h.', life: 3500 });
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
        <Head title="Mes questions aux instructeurs" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ ULTRA PRO V50 -->
            <PageHeader
                title="Mes Questions"
                subtitle="Posez vos questions aux instructeurs et suivez leurs réponses en temps réel."
                icon="pi-question-circle"
                badge="Assistance Pédagogique"
                accent="blue"
                :actions="[
                    { label: 'Poser une question', icon: 'pi-plus', onClick: openAsk, variant: 'primary' }
                ]"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <!-- KPI STRIP -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <PageKpi
                        label="Total questions"
                        :value="stats?.total ?? 0"
                        icon="pi-list"
                        color="blue"
                        subtext="Historique complet"
                    />
                    <PageKpi
                        label="En attente"
                        :value="stats?.open ?? 0"
                        icon="pi-clock"
                        color="amber"
                        subtext="À traiter par l'instructeur"
                    />
                    <PageKpi
                        label="Répondues"
                        :value="stats?.answered ?? 0"
                        icon="pi-reply"
                        color="blue"
                        subtext="Nouvelles réponses"
                    />
                    <PageKpi
                        label="Résolues"
                        :value="stats?.resolved ?? 0"
                        icon="pi-check-circle"
                        color="emerald"
                        subtext="Problèmes résolus"
                    />
                </div>

                <!-- TABLE DES QUESTIONS -->
                <div class="page-card-flush p-6">
                    <div class="flex items-center gap-3 mb-6 flex-wrap">
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl !text-xs font-bold" />
                        <span class="ml-auto text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">{{ questions?.total ?? 0 }} question(s)</span>
                    </div>

                    <DataTable :value="questions?.data ?? []" stripedRows paginator :rows="10" :totalRecords="questions?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column field="subject" header="Sujet" sortable>
                            <template #body="{ data }">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-800 truncate">{{ data.subject }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate">{{ data.course?.title ?? '—' }}<span v-if="data.lesson"> · {{ data.lesson.title }}</span></p>
                                </div>
                            </template>
                        </Column>
                        <Column header="Pièces jointes" :exportable="false">
                            <template #body="{ data }">
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 uppercase tracking-widest">
                                    <i class="pi pi-paperclip"></i> {{ data.attachments.length }}
                                </span>
                                <span v-else class="text-slate-300">—</span>
                            </template>
                        </Column>
                        <Column field="priority" header="Priorité" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="priorityLabel(data.priority)" :severity="prioritySeverity(data.priority)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="status" header="Statut" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column field="instructor.name" header="Instructeur">
                            <template #body="{ data }">
                                <span v-if="data.instructor" class="font-medium text-slate-700">{{ data.instructor.name }}</span>
                                <span v-else class="text-slate-400">—</span>
                            </template>
                        </Column>
                        <Column field="created_at" header="Date" sortable>
                            <template #body="{ data }"><span class="font-mono text-xs text-slate-500">{{ formatDate(data.created_at) }}</span></template>
                        </Column>
                        <Column header="Action" :exportable="false">
                            <template #body="{ data }">
                                <Link :href="route('student.questions.show', data.id)">
                                    <Button label="Consulter" icon="pi pi-arrow-right" iconPos="right" size="small" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest text-white shadow-sm" />
                                </Link>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12 text-slate-400">
                                <i class="pi pi-question-circle text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-sm font-bold text-slate-600">Aucune question pour l'instant.</p>
                                <p class="text-xs text-slate-400 mt-1">Vous pouvez poser une question à tout moment lors de votre formation.</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- DIALOG QUESTION -->
        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '580px' }" header="Poser une question" class="!rounded-2xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Formation concernée</label>
                    <Select v-model="askForm.course_id" :options="courses" optionLabel="title" optionValue="id" placeholder="Choisir une formation" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Sujet</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" placeholder="Ex: Question sur la configuration d'un routeur" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Message explicatif</label>
                    <Textarea v-model="askForm.body" rows="4" class="w-full !rounded-xl" placeholder="Décrivez votre question avec précision..." autoResize />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Niveau d'urgence</label>
                    <Select v-model="askForm.priority" :options="[{label:'Faible', value:'low'},{label:'Normale', value:'normal'},{label:'Importante', value:'high'},{label:'Bloquante', value:'urgent'}]" optionLabel="label" optionValue="value" class="w-full !rounded-xl" />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Pièces jointes (capture, doc… max 5 · 10Mo)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">Ajouter un fichier</span>
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
                    <Button label="Annuler" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl !text-xs font-bold" />
                    <Button :label="askSubmitting ? 'Envoi...' : 'Envoyer la question'" icon="pi pi-send" :loading="askSubmitting" @click="submitAsk" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
