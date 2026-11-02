<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';

const props = defineProps({ question: Object });

const toast = useToast();
const reply = ref('');
const replyFiles = ref([]);
const submitting = ref(false);

const statusLabel = (s) => ({ open: 'En attente', answered: 'Répondue', resolved: 'Résolue', closed: 'Fermée' }[s] ?? s);
const statusSeverity = (s) => ({ open: 'warn', answered: 'info', resolved: 'success', closed: 'secondary' }[s] ?? 'info');
const priorityLabel = (p) => ({ urgent: 'Bloquante', high: 'Importante', normal: 'Normale', low: 'Faible' }[p] ?? p);
const prioritySeverity = (p) => ({ urgent: 'danger', high: 'warn', normal: 'info', low: 'secondary' }[p] ?? 'info');
const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' }) : '—';
const formatSize = (b) => b < 1024 ? `${b} o` : b < 1048576 ? `${(b / 1024).toFixed(1)} Ko` : `${(b / 1048576).toFixed(1)} Mo`;

const onFileChange = (e) => {
    const files = Array.from(e.target.files ?? []);
    replyFiles.value = [...replyFiles.value, ...files].slice(0, 5);
    e.target.value = '';
};
const removeFile = (i) => replyFiles.value.splice(i, 1);

const sendReply = () => {
    if (!reply.value.trim()) return;
    submitting.value = true;
    router.post(route('student.questions.reply', props.question.id), {
        body: reply.value,
        attachments: replyFiles.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            reply.value = '';
            replyFiles.value = [];
            submitting.value = false;
            toast.add({ severity: 'success', summary: 'Réponse envoyée', detail: page.props?.flash?.success ?? '', life: 2500 });
        },
        onError: () => { submitting.value = false; },
    });
};

const markResolved = () => {
    router.post(route('student.questions.resolve', props.question.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Question résolue', life: 2500 }),
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="question.subject || 'Détail de la Question'" />

        <div class="page-bg min-h-screen">
            <!-- HEADER HARMONISÉ -->
            <PageHeader
                :title="question.subject || 'Question'"
                :subtitle="`${question.course?.title || 'Formation'}${question.lesson ? ' · ' + question.lesson.title : ''}`"
                icon="pi-comments"
                badge="Q&A"
                accent="blue"
                :actions="[
                    { label: 'Retour aux questions', icon: 'pi-arrow-left', href: route('student.questions.index') }
                ]"
            >
                <template #meta>
                    <div class="flex items-center gap-2 mt-3">
                        <Tag :value="statusLabel(question.status)" :severity="statusSeverity(question.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                        <Tag :value="priorityLabel(question.priority)" :severity="prioritySeverity(question.priority)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                    </div>
                </template>
            </PageHeader>

            <div class="page-container max-w-4xl space-y-6">
                <!-- QUESTION INITIALE -->
                <div class="page-card p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <Avatar :label="question.user?.name?.charAt(0)" shape="circle" class="!w-12 !h-12 !bg-blue-100 !text-blue-700 !font-black shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-slate-800">{{ question.user?.name }}</p>
                                <p class="text-xs font-mono text-slate-400">{{ formatDate(question.created_at) }}</p>
                            </div>
                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mt-0.5">Vous · Apprenant</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ question.body }}</p>

                    <!-- Pièces jointes -->
                    <div v-if="question.attachments?.length" class="mt-5 pt-4 border-t border-slate-100">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> Pièces jointes ({{ question.attachments.length }})
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a v-for="(att, i) in question.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-3 bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-xl px-3 py-2.5 transition-colors">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center shrink-0">
                                    <i :class="att.is_image ? 'pi pi-image' : 'pi pi-file'"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-slate-700 truncate">{{ att.original_name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ formatSize(att.size) }}</p>
                                </div>
                                <i class="pi pi-download text-slate-400 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RÉPONSE INSTRUCTEUR -->
                <div v-if="question.answer" class="rounded-xl p-6 border border-emerald-200 bg-emerald-50/50 shadow-sm">
                    <div class="flex items-start gap-4 mb-4">
                        <Avatar :label="question.instructor?.name?.charAt(0)" :image="question.instructor?.avatar" shape="circle" class="!w-12 !h-12 !bg-emerald-600 !text-white !font-black shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-emerald-900">{{ question.instructor?.name ?? 'Instructeur' }}</p>
                                <p class="text-xs font-mono text-emerald-700">{{ formatDate(question.answered_at) }}</p>
                            </div>
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mt-0.5">Instructeur · Réponse officielle</p>
                        </div>
                    </div>
                    <p class="text-sm text-emerald-950 leading-relaxed whitespace-pre-line">{{ question.answer }}</p>

                    <!-- Pièces jointes de la réponse -->
                    <div v-if="question.answer_attachments?.length" class="mt-5 pt-4 border-t border-emerald-200">
                        <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> Pièces jointes ({{ question.answer_attachments.length }})
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a v-for="(att, i) in question.answer_attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-3 bg-white hover:bg-emerald-100/50 border border-emerald-200 rounded-xl px-3 py-2.5 transition-colors">
                                <div class="w-8 h-8 bg-emerald-100 text-emerald-700 rounded-lg flex items-center justify-center shrink-0">
                                    <i :class="att.is_image ? 'pi pi-image' : 'pi pi-file'"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-emerald-900 truncate">{{ att.original_name }}</p>
                                    <p class="text-[10px] text-emerald-600 font-mono">{{ formatSize(att.size) }}</p>
                                </div>
                                <i class="pi pi-download text-emerald-700 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- SUIVI DE LA CONVERSATION -->
                <div v-for="r in (question.replies ?? [])" :key="r.id" class="page-card p-5">
                    <div class="flex items-start gap-3 mb-2">
                        <Avatar :label="r.user?.name?.charAt(0)" shape="circle" class="!w-9 !h-9 !bg-slate-100 !text-slate-700 !font-black !text-xs shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-slate-800">{{ r.user?.name }}</p>
                                <p class="text-xs font-mono text-slate-400">{{ formatDate(r.created_at) }}</p>
                            </div>
                            <p class="text-sm text-slate-700 leading-relaxed mt-2 whitespace-pre-line">{{ r.body }}</p>

                            <div v-if="r.attachments?.length" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <a v-for="(att, i) in r.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                                   class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs">
                                    <i :class="att.is_image ? 'pi pi-image text-blue-500' : 'pi pi-file text-slate-500'"></i>
                                    <span class="truncate font-bold text-slate-700 flex-1">{{ att.original_name }}</span>
                                    <span class="text-[9px] text-slate-400 font-mono">{{ formatSize(att.size) }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE RÉPONSE -->
                <div v-if="question.status !== 'closed'" class="page-card p-6">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-700 mb-3">Ajouter une précision ou répondre</h3>
                    <Textarea v-model="reply" rows="4" class="w-full !rounded-xl" placeholder="Écrivez votre message..." autoResize />

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-600 transition-colors">
                            <i class="pi pi-paperclip text-slate-500"></i>
                            <span class="font-bold">Joindre un fichier</span>
                            <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                        </label>

                        <div class="flex gap-2">
                            <Button v-if="question.status !== 'resolved'" label="Marquer comme résolu" icon="pi pi-check-circle" outlined @click="markResolved" class="!rounded-xl !text-xs !font-bold !text-emerald-600 !border-emerald-300" />
                            <Button :label="submitting ? 'Envoi...' : 'Envoyer'" icon="pi pi-send" :loading="submitting" :disabled="!reply.trim()" @click="sendReply" class="!bg-blue-600 hover:!bg-blue-700 !border-none !rounded-xl !text-xs !font-black !uppercase !tracking-widest text-white shadow-sm" />
                        </div>
                    </div>

                    <div v-if="replyFiles.length" class="mt-3 flex flex-wrap gap-2">
                        <div v-for="(f, i) in replyFiles" :key="i" class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-1.5 text-xs">
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
        </div>
    </AppLayout>
</template>
