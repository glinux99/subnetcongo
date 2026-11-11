<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS — GESTIONNAIRE DE LANGUES & TRADUCTIONS (données réelles)
 * ======================================================================================
 * - Langues : table `languages` (BDD), synchronisée avec resources/js/locales/*.json
 * - Complétion : nombre de clés réellement traduites vs référence FR
 * - Éditeur : lecture/écriture directe du JSON de la locale (rebuild Vite pour le front)
 */
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import debounce from 'lodash/debounce';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';
import ToggleSwitch from 'primevue/toggleswitch';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Chart from 'primevue/chart';

const props = defineProps({
    languages: { type: Array, default: () => [] },
    stats:     { type: Object, default: () => ({}) },
    current:   { type: String, default: 'fr' },
    editor:    { type: Object, default: null },
});

const toast = useToast();
const confirm = useConfirm();

const isEditor = computed(() => !!props.editor);

const headerActions = computed(() => isEditor.value
    ? [
        { label: 'Retour à la liste', icon: 'pi-arrow-left', variant: 'secondary', onClick: () => router.get(route('system.languages.index')) },
      ]
    : [
        { label: 'Importer', icon: 'pi-upload', variant: 'secondary', onClick: () => { importCode.value = null; displayImportDialog.value = true; } },
        { label: 'Nouvelle langue', icon: 'pi-plus', variant: 'primary', onClick: () => displayLangDialog.value = true },
      ]
);

/* ─── Liste des langues ─────────────────────────────────────────────── */

const toggleActive = (lang) => {
    router.post(route('system.languages.toggle'), { id: lang.id }, { preserveScroll: true });
};

const makeDefault = (lang) => {
    confirm.require({
        group: 'app',
        message: `Définir « ${lang.name} » comme langue par défaut du site ?`,
        header: 'Langue par défaut',
        icon: 'pi pi-star',
        acceptLabel: 'Définir par défaut',
        rejectLabel: 'Annuler',
        accept: () => router.post(route('system.languages.set-default'), { id: lang.id }, { preserveScroll: true }),
    });
};

const removeLanguage = (lang) => {
    confirm.require({
        group: 'app',
        message: `Supprimer « ${lang.name} » de la liste des langues ? Le fichier de traduction sera conservé.`,
        header: 'Supprimer la langue',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        accept: () => router.delete(route('system.languages.destroy', lang.id), { preserveScroll: true }),
    });
};

const openEditor = (lang) => {
    router.get(route('system.languages.keys', lang.id));
};

const exportLanguage = (lang) => {
    window.location.href = route('system.languages.export', lang.id);
};

const progressColor = (p) => (p >= 100 ? '#10B981' : p >= 50 ? '#2563EB' : '#F59E0B');

/* ─── Éditeur de clés ───────────────────────────────────────────────── */

const editValues = ref({});
const savingKeys = ref({});

watch(() => props.editor, (ed) => {
    editValues.value = {};
    if (ed) {
        for (const row of ed.keys || []) {
            editValues.value[row.key] = row.value ?? '';
        }
    }
}, { immediate: true });

const search = ref(props.editor?.search || '');
const applySearch = debounce(() => {
    router.get(route('system.languages.keys', props.editor.language.id), {
        search: search.value || undefined,
    }, { preserveScroll: true });
}, 400);
watch(search, applySearch);

const goToPage = (page) => {
    router.get(route('system.languages.keys', props.editor.language.id), {
        search: search.value || undefined,
        page,
    }, { preserveScroll: true });
};

const dirty = (row) => (editValues.value[row.key] ?? '') !== (row.value ?? '');

const saveKey = (row) => {
    savingKeys.value[row.key] = true;
    router.put(route('system.languages.keys.update', props.editor.language.id), {
        key: row.key,
        value: editValues.value[row.key] ?? '',
    }, {
        preserveScroll: true,
        onFinish: () => { savingKeys.value[row.key] = false; },
    });
};

const displayValue = (v) => (v === null || v === undefined || v === '' ? '—' : String(v));

/* ─── Nouvelle langue ───────────────────────────────────────────────── */

const displayLangDialog = ref(false);
const newLang = ref({ name: '', code: '' });

const saveLanguage = () => {
    if (!newLang.value.name.trim() || !newLang.value.code.trim()) {
        toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Nom et code ISO obligatoires.', life: 3000 });
        return;
    }
    router.post(route('system.languages.store'), {
        name: newLang.value.name.trim(),
        code: newLang.value.code.trim().toLowerCase(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            displayLangDialog.value = false;
            newLang.value = { name: '', code: '' };
        },
    });
};

/* ─── Import ────────────────────────────────────────────────────────── */

const displayImportDialog = ref(false);
const importCode = ref(null);
const importFile = ref(null);
const languageOptions = computed(() => props.languages.map(l => ({ label: `${l.flag} ${l.name} (${l.code})`, value: l.code })));

const onImportSelect = (e) => {
    importFile.value = e.files?.[0] ?? null;
};

const submitImport = () => {
    if (!importCode.value || !importFile.value) {
        toast.add({ severity: 'warn', summary: 'Sélection incomplète', detail: 'Choisissez une langue et un fichier JSON.', life: 3000 });
        return;
    }
    const form = new FormData();
    form.append('code', importCode.value);
    form.append('file', importFile.value);
    router.post(route('system.languages.import'), form, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { displayImportDialog.value = false; importFile.value = null; },
    });
};

/* ─── Graphique de couverture (réel) ────────────────────────────────── */

const coverageData = computed(() => ({
    labels: props.languages.map(l => l.name),
    datasets: [{
        data: props.languages.map(l => l.progress),
        backgroundColor: props.languages.map(l => progressColor(l.progress)),
        borderRadius: 6,
    }],
}));

const coverageOptions = {
    indexAxis: 'y',
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { max: 100, grid: { display: false }, border: { display: false } },
        y: { grid: { display: false }, border: { display: false } },
    },
};

const kpis = computed(() => [
    { label: 'Clés de référence (FR)', value: props.stats.total_keys ?? 0, icon: 'pi-key', color: 'blue' },
    { label: 'Langues actives', value: `${props.stats.active ?? 0} / ${props.stats.installed ?? 0}`, icon: 'pi-flag', color: 'emerald' },
    { label: 'Complétion moyenne', value: `${props.stats.completion ?? 0} %`, icon: 'pi-chart-line', color: 'amber' },
    { label: 'Langue par défaut', value: props.stats.default || '—', icon: 'pi-star', color: 'rose' },
]);
</script>

<template>
    <AppLayout>
        <Head :title="isEditor ? `Traductions — ${editor.language.name}` : 'Internationalisation - SubNetCongo'" />

        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="isEditor ? `Traductions ${editor.language.flag} ${editor.language.name}` : 'Internationalisation'"
                :subtitle="isEditor
                    ? 'Consultez et corrigez les clés de traduction. La colonne FR sert de référence ; les clés manquantes sont signalées.'
                    : 'Gérez le multilinguisme du site — langues installées, complétion réelle et fichiers de traduction.'"
                icon="pi-globe"
                accent="blue"
                badge="Système"
                :actions="headerActions"
            />

            <div class="page-container page-section">

                <!-- ═══ MODE ÉDITEUR DE CLÉS ═══ -->
                <template v-if="isEditor">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800 tracking-tight">
                                    {{ editor.language.flag }} {{ editor.language.name }}
                                    <span class="font-mono text-xs text-slate-400 ml-1">{{ editor.language.code }}</span>
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ editor.total }} clés — <span class="font-bold text-red-500">{{ editor.missing }} manquante(s)</span> vs FR
                                </p>
                            </div>
                            <IconField iconPosition="left" class="w-full sm:w-72">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="search" placeholder="Chercher une clé ou une valeur..." class="w-full" />
                            </IconField>
                        </div>

                        <Message severity="info" class="m-4 !rounded-xl text-xs">
                            Les modifications écrivent directement dans <code class="font-mono">resources/js/locales/{{ editor.language.code }}.json</code>.
                            Lancez <code class="font-mono font-bold">npm run build</code> pour les rendre visibles côté front.
                        </Message>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                                        <th class="py-3 px-4 w-1/3">Clé</th>
                                        <th class="py-3 px-4">Référence (FR)</th>
                                        <th class="py-3 px-4">{{ editor.language.name }}</th>
                                        <th class="py-3 px-4 text-right w-24">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in editor.keys" :key="row.key"
                                        class="border-b border-slate-100 hover:bg-slate-50/60"
                                        :class="{ 'bg-red-50/40': row.missing }">
                                        <td class="py-2.5 px-4 font-mono text-[11px] font-semibold text-slate-700 break-all">{{ row.key }}</td>
                                        <td class="py-2.5 px-4 text-slate-500 text-xs">{{ displayValue(row.fr) }}</td>
                                        <td class="py-2.5 px-2">
                                            <InputText
                                                v-model="editValues[row.key]"
                                                :class="['w-full !text-xs', row.missing && !editValues[row.key] ? '!border-red-300' : '']"
                                                :placeholder="row.missing ? 'Non traduit' : ''"
                                            />
                                        </td>
                                        <td class="py-2.5 px-4 text-right">
                                            <Button
                                                icon="pi pi-check" text rounded
                                                severity="success"
                                                :disabled="!dirty(row) || savingKeys[row.key]"
                                                :loading="savingKeys[row.key]"
                                                @click="saveKey(row)"
                                                v-tooltip.bottom="'Enregistrer cette clé'"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="editor.last_page > 1" class="flex justify-center gap-1 p-4 border-t border-slate-100">
                            <Button
                                v-for="p in editor.last_page" :key="p"
                                :label="String(p)"
                                text rounded size="small"
                                :severity="p === editor.page ? 'primary' : 'secondary'"
                                @click="goToPage(p)"
                            />
                        </div>
                    </div>
                </template>

                <!-- ═══ MODE LISTE ═══ -->
                <template v-else>
                    <!-- KPI réels -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="kpi in kpis" :key="kpi.label" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ kpi.label }}</p>
                                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ kpi.value }}</div>
                            </div>
                            <div :class="`w-12 h-12 rounded-xl bg-${kpi.color}-50 flex items-center justify-center text-${kpi.color}-600`">
                                <i :class="`pi ${kpi.icon} text-xl`"></i>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Liste des langues -->
                        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                                <h2 class="text-lg font-bold text-slate-800 tracking-tight">Langues installées</h2>
                                <p class="text-xs text-slate-500 mt-0.5">Synchronisées avec <code class="font-mono">resources/js/locales/*.json</code> et la table <code class="font-mono">languages</code>.</p>
                            </div>

                            <DataTable :value="languages" dataKey="id" stripedRows>
                                <Column header="Langue" style="min-width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-3">
                                            <span class="text-3xl">{{ data.flag }}</span>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-900">{{ data.name }}</span>
                                                    <Badge v-if="data.is_default" value="Défaut" severity="danger" class="!text-[9px] uppercase tracking-wider" />
                                                    <Tag v-if="data.code === current" value="Active" severity="info" class="!text-[9px] uppercase" />
                                                </div>
                                                <span class="text-xs font-mono text-slate-400">{{ data.code }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Complétion" style="min-width: 12rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1.5 pr-6">
                                            <div class="flex justify-between text-xs font-semibold">
                                                <span class="text-slate-600">{{ data.progress }} % traduit</span>
                                                <span class="text-slate-400">{{ data.keys }} clés</span>
                                            </div>
                                            <ProgressBar :value="data.progress" :showValue="false" style="height: 6px" :color="progressColor(data.progress)" />
                                            <span v-if="!data.has_file" class="text-[10px] text-amber-600 font-bold">Aucun fichier de traduction</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Visible" style="width: 7rem">
                                    <template #body="{ data }">
                                        <ToggleSwitch :modelValue="data.is_active" :disabled="data.is_default" @update:modelValue="toggleActive(data)" />
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 14rem">
                                    <template #body="{ data }">
                                        <div class="flex items-center gap-1">
                                            <Button icon="pi pi-pencil" text rounded severity="primary" @click="openEditor(data)" v-tooltip.bottom="'Éditer les traductions'" />
                                            <Button icon="pi pi-download" text rounded severity="secondary" @click="exportLanguage(data)" :disabled="!data.has_file" v-tooltip.bottom="'Exporter le JSON'" />
                                            <Button v-if="!data.is_default" icon="pi pi-star" text rounded severity="warning" @click="makeDefault(data)" v-tooltip.bottom="'Définir par défaut'" />
                                            <Button v-if="!data.is_default" icon="pi pi-trash" text rounded severity="danger" @click="removeLanguage(data)" v-tooltip.bottom="'Supprimer'" />
                                        </div>
                                    </template>
                                </Column>

                                <template #empty>
                                    <div class="text-center py-12 text-slate-400">
                                        <i class="pi pi-globe text-4xl mb-3"></i>
                                        <p class="font-semibold">Aucune langue enregistrée.</p>
                                    </div>
                                </template>
                            </DataTable>
                        </div>

                        <!-- Couverture + import -->
                        <div class="lg:col-span-4 space-y-6">
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                <h3 class="text-sm font-bold text-slate-900 mb-4 tracking-tight">Couverture réelle par langue</h3>
                                <div class="h-44">
                                    <Chart type="bar" :data="coverageData" :options="coverageOptions" class="h-full" />
                                </div>
                                <p class="text-[11px] text-slate-500 mt-3 leading-relaxed">
                                    Pourcentage de clés traduites par rapport au fichier de référence <code class="font-mono">fr.json</code>.
                                </p>
                            </div>

                            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                                <h3 class="text-sm font-bold text-slate-900 mb-2 tracking-tight px-2">Importer des traductions</h3>
                                <Button label="Importer un fichier JSON" icon="pi pi-upload" outlined severity="secondary" class="w-full !rounded-xl font-bold" @click="displayImportDialog = true" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ═══ DIALOGUE : NOUVELLE LANGUE ═══ -->
            <Dialog v-model:visible="displayLangDialog" modal header="Ajouter une langue" :style="{ width: '450px' }">
                <div class="pt-2 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Nom de la langue</label>
                        <InputText v-model="newLang.name" placeholder="ex : Portugais" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Code ISO</label>
                        <InputText v-model="newLang.code" placeholder="ex : pt" maxlength="8" class="w-full font-mono" />
                        <p class="text-[11px] text-slate-500 mt-1">Un fichier <code class="font-mono">js/locales/&lt;code&gt;.json</code> vide sera créé.</p>
                    </div>
                    <Message severity="info" class="!rounded-xl text-xs">La langue sera active après <code class="font-mono font-bold">npm run build</code> (chargement front).</Message>
                </div>
                <template #footer>
                    <Button label="Annuler" text severity="secondary" @click="displayLangDialog = false" />
                    <Button label="Ajouter la langue" icon="pi pi-plus" @click="saveLanguage" />
                </template>
            </Dialog>

            <!-- ═══ DIALOGUE : IMPORT JSON ═══ -->
            <Dialog v-model:visible="displayImportDialog" modal header="Importer des traductions (JSON)" :style="{ width: '500px' }">
                <div class="pt-2 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 tracking-wide">Langue cible</label>
                        <Select v-model="importCode" :options="languageOptions" optionLabel="label" optionValue="value" placeholder="Choisir une langue" class="w-full" />
                    </div>
                    <FileUpload mode="basic" accept=".json,application/json" :maxFileSize="2097152" chooseLabel="Choisir le fichier JSON" class="w-full" customUpload @select="onImportSelect" />
                    <Message severity="warn" class="!rounded-xl text-xs">Le fichier existant de cette langue sera remplacé par le contenu du fichier importé.</Message>
                </div>
                <template #footer>
                    <Button label="Annuler" text severity="secondary" @click="displayImportDialog = false" />
                    <Button label="Importer" icon="pi pi-upload" :disabled="!importCode || !importFile" @click="submitImport" />
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>
