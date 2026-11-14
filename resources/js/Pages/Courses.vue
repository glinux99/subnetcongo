<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE COURSE ARCHITECT & CATALOG MANAGER v12.0.0
 * ======================================================================================
 * Module de pilotage centralisÃ© pour la crÃ©ation et la gestion du patrimoine
 * pÃ©dagogique. ConÃ§u pour une ergonomie maximale (SaaS B2B).
 *
 * NOUVEAUTÃ‰S :
 * - UI/UX repensÃ©e pour l'Ã©diteur de cours (Validation par Ã©tape, Auto-save)
 * - Constructeur de curriculum hiÃ©rarchique avancÃ©
 * - Formulaire de catÃ©gorie Ã©tendu (Parent, Description, Meta)
 * - SEO & OpenGraph Live Preview
 *
 * @author  SubnetCongo Framework Division
 * @module  Curriculum/Catalog (Enterprise Edition)
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PageKpi from '@/Components/PageKpi.vue';
import SncDialog from '@/Components/SncDialog.vue';
import { useConfirmAction } from '@/composables/useConfirmAction';
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
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
import Editor from 'primevue/editor';
import MediaPicker from '@/Components/MediaPicker.vue';
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
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import Menu from 'primevue/menu';
import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();

// ======================================================================================
// PROPS INERTIA
// ======================================================================================
const props = defineProps({
    courses: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    categories: { type: Array, default: () => [] },
    levels: {
        type: Array,
        default: () => [
            { label: '', value: 'beginner', icon: 'pi pi-star' },
            { label: '', value: 'intermediate', icon: 'pi pi-star-half' },
            { label: '', value: 'advanced', icon: 'pi pi-star-fill' }
        ]
    },
    instructors: { type: Array, default: () => [] },
    exams: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_courses: 0, total_students: 0, active_instructors: 0 }) },
    filters: { type: Object, default: () => ({}) },
    meta: { type: Object, default: () => ({ total_revenue: 0, avg_rating: 0, completion_rate: 0 }) }
});

// ======================================================================================
// SERVICES & Ã‰TATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirmAction = useConfirmAction();
const loading = ref(true);
const selectedCourses = ref([]);

// Toggles de l'interface
const courseDialog = ref(false);
const analyticsDialog = ref(false);
const categoryDialog = ref(false);
const expandedRows = ref([]);
const courseRowMenu = ref(null);
const courseRowMenuTarget = ref(null);
const editorRef = ref(null);

// Simulation de sauvegarde automatique
const lastSavedAt = ref(null);
const autoSaveInterval = ref(null);

// ======================================================================================
// CONFIGURATION DE L'Ã‰DITEUR DE COURS (COURSE ARCHITECT)
// ======================================================================================
/**
 * DÃ©finition du menu latÃ©ral du constructeur de cours.
 * IntÃ¨gre un systÃ¨me d'Ã©tat pour valider chaque section indÃ©pendamment.
 */
const editorMenu = ref([
    { id: 'general', label: t('pages.courses.editorGeneralLabel'), description: t('pages.courses.editorGeneralDesc'), icon: 'pi pi-id-card' },
    { id: 'media', label: t('pages.courses.editorMediaLabel'), description: t('pages.courses.editorMediaDesc'), icon: 'pi pi-image' },
    { id: 'curriculum', label: t('pages.courses.editorCurriculumLabel'), description: t('pages.courses.editorCurriculumDesc'), icon: 'pi pi-sitemap' },
        { id: 'resources', label: t('pages.courses.editorResourcesLabel'), icon: 'pi pi-paperclip', description: t('pages.courses.editorResourcesDesc') }, // NO
    { id: 'seo', label: t('pages.courses.editorSeoLabel'), description: t('pages.courses.editorSeoDesc'), icon: 'pi pi-google' },
    { id: 'settings', label: t('pages.courses.editorSettingsLabel'), description: t('pages.courses.editorSettingsDesc'), icon: 'pi pi-cog' }
]);
const activeEditorTab = ref('general');
// 3. MÃ©thodes pour gÃ©rer les ressources
const addResource = () => {
    courseForm.resources.push({
        id: null,
        title: '',
        file: null,
        size: null,
        type: null,
        is_new: true
    });
};

const removeResource = (index) => {
    courseForm.resources.splice(index, 1);
};

const onResourceSelect = (event, index) => {
    const file = event.files[0];
    if (file) {
        courseForm.resources[index].file = file;
        courseForm.resources[index].title = courseForm.resources[index].title || file.name.split('.').slice(0, -1).join('.');
        courseForm.resources[index].size = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        courseForm.resources[index].type = file.name.split('.').pop().toUpperCase();
    }
};


// ======================================================================================
// FILTRAGE RÃ‰ACTIF
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
// GESTION AVANCÃ‰E DES CATÃ‰GORIES (ENTERPRISE GRADE)
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
    if (serverFilters.category_id) {
        categoryForm.parent_id = serverFilters.category_id;
    }
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
        toast.add({ severity: 'error', summary: t('pages.courses.validationNameRequiredSummary'), detail: t('pages.courses.categoryNameRequiredDetail'), life: 4000 });
        return;
    }

    savingCategory.value = true;
    categoryForm.post(route('categories.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: t('pages.courses.categoryCreatedSummary'), detail: t('pages.courses.categoryCreatedDetail'), life: 3000 });
            categoryDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: t('pages.courses.categorySaveErrorSummary'), detail: t('pages.courses.categorySaveErrorDetail'), life: 5000 });
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
    estimated_duration: 0,
    resources : [],
    has_final_exam: false,
    final_exam_weight: 0,
    passing_score_20: 10,
    quiz_weight_total: 80
});

// ======================================================================================
// LOGIQUE DE VALIDATION DU FORMULAIRE DE COURS (FRONT-END)
// ======================================================================================
/**
 * Calcule dynamiquement si un onglet spÃ©cifique contient des erreurs bloquantes.
 * Permet d'afficher des indicateurs visuels dans le menu latÃ©ral.
 */
const levelLabelKeys = { beginner: 'levelBeginner', intermediate: 'levelIntermediate', advanced: 'levelAdvanced' };
const levelOptions = computed(() => props.levels.map(l => ({ ...l, label: l.label || t(`pages.courses.${levelLabelKeys[l.value] || 'levelBeginner'}`) })));

const tabValidation = computed(() => {
    return {
        general: courseForm.title.length > 3 && courseForm.category_id !== null,
        media: true, // Optionnel par dÃ©faut
        curriculum: courseForm.sections.length > 0 && courseForm.sections.some(s => s.lessons && s.lessons.length > 0),
        seo: true,
        settings: courseForm.instructor_id !== null
    };
});

const isEntireFormValid = computed(() => {
    return tabValidation.value.general && tabValidation.value.curriculum && tabValidation.value.settings;
});

// Progression rÃ©elle du workspace (Ã©tapes obligatoires)
const requiredTabs = ['general', 'curriculum', 'settings'];
const validatedTabsCount = computed(() => requiredTabs.filter(k => tabValidation.value[k]).length);
const completionPercent = computed(() => Math.round((validatedTabsCount.value / requiredTabs.length) * 100));
const currentStepIndex = computed(() => editorMenu.value.findIndex(i => i.id === activeEditorTab.value));
const goToPrevStep = () => { const i = currentStepIndex.value; if (i > 0) activeEditorTab.value = editorMenu.value[i - 1].id; };
const goToNextStep = () => { const i = currentStepIndex.value; if (i >= 0 && i < editorMenu.value.length - 1) activeEditorTab.value = editorMenu.value[i + 1].id; };

// ======================================================================================
// MÃ‰THODES DU CYCLE DE VIE DU COURS
// ======================================================================================
const openNew = () => {
    courseForm.reset();
    courseForm.id = null;
    courseForm.sections = [];
    courseForm.seo_metadata = { title: '', description: '', keywords: '', og_image: null };
    if (serverFilters.category_id) {
        courseForm.category_id = serverFilters.category_id;
    }
    activeEditorTab.value = 'general';
    lastSavedAt.value = null;
    courseDialog.value = true;
};

const editCourse = (data) => {
    courseForm.clearErrors();
    const courseData = cloneDeep(data);

    // Chargement des donnÃ©es simples
    courseForm.id = courseData.id;
    courseForm.title = courseData.title;
    courseForm.slug = courseData.slug;
    courseForm.description = courseData.description || '';
    courseForm.short_info = courseData.short_info || '';
    courseForm.instructor_id = courseData.instructor_id;
    courseForm.category_id = courseData.category_id;
    courseForm.level = courseData.level || 'beginner';
    courseForm.price = parseFloat(courseData.price) || 0;
    courseForm.promo_price = courseData.promo_price ? parseFloat(courseData.promo_price) : null;
    courseForm.status = courseData.status || 'draft';
    courseForm.thumbnail = courseData.thumbnail_path;
    courseForm.preview_video_url = courseData.preview_video_url || '';
    courseForm.tags = courseData.tags || [];
    courseForm.seo_metadata = courseData.seo_metadata || { title: '', description: '', keywords: '' };
    courseForm.is_featured = !!courseData.is_featured;
    courseForm.requires_enrollment = courseData.requires_enrollment ?? true;
    courseForm.certificate_enabled = !!courseData.certificate_enabled;
    courseForm.prerequisites = courseData.prerequisites || '';
    courseForm.estimated_duration = courseData.estimated_duration || 0;

    // --- CORRECTION : Chargement des Ressources existantes ---
    courseForm.resources = (courseData.resources || []).map(res => ({
        id: res.id,
        title: res.title,
        file: null,
        size: res.human_size,
        type: res.file_type,
        full_url: res.full_url,
        is_new: false
    }));

    courseForm.has_final_exam = !!courseData.has_final_exam;
    courseForm.final_exam_weight = courseData.final_exam_weight || 0;
    courseForm.passing_score_20 = courseData.passing_score_20 || 10;
    courseForm.quiz_weight_total = courseData.quiz_weight_total || 80;

    // Normalisation des sections/lessons pour l'Ã©dition
    courseForm.sections = (courseData.sections || []).map(section => {
        const normalizedSection = {
            ...section,
            has_intro_video: !!section.intro_video_url,
            document_mode: section.document_url ? 'url' : 'url',
        };

        if (!normalizedSection.lessons) normalizedSection.lessons = [];
        normalizedSection.lessons = normalizedSection.lessons.map(lesson => {
            const normalizedLesson = {
                ...lesson,
                document_mode: lesson.document_url ? 'url' : 'url',
                document_file: null,
            };

            // Parse inline quiz data from content JSON
            if (lesson.type === 'quiz' && lesson.content) {
                try {
                    const contentData = JSON.parse(lesson.content);
                    if (contentData && contentData.created_inline && Array.isArray(contentData.questions)) {
                        normalizedLesson.quiz_mode = 'new';
                        normalizedLesson.questions = contentData.questions.map(q => ({
                            ...q,
                            options: q.options || ['', ''],
                            correct_answer: String(q.correct_answer ?? '0'),
                            points: q.points || 1,
                        }));
                        normalizedLesson.new_quiz_title = contentData.quiz_title || lesson.title;
                    } else {
                        normalizedLesson.quiz_mode = 'existing';
                    }
                } catch (e) {
                    normalizedLesson.quiz_mode = 'existing';
                }
            } else if (lesson.type === 'quiz') {
                normalizedLesson.quiz_mode = 'existing';
            }

            return normalizedLesson;
        });

        return normalizedSection;
    });

    activeEditorTab.value = 'general';
    courseDialog.value = true;

    // Initialisation Quill Editor
    nextTick(() => {
        setTimeout(() => {
            if (editorRef.value && editorRef.value.quill) {
                editorRef.value.quill.root.innerHTML = courseForm.description;
            }
        }, 200);
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

// Ouvre la présentation publique du cours dans un nouvel onglet (aperçu apprenant)
const previewCourse = () => {
    if (!courseForm.slug) return;
    window.open(route('marketplace.show', courseForm.slug), '_blank', 'noopener');
};

/**
 * Sélecteur de média (image/vidéo) pour la description du cours
 */
const mediaPickerOpen = ref(false);
const insertMediaIntoDescription = (media) => {
    const quill = editorRef.value?.quill;
    if (!quill) return;
    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();
    quill.insertEmbed(index, media.type === 'image' ? 'image' : 'video', media.url);
    quill.setSelection(index + 1);
};

const saveCourse = (action = 'save') => {
    if (!isEntireFormValid.value) {
        toast.add({
            severity: 'warn',
            summary: t('pages.courses.formIncompleteSummary'),
            detail: t('pages.courses.formIncompleteDetail'),
            life: 5000
        });
        // Redirection vers le premier onglet invalide
        if (!tabValidation.value.general) activeEditorTab.value = 'general';
        else if (!tabValidation.value.curriculum) activeEditorTab.value = 'curriculum';
        else if (!tabValidation.value.settings) activeEditorTab.value = 'settings';
        return;
    }

    // Gestion du statut selon le bouton cliquÃ©
    if (action === 'publish') courseForm.status = 'published';
    if (action === 'draft') courseForm.status = 'draft';

    const url = courseForm.id ? route('courses.update', courseForm.slug) : route('courses.store');

    courseForm.transform((data) => {
        const transformed = { ...data, _method: courseForm.id ? 'PUT' : 'POST' };
        if (typeof data.thumbnail === 'string') delete transformed.thumbnail; // Ã‰vite l'erreur "must be a file"
        return transformed;
    }).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            const actionText = action === 'publish' ? t('pages.courses.coursePublishedDetail') : t('pages.courses.courseSavedDetail');
            toast.add({ severity: 'success', summary: t('pages.courses.saveSuccessSummary'), detail: actionText, life: 4000 });
            lastSavedAt.value = new Date();
            if (action !== 'draft') courseDialog.value = false;
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: t('pages.courses.saveErrorSummary'), detail: t('pages.courses.saveErrorDetail'), life: 5000 });
            console.error('Validation Errors:', errors);
        }
    });
};

// Actions de masse et suppressions
const confirmArchive = (id) => {
    confirmAction({
        message: t('pages.courses.archiveCourseMessage'),
        header: t('pages.courses.archiveCourseHeader'),
        icon: 'pi pi-exclamation-triangle',
        danger: true,
        acceptLabel: t('pages.courses.archiveAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            router.delete(route('courses.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'info', summary: t('pages.courses.archivedSummary'), detail: t('pages.courses.archivedDetail'), life: 3000 })
            });
        }
    });
};

const confirmBulkArchive = () => {
    confirmAction({
        message: t('pages.courses.bulkArchiveMessage', { n: selectedCourses.value.length }),
        header: t('pages.courses.bulkArchiveHeader'),
        icon: 'pi pi-box',
        acceptLabel: t('pages.courses.archiveAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            const courseIds = selectedCourses.value.map(c => c.id);
            router.post(route('courses.bulk-destroy'), {
                ids: courseIds
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('pages.courses.bulkOperationSuccessSummary'), detail: t('pages.courses.bulkArchiveSuccessDetail'), life: 3000 });
                    selectedCourses.value = [];
                }
            });
        }
    });
};

const confirmBulkRestore = () => {
    confirmAction({
        message: t('pages.courses.bulkRestoreMessage', { n: selectedCourses.value.length }),
        header: t('pages.courses.bulkRestoreHeader'),
        icon: 'pi pi-undo',
        acceptLabel: t('pages.courses.restoreAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            const courseIds = selectedCourses.value.map(c => c.id);
            router.post(route('courses.bulk-restore'), {
                ids: courseIds
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('pages.courses.bulkOperationSuccessSummary'), detail: t('pages.courses.bulkRestoreSuccessDetail'), life: 3000 });
                    selectedCourses.value = [];
                }
            });
        }
    });
};

const cloneCourse = (course) => {
    confirmAction({
        message: t('pages.courses.cloneMessage', { title: course.title }),
        header: t('pages.courses.cloneHeader'),
        icon: 'pi pi-copy',
        acceptLabel: t('pages.courses.cloneAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            router.post(route('courses.duplicate', course.slug), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: t('pages.courses.cloneSuccessSummary'), detail: t('pages.courses.cloneSuccessDetail'), life: 3000 }),
                onError: () => toast.add({ severity: 'error', summary: t('pages.courses.cloneErrorSummary'), detail: t('pages.courses.cloneErrorDetail'), life: 3000 })
            });
        }
    });
};

const restoreCourse = (courseId) => {
    confirmAction({
        message: t('pages.courses.restoreCourseMessage'),
        header: t('pages.courses.restoreCourseHeader'),
        icon: 'pi pi-undo',
        acceptLabel: t('pages.courses.restoreAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            router.post(route('courses.restore', courseId), {}, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: t('pages.courses.restoredSummary'), detail: t('pages.courses.restoredDetail'), life: 3000 })
            });
        }
    });
};

const confirmPurge = (course) => {
    confirmAction({
        message: t('pages.courses.purgeMessage', { title: course.title }),
        header: t('pages.courses.purgeHeader'),
        icon: 'pi pi-ban',
        danger: true,
        acceptLabel: t('pages.courses.purgeAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            router.delete(route('courses.purge', course.slug), {
                preserveScroll: true,
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat().join(' ') || t('pages.courses.genericError');
                    toast.add({ severity: 'error', summary: t('pages.courses.purgeFailedSummary'), detail: errorMessage, life: 5000 });
                }
            });
        }
    });
};

const courseRowMenuItems = computed(() => {
    const course = courseRowMenuTarget.value;
    if (!course) return [];

    if (course.status === 'archived') {
        return [
            { label: t('pages.courses.restoreCourseTooltip'), icon: 'pi pi-undo', command: () => restoreCourse(course.id) },
            { separator: true },
            { label: t('pages.courses.purgeCourseTooltip'), icon: 'pi pi-ban', command: () => confirmPurge(course), class: 'text-red-600' },
        ];
    }

    return [
        { label: t('pages.courses.editCurriculumTooltip'), icon: 'pi pi-pen-to-square', command: () => editCourse(course) },
        { label: t('pages.courses.cloneCourseTooltip'), icon: 'pi pi-copy', command: () => cloneCourse(course) },
        { separator: true },
        { label: t('pages.courses.archiveCourseTooltip'), icon: 'pi pi-box', command: () => confirmArchive(course.id), class: 'text-amber-600' },
    ];
});

const toggleCourseRowMenu = (event, course) => {
    courseRowMenuTarget.value = course;
    courseRowMenu.value?.toggle(event);
};

const confirmBulkPurge = () => {
    confirmAction({
        message: t('pages.courses.bulkPurgeMessage', { n: selectedCourses.value.length }),
        header: t('pages.courses.bulkPurgeHeader'),
        icon: 'pi pi-ban',
        danger: true,
        acceptLabel: t('pages.courses.bulkPurgeAcceptLabel'),
        rejectLabel: t('pages.courses.cancel'),
        accept: () => {
            const courseIds = selectedCourses.value.map(c => c.id);
            router.post(route('courses.bulk-purge'), { ids: courseIds }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedCourses.value = [];
                }
            });
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
        text_content: '',
        has_intro_video: false,
        intro_video_url: '',
        intro_video_title: '',
        has_document: false,
        document_title: '',
        document_url: '',
        document_name: '',
        content_blocks: [],
        has_quiz: false,
        quiz_required: true,
        quiz_weight: 10,
        quiz_passing_score: 50,
        quiz_attempts_allowed: 3,
        order: courseForm.sections.length + 1,
        order_in_course: courseForm.sections.length + 1,
        lessons: []
    });
};

const getBlockIcon = (type) => {
    const map = {
        video: { icon: 'pi pi-video', bg: 'bg-red-100 text-red-600' },
        document: { icon: 'pi pi-file-pdf', bg: 'bg-blue-100 text-blue-600' },
        quiz: { icon: 'pi pi-check-square', bg: 'bg-emerald-100 text-emerald-600' },
        text: { icon: 'pi pi-align-left', bg: 'bg-slate-100 text-slate-600' }
    };
    return map[type] || map.text;
};

const getBlockPlaceholder = (type) => {
    const map = {
        video: t('pages.courses.videoBlockPlaceholder'),
        document: t('pages.courses.documentBlockPlaceholder'),
        quiz: t('pages.courses.quizBlockPlaceholder'),
        text: t('pages.courses.textBlockPlaceholder')
    };
    return map[type] || t('pages.courses.titlePlaceholder');
};

const addQuestion = (block) => {
    if (!block.questions) block.questions = [];
    block.questions.push({
        text: '',
        type: 'multiple_choice',
        options: ['', ''],
        correct_answer: '0',
        points: 1
    });
};

const removeQuestion = (block, qIdx) => {
    if (block.questions && block.questions.length > 0) {
        block.questions.splice(qIdx, 1);
    }
};

const addQuestionOption = (question) => {
    if (question.options.length < 6) {
        question.options.push('');
    }
};

const removeQuestionOption = (question, oIdx) => {
    if (question.options && question.options.length > 2) {
        question.options.splice(oIdx, 1);
        if (question.correct_answer === String(oIdx)) {
            question.correct_answer = '0';
        } else if (parseInt(question.correct_answer) > oIdx) {
            question.correct_answer = String(parseInt(question.correct_answer) - 1);
        }
    }
};

const onDocumentSelect = (event, sectionIndex, blockIndex) => {
    const file = event.files[0];
    if (file) {
        courseForm.sections[sectionIndex].lessons[blockIndex].document_file = file;
        courseForm.sections[sectionIndex].lessons[blockIndex].document_url = '';
        courseForm.sections[sectionIndex].lessons[blockIndex].document_mode = 'file';
    }
};

const removeSection = (index) => {
    confirmAction({
        message: t('pages.courses.removeModuleMessage'),
        header: t('pages.courses.removeModuleHeader'),
        icon: 'pi pi-trash',
        danger: true,
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

const addLesson = (sectionIndex, type = 'video') => {
    const block = {
        id: 'les_' + Date.now(),
        title: '',
        type: type,
        lesson_type: type,
        duration_minutes: 0,
        is_free: false,
        is_sequential: false,
        order: (courseForm.sections[sectionIndex].lessons?.length || 0) + 1,
        video_duration: 0,
        resource_url: '',
        content_text: '',
        content_html: '',
        has_attachment: false,
        attachment_title: '',
        quiz_id: null
    };

    switch(type) {
        case 'video':
            block.video_source = 'youtube';
            block.title = t('pages.courses.newVideoTitle');
            break;
        case 'document':
            block.document_url = '';
            block.document_title = '';
            block.document_mode = 'url';
            block.document_file = null;
            block.title = t('pages.courses.newDocumentTitle');
            break;
        case 'quiz':
            block.quiz_mode = 'new';
            block.questions = [];
            block.new_quiz_title = '';
            block.title = t('pages.courses.newQuizTitle');
            break;
        case 'text':
            block.content_text = '';
            block.content_html = '';
            block.title = t('pages.courses.newTextTitle');
            break;
        case 'assignment':
            block.title = t('pages.courses.newAssignmentTitle');
            break;
    }

    courseForm.sections[sectionIndex].lessons.push(block);
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
    return new Intl.DateTimeFormat(locale.value, { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date(date));
};

const getStatusBadgeDef = (status) => {
    const definitions = {
        published: { severity: 'success', label: t('pages.courses.statusPublishedOnline'), icon: 'pi pi-globe' },
        draft: { severity: 'secondary', label: t('pages.courses.statusDraftLocal'), icon: 'pi pi-file-edit' },
        pending: { severity: 'warning', label: t('pages.courses.statusPendingValidation'), icon: 'pi pi-clock' },
        archived: { severity: 'danger', label: t('pages.courses.statusArchivedInactive'), icon: 'pi pi-box' }
    };
    return definitions[status] || definitions['draft'];
};

const onPageChange = (event) => {
    router.get(route('courses.index'), { ...serverFilters, page: event.page + 1, per_page: event.rows }, { preserveState: true, preserveScroll: true });
};

// ======================================================================================
// COMPUTED PROPERTIES (ENRICHISSEMENT DES DONNÃ‰ES)
// ======================================================================================
const enrichedCourses = computed(() => {
    if (!props.courses?.data) return [];
    return props.courses.data.map(course => ({
        ...course,
        // Utilisation des compteurs rÃ©els fournis par le contrÃ´leur via withCount
        students_count: course.students_count ?? 0,
        revenue_generated: course.revenue_generated ?? 0,
        thumbnail_path: course.thumbnail_path || null
    }));
});

// PrÃ©visualisation SEO Google
const seoPreviewTitle = computed(() => {
    const title = courseForm.seo_metadata.title || courseForm.title;
    return title ? (title.length > 60 ? title.substring(0, 60) + '...' : title) : t('pages.courses.seoDefaultTitle');
});

const anySelectedNotArchived = computed(() => {
    if (!selectedCourses.value || selectedCourses.value.length === 0) return false;
    return selectedCourses.value.some(course => course.status !== 'archived');
});

const allSelectedAreArchived = computed(() => {
    if (!selectedCourses.value || selectedCourses.value.length === 0) return false;
    return selectedCourses.value.every(course => course.status === 'archived');
});

const seoPreviewDesc = computed(() => {
    const desc = courseForm.seo_metadata.description || courseForm.short_info;
    return desc ? (desc.length > 155 ? desc.substring(0, 155) + '...' : desc) : t('pages.courses.seoDefaultDesc');
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
    labels: [t('pages.courses.monthJan'), t('pages.courses.monthFeb'), t('pages.courses.monthMar'), t('pages.courses.monthApr'), t('pages.courses.monthMay'), t('pages.courses.monthJun'), t('pages.courses.monthJul')],
    datasets: [{
        label: t('pages.courses.chartRevenueLabel'),
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

// Computed pour l'URL d'aperÃ§u (CORRECTION ICI)
const thumbnailUrl = computed(() => {
  const thumb = courseForm.thumbnail
  if (!thumb) return ''

  // Cas 1 : c'est une chaÃ®ne (URL HTTP ou chemin storage)
  if (typeof thumb === 'string') {
    return thumb.startsWith('http') ? thumb : '/media/' + thumb
  }

  // Cas 2 : c'est un objet File ou Blob - vÃ©rification sans instanceof problÃ©matique
  // On teste la prÃ©sence de propriÃ©tÃ©s typiques d'un Blob/File
  if (thumb && typeof thumb === 'object' && 'size' in thumb && 'type' in thumb) {
    // Ã‰vite de crÃ©er des URL en double (nettoie l'ancienne si besoin)
    if (thumb._objectUrl) {
      URL.revokeObjectURL(thumb._objectUrl)
    }
    const url = URL.createObjectURL(thumb)
    thumb._objectUrl = url // stocke pour nettoyage ultÃ©rieur
    return url
  }

  return ''
})
const viewResource = (url) => {
    window.open(url, '_blank');
};

/**
 * Forcer le tÃ©lÃ©chargement d'une ressource
 */
const downloadResource = (url) => {
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', ''); // Force le tÃ©lÃ©chargement
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

/**
 * Remplacer un fichier existant (Reset de l'Ã©tat pour afficher l'upload)
 */
const replaceFile = (index) => {
    const resource = courseForm.resources[index];
    resource.file = null;
    resource.full_url = null;
    resource.is_new = true; // On repasse en mode "Nouveau" pour l'upload
};

/**
 * Confirmation de suppression
 */
const confirmRemoveResource = (index) => {
    const res = courseForm.resources[index];
    
    confirmAction({
        message: t('pages.courses.resourceDeleteMessage', { title: res.title || t('pages.courses.resourceUntitled') }),
        header: t('pages.courses.deleteConfirmHeader'),
        icon: 'pi pi-exclamation-triangle',
        danger: true,
        accept: () => {
            removeResource(index);
            toast.add({ severity: 'info', summary: t('pages.courses.deletedSummary'), detail: t('pages.courses.resourceRemovedDetail'), life: 3000 });
        }
    });
};

/**
 * Helper : Icones dynamiques par extension
 */
const getFileIcon = (type) => {
    if (!type) return 'pi pi-file';
    const t = type.toUpperCase();
    if (t === 'PDF') return 'pi pi-file-pdf text-red-500';
    if (['ZIP', 'RAR', '7Z'].includes(t)) return 'pi pi-box text-amber-500';
    if (['JPG', 'PNG', 'WEBP', 'JPEG'].includes(t)) return 'pi pi-image text-emerald-500';
    if (['DOC', 'DOCX'].includes(t)) return 'pi pi-file-word text-blue-500';
    if (['XLS', 'XLSX', 'CSV'].includes(t)) return 'pi pi-file-excel text-green-600';
    return 'pi pi-file text-slate-400';
};
// Gestion de la sÃ©lection du fichier
const onThumbnailSelect = (event) => {
  const file = event.files[0]
  if (!file) return

  // Nettoyer l'ancienne URL objet si prÃ©sente
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

    // Auto-save conceptuel (SimulÃ©)
    autoSaveInterval.value = setInterval(() => {
        if (courseDialog.value && courseForm.isDirty) {
            // Dans un vrai cas, on ferait un patch silencieux Ã  l'API
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
        <Head :title="t('pages.courses.headTitle')" />
        <Toast position="top-right" group="tc" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- ============================================================================== -->
            <!-- HEADER DE PAGE (PageHeader â€” Design System)                                     -->
            <!-- ============================================================================== -->
            <PageHeader
                :title="`${t('pages.courses.headerTitlePart1')} ${t('pages.courses.headerTitlePart2')}`"
                :subtitle="t('pages.courses.headerDescription')"
                :badge="t('pages.courses.enterpriseBadge')"
                icon="pi pi-book"
                accent="red"
            >
                <template #actions>
                    <Button
                        :label="t('pages.courses.headerInsightsButton')"
                        icon="pi pi-chart-pie"
                        class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white font-medium transition-colors"
                        @click="analyticsDialog = true"
                    />
                    <Button
                        :label="t('pages.courses.headerNewCategoryButton')"
                        icon="pi pi-tags"
                        class="!bg-white/5 hover:!bg-white/10 !border !border-white/10 !text-white font-medium transition-colors"
                        @click="openCategoryDialog"
                    />
                    <Button
                        :label="t('pages.courses.headerNewCourseButton')"
                        icon="pi pi-plus"
                        class="!bg-[#E92B2B] hover:!bg-red-700 !border-none !text-white font-bold shadow-lg shadow-red-900/50 transition-colors px-6"
                        @click="openNew"
                    />
                </template>
            </PageHeader>

            <div class="page-container page-section">

            <!-- KPI DASHBOARD (4 CARTES) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <PageKpi :label="t('pages.courses.kpiCoursesVolume')" :value="props.stats.total_courses || 0" icon="pi-book" color="blue">
                    <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">{{ t('pages.courses.kpiMonthlyGrowth') }}</span>
                </PageKpi>
                <PageKpi :label="t('pages.courses.kpiActiveLearners')" :value="props.stats.total_students || 0" icon="pi-users" color="emerald">
                    <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">+5.2%</span>
                </PageKpi>
                <PageKpi :label="t('pages.courses.kpiMrr')" :value="formatCurrency(props.meta.total_revenue || 0)" icon="pi-wallet" color="amber" />
                <PageKpi :label="t('pages.courses.kpiCompletionRate')" :value="`${props.meta.completion_rate || 0}%`" icon="pi-bullseye" color="red">
                    <ProgressBar :value="props.meta.completion_rate || 0" :showValue="false" class="h-2.5 mt-2 bg-slate-100 rounded-full" color="#e11d48"></ProgressBar>
                </PageKpi>
            </div>

            <!-- ============================================================================== -->
            <!-- DATA GRID : GESTION DES COURS                                                  -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <!-- TOOLBAR DE FILTRAGE AVANCÃ‰ -->
                <div class="bg-slate-50/50 border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <IconField iconPosition="left" class="w-full sm:w-auto">
                            <InputIcon class="pi pi-search text-slate-400" />
                            <InputText v-model="serverFilters.search" :placeholder="t('pages.courses.searchPlaceholder')" class="w-full sm:w-80 font-medium" />
                        </IconField>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block mx-1"></div>

                        <Dropdown v-model="serverFilters.category_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.courses.filterCategoryPlaceholder')" class="w-full sm:w-56 border-slate-200 shadow-sm" showClear>
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center gap-2 text-slate-700 font-medium">
                                    <i class="pi pi-folder-open text-slate-400"></i>
                                    <span>{{ props.categories.find(c => c.id === slotProps.value)?.name }}</span>
                                </div>
                                <span v-else class="text-slate-500">{{ t('pages.courses.filterCategoryAll') }}</span>
                            </template>
                        </Dropdown>

                        <Dropdown v-model="serverFilters.status" :options="[
                            {label: t('pages.courses.statusPublishedOnline'), value:'published', icon:'pi pi-globe', color:'text-emerald-500'},
                            {label: t('pages.courses.statusDraftLocal'), value:'draft', icon:'pi pi-file-edit', color:'text-slate-500'},
                            {label: t('pages.courses.statusFilterPending'), value:'pending', icon:'pi pi-clock', color:'text-amber-500'}
                        ]" optionLabel="label" optionValue="value" :placeholder="t('pages.courses.statusFilterPlaceholder')" class="w-full sm:w-48 border-slate-200 shadow-sm" showClear>
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="[slotProps.option.icon, slotProps.option.color]"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex items-center gap-2 w-full lg:w-auto justify-end">
                        <Button v-if="selectedCourses.length > 0 && anySelectedNotArchived" icon="pi pi-box" :label="t('pages.courses.bulkArchiveButton', { n: selectedCourses.length })" class="p-button-warning p-button-outlined shadow-sm" @click="confirmBulkArchive" />
                        <Button v-if="selectedCourses.length > 0 && allSelectedAreArchived" icon="pi pi-undo" :label="t('pages.courses.bulkRestoreButton', { n: selectedCourses.length })" class="p-button-success p-button-outlined shadow-sm" @click="confirmBulkRestore" />
                        <Button v-if="selectedCourses.length > 0 && allSelectedAreArchived" icon="pi pi-ban" :label="t('pages.courses.bulkPurgeButton', { n: selectedCourses.length })" class="p-button-danger p-button-outlined shadow-sm" @click="confirmBulkPurge" />
                        <Button icon="pi pi-download" :label="t('pages.courses.exportLabel')" class="p-button-secondary bg-white text-slate-700 border-slate-200 shadow-sm hidden sm:flex" v-tooltip.top="t('pages.courses.exportTooltip')" />
                        <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="t('pages.courses.refreshTooltip')" />
                    </div>
                </div>

                <!-- TABLEAU DE DONNÃ‰ES -->
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
                    class="p-datatable-custom p-datatable-mobile border-none"
                    rowHover
                    stripedRows
                >
                    <!-- EMPTY STATE -->
                    <template #empty>
                        <div class="flex flex-col items-center justify-center gap-3 p-8 text-center text-slate-500 bg-white sm:p-20">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                <i class="pi pi-inbox text-5xl text-slate-300"></i>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">{{ t('pages.courses.emptyStateTitle') }}</h3>
                            <p class="text-sm max-w-md text-center text-slate-500 leading-relaxed mb-6">
                                {{ t('pages.courses.emptyStateDescription') }}
                            </p>
                            <Button :label="t('pages.courses.emptyStateAction')" icon="pi pi-plus" class="p-button-primary font-bold shadow-md" @click="openNew" />
                        </div>
                    </template>

                    <!-- COLONNES -->
                    <Column selectionMode="multiple" headerStyle="width: 3rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" />
                    <Column expander style="width: 3rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" />

                    <Column field="title" :header="t('pages.courses.columnCourseIdentity')" headerClass="!text-left" style="min-width: 0">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2 sm:gap-4 px-1 py-1 sm:py-2 min-w-0">
                                <!-- Thumbnail -->
                                <div class="w-14 h-10 sm:w-24 sm:h-16 rounded-lg sm:rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200 relative group cursor-pointer" @click="editCourse(data)">
                                    <img v-if="data.thumbnail_path" :src="data.thumbnail_path.startsWith('http') ? data.thumbnail_path : '/media/' + data.thumbnail_path" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" :alt="t('pages.courses.thumbnailAlt')" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300 bg-slate-50">
                                        <i class="pi pi-image text-xl mb-1"></i>
                                        <span class="text-[8px] uppercase font-bold tracking-widest">{{ t('pages.courses.noCover') }}</span>
                                    </div>
                                    <div class="absolute inset-0 bg-blue-900/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <i class="pi pi-pencil text-white text-lg drop-shadow-md"></i>
                                    </div>
                                </div>

                                <!-- Meta -->
                                <div class="flex flex-col justify-center min-w-0">
                                    <div class="flex items-center gap-1.5 sm:gap-2 mb-1 min-w-0">
                                        <span class="font-extrabold text-slate-800 text-sm sm:text-base hover:text-blue-600 cursor-pointer transition-colors line-clamp-1 dark:text-slate-100 dark:hover:text-blue-300" @click="editCourse(data)">
                                            {{ data.title }}
                                        </span>
                                        <i v-if="data.is_featured" class="pi pi-star-fill text-amber-400 text-[10px] sm:text-xs flex-none" v-tooltip.top="t('pages.courses.featuredTooltip')"></i>
                                        <i v-if="data.certificate_enabled" class="pi pi-verified text-blue-500 text-[10px] sm:text-xs flex-none" v-tooltip.top="t('pages.courses.certificateTooltip')"></i>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1 sm:gap-2 text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <span class="flex items-center gap-1 bg-slate-100 border border-slate-200 px-1.5 sm:px-2 py-0.5 rounded-md text-slate-600 max-w-36 sm:max-w-none truncate dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                            <i class="pi pi-folder text-[10px] flex-none"></i>
                                            <span class="truncate">{{ data.category?.name || t('pages.courses.generalCategoryFallback') }}</span>
                                        </span>
                                        <Tag
                                            :severity="getStatusBadgeDef(data.status).severity"
                                            :value="getStatusBadgeDef(data.status).label"
                                            class="md:hidden text-[9px] font-bold px-1.5 py-0.5 border"
                                        />
                                        <span class="hidden lg:flex items-center gap-1 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                                            <i class="pi pi-chart-bar text-[10px]"></i>
                                            {{ levelOptions.find(l => l.value === data.level)?.label || t('pages.courses.levelBeginner') }}
                                        </span>
                                        <span v-if="data.estimated_duration" class="hidden xl:flex items-center gap-1 text-slate-400">
                                            <i class="pi pi-clock text-[10px]"></i> {{ data.estimated_duration }}h
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="price" :header="t('pages.courses.columnPricing')" headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell" style="min-width: 10rem">
                        <template #body="{ data }">
                            <div v-if="data.price == 0" class="flex items-center">
                                <Badge :value="t('pages.courses.freeBadge')" severity="success" class="text-[10px] tracking-wider border border-emerald-200 bg-emerald-50 text-emerald-700"></Badge>
                            </div>
                            <div v-else class="flex flex-col">
                                <span v-if="data.promo_price" class="font-black text-red-600 text-sm flex items-center gap-1">
                                    <i class="pi pi-tag text-[10px]"></i>
                                    {{ formatCurrency(data.promo_price) }}
                                </span>
                                <span :class="['font-bold text-sm', data.promo_price ? 'text-slate-400 line-through text-xs mt-0.5' : 'text-slate-700']">
                                    {{ formatCurrency(data.price) }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="metrics" :header="t('pages.courses.columnMetrics')" headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs w-32">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-users text-slate-400"></i> {{ t('pages.courses.metricLearners') }}</span>
                                    <span class="font-bold text-slate-700">{{ data.students_count?.toLocaleString() || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs w-32">
                                    <span class="text-slate-500 flex items-center gap-1.5"><i class="pi pi-money-bill text-slate-400"></i> {{ t('pages.courses.metricRevenue') }}</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(data.revenue_generated || 0) }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" :header="t('pages.courses.columnStatus')" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" style="min-width: 12rem">
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

                    <Column :header="t('pages.courses.columnActions')" headerClass="text-right" bodyClass="text-right" style="width: 3.25rem; min-width: 3.25rem;">
                        <template #body="{ data }">
                            <div class="hidden md:flex justify-end gap-1">
                                <Button v-if="data.status !== 'archived'" icon="pi pi-pen-to-square" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="t('pages.courses.editCurriculumTooltip')" @click="editCourse(data)" />
                                <Button v-if="data.status !== 'archived'" icon="pi pi-copy" class="p-button-text p-button-secondary p-button-rounded text-slate-500 hover:text-blue-600 hover:bg-blue-50" v-tooltip.top="t('pages.courses.cloneCourseTooltip')" @click="cloneCourse(data)" />
                                <Button v-if="data.status !== 'archived'" icon="pi pi-box" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-red-600 hover:bg-red-50" v-tooltip.top="t('pages.courses.archiveCourseTooltip')" @click="confirmArchive(data.id)" />
                                <Button v-if="data.status === 'archived'" icon="pi pi-undo" class="p-button-text p-button-success p-button-rounded text-slate-500 hover:text-emerald-600 hover:bg-emerald-50" v-tooltip.top="t('pages.courses.restoreCourseTooltip')" @click="restoreCourse(data.id)" />
                                <Button v-if="data.status === 'archived'" icon="pi pi-ban" class="p-button-text p-button-danger p-button-rounded text-slate-500 hover:text-red-600 hover:bg-red-50" v-tooltip.top="t('pages.courses.purgeCourseTooltip')" @click="confirmPurge(data)" />
                            </div>
                            <Button
                                icon="pi pi-ellipsis-v"
                                class="p-button-text p-button-secondary p-button-rounded w-11 h-11 md:hidden text-slate-500"
                                :aria-label="t('pages.live_sessions.menu_more')"
                                v-tooltip.top="t('pages.live_sessions.menu_more')"
                                @click="toggleCourseRowMenu($event, data)"
                            />
                        </template>
                    </Column>

                    <!-- LIGNE EXPANSIVE (VUE RAPIDE DU CURRICULUM) -->
                    <template #expansion="{ data }">
                        <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                            <div class="max-w-7xl mx-auto">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-slate-800 text-lg flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="pi pi-sitemap"></i></div>
                                        {{ t('pages.courses.curriculumPreviewTitle') }}
                                    </h4>
                                    <Button :label="t('pages.courses.manageModulesButton')" icon="pi pi-cog" class="p-button-outlined p-button-sm p-button-secondary bg-white shadow-sm font-bold" @click="editCourse(data)" />
                                </div>

                                <div v-if="data.sections && data.sections.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                                    <div v-for="(section, idx) in data.sections" :key="idx" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                                            <h5 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                                                <span class="text-xs text-slate-400 font-mono uppercase tracking-widest bg-slate-50 px-2 py-1 rounded">{{ t('pages.courses.moduleAbbrev') }} {{ idx + 1 }}</span>
                                                <span class="truncate" :title="section.title">{{ section.title || t('pages.courses.untitledModule') }}</span>
                                            </h5>
                                            <Badge :value="section.lessons?.length || 0" severity="secondary" class="text-[10px]" v-tooltip.top="t('pages.courses.lessonsCountTooltip')"></Badge>
                                        </div>
                                        <ul class="space-y-1.5">
                                            <li v-for="(lesson, lIdx) in (section.lessons || []).slice(0, 4)" :key="lIdx" class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-transparent hover:border-slate-200 transition-colors">
                                                <div class="flex items-center gap-2.5 overflow-hidden">
                                                    <i :class="{
                                                        'pi pi-video text-red-500': lesson.type === 'video',
                                                        'pi pi-file-pdf text-blue-500': lesson.type === 'document',
                                                        'pi pi-question-circle text-emerald-500': lesson.type === 'quiz',
                                                        'pi pi-code text-amber-500': lesson.type === 'assignment'
                                                    }" class="text-sm flex-shrink-0"></i>
                                                    <span class="truncate font-medium">{{ lesson.title || t('pages.courses.lessonFallback', { n: lIdx + 1 }) }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    <span v-if="lesson.duration_minutes" class="text-[10px] text-slate-400 font-mono"><i class="pi pi-clock mr-1"></i>{{ lesson.duration_minutes }}m</span>
                                                    <i v-if="lesson.is_free" class="pi pi-unlock text-emerald-500 text-[10px]" v-tooltip.top="t('pages.courses.freeAccessTooltip')"></i>
                                                </div>
                                            </li>
                                            <li v-if="(section.lessons || []).length > 4" class="text-center pt-2">
                                                <span class="text-[11px] font-bold text-blue-500 cursor-pointer hover:underline" @click="editCourse(data)">{{ t('pages.courses.moreLessonsLabel', { n: section.lessons.length - 4 }) }}</span>
                                            </li>
                                            <li v-if="!(section.lessons || []).length" class="text-[11px] text-slate-400 italic text-center py-2">{{ t('pages.courses.emptyModule') }}</li>
                                        </ul>
                                    </div>
                                </div>
                                <div v-else class="text-slate-500 p-8 bg-white rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-3">
                                    <i class="pi pi-compass text-3xl text-slate-300"></i>
                                    <div class="text-center">
                                        <p class="font-bold text-slate-700">{{ t('pages.courses.noPedagogicalContent') }}</p>
                                        <p class="text-sm">{{ t('pages.courses.noContentDescription') }}</p>
                                    </div>
                                    <Button :label="t('pages.courses.buildSyllabusButton')" icon="pi pi-hammer" class="p-button-text p-button-primary mt-2" @click="editCourse(data)" />
                                </div>
                            </div>
                        </div>
                    </template>
                </DataTable>

                <Menu id="course-actions-menu" ref="courseRowMenu" :model="courseRowMenuItems" popup aria-label="Actions du cours" />
            </div>

            </div>
        </div>


        <!-- ============================================================================== -->
        <!-- DIALOG Ã‰DITEUR ULTRA PRO (COURSE ARCHITECT WORKSPACE)                          -->
        <!-- Layout strict : Full height contraint, Stepper Ã  gauche, Contenu scrollable    -->
        <!-- ============================================================================== -->
        <Dialog
            v-model:visible="courseDialog"
            modal
            :closable="false"
            :draggable="false"
            :style="{ width: 'min(calc(100vw - 0.5rem), 1400px)', maxWidth: '100vw' }"
            contentClass="p-0 flex flex-col overflow-hidden bg-slate-50 h-[calc(100dvh-0.5rem)] sm:h-[90vh]"
            headerClass="hidden"
            class="course-workspace rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.2)] overflow-hidden border border-slate-200"
        >
            <!-- WORKSPACE HEADER (FIXE) -->
            <div class="flex-none min-h-14 sm:h-16 bg-white border-b border-slate-200 flex items-center justify-between gap-2 px-2 py-1.5 sm:px-6 sm:py-0 z-20 shadow-sm relative">
                <!-- Ligne de progression rÃ©elle en haut -->
                <div class="absolute top-0 left-0 h-1 bg-gradient-to-r from-red-500 to-red-600 transition-all duration-500" :style="{ width: completionPercent + '%' }"></div>

                <div class="flex items-center gap-2 sm:gap-5 min-w-0 flex-1">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-red-500 to-red-700 rounded-xl sm:rounded-2xl flex items-center justify-center text-white shadow-lg shadow-red-500/30 flex-none">
                        <i class="pi pi-hammer text-base sm:text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-[13px] sm:text-lg font-black text-slate-800 leading-tight flex items-center gap-1.5 sm:gap-3 min-w-0">
                            <span class="truncate max-w-[46vw] sm:max-w-none">{{ courseForm.id ? t('pages.courses.editorTitleEdit') : t('pages.courses.editorTitleNew') }}</span>
                            <Tag v-if="courseForm.status" :severity="getStatusBadgeDef(courseForm.status).severity" :value="getStatusBadgeDef(courseForm.status).label" class="hidden sm:inline-flex text-[9px] uppercase tracking-wider px-2 py-0.5 flex-none"></Tag>
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-slate-400 bg-slate-100 px-1.5 sm:px-2.5 py-1 rounded-lg flex-none whitespace-nowrap">
                                {{ t('pages.courses.stepLabel', { current: currentStepIndex + 1, total: editorMenu.length }) }}
                            </span>
                        </h2>
                        <div class="flex items-center gap-2 sm:gap-3 text-[10px] sm:text-[11px] font-medium text-slate-500 mt-0.5 min-w-0">
                            <span class="truncate max-w-[44vw] sm:max-w-[400px]">{{ courseForm.title || t('pages.courses.untitledCourse') }}</span>
                            <span v-if="lastSavedAt" class="hidden xl:flex items-center gap-1 text-emerald-600 flex-none"><i class="pi pi-check-circle"></i> {{ t('pages.courses.savedAtLabel', { time: formatDate(lastSavedAt) }) }}</span>
                            <span v-else-if="courseForm.isDirty" class="hidden sm:flex items-center gap-1 text-amber-500 flex-none"><i class="pi pi-spin pi-spinner"></i> {{ t('pages.courses.modificationsInProgress') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-3 flex-none">
                    <Button icon="pi pi-external-link" :label="t('pages.courses.previewVisualButton')" class="p-button-text p-button-secondary p-button-sm hidden lg:flex font-bold" v-tooltip.bottom="t('pages.courses.previewTooltip')" :disabled="!courseForm.slug" @click="previewCourse" />
                    <div class="w-px h-6 bg-slate-200 hidden md:block mx-1"></div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-100 hover:bg-slate-200 hover:text-slate-800 w-10 h-10" :aria-label="t('components.confirm_dialog.close')" @click="courseDialog = false" v-tooltip.bottom="t('pages.courses.closeWorkspaceTooltip')" />
                </div>
            </div>

            <!-- ÉTAPES — scroll horizontal sur mobile (la sidebar est masquée) -->
            <div class="flex-none md:hidden flex gap-2 overflow-x-auto border-b border-slate-200 bg-white px-2 py-2 custom-scrollbar" role="tablist" :aria-label="t('pages.courses.designPhasesLabel')">
                <button
                    v-for="(item, index) in editorMenu"
                    :key="item.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeEditorTab === item.id"
                    @click="activeEditorTab = item.id"
                    class="flex flex-none items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition-colors min-w-[7.5rem]"
                    :class="activeEditorTab === item.id ? 'border-red-200 bg-red-50 text-red-800 shadow-sm' : 'border-slate-200 bg-slate-50 text-slate-600'"
                >
                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-lg" :class="tabValidation[item.id] ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'">
                        <i v-if="tabValidation[item.id]" class="pi pi-check text-xs" aria-hidden="true"></i>
                        <i v-else class="pi pi-exclamation-circle text-xs" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[10px] font-black uppercase tracking-wide text-slate-400">{{ String(index + 1).padStart(2, '0') }}</span>
                        <span class="block max-w-28 truncate text-[11px] font-bold leading-tight">{{ item.label }}</span>
                    </span>
                </button>
            </div>

            <!-- WORKSPACE BODY (FLEX ROW) -->
            <div class="flex-1 flex overflow-hidden relative">

                <!-- SIDEBAR DE NAVIGATION (STEPPER) -->
                <div class="w-72 flex-none bg-white border-r border-slate-200 overflow-y-auto hidden md:flex flex-col z-10 custom-scrollbar shadow-[2px_0_10px_rgba(0,0,0,0.02)]">
                    <div class="p-6 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100">
                        {{ t('pages.courses.designPhasesLabel') }}
                    </div>

                    <div class="p-4 flex-1 space-y-1.5">
                        <button
                            v-for="(item, index) in editorMenu" :key="item.id"
                            @click="activeEditorTab = item.id"
                            class="w-full group flex items-start gap-4 px-4 py-4 rounded-xl transition-all text-left relative overflow-hidden"
                            :class="[
                                activeEditorTab === item.id
                                    ? 'bg-red-50 border border-red-100 shadow-sm'
                                    : 'hover:bg-slate-50 border border-transparent hover:border-slate-100'
                            ]"
                        >
                            <!-- Ligne active verticale -->
                            <div v-if="activeEditorTab === item.id" class="absolute left-0 top-0 bottom-0 w-1 bg-red-600 rounded-r-md"></div>

                            <!-- NumÃ©ro d'Ã©tape -->
                            <span
                                class="absolute top-3 right-3 text-[10px] font-black tabular-nums px-1.5 py-0.5 rounded"
                                :class="activeEditorTab === item.id ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400'"
                            >{{ String(index + 1).padStart(2, '0') }}</span>

                            <!-- Icon & Validation State -->
                            <div class="relative mt-0.5">
                                <i :class="[item.icon, 'text-xl transition-colors', activeEditorTab === item.id ? 'text-red-600' : 'text-slate-400 group-hover:text-slate-600']"></i>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full border-2 border-white flex items-center justify-center bg-white">
                                    <i v-if="tabValidation[item.id]" class="pi pi-check-circle text-emerald-500 text-[10px] bg-white rounded-full"></i>
                                    <i v-else class="pi pi-exclamation-circle text-amber-500 text-[10px] bg-white rounded-full"></i>
                                </div>
                            </div>

                            <!-- Text content -->
                            <div class="flex-1 pr-6">
                                <div :class="['font-bold text-sm mb-0.5', activeEditorTab === item.id ? 'text-red-800' : 'text-slate-700']">{{ item.label }}</div>
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
                                <div class="text-xs font-bold text-slate-800">{{ isEntireFormValid ? t('pages.courses.readyToPublish') : t('pages.courses.requiredInfo') }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                    {{ isEntireFormValid ? t('pages.courses.readyToPublishHint') : t('pages.courses.requiredInfoHint') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ZONE DE CONTENU PRINCIPALE (SCROLLABLE) -->
                <div class="flex-1 overflow-y-auto custom-scrollbar relative scroll-smooth bg-slate-50">
                    <div class="max-w-6xl mx-auto px-2 sm:px-5 py-3 sm:py-4 pb-6 sm:pb-8 space-y-3 sm:space-y-4 min-h-full">

                        <!-- ========================================== -->
                        <!-- TAB 1 : INFORMATIONS GÃ‰NÃ‰RALES             -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'general'" class="space-y-5" role="tabpanel">

                            <!-- Section: IdentitÃ© visuelle du cours -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-3 border-b border-slate-100 pb-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="pi pi-id-card"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.courses.identitySectionTitle') }}</h3>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.courses.identitySectionDesc') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                                    <!-- Titre -->
                                    <div class="col-span-1 md:col-span-2 space-y-1.5">
                                        <label for="course_title" class="block text-sm font-bold text-slate-700">{{ t('pages.courses.titleLabel') }} <span class="text-red-500" :aria-label="t('pages.courses.requiredAria')">*</span></label>
                                        <InputText id="course_title" v-model="courseForm.title" @input="generateCourseSlug" size="large" fluid class="font-bold" :placeholder="t('pages.courses.titleExamplePlaceholder')" :invalid="!courseForm.title || courseForm.title.length < 3" aria-describedby="title-help" />
                                        <div class="flex justify-between mt-1">
                                            <small id="title-help" class="text-slate-500 font-medium text-[11px]"><i class="pi pi-info-circle mr-1"></i>{{ t('pages.courses.titleHelpText') }}</small>
                                            <small class="font-mono text-[10px]" :class="courseForm.title.length > 60 ? 'text-amber-500' : 'text-slate-400'">{{ courseForm.title.length }}/60</small>
                                        </div>
                                    </div>

                                    <!-- Slug -->
                                    <div class="col-span-1 md:col-span-2 space-y-1.5">
                                        <label for="course_slug" class="block text-sm font-bold text-slate-700">{{ t('pages.courses.slugLabel') }}</label>
                                        <IconField iconPosition="left" class="w-full">
                                            <InputIcon class="pi pi-link text-slate-400" />
                                            <InputText id="course_slug" v-model="courseForm.slug" fluid class="font-mono" placeholder="masterclass-vuejs-3" />
                                        </IconField>
                                         <small class="text-slate-500 text-[11px]">{{ t('pages.courses.slugUrlHelp') }} <code>https://subnetcongo.net/courses/<strong>{{ courseForm.slug || '...' }}</strong></code></small>
                                    </div>

                                    <!-- RÃ©sumÃ© Court -->
                                    <div class="col-span-1 md:col-span-2 space-y-1.5 mt-2">
                                        <label for="course_short_info" class="block text-sm font-bold text-slate-700">{{ t('pages.courses.shortInfoLabel') }}</label>
                                        <Textarea id="course_short_info" v-model="courseForm.short_info" rows="3" fluid :placeholder="t('pages.courses.shortInfoPlaceholder')" maxlength="250" />
                                        <div class="flex justify-end mt-1">
                                            <small class="font-mono text-[10px] text-slate-400">{{ courseForm.short_info.length }}/250</small>
                                        </div>
                                    </div>

                                    <!-- Description Longue (Rich Text) -->
                                    <div class="col-span-1 md:col-span-2 space-y-1.5 mt-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.descriptionLabel') }}</label>
                                            <Button
                                                icon="pi pi-images"
                                                :label="t('pages.courses.insertMedia')"
                                                size="small"
                                                text
                                                class="!text-blue-600 hover:!bg-blue-50"
                                                @click="mediaPickerOpen = true"
                                            />
                                        </div>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm group hover:border-blue-300 transition-colors">
                                            <Editor
                                                ref="editorRef"
                                                v-model="courseForm.description"
                                                @load="forceEditorLoad"
                                                editorStyle="height: 240px; font-family: 'Inter', sans-serif;"
                                                :placeholder="t('pages.courses.descriptionPlaceholder')" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Classification & Taxonomie -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex items-center gap-3 mb-3 border-b border-slate-100 pb-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-tags"></i></div>
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.courses.taxonomySectionTitle') }}</h3>
                                        <p class="text-xs text-slate-500 mt-1">{{ t('pages.courses.taxonomySectionDesc') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.mainCategoryLabel') }} <span class="text-red-500">*</span></label>
                                        <div class="flex items-center gap-2">
                                            <Dropdown v-model="courseForm.category_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.courses.categorySelectPlaceholder')" fluid class="font-medium" filter :filterPlaceholder="t('pages.courses.categorySearchFilter')" :invalid="!courseForm.category_id">
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center gap-2">
                                                        <i class="pi pi-folder text-slate-400"></i>
                                                        <span>{{ props.categories.find(c => c.id === slotProps.value)?.name }}</span>
                                                    </div>
                                                    <span v-else class="text-slate-400">{{ t('pages.courses.selectEllipsis') }}</span>
                                                </template>
                                            </Dropdown>
                                            <Button icon="pi pi-plus" class="p-button-secondary bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200 h-12 w-12 flex-shrink-0" v-tooltip.top="t('pages.courses.createCategoryTooltip')" @click="openCategoryDialog" />
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.difficultyLabel') }}</label>
                                        <SelectButton v-model="courseForm.level" :options="levelOptions" optionLabel="label" optionValue="value" aria-labelledby="basic" class="w-full flex">
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

                            <!-- Section: StratÃ©gie de Pricing -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-amber-400">
                                <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-money-bill"></i></div>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-800 leading-none">{{ t('pages.courses.pricingSectionTitle') }}</h3>
                                            <p class="text-xs text-slate-500 mt-1">{{ t('pages.courses.pricingSectionDesc') }}</p>
                                        </div>
                                    </div>
                                    <Badge v-if="courseForm.price === 0" :value="t('pages.courses.freeBadgeShort')" severity="success"></Badge>
                                    <Badge v-else :value="t('pages.courses.paidBadgeShort')" severity="warning"></Badge>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-xl border border-slate-100">
                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.standardPriceLabel') }}</label>
                                        <InputNumber v-model="courseForm.price" mode="currency" currency="USD" locale="en-US" fluid :min="0" :pt="{ input: { class: 'text-xl font-black' } }" placeholder="$0.00" />
                                        <small class="text-slate-500 text-[11px] block mt-1">{{ t('pages.courses.freePriceHint') }}</small>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.promoPriceLabel') }}</label>
                                        <InputNumber v-model="courseForm.promo_price" mode="currency" currency="USD" locale="en-US" fluid :min="0" :max="courseForm.price || undefined" :pt="{ input: { class: 'text-xl font-black text-red-600' } }" placeholder="$0.00" :disabled="!courseForm.price || courseForm.price === 0" />
                                        <small class="text-slate-500 text-[11px] block mt-1">{{ t('pages.courses.promoPriceHint') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 2 : MÃ‰DIAS & ASSETS VISUELS            -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'media'" class="space-y-5" role="tabpanel">

                            <!-- Vignette -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-2 border-b border-slate-100 pb-4">{{ t('pages.courses.coverTitle') }}</h3>

                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-10 mt-6">
    <div class="lg:col-span-2">
      <div class="aspect-video bg-slate-100 rounded-xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center overflow-hidden relative group transition-colors hover:border-blue-400 hover:bg-blue-50/30">

        <!-- AperÃ§u de l'image -->
        <img
          v-if="courseForm.thumbnail"
          :src="thumbnailUrl"
          class="w-full h-full object-cover shadow-inner"
        />
        <div v-else class="text-center p-6 text-slate-400 flex flex-col items-center">
          <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4 border border-slate-100">
            <i class="pi pi-cloud-upload text-2xl text-blue-300"></i>
          </div>
          <p class="text-sm font-bold text-slate-600 mb-1">{{ t('pages.courses.noImageSelected') }}</p>
          <p class="text-[10px] uppercase font-bold tracking-widest text-slate-400">{{ t('pages.courses.previewAspect') }}</p>
        </div>

        <!-- Overlay interaction -->
        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-3 backdrop-blur-sm">
          <FileUpload
            mode="basic"
            accept="image/*"
            :maxFileSize="2000000"
            customUpload
            @select="onThumbnailSelect"
            :chooseLabel="t('pages.courses.replaceImageLabel')"
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
        <h4 class="font-bold text-slate-800 text-sm mb-2">{{ t('pages.courses.thumbnailInstructionsTitle') }}</h4>
        <p class="text-sm text-slate-500 leading-relaxed">{{ t('pages.courses.thumbnailInstructionsText') }}</p>
      </div>

      <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
        <ul class="space-y-3 text-sm text-slate-600">
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>{{ t('pages.courses.thumbnailRatioLabel') }}</strong> {{ t('pages.courses.thumbnailRatioText') }}</li>
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>{{ t('pages.courses.thumbnailFormatsLabel') }}</strong> {{ t('pages.courses.thumbnailFormatsText') }}</li>
          <li class="flex items-center gap-3"><i class="pi pi-check-circle text-emerald-500"></i> <strong>{{ t('pages.courses.thumbnailMaxWeightLabel') }}</strong> {{ t('pages.courses.thumbnailMaxWeightText') }}</li>
        </ul>
      </div>
<!--       
      <div class="pt-2">
        <FileUpload
          mode="basic"
          accept="image/*"
          :maxFileSize="2000000"
          customUpload
          @select="onThumbnailSelect"
          :chooseLabel="t('pages.courses.browseLocalFiles')"
          class="p-button-outlined p-button-secondary w-full border-dashed border-2 font-bold hover:bg-slate-50 py-3"
          auto
        />
      </div> -->

                                </div>
                            </div>
                            </div>

                            <!-- VidÃ©o Promo -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-2 border-b border-slate-100 pb-4">{{ t('pages.courses.teaserTitle') }}</h3>
                                <p class="text-sm text-slate-500 mb-6 mt-2">{{ t('pages.courses.teaserDesc') }}</p>

                                <div class="space-y-4">
                                    <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.videoUrlLabel') }}</label>
                                    <IconField iconPosition="left">
                                        <InputIcon class="pi pi-youtube text-red-500" />
                                        <InputText v-model="courseForm.preview_video_url" fluid class="font-mono" placeholder="https://www.youtube.com/watch?v=..." />
                                    </IconField>

                                    <div v-if="courseForm.preview_video_url" class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100"><i class="pi pi-play text-blue-600"></i></div>
                                        <div>
                                            <div class="font-bold text-sm text-slate-700">{{ t('pages.courses.videoDetected') }}</div>
                                            <a :href="courseForm.preview_video_url" target="_blank" class="text-xs text-blue-500 hover:underline flex items-center gap-1 mt-0.5"><i class="pi pi-external-link text-[10px]"></i> {{ t('pages.courses.testLink') }}</a>
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
                                    <h3 class="text-xl font-black text-slate-800 flex items-center gap-2"><i class="pi pi-sitemap text-blue-600"></i> {{ t('pages.courses.syllabusTitle') }}</h3>
                                    <p class="text-sm text-slate-500 mt-1 max-w-xl">{{ t('pages.courses.syllabusDesc') }}</p>
                                </div>
                                <div class="flex gap-2 w-full md:w-auto">
                                    <Button :label="t('pages.courses.generateAiButton')" icon="pi pi-sparkles" class="p-button-outlined p-button-help bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 hidden xl:flex font-bold text-sm" v-tooltip.top="t('pages.courses.generateAiTooltip')" />
                                    <Button :label="t('pages.courses.addModuleButton')" icon="pi pi-plus" class="p-button-primary shadow-md shadow-blue-500/20 font-bold px-5 w-full md:w-auto" @click="addSection" />
                                </div>
                            </div>

                            <!-- Ã‰tat vide du Curriculum -->
                            <div v-if="courseForm.sections.length === 0" class="bg-white p-16 rounded-3xl border-2 border-dashed border-slate-300 text-center flex flex-col items-center justify-center min-h-[400px] transition-colors hover:border-blue-300 hover:bg-blue-50/30">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-50 rounded-full flex items-center justify-center mb-6 shadow-inner border border-white">
                                    <i class="pi pi-objects-column text-4xl text-blue-400"></i>
                                </div>
                                <h4 class="text-2xl font-black text-slate-800 mb-3">{{ t('pages.courses.emptyCurriculumTitle') }}</h4>
                                <p class="text-slate-500 mb-8 max-w-lg text-base leading-relaxed">{{ t('pages.courses.emptyCurriculumDesc') }}</p>
                                <Button :label="t('pages.courses.createFirstModuleButton')" icon="pi pi-plus" class="p-button-outlined p-button-lg font-bold border-2 text-blue-600 hover:bg-blue-50" @click="addSection" />
                            </div>

                            <!-- Liste des Modules -->
                            <div v-else class="space-y-8 pb-10">
                                <div v-for="(section, sIdx) in courseForm.sections" :key="section.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-blue-300 hover:shadow-md relative group/module">

                                    <!-- MODULE HEADER -->
                                    <div class="bg-slate-50/80 px-4 py-3 flex items-center gap-4 border-b border-slate-200">
                                        <!-- Actions de rÃ©organisation -->
                                        <div class="flex flex-col gap-1 text-slate-300 hover:text-blue-500 transition-colors w-6 items-center flex-shrink-0">
                                            <i class="pi pi-caret-up cursor-pointer hover:scale-125 transition-transform" @click="moveSectionUp(sIdx)" :class="{'opacity-20 cursor-not-allowed': sIdx === 0}"></i>
                                            <i class="pi pi-caret-down cursor-pointer hover:scale-125 transition-transform" @click="moveSectionDown(sIdx)" :class="{'opacity-20 cursor-not-allowed': sIdx === courseForm.sections.length - 1}"></i>
                                        </div>

                                        <div class="bg-blue-100 text-blue-700 font-black text-[10px] uppercase tracking-widest px-3 py-1.5 rounded-md flex-shrink-0 border border-blue-200">
                                            {{ t('pages.courses.moduleBadge') }} {{ sIdx + 1 }}
                                        </div>

                                        <InputText v-model="section.title" class="flex-1 font-extrabold text-slate-800 bg-transparent border-transparent hover:border-slate-300 focus:bg-white text-lg p-2 transition-colors focus:ring-0" :placeholder="t('pages.courses.moduleTitlePlaceholder')" />

                                        <div class="flex items-center gap-2 opacity-0 group-hover/module:opacity-100 transition-opacity flex-shrink-0">
                                            <Badge :value="t('pages.courses.lessonsBadge', { n: section.lessons?.length || 0 })" severity="secondary" class="text-[10px] bg-white text-slate-600 border border-slate-200"></Badge>
                                            <div class="w-px h-5 bg-slate-300 mx-1"></div>
                                            <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded bg-white hover:bg-red-50 w-9 h-9" @click="removeSection(sIdx)" v-tooltip.top="t('pages.courses.deleteModuleTooltip')" />
                                        </div>
                                    </div>

                                    <!-- MODULE CONTENT BUILDER -->
                                    <div class="p-6 lg:p-8 bg-white space-y-6">
                                        
                                        <!-- 1. DESCRIPTION & TEXTE DU MODULE -->
                                        <div class="bg-slate-50/50 rounded-xl border border-slate-200 p-5 space-y-4">
                                            <h4 class="text-sm font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                                <i class="pi pi-file-edit text-blue-500"></i> {{ t('pages.courses.moduleContentTitle') }}
                                            </h4>

                                            <div class="space-y-1.5">
                                                <label class="block text-xs font-bold text-slate-600">{{ t('pages.courses.shortDescLabel') }}</label>
                                                <InputText v-model="section.description" class="w-full bg-white border-slate-200" :placeholder="t('pages.courses.moduleDescPlaceholder')" />
                                            </div>

                                            <div class="space-y-1.5">
                                                <label class="block text-xs font-bold text-slate-600">{{ t('pages.courses.moduleTextLabel') }} <span class="text-red-500">*</span></label>
                                                <Textarea v-model="section.text_content" rows="4" class="w-full bg-white border-slate-200 font-mono text-sm" :placeholder="t('pages.courses.moduleTextPlaceholder')"></Textarea>
                                                <div class="text-[10px] text-slate-400">{{ t('pages.courses.moduleTextHelp') }}</div>
                                            </div>
                                        </div>

                                        <!-- 2. MÃ‰DIAS DU MODULE -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- VidÃ©o d'introduction -->
                                            <div class="bg-red-50/30 rounded-xl border border-red-200 p-4 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-black text-red-700 uppercase tracking-wider flex items-center gap-2">
                                                        <i class="pi pi-video"></i> {{ t('pages.courses.introVideoLabel') }}
                                                    </label>
                                                    <ToggleSwitch v-model="section.has_intro_video" />
                                                </div>
                                                
                                                <div v-if="section.has_intro_video" class="space-y-3">
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.videoTitleLabel') }}</label>
                                                        <InputText v-model="section.intro_video_title" class="w-full bg-white border-slate-200 text-xs" :placeholder="t('pages.courses.videoTitlePlaceholder')" />
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.urlLabel') }}</label>
                                                        <InputText v-model="section.intro_video_url" class="w-full bg-white border-slate-200 text-xs font-mono" placeholder="https://www.youtube.com/watch?v=..." />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Document du module -->
                                            <div class="bg-blue-50/30 rounded-xl border border-blue-200 p-4 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-black text-blue-700 uppercase tracking-wider flex items-center gap-2">
                                                        <i class="pi pi-file-pdf"></i> {{ t('pages.courses.documentModuleLabel') }}
                                                    </label>
                                                    <ToggleSwitch v-model="section.has_document" />
                                                </div>
                                                
                                                <div v-if="section.has_document" class="space-y-3">
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.documentTitleLabel') }}</label>
                                                        <InputText v-model="section.document_title" class="w-full bg-white border-slate-200 text-xs" :placeholder="t('pages.courses.documentTitlePlaceholder')" />
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.urlOrFileLabel') }}</label>
                                                        <InputText v-model="section.document_url" class="w-full bg-white border-slate-200 text-xs font-mono" :placeholder="t('pages.courses.documentUrlPlaceholder')" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. BLOCS DE CONTENU (VIDÃ‰O, DOCUMENT, QUIZ) -->
                                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                            <div class="bg-slate-50/80 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                                                <h4 class="text-sm font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                                    <i class="pi pi-objects-column text-blue-500"></i> {{ t('pages.courses.moduleContentsTitle') }}
                                                </h4>
                                                <div class="flex gap-2">
                                                    <Button icon="pi pi-video" class="p-button-sm p-button-outlined p-button-danger text-red-600 border-red-200 hover:bg-red-50" @click="addLesson(sIdx, 'video')" v-tooltip.top="t('pages.courses.addVideoTooltip')" />
                                                    <Button icon="pi pi-file-pdf" class="p-button-sm p-button-outlined p-button-info text-blue-600 border-blue-200 hover:bg-blue-50" @click="addLesson(sIdx, 'document')" v-tooltip.top="t('pages.courses.addDocumentTooltip')" />
                                                    <Button icon="pi pi-check-square" class="p-button-sm p-button-outlined p-button-success text-emerald-600 border-emerald-200 hover:bg-emerald-50" @click="addLesson(sIdx, 'quiz')" v-tooltip.top="t('pages.courses.addQuizTooltip')" />
                                                    <Button icon="pi pi-align-left" class="p-button-sm p-button-outlined p-button-secondary text-slate-600 border-slate-200 hover:bg-slate-50" @click="addLesson(sIdx, 'text')" v-tooltip.top="t('pages.courses.addTextTooltip')" />
                                                </div>
                                            </div>

                                            <div class="p-5">
                                                <div v-if="section.lessons && section.lessons.length > 0" class="space-y-4">
                                                    <div v-for="(block, bIdx) in section.lessons" :key="block.id" class="border border-slate-200 rounded-xl overflow-hidden hover:border-blue-300 transition-all bg-white">
                                                        
                                                        <!-- Block Header -->
                                                        <div class="bg-slate-50/80 px-4 py-2.5 flex items-center gap-3 border-b border-slate-200">
                                                            <i class="pi pi-bars text-slate-300 cursor-move hover:text-blue-500"></i>
                                                            
                                                            <div class="w-7 h-7 rounded flex items-center justify-center" :class="getBlockIcon(block.type).bg">
                                                                <i :class="getBlockIcon(block.type).icon" class="text-xs"></i>
                                                            </div>
                                                            
                                                            <InputText v-model="block.title" class="flex-1 bg-transparent border-transparent font-bold text-sm text-slate-700" :placeholder="getBlockPlaceholder(block.type)" />
                                                            
                                                            <div class="flex items-center gap-2">
                                                                <div class="flex items-center gap-1 bg-white px-2 py-1 rounded border border-slate-200">
                                                                    <i class="pi pi-clock text-slate-400 text-[10px]"></i>
                                                                    <InputNumber v-model="block.duration_minutes" class="w-14 h-7 text-xs" :placeholder="t('pages.courses.minutesShort')" :pt="{ input: { class: 'p-1 text-xs text-center' } }" />
                                                                </div>
                                                                <ToggleSwitch v-model="block.is_free" />
                                                                <span class="text-[10px] uppercase font-bold" :class="block.is_free ? 'text-emerald-600' : 'text-slate-400'">{{ t('pages.courses.extractLabel') }}</span>
                                                                <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm p-button-rounded w-7 h-7" @click="removeLesson(sIdx, bIdx)" />
                                                            </div>
                                                        </div>

                                                        <!-- Block Body -->
                                                        <div class="p-4 bg-white space-y-3">
                                                            
                                                            <!-- Video Block -->
                                                            <div v-if="block.type === 'video'" class="space-y-3">
                                                                <div class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.videoSourceLabel') }}</label>
                                                                    <SelectButton v-model="block.video_source" :options="['youtube', 'vimeo', 's3', 'wistia']" class="w-full" />
                                                                </div>
                                                                <div class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.videoUrlBlockLabel') }}</label>
                                                                    <InputText v-model="block.video_url" class="w-full bg-slate-50 border-slate-200 font-mono text-xs" placeholder="https://www.youtube.com/watch?v=..." />
                                                                </div>
                                                                <div class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.durationSecondsLabel') }}</label>
                                                                    <InputNumber v-model="block.video_duration" class="w-full bg-slate-50 border-slate-200" :placeholder="t('pages.courses.durationSecondsPlaceholder')" />
                                                                </div>
                                                            </div>

                                                            <!-- Document Block -->
                                                            <div v-if="block.type === 'document'" class="space-y-3">
                                                                <div class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.documentTitleLabel') }}</label>
                                                                    <InputText v-model="block.document_title" class="w-full bg-slate-50 border-slate-200 text-xs" :placeholder="t('pages.courses.documentTitleShownPlaceholder')" />
                                                                </div>
                                                                
                                                                <!-- Toggle URL / Fichier -->
                                                                <div class="bg-slate-100 rounded-lg p-1 flex gap-1">
                                                                    <button type="button" @click="block.document_mode = 'url'" :class="['flex-1 py-1.5 text-[10px] font-bold uppercase rounded-md transition-all', block.document_mode === 'url' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                                                                        <i class="pi pi-link mr-1"></i> {{ t('pages.courses.urlLinkButton') }}
                                                                    </button>
                                                                    <button type="button" @click="block.document_mode = 'file'" :class="['flex-1 py-1.5 text-[10px] font-bold uppercase rounded-md transition-all', block.document_mode === 'file' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                                                                        <i class="pi pi-upload mr-1"></i> {{ t('pages.courses.importButton') }}
                                                                    </button>
                                                                </div>
                                                                
                                                                <!-- Mode URL -->
                                                                <div v-if="block.document_mode === 'url'" class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.documentUrlBlockLabel') }}</label>
                                                                    <InputText v-model="block.document_url" class="w-full bg-slate-50 border-slate-200 font-mono text-xs" placeholder="https://..." />
                                                                </div>
                                                                
                                                                <!-- Mode Fichier -->
                                                                <div v-if="block.document_mode === 'file'" class="space-y-1">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.fileToImportLabel') }}</label>
                                                                    <FileUpload 
                                                                        mode="basic" 
                                                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar"
                                                                        :maxFileSize="10000000"
                                                                        customUpload
                                                                        @select="onDocumentSelect($event, sIdx, bIdx)"
                                                                        :chooseLabel="t('pages.courses.selectFileLabel')"
                                                                        class="p-button-outlined p-button-secondary w-full border-dashed border-2"
                                                                        auto
                                                                    />
                                                                    <div v-if="block.document_file" class="text-[10px] text-emerald-600 font-medium mt-1">
                                                                        <i class="pi pi-check-circle mr-1"></i> {{ block.document_file.name }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Quiz Block -->
                                                            <div v-if="block.type === 'quiz'" class="space-y-3">
                                                                <!-- Tabs: Nouveau quiz / Quiz existant -->
                                                                <div class="bg-slate-100 rounded-lg p-1 flex gap-1">
                                                                    <button type="button" @click="block.quiz_mode = 'new'" :class="['flex-1 py-1.5 text-[10px] font-bold uppercase rounded-md transition-all', block.quiz_mode === 'new' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                                                                        <i class="pi pi-plus mr-1"></i> {{ t('pages.courses.createButton') }}
                                                                    </button>
                                                                    <button type="button" @click="block.quiz_mode = 'existing'" :class="['flex-1 py-1.5 text-[10px] font-bold uppercase rounded-md transition-all', block.quiz_mode === 'existing' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                                                                        <i class="pi pi-check-square mr-1"></i> {{ t('pages.courses.chooseButton') }}
                                                                    </button>
                                                                </div>

                                                                 <!-- Mode: CrÃ©er un nouveau quiz -->
                                                                 <div v-if="block.quiz_mode === 'new'" class="space-y-3 border border-emerald-100 rounded-xl p-4 bg-emerald-50/30">
                                                                     <div class="space-y-1">
                                                                         <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.quizTitleLabel') }}</label>
                                                                         <InputText v-model="block.new_quiz_title" class="w-full bg-white border-slate-200 text-xs" :placeholder="t('pages.courses.quizTitlePlaceholder')" />
                                                                     </div>
                                                                     
                                                                     <div class="space-y-1.5">
                                                                         <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.questionsLabel') }}</label>
                                                                         
                                                                         <div v-if="block.questions && block.questions.length > 0" class="space-y-3">
                                                                             <div v-for="(q, qIdx) in block.questions" :key="qIdx" class="bg-white border border-slate-200 rounded-lg p-3 space-y-1.5">
                                                                                 <div class="flex items-center justify-between">
                                                                                     <span class="text-[10px] font-bold text-slate-500">Q{{ qIdx + 1 }}</span>
                <div class="flex items-center justify-end gap-1.5 sm:gap-2 w-full sm:w-auto">
                                                                                          <Dropdown v-model="q.type" :options="[
                                                                                              { label: t('pages.courses.qcmOption'), value: 'multiple_choice' },
                                                                                              { label: t('pages.courses.trueFalseOption'), value: 'true_false' },
                                                                                              { label: t('pages.courses.shortAnswerOption'), value: 'short_answer' }
                                                                                          ]" optionLabel="label" optionValue="value" class="w-32 h-7 text-[10px]" />
                                                                                         <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm p-button-rounded w-6 h-6" @click="removeQuestion(block, qIdx)" />
                                                                                     </div>
                                                                                 </div>
                                                                                  <InputText v-model="q.text" class="w-full bg-slate-50 border-slate-200 text-xs" :placeholder="t('pages.courses.questionTextPlaceholder')" />
                                                                                 
                                                                                 <!-- QCM -->
                                                                                 <div v-if="q.type === 'multiple_choice'" class="space-y-1">
                                                                                     <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-2">
                                                                                         <RadioButton v-model="q.correct_answer" :value="String(oIdx)" :inputId="`q${qIdx}_o${oIdx}`" />
                                                                                          <InputText v-model="q.options[oIdx]" class="flex-1 bg-slate-50 border-slate-200 text-xs" :placeholder="t('pages.courses.optionPlaceholder', { n: oIdx + 1 })" />
                                                                                         <Button icon="pi pi-times" class="p-button-text p-button-danger p-button-sm w-6 h-6" @click="removeQuestionOption(q, oIdx)" v-if="q.options.length > 2" />
                                                                                     </div>
                                                                                      <Button icon="pi pi-plus" :label="t('pages.courses.addOptionButton')" class="p-button-text p-button-sm text-xs" @click="addQuestionOption(q)" />
                                                                                 </div>
                                                                                 
                                                                                 <!-- Vrai/Faux -->
                                                                                 <div v-if="q.type === 'true_false'" class="flex justify-center">
                                                                                      <SelectButton v-model="q.correct_answer" :options="[{label: t('pages.courses.trueLabel'), value:'true'}, {label: t('pages.courses.falseLabel'), value:'false'}]" optionLabel="label" optionValue="value" class="w-full flex" :pt="{ button: { class: 'flex-1 font-black py-2 bg-white text-xs' } }" />
                                                                                 </div>
                                                                                 
                                                                                 <!-- RÃ©ponse courte -->
                                                                                 <div v-if="q.type === 'short_answer'" class="space-y-1">
                                                                                      <label class="text-[9px] font-bold text-slate-400 uppercase">{{ t('pages.courses.expectedAnswerLabel') }}</label>
                                                                                      <InputText v-model="q.correct_answer" class="w-full bg-slate-50 border-slate-200 text-xs font-mono" :placeholder="t('pages.courses.exactKeywordPlaceholder')" />
                                                                                 </div>
                                                                             </div>
                                                                         </div>
                                                                         
                                                                          <Button icon="pi pi-plus" :label="t('pages.courses.addQuestionButton')" class="p-button-outlined p-button-sm w-full border-dashed border-2" @click="addQuestion(block)" />
                                                                     </div>
                                                                 </div>

                                                                <!-- Mode: Choisir un quiz existant -->
                                                                <div v-if="block.quiz_mode === 'existing'" class="space-y-1.5">
                                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.assignedQuizLabel') }}</label>
                                                                    <Dropdown v-model="block.quiz_id" :options="props.exams || []" optionLabel="title" optionValue="id" :placeholder="t('pages.courses.selectExistingQuizPlaceholder')" class="w-full" showClear>
                                                                        <template #option="slotProps">
                                                                            <div class="flex items-center gap-2 p-2">
                                                                                <i class="pi pi-check-square text-emerald-500 text-xs"></i>
                                                                                <span class="text-xs font-bold">{{ slotProps.option.title }}</span>
                                                                                <Tag :value="slotProps.option.type" severity="info" class="text-[9px] ml-auto" />
                                                                            </div>
                                                                        </template>
                                                                    </Dropdown>
                                                                    <Message severity="info" class="text-xs">
                                                                        {{ t('pages.courses.existingQuizMessage') }}
                                                                    </Message>
                                                                </div>
                                                            </div>

                                                            <!-- Text Block -->
                                                            <div v-if="block.type === 'text'" class="space-y-1.5">
                                                                <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.textContentLabel') }}</label>
                                                                <Textarea v-model="block.content_text" rows="4" class="w-full bg-slate-50 border-slate-200 font-mono text-xs" :placeholder="t('pages.courses.textContentPlaceholder')"></Textarea>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-else class="text-center py-8 bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl text-slate-400">
                                                    <i class="pi pi-objects-column text-3xl mb-2 block"></i>
                                                    <p class="text-sm font-medium">{{ t('pages.courses.noContentAdded') }}</p>
                                                    <p class="text-xs mt-1">{{ t('pages.courses.useAddButtonsHint') }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 4. QUIZ DE FIN DE MODULE -->
                                        <div class="bg-blue-50/50 rounded-xl border border-blue-200 p-5 space-y-4">
                                            <h4 class="text-sm font-black text-blue-700 uppercase tracking-wider flex items-center gap-2">
                                                <i class="pi pi-check-square"></i> {{ t('pages.courses.moduleQuizTitle') }}
                                            </h4>
                                            
                                            <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-blue-100">
                                                <div>
                                                    <div class="font-bold text-sm text-slate-800">{{ t('pages.courses.mandatoryQuizLabel') }}</div>
                                                    <div class="text-[11px] text-slate-500">{{ t('pages.courses.mandatoryQuizHint') }}</div>
                                                </div>
                                                <ToggleSwitch v-model="section.has_quiz" />
                                            </div>

                                            <div v-if="section.has_quiz" class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2">
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.requiredLabel') }}</label>
                                                    <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-blue-100">
                                                        <Checkbox v-model="section.quiz_required" :binary="true" />
                                                        <span class="text-xs text-slate-600">{{ t('pages.courses.requiredShort') }}</span>
                                                    </div>
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.weightLabel') }}</label>
                                                    <InputNumber v-model="section.quiz_weight" :min="0" :max="100" class="w-full bg-white border-blue-100" placeholder="10" />
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.scoreLabel') }}</label>
                                                    <InputNumber v-model="section.quiz_passing_score" :min="0" :max="20" class="w-full bg-white border-blue-100" placeholder="10" />
                                                </div>
                                                <div class="space-y-1">
                                                    <label class="text-[10px] font-bold text-slate-500 uppercase">{{ t('pages.courses.attemptsLabel') }}</label>
                                                    <InputNumber v-model="section.quiz_attempts_allowed" :min="1" :max="10" class="w-full bg-white border-blue-100" placeholder="3" />
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 4 : OPTIMISATION SEO & DECOUVERABILITÃ‰ -->
                        <!-- ========================================== -->
                         <!-- ========================================== -->
<!-- TAB 4 : RESSOURCES & DOCUMENTS ATTACHÃ‰S    -->
<!-- ========================================== -->
<div v-show="activeEditorTab === 'resources'" class="space-y-5" role="tabpanel">

    <!-- HEADER & ACTION -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                <i class="pi pi-paperclip text-blue-600"></i> {{ t('pages.courses.resourcesLibraryTitle') }}
            </h3>
            <p class="text-sm text-slate-500 mt-1 max-w-xl">
                {{ t('pages.courses.resourcesLibraryDesc') }}
            </p>
        </div>
        <Button :label="t('pages.courses.attachFileButton')" icon="pi pi-plus" class="p-button-primary shadow-md font-bold" @click="addResource" />
    </div>

    <!-- LISTE DES RESSOURCES -->
    <div v-if="courseForm.resources.length > 0" class="grid grid-cols-1 gap-4 pb-10">
        <div v-for="(res, rIdx) in courseForm.resources" :key="rIdx"
             class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:border-blue-300 transition-all flex flex-col lg:flex-row items-center gap-6 group relative">
                
            <!-- Badge d'Ã©tat (Nouveau vs Existant) -->
            <div class="absolute -top-2 -left-2">
                <Tag v-if="res.is_new" :value="t('pages.courses.newTag')" severity="success" class="text-[8px] uppercase px-2 shadow-sm" />
                <Tag v-else :value="t('pages.courses.onlineTag')" severity="info" class="text-[8px] uppercase px-2 shadow-sm" />
            </div>

            <!-- Icone Dynamique basÃ©e sur le type -->
            <div class="w-16 h-16 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 flex-shrink-0 group-hover:bg-blue-50 transition-colors shadow-inner">
                <i :class="[getFileIcon(res.file_type || res.type), 'text-3xl']"></i>
            </div>

            <!-- Configuration de la ressource -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                <!-- Titre (Modifiable dans les deux cas) -->
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.courses.resourceNameLabel') }}</label>
                    <InputText v-model="res.title" class="w-full font-bold text-slate-700 bg-slate-50 border-transparent focus:bg-white p-3 rounded-xl" :placeholder="t('pages.courses.resourceTitlePlaceholder')" />
                </div>

                <!-- Gestion du fichier -->
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('pages.courses.sourceFileLabel') }}</label>
                    
                    <!-- Cas 1 : Aucune sÃ©lection (Nouveau) -->
                    <div v-if="!res.file && !res.full_url" class="w-full">
                        <FileUpload mode="basic" :chooseLabel="t('pages.courses.chooseLocalFileLabel')" @select="onResourceSelect($event, rIdx)" auto class="p-button-sm p-button-secondary w-full border-dashed border-2" />
                    </div>

                    <!-- Cas 2 : Fichier prÃ©sent (Local ou Serveur) -->
                    <div v-else class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-xs font-black text-slate-700 truncate max-w-[200px]">
                                {{ res.file ? res.file.name : t('pages.courses.hostedDoc') }}
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ res.human_size || (res.file ? (res.file.size / 1024 / 1024).toFixed(2) + ' MB' : t('pages.courses.unknownSize')) }}
                            </span>
                        </div>
                        
                        <!-- Actions sur le fichier -->
                        <div class="flex items-center gap-2">
                            <!-- Visualiser (Si PDF ou Image) -->
                            <Button v-if="res.full_url" icon="pi pi-eye" class="p-button-text p-button-secondary p-button-sm w-8 h-8" v-tooltip.top="t('pages.courses.viewTooltip')" @click="viewResource(res.full_url)" />
                            
                            <!-- TÃ©lÃ©charger -->
                            <Button v-if="res.full_url" icon="pi pi-download" class="p-button-text p-button-info p-button-sm w-8 h-8" v-tooltip.top="t('pages.courses.downloadTooltip')" @click="downloadResource(res.full_url)" />
                            
                            <!-- Remplacer -->
                            <Button icon="pi pi-refresh" class="p-button-text p-button-warning p-button-sm w-8 h-8" v-tooltip.top="t('pages.courses.replaceFileTooltip')" @click="replaceFile(rIdx)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton Suppression (CRUD: Delete) -->
            <div class="flex-shrink-0 pt-4 lg:pt-0">
                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded bg-white border border-slate-100 hover:bg-red-50 shadow-sm" @click="confirmRemoveResource(rIdx)" v-tooltip.left="t('pages.courses.deleteForeverTooltip')" />
            </div>
        </div>
    </div>

    <!-- Ã‰TAT VIDE -->
    <div v-else class="bg-white p-16 rounded-3xl border-2 border-dashed border-slate-200 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-300">
            <i class="pi pi-paperclip text-4xl"></i>
        </div>
      
        <h4 class="text-xl font-black text-slate-800 mb-2">{{ t('pages.courses.emptyLibraryTitle') }}</h4>
        <p class="text-slate-500 text-sm max-w-sm mx-auto mb-8">{{ t('pages.courses.emptyLibraryDesc') }}</p>
        <Button :label="t('pages.courses.addFirstResourceButton')" icon="pi pi-plus" class="p-button-primary p-button-lg font-bold shadow-lg" @click="addResource" />
    </div>
</div>
                        <div v-show="activeEditorTab === 'seo'" class="space-y-5" role="tabpanel">

                            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 flex gap-4 items-start shadow-sm">
                                <i class="pi pi-lightbulb text-blue-500 text-2xl mt-1"></i>
                                <div>
                                    <h4 class="font-bold text-blue-900 mb-1">{{ t('pages.courses.seoWhyTitle') }}</h4>
                                    <p class="text-sm text-blue-700 leading-relaxed">{{ t('pages.courses.seoWhyDesc') }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
                                <!-- Formulaire SEO (Gauche) -->
                                <div class="xl:col-span-7 space-y-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                    <h3 class="text-lg font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2 flex items-center gap-2"><i class="pi pi-search text-slate-400"></i> {{ t('pages.courses.metaTagsTitle') }}</h3>

                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-end mb-1">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.seoTitleLabel') }}</label>
                                            <span class="text-[10px] font-mono" :class="courseForm.seo_metadata.title?.length > 60 ? 'text-red-500 font-bold' : 'text-slate-400'">{{ courseForm.seo_metadata.title?.length || 0 }} / 60 {{ t('pages.courses.seoCharOptimized') }}</span>
                                        </div>
                                        <InputText v-model="courseForm.seo_metadata.title" fluid :placeholder="t('pages.courses.seoTitlePlaceholder')" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-end mb-1">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.metaDescriptionLabel') }}</label>
                                            <span class="text-[10px] font-mono" :class="courseForm.seo_metadata.description?.length > 160 ? 'text-red-500 font-bold' : 'text-slate-400'">{{ courseForm.seo_metadata.description?.length || 0 }} / 160 {{ t('pages.courses.seoCharOptimized') }}</span>
                                        </div>
                                        <Textarea v-model="courseForm.seo_metadata.description" rows="4" fluid class="leading-relaxed" :placeholder="t('pages.courses.metaDescriptionPlaceholder')" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">{{ t('pages.courses.focusKeywordsLabel') }}</label>
                                        <IconField iconPosition="left">
                                            <InputIcon class="pi pi-hashtag text-slate-400" />
                                            <InputText v-model="courseForm.seo_metadata.keywords" fluid :placeholder="t('pages.courses.keywordsPlaceholder')" />
                                        </IconField>
                                        <small class="text-slate-400 text-[11px]">{{ t('pages.courses.keywordsHelp') }}</small>
                                    </div>
                                </div>

                                <!-- Previews Live (Droite) -->
                                <div class="xl:col-span-5 space-y-6">

                                    <!-- Google Preview -->
                                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-google text-blue-500"></i> {{ t('pages.courses.serpPreviewTitle') }}
                                        </h4>
                                        <div class="p-4 bg-white border border-slate-100 rounded-xl shadow-[0_2px_10px_rgba(0,0,0,0.05)] cursor-text selection:bg-blue-100">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-7 h-7 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">SC</div>
                                                <div>
                                                    <div class="text-[13px] text-slate-800 font-medium leading-none">SubnetCongo LMS</div>
                                                    <div class="text-[11px] text-slate-500 truncate max-w-[200px] mt-0.5">https://subnetcongo.net/courses/{{ courseForm.slug || t('pages.courses.slugFallback') }}</div>
                                                </div>
                                                <i class="pi pi-ellipsis-v ml-auto text-slate-400 text-xs"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-[#1a0dab] truncate mb-1 select-none" style="font-family: Arial, sans-serif;">{{ seoPreviewTitle }}</h3>
                                            <p class="text-sm text-[#4d5156] line-clamp-2 leading-[1.58]" style="font-family: Arial, sans-serif;">{{ seoPreviewDesc }}</p>
                                        </div>
                                    </div>

                                    <!-- Social Preview (OpenGraph) Mockup -->
                                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                            <i class="pi pi-linkedin text-blue-700"></i> {{ t('pages.courses.socialPreviewTitle') }}
                                        </h4>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50 shadow-sm">
                                            <div class="h-32 bg-slate-200 border-b border-slate-200 relative">
                                                <img v-if="courseForm.thumbnail" :src="typeof courseForm.thumbnail === 'string' ? courseForm.thumbnail : (courseForm.thumbnail && typeof File !== 'undefined' && courseForm.thumbnail instanceof File ? URL.createObjectURL(courseForm.thumbnail) : '')" class="w-full h-full object-cover" />
                                                <div v-else class="absolute inset-0 flex items-center justify-center text-slate-400 text-xs font-bold uppercase tracking-widest"><i class="pi pi-image mr-2 text-lg"></i> {{ t('pages.courses.openGraphImage') }}</div>
                                            </div>
                                            <div class="p-3 bg-slate-100/50">
                                                <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-1">subnetcongo.net</div>
                                                <div class="text-sm font-bold text-slate-800 truncate mb-1">{{ seoPreviewTitle }}</div>
                                                <div class="text-[11px] text-slate-500 truncate">{{ seoPreviewDesc }}</div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- TAB 5 : PARAMÃˆTRES AVANCÃ‰S                 -->
                        <!-- ========================================== -->
                        <div v-show="activeEditorTab === 'settings'" class="space-y-5" role="tabpanel">

                            <!-- ParamÃ¨tres Globaux -->
                            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-lg font-bold text-slate-800 mb-3 border-b border-slate-100 pb-2">{{ t('pages.courses.settingsTitle') }}</h3>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                                    <!-- Options Toggles -->
                                    <div class="space-y-4 col-span-1">
                                        <!-- Featured -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-amber-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-star-fill"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ t('pages.courses.featuredTitle') }}</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">{{ t('pages.courses.featuredDesc') }}</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.is_featured" />
                                        </div>

                                        <!-- Certificat -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-blue-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-verified"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ t('pages.courses.certificateTitle') }}</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">{{ t('pages.courses.certificateDesc') }}</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.certificate_enabled" />
                                        </div>

                                        <!-- Inscription -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-emerald-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-sign-in"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ t('pages.courses.enrollmentTitle') }}</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">{{ t('pages.courses.enrollmentDesc') }}</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.requires_enrollment" />
                                        </div>

                                        <!-- Examen Final -->
                                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-colors">
                                            <div class="flex gap-4">
                                                <div class="w-10 h-10 bg-white border border-slate-200 text-red-500 rounded-full flex items-center justify-center shadow-sm flex-shrink-0"><i class="pi pi-check-square"></i></div>
                                                <div>
                                                    <div class="font-bold text-slate-800 text-sm">{{ t('pages.courses.finalExamTitle') }}</div>
                                                    <div class="text-xs text-slate-500 mt-1 leading-tight">{{ t('pages.courses.finalExamDesc') }}</div>
                                                </div>
                                            </div>
                                            <ToggleSwitch v-model="courseForm.has_final_exam" />
                                        </div>
                                    </div>

                                    <!-- Quiz & Certification Settings -->
                                    <div class="space-y-6 col-span-1" v-if="courseForm.has_final_exam">
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.passingScoreLabel') }}</label>
                                            <InputNumber v-model="courseForm.passing_score_20" :min="0" :max="20" class="w-full" placeholder="Ex: 10" />
                                            <div class="text-[10px] text-slate-500">{{ t('pages.courses.passingScoreHint') }}</div>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.finalExamWeightLabel') }}</label>
                                            <InputNumber v-model="courseForm.final_exam_weight" :min="0" :max="100" class="w-full" placeholder="Ex: 70" />
                                            <div class="text-[10px] text-slate-500">{{ t('pages.courses.finalExamWeightHint') }}</div>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.quizTotalWeightLabel') }}</label>
                                            <InputNumber v-model="courseForm.quiz_weight_total" :min="0" :max="100" class="w-full" placeholder="Ex: 80" />
                                            <div class="text-[10px] text-slate-500">{{ t('pages.courses.quizTotalWeightHint') }}</div>
                                        </div>
                                    </div>

                                    <!-- Attribution & Infos annexes -->
                                    <div class="space-y-6 col-span-1">
                                        <div class="space-y-1.5">
                                            <label class="block text-sm font-bold text-slate-700 flex items-center gap-2"><i class="pi pi-user text-blue-500"></i> {{ t('pages.courses.instructorLabel') }} <span class="text-red-500">*</span></label>
                                            <Dropdown v-model="courseForm.instructor_id" :options="props.instructors" optionLabel="name" optionValue="id" :placeholder="t('pages.courses.instructorPlaceholder')" fluid class="font-medium" filter :invalid="!courseForm.instructor_id">
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                                            {{ props.instructors.find(i => i.id === slotProps.value)?.name.charAt(0) }}
                                                        </div>
                                                        <span>{{ props.instructors.find(i => i.id === slotProps.value)?.name }}</span>
                                                    </div>
                                                    <span v-else class="text-slate-400">{{ t('pages.courses.assignInstructor') }}</span>
                                                </template>
                                            </Dropdown>
                                        </div>

                                        <div class="space-y-1.5 mt-4">
                                            <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.prerequisitesLabel') }}</label>
                                            <Textarea v-model="courseForm.prerequisites" rows="3" fluid :placeholder="t('pages.courses.prerequisitesPlaceholder')" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- WORKSPACE FOOTER (ACTIONS DE SAUVEGARDE) -->
            <div class="flex-none bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 px-2 sm:px-6 py-2 sm:py-3 z-30 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div class="flex items-center gap-4">
                    <Button :label="t('pages.courses.deleteDraftButton')" icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm hidden md:flex font-medium" @click="courseDialog = false" v-if="!courseForm.id" />
                    <div class="hidden sm:flex items-center gap-2 text-[11px] font-black text-slate-400 uppercase tracking-widest">
                        <i class="pi pi-chart-bar text-red-500"></i>
                        <span>{{ completionPercent }}%</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        :label="t('pages.courses.prevStep')"
                        icon="pi pi-arrow-left"
                        iconPos="left"
                        class="p-button-outlined p-button-secondary font-bold bg-white text-slate-600 hover:bg-slate-50 border-slate-300 hidden sm:flex"
                        :disabled="currentStepIndex <= 0"
                        @click="goToPrevStep"
                    />
                    <Button
                        :label="t('pages.courses.nextStep')"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        class="p-button-outlined p-button-secondary font-bold bg-white text-slate-600 hover:bg-slate-50 border-slate-300 hidden sm:flex"
                        :disabled="currentStepIndex < 0 || currentStepIndex >= editorMenu.length - 1"
                        @click="goToNextStep"
                    />
                    <div class="w-px h-6 bg-slate-200 mx-1 hidden sm:block"></div>
                    <Button :label="t('pages.courses.saveDraftButton')" icon="pi pi-save" class="p-button-outlined p-button-secondary font-bold bg-white text-slate-700 hover:bg-slate-50 border-slate-300 !px-3 sm:!px-5 !text-xs sm:!text-sm flex-1 sm:flex-none min-w-0" @click="saveCourse('draft')" :loading="courseForm.processing" />

                    <div class="relative group">
                        <Button :label="t('pages.courses.publishButton')" icon="pi pi-rocket" class="!bg-red-600 hover:!bg-red-700 !border-none shadow-lg shadow-red-500/30 font-bold !px-4 sm:!px-8 !text-xs sm:!text-sm flex-1 sm:flex-none min-w-0" @click="saveCourse('publish')" :loading="courseForm.processing" :disabled="!isEntireFormValid" />
                        <!-- Tooltip custom si bloquÃ© -->
                        <div v-if="!isEntireFormValid" class="absolute bottom-full right-0 mb-2 w-64 bg-slate-800 text-white text-xs p-3 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50">
                            {{ t('pages.courses.publishBlockedHintStart') }} <i class="pi pi-exclamation-circle text-amber-400"></i> {{ t('pages.courses.publishBlockedHintEnd') }}
                            <div class="absolute -bottom-1 right-8 w-2 h-2 bg-slate-800 transform rotate-45"></div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG CRÃ‰ATION DE CATÃ‰GORIE (TAXONOMIE)                                       -->
        <!-- ============================================================================== -->
        <SncDialog v-model:visible="categoryDialog" :title="t('pages.courses.createCategoryHeader')" icon="pi-tags" width="500px">
            <div class="space-y-6">
                <!-- Message info -->
                <Message severity="info" :closable="false" class="m-0 border border-blue-100 bg-blue-50 text-blue-800">
                    {{ t('pages.courses.categoryInfoMessage') }}
                </Message>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.categoryNameLabel') }} <span class="text-red-500">*</span></label>
                        <InputText v-model="categoryForm.name" @input="generateCategorySlug" :placeholder="t('pages.courses.categoryNamePlaceholder')" class="w-full p-3 font-bold text-slate-800 bg-white border-slate-300" autofocus :class="{'p-invalid': categoryForm.errors?.name}" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.slugFieldLabel') }}</label>
                        <InputText v-model="categoryForm.slug" placeholder="developpement-web" class="w-full p-2 text-sm font-mono text-slate-500 bg-slate-100 border-transparent" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.parentCategoryLabel') }}</label>
                        <Dropdown v-model="categoryForm.parent_id" :options="props.categories" optionLabel="name" optionValue="id" :placeholder="t('pages.courses.rootCategoryOption')" class="w-full border-slate-300 bg-white" showClear filter />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">{{ t('pages.courses.categoryDescLabel') }}</label>
                        <Textarea v-model="categoryForm.description" rows="2" class="w-full bg-white border-slate-300" :placeholder="t('pages.courses.categoryDescPlaceholder')" />
                    </div>

                    <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 mt-2">
                        <div>
                            <div class="font-bold text-slate-800 text-sm">{{ t('pages.courses.publicVisibility') }}</div>
                            <div class="text-[11px] text-slate-500">{{ t('pages.courses.publicVisibilityDesc') }}</div>
                        </div>
                        <ToggleSwitch v-model="categoryForm.is_active" />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button :label="t('pages.courses.cancel')" class="p-button-text p-button-secondary font-bold text-slate-600 hover:bg-slate-100" @click="categoryDialog = false" />
                <Button :label="t('pages.courses.createCategoryButton')" icon="pi pi-check" class="p-button-primary font-bold shadow-md px-6" @click="saveCategory" :loading="savingCategory" />
            </template>
        </SncDialog>

        <!-- ============================================================================== -->
        <!-- DIALOG ANALYTICS (INSIGHTS)                                                    -->
        <!-- ============================================================================== -->
        <SncDialog v-model:visible="analyticsDialog" :title="t('pages.courses.analyticsTitle')" icon="pi-chart-line" width="600px">

            <div class="space-y-8">

                <!-- Graphe de revenus -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-chart-bar"></i> {{ t('pages.courses.revenueEvolutionTitle') }}</h4>
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-baseline gap-2 mb-4">
                            <span class="text-2xl font-black text-slate-800">$74,000</span>
                            <span class="text-xs text-emerald-500 font-bold"><i class="pi pi-arrow-up text-[10px]"></i> {{ t('pages.courses.revenueGrowthLabel') }}</span>
                        </div>
                        <Chart type="line" :data="revenueChartData" :options="chartOptions" class="h-48 w-full" />
                    </div>
                </div>

                <!-- Top Formations -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-trophy"></i> {{ t('pages.courses.bestSellersTitle') }}</h4>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <ul class="divide-y divide-slate-100">
                            <!-- Mockup dynamique -->
                            <li v-for="i in 4" :key="i" class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 font-bold text-xs border border-slate-200 group-hover:border-blue-300 transition-colors">
                                        #{{ i }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800 mb-0.5">{{ t('pages.courses.advancedCourseLabel', { n: i }) }}</div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1"><i class="pi pi-users text-[9px]"></i> {{ t('pages.courses.enrolledCount', { n: 1500 - (i * 250) }) }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-blue-600">${{ (12000 - (i * 1500)).toLocaleString() }}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Alertes & QualitÃ© -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="pi pi-shield"></i> {{ t('pages.courses.qualityAlertsTitle') }}</h4>
                    <div class="space-y-3">
                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 flex gap-3 items-start shadow-sm">
                            <i class="pi pi-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <div>
                                <div class="text-sm font-bold text-amber-900">{{ t('pages.courses.coursesNeedUpdate') }}</div>
                                <div class="text-xs text-amber-700 mt-1 leading-tight">{{ t('pages.courses.completionDropHint') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button :label="t('pages.courses.closeButton')" class="p-button-secondary p-button-text font-bold" @click="analyticsDialog = false" />
            </template>
        </SncDialog>

        <MediaPicker v-model:visible="mediaPickerOpen" accept="both" @insert="insertMediaIntoDescription" />
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
 * L'intÃ©rieur est gÃ©rÃ© par Flexbox pour faire scroller uniquement le contenu.
 */
.custom-dialog-height {
    height: 85vh !important;
    max-height: 85vh !important;
}

/* Scrollbar personnalisÃ©e (Style macOS / SaaS moderne) */
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

/* EnlÃ¨ve la bordure du Toolbar PrimeVue */
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

/* Ombre intÃ©rieure pour le panel d'expansion */
.inset-shadow {
    box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.02);
}

/* ------------------------------------------------------------------
   Workspace cours : harmonisation des champs de formulaire.
   La gÃ©omÃ©trie et les couleurs des champs (InputText, Textarea,
   Select, InputNumberâ€¦) sont portÃ©es par les design tokens du preset
   PrimeVue (resources/js/app.js, groupe formField) â€” aucune surcharge
   CSS ici : les inputs et les selects partagent exactement le mÃªme
   style (variant filled, arrondi, focus, invalide).
------------------------------------------------------------------- */
/* Labels : typo homogÃ¨ne dans le dialog */
:deep(.course-workspace label) {
    font-size: 0.8rem;
    font-weight: 700;
    color: #475569;
    letter-spacing: 0.01em;
}
</style>
