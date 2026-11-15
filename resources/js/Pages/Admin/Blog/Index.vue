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
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();
const confirmAction = useConfirmAction();

const props = defineProps({
    posts:      { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 15, last_page: 1 }) },
    categories: { type: Array, default: () => [] },
    filters:    { type: Object, default: () => ({}) },
    stats:      { type: Object, default: () => ({ total: 0, published: 0, drafts: 0 }) },
});

// ============================================================
// FILTRES SERVEUR
// ============================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    category_id: props.filters?.category_id || null,
});

const searchLoading = ref(false);

const reload = debounce(() => {
    searchLoading.value = true;
    router.get(route('admin.blog.index'), { ...serverFilters, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => (searchLoading.value = false),
    });
}, 350);

watch(serverFilters, reload);

const resetFilters = () => {
    serverFilters.search = '';
    serverFilters.status = null;
    serverFilters.category_id = null;
};

const onPage = (event) => {
    router.get(route('admin.blog.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows,
    }, { preserveState: true, preserveScroll: true });
};

const statusFilterOptions = computed(() => [
    { label: t('pages.blog.filterAll'), value: null },
    { label: t('pages.blog.statusPublished'), value: 'published' },
    { label: t('pages.blog.statusDraft'), value: 'draft' },
]);

const categoryFilterOptions = computed(() => [
    { label: t('pages.blog.allCategories'), value: null },
    ...props.categories.map((c) => ({ label: c.name, value: c.id })),
]);

const statusOptions = computed(() => [
    { label: t('pages.blog.statusDraft'), value: 'draft' },
    { label: t('pages.blog.statusPublished'), value: 'published' },
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
    excerpt: '',
    content: '',
    thumbnail: '',
    featured_image: '',
    category_id: null,
    status: 'draft',
    is_published: false,
    published_at: null,
});

const imagePreviewError = ref(false);
watch(() => form.featured_image, () => { imagePreviewError.value = false; });
const canPreview = computed(() => !!form.featured_image && !imagePreviewError.value);

const openNew = () => {
    form.reset();
    form.clearErrors();
    form.status = 'draft';
    form.is_published = false;
    mode.value = 'create';
    editingSlug.value = null;
    formDialog.value = true;
};

const fillForm = (item) => {
    form.title = item.title || '';
    form.slug = item.slug || '';
    form.excerpt = item.excerpt || '';
    form.content = item.content || '';
    form.thumbnail = item.thumbnail || '';
    form.featured_image = item.featured_image || '';
    form.category_id = item.category_id ?? null;
    form.status = item.status || (item.is_published ? 'published' : 'draft');
    form.is_published = !!item.is_published;
    form.published_at = item.published_at ? new Date(item.published_at) : null;
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

const dialogTitle = computed(() => (mode.value === 'edit' ? t('pages.blog.dialogEditTitle') : t('pages.blog.dialogNewTitle')));

const submitForm = () => {
    if (mode.value === 'edit' && editingSlug.value) {
        form.put(route('admin.blog.update', editingSlug.value), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    } else {
        form.post(route('admin.blog.store'), {
            preserveScroll: true,
            onSuccess: closeDialog,
        });
    }
};

const confirmDelete = (item) => {
    confirmAction({
        header: t('pages.blog.deleteHeader'),
        message: t('pages.blog.deleteMessage', { title: item.title }),
        acceptLabel: t('pages.blog.deleteAccept'),
        danger: true,
        accept: () => router.delete(route('admin.blog.destroy', item.slug), {
            preserveScroll: true,
        }),
    });
};

const togglePublish = (item) => {
    router.post(route('admin.blog.toggle', item.slug), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

// ============================================================
// AFFICHAGE
// ============================================================
const formatDate = (value) => {
    if (!value) return t('pages.blog.notPublished');
    return new Date(value).toLocaleDateString(useI18n().locale.value, { day: '2-digit', month: 'short', year: 'numeric' });
};

const postList = computed(() => props.posts?.data ?? []);
const firstRow = computed(() => ((props.posts?.current_page || 1) - 1) * (props.posts?.per_page || 15));
</script>

<template>
    <AppLayout>
        <Head :title="t('pages.blog.headTitle')" />

        <div class="page-bg min-h-screen font-sans">
            <PageHeader
                :title="t('pages.blog.title')"
                :subtitle="t('pages.blog.subtitle')"
                icon="pi-book"
                accent="violet"
                :badge="t('pages.blog.badge')"
                :actions="[{ label: t('pages.blog.newPost'), icon: 'pi-plus', variant: 'primary', onClick: openNew }]"
            />

            <div class="page-container page-section">
                <!-- KPIs -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-6">
                    <PageKpi :label="t('pages.blog.kpiTotal')" :value="stats.total" icon="pi-book" color="violet" />
                    <PageKpi :label="t('pages.blog.kpiPublished')" :value="stats.published" icon="pi-check-circle" color="emerald" />
                    <PageKpi :label="t('pages.blog.kpiDrafts')" :value="stats.drafts" icon="pi-pencil" color="amber" />
                </div>

                <PageCard>
                    <!-- Barre de filtres -->
                    <div class="flex flex-col md:flex-row md:flex-wrap md:items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <IconField iconPosition="left" class="w-full md:flex-1 md:min-w-[220px]">
                            <InputIcon class="pi pi-search text-slate-400" aria-hidden="true" />
                            <InputText
                                v-model="serverFilters.search"
                                :placeholder="t('pages.blog.searchPlaceholder')"
                                class="w-full h-11"
                                :aria-label="t('pages.blog.searchAria')"
                            />
                        </IconField>

                        <Select
                            v-model="serverFilters.category_id"
                            :options="categoryFilterOptions"
                            optionLabel="label"
                            optionValue="value"
                            :placeholder="t('pages.blog.allCategories')"
                            class="w-full md:w-56 h-11 shrink-0"
                            showClear
                            :aria-label="t('pages.blog.filterCategoryAria')"
                        />

                        <SelectButton
                            v-model="serverFilters.status"
                            :options="statusFilterOptions"
                            optionLabel="label"
                            optionValue="value"
                            :aria-label="t('pages.blog.filterStatusAria')"
                            class="shrink-0 overflow-x-auto"
                            :pt="{ pcButton: { root: { class: 'h-11' } } }"
                        />

                        <Button
                            :label="t('pages.blog.reset')"
                            icon="pi pi-refresh"
                            severity="secondary"
                            outlined
                            class="h-11 w-full md:w-auto shrink-0"
                            @click="resetFilters"
                        />
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-4 pb-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ t('pages.blog.postsTitle') }}</h2>
                            <Badge :value="posts.total ?? 0" severity="info" />
                        </div>
                        <i v-if="searchLoading" class="pi pi-spin pi-spinner text-slate-400" aria-hidden="true"></i>
                    </div>

                    <DataTable
                        :value="postList"
                        :lazy="true"
                        :paginator="true"
                        :rows="posts.per_page || 15"
                        :first="firstRow"
                        :totalRecords="posts.total ?? 0"
                        @page="onPage"
                        :loading="searchLoading"
                        stripedRows
                        responsiveLayout="scroll"
                        class="text-sm"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                    >
                        <Column :header="t('pages.blog.colImage')" style="width: 6.5rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">
                                <img
                                    v-if="data.featured_image"
                                    :src="data.featured_image"
                                    :alt="t('pages.blog.imgAlt', { title: data.title })"
                                    class="w-20 h-14 object-cover rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100"
                                    loading="lazy"
                                />
                                <div v-else class="w-20 h-14 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600">
                                    <i class="pi pi-image text-xl" aria-hidden="true"></i>
                                </div>
                            </template>
                        </Column>

                        <Column field="title" :header="t('pages.blog.colTitle')" style="min-width: 16rem">
                            <template #body="{ data }">
                                <p class="font-semibold text-slate-800 dark:text-slate-100 line-clamp-1">{{ data.title }}</p>
                                <p v-if="data.excerpt" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ data.excerpt }}</p>
                            </template>
                        </Column>

                        <Column :header="t('pages.blog.colCategory')" style="min-width: 10rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell">
                            <template #body="{ data }">
                                <Tag v-if="data.category" :value="data.category.name" severity="info" />
                                <span v-else class="text-slate-400">—</span>
                            </template>
                        </Column>

                        <Column :header="t('pages.blog.colStatus')" style="min-width: 8rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <ToggleSwitch
                                        :modelValue="!!data.is_published"
                                        @update:modelValue="togglePublish(data)"
                                        :aria-label="data.is_published ? t('pages.blog.unpublishAria') : t('pages.blog.publishAria')"
                                    />
                                    <Tag :value="data.is_published ? t('pages.blog.statusPublishedShort') : t('pages.blog.statusDraftShort')" :severity="data.is_published ? 'success' : 'secondary'" />
                                </div>
                            </template>
                        </Column>

                        <Column field="published_at" :header="t('pages.blog.colPublished')" style="min-width: 8rem" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell">
                            <template #body="{ data }">
                                <span class="text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(data.published_at) }}</span>
                            </template>
                        </Column>

                        <Column :header="t('pages.blog.actions')" style="width: 4rem; min-width: 4rem">
                            <template #body="{ data }">
                                <div class="flex items-center gap-1 whitespace-nowrap">
                                    <Button icon="pi pi-pencil" text rounded severity="secondary" :aria-label="t('pages.blog.edit')" v-tooltip.top="t('pages.blog.edit')" @click="openEdit(data)" />
                                    <Button icon="pi pi-trash" text rounded severity="danger" :aria-label="t('pages.blog.delete')" v-tooltip.top="t('pages.blog.delete')" @click="confirmDelete(data)" />
                                </div>
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center py-10 text-slate-500 dark:text-slate-400">
                                <i class="pi pi-book text-3xl mb-3 text-slate-300 dark:text-slate-600" aria-hidden="true"></i>
                                <p class="font-semibold">{{ t('pages.blog.emptyTitle') }}</p>
                                <p class="text-sm mt-1">{{ t('pages.blog.emptyHint') }}</p>
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
            :subtitle="t('pages.blog.dialogSubtitle')"
            :icon="mode === 'edit' ? 'pi-pencil' : 'pi-book'"
            iconClass="from-violet-500 to-violet-700"
            width="760px"
            @hide="closeDialog"
        >
            <form @submit.prevent="submitForm" class="space-y-5 py-1">

                <!-- Aperçu image -->
                <div v-if="canPreview" class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <img :src="form.featured_image" :alt="t('pages.blog.imgPreviewAlt')" class="w-full h-44 object-cover" @error="imagePreviewError = true" />
                    <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-slate-900/70 text-white text-[10px] font-bold uppercase tracking-wider">{{ t('pages.blog.preview') }}</span>
                </div>

                <SncSection
                    icon="pi-id-card"
                    :title="t('pages.blog.secArticle')"
                    :subtitle="t('pages.blog.secArticleSub')"
                    icon-class="from-indigo-600 to-slate-800"
                >
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="post-title" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldTitle') }} <span class="text-rose-500">*</span>
                            </label>
                            <InputText
                                id="post-title"
                                v-model="form.title"
                                :placeholder="t('pages.blog.titlePlaceholder')"
                                class="w-full h-11"
                                :class="{ '!border-rose-500': form.errors.title }"
                            />
                            <small v-if="form.errors.title" class="text-xs text-rose-500 mt-1 block">{{ form.errors.title }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="post-slug" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldSlug') }} <span class="text-slate-400 font-normal normal-case">{{ t('pages.blog.slugHint') }}</span>
                            </label>
                            <InputText
                                id="post-slug"
                                v-model="form.slug"
                                :placeholder="t('pages.blog.slugPlaceholder')"
                                class="w-full h-11 font-mono text-sm"
                                :class="{ '!border-rose-500': form.errors.slug }"
                            />
                            <small v-if="form.errors.slug" class="text-xs text-rose-500 mt-1 block">{{ form.errors.slug }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="post-excerpt" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldExcerpt') }}
                            </label>
                            <Textarea
                                id="post-excerpt"
                                v-model="form.excerpt"
                                rows="2"
                                autoResize
                                :placeholder="t('pages.blog.excerptPlaceholder')"
                                class="w-full text-sm"
                                :class="{ '!border-rose-500': form.errors.excerpt }"
                            />
                            <small v-if="form.errors.excerpt" class="text-xs text-rose-500 mt-1 block">{{ form.errors.excerpt }}</small>
                        </div>

                        <div class="col-span-12">
                            <label for="post-content" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldContent') }}
                            </label>
                            <Textarea
                                id="post-content"
                                v-model="form.content"
                                rows="6"
                                autoResize
                                :placeholder="t('pages.blog.contentPlaceholder')"
                                class="w-full text-sm"
                                :class="{ '!border-rose-500': form.errors.content }"
                            />
                            <small v-if="form.errors.content" class="text-xs text-rose-500 mt-1 block">{{ form.errors.content }}</small>
                        </div>
                    </div>
                </SncSection>

                <SncSection
                    icon="pi-image"
                    :title="t('pages.blog.secMedia')"
                    :subtitle="t('pages.blog.secMediaSub')"
                    icon-class="from-emerald-500 to-teal-700"
                >
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label for="post-featured" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldFeatured') }}
                            </label>
                            <InputText
                                id="post-featured"
                                v-model="form.featured_image"
                                :placeholder="t('pages.blog.featuredPlaceholder')"
                                class="w-full h-11 font-mono text-sm"
                                :class="{ '!border-rose-500': form.errors.featured_image }"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">{{ t('pages.blog.featuredHelp') }}</p>
                            <small v-if="form.errors.featured_image" class="text-xs text-rose-500 mt-1 block">{{ form.errors.featured_image }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="post-thumbnail" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldThumbnail') }}
                            </label>
                            <InputText
                                id="post-thumbnail"
                                v-model="form.thumbnail"
                                :placeholder="t('pages.blog.thumbnailPlaceholder')"
                                class="w-full h-11 font-mono text-sm"
                                :class="{ '!border-rose-500': form.errors.thumbnail }"
                            />
                            <small v-if="form.errors.thumbnail" class="text-xs text-rose-500 mt-1 block">{{ form.errors.thumbnail }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="post-category" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldCategory') }}
                            </label>
                            <Select
                                id="post-category"
                                v-model="form.category_id"
                                :options="categories"
                                optionLabel="name"
                                optionValue="id"
                                :placeholder="t('pages.blog.categoryPlaceholder')"
                                class="w-full h-11"
                                showClear
                                :aria-label="t('pages.blog.categoryAria')"
                            />
                            <small v-if="form.errors.category_id" class="text-xs text-rose-500 mt-1 block">{{ form.errors.category_id }}</small>
                        </div>
                    </div>
                </SncSection>

                <SncSection
                    icon="pi-calendar-clock"
                    :title="t('pages.blog.secPublish')"
                    :subtitle="t('pages.blog.secPublishSub')"
                    icon-class="from-amber-500 to-rose-600"
                >
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 sm:col-span-6">
                            <label for="post-status" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldStatus') }}
                            </label>
                            <Select
                                id="post-status"
                                v-model="form.status"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full h-11"
                                :aria-label="t('pages.blog.statusAria')"
                            />
                            <small v-if="form.errors.status" class="text-xs text-rose-500 mt-1 block">{{ form.errors.status }}</small>
                        </div>

                        <div class="col-span-12 sm:col-span-6">
                            <label for="post-published-at" class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 block">
                                {{ t('pages.blog.fieldPublishedAt') }}
                            </label>
                            <DatePicker
                                id="post-published-at"
                                v-model="form.published_at"
                                showIcon
                                showTime
                                hourFormat="24"
                                dateFormat="dd/mm/yy"
                                :manualInput="false"
                                class="w-full"
                                inputClass="!h-11 !text-sm"
                                :class="{ '!border-rose-500': form.errors.published_at }"
                            />
                            <small v-if="form.errors.published_at" class="text-xs text-rose-500 mt-1 block">{{ form.errors.published_at }}</small>
                        </div>

                        <div class="col-span-12 flex items-center justify-between p-4 rounded-xl border transition-all duration-300"
                            :class="form.is_published
                                ? 'bg-emerald-500/[0.04] border-emerald-500/30 dark:bg-emerald-950/20'
                                : 'bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800'">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors"
                                    :class="form.is_published ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-400'">
                                    <i :class="form.is_published ? 'pi pi-check' : 'pi pi-eye-slash'" class="text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <label for="post-published" class="font-bold text-sm text-slate-800 dark:text-slate-100 cursor-pointer">
                                        {{ form.is_published ? t('pages.blog.publishedState') : t('pages.blog.draftState') }}
                                    </label>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ form.is_published ? t('pages.blog.publishedHelp') : t('pages.blog.draftHelp') }}
                                    </p>
                                </div>
                            </div>
                            <ToggleSwitch id="post-published" v-model="form.is_published" />
                        </div>
                    </div>
                </SncSection>
            </form>

            <template #footer>
                <Button
                    :label="t('pages.blog.cancel')"
                    icon="pi pi-times"
                    text
                    severity="secondary"
                    class="!px-4 !py-2.5 !text-xs !font-bold rounded-xl"
                    @click="closeDialog"
                />
                <Button
                    :label="mode === 'edit' ? t('pages.blog.saveChanges') : t('pages.blog.createPost')"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="!px-5 !py-2.5 !text-xs !font-bold !bg-violet-600 hover:!bg-violet-700 !border-none !shadow-lg !shadow-violet-600/30 rounded-xl"
                    @click="submitForm"
                />
            </template>
        </SncDialog>
    </AppLayout>
</template>
