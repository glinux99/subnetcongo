<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - SYSTEM ARCHITECT & CONFIGURATION UI v10.2.0
 * ======================================================================================
 * Cette vue est le coeur de l'administration système. Elle permet de piloter
 * l'infrastructure logicielle, les serveurs de mails, la sécurité et le branding.
 *
 * Technologies: Vue 3 (Composition API), Inertia.js, PrimeVue 4, Tailwind CSS.
 *
 * @author  SubnetCongo Framework Division
 * @updated 2026-03-03
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// --- IMPORTATION DES COMPOSANTS ---
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
import ColorPicker from 'primevue/colorpicker';
import FileUpload from 'primevue/fileupload';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Textarea from 'primevue/textarea';
import MultiSelect from 'primevue/multiselect';
import SelectButton from 'primevue/selectbutton';
import InlineMessage from 'primevue/inlinemessage';
import ScrollPanel from 'primevue/scrollpanel';
import Password from 'primevue/password';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import Chip from 'primevue/chip';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

// --- RÉCEPTION DES PROPS DEPUIS LE CONTROLLER LARAVEL ---
const props = defineProps({
    settings: Object,       // Mapping complet des catégories (general, branding, lms, mail, etc.)
    dictionaries: Object,   // Listes (languages, timezones, currencies, mailDrivers)
    auth: Object,           // Utilisateur connecté
    errors: Object          // Erreurs de validation Laravel
});

// --- INITIALISATION DES SERVICES ---
const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

// --- ETATS DE GESTION UI ---
const activeTab = ref(0);
const loading = ref(false);
const testEmailDialog = ref(false);
const testEmailAddress = ref('');
const isSubmitting = ref(false);

// --- INITIALISATION DU FORMULAIRE INERTIA ---
// On clone les props dans le formulaire réactif d'Inertia pour la gestion des requêtes
const form = useForm({
    general: { ...props.settings.general },
    branding: { ...props.settings.branding },
    lms: { ...props.settings.lms },
    localization: { ...props.settings.localization },
    mail: { ...props.settings.mail },
    security: { ...props.settings.security },
    advanced: { ...props.settings.advanced }
});

/**
 * --------------------------------------------------------------------------
 * 1. MÉTHODES DE SAUVEGARDE & SYNCHRONISATION
 * --------------------------------------------------------------------------
 */

/**
 * Sauvegarde globale de tous les paramètres
 */
const submitSettings = () => {
    isSubmitting.value = true;
    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            toast.add({
                severity: 'success',
                summary: 'Système Mis à jour',
                detail: 'La configuration a été propagée sur l\'ensemble du cluster.',
                life: 5000
            });
        },
        onError: () => {
            isSubmitting.value = false;
            toast.add({
                severity: 'error',
                summary: 'Erreur Critique',
                detail: 'Certaines valeurs sont invalides. Veuillez vérifier les champs en rouge.',
                life: 5000
            });
        }
    });
};

/**
 * Action de réinitialisation
 */
const resetToDefaults = () => {
    confirm.require({
        message: 'Voulez-vous vraiment annuler toutes les modifications non enregistrées ?',
        header: 'Confirmation',
        icon: 'pi pi-refresh',
        accept: () => {
            form.reset();
            toast.add({ severity: 'info', summary: 'Réinitialisé', detail: 'Les données locales ont été restaurées.', life: 3000 });
        }
    });
};

/**
 * --------------------------------------------------------------------------
 * 2. GESTION DU MODE MAINTENANCE (ARTISAN)
 * --------------------------------------------------------------------------
 */
const toggleMaintenance = (event) => {
    const action = form.advanced.maintenance_mode ? 'désactiver' : 'activer';
    confirm.require({
        target: event.currentTarget,
        message: `Voulez-vous vraiment ${action} le mode maintenance ? Cela affectera l'accès de tous les utilisateurs.`,
        header: 'Alerte Système',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('settings.maintenance.toggle'), {}, {
                onSuccess: () => {
                    form.advanced.maintenance_mode = !form.advanced.maintenance_mode;
                    toast.add({
                        severity: form.advanced.maintenance_mode ? 'warn' : 'success',
                        summary: 'Mode Maintenance',
                        detail: `Le site est maintenant ${form.advanced.maintenance_mode ? 'hors ligne' : 'en ligne'}.`,
                        life: 4000
                    });
                }
            });
        }
    });
};

/**
 * --------------------------------------------------------------------------
 * 3. GESTION DU CACHE ET OPTIMISATION
 * --------------------------------------------------------------------------
 */
const clearCache = () => {
    loading.value = true;
    router.post(route('settings.cache.clear'), {}, {
        onSuccess: () => {
            loading.value = false;
            toast.add({ severity: 'info', summary: 'Cache Purgé', detail: 'Redis et les vues Blade ont été vidés.', life: 3000 });
        },
        onFinish: () => loading.value = false
    });
};

/**
 * --------------------------------------------------------------------------
 * 4. TESTS DE CONFIGURATION (EMAIL)
 * --------------------------------------------------------------------------
 */
const sendTestEmail = () => {
    if (!testEmailAddress.value) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez saisir une adresse email valide.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    router.post(route('settings.mail.test'), { email: testEmailAddress.value }, {
        onSuccess: () => {
            isSubmitting.value = false;
            testEmailDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Email de test envoyé. Vérifiez votre boîte de réception.', life: 5000 });
        },
        onError: () => {
            isSubmitting.value = false;
            toast.add({ severity: 'error', summary: 'Échec SMTP', detail: 'Le serveur n\'a pas pu envoyer l\'email. Vérifiez vos identifiants.', life: 5000 });
        }
    });
};

/**
 * --------------------------------------------------------------------------
 * 5. CALCULS ET COMPUTED
 * --------------------------------------------------------------------------
 */
const siteHealth = computed(() => {
    let score = 100;
    if (!form.security.force_2fa) score -= 15;
    if (form.advanced.debug_mode) score -= 25;
    if (!form.security.enable_captcha) score -= 10;
    if (form.mail.driver === 'smtp' && !form.mail.host) score -= 10;
    return score;
});

const healthSeverity = computed(() => {
    if (siteHealth.value > 80) return 'success';
    if (siteHealth.value > 50) return 'warning';
    return 'danger';
});

// --- GESTION LOGO ---
const onLogoUpload = (event) => {
    // Dans Inertia, on traite l'upload via un post multipart/form-data
    const file = event.files[0];
    router.post(route('settings.logo.upload'), { logo: file }, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Logo Uploadé', detail: 'L\'image de marque a été mise à jour.', life: 3000 })
    });
};

</script>

<template>
    <AppLayout>
        <Head title="Configuration Système - SubNetCongo" />
        <Toast />
        <ConfirmDialog />

        <!-- DIALOG TEST EMAIL -->
        <Dialog v-model:visible="testEmailDialog" modal header="Tester la configuration SMTP" :style="{ width: '450px' }">
            <div class="p-4 space-y-4">
                <p class="text-sm text-slate-500">Un email sera envoyé avec les paramètres actuels du serveur pour valider la délivrabilité.</p>
                <div class="field">
                    <label class="font-bold mb-2 block">Adresse email de destination</label>
                    <InputText v-model="testEmailAddress" placeholder="test@subnetcongo.cd" class="w-full" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="testEmailDialog = false" />
                <Button label="Envoyer le test" icon="pi pi-send" :loading="isSubmitting" @click="sendTestEmail" />
            </template>
        </Dialog>

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- --- BARRE DE TITRE & ACTIONS --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-5 rounded-[1rem] shadow-2xl shadow-indigo-100 -rotate-2 animate-pulse">
                        <i class="pi pi-cog text-white text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase italic">Configuration Globale</h1>
                            <Badge value="Core Engine v10" severity="info" class="text-[10px]" />
                        </div>
                        <p class="text-slate-500 font-medium italic">Pilotage de l'infrastructure logicielle SubNetCongo Evaluation</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                    <Button label="Réinitialiser" icon="pi pi-refresh" class="p-button-text p-button-secondary font-bold" @click="resetToDefaults" />
                    <Divider layout="vertical" />
                    <Button :label="isSubmitting ? 'Propagation...' : 'Enregistrer les modifications'"
                            :icon="isSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-cloud-upload'"
                            class="p-button-primary shadow-xl px-10 py-3 rounded-xl font-black text-lg transition-transform active:scale-95"
                            @click="submitSettings"
                            :disabled="isSubmitting" />
                </div>
            </header>

            <!-- --- SECTION PRINCIPALE DES PARAMÈTRES --- -->
            <div class="bg-white rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 overflow-hidden">
                <TabView v-model:activeIndex="activeTab" class="custom-settings-tabs">

                    <!-- --- TAB 1: IDENTITÉ & BRANDING --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-palette text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Branding</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Identité Visuelle</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 space-y-16">
                            <div class="grid grid-cols-12 gap-12">
                                <!-- Section gauche : Formulaire texte -->
                                <div class="col-span-12 lg:col-span-8 space-y-10">
                                    <div class="border-b-2 border-slate-50 pb-4">
                                        <h3 class="text-2xl font-black text-slate-800 tracking-tight italic">Informations Institutionnelles</h3>
                                        <p class="text-slate-400 text-sm">Ces données apparaissent sur les factures, emails et diplômes.</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="field">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Nom de l'organisation</label>
                                            <InputText v-model="form.general.site_name" class="w-full rounded-2xl border-slate-200 p-4 focus:ring-4 focus:ring-indigo-50 shadow-sm" placeholder="Ex: SubNetCongo LMS" />
                                            <small class="text-rose-500 font-bold" v-if="form.errors['general.site_name']">{{ form.errors['general.site_name'] }}</small>
                                        </div>
                                        <div class="field">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Slogan / Signature</label>
                                            <InputText v-model="form.general.site_slogan" class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" placeholder="La technologie du futur..." />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="field">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Email administratif</label>
                                            <InputGroup>
                                                <InputGroupAddon><i class="pi pi-envelope"></i></InputGroupAddon>
                                                <InputText v-model="form.general.contact_email" class="rounded-r-2xl border-slate-200 p-4" />
                                            </InputGroup>
                                        </div>
                                        <div class="field">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Téléphone Support</label>
                                            <InputGroup>
                                                <InputGroupAddon><i class="pi pi-phone"></i></InputGroupAddon>
                                                <InputText v-model="form.general.phone" class="rounded-r-2xl border-slate-200 p-4" />
                                            </InputGroup>
                                        </div>
                                    </div>

                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Adresse du siège social</label>
                                        <Textarea v-model="form.general.address" rows="3" class="w-full rounded-3xl border-slate-200 p-5 shadow-sm" placeholder="Numéro, Rue, Ville, Pays..." />
                                    </div>

                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-[0.2em]">Texte de bas de page (Footer)</label>
                                        <InputText v-model="form.general.footer_text" class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" />
                                    </div>
                                </div>

                                <!-- Section droite : Visual Branding -->
                                <div class="col-span-12 lg:col-span-4 bg-slate-50 p-8 rounded-[3.5rem] border border-slate-100 shadow-inner">
                                    <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-3 italic">
                                        <i class="pi pi-pencil text-indigo-500"></i> Interface UI
                                    </h3>

                                    <div class="space-y-10">
                                        <div class="flex items-center justify-between p-4 bg-white rounded-3xl shadow-sm border border-slate-100">
                                            <div>
                                                <span class="font-black text-slate-700 block italic">Couleur Primaire</span>
                                                <span class="text-[9px] text-slate-400 uppercase font-bold">Thème principal de navigation</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono text-xs text-indigo-500">#{{ form.branding.primary_color }}</span>
                                                <ColorPicker v-model="form.branding.primary_color" />
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between p-4 bg-white rounded-3xl shadow-sm border border-slate-100">
                                            <div>
                                                <span class="font-black text-slate-700 block italic">Couleur Secondaire</span>
                                                <span class="text-[9px] text-slate-400 uppercase font-bold">Accentuation des interactions</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-mono text-xs text-emerald-500">#{{ form.branding.secondary_color }}</span>
                                                <ColorPicker v-model="form.branding.secondary_color" />
                                            </div>
                                        </div>

                                        <Divider align="center"><span class="text-[10px] font-black text-slate-300 uppercase">Fichiers Média</span></Divider>

                                        <div class="space-y-6">
                                            <div class="field">
                                                <label class="block text-[11px] font-black text-slate-400 uppercase mb-4 tracking-widest text-center">Logo Officiel (Version Sombre)</label>
                                                <div class="flex flex-col items-center gap-6">
                                                    <div class="w-full p-10 bg-white rounded-[1.5rem] flex justify-center border-2 border-dashed border-slate-200 group hover:border-indigo-400 transition-all cursor-pointer">
                                                        <img :src="form.branding.logo_url" class="h-16 object-contain group-hover:scale-110 transition-transform" />
                                                    </div>
                                                    <FileUpload mode="basic" name="logo" @uploader="onLogoUpload" customUpload :auto="true" chooseLabel="Remplacer le Logo" class="p-button-outlined p-button-indigo rounded-xl font-bold" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- --- TAB 2: MOTEUR LMS --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-graduation-cap text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Moteur LMS</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Règles Pédagogiques</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 max-w-6xl mx-auto space-y-12">
                            <div class="flex items-center gap-4 border-b-2 border-slate-50 pb-6">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                    <i class="pi pi-sliders-v text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-slate-800 tracking-tight italic">Intelligence Académique</h3>
                                    <p class="text-slate-500 text-sm">Contrôlez l'automatisation des parcours et la validation des acquis.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                <!-- Règles d'accès -->
                                <div class="space-y-6">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em] mb-6">Accès & Inscriptions</h4>

                                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-lg transition-all group">
                                        <div class="flex gap-4 items-center">
                                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-bolt"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Auto-inscription sur paiement</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Délivrance immédiate de l'accès au cours</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.lms.auto_enroll_on_payment" />
                                    </div>

                                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-lg transition-all group">
                                        <div class="flex gap-4 items-center">
                                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-eye"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Aperçu vidéo autorisé</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Autoriser les non-inscrits à voir 5 min</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.lms.allow_course_preview" />
                                    </div>

                                    <div class="flex justify-between items-center bg-white p-6 rounded-[1rem] border border-slate-100 shadow-sm hover:shadow-lg transition-all group">
                                        <div class="flex gap-4 items-center">
                                            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-shield"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Filigrane de sécurité</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Affichage dynamique de l'ID élève sur vidéo</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.lms.video_watermark" />
                                    </div>
                                </div>

                                <!-- Validation & Diplômes -->
                                <div class="bg-slate-900 p-10 rounded-[3.5rem] shadow-2xl relative overflow-hidden">
                                    <i class="pi pi-verified absolute -right-10 -bottom-10 text-[12rem] text-white/5 rotate-12"></i>
                                    <h4 class="text-xs font-black text-indigo-400 uppercase tracking-[0.3em] mb-10">Certification & Diplômes</h4>

                                    <div class="space-y-8 relative z-10">
                                        <div class="field">
                                            <label class="block text-xs font-bold text-slate-400 mb-3 uppercase">Seuil de réussite minimal</label>
                                            <div class="flex items-center gap-6">
                                                <InputNumber v-model="form.lms.min_pass_score" suffix="%" class="flex-1" inputClass="bg-slate-800 border-none text-white text-2xl font-black p-4 rounded-2xl" />
                                                <span class="text-[10px] font-black text-indigo-300 w-24">Recommandé : 75%</span>
                                            </div>
                                            <ProgressBar :value="form.lms.min_pass_score" :showValue="false" class="h-2 mt-4 bg-slate-800" color="#6366f1" />
                                        </div>

                                        <div class="field">
                                            <label class="block text-xs font-bold text-slate-400 mb-3 uppercase">Préfixe de certification</label>
                                            <InputText v-model="form.lms.certificate_prefix" class="w-full bg-slate-800 border-none text-white font-mono p-4 rounded-2xl" />
                                            <p class="text-[9px] text-slate-500 mt-2 uppercase font-black">Génère : {{ form.lms.certificate_prefix }}2026-0001</p>
                                        </div>

                                        <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
                                            <div>
                                                <span class="font-black text-white block italic">Génération certificates PDF</span>
                                                <p class="text-[10px] text-slate-500">Service SNC-RENDER actif</p>
                                            </div>
                                            <InputSwitch v-model="form.lms.enable_certificates" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- --- TAB 3: LOCALISATION & UNITÉS --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-globe text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Localisation</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Culture & Unités</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 grid grid-cols-12 gap-12">
                            <div class="col-span-12 lg:col-span-6 space-y-10">
                                <div class="border-b-2 border-slate-50 pb-4">
                                    <h3 class="text-2xl font-black text-slate-800 tracking-tight italic">Région & Unités</h3>
                                    <p class="text-slate-400 text-sm">Adaptez l'interface aux standards locaux de vos élèves.</p>
                                </div>

                                <div class="field">
                                    <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Langue par défaut</label>
                                    <Dropdown v-model="form.localization.default_language" :options="props.dictionaries.languages" optionLabel="name" optionValue="code" class="w-full rounded-2xl p-2 border-slate-200 shadow-sm" />
                                </div>

                                <div class="field">
                                    <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Fuseau Horaire (Timezone)</label>
                                    <Dropdown v-model="form.localization.timezone" :options="props.dictionaries.timezones" optionLabel="label" optionValue="value" filter placeholder="Sélectionner une zone" class="w-full rounded-2xl p-2 border-slate-200 shadow-sm" />
                                </div>

                                <div class="grid grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Devise</label>
                                        <Dropdown v-model="form.localization.currency" :options="props.dictionaries.currencies" optionLabel="label" optionValue="value" class="w-full rounded-2xl p-2 border-slate-200 shadow-sm" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Symbole</label>
                                        <InputText v-model="form.localization.currency_symbol" class="w-full rounded-2xl border-slate-200 p-4 font-black text-center" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Séparateur Milliers</label>
                                        <InputText v-model="form.localization.thousand_separator" class="w-full rounded-2xl border-slate-200 p-4 text-center font-black" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Séparateur Décimal</label>
                                        <InputText v-model="form.localization.decimal_separator" class="w-full rounded-2xl border-slate-200 p-4 text-center font-black" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 lg:col-span-6 bg-slate-900 p-12 rounded-[4rem] flex flex-col justify-center items-center text-center shadow-2xl">
                                <div class="w-24 h-24 bg-white/5 rounded-full flex items-center justify-center mb-8">
                                    <i class="pi pi-clock text-4xl text-indigo-400"></i>
                                </div>
                                <h4 class="text-3xl font-black text-white italic tracking-tight mb-2">Aperçu du Format</h4>
                                <p class="text-slate-400 text-sm mb-10 max-w-xs">Le système utilise dynamiquement ces réglages pour l'affichage monétaire et temporel.</p>

                                <div class="space-y-4 w-full">
                                    <div class="bg-white/5 p-6 rounded-3xl border border-white/10">
                                        <span class="text-[10px] font-black text-indigo-400 uppercase block mb-2">Affichage Prix</span>
                                        <span class="text-4xl font-black text-white italic">1{{ form.localization.thousand_separator }}250{{ form.localization.decimal_separator }}50 {{ form.localization.currency_symbol }}</span>
                                    </div>
                                    <div class="bg-white/5 p-6 rounded-3xl border border-white/10">
                                        <span class="text-[10px] font-black text-indigo-400 uppercase block mb-2">Date courante</span>
                                        <span class="text-xl font-bold text-white">{{ new Date().toLocaleDateString(form.localization.default_language) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- --- TAB 4: SERVEUR MAIL (SMTP/API) --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-envelope text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Serveur Mail</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Infrastructure Envoi</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 grid grid-cols-12 gap-12">
                            <div class="col-span-12 lg:col-span-7 space-y-10">
                                <div class="flex justify-between items-center border-b-2 border-slate-50 pb-6">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-800 tracking-tight italic">Passerelle de Communication</h3>
                                        <p class="text-slate-400 text-sm">Configurez les services SMTP ou API pour les notifications élèves.</p>
                                    </div>
                                    <Button label="Tester la connexion" icon="pi pi-send" class="p-button-outlined p-button-indigo rounded-xl font-bold" @click="testEmailDialog = true" />
                                </div>

                                <div class="field">
                                    <label class="block text-[11px] font-black text-slate-400 uppercase mb-4 tracking-widest italic">Driver de messagerie actif</label>
                                    <SelectButton v-model="form.mail.driver" :options="props.dictionaries.mailDrivers" optionLabel="label" optionValue="value" class="custom-select-btn" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div class="md:col-span-3 field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Hôte (Host)</label>
                                        <InputText v-model="form.mail.host" class="w-full rounded-2xl border-slate-200 p-4 font-mono text-indigo-600" placeholder="smtp.mailtrap.io" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Port</label>
                                        <InputNumber v-model="form.mail.port" class="w-full" inputClass="rounded-2xl border-slate-200 p-4 text-center" :useGrouping="false" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Nom d'utilisateur</label>
                                        <InputText v-model="form.mail.username" class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Mot de passe / Clé API</label>
                                        <Password v-model="form.mail.password" :feedback="false" toggleMask class="w-full rounded-2xl" inputClass="w-full p-4 border-slate-200 rounded-2xl" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Email expéditeur (From)</label>
                                        <InputText v-model="form.mail.from_address" class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-3 tracking-widest italic">Nom d'envoi</label>
                                        <InputText v-model="form.mail.from_name" class="w-full rounded-2xl border-slate-200 p-4 shadow-sm" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 lg:col-span-5 space-y-8">
                                <div class="p-8 bg-slate-50 border-2 border-slate-100 rounded-[3rem] space-y-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-500">
                                            <i class="pi pi-shield text-xl"></i>
                                        </div>
                                        <h4 class="font-black text-slate-800 italic uppercase text-sm">Sécurité de transport</h4>
                                    </div>

                                    <div class="field">
                                        <label class="block text-[11px] font-black text-slate-400 uppercase mb-4 tracking-widest italic">Type de chiffrement (Encryption)</label>
                                        <SelectButton v-model="form.mail.encryption" :options="['tls', 'ssl', 'none']" class="custom-select-btn" />
                                    </div>

                                    <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm space-y-4">
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-slate-400 uppercase">Dernier succès</span>
                                            <span class="text-emerald-500 italic">Il y a 14 min</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-slate-400 uppercase">File d'attente (Queue)</span>
                                            <span class="text-indigo-500 italic">0 en attente</span>
                                        </div>
                                    </div>

                                    <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100 flex gap-4">
                                        <i class="pi pi-exclamation-triangle text-amber-500 mt-1"></i>
                                        <p class="text-[10px] font-bold text-amber-700 leading-relaxed uppercase">
                                            Assurez-vous que votre pare-feu autorise les connexions sortantes sur les ports {{ form.mail.port }}. L'utilisation de serveurs mail non sécurisés peut blacklister votre adresse IP.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- --- TAB 5: SÉCURITÉ & ACCÈS --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-shield text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Sécurité</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Blindage Système</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 max-w-5xl mx-auto space-y-12">
                            <div class="flex items-center justify-between border-b-2 border-slate-50 pb-8">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 bg-slate-900 text-white rounded-3xl flex items-center justify-center shadow-2xl">
                                        <i class="pi pi-lock text-3xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-800 tracking-tight italic uppercase">Authentification & Accès</h3>
                                        <p class="text-slate-400 font-medium italic">Configurez les barrières de protection de votre base de données.</p>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-[10px] font-black uppercase text-slate-400">Score de Santé</span>
                                        <Tag :severity="healthSeverity" :value="siteHealth + '%'" class="px-4 py-2 font-black rounded-xl text-lg" />
                                    </div>
                                    <ProgressBar :value="siteHealth" :showValue="false" class="h-2 w-32 bg-slate-100" :color="siteHealth > 80 ? '#10b981' : '#f59e0b'" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-6">
                                    <div class="p-8 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm flex items-center justify-between group hover:border-indigo-300 transition-all">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-user-plus text-xl"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Inscriptions Publiques</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Autoriser les nouveaux comptes</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.security.enable_registration" />
                                    </div>

                                    <div class="p-8 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm flex items-center justify-between group hover:border-rose-300 transition-all">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-verified text-xl"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Double Authentification</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Forcer le 2FA pour les Admins</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.security.force_2fa" />
                                    </div>

                                    <div class="p-8 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm flex items-center justify-between group hover:border-amber-300 transition-all">
                                        <div class="flex items-center gap-5">
                                            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                                <i class="pi pi-shield text-xl"></i>
                                            </div>
                                            <div>
                                                <span class="font-black text-slate-800 block italic">Protection Captcha</span>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Antispam sur login et inscription</p>
                                            </div>
                                        </div>
                                        <InputSwitch v-model="form.security.enable_captcha" />
                                    </div>
                                </div>

                                <div class="bg-slate-50 p-10 rounded-[3rem] border border-slate-200 shadow-inner">
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-10 text-center">Politique de Mots de Passe</h4>

                                    <div class="space-y-10">
                                        <div class="field">
                                            <label class="block text-sm font-bold text-slate-700 mb-4 italic">Longueur minimale exigée</label>
                                            <div class="flex items-center gap-6">
                                                <InputNumber v-model="form.security.password_min_length" showButtons :min="6" :max="32" class="flex-1" inputClass="rounded-2xl border-none p-4 text-2xl font-black text-indigo-600 shadow-sm" />
                                                <span class="text-xs font-black text-slate-400 uppercase">caractères</span>
                                            </div>
                                        </div>

                                        <div class="flex justify-between items-center py-4 border-t border-slate-200">
                                            <span class="font-bold text-slate-600 italic">Caractères spéciaux requis</span>
                                            <InputSwitch v-model="form.security.require_special_char" />
                                        </div>

                                        <div class="field pt-6 border-t border-slate-200">
                                            <label class="block text-sm font-bold text-slate-700 mb-4 italic">Durée de session (Inactivité)</label>
                                            <div class="flex items-center gap-6">
                                                <InputNumber v-model="form.security.session_timeout" suffix=" min" showButtons :min="15" :step="15" class="flex-1" inputClass="rounded-2xl border-none p-4 text-2xl font-black text-indigo-600 shadow-sm" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-8 bg-rose-50 rounded-[3rem] border border-rose-100 flex items-start gap-6">
                                <i class="pi pi-info-circle text-2xl text-rose-500 mt-1"></i>
                                <div>
                                    <h5 class="font-black text-rose-900 italic uppercase text-sm">Contrôle par liste blanche (IP)</h5>
                                    <p class="text-xs text-rose-700 mt-2 mb-6">Restreindre l'accès au panneau d'administration uniquement aux adresses IP listées ci-dessous.</p>
                                    <InputText v-model="form.security.ip_whitelist" placeholder="192.168.1.1, 10.0.0.1 (Laissez vide pour autoriser tout le monde)" class="w-full rounded-2xl p-4 border-rose-200 font-mono text-xs shadow-sm" />
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- --- TAB 6: AVANCÉ & MAINTENANCE --- -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-3 py-2 px-4">
                                <i class="pi pi-database text-xl"></i>
                                <div class="flex flex-col items-start">
                                    <span class="text-sm font-black">Avancé</span>
                                    <span class="text-[9px] uppercase tracking-widest text-slate-400">Noyau & Système</span>
                                </div>
                            </div>
                        </template>

                        <div class="p-10 max-w-5xl mx-auto space-y-16">
                            <!-- Zone de Danger : Maintenance -->
                            <div class="bg-rose-50 border-2 border-rose-100 p-10 rounded-[4rem] shadow-xl relative overflow-hidden group">
                                <i class="pi pi-bolt absolute -right-10 -bottom-10 text-[15rem] text-rose-100/30 rotate-12 transition-transform group-hover:rotate-0"></i>

                                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-10 relative z-10">
                                    <div class="flex items-center gap-8">
                                        <div :class="form.advanced.maintenance_mode ? 'bg-rose-600 text-white' : 'bg-rose-100 text-rose-600'"
                                             class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center shadow-2xl transition-all duration-500">
                                            <i :class="form.advanced.maintenance_mode ? 'pi pi-lock' : 'pi pi-unlock'" class="text-4xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-3xl font-black text-rose-900 tracking-tighter uppercase italic">Zone de Danger</h3>
                                            <p class="text-rose-700 font-medium italic">Actions critiques affectant la disponibilité globale.</p>
                                        </div>
                                    </div>
                                    <Button :label="form.advanced.maintenance_mode ? 'Désactiver la Maintenance' : 'Activer le Mode Maintenance'"
                                            :icon="form.advanced.maintenance_mode ? 'pi pi-power-off' : 'pi pi-exclamation-triangle'"
                                            :class="form.advanced.maintenance_mode ? 'p-button-danger' : 'p-button-outlined p-button-danger border-4'"
                                            class="font-black px-10 py-5 rounded-[1rem] text-lg shadow-2xl transition-all active:scale-95"
                                            @click="toggleMaintenance" />
                                </div>

                                <div v-if="form.advanced.maintenance_mode" class="mt-10 p-6 bg-white/50 rounded-3xl border border-rose-200">
                                    <InlineMessage severity="error" class="w-full justify-start border-none bg-transparent font-bold">
                                        Le site est actuellement inaccessible pour les élèves. Seul l'accès administrateur est autorisé.
                                    </InlineMessage>
                                </div>
                            </div>

                            <!-- Optimisation Cache -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <Card class="border-2 border-slate-100 rounded-[3rem] shadow-none overflow-hidden group hover:border-indigo-200 transition-all">
                                    <template #title>
                                        <div class="flex items-center gap-4 p-4 border-b border-slate-50 bg-slate-50/50">
                                            <i class="pi pi-bolt text-amber-500"></i>
                                            <span class="text-base font-black italic uppercase">Nettoyage Cache</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="p-4 space-y-6 text-center">
                                            <p class="text-sm text-slate-500 font-medium italic">Purgez les fichiers temporaires et les caches d'application pour libérer de la RAM et appliquer les modifications Blade/Route.</p>
                                            <Button label="Purger le Cache Système" icon="pi pi-trash" class="p-button-outlined p-button-secondary w-full font-black py-4 rounded-2xl shadow-sm hover:shadow-lg transition-all" @click="clearCache" :loading="loading" />
                                        </div>
                                    </template>
                                </Card>

                                <Card class="border-2 border-slate-100 rounded-[3rem] shadow-none overflow-hidden group hover:border-indigo-200 transition-all">
                                    <template #title>
                                        <div class="flex items-center gap-4 p-4 border-b border-slate-50 bg-slate-50/50">
                                            <i class="pi pi-chart-bar text-indigo-500"></i>
                                            <span class="text-base font-black italic uppercase">Analytique Externe</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="p-4 space-y-6">
                                            <div class="field">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest text-center italic">Google Analytics Property ID</label>
                                                <InputText v-model="form.advanced.analytics_id" placeholder="G-XXXXXXXXXX" class="w-full font-mono bg-slate-50 border-none shadow-inner p-4 rounded-2xl text-center font-black text-indigo-600" />
                                            </div>
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full uppercase">Connecté</span>
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>

                            <!-- Diagnostic System -->
                            <div class="p-10 bg-slate-900 rounded-[4rem] text-white shadow-2xl relative group overflow-hidden">
                                <i class="pi pi-database absolute -left-10 -bottom-10 text-[12rem] text-white/5 rotate-12 group-hover:rotate-0 transition-all duration-700"></i>
                                <div class="flex flex-col md:flex-row justify-between items-center gap-8 relative z-10">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-white/5 rounded-3xl flex items-center justify-center border border-white/10 shadow-inner">
                                            <i class="pi pi-server text-3xl text-indigo-400"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-2xl font-black italic tracking-tighter">LMS Core Cluster v10.1</span>
                                                <Badge value="Stable" severity="success" class="rounded-xl px-4" />
                                            </div>
                                            <p class="text-slate-400 text-sm mt-1">Serveur : PHP 8.2 / Laravel 10.x / PostgreSQL 15</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <Button label="Vérifier Update" icon="pi pi-cloud-download" class="p-button-text p-button-white font-black italic" />
                                        <Divider layout="vertical" class="bg-white/10" />
                                        <Button label="Logs" icon="pi pi-list" class="p-button-text p-button-white font-black italic" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                </TabView>
            </div>

            <!-- --- FOOTER INFO --- -->
            <footer class="mt-16 text-center space-y-4 pb-12">
                <div class="flex justify-center gap-8">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Base de données OK</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Storage S3 connecté</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Redis Cache Actif</span>
                    </div>
                </div>
                <p class="text-slate-300 text-[11px] font-medium tracking-widest italic">
                    © 2026 SubNetCongo Technologies - Système de Gestion des Apprentissages Centralisé.
                </p>
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/**
 * ======================================================================================
 * CUSTOM STYLES - SUBNETCONGO EVALUATION SYSTEM UI
 * ======================================================================================
 */

/* TabView Personalization */
:deep(.p-tabview-nav) {
    background: #fff;
    border-bottom: 2px solid #f8fafc;
    padding: 0 4rem;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    color: #94a3b8;
    padding: 2.5rem 1.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link::after) {
    content: '';
    position: absolute;
    bottom: 0;
    left: 1.5rem;
    right: 1.5rem;
    height: 4px;
    background: #6366f1;
    border-radius: 10px 10px 0 0;
    box-shadow: 0 -4px 10px rgba(99, 102, 241, 0.4);
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* ColorPicker Style */
:deep(.p-colorpicker-preview) {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1.25rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 3px solid #fff;
}

/* Form UI */
:deep(.custom-select-btn .p-button) {
    background: #fff;
    border: 2px solid #f1f5f9;
    color: #64748b;
    font-weight: 800;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 1.2rem 1.5rem;
    flex: 1;
    transition: all 0.3s;
}

:deep(.custom-select-btn .p-button.p-highlight) {
    background: #f5f3ff;
    border-color: #6366f1;
    color: #6366f1;
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
}

:deep(.large-select-btn .p-button) {
    padding: 3rem 5rem;
    font-size: 1.5rem;
    font-weight: 900;
    border-radius: 3rem;
    margin: 0 1.5rem;
}

/* Animations & Transitions */
.animate-pulse {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1) rotate(-2deg); }
    50% { opacity: 0.8; transform: scale(1.05) rotate(0deg); }
}

/* Responsive Table Adjustments */
@media screen and (max-width: 960px) {
    :deep(.p-tabview-nav) {
        padding: 0 1rem;
        flex-wrap: nowrap;
        overflow-x: auto;
    }
}

/* Scrollbar Style */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
::-webkit-scrollbar-track {
    background: #f1f5f9;
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/**
 * --- FIN DU FICHIER ---
 * Ce composant gère l'infrastructure atomique du système SubNetCongo.
 * Designé pour la robustesse et l'évolutivité.
 */
</style>
