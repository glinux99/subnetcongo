<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// --- PRIME VUE IMPORTS ---
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

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

// --- ETATS DE GESTION ---
const activeTab = ref(0);
const loading = ref(false);
const saving = ref(false);

// --- MODÈLE DE DONNÉES DES PARAMÈTRES (TRÈS DÉTAILLÉ) ---
const settings = ref({
    general: {
        site_name: 'SubNetCongo LMS',
        site_slogan: 'La technologie du futur pour l\'apprentissage',
        contact_email: 'admin@subnetcongo.cd',
        phone: '+243 81 000 0000',
        address: 'Kinshasa, Gombe - RDC',
        footer_text: '© 2026 SubNetCongo. Tous droits réservés.',
        is_multilingue: true
    },
    branding: {
        primary_color: '6366F1',
        secondary_color: '10B981',
        logo_url: '/images/logo-dark.png',
        favicon_url: '/images/favicon.ico',
        border_radius: '16px'
    },
    lms: {
        auto_enroll_on_payment: true,
        enable_certificates: true,
        certificate_prefix: 'SNC-',
        min_pass_score: 75,
        allow_course_preview: true,
        video_watermark: true,
        max_login_attempts: 5
    },
    localization: {
        default_language: 'fr',
        timezone: 'Africa/Kinshasa',
        currency: 'USD',
        currency_symbol: '$',
        date_format: 'DD/MM/YYYY',
        thousand_separator: ' ',
        decimal_separator: ','
    },
    mail: {
        driver: 'smtp',
        host: 'smtp.sendgrid.net',
        port: 587,
        username: 'apikey',
        encryption: 'tls',
        from_name: 'SubNetCongo Support',
        from_address: 'support@subnetcongo.cd'
    },
    security: {
        enable_registration: true,
        force_2fa: false,
        password_min_length: 8,
        require_special_char: true,
        enable_captcha: true,
        session_timeout: 120, // minutes
        ip_whitelist: ''
    },
    advanced: {
        maintenance_mode: false,
        debug_mode: false,
        cache_driver: 'redis',
        analytics_id: 'G-XXXXXXXXXX'
    }
});

// --- OPTIONS DES DROPDOWNS ---
const languages = ref([
    { name: 'Français', code: 'fr' },
    { name: 'English', code: 'en' },
    { name: 'Lingala', code: 'ln' }
]);

const timezones = ref([
    { label: 'Kinshasa (GMT+1)', value: 'Africa/Kinshasa' },
    { label: 'Lubumbashi (GMT+2)', value: 'Africa/Lubumbashi' },
    { label: 'Paris (GMT+1)', value: 'Europe/Paris' },
    { label: 'New York (GMT-5)', value: 'America/New_York' }
]);

const currencies = ref([
    { label: 'US Dollar ($)', value: 'USD' },
    { label: 'Congolese Franc (FC)', value: 'CDF' },
    { label: 'Euro (€)', value: 'EUR' }
]);

const mailDrivers = ref([
    { label: 'SMTP', value: 'smtp' },
    { label: 'Mailgun', value: 'mailgun' },
    { label: 'SES (AWS)', value: 'ses' }
]);

// --- ACTIONS ---
const handleSave = () => {
    saving.value = true;
    setTimeout(() => {
        saving.value = false;
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Tous les paramètres ont été mis à jour.', life: 4000 });
    }, 1500);
};

const confirmMaintenance = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Voulez-vous vraiment activer le mode maintenance ? Le site sera inaccessible aux élèves.',
        header: 'Attention Sécurité',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            settings.value.advanced.maintenance_mode = true;
            toast.add({ severity: 'warn', summary: 'Mode Maintenance', detail: 'Le site est maintenant en maintenance.', life: 3000 });
        }
    });
};

const clearCache = () => {
    loading.value = true;
    setTimeout(() => {
        loading.value = false;
        toast.add({ severity: 'info', summary: 'Cache vidé', detail: 'Le cache Redis a été purgé avec succès.', life: 3000 });
    }, 1000);
};

const onLogoUpload = (event) => {
    toast.add({ severity: 'info', summary: 'Upload', detail: 'Le nouveau logo a été téléchargé.', life: 2000 });
};

// --- CALCULS ---
const siteHealth = computed(() => {
    let score = 100;
    if (!settings.value.security.force_2fa) score -= 10;
    if (settings.value.advanced.debug_mode) score -= 20;
    if (!settings.value.security.enable_captcha) score -= 5;
    return score;
});

</script>

<template>
    <AppLayout>
        <Head title="Paramètres Système - SubNetCongo" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen pb-20">

            <!-- HEADER GLOBAL -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-6">
                    <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100 -rotate-2">
                        <i class="pi pi-cog text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Configuration Globale</h1>
                        <p class="text-slate-500 font-medium">Contrôlez l'ensemble de l'écosystème SubNetCongo</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Button label="Réinitialiser" icon="pi pi-refresh" class="p-button-outlined p-button-secondary border-2 font-bold" @click="loading = true" />
                    <Button :label="saving ? 'Enregistrement...' : 'Enregistrer les modifications'"
                            :icon="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
                            class="p-button-primary shadow-lg px-8 font-black"
                            @click="handleSave" />
                </div>
            </header>

            <!-- ZONE DE TABS -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-50 overflow-hidden">
                <TabView v-model:activeIndex="activeTab" class="custom-settings-tabs">

                    <!-- TAB 1: GENERAL & BRANDING -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-palette"></i>
                                <span>Identité & Branding</span>
                            </div>
                        </template>

                        <div class="p-8 space-y-12">
                            <div class="grid grid-cols-12 gap-8">
                                <!-- Section gauche : Formulaire texte -->
                                <div class="col-span-12 lg:col-span-8 space-y-6">
                                    <h3 class="text-xl font-black text-slate-800 border-l-4 border-indigo-500 pl-4">Informations de base</h3>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="field">
                                            <label class="block text-xs font-black text-slate-400 uppercase mb-2 tracking-widest">Nom du site</label>
                                            <InputText v-model="settings.general.site_name" class="w-full rounded-xl border-2 py-3" />
                                        </div>
                                        <div class="field">
                                            <label class="block text-xs font-black text-slate-400 uppercase mb-2 tracking-widest">Slogan</label>
                                            <InputText v-model="settings.general.site_slogan" class="w-full rounded-xl border-2 py-3" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="field">
                                            <label class="block text-xs font-black text-slate-400 uppercase mb-2 tracking-widest">Email de contact</label>
                                            <InputText v-model="settings.general.contact_email" class="w-full rounded-xl border-2 py-3" />
                                        </div>
                                        <div class="field">
                                            <label class="block text-xs font-black text-slate-400 uppercase mb-2 tracking-widest">Téléphone officiel</label>
                                            <InputText v-model="settings.general.phone" class="w-full rounded-xl border-2 py-3" />
                                        </div>
                                    </div>

                                    <div class="field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-2 tracking-widest">Adresse Physique</label>
                                        <Textarea v-model="settings.general.address" rows="2" class="w-full rounded-xl border-2" />
                                    </div>
                                </div>

                                <!-- Section droite : Visual Branding -->
                                <div class="col-span-12 lg:col-span-4 bg-slate-50 p-6 rounded-[2rem] border border-slate-100">
                                    <h3 class="text-lg font-black text-slate-800 mb-6">Design & Couleurs</h3>

                                    <div class="space-y-8">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-bold text-slate-700 block">Couleur Primaire</span>
                                                <span class="text-[10px] text-slate-400">Utilisée pour les boutons et titres</span>
                                            </div>
                                            <ColorPicker v-model="settings.branding.primary_color" />
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-bold text-slate-700 block">Couleur Secondaire</span>
                                                <span class="text-[10px] text-slate-400">Utilisée pour les badges</span>
                                            </div>
                                            <ColorPicker v-model="settings.branding.secondary_color" />
                                        </div>

                                        <Divider />

                                        <div>
                                            <label class="block text-xs font-black text-slate-400 uppercase mb-3 tracking-widest">Logo (Format PNG/SVG)</label>
                                            <FileUpload mode="basic" name="logo" url="/api/upload" accept="image/*" :maxFileSize="1000000" @upload="onLogoUpload" class="p-button-outlined p-button-secondary w-full" />
                                            <div class="mt-4 p-4 bg-white rounded-2xl flex justify-center border border-dashed border-slate-200">
                                                <img :src="settings.branding.logo_url" class="h-12 opacity-50" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 2: LMS ENGINE -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-graduation-cap"></i>
                                <span>Moteur LMS</span>
                            </div>
                        </template>

                        <div class="p-8 max-w-5xl">
                            <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center gap-3">
                                <i class="pi pi-sliders-v text-indigo-500"></i>
                                Paramètres Pédagogiques
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <!-- Col 1: Access Rules -->
                                <div class="space-y-8">
                                    <div class="flex justify-between items-center bg-indigo-50/30 p-4 rounded-2xl border border-indigo-100">
                                        <div>
                                            <span class="font-black text-slate-800 block">Inscription automatique</span>
                                            <p class="text-[10px] text-slate-500 uppercase font-bold">Inscrire l'élève dès le paiement confirmé</p>
                                        </div>
                                        <InputSwitch v-model="settings.lms.auto_enroll_on_payment" />
                                    </div>

                                    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl">
                                        <div>
                                            <span class="font-black text-slate-800 block">Aperçu gratuit des cours</span>
                                            <p class="text-[10px] text-slate-500 uppercase font-bold">Autoriser la lecture des 3 premiers chapitres</p>
                                        </div>
                                        <InputSwitch v-model="settings.lms.allow_course_preview" />
                                    </div>

                                    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl">
                                        <div>
                                            <span class="font-black text-slate-800 block">Filigrane Vidéo</span>
                                            <p class="text-[10px] text-slate-500 uppercase font-bold">Afficher l'ID élève sur les vidéos pour le piratage</p>
                                        </div>
                                        <InputSwitch v-model="settings.lms.video_watermark" />
                                    </div>
                                </div>

                                <!-- Col 2: Graduation -->
                                <div class="bg-white border-2 border-slate-100 p-6 rounded-3xl space-y-6">
                                    <h4 class="font-black text-slate-400 uppercase text-xs tracking-widest">Certificats & Examens</h4>

                                    <div class="field">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Seuil de réussite (%)</label>
                                        <InputNumber v-model="settings.lms.min_pass_score" suffix="%" class="w-full" showButtons :min="50" :max="100" />
                                    </div>

                                    <div class="field">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Préfixe Certificat</label>
                                        <InputText v-model="settings.lms.certificate_prefix" class="w-full font-mono uppercase" />
                                    </div>

                                    <Divider />

                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                                            <i class="pi pi-verified text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-800 block">Génération PDF active</span>
                                            <p class="text-[10px] text-slate-400">Modèle standard v.2026</p>
                                        </div>
                                        <Button icon="pi pi-external-link" class="p-button-text p-button-secondary ml-auto" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 3: LOCALIZATION -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-globe"></i>
                                <span>Localisation</span>
                            </div>
                        </template>

                        <div class="p-8 grid grid-cols-12 gap-8">
                            <div class="col-span-12 lg:col-span-6 space-y-6">
                                <div class="field">
                                    <label class="block text-xs font-black text-slate-400 uppercase mb-3">Langue par défaut du système</label>
                                    <Dropdown v-model="settings.localization.default_language" :options="languages" optionLabel="name" optionValue="code" class="w-full rounded-xl py-2" />
                                </div>

                                <div class="field">
                                    <label class="block text-xs font-black text-slate-400 uppercase mb-3">Fuseau Horaire (Timezone)</label>
                                    <Dropdown v-model="settings.localization.timezone" :options="timezones" class="w-full rounded-xl py-2" filter />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-3">Devise Principale</label>
                                        <Dropdown v-model="settings.localization.currency" :options="currencies" class="w-full rounded-xl py-2" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-3">Symbole</label>
                                        <InputText v-model="settings.localization.currency_symbol" class="w-full rounded-xl font-bold text-center py-3" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 lg:col-span-6 bg-slate-50 p-8 rounded-[2rem] flex flex-col justify-center items-center text-center">
                                <div class="text-4xl font-black text-slate-300 mb-4 opacity-50">
                                    {{ new Date().toLocaleDateString(settings.localization.default_language) }}
                                </div>
                                <h4 class="text-xl font-black text-slate-800">Aperçu du Format</h4>
                                <p class="text-slate-500 max-w-xs mt-2">Ce format sera utilisé pour toutes les factures, dates de cours et rapports.</p>
                                <div class="mt-8 text-2xl font-mono font-bold text-indigo-600 bg-white px-6 py-3 rounded-2xl shadow-sm">
                                    1 250,50 {{ settings.localization.currency_symbol }}
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 4: MAIL SERVER -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-envelope"></i>
                                <span>Serveur Mail</span>
                            </div>
                        </template>

                        <div class="p-8 grid grid-cols-12 gap-8">
                            <div class="col-span-12 lg:col-span-7 space-y-6">
                                <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-send text-indigo-500"></i>
                                    Configuration SMTP
                                </h3>

                                <div class="field">
                                    <label class="block text-xs font-black text-slate-400 uppercase mb-2">Driver de messagerie</label>
                                    <SelectButton v-model="settings.mail.driver" :options="mailDrivers" optionLabel="label" optionValue="value" class="custom-select-btn" />
                                </div>

                                <div class="grid grid-cols-12 gap-4">
                                    <div class="col-span-9 field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-2">Hôte SMTP</label>
                                        <InputText v-model="settings.mail.host" class="w-full rounded-xl font-mono" />
                                    </div>
                                    <div class="col-span-3 field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-2">Port</label>
                                        <InputNumber v-model="settings.mail.port" class="w-full" :useGrouping="false" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-2">Identifiant</label>
                                        <InputText v-model="settings.mail.username" class="w-full rounded-xl" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-xs font-black text-slate-400 uppercase mb-2">Mot de passe</label>
                                        <Password v-model="settings.mail.password" :feedback="false" toggleMask class="w-full rounded-xl" inputClass="w-full" />
                                    </div>
                                </div>

                                <div class="flex gap-4 p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                                    <i class="pi pi-info-circle text-indigo-500 mt-1"></i>
                                    <p class="text-xs text-indigo-700 font-medium leading-relaxed">
                                        Pour des raisons de délivrabilité, nous recommandons l'utilisation d'un service tiers comme <b>SendGrid</b> ou <b>Postmark</b>. Évitez les SMTP classiques des hébergements mutualisés.
                                    </p>
                                </div>
                            </div>

                            <div class="col-span-12 lg:col-span-5 space-y-6">
                                <Card class="border-2 border-slate-100 rounded-[2rem] shadow-none">
                                    <template #title><span class="text-base font-black px-4 pt-2 block">Test de Connexion</span></template>
                                    <template #content>
                                        <div class="p-4 bg-slate-50 rounded-2xl mb-6">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Destinataire test</span>
                                            <InputText placeholder="votre-email@gmail.com" class="w-full border-none shadow-sm mb-4" />
                                            <Button label="Envoyer un email de test" icon="pi pi-bolt" class="p-button-secondary p-button-sm w-full font-bold" />
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-slate-400 px-4">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                            <span>Service mail opérationnel</span>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 5: SECURITY -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-shield"></i>
                                <span>Sécurité</span>
                            </div>
                        </template>

                        <div class="p-8 max-w-4xl space-y-10">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-black text-slate-800">Authentification & Accès</h3>
                                    <p class="text-slate-500 font-medium">Configurez les barrières de sécurité de la plateforme</p>
                                </div>
                                <div class="flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl">
                                    <span class="text-[10px] font-black uppercase">Score de Santé</span>
                                    <span class="text-lg font-black" :class="siteHealth > 80 ? 'text-emerald-400' : 'text-amber-400'">{{ siteHealth }}%</span>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="flex justify-between items-center p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-indigo-200 transition-colors">
                                    <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                                            <i class="pi pi-user-plus text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-800 text-lg block">Inscriptions publiques</span>
                                            <p class="text-sm text-slate-400 font-medium">Autoriser les nouveaux élèves à créer un compte</p>
                                        </div>
                                    </div>
                                    <InputSwitch v-model="settings.security.enable_registration" />
                                </div>

                                <div class="flex justify-between items-center p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-rose-200 transition-colors">
                                    <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center">
                                            <i class="pi pi-lock text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-800 text-lg block">Double Authentification (2FA)</span>
                                            <p class="text-sm text-slate-400 font-medium">Forcer tous les administrateurs à utiliser le 2FA</p>
                                        </div>
                                    </div>
                                    <InputSwitch v-model="settings.security.force_2fa" />
                                </div>

                                <div class="flex justify-between items-center p-6 bg-white border-2 border-slate-100 rounded-3xl hover:border-amber-200 transition-colors">
                                    <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                                            <i class="pi pi-shield text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-800 text-lg block">Protection Captcha</span>
                                            <p class="text-sm text-slate-400 font-medium">Google reCaptcha sur les formulaires de login/inscription</p>
                                        </div>
                                    </div>
                                    <InputSwitch v-model="settings.security.enable_captcha" />
                                </div>
                            </div>

                            <div class="p-8 bg-slate-50 rounded-[2rem] border border-slate-100">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Politique de mots de passe</h4>
                                <div class="grid grid-cols-2 gap-8">
                                    <div class="field">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Longueur minimale</label>
                                        <InputNumber v-model="settings.security.password_min_length" showButtons :min="6" :max="20" class="w-full" />
                                    </div>
                                    <div class="field">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Temps de session (minutes)</label>
                                        <InputNumber v-model="settings.security.session_timeout" showButtons :min="15" suffix=" min" class="w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- TAB 6: ADVANCED & MAINTENANCE -->
                    <TabPanel>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-database"></i>
                                <span>Avancé</span>
                            </div>
                        </template>

                        <div class="p-8 max-w-4xl space-y-12">
                            <div class="bg-rose-50 border-2 border-rose-100 p-8 rounded-[2.5rem]">
                                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                                    <div class="flex items-center gap-5">
                                        <div class="w-16 h-16 bg-rose-500 text-white rounded-3xl flex items-center justify-center shadow-lg shadow-rose-200">
                                            <i class="pi pi-exclamation-circle text-3xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black text-rose-900">Zone de Danger</h3>
                                            <p class="text-rose-700 font-medium">Actions irréversibles sur l'état du système</p>
                                        </div>
                                    </div>
                                    <Button label="Mode Maintenance"
                                            :icon="settings.advanced.maintenance_mode ? 'pi pi-lock' : 'pi pi-unlock'"
                                            :class="settings.advanced.maintenance_mode ? 'p-button-danger' : 'p-button-outlined p-button-danger border-2'"
                                            class="font-black px-6 py-3"
                                            @click="confirmMaintenance" />
                                </div>
                                <p class="mt-6 text-sm text-rose-800 leading-relaxed italic" v-if="settings.advanced.maintenance_mode">
                                    Attention : Le mode maintenance est actuellement <b>ACTIF</b>. Seuls les administrateurs avec accès direct à la base de données peuvent désactiver ce mode si l'interface est bloquée.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-8">
                                <Card class="border-2 border-slate-100 rounded-3xl shadow-none">
                                    <template #title><span class="text-base font-black px-2 pt-2 block">Nettoyage Système</span></template>
                                    <template #content>
                                        <p class="text-sm text-slate-500 mb-6">Videz les fichiers temporaires et les caches d'application pour libérer de la RAM.</p>
                                        <Button label="Purger le Cache Redis" icon="pi pi-trash" class="p-button-outlined p-button-secondary w-full font-bold" @click="clearCache" :loading="loading" />
                                    </template>
                                </Card>

                                <Card class="border-2 border-slate-100 rounded-3xl shadow-none">
                                    <template #title><span class="text-base font-black px-2 pt-2 block">Suivi Analytics</span></template>
                                    <template #content>
                                        <div class="field mb-0">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">Google Analytics ID</label>
                                            <InputText v-model="settings.advanced.analytics_id" placeholder="UA-XXXXX-Y" class="w-full font-mono bg-slate-50 border-none shadow-sm" />
                                        </div>
                                    </template>
                                </Card>
                            </div>

                            <div class="p-6 bg-slate-900 rounded-3xl text-white flex justify-between items-center">
                                <div class="flex items-center gap-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase text-slate-500">Version du Noyau</span>
                                        <span class="font-black text-xl">LMS Core v5.2.4</span>
                                    </div>
                                    <Badge value="Stable" severity="success" />
                                </div>
                                <Button label="Vérifier les mises à jour" icon="pi pi-cloud-download" class="p-button-text p-button-white font-bold" />
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>

            <!-- FOOTER INFO -->
            <footer class="mt-12 text-center text-slate-400 text-sm pb-8 font-medium">
                © 2026 SubNetCongo - Panneau d'Administration Centralisé.
            </footer>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Overrides PrimeVue pour look SubNetCongo Settings */
:deep(.p-tabview-nav) {
    background: #fff;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 2rem;
}

:deep(.p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.1em;
    color: #94a3b8;
    padding: 2rem 1.5rem;
    transition: all 0.3s;
}

:deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366f1 !important;
    border-bottom: 3px solid #6366f1 !important;
}

:deep(.p-tabview-panels) {
    background: transparent;
    padding: 0;
}

:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background: #6366f1;
}

:deep(.p-colorpicker-preview) {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
}

:deep(.custom-select-btn .p-button) {
    background: #fff;
    border: 2px solid #f1f5f9;
    color: #64748b;
    font-weight: 700;
    font-size: 0.8rem;
}

:deep(.custom-select-btn .p-button.p-highlight) {
    background: #f5f3ff;
    border-color: #6366f1;
    color: #6366f1;
}

/* Transitions */
.p-button-indigo {
    background: #6366f1;
    border: none;
    color: white;
}

.p-button-indigo:hover {
    background: #4f46e5;
}

/* Scroll Panel UI */
:deep(.p-scrollpanel-bar) {
    background: #cbd5e1;
    opacity: 1;
}

/* File Upload Custom */
:deep(.p-fileupload-basic) {
    display: block;
}
</style>
