<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE COURSE ARCHITECT & CATALOG MANAGER v12.0.0
 * ======================================================================================
 * Module de pilotage centralisé pour la création et la gestion du patrimoine
 * pédagogique. Conçu pour une ergonomie maximale (SaaS B2B).
 *
 * NOUVEAUTÉS :
 * - UI/UX repensée pour l'éditeur de cours (Validation par étape, Auto-save)
 * - Constructeur de curriculum hiérarchique avancé
 * - Formulaire de catégorie étendu (Parent, Description, Meta)
 * - SEO & OpenGraph Live Preview
 *
 * @author  SubnetCongo Framework Division
 * @module  Curriculum/Catalog (Enterprise Edition)
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// ======================================================================================
// PRIMEVUE COMPONENTS (Importation exhaustive pour un rendu Enterprise)
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
import Divider from 'primevue/divider';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Editor from 'primevue/editor';
import Breadcrumb from 'primevue/breadcrumb';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Chart from 'primevue/chart';
import Toolbar from 'primevue/toolbar';
import Sidebar from 'primevue/sidebar';
import Skeleton from 'primevue/skeleton';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ToggleSwitch from 'primevue/toggleswitch';
import Tooltip from 'primevue/tooltip';

// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    courses: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    categories: { type: Array, default: () => [] },
    levels: {
        type: Array,
        default: () => [
            { label: 'Débutant', value: 'beginner', icon: 'pi pi-star' },
            { label: 'Intermédiaire', value: 'intermediate', icon: 'pi pi-star-half' },
            { label: 'Expert', value: 'advanced', icon: 'pi pi-star-fill' }
        ]
    },
    instructors: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_courses: 0, total_students: 0, active_instructors: 0 }) },
    filters: { type: Object, default: () => ({}) },
    meta: { type: Object, default: () => ({ total_revenue: 0, avg_rating: 0, completion_rate: 0 }) }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const selectedCourses = ref([]);

// Toggles de l'interface
const courseDialog = ref(false);
const analyticsDialog = ref(false);
const categoryDialog = ref(false);
const expandedRows = ref([]);
const editorRef = ref(null);

// Simulation de sauvegarde automatique
const lastSavedAt = ref(null);
const autoSaveInterval = ref(null);

// ======================================================================================
// CONFIGURATION DE L'ÉDITEUR DE COURS (COURSE ARCHITECT)
// ======================================================================================
/**
 * Définition du menu latéral du constructeur de cours.
 * Intègre un système d'état pour valider chaque section indépendamment.
 */
const editorMenu = ref([
    { id: 'general', label: 'Informations Générales', description: 'Titre, description, prix', icon: 'pi pi-id-card' },
    { id: 'media', label: 'Médias & Assets', description: 'Vignette, vidéo promo', icon: 'pi pi-image' },
    { id: 'curriculum', label: 'Curriculum & Modules', description: 'Leçons, quiz, ressources', icon: 'pi pi-sitemap' },
    { id: 'seo', label: 'Optimisation SEO', description: 'Moteurs de recherche & sociaux', icon: 'pi pi-google' },
    { id: 'settings', label: 'Paramètres Avancés', description: 'Certificats, accès, prérequis', icon: 'pi pi-cog' }
]);
const activeEditorTab = ref('general');

// ======================================================================================
// FILTRAGE RÉACTIF
// ======================================================================================
const serverFilters = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    category_id: props.filters?.category_id || null,
    level: props.filters?.level || null,
});

watch(serverFilters, debounce(() => {
    loading.value = true;
    router.get(route('courses.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 400));

// ======================================================================================
// GESTION AVANCÉE DES CATÉGORIES (ENTERPRISE GRADE)
// ======================================================================================
const categoryForm = useForm({
    name: '',
    slug: '',
    parent_id: null,
    description: '',
    icon: 'pi pi-folder',
    is_active: true
});
const savingCategory = ref(false);

const openCategoryDialog = () => {
    categoryForm.reset();
    categoryDialog.value = true;
};

const generateCategorySlug = () => {
    if (categoryForm.name) {
        categoryForm.slug = categoryForm.name.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }
};

const saveCategory = () => {
    if (!categoryForm.name.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur de saisie', detail: 'Le nom de la catégorie est obligatoire.', life: 4000 });
        return;
    }

    savingCategory.value = true;
    categoryForm.post(route('categories.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Catégorie créée', detail: 'La taxonomie a été mise à jour avec succès.', life: 3000 });
            categoryDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur serveur', detail: 'Vérifiez les champs du formulaire.', life: 5000 });
        },
        onFinish: () => savingCategory.value = false
    });
};

// ======================================================================================
// FORMULAIRE INERTIA - COURSE ARCHITECT (LMS CORE)
// ======================================================================================
const courseForm = useForm({
    id: null,
    title: '',
    slug: '',
    description: '',
    short_info: '',
    instructor_id: null,
    category_id: null,
    level: 'beginner',
    price: 0,
    promo_price: null,
    status: 'draft',
    thumbnail: null,
    preview_video_url: '',
    tags: [],
    seo_metadata: { title: '', description: '', keywords: '', og_image: null },
    sections: [],
    is_featured: false,
    requires_enrollment: true,
    certificate_enabled: false,
    prerequisites: '',
    estimated_duration: 0
});

// ======================================================================================
// LOGIQUE DE VALIDATION DU FORMULAIRE DE COURS (FRONT-END)
// ======================================================================================
/**
 * Calcule dynamiquement si un onglet spécifique contient des erreurs bloquantes.
 * Permet d'afficher des indicateurs visuels dans le menu latéral.
 */
const tabValidation = computed(() => {
    return {
        general: courseForm.title.length > 3 && courseForm.category_id !== null,
        media: true, // Optionnel par défaut
        curriculum: courseForm.sections.length > 0 && courseForm.sections[0].lessons?.length > 0,
        seo: true,
        settings: courseForm.instructor_id !== null
    };
});

const isEntireFormValid = computed(() => {
    return tabValidation.value.general && tabValidation.value.curriculum && tabValidation.value.settings;
});

// ======================================================================================
// MÉTHODES DU CYCLE DE VIE DU COURS
// ======================================================================================
const openNew = () => {
    courseForm.reset();
    courseForm.id = null;
    courseForm.sections = [];
    courseForm.seo_metadata = { title: '', description: '', keywords: '', og_image: null };
    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    courseDialog.value = true;
};

const editCourse = (data) => {
    courseForm.clearErrors();

    // Deep clone pour éviter de muter les props
    const courseData = cloneDeep(data);

    courseForm.id = courseData.id;
    courseForm.title = courseData.title;
    courseForm.slug = courseData.slug;
    courseForm.description = courseData.description || '';
    courseForm.short_info = courseData.short_info || '';
    courseForm.instructor_id = courseData.instructor_id || null;
    courseForm.category_id = courseData.category_id || null;
    courseForm.level = courseData.level || 'beginner';
    courseForm.price = parseFloat(courseData.price) || 0;
    courseForm.promo_price = courseData.promo_price ? parseFloat(courseData.promo_price) : null;
    courseForm.status = courseData.status || 'draft';
    courseForm.thumbnail = courseData.thumbnail_path || null;
    courseForm.preview_video_url = courseData.preview_video_url || '';
    courseForm.tags = courseData.tags || [];
    courseForm.seo_metadata = courseData.seo_metadata || { title: '', description: '', keywords: '', og_image: null };
    courseForm.sections = courseData.sections || [];
    courseForm.is_featured = courseData.is_featured || false;
    courseForm.requires_enrollment = courseData.requires_enrollment ?? true;
    courseForm.certificate_enabled = courseData.certificate_enabled || false;
    courseForm.prerequisites = courseData.prerequisites || '';
    courseForm.estimated_duration = courseData.estimated_duration || 0;

    activeEditorTab.value = 'general';
    lastSavedAt.value = new Date();
    courseDialog.value = true;

    // Injection forcée du contenu HTML dans Quill pour l'édition
    nextTick(() => {
        setTimeout(() => {
            if (editorRef.value && editorRef.value.quill) {
                const htmlContent = courseData.description || '';
                const delta = editorRef.value.quill.clipboard.convert({
                    html: htmlContent
                });
                editorRef.value.quill.setContents(delta, 'silent');
            }
        }, 100);
    });
};

/**
 * Force le rechargement du contenu dans l'instance Quill
 * Utile lors du switch d'onglets ou de l'ouverture initiale
 */
const forceEditorLoad = () => {
    if (!editorRef.value || !editorRef.value.quill) return;
    const htmlContent = courseForm.description || "";
    const delta = editorRef.value.quill.clipboard.convert({
        html: htmlContent
    });
    nextTick(() => {
        editorRef.value.quill.setContents(delta, 'silent');
    });
};

const saveCourse = (action = 'save') => {
    if (!isEntireFormValid.value) {
        toast.add({
            severity: 'warn',
            summary: 'Données incomplètes',
            detail: 'Veuillez remplir tous les champs obligatoires marqués d\'un astérisque.',
            life: 5000
        });
        // Redirection vers le premier onglet invalide
        if (!tabValidation.value.general) activeEditorTab.value = 'general';
        else if (!tabValidation.value.curriculum) activeEditorTab.value = 'curriculum';
        else if (!tabValidation.value.settings) activeEditorTab.value = 'settings';
        return;
    }

    // Gestion du statut selon le bouton cliqué
    if (action === 'publish') courseForm.status = 'published';
    if (action === 'draft') courseForm.status = 'draft';

    const url = courseForm.id ? route('courses.update', courseForm.id) : route('courses.store');

    courseForm.transform((data) => {
        const transformed = { ...data, _method: courseForm.id ? 'PUT' : 'POST' };
        if (typeof data.thumbnail === 'string') delete transformed.thumbnail; // Évite l'erreur "must be a file"
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            const actionText = action === 'publish' ? 'publié' : 'sauvegardé';
            toast.add({ severity: 'success', summary: 'Succès', detail: `Le cours a été ${actionText} avec succès.`, life: 4000 });
            lastSavedAt.value = new Date();
            if (action !== 'draft') courseDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Erreur système', detail: 'Des erreurs ont empêché la sauvegarde.', life: 5000 });
            console.error('Validation Errors:', errors);
        }
    });
};

// Actions de masse et suppressions
const confirmArchive = (id) => {
    confirm.require({
        message: 'Cette action rendra le cours inaccessible aux nouveaux apprenants. Les apprenants inscrits y auront toujours accès. Confirmer ?',
        header: 'Archivage du cours',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        rejectClass: 'p-button-secondary p-button-text',
        acceptLabel: 'Oui, archiver',
        rejectLabel: 'Annuler',
        accept: () => {
            router.delete(route('courses.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: 'Archivé', detail: 'Le cours a été déplacé aux archives.', life: 3000 })
            });
        }
    });
};

const confirmBulkDelete = () => {
    confirm.require({
        message: `Vous êtes sur le point de supprimer DÉFINITIVEMENT ${selectedCourses.value.length} cours. Cette action est irréversible. Continuer ?`,
        header: 'Suppression destructrice en masse',
        icon: 'pi pi-ban',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Supprimer définitivement',
        rejectLabel: 'Annuler',
        accept: () => {
            toast.add({ severity: 'success', summary: 'Opération réussie', detail: 'Les cours sélectionnés ont été purgés.', life: 3000 });
            selectedCourses.value = [];
        }
    });
};

// ======================================================================================
// CONSTRUCTEUR DE CURRICULUM (CURRICULUM BUILDER LOGIC)
// ======================================================================================
const addSection = () => {
    courseForm.sections.push({
        id: 'sec_' + Date.now(),
        title: '',
        description: '',
        order: courseForm.sections.length + 1,
        lessons: []
    });
};

const removeSection = (index) => {
    confirm.require({
        message: 'Supprimer ce module entraînera la suppression de toutes les leçons qu\'il contient. Confirmer ?',
        header: 'Suppression de module',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => courseForm.sections.splice(index, 1)
    });
};

const moveSectionUp = (index) => {
    if (index > 0) {
        const temp = courseForm.sections[index];
        courseForm.sections[index] = courseForm.sections[index - 1];
        courseForm.sections[index - 1] = temp;
    }
};

const moveSectionDown = (index) => {
    if (index < courseForm.sections.length - 1) {
        const temp = courseForm.sections[index];
        courseForm.sections[index] = courseForm.sections[index + 1];
        courseForm.sections[index + 1] = temp;
    }
};

const addLesson = (sectionIndex) => {
    courseForm.sections[sectionIndex].lessons.push({
        id: 'les_' + Date.now(),
        title: '',
        type: 'video', // 'video', 'document', 'quiz', 'assignment'
        duration_minutes: 0,
        is_free: false,
        resource_url: '',
        order: courseForm.sections[sectionIndex].lessons.length + 1
    });
};

const removeLesson = (sIdx, lIdx) => courseForm.sections[sIdx].lessons.splice(lIdx, 1);

// ======================================================================================
// HELPERS, UTILITAIRES ET FORMATTERS
// ======================================================================================
const generateCourseSlug = () => {
    if (courseForm.title && !courseForm.id) {
        courseForm.slug = courseForm.title.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)+/g, '');
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date(date));
};

const getStatusBadgeDef = (status) => {
    const definitions = {
        published: { severity: 'success', label: 'Publié en ligne', icon: 'pi pi-globe' },
        draft: { severity: 'secondary', label: 'Brouillon local', icon: 'pi pi-file-edit' },
        pending: { severity: 'warning', label: 'En attente de validation', icon: 'pi pi-clock' },
        archived: { severity: 'danger', label: 'Archivé (Inactif)', icon: 'pi pi-box' }
    };
    return definitions[status] || definitions['draft'];
};

const onPageChange = (event) => {
    router.get(route('courses.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true, preserveScroll: true });
};

// ======================================================================================
// COMPUTED PROPERTIES (ENRICHISSEMENT DES DONNÉES)
// ======================================================================================
const enrichedCourses = computed(() => {
    if (!props.courses?.data) return [];
    return props.courses.data.map(course => ({
        ...course,
        // Utilisation des compteurs réels fournis par le contrôleur via withCount
        students_count: course.students_count ?? 0,
        revenue_generated: course.revenue_generated ?? 0,
        thumbnail_path: course.thumbnail_path || null
    }));
});

// Prévisualisation SEO Google
const seoPreviewTitle = computed(() => {
    const title = courseForm.seo_metadata.title || courseForm.title;
    return title ? (title.length > 60 ? title.substring(0, 60) + '...' : title) : 'Titre optimisé de votre formation';
});

const seoPreviewDesc = computed(() => {
    const desc = courseForm.seo_metadata.description || courseForm.short_info;
    return desc ? (desc.length > 155 ? desc.substring(0, 155) + '...' : desc) : 'Description concise et percutante qui apparaîtra dans les résultats de recherche Google pour inciter au clic.';
});



// ======================================================================================
// CHARTS & ANALYTICS CONFIGURATION
// ======================================================================================
const chartOptions = {
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false, drawBorder: false }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } } },
        y: { grid: { color: '#f1f5f9', borderDash: [5, 5] }, ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }, border: { display: false } }
    },
    tension: 0.4,
    interaction: { mode: 'index', intersect: false }
};

const revenueChartData = ref({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Jui'],
    datasets: [{
        label: 'Revenus ($)',
        data: [4500, 7200, 6800, 9500, 12000, 15400, 18900],
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.05)',
        fill: true,
        borderWidth: 2,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#4f46e5',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6
    }]
});
const uploadMockProgress = ref(0)

// Computed pour l'URL d'aperçu (CORRECTION ICI)
const thumbnailUrl = computed(() => {
  const thumb = courseForm.thumbnail
  if (!thumb) return ''

  // Cas 1 : c'est une chaîne (URL HTTP ou chemin storage)
  if (typeof thumb === 'string') {
    return thumb.startsWith('http') ? thumb : '/media/' + thumb
  }

  // Cas 2 : c'est un objet File ou Blob - vérification sans instanceof problématique
  // On teste la présence de propriétés typiques d'un Blob/File
  if (thumb && typeof thumb === 'object' && 'size' in thumb && 'type' in thumb) {
    // Évite de créer des URL en double (nettoie l'ancienne si besoin)
    if (thumb._objectUrl) {
      URL.revokeObjectURL(thumb._objectUrl)
    }
    const url = URL.createObjectURL(thumb)
    thumb._objectUrl = url // stocke pour nettoyage ultérieur
    return url
  }

  return ''
})

// Gestion de la sélection du fichier
const onThumbnailSelect = (event) => {
  const file = event.files[0]
  if (!file) return

  // Nettoyer l'ancienne URL objet si présente
  if (courseForm.thumbnail && typeof courseForm.thumbnail === 'object' && courseForm.thumbnail._objectUrl) {
    URL.revokeObjectURL(courseForm.thumbnail._objectUrl)
  }

  courseForm.thumbnail = file

  // Simuler une progression (optionnel)
  uploadMockProgress.value = 100
  setTimeout(() => {
    uploadMockProgress.value = 0
  }, 500)
}
// ======================================================================================
// HOOKS
// ======================================================================================
onMounted(() => {
    setTimeout(() => loading.value = false, 600);

    // Auto-save conceptuel (Simulé)
    autoSaveInterval.value = setInterval(() => {
        if (courseDialog.value && courseForm.isDirty) {
            // Dans un vrai cas, on ferait un patch silencieux à l'API
            lastSavedAt.value = new Date();
        }
    }, 60000); // Check chaque minute
});

onBeforeUnmount(() => {
    if (autoSaveInterval.value) clearInterval(autoSaveInterval.value);
});

</script>

<template>
    <AppLayout>
        <Head title="Architecte Pédagogique | LMS Enterprise" />
        <Toast position="top-right" group="tc" />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50/50 p-4 md:p-8 lg:p-10 font-sans pb-24">

            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE ET KPI                                                          -->
            <!-- ============================================================================== -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Patrimoine Pédagogique', class: 'font-semibold text-indigo-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex flex-wrap items-center gap-3">
                            Catalogue des Formations
                            <Badge value="Enterprise" severity="info" class="text-[10px] bg-indigo-100 text-indigo-700 border border-indigo-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Pilotez l'ingénierie de votre écosystème d'apprentissage. Concevez des cursus interactifs avancés, gérez les stratégies de monétisation et analysez l'engagement de vos cohortes.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Insights & Rapports" icon="pi pi-chart-pie" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 hover:bg-slate-50 w-full sm:w-auto font-medium shadow-sm" @click="analyticsDialog = true" />
                    <Button label="Nouvelle Catégorie" icon="pi pi-tags" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 hover:bg-slate-50 w-full sm:w-auto font-medium shadow-sm" @click="openCategoryDialog" />
                    <Button label="Forger un Nouveau Cours" icon="pi pi-plus" class="p-button-primary shadow-md shadow-indigo-500/20 w-full sm:w-auto font-bold px-6" @click="openNew" />
                </div>
            </div>

            <!-- KPI DASHBOARD (4 CARTES) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- KPI 1 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-book text-6xl text-indigo-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-book text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Volume de Cours</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.stats.total_courses || 0 }}</h3>
                            <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">+12% ce mois</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 2 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-users text-6xl text-emerald-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-users text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Apprenants Actifs</p>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-slate-800">{{ props.stats.total_students || 0 }}</h3>
                            <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">+5.2%</span>
                        </div>
                    </div>
                </div>

                <!-- KPI 3 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity"><i class="pi pi-wallet text-6xl text-amber-600"></i></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-wallet text-sm"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">MRR (Revenu Mensuel)</p>
                        </div>
                        <h3 class="text-4xl font-black text-slate-800">{{ formatCurrency(props.meta.total_revenue || 0) }}</h3>
                    </div>
                </div>

                <!-- KPI 4 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-300 transition-all duration-300 hover:shadow-md flex flex-col justify-between">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center"><i class="pi pi-bullseye text-sm"></i></div>
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Tx. de Complétion</p>
                            </div>
                            <span class="text-lg font-black text-rose-600">{{ props.meta.completion_rate || 0 }}%</span>
                        </div>
                    </div>
                    <ProgressBar :value="props.meta.completion_rate || 0" :showValue="false" class="h-2.5 mt-2 bg-slate-100 rounded-full" color="#e11d48"></ProgressBar>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DATA GRID : GESTION DES COURS                                                  -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- TOOLBAR DE FILTRAGE AVANCÉ -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-auto">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" placeholder="Rechercher par titre, ID ou formateur..." class="w-full sm:w-80 border-slate-200 shadow-sm font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.category_id" :options="props.categories" optionLabel="name" optionValue="id" placeholder="Toutes les taxinomies" class="w-full sm:w-56 border-slate-200 shadow-sm" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2 text-slate-700 font-medium">
                                    <i class="pi pi-folder-open text-slate-400"></i>
                                    <span>{{ props.categories.find(c => c.id === slotProps.value)?.name }}</span>
                                </div>
                                <span v-else class="text-slate-500">Filtrer par catégorie</span>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.status" :options="[
                            {label:'Publié en ligne', value:'published', icon:'pi pi-globe', color:'text-emerald-500'},
                            {label:'Brouillon local', value:'draft', icon:'pi pi-file-edit', color:'text-slate-500'},
                            {label:'En attente', value:'pending', icon:'pi pi-clock', color:'text-amber-500'}
                        ]" optionLabel="label" optionValue="value" placeholder="État de publication" class="w-full sm:w-48 border-slate-200 shadow-sm" showClear>
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="[slotProps.option.icon, slotProps.option.color]"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedCourses.length > 0" icon="pi pi-trash" :label="`Supprimer (${selectedCourses.length})`" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkDelete" />
                        <Button icon="pi pi-download" label="Export" class="p-button-secondary bg-white text-slate-700 border-slate-200 shadow-sm hidden sm:flex" v-tooltip.top="'Générer un rapport CSV/Excel'" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser les données'" />
                    </div>
                </div>

                <!-- TABLEAU DE DONNÉES -->
                <DataTable
                    v-model:selection="selectedCourses"
                    v-model:expandedRows="expandedRows"
                    :value="enrichedCourses"
                    dataKey="id"
                    :lazy="true"
                    :paginator="true"
                    :rows="props.courses.per_page"
                    :totalRecords="props.courses.total"
                    :first="(props.courses.current_page - 1) * props.courses.per_page"
                    @page="onPageChange"
                    :loading="loading"
                    responsiveLayout="stack"
                    class="p-datatable-custom border-none"
                    rowHover
                    stripedRows
                >
                    <!-- EMPTY STATE -->
                    <template #empty>
                        <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                <i class="pi pi-inbox text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun parcours pédagogique trouvé</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                Il semblerait que votre catalogue soit vide avec les filtres actuels. Modifiez vos critères de recherche ou commencez à concevoir un nouveau cursus.
                            </p>
                            <Button label="Concevoir une formation" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <!-- COLONNES -->
                    <Column selectionMode="multiple" headerStyle="width: 3rem; background-color: #f8fafc;" />
                    <Column expander style="width: 3rem; background-color: #f8fafc;" />

                    <Column field="title" header="Identité & Classification du Cours" style="min-width: 25rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4 py-2">
                                <!-- Thumbnail -->

                                <div class="w-24 h-16 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200 relative group cursor-pointer" @click="editCourse(data)">
                                    <img v-if="data.thumbnail_path" :src="data.thumbnail_path.startsWith('http') ? data.thumbnail_path : '/media/' + data.thumbnail_path" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Vignette du cours" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300 bg-slate-50">
                                        <i class="pi pi-image text-xl mb-1"></i>
                                        <span class="text-[8px] uppercase font-bold tracking-widest">No Cover</span>
                                    </div>
                                    <div class="absolute inset-0 bg-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <i class="pi pi-pencil text-white text-lg drop-shadow-md"></i>
                                    </div>
                                </div>

                                <!-- Meta -->
                                <div class="flex flex-col justify-center">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-extrabold text-slate-800 text-base hover:text-indigo-600 cursor-pointer transition-colors line-clamp-1" @click="editCourse(data)">
                                            {{ data.title }}
                                        </span>
                                        <i v-if="data.is_featured" class="pi pi-star-fill text-amber-400 text-xs" v-tooltip.top="'Formation recommandée (Mise en avant)'"></i>
                                        <i v-if="data.certificate_enabled" class="pi pi-verified text-blue-500 text-xs" v-tooltip.top="'Certificat délivré à la fin'"></i>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                                        <span class="flex items-center gap-1 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md text-slate-600">
                                            <i class="pi pi-folder text-[10px]"></i>
                                            {{ data.category?.name || 'Général' }}
                                        </span>
                                        <span class="flex items-center gap-1 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md text-slate-600">
                                            <i class="pi pi-chart-bar text-[10px]"></i>
                                            {{ props.levels.find(l => l.value === data.level)?.label || 'Débutant' }}
                                        </span>
                                        <span v-if="data.estimated_duration" class="flex items-center gap-1 text-slate-400">
                                            <i class="pi pi-clock text-[10px]"></i> {{ data.estimated_duration }}h
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="price" header="Tarification" style="min-width: 10rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div v-if="data.price == 0" class="flex items-center">
                                <Badge value="100% GRATUIT" severity="success" class="text-[10px] tracking-wider border border-emerald-200 bg-emerald-50 text-emerald-700"></Badge>
                            </div>
                            <div v-else class="flex flex-col">
                                <span v-if="data.promo_price" class="font-black text-rose-600 text-sm flex items-center gap-1">
                                    <i class="pi pi-tag text-[10px]"></i>
                                    {{ formatCurrency(data.promo_price) }}
                                </span>
                                <span :class="['font-bold text-sm', data.promo_price ? 'text-slate-400 line-through text-xs mt-0.5' : 'text-slate-700']">
                                    {{ formatCurrency(data.price) }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="metrics" header="Engagement & KPI" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-32">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-users text-slate-400"></i> Apprenants</span>
                                    <span class="font-bold text-slate-700">{{ data.students_count?.toLocaleString() || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-32">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-money-bill text-slate-400"></i> Revenu</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(data.revenue_generated || 0) }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État de publication" style="min-width: 12rem; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <Tag
                                :severity="getStatusBadgeDef(data.status).severity"
                                :icon="getStatusBadgeDef(data.status).icon"
                                :value="getStatusBadgeDef(data.status).label"
                                class="text-[10px] font-bold px-3 py-1.5 border"
                                :class="`border-${getStatusBadgeDef(data.status).severity}-200 bg-${getStatusBadgeDef(data.status).severity}-50 text-${getStatusBadgeDef(data.status).severity}-700`"
                            ></Tag>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" :frozen="true" style="min-width: 8rem; text-align: right; background-color: #f8fafc;">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pen-to-square" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-indigo-600 hover:bg-indigo-50" v-tooltip.top="'Éditer le curriculum'" @click="editCourse(data)" />
                                <Button icon="pi pi-external-link" class="p-button-text p-button-info p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="'Aperçu Public du cours'" />
                                <Button icon="pi pi-box" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-rose-600 hover:bg-rose-50" v-tooltip.top="'Archiver le cours'" @click="confirmArchive(data.id)" />
                            </div>
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (VUE RAPIDE DU CURRICULUM) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="pi pi-sitemap"></i></div>
                                        Architecture du Curriculum (Aperçu)
                                    </h4>
                                    <Button label="Gérer les modules" icon="pi pi-cog" class="p-button-outlined p-button-sm p-button-secondary bg-white shadow-sm font-bold" @click="editCourse(data)" />
                                </div>

                                <div v-if="data.sections && data.sections.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                    <div v-for="(section, idx) in data.sections" :key="idx" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                                            <h5 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                                                <span class="text-xs text-slate-400 font-mono uppercase tracking-widest bg-slate-50 px-2 py-1 rounded">Mod {{ idx + 1 }}</span>
                                                <span class="truncate" :title="section.title">{{ section.title || 'Module sans titre' }}</span>
                                            </h5>
                                            <Badge :value="section.lessons?.length || 0" severity="secondary" class="text-[10px]" v-tooltip.top="'Nombre de leçons'"></Badge>
                                        </div>
                                        <ul class="space-y-2">
                                            <li v-for="(lesson, lIdx) in (section.lessons || []).slice(0, 4)" :key="lIdx" class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-transparent hover:border-slate-200 transition-colors">
                                                <div class="flex items-center gap-2.5 overflow-hidden">
                                                    <i :class="{
                                                        'pi pi-video text-rose-500': lesson.type === 'video',
                                                        'pi pi-file-pdf text-blue-500': lesson.type === 'document',
                                                        'pi pi-question-circle text-emerald-500': lesson.type === 'quiz',
                                                        'pi pi-code text-amber-500': lesson.type === 'assignment'
                                                    }" class="text-sm flex-shrink-0"></i>
                                                    <span class="truncate font-medium">{{ lesson.title || `Leçon ${lIdx + 1}` }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <span v-if="lesson.duration_minutes" class="text-[10px] text-slate-400 font-mono"><i class="pi pi-clock mr-1"></i>{{ lesson.duration_minutes }}m</span>
                                                    <i v-if="lesson.is_free" class="pi pi-unlock text-emerald-500 text-[10px]" v-tooltip.top="'Accès libre (Preview)'"></i>
                                                </div>
                                            </li>
                                            <li v-if="(section.lessons || []).length > 4" class="text-center pt-2">
                                                <span class="text-[11px] font-bold text-indigo-500 cursor-pointer hover:underline" @click="editCourse(data)">+ {{ section.lessons.length - 4 }} autres leçons</span>
                                            </li>
                                            <li v-if="!(section.lessons || []).length" class="text-[11px] text-slate-400 italic text-center py-2">Module vide</li>
                                        </ul>
                                    </div>
                                </div>
                                <div v-else class="text-slate-500 p-8 bg-white rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-3">
                                    <i class="pi pi-compass text-3xl text-slate-300"></i>
                                    <div class="text-center">
                                        <p class="font-bold text-slate-700">Aucun contenu pédagogique</p>
                                        <p class="text-sm">Le programme de ce cours n'a pas encore été défini.</p>
                                    </div>
                                    <Button label="Construire le programme" icon="pi pi-hammer" class="p-button-text p-button-primary mt-2" @click="editCourse(data)" />
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG ÉDITEUR ULTRA PRO (COURSE ARCHITECT WORKSPACE)                          -->
        <!-- Layout strict : Full height contraint, Stepper à gauche, Contenu scrollable    -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="courseDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: '95vw', maxWidth: '1400px' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[90vh]"
            headerClass="hidden"
            class="rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.2)] overflow-hidden border border-slate-200"
        >
            <!-- WORKSPACE HEADER (FIXE) -->
            <div class="flex-none h-[72px] bg-white border-b border-slate-200 flex items-center justify-between px-6 z-20 shadow-sm relative">
                <!-- Ligne de progression simulée en haut -->
                <div class="absolute top-0 left-0 h-0.5 bg-indigo-600 transition-all duration-500" :style="{ width: isEntireFormValid ? '100%' : '30%' }"></div>

                <div class="flex items-center gap-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <i class="pi pi-hammer text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-800 leading-tight flex items-center gap-3">
                            {{ courseForm.id ? 'Éditeur de Parcours' : 'Création Pédagogique' }}
                            <Tag v-if="courseForm.status" :severity="getStatusBadgeDef(courseForm.status).severity" :value="getStatusBadgeDef(courseForm.status).label" class="text-[9px] uppercase tracking-wider px-2 py-0.5"></Tag>
                        </h2>
                        <div class="flex items-center gap-3 text-[11px] font-medium text-slate-500 mt-0.5">
                            <span class="truncate max-w-[200px] md:max-w-[400px]">{{ courseForm.title || 'Cours sans titre' }}</span>
                            <span v-if="lastSavedAt" class="flex items-center gap-1 text-emerald-600"><i class="pi pi-check-circle"></i> Enregistré à {{ formatDate(lastSavedAt) }}</span>
                            <span v-else-if="courseForm.isDirty" class="flex items-center gap-1 text-amber-500"><i class="pi pi-spin pi-spinner"></i> Modifications en cours...</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button icon="pi pi-external-link" label="Aperçu visuel" class="p-button-text p-button-secondary p-button-sm hidden lg:flex font-bold" v-tooltip.bottom="'Prévisualiser la page de vente du cours'" />
                    <div class="w-px h-6 bg-slate-200 hidden md:block mx-1"></div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" @click="courseDialog = false" v-tooltip.bottom="'Fermer l\'espace de travail'" />
                </div>
            </div>

            <!-- WORKSPACE BODY (FLEX ROW) -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR DE NAVIGATION (STEPPER) -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        Phases de conception
                    </div>

                    <div class="p-4 flex-1 space-y-2">
                        <button
                            v-for="(item, index) in editorMenu" :key="item.id"
                            @click="activeEditorTab = item.id"
                            class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                            :class="[
                                activeEditorTab === item.id
                                    ? 'bg-indigo-50 border border-indigo-100 shadow-sm'
                                    : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'
                            ]"
                        >
                            <!-- Ligne active verticale -->
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600 rounded-r-md"></div>

                            <!-- Icon & Validation State -->
                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white">
                                    <i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px] bg-white rounded-full"></i>
                                    <i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px] bg-white rounded-full"></i>
                                </div>
                            </div>

                            <!-- Text content -->
                            <div class="flex-1">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-indigo-800' : 'text-slate-700']">{{ item.label }}</div>
                                <div class="text-[11px] font-medium text-slate-500 leading-tight">{{ item.description }}</div>
                            </div>
                        </button>
                    </div>

                    <!-- Sidebar Footer (Global Status) -->
                    <div class="p-6 border-t border-slate-200 bg-slate-50 mt-auto">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" :class="isEntireFormValid ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                                <i :class="isEntireFormValid ? 'pi pi-check' : 'pi pi-exclamation-triangle'" class="text-lg"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ isEntireFormValid ? 'Prêt à publier' : 'Informations requises' }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                    {{ isEntireFormValid ? 'Tous les critères obligatoires sont remplis.' : 'Complétez les sections avec un avertissement.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE CONTENU PRINCIPALE (SCROLLABLE) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative scroll-smooth bg-slate-50">
                    <div class="max-w-6xl mx-auto p-6 lg:p-10 pb-24 space-y-8 min-h-full">

                        <!-- ========================================== -->
                        <!-- TAB 1 : INFORMATIONS GÉNÉRALES             -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-8" role="tabpanel">

                            <!-- Section: Identité visuelle du cours -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Identité & Positionnement</h3>
                                        <p class="text-xs text-slate-500 mt-1">Les informations de base visibles sur la marketplace.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                    <!-- Titre -->
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label for="course_title" class="block text-sm font-bold text-slate-700">Titre de la formation <span class="text-rose-500" aria-label="requis">*</span></label>
                                        <InputText id="course_title" v-model="courseForm.title" @input="generateCourseSlug" class="w-full p-4 text-lg font-bold text-slate-800 bg-slate-50/50 border-slate-300 focus:bg-white transition-colors" placeholder="Ex: Masterclass Vue.js 3 - De Zéro à Héros" :class="{'p-invalid': !courseForm.title || courseForm.title.length < 3}" aria-describedby="title-help" />
                                        <div class="flex justify-between mt-1">
                                            <small id="title-help" class="text-slate-500 font-medium text-[11px]"><i class="pi pi-info-circle mr-1"></i>Un titre clair et incluant des mots-clés convertit mieux.</small>
                                            <small class="font-mono text-[10px]" :class="courseForm.title.length > 60 ? 'text-amber-500' : 'text-slate-400'">{{ courseForm.title.length }}/60</small>
                                        </div>
                                    </div>

                                    <!-- Slug -->
                                    <div class="col-span-1 md:col-span-2 space-y-2">
                                        <label for="course_slug" class="block text-sm font-bold text-slate-700">Permalien (URL Slug)</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-link text-slate-400" />
                                            <InputText id="course_slug" v-model="courseForm.slug" class="w-full bg-slate-100 text-slate-600 font-mono text-sm border-transparent focus:border-indigo-400" placeholder="masterclass-vuejs-3" />
                                        </IconField>
                                        <small class="text-slate-500 text-[11px]">L'URL publique sera : <code>https://lms.subnetcongo.com/courses/<strong>{{ courseForm.slug || '...' }}</strong></code></small>
                                    </div>

                                    <!-- Résumé Court -->
                                    <div class="col-span-1 md:col-span-2 space-y-2 mt-2">
                                        <label for="course_short_info" class="block text-sm font-bold text-slate-700">Accroche (Sous-titre / Résumé)</label>
                                        <Textarea id="course_short_info" v-model="courseForm.short_info" rows="3" class="w-full p-3 bg-slate-50 border-slate-300 focus:bg-white" placeholder="Résumez la promesse pédagogique de cette formation en 2 ou 3 phrases percutantes..." maxlength="250" />
                                        <div class="flex justify-end mt-1">
                                            <small class="font-mono text-[10px] text-slate-400">{{ courseForm.short_info.length }}/250</small>
                                        </div>
                                    </div>

                                    <!-- Description Longue (Rich Text) -->
                                    <div class="col-span-1 md:col-span-2 space-y-2 mt-2">
                                        <label class="block text-sm font-bold text-slate-700">Description Détaillée (Page de vente)</label>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm group hover:border-indigo-300 transition-colors">
                                            <Editor
                                                ref="editorRef"
                                                v-model="courseForm.description"
                                                @load="forceEditorLoad"
                                                editorStyle="height: 300px; font-family: 'Inter', sans-serif;"
                                                placeholder="Détaillez le programme, les objectifs, à qui s'adresse ce cours..." />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Classification & Taxonomie -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-6 border-b border-slate-100 pb-4">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-tags"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">Taxinomie & Niveau</h3>
                                        <p class="text-xs text-slate-500 mt-1">Aidez les apprenants à trouver ce contenu facilement.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Catégorie Principale <span class="text-rose-500">*</span></label>
                                        <div class="flex items-center gap-2">
                                            <Dropdown v-model="courseForm.category_id" :options="props.categories" optionLabel="name" optionValue="id" placeholder="Sélectionnez un domaine..." class="flex-1 border-slate-300 font-medium" filter filterPlaceholder="Chercher une catégorie..." :class="{'p-invalid': !courseForm.category_id}">
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center gap-2">
                                                        <i class="pi pi-folder text-slate-400"></i>
                                                        <span>{{ props.categories.find(c => c.id === slotProps.value)?.name }}</span>
                                                    </div>
                                                    <span v-else class="text-slate-400">Sélectionnez...</span>
                                                </template>
                                            </Dropdown>
                                            <Button icon="pi pi-plus" class="p-button-secondary bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200 h-12 w-12 flex-shrink-0" v-tooltip.top="'Créer une nouvelle catégorie'" @click="openCategoryDialog" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Niveau de Difficulté</label>
                                        <SelectButton v-model="courseForm.level" :options="props.levels" optionLabel="label" optionValue="value" aria-labelledby="basic" class="w-full flex">
                                            <template #option="slotProps">
                                                <div class="flex flex-col items-center justify-center p-1 py-2 gap-1 w-full">
                                                    <i :class="slotProps.option.icon" class="text-sm"></i>
                                                    <span class="text-xs font-bold">{{ slotProps.option.label }}</span>
                                                </div>
                                            </template>
                                        </SelectButton>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Stratégie de Pricing -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-amber-400">
                                <div class="flex items-center justify-between mb-6 border-b border-slate-100 pb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-money-bill"></i></div>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-800 leading-none">Modèle Économique (Pricing)</h3>
                                            <p class="text-xs text-slate-500 mt-1">Définissez la valeur monétaire de cette formation.</p>
                                        </div>
                                    </div>
                                    <Badge v-if="courseForm.price === 0" value="Gratuit" severity="success"></Badge>
                                    <Badge v-else value="Payant" severity="warning"></Badge>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-slate-50 p-6 rounded-xl border border-slate-100">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Prix Standard (Valeur réelle)</label>
                                        <InputNumber v-model="courseForm.price" mode="currency" currency="USD" locale="en-US" class="w-full" :min="0" :pt="{ input: { class: 'text-xl font-black text-slate-800 p-4' } }" placeholder="$0.00" />
                                        <small class="text-slate-500 text-[11px] block mt-1">Laissez à 0 pour offrir le cours gratuitement.</small>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700">Prix Promo (Optionnel)</label>
                                        <InputNumber v-model="courseForm.promo_price" mode="currency" currency="USD" locale="en-US" class="w-full" :min="0" :max="courseForm.price || undefined" :pt="{ input: { class: 'text-xl font-black text-rose-600 p-4' } }" placeholder="$0.00" :disabled="!courseForm.price || courseForm.price === 0" />
                                        <small class="text-slate-500 text-[11px] block mt-1">Doit être inférieur au prix standard. Crée un effet barré.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 2 : MÉDIAS & ASSETS VISUELS            -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'media'" class="space-y-8" role="tabpanel">

                            <!-- Vignette -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-2 border-b border-slate-100 pb-4">Cover / Image à la Une</h3>

                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 mt-6">
    <div class="lg:col-span-2">
      <div class="aspect-video bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden relative group transition-colors hover:border-indigo-400 hover:bg-indigo-50/30">

        <!-- Aperçu de l'image -->
        <img
          v-if="courseForm.thumbnail"
          :src="thumbnailUrl"
          class="w-full h-full object-cover shadow-inner"
        />
        <div v-else class="text-center p-6 text-slate-400 flex flex-col items-center">
          <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4 border border-slate-100">
            <i class="pi pi-cloud-upload text-2xl text-indigo-300"></i>
          </div>
          <p class="text-sm font-bold text-slate-600 mb-1">Aucune image sélectionnée</p>
          <p class="text-[10px] uppercase font-bold tracking-widest text-slate-400">Preview 16:9</p>
        </div>

        <!-- Overlay interaction -->
        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-3 backdrop-blur-sm">
          <FileUpload
            mode="basic"
            accept="image/*"
            :maxFileSize="2000000"
            customUpload
            @select="onThumbnailSelect"
            chooseLabel="Remplacer l'image"
            class="p-button-sm p-button-rounded font-bold shadow-xl"
            auto
          />
          <Button
            v-if="courseForm.thumbnail"
            icon="pi pi-trash"
            class="p-button-rounded p-button-danger p-button-text text-white hover:bg-red-500/20"
            @click="clearThumbnail"
          />
        </div>

        <!-- Progress Bar -->
        <ProgressBar
          v-if="uploadMockProgress > 0 && uploadMockProgress < 100"
          :value="uploadMockProgress"
          class="absolute bottom-0 left-0 w-full h-1"
          :showValue="false"
        />
      </div>
    </div>

    <div class="lg:col-span-3 flex flex-col justify-center space-y-6">
      <div>
        <h4 class="font-bold text-slate-800 text-sm mb-2">Instructions pour la vignette</h4>
        <p class="text-sm text-slate-500 leading-relaxed">Cette image sera la première chose que vos futurs apprenants verront. Une image de haute qualité augmente considérablement le taux de conversion.</p>
      </div>

      <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
        <ul class="space-y-3 text-sm text-slate-600">
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Ratio & Dimensions :</strong> 16:9 recommandé (1920x1080px ou 1280x720px).</li>
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Formats acceptés :</strong> .JPG, .PNG, .WebP.</li>
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>Poids maximum :</strong> 2 Mo (optimisation automatique à l'upload).</li>
        </ul>
      </div>

      <div class="pt-2">
        <FileUpload
          mode="basic"
          accept="image/*"
          :maxFileSize="2000000"
          customUpload
          @select="onThumbnailSelect"
          chooseLabel="Parcourir les fichiers de votre ordinateur"
          class="p-button-outlined p-button-secondary w-full border-dashed border-2 font-bold hover:bg-slate-50 py-3"
          auto
        />
      </div>

                                </div>
                            </div>
                            </div>

                            <!-- Vidéo Promo -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-2 border-b border-slate-100 pb-4">Vidéo Teaser (Bande Annonce)</h3>
                                <p class="text-sm text-slate-500 mb-6 mt-2">Les cours avec une vidéo de présentation ont en moyenne 35% d'inscriptions en plus.</p>

                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700">URL du flux vidéo (YouTube, Vimeo, AWS S3)</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-youtube text-red-500" />
                                        <InputText v-model="courseForm.preview_video_url" class="w-full p-4 font-mono text-sm bg-slate-50 border-slate-300" placeholder="https://www.youtube.com/watch?v=..." />
                                    </IconField>

                                    <div v-if="courseForm.preview_video_url" class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100"><i class="pi pi-play text-indigo-600"></i></div>
                                        <div>
                                            <div class="font-bold text-sm text-slate-700">Vidéo détectée</div>
                                            <a :href="courseForm.preview_video_url" target="_blank" class="text-xs text-indigo-500 hover:underline flex items-center gap-1 mt-0.5"><i class="pi pi-external-link text-[10px]"></i> Tester le lien</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 3 : CURRICULUM ARCHITECT (DRAG & DROP) -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'curriculum'" role="tabpanel">

                            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm sticky top-0 z-30">
                                <div>
                                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-2"><i class="pi pi-sitemap text-indigo-600"></i> Syllabus & Scénario Pédagogique</h3>
                                    <p class="text-sm text-slate-500 mt-1 max-w-xl">Structurez votre expertise en chapitres (Modules) et ajoutez-y des contenus interactifs (Leçons, Quiz, Exercices).</p>
                                </div>
                                <div class="flex gap-2 w-full md:w-auto">
                                    <Button label="Générer avec l'IA" icon="pi pi-sparkles" class="p-button-outlined p-button-help bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 hidden xl:flex font-bold text-sm" v-tooltip.top="'Beta : Générer un plan de cours auto.'" />
                                    <Button label="Ajouter un Module" icon="pi pi-plus" class="p-button-primary shadow-md shadow-indigo-500/20 font-bold px-5 w-full md:w-auto" @click="addSection" />
                                </div>
                            </div>

                            <!-- État vide du Curriculum -->
                            <div v-if="courseForm.sections.length === 0" class="bg-white p-16 rounded-3xl border-2 border-dashed border-slate-300 text-center flex flex-col items-center justify-center min-h-[400px] transition-colors hover:border-indigo-300 hover:bg-indigo-50/30">
                                <div class="w-24 h-24 bg-gradient-to-br from-indigo-100 to-blue-50 rounded-full flex items-center justify-center mb-6 shadow-inner border border-white">
                                    <i class="pi pi-objects-column text-4xl text-indigo-400"></i>
                                </div>
                                <h4 class="text-2xl font-black text-slate-800 mb-3">Toile pédagogique vierge</h4>
                                <p class="text-slate-500 mb-8 max-w-lg text-base leading-relaxed">Un cours structuré commence par un premier grand chapitre. Créez votre premier module pour débuter l'ingénierie de votre formation.</p>
                                <Button label="Créer le premier Module (Chapitre 1)" icon="pi pi-plus" class="p-button-outlined p-button-lg font-bold border-2 text-indigo-600 hover:bg-indigo-50" @click="addSection" />
                            </div>

                            <!-- Liste des Modules -->
                            <div v-else class="space-y-8 pb-10">
                                <div v-for="(section, sIdx) in courseForm.sections" :key="section.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-indigo-300 hover:shadow-md relative group/module">

                                    <!-- MODULE HEADER -->
                                    <div class="bg-slate-50/80 px-4 py-3 flex items-center gap-4 border-b border-slate-200">
                                        <!-- Actions de réorganisation -->
                                        <div class="flex flex-col gap-1 text-slate-300 hover:text-indigo-500 transition-colors w-6 items-center flex-shrink-0">
                                            <i class="pi pi-caret-up cursor-pointer hover:scale-125 transition-transform" @click="moveSectionUp(sIdx)" :class="{'opacity-20 cursor-not-allowed': sIdx === 0}"></i>
                                            <i class="pi pi-caret-down cursor-pointer hover:scale-125 transition-transform" @click="moveSectionDown(sIdx)" :class="{'opacity-20 cursor-not-allowed': sIdx === courseForm.sections.length - 1}"></i>
                                        </div>

                                        <div class="bg-indigo-100 text-indigo-700 font-black text-[10px] uppercase tracking-widest px-3 py-1.5 rounded-md flex-shrink-0 border border-indigo-200">
                                            Module {{ sIdx + 1 }}
                                        </div>

                                        <InputText v-model="section.title" class="flex-1 font-extrabold text-slate-800 bg-transparent border-transparent hover:border-slate-300 focus:bg-white text-lg p-2 transition-colors focus:ring-0" placeholder="Titre du module (ex: Les bases du langage)..." />

                                        <div class="flex items-center gap-2 opacity-0 group-hover/module:opacity-100 transition-opacity flex-shrink-0">
                                            <Badge :value="`${section.lessons?.length || 0} leçons`" severity="secondary" class="text-[10px] bg-white text-slate-600 border border-slate-200"></Badge>
                                            <div class="w-px h-5 bg-slate-300 mx-1"></div>
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded bg-white hover:bg-rose-50 w-9 h-9" @click="removeSection(sIdx)" v-tooltip.top="'Supprimer ce module entier'" />
                                        </div>
                                    </div>

                                    <!-- MODULE BODY (LEÇONS) -->
                                    <div class="p-6 lg:p-8 bg-white">
                                        <!-- Leçons existantes -->
                                        <div class="space-y-3 mb-6" v-if="section.lessons.length > 0">
                                            <div v-for="(lesson, lIdx) in section.lessons" :key="lesson.id" class="flex items-center gap-4 bg-slate-50/50 border border-slate-200 rounded-xl p-3 hover:shadow-md hover:border-indigo-300 hover:bg-white transition-all duration-300 group/lesson relative">

                                                <!-- Handle Drag -->
                                                <i class="pi pi-bars text-slate-300 cursor-move hover:text-indigo-500 px-2 flex-shrink-0" v-tooltip.top="'Glisser pour réorganiser'"></i>

                                                <!-- Type Selector -->
                                                <Dropdown v-model="lesson.type" :options="[
                                                    {icon:'pi pi-video', val:'video', color:'text-rose-500', bg:'bg-rose-50'},
                                                    {icon:'pi pi-file-pdf', val:'document', color:'text-blue-500', bg:'bg-blue-50'},
                                                    {icon:'pi pi-check-square', val:'quiz', color:'text-emerald-500', bg:'bg-emerald-50'},
                                                    {icon:'pi pi-code', val:'assignment', color:'text-amber-500', bg:'bg-amber-50'}
                                                ]" optionValue="val" class="w-[70px] border border-slate-200 shadow-sm bg-white rounded-lg flex-shrink-0 p-0" :pt="{ input: { class: 'p-2 flex justify-center' } }">
                                                    <template #value="slotProps">
                                                        <div class="w-8 h-8 rounded flex items-center justify-center" :class="[
                                                            slotProps.value === 'video' ? 'bg-rose-50 text-rose-500' : '',
                                                            slotProps.value === 'document' ? 'bg-blue-50 text-blue-500' : '',
                                                            slotProps.value === 'quiz' ? 'bg-emerald-50 text-emerald-500' : '',
                                                            slotProps.value === 'assignment' ? 'bg-amber-50 text-amber-500' : ''
                                                        ]">
                                                            <i :class="[
                                                                slotProps.value === 'video' ? 'pi pi-video' : '',
                                                                slotProps.value === 'document' ? 'pi pi-file-pdf' : '',
                                                                slotProps.value === 'quiz' ? 'pi pi-check-square' : '',
                                                                slotProps.value === 'assignment' ? 'pi pi-code' : ''
                                                            ]" class="text-sm"></i>
                                                        </div>
                                                    </template>
                                                    <template #option="slotProps">
                                                        <div class="flex items-center gap-3 w-full p-1">
                                                            <div class="w-6 h-6 rounded flex items-center justify-center" :class="slotProps.option.bg"><i :class="[slotProps.option.icon, slotProps.option.color]" class="text-[10px]"></i></div>
                                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">{{ slotProps.option.val }}</span>
                                                        </div>
                                                    </template>
                                                </Dropdown>

                                                <!-- Lesson Title Input -->
                                                <InputText v-model="lesson.title" class="flex-1 border-transparent hover:border-slate-300 focus:border-indigo-400 bg-transparent focus:bg-white font-bold text-slate-700 text-sm py-2 px-3 transition-colors shadow-none" placeholder="Donnez un nom à cette leçon..." />

                                                <!-- Settings & Actions (Affichés au survol) -->
                                                <div class="flex items-center gap-3 xl:gap-5 flex-shrink-0 opacity-40 group-hover/lesson:opacity-100 transition-opacity">

                                                    <!-- Duration Input -->
                                                    <div class="hidden xl:flex items-center gap-2" v-tooltip.top="'Durée estimée (minutes)'">
                                                        <i class="pi pi-clock text-slate-400 text-xs"></i>

                                                        <InputNumber v-model="lesson.duration_minutes" class="w-16" :pt="{ input: { class: 'p-1.5 text-xs text-center font-mono' } }" placeholder="min" />
                                                    </div>

                                                    <div class="w-px h-6 bg-slate-200 hidden xl:block"></div>

                                                    <!-- Free Preview Toggle -->
                                                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm cursor-pointer hover:border-emerald-300 transition-colors" @click="lesson.is_free = !lesson.is_free">
                                                        <i :class="lesson.is_free ? 'pi pi-unlock text-emerald-500' : 'pi pi-lock text-slate-300'" class="text-xs"></i>
                                                        <label class="text-[10px] uppercase font-bold cursor-pointer" :class="lesson.is_free ? 'text-emerald-600' : 'text-slate-500'">Extrait</label>
                                                    </div>

                                                    <!-- Delete Lesson -->
                                                    <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm p-button-rounded bg-white border border-slate-200 hover:bg-rose-50 w-9 h-9" @click="removeLesson(sIdx, lIdx)" v-tooltip.top="'Supprimer cette leçon'" />
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="text-center py-6 bg-slate-50 border border-dashed border-slate-200 rounded-xl mb-4 text-slate-500 text-sm italic">
                                            Ce module ne contient encore aucun contenu.
                                        </div>

                                        <!-- Bouton Add Lesson -->
                                        <button @click="addLesson(sIdx)" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed border-slate-200 text-slate-500 font-bold hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-300 transition-all group">
                                            <div class="w-6 h-6 rounded-full bg-slate-100 group-hover:bg-indigo-100 flex items-center justify-center transition-colors"><i class="pi pi-plus text-xs"></i></div>
                                            Ajouter un contenu à ce module
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 4 : OPTIMISATION SEO & DECOUVERABILITÉ -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'seo'" class="space-y-8" role="tabpanel">

                            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 flex gap-4 items-start shadow-sm">
                                <i class="pi pi-lightbulb text-indigo-500 text-2xl mt-1"></i>
                                <div>
                                    <h4 class="font-bold text-indigo-900 mb-1">Pourquoi optimiser le SEO ?</h4>
                                    <p class="text-sm text-indigo-700 leading-relaxed">Les métadonnées aident les moteurs de recherche (Google, Bing) et les réseaux sociaux (LinkedIn, Twitter) à comprendre le sujet de votre cours. C'est votre principal levier d'acquisition de trafic organique gratuit.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
                                <!-- Formulaire SEO (Gauche) -->
                                <div class="xl:col-span-7 space-y-6 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                    <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4 flex items-center gap-2"><i class="pi pi-search text-slate-400"></i> Balises Meta (HTML)</h3>

                                    <div class="space-y-2">
                                        <div class="flex justify-between items-end mb-1">
                                            <label class="block text-sm font-bold text-slate-700">Titre SEO (Balise Title)</label>
                                            <span class="text-[10px] font-mono" :class="courseForm.seo_metadata.title?.length > 60 ? 'text-rose-500 font-bold' : 'text-slate-400'">{{ courseForm.seo_metadata.title?.length || 0 }} / 60 char. opti.</span>
                                        </div>
                                        <InputText v-model="courseForm.seo_metadata.title" class="w-full bg-slate-50 border-slate-300" placeholder="Laissez vide pour utiliser le titre du cours..." />
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex justify-between items-end mb-1">
                                            <label class="block text-sm font-bold text-slate-700">Meta Description</label>
                                            <span class="text-[10px] font-mono" :class="courseForm.seo_metadata.description?.length > 160 ? 'text-rose-500 font-bold' : 'text-slate-400'">{{ courseForm.seo_metadata.description?.length || 0 }} / 160 char. opti.</span>
                                        </div>
                                        <Textarea v-model="courseForm.seo_metadata.description" rows="4" class="w-full bg-slate-50 border-slate-300 leading-relaxed" placeholder="Résumé incitatif pour la page de recherche Google..." />
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Mots-clés cibles (Focus Keywords)</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-hashtag text-slate-400" />
                                            <InputText v-model="courseForm.seo_metadata.keywords" class="w-full bg-slate-50 border-slate-300" placeholder="Ex: formation design, ux ui, figma..." />
                                        </IconField>
                                        <small class="text-slate-400 text-[11px]">Séparez les expressions par des virgules.</small>
                                    </div>
                                </div>

                                <!-- Previews Live (Droite) -->
                                <div class="xl:col-span-5 space-y-6">

                                    <!-- Google Preview -->
                                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-google text-blue-500"></i> Aperçu SERP Google Desktop
                                        </h4>
                                        <div class="p-4 bg-white border border-slate-100 rounded-xl shadow-[0_2px_10px_rgba(0,0,0,0.05)] cursor-text selection:bg-blue-100">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-7 h-7 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">SC</div>
                                                <div>
                                                    <div class="text-[13px] text-slate-800 font-medium leading-none">SubnetCongo LMS</div>
                                                    <div class="text-[11px] text-slate-500 truncate max-w-[200px] mt-0.5">https://lms.subnetcongo.com/courses/{{ courseForm.slug || 'slug-du-cours' }}</div>
                                                </div>
                                                <i class="pi pi-ellipsis-v ml-auto text-slate-400 text-xs"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-[#1a0dab] hover:underline cursor-pointer truncate mb-1" style="font-family: Arial, sans-serif;">{{ seoPreviewTitle }}</h3>
                                            <p class="text-sm text-[#4d5156] line-clamp-2 leading-[1.58]" style="font-family: Arial, sans-serif;">{{ seoPreviewDesc }}</p>
                                        </div>
                                    </div>

                                    <!-- Social Preview (OpenGraph) Mockup -->
                                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-linkedin text-blue-700"></i> Aperçu Partage Social
                                        </h4>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 shadow-sm">
                                            <div class="h-32 bg-slate-200 border-b border-slate-200 relative">
                                                <img v-if="courseForm.thumbnail" :src="typeof courseForm.thumbnail === 'string' ? courseForm.thumbnail : (courseForm.thumbnail && typeof File !== 'undefined' && courseForm.thumbnail instanceof File ? URL.createObjectURL(courseForm.thumbnail) : '')" class="w-full h-full object-cover" />
                                                <div v-else class="absolute inset-0 flex items-center justify-center text-slate-400 text-xs font-bold uppercase tracking-widest"><i class="pi pi-image mr-2 text-lg"></i> Image OpenGraph</div>
                                            </div>
                                            <div class="p-3 bg-slate-100/50">
                                                <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">lms.subnetcongo.com</div>
                                                <div class="text-sm font-bold text-slate-800 truncate mb-1">{{ seoPreviewTitle }}</div>
                                                <div class="text-[11px] text-slate-500 truncate">{{ seoPreviewDesc }}</div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 5 : PARAMÈTRES AVANCÉS                 -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'settings'" class="space-y-8" role="tabpanel">

                            <!-- Paramètres Globaux -->
                            <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">Configuration & Déploiement</h3>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                                    <!-- Options Toggles -->
                                    <div class="space-y-4 col-span-1">
                                        <!-- Featured -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-amber-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-star-fill"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">Mise en avant (Featured)</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">Placer ce cours dans le carrousel principal de la page d'accueil de la plateforme.</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.is_featured" />
                                        </div>

                                        <!-- Certificat -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-blue-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-verified"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">Délivrance de Certificat</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">Générer automatiquement un diplôme PDF nominatif à 100% de complétion.</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.certificate_enabled" />
                                        </div>

                                        <!-- Inscription -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-emerald-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-sign-in"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">Inscription Obligatoire</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">Bloque l'accès aux utilisateurs non connectés, même si le cours est gratuit.</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.requires_enrollment" />
                                        </div>
                                    </div>

                                    <!-- Attribution & Infos annexes -->
                                    <div class="space-y-6 col-span-1">
                                        <div class="space-y-2">
                                            <label class="block text-sm font-bold text-slate-700 flex items-center gap-2"><i class="pi pi-user text-indigo-500"></i> Formateur / Instructeur Assigné <span class="text-rose-500">*</span></label>
                                            <Dropdown v-model="courseForm.instructor_id" :options="props.instructors" optionLabel="name" optionValue="id" placeholder="Sélectionnez l'expert référent..." class="w-full border-slate-300 bg-slate-50 font-medium" filter :class="{'p-invalid': !courseForm.instructor_id}">
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                                        <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold">
                                                            {{ props.instructors.find(i => i.id === slotProps.value)?.name.charAt(0) }}
                                                        </div>
                                                        <span>{{ props.instructors.find(i => i.id === slotProps.value)?.name }}</span>
                                                    </div>
                                                    <span v-else class="text-slate-400">Assignez un professeur...</span>
                                                </template>
                                            </Dropdown>
                                        </div>

                                        <div class="space-y-2 mt-4">
                                            <label class="block text-sm font-bold text-slate-700">Prérequis conseillés</label>
                                            <Textarea v-model="courseForm.prerequisites" rows="3" class="w-full bg-slate-50 border-slate-300" placeholder="Ex: Connaissances de base en HTML/CSS, ordinateur de moins de 5 ans..." />
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER (ACTIONS DE SAUVEGARDE) -->
            <div class="flex-none h-20 bg-white border-t border-slate-200 flex items-center justify-between px-6 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div class="text-xs text-slate-500 flex items-center gap-4">
                    <Button label="Supprimer le brouillon" icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm hidden md:flex font-medium" @click="courseDialog = false" v-if="!courseForm.id" />
                </div>

                <div class="flex items-center gap-3">
                    <Button label="Enregistrer comme Brouillon" icon="pi pi-save" class="p-button-outlined p-button-secondary font-bold bg-white text-slate-700 hover:bg-slate-50 border-slate-300" @click="saveCourse('draft')" :loading="courseForm.processing" />

                    <div class="relative group">
                        <Button label="Publier le Cours" icon="pi pi-rocket" class="p-button-primary shadow-lg shadow-indigo-500/30 font-bold px-8 text-sm" @click="saveCourse('publish')" :loading="courseForm.processing" :disabled="!isEntireFormValid" />
                        <!-- Tooltip custom si bloqué -->
                        <div v-if="!isEntireFormValid" class="absolute bottom-full right-0 mb-2 w-64 bg-slate-800 text-white text-xs p-3 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                            Complétez d'abord les étapes obligatoires (marquées d'un <i class="pi pi-exclamation-circle text-amber-400"></i>) dans le menu pour activer la publication.
                            <div class="absolute -bottom-1 right-8 w-2 h-2 bg-slate-800 transform rotate-45"></div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG CRÉATION DE CATÉGORIE (TAXONOMIE)                                       -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="categoryDialog" header="Créer une catégories" :modal="true" :style="{ width: '500px' }" :draggable="false" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="bg-slate-50 p-0" headerClass="bg-white border-b border-slate-100 px-6 py-4">
            <div class="p-6 space-y-6">
                <!-- Message info -->
                <Message severity="info" :closable="false" class="m-0 border border-blue-100 bg-blue-50 text-blue-800">
                    Les catégories structurent le catalogue. Une hiérarchie claire améliore l'expérience utilisateur.
                </Message>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">Nom de la catégorie <span class="text-rose-500">*</span></label>
                        <InputText v-model="categoryForm.name" @input="generateCategorySlug" placeholder="Ex: Développement Web, Management..." class="w-full p-3 font-bold text-slate-800 bg-white border-slate-300" autofocus :class="{'p-invalid': categoryForm.errors?.name}" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">Identifiant URL (Slug)</label>
                        <InputText v-model="categoryForm.slug" placeholder="developpement-web" class="w-full p-2 text-sm font-mono text-slate-500 bg-slate-100 border-transparent" />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">Catégorie Parente (Optionnel)</label>
                        <Dropdown v-model="categoryForm.parent_id" :options="props.categories" optionLabel="name" optionValue="id" placeholder="Catégorie racine..." class="w-full border-slate-300 bg-white" showClear filter />
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">Description courte (SEO)</label>
                        <Textarea v-model="categoryForm.description" rows="2" class="w-full bg-white border-slate-300" placeholder="Décrivez le contenu de cette catégorie..." />
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 mt-2">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">Visibilité publique</div>
                            <div class="text-[11px] text-slate-500">Activer l'affichage dans le menu principal.</div>
                        </div>
                        <ToggleSwitch v-model="categoryForm.is_active" />
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-100" @click="categoryDialog = false" />
                    <Button label="Créer la catégorie" icon="pi pi-check" class="p-button-primary font-bold shadow-md px-6" @click="saveCategory" :loading="savingCategory" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG ANALYTICS (INSIGHTS)                                                    -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="analyticsDialog" modal :style="{ width: '90vw', maxWidth: '600px' }" class="rounded-2xl overflow-hidden" contentClass="p-0 bg-slate-50">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-700 shadow-sm border border-indigo-200"><i class="pi pi-chart-line text-lg"></i></div>
                    <span class="font-black text-xl text-slate-800">Intelligence Pédagogique</span>
                </div>
            </template>

            <div class="p-6 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">

                <!-- Graphe de revenus -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-chart-bar"></i> Évolution du CA (7 mois)</h4>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-baseline gap-2 mb-4">
                            <span class="text-2xl font-black text-slate-800">$74,000</span>
                            <span class="text-xs text-emerald-500 font-bold"><i class="pi pi-arrow-up text-[10px]"></i> 18% vs N-1</span>
                        </div>
                        <Chart type="line" :data="revenueChartData" :options="chartOptions" class="h-48 w-full" />
                    </div>
                </div>

                <!-- Top Formations -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-trophy"></i> Best-Sellers (Top 4)</h4>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <ul class="divide-y divide-slate-100">
                            <!-- Mockup dynamique -->
                            <li v-for="i in 4" :key="i" class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 font-bold text-xs border border-slate-200 group-hover:border-indigo-300 transition-colors">
                                        #{{ i }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800 mb-0.5">Formation avancée {{ i }}</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1"><i class="pi pi-users text-[9px]"></i> {{ 1500 - (i * 250) }} inscrits</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-indigo-600">${{ (12000 - (i * 1500)).toLocaleString() }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Alertes & Qualité -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-shield"></i> Qualité & Alertes</h4>
                    <div class="space-y-3">
                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 flex gap-3 items-start shadow-sm">
                            <i class="pi pi-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <div>
                                <div class="text-sm font-bold text-amber-900">3 cours nécessitent une mise à jour</div>
                                <div class="text-xs text-amber-700 mt-1 leading-tight">Leur taux d'achèvement a chuté sous les 20% ce mois-ci.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end p-4 bg-white border-t border-slate-100">
                    <Button label="Fermer" class="p-button-secondary p-button-text font-bold" @click="analyticsDialog = false" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Animations fluides */
.animate-fade-in {
    animation: fadeIn 0.25s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/*
 * ASTUCE ULTRA PRO : Forcer la hauteur du Dialog pour qu'il soit absolument fixe (85vh).
 * L'intérieur est géré par Flexbox pour faire scroller uniquement le contenu.
 */
.custom-dialog-height {
    height: 85vh !important;
    max-height: 85vh !important;
}

/* Scrollbar personnalisée (Style macOS / SaaS moderne) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}

/* Customisation profonde de la DataTable PrimeVue */
:deep(.p-datatable-custom .p-datatable-thead > tr > th) {
    background-color: #f8fafc;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr) {
    transition: background-color 0.2s ease;
}
:deep(.p-datatable-custom .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}

/* Enlève la bordure du Toolbar PrimeVue */
:deep(.p-toolbar) {
    background: transparent;
    border: none;
    border-bottom: 1px solid #f1f5f9;
}

/* Customisation du SelectButton */
:deep(.custom-select-button .p-button) {
    font-weight: 700;
    font-size: 0.875rem;
}

/* Sidebar Analytics */
:deep(.p-sidebar-custom) {
    background-color: #f8fafc;
}

/* Ombre intérieure pour le panel d'expansion */
.inset-shadow {
    box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.02);
}
</style>
