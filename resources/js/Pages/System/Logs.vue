<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

const toast = useToast();

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1 }) },
    filters: { type: Object, default: () => ({}) },
    events: { type: Array, default: () => [] },
});

const loading = ref(false);
const displayLogDialog = ref(false);
const currentLog = ref(null);
const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || '');
const autoRefresh = ref(true);
const refreshInterval = ref(null);

const logLevels = ref([
    { label: 'CRITICAL', severity: 'danger' },
    { label: 'ERROR', severity: 'danger' },
    { label: 'WARNING', severity: 'warning' },
    { label: 'INFO', severity: 'info' },
    { label: 'DEBUG', severity: 'secondary' },
]);

const getLevelSeverity = (event) => {
    const upper = (event || '').toUpperCase();
    if (upper.includes('CRITICAL') || upper.includes('ERROR')) return 'danger';
    if (upper.includes('WARNING')) return 'warning';
    if (upper.includes('INFO')) return 'info';
    return 'secondary';
};

const viewLogDetail = (log) => {
    currentLog.value = log;
    displayLogDialog.value = true;
};

const clearLogs = () => {
    router.delete(route('system.logs.clear'), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Logs effacés', life: 3000 }),
    });
};

const deleteLog = (log) => {
    router.delete(route('system.logs.destroy', log.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Log supprimé', life: 3000 }),
    });
};

const fetchLogs = () => {
    loading.value = true;
    router.get(route('system.logs'), { search: search.value, event: eventFilter.value }, {
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
    startAutoRefresh();
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('fr-FR');
};

const truncate = (str, len = 80) => {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
};
</script>

<template>
    <Head title="Logs d'audit" />
    <AppLayout>
        <div class="py-10 px-6 lg:px-12">
            <div class="max-w-[1600px] mx-auto space-y-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tighter">Logs d'audit</h1>
                        <p class="text-slate-500">Journal des actions et événements système.</p>
                    </div>
                    <div class="flex gap-3">
                        <Button label="Actualiser" icon="pi pi-refresh" class="p-button-outlined" @click="fetchLogs" />
                        <Button label="Effacer les logs" icon="pi pi-trash" class="p-button-outlined p-button-danger" @click="clearLogs" />
                    </div>
                </div>

                <Card>
                    <template #content>
                        <div class="flex flex-col md:flex-row gap-4 mb-6">
                            <InputText v-model="search" placeholder="Rechercher un événement, IP, utilisateur..." class="w-full md:w-96" @keyup.enter="fetchLogs" />
                            <Dropdown v-model="eventFilter" :options="events" optionLabel="event" optionValue="event" placeholder="Filtrer par événement" class="w-full md:w-64" showClear @change="fetchLogs" />
                        </div>

                        <DataTable :value="logs.data" :paginator="true" :rows="20" :loading="loading" responsiveLayout="scroll" class="p-datatable-sm">
                            <Column field="created_at" header="DATE" style="min-width: 12rem">
                                <template #body="{ data }">
                                    <span class="text-xs text-slate-500">{{ formatDate(data.created_at) }}</span>
                                </template>
                            </Column>
                            <Column field="user.name" header="UTILISATEUR" style="min-width: 14rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <Avatar icon="pi pi-user" shape="circle" class="bg-indigo-100 text-indigo-600" size="small" />
                                        <span class="font-semibold text-slate-700">{{ data.user?.name || 'Système' }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="event" header="ÉVÉNEMENT" style="min-width: 18rem">
                                <template #body="{ data }">
                                    <Tag :value="data.event" :severity="getLevelSeverity(data.event)" class="text-[9px] font-black" />
                                </template>
                            </Column>
                            <Column field="auditable_type" header="TYPE" style="min-width: 12rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.auditable_type?.split('\\').pop() || '-' }}</span>
                                </template>
                            </Column>
                            <Column field="ip_address" header="ADRESSE IP" style="min-width: 10rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.ip_address || '-' }}</span>
                                </template>
                            </Column>
                            <Column header="ACTIONS" style="width: 10rem">
                                <template #body="{ data }">
                                    <Button icon="pi pi-eye" class="p-button-text p-button-sm" @click="viewLogDetail(data)" />
                                    <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="deleteLog(data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>

        <Dialog v-model:visible="displayLogDialog" :modal="true" :style="{ width: '700px' }" header="Détail du log" class="p-fluid">
            <div v-if="currentLog" class="space-y-4 pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Événement</p>
                        <p class="font-semibold text-slate-900">{{ currentLog.event }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Date</p>
                        <p class="font-semibold text-slate-900">{{ formatDate(currentLog.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">Utilisateur</p>
                        <p class="font-semibold text-slate-900">{{ currentLog.user?.name || 'Système' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase">IP</p>
                        <p class="font-semibold text-slate-900">{{ currentLog.ip_address || '-' }}</p>
                    </div>
                </div>
                <div v-if="currentLog.old_values || currentLog.new_values">
                    <p class="text-xs font-black text-slate-400 uppercase mb-2">Anciennes valeurs</p>
                    <pre class="bg-slate-50 p-4 rounded-xl text-xs overflow-auto max-h-40">{{ currentLog.old_values ? JSON.stringify(JSON.parse(currentLog.old_values), null, 2) : 'Aucune' }}</pre>
                    <p class="text-xs font-black text-slate-400 uppercase mb-2 mt-4">Nouvelles valeurs</p>
                    <pre class="bg-slate-50 p-4 rounded-xl text-xs overflow-auto max-h-40">{{ currentLog.new_values ? JSON.stringify(JSON.parse(currentLog.new_values), null, 2) : 'Aucune' }}</pre>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
