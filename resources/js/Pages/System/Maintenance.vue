<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import { ref, computed } from 'vue';

const props = defineProps({
    scheduled_tasks: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    system_health: { type: Object, default: () => ({}) },
    is_maintenance_mode: { type: Boolean, default: false },
    laravel_version: { type: String, default: '10.x' },
    php_version: { type: String, default: '8.x' },
    logs: { type: Array, default: () => [] },
    file_size: { type: String, default: '0 MB' },
    info: { type: String, default: '' },
    view: { type: String, default: 'dashboard' },
});

const activeTab = ref(props.view === 'logs' ? 1 : props.view === 'phpinfo' ? 2 : 0);

// Fonction pour définir la couleur de la barre de progression selon le %
const getProgressColor = (percent) => {
    if (percent < 70) return 'bg-emerald-500';
    if (percent < 90) return 'bg-amber-500';
    return 'bg-rose-500';
};

const severityMap = {
    low: 'success',
    medium: 'warning',
    high: 'danger',
    critical: 'danger',
};
</script>

<template>
    <Head title="Maintenance Serveur" />
    <AppLayout>
        <div class="py-8 px-4 sm:px-8 bg-slate-50 min-h-screen">
            <div class="max-w-[1600px] mx-auto space-y-8">

                <!-- En-tête -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Maintenance Serveur</h1>
                            <Tag :value="is_maintenance_mode ? 'Maintenance Active' : 'Système En Ligne'" 
                                 :severity="is_maintenance_mode ? 'danger' : 'success'" 
                                 class="font-bold uppercase tracking-wider text-[10px]" />
                        </div>
                        <div class="flex items-center gap-4 text-sm text-slate-500 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i class="pi pi-code text-red-500"></i> Laravel {{ laravel_version }}
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="flex items-center gap-1.5">
                                <i class="pi pi-server text-indigo-500"></i> PHP {{ php_version }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 font-bold text-sm transition-colors shadow-sm flex items-center gap-2">
                            <i class="pi pi-refresh"></i> Rafraîchir
                        </button>
                    </div>
                </div>

                <!-- Contenu Principal -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-2">
                    <TabView v-model:activeIndex="activeTab" class="custom-tabview">
                        
                        <!-- TAB 1 : DASHBOARD -->
                        <TabPanel header="Tableau de bord">
                            
                            <!-- 4 Cartes de métriques (Widgets) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-4 p-4">
                                
                                <!-- Disque -->
                                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Espace Disque</p>
                                            <p class="text-3xl font-black text-slate-800 mt-1">{{ system_health?.disk?.percent || 0 }}%</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                                            <i class="pi pi-database text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 mb-2">
                                        <div class="h-1.5 rounded-full" :class="getProgressColor(system_health?.disk?.percent || 0)" :style="`width: ${system_health?.disk?.percent || 0}%`"></div>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-500">{{ system_health?.disk?.used || '0 GB' }} utilisés sur {{ system_health?.disk?.total || '0 GB' }}</p>
                                </div>

                                <!-- RAM -->
                                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Mémoire RAM</p>
                                            <p class="text-3xl font-black text-slate-800 mt-1">{{ system_health?.memory?.percent || 0 }}%</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                                            <i class="pi pi-microchip text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 mb-2">
                                        <div class="h-1.5 rounded-full" :class="getProgressColor(system_health?.memory?.percent || 0)" :style="`width: ${system_health?.memory?.percent || 0}%`"></div>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-500">{{ system_health?.memory?.used || '0 GB' }} utilisés sur {{ system_health?.memory?.total || '0 GB' }}</p>
                                </div>

                                <!-- CPU -->
                                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Charge CPU</p>
                                            <p class="text-3xl font-black text-slate-800 mt-1">{{ system_health?.cpu_load?.['1min'] || '0.00' }}</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                                            <i class="pi pi-server text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-500 mt-5">
                                        <span class="bg-slate-100 px-2 py-1 rounded">1m: {{ system_health?.cpu_load?.['1min'] || 0 }}</span>
                                        <span class="bg-slate-100 px-2 py-1 rounded">5m: {{ system_health?.cpu_load?.['5min'] || 0 }}</span>
                                    </div>
                                </div>

                                <!-- Database -->
                                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Base de données</p>
                                            <p class="text-3xl font-black text-slate-800 mt-1">{{ system_health?.database?.size || 'N/A' }}</p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600 group-hover:scale-110 transition-transform">
                                            <i class="pi pi-table text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <p class="text-xs font-semibold text-slate-600">Connecté ({{ system_health?.database?.connection || 'MySQL' }})</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Listes (Tâches & Historique) -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4 p-4">
                                
                                <!-- Tâches planifiées -->
                                <div class="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
                                    <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                            <i class="pi pi-calendar text-slate-400"></i> Tâches planifiées (Cron)
                                        </h3>
                                        <Tag :value="scheduled_tasks.length" severity="secondary" rounded />
                                    </div>
                                    <div class="p-4 flex-1">
                                        <div v-if="scheduled_tasks.length" class="space-y-3">
                                            <div v-for="task in scheduled_tasks" :key="task.id" class="flex items-center justify-between p-4 bg-white border border-slate-100 shadow-sm rounded-lg hover:border-blue-200 transition-colors">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                                        <i class="pi pi-cog text-slate-500 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-sm text-slate-900">{{ task.title }}</p>
                                                        <p class="text-xs text-slate-500 font-medium mt-0.5">{{ task.type }} • Exécution : {{ task.scheduled_at }}</p>
                                                    </div>
                                                </div>
                                                <Tag :value="task.severity" :severity="severityMap[task.severity] || 'info'" class="text-[10px] uppercase font-bold" />
                                            </div>
                                        </div>
                                        <div v-else class="h-full flex flex-col items-center justify-center py-10 text-slate-400">
                                            <i class="pi pi-inbox text-4xl mb-3 opacity-50"></i>
                                            <p class="text-sm font-medium">Aucune tâche planifiée</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Historique récent -->
                                <div class="border border-slate-200 rounded-xl overflow-hidden flex flex-col">
                                    <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                            <i class="pi pi-history text-slate-400"></i> Historique Système
                                        </h3>
                                    </div>
                                    <div class="p-4 flex-1">
                                        <div v-if="history.length" class="space-y-3">
                                            <div v-for="item in history" :key="item.id" class="flex items-center justify-between p-4 bg-white border border-slate-100 shadow-sm rounded-lg hover:border-blue-200 transition-colors">
                                                <div>
                                                    <p class="font-bold text-sm text-slate-900">{{ item.title }}</p>
                                                    <p class="text-xs text-slate-500 font-medium mt-0.5">Le {{ item.created_at }}</p>
                                                </div>
                                                <Tag :value="item.status" severity="info" class="text-[10px] uppercase font-bold" />
                                            </div>
                                        </div>
                                        <div v-else class="h-full flex flex-col items-center justify-center py-10 text-slate-400">
                                            <i class="pi pi-clock text-4xl mb-3 opacity-50"></i>
                                            <p class="text-sm font-medium">Aucun historique récent</p>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </TabPanel>

                        <!-- TAB 2 : LOGS -->
                        <TabPanel header="Logs Système">
                            <div class="p-4">
                                <!-- Faux Terminal Mac/Linux -->
                                <div class="bg-[#0f172a] rounded-xl shadow-2xl overflow-hidden border border-slate-700">
                                    <!-- Header Terminal -->
                                    <div class="bg-[#1e293b] px-4 py-3 flex items-center justify-between border-b border-slate-700">
                                        <div class="flex gap-2">
                                            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                        </div>
                                        <span class="text-xs font-mono text-slate-400">laravel.log ({{ file_size }})</span>
                                        <button class="text-slate-400 hover:text-white transition-colors">
                                            <i class="pi pi-copy text-sm"></i>
                                        </button>
                                    </div>
                                    <!-- Contenu Terminal -->
                                    <div class="p-6 overflow-y-auto max-h-[600px] custom-scrollbar">
                                        <pre class="text-emerald-400 text-xs font-mono whitespace-pre-wrap leading-relaxed">{{ logs.length ? logs.join('\n') : 'Aucun log système trouvé. Le système fonctionne parfaitement.' }}</pre>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 3 : PHP INFO -->
                        <TabPanel header="Informations PHP">
                            <div class="p-4">
                                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-x-auto max-h-[700px] custom-scrollbar phpinfo-wrapper" v-html="info"></div>
                            </div>
                        </TabPanel>
                        
                    </TabView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Style pour la scrollbar du terminal et du tableau PHP */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* OVERRIDE MAGIQUE POUR RENDRE LE PHPINFO() JOLI */
.phpinfo-wrapper {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.phpinfo-wrapper pre { margin: 0; font-family: monospace; }
.phpinfo-wrapper a:link { color: #2563eb; text-decoration: none; }
.phpinfo-wrapper a:hover { text-decoration: underline; }
.phpinfo-wrapper table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); }
.phpinfo-wrapper .center { text-align: left; }
.phpinfo-wrapper .center table { margin-left: 0; margin-right: 0; text-align: left; }
.phpinfo-wrapper .center th { text-align: left !important; }
.phpinfo-wrapper td, .phpinfo-wrapper th { border: 1px solid #e2e8f0; font-size: 0.875rem; vertical-align: top; padding: 0.75rem 1rem; color: #334155;}
.phpinfo-wrapper h1 { font-size: 1.875rem; font-weight: 900; margin-bottom: 1.5rem; color: #0f172a; }
.phpinfo-wrapper h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1rem; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;}
.phpinfo-wrapper .p { text-align: left; }
.phpinfo-wrapper .e { background-color: #f8fafc; font-weight: 600; width: 30%; color: #0f172a;}
.phpinfo-wrapper .h { background-color: #1e293b; font-weight: bold; color: #ffffff; }
.phpinfo-wrapper .v { word-break: break-all; max-width: 0; }
.phpinfo-wrapper .vr { background-color: #f1f5f9; text-align: right; color: #64748b;}
.phpinfo-wrapper img { margin-bottom: 1rem; border-radius: 4px; }
.phpinfo-wrapper hr { display: none; }
</style>