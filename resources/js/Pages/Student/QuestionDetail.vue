<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
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
        <Head :title="question.subject || 'Question'" />
        <Toast />
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-4xl mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg border border-white/10">
                            <i class="pi pi-question-circle text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-2xl font-black text-white tracking-tight">{{ question.subject }}</h1>
                                <Tag :value="statusLabel(question.status)" :severity="statusSeverity(question.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                                <Tag :value="priorityLabel(question.priority)" :severity="prioritySeverity(question.priority)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ question.course?.title }}<span v-if="question.lesson"> · {{ question.lesson.title }}</span></p>
                        </div>
                    </div>
                    <Link href="/student/questions">
                        <Button label="Retour" icon="pi pi-arrow-left" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4 lg:px-6 -mt-6 relative z-20 space-y-4">
                <!-- Question initiale -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-start gap-4 mb-4">
                        <Avatar :label="question.user?.name?.charAt(0)" shape="circle" class="!w-12 !h-12 !bg-indigo-100 !text-indigo-700 !font-black" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-slate-800">{{ question.user?.name }}</p>
                                <p class="text-[10px] font-mono text-slate-400">{{ formatDate(question.created_at) }}</p>
                            </div>
                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Vous · Apprenant</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ question.body }}</p>

                    <!-- Pièces jointes -->
                    <div v-if="question.attachments?.length" class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> Pièces jointes ({{ question.attachments.length }})
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <a v-for="(att, i) in question.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 rounded-xl px-3 py-2 transition-colors">
                                <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center shrink-0">
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

                <!-- Réponse instructeur -->
                <div v-if="question.answer" class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-3xl p-6 border border-emerald-200 shadow-sm">
                    <div class="flex items-start gap-4 mb-4">
                        <Avatar :label="question.instructor?.name?.charAt(0)" :image="question.instructor?.avatar" shape="circle" class="!w-12 !h-12 !bg-emerald-500 !text-white !font-black" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-emerald-800">{{ question.instructor?.name ?? 'Instructeur' }}</p>
                                <p class="text-[10px] font-mono text-emerald-600">{{ formatDate(question.answered_at) }}</p>
                            </div>
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Instructeur · Réponse officielle</p>
                        </div>
                    </div>
                    <p class="text-sm text-emerald-900 leading-relaxed whitespace-pre-line">{{ question.answer }}</p>

                    <!-- Pièces jointes de la réponse -->
                    <div v-if="question.answer_attachments?.length" class="mt-4 pt-4 border-t border-emerald-200">
                        <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest mb-2 flex items-center gap-2">
                            <i class="pi pi-paperclip"></i> Pièces jointes ({{ question.answer_attachments.length }})
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <a v-for="(att, i) in question.answer_attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                               class="flex items-center gap-2 bg-white/70 hover:bg-white border border-emerald-200 rounded-xl px-3 py-2 transition-colors">
                                <div class="w-8 h-8 bg-emerald-200 text-emerald-700 rounded-lg flex items-center justify-center shrink-0">
                                    <i :class="att.is_image ? 'pi pi-image' : 'pi pi-file'"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-emerald-800 truncate">{{ att.original_name }}</p>
                                    <p class="text-[10px] text-emerald-600 font-mono">{{ formatSize(att.size) }}</p>
                                </div>
                                <i class="pi pi-download text-emerald-600 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Suivi de la conversation (réponses) -->
                <div v-for="r in (question.replies ?? [])" :key="r.id" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-start gap-3 mb-2">
                        <Avatar :label="r.user?.name?.charAt(0)" shape="circle" class="!w-9 !h-9 !bg-slate-100 !text-slate-700 !font-black !text-xs" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <p class="text-sm font-black text-slate-800">{{ r.user?.name }}</p>
                                <p class="text-[10px] font-mono text-slate-400">{{ formatDate(r.created_at) }}</p>
                            </div>
                            <p class="text-sm text-slate-700 leading-relaxed mt-1 whitespace-pre-line">{{ r.body }}</p>

                            <div v-if="r.attachments?.length" class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <a v-for="(att, i) in r.attachments" :key="i" :href="att.url" target="_blank" rel="noopener"
                                   class="flex items-center gap-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs">
                                    <i :class="att.is_image ? 'pi pi-image text-indigo-500' : 'pi pi-file text-slate-500'"></i>
                                    <span class="truncate font-bold text-slate-700 flex-1">{{ att.original_name }}</span>
                                    <span class="text-[9px] text-slate-400 font-mono">{{ formatSize(att.size) }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zone de réponse -->
                <div v-if="question.status !== 'closed'" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-3">Ajouter une réponse / précision</h3>
                    <Textarea v-model="reply" rows="4" class="w-full !rounded-xl" placeholder="Écrivez votre message..." autoResize />

                    <div class="mt-3">
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 border border-dashed border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-600 transition-colors w-fit">
                            <i class="pi pi-paperclip text-slate-500"></i>
                            <span class="font-bold">Joindre un fichier (optionnel)</span>
                            <input type="file" multiple class="hidden" @change="onFileChange" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md,.zip,.rar,.7z,.json,.xml" />
                        </label>
                        <div v-if="replyFiles.length" class="mt-2 space-y-1.5">
                            <div v-for="(f, i) in replyFiles" :key="i" class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 text-xs w-fit">
                                <i :class="f.type?.startsWith('image/') ? 'pi pi-image text-indigo-500' : 'pi pi-file text-indigo-500'"></i>
                                <span class="font-bold text-slate-700">{{ f.name }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ formatSize(f.size) }}</span>
                                <button type="button" @click="removeFile(i)" class="w-5 h-5 rounded-full bg-rose-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center">
                                    <i class="pi pi-times text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-3">
                        <Button v-if="question.status !== 'resolved'" label="Marquer comme résolu" icon="pi pi-check-circle" outlined @click="markResolved" class="!rounded-xl !text-emerald-600 !border-emerald-200" />
                        <Button :label="submitting ? 'Envoi...' : 'Envoyer'" icon="pi pi-send" :loading="submitting" :disabled="!reply.trim()" @click="sendReply" class="!bg-gradient-to-r !from-indigo-500 !to-rose-500 !border-none !rounded-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
