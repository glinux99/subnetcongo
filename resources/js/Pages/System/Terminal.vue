<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — TERMINAL PERSISTANT v2.0
 * ======================================================================================
 * Onglets multiples, processus persistants côté serveur, sortie en temps réel.
 * Les sessions terminées restent visibles ; chacune ouvre son propre onglet.
 * ======================================================================================
 */
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SncDialog from '@/Components/SncDialog.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
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
const viewTab = ref('terminals'); // 'terminals' | 'history'
const termMaximized = ref(false);
const termClosed = ref(false);

// sessions shown in tabs (id + UI state)
const tabs = reactive([]); // [{ id, title, command, status, exit_code, offset, outputLines, started_at, ended_at }]
const activeTabId = ref(null);

const dangerousPrefixes = [
    'down', 'up',
    'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:seed', 'db:wipe',
    'key:generate', 'storage:link', 'queue:flush', 'queue:failed',
    'optimize:clear', 'octane:clear', 'vendor:publish',
];

const quickCommands = [
    'migrate:status',
    'cache:clear',
    'config:cache',
    'route:clear',
    'view:clear',
    'queue:restart',
    'live-sessions:sweep',
];

const normalizeVendorName = (s) => (s || '').replace(/[^\w:-]/g, '').toLowerCase();

const isDangerousCommand = (cmd) => {
    let base = normalizeVendorName((cmd || '').trim().replace(/^php artisan\s+|^artisan\s+/i, ''));
    return dangerousPrefixes.some((p) => base === p || base.startsWith(p + ' ') || base.startsWith(p + ':'));
};

const commandIsDangerous = computed(() => isDangerousCommand(newCommand.value));

const activeTab = computed(() => tabs.find((t) => t.id === activeTabId.value) ?? null);

const outputText = computed(() => (activeTab.value?.outputLines ?? []).join('\n'));
const historyLines = computed(() => {
    const all = props.sessions
        .filter((s) => s.status === 'done' || s.status === 'closed' || s.status === 'error')
        .slice()
        .sort((a, b) => (b.id ?? 0) - (a.id ?? 0));
    return all;
});

const runningCount = computed(() => tabs.filter((x) => x.status === 'running').length);

const statusSeverity = (status, exitCode) => {
    if (status === 'running') return 'success';
    if (status === 'error' || (exitCode != null && exitCode !== 0)) return 'danger';
    return 'secondary';
};

const copyOutput = async () => {
    if (!outputText.value) return;
    try {
        await navigator.clipboard.writeText(outputText.value);
        toast.add({ severity: 'success', summary: t('pages.terminal.output_copied'), life: 2500 });
    } catch (_) {
        toast.add({ severity: 'error', summary: t('errors.error'), life: 3000 });
    }
};

const clearActiveOutput = () => {
    if (activeTab.value) activeTab.value.outputLines = [];
};

const initFromSessions = () => {
    tabs.splice(0, tabs.length);
    for (const s of props.sessions ?? []) {
        if (s.status === 'closed') continue;
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
        tabs.forEach((tab) => pollTail(tab.id));
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
            if (s.status === 'closed') continue;
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
                pollTail(s.id);
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
    const idx = tabs.findIndex((x) => x.id === tab.id);
    if (idx !== -1) tabs.splice(idx, 1);
    if (activeTabId.value === tab.id) {
        activeTabId.value = tabs[0]?.id ?? null;
    }
};

const selectTab = (id) => {
    activeTabId.value = id;
    viewTab.value = 'terminals';
};

const toggleMaximize = () => { termMaximized.value = !termMaximized.value; };
const closePanel = () => { termMaximized.value = false; termClosed.value = true; };
const reopenPanel = () => { termClosed.value = false; };

const pollTail = async (tabId) => {
    const tab = tabs.find((x) => x.id === tabId);
    if (!tab || tab._polling) return;

    tab._polling = true;
    try {
        const res = await window.axios.get(`/system/terminal/${tabId}/tail`, { params: { offset: tab.offset ?? 0 } });
        const data = res?.data ?? {};
        const incoming = data.content ?? '';

        if (incoming) {
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

        setTimeout(() => pollTail(tabId), 1000);
    } catch (e) {
        // silencieux : réseau peut échouer brièvement
        setTimeout(() => pollTail(tabId), 2000);
    } finally {
        tab._polling = false;
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
    // pas d'intervalle global : le polling est par onglet via setTimeout
});
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.terminal.title') + ' — SubnetCongo'" />

        <div class="min-h-screen bg-slate-50">
            <PageHeader
                :title="t('pages.terminal.title')"
                :subtitle="t('pages.terminal.subtitle')"
                icon="pi-desktop"
                accent="blue"
                badge="Admin"
            />

            <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-4">
                <!-- Barre de création d'un nouveau terminal -->
                <section class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 md:p-5">
                    <div class="flex flex-col lg:flex-row gap-3 lg:items-center">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-md flex-shrink-0" aria-hidden="true">
                            <i class="pi pi-desktop text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5 block">{{ t('pages.terminal.label_command') }}</label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <InputText
                                    v-model="newCommand"
                                    class="flex-1 font-mono text-sm !rounded-xl"
                                    :placeholder="t('pages.terminal.placeholder')"
                                    autofocus
                                    @keydown="onNewCommandKey"
                                />
                                <Button
                                    :label="t('pages.terminal.btn_run')"
                                    icon="pi pi-play"
                                    :disabled="!newCommand.trim()"
                                    class="!bg-blue-600 hover:!bg-blue-700 !border-none !text-white !rounded-xl font-bold whitespace-nowrap"
                                    @click="startNew"
                                />
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-2.5">
                                <Tag
                                    v-if="commandIsDangerous"
                                    severity="danger"
                                    class="!text-[10px] !font-bold uppercase tracking-wider"
                                    :value="t('pages.terminal.badge_danger')"
                                />
                                <Tag
                                    v-else-if="newCommand.trim()"
                                    severity="success"
                                    class="!text-[10px] !font-bold uppercase tracking-wider"
                                    :value="t('pages.terminal.badge_safe')"
                                />
                                <span class="text-[10px] text-slate-400 font-medium">{{ t('pages.terminal.quick') }} :</span>
                                <button
                                    v-for="qc in quickCommands"
                                    :key="qc"
                                    type="button"
                                    class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-700 border border-slate-200 hover:border-blue-200 transition-colors"
                                    @click="newCommand = qc"
                                >{{ qc }}</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Zone principale : onglets (gauche list) + contenu (droite) -->
                <section class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <!-- Panneau réduit -->
                    <div v-if="termClosed" class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="flex items-center gap-2 text-xs font-bold text-slate-500">
                            <i class="pi pi-desktop text-slate-400"></i>
                            {{ t('pages.terminal.tab_terminal') }}
                            <span v-if="tabs.length" class="text-[10px] font-mono text-slate-400">({{ tabs.length }})</span>
                        </span>
                        <Button
                            label="Réouvrir"
                            icon="pi pi-window-maximize"
                            text
                            severity="secondary"
                            class="!text-blue-600 !text-xs !font-bold"
                            @click="reopenPanel"
                        />
                    </div>

                    <template v-else>
                    <!-- Sélecteur de vue -->
                    <div class="flex items-center gap-1 px-4 pt-3 border-b border-slate-100">
                        <button
                            type="button"
                            class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-lg border-b-2 transition-colors -mb-px"
                            :class="viewTab === 'terminals' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            @click="viewTab = 'terminals'"
                        >
                            <i class="pi pi-desktop text-[11px]"></i>
                            {{ t('pages.terminal.tab_terminal') }}
                            <span v-if="tabs.length" class="ml-1 px-1.5 py-0.5 rounded-full text-[9px] font-black" :class="viewTab === 'terminals' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'">{{ tabs.length }}</span>
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-t-lg border-b-2 transition-colors -mb-px"
                            :class="viewTab === 'history' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                            @click="viewTab = 'history'"
                        >
                            <i class="pi pi-history text-[11px]"></i>
                            {{ t('pages.terminal.tab_history').replace('{n}', historyLines.length) }}
                        </button>
                        <span v-if="runningCount" class="ml-auto flex items-center gap-1.5 text-[10px] font-bold text-emerald-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ runningCount }} actif{{ runningCount > 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Vue Terminaux -->
                    <div v-if="viewTab === 'terminals'" class="grid grid-cols-1 lg:grid-cols-12 min-h-[560px]">
                        <!-- Barre latérale / onglets -->
                        <div
                            class="border-b lg:border-b-0 lg:border-r border-slate-100 flex flex-col max-h-[300px] lg:max-h-none"
                            :class="termMaximized ? 'hidden' : 'lg:col-span-3'"
                        >
                            <div class="px-4 py-2.5 border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-500 flex items-center justify-between">
                                <span>{{ t('pages.terminal.tab_terminal') }}</span>
                                <i class="pi pi-desktop text-slate-300 text-xs"></i>
                            </div>
                            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    type="button"
                                    class="w-full text-left rounded-xl px-3 py-2.5 border transition-all flex items-start gap-2.5"
                                    :class="activeTabId === tab.id ? 'border-blue-500 bg-blue-50/60 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50'"
                                    @click="selectTab(tab.id)"
                                >
                                    <span
                                        class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                        :class="activeTabId === tab.id ? 'bg-blue-600 text-white' : (tab.status === 'running' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400')"
                                        aria-hidden="true"
                                    >
                                        <i :class="['pi', tab.status === 'running' ? 'pi-circle-fill text-[8px]' : 'pi-desktop', 'text-xs']"></i>
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold truncate" :class="activeTabId === tab.id ? 'text-blue-900' : 'text-slate-700'">{{ tab.title }}</span>
                                            <span v-if="tab.status === 'running'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse flex-shrink-0" :title="'Running'"></span>
                                        </span>
                                        <span class="block text-[10px] font-mono truncate mt-0.5" :class="activeTabId === tab.id ? 'text-blue-600/80' : 'text-slate-400'">{{ tab.command }}</span>
                                        <span v-if="tab.status !== 'running'" class="block text-[9px] uppercase tracking-wider mt-1 font-bold" :class="tab.exit_code === 0 ? 'text-emerald-600' : 'text-red-500'">
                                            {{ t('pages.terminal.exit_code').replace('{code}', tab.exit_code ?? '?') }}
                                        </span>
                                    </span>
                                    <button
                                        type="button"
                                        class="text-slate-300 hover:text-red-500 transition-colors flex-shrink-0 mt-1"
                                        :aria-label="`Fermer ${tab.title}`"
                                        @click.stop="closeTab(tab)"
                                    >
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </button>

                                <div v-if="!tabs.length" class="text-center py-10 px-4">
                                    <div class="w-12 h-12 mx-auto rounded-xl bg-slate-100 flex items-center justify-center mb-3">
                                        <i class="pi pi-desktop text-slate-400 text-xl"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500">{{ t('pages.terminal.empty_state') }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ t('pages.terminal.footer_hint') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contenu terminal actif -->
                        <div
                            class="bg-[#0b1220] text-slate-100 flex flex-col min-h-[420px] font-mono text-xs"
                            :class="termMaximized ? 'fixed bottom-5 right-5 z-[90] w-[min(980px,calc(100vw-2.5rem))] h-[min(600px,72vh)] rounded-xl shadow-2xl border border-slate-700 lg:col-span-12' : 'lg:col-span-9'"
                        >
                            <!-- Barre titre façon fenêtre -->
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-[#0f172a] border-b border-white/10">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <div class="min-w-0 hidden sm:block">
                                        <p class="text-[11px] font-bold text-slate-200 truncate">{{ activeTab?.title ?? '—' }}</p>
                                        <p class="text-[9px] font-mono text-slate-500 truncate">{{ activeTab?.command ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <Tag
                                        v-if="activeTab"
                                        :severity="statusSeverity(activeTab.status, activeTab.exit_code)"
                                        class="!text-[9px] !font-black uppercase tracking-wider"
                                        :value="activeTab.status === 'running' ? 'Running' : (t('pages.terminal.exit_code').replace('{code}', activeTab.exit_code ?? '?'))"
                                    />
                                    <Button
                                        icon="pi pi-copy"
                                        text
                                        severity="secondary"
                                        class="!text-slate-400 hover:!text-white !w-8 !h-8"
                                        :disabled="!outputText"
                                        v-tooltip.top="t('pages.terminal.btn_copy')"
                                        @click="copyOutput"
                                    />
                                    <Button
                                        icon="pi pi-eraser"
                                        text
                                        severity="secondary"
                                        class="!text-slate-400 hover:!text-white !w-8 !h-8"
                                        :disabled="!activeTab || !activeTab.outputLines.length"
                                        v-tooltip.top="t('pages.terminal.btn_clear')"
                                        @click="clearActiveOutput"
                                    />
                                    <span class="w-px h-5 bg-white/10 mx-1"></span>
                                    <Button
                                        :icon="termMaximized ? 'pi pi-window-minimize' : 'pi pi-window-maximize'"
                                        text
                                        severity="secondary"
                                        class="!text-slate-400 hover:!text-white !w-8 !h-8"
                                        :aria-label="termMaximized ? 'Restaurer' : 'Agrandir'"
                                        v-tooltip.top="termMaximized ? 'Restaurer' : 'Agrandir'"
                                        @click="toggleMaximize"
                                    />
                                    <Button
                                        icon="pi pi-times"
                                        text
                                        severity="secondary"
                                        class="!text-slate-400 hover:!text-white !w-8 !h-8"
                                        aria-label="Réduire le panneau"
                                        v-tooltip.top="'Réduire'"
                                        @click="closePanel"
                                    />
                                    <Button
                                        icon="pi pi-stop-circle"
                                        text
                                        severity="danger"
                                        class="!text-slate-400 hover:!text-red-400 !w-8 !h-8"
                                        :disabled="!activeTab || activeTab.status !== 'running'"
                                        v-tooltip.top="'Stopper la commande'"
                                        @click="killTab(activeTab)"
                                    />
                                </div>
                            </div>
                            <!-- Sortie terminal -->
                            <div
                                ref="outputEl"
                                class="flex-1 overflow-auto px-4 py-3 leading-relaxed whitespace-pre-wrap break-all custom-scrollbar"
                                aria-live="polite"
                            ><p v-if="!activeTab" class="text-slate-500 italic select-none">{{ t('pages.terminal.empty_state') }}</p>
                                <p v-else-if="!outputText" class="text-slate-500 italic select-none">{{ t('pages.terminal.empty_state') }}</p>
                                <code v-else class="text-[12px]">{{ outputText }}</code></div>
                        </div>
                    </div>

                    <!-- Vue Historique -->
                    <div v-else class="p-4 md:p-5">
                        <div v-if="!historyLines.length" class="text-center py-12">
                            <div class="w-14 h-14 mx-auto rounded-xl bg-slate-100 flex items-center justify-center mb-3">
                                <i class="pi pi-history text-slate-400 text-2xl"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-500">{{ t('pages.terminal.history_empty') }}</p>
                        </div>
                        <ul v-else class="divide-y divide-slate-100">
                            <li
                                v-for="h in historyLines"
                                :key="h.id"
                                class="flex items-center gap-3 py-2.5 group"
                            >
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="h.exit_code === 0 || h.exit_code == null ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-500'">
                                    <i :class="['pi', h.exit_code === 0 || h.exit_code == null ? 'pi-check' : 'pi-times', 'text-xs']"></i>
                                </span>
                                <div class="flex-1 min-w-0">
                                    <code class="block text-[11px] font-mono bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700 truncate">{{ h.command }}</code>
                                </div>
                                <span v-if="h.ended_at" class="text-[10px] text-slate-400 hidden md:block">{{ new Date(h.ended_at).toLocaleString() }}</span>
                                <Tag
                                    :severity="h.exit_code === 0 || h.exit_code == null ? 'success' : 'danger'"
                                    class="!text-[9px] !font-black uppercase"
                                    :value="t('pages.terminal.exit_code').replace('{code}', h.exit_code ?? '?')"
                                />
                                <Button
                                    icon="pi pi-arrow-up-right"
                                    text
                                    severity="secondary"
                                    class="!text-slate-400 hover:!text-blue-600 !w-8 !h-8 opacity-0 group-hover:opacity-100 transition-opacity"
                                    :aria-label="t('pages.terminal.load_in_editor')"
                                    @click="newCommand = h.command; viewTab = 'terminals'"
                                />
                            </li>
                        </ul>
                    </div>
                    </template>
                </section>
            </div>
        </div>
    </AppLayout>

    <SncDialog
        v-model:visible="confirmDialog"
        :title="t('pages.terminal.danger_title')"
        :subtitle="t('pages.terminal.danger_desc')"
        icon="pi-exclamation-triangle"
        icon-class="from-amber-500 to-red-600"
        width="460px"
    >
        <div class="space-y-3">
            <p class="text-xs font-mono bg-slate-100 border border-slate-200 rounded-xl p-3 break-all text-slate-700">{{ pendingCommand }}</p>
        </div>
        <template #footer>
            <Button :label="t('pages.terminal.cancel')" text severity="secondary" @click="confirmDialog = false" />
            <Button :label="t('pages.terminal.run_anyway')" icon="pi pi-play" class="!bg-red-600 hover:!bg-red-700 !border-none !text-white !rounded-xl font-bold" @click="doStartNew(pendingCommand)" />
        </template>
    </SncDialog>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>
