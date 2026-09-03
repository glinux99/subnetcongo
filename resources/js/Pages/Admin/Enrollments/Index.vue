<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    enrollments: Object,
    stats: Object,
    filters: Object,
    courses: Array,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'pending');
const search = ref(props.filters?.search ?? '');
const courseFilter = ref(props.filters?.course_id ?? null);

const statusOptions = [
    { label: 'Toutes', value: 'all' },
    { label: 'En attente', value: 'pending' },
    { label: 'Validées', value: 'active' },
    { label: 'Suspendues', value: 'suspended' },
    { label: 'Refusées', value: 'rejected' },
    { label: 'Terminées', value: 'completed' },
    { label: 'Annulées', value: 'cancelled' },
];

const statusLabel = (s) => ({
    active: 'Validée', pending: 'En attente', completed: 'Terminée',
    suspended: 'Suspendue', rejected: 'Refusée', cancelled: 'Annulée', expired: 'Expirée',
}[s] ?? s);

const statusSeverity = (s) => ({
    active: 'success', pending: 'warn', completed: 'info',
    suspended: 'danger', rejected: 'danger', cancelled: 'secondary', expired: 'secondary',
}[s] ?? 'secondary');

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
const formatPrice = (p) => p ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p) : 'Gratuit';

const applyFilter = () => {
    router.get(route('admin.enrollments.index'), {
        status: statusFilter.value,
        search: search.value,
        course_id: courseFilter.value,
    }, { preserveState: true });
};

const selected = ref([]);
const showBulkDialog = ref(false);
const bulkAction = ref(null);
const bulkNotes = ref('');

const askBulk = (action) => {
    if (selected.value.length === 0) {
        toast.add({ severity: 'warn', summary: 'Sélectionnez au moins une ligne', life: 2500 });
        return;
    }
    bulkAction.value = action;
    bulkNotes.value = '';
    showBulkDialog.value = true;
};

const submitBulk = () => {
    if (!bulkAction.value) return;
    router.post(route('admin.enrollments.bulk'), {
        action: bulkAction.value,
        ids: selected.value,
        admin_notes: bulkNotes.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkDialog.value = false;
            selected.value = [];
            toast.add({ severity: 'success', summary: 'Action appliquée', life: 2500 });
        },
    });
};

const quickApprove = (id) => router.post(route('admin.enrollments.approve', id), {}, { preserveScroll: true });
const quickReject = (id) => {
    const reason = prompt('Motif du refus (obligatoire) :');
    if (!reason) return;
    router.post(route('admin.enrollments.reject', id), { admin_notes: reason }, { preserveScroll: true });
};
const quickRevoke = (id) => {
    const reason = prompt('Motif de la révocation (obligatoire) :');
    if (!reason) return;
    router.post(route('admin.enrollments.revoke', id), { revoked_reason: reason }, { preserveScroll: true });
};
const quickReactivate = (id) => router.post(route('admin.enrollments.reactivate', id), {}, { preserveScroll: true });
const quickDelete = (id) => {
    if (!confirm('Supprimer définitivement cette inscription ?')) return;
    router.delete(route('admin.enrollments.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Demandes d'inscription" />
        <Toast />
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-6 pb-12 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-rose-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-amber-500 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-500/30 border border-white/10">
                            <i class="pi pi-users text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Demandes d'inscription</h1>
                            <p class="text-slate-400 font-medium text-sm">Approuver, refuser ou révoquer les accès des étudiants.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Aujourd\'hui', value: stats?.today ?? 0, icon: 'pi-calendar', color: 'from-indigo-500 to-blue-500' },
                        { label: 'En attente', value: stats?.pending ?? 0, icon: 'pi-clock', color: 'from-amber-500 to-orange-500', alert: true },
                        { label: 'Validées', value: stats?.active ?? 0, icon: 'pi-check-circle', color: 'from-emerald-500 to-teal-500' },
                        { label: 'Suspendues', value: stats?.suspended ?? 0, icon: 'pi-ban', color: 'from-rose-500 to-pink-500' },
                        { label: 'Refusées', value: stats?.rejected ?? 0, icon: 'pi-times-circle', color: 'from-rose-600 to-red-600' },
                        { label: 'Terminées', value: stats?.completed ?? 0, icon: 'pi-trophy', color: 'from-violet-500 to-indigo-500' },
                    ]" :key="kpi.label" class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm" :class="kpi.alert && kpi.value > 0 ? 'ring-2 ring-amber-300' : ''">
                        <div :class="['w-10 h-10 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white shadow-lg mb-2', kpi.color]">
                            <i :class="['pi', kpi.icon, 'text-base']"></i>
                        </div>
                        <div class="text-xl font-black text-slate-800 tracking-tight font-mono">{{ kpi.value }}</div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <!-- Filtres + actions bulk -->
                    <div class="flex items-center gap-3 mb-4 flex-wrap">
                        <span class="p-input-icon-left flex-1 min-w-[200px]">
                            <i class="pi pi-search"></i>
                            <InputText v-model="search" placeholder="Rechercher (nom, email, cours)..." class="w-full !rounded-xl" @keyup.enter="applyFilter" />
                        </span>
                        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Select v-model="courseFilter" :options="courses" optionLabel="title" optionValue="id" placeholder="Tous les cours" class="!rounded-xl min-w-[200px]" showClear />
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl" />

                        <span class="ml-auto text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ enrollments?.total ?? 0 }} inscription(s)</span>
                    </div>

                    <!-- Actions en masse -->
                    <div v-if="selected.length" class="flex items-center gap-2 mb-3 p-2.5 bg-indigo-50 border border-indigo-200 rounded-xl">
                        <i class="pi pi-check-square text-indigo-600"></i>
                        <span class="text-xs font-black text-indigo-700">{{ selected.length }} sélectionné(s)</span>
                        <div class="ml-auto flex items-center gap-1">
                            <Button label="Approuver" icon="pi pi-check" size="small" severity="success" @click="askBulk('approve')" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            <Button label="Refuser" icon="pi pi-times" size="small" severity="danger" @click="askBulk('reject')" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                            <Button label="Supprimer" icon="pi pi-trash" size="small" severity="secondary" @click="askBulk('delete')" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                    </div>

                    <DataTable :value="enrollments?.data ?? []" v-model:selection="selected" dataKey="id" stripedRows paginator :rows="20" :totalRecords="enrollments?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column selectionMode="multiple" headerStyle="width: 3rem" />
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
                        <Column header="Cours">
                            <template #body="{ data }">
                                <p class="text-sm font-black text-slate-800 truncate" :title="data.course?.title">{{ data.course?.title }}</p>
                                <p class="text-[10px] text-slate-400">{{ formatPrice(data.course?.price) }}</p>
                            </template>
                        </Column>
                        <Column field="status" header="Statut" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column header="Commande">
                            <template #body="{ data }">
                                <span v-if="data.order" class="text-[10px] font-mono text-slate-600">
                                    {{ data.order.order_number }}<br />
                                    <Tag :value="data.order.status" :severity="data.order.status === 'paid' ? 'success' : 'warn'" class="!text-[9px] !font-black !uppercase !tracking-widest !mt-1" />
                                </span>
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </template>
                        </Column>
                        <Column header="Date" sortable>
                            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                        </Column>
                        <Column header="Actions" :exportable="false">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1">
                                    <Link :href="route('admin.enrollments.show', data.id)">
                                        <Button icon="pi pi-eye" rounded text severity="secondary" v-tooltip.bottom="'Détails'" />
                                    </Link>
                                    <Button v-if="['pending','suspended','rejected'].includes(data.status)" icon="pi pi-check" rounded text severity="success" @click="quickApprove(data.id)" v-tooltip.bottom="'Approuver'" />
                                    <Button v-if="data.status === 'pending'" icon="pi pi-times" rounded text severity="warn" @click="quickReject(data.id)" v-tooltip.bottom="'Refuser'" />
                                    <Button v-if="data.status === 'active'" icon="pi pi-ban" rounded text severity="danger" @click="quickRevoke(data.id)" v-tooltip.bottom="'Révoquer'" />
                                    <Button v-if="['suspended','rejected'].includes(data.status)" icon="pi pi-refresh" rounded text severity="info" @click="quickReactivate(data.id)" v-tooltip.bottom="'Réactiver'" />
                                    <Button icon="pi pi-trash" rounded text severity="secondary" @click="quickDelete(data.id)" v-tooltip.bottom="'Supprimer'" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-inbox text-3xl mb-2 block"></i>
                                Aucune inscription trouvée.
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showBulkDialog" modal :style="{ width: '500px' }" header="Action en masse" class="!rounded-3xl">
            <div class="p-2">
                <p class="text-sm text-slate-600 mb-3">
                    Vous allez appliquer l'action <strong>{{ bulkAction === 'approve' ? 'Approuver' : bulkAction === 'reject' ? 'Refuser' : 'Supprimer' }}</strong>
                    à <strong>{{ selected.length }}</strong> inscription(s).
                </p>
                <div v-if="bulkAction === 'reject'">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 block">Motif du refus (obligatoire)</label>
                    <Textarea v-model="bulkNotes" rows="3" class="w-full !rounded-xl" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" outlined @click="showBulkDialog = false" class="!rounded-xl" />
                <Button :label="bulkAction === 'delete' ? 'Supprimer' : 'Appliquer'" :severity="bulkAction === 'reject' ? 'danger' : bulkAction === 'delete' ? 'danger' : 'success'" @click="submitBulk" class="!rounded-xl" />
            </template>
        </Dialog>
    </AppLayout>
</template>
