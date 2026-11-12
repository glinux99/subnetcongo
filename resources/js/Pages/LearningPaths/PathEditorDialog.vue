<script setup>
/**
 * ====================================================================================================
 * SUBNETCONGO LMS - PATH ARCHITECT WORKSPACE DIALOG (EXTRACTED FROM LearningPaths.vue)
 * ====================================================================================================
 * Composant Dialog contenant l'éditeur de parcours pédagogique (split-screen).
 * Gère : pathForm (useForm), onglets, curriculum builder (drag & drop), uploads
 * thumbnail/badge, validation et sauvegarde (store/update).
 * ====================================================================================================
 */

import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useI18n } from 'vue-i18n';
import cloneDeep from 'lodash/cloneDeep';
import draggable from 'vuedraggable';

// ======================================================================================
// IMPORTATION DES COMPOSANTS PRIMEVUE
// ======================================================================================
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import SelectButton from 'primevue/selectbutton';
import FileUpload from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import InputSwitch from 'primevue/inputswitch';
import Editor from 'primevue/editor';
import Tooltip from 'primevue/tooltip';

// ======================================================================================
// PROPS & EMITS
// ======================================================================================
const props = defineProps({
    /** Contrôle la visibilité du dialog (v-model:visible) */
    visible: { type: Boolean, default: false },
    /** Catégories pour la classification */
    categories: { type: Array, default: () => [] },
    /** Catalogue complet des cours disponibles pour la construction */
    availableCourses: { type: Array, default: () => [] },
    /** Mapping des niveaux (Beginner/Intermediate/...) */
    levelsMap: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:visible', 'saved', 'create-category']);

const toast = useToast();
const { t } = useI18n();

// Proxy v-model:visible (conserve le nom `pathDialog` utilisé dans le template)
const pathDialog = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

// ======================================================================================
// ÉTATS GLOBAUX
// ======================================================================================
const lastSavedAt = ref(null);
const uploadProgress = ref(0);
const badgeUploadProgress = ref(0);

// ======================================================================================
// CONFIGURATION DU WORKSPACE ARCHITECTE (ONGLETS)
// ======================================================================================
const editorTabs = computed(() => [
    { id: 'general', label: t('pages.learning_paths.tabGeneral'), desc: t('pages.learning_paths.tabGeneralDesc'), icon: 'pi pi-id-card' },
    { id: 'curriculum', label: t('pages.learning_paths.tabCurriculum'), desc: t('pages.learning_paths.tabCurriculumDesc'), icon: 'pi pi-sitemap' },
    { id: 'media', label: t('pages.learning_paths.tabMedia'), desc: t('pages.learning_paths.tabMediaDesc'), icon: 'pi pi-image' },
    { id: 'settings', label: t('pages.learning_paths.tabSettings'), desc: t('pages.learning_paths.tabSettingsDesc'), icon: 'pi pi-cog' },
    { id: 'seo', label: t('pages.learning_paths.tabSeo'), desc: t('pages.learning_paths.tabSeoDesc'), icon: 'pi pi-google' }
]);
const activeTab = ref('general');

// ======================================================================================
// FORMULAIRES (USEFORM)
// ======================================================================================

/**
 * pathForm: Gère la création et l'édition de la structure d'un parcours.
 */
const pathForm = useForm({
    id: null,
    name: '',
    slug: '',
    description: '',
    category_id: null,
    level: 'Beginner',
    price: 0,
    status: 'Draft',
    thumbnail: null,
    badge_url: null,
    settings: {
        is_linear: true,
        allow_skip: false,
        show_certificate: true,
        expiration_days: null
    },
    seo_metadata: { title: '', description: '', keywords: '' },
    courses: []
});

// Computed pour l'aperçu dynamique de la vignette
const thumbnailUrl = computed(() => {
    const thumb = pathForm.thumbnail;
    if (!thumb) return null;
    if (typeof thumb === 'string') return thumb;
    // Gestion de l'objet File sélectionné
    if (thumb instanceof File || thumb instanceof Blob) {
        return URL.createObjectURL(thumb);
    }
    return null;
});

// Computed pour l'aperçu dynamique du badge
const badgePreviewUrl = computed(() => {
    const badge = pathForm.badge_url;
    if (!badge) return null;
    if (typeof badge === 'string') return badge;
    // Gestion de l'objet File sélectionné
    if (badge instanceof File || badge instanceof Blob) {
        return URL.createObjectURL(badge);
    }
    return null;
});

// Modèles pour le Builder de Curriculum (Dual Pane)
const searchCourseQuery = ref('');
const availableCoursesList = ref([]); // Cours dans le catalogue
const pathSyllabusList = ref([]);     // Cours dans le parcours

// ======================================================================================
// VALIDATEURS DU WORKSPACE ARCHITECTE
// ======================================================================================
const isTabGeneralValid = computed(() => pathForm.name.length > 3 && pathForm.category_id !== null);
const isTabCurriculumValid = computed(() => pathSyllabusList.value.length > 0);
const isPathValid = computed(() => isTabGeneralValid.value && isTabCurriculumValid.value);

// ======================================================================================
// MÉTHODES DU WORKSPACE ARCHITECTE (CRUD PARCOURS)
// ======================================================================================

/**
 * Ouvre le workspace pour un NOUVEAU parcours
 */
const openNewPath = () => {
    pathForm.reset();
    pathForm.settings = { is_linear: true, allow_skip: false, show_certificate: true, expiration_days: null };
    pathForm.seo_metadata = { title: '', description: '', keywords: '' };

    // Initialiser le builder
    availableCoursesList.value = cloneDeep(props.availableCourses);
    pathSyllabusList.value = [];
    searchCourseQuery.value = '';

    activeTab.value = 'general';
    lastSavedAt.value = null;
    pathDialog.value = true;
};

/**
 * Ouvre le workspace pour ÉDITER un parcours existant
 * @param {Object} pathData - Les données du parcours
 */
const editPath = (pathData) => {
    pathForm.clearErrors();
    const data = cloneDeep(pathData);

    pathForm.id = data.id;
    pathForm.name = data.name;
    pathForm.slug = data.slug;
    pathForm.description = data.description || '';
    pathForm.category_id = data.category_id;
    pathForm.level = data.level || 'Beginner';
    pathForm.price = parseFloat(data.price) || 0;
    pathForm.status = data.status || 'Draft';
    pathForm.thumbnail = data.thumbnail || null;
    pathForm.badge_url = data.badge_url || null;

    pathForm.settings = {
        is_linear: data.settings?.is_linear ?? true,
        allow_skip: data.settings?.allow_skip ?? false,
        show_certificate: data.settings?.show_certificate ?? true,
        expiration_days: data.settings?.expiration_days ?? null
    };
    pathForm.seo_metadata = data.seo_metadata || { title: '', description: '', keywords: '' };

    // Setup Curriculum Builder Lists
    const existingCourseIds = (data.courses || []).map(c => c.id);
    pathSyllabusList.value = data.courses || [];

    availableCoursesList.value = cloneDeep(props.availableCourses).filter(
        course => !existingCourseIds.includes(course.id)
    );

    searchCourseQuery.value = '';
    activeTab.value = 'general';
    pathDialog.value = true;
};

/**
 * Sauvegarde le parcours (Création ou Mise à jour)
 * @param {String} action - 'save', 'publish', ou 'draft'
 */
const savePath = (action = 'save') => {
    if (!isPathValid.value) {
        toast.add({ severity: 'error', summary: t('pages.learning_paths.validationFailedSummary'), detail: t('pages.learning_paths.validationFailedDetail'), life: 6000 });
        if (!isTabGeneralValid.value) activeTab.value = 'general';
        else if (!isTabCurriculumValid.value) activeTab.value = 'curriculum';
        return;
    }

    if (action === 'publish') pathForm.status = 'Published';
    if (action === 'draft') pathForm.status = 'Draft';

    // Synchroniser le syllabus avant l'envoi
    pathForm.courses = pathSyllabusList.value.map((c, index) => ({
        course_id: c.id,
        order: index + 1
    }));

    const url = pathForm.id ? route('learning-paths.update', pathForm.slug) : route('learning-paths.store');

    pathForm.transform((data) => {
        const transformed = { ...data, _method: pathForm.id ? 'PUT' : 'POST' };
        if (typeof data.thumbnail === 'string') delete transformed.thumbnail;
        if (typeof data.badge_url === 'string') delete transformed.badge_url;
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.learning_paths.successSummary'), detail: t('pages.learning_paths.pathSavedDetail'), life: 4000 });
            if (action !== 'draft') pathDialog.value = false;
            lastSavedAt.value = new Date();
            emit('saved');
        },
        onError: () => {
            toast.add({ severity: 'error', summary: t('pages.learning_paths.serverErrorSummary'), detail: t('pages.learning_paths.serverErrorDetail'), life: 5000 });
        }
    });
};

// ======================================================================================
// CURRICULUM BUILDER LOGIC (DRAG & DROP)
// ======================================================================================
const filteredAvailableCourses = computed(() => {
    if (!searchCourseQuery.value) return availableCoursesList.value;
    const q = searchCourseQuery.value.toLowerCase();
    return availableCoursesList.value.filter(c =>
        c.title.toLowerCase().includes(q) || (c.category?.name || '').toLowerCase().includes(q)
    );
});

const addCourseToPath = (course) => {
    pathSyllabusList.value.push(course);
    availableCoursesList.value = availableCoursesList.value.filter(c => c.id !== course.id);
};

const removeCourseFromPath = (course) => {
    availableCoursesList.value.unshift(course);
    pathSyllabusList.value = pathSyllabusList.value.filter(c => c.id !== course.id);
};

// ======================================================================================
// UPLOADERS & HELPERS DIVERS
// ======================================================================================
const onThumbnailSelect = (event) => {
    pathForm.thumbnail = event.files[0];
    simulateUpload(uploadProgress);
};

const onBadgeSelect = (event) => {
    pathForm.badge_url = event.files[0];
    simulateUpload(badgeUploadProgress);
};

const simulateUpload = (progressRef) => {
    progressRef.value = 0;
    const interval = setInterval(() => {
        progressRef.value += 20;
        if (progressRef.value >= 100) {
            clearInterval(interval);
            setTimeout(() => progressRef.value = 0, 500);
        }
    }, 150);
};

const generateSlug = () => {
    if (pathForm.name && !pathForm.id) {
        pathForm.slug = pathForm.name.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
    }
};

/**
 * Demande au parent l'ouverture du dialog de création de catégorie.
 */
const openCategoryDialog = () => {
    emit('create-category');
};

// ======================================================================================
// EXPOSITION VERS LE PARENT (ORCHESTRATION)
// ======================================================================================
defineExpose({
    openNew: openNewPath,
    openEdit: editPath,
    pathForm
});
</script>

<template>
        <Dialog
            v-model:visible="pathDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1400px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[90vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-2xl border border-slate-200"
        >
            <!-- HEADER WORKSPACE ARCHITECTE -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 shadow-inner border border-blue-200"><i class="pi pi-map text-xl"></i></div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 leading-tight">{{ pathForm.id ? t('pages.learning_paths.editPathTitle') : t('pages.learning_paths.createPathTitle') }}</h2>
                        <div class="flex items-center gap-3 text-xs text-slate-500 mt-0.5 font-medium">
                            <span>{{ t('pages.learning_paths.workspaceArchitect') }}</span>
                            <span v-if="lastSavedAt" class="text-emerald-600 flex items-center gap-1 font-bold"><i class="pi pi-check"></i> {{ t('pages.learning_paths.savedAt', { time: lastSavedAt.toLocaleTimeString() }) }}</span>
                        </div>
                    </div>
                </div>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text bg-slate-100 hover:bg-slate-200 text-slate-600 w-10 h-10 transition-colors" @click="pathDialog = false" />
            </div>

            <!-- BODY WORKSPACE ARCHITECTE -->
            <div class="flex-1 flex overflow-hidden">
                <!-- SIDEBAR STEPPER -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden lg:block p-5 space-y-2 z-10 shadow-[2px_0_15px_rgba(0,0,0,0.02)] custom-scrollbar">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest px-2 mb-5 border-b border-slate-100 pb-3">{{ t('pages.learning_paths.bundleConfig') }}</div>
                    <button
                        v-for="tab in editorTabs" :key="tab.id"
                        @click="activeTab = tab.id"
                        class="w-full flex items-start gap-4 px-4 py-4 rounded-xl transition-all duration-300 text-left relative overflow-hidden group"
                        :class="activeTab === tab.id ? 'bg-blue-50 border border-blue-200 shadow-sm' : 'hover:bg-slate-50 border border-transparent'"
                    >
                        <div v-if="activeTab === tab.id" class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-600"></div>
                        <i :class="[tab.icon, activeTab === tab.id ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600']" class="text-xl mt-0.5 transition-colors"></i>
                        <div class="flex-1">
                            <div class="font-bold text-sm" :class="activeTab === tab.id ? 'text-blue-900' : 'text-slate-700'">{{ tab.label }}</div>
                            <div class="text-[10px] mt-1 leading-tight" :class="activeTab === tab.id ? 'text-blue-600/80' : 'text-slate-500'">{{ tab.desc }}</div>
                        </div>

                        <!-- Indicateurs de Validation -->
                        <i v-if="tab.id === 'general' && !isTabGeneralValid" class="pi pi-exclamation-circle text-red-500 absolute right-3 top-1/2 -translate-y-1/2 text-sm"></i>
                        <i v-if="tab.id === 'curriculum' && !isTabCurriculumValid" class="pi pi-exclamation-circle text-red-500 absolute right-3 top-1/2 -translate-y-1/2 text-sm"></i>
                        <i v-if="tab.id === 'general' && isTabGeneralValid" class="pi pi-check-circle text-emerald-500 absolute right-3 top-1/2 -translate-y-1/2 text-sm"></i>
                        <i v-if="tab.id === 'curriculum' && isTabCurriculumValid" class="pi pi-check-circle text-emerald-500 absolute right-3 top-1/2 -translate-y-1/2 text-sm"></i>
                    </button>
                </div>

                <!-- CONTENT AREA ARCHITECTE -->
                <div class="flex-1 overflow-y-auto bg-slate-50/50 p-6 lg:p-10 custom-scrollbar relative">
                    <!-- CONTENU ONGLET 1: GENERAL (Réduit pour espace, identique au prompt initial) -->
                    <div v-show="activeTab === 'general'" class="max-w-4xl mx-auto space-y-6 animate-fade-in">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4"><div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div> {{ t('pages.learning_paths.globalIdentity') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2 space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.pathName') }} <span class="text-red-500">*</span></label>
                                    <InputText v-model="pathForm.name" @input="generateSlug" class="w-full p-4 font-black text-lg bg-slate-50 border-slate-300 focus:bg-white" :placeholder="t('pages.learning_paths.pathNamePlaceholder')" :class="{'p-invalid': pathForm.name.length > 0 && pathForm.name.length < 4}" />
                                </div>
                                <div class="col-span-2 space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.descriptionLabel') }}</label>
                                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-inner"><Editor v-model="pathForm.description" editorStyle="height: 250px" /></div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.categoryField') }} <span class="text-red-500">*</span></label>
                                    <div class="flex gap-2">
                                        <Dropdown v-model="pathForm.category_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.learning_paths.categoryPlaceholder')" class="flex-1 font-bold bg-slate-50" filter :class="{'p-invalid': !pathForm.category_id}" />
                                        <Button icon="pi pi-plus" class="p-button-outlined p-button-secondary bg-white" v-tooltip.top="t('pages.learning_paths.createCategoryTooltip')" @click="openCategoryDialog" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.levelLabel') }}</label>
                                    <SelectButton v-model="pathForm.level" :options="levelsMap" optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENU ONGLET 2: CURRICULUM (Réduit pour espace, identique au prompt initial) -->
                    <div v-show="activeTab === 'curriculum'" class="h-full flex flex-col max-w-6xl mx-auto animate-fade-in">
                        <!-- ... (Contenu identique à l'original: Dual Pane avec vuedraggable) ... -->
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex items-start gap-4 shadow-sm">
                            <i class="pi pi-info-circle text-blue-500 text-xl mt-0.5"></i>
                            <div>
                                <h4 class="font-bold text-blue-900 text-sm">{{ t('pages.learning_paths.sequenceBuilderTitle') }}</h4>
                                <p class="text-xs text-blue-700 mt-1 leading-relaxed">{{ t('pages.learning_paths.sequenceBuilderDesc') }}</p>
                            </div>
                        </div>
                        <div class="flex-1 grid grid-cols-1 lg:grid-cols-2 gap-6 min-h-[500px]">
                            <!-- PANE GAUCHE -->
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                                <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                                    <h3 class="font-bold text-slate-800 text-sm"><i class="pi pi-search mr-2 text-slate-400"></i>{{ t('pages.learning_paths.catalogTitle') }}</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-slate-50/50 custom-scrollbar">
                                    <div v-for="course in filteredAvailableCourses" :key="'avail_'+course.id" class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                                        <div class="flex-1 min-w-0 font-bold text-slate-700 text-sm truncate">{{ course.title }}</div>
                                        <Button icon="pi pi-plus" class="p-button-rounded p-button-text p-button-sm p-button-success" @click="addCourseToPath(course)" />
                                    </div>
                                </div>
                            </div>
                            <!-- PANE DROITE (SYLLABUS) -->
                            <div class="bg-white rounded-2xl border-2 border-blue-200 shadow-sm flex flex-col overflow-hidden relative">
                                <div class="p-4 border-b border-slate-100 bg-blue-50 flex items-center justify-between">
                                    <h3 class="font-black text-blue-900 text-sm"><i class="pi pi-list mr-2"></i>{{ t('pages.learning_paths.programTitle') }}</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto p-4 bg-slate-50 custom-scrollbar">
                                    <draggable v-model="pathSyllabusList" item-key="id" handle=".drag-handle" class="space-y-3">
                                        <template #item="{ element: course, index }">
                                            <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                                                <i class="pi pi-bars drag-handle text-slate-300 cursor-move"></i>
                                                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-black text-[10px] flex items-center justify-center">{{ index + 1 }}</div>
                                                <div class="flex-1 min-w-0 font-bold text-slate-800 text-sm truncate">{{ course.title }}</div>
                                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-sm p-button-danger" @click="removeCourseFromPath(course)" />
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ONGLETS 3, 4, 5 (Media, Settings, SEO) ... -->

                    <div v-show="activeTab === 'media'" class="max-w-4xl mx-auto space-y-8 animate-fade-in">
                        <!-- Vignette du Parcours -->
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-image"></i></div>
                                {{ t('pages.learning_paths.thumbnailTitle') }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                                <div class="aspect-video bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden relative group">
                                    <img v-if="thumbnailUrl" :src="thumbnailUrl" class="w-full h-full object-cover" />
                                    <div v-else class="text-slate-400 text-center">
                                        <i class="pi pi-cloud-upload text-4xl mb-2"></i>
                                        <p class="text-xs font-bold uppercase tracking-widest">{{ t('pages.learning_paths.preview169') }}</p>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <FileUpload mode="basic" auto accept="image/*" customUpload @select="onThumbnailSelect" :chooseLabel="t('pages.learning_paths.changeImage')" class="p-button-sm" />
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ t('pages.learning_paths.thumbnailDesc') }}</p>
                                    <ul class="text-xs text-slate-400 space-y-2">
                                        <li><i class="pi pi-check text-emerald-500 mr-2"></i> {{ t('pages.learning_paths.recFormatImage') }}</li>
                                        <li><i class="pi pi-check text-emerald-500 mr-2"></i> {{ t('pages.learning_paths.maxWeight') }}</li>
                                    </ul>
                                    <ProgressBar v-if="uploadProgress > 0" :value="uploadProgress" class="h-1" :showValue="false" />
                                </div>
                            </div>
                        </div>

                        <!-- Badge de réussite -->
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-verified"></i></div>
                                {{ t('pages.learning_paths.badgeTitle') }}
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-32 h-32 rounded-full bg-slate-50 border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden relative group">
                                        <img v-if="badgePreviewUrl" :src="badgePreviewUrl" class="w-24 h-24 object-contain drop-shadow-lg" />
                                        <div v-else class="text-slate-400 text-center">
                                            <i class="pi pi-shield text-4xl"></i>
                                        </div>
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <FileUpload mode="basic" auto accept="image/*" customUpload @select="onBadgeSelect" :chooseLabel="t('pages.learning_paths.badgeChoose')" class="p-button-sm p-button-rounded" />
                                        </div>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">{{ t('pages.learning_paths.badgePreviewLabel') }}</p>
                                </div>
                                <div class="space-y-4">
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ t('pages.learning_paths.badgeDesc') }}</p>
                                    <ul class="text-xs text-slate-400 space-y-2">
                                        <li><i class="pi pi-check text-emerald-500 mr-2"></i> {{ t('pages.learning_paths.recFormatBadge') }}</li>
                                        <li><i class="pi pi-check text-emerald-500 mr-2"></i> {{ t('pages.learning_paths.badgeStyle') }}</li>
                                    </ul>
                                    <ProgressBar v-if="badgeUploadProgress > 0" :value="badgeUploadProgress" class="h-1" :showValue="false" />
                                </div>
                            </div>
                        </div>
                    </div>


                    <div v-show="activeTab === 'settings'" class="max-w-4xl mx-auto space-y-6 animate-fade-in">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center"><i class="pi pi-cog"></i></div>
                                {{ t('pages.learning_paths.progressRules') }}
                            </h3>
                            <div class="space-y-6">
                                <div class="flex items-start justify-between p-4 rounded-lg border border-slate-100 bg-slate-50/50">
                                    <div>
                                        <label for="is_linear" class="font-bold text-slate-700">{{ t('pages.learning_paths.linearProgress') }}</label>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.learning_paths.linearProgressDesc') }}</p>
                                    </div>
                                    <InputSwitch v-model="pathForm.settings.is_linear" id="is_linear" />
                                </div>
                                <div class="flex items-start justify-between p-4 rounded-lg border border-slate-100 bg-slate-50/50">
                                    <div>
                                        <label for="allow_skip" class="font-bold text-slate-700">{{ t('pages.learning_paths.allowSkip') }}</label>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.learning_paths.allowSkipDesc') }}</p>
                                    </div>
                                    <InputSwitch v-model="pathForm.settings.allow_skip" id="allow_skip" :disabled="pathForm.settings.is_linear" />
                                </div>
                                <div class="flex items-start justify-between p-4 rounded-lg border border-slate-100 bg-slate-50/50">
                                    <div>
                                        <label for="show_certificate" class="font-bold text-slate-700">{{ t('pages.learning_paths.certificate') }}</label>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.learning_paths.certificateDesc') }}</p>
                                    </div>
                                    <InputSwitch v-model="pathForm.settings.show_certificate" id="show_certificate" />
                                </div>
                                <div class="flex items-center justify-between p-4 rounded-lg border border-slate-100 bg-slate-50/50">
                                    <div>
                                        <label for="expiration_days" class="font-bold text-slate-700">{{ t('pages.learning_paths.accessDuration') }}</label>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.learning_paths.accessDurationDesc') }}</p>
                                    </div>
                                    <InputNumber v-model="pathForm.settings.expiration_days" id="expiration_days" :placeholder="t('pages.learning_paths.infinitePlaceholder')" :min="0" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENU ONGLET 5: SEO -->
                    <div v-show="activeTab === 'seo'" class="max-w-4xl mx-auto space-y-6 animate-fade-in">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="text-lg font-black text-slate-800 mb-6 flex items-center gap-3 border-b border-slate-100 pb-4">
                                <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center"><i class="pi pi-google"></i></div>
                                {{ t('pages.learning_paths.seoTitle') }}
                            </h3>
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.seoTitleLabel') }}</label>
                                    <InputText v-model="pathForm.seo_metadata.title" class="w-full" :placeholder="t('pages.learning_paths.seoTitlePlaceholder')" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.seoDescLabel') }}</label>
                                    <Textarea v-model="pathForm.seo_metadata.description" class="w-full" rows="3" :placeholder="t('pages.learning_paths.seoDescPlaceholder')" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.learning_paths.keywordsLabel') }}</label>
                                    <InputText v-model="pathForm.seo_metadata.keywords" class="w-full" :placeholder="t('pages.learning_paths.keywordsPlaceholder')" />
                                    <p class="text-xs text-slate-500">{{ t('pages.learning_paths.keywordsHint') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER WORKSPACE ARCHITECTE -->
            <div class="flex-none h-[80px] bg-white border-t border-slate-200 flex justify-between items-center px-6 z-30 shadow-[0_-5px_15px_rgba(0,0,0,0.03)]">
                <div class="text-xs text-slate-500 font-medium flex items-center gap-2">
                    {{ t('pages.learning_paths.validationLabel') }}
                    <span v-if="isPathValid" class="text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100 flex items-center gap-1"><i class="pi pi-check"></i> {{ t('pages.learning_paths.readyToPublish') }}</span>
                    <span v-else class="text-red-600 bg-red-50 px-2 py-1 rounded border border-red-100 flex items-center gap-1"><i class="pi pi-times"></i> {{ t('pages.learning_paths.missingInfo') }}</span>
                </div>
                <div class="flex gap-3">
                    <Button :label="t('pages.learning_paths.btnSaveDraft')" icon="pi pi-save" class="p-button-outlined p-button-secondary font-bold bg-white" @click="savePath('draft')" :loading="pathForm.processing" />
                    <Button :label="t('pages.learning_paths.btnPublish')" icon="pi pi-rocket" class="p-button-primary bg-blue-600 border-blue-600 hover:bg-blue-700 font-bold px-8 shadow-lg shadow-violet-500/30 transition-transform hover:-translate-y-0.5" @click="savePath('publish')" :loading="pathForm.processing" :disabled="!isPathValid" />
                </div>
            </div>
        </Dialog>
</template>
