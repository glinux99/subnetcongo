<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SncDialog from '@/Components/SncDialog.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

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
        message: 'Tous les logs d\'audit seront définitivement effacés. Continuer ?',
        header: 'Effacer les logs',
        acceptLabel: 'Tout effacer',
        danger: true,
        accept: () => router.delete(route('system.logs.clear'), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Logs effacés', life: 3000 }),
        }),
    });
};

const deleteLog = (log) => {
    confirmAction({
        message: `Supprimer le log « ${log.event} » ?`,
        header: 'Supprimer le log',
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('system.logs.destroy', log.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Log supprimé', life: 3000 }),
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
    return new Date(date).toLocaleString('fr-FR');
};
</script>

<template>
    <AppLayout>
        <Head title="Logs d'audit — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Logs d'audit"
                subtitle="Journal des actions et événements système — actualisation automatique toutes les 30 s"
                icon="pi-list"
                accent="slate"
                badge="Système"
                :actions="[
                    { label: 'Actualiser', icon: 'pi-refresh', variant: 'secondary', onClick: fetchLogs },
                    { label: 'Effacer les logs', icon: 'pi-trash', variant: 'secondary', onClick: clearLogs },
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
                                    placeholder="Rechercher un événement, IP, utilisateur…"
                                    class="w-full h-11"
                                    aria-label="Rechercher dans les logs"
                                    @keyup.enter="fetchLogs"
                                />
                            </IconField>

                            <Select
                                v-model="eventFilter"
                                :options="eventOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Tous les événements"
                                showClear
                                class="w-full lg:w-72 h-11 shrink-0"
                                aria-label="Filtrer par événement"
                                @change="fetchLogs"
                            />

                            <Button
                                label="Rechercher"
                                icon="pi pi-search"
                                class="h-11 w-full lg:w-auto shrink-0"
                                @click="fetchLogs"
                            />

                            <div class="flex items-center gap-2 lg:ml-auto shrink-0">
                                <ToggleSwitch v-model="autoRefresh" inputId="auto-refresh" />
                                <label for="auto-refresh" class="text-sm font-semibold text-slate-600 cursor-pointer">Actualisation auto</label>
                            </div>
                        </div>

                        <DataTable
                            :value="logs.data"
                            :loading="loading"
                            responsiveLayout="scroll"
                            class="p-datatable-sm"
                            :pt="{ table: { class: 'w-full' } }"
                        >
                            <Column field="created_at" header="Date" style="min-width: 11rem">
                                <template #body="{ data }">
                                    <span class="text-xs text-slate-500 whitespace-nowrap">{{ formatDate(data.created_at) }}</span>
                                </template>
                            </Column>
                            <Column field="user.name" header="Utilisateur" style="min-width: 13rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <Avatar icon="pi pi-user" shape="circle" class="bg-blue-100 text-blue-600" size="small" />
                                        <span class="font-semibold text-slate-700">{{ data.user?.name || 'Système' }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="event" header="Événement" style="min-width: 17rem">
                                <template #body="{ data }">
                                    <div class="flex flex-col gap-1">
                                        <Tag :value="data.event_title || data.event" :severity="getLevelSeverity(data.event_type || data.event)" class="text-[10px] font-black w-fit" />
                                        <span class="text-[10px] font-mono text-slate-400 truncate" :title="data.event_type">{{ data.event_type }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="auditable_type" header="Type" style="min-width: 11rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.auditable_type?.split('\\').pop() || '—' }}</span>
                                </template>
                            </Column>
                            <Column field="ip_address" header="Adresse IP" style="min-width: 9rem">
                                <template #body="{ data }">
                                    <span class="text-xs font-mono text-slate-500">{{ data.ip_address || '—' }}</span>
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 8rem">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-1 whitespace-nowrap">
                                        <Button icon="pi pi-eye" text rounded severity="secondary" aria-label="Voir le détail" title="Voir le détail" @click="viewLogDetail(data)" />
                                        <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Supprimer le log" title="Supprimer" @click="deleteLog(data)" />
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-10 text-slate-500">
                                    <i class="pi pi-inbox text-3xl mb-2 text-slate-300"></i>
                                    <p class="font-semibold">Aucun log trouvé</p>
                                </div>
                            </template>
                        </DataTable>
                    </template>
                </Card>
            </div>
        </div>

        <SncDialog
            v-model:visible="displayLogDialog"
            title="Détail du log"
            subtitle="Informations complètes sur cet événement"
            icon="pi-list"
            icon-class="from-slate-500 to-slate-700"
            width="700px"
        >
            <div v-if="currentLog" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Événement</p>
                        <p class="font-bold text-slate-800 mt-1">{{ currentLog.event_title || currentLog.event }}</p>
                        <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ currentLog.event_type }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</p>
                        <p class="font-bold text-slate-800 mt-1">{{ formatDate(currentLog.created_at) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</p>
                        <p class="font-bold text-slate-800 mt-1">{{ currentLog.user?.name || 'Système' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Adresse IP</p>
                        <p class="font-bold text-slate-800 mt-1 font-mono">{{ currentLog.ip_address || '—' }}</p>
                    </div>
                </div>

                <div v-if="currentLog.old_values || currentLog.new_values" class="space-y-3">
                    <div v-if="currentLog.old_values">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Anciennes valeurs</p>
                        <pre class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-xs overflow-auto max-h-44">{{ JSON.stringify(JSON.parse(currentLog.old_values), null, 2) }}</pre>
                    </div>
                    <div v-if="currentLog.new_values">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nouvelles valeurs</p>
                        <pre class="bg-slate-50 border border-slate-200 p-4 rounded-xl text-xs overflow-auto max-h-44">{{ JSON.stringify(JSON.parse(currentLog.new_values), null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Fermer" icon="pi pi-times" text severity="secondary" @click="displayLogDialog = false" />
            </template>
        </SncDialog>
    </AppLayout>
</template>
