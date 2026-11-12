<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirmAction } from '@/composables/useConfirmAction';
import debounce from 'lodash/debounce';

import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import PageCard from '@/Components/PageCard.vue';
import SncDialog from '@/Components/SncDialog.vue';
import SncSection from '@/Components/SncSection.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import DatePicker from 'primevue/datepicker';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import Textarea from 'primevue/textarea';

const toast = useToast();
const confirmAction = useConfirmAction();

const props = defineProps({
    realizations: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 15, last_page: 1 }) },
    categories:   { type: Array, default: () => [] },
    filters:      { type: Object, default: () => ({}) },
    stats:        { type: Object, default: () => ({ total: 0, published: 0, projects: 0 }) },
});

// ============================================================
// FILTRES SERVEUR
// ============================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || null,
    status: props.filters?.status || null,
});

const searchLoading = ref(false);

const reload = debounce(() => {
    searchLoading.value = true;
    router.get(route('admin.realizations.index'), { ...serverFilters, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => (searchLoading.value = false),
    });
}, 350);

watch(serverFilters, reload);

const resetFilters = () => {
    serverFilters.search = '';
    serverFilters.category = null;
    serverFilters.status = null;
};

const onPage = (event) => {
    router.get(route('admin.realizations.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows,
    }, { preserveState: true, preserveScroll: true });
};

const statusFilterOptions = [
    { label: 'Tous', value: null },
    { label: 'Publiées', value: 'published' },
    { label: 'Brouillons', value: 'draft' },
];

const categoryFilterOptions = computed(() => [
    { label: 'Toutes les catégories', value: null },
    ...props.categories.map((c) => ({ label: c, value: c })),
]);

// ============================================================
// FORMULAIRE (création / édition)
// ============================================================
const formDialog = ref(false);
const mode = ref('create'); // 'create' | 'edit'
const editingSlug = ref(null);

const form = useForm({
    title: '',
    slug: '',
    summary: '',
    description: '',
    image: '',
    client: '',
    location: '',
    completed_at: null,
    category: '',
    tags: [],
    is_published: true,
});

const tagsInput = computed({
    get: () => (form.tags || []).join(', '),
    set: (value) => {
        form.tags = String(value || '')
            .split(',')
            .map((t) => t.trim())
            .filter(Boolean);
    },
});

const imagePreviewError = ref(false);
watch(() => form.image, () => { imagePreviewError.value = false; });
const canPreview = computed(() => !!form.image && !imagePreviewError.value);

const openNew = () => {
    form.reset();
    form.clearErrors();
    form.is_published = true;
    mode.value = 'create';
    editingSlug.value = null;
    formDialog.value = true;
};

const fillForm = (item) => {
    form.title = item.title || '';
    form.slug = item.slug || '';
    form.summary = item.summary || '';
    form.description = item.description || '';
    form.image = item.image || '';
    form.client = item.client || '';
    form.location = item.location || '';
    form.completed_at = item.completed_at ? new Date(item.completed_at) : null;
    form.category = item.category || '';
    form.tags = Array.isArray(item.tags) ? [...item.tags] : [];
    form.is_published = !!item.is_published;
};

const openEdit = (item) => {
    form.clearErrors();
    fillForm(item);
    mode.value = 'edit';
    editingSlug.value = item.slug;
    formDialog.value = true;
};

const closeDialog = () => {
    formDialog.value = false;
    form.reset();
    form.clearErrors();
};

const dialogTitle = computed(() => (mode.value === 'edit' ? 'Modifier la réalisation' : 'Nouvelle réalisation'));

const submitForm = () => {
    if (mode.value === 'edit' && editingSlug.value) {
        form.put(route('admin.realizations.update', editingSlug.value), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    } else {
        form.post(route('admin.realizations.store'), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    }
};

const confirmDelete = (item) => {
    confirmAction({
        header: 'Supprimer la réalisation',
        message: `Supprimer définitivement « ${item.title} » ? Cette action est irréversible.`,
        acceptLabel: 'Supprimer',
        danger: true,
        accept: () => router.delete(route('admin.realizations.destroy', item.slug), {
            preserveScroll: true,
        }),
    });
};

const togglePublish = (item) => {
    router.post(route('admin.realizations.toggle', item.slug), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

// ============================================================
// AFFICHAGE
// ============================================================
const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

const realizationList = computed(() => props.realizations?.data ?? []);
const firstRow = computed(() => ((props.realizations?.current_page || 1) - 1) * (props.realizations?.per_page || 15));
</script>

<template>
    <AppLayout>
        <Head title="Nos réalisations — SubNetCongo" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                title="Nos réalisations"
                subtitle="Gérez le portfolio de projets présentés sur le site public"
                icon="pi-briefcase"
                accent="violet"
                badge="Site web"
                :actions="[{ label: 'Nouvelle réalisation', icon: 'pi-plus', variant: 'primary', onClick: openNew }]"
            />

            <div class="page-container page-section">
                <!-- KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6">
                    <PageKpi label="Total réalisations" :value="stats.total" icon="pi-briefcase" color="blue" />
                    <PageKpi label="Publiées" :value="stats.published" icon="pi-check-circle" color="emerald" />
                    <PageKpi label="Projets livrés" :value="stats.projects" icon="pi-folder" color="violet" subtext="Avec une date de fin renseignée" />
                </div>

                <PageCard>
                    <!-- Barre de filtres -->
                    <div class="flex flex-col md:flex-row md:flex-wrap md:items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <IconField iconPosition="left" class="w-full md:flex-1 md:min-w-[220px]">
                            <InputIcon class="pi pi-search text-slate-400" aria-hidden="true" />
                            <InputText
                                v-model="serverFilters.search"
                                placeholder="Rechercher un projet, un client, un lieu…"
                                class="w-full h-11"
                                aria-label="Rechercher une réalisation"
                            />
                        </IconField>

                        <Select
                            v-model="serverFilters.category"
                            :options="categoryFilterOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Toutes les catégories"
                            class="w-full md:w-56 h-11 shrink-0"
                            showClear
                            aria-label="Filtrer par catégorie"
                        />

                        <SelectButton
                            v-model="serverFilters.status"
                            :options="statusFilterOptions"
                            optionLabel="label"
                            optionValue="value"
                            aria-label="Filtrer par publication"
                            class="shrink-0 overflow-x-auto"
                            :pt="{ pcButton: { root: { class: 'h-11' } } }"
                        />

                        <Button
                            label="Réinitialiser"
                            icon="pi pi-refresh"
                            severity="secondary"
                            outlined
                            class="h-11 w-full md:w-auto shrink-0"
                            @click="resetFilters"
                        />
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-4 pb-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Portfolio des réalisations</h2>
                            <Badge :value="realizations.total ?? 0" severity="info" />
                        </div>
                        <i v-if="searchLoading" class="pi pi-spin pi-spinner text-slate-400" aria-hidden="true"></i>
                    </div>

                    <DataTable
                        :value="realizationList"
                        :lazy="true"
                        :paginator="true"
                        :rows="realizations.per_page || 15"
                        :first="firstRow"
                        :totalRecords="realizations.total ?? 0"
                        @page="onPage"
                        :loading="searchLoading"
                        stripedRows
                        responsiveLayout="scroll"
                        class="text-sm"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    >
                        <Column header="Image" style="width: 6.5rem">
                            <template #body="{ data }">
                                <img
                                    v-if="data.image"
                                    :src="data.image"
                                    :alt="`Aperçu — ${data.title}`"
                                    class="w-20 h-14 object-cover rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100"
                                    loading="lazy"
                                />
                                <div v-else class="w-20 h-14 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600">
                                    <i class="pi pi-image text-xl" aria-hidden="true"></i>
                                </div>
                            </template>
                        </Column>

                        <Column field="title" header="Titre" style="min-width: 16rem">
                            <template #body="{ data }">
                                <p class="font-semibold text-slate-800 dark:text-slate-100 line-clamp-1">{{ data.title }}</p>
                                <p v-if="data.summary" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ data.summary }}</p>
                            </template>
                        </Column>

                        <Column field="client" header="Client" style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="text-slate-600 dark:text-slate-300">{{ data.client || '—' }}</span>
                            </template>
                        </Column>

                        <Column field="location" header="Lieu" style="min-width: 9rem">
                            <template #body="{ data }">
                                <span class="text-slate-600 dark:text-slate-300">{{ data.location || '—' }}</span>
                            </template>
                        </Column>

                        <Column field="completed_at" header="Date" style="min-width: 8rem">
                            <template #body="{ data }">
                                <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.completed_at) }}</span>
                            </template>
                        </Column>

                        <Column header="Statut" style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <ToggleSwitch
                                        :modelValue="!!data.is_published"
                                        @update:modelValue="togglePublish(data)"
                                        :aria-label="data.is_published ? 'Dépublier la réalisation' : 'Publier la réalisation'"
                                    />
                                    <Tag :value="data.is_published ? 'Publiée' : 'Brouillon'" :severity="data.is_published ? 'success' : 'secondary'" />
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" frozen style="min-width: 9rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 whitespace-nowrap">
                                    <Button icon="pi pi-pencil" text rounded severity="secondary" aria-label="Éditer" v-tooltip.top="'Éditer'" @click="openEdit(data)" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Supprimer" v-tooltip.top="'Supprimer'" @click="confirmDelete(data)" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-10 text-slate-500 dark:text-slate-400">
                                <i class="pi pi-briefcase text-3xl mb-3 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                                <p class="font-semibold">Aucune réalisation trouvée</p>
                                <p class="text-sm mt-1">Modifiez vos filtres ou ajoutez un nouveau projet au portfolio.</p>
                            </div>
                        </template>
                    </DataTable>
                </PageCard>
            </div>
        </div>

        <!-- Dialogue création / édition -->
        <SncDialog
            v-model:visible="formDialog"
            :title="dialogTitle"
            subtitle="Référencez un projet pour l'afficher dans la section Réalisations du site public"
            :icon="mode === 'edit' ? 'pi-pencil' : 'pi-briefcase'"
            iconClass="from-blue-500 to-blue-700"
            width="720px"
            @hide="closeDialog"
        >
            <form @submit.prevent="submitForm" class="space-y-5 py-1">

                <!-- Aperçu image -->
                <div v-if="canPreview" class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <img :src="form.image" alt="Aperçu de l'image de la réalisation" class="w-full h-44 object-cover" @error="imagePreviewError = true" />
                    <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-slate-900/70 text-white text-[10px] font-bold uppercase tracking-wider">Aperçu</span>
                </div>

                <SncSection
                    icon="pi-id-card"
                    title="Projet"
                    subtitle="Titre, slug et résumé affichés publiquement"
                    icon-class="from-indigo-600 to-slate-800"
                >
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="real-title" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Titre <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                id="real-title"
                                v-model="form.title"
                                placeholder="ex: Déploiement fibre optique — Kinshasa"
                                class="w-full h-11"
                                :class="{ '!border-rose-500': form.errors.title }"
                            />
                            <small v-if="form.errors.title" class="text-xs text-rose-500 mt-1 block">{{ form.errors.title }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="real-slug" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Slug <span class="text-slate-400 font-normal normal-case">(optionnel — généré depuis le titre)</span>
                            </label>
                            <InputText
                                id="real-slug"
                                v-model="form.slug"
                                placeholder="ex: deploiement-fibre-kinshasa"
                                class="w-full h-11 font-mono text-sm"
                                :class="{ '!border-rose-500': form.errors.slug }"
                            />
                            <small v-if="form.errors.slug" class="text-xs text-rose-500 mt-1 block">{{ form.errors.slug }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="real-summary" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Résumé
                            </label>
                            <Textarea
                                id="real-summary"
                                v-model="form.summary"
                                rows="2"
                                autoResize
                                placeholder="Une ou deux phrases affichées sur la carte du portfolio…"
                                class="w-full text-sm"
                                :class="{ '!border-rose-500': form.errors.summary }"
                            />
                            <small v-if="form.errors.summary" class="text-xs text-rose-500 mt-1 block">{{ form.errors.summary }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="real-description" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Description détaillée
                            </label>
                            <Textarea
                                id="real-description"
                                v-model="form.description"
                                rows="4"
                                autoResize
                                placeholder="Contexte, périmètre, résultats…"
                                class="w-full text-sm"
                                :class="{ '!border-rose-500': form.errors.description }"
                            />
                            <small v-if="form.errors.description" class="text-xs text-rose-500 mt-1 block">{{ form.errors.description }}</small>
                        </div>
                    </div>
                </SncSection>

                <SncSection
                    icon="pi-image"
                    title="Média & contexte"
                    subtitle="Chemin de la photo, client, lieu, catégorie et tags"
                    icon-class="from-emerald-500 to-teal-700"
                >
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="real-image" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Chemin / URL de l'image
                            </label>
                            <InputText
                                id="real-image"
                                v-model="form.image"
                                placeholder="ex: /media/realizations/real-p15-1.jpg"
                                class="w-full h-11 font-mono text-sm"
                                :class="{ '!border-rose-500': form.errors.image }"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">L'upload de fichier n'est pas géré ici : utilisez un chemin public (ex: <code>/media/…</code>) ou une URL complète.</p>
                            <small v-if="form.errors.image" class="text-xs text-rose-500 mt-1 block">{{ form.errors.image }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="real-client" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Client
                            </label>
                            <InputText id="real-client" v-model="form.client" placeholder="ex: Motorola Afrique" class="w-full h-11" :class="{ '!border-rose-500': form.errors.client }" />
                            <small v-if="form.errors.client" class="text-xs text-rose-500 mt-1 block">{{ form.errors.client }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="real-location" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Lieu
                            </label>
                            <InputText id="real-location" v-model="form.location" placeholder="ex: Kinshasa, RDC" class="w-full h-11" :class="{ '!border-rose-500': form.errors.location }" />
                            <small v-if="form.errors.location" class="text-xs text-rose-500 mt-1 block">{{ form.errors.location }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="real-completed" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Date de fin
                            </label>
                            <DatePicker
                                id="real-completed"
                                v-model="form.completed_at"
                                showIcon
                                dateFormat="dd/mm/yy"
                                :manualInput="false"
                                class="w-full"
                                inputClass="!h-11 !text-sm"
                                :class="{ '!border-rose-500': form.errors.completed_at }"
                            />
                            <small v-if="form.errors.completed_at" class="text-xs text-rose-500 mt-1 block">{{ form.errors.completed_at }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="real-category" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Catégorie
                            </label>
                            <InputText
                                id="real-category"
                                v-model="form.category"
                                list="real-category-options"
                                placeholder="ex: Réseaux & Télécoms"
                                class="w-full h-11"
                                inputClass="!h-11 !text-sm"
                                aria-label="Catégorie de la réalisation"
                            />
                            <datalist id="real-category-options">
                                <option v-for="c in categories" :key="c" :value="c" />
                            </datalist>
                            <small v-if="form.errors.category" class="text-xs text-rose-500 mt-1 block">{{ form.errors.category }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="real-tags" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                Tags <span class="text-slate-400 font-normal normal-case">(séparés par des virgules)</span>
                            </label>
                            <InputText
                                id="real-tags"
                                v-model="tagsInput"
                                placeholder="ex: fibre, vsat, installation"
                                class="w-full h-11 text-sm"
                                :class="{ '!border-rose-500': form.errors.tags }"
                            />
                            <small v-if="form.errors.tags" class="text-xs text-rose-500 mt-1 block">{{ form.errors.tags }}</small>
                        </div>
                    </div>
                </SncSection>

                <!-- Publication -->
                <div class="flex items-center justify-between p-4 rounded-xl border transition-all duration-300"
                    :class="form.is_published
                        ? 'bg-emerald-500/[0.04] border-emerald-500/30 dark:bg-emerald-950/20'
                        : 'bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800'">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                            :class="form.is_published ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'">
                            <i :class="form.is_published ? 'pi pi-check' : 'pi pi-eye-slash'" class="text-sm" aria-hidden="true"></i>
                        </div>
                        <div>
                            <label for="real-published" class="font-bold text-sm text-slate-800 dark:text-slate-100 cursor-pointer">
                                {{ form.is_published ? 'Visible sur le site public' : 'Masquée (brouillon)' }}
                            </label>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ form.is_published ? 'La réalisation apparaît dans le portfolio public.' : 'Non visible tant que vous ne l’avez pas publiée.' }}
                            </p>
                        </div>
                    </div>
                    <ToggleSwitch id="real-published" v-model="form.is_published" />
                </div>
            </form>

            <template #footer>
                <Button
                    label="Annuler"
                    icon="pi pi-times"
                    text
                    severity="secondary"
                    class="!px-4 !py-2.5 !text-xs !font-bold rounded-xl"
                    @click="closeDialog"
                />
                <Button
                    :label="mode === 'edit' ? 'Sauvegarder les modifications' : 'Créer la réalisation'"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="!px-5 !py-2.5 !text-xs !font-bold !bg-blue-600 hover:!bg-blue-700 !border-none !shadow-lg !shadow-blue-600/30 rounded-xl"
                    @click="submitForm"
                />
            </template>
        </SncDialog>
    </AppLayout>
</template>
