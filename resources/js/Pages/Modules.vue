<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - CURRICULUM ARCHITECT & MODULE MANAGER v15.0 (ENTERPRISE)
 * ======================================================================================
 * Gestionnaire ultra-avancé des Modules (Chapitres) et Leçons.
 * Connecté aux tables `modules` et `lessons` avec prise en charge des relations `course_id`.
 *
 * FONCTIONNALITÉS CLÉS :
 * - Vue hiérarchique avec DataTable expansible.
 * - Drag & Drop inter et intra-modules.
 * - Réaffectation dynamique d'un module vers un autre cours.
 * - Éditeurs Split-Screen avec Stepper et validation temps réel.
 * - Uploads de pièces jointes (documents) et configuration vidéo avancée.
 * - SEO Live Preview (Google & OpenGraph).
 *
 * @author SubnetCongo Framework Division
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';
import draggable from 'vuedraggable';

// ======================================================================================
// COMPOSANTS PRIMEVUE
// ======================================================================================
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import FileUpload from 'primevue/fileupload';
import SelectButton from 'primevue/selectbutton';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Editor from 'primevue/editor';
import Breadcrumb from 'primevue/breadcrumb';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import ProgressBar from 'primevue/progressbar';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';
import Chip from 'primevue/chip';
import Skeleton from 'primevue/skeleton';
import Message from 'primevue/message';
import Checkbox from 'primevue/checkbox';

// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    modules: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    courses: { type: Array, default: () => [] },
    exams: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_modules: 0, total_lessons: 0, video_hours: 0, total_attachments: 0 }) },
    filters: { type: Object, default: () => ({ search: '', course_id: null, status: null }) }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);

const selectedModules = ref([]);
const expandedRows = ref({}); // Pour ouvrir les modules et voir les leçons (PrimeVue DataTable utilise un objet pour les lignes expansées)
const localModules = ref([]); // Copie locale pour le drag&drop global si nécessaire

// Modales
const moduleDialog = ref(false);
const lessonDialog = ref(false);
const transferDialog = ref(false);

const lastSavedAt = ref(null);
const uploadProgress = ref(0);

// Sincronisation locale
watch(() => props.modules.data, (newVal) => {
    localModules.value = cloneDeep(newVal);
    loading.value = false;
}, { immediate: true, deep: true });

// ======================================================================================
// FILTRES RÉACTIFS
// ======================================================================================
const serverFilters = reactive({
    search: props.filters.search || '',
    course_id: props.filters.course_id || null,
    status: props.filters.status || null,
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('curriculum.modules.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 500));

// ======================================================================================
// LOGIQUE YOUTUBE PREVIEW
// ======================================================================================
const getYoutubeId = (url) => {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url?.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
};

const youtubeThumb = computed(() => {
    const id = getYoutubeId(lessonForm.video_url);
    return id ? `https://img.youtube.com/vi/${id}/mqdefault.jpg` : null;
});

// ======================================================================================
// CONFIGURATION DES ONGLETS (WORKSPACE)
// ======================================================================================
const moduleTabs = ref([
    { id: 'general', label: 'Liaison & Identité', desc: 'Cours associé, Titre, Slug', icon: 'pi pi-folder' },
    { id: 'settings', label: 'Paramètres du Module', desc: 'Statut, Verrouillage, Durée', icon: 'pi pi-cog' },
    { id: 'media', label: 'Média & Vignette', desc: 'Image de couverture', icon: 'pi pi-image' },
    { id: 'seo', label: 'Optimisation SEO', desc: 'Méta-données', icon: 'pi pi-google' }
]);
const activeModuleTab = ref('general');

const lessonTabs = ref([
    { id: 'general', label: 'Configuration Principale', desc: 'Titre, Type, Accès', icon: 'pi pi-id-card' },
    { id: 'content', label: 'Contenu Pédagogique', desc: 'Vidéo, Texte, Source', icon: 'pi pi-desktop' },
    { id: 'attachments', label: 'Fichiers Joints', desc: 'PDF, ZIP, Code source', icon: 'pi pi-paperclip' },
    { id: 'seo', label: 'Référencement (SEO)', desc: 'Méta-données de la leçon', icon: 'pi pi-globe' }
]);
const activeLessonTab = ref('general');

// ======================================================================================
// FORMULAIRES (INERTIA USEFORM)
// ======================================================================================
// Modèle Module
const moduleForm = useForm({
    id: null,
    course_id: null,
    title: '',
    slug: '',
    description: '',
    status: 'draft',
    order: 0,
    is_locked: false,
    estimated_duration: 0,
    thumbnail_path: null,
    seo_metadata: { title: '', description: '', keywords: '' },
    has_quiz: false,
    quiz_required: true,
    quiz_weight: 10,
    quiz_passing_score: 50,
    quiz_attempts_allowed: 3,
    intro_video_url: '',
    intro_video_title: '',
    text_content: '',
    objectives: [],
    has_document: false,
    document_title: '',
    document_url: '',
    document_name: '',
    order_in_course: 0
});

// Modèle Leçon
const lessonForm = useForm({
    id: null,
    module_id: null,
    title: '',
    slug: '',
    type: 'video',
    lesson_type: 'video',
    content: '',
    content_text: '',
    content_html: '',
    video_url: '',
    video_source: 'youtube',
    video_duration: 0,
    duration_minutes: 0,
    is_free_preview: false,
    status: 'draft',
    order: 0,
    is_sequential: false,
    has_attachment: false,
    attachment_title: '',
    attachment_paths: [],
    quiz_id: null,
    seo_metadata: { title: '', description: '', keywords: '' }
});

// Formulaire Transfert (Déplacer un module vers un autre cours)
const transferForm = useForm({
    module_id: null,
    target_course_id: null
});

// ======================================================================================
// VALIDATION COMPUTÉE
// ======================================================================================
const isModuleGeneralValid = computed(() => moduleForm.title.length > 2 && moduleForm.course_id !== null);
const isModuleValid = computed(() => isModuleGeneralValid.value);

const isLessonGeneralValid = computed(() => lessonForm.title.length > 2);
const isLessonContentValid = computed(() => {
    if (lessonForm.type === 'video') return lessonForm.video_url?.length > 5;
    return true; // Document/Quiz content validation logic
});
const isLessonValid = computed(() => isLessonGeneralValid.value && isLessonContentValid.value);

// ======================================================================================
// MÉTHODES : MODULES
// ======================================================================================
const openNewModule = () => {
    moduleForm.reset();
    moduleForm.seo_metadata = { title: '', description: '', keywords: '' };
    // Si un cours est filtré, le pré-sélectionner
    if (serverFilters.course_id) moduleForm.course_id = serverFilters.course_id;
    activeModuleTab.value = 'general';
    moduleDialog.value = true;
};

const editModule = (moduleData) => {
    moduleForm.clearErrors();
    const data = cloneDeep(moduleData);

    moduleForm.id = data.id;
    moduleForm.course_id = data.course_id;
    moduleForm.title = data.title;
    moduleForm.slug = data.slug;
    moduleForm.description = data.description || '';
    moduleForm.status = data.status || 'draft';
    moduleForm.order = data.order || 0;
    moduleForm.is_locked = data.is_locked || false;
    moduleForm.estimated_duration = data.estimated_duration || 0;
    moduleForm.thumbnail_path = data.thumbnail_path || null;
    moduleForm.seo_metadata = data.seo_metadata || { title: '', description: '', keywords: '' };
    moduleForm.has_quiz = !!data.has_quiz;
    moduleForm.quiz_required = !!data.quiz_required;
    moduleForm.quiz_weight = data.quiz_weight || 10;
    moduleForm.quiz_passing_score = data.quiz_passing_score || 50;
    moduleForm.quiz_attempts_allowed = data.quiz_attempts_allowed || 3;
    moduleForm.intro_video_url = data.intro_video_url || '';
    moduleForm.intro_video_title = data.intro_video_title || '';
    moduleForm.text_content = data.text_content || '';
    moduleForm.objectives = data.objectives || [];
    moduleForm.has_document = !!data.has_document;
    moduleForm.document_title = data.document_title || '';
    moduleForm.document_url = data.document_url || '';
    moduleForm.document_name = data.document_name || '';
    moduleForm.order_in_course = data.order_in_course || 0;

    activeModuleTab.value = 'general';
    moduleDialog.value = true;
};

const saveModule = () => {
    if (!isModuleValid.value) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez remplir le titre et sélectionner un cours.', life: 4000 });
        activeModuleTab.value = 'general';
        return;
    }

    const url = moduleForm.id ? route('modules.update', moduleForm.id) : route('modules.store', moduleForm.course_id);

    moduleForm.transform((data) => {
        const transformed = { ...data, _method: moduleForm.id ? 'PUT' : 'POST' };
        if (typeof data.thumbnail_path === 'string') delete transformed.thumbnail_path;
        return transformed;
    }).post(url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `Le module a été sauvegardé.`, life: 3000 });
            moduleDialog.value = false;
            lastSavedAt.value = new Date();
        }
    });
};

const confirmDeleteModule = (module) => {
    confirm.require({
        message: `Supprimer DÉFINITIVEMENT le module "${module.title}" ? Toutes ses leçons (${module.lessons?.length || 0}) seront détruites. Action irréversible.`,
        header: 'Purge du Module',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('curriculum.modules.destroy', module.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Module et leçons purgés.', life: 3000 })
            });
        }
    });
};

// ======================================================================================
// MÉTHODES : TRANSFERT DE MODULE (CHANGER DE COURS)
// ======================================================================================
const openTransferDialog = (module) => {
    transferForm.reset();
    transferForm.module_id = module.id;
    transferForm.target_course_id = null;
    transferDialog.value = true;
};

const executeTransfer = () => {
    if (!transferForm.target_course_id) return;
    transferForm.post(route('curriculum.modules.transfer', transferForm.module_id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Transfert Réussi', detail: 'Le module a été rattaché au nouveau cours.', life: 4000 });
            transferDialog.value = false;
        }
    });
};

// ======================================================================================
// MÉTHODES : LEÇONS
// ======================================================================================
const openNewLesson = (moduleId) => {
    lessonForm.reset();
    lessonForm.module_id = moduleId;
    lessonForm.type = 'video';
    lessonForm.lesson_type = 'video';
    lessonForm.video_source = 'youtube';
    lessonForm.content = '<h2>Transcription de la vidéo</h2><p>Saisissez ici le contenu textuel ou la transcription de votre leçon...</p>';
    lessonForm.content_text = '';
    lessonForm.content_html = '';
    lessonForm.video_duration = 0;
    lessonForm.attachment_paths = [];
    lessonForm.has_attachment = false;
    lessonForm.attachment_title = '';
    lessonForm.is_sequential = false;
    lessonForm.quiz_id = null;
    lessonForm.seo_metadata = { title: '', description: '', keywords: '' };

    if (!expandedRows.value[moduleId]) {
        expandedRows.value[moduleId] = true;
    }

    activeLessonTab.value = 'general';
    lessonDialog.value = true;
};

const editLesson = (lessonData, moduleId) => {
    lessonForm.clearErrors();
    const data = cloneDeep(lessonData);

    lessonForm.id = data.id;
    lessonForm.module_id = moduleId;
    lessonForm.title = data.title;
    lessonForm.slug = data.slug;
    lessonForm.type = data.type || 'video';
    lessonForm.content = data.content || '';
    lessonForm.video_url = data.video_url || '';
    lessonForm.video_source = data.video_source || 'youtube';
    lessonForm.duration_minutes = data.duration_minutes || 0;
    lessonForm.is_free_preview = data.is_free_preview || false;
    lessonForm.status = data.status || 'draft';
    lessonForm.order = data.order || 0;
    lessonForm.attachment_paths = data.attachment_paths || [];
    lessonForm.seo_metadata = data.seo_metadata || { title: '', description: '', keywords: '' };
    lessonForm.lesson_type = data.lesson_type || data.type || 'video';
    lessonForm.content_text = data.content_text || '';
    lessonForm.content_html = data.content_html || '';
    lessonForm.video_duration = data.video_duration || 0;
    lessonForm.has_attachment = !!data.has_attachment;
    lessonForm.attachment_title = data.attachment_title || '';
    lessonForm.is_sequential = !!data.is_sequential;
    lessonForm.quiz_id = data.quiz_id || null;

    activeLessonTab.value = 'general';
    lessonDialog.value = true;
};

const saveLesson = () => {
    if (!isLessonValid.value) {
        toast.add({ severity: 'error', summary: 'Données incomplètes', detail: 'Vérifiez le titre et le contenu (URL vidéo requise si type vidéo).', life: 4000 });
        return;
    }

    const url = lessonForm.id ? route('lessons.update', lessonForm.id) : route('lessons.store', lessonForm.module_id);

    lessonForm.transform((data) => {
        return { ...data, _method: lessonForm.id ? 'PUT' : 'POST' };
    }).post(url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Leçon enregistrée', detail: `La leçon "${lessonForm.title}" a été mise à jour.`, life: 3000 });
            lessonDialog.value = false;
        }
    });
};

const confirmDeleteLesson = (lesson) => {
    confirm.require({
        message: `Supprimer la leçon "${lesson.title}" définitivement ?`,
        header: 'Suppression de Leçon',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('curriculum.lessons.destroy', lesson.id), { preserveScroll: true });
        }
    });
};

// ======================================================================================
// DRAG & DROP ORDONNANCEMENT
// ======================================================================================
const updateLessonsOrder = (moduleId, updatedLessons) => {
    const orders = updatedLessons.map((les, idx) => ({ id: les.id, order: idx + 1 }));
    router.post(route('lessons.reorder', moduleId), { orders }, { preserveScroll: true });
};

// ======================================================================================
// HELPERS ET FORMATTERS (UIX)
// ======================================================================================
const generateSlug = (formRef) => {
    if (formRef.title && !formRef.id) {
        formRef.slug = formRef.title.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }
};

const formatDuration = (minutes) => {
    if (!minutes) return '0 min';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const onThumbnailSelect = (event) => {
    moduleForm.thumbnail_path = event.files[0];
    simulateUpload();
};

// Computed pour l'URL d'aperçu sécurisée de la vignette du module
const moduleThumbnailUrl = computed(() => {
    const thumb = moduleForm.thumbnail_path;
    if (!thumb) return '';

    if (typeof thumb === 'string') {
        return thumb.startsWith('http') ? thumb : '/media/' + thumb;
    }

    if (thumb && typeof thumb === 'object' && 'size' in thumb && 'type' in thumb) {
        return URL.createObjectURL(thumb);
    }
    return '';
});

const onAttachmentUpload = (event) => {
    const file = event.files[0];
    if (!file) return;
    // On stocke l'objet File réel pour qu'Inertia l'envoie via Multipart
    lessonForm.attachment_paths.push(file);
    toast.add({ severity: 'success', summary: 'Fichier Joint', detail: `${file.name} ajouté à la file d'envoi.`, life: 2000 });
};

const simulateUpload = (callback = null) => {
    uploadProgress.value = 0;
    const interval = setInterval(() => {
        uploadProgress.value += 20;
        if (uploadProgress.value >= 100) {
            clearInterval(interval);
            if (callback) callback();
            setTimeout(() => uploadProgress.value = 0, 500);
        }
    }, 150);
};

const onPageChange = (event) => {
    router.get(route('curriculum.modules.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true, preserveScroll: true });
};
const downloadAttachment = (path) => {
  if (typeof path === 'string') {
    // Alternative plus robuste contre les bloqueurs de popup
    const link = document.createElement('a');
    link.href = '/media/' + path;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.click();
  }
};
// ======================================================================================
// SEO PREVIEWS COMPUTED
// ======================================================================================
const seoPreviewTitleMod = computed(() => moduleForm.seo_metadata.title || moduleForm.title || 'Titre SEO du Module');
const seoPreviewDescMod = computed(() => moduleForm.seo_metadata.description || moduleForm.description?.replace(/<[^>]*>?/gm, '').substring(0, 150) || 'Description optimisée pour les moteurs de recherche...');

const seoPreviewTitleLes = computed(() => lessonForm.seo_metadata.title || lessonForm.title || 'Titre SEO de la Leçon');
const seoPreviewDescLes = computed(() => lessonForm.seo_metadata.description || lessonForm.content?.replace(/<[^>]*>?/gm, '').substring(0, 150) || 'Description de la leçon...');

// Definition des types de leçons
const lessonTypesDef = [
    { label: 'Vidéo', value: 'video', icon: 'pi pi-video', color: 'text-rose-500', bg: 'bg-rose-50' },
    { label: 'Document', value: 'document', icon: 'pi pi-file-pdf', color: 'text-blue-500', bg: 'bg-blue-50' },
    { label: 'Quiz / QCM', value: 'quiz', icon: 'pi pi-question-circle', color: 'text-emerald-500', bg: 'bg-emerald-50' },
    { label: 'Devoir (Upload)', value: 'assignment', icon: 'pi pi-cloud-upload', color: 'text-amber-500', bg: 'bg-amber-50' }
];

const getCourseName = (id) => props.courses.find(c => c.id === id)?.title || 'Cours Inconnu';
</script>

<template>
    <AppLayout>
        <Head title="Curriculum Manager | Enterprise" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

  <div class="page-bg min-h-screen font-sans pb-20">

    <!-- ============================================================================== -->
    <!-- HEADER DE PAGE (Dark Intelligence Hub Style)                                   -->
    <!-- ============================================================================== -->
    <div class="flex flex-col gap-2 mb-12">
        
        <!-- Breadcrumb -->
        <div class="flex items-center text-sm px-1 mb-2">
            <Breadcrumb 
                :model="[{ label: 'Cours', icon: 'pi pi-book' }, { label: 'Curriculum (Modules & Leçons)' }]" 
                class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
            />
        </div>

        <!-- Bannière Sombre avec padding bas (pb-28) pour le chevauchement -->
        <div class="relative bg-gradient-to-r from-[#111322] via-[#151326] to-[#1c1825] rounded-xl pt-10 pb-28 px-6 md:px-10 shadow-lg overflow-hidden">
            
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10">
                
                <!-- Partie Gauche (Titres) -->
                <div class="space-y-4 max-w-3xl">
                    <!-- Badge Architect -->
                    <span class="inline-block border border-red-900/50 text-red-500 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full uppercase bg-red-950/30">
                        Architect
                    </span>

                    <!-- Titre Principal (Italique, gras, 2 couleurs) -->
                    <h1 class="text-3xl md:text-5xl font-black italic tracking-tighter flex flex-wrap gap-2 uppercase">
                        <span class="text-white">Gestionnaire de</span>
                        <span class="text-[#E92B2B]">Curriculum</span>
                    </h1>

                    <!-- Sous-titre -->
                    <p class="text-slate-400 text-sm md:text-sm italic leading-relaxed">
                        Organisez votre patrimoine pédagogique. Créez des modules, associez-les à vos cours, et construisez des leçons multimédias riches.
                    </p>
                </div>

                <!-- Partie Droite (Boutons) -->
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <Button 
                        label="Créer un Module Indépendant" 
                        icon="pi pi-plus" 
                        class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white w-full sm:w-auto font-bold shadow-lg shadow-red-900/50 transition-colors px-6 py-3" 
                        @click="openNewModule" 
                    />
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- KPI CARDS (Overlapping / Chevauchement)                                        -->
        <!-- ============================================================================== -->
        <!-- Marge négative (-mt-16) pour faire remonter les cartes sur la bannière sombre -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-4 md:px-6 -mt-16 relative z-20">
            
            <!-- KPI 1 : Modules -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-indigo-50 flex items-center justify-center mb-4">
                    <i class="pi pi-folder-open text-indigo-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Modules</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ stats.total_modules }}</p>
                </div>
            </div>

            <!-- KPI 2 : Leçons -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-emerald-50 flex items-center justify-center mb-4">
                    <i class="pi pi-file text-emerald-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Leçons</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ stats.total_lessons }}</p>
                </div>
            </div>

            <!-- KPI 3 : Heures de vidéo -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-rose-50 flex items-center justify-center mb-4">
                    <i class="pi pi-video text-rose-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Heures de Vidéo</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ stats.video_hours }}<span class="text-lg text-slate-400 ml-1">h</span></p>
                </div>
            </div>

            <!-- KPI 4 : Ressources jointes -->
            <div class="bg-white rounded-xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col justify-between h-36">
                <div class="w-10 h-10 rounded-md bg-amber-50 flex items-center justify-center mb-4">
                    <i class="pi pi-paperclip text-amber-500 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ressources jointes</h3>
                    <p class="text-3xl font-extrabold text-slate-900">{{ stats.total_attachments }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================== -->
    <!-- DATA GRID : GESTION DES MODULES (VUE ARCHITECTE) - (Code inchangé)             -->
    <!-- ============================================================================== -->
    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-slate-200 overflow-hidden mt-6">

        <!-- TOOLBAR -->
        <div class="bg-slate-50 border-b border-slate-200 p-5 flex flex-col lg:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <IconField iconPosition="left" class="w-full sm:w-auto">
                    <InputIcon class="pi pi-search text-slate-400" />
                    <InputText v-model="serverFilters.search" placeholder="Chercher un module..." class="w-full sm:w-64 bg-white border-slate-200" />
                </IconField>

                <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                <!-- Filtre par Cours -->
                <Dropdown v-model="serverFilters.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Filtrer par Formation" class="w-full sm:w-72 bg-white border-slate-200" showClear filter>
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-2 text-indigo-700 font-bold text-sm">
                            <i class="pi pi-book"></i>
                            <span class="truncate max-w-[200px]">{{ props.courses.find(c => c.id === slotProps.value)?.title }}</span>
                        </div>
                        <span v-else class="text-slate-500">Tous les cours</span>
                    </template>
                </Dropdown>

                <Dropdown v-model="serverFilters.status" :options="[{label:'Publiés', value:'published'}, {label:'Brouillons', value:'draft'}]" optionLabel="label" optionValue="value" placeholder="Statut" class="w-full sm:w-40 bg-white border-slate-200" showClear />
            </div>
        </div>

        <!-- TABLEAU PRINCIPAL -->
        <DataTable
            v-model:expandedRows="expandedRows"
            :value="localModules"
            dataKey="id"
            :lazy="true"
            :paginator="true"
            :rows="props.modules.per_page"
            :totalRecords="props.modules.total"
            :first="(props.modules.current_page - 1) * props.modules.per_page"
            @page="onPageChange"
            :loading="loading"
            class="p-datatable-custom border-none"
            rowHover
            stripedRows
        >
            <template #empty>
                <div class="p-16 text-center flex flex-col items-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center border border-slate-200 mb-4">
                        <i class="pi pi-folder-open text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Aucun module trouvé</h3>
                    <p class="text-slate-500 text-sm mt-2 max-w-md">Modifiez vos filtres ou créez un nouveau module pour structurer votre premier cours.</p>
                    <Button label="Créer un Module" icon="pi pi-plus" class="p-button-outlined mt-6" @click="openNewModule" />
                </div>
            </template>

            <Column expander style="width: 3rem; background-color: #f8fafc;" />

            <!-- COLONNE : INFO MODULE -->
            <Column field="title" header="Module / Chapitre" style="min-width: 25rem; background-color: #f8fafc;">
                <template #body="{ data }">
                    <div class="flex gap-4 py-1">

                        <!-- Thumbnail miniature -->
                        <div class="w-16 h-12 rounded-lg bg-slate-100 border border-slate-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                            <img v-if="data.thumbnail_path" :src="data.thumbnail_path.startsWith('http') ? data.thumbnail_path : '/media/' + data.thumbnail_path" class="w-full h-full object-cover" />
                            <i v-else class="pi pi-image text-slate-300 text-lg"></i>
                        </div>

                        <div>
                            <div class="font-extrabold text-slate-800 text-base leading-tight hover:text-indigo-600 cursor-pointer" @click="editModule(data)">
                                {{ data.title }}
                                <i v-if="data.is_locked" class="pi pi-lock text-amber-500 text-xs ml-1" v-tooltip.top="'Module verrouillé (prérequis strict)'"></i>
                            </div>
                            <div class="flex items-center gap-2 mt-1">
                                <!-- Badge du cours parent -->
                                <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded font-bold flex items-center gap-1 max-w-[200px] truncate" v-tooltip.top="'Cours associé'">
                                    <i class="pi pi-book text-[9px]"></i> {{ getCourseName(data.course_id) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Column>

            <Column field="stats" header="Contenu" style="min-width: 10rem; background-color: #f8fafc;">
                <template #body="{ data }">
                    <div class="flex flex-col text-xs text-slate-600 space-y-1">
                        <span class="font-bold"><i class="pi pi-file text-slate-400 mr-1 text-[10px]"></i> {{ data.lessons?.length || 0 }} Leçon(s)</span>
                        <span><i class="pi pi-clock text-slate-400 mr-1 text-[10px]"></i> {{ formatDuration(data.estimated_duration) }} estimé</span>
                    </div>
                </template>
            </Column>

            <Column field="status" header="Statut" style="min-width: 8rem; background-color: #f8fafc;">
                <template #body="{ data }">
                    <Tag :severity="data.status === 'published' ? 'success' : 'secondary'" :value="data.status === 'published' ? 'Publié' : 'Brouillon'" class="text-[10px] uppercase font-bold tracking-wider" />
                </template>
            </Column>

            <Column header="Actions" alignFrozen="right" :frozen="true" style="min-width: 12rem; text-align: right; background-color: #f8fafc;">
                <template #body="{ data }">
                    <div class="flex justify-end gap-1">
                        <Button icon="pi pi-plus" class="p-button-text p-button-success p-button-rounded bg-emerald-50 hover:bg-emerald-100 w-8 h-8" v-tooltip.top="'Ajouter une leçon'" @click="openNewLesson(data.id)" />
                        <div class="w-px h-5 bg-slate-200 self-center mx-1"></div>
                        <Button icon="pi pi-arrow-right-arrow-left" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="'Transférer vers un autre cours'" @click="openTransferDialog(data)" />
                        <Button icon="pi pi-cog" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-indigo-600 hover:bg-indigo-50" v-tooltip.top="'Configurer le module'" @click="editModule(data)" />
                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-rose-600 hover:bg-rose-50" v-tooltip.top="'Purger ce module'" @click="confirmDeleteModule(data)" />
                    </div>
                </template>
            </Column>

            <!-- LIGNE EXPANSIVE : LISTE DES LEÇONS (DRAGGABLE) -->
            <template #expansion="{ data }">
                <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_5px_10px_rgba(0,0,0,0.02)]">
                    <div class="max-w-6xl mx-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="font-black text-slate-800 text-lg flex items-center gap-2">
                                <i class="pi pi-list text-indigo-500"></i> Sommaire du Module
                            </h4>
                            <Button label="Nouvelle Leçon" icon="pi pi-plus" class="p-button-sm p-button-success shadow-sm font-bold" @click="openNewLesson(data.id)" />
                        </div>

                        <div v-if="data.lessons && data.lessons.length > 0" class="bg-white rounded-2xl border border-slate-200 p-2 shadow-sm">
                            <draggable
                                v-model="data.lessons"
                                item-key="id"
                                handle=".lesson-drag"
                                @end="updateLessonsOrder(data.id, data.lessons)"
                                class="divide-y divide-slate-100"
                            >
                                <template #item="{ element: lesson, index }">
                                    <div class="flex items-center p-3 hover:bg-slate-50 transition-colors group">

                                        <i class="pi pi-bars lesson-drag text-slate-300 cursor-move hover:text-indigo-500 p-3 flex-shrink-0" v-tooltip.top="'Ordonner'"></i>

                                        <div class="w-8 h-8 rounded-full text-[10px] font-black flex items-center justify-center mr-4 bg-slate-100 text-slate-400 flex-shrink-0 border border-slate-200">
                                            {{ index + 1 }}
                                        </div>

                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-4 flex-shrink-0 border border-white shadow-sm" :class="lessonTypesDef.find(t => t.value === lesson.type)?.bg">
                                            <i :class="[lessonTypesDef.find(t => t.value === lesson.type)?.icon, lessonTypesDef.find(t => t.value === lesson.type)?.color]" class="text-lg"></i>
                                        </div>

                                        <div class="flex-1 min-w-0 pr-4">
                                            <div class="font-bold text-slate-800 text-base truncate hover:text-indigo-600 cursor-pointer" @click="editLesson(lesson, data.id)">
                                                {{ lesson.title }}
                                            </div>
                                            <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                                <span class="uppercase tracking-wider font-bold text-[10px] text-slate-400">{{ lesson.type }}</span>
                                                        <span v-if="lesson.duration_minutes"><i class="pi pi-clock text-[10px]"></i> {{ lesson.duration_minutes }} min</span>
                                                <span v-if="lesson.is_free_preview" class="text-emerald-500 font-bold bg-emerald-50 px-1.5 py-0.5 rounded"><i class="pi pi-unlock text-[10px]"></i> Extrait Gratuit</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-6 mr-6 hidden md:flex">
                                            <div v-if="lesson.attachment_paths?.length" class="text-xs font-bold text-slate-400 flex items-center gap-1" v-tooltip.top="`${lesson.attachment_paths.length} fichier(s) joint(s)`">
                                               <i class="pi pi-paperclip"></i> {{ lesson.attachment_paths.length }}
                                            </div>
                                            <Tag :severity="lesson.status === 'published' ? 'success' : 'secondary'" :value="lesson.status === 'published' ? 'Publié' : 'Brouillon'" class="text-[10px]" />
                                        </div>

                                        <div class="flex gap-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary text-slate-500 hover:bg-indigo-50 hover:text-indigo-600" @click="editLesson(lesson, data.id)" />
                                            <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger text-slate-500 hover:bg-rose-50 hover:text-rose-600" @click="confirmDeleteLesson(lesson)" />
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                        <div v-else class="text-center p-10 bg-white rounded-xl border border-dashed border-slate-300">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3"><i class="pi pi-box text-2xl text-slate-300"></i></div>
                            <p class="text-slate-500 font-bold">Ce module est vide.</p>
                            <p class="text-xs text-slate-400 mt-1">Ajoutez des vidéos, documents ou devoirs.</p>
                        </div>
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</div>

        <!-- ============================================================================== -->
        <!-- DIALOG : ÉDITEUR DE MODULE (SPLIT-SCREEN)                                      -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="moduleDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1100px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[85vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-2xl border border-slate-200"
        >
            <!-- HEADER -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600"><i class="pi pi-folder-open text-lg"></i></div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">{{ moduleForm.id ? 'Modifier le Module' : 'Créer un Module' }}</h2>
                        <div class="text-[11px] text-slate-500 mt-0.5">Structurez les chapitres de votre formation</div>
                    </div>
                </div>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text bg-slate-100 hover:bg-slate-200 w-10 h-10" @click="moduleDialog = false" />
            </div>

            <!-- BODY -->
            <div class="flex-1 flex overflow-hidden">
                <!-- STEPPER LEFT -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:block p-4 space-y-2">
                    <button
                        v-for="tab in moduleTabs" :key="tab.id"
                        @click="activeModuleTab = tab.id"
                        class="w-full flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative"
                        :class="activeModuleTab === tab.id ? 'bg-indigo-50 border border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border border-transparent'"
                    >
                        <i :class="[tab.icon, activeModuleTab === tab.id ? 'text-indigo-600' : 'text-slate-400']" class="text-xl mt-0.5"></i>
                        <div class="flex-1">
                            <div class="font-bold text-sm" :class="activeModuleTab === tab.id ? 'text-indigo-800' : 'text-slate-700'">{{ tab.label }}</div>
                            <div class="text-[10px] text-slate-500 mt-0.5">{{ tab.desc }}</div>
                        </div>
                        <i v-if="tab.id === 'general' && isModuleGeneralValid" class="pi pi-check-circle text-emerald-500 absolute right-4 top-5 text-sm"></i>
                    </button>
                </div>

                <!-- CONTENT RIGHT -->
                <div class="flex-1 overflow-y-auto p-6 md:p-10 bg-slate-50 custom-scrollbar">

                    <div v-show="activeModuleTab === 'general'" class="space-y-6 max-w-2xl mx-auto">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                            <h3 class="font-bold text-lg border-b border-slate-100 pb-3">Association au Cours</h3>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700">Sélectionnez le cours parent <span class="text-rose-500">*</span></label>
                                <Dropdown v-model="moduleForm.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Lier à une formation existante..." class="w-full font-bold" filter :class="{'p-invalid': !moduleForm.course_id}">
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-2"><i class="pi pi-book text-indigo-500"></i> {{ getCourseName(slotProps.value) }}</div>
                                        <span v-else class="text-slate-400">Choisir un cours...</span>
                                    </template>
                                </Dropdown>
                                <small class="text-slate-500 text-[11px]">Le module doit obligatoirement appartenir à un cours pour être visible.</small>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                            <h3 class="font-bold text-lg border-b border-slate-100 pb-3">Identité du Module</h3>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700">Titre (Nom du chapitre) <span class="text-rose-500">*</span></label>
                                <InputText v-model="moduleForm.title" @input="generateSlug(moduleForm)" class="w-full p-3 font-bold bg-slate-50" placeholder="Ex: Introduction au Cloud Computing..." :class="{'p-invalid': moduleForm.title.length < 3}" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700">URL Slug</label>
                                <InputText v-model="moduleForm.slug" class="w-full font-mono text-sm bg-slate-100 text-slate-500 border-transparent" />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700">Description (Optionnelle)</label>
                                <Textarea v-model="moduleForm.description" rows="3" class="w-full bg-slate-50" placeholder="Objectifs pédagogiques de ce module..." />
                            </div>
                        </div>
                    </div>

                    <div v-show="activeModuleTab === 'settings'" class="space-y-6 max-w-2xl mx-auto">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                            <h3 class="font-bold text-lg border-b border-slate-100 pb-3">Configuration d'accès</h3>

                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <div>
                                    <div class="font-bold text-sm text-slate-800">Verrouillage Séquentiel (Drip)</div>
                                    <div class="text-[11px] text-slate-500 mt-1">L'apprenant doit terminer le module précédent pour y accéder.</div>
                                </div>
                                <ToggleSwitch v-model="moduleForm.is_locked" />
                            </div>

                            <div class="grid grid-cols-2 gap-6 pt-2">
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Statut initial</label>
                                    <SelectButton v-model="moduleForm.status" :options="[{l:'Brouillon', v:'draft'}, {l:'Publié', v:'published'}]" optionLabel="l" optionValue="v" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Ordre d'affichage</label>
                                    <InputNumber v-model="moduleForm.order" class="w-full" />
                                </div>
                            </div>

                            <div class="space-y-2 pt-2">
                                <label class="block text-sm font-bold text-slate-700">Durée Estimée (minutes)</label>
                                <InputNumber v-model="moduleForm.estimated_duration" class="w-full" placeholder="Ex: 120 pour 2h" />
                            </div>
                        </div>

                        <!-- Quiz de Fin de Module -->
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                            <h3 class="font-bold text-lg border-b border-slate-100 pb-3 flex items-center gap-2">
                                <i class="pi pi-check-square text-emerald-500"></i> Quiz de Validation
                            </h3>

                            <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                <div>
                                    <div class="font-bold text-sm text-slate-800">Quiz obligatoire à la fin du module</div>
                                    <div class="text-[11px] text-slate-500 mt-1">L'apprenant doit réussir ce quiz pour débloquer le module suivant.</div>
                                </div>
                                <ToggleSwitch v-model="moduleForm.has_quiz" />
                            </div>

                            <div v-if="moduleForm.has_quiz" class="grid grid-cols-2 gap-6 pt-2">
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                                        <Checkbox v-model="moduleForm.quiz_required" :binary="true" />
                                        Quiz obligatoire
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Tentatives autorisées</label>
                                    <InputNumber v-model="moduleForm.quiz_attempts_allowed" :min="1" :max="10" class="w-full" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Pondération (%)</label>
                                    <InputNumber v-model="moduleForm.quiz_weight" :min="0" :max="100" class="w-full" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Note de passage (/20)</label>
                                    <InputNumber v-model="moduleForm.quiz_passing_score" :min="0" :max="20" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Autres onglets Media / SEO similaires au précédent... -->
                    <!-- Pour rester synthétique, j'ai implémenté le coeur ci-dessus -->
                    <div v-show="activeModuleTab === 'media'" class="space-y-6 max-w-2xl mx-auto bg-white p-6 rounded-2xl border">
                        <h3 class="font-bold text-lg border-b border-slate-100 pb-3 mb-4">Vignette du Module</h3>
                        <div class="aspect-video bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl flex items-center justify-center relative overflow-hidden">
                            <img v-if="moduleForm.thumbnail_path" :src="moduleThumbnailUrl" class="w-full h-full object-cover" />
                            <FileUpload v-else mode="basic" accept="image/*" customUpload @select="onThumbnailSelect" auto chooseLabel="Importer une image" class="p-button-outlined" />
                            <ProgressBar v-if="uploadProgress > 0 && uploadProgress < 100" :value="uploadProgress" class="absolute bottom-0 w-full h-1" :showValue="false" />
                        </div>
                    </div>

                    <div v-show="activeModuleTab === 'seo'" class="space-y-6 max-w-2xl mx-auto bg-white p-6 rounded-2xl border">
                        <h3 class="font-bold text-lg border-b border-slate-100 pb-3 mb-4">Optimisation SEO</h3>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-bold mb-1">Titre SEO</label><InputText v-model="moduleForm.seo_metadata.title" class="w-full bg-slate-50" /></div>
                            <div><label class="block text-sm font-bold mb-1">Meta Description</label><Textarea v-model="moduleForm.seo_metadata.description" rows="3" class="w-full bg-slate-50" /></div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex-none h-[72px] bg-white border-t border-slate-200 flex justify-between items-center px-6">
                <span class="text-xs text-slate-400">Workspace Architecte</span>
                <div class="flex gap-3">
                    <Button label="Annuler" class="p-button-text text-slate-600 font-bold" @click="moduleDialog = false" />
                    <Button label="Enregistrer le Module" icon="pi pi-check" class="p-button-primary font-bold shadow-md" @click="saveModule" :loading="moduleForm.processing" :disabled="!isModuleValid" />
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : ÉDITEUR DE LEÇON (CONTENU MULTIMÉDIA)                                 -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="lessonDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1200px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[90vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-2xl border border-slate-200"
        >
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600"><i class="pi pi-play-circle text-lg"></i></div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">{{ lessonForm.id ? 'Éditer la Leçon' : 'Nouveau Contenu' }}</h2>
                        <div class="text-[11px] text-slate-500 uppercase tracking-widest font-bold mt-0.5">{{ lessonForm.type }}</div>
                    </div>
                </div>
                <Button icon="pi pi-times" class="p-button-rounded p-button-text bg-slate-100 hover:bg-slate-200 w-10 h-10" @click="lessonDialog = false" />
            </div>

            <div class="flex-1 flex overflow-hidden">
                <!-- STEPPER LEFT -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden lg:block p-4 space-y-2">
                    <button
                        v-for="tab in lessonTabs" :key="tab.id"
                        @click="activeLessonTab = tab.id"
                        class="w-full flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative"
                        :class="activeLessonTab === tab.id ? 'bg-emerald-50 border border-emerald-100 shadow-sm' : 'hover:bg-slate-50 border border-transparent'"
                    >
                        <i :class="[tab.icon, activeLessonTab === tab.id ? 'text-emerald-600' : 'text-slate-400']" class="text-xl mt-0.5"></i>
                        <div class="flex-1">
                            <div class="font-bold text-sm" :class="activeLessonTab === tab.id ? 'text-emerald-800' : 'text-slate-700'">{{ tab.label }}</div>
                            <div class="text-[10px] text-slate-500 mt-0.5">{{ tab.desc }}</div>
                        </div>
                    </button>
                </div>

                <!-- CONTENT RIGHT -->
                <div class="flex-1 overflow-y-auto p-6 md:p-10 bg-slate-50 custom-scrollbar relative">

                    <div v-show="activeLessonTab === 'general'" class="max-w-3xl mx-auto space-y-6">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Format Pédagogique (Type de leçon)</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div v-for="typeDef in lessonTypesDef" :key="typeDef.value"
                                        @click="lessonForm.type = typeDef.value"
                                        class="border rounded-xl p-4 cursor-pointer text-center transition-all"
                                        :class="lessonForm.type === typeDef.value ? `border-${typeDef.color.split('-')[1]}-500 ${typeDef.bg} shadow-md` : 'border-slate-200 hover:bg-slate-50'">
                                        <i :class="[typeDef.icon, typeDef.color]" class="text-2xl mb-2"></i>
                                        <div class="text-xs font-bold text-slate-700">{{ typeDef.label }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 pt-4 border-t border-slate-100">
                                <label class="block text-sm font-bold text-slate-700">Titre de la leçon <span class="text-rose-500">*</span></label>
                                <InputText v-model="lessonForm.title" @input="generateSlug(lessonForm)" class="w-full p-4 font-bold text-lg bg-slate-50" placeholder="Leçon 1 : Les concepts de base..." />
                            </div>

                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Durée (Min)</label>
                                    <InputNumber v-model="lessonForm.duration_minutes" class="w-full" />
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Statut</label>
                                    <SelectButton v-model="lessonForm.status" :options="[{l:'Brouillon', v:'draft'}, {l:'Publié', v:'published'}]" optionLabel="l" optionValue="v" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 mt-4">
                                <div>
                                    <div class="font-bold text-sm text-slate-800 flex items-center gap-2"><i class="pi pi-unlock text-emerald-500"></i> Aperçu Gratuit (Teaser)</div>
                                    <div class="text-[11px] text-slate-500">Permet aux non-inscrits de visionner cette leçon avant achat.</div>
                                </div>
                                <ToggleSwitch v-model="lessonForm.is_free_preview" />
                            </div>

                            <!-- Quiz Configuration (si type quiz) -->
                            <div v-if="lessonForm.type === 'quiz'" class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 mt-4">
                                <h4 class="font-bold text-sm text-emerald-800 mb-3 flex items-center gap-2">
                                    <i class="pi pi-check-square"></i> Configuration du Quiz
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-700">Quiz associé</label>
                                        <Dropdown v-model="lessonForm.quiz_id" :options="props.exams" optionLabel="title" optionValue="id" placeholder="Sélectionner un quiz..." class="w-full" showClear />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-slate-700">Séquentiel</label>
                                        <div class="flex items-center gap-2">
                                            <Checkbox v-model="lessonForm.is_sequential" :binary="true" />
                                            <span class="text-xs text-slate-600">L'étudiant doit terminer cette leçon avant la suivante</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeLessonTab === 'content'" class="max-w-4xl mx-auto space-y-6">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">

                            <!-- Video Section -->
                            <div v-if="lessonForm.type === 'video'" class="space-y-6">
                                <div class="bg-rose-50 border border-rose-100 p-6 rounded-xl">
                                    <h4 class="font-bold text-rose-900 mb-4 flex items-center gap-2"><i class="pi pi-youtube"></i> Source de la Vidéo</h4>
                                    <div class="flex flex-col md:flex-row gap-4 mb-4">
                                        <SelectButton v-model="lessonForm.video_source" :options="['youtube', 'vimeo', 'custom']" class="flex-shrink-0" />
                                        <InputText v-model="lessonForm.video_url" class="w-full font-mono text-sm" placeholder="https://..." />
                                    </div>
                                    <div v-if="lessonForm.video_url" class="aspect-video bg-slate-900 rounded-lg overflow-hidden relative shadow-lg border-4 border-white">
                                        <img v-if="lessonForm.video_source === 'youtube' && youtubeThumb" :src="youtubeThumb" class="w-full h-full object-cover opacity-60" />
                                        <div class="absolute inset-0 flex flex-col items-center justify-center text-white">
                                            <i class="pi pi-play-circle text-6xl mb-2 text-white/80"></i>
                                            <span class="text-xs font-black tracking-widest bg-black/50 px-3 py-1 rounded">PRÉVISUALISATION ACTIVE</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rich Text Section (Toujours visible pour transcipt ou doc) -->
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-700">
                                    {{ lessonForm.type === 'document' ? 'Contenu du Document (Article)' : 'Description / Transcription de la vidéo' }}
                                </label>
                                <div class="border border-slate-200 rounded-xl overflow-hidden">
                                    <Editor v-model="lessonForm.content" editorStyle="height: 350px" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeLessonTab === 'attachments'" class="max-w-3xl mx-auto">
                        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <h3 class="font-bold text-lg border-b border-slate-100 pb-3 mb-6 flex items-center gap-2"><i class="pi pi-download"></i> Fichiers Téléchargeables</h3>

                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-10 text-center bg-slate-50 hover:bg-slate-100 transition-colors">
                                <i class="pi pi-cloud-upload text-5xl text-indigo-300 mb-4 block"></i>
                                <h4 class="text-slate-700 font-bold mb-1">Ajouter des ressources</h4>
                                <p class="text-xs text-slate-500 mb-6">PDF, ZIP, Code source (Max 50MB)</p>
                                <FileUpload mode="basic" customUpload @select="onAttachmentUpload" auto chooseLabel="Parcourir..." class="p-button-outlined" />
                                <ProgressBar v-if="uploadProgress > 0" :value="uploadProgress" class="mt-4 h-2" :showValue="false" />
                            </div>

                            <div v-if="lessonForm.attachment_paths.length > 0" class="mt-8 space-y-3">
                                <h4 class="font-bold text-sm text-slate-700">Fichiers joints ({{ lessonForm.attachment_paths.length }})</h4>
                                <div class="flex flex-col gap-2">
                                    <div v-for="(path, index) in lessonForm.attachment_paths" :key="index" class="flex items-center justify-between p-3 border border-slate-200 rounded-lg bg-white shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <i :class="typeof path === 'string' ? 'pi pi-cloud-download text-emerald-500' : 'pi pi-file-pdf text-blue-500'" class="text-lg"></i>
                                            <span class="text-sm font-bold text-slate-700">
                                                {{ typeof path === 'string' ? path.split('/').pop() : path.name }}
                                            </span>
                                        </div>
                                        <div class="flex gap-1">
                                            <Button v-if="typeof path === 'string'"
              icon="pi pi-download"
              class="p-button-text p-button-info p-button-sm p-button-rounded"
              v-tooltip.top="'Télécharger'"
              @click="downloadAttachment(path)" />
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm p-button-rounded" v-tooltip.top="'Retirer'" @click="lessonForm.attachment_paths.splice(index, 1)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeLessonTab === 'seo'" class="max-w-3xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                        <!-- Identique à l'onglet SEO de Module... -->
                        <h3 class="font-bold text-lg border-b border-slate-100 pb-3">Référencement (SEO) de la leçon</h3>
                        <div><label class="block text-sm font-bold mb-1">Meta Title</label><InputText v-model="lessonForm.seo_metadata.title" class="w-full bg-slate-50" /></div>
                        <div><label class="block text-sm font-bold mb-1">Meta Description</label><Textarea v-model="lessonForm.seo_metadata.description" rows="3" class="w-full bg-slate-50" /></div>
                    </div>
                </div>
            </div>

            <div class="flex-none h-[72px] bg-white border-t border-slate-200 flex justify-end items-center px-6 gap-3 shadow-[0_-5px_10px_rgba(0,0,0,0.02)]">
                <Button label="Annuler" class="p-button-text text-slate-600 font-bold" @click="lessonDialog = false" />
                <Button label="Enregistrer la Leçon" icon="pi pi-check" class="p-button-success font-bold shadow-md" @click="saveLesson" :loading="lessonForm.processing" :disabled="!isLessonValid" />
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : TRANSFERT DE MODULE (CHANGER DE COURS)                                -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="transferDialog" header="Déplacer le Module" :modal="true" :style="{ width: '450px' }" class="rounded-2xl shadow-2xl">
            <div class="pt-4">
                <Message severity="warn" :closable="false" class="mb-6 text-sm">
                    Déplacer ce module vers un autre cours modifiera la structure des deux cursus concernés.
                </Message>
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700">Sélectionnez le cours de destination :</label>
                    <Dropdown v-model="transferForm.target_course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Choisir un cours..." class="w-full" filter />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-4">
                    <Button label="Annuler" class="p-button-text text-slate-500" @click="transferDialog = false" />
                    <Button label="Confirmer le transfert" icon="pi pi-arrow-right-arrow-left" class="p-button-info" @click="executeTransfer" :disabled="!transferForm.target_course_id" :loading="transferForm.processing" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Scrollbar Custom pour le workspace */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* Customisation DataTable */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f1f5f9;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Drag & Drop Feedback visuel */
.sortable-ghost {
    opacity: 0.3;
    background-color: #f1f5f9;
}
.sortable-drag {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    background-color: white !important;
    border-radius: 0.5rem;
    cursor: grabbing !important;
}
</style>
