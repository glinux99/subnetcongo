<script setup>
/**
 * MÉDIATHÈQUE — fichiers réels des disques « media » (public/media)
 * et « public » (storage/app/public). Recherche + filtres côté serveur,
 * CRUD complet (upload / renommer / déplacer / supprimer) et
 * détection des fichiers référencés dans la BDD.
 */
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { useToast } from 'primevue/usetoast';

import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    assets:  { type: Object, default: () => ({ data: [] }) },
    stats:   { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const toast = useToast();

const search = ref(props.filters.search || '');
const disk = ref(props.filters.disk || 'all');
const type = ref(props.filters.type || 'all');

const typeOptions = computed(() => [
    { label: t('pages.web_assets.typeAll'), value: 'all' },
    { label: t('pages.web_assets.typeImages'), value: 'image' },
    { label: t('pages.web_assets.typeVideos'), value: 'video' },
    { label: t('pages.web_assets.typeAudios'), value: 'audio' },
    { label: t('pages.web_assets.typeDocuments'), value: 'document' },
    { label: t('pages.web_assets.typeArchives'), value: 'archive' },
]);

const diskOptions = computed(() => [
    { label: t('pages.web_assets.diskAll'), value: 'all' },
    { label: t('pages.web_assets.diskMedia'), value: 'media' },
    { label: t('pages.web_assets.diskStorage'), value: 'public' },
]);

const applyFilters = debounce(() => {
    router.get(route('web.assets'), {
        search: search.value || undefined,
        disk: disk.value !== 'all' ? disk.value : undefined,
        type: type.value !== 'all' ? type.value : undefined,
    }, { preserveState: true, preserveScroll: true });
}, 350);

watch(search, applyFilters);
watch([disk, type], applyFilters);

const goToPage = (page) => {
    router.get(route('web.assets'), {
        search: search.value || undefined,
        disk: disk.value !== 'all' ? disk.value : undefined,
        type: type.value !== 'all' ? type.value : undefined,
        page,
    }, { preserveState: true, preserveScroll: true });
};

const diskLabel = (d) => (d === 'media' ? 'Media' : 'Storage');
const keyOf = (f) => `${f.disk}:${f.path}`;

const fileIcon = (f) => {
    if (f.is_image) return 'pi-image';
    if (f.is_video) return 'pi-video';
    if (f.is_audio) return 'pi-volume-up';
    if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'].includes(f.extension)) return 'pi-file';
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(f.extension)) return 'pi-box';
    return 'pi-file';
};

/* ---------- Sélection multiple ---------- */
const selected = ref([]);

const pageKeys = computed(() => (props.assets.data || []).map(keyOf));
const allPageSelected = computed(() => pageKeys.value.length > 0 && pageKeys.value.every((k) => selected.value.includes(k)));
const somePageSelected = computed(() => pageKeys.value.some((k) => selected.value.includes(k)));

const toggleAllPage = () => {
    if (allPageSelected.value) {
        selected.value = selected.value.filter((k) => !pageKeys.value.includes(k));
    } else {
        selected.value = [...new Set([...selected.value, ...pageKeys.value])];
    }
};

const selectedFiles = computed(() => (props.assets.data || []).filter((f) => selected.value.includes(keyOf(f))));

/* ---------- Détection d'utilisation ---------- */
const usageMap = ref({});   // « disk:path » => [table.colonne...]
const scanning = ref(false);

const scanUsage = async () => {
    const items = props.assets.data || [];
    if (!items.length) {
        usageMap.value = {};
        return;
    }
    scanning.value = true;
    try {
        const { data } = await window.axios.post(route('web.assets.usage'), {
            keys: items.map(keyOf),
        });
        usageMap.value = data.usage || {};
    } catch (error) {
        console.error('Détection d’utilisation échouée', error);
    } finally {
        scanning.value = false;
    }
};

onMounted(scanUsage);
watch(() => props.assets, () => {
    selected.value = [];
    scanUsage();
});

const usageOf = (f) => usageMap.value[keyOf(f)] || [];

/* ---------- Upload ---------- */
const uploadDialog = ref(false);
const uploadDisk = ref('media');
const uploadFolder = ref('');
const uploading = ref(false);

const onUploader = async (event) => {
    const files = event?.files || [];
    if (!files.length) return;
    uploading.value = true;
    const fd = new FormData();
    fd.append('disk', uploadDisk.value);
    fd.append('folder', uploadFolder.value || '');
    files.forEach((file) => fd.append('files[]', file));
    try {
        const { data } = await window.axios.post(route('web.assets.upload'), fd);
        if (data.ok) {
            toast.add({ severity: 'success', summary: t('pages.web_assets.toastFilesSent'), detail: t('pages.web_assets.toastFilesAdded', { count: data.files.length }), life: 4000 });
            uploadDialog.value = false;
            uploadFolder.value = '';
            router.reload({ only: ['assets', 'stats'] });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: t('pages.web_assets.toastUploadFailed'), detail: error.response?.data?.message || t('pages.web_assets.toastUploadErrorDetail'), life: 5000 });
    } finally {
        uploading.value = false;
    }
};

/* ---------- Renommer / déplacer ---------- */
const renameDialog = reactive({ visible: false, file: null, name: '', folder: '' });
const renaming = ref(false);

const openRename = (f) => {
    renameDialog.file = f;
    renameDialog.name = f.name;
    renameDialog.folder = f.folder;
    renameDialog.visible = true;
};

const submitRename = async () => {
    if (!renameDialog.file) return;
    renaming.value = true;
    try {
        const { data } = await window.axios.post(route('web.assets.rename'), {
            disk: renameDialog.file.disk,
            path: renameDialog.file.path,
            name: renameDialog.name,
            folder: renameDialog.folder,
        });
        if (data.ok) {
            toast.add({ severity: 'success', summary: t('pages.web_assets.toastRenamed'), detail: data.asset?.name || '', life: 3000 });
            renameDialog.visible = false;
            router.reload({ only: ['assets', 'stats'] });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: t('pages.web_assets.toastFailed'), detail: error.response?.data?.message || t('pages.web_assets.toastRenameFailed'), life: 5000 });
    } finally {
        renaming.value = false;
    }
};

/* ---------- Suppression ---------- */
const deleteDialog = reactive({ visible: false, items: [], blocked: [] });
const deleting = ref(false);

const openDelete = (items) => {
    deleteDialog.items = items;
    // Pré-calcul côté client des dépendances connues pour afficher l'avertissement immédiatement.
    deleteDialog.blocked = items
        .map((i) => ({ path: i.path, disk: i.disk, usages: usageMap.value[`${i.disk}:${i.path}`] || [] }))
        .filter((b) => b.usages.length > 0);
    deleteDialog.visible = true;
};

const submitDelete = async (force = false) => {
    deleting.value = true;
    try {
        const { data } = await window.axios.post(route('web.assets.delete'), {
            items: deleteDialog.items.map((i) => ({ disk: i.disk, path: i.path })),
            force,
        });
        if (data.ok) {
            toast.add({ severity: 'success', summary: t('pages.web_assets.toastDeleted'), detail: t('pages.web_assets.toastFilesDeleted', { count: data.removed }), life: 4000 });
            deleteDialog.visible = false;
            selected.value = [];
            router.reload({ only: ['assets', 'stats'] });
        }
    } catch (error) {
        if (error.response?.status === 409) {
            // Le scan était obsolète : on bascule sur les preuves serveur.
            deleteDialog.blocked = error.response.data.blocked || [];
        } else {
            toast.add({ severity: 'error', summary: t('pages.web_assets.toastFailed'), detail: error.response?.data?.message || t('pages.web_assets.toastDeleteFailed'), life: 5000 });
        }
    } finally {
        deleting.value = false;
    }
};

const copyUrl = async (f) => {
    try {
        await navigator.clipboard.writeText(f.url);
        toast.add({ severity: 'success', summary: t('pages.web_assets.toastCopied'), detail: t('pages.web_assets.toastUrlCopied'), life: 2000 });
    } catch {
        toast.add({ severity: 'warn', summary: t('pages.web_assets.toastCopyFailed'), detail: f.url, life: 4000 });
    }
};

const kpis = computed(() => [
    { label: t('pages.web_assets.kpiTotalFiles'), value: props.stats.count ?? 0, icon: 'pi-folder-open', classes: 'bg-blue-50 text-blue-600' },
    { label: t('pages.web_assets.kpiTotalSize'), value: props.stats.total_size ?? '0', icon: 'pi-server', classes: 'bg-emerald-50 text-emerald-600' },
    { label: t('pages.web_assets.kpiDiskMedia'), value: t('pages.web_assets.filesCount', { count: props.stats.by_disk?.media?.count ?? 0 }), icon: 'pi-images', classes: 'bg-amber-50 text-amber-600' },
    { label: t('pages.web_assets.kpiDiskStorage'), value: t('pages.web_assets.filesCount', { count: props.stats.by_disk?.public?.count ?? 0 }), icon: 'pi-cloud', classes: 'bg-rose-50 text-rose-600' },
]);
</script>

<template>
    <Head :title="t('pages.web_assets.title')" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                :title="t('pages.web_assets.title')"
                :subtitle="t('pages.web_assets.subtitle')"
                icon="pi-images"
                accent="blue"
                :badge="t('pages.web_assets.badge')"
            >
                <template #actions>
                    <Button :label="t('pages.web_assets.addFiles')" icon="pi pi-upload" size="small" @click="uploadDialog = true" />
                </template>
            </PageHeader>

            <div class="page-container page-section">

                <!-- KPI réels -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                    <div v-for="kpi in kpis" :key="kpi.label" class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ kpi.label }}</p>
                            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ kpi.value }}</div>
                        </div>
                        <div :class="`w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center ${kpi.classes}`">
                            <i :class="`pi ${kpi.icon} text-xl`"></i>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex flex-col md:flex-row gap-3 items-center">
                        <IconField iconPosition="left" class="flex-1 w-full">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="search" :placeholder="t('pages.web_assets.searchPlaceholder')" class="w-full h-11" />
                        </IconField>
                        <Select v-model="disk" :options="diskOptions" optionLabel="label" optionValue="value" class="w-full md:w-56" />
                        <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full md:w-48" />
                        <Button icon="pi pi-refresh" :label="t('pages.web_assets.rescan')" text size="small" :loading="scanning"
                                v-tooltip.top="t('pages.web_assets.rescanTip')" @click="scanUsage" />
                    </div>
                </div>

                <!-- Barre de sélection multiple -->
                <div v-if="selected.length > 0" class="flex flex-wrap items-center gap-3 bg-blue-50 border border-blue-200 rounded-2xl px-5 py-3">
                    <span class="text-sm font-bold text-blue-800">{{ t('pages.web_assets.selectedCount', { count: selected.length }) }}</span>
                    <div class="flex items-center gap-2 ml-auto">
                        <Button :label="t('pages.web_assets.selectAll')" text size="small" @click="toggleAllPage" v-if="!allPageSelected" />
                        <Button :label="t('pages.web_assets.deselect')" text size="small" @click="selected = []" />
                        <Button :label="t('pages.web_assets.delete')" icon="pi pi-trash" severity="danger" size="small"
                                @click="openDelete(selectedFiles)" />
                    </div>
                </div>

                <!-- Grille de fichiers -->
                <div v-if="assets.data?.length">
                    <div class="flex items-center gap-2 mb-2 px-1">
                        <Checkbox :modelValue="allPageSelected" :indeterminate="somePageSelected && !allPageSelected"
                                  binary @change="toggleAllPage" inputId="select-all" />
                        <label for="select-all" class="text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer select-none">
                            {{ t('pages.web_assets.selectPage') }}
                        </label>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <div v-for="f in assets.data" :key="keyOf(f)"
                             class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow flex flex-col relative"
                             :class="{ 'ring-2 ring-blue-400': selected.includes(keyOf(f)) }">
                            <div class="aspect-video bg-slate-100 flex items-center justify-center overflow-hidden relative">
                                <img v-if="f.is_image" :src="f.url" :alt="f.name" loading="lazy" class="w-full h-full object-cover" />
                                <i v-else :class="`pi ${fileIcon(f)} text-3xl text-slate-400`"></i>
                                <div class="absolute top-1.5 left-1.5 bg-white/90 rounded-md p-1 shadow-sm">
                                    <Checkbox v-model="selected" :value="keyOf(f)" :binary="false" />
                                </div>
                            </div>
                            <div class="p-3 flex flex-col gap-1 flex-1">
                                <p class="text-xs font-bold text-slate-800 truncate" :title="f.name">{{ f.name }}</p>
                                <p class="text-[10px] text-slate-400 truncate font-mono" :title="f.path">{{ f.folder || t('pages.web_assets.rootFolder') }}</p>
                                <div class="flex items-center justify-between gap-1 mt-0.5">
                                    <span class="text-[10px] font-bold text-slate-500">{{ f.human_size }}</span>
                                    <Tag :value="diskLabel(f.disk)" :severity="f.disk === 'media' ? 'warn' : 'info'" class="!text-[9px] !px-1.5" />
                                </div>
                                <!-- Badge d'utilisation -->
                                <div class="mt-1">
                                    <span v-if="scanning" class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                        <i class="pi pi-spinner pi-spin text-[9px]"></i>{{ t('pages.web_assets.analyzing') }}
                                    </span>
                                    <Tag v-else-if="usageOf(f).length" severity="warn"
                                         :value="t('pages.web_assets.usedCount', { count: usageOf(f).length })"
                                         class="!text-[9px]" v-tooltip.top="usageOf(f).join('\n')" />
                                    <Tag v-else severity="success" :value="t('pages.web_assets.unused')" class="!text-[9px]" />
                                </div>
                                <div class="flex items-center gap-1 mt-auto pt-1.5">
                                    <a :href="f.url" target="_blank" rel="noopener" v-tooltip.top="t('pages.web_assets.openTab')"
                                       class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center transition-colors">
                                        <i class="pi pi-external-link text-[11px]"></i>
                                    </a>
                                    <button type="button" v-tooltip.top="t('pages.web_assets.copyUrl')" @click="copyUrl(f)"
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-500 hover:text-blue-600 flex items-center justify-center transition-colors">
                                        <i class="pi pi-copy text-[11px]"></i>
                                    </button>
                                    <button type="button" v-tooltip.top="t('pages.web_assets.renameMove')" @click="openRename(f)"
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-amber-50 text-slate-500 hover:text-amber-600 flex items-center justify-center transition-colors">
                                        <i class="pi pi-pencil text-[11px]"></i>
                                    </button>
                                    <button type="button" v-tooltip.top="t('pages.web_assets.delete')" @click="openDelete([f])"
                                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 flex items-center justify-center transition-colors">
                                        <i class="pi pi-trash text-[11px]"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="text-center py-20 bg-white rounded-2xl border border-slate-200">
                    <i class="pi pi-images text-5xl text-slate-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-slate-700 mb-1">{{ t('pages.web_assets.emptyTitle') }}</h3>
                    <p class="text-sm text-slate-500">{{ t('pages.web_assets.emptyText') }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="assets.last_page > 1" class="flex justify-center gap-1">
                    <Button
                        v-for="p in assets.last_page" :key="p"
                        :label="String(p)"
                        text rounded size="small"
                        :severity="p === assets.page ? 'primary' : 'secondary'"
                        @click="goToPage(p)"
                    />
                </div>
            </div>
        </div>

        <!-- Dialogue Upload -->
        <Dialog v-model:visible="uploadDialog" modal :header="t('pages.web_assets.addFiles')" :style="{ width: '34rem' }">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600" for="upload-disk">{{ t('pages.web_assets.uploadDisk') }}</label>
                    <Select id="upload-disk" v-model="uploadDisk" :options="diskOptions.filter(d => d.value !== 'all')"
                            optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600" for="upload-folder">{{ t('pages.web_assets.uploadFolder') }} <code>realizations</code></label>
                    <InputText id="upload-folder" v-model="uploadFolder" placeholder="media/mon-dossier" class="w-full" />
                </div>
                <FileUpload mode="advanced" :multiple="true" :customUpload="true" @uploader="onUploader"
                            :chooseLabel="t('pages.web_assets.browse')" :uploadLabel="t('pages.web_assets.send')" :cancelLabel="t('pages.web_assets.cancel')"
                            accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.csv"
                            :maxFileSize="51200000" :disabled="uploading" />
                <p class="text-[10px] text-slate-400">{{ t('pages.web_assets.uploadHelp') }}</p>
            </div>
        </Dialog>

        <!-- Dialogue Renommer -->
        <Dialog v-model:visible="renameDialog.visible" modal :header="t('pages.web_assets.renameTitle')" :style="{ width: '30rem' }">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600" for="rename-name">{{ t('pages.web_assets.fileName') }}</label>
                    <InputText id="rename-name" v-model="renameDialog.name" class="w-full" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600" for="rename-folder">{{ t('pages.web_assets.renameFolder') }}</label>
                    <InputText id="rename-folder" v-model="renameDialog.folder" placeholder="realizations/2026" class="w-full" />
                </div>
                <p class="text-[10px] text-slate-400">{{ t('pages.web_assets.renameHelp') }}</p>
                <div class="flex justify-end gap-2">
                    <Button :label="t('pages.web_assets.cancel')" text severity="secondary" @click="renameDialog.visible = false" />
                    <Button :label="t('pages.web_assets.save')" icon="pi pi-check" :loading="renaming" @click="submitRename" />
                </div>
            </div>
        </Dialog>

        <!-- Dialogue Suppression -->
        <Dialog v-model:visible="deleteDialog.visible" modal :header="t('pages.web_assets.delete')" :style="{ width: '32rem' }">
            <div class="flex flex-col gap-4">
                <p class="text-sm text-slate-600">
                    {{ t('pages.web_assets.deleteConfirm', { count: deleteDialog.items.length }) }}
                </p>
                <div v-if="deleteDialog.blocked.length" class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex flex-col gap-2 max-h-56 overflow-y-auto">
                    <p class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                        <i class="pi pi-exclamation-triangle"></i>
                        {{ t('pages.web_assets.deleteBlockedCount', { count: deleteDialog.blocked.length }) }}
                    </p>
                    <div v-for="b in deleteDialog.blocked" :key="b.path" class="text-[11px] font-mono text-amber-900 bg-amber-100/60 rounded-lg px-2 py-1.5">
                        <span class="font-bold">{{ b.path }}</span>
                        <ul class="list-disc list-inside text-amber-800 mt-0.5">
                            <li v-for="u in b.usages" :key="u">{{ u }}</li>
                        </ul>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <Button :label="t('pages.web_assets.cancel')" text severity="secondary" @click="deleteDialog.visible = false" />
                    <Button v-if="!deleteDialog.blocked.length" :label="t('pages.web_assets.delete')" icon="pi pi-trash" severity="danger"
                            :loading="deleting" @click="submitDelete(false)" />
                    <Button v-else :label="t('pages.web_assets.deleteAnyway')" icon="pi pi-trash" severity="danger"
                            :loading="deleting" @click="submitDelete(true)" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
