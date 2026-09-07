<script setup>
/**
 * ======================================================================================
 * SUBNETCONGO LMS - SYSTEM ARCHITECT & CONFIGURATION UI v40.0
 * ======================================================================================
 * Interface Ultimate : Design épuré, UX optimisée, Sticky Header.
 * Technologies: Vue 3, Inertia.js, PrimeVue 4, Tailwind CSS.
 */

import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed } from 'vue';
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
import SelectButton from 'primevue/selectbutton';
import InlineMessage from 'primevue/inlinemessage';
import Password from 'primevue/password';
import Badge from 'primevue/badge';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

// --- RÉCEPTION DES PROPS ---
const props = defineProps({
    settings: Object,
    dictionaries: Object,
    auth: Object,
    errors: Object
});

// --- SERVICES ---
const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

// --- ETATS UI ---
const activeTab = ref(0);
const loading = ref(false);
const testEmailDialog = ref(false);
const testEmailAddress = ref('');
const isSubmitting = ref(false);

// --- FORMULAIRE INERTIA ---
const form = useForm({
    general: { ...props.settings.general },
    branding: { ...props.settings.branding },
    lms: { ...props.settings.lms },
    localization: { ...props.settings.localization },
    mail: { ...props.settings.mail },
    security: { ...props.settings.security },
    advanced: { ...props.settings.advanced }
});

// --- MÉTHODES ---
const submitSettings = () => {
    isSubmitting.value = true;
    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            toast.add({ severity: 'success', summary: 'Système mis à jour', detail: 'La configuration a été propagée avec succès.', life: 3000 });
        },
        onError: () => {
            isSubmitting.value = false;
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Vérifiez les champs en surbrillance.', life: 4000 });
        }
    });
};

const resetToDefaults = () => {
    confirm.require({
        message: 'Voulez-vous vraiment annuler toutes les modifications non enregistrées ?',
        header: 'Confirmer l\'annulation',
        icon: 'pi pi-exclamation-circle',
        accept: () => {
            form.reset();
            toast.add({ severity: 'info', summary: 'Réinitialisé', detail: 'Modifications annulées.', life: 3000 });
        }
    });
};

const toggleMaintenance = (event) => {
    const action = form.advanced.maintenance_mode ? 'désactiver' : 'activer';
    confirm.require({
        target: event.currentTarget,
        message: `Voulez-vous vraiment ${action} le mode maintenance ?`,
        header: 'Alerte de Disponibilité',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(route('settings.maintenance.toggle'), {}, {
                onSuccess: () => {
                    form.advanced.maintenance_mode = !form.advanced.maintenance_mode;
                    toast.add({ severity: form.advanced.maintenance_mode ? 'warn' : 'success', summary: 'Maintenance', detail: `Système ${form.advanced.maintenance_mode ? 'hors ligne' : 'en ligne'}.`, life: 3000 });
                }
            });
        }
    });
};

const clearCache = () => {
    loading.value = true;
    router.post(route('settings.cache.clear'), {}, {
        onSuccess: () => { loading.value = false; toast.add({ severity: 'success', summary: 'Cache Purgé', detail: 'Les performances sont optimales.', life: 3000 }); },
        onFinish: () => loading.value = false
    });
};

const sendTestEmail = () => {
    if (!testEmailAddress.value) return;
    isSubmitting.value = true;
    router.post(route('system.settings.mail.test'), { email: testEmailAddress.value }, {
        onSuccess: () => { isSubmitting.value = false; testEmailDialog.value = false; toast.add({ severity: 'success', summary: 'Email envoyé', detail: 'Vérifiez votre boîte de réception.', life: 3000 }); },
        onError: () => { isSubmitting.value = false; toast.add({ severity: 'error', summary: 'Échec SMTP', detail: 'Vérifiez vos identifiants.', life: 4000 }); }
    });
};

const onLogoUpload = (event) => {
    router.post(route('settings.logo.upload'), { logo: event.files[0] }, {
        forceFormData: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Upload réussi', detail: 'Le logo a été mis à jour.', life: 3000 })
    });
};

const siteHealth = computed(() => {
    let score = 100;
    if (!form.security.force_2fa) score -= 15;
    if (form.advanced.debug_mode) score -= 25;
    if (!form.security.enable_captcha) score -= 10;
    if (form.mail.driver === 'smtp' && !form.mail.host) score -= 10;
    return score;
});

const healthSeverity = computed(() => siteHealth.value > 80 ? 'success' : siteHealth.value > 50 ? 'warning' : 'danger');
</script>

<template>
    <AppLayout>
        <Head title="Configuration Système" />
        <Toast />
        <ConfirmDialog />

        <!-- DIALOG TEST EMAIL -->
        <Dialog v-model:visible="testEmailDialog" modal header="Test de connexion SMTP" :style="{ width: '400px' }">
            <div class="pt-4 space-y-4">
                <p class="text-sm text-slate-500 leading-relaxed">Saisissez une adresse e-mail pour vérifier la délivrabilité de vos paramètres actuels.</p>
                <div class="field">
                    <label class="text-sm font-semibold text-slate-700 block mb-2">Email destinataire</label>
                    <InputText v-model="testEmailAddress" placeholder="admin@domaine.com" class="w-full !rounded-lg" />
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" class="p-button-text p-button-secondary" @click="testEmailDialog = false" />
                <Button label="Envoyer le test" icon="pi pi-send" class="p-button-primary !rounded-lg" :loading="isSubmitting" @click="sendTestEmail" />
            </template>
        </Dialog>

        <div class="min-h-screen bg-slate-50/50 pb-20">

            <!-- --- STICKY HEADER (Le cœur de la nouvelle UX) --- -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl shadow-inner flex items-center justify-center">
                        <i class="pi pi-cog text-white text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Paramètres Globaux</h1>
                            <Badge value="v40.0" severity="info" class="!text-xs !px-2" />
                        </div>
                        <p class="text-sm text-slate-500 font-medium mt-0.5">Configuration du moteur ERP & LMS</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button label="Annuler" icon="pi pi-refresh" class="p-button-text p-button-secondary font-bold" @click="resetToDefaults" :disabled="isSubmitting" />
                    <Button :label="isSubmitting ? 'Enregistrement...' : 'Enregistrer'"
                            :icon="isSubmitting ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
                            class="p-button-primary !px-6 !py-2.5 !rounded-xl font-bold shadow-md hover:shadow-lg transition-all"
                            @click="submitSettings"
                            :disabled="isSubmitting" />
                </div>
            </header>

            <!-- --- SECTION PRINCIPALE --- -->
            <div class="max-w-7xl mx-auto px-6 mt-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <TabView v-model:activeIndex="activeTab" class="ultimate-tabs">

                        <!-- --- TAB 1: IDENTITÉ & BRANDING --- -->
                        <TabPanel>
                            <template #header><i class="pi pi-palette mr-2"></i> Branding</template>
                            <div class="p-8 lg:p-12 space-y-12">
                                
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                                    <div class="col-span-1 lg:col-span-2 space-y-8">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Identité de l'Organisation</h3>
                                            <p class="text-sm text-slate-500">Ces informations seront visibles sur les factures et communications.</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                                            <div class="field">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Nom de l'organisation</label>
                                                <InputText v-model="form.general.site_name" class="w-full !rounded-lg" placeholder="Ex: SubNetCongo" />
                                            </div>
                                            <div class="field">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Slogan</label>
                                                <InputText v-model="form.general.site_slogan" class="w-full !rounded-lg" />
                                            </div>
                                            <div class="field">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Email de contact</label>
                                                <InputGroup class="!rounded-lg overflow-hidden">
                                                    <InputGroupAddon><i class="pi pi-envelope"></i></InputGroupAddon>
                                                    <InputText v-model="form.general.contact_email" />
                                                </InputGroup>
                                            </div>
                                            <div class="field">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Téléphone</label>
                                                <InputGroup class="!rounded-lg overflow-hidden">
                                                    <InputGroupAddon><i class="pi pi-phone"></i></InputGroupAddon>
                                                    <InputText v-model="form.general.phone" />
                                                </InputGroup>
                                            </div>
                                            <div class="field md:col-span-2">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Adresse du siège</label>
                                                <Textarea v-model="form.general.address" rows="3" class="w-full !rounded-lg" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- UI Branding -->
                                    <div class="col-span-1 space-y-8">
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Thème Visuel</h3>
                                            <p class="text-sm text-slate-500">Couleurs et logotype.</p>
                                        </div>

                                        <div class="bg-white border border-slate-200 p-6 rounded-2xl shadow-sm space-y-6">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-semibold text-slate-700">Couleur Primaire</span>
                                                <ColorPicker v-model="form.branding.primary_color" />
                                            </div>
                                            <Divider class="!my-2" />
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-semibold text-slate-700">Couleur Secondaire</span>
                                                <ColorPicker v-model="form.branding.secondary_color" />
                                            </div>
                                            
                                            <div class="pt-4">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-4 text-center">Logo Officiel</label>
                                                <div class="border-2 border-dashed border-slate-300 bg-slate-50 rounded-xl p-6 flex flex-col items-center justify-center gap-4 hover:border-indigo-400 transition-colors">
                                                    <img :src="form.branding.logo_url" class="h-12 object-contain" alt="Logo" />
                                                    <FileUpload mode="basic" name="logo" @uploader="onLogoUpload" customUpload :auto="true" chooseLabel="Changer" class="p-button-outlined p-button-sm !rounded-lg" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- --- TAB 2: MOTEUR LMS --- -->
                        <TabPanel>
                            <template #header><i class="pi pi-graduation-cap mr-2"></i> Moteur LMS</template>
                            <div class="p-8 lg:p-12">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                    
                                    <!-- Automation -->
                                    <div class="space-y-6">
                                        <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-6">Automatisation & Accès</h3>
                                        
                                        <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition-colors">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Auto-inscription post-paiement</span>
                                                <p class="text-xs text-slate-500 mt-1">L'élève accède au cours dès validation financière.</p>
                                            </div>
                                            <InputSwitch v-model="form.lms.auto_enroll_on_payment" />
                                        </div>

                                        <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition-colors">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Mode Aperçu (Preview)</span>
                                                <p class="text-xs text-slate-500 mt-1">Les non-inscrits peuvent voir les modules gratuits.</p>
                                            </div>
                                            <InputSwitch v-model="form.lms.allow_course_preview" />
                                        </div>

                                        <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition-colors">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Filigrane Vidéo (Anti-piratage)</span>
                                                <p class="text-xs text-slate-500 mt-1">Incruste l'ID de l'élève sur le lecteur vidéo.</p>
                                            </div>
                                            <InputSwitch v-model="form.lms.video_watermark" />
                                        </div>
                                    </div>

                                    <!-- Diplômes -->
                                    <div class="space-y-6">
                                        <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-6">Certification</h3>
                                        
                                        <div class="bg-slate-900 p-8 rounded-2xl shadow-lg border border-slate-800 text-white space-y-6">
                                            <div class="flex justify-between items-center">
                                                <span class="font-bold block">Générateur PDF de Diplômes</span>
                                                <InputSwitch v-model="form.lms.enable_certificates" />
                                            </div>
                                            <Divider class="!border-slate-700" />
                                            
                                            <div class="field">
                                                <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">Score de validation minimum</label>
                                                <div class="flex items-center gap-4">
                                                    <InputNumber v-model="form.lms.min_pass_score" suffix="%" class="w-32" inputClass="!bg-slate-800 !border-slate-700 !text-white !font-bold !rounded-lg !p-3 text-center" />
                                                    <ProgressBar :value="form.lms.min_pass_score" :showValue="false" class="h-2 flex-1 !bg-slate-800" color="#6366f1" />
                                                </div>
                                            </div>

                                            <div class="field">
                                                <label class="block text-xs font-semibold text-slate-400 uppercase mb-2">Préfixe Matricule</label>
                                                <InputText v-model="form.lms.certificate_prefix" class="w-full !bg-slate-800 !border-slate-700 !text-white !font-mono !rounded-lg" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- --- TAB 4: SERVEUR MAIL --- -->
                        <TabPanel>
                            <template #header><i class="pi pi-send mr-2"></i> Messagerie</template>
                            <div class="p-8 lg:p-12">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                                    <div class="lg:col-span-8 space-y-8">
                                        <div class="flex justify-between items-center mb-6">
                                            <div>
                                                <h3 class="text-lg font-bold text-slate-900 tracking-tight">Configuration SMTP / API</h3>
                                                <p class="text-sm text-slate-500">Routeur de courriels sortants.</p>
                                            </div>
                                            <Button label="Tester SMTP" icon="pi pi-check-circle" class="p-button-outlined p-button-sm !rounded-lg font-bold" @click="testEmailDialog = true" />
                                        </div>

                                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 space-y-6">
                                            <div class="field">
                                                <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Pilote d'envoi (Driver)</label>
                                                <SelectButton v-model="form.mail.driver" :options="props.dictionaries.mailDrivers" optionLabel="label" optionValue="value" class="ultimate-select-btn" />
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                                <div class="md:col-span-3 field">
                                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Hôte (Host)</label>
                                                    <InputText v-model="form.mail.host" class="w-full !rounded-lg font-mono" />
                                                </div>
                                                <div class="field">
                                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Port</label>
                                                    <InputNumber v-model="form.mail.port" class="w-full" inputClass="!rounded-lg text-center" :useGrouping="false" />
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div class="field">
                                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Utilisateur (Username)</label>
                                                    <InputText v-model="form.mail.username" class="w-full !rounded-lg" />
                                                </div>
                                                <div class="field">
                                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-2">Mot de passe / Clé API</label>
                                                    <Password v-model="form.mail.password" :feedback="false" toggleMask class="w-full" inputClass="w-full !rounded-lg" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-4 space-y-6">
                                        <div class="bg-amber-50 p-6 rounded-2xl border border-amber-200">
                                            <div class="flex gap-3 mb-2">
                                                <i class="pi pi-shield text-amber-600 mt-0.5"></i>
                                                <h4 class="font-bold text-amber-900 text-sm">Chiffrement SSL/TLS</h4>
                                            </div>
                                            <p class="text-xs text-amber-800 leading-relaxed mb-4">Pour éviter le blacklistage IP, il est fortement recommandé d'utiliser une connexion chiffrée (TLS 1.2+).</p>
                                            <SelectButton v-model="form.mail.encryption" :options="['tls', 'ssl', 'none']" class="ultimate-select-btn !w-full" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- --- TAB 5: SÉCURITÉ --- -->
                        <TabPanel>
                            <template #header><i class="pi pi-lock mr-2"></i> Sécurité</template>
                            <div class="p-8 lg:p-12">
                                <div class="flex items-center justify-between mb-10 bg-slate-900 p-6 rounded-2xl text-white">
                                    <div>
                                        <h3 class="text-lg font-bold tracking-tight">Poste de Commandement Cybersécurité</h3>
                                        <p class="text-sm text-slate-400">Verrouillage des accès au cœur du système.</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-slate-400 uppercase mb-1">Health Score</div>
                                        <div class="flex items-center gap-4">
                                            <ProgressBar :value="siteHealth" :showValue="false" class="h-2 w-32 !bg-slate-700" :color="siteHealth > 80 ? '#10b981' : '#f59e0b'" />
                                            <span class="text-2xl font-black" :class="siteHealth > 80 ? 'text-emerald-400' : 'text-amber-400'">{{ siteHealth }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Forcer 2FA (Admins)</span>
                                                <p class="text-xs text-slate-500 mt-1">Obligatoire pour les rôles à privilèges.</p>
                                            </div>
                                            <InputSwitch v-model="form.security.force_2fa" />
                                        </div>
                                        <Divider />
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Protection Captcha</span>
                                                <p class="text-xs text-slate-500 mt-1">Bouclier anti-bots sur les formulaires publics.</p>
                                            </div>
                                            <InputSwitch v-model="form.security.enable_captcha" />
                                        </div>
                                        <Divider />
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="font-bold text-slate-800 block">Inscriptions Ouvertes</span>
                                                <p class="text-xs text-slate-500 mt-1">Autoriser la création de comptes élèves.</p>
                                            </div>
                                            <InputSwitch v-model="form.security.enable_registration" />
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                                            <label class="block text-sm font-bold text-slate-800 mb-2">Restriction IP (Liste Blanche Admin)</label>
                                            <p class="text-xs text-slate-500 mb-4">Laissez vide pour désactiver. Séparer par des virgules.</p>
                                            <InputText v-model="form.security.ip_whitelist" placeholder="192.168.1.1, 10.0.0.1" class="w-full !rounded-lg font-mono text-sm" />
                                        </div>
                                        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center gap-4">
                                            <label class="block text-sm font-bold text-slate-800 w-1/2">Expiration de Session (min)</label>
                                            <InputNumber v-model="form.security.session_timeout" showButtons :min="15" :step="15" class="w-1/2" inputClass="!rounded-lg text-center font-bold" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- --- TAB 6: MAINTENANCE (DANGER ZONE) --- -->
                        <TabPanel>
                            <template #header><i class="pi pi-exclamation-triangle text-rose-500 mr-2"></i> Système</template>
                            <div class="p-8 lg:p-12 space-y-10">
                                
                                <!-- Zone Danger -->
                                <div class="bg-white border border-rose-200 rounded-3xl p-8 lg:p-10 shadow-sm relative overflow-hidden">
                                    <div class="absolute top-0 left-0 w-2 h-full bg-rose-500"></div>
                                    
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                                        <div>
                                            <h3 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                                                Mode Maintenance
                                                <Tag v-if="form.advanced.maintenance_mode" value="ACTIF" severity="danger" class="!font-bold !px-3" />
                                            </h3>
                                            <p class="text-sm text-slate-600 mt-2 max-w-xl leading-relaxed">
                                                Verrouille l'accès public au site (Erreur 503). Seuls les administrateurs connectés pourront naviguer sur la plateforme. Utilisez ce mode uniquement pour les mises à jour critiques.
                                            </p>
                                        </div>
                                        <Button :label="form.advanced.maintenance_mode ? 'Désactiver la maintenance' : 'Activer le mode maintenance'"
                                                :icon="form.advanced.maintenance_mode ? 'pi pi-unlock' : 'pi pi-lock'"
                                                :class="form.advanced.maintenance_mode ? 'p-button-secondary' : 'p-button-danger'"
                                                class="!font-bold !px-6 !py-3 !rounded-xl whitespace-nowrap"
                                                @click="toggleMaintenance" />
                                    </div>
                                </div>

                                <!-- Utilitaires -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 flex flex-col justify-between">
                                        <div>
                                            <h4 class="font-bold text-slate-900 mb-2"><i class="pi pi-eraser mr-2"></i> Purge du Cache Serveur</h4>
                                            <p class="text-xs text-slate-500 leading-relaxed mb-6">Recompile les vues Blade, vide les routes en cache et nettoie le cache applicatif Redis/Fichier.</p>
                                        </div>
                                        <Button label="Nettoyer le Cache" class="p-button-outlined p-button-secondary !rounded-lg w-full font-bold" @click="clearCache" :loading="loading" />
                                    </div>
                                </div>
                                
                            </div>
                        </TabPanel>

                    </TabView>
                </div>
            </div>
            
            <footer class="text-center mt-12 pb-6 text-xs text-slate-400 font-medium">
                V40 Ultimate Edition — Conçu par SubNetCongo Infrastructure
            </footer>
        </div>
    </AppLayout>
</template>

<style scoped>
/* 
 * ULTIMATE TABS DESIGN 
 * Style moderne type SaaS (Stripe/Vercel)
 */
:deep(.ultimate-tabs .p-tabview-nav) {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0 1rem;
    gap: 1rem;
}

:deep(.ultimate-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    font-weight: 600;
    font-size: 0.875rem;
    color: #64748b;
    padding: 1.25rem 1rem;
    position: relative;
    transition: color 0.2s ease;
}

:deep(.ultimate-tabs .p-tabview-nav li:not(.p-highlight) .p-tabview-nav-link:hover) {
    color: #0f172a;
}

:deep(.ultimate-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #4f46e5 !important;
}

:deep(.ultimate-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link::after) {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #4f46e5;
    border-radius: 2px 2px 0 0;
}

:deep(.ultimate-tabs .p-tabview-panels) {
    padding: 0;
    background: transparent;
}

/*
 * ULTIMATE SELECT BUTTONS
 */
:deep(.ultimate-select-btn .p-button) {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease;
}

:deep(.ultimate-select-btn .p-button.p-highlight) {
    background: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff;
}

:deep(.ultimate-select-btn .p-button:not(.p-highlight):hover) {
    background: #f8fafc;
}
</style>