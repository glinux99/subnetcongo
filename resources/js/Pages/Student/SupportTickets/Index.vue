<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    tickets:    { type: Object, default: () => ({ data: [], links: [] }) },
    categories: { type: Array,  default: () => [] },
    stats:      { type: Object, default: () => ({}) },
    filters:    { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const category = ref(props.filters.category_id || '');

const apply = () => router.get(route('student.support.index'), {
    search: search.value || undefined,
    status: status.value || undefined,
    category_id: category.value || undefined,
}, { preserveState: true, replace: true });

const statusLabel = (s) => ({
    open: t('pages.support_tickets.stOpenLabel'), new: t('pages.support_tickets.stNew'), pending: t('pages.support_tickets.stPendingLabel'), in_progress: t('pages.support_tickets.stInProgressLabel'),
    resolved: t('pages.support_tickets.stResolvedLabel'), closed: t('pages.support_tickets.stClosedLabel'),
}[s] || s);

const statusSeverity = (s) => ({
    open: 'info', new: 'info', pending: 'warn', in_progress: 'info',
    resolved: 'success', closed: 'secondary',
}[s] || 'secondary');

const priorityLabel = (p) => ({ low: t('pages.support_tickets.prioLow'), medium: t('pages.support_tickets.prioMedium'), high: t('pages.support_tickets.prioHigh'), urgent: t('pages.support_tickets.prioUrgent') }[p] || p);

// Adapté pour les severities natives de PrimeVue (danger, warning, info, success)
const prioritySeverity = (p) => ({ urgent: 'danger', high: 'warn', medium: 'info', low: 'success' }[p] || 'secondary');

const statusOptions = computed(() => [
    { label: t('pages.support_tickets.optAllStatuses'), value: '' },
    { label: t('pages.support_tickets.optOpen'), value: 'open' },
    { label: t('pages.support_tickets.optPending'), value: 'pending' },
    { label: t('pages.support_tickets.optInProgress'), value: 'in_progress' },
    { label: t('pages.support_tickets.optResolved'), value: 'resolved' },
    { label: t('pages.support_tickets.optClosed'), value: 'closed' },
]);

const categoryOptions = computed(() => [{ label: t('pages.support_tickets.optAllCategories'), value: '' }, ...(props.categories?.map(c => ({ label: c.name, value: c.id })) || [])]);

const formatDate = (iso) => {
    if (!iso) return '';
    try { return new Date(iso).toLocaleString(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
    catch (_) { return iso; }
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.support_tickets.headTitle')" />
        
        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="`${t('pages.support_tickets.titlePart1')} ${t('pages.support_tickets.titlePart2')}`"
                :subtitle="t('pages.support_tickets.pageSubtitle')"
                badge="Help Desk"
                icon="pi-ticket"
                accent="blue"
                :actions="[
                    { label: t('pages.support_tickets.refresh'), icon: 'pi-sync', variant: 'secondary', onClick: apply },
                    { label: t('pages.support_tickets.newTicket'), icon: 'pi-plus', variant: 'primary', href: route('student.support.create') },
                ]"
            />

            <div class="page-container page-section">

                <!-- CARTES KPI -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <PageKpi :label="t('pages.support_tickets.kpiTotal')" :value="stats?.total ?? 0" icon="pi-list" color="blue" />
                    <PageKpi :label="t('pages.support_tickets.kpiOpen')" :value="stats?.open ?? 0" icon="pi-envelope-open" color="sky" />
                    <PageKpi :label="t('pages.support_tickets.kpiPending')" :value="stats?.pending ?? 0" icon="pi-clock" color="amber" />
                    <PageKpi :label="t('pages.support_tickets.kpiResolved')" :value="stats?.resolved ?? 0" icon="pi-check-circle" color="emerald" />
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU TABLEAU (Filtres et Liste)                                             -->
                <!-- ============================================================================== -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    
                    <!-- Zone des filtres -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <IconField iconPosition="left" class="flex-1 min-w-[250px]">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="search" @keyup.enter="apply" :placeholder="t('pages.support_tickets.searchPlaceholder')" class="w-full" />
                        </IconField>
                        
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl border-slate-200 min-w-[150px]" />
                        
                        <Select v-model="category" :options="categoryOptions" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl border-slate-200 min-w-[180px]" />
                        
                        <Button :label="t('pages.support_tickets.filterBtn')" icon="pi pi-filter" @click="apply" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold" />
                    </div>

                    <!-- Tableau natif re-stylisé façon "PrimeVue B2B" -->
                    <div v-if="tickets.data?.length" class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thTicket') }}</th>
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thSubject') }}</th>
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thCategory') }}</th>
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thPriority') }}</th>
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thStatus') }}</th>
                                    <th class="py-4 px-4 whitespace-nowrap">{{ t('pages.support_tickets.thUpdated') }}</th>
                                    <th class="py-4 px-4 text-right whitespace-nowrap">{{ t('pages.support_tickets.thAction') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in tickets.data" :key="t.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-4 font-mono text-[11px] font-bold text-slate-500 whitespace-nowrap">#{{ t.ticket_number }}</td>
                                    
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-800 line-clamp-1 min-w-[200px]">{{ t.subject }}</div>
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag v-if="t.category" :value="t.category.name" :style="{ backgroundColor: t.category.color }" class="!text-white !text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                        <span v-else class="text-slate-400 text-xs font-medium">—</span>
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag :value="priorityLabel(t.priority)" :severity="prioritySeverity(t.priority)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag :value="statusLabel(t.status)" :severity="statusSeverity(t.status)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                    </td>
                                    
                                    <td class="py-4 px-4 text-xs font-medium text-slate-500 whitespace-nowrap">
                                        {{ formatDate(t.updated_at || t.created_at) }}
                                    </td>
                                    
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <Link :href="route('student.support.show', t.ticket_number)">
                                            <Button :label="t('pages.support_tickets.openBtn')" icon="pi pi-arrow-right" size="small" class="!rounded-xl !text-[10px] !font-bold !uppercase !tracking-widest !py-2 !bg-blue-50 !text-blue-600 !border-none hover:!bg-blue-100 transition-colors" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-20 text-slate-400 flex flex-col items-center border border-slate-100 rounded-xl bg-slate-50/50">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center border border-slate-200 mb-4 shadow-sm">
                            <i class="pi pi-inbox text-2xl text-slate-300"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">{{ t('pages.support_tickets.emptyTitle') }}</h3>
                        <p class="text-sm text-slate-500 mb-6">{{ t('pages.support_tickets.emptyDesc') }}</p>
                        <Link :href="route('student.support.create')">
                            <Button :label="t('pages.support_tickets.emptyAction')" icon="pi pi-plus" class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 shadow-md shadow-red-500/20" />
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tickets.links?.length > 3" class="flex justify-center gap-1 mt-8 flex-wrap">
                        <Link v-for="(l, i) in tickets.links" :key="i" :href="l.url || '#'" v-html="l.label"
                            :class="[
                                'px-3 py-2 rounded-xl text-xs font-black transition-colors', 
                                l.active ? '!bg-blue-600 !text-white shadow-md' : '!bg-slate-100 !text-slate-600 hover:!bg-slate-200', 
                                !l.url && 'opacity-50 cursor-not-allowed'
                            ]" 
                        />
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Overrides UI Forms PrimeVue */
:deep(.p-inputtext), :deep(.p-select) {
    border-radius: 0.75rem;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-select:not(.p-disabled).p-focus) {
    border-color: #FF3B3B;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
}
</style>