<script setup>
/**
 * THEME CUSTOMIZER - SUBNETCONGO LMS
 * Version: 1.5.0
 * Description: Interface d'administration pour la personnalisation visuelle globale.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColorPicker from 'primevue/colorpicker';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import SelectButton from 'primevue/selectbutton';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Slider from 'primevue/slider';
import InputSwitch from 'primevue/inputswitch';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import Sidebar from 'primevue/sidebar';
import Message from 'primevue/message';
import InlineMessage from 'primevue/inlinemessage';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import ScrollPanel from 'primevue/scrollpanel';
import Tooltip from 'primevue/tooltip';

const toast = useToast();
const isSaving = ref(false);
const showResetDialog = ref(false);

// --- CONFIGURATION PAR DÉFAUT ---
const defaultTheme = {
    branding: {
        siteName: 'SubnetCongo',
        logoLight: null,
        logoDark: null,
        favicon: null,
        loaderType: 'pulse'
    },
    colors: {
        primary: '6366F1', // Indigo 600
        secondary: '64748B', // Slate 500
        accent: 'F59E0B', // Amber 500
        danger: 'EF4444', // Red 500
        success: '10B981', // Emerald 500
        background: 'F8FAFC',
        surface: 'FFFFFF',
        textMain: '1E293B'
    },
    typography: {
        fontFamily: 'Inter',
        baseSize: 16,
        headingWeight: '800',
        lineHeight: 1.5
    },
    ui: {
        borderRadius: 16,
        buttonPadding: '0.75rem 1.5rem',
        cardShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        borderWidth: 1,
        enableAnimations: true
    },
    customCss: '/* Ajoutez votre CSS personnalisé ici */\n.custom-btn {\n  transition: all 0.3s;\n}'
};

// --- STATE MANAGEMENT ---
const theme = reactive(JSON.parse(JSON.stringify(defaultTheme)));

// Options de Typographie
const fontOptions = [
    { label: 'Inter (Défaut)', value: 'Inter' },
    { label: 'Roboto', value: 'Roboto' },
    { label: 'Montserrat', value: 'Montserrat' },
    { label: 'Poppins', value: 'Poppins' },
    { label: 'Open Sans', value: 'Open Sans' }
];

const weightOptions = [
    { label: 'Normal', value: '400' },
    { label: 'Semi-Bold', value: '600' },
    { label: 'Bold', value: '700' },
    { label: 'Extra-Bold', value: '800' },
    { label: 'Black', value: '900' }
];

// --- PRÉVISUALISATION DYNAMIQUE ---
const previewStyle = computed(() => {
    return {
        '--primary-color': `#${theme.colors.primary}`,
        '--bg-color': `#${theme.colors.background}`,
        '--surface-color': `#${theme.colors.surface}`,
        '--text-color': `#${theme.colors.textMain}`,
        '--border-radius': `${theme.ui.borderRadius}px`,
        'font-family': `'${theme.typography.fontFamily}', sans-serif`
    };
});

// --- MÉTHODES ---
const onUpload = (event, type) => {
    toast.add({ severity: 'success', summary: 'Image chargée', detail: `${type} mis à jour avec succès.`, life: 3000 });
};

const saveTheme = () => {
    isSaving.value = true;
    // Simulation d'appel API
    setTimeout(() => {
        isSaving.value = false;
        toast.add({ severity: 'success', summary: 'Thème Appliqué', detail: 'Les modifications sont maintenant visibles pour tous les utilisateurs.', life: 3000 });
        localStorage.setItem('subnet_theme_config', JSON.stringify(theme));
    }, 1500);
};

const resetToDefault = () => {
    Object.assign(theme, JSON.parse(JSON.stringify(defaultTheme)));
    showResetDialog.value = false;
    toast.add({ severity: 'info', summary: 'Réinitialisé', detail: 'Le thème par défaut a été restauré.', life: 3000 });
};

const downloadConfig = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(theme));
    const downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "subnet_theme_config.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
};

onMounted(() => {
    const saved = localStorage.getItem('subnet_theme_config');
    if (saved) Object.assign(theme, JSON.parse(saved));
});
</script>

<template>
    <AppLayout>
        <Head title="Apparence & Design System" />
        <Toast />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen font-sans" :style="previewStyle">

            <!-- --- HEADER SECTION --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-3 text-indigo-600 font-bold uppercase tracking-[0.2em] text-xs">
                        <i class="pi pi-palette"></i>
                        <span>Configuration Visuelle</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter">Design System Manager</h1>
                    <p class="text-slate-500 font-medium max-w-2xl">
                        Personnalisez l'expérience utilisateur de <span class="text-indigo-600 font-bold">SubnetCongo</span>.
                        Modifiez les couleurs, logos et composants en temps réel.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 bg-white p-2 rounded-[2rem] shadow-sm border border-slate-100">
                    <Button label="Réinitialiser" icon="pi pi-refresh" class="p-button-text p-button-secondary rounded-xl" @click="showResetDialog = true" />
                    <Button label="Exporter JSON" icon="pi pi-download" class="p-button-outlined p-button-secondary rounded-xl" @click="downloadConfig" />
                    <Button label="Publier les changements" icon="pi pi-check" class="p-button-primary shadow-xl shadow-indigo-200 px-8 rounded-xl font-bold" @click="saveTheme" :loading="isSaving" />
                </div>
            </header>

            <div class="grid grid-cols-12 gap-10">

                <!-- --- COLONNE GAUCHE : RÉGLAGES --- -->
                <div class="col-span-12 lg:col-span-7 space-y-8">
                    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                        <TabView class="custom-theme-tabs">

                            <!-- ONGLET 1 : BRANDING -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-image"></i>
                                        <span>Branding</span>
                                    </div>
                                </template>

                                <div class="p-8 space-y-10">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-4">
                                            <label class="text-sm font-black text-slate-700 uppercase tracking-widest block">Logo (Mode Clair)</label>
                                            <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:bg-slate-50 transition-all group">
                                                <i class="pi pi-sun text-3xl text-slate-300 mb-4 group-hover:text-amber-500 transition-colors"></i>
                                                <FileUpload mode="basic" name="logo_light" url="/api/upload" accept="image/*" @upload="onUpload($event, 'Logo clair')" class="p-button-sm p-button-outlined w-full" />
                                                <p class="text-[10px] text-slate-400 mt-2">PNG, SVG recommandé. Max 2MB.</p>
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <label class="text-sm font-black text-slate-700 uppercase tracking-widest block">Logo (Mode Sombre)</label>
                                            <div class="border-2 border-dashed border-slate-800 bg-slate-900 rounded-3xl p-6 text-center group">
                                                <i class="pi pi-moon text-3xl text-slate-600 mb-4 group-hover:text-indigo-400 transition-colors"></i>
                                                <FileUpload mode="basic" name="logo_dark" url="/api/upload" accept="image/*" @upload="onUpload($event, 'Logo sombre')" class="p-button-sm p-button-secondary w-full" />
                                                <p class="text-[10px] text-slate-500 mt-2">Utilisé sur les fonds foncés.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <Divider />

                                    <div class="flex flex-col md:flex-row gap-8 items-start">
                                        <div class="flex-1 space-y-4">
                                            <label class="text-sm font-black text-slate-700 uppercase tracking-widest block">Favicon</label>
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-white border border-slate-200 rounded-2xl flex items-center justify-center shadow-inner">
                                                    <i class="pi pi-globe text-slate-200 text-2xl"></i>
                                                </div>
                                                <FileUpload mode="basic" chooseLabel="Changer Favicon" class="p-button-text p-button-sm" />
                                            </div>
                                        </div>
                                        <div class="flex-1 space-y-4">
                                            <label class="text-sm font-black text-slate-700 uppercase tracking-widest block">Nom de la Plateforme</label>
                                            <InputText v-model="theme.branding.siteName" class="w-full p-3 rounded-xl bg-slate-50 border-none ring-1 ring-slate-200" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- ONGLET 2 : COULEURS -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-palette"></i>
                                        <span>Palette de Couleurs</span>
                                    </div>
                                </template>

                                <div class="p-8 space-y-8">
                                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                                        <span class="w-8 h-1 bg-indigo-600 rounded-full"></span>
                                        Identité Primaire
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div class="bg-slate-50 p-4 rounded-2xl flex items-center justify-between border border-slate-100">
                                            <div>
                                                <p class="text-xs font-bold text-slate-500 mb-1">Primaire (Action)</p>
                                                <code class="text-xs">#{{ theme.colors.primary }}</code>
                                            </div>
                                            <ColorPicker v-model="theme.colors.primary" />
                                        </div>
                                        <div class="bg-slate-50 p-4 rounded-2xl flex items-center justify-between border border-slate-100">
                                            <div>
                                                <p class="text-xs font-bold text-slate-500 mb-1">Secondaire</p>
                                                <code class="text-xs">#{{ theme.colors.secondary }}</code>
                                            </div>
                                            <ColorPicker v-model="theme.colors.secondary" />
                                        </div>
                                        <div class="bg-slate-50 p-4 rounded-2xl flex items-center justify-between border border-slate-100">
                                            <div>
                                                <p class="text-xs font-bold text-slate-500 mb-1">Accent</p>
                                                <code class="text-xs">#{{ theme.colors.accent }}</code>
                                            </div>
                                            <ColorPicker v-model="theme.colors.accent" />
                                        </div>
                                    </div>

                                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2 pt-4">
                                        <span class="w-8 h-1 bg-slate-300 rounded-full"></span>
                                        Surfaces & États
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl">
                                                <span class="text-sm font-medium">Fond de page</span>
                                                <ColorPicker v-model="theme.colors.background" />
                                            </div>
                                            <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl">
                                                <span class="text-sm font-medium">Surface (Cards)</span>
                                                <ColorPicker v-model="theme.colors.surface" />
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl">
                                                <span class="text-sm font-medium text-emerald-600">Succès</span>
                                                <ColorPicker v-model="theme.colors.success" />
                                            </div>
                                            <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl">
                                                <span class="text-sm font-medium text-rose-600">Danger / Erreur</span>
                                                <ColorPicker v-model="theme.colors.danger" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- ONGLET 3 : TYPO & UI -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-pencil"></i>
                                        <span>Typo & Interface</span>
                                    </div>
                                </template>

                                <div class="p-8 space-y-10">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                        <div class="space-y-6">
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Typographie</h4>
                                            <div class="field">
                                                <label class="text-sm font-bold text-slate-700 block mb-2">Famille de police (Google Fonts)</label>
                                                <Dropdown v-model="theme.typography.fontFamily" :options="fontOptions" optionLabel="label" optionValue="value" class="w-full" />
                                            </div>
                                            <div class="field">
                                                <label class="text-sm font-bold text-slate-700 block mb-2">Graisse des titres</label>
                                                <SelectButton v-model="theme.typography.headingWeight" :options="weightOptions" optionLabel="label" optionValue="value" />
                                            </div>
                                            <div class="field">
                                                <label class="text-sm font-bold text-slate-700 block mb-2">Taille de base ({{ theme.typography.baseSize }}px)</label>
                                                <Slider v-model="theme.typography.baseSize" :min="12" :max="20" class="mt-4" />
                                            </div>
                                        </div>

                                        <div class="space-y-6">
                                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Éléments UI</h4>
                                            <div class="field">
                                                <label class="text-sm font-bold text-slate-700 block mb-2">Rayon des bordures ({{ theme.ui.borderRadius }}px)</label>
                                                <div class="flex items-center gap-4">
                                                    <Slider v-model="theme.ui.borderRadius" :min="0" :max="40" class="flex-1" />
                                                    <InputNumber v-model="theme.ui.borderRadius" class="w-20" />
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label class="text-sm font-bold text-slate-700 block mb-2">Animations d'interface</label>
                                                <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                                                    <span class="text-sm font-bold text-indigo-700">Activer les transitions</span>
                                                    <InputSwitch v-model="theme.ui.enableAnimations" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- ONGLET 4 : CUSTOM CSS -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-code"></i>
                                        <span>Custom CSS</span>
                                    </div>
                                </template>
                                <div class="p-8">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center">
                                            <i class="pi pi-info text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800">Personnalisation Avancée</h4>
                                            <p class="text-xs text-slate-400">Le CSS ajouté ici sera injecté dans toutes les pages de la plateforme.</p>
                                        </div>
                                    </div>
                                    <Textarea v-model="theme.customCss" rows="15" class="w-full font-mono text-xs p-6 bg-slate-900 text-emerald-400 border-none rounded-3xl shadow-inner outline-none focus:ring-4 ring-indigo-500/20" />
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>

                    <!-- TIPS SECTION -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-6 bg-amber-50 border border-amber-100 rounded-[2.5rem] flex gap-4">
                            <i class="pi pi-lightbulb text-amber-500 text-2xl"></i>
                            <div>
                                <h4 class="font-bold text-amber-900">Conseil Accessibilité</h4>
                                <p class="text-xs text-amber-700 leading-relaxed mt-1">
                                    Assurez-vous que le contraste entre la couleur primaire (#{{ theme.colors.primary }}) et le texte soit suffisant pour les malvoyants.
                                </p>
                            </div>
                        </div>
                        <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-[2.5rem] flex gap-4">
                            <i class="pi pi-cloud text-indigo-500 text-2xl"></i>
                            <div>
                                <h4 class="font-bold text-indigo-900">Mise en Cache</h4>
                                <p class="text-xs text-indigo-700 leading-relaxed mt-1">
                                    Les modifications de thème sont propagées via notre CDN. Le délai de rafraîchissement peut prendre jusqu'à 30 secondes.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- --- COLONNE DROITE : PRÉVISUALISATION --- -->
                <div class="col-span-12 lg:col-span-5">
                    <div class="sticky top-10 space-y-6">
                        <div class="bg-slate-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden h-[800px] flex flex-col">
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-600/20 rounded-full blur-[100px]"></div>

                            <div class="relative z-10 flex flex-col h-full">
                                <div class="flex justify-between items-center mb-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-500 rounded-xl flex items-center justify-center shadow-lg">
                                            <i class="pi pi-graduation-cap text-white text-xl"></i>
                                        </div>
                                        <span class="font-black text-xl tracking-tighter">{{ theme.branding.siteName }}</span>
                                    </div>
                                    <Badge value="Live Preview" severity="info" class="rounded-lg px-3" />
                                </div>

                                <ScrollPanel class="flex-1 pr-4">
                                    <div class="space-y-8">
                                        <!-- Header Preview -->
                                        <section class="space-y-4">
                                            <h2 class="text-3xl font-black italic tracking-tight" :style="{ fontWeight: theme.typography.headingWeight }">
                                                L'avenir de l'éducation en RDC
                                            </h2>
                                            <p class="text-slate-400 text-sm leading-relaxed">
                                                Voici comment vos textes apparaîtront sur les pages de cours.
                                                La police <span class="text-white font-bold">{{ theme.typography.fontFamily }}</span> est actuellement sélectionnée.
                                            </p>
                                        </section>

                                        <!-- Button Preview -->
                                        <section class="bg-white/5 p-6 rounded-[2rem] border border-white/10 space-y-4">
                                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Composants de boutons</h4>
                                            <div class="flex flex-wrap gap-3">
                                                <button class="px-6 py-2 bg-indigo-600 rounded-full text-xs font-bold shadow-lg shadow-indigo-500/30" :style="{ backgroundColor: `#${theme.colors.primary}`, borderRadius: `${theme.ui.borderRadius}px` }">
                                                    Action Primaire
                                                </button>
                                                <button class="px-6 py-2 bg-white/10 rounded-full text-xs font-bold border border-white/10 hover:bg-white/20 transition-all" :style="{ borderRadius: `${theme.ui.borderRadius}px` }">
                                                    Secondaire
                                                </button>
                                            </div>
                                        </section>

                                        <!-- Card Preview -->
                                        <section class="grid grid-cols-2 gap-4">
                                            <div class="bg-white rounded-[2rem] p-5 shadow-xl" :style="{ borderRadius: `${theme.ui.borderRadius}px` }">
                                                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                                                    <i class="pi pi-check-circle"></i>
                                                </div>
                                                <h5 class="text-slate-900 font-bold text-sm mb-1">Cours Terminés</h5>
                                                <p class="text-slate-400 text-[10px] font-bold">128 Étudiants</p>
                                            </div>
                                            <div class="bg-white rounded-[2rem] p-5 shadow-xl" :style="{ borderRadius: `${theme.ui.borderRadius}px` }">
                                                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4">
                                                    <i class="pi pi-star-fill"></i>
                                                </div>
                                                <h5 class="text-slate-900 font-bold text-sm mb-1">Top Mentor</h5>
                                                <p class="text-slate-400 text-[10px] font-bold">4.9/5 Note</p>
                                            </div>
                                        </section>

                                        <!-- Alert Preview -->
                                        <div class="p-4 bg-rose-500/20 border border-rose-500/50 rounded-2xl flex gap-3 items-center" :style="{ borderRadius: `${theme.ui.borderRadius}px` }">
                                            <i class="pi pi-exclamation-circle text-rose-400"></i>
                                            <span class="text-[10px] font-bold text-rose-200">Attention : Votre abonnement expire bientôt.</span>
                                        </div>

                                        <!-- Form Preview -->
                                        <div class="space-y-3">
                                            <label class="text-[10px] font-black uppercase text-slate-500">Champ de saisie</label>
                                            <div class="relative">
                                                <input type="text" placeholder="Entrez votre email" class="w-full bg-white/5 border border-white/10 p-3 rounded-xl text-xs outline-none focus:ring-2 ring-indigo-500" :style="{ borderRadius: `${theme.ui.borderRadius}px` }" />
                                                <i class="pi pi-envelope absolute right-4 top-3.5 text-slate-500"></i>
                                            </div>
                                        </div>
                                    </div>
                                </ScrollPanel>

                                <div class="mt-8 p-4 bg-white/5 border border-white/10 rounded-2xl flex justify-between items-center">
                                    <div class="flex gap-2">
                                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-500 italic">Interface Subnet v2.4</span>
                                </div>
                            </div>
                        </div>

                        <!-- RECENTLY SAVED -->
                        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
                            <h4 class="text-xs font-black text-slate-800 uppercase mb-4 tracking-widest">Historique des Designs</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-xl cursor-pointer group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                        <span class="text-xs font-bold text-slate-700">Thème "Back to School"</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 group-hover:text-indigo-600 font-bold">Appliquer</span>
                                </div>
                                <div class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-xl cursor-pointer group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="text-xs font-bold text-slate-700">Thème "Nuit Noire"</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 group-hover:text-indigo-600 font-bold">Appliquer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- --- MODAL DE CONFIRMATION DE RÉINITIALISATION --- -->
        <Dialog v-model:visible="showResetDialog" header="Réinitialisation Totale" :modal="true" :style="{ width: '400px' }">
            <div class="flex flex-col items-center text-center py-4">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <i class="pi pi-exclamation-triangle text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Êtes-vous sûr ?</h3>
                <p class="text-slate-500 text-sm">
                    Cette action supprimera toutes vos personnalisations actuelles (couleurs, logos, polices) pour restaurer l'apparence par défaut de SubnetCongo.
                </p>
            </div>
            <template #footer>
                <div class="flex gap-2 justify-center w-full pb-4 px-4">
                    <Button label="Garder mes réglages" class="p-button-text p-button-secondary font-bold flex-1" @click="showResetDialog = false" />
                    <Button label="Réinitialiser" class="p-button-danger rounded-xl flex-1 shadow-lg shadow-rose-200" @click="resetToDefault" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* --- CUSTOM THEME STYLES --- */

:deep(.custom-theme-tabs .p-tabview-nav) {
    background: #fdfdff;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 2rem;
}

:deep(.custom-theme-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    color: #94a3b8;
    padding: 2rem 1.5rem;
    transition: all 0.3s;
}

:deep(.custom-theme-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    box-shadow: inset 0 -3px 0 #6366F1;
}

:deep(.custom-theme-tabs .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* UI ELEMENTS */
:deep(.p-colorpicker-preview) {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 2px solid white;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-inputnumber-input) {
    border-radius: 12px;
    border-color: #e2e8f0;
    transition: all 0.3s;
}

:deep(.p-inputtext:focus) {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

:deep(.p-slider .p-slider-handle) {
    background: #6366F1;
    border: 2px solid white;
}

:deep(.p-slider .p-slider-range) {
    background: #6366F1;
}

:deep(.p-selectbutton .p-button) {
    background: #f8fafc;
    border-color: #e2e8f0;
    font-size: 0.75rem;
    font-weight: 700;
}

:deep(.p-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    border-color: #6366F1;
}

/* Transitions */
.p-button {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.p-button:active {
    transform: scale(0.95);
}

/* Scrollbar Smooth */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
