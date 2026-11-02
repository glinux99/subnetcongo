<script setup>
import { ref } from 'vue';
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

const vTooltip = Tooltip;

const props = defineProps({
    questions: Object,
    stats: Object,
    filters: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');

const filterOptions = [
    { label: 'Toutes les questions', value: 'all' },
    { label: 'En attente de réponse', value: 'open' },
    { label: 'Déjà répondues', value: 'answered' },
    { label: 'Résolues', value: 'resolved' },
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
            toast.add({ severity: 'success', summary: 'Réponse envoyée', detail: page.props?.flash?.success ?? '', life: 2500 });
        },
        onError: () => { submitting.value = false; },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Questions des apprenants | Espace Formateur" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ -->
            <PageHeader
                title="Questions des Apprenants"
                subtitle="Répondez aux interrogations des étudiants et assurez un suivi pédagogique continu."
                icon="pi-inbox"
                badge="Espace Formateur"
                accent="blue"
            />

            <!-- CONTENEUR HARMONISÉ -->
            <div class="page-container space-y-6">
                <!-- KPI STRIP -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <PageKpi
                        label="Total reçues"
                        :value="stats?.total ?? 0"
                        icon="pi-list"
                        color="blue"
                        subtext="Toutes les questions"
                    />
                    <PageKpi
                        label="En attente de réponse"
                        :value="stats?.open ?? 0"
                        icon="pi-clock"
                        color="amber"
                        subtext="Requiert votre attention"
                    />
                    <PageKpi
                        label="Questions traitées"
                        :value="stats?.answered ?? 0"
                        icon="pi-check-circle"
                        color="emerald"
                        subtext="Réponses fournies"
                    />
                </div>

                <!-- TABLE DES QUESTIONS -->
                <div class="page-card-flush p-6">
                    <div class="flex items-center gap-3 mb-6 flex-wrap">
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl !text-xs font-bold" />
                        <span class="ml-auto text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">{{ questions?.total ?? 0 }} question(s)</span>
                    </div>

                    <DataTable :value="questions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="questions?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column header="Étudiant">
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
                        <Column field="subject" header="Question">
                            <template #body="{ data }">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-800 truncate">{{ data.subject }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest truncate">{{ data.course?.title ?? '—' }}<span v-if="data.lesson"> · {{ data.lesson.title }}</span></p>
                                </div>
                            </template>
                        </Column>
                        <Column header="PJ" :exportable="false">
                            <template #body="{ data }">
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-blue-600 uppercase tracking-widest" v-tooltip.bottom="`${data.attachments.length} pièce(s) jointe(s)`">
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
                        <Column field="created_at" header="Reçue le" sortable>
                            <template #body="{ data }"><span class="font-mono text-xs text-slate-500">{{ formatDate(data.created_at) }}</span></template>
                        </Column>
                        <Column header="Action" :exportable="false">
                            <template #body="{ data }">
                                <Button :label="data.answer ? 'Voir / modifier' : 'Répondre'" icon="pi pi-reply" size="small" :severity="data.answer ? 'secondary' : 'primary'" @click="openAnswer(data)" :outlined="!!data.answer" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-12 text-slate-400">
                                <i class="pi pi-inbox text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-sm font-bold text-slate-600">Aucune question pour vos cours.</p>
                                <p class="text-xs text-slate-400 mt-1">Les questions des étudiants inscrits à vos cours apparaîtront ici.</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- DIALOG RÉPONSE FORMATEUR -->
        <Dialog v-model:visible="showAnswerDialog" modal :style="{ width: '700px' }" :header="activeQuestion?.subject || 'Répondre'" class="!rounded-2xl" :closable="!submitting">
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
                            <i class="pi pi-paperclip"></i> Pièces jointes de l'étudiant
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
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Votre réponse pédagogique</label>
                    <Textarea v-model="answerText" rows="6" class="w-full !rounded-xl" placeholder="Rédigez une réponse claire et détaillée..." autoResize />
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600 mb-1.5 block">Pièces jointes complémentaires (optionnel)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-600 transition-colors w-fit">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">Ajouter un fichier</span>
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
                    <Button label="Annuler" outlined :disabled="submitting" @click="showAnswerDialog = false" class="!rounded-xl !text-xs font-bold" />
                    <Button :label="submitting ? 'Envoi...' : 'Envoyer la réponse'" icon="pi pi-send" :loading="submitting" @click="submitAnswer" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-lg shadow-blue-500/20" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
