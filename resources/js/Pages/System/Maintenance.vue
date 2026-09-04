<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import { ref, computed } from 'vue';

const props = defineProps({
    scheduled_tasks: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    system_health: { type: Object, default: () => ({}) },
    is_maintenance_mode: { type: Boolean, default: false },
    laravel_version: { type: String, default: '' },
    php_version: { type: String, default: '' },
    logs: { type: Array, default: () => [] },
    file_size: { type: String, default: '0 MB' },
    info: { type: String, default: '' },
    view: { type: String, default: 'dashboard' },
});

const activeTab = ref(props.view === 'logs' ? 1 : props.view === 'phpinfo' ? 2 : 0);

const diskPercent = computed(() => {
    const total = parseFloat(props.system_health?.disk?.total) || 0;
    const used = parseFloat(props.system_health?.disk?.used) || 0;
    return total > 0 ? Math.round((used / total) * 100) : 0;
});

const severitySeverity = {
    low: 'success',
    medium: 'warning',
    high: 'warn',
    critical: 'danger',
};
</script>

<template>
    <Head title="Maintenance Serveur" />
    <AppLayout>
        <div class="py-10 px-6 lg:px-12">
            <div class="max-w-[1600px] mx-auto space-y-6">

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tighter">Maintenance Serveur</h1>
                        <p class="text-slate-500 mt-1">Laravel {{ laravel_version }} • PHP {{ php_version }}</p>
                    </div>
                    <div class="flex gap-3">
                        <Tag :value="is_maintenance_mode ? 'Mode maintenance ACTIF' : 'En ligne'" :severity="is_maintenance_mode ? 'danger' : 'success'" />
                    </div>
                </div>

                <TabView v-model:activeIndex="activeTab">
                    <TabPanel header="Tableau de bord">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                            <Card>
                                <template #content>
                                    <p class="text-sm text-slate-500 uppercase tracking-wider">Disque</p>
                                    <p class="text-2xl font-black text-slate-900 mt-2">{{ system_health?.disk?.percent || 0 }}%</p>
                                    <p class="text-xs text-slate-500">{{ system_health?.disk?.used || '0' }} / {{ system_health?.disk?.total || '0' }}</p>
                                </template>
                            </Card>
                            <Card>
                                <template #content>
                                    <p class="text-sm text-slate-500 uppercase tracking-wider">Mémoire</p>
                                    <p class="text-2xl font-black text-slate-900 mt-2">{{ system_health?.memory?.percent || 0 }}%</p>
                                    <p class="text-xs text-slate-500">{{ system_health?.memory?.used || '0' }} / {{ system_health?.memory?.total || '0' }}</p>
                                </template>
                            </Card>
                            <Card>
                                <template #content>
                                    <p class="text-sm text-slate-500 uppercase tracking-wider">CPU (load avg)</p>
                                    <p class="text-2xl font-black text-slate-900 mt-2">{{ system_health?.cpu_load?.['1min'] || 0 }}</p>
                                    <p class="text-xs text-slate-500">1min / 5min / 15min</p>
                                </template>
                            </Card>
                            <Card>
                                <template #content>
                                    <p class="text-sm text-slate-500 uppercase tracking-wider">Base de données</p>
                                    <p class="text-2xl font-black text-slate-900 mt-2">{{ system_health?.database?.size || 'N/A' }}</p>
                                    <p class="text-xs text-slate-500">{{ system_health?.database?.connection || '' }}</p>
                                </template>
                            </Card>
                        </div>

                        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card>
                                <template #title>Tâches planifiées</template>
                                <template #content>
                                    <div v-if="scheduled_tasks.length" class="space-y-3">
                                        <div v-for="task in scheduled_tasks" :key="task.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ task.title }}</p>
                                                <p class="text-xs text-slate-500">{{ task.type }} • {{ task.scheduled_at }}</p>
                                            </div>
                                            <Tag :value="task.severity" :severity="severitySeverity[task.severity] || 'info'" />
                                        </div>
                                    </div>
                                    <p v-else class="text-slate-500 text-sm">Aucune tâche planifiée.</p>
                                </template>
                            </Card>
                            <Card>
                                <template #title>Historique récent</template>
                                <template #content>
                                    <div v-if="history.length" class="space-y-3">
                                        <div v-for="item in history" :key="item.id" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                                <p class="text-xs text-slate-500">{{ item.status }} • {{ item.created_at }}</p>
                                            </div>
                                            <Tag :value="item.status" severity="info" />
                                        </div>
                                    </div>
                                    <p v-else class="text-slate-500 text-sm">Aucun historique.</p>
                                </template>
                            </Card>
                        </div>
                    </TabPanel>

                    <TabPanel header="Logs système">
                        <div class="mt-6 bg-slate-900 rounded-xl p-6 overflow-auto max-h-[600px]">
                            <pre class="text-green-400 text-xs font-mono whitespace-pre-wrap">{{ logs.join('\n') || 'Aucun log.' }}</pre>
                        </div>
                    </TabPanel>

                    <TabPanel header="PHP Info">
                        <div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-auto max-h-[700px]" v-html="info"></div>
                    </TabPanel>
                </TabView>
            </div>
        </div>
    </AppLayout>
</template>
