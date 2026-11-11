<script setup>
/**
 * MÉDIATHÈQUE — fichiers réels des disques « media » (public/media)
 * et « public » (storage/app/public). Recherche + filtres côté serveur.
 */
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { Link } from '@inertiajs/vue3';

import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    assets:  { type: Object, default: () => ({ data: [] }) },
    stats:   { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const disk = ref(props.filters.disk || 'all');
const type = ref(props.filters.type || 'all');

const applyFilters = debounce(() => {
    router.get(route('web.assets.index'), {
        search: search.value || undefined,
        disk: disk.value !== 'all' ? disk.value : undefined,
        type: type.value !== 'all' ? type.value : undefined,
    }, { preserveState: true, preserveScroll: true });
}, 350);

watch(search, applyFilters);
watch([disk, type], applyFilters);

const goToPage = (page) => {
    router.get(route('web.assets.index'), {
        search: search.value || undefined,
        disk: disk.value !== 'all' ? disk.value : undefined,
        type: type.value !== 'all' ? type.value : undefined,
        page,
    }, { preserveState: true, preserveScroll: true });
};

const typeOptions = [
    { label: 'Tous les types', value: 'all' },
    { label: 'Images', value: 'image' },
    { label: 'Vidéos', value: 'video' },
    { label: 'Audios', value: 'audio' },
    { label: 'Documents', value: 'document' },
    { label: 'Archives', value: 'archive' },
];

const diskOptions = [
    { label: 'Tous les disques', value: 'all' },
    { label: 'Media (public/media)', value: 'media' },
    { label: 'Storage (app/public)', value: 'public' },
];

const diskLabel = (d) => (d === 'media' ? 'Media' : 'Storage');
const fileIcon = (f) => {
    if (f.is_image) return 'pi-image';
    if (f.is_video) return 'pi-video';
    if (f.is_audio) return 'pi-volume-up';
    if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'].includes(f.extension)) return 'pi-file';
    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(f.extension)) return 'pi-box';
    return 'pi-file';
};

const kpis = [
    { label: 'Fichiers au total', value: props.stats.count ?? 0, icon: 'pi-folder-open', color: 'blue' },
    { label: 'Poids total', value: props.stats.total_size ?? '0', icon: 'pi-server', color: 'emerald' },
    { label: 'Disque Media', value: `${props.stats.by_disk?.media?.count ?? 0} fichiers`, icon: 'pi-images', color: 'amber' },
    { label: 'Disque Storage', value: `${props.stats.by_disk?.public?.count ?? 0} fichiers`, icon: 'pi-cloud', color: 'rose' },
];
</script>

<template>
    <Head title="Médiathèque" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans">

            <PageHeader
                title="Médiathèque"
                subtitle="Tous les fichiers réellement présents sur les disques Media (public/media) et Storage (storage/app/public)."
                icon="pi-images"
                accent="blue"
                badge="Site web"
            />

            <div class="page-container page-section">

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

                <!-- Filtres -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex flex-col md:flex-row gap-3 items-center">
                        <IconField iconPosition="left" class="flex-1 w-full">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="search" placeholder="Rechercher un fichier ou un dossier..." class="w-full" />
                        </IconField>
                        <Select v-model="disk" :options="diskOptions" optionLabel="label" optionValue="value" class="w-full md:w-56" />
                        <Select v-model="type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full md:w-48" />
                    </div>
                </div>

                <!-- Grille de fichiers -->
                <div v-if="assets.data?.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <div v-for="f in assets.data" :key="f.disk + '/' + f.path"
                         class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                        <div class="aspect-video bg-slate-100 flex items-center justify-center overflow-hidden">
                            <img v-if="f.is_image" :src="f.url" :alt="f.name" loading="lazy" class="w-full h-full object-cover" />
                            <i v-else :class="`pi ${fileIcon(f)} text-3xl text-slate-400`"></i>
                        </div>
                        <div class="p-3 flex flex-col gap-1 flex-1">
                            <p class="text-xs font-bold text-slate-800 truncate" :title="f.name">{{ f.name }}</p>
                            <p class="text-[10px] text-slate-400 truncate font-mono" :title="f.path">{{ f.folder || 'racine' }}</p>
                            <div class="flex items-center justify-between mt-auto pt-1">
                                <span class="text-[10px] font-bold text-slate-500">{{ f.human_size }}</span>
                                <Tag :value="diskLabel(f.disk)" :severity="f.disk === 'media' ? 'warn' : 'info'" class="!text-[9px] !px-1.5" />
                            </div>
                            <a :href="f.url" target="_blank" rel="noopener"
                               class="text-[10px] font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 mt-1">
                                <i class="pi pi-external-link text-[9px]"></i> Ouvrir
                            </a>
                        </div>
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="text-center py-20 bg-white rounded-2xl border border-slate-200">
                    <i class="pi pi-images text-5xl text-slate-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-slate-700 mb-1">Aucun fichier trouvé</h3>
                    <p class="text-sm text-slate-500">Aucun fichier sur les disques sélectionnés pour ces filtres.</p>
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
    </AppLayout>
</template>
