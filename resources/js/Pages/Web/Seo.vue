<script setup>
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { ref, computed, onMounted, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from "primevue/usetoast";

// --- PRIME VUE IMPORTS ---
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import SelectButton from 'primevue/selectbutton';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Chip from 'primevue/chip';
import Avatar from 'primevue/avatar';
import Panel from 'primevue/panel';
import PickList from 'primevue/picklist';
import ScrollPanel from 'primevue/scrollpanel';
import Breadcrumb from 'primevue/breadcrumb';
import Fieldset from 'primevue/fieldset';
import Badge from 'primevue/badge';

const toast = useToast();

// --- BREADCRUMB ---
const breadcrumbHome = { icon: 'pi pi-home', to: '/' };
const breadcrumbItems = ref([
    { label: 'Site Web' },
    { label: 'Configuration SEO', class: 'font-bold text-indigo-600' }
]);

// --- STATE MANAGEMENT (DONNÉES SEO) ---
const seoSettings = reactive({
    global: {
        siteName: 'SubnetCongo LMS',
        titleTemplate: '%page_title% | SubnetCongo',
        defaultDescription: 'La plateforme de e-learning leader en RDC pour les technologies de demain.',
        keywords: 'formation, congo, lms, e-learning, kinshasa, technologie, développement, subnet',
        favicon: null,
        language: 'fr_FR',
        canonicalUrl: 'https://subnetcongo.cd'
    },
    social: {
        fbAppId: '1234567890',
        twitterHandle: '@SubnetCongo',
        defaultOgImage: 'https://subnetcongo.cd/og-default.jpg',
        ogType: 'website',
        twitterCard: 'summary_large_image'
    },
    verification: {
        google: 'g-verification-code-123',
        bing: 'bing-code-456',
        pinterest: '',
        yandex: ''
    },
    indexing: {
        autoSitemap: true,
        sitemapFrequency: 'daily',
        robotsTxt: 'User-agent: *\nAllow: /\nDisallow: /admin/\nSitemap: https://subnetcongo.cd/sitemap.xml',
        noIndexAdmin: true
    },
    scripts: {
        // CORRECTION ICI : On utilise la concaténation pour éviter de fermer la balise <script> du fichier Vue
        header: '<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':\nnew Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=\n\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,\'script\',\'dataLayer\',\'GTM-XXXX\');<' + '/script>',
        footer: '<!-- Meta Pixel -->',
        analyticsId: 'G-XXXXXXX'
    }
});

// --- STATE: REDIRECTIONS ---
const redirects = ref([
    { id: 1, oldUrl: '/anciens-cours', newUrl: '/courses', type: '301', hits: 450, status: 'Active' },
    { id: 2, oldUrl: '/promo-2023', newUrl: '/offers', type: '302', hits: 120, status: 'Active' },
    { id: 3, oldUrl: '/contact-us', newUrl: '/contact', type: '301', hits: 89, status: 'Inactive' }
]);

const redirectDialog = ref(false);
const newRedirect = ref({ oldUrl: '', newUrl: '', type: '301' });

// --- STATE: SEO AUDIT (SIMULATION) ---
const auditProgress = ref(0);
const isAuditing = ref(false);
const auditResults = ref([
    { id: 1, label: 'Certificat SSL (HTTPS)', status: 'success', msg: 'Votre site est sécurisé.' },
    { id: 2, label: 'Balises Title manquantes', status: 'warning', msg: '3 pages n\'ont pas de titre unique.' },
    { id: 3, label: 'Images sans attribut ALT', status: 'danger', msg: '12 images ralentissent votre SEO.' },
    { id: 4, label: 'Vitesse de chargement (LCP)', status: 'success', msg: 'Excellent (1.2s)' },
    { id: 5, label: 'Fichier Sitemap.xml', status: 'success', msg: 'Présent et valide.' }
]);

// --- PREVIEW COMPUTED ---
const googlePreviewTitle = computed(() => {
    return seoSettings.global.titleTemplate.replace('%page_title%', 'Titre de votre Page');
});

// --- METHODS ---
const saveSeoSettings = () => {
    toast.add({ severity: 'success', summary: 'Configuration mise à jour', detail: 'Les moteurs de recherche seront notifiés du changement.', life: 3000 });
};

const runAudit = () => {
    if (isAuditing.value) return;

    isAuditing.value = true;
    auditProgress.value = 0;

    const interval = setInterval(() => {
        auditProgress.value += 10;
        if (auditProgress.value >= 100) {
            clearInterval(interval);
            isAuditing.value = false;
            toast.add({ severity: 'info', summary: 'Audit Terminé', detail: '85% de score SEO global.', life: 3000 });
        }
    }, 200);
};

const addRedirect = () => {
    if (!newRedirect.value.oldUrl || !newRedirect.value.newUrl) return;

    redirects.value.push({
        ...newRedirect.value,
        id: Date.now(),
        hits: 0,
        status: 'Active'
    });

    redirectDialog.value = false;
    newRedirect.value = { oldUrl: '', newUrl: '', type: '301' };
    toast.add({ severity: 'success', summary: 'Redirection ajoutée', life: 2000 });
};

const deleteRedirect = (id) => {
    redirects.value = redirects.value.filter(r => r.id !== id);
    toast.add({ severity: 'warn', summary: 'Supprimé', detail: 'La redirection a été retirée.', life: 2000 });
};

const copySitemapUrl = () => {
    const url = seoSettings.global.canonicalUrl + '/sitemap.xml';
    navigator.clipboard.writeText(url).then(() => {
        toast.add({ severity: 'info', summary: 'Copié !', detail: 'URL du sitemap dans le presse-papier', life: 2000 });
    });
};
</script>

<template>
    <AppLayout>
        <Head title="SEO & Meta-Tags Management" />
        <Toast />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">

            <!-- --- HEADER --- -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex flex-col gap-2">
                    <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="bg-transparent p-0 border-none text-xs" />
                    <div class="flex items-center gap-4">
                        <div class="bg-indigo-600 p-4 rounded-3xl shadow-xl shadow-indigo-100">
                            <i class="pi pi-google text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight italic">SEO Power Center</h1>
                            <p class="text-slate-500 font-medium">Optimisez la visibilité de <span class="text-indigo-600 font-bold">SubnetCongo</span> sur le web.</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button label="Lancer un Audit" icon="pi pi-bolt" class="p-button-outlined p-button-secondary bg-white px-6 rounded-2xl" @click="runAudit" :loading="isAuditing" />
                    <Button label="Appliquer les changements" icon="pi pi-save" class="p-button-primary shadow-lg shadow-indigo-200 px-6 rounded-2xl" @click="saveSeoSettings" />
                </div>
            </header>

            <!-- --- KPI SECTION --- -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Score SEO</span>
                        <Badge value="85/100" severity="success" />
                    </div>
                    <div class="flex items-end gap-2">
                        <h2 class="text-3xl font-black text-slate-800">Excellent</h2>
                        <i class="pi pi-arrow-up-right text-emerald-500 mb-1"></i>
                    </div>
                    <ProgressBar :value="85" :showValue="false" style="height: 6px" class="mt-4" />
                </div>

                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pages Indexées</span>
                        <i class="pi pi-eye text-indigo-500"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800">1,420</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-2">Source : Google Search Console</p>
                </div>

                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trafic Organique</span>
                        <Tag value="+12%" severity="success" />
                    </div>
                    <h2 class="text-3xl font-black text-slate-800">42.5k</h2>
                    <p class="text-[10px] text-slate-400 font-bold mt-2">Visiteurs ce mois-ci</p>
                </div>

                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Erreurs 404</span>
                        <Badge value="3" severity="danger" />
                    </div>
                    <h2 class="text-3xl font-black text-slate-800">24</h2>
                    <Button label="Réparer maintenant" class="p-button-text p-button-sm p-0 mt-2 text-rose-500 font-bold" />
                </div>
            </div>

            <!-- --- MAIN CONFIGURATION --- -->
            <div class="grid grid-cols-12 gap-8">

                <!-- NAVIGATION TABS -->
                <div class="col-span-12 lg:col-span-9">
                    <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                        <TabView class="custom-seo-tabs">

                            <!-- TAB 1: GENERAL & META -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-info-circle"></i>
                                        <span>Identité & Meta</span>
                                    </div>
                                </template>

                                <div class="p-6 space-y-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-6">
                                            <div class="field">
                                                <label class="text-sm font-black text-slate-700 uppercase block mb-2">Nom du Site</label>
                                                <InputText v-model="seoSettings.global.siteName" class="w-full p-3 rounded-2xl border-slate-200" />
                                            </div>
                                            <div class="field">
                                                <label class="text-sm font-black text-slate-700 uppercase block mb-2">Structure du Titre (Template)</label>
                                                <InputText v-model="seoSettings.global.titleTemplate" class="w-full p-3 rounded-2xl border-slate-200" />
                                                <small class="text-slate-400">Utilisez <code>%page_title%</code> pour le titre dynamique.</small>
                                            </div>
                                        </div>
                                        <div class="space-y-6">
                                            <div class="field">
                                                <label class="text-sm font-black text-slate-700 uppercase block mb-2">URL Canonique (Base)</label>
                                                <InputText v-model="seoSettings.global.canonicalUrl" class="w-full p-3 rounded-2xl border-slate-200 font-mono text-indigo-600" />
                                            </div>
                                            <div class="field">
                                                <label class="text-sm font-black text-slate-700 uppercase block mb-2">Mots-clés par défaut</label>
                                                <Textarea v-model="seoSettings.global.keywords" rows="2" class="w-full p-3 rounded-2xl border-slate-200" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field">
                                        <label class="text-sm font-black text-slate-700 uppercase block mb-2">Description Meta par défaut</label>
                                        <Textarea v-model="seoSettings.global.defaultDescription" rows="3" class="w-full p-4 rounded-3xl border-slate-200" placeholder="Décrivez SubnetCongo en 160 caractères..." />
                                        <div class="flex justify-between mt-2">
                                            <small :class="seoSettings.global.defaultDescription.length > 160 ? 'text-rose-500' : 'text-slate-400'">
                                                {{ seoSettings.global.defaultDescription.length }} / 160 caractères
                                            </small>
                                            <small class="text-indigo-500 font-bold">Conseil : Incluez vos mots-clés principaux ici.</small>
                                        </div>
                                    </div>

                                    <Divider />

                                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white">
                                        <div class="flex items-center gap-2 mb-6">
                                            <i class="pi pi-google text-rose-400"></i>
                                            <span class="text-xs font-black uppercase tracking-widest text-slate-400">Aperçu du résultat Google</span>
                                        </div>
                                        <div class="max-w-xl">
                                            <p class="text-blue-400 text-xl font-medium hover:underline cursor-pointer mb-1">{{ googlePreviewTitle }}</p>
                                            <p class="text-emerald-500 text-sm mb-2">{{ seoSettings.global.canonicalUrl }} › accueil</p>
                                            <p class="text-slate-300 text-sm leading-relaxed">{{ seoSettings.global.defaultDescription }}</p>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 2: RÉSEAUX SOCIAUX -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-share-alt"></i>
                                        <span>Partage Social (OG)</span>
                                    </div>
                                </template>

                                <div class="p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                                        <div class="space-y-6">
                                            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                                                <i class="pi pi-facebook text-blue-600"></i> Open Graph (Facebook)
                                            </h3>
                                            <div class="field">
                                                <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Facebook App ID</label>
                                                <InputText v-model="seoSettings.social.fbAppId" class="w-full" />
                                            </div>
                                            <div class="field">
                                                <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Type de contenu par défaut</label>
                                                <Dropdown v-model="seoSettings.social.ogType" :options="['website', 'article', 'education']" class="w-full" />
                                            </div>

                                            <Divider />

                                            <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                                                <i class="pi pi-twitter text-sky-500"></i> X / Twitter Cards
                                            </h3>
                                            <div class="field">
                                                <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Nom d'utilisateur Twitter (@)</label>
                                                <InputText v-model="seoSettings.social.twitterHandle" class="w-full" />
                                            </div>
                                            <div class="field">
                                                <label class="text-xs font-bold text-slate-400 uppercase block mb-2">Style de carte Twitter</label>
                                                <SelectButton v-model="seoSettings.social.twitterCard" :options="['summary', 'summary_large_image']" />
                                            </div>
                                        </div>

                                        <div>
                                            <div class="bg-slate-50 border border-slate-200 rounded-[2.5rem] p-6">
                                                <h4 class="text-xs font-black text-slate-400 uppercase mb-4 tracking-widest">Image de partage par défaut</h4>
                                                <div class="aspect-video bg-white rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center overflow-hidden group relative">
                                                    <img v-if="seoSettings.social.defaultOgImage" :src="seoSettings.social.defaultOgImage" class="w-full h-full object-cover" />
                                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                        <Button icon="pi pi-upload" label="Changer l'image" class="p-button-sm" />
                                                    </div>
                                                    <div v-if="!seoSettings.social.defaultOgImage" class="text-center">
                                                        <i class="pi pi-image text-4xl text-slate-200 mb-2"></i>
                                                        <p class="text-xs text-slate-400 font-bold">1200 x 630 pixels recommandés</p>
                                                    </div>
                                                </div>
                                                <p class="text-xs text-slate-500 mt-4 italic">
                                                    Cette image s'affichera sur Facebook, LinkedIn et Twitter lorsqu'une page n'a pas d'image spécifique.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 3: INDEXATION & SITEMAP -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-sitemap"></i>
                                        <span>Indexation & Robots</span>
                                    </div>
                                </template>

                                <div class="p-6 space-y-8">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="flex items-center gap-3">
                                                    <i class="pi pi-map text-indigo-600 text-xl"></i>
                                                    <span class="font-bold text-indigo-900">Sitemap XML Automatique</span>
                                                </div>
                                                <InputSwitch v-model="seoSettings.indexing.autoSitemap" />
                                            </div>
                                            <p class="text-xs text-indigo-700 mb-4">Génère dynamiquement un plan du site pour Google à chaque nouvel article ou cours.</p>
                                            <div class="flex gap-2">
                                                <InputText readonly :value="seoSettings.global.canonicalUrl + '/sitemap.xml'" class="flex-1 text-xs border-indigo-200" />
                                                <Button icon="pi pi-copy" class="p-button-text p-button-indigo" @click="copySitemapUrl" />
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="flex items-center gap-3">
                                                    <i class="pi pi-shield text-slate-600 text-xl"></i>
                                                    <span class="font-bold text-slate-900">Visibilité Admin</span>
                                                </div>
                                                <InputSwitch v-model="seoSettings.indexing.noIndexAdmin" />
                                            </div>
                                            <p class="text-xs text-slate-500">Ajoute automatiquement une balise <code>noindex</code> sur toutes les pages du panneau d'administration pour éviter leur référencement.</p>
                                        </div>
                                    </div>

                                    <div class="field">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="text-sm font-black text-slate-700 uppercase">Éditeur Robots.txt</label>
                                            <Tag value="Avancé" severity="warning" />
                                        </div>
                                        <Textarea v-model="seoSettings.indexing.robotsTxt" rows="6" class="w-full font-mono text-sm p-4 rounded-3xl bg-slate-900 text-emerald-400 border-none" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="p-4 bg-white border border-slate-100 rounded-2xl text-center">
                                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Google Bot</p>
                                            <Tag value="Autorisé" severity="success" />
                                        </div>
                                        <div class="p-4 bg-white border border-slate-100 rounded-2xl text-center">
                                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Bing Bot</p>
                                            <Tag value="Autorisé" severity="success" />
                                        </div>
                                        <div class="p-4 bg-white border border-slate-100 rounded-2xl text-center">
                                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">GPTBot (AI)</p>
                                            <Tag value="Bloqué" severity="danger" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>

                            <!-- TAB 4: REDIRECTIONS -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-directions"></i>
                                        <span>Redirections 301</span>
                                    </div>
                                </template>

                                <div class="p-6">
                                    <Toolbar class="bg-transparent border-none p-0 mb-6">
                                        <template #start>
                                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Gestionnaire d'URLs</h3>
                                        </template>
                                        <template #end>
                                            <Button label="Nouvelle Redirection" icon="pi pi-plus" class="p-button-outlined rounded-xl" @click="redirectDialog = true" />
                                        </template>
                                    </Toolbar>

                                    <DataTable :value="redirects" class="p-datatable-sm rounded-2xl overflow-hidden border border-slate-100">
                                        <Column field="oldUrl" header="Ancienne URL (Source)" style="width: 35%">
                                            <template #body="{ data }">
                                                <span class="font-mono text-rose-500 font-bold text-xs">{{ data.oldUrl }}</span>
                                            </template>
                                        </Column>
                                        <Column field="newUrl" header="Nouvelle URL (Cible)" style="width: 35%">
                                            <template #body="{ data }">
                                                <span class="font-mono text-emerald-600 font-bold text-xs">{{ data.newUrl }}</span>
                                            </template>
                                        </Column>
                                        <Column field="type" header="Type" style="width: 10%">
                                            <template #body="{ data }">
                                                <Tag :value="data.type" :severity="data.type === '301' ? 'info' : 'warning'" />
                                            </template>
                                        </Column>
                                        <Column field="hits" header="Clics" sortable style="width: 10%"></Column>
                                        <Column header="Action" class="text-right" style="width: 10%">
                                            <template #body="{ data }">
                                                <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm" @click="deleteRedirect(data.id)" />
                                            </template>
                                        </Column>
                                    </DataTable>
                                </div>
                            </TabPanel>

                            <!-- TAB 5: ANALYTICS & SCRIPTS -->
                            <TabPanel>
                                <template #header>
                                    <div class="flex items-center gap-2 py-2">
                                        <i class="pi pi-code"></i>
                                        <span>Tracking & Pixels</span>
                                    </div>
                                </template>

                                <div class="p-6 space-y-8">
                                    <div class="bg-emerald-900 rounded-[2.5rem] p-8 text-white relative overflow-hidden">
                                        <i class="pi pi-chart-bar absolute -right-6 -bottom-6 text-9xl text-white/10"></i>
                                        <div class="relative z-10">
                                            <h3 class="text-2xl font-black mb-2">Google Analytics 4</h3>
                                            <p class="text-emerald-100 text-sm mb-6 max-w-md">Connectez votre propriété GA4 pour suivre les événements e-learning et les inscriptions en temps réel.</p>
                                            <div class="flex gap-4">
                                                <InputText v-model="seoSettings.scripts.analyticsId" placeholder="G-XXXXXXXX" class="w-64 bg-white/10 border-white/20 text-white placeholder:text-white/40" />
                                                <Button label="Vérifier la connexion" class="p-button-white p-button-outlined" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="field">
                                            <label class="text-sm font-black text-slate-700 uppercase block mb-2 italic">Scripts d'en-tête (Head)</label>
                                            <Textarea v-model="seoSettings.scripts.header" rows="8" class="w-full font-mono text-xs p-4 rounded-3xl bg-slate-100 border-none" placeholder="<!-- GTM ou Meta Pixel -->" />
                                        </div>
                                        <div class="field">
                                            <label class="text-sm font-black text-slate-700 uppercase block mb-2 italic">Scripts de pied de page (Footer)</label>
                                            <Textarea v-model="seoSettings.scripts.footer" rows="8" class="w-full font-mono text-xs p-4 rounded-3xl bg-slate-100 border-none" placeholder="<!-- Chat widget ou Scripts analytics -->" />
                                        </div>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>

                <!-- RIGHT SIDEBAR: SEO AUDIT & TOOLS -->
                <div class="col-span-12 lg:col-span-3 space-y-8">

                    <!-- AUDIT TOOL -->
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100">
                        <h3 class="text-lg font-black text-slate-800 mb-2 tracking-tight">Audit de santé SEO</h3>
                        <p class="text-xs text-slate-400 mb-6 uppercase font-bold tracking-widest">Diagnostic SubnetCongo</p>

                        <div v-if="isAuditing" class="py-8 text-center">
                            <i class="pi pi-spin pi-spinner text-4xl text-indigo-600 mb-4"></i>
                            <p class="text-sm font-bold text-slate-600 animate-pulse">Analyse des balises en cours...</p>
                            <ProgressBar :value="auditProgress" class="mt-4" style="height: 6px" />
                        </div>

                        <div v-else class="space-y-6">
                            <div v-for="res in auditResults" :key="res.id" class="flex gap-4 items-start">
                                <i :class="['pi mt-1',
                                    res.status === 'success' ? 'pi-check-circle text-emerald-500' :
                                    res.status === 'warning' ? 'pi-exclamation-triangle text-amber-500' : 'pi-times-circle text-rose-500']"></i>
                                <div>
                                    <p class="text-xs font-black text-slate-800">{{ res.label }}</p>
                                    <p class="text-[10px] text-slate-400 leading-tight">{{ res.msg }}</p>
                                </div>
                            </div>
                            <Button label="Relancer l'analyse" icon="pi pi-sync" class="w-full p-button-outlined p-button-sm mt-4 rounded-xl" @click="runAudit" />
                        </div>
                    </div>

                    <!-- VERIFICATION BOX -->
                    <div class="bg-indigo-900 rounded-[2.5rem] p-8 text-white shadow-xl relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>
                        <h3 class="text-lg font-bold mb-6">Codes de vérification</h3>
                        <div class="space-y-4">
                            <div class="field">
                                <label class="text-[10px] uppercase font-black text-indigo-300">Google Console</label>
                                <InputText v-model="seoSettings.verification.google" class="w-full p-2 text-xs bg-white/10 border-none text-white rounded-lg mt-1" />
                            </div>
                            <div class="field">
                                <label class="text-[10px] uppercase font-black text-indigo-300">Bing Webmaster</label>
                                <InputText v-model="seoSettings.verification.bing" class="w-full p-2 text-xs bg-white/10 border-none text-white rounded-lg mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- HELP CENTER -->
                    <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2.5rem]">
                        <h4 class="font-bold text-slate-800 mb-2">Besoin d'aide ?</h4>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">Consultez notre guide de référencement pour optimiser vos cours sur le marché Congolais.</p>
                        <Button label="Lire la documentation" icon="pi pi-external-link" class="p-button-text p-button-sm p-0 font-bold" />
                    </div>
                </div>
            </div>
        </div>

        <!-- --- DIALOG: ADD REDIRECT --- -->
        <Dialog v-model:visible="redirectDialog" :style="{width: '450px'}" header="Ajouter une Redirection" :modal="true" class="p-fluid">
            <div class="space-y-4 pt-4">
                <div class="field">
                    <label class="font-bold text-sm text-slate-700">Ancienne URL (Lien brisé)</label>
                    <InputText v-model="newRedirect.oldUrl" placeholder="/anciens-cours" />
                </div>
                <div class="field">
                    <label class="font-bold text-sm text-slate-700">Nouvelle URL (Destination)</label>
                    <InputText v-model="newRedirect.newUrl" placeholder="/courses" />
                </div>
                <div class="field">
                    <label class="font-bold text-sm text-slate-700">Code de redirection</label>
                    <SelectButton v-model="newRedirect.type" :options="['301', '302']" />
                    <small class="text-slate-400 mt-2 block">301 : Permanente (Recommandé) | 302 : Temporaire</small>
                </div>
            </div>
            <template #footer>
                <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="redirectDialog = false" />
                <Button label="Enregistrer" icon="pi pi-check" class="p-button-primary" @click="addRedirect" />
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* --- SEO CUSTOM TABS STYLING --- */

:deep(.custom-seo-tabs .p-tabview-nav) {
    background: #fdfdff;
    border-bottom: 2px solid #f1f5f9;
    padding: 0 2rem;
}

:deep(.custom-seo-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background: transparent;
    border: none;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #94a3b8;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 1.5rem 2rem;
}

:deep(.custom-seo-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #6366F1;
    box-shadow: inset 0 -3px 0 #6366F1;
}

:deep(.custom-seo-tabs .p-tabview-panels) {
    background: transparent;
    padding: 0;
}

/* UI ELEMENTS */

:deep(.p-inputtext), :deep(.p-textarea) {
    border-color: #f1f5f9;
    background: #f8fafc;
    transition: all 0.3s;
}

:deep(.p-inputtext:focus), :deep(.p-textarea:focus) {
    background: white;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
    border-color: #6366f1;
}

:deep(.p-datatable.p-datatable-sm .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 1rem;
}

:deep(.p-selectbutton .p-button.p-highlight) {
    background: #6366F1;
    border-color: #6366F1;
}

:deep(.p-progressbar) {
    background: #f1f5f9;
}

:deep(.p-progressbar-value) {
    background: linear-gradient(90deg, #6366F1 0%, #818CF8 100%);
}

/* Custom transitions for Audit */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

/* Responsive Table Fixes */
@media screen and (max-width: 960px) {
    :deep(.p-datatable .p-datatable-thead) {
        display: none;
    }
}
</style>
