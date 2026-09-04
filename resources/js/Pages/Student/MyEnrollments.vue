<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    enrollments: Object,
    stats: Object,
    filters: Object,
    adminContact: Object,
});

const toast = useToast();
const statusFilter = ref(props.filters?.status ?? 'all');

const filterOptions = [
    { label: 'Toutes', value: 'all' },
    { label: 'Validées', value: 'active' },
    { label: 'En attente', value: 'pending' },
    { label: 'Terminées', value: 'completed' },
    { label: 'Suspendues', value: 'suspended' },
    { label: 'Refusées', value: 'rejected' },
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
const formatPrice = (p) => {
    if (!p) return 'Gratuit';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
};

const applyFilter = () => {
    router.get(route('student.my-enrollments'), { status: statusFilter.value }, { preserveState: true });
};

const cancelEnrollment = (id) => {
    if (!confirm('Annuler cette demande d\'inscription ?')) return;
    router.post(route('student.enrollments.cancel', id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Demande annulée', life: 2500 }),
    });
};

const getImageUrl = (path) => !path ? 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=400' : (path.startsWith('http') ? path : `/storage/${path}`);
</script>

<template>
    <AppLayout>
        <Head title="Mes inscriptions" />
        <Toast />
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center justify-between gap-5 flex-wrap">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-id-card text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Mes inscriptions</h1>
                            <p class="text-slate-400 font-medium text-sm">Suivez vos demandes, validations et révocations.</p>
                        </div>
                    </div>
                    <Link :href="route('marketplace.index')">
                        <Button label="Catalogue" icon="pi pi-shopping-bag" outlined class="!text-white !border-white/20 hover:!bg-white/10 !rounded-xl" />
                    </Link>
                </div>
            </div>

            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-6 relative z-20">
                <!-- Stats KPI -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                    <div v-for="kpi in [
                        { label: 'Total', value: stats?.total ?? 0, icon: 'pi-list', color: 'from-indigo-500 to-blue-500' },
                        { label: 'Validées', value: stats?.active ?? 0, icon: 'pi-check-circle', color: 'from-emerald-500 to-teal-500' },
                        { label: 'En attente', value: stats?.pending ?? 0, icon: 'pi-clock', color: 'from-amber-500 to-orange-500' },
                        { label: 'Terminées', value: stats?.completed ?? 0, icon: 'pi-trophy', color: 'from-indigo-500 to-purple-500' },
                        { label: 'Suspendues', value: stats?.suspended ?? 0, icon: 'pi-ban', color: 'from-rose-500 to-pink-500' },
                        { label: 'Refusées', value: stats?.rejected ?? 0, icon: 'pi-times-circle', color: 'from-rose-600 to-red-600' },
                    ]" :key="kpi.label" class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm">
                        <div :class="['w-10 h-10 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white shadow-lg mb-2', kpi.color]">
                            <i :class="['pi', kpi.icon, 'text-base']"></i>
                        </div>
                        <div class="text-xl font-black text-slate-800 tracking-tight font-mono">{{ kpi.value }}</div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ kpi.label }}</div>
                    </div>
                </div>

                <!-- Bandeau "en attente de validation" -->
                <div v-if="stats?.pending > 0" class="bg-amber-50 border-2 border-amber-200 rounded-3xl p-5 mb-4 flex items-center gap-4">
                    <div class="w-12 h-12 bg-amber-500 text-white rounded-2xl flex items-center justify-center shrink-0">
                        <i class="pi pi-info-circle text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-amber-800">Vous avez {{ stats.pending }} demande(s) d'inscription en attente de validation.</p>
                        <p class="text-xs text-amber-700 mt-0.5">L'administrateur vérifie votre paiement puis valide l'accès. Vous serez notifié(e).</p>
                    </div>
                    <a :href="`mailto:${adminContact?.email}`" class="hidden md:inline-flex">
                        <Button label="Contacter l'admin" icon="pi pi-envelope" size="small" class="!bg-amber-500 hover:!bg-amber-600 !border-none !rounded-xl !text-white !text-[10px] !font-black !uppercase !tracking-widest !py-2 !px-3" />
                    </a>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-3 mb-4 flex-wrap">
                        <Select v-model="statusFilter" :options="filterOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" outlined @click="applyFilter" class="!rounded-xl" />
                        <span class="ml-auto text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ enrollments?.total ?? 0 }} inscription(s)</span>
                    </div>

                    <DataTable :value="enrollments?.data ?? []" stripedRows paginator :rows="10" :totalRecords="enrollments?.total ?? 0" responsiveLayout="scroll" class="text-sm">
                        <Column header="Cours">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl overflow-hidden shrink-0 bg-slate-100">
                                        <img :src="getImageUrl(data.course?.thumbnail_path)" :alt="data.course?.title" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-slate-800 truncate">{{ data.course?.title ?? '—' }}</p>
                                        <p class="text-[10px] text-slate-400 truncate">{{ data.course?.instructor?.name ?? '—' }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="status" header="Statut" :exportable="false">
                            <template #body="{ data }">
                                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="!text-[10px] !font-black !uppercase !tracking-widest" />
                            </template>
                        </Column>
                        <Column header="Progression">
                            <template #body="{ data }">
                                <div class="min-w-[120px]">
                                    <div class="flex justify-between text-[10px] font-mono mb-1">
                                        <span class="text-slate-400">Avancement</span>
                                        <span class="font-black text-slate-700">{{ Math.round(data.progress_percent ?? 0) }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500" :style="`width: ${data.progress_percent ?? 0}%`"></div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Demande">
                            <template #body="{ data }">
                                <p class="text-xs text-slate-500 font-mono">{{ formatDate(data.created_at) }}</p>
                            </template>
                        </Column>
                        <Column header="Notes admin" :exportable="false">
                            <template #body="{ data }">
                                <p v-if="data.revoked_reason" class="text-xs text-rose-600 italic truncate" v-tooltip.bottom="data.revoked_reason">🚫 {{ data.revoked_reason }}</p>
                                <p v-else-if="data.admin_notes" class="text-xs text-slate-500 italic truncate" v-tooltip.bottom="data.admin_notes">💬 {{ data.admin_notes }}</p>
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </template>
                        </Column>
                        <Column header="Action" :exportable="false">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1">
                                    <Link v-if="data.status === 'active' || data.status === 'completed'" :href="route('student.my-courses')">
                                        <Button label="Continuer" icon="pi pi-play" size="small" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest !py-2 !px-3" />
                                    </Link>
                                    <Button v-if="data.status === 'pending'" label="Annuler" icon="pi pi-times" size="small" severity="danger" outlined @click="cancelEnrollment(data.id)" class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-id-card text-3xl mb-2 block"></i>
                                Aucune inscription.
                            </div>
                        </template>
                    </DataTable>
                </div>

                <!-- Footer : contact admin -->
                <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm mt-6 flex flex-wrap items-center gap-4">
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-2xl flex items-center justify-center">
                        <i class="pi pi-headphones text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Besoin d'aide avec une inscription ?</h3>
                        <p class="text-xs text-slate-500">Contactez l'administration pour toute question sur vos demandes d'inscription ou vos paiements.</p>
                    </div>
                    <a :href="`mailto:${adminContact?.email}`" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-800 transition-colors">
                        <i class="pi pi-envelope"></i> {{ adminContact?.email }}
                    </a>
                    <a v-if="adminContact?.phone" :href="`tel:${adminContact.phone}`" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-colors">
                        <i class="pi pi-phone"></i> {{ adminContact.phone }}
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
