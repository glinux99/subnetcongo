<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({ enrollment: Object });

const toast = useToast();
const showApprove = ref(false);
const showReject = ref(false);
const showRevoke = ref(false);
const notes = ref('');
const reason = ref('');
const submitting = ref(false);

const statusLabel = (s) => ({
    active: 'Validée', pending: 'En attente', completed: 'Terminée',
    suspended: 'Suspendue', rejected: 'Refusée', cancelled: 'Annulée', expired: 'Expirée',
}[s] ?? s);

const statusSeverity = (s) => ({
    active: 'success', pending: 'warn', completed: 'info',
    suspended: 'danger', rejected: 'danger', cancelled: 'secondary', expired: 'secondary',
}[s] ?? 'secondary');

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'long', timeStyle: 'short' }) : '—';

const submit = (action) => {
    submitting.value = true;
    const data = action === 'approve' ? { admin_notes: notes.value } : action === 'reject' ? { admin_notes: notes.value } : { revoked_reason: reason.value };
    router.post(route(`admin.enrollments.${action}`, props.enrollment.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            submitting.value = false;
            showApprove.value = showReject.value = showRevoke.value = false;
            notes.value = reason.value = '';
            toast.add({ severity: 'success', summary: 'Action enregistrée', life: 2500 });
        },
        onError: () => { submitting.value = false; },
    });
};

const reactivate = () => router.post(route('admin.enrollments.reactivate', props.enrollment.id), {}, { preserveScroll: true });
const destroy = () => {
    if (confirm('Supprimer définitivement ?')) router.delete(route('admin.enrollments.destroy', props.enrollment.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Inscription #${enrollment.id}`" />
        <Toast />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-5xl mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg border border-white/10">
                            <i class="pi pi-id-card text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-2xl font-black text-white tracking-tight">Inscription #{{ enrollment.id }}</h1>
                                <Tag :value="statusLabel(enrollment.status)" :severity="statusSeverity(enrollment.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">{{ enrollment.course?.title }}</p>
                        </div>
                    </div>
                    <Link :href="route('admin.enrollments.index')">
                        <Button label="Retour" icon="pi pi-arrow-left" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 lg:px-6 -mt-6 relative z-20 space-y-4">
                <!-- Actions -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex flex-wrap items-center gap-3">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mr-auto">Actions</h3>
                    <Button v-if="['pending','suspended','rejected'].includes(enrollment.status)" label="Approuver" icon="pi pi-check" @click="showApprove = true" class="!bg-emerald-500 hover:!bg-emerald-600 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5 !px-4" />
                    <Button v-if="enrollment.status === 'pending'" label="Refuser" icon="pi pi-times" severity="danger" outlined @click="showReject = true" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5 !px-4" />
                    <Button v-if="enrollment.status === 'active'" label="Révoquer l'accès" icon="pi pi-ban" severity="danger" outlined @click="showRevoke = true" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5 !px-4" />
                    <Button v-if="['suspended','rejected'].includes(enrollment.status)" label="Réactiver" icon="pi pi-refresh" severity="info" outlined @click="reactivate" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5 !px-4" />
                    <Button label="Supprimer" icon="pi pi-trash" severity="secondary" outlined @click="destroy" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2.5 !px-4" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Étudiant -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Étudiant</h3>
                        <div class="flex items-center gap-3 mb-3">
                            <Avatar :label="enrollment.user?.name?.charAt(0)" shape="circle" class="!w-12 !h-12 !bg-indigo-100 !text-indigo-700 !font-black" />
                            <div>
                                <p class="text-sm font-black text-slate-800">{{ enrollment.user?.name }}</p>
                                <p class="text-xs text-slate-500">{{ enrollment.user?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cours -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Cours</h3>
                        <p class="text-sm font-black text-slate-800">{{ enrollment.course?.title }}</p>
                        <p class="text-xs text-slate-500">Instructeur : {{ enrollment.course?.instructor?.name ?? '—' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Créé le {{ formatDate(enrollment.created_at) }}</p>
                    </div>

                    <!-- Workflow -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm md:col-span-2">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Workflow</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Demande</p>
                                <p class="font-mono text-slate-700">{{ formatDate(enrollment.created_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Inscription effective</p>
                                <p class="font-mono text-slate-700">{{ formatDate(enrollment.enrolled_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Revue admin</p>
                                <p class="font-mono text-slate-700">{{ formatDate(enrollment.reviewed_at) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Révoqué le</p>
                                <p class="font-mono text-rose-600">{{ formatDate(enrollment.revoked_at) }}</p>
                            </div>
                        </div>
                        <div v-if="enrollment.admin_notes" class="mt-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Notes admin</p>
                            <p class="text-sm text-slate-700 whitespace-pre-line">{{ enrollment.admin_notes }}</p>
                        </div>
                        <div v-if="enrollment.revoked_reason" class="mt-3 p-3 bg-rose-50 rounded-xl border border-rose-200">
                            <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1">Motif de révocation</p>
                            <p class="text-sm text-rose-700 whitespace-pre-line">{{ enrollment.revoked_reason }}</p>
                        </div>
                    </div>

                    <!-- Commande -->
                    <div v-if="enrollment.order" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm md:col-span-2">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Commande associée</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Numéro</p>
                                <p class="font-mono text-slate-700">{{ enrollment.order.order_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Montant</p>
                                <p class="font-mono text-slate-700">{{ enrollment.order.amount }} {{ enrollment.order.currency }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</p>
                                <Tag :value="enrollment.order.status" :severity="enrollment.order.status === 'paid' ? 'success' : 'warn'" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Payée le</p>
                                <p class="font-mono text-slate-700">{{ formatDate(enrollment.order.paid_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showApprove" modal :style="{ width: '500px' }" header="Approuver l'inscription" class="!rounded-3xl">
            <div class="p-2 space-y-3">
                <p class="text-sm text-slate-600">L'étudiant aura immédiatement accès au cours et sera notifié.</p>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Note (optionnel)</label>
                    <Textarea v-model="notes" rows="3" class="w-full !rounded-xl" placeholder="Commentaire interne..." />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined @click="showApprove = false" class="!rounded-xl" />
                <Button :label="submitting ? '...' : 'Approuver'" icon="pi pi-check" :loading="submitting" @click="submit('approve')" class="!bg-emerald-500 hover:!bg-emerald-600 !border-none !rounded-xl" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showReject" modal :style="{ width: '500px' }" header="Refuser l'inscription" class="!rounded-3xl">
            <div class="p-2 space-y-3">
                <p class="text-sm text-slate-600">L'étudiant sera notifié du refus avec le motif ci-dessous.</p>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Motif (obligatoire)</label>
                    <Textarea v-model="notes" rows="3" class="w-full !rounded-xl" placeholder="Ex: Paiement non reçu..." />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined @click="showReject = false" class="!rounded-xl" />
                <Button :label="submitting ? '...' : 'Refuser'" icon="pi pi-times" severity="danger" :loading="submitting" @click="submit('reject')" class="!rounded-xl" />
            </template>
        </Dialog>

        <Dialog v-model:visible="showRevoke" modal :style="{ width: '500px' }" header="Révoquer l'accès" class="!rounded-3xl">
            <div class="p-2 space-y-3">
                <p class="text-sm text-rose-600 font-bold">⚠ L'étudiant perdra immédiatement l'accès au cours.</p>
                <div>
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Motif (obligatoire)</label>
                    <Textarea v-model="reason" rows="3" class="w-full !rounded-xl" placeholder="Ex: Fraude, non-respect des règles..." />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined @click="showRevoke = false" class="!rounded-xl" />
                <Button :label="submitting ? '...' : 'Révoquer'" icon="pi pi-ban" severity="danger" :loading="submitting" @click="submit('revoke')" class="!rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
