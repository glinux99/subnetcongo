<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - QUANTUM ENGINE STUDIO (ULTIMATE PRO EDITION)
 * ======================================================================================
 * Éditeur visuel avancé de diplômes.
 * Supporte : Drag & Drop complet, Images custom, Opacité, Styles Texte Avancés, Z-Index.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import debounce from 'lodash/debounce';
import cloneDeep from 'lodash/cloneDeep';

// Composants PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Sidebar from 'primevue/sidebar';
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
import InputSwitch from 'primevue/inputswitch';
import Divider from 'primevue/divider';
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
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Textarea from 'primevue/textarea';
import Calendar from 'primevue/calendar';

const props = defineProps({
    certificates: { type: Object, default: () => ({ data: [], total: 0, current_page: 1, per_page: 10 }) },
    templates: { type: Array, default: () => [] },
    courses: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ total_issued: 0, success_rate: 0, avg_score: 0, revocation_rate: 0 }) },
    filters: { type: Object, default: () => ({}) },
    meta: {
        type: Object,
        default: () => ({ fonts: ['Montserrat', 'Playfair Display', 'Roboto', 'Open Sans', 'Courier Prime', 'Arial', 'Times New Roman'] })
    }
});

const toast = useToast();
const confirm = useConfirm();
const loading = ref(true);
const searchLoading = ref(false);

const activeMainTab = ref('log');
const designerDialog = ref(false);
const revocationDialog = ref(false);
const bulkIssuanceDialog = ref(false);
const expirationDialog = ref(false);
const isSaving = ref(false);

// Refs pour les inputs File Globaux (Fond, Logo Officiel, Signature Officielle)
const logoUploader = ref(null);
const sigUploader = ref(null);
const bgUploader = ref(null);

const serverFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || null,
    course_id: props.filters.course_id || null,
});

watch(serverFilters, debounce(() => {
    searchLoading.value = true;
    router.get(route('certificates.index'), serverFilters, {
        preserveState: true, preserveScroll: true, replace: true,
        onFinish: () => searchLoading.value = false
    });
}, 400));

// ======================================================================================
// CONFIGURATION DU DESIGNER (ÉTAT & MOTEUR)
// ======================================================================================

const defaultFilters = () => ({ brightness: 100, contrast: 100, saturate: 100, grayscale: 0, hueRotate: 0, invert: false, blur: 0 });

const templateForm = reactive({
    id: null,
    name: "Modèle Pro",
    orientation: 'landscape',
    background_color: '#ffffff',
    border_color: '#dc2626',
    border_width: 25,
    primary_font: 'Montserrat',
    accent_font: 'Playfair Display',
    text_color: '#1e293b',
    show_qr: true,
    show_signature: true,

    // Fichiers d'upload backend
    logo_file: null,
    signature_file: null,
    background_image_file: null,

    // Flags de suppression
    delete_logo: false,
    delete_signature: false,
    delete_background: false,

    bg_opacity: 100,
    bg_filters: defaultFilters(),
    elements: [] // Contient Textes, Images customs, Logo, Signature, QR
});

const backgroundPreview = ref(null);
const logoPreview = ref(null);
const signaturePreview = ref(null);

// Variables du moteur Drag & Drop
const canvasZoom = ref(60);
const selectedElementId = ref(null);
const draggingElementId = ref(null);
const dragOffset = reactive({ x: 0, y: 0 });

// ======================================================================================
// BLUEPRINT (MODÈLE DE BASE AU CHARGEMENT)
// ======================================================================================
const loadBlueprintDesign = () => {
    templateForm.name = "Diplôme Excellence SubnetCongo";
    templateForm.border_color = '#b91c1c';
    templateForm.border_width = 30;
    templateForm.orientation = 'landscape';
    templateForm.background_color = '#ffffff';
    templateForm.primary_font = 'Montserrat';
    templateForm.text_color = '#1e293b';
    templateForm.bg_opacity = 100;

    templateForm.elements = [
        { id: 'el_title', type: 'text', name: 'Titre Principal', content: 'CERTIFICAT D\'ACHÈVEMENT', x: 160, y: 170, width: 800, height: null, rotation: 0, opacity: 100, filters: defaultFilters(), style: { fontSize: 48, color: '#b91c1c', fontFamily: 'Montserrat', fontWeight: '900', fontStyle: 'normal', textDecoration: 'none', letterSpacing: 2, textAlign: 'center', lineHeight: 1.2 } },
        { id: 'el_subtitle', type: 'text', name: 'Mention', content: 'Décerné avec la mention : {{MENTION}}', x: 360, y: 260, width: 400, height: null, rotation: 0, opacity: 100, filters: defaultFilters(), style: { fontSize: 14, color: '#475569', fontFamily: 'Montserrat', fontWeight: '400', fontStyle: 'normal', textDecoration: 'none', letterSpacing: 0, textAlign: 'center', lineHeight: 1.2 } },
        { id: 'el_student', type: 'text', name: 'Apprenant (Variable)', content: '{{NOM_APPRENANT}}', x: 260, y: 300, width: 600, height: null, rotation: 0, opacity: 100, filters: defaultFilters(), style: { fontSize: 56, color: '#dc2626', fontFamily: 'Playfair Display', fontStyle: 'italic', fontWeight: '700', textDecoration: 'none', letterSpacing: 0, textAlign: 'center', lineHeight: 1.2 } },
        { id: 'el_body', type: 'text', name: 'Description (Variable)', content: 'Pour avoir complété avec succès le programme : {{NOM_FORMATION}}.', x: 210, y: 410, width: 700, height: null, rotation: 0, opacity: 100, filters: defaultFilters(), style: { fontSize: 16, color: '#475569', fontFamily: 'Montserrat', fontWeight: '400', fontStyle: 'normal', textDecoration: 'none', letterSpacing: 0, textAlign: 'center', lineHeight: 1.6 } },
        { id: 'el_logo_off', type: 'logo', name: 'Logo Institution', content: null, x: 480, y: 50, width: 160, height: 100, rotation: 0, opacity: 100, filters: defaultFilters() },
        { id: 'el_sig_off', type: 'signature', name: 'Signature Officielle', content: null, x: 100, y: 580, width: 200, height: 100, rotation: 0, opacity: 100, filters: defaultFilters() },
        { id: 'el_qr_off', type: 'qr', name: 'Sceau QR Code', content: 'QR_CODE_PLACEHOLDER', x: 510, y: 560, width: 100, height: 100, rotation: 0, opacity: 100, filters: defaultFilters() }
    ];
};

const openDesigner = (template = null) => {
    selectedElementId.value = null;
    backgroundPreview.value = null;
    logoPreview.value = null;
    signaturePreview.value = null;

    if (template && template.id) {
        const t = cloneDeep(template);
        templateForm.id = t.id;
        templateForm.name = t.name;
        templateForm.background_color = t.background_color ? `#${t.background_color.replace('#', '')}` : '#ffffff';
        templateForm.border_color = t.border_color ? `#${t.border_color.replace('#', '')}` : '#dc2626';
        templateForm.border_width = t.border_width || 0;
        templateForm.orientation = t.orientation || 'landscape';
        templateForm.primary_font = t.primary_font || 'Montserrat';
        templateForm.accent_font = t.accent_font || 'Playfair Display';
        templateForm.text_color = t.text_color ? `#${t.text_color.replace('#', '')}` : '#1e293b';
        templateForm.show_qr = t.show_qr ?? true;
        templateForm.show_signature = t.show_signature ?? true;
        templateForm.bg_opacity = t.bg_opacity ?? 100;

        backgroundPreview.value = t.background_url || null;
        logoPreview.value = t.logo_url || null;
        signaturePreview.value = t.signature_url || null;

        // Charger éléments avec assignation des previews et vérification de la variable opacity
        const loadedElements = Array.isArray(t.elements) ? cloneDeep(t.elements) : [];
        loadedElements.forEach(el => {
            if (el.type === 'logo') el.content = logoPreview.value;
            if (el.type === 'signature') el.content = signaturePreview.value;
            if (el.opacity === undefined) el.opacity = 100; // Retrocompatibilité
        });
        templateForm.elements = loadedElements;

    } else {
        templateForm.id = null;
        loadBlueprintDesign();
    }

    templateForm.delete_background = false;
    templateForm.delete_logo = false;
    templateForm.delete_signature = false;
    designerDialog.value = true;
};

// ======================================================================================
// SYSTÈME DE GESTION DES CALQUES (Z-INDEX)
// ======================================================================================

const moveLayer = (elId, direction) => {
    const index = templateForm.elements.findIndex(e => e.id === elId);
    if (index < 0) return;

    const el = templateForm.elements.splice(index, 1)[0];

    if (direction === 'up') {
        templateForm.elements.splice(Math.min(index + 1, templateForm.elements.length), 0, el);
    } else if (direction === 'down') {
        templateForm.elements.splice(Math.max(index - 1, 0), 0, el);
    } else if (direction === 'front') {
        templateForm.elements.push(el);
    } else if (direction === 'back') {
        templateForm.elements.unshift(el);
    }
};

// ======================================================================================
// SYSTÈME DE DRAG & DROP (MOTEUR)
// ======================================================================================

const startElementDrag = (event, el) => {
    selectedElementId.value = el.id;
    draggingElementId.value = el.id;
    const canvas = document.getElementById('snc-quantum-canvas');
    const rect = canvas.getBoundingClientRect();
    const zoom = canvasZoom.value / 100;

    // Offset permet d'attraper l'élément là où la souris a cliqué
    dragOffset.x = (event.clientX - rect.left) / zoom - el.x;
    dragOffset.y = (event.clientY - rect.top) / zoom - el.y;

    document.addEventListener('mousemove', handleGlobalDrag);
    document.addEventListener('mouseup', stopGlobalDrag);
};

const handleGlobalDrag = (event) => {
    if (!draggingElementId.value) return;
    const el = templateForm.elements.find(e => e.id === draggingElementId.value);
    if (!el) return;

    const canvas = document.getElementById('snc-quantum-canvas');
    const rect = canvas.getBoundingClientRect();
    const zoom = canvasZoom.value / 100;

    el.x = Math.round((event.clientX - rect.left) / zoom - dragOffset.x);
    el.y = Math.round((event.clientY - rect.top) / zoom - dragOffset.y);
};

const stopGlobalDrag = () => {
    draggingElementId.value = null;
    document.removeEventListener('mousemove', handleGlobalDrag);
    document.removeEventListener('mouseup', stopGlobalDrag);
};

// ======================================================================================
// STYLES COMPUTÉS ET FONCTIONS D'ÉDITION (TEXTE & OPACITÉ)
// ======================================================================================

const quantumCanvasStyle = computed(() => {
    const width = templateForm.orientation === 'landscape' ? 1120 : 792;
    const height = templateForm.orientation === 'landscape' ? 792 : 1120;
    return {
        width: `${width}px`,
        height: `${height}px`,
        transform: `scale(${canvasZoom.value / 100})`,
        backgroundColor: templateForm.background_color
    };
});

const quantumElementStyle = (el) => {
    // L'ordre dans le tableau détermine le Z-Index naturel (comme dans Photoshop)
    const zIndex = templateForm.elements.findIndex(e => e.id === el.id) + 10;
    return {
        position: 'absolute',
        left: `${el.x}px`,
        top: `${el.y}px`,
        width: el.width ? `${el.width}px` : 'auto',
        height: el.height ? `${el.height}px` : 'auto',
        transform: `rotate(${el.rotation || 0}deg)`,
        opacity: el.opacity !== undefined ? el.opacity / 100 : 1,
        zIndex: zIndex,
        cursor: draggingElementId.value === el.id ? 'grabbing' : 'grab',
        outline: selectedElementId.value === el.id ? '2px dashed #dc2626' : 'none'
    };
};

const quantumTextStyle = (el) => ({
    fontSize: `${el.style.fontSize}px`,
    color: el.style.color,
    fontFamily: el.style.fontFamily,
    letterSpacing: `${el.style.letterSpacing}px`,
    fontWeight: el.style.fontWeight,
    fontStyle: el.style.fontStyle,
    textDecoration: el.style.textDecoration || 'none',
    textAlign: el.style.textAlign,
    lineHeight: el.style.lineHeight || 1.2
});

const quantumFilterStyle = (filters) => {
    if (!filters) return {};
    return {
        filter: `brightness(${filters.brightness}%) contrast(${filters.contrast}%) grayscale(${filters.grayscale}%) saturate(${filters.saturate ?? 100}%) hue-rotate(${filters.hueRotate}deg) invert(${filters.invert ? 100 : 0}%) blur(${filters.blur || 0}px)`
    };
};

// Formater le texte (Gras, Italique, Souligné)
const toggleTextStyle = (el, prop, activeVal, defaultVal) => {
    if (!el || el.type !== 'text') return;
    el.style[prop] = el.style[prop] === activeVal ? defaultVal : activeVal;
};

const handleTextEdit = (event, el) => {
    el.content = event.target.innerHTML;
};

// ======================================================================================
// AJOUT ET GESTION DES ÉLÉMENTS (INCLUANT L'UPLOAD D'IMAGES CUSTOM)
// ======================================================================================

const addElement = (type, variant = 'standard', content = null) => {
    const newEl = {
        id: `el_${Date.now()}_${Math.random().toString(36).substr(2, 5)}`,
        type,
        name: type === 'text' ? 'Nouveau Texte' : (type === 'image' ? 'Image Custom' : type),
        content: content || (type === 'text' ? 'Modifiez ce texte' : null),
        x: 100, y: 100,
        width: type === 'text' ? 400 : 200,
        height: type === 'text' ? null : 200,
        rotation: 0,
        opacity: 100, // Nouvelle propriété d'opacité universelle
        filters: defaultFilters(),
        style: type === 'text' ? {
            fontSize: variant === 'h1' ? 48 : 16,
            color: templateForm.text_color,
            fontFamily: templateForm.primary_font,
            fontWeight: variant === 'h1' ? '900' : '400',
            fontStyle: 'normal',
            textDecoration: 'none',
            textAlign: 'left',
            letterSpacing: 0,
            lineHeight: 1.2
        } : {}
    };
    templateForm.elements.push(newEl);
    selectedElementId.value = newEl.id;
};

// Fonction spéciale pour Uploader n'importe quelle image directement sur le canevas
const addCustomImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            addElement('image', 'standard', ev.target.result);
        };
        reader.readAsDataURL(file);
    };
    input.click();
};

const removeElement = (id) => {
    templateForm.elements = templateForm.elements.filter(e => e.id !== id);
    if (selectedElementId.value === id) selectedElementId.value = null;
};

const duplicateElement = (el) => {
    const clone = cloneDeep(el);
    clone.id = `copy_${Date.now()}`;
    clone.x += 30;
    clone.y += 30;
    templateForm.elements.push(clone);
    selectedElementId.value = clone.id;
};

// ======================================================================================
// GESTION DES UPLOADS OFFICIELS (FOND, LOGO, SIGNATURE) ET SYNCHRONISATION
// ======================================================================================

const handleFileUpload = (event, type) => {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();

    reader.onload = (e) => {
        if (type === 'bg') {
            backgroundPreview.value = e.target.result;
            templateForm.background_image_file = file;
            templateForm.delete_background = false;
        }
        if (type === 'logo') {
            logoPreview.value = e.target.result;
            templateForm.logo_file = file;
            templateForm.delete_logo = false;
            let logoEl = templateForm.elements.find(el => el.type === 'logo');
            if (logoEl) logoEl.content = e.target.result;
            else templateForm.elements.push({ id: `el_logo_${Date.now()}`, type: 'logo', name: 'Logo Institution', content: e.target.result, x: 50, y: 50, width: 150, height: 100, rotation: 0, opacity: 100, filters: defaultFilters() });
        }
        if (type === 'sig') {
            signaturePreview.value = e.target.result;
            templateForm.signature_file = file;
            templateForm.delete_signature = false;
            templateForm.show_signature = true;
            let sigEl = templateForm.elements.find(el => el.type === 'signature');
            if (sigEl) sigEl.content = e.target.result;
            else templateForm.elements.push({ id: `el_sig_${Date.now()}`, type: 'signature', name: 'Signature Officielle', content: e.target.result, x: 100, y: 600, width: 200, height: 100, rotation: 0, opacity: 100, filters: defaultFilters() });
        }
    };
    reader.readAsDataURL(file);
    event.target.value = null;
};

const clearFile = (type) => {
    if (type === 'bg') { backgroundPreview.value = null; templateForm.background_image_file = null; templateForm.delete_background = true; }
    if (type === 'logo') {
        logoPreview.value = null; templateForm.logo_file = null; templateForm.delete_logo = true;
        let logoEl = templateForm.elements.find(el => el.type === 'logo');
        if (logoEl) logoEl.content = null;
    }
    if (type === 'sig') {
        signaturePreview.value = null; templateForm.signature_file = null; templateForm.delete_signature = true;
        let sigEl = templateForm.elements.find(el => el.type === 'signature');
        if (sigEl) sigEl.content = null;
    }
};

// ======================================================================================
// SAUVEGARDE & COMMUNICATION BACKEND
// ======================================================================================

const saveTemplate = () => {
    if (!templateForm.name.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le nom est obligatoire.', life: 3000 });
        return;
    }
    isSaving.value = true;
    const isUpdate = !!templateForm.id;
    const url = isUpdate ? route('certificates.templates.update', { template: templateForm.id }) : route('certificates.templates.store');

    const payload = {
        ...templateForm,
        background_color: templateForm.background_color.replace('#', ''),
        border_color: templateForm.border_color.replace('#', ''),
        text_color: templateForm.text_color.replace('#', ''),
        elements: JSON.stringify(templateForm.elements)
    };
    if (isUpdate) delete payload.id;

    router.post(url, payload, {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Succès', detail: 'Modèle sauvegardé avec succès.' }); designerDialog.value = false; },
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les données saisies.' }),
        onFinish: () => isSaving.value = false
    });
};

const duplicateTemplate = (template) => {
    const newTemplate = cloneDeep(template);
    newTemplate.id = null; newTemplate.name = `${template.name} - Copie`;
    openDesigner(newTemplate);
};

const deleteTemplate = (id) => {
    confirm.require({
        message: 'Voulez-vous vraiment supprimer ce modèle ?', header: 'Suppression', icon: 'pi pi-trash', acceptClass: 'p-button-danger',
        accept: () => router.delete(route('certificates.templates.destroy', { template: id }))
    });
};

// ... [Logique d'Émission Bulk & Révocation] ...
const bulkIssueForm = useForm({ course_id: null, template_id: null, notify_users: true, expires_at: null });
const revocationForm = useForm({ id: null, reason: '', confirm_action: false });
const expirationForm = useForm({ id: null, expires_at: null, status: null });

const openBulkIssuance = () => { bulkIssueForm.reset(); bulkIssuanceDialog.value = true; };
const submitBulkIssuance = () => {
    if (!bulkIssueForm.course_id || !bulkIssueForm.template_id) return;
    isSaving.value = true;
    bulkIssueForm.post(route('certificates.bulkIssue'), {
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Génération lancée' }); bulkIssuanceDialog.value = false; },
        onFinish: () => isSaving.value = false
    });
};

const handleRevocation = (cert) => { revocationForm.reset(); revocationForm.id = cert.id; revocationDialog.value = true; };
const submitRevocation = () => {
    if (!revocationForm.reason || !revocationForm.confirm_action) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez fournir une raison et confirmer l\'action.', life: 3000 });
        return;
    }
    router.put(route('certificates.update', { certificate: revocationForm.id }), {
        status: 'Revoked',
        revocation_reason: revocationForm.reason,
    }, {
        onSuccess: () => {
            toast.add({ severity: 'warn', summary: 'Révoqué', detail: 'Le certificat a été révoqué avec succès.' });
            revocationDialog.value = false;
        },
        onError: (errors) => {
            let detail = 'Une erreur est survenue lors de la révocation.';
            if (errors.revocation_reason) {
                detail = errors.revocation_reason;
            } else if (errors.status) {
                detail = errors.status;
            } else if (Object.keys(errors).length > 0) {
                detail = Object.values(errors).join(' ');
            }
            toast.add({ severity: 'error', summary: 'Erreur', detail: detail, life: 5000 });
        }
    });
};

const openExpirationDialog = (cert) => {
    expirationForm.reset();
    expirationForm.id = cert.id;
    expirationForm.status = cert.status;
    expirationForm.expires_at = cert.expires_at ? new Date(cert.expires_at) : null;
    expirationDialog.value = true;
};

const submitExpiration = () => {
    const expirationDate = new Date(expirationForm.expires_at).setHours(0,0,0,0);
    const today = new Date().setHours(0,0,0,0);
    expirationForm.status =expirationDate <= today ? 'Expired' : "Active";
    if (!expirationForm.id || !expirationForm.expires_at) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez sélectionner une date.', life: 3000 });
        return;
    }
    router.put(route('certificates.update', { certificate: expirationForm.id }), expirationForm, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Date d\'expiration définie.' });
            expirationDialog.value = false;
        },
        onError: (errors) => {
            const errorDetail = Object.values(errors).flat().join(' ; ') || 'Une erreur est survenue.';
            toast.add({ severity: 'error', summary: 'Erreur', detail: errorDetail, life: 5000 });
        }
    });
};

const downloadPdf = (id) => window.open(route('certificates.download', { certificate: id }), '_blank');
const openVerificationLink = (hash) => window.open(route('certificates.verify.public', { hash: hash }), '_blank');
const selectedElement = computed(() => templateForm.elements.find(e => e.id === selectedElementId.value) || null);
const getStatusSeverity = (status) => ({ Active: { sev: 'success', label: 'Valide', icon: 'pi-verified' }, Revoked: { sev: 'danger', label: 'Révoqué', icon: 'pi-shield-slash' }, Expired: { sev: 'warning', label: 'Expiré', icon: 'pi-clock' } })[status] || { sev: 'info', label: status, icon: 'pi-info-circle' };

onMounted(() => setTimeout(() => loading.value = false, 800));
</script>
<template>
    <AppLayout>
        <Head title="Ingénierie de Certification | SubnetCongo LMS" />
        <Toast position="top-right" />
        <ConfirmDialog />

        <!-- ============================================================================== -->
        <!-- TABLEAU DE BORD PRINCIPAL (HORS ÉDITEUR)                                       -->
        <!-- ============================================================================== -->
        <div class="min-h-screen bg-slate-50 p-4 md:p-8 lg:p-10 font-sans pb-24">

            <!-- EN-TÊTE DU MODULE -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6 mb-10 bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                <div class="space-y-4 w-full xl:w-auto">
                    <Breadcrumb :model="[{ label: 'LMS Center', icon: 'pi pi-home' }, { label: 'Trust Center & Diplômes', class: 'font-bold text-red-600' }]" class="bg-transparent p-0 m-0 border-none text-xs text-slate-500" />
                    <div>
                        <h1 class="text-3xl md:text-5xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                            Trust Center
                            <Badge value="Blockchain Ready" severity="success" class="text-xs bg-emerald-100 text-emerald-700 border border-emerald-200 px-3 py-1"></Badge>
                        </h1>
                        <p class="text-slate-500 text-sm md:text-base max-w-3xl mt-3 leading-relaxed font-medium">
                            Pilotez la valeur académique de votre institution. Le <strong>Quantum Engine</strong> vous permet de concevoir des diplômes HD avec un placement libre (Drag & Drop) des variables, QR Codes, et Signatures.
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                    <Button label="Audit Log" icon="pi pi-history" class="p-button-outlined p-button-secondary bg-slate-50 text-slate-700 border-slate-200 shadow-sm font-bold w-full sm:w-auto px-6 py-3 rounded-xl hover:bg-slate-100 transition-all" />
                    <Button label="Délivrer en masse" icon="pi pi-send" class="bg-red-600 hover:bg-red-700 border-none shadow-lg shadow-red-500/30 font-bold px-8 py-3 w-full sm:w-auto text-white rounded-xl transition-all hover:-translate-y-0.5" @click="openBulkIssuance" />
                </div>
            </div>

            <!-- KPI DASHBOARD -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:border-red-300 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out opacity-50"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shadow-sm border border-red-200"><i class="pi pi-copy text-lg"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Délivrés</p>
                        </div>
                        <div class="flex items-end gap-3">
                            <h3 class="text-5xl font-black text-slate-800 tracking-tighter">{{ props.stats?.total_issued || '2,450' }}</h3>
                            <span class="text-sm font-bold text-emerald-500 mb-1 flex items-center"><i class="pi pi-arrow-up text-[10px] mr-1"></i>12%</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:border-emerald-300 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm border border-emerald-200"><i class="pi pi-chart-pie text-lg"></i></div>
                            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Taux de Réussite</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between items-end mb-2">
                            <h3 class="text-4xl font-black text-slate-800 tracking-tighter">{{ props.stats?.success_rate || 78 }}%</h3>
                            <span class="text-xs text-slate-400 font-medium mb-1">Moyenne globale</span>
                        </div>
                        <ProgressBar :value="props.stats?.success_rate || 78" :showValue="false" class="h-2 bg-slate-100 rounded-full" color="#10b981"></ProgressBar>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:border-blue-300 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-sm border border-blue-200"><i class="pi pi-globe text-lg"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Vérifications Web</p>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-5xl font-black text-slate-800 tracking-tighter">14.2k</h3>
                        <span class="text-xs text-slate-400 font-medium bg-slate-100 px-2 py-1 rounded-md">Vues employeurs</span>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-3xl p-6 border border-slate-800 shadow-lg hover:border-rose-500 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+CjxjaXJjbGUgY3g9IjIiIGN5PSIyIiByPSIyIiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDUiLz4KPC9zdmc+')] opacity-50"></div>
                    <div class="flex items-center gap-3 mb-6 relative z-10">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center border border-rose-500/30"><i class="pi pi-shield-slash text-lg"></i></div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Titres Révoqués</p>
                    </div>
                    <h3 class="text-5xl font-black text-white tracking-tighter relative z-10">{{ props.stats?.revocation_rate || 14 }}</h3>
                    <i class="pi pi-ban absolute -right-6 -bottom-6 text-[100px] text-white/5 group-hover:scale-110 group-hover:text-rose-500/10 transition-all duration-500"></i>
                </div>
            </div>

            <!-- SYSTÈME D'ONGLETS PRINCIPAL -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mb-10">
                <div class="flex border-b border-slate-100 bg-slate-50/80 px-6 pt-4 gap-2">
                    <button @click="activeMainTab = 'log'" class="px-8 py-4 font-bold text-sm flex items-center gap-3 border-b-2 transition-all" :class="activeMainTab === 'log' ? 'border-red-600 text-red-700 bg-white rounded-t-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 rounded-t-2xl'">
                        <i class="pi pi-list"></i> Registre des Émissions
                    </button>
                    <button @click="activeMainTab = 'templates'" class="px-8 py-4 font-bold text-sm flex items-center gap-3 border-b-2 transition-all" :class="activeMainTab === 'templates' ? 'border-red-600 text-red-700 bg-white rounded-t-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.02)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 rounded-t-2xl'">
                        <i class="pi pi-palette"></i> Studio de Création (Modèles)
                    </button>
                </div>

                <!-- ========================================================================== -->
                <!-- VUE 1 : REGISTRE DES ÉMISSIONS (DATATABLE)                                 -->
                <!-- ========================================================================== -->
                <div v-show="activeMainTab === 'log'">
                    <div class="border-b border-slate-100 p-6 flex flex-col lg:flex-row justify-between items-center gap-4 bg-white">
                        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="serverFilters.search" placeholder="Rechercher un apprenant, un Hash..." class="w-full sm:w-80 border-slate-200 font-medium bg-slate-50 rounded-xl" />
                            </IconField>
                            <Dropdown v-model="serverFilters.status" :options="['Active', 'Revoked', 'Expired']" placeholder="État du certificat" class="w-56 border-slate-200 font-medium bg-slate-50 rounded-xl" showClear>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2 font-bold">
                                        <i :class="`pi ${getStatusSeverity(slotProps.option).icon} text-${getStatusSeverity(slotProps.option).sev}-500`"></i>
                                        <span>{{ getStatusSeverity(slotProps.option).label }}</span>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button icon="pi pi-file-excel" label="Export XLS" class="p-button-secondary p-button-outlined bg-white shadow-sm font-bold rounded-xl" />
                            <Button icon="pi pi-sync" class="p-button-secondary p-button-text p-button-rounded hover:bg-slate-100" @click="router.reload()" v-tooltip.top="'Actualiser'" />
                        </div>
                    </div>

                    <DataTable :value="props.certificates.data" dataKey="id" :paginator="true" :rows="10" :totalRecords="props.certificates.total" lazy class="p-datatable-custom border-none" stripedRows rowHover>
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-24 text-slate-500 bg-white">
                                <div class="w-32 h-32 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
                                    <i class="pi pi-verified text-6xl text-slate-300"></i>
                                </div>
                                <h3 class="text-2xl font-black text-slate-800 mb-2">Aucun diplôme délivré</h3>
                                <p class="text-base text-center text-slate-500">Les certificats générés pour vos apprenants s'afficheront ici avec leur empreinte cryptographique.</p>
                            </div>
                        </template>

                        <Column field="certificate_code" header="Identifiant Trust">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1 py-3">
                                    <span class="font-mono text-sm font-black text-red-600 bg-red-50 px-3 py-1 rounded-md border border-red-100 w-fit tracking-wider">{{ data.certificate_code }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1 mt-1" v-tooltip.top="data.verification_hash">
                                        <i class="pi pi-lock text-slate-300"></i> SHA256: {{ data.verification_hash?.substring(0, 16) }}...
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column field="user.name" header="Récipiendaire">
                            <template #body="{ data }">
                                <div class="flex items-center gap-4">
                                    <Avatar :image="data.user.avatar" shape="circle" size="xlarge" class="shadow-sm border-2 border-white ring-1 ring-slate-200" />
                                    <div>
                                        <div class="font-black text-slate-800 text-sm mb-0.5">{{ data.user.name }}</div>
                                        <div class="text-xs text-slate-500 font-medium">{{ data.user.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="course.title" header="Formation & Délivrance">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-2">
                                    <span class="font-bold text-slate-700 text-sm line-clamp-1">{{ data.course.title }}</span>
                                    <div class="flex items-center gap-2">
                                        <Badge :value="`Score: ${data.score}%`" severity="secondary" class="text-[10px] bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5"></Badge>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider"><i class="pi pi-calendar text-[10px] mr-1"></i> {{ data.issued_at }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column field="status" header="Statut">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status).sev" :icon="getStatusSeverity(data.status).icon" :value="getStatusSeverity(data.status).label" class="text-[10px] font-black px-3 py-1.5 uppercase tracking-widest border rounded-lg" :class="`border-${getStatusSeverity(data.status).sev}-200 bg-${getStatusSeverity(data.status).sev}-50 text-${getStatusSeverity(data.status).sev}-700`" />
                            </template>
                        </Column>
                        <Column header="Actions" alignFrozen="right" style="text-align: right; min-width: 12rem;">
                            <template #body="{ data }">
                                <div class="flex justify-end gap-2">
                                    <Button icon="pi pi-download" class="p-button-text p-button-secondary p-button-rounded hover:bg-slate-100 w-10 h-10" v-tooltip.top="'Télécharger PDF HD'" @click="downloadPdf(data.id)" />
                                    <Button icon="pi pi-share-alt" class="p-button-text p-button-info p-button-rounded hover:bg-blue-50 hover:text-blue-600 w-10 h-10" v-tooltip.top="'Lien Public / Partage'" @click="openVerificationLink(data.verification_hash)" />
                                    <Button v-if="data.status === 'Active'" icon="pi pi-calendar-times" class="p-button-text p-button-warning p-button-rounded hover:bg-amber-50 hover:text-amber-600 w-10 h-10" v-tooltip.top="'Définir une date d\'expiration'" @click="openExpirationDialog(data)" />
                                    <Button v-if="data.status !== 'Revoked'" icon="pi pi-shield-slash" class="p-button-text p-button-danger p-button-rounded hover:bg-rose-50 hover:text-rose-600 w-10 h-10" v-tooltip.top="'Révoquer le diplôme'" @click="handleRevocation(data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- ========================================================================== -->
                <!-- VUE 2 : GALERIE DES MODÈLES (STUDIO)                                       -->
                <!-- ========================================================================== -->
                <div v-show="activeMainTab === 'templates'" class="p-8 bg-slate-50/50">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                        <div>
                            <h3 class="text-2xl font-black text-slate-800">Galerie de Modèles</h3>
                            <p class="text-sm text-slate-500 mt-1">Gérez l'identité visuelle de vos diplômes.</p>
                        </div>
                        <Button label="Forger un Nouveau Modèle" icon="pi pi-sparkles" class="bg-red-600 hover:bg-red-700 border-none font-bold text-white shadow-lg shadow-red-500/30 px-6 py-3 rounded-xl transition-transform hover:-translate-y-1" @click="openDesigner(null)" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-8">

                        <!-- CARTE : AJOUTER -->
                        <div class="bg-transparent border-2 border-dashed border-slate-300 rounded-3xl flex flex-col items-center justify-center p-8 min-h-[320px] cursor-pointer hover:bg-red-50/50 hover:border-red-400 hover:shadow-lg transition-all duration-300 group" @click="openDesigner(null)">
                            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-slate-200 group-hover:text-red-600 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                <i class="pi pi-plus text-3xl"></i>
                            </div>
                            <h4 class="font-black text-lg text-slate-700 group-hover:text-red-700 text-center">Création depuis Zéro</h4>
                            <p class="text-xs text-slate-500 mt-3 text-center px-4 leading-relaxed font-medium">Lancez le Quantum Engine pour un design pixel-perfect avec variables dynamiques.</p>
                        </div>

                        <!-- BOUCLE DES TEMPLATES EXISTANTS -->
                        <div v-for="template in templates" :key="template.id" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden group hover:border-red-400 hover:shadow-xl transition-all duration-300 flex flex-col h-full">

                            <!-- Miniatura CSS -->
                            <div class="h-48 bg-slate-100 flex items-center justify-center p-6 relative overflow-hidden transition-transform duration-500 group-hover:scale-105" :style="{ backgroundColor: `#${template.background_color?.replace('#','')}` }">
                                <!-- Trame de fond -->
                                <div class="absolute inset-0 opacity-[0.03] bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMSIvPgo8cGF0aCBkPSJNMCAwaDh2OEgwaC04eiIgZmlsbD0ibm9uZSIvPjwvc3ZnPg==')]"></div>

                                <div class="absolute inset-3 rounded-lg shadow-sm" :style="{ border: `${template.border_width / 3}px solid #${template.border_color?.replace('#','')}` }"></div>
                                <div class="text-center relative z-10 w-full px-8">
                                    <div class="font-black text-lg mb-2 tracking-widest uppercase" :style="{ color: `#${template.text_color?.replace('#','')}`, fontFamily: template.primary_font }">CERTIFICAT</div>
                                    <div class="w-full h-px opacity-20 mb-3" :style="{ backgroundColor: `#${template.text_color?.replace('#','')}` }"></div>
                                    <div class="w-3/4 mx-auto h-2 rounded-full opacity-10 mb-2" :style="{ backgroundColor: `#${template.text_color?.replace('#','')}` }"></div>
                                    <div class="w-1/2 mx-auto h-2 rounded-full opacity-10" :style="{ backgroundColor: `#${template.text_color?.replace('#','')}` }"></div>
                                </div>
                            </div>

                            <div class="p-6 flex-1 flex flex-col justify-between bg-white relative z-20">
                                <div>
                                    <div class="flex justify-between items-start mb-3">
                                        <h4 class="font-black text-lg text-slate-800 line-clamp-1 pr-2" :title="template.name">{{ template.name }}</h4>
                                        <Badge v-if="template.is_default" value="Défaut" severity="success" class="text-[9px] px-2" />
                                    </div>
                                    <div class="flex flex-wrap gap-2 text-xs text-slate-500 font-bold mb-4">
                                        <span class="bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 uppercase tracking-widest text-[9px] flex items-center gap-1"><i :class="template.orientation === 'landscape' ? 'pi pi-image' : 'pi pi-file'"></i> {{ template.orientation }}</span>
                                        <span class="bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 text-[10px]">{{ template.elements?.length || 0 }} calques</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-5 border-t border-slate-100">
                                    <Button label="Éditer le design" icon="pi pi-pencil" class="p-button-outlined text-red-600 border-red-200 hover:bg-red-50 hover:border-red-400 font-bold w-full mr-2 rounded-xl" @click="openDesigner(template)" />
                                    <div class="flex gap-1">
                                        <Button icon="pi pi-copy" class="p-button-text p-button-secondary p-button-rounded w-10 h-10 hover:bg-slate-100 text-slate-400 hover:text-slate-700" v-tooltip.top="'Dupliquer ce modèle'" @click="duplicateTemplate(template)" />
                                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-rounded w-10 h-10 hover:bg-rose-50 text-slate-300 hover:text-rose-500" v-tooltip.top="'Supprimer définitivement'" @click="deleteTemplate(template.id)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================================== -->
        <!-- THE QUANTUM ENGINE : FULLSCREEN VISUAL DESIGNER (DARK MODE SAAS)               -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="designerDialog" modal :closable="false" :draggable="false" :style="{ width: '100vw', height: '100vh', maxHeight: '100vh', margin: 0 }" contentClass="p-0 flex flex-col overflow-hidden bg-[#0c0c0e]" headerClass="hidden" class="m-0 rounded-none shadow-none SNC-Quantum-Engine" blockScroll>

            <!-- ---------------------------------------------------------------------- -->
            <!-- TOP NAVBAR (App Bar)                                                   -->
            <!-- ---------------------------------------------------------------------- -->
            <nav class="flex-none h-[72px] bg-[#121215] border-b border-white/5 flex items-center justify-between px-6 z-[300] shadow-2xl relative">

                <!-- Left: Branding & Meta -->
                <div class="flex items-center gap-6 w-1/3">
                    <button @click="designerDialog = false" class="w-11 h-11 rounded-xl bg-white/5 hover:bg-red-600 hover:text-white flex items-center justify-center transition-all duration-300 border border-white/5 hover:border-red-500 group" v-tooltip.bottom="'Quitter le studio'">
                        <i class="pi pi-arrow-left text-white/50 group-hover:text-white"></i>
                    </button>
                    <div class="h-8 w-px bg-white/10"></div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2 group cursor-text">
                            <InputText v-model="templateForm.name" class="bg-transparent border-none text-white font-black text-lg p-0 focus:ring-0 w-64 placeholder:text-white/20 transition-colors hover:text-red-100" placeholder="Nom du modèle..." />
                            <i class="pi pi-pencil text-white/20 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[9px] text-emerald-400/70 font-mono tracking-widest uppercase">SubnetCongo Engine v4.0</span>
                        </div>
                    </div>
                </div>

                <!-- Center: Zoom & View Controls -->
                <div class="hidden lg:flex items-center justify-center w-1/3">
                    <div class="flex items-center gap-2 bg-[#1a1a1f] p-1.5 rounded-xl border border-white/5 shadow-inner">
                        <Button icon="pi pi-minus" class="p-button-text p-button-rounded text-white/40 hover:text-white hover:bg-white/5 w-8 h-8 p-0" @click="canvasZoom = Math.max(10, canvasZoom - 10)" />
                        <Slider v-model="canvasZoom" :min="10" :max="300" class="w-40 red-slider mx-2" />
                        <Button icon="pi pi-plus" class="p-button-text p-button-rounded text-white/40 hover:text-white hover:bg-white/5 w-8 h-8 p-0" @click="canvasZoom = Math.min(300, canvasZoom + 10)" />
                        <div class="w-px h-5 bg-white/10 mx-1"></div>
                        <div class="w-14 text-center">
                            <span class="text-xs font-mono font-bold text-red-400">{{ canvasZoom }}%</span>
                        </div>
                        <Button icon="pi pi-expand" class="p-button-text p-button-rounded text-white/40 hover:text-white hover:bg-white/5 w-8 h-8 p-0 ml-1" @click="canvasZoom = 60" v-tooltip.bottom="'Ajuster à l\'écran'" />
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center justify-end gap-4 w-1/3">
                    <Button label="Aperçu HD" icon="pi pi-eye" class="p-button-text text-white/70 hover:text-white hover:bg-white/5 font-bold px-4 rounded-xl" />
                    <Button label="Sauvegarder le Modèle" icon="pi pi-cloud-upload" class="bg-red-600 hover:bg-red-500 border-none font-bold px-8 py-3 rounded-xl text-white shadow-[0_0_20px_rgba(220,38,38,0.3)] transition-all hover:-translate-y-0.5" @click="saveTemplate" :loading="isSaving" />
                </div>
            </nav>

            <div class="flex-1 flex overflow-hidden relative">

                <!-- ---------------------------------------------------------------------- -->
                <!-- LEFT SIDEBAR : TOOLBOX, ASSETS & LAYERS                                -->
                <!-- ---------------------------------------------------------------------- -->
                <aside class="w-[340px] flex-none bg-[#141418] border-r border-white/5 flex flex-col z-[200] shadow-2xl relative">
                    <TabView class="quantum-tabs h-full flex flex-col w-full">

                        <!-- TAB 1 : INSERTION & VARIABLES -->
                        <TabPanel>
                            <template #header><div class="flex flex-col items-center gap-1 w-full py-1"><i class="pi pi-box text-lg"></i><span class="text-[9px] uppercase tracking-widest">Outils</span></div></template>

                            <div class="p-6 space-y-8 overflow-y-auto custom-scrollbar h-[calc(100vh-140px)] pb-32">

                                <!-- Typographie Base -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="h-px bg-white/10 flex-1"></div>
                                        <span class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Typographie</span>
                                        <div class="h-px bg-white/10 flex-1"></div>
                                    </div>
                                    <button @click="addElement('text', 'h1')" class="w-full flex items-center gap-4 p-4 rounded-2xl bg-[#1a1a1f] hover:bg-red-600/10 border border-white/5 hover:border-red-500/30 transition-all group text-left shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center group-hover:bg-red-500/20 transition-colors">
                                            <i class="pi pi-heading text-xl text-white/50 group-hover:text-red-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white">Gros Titre</div>
                                            <div class="text-[10px] text-white/40 mt-0.5">Pour le nom du diplôme</div>
                                        </div>
                                    </button>
                                    <button @click="addElement('text', 'body')" class="w-full flex items-center gap-4 p-4 rounded-2xl bg-[#1a1a1f] hover:bg-white/5 border border-white/5 transition-all group text-left shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                                            <i class="pi pi-align-left text-xl text-white/50"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white">Corps de texte</div>
                                            <div class="text-[10px] text-white/40 mt-0.5">Paragraphes et mentions</div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Données Dynamiques (Variables) -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="h-px bg-white/10 flex-1"></div>
                                        <span class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em] flex items-center gap-1"><i class="pi pi-database text-[8px]"></i> Variables Auto</span>
                                        <div class="h-px bg-white/10 flex-1"></div>
                                    </div>
                                    <div class="bg-blue-500/5 border border-blue-500/20 rounded-2xl p-4 shadow-inner">
                                        <p class="text-[10px] text-white/60 mb-4 leading-relaxed font-medium">Glissez ou tapez ces tags dans vos textes. Le système les remplacera lors de l'émission.</p>
                                        <div class="flex flex-wrap gap-2">
                                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold rounded-lg border border-blue-500/30 cursor-copy hover:bg-blue-500/40 transition-colors" v-tooltip.top="'Copier'">{{ "{NOM_APPRENANT}" }}</span>
                                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold rounded-lg border border-blue-500/30 cursor-copy hover:bg-blue-500/40 transition-colors" v-tooltip.top="'Copier'">{{ "{NOM_FORMATION}" }}</span>
                                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold rounded-lg border border-blue-500/30 cursor-copy hover:bg-blue-500/40 transition-colors" v-tooltip.top="'Copier'">{{ "{DATE}" }}</span>
                                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold rounded-lg border border-blue-500/30 cursor-copy hover:bg-blue-500/40 transition-colors" v-tooltip.top="'Copier'">{{ "{CERTIFICATE_ID}" }}</span>
                                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold rounded-lg border border-blue-500/30 cursor-copy hover:bg-blue-500/40 transition-colors" v-tooltip.top="'Copier'">{{ "{MENTION}" }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Médias Libres -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="h-px bg-white/10 flex-1"></div>
                                        <span class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Médias</span>
                                        <div class="h-px bg-white/10 flex-1"></div>
                                    </div>
                                    <button @click="addElement('image')" class="w-full flex flex-col items-center justify-center gap-3 p-6 rounded-2xl bg-[#1a1a1f] hover:bg-white/5 border border-dashed border-white/20 transition-all text-center">
                                        <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center">
                                            <i class="pi pi-image text-2xl text-white/40"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white mb-1">Image Personnalisée</div>
                                            <div class="text-[9px] text-white/40">Ajouter tampons, logos partenaires...</div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- TAB 2 : GESTION DES CALQUES -->
                        <TabPanel>
                            <template #header><div class="flex flex-col items-center gap-1 w-full py-1"><i class="pi pi-clone text-lg"></i><span class="text-[9px] uppercase tracking-widest">Calques</span></div></template>

                            <div class="p-4 space-y-1 overflow-y-auto custom-scrollbar h-[calc(100vh-140px)]">
                                <div class="flex justify-between items-center px-2 mb-4 mt-2">
                                    <span class="text-[10px] font-black text-white/30 uppercase tracking-widest">Ordre d'affichage (Z-Index)</span>
                                    <Badge :value="templateForm.elements.length" class="bg-white/10 text-white/60 border-none text-[9px]" />
                                </div>

                                <!-- Liste des calques -->
                                <div v-for="el in templateForm.elements" :key="el.id"
                                     @click="selectedElementId = el.id"
                                     class="flex items-center justify-between p-3 rounded-xl cursor-pointer group transition-all border border-transparent"
                                     :class="selectedElementId === el.id ? 'bg-red-600/10 border-red-500/30 shadow-inner' : 'hover:bg-[#1a1a1f]'">

                                    <div class="flex items-center gap-4 overflow-hidden w-full">
                                        <i class="pi pi-bars text-white/10 group-hover:text-white/30 cursor-grab text-xs"></i>
                                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center flex-none">
                                            <i :class="{
                                                'pi pi-font text-blue-400': el.type === 'text',
                                                'pi pi-image text-purple-400': el.type === 'image',
                                                'pi pi-shield text-emerald-400': el.type === 'logo',
                                                'pi pi-pencil text-amber-400': el.type === 'signature',
                                                'pi pi-qrcode text-slate-400': el.type === 'qr'
                                            }" class="text-sm"></i>
                                        </div>
                                        <div class="flex flex-col overflow-hidden w-full pr-2">
                                            <span class="text-xs font-bold text-white truncate w-full" :class="{'text-red-400': selectedElementId === el.id}">{{ el.name || el.type }}</span>
                                            <span v-if="el.type==='text'" class="text-[9px] text-white/40 truncate w-full">{{ el.content }}</span>
                                        </div>
                                    </div>

                                    <!-- Actions Rapides Calque -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button v-if="el.type !== 'qr' && el.type !== 'logo' && el.type !== 'signature'" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-colors" @click.stop="duplicateElement(el)" v-tooltip.top="'Dupliquer'">
                                            <i class="pi pi-copy text-[10px]"></i>
                                        </button>
                                        <button v-if="el.type !== 'qr' && el.type !== 'logo' && el.type !== 'signature'" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-red-500/20 text-red-400/50 hover:text-red-400 transition-colors" @click.stop="removeElement(el.id)" v-tooltip.top="'Supprimer'">
                                            <i class="pi pi-trash text-[10px]"></i>
                                        </button>
                                        <i v-else class="pi pi-lock text-[10px] text-white/20 px-2" v-tooltip.top="'Géré via paramètres globaux'"></i>
                                    </div>
                                </div>

                                <div v-if="templateForm.elements.length === 0" class="flex flex-col items-center justify-center p-10 text-center">
                                    <i class="pi pi-inbox text-3xl text-white/10 mb-3"></i>
                                    <p class="text-xs text-white/30">Aucun élément sur le canevas.</p>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </aside>

                <!-- ---------------------------------------------------------------------- -->
                <!-- CENTER WORKSPACE : THE CANVAS (Infinite Grid)                          -->
                <!-- ---------------------------------------------------------------------- -->
                <main class="flex-1 relative overflow-hidden flex items-center justify-center p-10 quantum-grid" @dblclick.self="addElementAtPosition">

                    <!-- Rulers (Esthétique Logiciel) -->
                    <div class="absolute top-0 left-[340px] right-[400px] h-6 bg-[#0f0f13] border-b border-white/5 flex items-end overflow-hidden z-10 opacity-50 pointer-events-none">
                        <div v-for="n in 50" :key="'x'+n" class="border-l border-white/10 h-2 w-[100px] flex-none relative">
                            <span class="absolute -top-4 -left-1 text-[8px] font-mono text-white/30">{{ (n-1)*100 }}</span>
                        </div>
                    </div>
                    <div class="absolute top-[72px] left-[340px] bottom-0 w-6 bg-[#0f0f13] border-r border-white/5 flex flex-col items-end overflow-hidden z-10 opacity-50 pointer-events-none">
                        <div v-for="n in 50" :key="'y'+n" class="border-t border-white/10 w-2 h-[100px] flex-none relative">
                            <span class="absolute -top-2 left-1 text-[8px] font-mono text-white/30 rotate-90">{{ (n-1)*100 }}</span>
                        </div>
                    </div>

                    <!-- THE PHYSICAL DOCUMENT (A4) -->
                    <div id="snc-quantum-canvas" class="relative bg-white shadow-[0_20px_100px_rgba(0,0,0,0.8)] origin-center ring-1 ring-white/10 transition-transform duration-300 ease-out" :style="quantumCanvasStyle">

                        <!-- Layer 0: Background Generation -->
                        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
                            <!-- Background Image (Upload) -->
                            <img v-if="backgroundPreview || templateForm.background_url" :src="backgroundPreview || templateForm.background_url" class="absolute inset-0 w-full h-full object-cover transition-opacity" :style="[quantumFilterStyle(templateForm.bg_filters), { opacity: templateForm.bg_opacity / 100 }]" />
                            <!-- Base Color -->
                            <div class="absolute inset-0 mix-blend-multiply" :style="{ backgroundColor: `#${templateForm.background_color.replace('#','')}`, opacity: (100 - (backgroundPreview ? templateForm.bg_opacity : 0)) / 100 }"></div>
                        </div>

                        <!-- Layer 1: Master Border -->
                        <div v-if="templateForm.border_width > 0" class="absolute pointer-events-none z-10" :style="{ top: '40px', left: '40px', right: '40px', bottom: '40px', border: `${templateForm.border_width}px solid #${templateForm.border_color.replace('#','')}` }"></div>

                        <!-- Layer 2: Draggable Quantum Elements -->
                        <div v-for="el in templateForm.elements" :key="el.id"
                             class="absolute z-20 group quantum-element"
                             :class="{ 'is-selected': selectedElementId === el.id, 'is-dragging': draggingElementId === el.id }"
                             :style="quantumElementStyle(el)"
                             @mousedown.stop="startElementDrag($event, el)">

                            <!-- Bounding Box (Visible on selection) -->
                            <div v-if="selectedElementId === el.id" class="absolute -inset-1.5 border-2 border-red-500 pointer-events-none z-30">
                                <!-- Resize Nodes (Visual aesthetics for UI) -->
                                <div class="absolute -top-1.5 -left-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                                <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                                <div class="absolute -bottom-1.5 -left-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                                <div class="absolute -bottom-1.5 -right-1.5 w-3 h-3 bg-white border-2 border-red-500 rounded-full"></div>
                            </div>

                            <!-- Element Core Wrapper -->
                            <div class="w-full h-full relative overflow-visible flex items-center justify-center">

                                <!-- Render: TEXT -->
                                <div v-if="el.type === 'text'" class="w-full h-full outline-none whitespace-pre-wrap select-text break-words" contenteditable="true" @blur="handleTextEdit($event, el)" :style="quantumTextStyle(el)" spellcheck="false" title="Double-cliquez pour éditer" v-html="el.content"></div>

                                <!-- Render: LOGO -->
                                <div v-if="el.type === 'logo'" class="w-full h-full pointer-events-none flex items-center justify-center">
                                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="quantumFilterStyle(el.filters)" />
                                    <div v-else class="w-full h-full bg-slate-100 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 rounded-xl">
                                        <i class="pi pi-shield text-4xl mb-2"></i><span class="text-xs font-bold uppercase tracking-widest text-center px-2">Espace<br>Logo Officiel</span>
                                    </div>
                                </div>

                                <!-- Render: SIGNATURE -->
                                <div v-if="el.type === 'signature'" class="w-full h-full pointer-events-none flex items-center justify-center">
                                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="quantumFilterStyle(el.filters)" />
                                    <div v-else class="w-full h-full bg-slate-100/50 border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400 rounded-xl">
                                        <i class="pi pi-pencil text-3xl mb-2"></i><span class="text-[10px] font-bold uppercase tracking-widest">Espace Signature</span>
                                    </div>
                                </div>

                                <!-- Render: QR CODE -->
                                <div v-if="el.type === 'qr'" class="w-full h-full pointer-events-none bg-white p-3 border border-slate-200 flex items-center justify-center shadow-sm">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=TrustCenterValidation" class="w-full h-full object-contain opacity-80" />
                                </div>

                                <!-- Render: CUSTOM IMAGE -->
                                <div v-if="el.type === 'image'" class="w-full h-full pointer-events-none flex items-center justify-center">
                                    <img v-if="el.content" :src="el.content" class="w-full h-full object-contain" :style="quantumFilterStyle(el.filters)" />
                                    <div v-else class="w-full h-full bg-indigo-50 border border-indigo-100 flex flex-col items-center justify-center text-indigo-300">
                                        <i class="pi pi-image text-3xl mb-1"></i><span class="text-[8px] font-bold uppercase">Image Libre</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </main>

                <!-- ---------------------------------------------------------------------- -->
                <!-- RIGHT SIDEBAR : THE INSPECTOR (Deep Property Editing)                  -->
                <!-- ---------------------------------------------------------------------- -->
                <aside class="w-[400px] flex-none bg-[#141418] border-l border-white/5 flex flex-col z-[200] shadow-2xl relative">

                    <!-- Inspector Header -->
                    <div class="p-5 border-b border-white/5 bg-[#1a1a1f] flex justify-between items-center sticky top-0 z-20">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center border border-red-500/20">
                                <i class="pi pi-sliders-h text-red-400 text-sm"></i>
                            </div>
                            <span class="text-xs font-black text-white uppercase tracking-widest">
                                {{ selectedElementId ? 'Propriétés Élément' : 'Document Master' }}
                            </span>
                        </div>
                        <button v-if="selectedElementId" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-white/50 hover:text-white transition-colors" @click="selectedElementId = null" v-tooltip.left="'Désélectionner'">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>

                    <!-- Inspector Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar">

                        <!-- ========================================================== -->
                        <!-- 🔴 CONTEXTE 1 : DOCUMENT GLOBAL (Si rien n'est sélectionné)-->
                        <!-- ========================================================== -->
                        <div v-if="!selectedElementId" class="p-6 space-y-10 pb-40">

                            <!-- BLOCK: Format & Géométrie -->
                            <section>
                                <div class="flex items-center gap-3 mb-5">
                                    <i class="pi pi-file text-white/30 text-sm"></i>
                                    <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Géométrie</h4>
                                </div>
                                <div class="space-y-4 bg-[#1a1a1f] p-5 rounded-2xl border border-white/5">
                                    <div class="flex flex-col gap-2">
                                        <label class="text-[10px] text-white/60 font-bold">Orientation du Canevas</label>
                                        <SelectButton v-model="templateForm.orientation" :options="[{l:'Paysage (A4)', v:'landscape'}, {l:'Portrait (A4)', v:'portrait'}]" optionLabel="l" optionValue="v" class="w-full q-select-btn" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 pt-2">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] text-white/40 uppercase">Largeur (px)</span>
                                            <div class="px-3 py-2 bg-black/20 rounded-lg text-white/60 font-mono text-xs border border-white/5">{{ templateForm.orientation === 'landscape' ? 1120 : 792 }}</div>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] text-white/40 uppercase">Hauteur (px)</span>
                                            <div class="px-3 py-2 bg-black/20 rounded-lg text-white/60 font-mono text-xs border border-white/5">{{ templateForm.orientation === 'landscape' ? 792 : 1120 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <Divider class="border-white/5" />

                            <!-- BLOCK: Design Global (Fond & Bordure) -->
                            <section>
                                <div class="flex items-center gap-3 mb-5">
                                    <i class="pi pi-palette text-white/30 text-sm"></i>
                                    <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Apparence Générale</h4>
                                </div>
                                <div class="space-y-4">

                                    <!-- Bordure -->
                                    <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5 space-y-4">
                                        <h5 class="text-[10px] text-white font-bold uppercase tracking-widest border-b border-white/10 pb-2 mb-4">Cadre & Bordure</h5>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-white/70 font-medium">Couleur Master</span>
                                            <div class="flex items-center gap-2 bg-black/20 p-1.5 rounded-lg border border-white/10">
                                                <ColorPicker v-model="templateForm.border_color" />
                                                <span class="text-[10px] font-mono text-white/50 w-16 uppercase">{{ templateForm.border_color }}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 pt-2">
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span class="text-white/70">Épaisseur</span>
                                                <span class="text-red-400 font-mono font-bold bg-red-500/10 px-2 py-0.5 rounded">{{ templateForm.border_width }} px</span>
                                            </div>
                                            <Slider v-model="templateForm.border_width" :min="0" :max="150" class="red-slider" />
                                        </div>
                                    </div>

                                    <!-- Fond (Couleur & Image) -->
                                    <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5 space-y-4">
                                        <h5 class="text-[10px] text-white font-bold uppercase tracking-widest border-b border-white/10 pb-2 mb-4">Arrière-plan</h5>
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="text-xs text-white/70 font-medium">Teinte de Base</span>
                                            <div class="flex items-center gap-2 bg-black/20 p-1.5 rounded-lg border border-white/10">
                                                <ColorPicker v-model="templateForm.background_color" />
                                                <span class="text-[10px] font-mono text-white/50 w-16 uppercase">{{ templateForm.background_color }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <span class="text-[10px] text-white/50 block">Texture / Image HD</span>
                                            <input type="file" ref="bgUploader" hidden @change="(e) => handleFileUpload(e, 'bg')" accept="image/*" />

                                            <div v-if="backgroundPreview || templateForm.background_url" class="relative w-full h-24 rounded-xl overflow-hidden border border-white/10 group">
                                                <img :src="backgroundPreview || templateForm.background_url" class="w-full h-full object-cover" />
                                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                                    <button class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors" @click="$refs.bgUploader.click()" v-tooltip.top="'Remplacer'"><i class="pi pi-sync"></i></button>
                                                    <button class="w-10 h-10 rounded-full bg-red-500/20 hover:bg-red-500 text-white flex items-center justify-center transition-colors" @click="clearFile('bg')" v-tooltip.top="'Supprimer'"><i class="pi pi-trash"></i></button>
                                                </div>
                                            </div>
                                            <button v-else @click="$refs.bgUploader.click()" class="w-full py-4 rounded-xl border border-dashed border-white/20 text-xs text-white/50 hover:text-white hover:border-white/50 hover:bg-white/5 transition-all flex flex-col items-center gap-2">
                                                <i class="pi pi-cloud-upload text-xl"></i> Uploader un Pattern
                                            </button>
                                        </div>

                                        <div class="space-y-2 pt-2" v-if="backgroundPreview || templateForm.background_url">
                                            <div class="flex justify-between items-center text-[10px]">
                                                <span class="text-white/70">Opacité de l'image</span>
                                                <span class="text-white font-mono">{{ templateForm.bg_opacity }}%</span>
                                            </div>
                                            <Slider v-model="templateForm.bg_opacity" :min="0" :max="100" class="red-slider" />
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <Divider class="border-white/5" />

                            <!-- BLOCK: Chartes Graphique Textes -->
                            <section>
                                <div class="flex items-center gap-3 mb-5">
                                    <i class="pi pi-language text-white/30 text-sm"></i>
                                    <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Charte Typographique</h4>
                                </div>
                                <div class="space-y-4 bg-[#1a1a1f] p-5 rounded-2xl border border-white/5">
                                    <div class="space-y-2">
                                        <label class="text-[10px] text-white/60 font-bold">Police Principale (Corps)</label>
                                        <Dropdown v-model="templateForm.primary_font" :options="props.meta.fonts" class="w-full q-dropdown" placeholder="Sélectionner une police">
                                            <template #value="slotProps"><span :style="{fontFamily: slotProps.value}">{{slotProps.value}}</span></template>
                                            <template #option="slotProps"><span :style="{fontFamily: slotProps.option}">{{slotProps.option}}</span></template>
                                        </Dropdown>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] text-white/60 font-bold">Police Secondaire (Titres)</label>
                                        <Dropdown v-model="templateForm.accent_font" :options="props.meta.fonts" class="w-full q-dropdown" placeholder="Sélectionner une police">
                                            <template #value="slotProps"><span :style="{fontFamily: slotProps.value}">{{slotProps.value}}</span></template>
                                            <template #option="slotProps"><span :style="{fontFamily: slotProps.option}">{{slotProps.option}}</span></template>
                                        </Dropdown>
                                    </div>
                                    <div class="flex items-center justify-between pt-3 border-t border-white/5">
                                        <span class="text-xs text-white/70 font-medium">Couleur Texte Défaut</span>
                                        <div class="flex items-center gap-2 bg-black/20 p-1.5 rounded-lg border border-white/10">
                                            <ColorPicker v-model="templateForm.text_color" />
                                            <span class="text-[10px] font-mono text-white/50 w-16 uppercase">{{ templateForm.text_color }}</span>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <Divider class="border-white/5" />

                            <!-- BLOCK: Gestion des Sceaux (Uploads & Toggles) -->
                            <section>
                                <div class="flex items-center gap-3 mb-5">
                                    <i class="pi pi-verified text-white/30 text-sm"></i>
                                    <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Identité & Sceaux</h4>
                                </div>
                                <div class="space-y-4">

                                    <!-- Inputs cachés -->
                                    <input type="file" ref="logoUploader" hidden @change="(e) => handleFileUpload(e, 'logo')" accept="image/*" />
                                    <input type="file" ref="sigUploader" hidden @change="(e) => handleFileUpload(e, 'sig')" accept="image/*" />

                                    <!-- Logo Institution -->
                                    <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5">
                                        <div class="flex justify-between items-center mb-4">
                                            <h5 class="text-[10px] text-white font-bold uppercase tracking-widest">Logo Principal</h5>
                                            <Badge v-if="logoPreview || templateForm.logo_url" value="Inclus" severity="success" class="text-[8px] bg-emerald-500/20 text-emerald-400 border-none" />
                                        </div>
                                        <div v-if="!(logoPreview || templateForm.logo_url) || templateForm.delete_logo" class="flex flex-col items-center gap-3">
                                            <button @click="$refs.logoUploader.click()" class="w-full py-4 rounded-xl border border-dashed border-white/20 text-xs text-white/50 hover:text-white hover:bg-white/5 transition-all"><i class="pi pi-upload mr-2"></i> Sélectionner le fichier</button>
                                        </div>
                                        <div v-else class="flex flex-col gap-3">
                                            <div class="h-20 w-full bg-white/5 rounded-xl border border-white/10 p-2 flex items-center justify-center relative group">
                                                <img :src="logoPreview || templateForm.logo_url" class="h-full object-contain" />
                                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-xl backdrop-blur-sm">
                                                    <button class="w-8 h-8 rounded-full bg-red-500/20 hover:bg-red-500 text-white flex items-center justify-center transition-colors" @click="clearFile('logo')"><i class="pi pi-trash text-xs"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Signature Autorité -->
                                    <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5">
                                        <div class="flex justify-between items-center mb-4">
                                            <h5 class="text-[10px] text-white font-bold uppercase tracking-widest">Signature Master</h5>
                                            <InputSwitch v-model="templateForm.show_signature" class="scale-75" />
                                        </div>
                                        <div v-if="templateForm.show_signature">
                                            <div v-if="!(signaturePreview || templateForm.signature_url) || templateForm.delete_signature" class="flex flex-col items-center gap-3 mt-3">
                                                <button @click="$refs.sigUploader.click()" class="w-full py-3 rounded-xl border border-dashed border-white/20 text-[10px] text-white/50 hover:text-white hover:bg-white/5 transition-all"><i class="pi pi-upload mr-2"></i> Fichier Signature (PNG/Transparent)</button>
                                            </div>
                                            <div v-else class="mt-3 h-16 w-full bg-white/5 rounded-xl border border-white/10 p-2 flex items-center justify-center relative group">
                                                <img :src="signaturePreview || templateForm.signature_url" class="h-full object-contain filter invert opacity-80" />
                                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity rounded-xl backdrop-blur-sm">
                                                    <button class="w-8 h-8 rounded-full bg-red-500/20 hover:bg-red-500 text-white flex items-center justify-center transition-colors" @click="clearFile('sig')"><i class="pi pi-trash text-xs"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Code System -->
                                    <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5 flex items-center justify-between">
                                        <div>
                                            <h5 class="text-[10px] text-white font-bold uppercase tracking-widest mb-1">Blockchain QR Seal</h5>
                                            <p class="text-[9px] text-white/40 leading-tight">Génère un lien de vérification unique sur le diplôme.</p>
                                        </div>
                                        <InputSwitch v-model="templateForm.show_qr" />
                                    </div>

                                </div>
                            </section>

                        </div>

                        <!-- ========================================================== -->
                        <!-- 🔵 CONTEXTE 2 : ÉLÉMENT SÉLECTIONNÉ                        -->
                        <!-- ========================================================== -->
                        <div v-else class="p-6 space-y-8 pb-40">

                            <!-- Header Contextuel -->
                            <div class="bg-gradient-to-br from-red-600/20 to-transparent border border-red-500/30 rounded-2xl p-4 flex gap-4 shadow-inner relative overflow-hidden">
                                <div class="w-12 h-12 rounded-xl bg-red-500/20 text-red-400 flex items-center justify-center flex-none border border-red-500/30">
                                    <i :class="{
                                        'pi pi-font text-xl': selectedElement.type === 'text',
                                        'pi pi-image text-xl': selectedElement.type === 'image',
                                        'pi pi-shield text-xl': selectedElement.type === 'logo',
                                        'pi pi-pencil text-xl': selectedElement.type === 'signature',
                                        'pi pi-qrcode text-xl': selectedElement.type === 'qr'
                                    }"></i>
                                </div>
                                <div class="flex flex-col w-full">
                                    <span class="text-[8px] text-red-300 font-black uppercase tracking-widest mb-0.5 opacity-80">Calque / {{ selectedElement.type }}</span>
                                    <InputText v-model="selectedElement.name" class="bg-transparent border-none text-white font-bold text-sm p-0 focus:ring-0 w-full placeholder:text-white/20" placeholder="Nom du calque..." />
                                </div>
                                <i class="pi pi-cog absolute -right-4 -bottom-4 text-[60px] text-red-500/5"></i>
                            </div>

                            <!-- BLOCK: Transformation & Géométrie -->
                            <section class="space-y-4">
                                <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em] mb-4">Transformations</h4>
                                <div class="grid grid-cols-2 gap-3 bg-[#1a1a1f] p-4 rounded-2xl border border-white/5">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Axe X</label>
                                        <InputNumber v-model="selectedElement.x" class="q-input-number" suffix=" px" :useGrouping="false" />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Axe Y</label>
                                        <InputNumber v-model="selectedElement.y" class="q-input-number" suffix=" px" :useGrouping="false" />
                                    </div>
                                    <div class="flex flex-col gap-1.5 mt-2">
                                        <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Largeur</label>
                                        <InputNumber v-model="selectedElement.width" class="q-input-number" suffix=" px" :useGrouping="false" />
                                    </div>
                                    <div class="flex flex-col gap-1.5 mt-2">
                                        <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Hauteur</label>
                                        <InputNumber v-if="selectedElement.type !== 'text'" v-model="selectedElement.height" class="q-input-number" suffix=" px" :useGrouping="false" />
                                        <div v-else class="h-[38px] bg-black/10 rounded-lg flex items-center px-3 border border-white/5 text-white/30 text-[10px] cursor-not-allowed">Auto (Texte)</div>
                                    </div>
                                </div>
                            </section>

                            <Divider class="border-white/5" />

                            <!-- BLOCK: Spécifique TEXTE -->
                            <section v-if="selectedElement.type === 'text'" class="space-y-6">
                                <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Stylisation Typographique</h4>

                                <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5 space-y-5">

                                    <!-- Famille de police -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] text-white/60 font-bold">Police de caractères</label>
                                        <Dropdown v-model="selectedElement.style.fontFamily" :options="props.meta.fonts" class="w-full q-dropdown">
                                            <template #value="slotProps"><span :style="{fontFamily: slotProps.value}">{{slotProps.value}}</span></template>
                                            <template #option="slotProps"><span :style="{fontFamily: slotProps.option}">{{slotProps.option}}</span></template>
                                        </Dropdown>
                                    </div>

                                    <!-- Tailles -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Taille (Size)</label>
                                            <InputNumber v-model="selectedElement.style.fontSize" class="q-input-number" suffix=" px" />
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-[9px] text-white/40 uppercase tracking-wider pl-1">Espacement</label>
                                            <InputNumber v-model="selectedElement.style.letterSpacing" class="q-input-number" suffix=" px" />
                                        </div>
                                    </div>

                                    <!-- Couleur -->
                                    <div class="flex items-center justify-between bg-black/20 p-3 rounded-xl border border-white/5">
                                        <span class="text-xs text-white/70 font-medium">Couleur Remplissage</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-mono text-white/50 uppercase">{{ selectedElement.style.color }}</span>
                                            <ColorPicker v-model="selectedElement.style.color" />
                                        </div>
                                    </div>

                                    <!-- Alignement & Formatage Avancé -->
                                    <div class="space-y-3 pt-2">
                                        <label class="text-[10px] text-white/60 font-bold">Formatage</label>

                                        <!-- Alignement -->
                                        <div class="flex bg-black/20 rounded-xl overflow-hidden border border-white/10 p-1 gap-1">
                                            <button @click="selectedElement.style.textAlign = 'left'" class="flex-1 py-2 rounded-lg hover:bg-white/10 text-white/60 transition-colors" :class="{'bg-red-600 text-white shadow-md': selectedElement.style.textAlign === 'left'}"><i class="pi pi-align-left text-sm"></i></button>
                                            <button @click="selectedElement.style.textAlign = 'center'" class="flex-1 py-2 rounded-lg hover:bg-white/10 text-white/60 transition-colors" :class="{'bg-red-600 text-white shadow-md': selectedElement.style.textAlign === 'center'}"><i class="pi pi-align-center text-sm"></i></button>
                                            <button @click="selectedElement.style.textAlign = 'right'" class="flex-1 py-2 rounded-lg hover:bg-white/10 text-white/60 transition-colors" :class="{'bg-red-600 text-white shadow-md': selectedElement.style.textAlign === 'right'}"><i class="pi pi-align-right text-sm"></i></button>
                                        </div>

                                        <!-- Poids et Style -->
                                        <div class="flex gap-2">
                                            <button @click="selectedElement.style.fontWeight = selectedElement.style.fontWeight === '900' ? '400' : '900'" class="flex-1 py-2 rounded-xl border transition-all text-sm font-serif" :class="selectedElement.style.fontWeight === '900' ? 'bg-white/10 border-white/20 text-white font-black' : 'bg-transparent border-white/5 text-white/40 hover:bg-white/5'">B</button>
                                            <button @click="selectedElement.style.fontStyle = selectedElement.style.fontStyle === 'italic' ? 'normal' : 'italic'" class="flex-1 py-2 rounded-xl border transition-all text-sm font-serif italic" :class="selectedElement.style.fontStyle === 'italic' ? 'bg-white/10 border-white/20 text-white' : 'bg-transparent border-white/5 text-white/40 hover:bg-white/5'">I</button>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- BLOCK: Spécifique IMAGE / LOGO / SIG -->
                            <section v-if="['image', 'logo', 'signature'].includes(selectedElement.type)" class="space-y-6">
                                <h4 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Traitements d'Image</h4>

                                <div class="bg-[#1a1a1f] p-5 rounded-2xl border border-white/5 space-y-5">
                                    <div v-if="selectedElement.type === 'image'" class="space-y-2">
                                        <input type="file" ref="customImgUploader" hidden @change="(e) => {
                                            const file = e.target.files[0];
                                            if(!file) return;
                                            const reader = new FileReader();
                                            reader.onload = (ev) => selectedElement.content = ev.target.result;
                                            reader.readAsDataURL(file);
                                        }" accept="image/*" />
                                        <button @click="$refs.customImgUploader.click()" class="w-full py-3 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-xs text-white transition-all"><i class="pi pi-sync mr-2"></i> Remplacer l'image</button>
                                    </div>

                                    <!-- Filtres CSS -->
                                    <div class="space-y-4 pt-2">
                                        <label class="text-[10px] text-white/60 font-bold border-b border-white/10 pb-2 block">Matrice de filtres</label>

                                        <div class="space-y-2">
                                            <div class="flex justify-between text-[9px] text-white/50"><span class="uppercase">Luminosité</span><span class="font-mono text-red-400">{{ selectedElement.filters.brightness }}%</span></div>
                                            <Slider v-model="selectedElement.filters.brightness" :min="0" :max="200" class="red-slider h-1" />
                                        </div>
                                        <div class="space-y-2 pt-2">
                                            <div class="flex justify-between text-[9px] text-white/50"><span class="uppercase">Contraste</span><span class="font-mono text-red-400">{{ selectedElement.filters.contrast }}%</span></div>
                                            <Slider v-model="selectedElement.filters.contrast" :min="0" :max="200" class="red-slider h-1" />
                                        </div>
                                        <div class="space-y-2 pt-2">
                                            <div class="flex justify-between text-[9px] text-white/50"><span class="uppercase">Niveaux de gris</span><span class="font-mono text-red-400">{{ selectedElement.filters.grayscale }}%</span></div>
                                            <Slider v-model="selectedElement.filters.grayscale" :min="0" :max="100" class="red-slider h-1" />
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Actions de Calque (Communes) -->
                            <section v-if="!['logo', 'signature', 'qr'].includes(selectedElement.type)">
                                <Divider class="border-white/5" />
                                <div class="grid grid-cols-2 gap-3">
                                    <Button label="Dupliquer" icon="pi pi-copy" class="p-button-outlined p-button-secondary text-white/70 border-white/20 hover:bg-white/10 rounded-xl" @click="duplicateElement(selectedElement)" />
                                    <Button label="Supprimer" icon="pi pi-trash" class="bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500 hover:text-white rounded-xl" @click="removeElement(selectedElement.id)" />
                                </div>
                            </section>
                            <section v-else>
                                <Message severity="info" :closable="false" class="bg-blue-500/10 border-blue-500/20 text-blue-300 text-xs">Cet élément est natif au modèle. Vous pouvez le désactiver depuis les paramètres globaux du document.</Message>
                            </section>

                        </div>
                    </div>
                </aside>
            </div>
        </Dialog>

        <!-- ============================================================================== -->
        <!-- DIALOGUES SECONDAIRES (Émission Massive & Révocation)                          -->
        <!-- ============================================================================== -->
        <Dialog v-model:visible="bulkIssuanceDialog" modal header="Moteur d'Émission Massive" :style="{width: '650px'}" class="rounded-3xl shadow-2xl" contentClass="p-0 bg-slate-50 border-t border-slate-100" headerClass="bg-white border-b border-slate-100 p-6">
            <div class="p-8 space-y-8">

                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex gap-4 items-start">
                    <i class="pi pi-info-circle text-blue-500 text-xl mt-0.5"></i>
                    <div>
                        <h4 class="text-sm font-bold text-blue-900 mb-1">Processus Automatisé</h4>
                        <p class="text-xs text-blue-700/80 leading-relaxed">Sélectionnez un programme. Le système générera les PDF HD, insérera les variables, hachera les documents sur la blockchain et notifiera les étudiants éligibles en un seul clic.</p>
                    </div>
                </div>

                <div class="space-y-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Programme de Formation <span class="text-red-500">*</span></label>
                        <Dropdown v-model="bulkIssueForm.course_id" :options="props.courses" optionLabel="title" optionValue="id" placeholder="Rechercher une formation finalisée..." class="w-full bg-slate-50 border-slate-200 rounded-xl" filter>
                            <template #option="slotProps">
                                <div class="flex items-center gap-3 py-1">
                                    <div class="w-8 h-8 rounded bg-indigo-100 text-indigo-600 flex items-center justify-center"><i class="pi pi-book text-xs"></i></div>
                                    <span class="font-bold text-slate-700">{{ slotProps.option.title }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Date d'expiration (Optionnel)</label>
                        <Calendar v-model="bulkIssueForm.expires_at" dateFormat="dd/mm/yy" showIcon class="w-full" inputClass="rounded-xl bg-slate-50 border-slate-200" placeholder="Laissez vide pour aucune expiration" />
                    </div>

                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-800 uppercase tracking-widest">Matrice Visuelle (Modèle) <span class="text-red-500">*</span></label>
                        <Dropdown v-model="bulkIssueForm.template_id" :options="templates" optionLabel="name" optionValue="id" placeholder="Sélectionner le design du diplôme..." class="w-full bg-slate-50 border-slate-200 rounded-xl">
                            <template #option="slotProps">
                                <div class="flex items-center gap-3 py-1">
                                    <div class="w-8 h-8 rounded border border-slate-200 flex items-center justify-center" :style="{ backgroundColor: `#${slotProps.option.background_color?.replace('#','')}` }">
                                        <div class="w-4 h-3 border" :style="{ borderColor: `#${slotProps.option.border_color?.replace('#','')}` }"></div>
                                    </div>
                                    <span class="font-bold text-slate-700">{{ slotProps.option.name }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <div class="flex items-center justify-between p-5 bg-white rounded-2xl border border-slate-200 shadow-sm hover:border-slate-300 transition-colors cursor-pointer" @click="bulkIssueForm.notify_users = !bulkIssueForm.notify_users">
                    <div class="flex gap-4 items-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center"><i class="pi pi-envelope text-lg"></i></div>
                        <div>
                            <div class="text-sm font-black text-slate-800">Notification Push & Email</div>
                            <div class="text-xs text-slate-500 mt-0.5">Envoyer instantanément le diplôme aux étudiants.</div>
                        </div>
                    </div>
                    <InputSwitch v-model="bulkIssueForm.notify_users" @click.stop />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center p-6 bg-white border-t border-slate-100 rounded-b-3xl">
                    <Button label="Annuler" class="p-button-text text-slate-500 font-bold hover:bg-slate-50 px-6 rounded-xl" @click="bulkIssuanceDialog = false" />
                    <Button label="Lancer l'Ingénierie" icon="pi pi-bolt" class="bg-red-600 hover:bg-red-700 border-none text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-red-500/20" @click="submitBulkIssuance" :loading="isSaving" />
                </div>
            </template>
        </Dialog>

        <Dialog v-model:visible="expirationDialog" modal header="Définir une Date d'Expiration" :style="{width: '450px'}" class="rounded-3xl shadow-2xl" contentClass="p-0 bg-slate-50 border-t border-slate-100" headerClass="bg-white border-b border-slate-100 p-6">
            <div class="p-8 space-y-6">
                <div class="flex items-start gap-4 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
                    <i class="pi pi-exclamation-triangle text-amber-500 text-xl mt-1"></i>
                    <div>
                        <h4 class="font-bold text-amber-900 text-sm mb-1">Impact de l'action</h4>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            Une fois la date d'expiration passée, le certificat sera marqué comme "Expiré" sur le vérificateur public. Cette action est réversible en supprimant la date.
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="expiration-date" class="text-xs font-black text-slate-800 uppercase tracking-widest">Date d'expiration</label>
                    <Calendar id="expiration-date" v-model="expirationForm.expires_at" dateFormat="dd/mm/yy" showIcon class="w-full" inputClass="rounded-xl bg-white border-slate-200" />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center p-6 bg-white border-t border-slate-100 rounded-b-3xl">
                    <Button label="Annuler" class="p-button-text text-slate-500 font-bold hover:bg-slate-50 px-6 rounded-xl" @click="expirationDialog = false" />
                    <Button label="Confirmer l'Expiration" icon="pi pi-check" class="bg-amber-500 hover:bg-amber-600 border-none text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-amber-500/20" @click="submitExpiration" :loading="expirationForm.processing" />
                </div>
            </template>
        </Dialog>

        <Dialog v-model:visible="revocationDialog" modal header="Protocole de Révocation" :style="{width: '550px'}" class="rounded-3xl shadow-2xl" contentClass="p-0 bg-slate-50 border-t border-slate-100" headerClass="bg-white border-b border-slate-100 p-6">
            <div class="p-8 text-center flex flex-col items-center">
                <div class="w-24 h-24 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mb-6 shadow-inner border border-rose-100 relative">
                    <div class="absolute inset-0 rounded-full border-2 border-rose-500 border-dashed animate-[spin_10s_linear_infinite] opacity-20"></div>
                    <i class="pi pi-shield-slash text-4xl relative z-10"></i>
                </div>

                <h3 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Révocation Irréversible</h3>
                <p class="text-slate-500 text-sm mb-8 px-4 leading-relaxed">
                    Cette action détruira la validité de ce certificat sur le vérificateur public de la blockchain. Le fichier PDF restera en possession de l'étudiant mais sera marqué comme <strong>frauduleux ou annulé</strong> lors d'un scan.
                </p>

                <div class="w-full text-left space-y-3 mb-8 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="pi pi-align-left text-rose-400"></i> Motif Officiel de Révocation <span class="text-rose-500">*</span>
                    </label>
                    <Textarea v-model="revocationForm.reason" rows="3" placeholder="Ex: Fraude lors de l'examen final, erreur administrative..." class="w-full rounded-xl p-3 border-slate-200 bg-slate-50 focus:bg-white transition-colors text-sm" />
                </div>

                <div class="w-full flex items-center justify-between p-5 rounded-2xl border transition-colors cursor-pointer" :class="revocationForm.confirm_action ? 'bg-rose-50 border-rose-200' : 'bg-white border-slate-200'" @click="revocationForm.confirm_action = !revocationForm.confirm_action">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" :class="revocationForm.confirm_action ? 'bg-rose-500 animate-pulse' : 'bg-slate-300'"></div>
                        <span class="text-sm font-bold transition-colors" :class="revocationForm.confirm_action ? 'text-rose-700' : 'text-slate-600'">Je confirme l'invalidation définitive</span>
                    </div>
                    <InputSwitch v-model="revocationForm.confirm_action" @click.stop />
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center p-6 bg-white border-t border-slate-100 rounded-b-3xl">
                    <Button label="Annuler" class="p-button-text text-slate-500 font-bold hover:bg-slate-50 px-6 rounded-xl" @click="revocationDialog = false" />
                    <Button label="Confirmer la Révocation" icon="pi pi-exclamation-triangle" :disabled="!revocationForm.confirm_action" class="bg-rose-600 hover:bg-rose-700 border-none text-white font-bold px-8 py-3 rounded-xl shadow-lg shadow-rose-500/20 transition-all disabled:opacity-50 disabled:hover:bg-rose-600" @click="submitRevocation" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* ========================================================================= */
/* CSS THEME ROUGE - SUBNETCONGO QUANTUM ENGINE (SAAS ENTERPRISE LEVEL)      */
/* ========================================================================= */

.SNC-Quantum-Engine {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Grille infinie avec fondu sur les bords */
.quantum-grid {
    background-color: #0c0c0e;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 20px 20px;
    background-position: center center;
}

/* Animations des éléments sélectionnées (Respirations subtiles) */
.quantum-element {
    transition: box-shadow 0.2s ease, outline 0.1s ease;
}
.quantum-element.is-dragging {
    opacity: 0.8;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    z-index: 999 !important;
}

/* Scrollbars personnalisées (Dark Mode Webkit) */
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.2); }

/* Customisation profonde PrimeVue pour s'intégrer au Dark Mode sans ruiner le Light Mode de l'app */
:deep(.quantum-tabs .p-tabview-nav) { background: transparent; border-bottom: 1px solid rgba(255,255,255,0.05); }
:deep(.quantum-tabs .p-tabview-nav li .p-tabview-nav-link) { background: transparent; color: rgba(255,255,255,0.4); border: none; border-bottom: 2px solid transparent; padding: 1rem 0; margin: 0 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.7rem; transition: all 0.3s ease; }
:deep(.quantum-tabs .p-tabview-nav li:hover .p-tabview-nav-link) { color: rgba(255,255,255,0.8); background: transparent; }
:deep(.quantum-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) { color: #f87171; border-bottom-color: #ef4444; }
:deep(.quantum-tabs .p-tabview-panels) { background: transparent; padding: 0; }

/* Sliders Rouges (Volume/Taille) */
:deep(.red-slider .p-slider-range) { background-color: #ef4444; }
:deep(.red-slider .p-slider-handle) { border: 2px solid #ef4444; background: #fff; box-shadow: 0 0 10px rgba(239,68,68,0.5); transition: transform 0.1s; }
:deep(.red-slider .p-slider-handle:hover) { transform: scale(1.2); }

/* Inputs Text/Number Sombres */
:deep(.q-input-number .p-inputtext) {
    background-color: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    font-family: 'JetBrains Mono', 'Courier New', Courier, monospace;
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    width: 100%;
    transition: all 0.2s ease;
}
:deep(.q-input-number .p-inputtext:focus) { border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.2); }

/* Dropdowns Sombres */
:deep(.q-dropdown) { background-color: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.5rem; }
:deep(.q-dropdown:not(.p-disabled).p-focus) { border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.2); }
:deep(.q-dropdown .p-dropdown-label) { color: white; font-size: 0.8rem; padding: 0.6rem 0.75rem; }
:deep(.q-dropdown .p-dropdown-trigger) { color: rgba(255,255,255,0.4); width: 2.5rem; }

/* SelectButtons (Boutons groupés Paysage/Portrait) */
:deep(.q-select-btn .p-button) {
    background-color: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.6rem 1rem;
    transition: all 0.2s ease;
}
:deep(.q-select-btn .p-button:not(.p-disabled):hover) { background-color: rgba(255, 255, 255, 0.05); color: white; }
:deep(.q-select-btn .p-button.p-highlight) { background-color: #dc2626; color: white; border-color: #dc2626; z-index: 1; }

/* ColorPicker Overlay Tuning */
:deep(.p-colorpicker-preview) { border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.2); width: 1.5rem; height: 1.5rem; }

/* Utilitaires spécifiques */
.cursor-copy { cursor: copy; }
</style>
