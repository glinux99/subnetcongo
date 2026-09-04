<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    questions: Object,
    stats: Object,
    filters: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');
const filterOptions = [
    { label: 'Toutes', value: 'all' },
    { label: 'En attente', value: 'open' },
    { label: 'Répondues', value: 'answered' },
    { label: 'Résolues', value: 'resolved' },
    { label: 'Fermées', value: 'closed' },
];

const statusLabel = (s) => ({ open: 'En attente', answered: 'Répondue', resolved: 'Résolue', closed: 'Fermée' }[s] ?? s);
const statusSeverity = (s) => ({ open: 'warn', answered: 'info', resolved: 'success', closed: 'secondary' }[s] ?? 'info');
const priorityLabel = (p) => ({ urgent: 'Bloquante', high: 'Importante', normal: 'Normale', low: 'Faible' }[p] ?? p);
const prioritySeverity = (p) => ({ urgent: 'danger', high: 'warn', normal: 'info', low: 'secondary' }[p] ?? 'info');
const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const applyFilter = () => router.get(route('instructor.questions.index'), { status: statusFilter.value }, { preserveState: true });

// Answer dialog
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
        <Head title="Questions des apprenants" />
        <Toast />
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-amber-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-indigo-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-indigo-500 rounded-2xl flex items-center justify-center shadow-lg border border-white/10">
                        <i class="pi pi-inbox text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-white tracking-tight">Questions des apprenants</h1>
                        <p class="text-slate-400 font-medium text-sm">Répondez aux questions posées sur vos cours.</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Total', value: stats?.total ?? 0, icon: 'pi-list', color: 'from-indigo-500 to-blue-500' },
                        { label: 'En attente', value: stats?.open ?? 0, icon: 'pi-clock', color: 'from-amber-500 to-orange-500' },
                        { label: 'Répondues', value: stats?.answered ?? 0, icon: 'pi-reply', color: 'from-emerald-500 to-teal-500' },
                    ]" :key="kpi.label" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                        <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white shadow-lg mb-3', kpi.color]">
                            <i :class="['pi', kpi.icon, 'text-xl']"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-800 tracking-tight">{{ kpi.value }}</div>
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4 flex-wrap">
                        <select v-model="statusFilter" class="px-3 py-2 border border-slate-200 rounded-xl text-xs font-black uppercase tracking-widest" @change="applyFilter">
                            <option v-for="f in filterOptions" :key="f.value" :value="f.value">{{ f.label }}</option>
                        </select>
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl" />
                        <span class="ml-auto text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ questions?.total ?? 0 }} question(s)</span>
                    </div>

                    <DataTable :value="questions?.data ?? []" stripedRows paginator :rows="15" :totalRecords="questions?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column header="Étudiant">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Avatar :label="data.user?.name?.charAt(0)" shape="circle" size="normal" class="!bg-indigo-100 !text-indigo-700 !font-black" />
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
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-indigo-600 uppercase tracking-widest" v-tooltip.bottom="`${data.attachments.length} pièce(s) jointe(s)`">
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
                            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                        </Column>
                        <Column header="Action" :exportable="false">
                            <template #body="{ data }">
                                <Button :label="data.answer ? 'Voir / modifier' : 'Répondre'" icon="pi pi-reply" size="small" :severity="data.answer ? 'secondary' : 'success'" @click="openAnswer(data)" outlined class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-inbox text-3xl mb-2 block"></i>
                                Aucune question pour vos cours.
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showAnswerDialog" modal :style="{ width: '720px' }" :header="activeQuestion?.subject || 'Répondre'" class="!rounded-3xl" :closable="!submitting">
            <div v-if="activeQuestion" class="space-y-3 p-2">
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                    <div class="flex items-center gap-2 mb-2">
                        <Avatar :label="activeQuestion.user?.name?.charAt(0)" shape="circle" class="!w-9 !h-9 !bg-indigo-100 !text-indigo-700 !font-black !text-xs" />
                        <div>
                            <p class="text-xs font-black text-slate-800">{{ activeQuestion.user?.name }}</p>
                            <p class="text-[10px] text-slate-400">{{ formatDate(activeQuestion.created_at) }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ activeQuestion.body }}</p>

                    <!-- Pièces jointes de la question (lecture seule) -->
                    <div v-if="activeQuestion.attachments?.length" class="mt-3 pt-3 border-t border-slate-200">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> Pièces jointes de l'étudiant
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <a v-for="(att, i) in activeQuestion.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 rounded-lg px-2.5 py-1.5 text-xs">
                                <i :class="att.is_image ? 'pi pi-image text-indigo-500' : 'pi pi-file text-slate-500'"></i>
                                <span class="truncate font-bold text-slate-700 flex-1">{{ att.original_name }}</span>
                                <span class="text-[9px] text-slate-400 font-mono">{{ formatSize(att.size) }}</span>
                                <i class="pi pi-download text-slate-400 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Votre réponse</label>
                    <Textarea v-model="answerText" rows="6" class="w-full !rounded-xl" autoResize />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Pièces jointes (optionnel · 5 max · 10Mo / fichier)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">Ajouter un fichier</span>
                        <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="answerFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in answerFiles" :key="i" class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-xs">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-indigo-500' : 'pi pi-file text-indigo-500'"></i>
                            <span class="flex-1 truncate font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ formatSize(f.size) }}</span>
                            <button type="button" @click="removeFile(i)" class="w-5 h-5 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center">
                                <i class="pi pi-times text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined :disabled="submitting" @click="showAnswerDialog = false" class="!rounded-xl" />
                <Button :label="submitting ? 'Envoi...' : 'Envoyer la réponse'" icon="pi pi-send" :loading="submitting" @click="submitAnswer" class="!bg-gradient-to-r !from-amber-500 !to-indigo-500 !border-none !rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
