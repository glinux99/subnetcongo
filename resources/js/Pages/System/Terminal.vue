<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — TERMINAL PERSISTANT v2.0
 * ======================================================================================
 * Onglets multiples, processus persistants côté serveur, sortie en temps réel.
 * Les sessions terminées restent visibles ; chacune ouvre son propre onglet.
 * ======================================================================================
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

// state
const newCommand = ref('');
const confirmDialog = ref(false);
const pendingCommand = ref('');
const newTitle = ref('');

// sessions shown in tabs (id + UI state)
const tabs = reactive([]); // [{ id, title, command, status, exit_code, offset, outputLines, started_at, ended_at }]
const activeTabId = ref(null);

// intervals
let pollTimer = null;

const dangerousPrefixes = [
    'down', 'up',
    'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:seed', 'db:wipe',
    'key:generate', 'storage:link', 'queue:flush', 'queue:failed',
    'optimize:clear', 'octane:clear', 'vendor:publish',
];

const normalizeVendorName = (s) => (s || '').replace(/[^\w:-]/g, '').toLowerCase();

const isDangerousCommand = (cmd) => {
    let base = normalizeVendorName((cmd || '').trim().replace(/^php artisan\s+|^artisan\s+/i, ''));
    return dangerousPrefixes.some((p) => base === p || base.startsWith(p + ' ') || base.startsWith(p + ':'));
};

const activeTab = computed(() => tabs.find((t) => t.id === activeTabId.value) ?? null);

const outputText = computed(() => (activeTab.value?.outputLines ?? []).join('\n'));
const historyLines = computed(() => {
    const all = props.sessions
        .filter((s) => s.status === 'done' || s.status === 'closed' || s.status === 'error')
        .slice()
        .sort((a, b) => (b.id ?? 0) - (a.id ?? 0));
    return all;
});

const initFromSessions = () => {
    tabs.splice(0, tabs.length);
    for (const s of props.sessions ?? []) {
        tabs.push({
            id: s.id,
            title: s.title,
            command: s.command,
            status: s.status,
            exit_code: s.exit_code,
            offset: 0,
            started_at: s.started_at,
            ended_at: s.ended_at,
            outputLines: [],
            partial: '',
        });
    }

    if (tabs.length > 0 && activeTabId.value == null) {
        activeTabId.value = tabs[0].id;
    }

    nextTick(() => {
        tabs.forEach((tab) => { if (tab.status === 'running' && tab.offset === 0) pollTail(tab.id); });
    });
};

// When Inertia reloads sessions after a launch, we re-derive the running tab list.
watch(
    () => props.sessions,
    (next) => {
        const incoming = next ?? [];
        const currentIds = new Set(tabs.map((x) => x.id));

        // Add new sessions that were created elsewhere / after reload
        for (const s of incoming) {
            if (!currentIds.has(s.id)) {
                tabs.push({
                    id: s.id,
                    title: s.title,
                    command: s.command,
                    status: s.status,
                    exit_code: s.exit_code,
                    offset: 0,
                    started_at: s.started_at,
                    ended_at: s.ended_at,
                    outputLines: [],
                    partial: '',
                });
                if (s.status === 'running') pollTail(s.id);
            }
        }

        // Update statuses of known tabs
        for (const s of incoming) {
            const tab = tabs.find((x) => x.id === s.id);
            if (tab) {
                tab.status = s.status;
                tab.exit_code = s.exit_code;
                tab.ended_at = s.ended_at;
            }
        }

        // Refresh polling for running tabs
        tabs.forEach((tab) => { if (tab.status === 'running') pollTail(tab.id); });
    },
    { deep: true }
);

const startNew = () => {
    const cmd = (newCommand.value || '').trim();
    if (!cmd) return;

    if (isDangerousCommand(cmd)) {
        pendingCommand.value = cmd;
        confirmDialog.value = true;
        return;
    }

    doStartNew(cmd);
};

const doStartNew = (cmd) => {
    confirmDialog.value = false;
    pendingCommand.value = '';

    router.post('/system/terminal/start', {
        cmd,
        title: newTitle.value || cmd,
        confirm: '1',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newCommand.value = '';
            newTitle.value = '';
            // Le backend redirige vers /system/terminal ; on va recharger les sessions depuis la page Inertia
            router.reload({ only: ['sessions'] });
        },
        onError: (errors) => {
            const first = Object.values(errors || {})[0];
            toast.add({ severity: 'error', summary: t('errors.error'), detail: first ?? 'Erreur', life: 4000 });
        },
    });
};

const killTab = (tab) => {
    router.post(`/system/terminal/${tab.id}/kill`, {}, { preserveScroll: true });
};

const closeTab = (tab) => {
    router.post(`/system/terminal/${tab.id}/close`, {}, { preserveScroll: true });
    // retirer le tab de la liste locale immédiatement
    const idx = tabs.findIndex((x) => x.id === tab.id);
    if (idx !== -1) tabs.splice(idx, 1);
    if (activeTabId.value === tab.id) {
        activeTabId.value = tabs[0]?.id ?? null;
    }
};

const selectTab = (id) => {
    activeTabId.value = id;
};

const pollTail = async (tabId) => {
    const tab = tabs.find((x) => x.id === tabId);
    if (!tab) return;

    try {
        const res = await window.axios.get(`/system/terminal/${tabId}/tail`, { params: { offset: tab.offset ?? 0 } });
        const data = res?.data ?? {};
        const incoming = data.content ?? '';

        if (incoming) {
            // On fusionne les lignes complètes et on conserve la ligne partielle
            const combined = (tab.partial ?? '') + incoming;
            const parts = combined.split('\n');
            tab.partial = parts.pop() ?? '';
            for (const line of parts) tab.outputLines.push(line);
            tab.offset = data.offset ?? tab.offset;
        }

        tab.status = data.status ?? tab.status;
        tab.exit_code = data.exit_code;
        tab.ended_at = data.ended_at;

        if (tab.status !== 'running') {
            return;
        }

        // continue polling while running
        setTimeout(() => pollTail(tabId), 1000);
    } catch (e) {
        // silencieux : réseau peut échouer brièvement
        setTimeout(() => pollTail(tabId), 2000);
    }
};

const onNewCommandKey = (e) => {
    if (e.key === 'Enter') startNew();
};

const outputEl = ref(null);
watch(outputText, async () => {
    await nextTick();
    if (outputEl.value) {
        outputEl.value.scrollTop = outputEl.value.scrollHeight;
    }
});

onMounted(() => {
    initFromSessions();
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.terminal.title') + ' — SubnetCongo'" />

        <div class="min-h-screen bg-slate-50">
            <PageHeader
                :title="t('pages.terminal.title')"
                :subtitle="t('pages.terminal.subtitle')"
                icon="pi-terminal"
                accent="slate"
                badge="Terminal"
            />

            <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-4">
                <!-- Barre de création d'un nouveau terminal -->
                <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 md:p-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-end">
                        <div class="flex-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5 block">Nouveau terminal</label>
                            <InputText
                                v-model="newCommand"
                                class="w-full font-mono text-sm !rounded-xl border-slate-200"
                                :placeholder="t('pages.terminal.placeholder')"
                                autofocus
                                @keydown="onNewCommandKey"
                            />
                        </div>
                        <Button
                            label="Nouveau terminal"
                            icon="pi pi-plus"
                            class="!bg-slate-900 hover:!bg-slate-800 !border-none !text-white !rounded-xl font-bold"
                            @click="startNew"
                        />
                    </div>
                </section>

                <!-- Zone principale : onglets (gauche list) + contenu (droite) -->
                <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-[560px]">
                    <!-- Barre latérale / onglets -->
                    <div class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                        <div class="px-4 py-2 border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-500">Terminaux actifs</div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                type="button"
                                class="w-full text-left rounded-xl px-3 py-2 border transition-colors flex items-start gap-2"
                                :class="activeTabId === tab.id ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 hover:border-slate-400 bg-white'"
                                @click="selectTab(tab.id)"
                            >
                                <i :class="['pi', tab.status === 'running' ? 'pi-circle text-emerald-400 animate-pulse' : 'pi-check-circle text-slate-400', 'text-[10px] mt-1']" aria-hidden="true"></i>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-xs font-bold truncate">{{ tab.title }}</span>
                                    <span class="block text-[10px] opacity-75 font-mono truncate">{{ tab.command }}</span>
                                    <span v-if="tab.status !== 'running'" class="block text-[9px] uppercase tracking-wider mt-1" :class="tab.exit_code === 0 ? 'text-emerald-500' : 'text-red-400'">exit {{ tab.exit_code ?? '?' }}</span>
                                </span>
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-red-400 shrink-0"
                                    :aria-label="`Fermer ${tab.title}`"
                                    @click.stop="closeTab(tab)"
                                >
                                    <i class="pi pi-times text-xs"></i>
                                </button>
                            </button>
                        </div>
                    </div>

                    <!-- Contenu terminal actif -->
                    <div class="lg:col-span-9 bg-[#0b0f14] text-slate-100 rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col font-mono text-xs">
                        <!-- Barre fat =zzero -->
                        <div class="flex items-center justify-between gap-3 px-4 py-2 bg-[#111827] border-b border-slate-800 text-[11px] text-slate-400">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span>
                                <span class="ml-3 truncate">Séance #{{ activeTab?.id ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <Button
                                    icon="pi pi-trash"
                                    text
                                    severity="danger"
                                    :disabled="!activeTab || activeTab.status !== 'running'"
                                    v-tooltip.top="'Stopper la commande'"
                                    @click="killTab(activeTab)"
                                />
                                <span v-if="activeTab?.status === 'running'" class="text-[10px] text-emerald-400 animate-pulse">Running</span>
                                <span v-else class="text-[10px] text-slate-500">Terminé</span>
                            </div>
                        </div>
                        <!-- Sortie terminal -->
                        <div
                            ref="outputEl"
                            class="flex-1 overflow-auto px-4 py-3 leading-relaxed whitespace-pre-wrap break-all custom-scrollbar"
                            aria-live="polite"
                        ><p v-if="!activeTab" class="text-slate-600 italic select-none">Aucun terminal pour ce compte. Lancez une commande à gauche.</p>
                            <p v-else-if="!outputText" class="text-slate-600 italic select-none">En attente de la sortie de « {{ activeTab.command }} »…</p>
                            <code v-else>{{ outputText }}</code></div>
                    </div>
                </section>

                <!-- Historique des terminals (finis/enregistrements) -->
                <section v-if="historyLines.length" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 md:p-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">Terminaux terminés</h3>
                    <ul class="divide-y divide-slate-100">
                        <li v-for="h in historyLines" :key="h.id" class="flex items-center gap-3 py-2">
                            <code class="text-[11px] font-mono bg-slate-50 border border-slate-200 rounded px-2 py-1 text-slate-700">{{ h.command }}</code>
                            <span v-if="h.ended_at" class="text-[10px] text-slate-500">{{ new Date(h.ended_at).toLocaleString() }}</span>
                            <span class="text-[10px] font-bold uppercase" :class="h.exit_code === 0 || h.exit_code == null ? 'text-emerald-600' : 'text-red-500'">exit {{ h.exit_code ?? '?' }}</span>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Barre d'actions globales / aide -->
            <div class="text-center text-[11px] text-slate-400 py-2">Les commandes sont conservées même apres fermeture de l'onglet.</div>
        </div>
    </AppLayout>

    <Dialog v-model:visible="confirmDialog" pt:root:class="w-[420px]" :closable="true" :modal="true">
        <template #header>
            <span class="font-black text-slate-900 flex items-center gap-2"><i class="pi pi-exclamation-triangle text-amber-500"></i>Commande destructive</span>
        </template>
        <div class="space-y-3">
            <p class="text-sm text-slate-600">Cette commande peut modifier la base de données, le stockage ou la configuration.</p>
            <p class="text-xs font-mono bg-slate-100 rounded p-2 break-all">{{ pendingCommand }}</p>
        </div>
        <template #footer>
            <Button label="Annuler" text severity="secondary" @click="confirmDialog = false" />
            <Button label="Exécuter" icon="pi pi-play" class="!bg-red-600 hover:!bg-red-700 !border-none !text-white !rounded-xl font-bold" @click="doStartNew(pendingCommand)" />
        </template>
    </Dialog>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
</style>
