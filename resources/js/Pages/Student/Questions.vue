<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
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
    { label: 'Toutes', value: 'all' },
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
        <Head title="Mes questions" />
        <Toast />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30 border border-white/10">
                            <i class="pi pi-question-circle text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Mes questions</h1>
                            <p class="text-slate-400 font-medium text-sm">Posez vos questions aux instructeurs et suivez leurs réponses.</p>
                        </div>
                    </div>
                    <Button label="Poser une question" icon="pi pi-plus" @click="openAsk" class="!bg-gradient-to-r !from-amber-500 !to-rose-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 !px-5" />
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Total', value: stats?.total ?? 0, icon: 'pi-list', color: 'from-indigo-500 to-blue-500' },
                        { label: 'En attente', value: stats?.open ?? 0, icon: 'pi-clock', color: 'from-amber-500 to-orange-500' },
                        { label: 'Répondues', value: stats?.answered ?? 0, icon: 'pi-reply', color: 'from-cyan-500 to-blue-500' },
                        { label: 'Résolues', value: stats?.resolved ?? 0, icon: 'pi-check-circle', color: 'from-emerald-500 to-teal-500' },
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
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl" />
                        <span class="ml-auto text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ questions?.total ?? 0 }} question(s)</span>
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
                                <span v-if="data.attachments?.length" class="inline-flex items-center gap-1 text-[10px] font-black text-indigo-600 uppercase tracking-widest">
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
                                <span v-if="data.instructor">{{ data.instructor.name }}</span>
                                <span v-else class="text-slate-400">—</span>
                            </template>
                        </Column>
                        <Column field="created_at" header="Posée le" sortable>
                            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                        </Column>
                        <Column header="Action" :exportable="false">
                            <template #body="{ data }">
                                <Link :href="route('student.questions.show', data.id)">
                                    <Button label="Voir" icon="pi pi-arrow-right" iconPos="right" size="small" outlined class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                                </Link>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-question-circle text-3xl mb-2 block"></i>
                                Aucune question pour l'instant.
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showAskDialog" modal :style="{ width: '600px' }" header="Poser une question" class="!rounded-3xl" :closable="!askSubmitting">
            <div class="space-y-4 p-2">
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Cours concerné</label>
                    <Select v-model="askForm.course_id" :options="courses" optionLabel="title" optionValue="id" placeholder="Choisir un cours" class="w-full" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Sujet</label>
                    <InputText v-model="askForm.subject" class="w-full !rounded-xl" placeholder="Ex: Je ne comprends pas le subnetting VLSM" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Message détaillé</label>
                    <Textarea v-model="askForm.body" rows="5" class="w-full !rounded-xl" placeholder="Décrivez votre question ou votre blocage..." autoResize />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Priorité</label>
                    <Select v-model="askForm.priority" :options="[{label:'Faible', value:'low'},{label:'Normale', value:'normal'},{label:'Importante', value:'high'},{label:'Bloquante', value:'urgent'}]" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Pièces jointes (capture, PDF, doc… · 5 max · 10Mo / fichier)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2.5 text-xs text-slate-600 transition-colors">
                        <i class="pi pi-paperclip text-slate-500"></i>
                        <span class="font-bold">Ajouter un fichier</span>
                        <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                    </label>
                    <div v-if="askFiles.length" class="mt-2 space-y-1.5">
                        <div v-for="(f, i) in askFiles" :key="i" class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-xs">
                            <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-indigo-500' : 'pi pi-file text-indigo-500'"></i>
                            <span class="flex-1 truncate font-bold text-slate-700">{{ f.name }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ formatSize(f.size) }}</span>
                            <button type="button" @click="removeFile(i)" class="w-5 h-5 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="askErrors.form || askErrors.subject || askErrors.body" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl p-3">
                    {{ askErrors.form || askErrors.subject || askErrors.body }}
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined :disabled="askSubmitting" @click="showAskDialog = false" class="!rounded-xl" />
                <Button :label="askSubmitting ? 'Envoi...' : 'Envoyer'" icon="pi pi-send" :loading="askSubmitting" @click="submitAsk" class="!bg-gradient-to-r !from-amber-500 !to-rose-500 !border-none !rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
