<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
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
    { label: 'Toutes les demandes', value: 'all' },
    { label: 'En attente', value: 'pending' },
    { label: 'Validées (Actives)', value: 'active' },
    { label: 'Suspendues', value: 'suspended' },
    { label: 'Refusées', value: 'rejected' },
    { label: 'Terminées', value: 'completed' },
];

const statusLabel = (s) => ({
    active: 'Opérationnel', pending: 'En attente', completed: 'Diplômé',
    suspended: 'Interrompu', rejected: 'Refusé', cancelled: 'Annulé',
}[s] ?? s);

const statusSeverity = (s) => ({
    active: 'success', pending: 'warn', completed: 'info',
    suspended: 'danger', rejected: 'danger',
}[s] ?? 'secondary');

const formatDate = (d) => d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium' }) : '—';
const formatPrice = (p) => p ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(p) : 'Gratuit';

const applyFilter = () => {
    router.get(route('admin.enrollments.index'), {
        status: statusFilter.value,
        search: search.value,
        course_id: courseFilter.value,
    }, { preserveState: true, preserveScroll: true });
};

const selected = ref([]);
const showBulkDialog = ref(false);
const bulkAction = ref(null);
const bulkNotes = ref('');

const askBulk = (action) => {
    if (selected.value.length === 0) return;
    bulkAction.value = action;
    bulkNotes.value = '';
    showBulkDialog.value = true;
};

const submitBulk = () => {
    router.post(route('admin.enrollments.bulk'), {
        action: bulkAction.value,
        ids: selected.value.map(i => i.id),
        admin_notes: bulkNotes.value || null,
    }, {
        onSuccess: () => {
            showBulkDialog.value = false;
            selected.value = [];
            toast.add({ severity: 'success', summary: 'Système mis à jour', life: 3000 });
        },
    });
};

// Actions Rapides
const quickApprove = (id) => router.post(route('admin.enrollments.approve', id));
const quickReject = (id) => {
    const reason = prompt('Motif du refus :');
    if (reason) router.post(route('admin.enrollments.reject', id), { admin_notes: reason });
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Inscriptions | Admin" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                title="Flux des Inscriptions"
                subtitle="Supervisez les admissions, validez les paiements et gérez les accès aux parcours de formation."
                badge="Enrollment Management"
                icon="pi-id-card"
                accent="blue"
            >
                <template #actions>
                    <div class="flex items-center gap-4 bg-white/5 p-2 rounded-xl border border-white/10 backdrop-blur-xl">
                        <div class="px-6 py-2 text-center border-r border-white/10">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Taux de conversion</p>
                            <p class="text-white font-black text-xl italic">84%</p>
                        </div>
                        <div class="px-6 py-2 text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nouveaux (24h)</p>
                            <p class="text-blue-300 font-black text-xl italic">+{{ stats?.today ?? 0 }}</p>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <div class="page-container page-section">

                <!-- KPI STRIP -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <PageKpi label="En attente" :value="stats?.pending ?? 0" icon="pi-clock" color="amber" />
                    <PageKpi label="Actives" :value="stats?.active ?? 0" icon="pi-check-circle" color="blue" />
                    <PageKpi label="Terminées" :value="stats?.completed ?? 0" icon="pi-trophy" color="emerald" />
                    <PageKpi label="Suspendues" :value="stats?.suspended ?? 0" icon="pi-ban" color="red" />
                    <PageKpi label="Refusées" :value="stats?.rejected ?? 0" icon="pi-times-circle" color="slate" />
                    <PageKpi label="Total global" :value="enrollments?.total ?? 0" icon="pi-users" color="indigo" />
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                    
                    <!-- TOOLBAR PRO -->
                    <div class="p-8 border-b border-slate-100 space-y-6">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                            <div class="flex flex-1 items-center gap-3 w-full">
                                <IconField iconPosition="left" class="flex-1 max-w-md">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="search" placeholder="Rechercher un étudiant ou un cours..." class="w-full !rounded-xl !bg-slate-50 border-none !py-3" @keyup.enter="applyFilter" />
                                </IconField>
                                <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" class="!rounded-xl !bg-slate-50 border-none !min-w-[180px]" @change="applyFilter" />
                                <Select v-model="courseFilter" :options="courses" optionLabel="title" optionValue="id" placeholder="Filtrer par cours" class="!rounded-xl !bg-slate-50 border-none !flex-1 lg:max-w-xs" showClear @change="applyFilter" >
  <template v-slot:empty></template>
</Select>
                            </div>
                            <Button label="Exporter" icon="pi pi-download" text class="!text-slate-500 !font-bold" />
                        </div>

                        <!-- BULK ACTION BAR -->
                        <div v-if="selected.length" class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl animate-fade-in">
                            <div class="flex items-center gap-4 px-4 text-slate-700">
                                <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center font-black text-xs text-white">{{ selected.length }}</div>
                                <span class="text-xs font-bold uppercase tracking-widest">Éléments sélectionnés</span>
                            </div>
                            <div class="flex gap-2">
                                <Button label="Approuver" icon="pi pi-check" severity="success" size="small" @click="askBulk('approve')" class="!rounded-xl !text-[10px] !font-black !px-6" />
                                <Button label="Refuser" icon="pi pi-times" severity="danger" size="small" @click="askBulk('reject')" class="!rounded-xl !text-[10px] !font-black !px-6" />
                                <Button label="Annuler" text @click="selected = []" class="!text-slate-400 !text-xs" />
                            </div>
                        </div>
                    </div>

                    <!-- TABLE V30 -->
                    <DataTable :value="enrollments?.data ?? []" v-model:selection="selected" dataKey="id" 
                               paginator :rows="15" responsiveLayout="scroll" class="text-sm">
                        
                        <Column selectionMode="multiple" headerStyle="width: 4rem; padding-left: 2rem" />
                        
                        <Column header="Profil Étudiant">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4 py-2">
                                    <Avatar :image="data.user?.profile_photo_url" :label="data.user?.name?.charAt(0)" shape="circle" size="large" 
                                            class="!bg-red-50 !text-red-600 !font-black border border-red-100" />
                                    <div>
                                        <p class="font-black text-slate-800 tracking-tight">{{ data.user?.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-medium">{{ data.user?.email }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Parcours de Formation">
                            <template #body="{ data }">
                                <div class="max-w-[250px]">
                                    <p class="font-bold text-slate-700 truncate tracking-tight italic">{{ data.course?.title }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ formatPrice(data.course?.price) }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Statut Admission">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" 
                                     class="!text-[9px] !font-black !uppercase !tracking-[0.15em] !px-3 !rounded-lg" />
                            </template>
                        </Column>

                        <Column header="Référence Commande">
                            <template #body="{ data }">
                                <div v-if="data.order" class="space-y-1">
                                    <p class="text-[10px] font-mono font-bold text-slate-600">#{{ data.order.order_number }}</p>
                                    <Tag :value="data.order.status === 'paid' ? 'Payé' : 'Attente'" :severity="data.order.status === 'paid' ? 'success' : 'warn'" class="!text-[8px]" />
                                </div>
                                <span v-else class="text-slate-300 text-xs italic">Direct / Offert</span>
                            </template>
                        </Column>

                        <Column header="Date d'Inscription">
                            <template #body="{ data }">
                                <div class="text-center md:text-left">
                                    <p class="text-xs font-bold text-slate-700">{{ formatDate(data.created_at) }}</p>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">GMT+1</p>
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" frozen>
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Link :href="route('admin.enrollments.show', data.id)">
                                        <Button icon="pi pi-arrow-right" rounded text severity="secondary" v-tooltip="'Détails complets'" />
                                    </Link>
                                    <Button v-if="data.status === 'pending'" icon="pi pi-check-circle" rounded text severity="success" @click="quickApprove(data.id)" v-tooltip="'Approuver immédiatement'" />
                                    <Button v-if="data.status === 'pending'" icon="pi pi-times" rounded text severity="danger" @click="quickReject(data.id)" v-tooltip="'Refuser'" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="py-20 text-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <i class="pi pi-inbox text-slate-300 text-3xl"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-800">Aucun enregistrement</h4>
                                <p class="text-slate-500 text-sm italic">Modifiez vos filtres pour voir d'autres résultats.</p>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODAL BULK V30 -->
        <Dialog v-model:visible="showBulkDialog" modal :header="`Action Groupée : ${bulkAction}`" :style="{ width: '450px' }" class="!rounded-[1rem]">
            <div class="p-6 text-center space-y-6">
                <div class="w-16 h-16 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mx-auto shadow-inner">
                    <i :class="bulkAction === 'approve' ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'" class="text-2xl"></i>
                </div>
                <div>
                    <p class="text-slate-600">Vous allez traiter <strong>{{ selected.length }}</strong> inscriptions simultanément.</p>
                </div>
                <div v-if="bulkAction === 'reject'" class="text-left">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2 block">Motif du refus collectif</label>
                    <Textarea v-model="bulkNotes" rows="3" class="w-full !rounded-xl !bg-slate-50 border-none" placeholder="Ex: Paiement non reçu..." />
                </div>
                <div class="flex gap-3 pt-4">
                    <Button label="Annuler" text @click="showBulkDialog = false" class="flex-1 !font-bold !text-slate-500" />
                    <Button :label="bulkAction === 'reject' ? 'Refuser Tout' : 'Confirmer'" 
                            :severity="bulkAction === 'reject' ? 'danger' : 'success'" 
                            @click="submitBulk" class="flex-1 !rounded-xl !py-3 !font-black !uppercase !tracking-widest" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

:deep(.p-datatable-header) { background: transparent; border: none; }
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: transparent;
    color: #94A3B8;
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 900;
    letter-spacing: 0.15em;
    padding: 1.5rem 1rem;
    border-bottom: 1px solid #F1F5F9;
}
:deep(.p-datatable .p-datatable-tbody > tr) { transition: all 0.2s ease; border-bottom: 1px solid #F8FAFC; }
:deep(.p-datatable .p-datatable-tbody > tr:hover) { background-color: #F8FAFC !important; transform: scale(0.995); }
:deep(.p-inputtext:focus) { border-color: #E11D48; box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.1); }
</style>