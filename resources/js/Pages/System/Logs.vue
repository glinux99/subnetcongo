<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SncDialog from '@/Components/SncDialog.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirmAction } from '@/composables/useConfirmAction';

const { t, locale } = useI18n();
const toast = useToast();
const confirmAction = useConfirmAction();

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1 }) },
    filters: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
});

const loading = ref(false);
const displayLogDialog = ref(false);
const currentLog = ref(null);
const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || null);
const autoRefresh = ref(true);
const refreshInterval = ref(null);

const eventOptions = ref([]);

const getLevelSeverity = (event) => {
    const upper = (event || '').toUpperCase();
    if (upper.includes('CRITICAL') || upper.includes('ERROR') || upper.includes('FAIL') || upper.includes('REJECT')) return 'danger';
    if (upper.includes('WARNING') || upper.includes('DELET') || upper.includes('REVOK') || upper.includes('REFUND')) return 'warning';
    if (upper.includes('INFO')) return 'info';
    return 'secondary';
};

const viewLogDetail = (log) => {
    currentLog.value = log;
    displayLogDialog.value = true;
};

const clearLogs = () => {
    confirmAction({
        message: t('pages.logs.clear_confirm'),
        header: t('pages.logs.clear_header'),
        acceptLabel: t('pages.logs.clear_all'),
        danger: true,
        accept: () => router.delete(route('system.logs.clear'), {
            onSuccess: () => toast.add({ severity: 'success', summary: t('pages.logs.logs_cleared'), life: 3000 }),
        }),
    });
};

const deleteLog = (log) => {
    confirmAction({
        message: t('pages.logs.delete_confirm', { event: log.event }),
        header: t('pages.logs.delete_header'),
        acceptLabel: t('pages.logs.delete'),
        danger: true,
        accept: () => router.delete(route('system.logs.destroy', log.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: t('pages.logs.log_deleted'), life: 3000 }),
        }),
    });
};

const fetchLogs = () => {
    loading.value = true;
    router.get(route('system.logs'), {
        search: search.value || undefined,
        event: eventFilter.value || undefined,
    }, {
        preserveState: true,
        onFinish: () => { loading.value = false; },
    });
};

const startAutoRefresh = () => {
    refreshInterval.value = setInterval(() => {
        if (autoRefresh.value) {
            router.reload({ only: ['logs'] });
        }
    }, 30000);
};

onMounted(() => {
    eventOptions.value = (props.events || []).map((e) =>
        typeof e === 'string'
            ? { label: e, value: e }
            : { label: e.label || e.event || e.name || String(e), value: e.value || e.event || e.name || String(e) }
    );
    startAutoRefresh();
});

onBeforeUnmount(() => {
    if (refreshInterval.value) clearInterval(refreshInterval.value);
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString(locale.value);
};
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.logs.head_title')" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                :title="t('pages.logs.page_title')"
                :subtitle="t('pages.logs.page_subtitle')"
                icon="pi-list"
                accent="slate"
                :badge="t('pages.logs.badge_system')"
                :actions="[
                    { label: t('pages.logs.refresh'), icon: 'pi-refresh', variant: 'secondary', onClick: fetchLogs },
                    { label: t('pages.logs.clear_logs'), icon: 'pi-trash', variant: 'secondary', onClick: clearLogs },
                ]"
            />

            <div class="page-container page-section">
                <Card>
                    <template #content>
                        <!-- Filtres -->
                        <div class="flex flex-col lg:flex-row lg:items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                            <IconField iconPosition="left" class="w-full lg:flex-1 lg:min-w-[260px]">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText
                                    v-model="search"
                                    :placeholder="t('pages.logs.search_placeholder')"
                                    class="w-full h-11"
                                    :aria-label="t('pages.logs.search_aria')"
                                    @keyup.enter="fetchLogs"
                                />
                            </IconField>

                            <Select
                                v-model="eventFilter"
                                :options="eventOptions"
                                optionLabel="label"
                                optionValue="value"
                                :placeholder="t('pages.logs.all_events')"
                                showClear
                                class="w-full lg:w-72 h-11 shrink-0"
                                :aria-label="t('pages.logs.filter_aria')"
                                @change="fetchLogs"
                            />

                            <Button
                                :label="t('pages.logs.search_button')"
                                icon="pi pi-search"
                                class="h-11 w-full lg:w-auto shrink-0"
                                @click="fetchLogs"
                            />

                            <div class="flex items-center gap-2 lg:ml-auto shrink-0">
                                <ToggleSwitch v-model="autoRefresh" inputId="auto-refresh" />
                                <label for="auto-refresh" class="text-sm font-semibold text-slate-600 cursor-pointer">{{ t('pages.logs.auto_refresh') }}</label>
                            </div>
                        </div>

                        <DataTable
                            :value="logs.data"
                            :loading="loading"
                            responsiveLayout="scroll"
                            class="p-datatable-sm"
                            :pt="{ table: { class: 'w-full' } }"
                        >
                            <Column field="created_at" :header="t('pages.logs.col_date')" style="min-width: 11rem">
                                <template #body="{ data }">
                                    <span class="text-xs text-slate-500 whitespace-nowrap">{{ formatDate(data.created_at) }}</span>
                                </template>
                            </Column>
                            <Column field="user.name" :header="t('pages.logs.col_user')" style="min-width: 13rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <Avatar icon="pi pi-user" shape="circle" class="bg-blue-100 text-blue-600" size="small" />
                                        <span class="font-semibold text-slate-700">{{ data.user?.name || t('pages.logs.system') }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="event" :header="t('pages.logs.col_event')" style="min-width: 17rem">
                                <template #body="{ data }">
                                    <div class="flex flex-col gap-1">
                                        <Tag :value="data.event_title || data.event" :severity="getLevelSeverity(data.event_type || data.event)" class="text-[10px] font-black w-fit" />
                                        <span class="text-[10px] font-mono text-slate-400 truncate" :title="data.event_type">{{ data.event_type }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="auditable_type" :header="t('pages.logs.col_type')" style="min-width: 11rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.auditable_type?.split('\\').pop() || '—' }}</span>
                                </template>
                            </Column>
                            <Column field="ip_address" :header="t('pages.logs.col_ip')" style="min-width: 9rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.ip_address || '—' }}</span>
                                </template>
                            </Column>
                            <Column :header="t('pages.logs.col_actions')" style="width: 8rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-1 whitespace-nowrap">
                                        <Button icon="pi pi-eye" text rounded severity="secondary" :aria-label="t('pages.logs.view_detail')" :title="t('pages.logs.view_detail')" @click="viewLogDetail(data)" />
                                        <Button icon="pi pi-trash" text rounded severity="danger" :aria-label="t('pages.logs.delete_log')" :title="t('pages.logs.delete')" @click="deleteLog(data)" />
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-10 text-slate-500">
                                    <i class="pi pi-inbox text-3xl mb-2 text-slate-300"></i>
                                    <p class="font-semibold">{{ t('pages.logs.no_logs') }}</p>
                                </div>
                            </template>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>

        <SncDialog
            v-model:visible="displayLogDialog"
            :title="t('pages.logs.dialog_title')"
            :subtitle="t('pages.logs.dialog_subtitle')"
            icon="pi-list"
            icon-class="from-slate-500 to-slate-700"
            width="700px"
        >
            <div v-if="currentLog" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.logs.col_event') }}</p>
                        <p class="font-bold text-slate-800 mt-1">{{ currentLog.event_title || currentLog.event }}</p>
                        <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ currentLog.event_type }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.logs.col_date') }}</p>
                        <p class="font-bold text-slate-800 mt-1">{{ formatDate(currentLog.created_at) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.logs.col_user') }}</p>
                        <p class="font-bold text-slate-800 mt-1">{{ currentLog.user?.name || t('pages.logs.system') }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.logs.col_ip') }}</p>
                        <p class="font-bold text-slate-800 mt-1 font-mono">{{ currentLog.ip_address || '—' }}</p>
                    </div>
                </div>

                <div v-if="currentLog.old_values || currentLog.new_values" class="space-y-3">
                    <div v-if="currentLog.old_values">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ t('pages.logs.old_values') }}</p>
                        <pre class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-xs overflow-auto max-h-44">{{ JSON.stringify(JSON.parse(currentLog.old_values), null, 2) }}</pre>
                    </div>
                    <div v-if="currentLog.new_values">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">{{ t('pages.logs.new_values') }}</p>
                        <pre class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-xs overflow-auto max-h-44">{{ JSON.stringify(JSON.parse(currentLog.new_values), null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button :label="t('pages.logs.close')" icon="pi pi-times" text severity="secondary" @click="displayLogDialog = false" />
            </template>
        </SncDialog>
    </AppLayout>
</template>
