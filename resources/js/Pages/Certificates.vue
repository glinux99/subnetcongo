<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - CERTIFICATE ARCHITECT & TRUST CENTER v12.0.0
 * ======================================================================================
 * Module de pilotage de la valeur académique et d'édition de diplômes.
 * Synchronisé avec CertificateController (Laravel).
 *
 * @author  SubnetCongo Framework Division
 * @module  Credentialing/TrustCenter
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

// Composants PrimeVue (inchangés)
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import FileUpload from 'primevue/fileupload';
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
import InputSwitch from 'primevue/inputswitch';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Breadcrumb from 'primevue/breadcrumb';
import Chart from 'primevue/chart';
import Message from 'primevue/message';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Timeline from 'primevue/timeline';
import Tooltip from 'primevue/tooltip';
import OverlayPanel from 'primevue/overlaypanel';
import MultiSelect from 'primevue/multiselect';
import Textarea from 'primevue/textarea';

// ======================================================================================
// PROPS INERTIA (reçues depuis CertificateController@index)
// ======================================================================================
const props = defineProps({
    certificates: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    templates: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_issued: 0, success_rate: 0, avg_score: 0, revocation_rate: 0 }) },
    filters: { type: Object, default: () => ({}) },
     meta: {
        type: Object,
        default: () => ({ fonts: ['Montserrat', 'Playfair Display', 'Roboto', 'Open Sans', 'Courier Prime'] })
    }
});

// ======================================================================================
// SERVICES & ÉTATS GLOBAUX
// ======================================================================================
const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const searchLoading = ref(false);

const activeMainTab = ref('log'); // 'log' ou 'templates'
const expandedRows = ref([]);
const selectedCertificates = ref([]);

// Modals & Sidebars
const designerDialog = ref(false);
const revocationDialog = ref(false);
const bulkIssuanceDialog = ref(false);
const analyticsSidebar = ref(false);

const isSaving = ref(false);
const isGeneratingPdf = ref(false);

// ======================================================================================
// FILTRAGE RÉACTIF (recherche et filtres)
// ======================================================================================
const serverFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || null,
    course_id: props.filters.course_id || null,
});

watch(serverFilters, debounce(() => {
    searchLoading.value = true;
    router.get(route('certificates.index'), serverFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => searchLoading.value = false
    });
}, 400));

// ======================================================================================
// FORMULAIRES INERTIA (useForm)
// ======================================================================================

/** Formulaire du Designer Visuel (création / mise à jour d'un template) */
// --- ÉTAT DU FORMULAIRE ---

const activeDragKey = ref('');
const dragStartY = ref(0);
const elementStartTop = ref(0);
const defaultFilters = () => ({
    brightness: 100,
    contrast: 100,
    saturate: 100,
    grayscale: 0,
    hueRotate: 0,
    invert: false,
    blur: 0
});
const templateForm = reactive({
    name: "Certificat de Réussite Prestige",
    orientation: 'landscape',
    background_color: '#ffffff',
    bg_opacity: 100,
    text_color: '#1e293b',
    border_color: '#c5a059', // Doré
    border_width: 25,
    primary_font: 'Montserrat',
    accent_font: 'Playfair Display',
     bg_filters: {
        blur: 0,
        brightness: 100,
        contrast: 100,
        grayscale: 0,
        hueRotate: 0
    },
    // Media
    show_logo: true,
    logo_file: null,
    logo_scale: 15,
    logo_top: 10,

    show_background: false,
    background_image_file: null,
    background_opacity: 15,

    show_signature: true,
    signature_file: null,

    show_qr: true,

    // Deletion flags
    delete_logo: false,
    delete_background: false,
    delete_signature: false,

    elements: [
        {
            id: 'el_title',
            type: 'text',
            name: 'Titre Principal',
            content: "CERTIFICAT DE RÉUSSITE",
            x: 300, y: 150, width: 500, rotation: 0,
            filters: defaultFilters(),
            style: { fontSize: 48, color: '#ffffff', fontFamily: 'Montserrat', fontWeight: '900', letterSpacing: 8, textAlign: 'center' }
        },
        {
            id: 'el_subtitle',
            type: 'text',
            name: 'Sous-Titre',
            content: "Ceci est décerné avec les honneurs à",
            x: 350, y: 320, width: 400, rotation: 0,
            filters: defaultFilters(),
            style: { fontSize: 18, color: '#ffffff', fontFamily: 'Montserrat', fontWeight: '400', letterSpacing: 2, textAlign: 'center' }
        }
    ]
});

// --- PREVIEWS (BASE64) ---
const logoPreview = ref(null);
const backgroundPreview = ref(null);
const signaturePreview = ref(null);

// --- LOGIQUE DRAG & DROP ---
const onDragStart = (event, key) => {
    activeDragKey.value = key;
    dragStartY.value = event.clientY;
    elementStartTop.value = templateForm.elements[key].top;

    // Ajouter une classe au body pour éviter la sélection de texte pendant le drag
    document.body.style.userSelect = 'none';
};

const onDragMove = (event) => {
    if (!activeDragKey.value) return;

    const canvas = document.getElementById('certificate-canvas');
    const canvasRect = canvas.getBoundingClientRect();

    // Calcul du delta en pixels
    const deltaY = event.clientY - dragStartY.value;

    // Conversion du delta pixels en pourcentage par rapport à la hauteur du canvas
    const deltaPercentage = (deltaY / canvasRect.height) * 100;

    // Mise à jour de la position avec limites (0-95%)
    let newTop = elementStartTop.value + deltaPercentage;
    templateForm.elements[activeDragKey.value].top = Math.min(Math.max(0, newTop), 95).toFixed(2);
};

const onDragEnd = () => {
    activeDragKey.ref = null;
    document.body.style.userSelect = 'auto';
};

// --- GESTION DES FICHIERS ---
const onLogoSelect = (event) => {
    const file = event.files[0];
    templateForm.logo_file = file;
    templateForm.delete_logo = false;
    const reader = new FileReader();
    reader.onload = (e) => {
        logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const onBackgroundSelect = (event) => {
    const file = event.files[0];
    templateForm.background_image_file = file;
    templateForm.delete_background = false;
    const reader = new FileReader();
    reader.onload = (e) => {
        backgroundPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const onSignatureSelect = (event) => {
    const file = event.files[0];
    templateForm.signature_file = file;
    templateForm.delete_signature = false;
    const reader = new FileReader();
    reader.onload = (e) => {
        signaturePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

// --- STYLES CALCULÉS ---
const canvasStyle = computed(() => {
    const isLandscape = templateForm.orientation === 'landscape';
    // Ratio A4 (1.414)
    const baseWidth = isLandscape ? 1120 : 792;
    const baseHeight = isLandscape ? 792 : 1120;

    return {
        width: `${baseWidth}px`,
        height: `${baseHeight}px`,
        minWidth: `${baseWidth}px`,
        minHeight: `${baseHeight}px`
    };
});

const borderStyle = computed(() => {
    const margin = 30;
    return {
        top: `${margin}px`,
        left: `${margin}px`,
        right: `${margin}px`,
        bottom: `${margin}px`,
        border: `${templateForm.border_width}px solid ${templateForm.border_color}`,
        outline: `2px solid ${templateForm.border_color}`,
        outlineOffset: '8px'
    };
});

const getElementStyle = (el, isTitle = false) => {
    return {
        fontSize: `${el.size}px`,
        letterSpacing: `${el.letter_spacing}px`,
        fontFamily: isTitle ? templateForm.accent_font : 'inherit',
        fontWeight: isTitle ? '900' : 'normal',
        lineHeight: '1.4'
    };
};

const getElementName = (key) => {
    const names = { title: 'Titre', subtitle: 'Sous-Titre', body: 'Corps de texte' };
    return names[key] || key;
};


/**
 * ======================================================================================
 * QUANTUM ENGINE LOGIC : SYNC & DYNAMICS
 * ======================================================================================
 */

// --- ÉTATS MOTEUR QUANTUM ---
const canvasZoom = ref(60);
const selectedElementId = ref(null);
const draggingElementId = ref(null);
const dragOffset = reactive({ x: 0, y: 0 });

// Filtres par défaut pour les nouveaux éléments images


// --- COMPUTED : QUANTUM STYLE ENGINE ---

/** Style du conteneur principal A4 */
const quantumCanvasStyle = computed(() => {
    const isLandscape = templateForm.orientation === 'landscape';
    const width = isLandscape ? 1120 : 792;
    const height = isLandscape ? 792 : 1120;

    return {
        width: `${width}px`,
        height: `${height}px`,
        transform: `scale(${canvasZoom.value / 100})`,
        backgroundColor: templateForm.background_color,
        transition: draggingElementId.value ? 'none' : 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)'
    };
});

/** Générateur de filtres CSS matriciels */
const quantumFilterStyle = (filters) => {
    if (!filters) return {};
    return {
        filter: `
            brightness(${filters.brightness}%)
            contrast(${filters.contrast}%)
            grayscale(${filters.grayscale}%)
            saturate(${filters.saturate ?? 100}%)
            hue-rotate(${filters.hueRotate}deg)
            invert(${filters.invert ? 100 : 0}%)
            blur(${filters.blur || 0}px)
        `.replace(/\s+/g, ' ')
    };
};

/** Style dynamique d'un élément (Positionnement & Géométrie) */
const quantumElementStyle = (el) => {
    const isActive = selectedElementId.value === el.id;
    const zIndex = Array.isArray(templateForm.elements)
        ? templateForm.elements.indexOf(el) + 10
        : 10;
    return {
        left: `${el.x}px`,
        top: `${el.y}px`,
        width: el.width ? `${el.width}px` : 'auto',
        height: el.height ? `${el.height}px` : 'auto',
        transform: `rotate(${el.rotation || 0}deg)`,
        zIndex: zIndex,
        cursor: draggingElementId.value === el.id ? 'grabbing' : 'grab',
        outline: isActive ? '2px solid #6366f1' : 'none',
        transition: draggingElementId.value === el.id ? 'none' : 'box-shadow 0.2s ease'
    };
};

/** Style spécifique pour les blocs de texte */
const quantumTextStyle = (el) => ({
    fontSize: `${el.style.fontSize}px`,
    color: el.style.color,
    fontFamily: el.style.fontFamily,
    letterSpacing: `${el.style.letterSpacing}px`,
    fontWeight: el.style.fontWeight,
    fontStyle: el.style.fontStyle,
    textTransform: el.style.textTransform,
    textDecoration: el.style.textDecoration,
    textAlign: el.style.textAlign,
    lineHeight: el.style.lineHeight || 1.2
});

// --- ACTIONS : GESTION DES ÉLÉMENTS ---

/** Ajout d'un nouvel élément sur le canvas */
const addElement = (type, variant = 'standard') => {
    const newId = `quantum_${Date.now()}`;
    const newElement = {
        id: newId,
        type: type,
        name: `${type.toUpperCase()} Element`,
        x: 100,
        y: 100,
        width: type === 'text' ? 400 : 200,
        rotation: 0,
        content: variant === 'h1' ? 'TITRE DE DISTINCTION' : (type === 'text' ? 'Nouveau bloc de texte' : ''),
        filters: defaultFilters(),
        style: type === 'text' ? {
            fontSize: variant === 'h1' ? 48 : 18,
            color: templateForm.text_color,
            fontFamily: templateForm.primary_font,
            fontWeight: variant === 'h1' ? '900' : '400',
            letterSpacing: 0,
            textAlign: 'center',
            textTransform: variant === 'h1' ? 'uppercase' : 'none'
        } : {}
    };

    templateForm.elements.push(newElement);
    selectedElementId.value = newId;
};

/** Duplication d'un élément existant */
const duplicateElement = (el) => {
    const clone = cloneDeep(el);
    clone.id = `quantum_copy_${Date.now()}`;
    clone.x += 30;
    clone.y += 30;
    templateForm.elements.push(clone);
    selectedElementId.value = clone.id;
};

/** Suppression d'un élément */
const removeElement = (id) => {
    const index = templateForm.elements.findIndex(e => e.id === id);
    if (index !== -1) {
        templateForm.elements.splice(index, 1);
        if (selectedElementId.value === id) selectedElementId.value = null;
    }
};

/** Toggle des styles (Bold, Italic, Uppercase) */
const toggleStyle = (el, prop, val1, val2) => {
    el.style[prop] = el.style[prop] === val1 ? val2 : val1;
};

/** Gestion des calques (Monter/Descendre) */
const reorderLayer = (el, direction) => {
    const idx = templateForm.elements.indexOf(el);
    if (direction === 'up' && idx < templateForm.elements.length - 1) {
        const temp = templateForm.elements[idx + 1];
        templateForm.elements[idx + 1] = templateForm.elements[idx];
        templateForm.elements[idx] = temp;
    } else if (direction === 'down' && idx > 0) {
        const temp = templateForm.elements[idx - 1];
        templateForm.elements[idx - 1] = templateForm.elements[idx];
        templateForm.elements[idx] = temp;
    }
};

// --- LOGIQUE DRAG & DROP QUANTUM ---

const startElementDrag = (event, el) => {
    selectedElementId.value = el.id;
    draggingElementId.value = el.id;

    const canvas = document.getElementById('snc-quantum-canvas');
    const rect = canvas.getBoundingClientRect();
    const zoom = canvasZoom.value / 100;

    // Calculer l'offset pour que la souris ne "saute" pas au centre
    dragOffset.x = (event.clientX - rect.left) / zoom - el.x;
    dragOffset.y = (event.clientY - rect.top) / zoom - el.y;

    document.addEventListener('mousemove', handleGlobalDrag);
    document.addEventListener('mouseup', stopGlobalDrag);
};

const handleGlobalDrag = (event) => {
    if (!draggingElementId.value) return;

    const el = templateForm.elements.find(e => e.id === draggingElementId.value);
    const canvas = document.getElementById('snc-quantum-canvas');
    const rect = canvas.getBoundingClientRect();
    const zoom = canvasZoom.value / 100;

    // Calculer les nouvelles coordonnées avec le facteur de zoom
    el.x = Math.round((event.clientX - rect.left) / zoom - dragOffset.x);
    el.y = Math.round((event.clientY - rect.top) / zoom - dragOffset.y);
};



// --- GESTION DES MÉDIAS (UPLOAD & FILTRES) ---

const uploadAsset = (event, type) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const content = e.target.result;
        if (type === 'background') {
            backgroundPreview.value = content;
            templateForm.background_image_file = file;
        } else {
            // Créer un nouvel élément image sur le canvas
            const newId = `quantum_img_${Date.now()}`;
            templateForm.elements.push({
                id: newId,
                type: 'image',
                name: type === 'logo' ? 'Institution Logo' : 'Signature',
                content: content,
                x: 100, y: 100, width: 200, height: 100,
                rotation: 0,
                filters: defaultFilters()
            });
            selectedElementId.value = newId;
        }
    };
    reader.readAsDataURL(file);
};

/** Synchronisation du texte en temps réel via contenteditable */
const handleTextEdit = (event, el) => {
    el.content = event.target.innerText;
};

/** Basculer l'orientation du canvas */
const toggleOrientation = () => {
    templateForm.orientation = templateForm.orientation === 'landscape' ? 'portrait' : 'landscape';
    toast.add({ severity: 'info', summary: 'Orientation', detail: `Canevas basculé en mode ${templateForm.orientation}`, life: 2000 });
};

const getElementIcon = (type) => {
    const icons = { text: 'pi-align-left', image: 'pi-image', qr: 'pi-qrcode' };
    return icons[type] || 'pi-box';
};

/** Sélection de l'élément actif */
const selectedElement = computed(() => {
    // return templateForm.elements.find(e => e.id === selectedElementId.value) || null;
    return  null;
});

// --- SYNC AVEC BACKEND ---

/** Écrase saveTemplate pour gérer le tableau d'éléments JSON */
const publishQuantumDesign = () => {
    isSaving.value = true;

    // On prépare une copie propre pour l'envoi
    const payload = cloneDeep(templateForm);
    payload.background_color = payload.background_color.replace('#', '');
    payload.border_color = payload.border_color.replace('#', '');
    payload.text_color = payload.text_color.replace('#', '');

    // Inertia gère le multipart/form-data automatiquement avec useForm ou router.post
    router.post(route('certificates.templates.store'), payload, {
        forceFormData: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Quantum Sync', detail: 'Design publié avec succès.' });
            designerDialog.value = false;
        },
        onFinish: () => isSaving.value = false
    });
};

/** Formulaire d'émission en masse */
const bulkIssueForm = useForm({
    course_id: null,
    template_id: null,
    notify_users: true
});

/** Formulaire de révocation */
const revocationForm = useForm({
    id: null,
    reason: '',
    confirm_action: false
});

// ======================================================================================
// LOGIQUE MÉTIER : TEMPLATE DESIGNER
// ======================================================================================

/** Ouvre le designer pour créer ou éditer un template */
const openDesigner = (template = null) => {
    // Reset file inputs and flags for both new and edit
    templateForm.logo_file = null;
    templateForm.background_image_file = null;
    templateForm.signature_file = null;
    templateForm.delete_logo = false;
    templateForm.delete_background = false;
    templateForm.delete_signature = false;

    if (template && template.id) { // Check for template.id to ensure it's an existing template
        // Reset previews
        logoPreview.value = template.logo_url || null;
        backgroundPreview.value = template.background_url || null;
        signaturePreview.value = template.signature_url || null;
        const t = cloneDeep(template);
        templateForm.id = t.id;
        templateForm.name = t.name;
        // Les couleurs sont stockées sans # dans la BDD, mais le front les utilise avec #
        templateForm.background_color = t.background_color ? `#${t.background_color.replace('#', '')}` : '#ffffff';
        templateForm.border_color = t.border_color ? `#${t.border_color.replace('#', '')}` : '#4f46e5';
        templateForm.border_width = t.border_width;
        templateForm.primary_font = t.primary_font;
        templateForm.accent_font = t.accent_font || 'Playfair Display';
        templateForm.text_color = t.text_color ? `#${t.text_color.replace('#', '')}` : '#1e293b';
        templateForm.show_qr = t.show_qr ?? true;
        templateForm.show_signature = t.show_signature ?? true;
        templateForm.logo_pos = t.logo_pos || 'center';
        templateForm.orientation = t.orientation || 'landscape';
        templateForm.bg_opacity = t.bg_opacity ?? 100;
        templateForm.logo_scale = t.logo_scale ?? 15;
        templateForm.bg_filters = t.bg_filters ? cloneDeep(t.bg_filters) : defaultFilters();
        templateForm.logo_top = t.logo_top ?? 10;
        templateForm.elements = t.elements ? cloneDeep(t.elements) : templateForm.elements; // Deep clone elements for reactivity
    } else {
        // Valeurs par défaut pour un nouveau template
        templateForm.id = null;
        templateForm.name = '';
        templateForm.background_color = '#ffffff';
        templateForm.border_color = '#4f46e5';
        templateForm.border_width = 10;
        templateForm.primary_font = 'Montserrat';
        templateForm.accent_font = 'Playfair Display';
        templateForm.text_color = '#1e293b';
        templateForm.show_qr = true;
        templateForm.show_signature = true;
        templateForm.logo_pos = 'center';
        templateForm.orientation = 'landscape';
        templateForm.bg_opacity = 100;
        templateForm.logo_scale = 15;
        templateForm.logo_top = 10;
        templateForm.elements = {
            title: { text: 'CERTIFICAT DE RÉUSSITE', size: 48, top: 25, letter_spacing: 8, color: null },
            subtitle: { text: 'Ceci est décerné avec les honneurs à', size: 18, top: 40, letter_spacing: 2, color: null },
            // Default body text for new templates
            body: { text: 'Pour avoir complété avec succès le programme :', size: 14, top: 60, letter_spacing: 0 }
        };
        // Clear previews for new template
        logoPreview.value = null;
        backgroundPreview.value = null;
        signaturePreview.value = null;
    }

    designerDialog.value = true;
};

/** Sauvegarde le template (création ou mise à jour) */
const saveTemplate = () => {
    if (!templateForm.name.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le nom du modèle est obligatoire.', life: 3000 });
        return;
    }

    const form = useForm(templateForm); // Create a useForm instance from the reactive object

    isSaving.value = true;
    const isUpdate = !!templateForm.id;
    const url = isUpdate
        ? route('certificates.templates.update', { template: templateForm.id })
        : route('certificates.templates.store');

    // Transform data before sending
    form.transform((data) => ({
        ...data,
        background_color: data.background_color.replace('#', ''),
        border_color: data.border_color.replace('#', ''),
        text_color: data.text_color.replace('#', ''),
        // Ensure elements are stringified if they are objects
        elements: data.elements, // Laravel will handle JSON encoding/decoding
        // Remove id if it's an update, as it's in the URL
        ...(isUpdate && { id: undefined })
    })).post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Reset the form after successful submission
            form.reset();
            toast.add({ severity: 'success', summary: 'Template Sauvegardé', detail: 'Le design est prêt à être utilisé.', life: 4000 });
            designerDialog.value = false;
            // Recharger la page pour rafraîchir la liste des templates
            router.reload({ only: ['templates'] });
        },
        onError: (errors) => {
            console.error(errors);
            toast.add({ severity: 'error', summary: 'Erreur Serveur', detail: 'Impossible de sauvegarder le template.', life: 5000 });
        },
        onFinish: () => { isSaving.value = false; }
    });
};

/** Génère un aperçu PDF du template en cours de design via html2canvas */
const previewPDF = async () => {
    const canvasElement = document.getElementById('certificate-canvas');
    if (!canvasElement) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Élément de canevas introuvable.', life: 3000 });
        return;
    }

    isGeneratingPdf.value = true;
    toast.add({ severity: 'info', summary: 'Génération PDF', detail: 'Préparation de l\'aperçu en cours...', life: 4000 });

    try {
        const canvas = await html2canvas(canvasElement, {
            scale: 3, // Augmenter la résolution pour une meilleure qualité
            useCORS: true,
            backgroundColor: null, // Le fond est déjà dans le div
        });

        const imgData = canvas.toDataURL('image/png');

        const orientation = templateForm.orientation; // 'landscape' or 'portrait'
        const pdf = new jsPDF({
            orientation: orientation,
            unit: 'mm',
            format: 'a4'
        });

        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);

        const filename = `${templateForm.name.replace(/\s+/g, '_')}_preview.pdf`;
        pdf.save(filename);

    } catch (error) {
        console.error("Erreur lors de la génération du PDF:", error);
        toast.add({ severity: 'error', summary: 'Erreur PDF', detail: 'Impossible de générer l\'aperçu PDF.', life: 5000 });
    } finally {
        isGeneratingPdf.value = false;
    }
};

/** Duplication d'un template */
const duplicateTemplate = (template) => {
    const newTemplate = cloneDeep(template);
    newTemplate.id = null;
    newTemplate.name = `${template.name} - Copie`;
    newTemplate.is_default = false;

    // Ouvre le designer avec les données pré-remplies du template dupliqué
    openDesigner(newTemplate);

    toast.add({ severity: 'info', summary: 'Modèle Dupliqué', detail: 'Le design a été copié. Vous pouvez maintenant le modifier et l\'enregistrer.', life: 5000 });
};

/** Suppression d'un template */
const deleteTemplate = (id) => {
    confirm.require({
        message: 'Supprimer ce modèle de certificat ? Les certificats déjà émis ne seront pas affectés (ils sont figés en PDF).',
        header: 'Suppression de Modèle',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('certificates.templates.destroy', { template: id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Modèle retiré de la galerie.', life: 3000 });
                },
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer ce modèle (peut-être utilisé).', life: 4000 })
            });
        }
    });
};

// ======================================================================================
// LOGIQUE MÉTIER : ÉMISSION & RÉVOCATION
// ======================================================================================

/** Ouvre le dialogue d'émission massive */
const openBulkIssuance = () => {
    bulkIssueForm.reset();
    bulkIssuanceDialog.value = true;
};

/** Soumet la demande d'émission massive */
const submitBulkIssuance = () => {
    if (!bulkIssueForm.course_id || !bulkIssueForm.template_id) {
        toast.add({ severity: 'warn', summary: 'Champs manquants', detail: 'Veuillez sélectionner une formation et un modèle.', life: 3000 });
        return;
    }

    isSaving.value = true;
    bulkIssueForm.post(route('certificates.bulkIssue'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Génération en cours', detail: 'Les certificats sont générés en arrière-plan.', life: 5000 });
            bulkIssuanceDialog.value = false;
            // Recharger la liste des certificats
            router.reload({ only: ['certificates', 'stats'] });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: err?.response?.data?.message || 'Échec de la génération massive.', life: 5000 });
        },
        onFinish: () => isSaving.value = false
    });
};

/** Ouvre le dialogue de révocation pour un certificat */
const handleRevocation = (cert) => {
    revocationForm.reset();
    revocationForm.id = cert.id;
    revocationForm.reason = '';
    revocationForm.confirm_action = false;
    revocationDialog.value = true;
};

/** Soumet la révocation */
const submitRevocation = () => {
    if (!revocationForm.reason.trim() || !revocationForm.confirm_action) {
        toast.add({ severity: 'warn', summary: 'Formulaire incomplet', detail: 'Motif et confirmation requis.', life: 3000 });
        return;
    }

    // Utiliser la route de révocation définie dans web.php (certificates.revoke)
    router.post(route('certificates.revoke', { certificate: revocationForm.id }), {
        reason: revocationForm.reason,
        _method: 'DELETE' // ou POST avec méthode spoofing – le contrôleur attend DELETE
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'warn', summary: 'Diplôme Invalidé', detail: 'La révocation a été enregistrée.', life: 5000 });
            revocationDialog.value = false;
            router.reload({ only: ['certificates', 'stats'] });
        },
        onError: (err) => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: err?.response?.data?.message || 'Impossible de révoquer.', life: 4000 });
        }
    });
};

/** Téléchargement du PDF */
const downloadPdf = (id) => {
    // Ouvrir dans un nouvel onglet pour déclencher le téléchargement
    window.open(route('certificates.download', { certificate: id }), '_blank');
};

/** Ouvre le lien de vérification public */
const openVerificationLink = (hash) => {
    // Ouvre le lien dans un nouvel onglet
    window.open(route('certificates.verify.public', { hash: hash }), '_blank');
};

// ======================================================================================
// UTILITAIRES D'AFFICHAGE
// ======================================================================================

/** Style dynamique pour l'aperçu du canvas */
// const canvasStyle = computed(() => ({
//     backgroundColor: templateForm.background_color,
//     color: templateForm.text_color,
//     fontFamily: templateForm.primary_font,
//     position: 'relative',
//     overflow: 'hidden',
//     width: '842px',
//     height: '595px',
// }));

const canvasBorderStyle = computed(() => ({
    position: 'absolute',
    top: '20px', left: '20px', right: '20px', bottom: '20px',
    border: `${templateForm.border_width}px solid ${templateForm.border_color}`,
    pointerEvents: 'none'
}));

const getStatusSeverity = (status) => {
    const map = {
        Active: { sev: 'success', label: 'Valide & Certifié', icon: 'pi-verified' },
        Revoked: { sev: 'danger', label: 'Révoqué', icon: 'pi-shield-slash' },
        Expired: { sev: 'warning', label: 'Expiré', icon: 'pi-clock' }
    };
    return map[status] || { sev: 'info', label: status, icon: 'pi-info-circle' };
};

// Chart data (statiques ou calculés selon les props)
const trendChartData = reactive({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{
        label: 'Diplômes Délivrés',
        data: [12, 19, 45, 32, 85, 42],
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.05)',
        fill: true,
        tension: 0.4,
        borderWidth: 2
    }]
});

const chartOptions = {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false } },
        y: { grid: { borderDash: [5, 5], color: '#f1f5f9' }, beginAtZero: true }
    }
};
const stopGlobalDrag = () => {
  draggingElementId.value = null;
  document.removeEventListener('mousemove', handleGlobalDrag);
  document.removeEventListener('mouseup', stopGlobalDrag);
};
// Pagination manuelle (si besoin)
const onPage = (event) => {
    router.get(route('certificates.index'), {
        ...serverFilters,
        page: event.page + 1,
        per_page: event.rows
    }, { preserveState: true });
};
// Ajouter un élément à la position du clic sur le canvas
const addElementAtPosition = (event) => {
  const canvas = document.getElementById('snc-quantum-canvas');
  if (!canvas) return;
  const rect = canvas.getBoundingClientRect();
  const zoom = canvasZoom.value / 100;
  const x = (event.clientX - rect.left) / zoom;
  const y = (event.clientY - rect.top) / zoom;

  const newId = `quantum_${Date.now()}`;
  const newElement = {
    id: newId,
    type: 'text',
    name: 'Nouveau texte',
    content: 'Texte libre',
    x: Math.round(x),
    y: Math.round(y),
    width: 300,
    rotation: 0,
    filters: defaultFilters(),
    style: {
      fontSize: 18,
      color: templateForm.text_color,
      fontFamily: templateForm.primary_font,
      fontWeight: '400',
      letterSpacing: 0,
      textAlign: 'left',
      textTransform: 'none',
    },
  };

  templateForm.elements.push(newElement);
  selectedElementId.value = newId;
};
// ======================================================================================
// CYCLE DE VIE
// ======================================================================================
onMounted(() => {
    setTimeout(() => loading.value = false, 800);
});
</script>

<template>
    <AppLayout>
        <Head title="Ingénierie de Certification | LMS Enterprise" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50/50 p-4 md:p-8 lg:p-10 font-sans pb-24">

            <!-- ============================================================================== -->
            <!-- HEADER ET NAVIGATION GLOBALE                                                   -->
            <!-- ============================================================================== -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10">
                <div class="space-y-3 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Certification & Diplômes', class: 'font-semibold text-indigo-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            Trust Center & Diplômes
                            <Badge value="Blockchain Sync" severity="success" class="text-[10px] bg-emerald-100 text-emerald-700 border border-emerald-200"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-2 leading-relaxed">
                            Gérez la valeur académique de votre institution. Concevez des diplômes HD, délivrez-les en masse, et garantissez leur intégrité via des hashs cryptographiques.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full xl:w-auto">
                    <Button label="Analytique d'Émission" icon="pi pi-chart-line" class="p-button-outlined p-button-secondary bg-white text-slate-700 border-slate-200 shadow-sm font-medium w-full sm:w-auto" @click="analyticsSidebar = true" />
                    <Button label="Délivrer en masse" icon="pi pi-send" class="p-button-primary shadow-md shadow-indigo-500/20 font-bold px-6 w-full sm:w-auto" @click="openBulkIssuance" />
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- KPI DASHBOARD                                                                  -->
            <!-- ============================================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="pi pi-copy text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Délivrés</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">{{ props.stats?.total_issued || 2450 }}</h3>
                        <span class="text-xs font-medium text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">+12%</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition-all duration-300 hover:shadow-md flex flex-col justify-between">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="pi pi-chart-pie text-sm"></i></div>
                                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Tx. Obtention</p>
                            </div>
                            <span class="text-lg font-black text-emerald-600">{{ props.stats?.success_rate || 78 }}%</span>
                        </div>
                    </div>
                    <ProgressBar :value="props.stats?.success_rate || 78" :showValue="false" class="h-2 mt-2 bg-slate-100 rounded-full" color="#10b981"></ProgressBar>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center"><i class="pi pi-eye text-sm"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Vérifications Publiques</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-slate-800">14.2k</h3>
                        <span class="text-xs text-slate-400 font-medium">Vues recruteurs</span>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-2xl p-6 border border-slate-800 shadow-sm relative overflow-hidden group hover:border-rose-500 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center gap-2 mb-4 relative z-10">
                        <div class="w-8 h-8 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center"><i class="pi pi-shield-slash text-sm"></i></div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Titres Révoqués</p>
                    </div>
                    <h3 class="text-4xl font-black text-white relative z-10">{{ props.stats?.revocation_rate || 14 }}</h3>
                    <i class="pi pi-shield absolute -right-4 -bottom-4 text-[80px] text-white/5 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>

            <!-- ============================================================================== -->
            <!-- DUAL VIEW : TABS (JOURNAL vs TEMPLATES)                                        -->
            <!-- ============================================================================== -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-10">

                <!-- Tab Menu (Custom B2B Style) -->
                <div class="flex border-b border-slate-200 bg-slate-50/50 px-4 pt-4">
                    <button @click="activeMainTab = 'log'" class="px-6 py-3 font-bold text-sm flex items-center gap-2 border-b-2 transition-colors" :class="activeMainTab === 'log' ? 'border-indigo-600 text-indigo-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 rounded-t-lg'">
                        <i class="pi pi-list"></i> Journal des Émissions
                    </button>
                    <button @click="activeMainTab = 'templates'" class="px-6 py-3 font-bold text-sm flex items-center gap-2 border-b-2 transition-colors" :class="activeMainTab === 'templates' ? 'border-indigo-600 text-indigo-700 bg-white rounded-t-lg' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 rounded-t-lg'">
                        <i class="pi pi-palette"></i> Galerie des Modèles (Design)
                    </button>
                </div>

                <!-- ========================================================================== -->
                <!-- VUE 1 : JOURNAL DES ÉMISSIONS (DATATABLE)                                  -->
                <!-- ========================================================================== -->
                <div v-show="activeMainTab === 'log'">
                    <!-- Toolbar de filtre -->
                    <div class="border-b border-slate-200 p-4 sm:p-6 flex flex-col lg:flex-row justify-between items-center gap-4 bg-white">
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="serverFilters.search" placeholder="Recherche par Hash, Nom..." class="w-full sm:w-80 border-slate-200 font-medium bg-slate-50" />
                            </IconField>

                            <Dropdown v-model="serverFilters.status" :options="['Active', 'Revoked', 'Expired']" placeholder="Tous les états" class="w-48 border-slate-200 font-medium bg-slate-50" showClear>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <i :class="`pi ${getStatusSeverity(slotProps.option).icon} text-${getStatusSeverity(slotProps.option).sev}-500`"></i>
                                        <span>{{ getStatusSeverity(slotProps.option).label }}</span>
                                    </div>
                                </template>
                            </Dropdown>

                            <Dropdown v-model="serverFilters.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Filtrer par Formation" class="w-56 border-slate-200 font-medium bg-slate-50" showClear filter />
                        </div>

                        <div class="flex items-center gap-2">
                            <Button icon="pi pi-download" label="Export CSV" class="p-button-secondary p-button-outlined bg-white shadow-sm font-bold" />
                            <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser'" />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <DataTable
                        v-model:selection="selectedCertificates"
                        v-model:expandedRows="expandedRows"
                        :value="props.certificates.data"
                        dataKey="id"
                        :paginator="true"
                        :rows="props.certificates.per_page"
                        :totalRecords="props.certificates.total"
                        @page="onPage"
                        lazy
                        :loading="loading || searchLoading"
                        class="p-datatable-custom border-none"
                        rowHover
                        stripedRows
                    >
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-20 text-slate-500 bg-white">
                                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100">
                                    <i class="pi pi-verified text-5xl text-slate-300"></i>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun diplôme émis</h3>
                                <p class="text-sm text-center text-slate-500">Les certificats délivrés aux apprenants apparaîtront ici.</p>
                            </div>
                        </template>

                        <Column expander style="width: 3rem; background-color: #f8fafc;" />

                        <Column field="certificate_code" header="Identifiant Trust" style="min-width: 14rem; background-color: #f8fafc;">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1 py-2">
                                    <span class="font-mono text-sm font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 w-fit">{{ data.certificate_code }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1" v-tooltip.top="data.verification_hash">
                                        <i class="pi pi-key"></i> SHA-256: {{ data.verification_hash?.substring(0, 12) }}...
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="user.name" header="Récipiendaire" style="min-width: 18rem; background-color: #f8fafc;">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar :image="data.user.avatar" shape="circle" size="large" class="shadow-sm border border-slate-200" />
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm">{{ data.user.name }}</div>
                                        <div class="text-xs text-slate-500">{{ data.user.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="course.title" header="Titre & Score" style="min-width: 16rem; background-color: #f8fafc;">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-slate-700 text-sm line-clamp-1">{{ data.course.title }}</span>
                                    <div class="flex items-center gap-2">
                                        <Badge :value="`Score: ${data.score}%`" severity="secondary" class="text-[9px] bg-slate-100 text-slate-600 border border-slate-200"></Badge>
                                        <span class="text-[10px] text-slate-400 font-medium"><i class="pi pi-calendar text-[9px] mr-0.5"></i> Émis le {{ data.issued_at }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Validité" style="background-color: #f8fafc;">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status).sev" :icon="getStatusSeverity(data.status).icon" :value="getStatusSeverity(data.status).label" class="text-[10px] font-bold px-2 py-1 uppercase tracking-wider border" :class="`border-${getStatusSeverity(data.status).sev}-200 bg-${getStatusSeverity(data.status).sev}-50 text-${getStatusSeverity(data.status).sev}-700`" />
                            </template>
                        </Column>

                        <Column header="Actions" alignFrozen="right" :frozen="true" style="text-align: right; background-color: #f8fafc; min-width: 10rem;">
                            <template #body="{ data }">
                                <div class="flex justify-end gap-1">
                                    <Button icon="pi pi-file-pdf" class="p-button-text p-button-secondary p-button-rounded hover:bg-slate-100" v-tooltip.top="'Télécharger PDF'" @click="downloadPdf(data.id)" />
                                    <Button icon="pi pi-external-link" class="p-button-text p-button-info p-button-rounded hover:bg-blue-50 hover:text-blue-600" v-tooltip.top="'Lien Public de Vérification'" @click="openVerificationLink(data.verification_hash)" />
                                    <Button v-if="data.status !== 'Revoked'" icon="pi pi-shield-slash" class="p-button-text p-button-danger p-button-rounded hover:bg-rose-50" v-tooltip.top="'Révoquer le titre'" @click="handleRevocation(data)" />
                                </div>
                            </template>
                        </Column>

                        <!-- LIGNE EXPANSIVE (Audit & Historique de Vérification) -->
                        <template #expansion="{ data }">
                            <div class="p-6 md:p-8 bg-slate-50/80 border-y border-slate-200 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)]">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto">

                                    <!-- Meta Information (Blockchain/Hash) -->
                                    <div class="space-y-4">
                                        <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2"><i class="pi pi-database text-indigo-500"></i> Métadonnées d'Intégrité</h4>

                                        <div v-if="data.status === 'Revoked'" class="bg-rose-50 p-4 rounded-xl border border-rose-200 mb-4">
                                            <p class="text-xs font-bold text-rose-800 uppercase tracking-widest mb-1 flex items-center gap-2"><i class="pi pi-exclamation-triangle"></i> Motif de Révocation</p>
                                            <p class="text-sm text-rose-700 font-medium">{{ data.revocation_reason }}</p>
                                        </div>

                                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                                            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                                <span class="text-xs font-bold text-slate-500 uppercase">Empreinte SHA-256</span>
                                                <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded truncate max-w-[200px]" v-tooltip.top="data.verification_hash">{{ data.verification_hash }}</span>
                                            </div>
                                            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                                <span class="text-xs font-bold text-slate-500 uppercase">Modèle utilisé</span>
                                                <span class="text-sm font-bold text-slate-700">{{ data.template.name }}</span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs font-bold text-slate-500 uppercase">Vues Publiques</span>
                                                <span class="text-sm font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ data.verifications_count }} vues</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timeline de l'Audit Log -->
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2"><i class="pi pi-list text-indigo-500"></i> Audit Log (Traçabilité)</h4>
                                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                                            <Timeline :value="data.audit_log" class="custom-timeline">
                                                <template #marker="slotProps">
                                                    <span class="flex items-center justify-center w-6 h-6 rounded-full text-white shadow-sm" :style="{ backgroundColor: slotProps.item.color }">
                                                        <i :class="slotProps.item.icon" class="text-[10px]"></i>
                                                    </span>
                                                </template>
                                                <template #content="slotProps">
                                                    <div class="ml-3 mb-6">
                                                        <div class="text-sm font-bold text-slate-700">{{ slotProps.item.event }}</div>
                                                        <div class="text-[10px] font-mono text-slate-400 mt-1"><i class="pi pi-clock mr-1"></i>{{ slotProps.item.date }}</div>
                                                    </div>
                                                </template>
                                            </Timeline>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <!-- ========================================================================== -->
                <!-- VUE 2 : GALERIE DES TEMPLATES (VISUAL DESIGNER)                            -->
                <!-- ========================================================================== -->
                <div v-show="activeMainTab === 'templates'" class="p-6 bg-slate-50/50">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Modèles Visuels Disponibles</h3>
                        <Button label="Créer un Modèle" icon="pi pi-palette" class="p-button-outlined p-button-indigo bg-white font-bold shadow-sm" @click="openDesigner(null)" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <!-- Boucle sur les templates -->
                        <div v-for="template in templates" :key="template.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden group hover:border-indigo-300 hover:shadow-md transition-all flex flex-col">

                            <!-- Mini Preview (Canvas Mock) -->
                            <div class="h-40 bg-slate-100 flex items-center justify-center p-4 border-b border-slate-100 relative overflow-hidden" :style="{ backgroundColor: `#${template.background_color}` }">
                                <!-- Fake Border -->
                                <div class="absolute inset-2" :style="{ border: `${template.border_width / 2}px solid #${template.border_color}` }"></div>
                                <div class="text-center relative z-10" :style="{ color: `#${template.text_color}` }">
                                    <div class="font-black text-sm mb-1" :style="{ fontFamily: template.accent_font }">{{ template.elements.title.text }}</div>
                                    <div class="w-16 h-0.5 bg-current mx-auto opacity-30 mb-2"></div>
                                    <div class="text-[8px] uppercase tracking-widest opacity-80">{{ template.elements.subtitle.text }}</div>
                                </div>
                            </div>

                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-800 truncate pr-2">{{ template.name }}</h4>
                                        <Badge v-if="template.is_default" value="Défaut" severity="success" class="text-[9px]" />
                                    </div>
                                    <div class="flex gap-2 text-xs text-slate-500 font-medium mb-4">
                                        <span class="bg-slate-100 px-2 py-1 rounded border border-slate-200 uppercase tracking-wider text-[9px]">{{ template.orientation === 'landscape' ? 'Paysage' : 'Portrait' }}</span>
                                        <span class="bg-slate-100 px-2 py-1 rounded border border-slate-200 uppercase tracking-wider text-[9px]">{{ template.primary_font }}</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info p-button-sm shadow-xl" v-tooltip.top="'Éditer le design'" @click="openDesigner(template)" />
                                        <Button icon="pi pi-copy" class="p-button-rounded p-button-secondary p-button-sm shadow-xl bg-white text-slate-700" v-tooltip.top="'Dupliquer'" @click="duplicateTemplate(template)" />
                                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm p-0 w-8 h-8 rounded-full hover:bg-rose-50" @click="deleteTemplate(template.id)" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add New Card -->
                        <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl flex flex-col items-center justify-center p-8 min-h-[250px] cursor-pointer hover:bg-indigo-50 hover:border-indigo-300 transition-colors group" @click="openDesigner(null)">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm border border-slate-200 group-hover:text-indigo-600 group-hover:scale-110 transition-transform">
                                <i class="pi pi-plus text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-slate-700 group-hover:text-indigo-700">Forger un nouveau design</h4>
                            <p class="text-xs text-slate-500 mt-2 text-center">Créez un template visuel unique pour vos certifications.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- DIALOG : CERTIFICATE DESIGNER (WORKSPACE SPLIT PANE)                           -->
        <!-- ============================================================================== -->
       <!-- ============================================================================== -->
<!-- DIALOG : CERTIFICATE VISUAL DESIGNER (CANVA-STYLE)                             -->
<!-- ============================================================================== -->
  <Dialog
    v-model:visible="designerDialog"
    modal
    :closable="false"
    :draggable="false"
    :style="{ width: '100vw', height: '100vh', maxHeight: '100vh' }"
    contentClass="p-0 flex flex-col overflow-hidden bg-[#050608]"
    headerClass="hidden"
    class="m-0 rounded-none shadow-none SNC-Quantum-Engine"
  >
    <!--
      ===========================================================================================
      QUANTUM NAVIGATION BAR : TOP-LEVEL CONTROLS
      ===========================================================================================
    -->
    <nav class="flex-none h-[75px] bg-[#0d0f16] border-b border-white/5 flex items-center justify-between px-8 z-[200] shadow-2xl">
      <!-- Section Gauche : Logo & Branding -->
      <div class="flex items-center gap-8">
        <div class="flex items-center gap-4 group cursor-pointer" @click="designerDialog = false">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
            <i class="pi pi-chevron-left text-white text-lg"></i>
          </div>
          <div class="flex flex-col">
            <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em] leading-none mb-1">Quantum Studio</span>
            <span class="text-xs font-bold text-white/40 uppercase tracking-widest">Back to Dashboard</span>
          </div>
        </div>

        <div class="h-8 w-px bg-white/10"></div>

        <!-- Nom du Projet Dynamique -->
        <div class="flex flex-col">
          <div class="flex items-center gap-2">
            <InputText
              v-model="templateForm.name"
              class="bg-transparent border-none text-white font-black text-xl p-0 focus:ring-0 w-80 placeholder:text-white/10"
              placeholder="Sans titre - Certificat Quantum"
            />
            <i class="pi pi-pencil text-white/20 text-xs"></i>
          </div>
          <div class="flex items-center gap-2 mt-1">
            <Badge value="V4.2.0" severity="info" class="quantum-badge-xs"></Badge>
            <span class="text-[9px] font-bold text-white/30 uppercase tracking-widest">Auto-save: Enabled</span>
          </div>
        </div>
      </div>

      <!-- Section Centrale : Quick Actions -->
      <div class="hidden lg:flex items-center gap-3 bg-white/5 p-1.5 rounded-2xl border border-white/5 shadow-inner">
        <Button icon="pi pi-undo" class="p-button-text p-button-sm text-white/60 hover:text-white hover:bg-white/5" v-tooltip.bottom="'Undo (Ctrl+Z)'" />
        <Button icon="pi pi-redo" class="p-button-text p-button-sm text-white/60 hover:text-white hover:bg-white/5" v-tooltip.bottom="'Redo (Ctrl+Y)'" />
        <div class="w-px h-4 bg-white/10"></div>
        <div class="flex items-center gap-4 px-3">
          <i class="pi pi-search text-white/20 text-xs"></i>
          <Slider v-model="canvasZoom" :min="10" :max="250" class="w-40 quantum-slider-cyan" />
          <span class="text-[10px] font-mono text-cyan-400 w-10">{{ canvasZoom }}%</span>
        </div>
        <div class="w-px h-4 bg-white/10"></div>
        <Button icon="pi pi- clone" class="p-button-text p-button-sm text-white/60 hover:text-white" v-tooltip.bottom="'Duplicate Layout'" />
      </div>

      <!-- Section Droite : Finalisation -->
      <div class="flex items-center gap-4">
        <div class="flex flex-col items-end pr-4 border-r border-white/10 mr-2">
          <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Quantum Engine</span>
          <span class="text-[9px] text-white/30 font-bold uppercase">Ready for 600 DPI Export</span>
        </div>

        <Button
          icon="pi pi-eye"
          label="Simulation"
          class="p-button-text text-white/80 hover:bg-white/5 font-bold px-6 rounded-xl border border-white/10"
        />

        <Button
          label="Publish Quantum Design"
          icon="pi pi-bolt"
          class="quantum-btn-glow bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 border-none font-black px-8 rounded-xl h-12 transition-all transform hover:scale-105"
          @click="saveTemplate"
          :loading="isSaving"
        />
      </div>
    </nav>

    <div class="flex-1 flex overflow-hidden">
      <!--
        ===========================================================================================
        LEFT SIDEBAR : ASSETS, COMPONENTS & NEURAL LAYERS
        ===========================================================================================
      -->
      <aside class="w-[380px] flex-none bg-[#090b11] border-r border-white/5 flex flex-col z-[150] shadow-2xl">
        <TabView class="quantum-sidebar-tabs">
          <!-- TAB 1 : INSERTION D'ELEMENTS -->
          <TabPanel>
            <template #header>
              <div class="flex flex-col items-center gap-1">
                <i class="pi pi-plus-circle text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-tighter">Assets</span>
              </div>
            </template>

            <div class="p-6 space-y-10 custom-scrollbar overflow-y-auto max-h-[calc(100vh-160px)]">
              <!-- Section : Typographie Dynamique -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Neural Typography</span>
                  <i class="pi pi-info-circle text-white/10 text-xs"></i>
                </div>
                <div class="grid grid-cols-1 gap-3">
                  <div @click="addElement('text', 'h1')" class="quantum-asset-card group">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-xl font-black text-white group-hover:bg-indigo-500 transition-colors">T</div>
                      <div class="flex flex-col">
                        <span class="text-sm font-bold text-white/80">Titre de Distinction</span>
                        <span class="text-[9px] text-white/30 uppercase font-black">Headline Quantum</span>
                      </div>
                    </div>
                  </div>
                  <div @click="addElement('text', 'body')" class="quantum-asset-card group">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-white/5 rounded-xl flex items-center justify-center text-sm font-medium text-white group-hover:bg-violet-500 transition-colors">Abc</div>
                      <div class="flex flex-col">
                        <span class="text-sm font-bold text-white/80">Bloc de Narration</span>
                        <span class="text-[9px] text-white/30 uppercase font-black">Body / Description</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section : Images & Sceaux -->
              <div class="space-y-4">
                <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Visual Identities</span>
                <div class="grid grid-cols-2 gap-3">
                  <div @click="$refs.logoUploader.click()" class="quantum-upload-zone group">
                    <input type="file" ref="logoUploader" hidden @change="uploadAsset($event, 'logo')" />
                    <i class="pi pi-shield text-2xl text-indigo-400 group-hover:scale-125 transition-transform"></i>
                    <span class="text-[10px] font-bold text-white/60 uppercase mt-2">Logo Master</span>
                  </div>
                  <div @click="$refs.sigUploader.click()" class="quantum-upload-zone group">
                    <input type="file" ref="sigUploader" hidden @change="uploadAsset($event, 'signature')" />
                    <i class="pi pi-pencil text-2xl text-violet-400 group-hover:scale-125 transition-transform"></i>
                    <span class="text-[10px] font-bold text-white/60 uppercase mt-2">Signature</span>
                  </div>
                </div>
              </div>

              <!-- Section : Background Patterns & Overlays -->
              <div class="space-y-4">
                <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Quantum Overlays</span>
                <div class="grid grid-cols-3 gap-2">
                  <div v-for="n in 9" :key="n" class="aspect-square rounded-lg bg-white/5 border border-white/5 hover:border-indigo-500 cursor-pointer overflow-hidden group">
                    <div class="w-full h-full bg-gradient-to-br from-white/10 to-transparent group-hover:scale-110 transition-transform"></div>
                  </div>
                </div>
                <Button label="Browse Texture Library" class="p-button-text p-button-sm w-full text-[10px] font-black text-indigo-400" />
              </div>

              <!-- Section : Sécurité Blockchain -->
              <div class="p-6 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl space-y-4 shadow-2xl">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                    <i class="pi pi-qrcode text-white"></i>
                  </div>
                  <span class="text-[10px] font-black text-white uppercase tracking-widest">Smart QR Seal</span>
                </div>
                <p class="text-[9px] text-white/50 leading-relaxed font-medium">
                  Intègre un identifiant unique vérifiable sur la blockchain SubnetCongo Trust.
                </p>
                <Button @click="addElement('qr', 'standard')" label="Générer le Sceau" class="p-button-sm w-full bg-indigo-600 border-none font-black text-[9px] uppercase" />
              </div>
            </div>
          </TabPanel>

          <!-- TAB 2 : GESTION DES CALQUES (LAYERS) -->
          <TabPanel>
            <template #header>
              <div class="flex flex-col items-center gap-1">
                <i class="pi pi-clone text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-tighter">Layers</span>
              </div>
            </template>
            <div class="p-4 space-y-2">
              <div class="flex items-center justify-between mb-4 px-2">
                <span class="text-[10px] font-black text-white/20 uppercase tracking-widest">Layer Stack</span>
                <span class="text-[10px] font-bold text-indigo-400">{{ templateForm.elements.length }} Elements</span>
              </div>

              <draggable v-model="templateForm.elements" item-key="id" handle=".handle">
                <template #item="{element}">
                  <div
                    class="quantum-layer-item group"
                    :class="{'is-active': selectedElementId === element.id}"
                    @click="selectedElementId = element.id"
                  >
                    <div class="flex items-center gap-3 flex-1">
                      <i class="pi pi-bars handle cursor-grab text-white/10 hover:text-white/40"></i>
                      <div class="w-8 h-8 rounded bg-white/5 flex items-center justify-center">
                        <i :class="getElementIcon(element.type)" class="text-[10px] text-white/40"></i>
                      </div>
                      <span class="text-xs font-bold text-white/70 truncate w-32">{{ element.name || element.content }}</span>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <Button icon="pi pi-eye" class="p-button-text p-button-xs text-white/40" />
                      <Button icon="pi pi-lock" class="p-button-text p-button-xs text-white/40" />
                      <Button @click.stop="removeElement(element.id)" icon="pi pi-trash" class="p-button-text p-button-xs text-red-400/60" />
                    </div>
                  </div>
                </template>
              </draggable>
            </div>
          </TabPanel>

          <!-- TAB 3 : HISTORIQUE -->
          <TabPanel>
            <template #header>
              <div class="flex flex-col items-center gap-1">
                <i class="pi pi-history text-lg"></i>
                <span class="text-[8px] font-black uppercase tracking-tighter">History</span>
              </div>
            </template>
            <div class="p-6 text-center space-y-4">
              <i class="pi pi-clock text-4xl text-white/5"></i>
              <p class="text-[10px] font-bold text-white/20 uppercase tracking-widest">No recent changes recorded</p>
            </div>
          </TabPanel>
        </TabView>
      </aside>

      <!--
        ===========================================================================================
        CENTER WORKSPACE : THE QUANTUM CANVAS VIEWPORT
        ===========================================================================================
      -->
      <main
        class="flex-1 relative overflow-hidden bg-[#050608] flex items-center justify-center p-20 quantum-infinite-grid"
  @dblclick="addElementAtPosition"
      >
        <!-- Floating Canvas Toolbar -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex items-center gap-2 bg-[#11141d]/80 backdrop-blur-2xl border border-white/5 p-2 rounded-2xl z-[100] shadow-2xl">
          <div class="flex items-center gap-1">
            <Button icon="pi pi-search-minus" @click="canvasZoom -= 10" class="p-button-text text-white/60" />
            <span class="text-[10px] font-mono text-white/80 w-12 text-center">{{ canvasZoom }}%</span>
            <Button icon="pi pi-search-plus" @click="canvasZoom += 10" class="p-button-text text-white/60" />
          </div>
          <div class="w-px h-4 bg-white/10 mx-2"></div>
          <Button icon="pi pi-expand" @click="canvasZoom = 100" class="p-button-text text-white/60" v-tooltip="'Fit to Screen'" />
          <Button icon="pi pi-desktop" @click="toggleOrientation" class="p-button-text text-white/60" v-tooltip="'Toggle Orientation'" />
          <div class="w-px h-4 bg-white/10 mx-2"></div>
          <Button icon="pi pi-refresh" class="p-button-text text-indigo-400" v-tooltip="'Reset Canvas'" />
        </div>

        <!-- Rulers (Optionnel pour l'UX Quantum) -->
        <div class="absolute top-0 left-[20px] right-0 h-5 bg-white/5 border-b border-white/10 text-[8px] font-mono text-white/20 flex items-center">
          <div v-for="n in 20" :key="n" class="border-l border-white/10 h-full w-[100px] flex items-end pb-0.5 pl-1">{{ (n-1)*100 }}</div>
        </div>
        <div class="absolute left-0 top-[20px] bottom-0 w-5 bg-white/5 border-r border-white/10 text-[8px] font-mono text-white/20 flex flex-col items-center">
          <div v-for="n in 20" :key="n" class="border-t border-white/10 w-full h-[100px] flex items-center justify-center rotate-90">{{ (n-1)*100 }}</div>
        </div>

        <!--
          *****************************************************************************************
          THE MASTER DOCUMENT (A4 PHYSICAL BOUNDS)
          *****************************************************************************************
        -->
        <div
          id="snc-quantum-canvas"
          class="relative transition-shadow duration-700 bg-white shadow-[0_0_150px_rgba(0,0,0,0.8)] origin-center overflow-hidden"
          :style="quantumCanvasStyle"
        >
          <!-- Layer 1 : Background Media -->
          <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <img
              v-if="backgroundPreview"
              :src="backgroundPreview"
              class="w-full h-full object-cover"
              :style="quantumFilterStyle(templateForm.bg_filters)"
            />
            <!-- Quantum Mesh Gradients (SaaS Aesthetics) -->
            <div class="absolute inset-0 quantum-mesh-gradient" :style="{ opacity: templateForm.bg_opacity / 100 }"></div>
            <!-- Custom Color Overlay -->
            <div class="absolute inset-0" :style="{ background: templateForm.background_color, mixBlendMode: 'multiply', opacity: 0.2 }"></div>
          </div>

          <!-- Layer 2 : Decorative Border -->
          <div
            v-if="templateForm.border_width > 0"
            class="absolute pointer-events-none z-50 border-double transition-all duration-500"
            :style="{
              top: '40px', left: '40px', right: '40px', bottom: '40px',
              border: `${templateForm.border_width}px double ${templateForm.border_color}`,
              opacity: 0.7
            }"
          ></div>

          <!-- Layer 3 : Interactive Elements Loop (Drag and Drop Area) -->
          <div
            v-for="(el, idx) in templateForm.elements"
            :key="el.id"
            class="absolute quantum-draggable-container group"
            :class="{
              'is-selected': selectedElementId === el.id,
              'is-dragging': draggingElementId === el.id
            }"
            :style="quantumElementStyle(el)"
            @mousedown.stop="startElementDrag($event, el)"
          >
            <!-- Control Handles (Visible only on selected) -->
            <div v-if="selectedElementId === el.id" class="absolute -inset-2 border-2 border-indigo-500 quantum-drag-frame rounded-sm pointer-events-none">
              <div class="absolute -top-1 -left-1 w-3 h-3 bg-white border-2 border-indigo-500 rounded-full pointer-events-auto cursor-nw-resize"></div>
              <div class="absolute -top-1 -right-1 w-3 h-3 bg-white border-2 border-indigo-500 rounded-full pointer-events-auto cursor-ne-resize"></div>
              <div class="absolute -bottom-1 -left-1 w-3 h-3 bg-white border-2 border-indigo-500 rounded-full pointer-events-auto cursor-sw-resize"></div>
              <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-white border-2 border-indigo-500 rounded-full pointer-events-auto cursor-se-resize"></div>
              <!-- Rotation Handle -->
              <div class="absolute -top-8 left-1/2 -translate-x-1/2 w-4 h-4 bg-indigo-500 rounded-full flex items-center justify-center pointer-events-auto cursor-grab shadow-xl">
                 <i class="pi pi-refresh text-white text-[8px]"></i>
              </div>
            </div>

            <!-- Content Rendering Logic -->
            <div class="w-full h-full relative overflow-visible">

              <!-- TYPE: TEXT -->
              <div v-if="el.type === 'text'"
                class="w-full h-full outline-none focus:outline-none whitespace-pre-wrap select-text"
                contenteditable="true"
                @input="handleTextEdit($event, el)"
                :style="quantumTextStyle(el)"
              >
                {{ el.content }}
              </div>

              <!-- TYPE: IMAGE (LOGO / SIGNATURE) -->
              <div v-if="el.type === 'image'" class="w-full h-full select-none pointer-events-none">
                <img
                  :src="el.content"
                  class="w-full h-full object-contain"
                  :style="quantumFilterStyle(el.filters)"
                />
              </div>

              <!-- TYPE: QR CODE -->
              <div v-if="el.type === 'qr'" class="w-full h-full bg-white p-2 border shadow-sm">
                <div class="w-full h-full bg-slate-100 flex items-center justify-center">
                  <i class="pi pi-qrcode text-4xl text-slate-800"></i>
                </div>
              </div>

            </div>

            <!-- Contextual Quick Action Menu -->
            <div v-if="selectedElementId === el.id" class="absolute -bottom-14 left-1/2 -translate-x-1/2 bg-[#1a1f2e] border border-white/10 rounded-xl px-4 py-2 flex items-center gap-3 shadow-2xl opacity-0 group-hover:opacity-100 transition-opacity">
              <Button icon="pi pi-clone" class="p-button-text p-button-sm text-white/50" v-tooltip="'Clone'" @click="duplicateElement(el)" />
              <div class="w-px h-3 bg-white/10"></div>
              <Button icon="pi pi-lock" class="p-button-text p-button-sm text-white/50" />
              <Button icon="pi pi-trash" class="p-button-text p-button-sm text-red-400" @click="removeElement(el.id)" />
            </div>
          </div>

          <!-- Layer 4 : Overlays (Grain, Static, etc.) -->
          <div class="absolute inset-0 pointer-events-none z-[100] opacity-[0.03] mix-blend-overlay quantum-grain"></div>

        </div>
      </main>

      <!--
        ===========================================================================================
        RIGHT SIDEBAR : PROPERTY INSPECTOR & NEURAL STYLING
        ===========================================================================================
      -->
      <aside class="w-[400px] flex-none bg-[#090b11] border-l border-white/5 flex flex-col z-[150] shadow-2xl overflow-y-auto custom-scrollbar">
        <!-- HEADER INSPECTOR -->
        <div class="p-6 bg-white/[0.02] border-b border-white/5 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30">
              <i class="pi pi-sliders-h text-indigo-400 text-xs"></i>
            </div>
            <h3 class="text-xs font-black text-white/80 uppercase tracking-widest italic">
              {{ selectedElementId ? 'Element Inspector' : 'Document Global' }}
            </h3>
          </div>
          <Button v-if="selectedElementId" @click="selectedElementId = null" icon="pi pi-times" class="p-button-text p-button-xs text-white/20 hover:text-white" />
        </div>

        <!--
          -----------------------------------------------------------------------------------------
          CONTEXT 1 : DOCUMENT GLOBAL PROPERTIES (When nothing is selected)
          -----------------------------------------------------------------------------------------
        -->
        <div v-if="!selectedElementId" class="p-8 space-y-10 pb-40">

          <!-- Section : Géométrie Master -->
          <div class="space-y-6">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-file text-[10px]"></i> Page Geometry
            </span>
            <div class="grid grid-cols-2 gap-4">
              <div class="quantum-property-group">
                <label class="quantum-label">Orientation</label>
                <SelectButton v-model="templateForm.orientation" :options="[{l:'A4 Paysage', v:'landscape'}, {l:'A4 Portrait', v:'portrait'}]" optionLabel="l" optionValue="v" class="quantum-select-sm" />
              </div>
              <div class="quantum-property-group">
                <label class="quantum-label">Unit System</label>
                <Dropdown disabled placeholder="Pixels (DPI 300)" class="quantum-dropdown-sm" />
              </div>
            </div>
          </div>

          <Divider class="border-white/5" />

          <!-- Section : Deep Background Configuration -->
          <div class="space-y-6">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-palette text-[10px]"></i> Neural Background
            </span>

            <!-- Background Image Upload -->
            <div class="space-y-4">
              <label class="quantum-label">Master Texture</label>
              <div class="p-4 rounded-2xl bg-white/5 border border-dashed border-white/10 flex flex-col items-center gap-4 group hover:border-indigo-500 transition-colors cursor-pointer" @click="$refs.bgUploader.click()">
                <input type="file" ref="bgUploader" hidden @change="handleBgUpload" />
                <div v-if="!backgroundPreview" class="flex flex-col items-center gap-2">
                  <i class="pi pi-cloud-upload text-2xl text-white/10"></i>
                  <span class="text-[9px] font-bold text-white/20 uppercase tracking-widest">Select HD Asset</span>
                </div>
                <div v-else class="relative w-full h-32 rounded-xl overflow-hidden shadow-2xl">
                  <img :src="backgroundPreview" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-[10px] font-black text-white uppercase tracking-widest">Replace Asset</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Background FX Sliders -->
            <div class="space-y-6 p-6 rounded-3xl bg-white/[0.02] border border-white/5">
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <label class="text-[9px] font-black text-white/40 uppercase tracking-widest italic">Base Tint</label>
                  <ColorPicker v-model="templateForm.background_color" />
                </div>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <label class="text-[9px] font-black text-white/40 uppercase tracking-widest italic">Tint Opacity</label>
                    <span class="text-[10px] font-mono text-indigo-400">{{ templateForm.bg_opacity }}%</span>
                  </div>
                  <Slider v-model="templateForm.bg_opacity" :min="0" :max="100" class="quantum-slider-xs" />
                </div>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <label class="text-[9px] font-black text-white/40 uppercase tracking-widest italic">Filter Blur</label>
                    <span class="text-[10px] font-mono text-indigo-400">{{ templateForm.bg_filters.blur }}px</span>
                  </div>
                  <Slider v-model="templateForm.bg_filters.blur" :min="0" :max="50" class="quantum-slider-xs" />
                </div>
              </div>
            </div>
          </div>

          <Divider class="border-white/5" />

          <!-- Section : Master Borders -->
          <div class="space-y-6">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-box text-[10px]"></i> Frame Aesthetics
            </span>
            <div class="space-y-6">
               <div class="flex items-center gap-4">
                  <ColorPicker v-model="templateForm.border_color" />
                  <InputText v-model="templateForm.border_color" class="quantum-input-sm uppercase font-mono text-indigo-400" />
               </div>
               <div class="space-y-2">
                  <div class="flex justify-between">
                    <label class="text-[9px] font-black text-white/40 uppercase tracking-widest italic">Frame Thickness</label>
                    <span class="text-[10px] font-mono text-indigo-400">{{ templateForm.border_width }}px</span>
                  </div>
                  <Slider v-model="templateForm.border_width" :min="0" :max="100" class="quantum-slider-xs" />
               </div>
            </div>
          </div>
        </div>

        <!--
          -----------------------------------------------------------------------------------------
          CONTEXT 2 : ELEMENT PROPERTIES (When an element is selected)
          -----------------------------------------------------------------------------------------
        -->
        <div v-else class="p-8 space-y-10 pb-40">

          <!-- Element Identity -->
          <div class="p-6 rounded-3xl bg-indigo-500/5 border border-indigo-500/10 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500 flex items-center justify-center shadow-lg">
               <i :class="getElementIcon(selectedElement.type)" class="text-white text-xl"></i>
            </div>
            <div class="flex flex-col">
              <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest leading-none mb-1">Entity Type: {{ selectedElement.type }}</span>
              <InputText v-model="selectedElement.name" class="bg-transparent border-none text-white font-black text-sm p-0 focus:ring-0" placeholder="Nom du calque..." />
            </div>
          </div>

          <!-- Section : Geometry Transform -->
          <div class="space-y-6">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-directions text-[10px]"></i> Neural Transform
            </span>

            <div class="grid grid-cols-2 gap-4">
              <div class="quantum-property-group">
                <label class="quantum-label">Axis X (Offset)</label>
                <InputNumber v-model="selectedElement.x" showButtons class="quantum-number-sm" suffix=" px" />
              </div>
              <div class="quantum-property-group">
                <label class="quantum-label">Axis Y (Offset)</label>
                <InputNumber v-model="selectedElement.y" showButtons class="quantum-number-sm" suffix=" px" />
              </div>
              <div class="quantum-property-group">
                <label class="quantum-label">Width</label>
                <InputNumber v-model="selectedElement.width" showButtons class="quantum-number-sm" suffix=" px" />
              </div>
              <div class="quantum-property-group">
                <label class="quantum-label">Rotation</label>
                <InputNumber v-model="selectedElement.rotation" showButtons class="quantum-number-sm" suffix=" °" :min="-360" :max="360" />
              </div>
            </div>
          </div>

          <Divider class="border-white/5" />

          <!-- Section Contextuelle : TEXT STYLING -->
          <div v-if="selectedElement.type === 'text'" class="space-y-8">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-pencil text-[10px]"></i> Typeface Calibration
            </span>

            <div class="space-y-6">
              <!-- Font Family Selector -->
              <div class="quantum-property-group">
                <label class="quantum-label">Neural Font Family</label>
                <Dropdown
                  v-model="selectedElement.style.fontFamily"
                  :options="props.meta.fonts"
                  class="quantum-dropdown-sm"
                  placeholder="Select Quantum Font"
                >
                  <template #option="slotProps">
                    <div :style="{ fontFamily: slotProps.option }" class="text-sm">{{ slotProps.option }}</div>
                  </template>
                </Dropdown>
              </div>

              <!-- Typography Grid -->
              <div class="grid grid-cols-2 gap-4">
                <div class="quantum-property-group">
                   <label class="quantum-label">Scale (Size)</label>
                   <InputNumber v-model="selectedElement.style.fontSize" class="quantum-number-sm" suffix=" px" />
                </div>
                <div class="quantum-property-group">
                   <label class="quantum-label">Kerning</label>
                   <InputNumber v-model="selectedElement.style.letterSpacing" class="quantum-number-sm" suffix=" px" />
                </div>
              </div>

              <!-- Color & Opacity -->
              <div class="grid grid-cols-1 gap-4">
                <div class="quantum-property-group">
                  <label class="quantum-label">Base Color</label>
                  <div class="flex items-center gap-4 bg-white/5 p-3 rounded-2xl">
                    <ColorPicker v-model="selectedElement.style.color" />
                    <InputText v-model="selectedElement.style.color" class="quantum-input-sm uppercase font-mono" />
                  </div>
                </div>
              </div>

              <!-- Advanced Formatting -->
              <div class="space-y-4">
                <label class="quantum-label">Advanced Logic</label>
                <div class="flex flex-wrap gap-2">
                  <button @click="toggleStyle(selectedElement, 'fontWeight', '900', '400')" class="quantum-format-btn" :class="{'active': selectedElement.style.fontWeight === '900'}"><i class="pi pi-bold"></i></button>
                  <button @click="toggleStyle(selectedElement, 'fontStyle', 'italic', 'normal')" class="quantum-format-btn" :class="{'active': selectedElement.style.fontStyle === 'italic'}"><i class="pi pi-italic"></i></button>
                  <button @click="toggleStyle(selectedElement, 'textTransform', 'uppercase', 'none')" class="quantum-format-btn" :class="{'active': selectedElement.style.textTransform === 'uppercase'}"><i class="pi pi-sort-alpha-up"></i></button>
                  <button @click="toggleStyle(selectedElement, 'textDecoration', 'underline', 'none')" class="quantum-format-btn" :class="{'active': selectedElement.style.textDecoration === 'underline'}"><i class="pi pi-minus"></i></button>
                </div>
                <div class="flex gap-2">
                  <SelectButton v-model="selectedElement.style.textAlign" :options="[{l:'pi pi-align-left', v:'left'},{l:'pi pi-align-center', v:'center'},{l:'pi pi-align-right', v:'right'}]" optionLabel="l" optionValue="v" class="quantum-select-sm flex-1">
                    <template #option="slotProps">
                      <i :class="slotProps.option.l"></i>
                    </template>
                  </SelectButton>
                </div>
              </div>
            </div>
          </div>

          <!-- Section Contextuelle : IMAGE FILTERS (LOGO / SIG / BG) -->
          <div v-if="selectedElement.type === 'image' || selectedElement.type === 'logo'" class="space-y-8">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-filter text-[10px]"></i> Quantum FX Matrix
            </span>

            <div class="p-6 rounded-3xl bg-white/[0.02] border border-white/5 space-y-8">
              <!-- Brightness -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <label class="text-[9px] font-black text-white/40 uppercase tracking-widest">Brightness</label>
                  <span class="text-[10px] font-mono text-indigo-400">{{ selectedElement.filters.brightness }}%</span>
                </div>
                <Slider v-model="selectedElement.filters.brightness" :min="0" :max="200" class="quantum-slider-xs" />
              </div>
              <!-- Contrast -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <label class="text-[9px] font-black text-white/40 uppercase tracking-widest">Contrast</label>
                  <span class="text-[10px] font-mono text-indigo-400">{{ selectedElement.filters.contrast }}%</span>
                </div>
                <Slider v-model="selectedElement.filters.contrast" :min="0" :max="200" class="quantum-slider-xs" />
              </div>
              <!-- Grayscale -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <label class="text-[9px] font-black text-white/40 uppercase tracking-widest">Grayscale</label>
                  <span class="text-[10px] font-mono text-indigo-400">{{ selectedElement.filters.grayscale }}%</span>
                </div>
                <Slider v-model="selectedElement.filters.grayscale" :min="0" :max="100" class="quantum-slider-xs" />
              </div>
              <!-- Hue-Rotate (Color Shifting) -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <label class="text-[9px] font-black text-white/40 uppercase tracking-widest">Hue Rotation</label>
                  <span class="text-[10px] font-mono text-indigo-400">{{ selectedElement.filters.hueRotate }}°</span>
                </div>
                <Slider v-model="selectedElement.filters.hueRotate" :min="0" :max="360" class="quantum-slider-xs" />
              </div>
              <!-- Invert -->
              <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                <span class="text-[9px] font-black text-white/40 uppercase tracking-widest">Negative Inversion</span>
                <InputSwitch v-model="selectedElement.filters.invert" class="scale-75" />
              </div>
            </div>
          </div>

          <Divider class="border-white/5" />

          <!-- Layer Depth & Controls -->
          <div class="space-y-6">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-2">
              <i class="pi pi-sort-alt text-[10px]"></i> Depth Management
            </span>
            <div class="flex gap-2">
              <Button @click="reorderLayer(selectedElement, 'up')" icon="pi pi-arrow-up" label="Bring Forward" class="p-button-outlined p-button-sm flex-1 text-[10px] uppercase font-black" />
              <Button @click="reorderLayer(selectedElement, 'down')" icon="pi pi-arrow-down" label="Send Backward" class="p-button-outlined p-button-sm flex-1 text-[10px] uppercase font-black" />
            </div>
            <Button @click="removeElement(selectedElement.id)" icon="pi pi-trash" label="Delete Element" class="p-button-danger p-button-text w-full text-[10px] uppercase font-black" />
          </div>
        </div>
      </aside>
    </div>

    <!--
      ===========================================================================================
      FOOTER STATUS BAR
      ===========================================================================================
    -->
    <footer class="flex-none h-8 bg-[#0d0f16] border-t border-white/5 px-6 flex items-center justify-between z-[200]">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
          <span class="text-[9px] font-bold text-white/40 uppercase tracking-widest italic">Live Engine Sync</span>
        </div>
        <div class="w-px h-3 bg-white/10"></div>
        <span class="text-[9px] font-mono text-white/20">COORD: [X: {{ selectedElement?.x || 0 }}, Y: {{ selectedElement?.y || 0 }}]</span>
      </div>
      <div class="flex items-center gap-6 text-[9px] font-bold uppercase tracking-widest text-white/30">
        <span>SubnetCongo B2B Credentialing Division</span>
        <span>Environment: Production v4.0</span>
      </div>
    </footer>
  </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : RÉVOCATION (ALERT STRICT)                                             -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="revocationDialog" modal header="Protocole de Révocation" :style="{width: '500px'}" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-rose-100">
                    <i class="pi pi-shield-slash text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 uppercase">Action Irréversible</h3>
                <p class="text-slate-500 text-sm mb-8 leading-relaxed">
                    La révocation invalidera immédiatement ce certificat sur le vérificateur public. Le Hash restera dans la base pour audit.
                </p>

                <div class="text-left space-y-2 mb-8">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest">Motif de la révocation <span class="text-rose-500">*</span></label>
                    <Textarea v-model="revocationForm.reason" rows="3" placeholder="Ex: Fraude avérée, erreur d'attribution..." class="w-full rounded-xl p-3 border-slate-300" />
                </div>

                <div class="flex items-center justify-center gap-3 bg-white p-4 rounded-xl border border-slate-200">
                    <InputSwitch v-model="revocationForm.confirm_action" />
                    <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">Je confirme l'invalidation</span>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between gap-4 p-5 border-t border-slate-200 bg-white">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="revocationDialog = false" />
                    <Button label="Révoquer le Titre" icon="pi pi-check" :disabled="!revocationForm.confirm_action" class="p-button-danger px-6 font-bold shadow-md" @click="submitRevocation" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOG : ÉMISSION EN MASSE (BULK ISSUANCE)                                     -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="bulkIssuanceDialog" modal header="Délivrance de Diplômes en Masse" :style="{width: '600px'}" class="rounded-2xl shadow-2xl border border-slate-200" contentClass="p-0 bg-slate-50">
            <div class="p-8 space-y-6">
                <Message severity="info" :closable="false" class="mb-4">
                    Sélectionnez une formation terminée. Le système générera automatiquement les certificats pour tous les étudiants ayant validé l'évaluation finale.
                </Message>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700">Programme de formation <span class="text-rose-500">*</span></label>
                    <Dropdown v-model="bulkIssueForm.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Sélectionnez un cours..." class="w-full bg-white border-slate-300" filter />
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-slate-700">Modèle de certificat à appliquer <span class="text-rose-500">*</span></label>
                    <Dropdown v-model="bulkIssueForm.template_id" :options="templates" optionLabel="name" optionValue="id" placeholder="Sélectionnez un design..." class="w-full bg-white border-slate-300" />
                </div>

                <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm mt-4">
                    <div>
                        <div class="text-sm font-bold text-slate-700">Envoyer les emails de félicitations</div>
                        <div class="text-xs text-slate-500 mt-0.5">Notifie instantanément les récipiendaires avec leur PDF en pièce jointe.</div>
                    </div>
                    <InputSwitch v-model="bulkIssueForm.notify_users" />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-4 p-5 border-t border-slate-200 bg-white">
                    <Button label="Annuler" class="p-button-text p-button-secondary font-bold" @click="bulkIssuanceDialog = false" />
                    <Button label="Lancer la Génération" icon="pi pi-cog" class="p-button-primary px-6 font-bold shadow-md" @click="submitBulkIssuance" :loading="isSaving" />
                </div>
            </template>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- SIDEBAR : STATISTIQUES (ANALYTICS)                                             -->
        <!-- ============================================================================== -->
        <Sidebar v-model:visible="analyticsSidebar" position="right" class="w-full md:w-[500px] p-sidebar-custom">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-chart-bar text-xl"></i></div>
                    <h2 class="text-xl font-bold text-slate-800">Analytique d'Émission</h2>
                </div>
            </template>
            <div class="p-6 space-y-8">

                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Tendances des 6 derniers mois</h4>
                    <Chart type="line" :data="trendChartData" :options="chartOptions" class="h-48" />
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h4 class="text-sm font-bold text-slate-800">Top Formations Certifiantes</h4>
                    </div>
                    <div class="p-0">
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-medium text-slate-700">Développement Web Fullstack</td>
                                    <td class="px-5 py-3 text-right font-bold text-indigo-600">845</td>
                                </tr>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3 font-medium text-slate-700">UI/UX Design Avancé</td>
                                    <td class="px-5 py-3 text-right font-bold text-indigo-600">432</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Button label="Exporter le rapport d'audit (PDF)" icon="pi pi-download" class="p-button-outlined p-button-secondary w-full font-bold bg-white" />
            </div>
        </Sidebar>

    </AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée SaaS */
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

/* Pattern sombre pour l'arrière-plan du designer */
.pattern-grid-dark {
    background-image: radial-gradient(#334155 1px, transparent 0);
    background-size: 24px 24px;
}

/* Customisation PrimeVue DataTable B2B */
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

/* Custom Timeline (Audit Row Expansion) */
:deep(.custom-timeline .p-timeline-event-opposite) {
    display: none;
}
:deep(.custom-timeline .p-timeline-event-content) {
    width: 100%;
}

/* Overrides UI Forms */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 0.75rem;
    border-color: #e2e8f0;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-dropdown:not(.p-disabled).p-focus) {
    border-color: #818cf8;
    box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
}

/* Accordion Custom for Designer */
:deep(.custom-accordion .p-accordion-header .p-accordion-header-link) {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    margin-bottom: 0.5rem;
    font-weight: 800;
    font-size: 0.875rem;
    color: #334155;
}
:deep(.custom-accordion .p-accordion-content) {
    padding: 0;
    border: none;
    background: transparent;
}
/*
===========================================================================================
QUANTUM ENGINE : CORE DESIGN SYSTEM STYLES
===========================================================================================
*/

/* Arrière-plan infini avec grille de précision */
.quantum-infinite-grid {
    background-color: #050608;
    background-image:
        linear-gradient(rgba(99, 102, 241, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.05) 1px, transparent 1px);
    background-size: 50px 50px;
}

/* Dégradé de maillage SaaS Quantum */
.quantum-mesh-gradient {
    background-image:
        radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%),
        radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%),
        radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
    filter: blur(40px);
}

/* Effet de grain Quantum (Texture de papier/film) */
.quantum-grain {
    background-image: url("https://grainy-gradients.vercel.app/noise.svg");
    filter: contrast(150%) brightness(1000%);
}

/*
===========================================================================================
CANVAS & DRAG-AND-DROP VISUALS
===========================================================================================
*/

.quantum-draggable-container {
    transition: box-shadow 0.2s ease, transform 0.1s ease;
    user-select: none;
}

.quantum-draggable-container:hover {
    outline: 1px dashed rgba(99, 102, 241, 0.5);
    outline-offset: 4px;
}

.quantum-draggable-container.is-selected {
    z-index: 1000 !important;
}

.quantum-draggable-container.is-dragging {
    opacity: 0.8;
    filter: brightness(1.1);
}

/* Boutons de formatage Quantum */
.quantum-format-btn {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.2s ease;
}

.quantum-format-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quantum-format-btn.active {
    background: #6366f1;
    color: white;
    border-color: #818cf8;
    box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
}

/*
===========================================================================================
SIDEBAR TABVIEW CUSTOMIZATION (DARK THEME)
===========================================================================================
*/

:deep(.quantum-sidebar-tabs .p-tabview-nav) {
    background: transparent !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    justify-content: center;
}

:deep(.quantum-sidebar-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border-color: transparent !important;
    color: rgba(255, 255, 255, 0.3) !important;
    padding: 1.5rem 1rem !important;
    transition: all 0.3s ease;
}

:deep(.quantum-sidebar-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom-color: #6366f1 !important;
}

:deep(.quantum-sidebar-tabs .p-tabview-panels) {
    background: transparent !important;
    padding: 0;
}

/*
===========================================================================================
QUANTUM PROPERTY INPUTS (Sliders, SelectButtons, Badges)
===========================================================================================
*/

.quantum-label {
    display: block;
    font-size: 10px;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-bottom: 0.75rem;
}

.quantum-asset-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 1.25rem;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.quantum-asset-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #6366f1;
    transform: translateY(-2px);
}

.quantum-upload-zone {
    background: rgba(255, 255, 255, 0.02);
    border: 2px dashed rgba(255, 255, 255, 0.05);
    border-radius: 1.5rem;
    padding: 2rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.quantum-upload-zone:hover {
    background: rgba(99, 102, 241, 0.05);
    border-color: #6366f1;
}

/* Sliders Néons */
:deep(.quantum-slider-xs .p-slider-range) {
    background: #6366f1 !important;
}

:deep(.quantum-slider-xs .p-slider-handle) {
    background: #ffffff !important;
    border: 2px solid #6366f1 !important;
    width: 14px !important;
    height: 14px !important;
    margin-top: -5px !important;
}

/*
===========================================================================================
LAYER LIST (CALQUES)
===========================================================================================
*/

.quantum-layer-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.01);
    border: 1px solid transparent;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quantum-layer-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.quantum-layer-item.is-active {
    background: rgba(99, 102, 241, 0.1);
    border-color: rgba(99, 102, 241, 0.3);
}

/* Bouton Quantum avec lueur (glow) */
.quantum-btn-glow {
    box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
}

.quantum-btn-glow:hover {
    box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
}

/* Animation de scan/pulsation pour le QR */
@keyframes quantum-pulse {
    0% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(1); opacity: 0.5; }
}

.quantum-badge-xs {
    font-size: 8px !important;
    padding: 0.15rem 0.4rem !important;
    font-weight: 900 !important;
}
</style>
