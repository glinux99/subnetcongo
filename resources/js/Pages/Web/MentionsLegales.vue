<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    legal_content: { type: Object, default: () => ({}) },
});

const page = usePage();

// `legal` est partagé globalement par HandleInertiaRequests (LegalInfo::first()).
const legal = computed(() => page.props.legal || null);
const identity = computed(() => props.identity || {});

const missing = 'Information non renseignée';

const companyName = computed(() => legal.value?.company_name || identity.value?.site_name || 'SubnetCongo');

const capitalDisplay = computed(() => {
    const raw = legal.value?.capital_social;
    if (!raw) return null;
    const trimmed = String(raw).trim();
    return /[A-Za-z]/.test(trimmed) ? trimmed : `${trimmed} ${legal.value?.currency || 'CDF'}`;
});

const legalFormCapital = computed(() => {
    const parts = [];
    if (legal.value?.legal_form) parts.push(legal.value.legal_form);
    if (capitalDisplay.value) parts.push(`au capital de ${capitalDisplay.value}`);
    return parts.length ? parts.join(' ') : null;
});

const seatAddress = computed(() => {
    const l = legal.value;
    const parts = [l?.address_line, l?.city, l?.province, l?.country].filter(Boolean);
    if (parts.length) return parts.join(', ');
    return identity.value?.contact_address || 'Kinshasa, RDC';
});

const legalFilled = computed(() => {
    const l = legal.value;
    if (!l) return false;
    return ['company_name', 'legal_form', 'rccm', 'ifu', 'tax_id', 'nif', 'vat_number', 'capital_social', 'address_line', 'po_box', 'website'].some((k) => l[k]);
});

const legalUpdated = computed(() => {
    const iso = legal.value?.updated_at;
    if (!iso) return null;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return null;
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(d);
});

const websiteHref = computed(() => {
    const w = legal.value?.website;
    if (!w) return null;
    return /^https?:\/\//i.test(w) ? w : `https://${w}`;
});
</script>

<template>
    <Head :title="`Mentions Légales - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        
        <!-- En-tête de la page -->
        <section class="relative pt-32 pb-32 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] via-[#003399]/80 to-[#001A4D] opacity-90"></div>
            
            <!-- Motif de fond discret (Optionnel pour un look tech) -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white via-transparent to-transparent bg-[length:20px_20px]"></div>

            <div class="relative max-w-5xl mx-auto px-6 text-center z-10">
                <span class="inline-flex items-center gap-2 text-red-400 mb-4">
                    <span class="w-8 h-[2px] bg-red-400"></span>
                    <span class="font-bold uppercase tracking-[0.3em] text-xs">Informations réglementaires</span>
                    <span class="w-8 h-[2px] bg-red-400"></span>
                </span>
                <h1 class="text-4xl md:text-6xl font-black mt-2 tracking-tight">Mentions <span class="text-[#E11D48]">Légales</span></h1>
                <p class="text-lg text-blue-200 mt-6 max-w-3xl mx-auto font-medium">
                    Conformément aux dispositions de la loi congolaise n° 20/017 du 25 novembre 2020 relative au numérique en RDC.
                </p>
            </div>
        </section>

        <!-- Contenu Légal (Style Document) -->
        <section class="pb-20 bg-slate-50 px-4 sm:px-6 relative">
            
            <!-- Le bloc principal remonte sur le header bleu avec une ombre -->
            <div class="max-w-8xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-100 p-8 sm:p-12 -mt-16 relative z-20 space-y-16">

                <!-- 1. Éditeur -->
                <article>
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">01</span>
                        Identification de l'éditeur
                    </h2>
                    <div class="text-slate-600 leading-relaxed mb-6">
                        Le site internet <strong>{{ identity.site_name || 'SubnetCongo' }}</strong> est édité et exploité par la société :
                    </div>

                    <!-- Fiche entreprise (Grille professionnelle) -->
                    <div v-if="legalFilled" class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 bg-slate-50 p-6 sm:p-8 rounded-xl border border-slate-200 text-sm">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Dénomination sociale</span>
                            <span class="font-semibold text-slate-900 text-base">{{ legal?.company_name || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Forme juridique & Capital</span>
                            <span class="font-medium text-slate-700">{{ legalFormCapital || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Immatriculation (RCCM)</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.rccm || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Identifiant Fiscal Unique (IFU)</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.ifu || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Numéro d'impôt</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.tax_id || missing }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Numéro d'identification (NIF)</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal?.nif || missing }}</span>
                        </div>
                        <div v-if="legal?.vat_number">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">N° TVA</span>
                            <span class="font-medium text-slate-700 font-mono">{{ legal.vat_number }}</span>
                        </div>
                        <div v-if="legal?.po_box">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Boîte postale</span>
                            <span class="font-medium text-slate-700">{{ legal.po_box }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Siège social</span>
                            <span class="font-medium text-slate-700">{{ seatAddress }}</span>
                        </div>
                        <div v-if="websiteHref">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Site web</span>
                            <a :href="websiteHref" target="_blank" rel="noopener" class="font-medium text-[#003399] hover:underline break-all">{{ legal.website }}</a>
                        </div>
                        <div class="md:col-span-2 pt-4 border-t border-slate-200 mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Contact direct</span>
                                <span class="font-medium text-[#003399]">{{ identity.contact_phone || 'Contact non renseigné' }}</span><br>
                                <span class="font-medium text-[#003399]">{{ identity.contact_email || 'contact non renseigné' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Directeur de publication</span>
                                <span class="font-medium text-slate-700">Le Gérant de {{ companyName }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fiche non renseignée : repli élégant -->
                    <div v-else class="border border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50">
                        <i class="pi pi-info-circle text-3xl text-slate-300 mb-3" aria-hidden="true"></i>
                        <p class="font-semibold text-slate-700">Informations non renseignées</p>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Les informations légales de la société seront affichées ici dès qu'elles auront été complétées depuis l'espace d'administration.</p>
                        <p class="text-sm text-[#003399] mt-4">{{ identity.contact_email || 'contact@subnetcongo.cd' }} · {{ identity.contact_phone || '' }}</p>
                    </div>
                </article>

                <!-- 2. Hébergement -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">02</span>
                        Hébergement
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4">
                        <p>L'hébergement du site et des infrastructures ERP est assuré par :</p>
                        <div class="bg-blue-50/50 p-4 border border-blue-100 rounded-lg">
                            <ul class="space-y-2 text-sm">
                                <li><strong class="text-slate-800">Prestataire :</strong> {{ companyName }} (Infrastructure interne)</li>
                                <li><strong class="text-slate-800">Localisation des serveurs :</strong> {{ seatAddress }}</li>
                                <li><strong class="text-slate-800">Supervision :</strong> Assurée 24/7 par nos équipes techniques.</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <!-- 3. Propriété Intellectuelle -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">03</span>
                        Propriété intellectuelle
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>L'ensemble du contenu du site <strong>{{ identity.site_name || 'SubnetCongo' }}</strong> — incluant, de manière non limitative, les textes, graphiques, logos, icônes, interfaces, codes sources, vidéos, et logiciels de l'ERP — est la propriété exclusive de {{ companyName }}.</p>
                        <p>Ces éléments sont protégés par les lois congolaises et internationales relatives à la propriété intellectuelle, notamment le Code de la Propriété Intellectuelle congolais et les conventions internationales dont la RDC est signataire.</p>
                        <div class="border-l-4 border-red-500 pl-4 bg-red-50/50 p-4 rounded-r-lg mt-4 text-sm text-slate-700">
                            <strong>Avertissement pénal :</strong> Toute reproduction, représentation, modification ou extraction (par data mining ou autres) de tout ou partie des éléments du site, sans l'autorisation écrite préalable de {{ companyName }}, est strictement interdite et constitutive d'une contrefaçon sanctionnée par les articles 79 à 91 du Code Pénal Congolais (Loi n° 09/001 du 10 janvier 2009).
                        </div>
                    </div>
                </article>

                <!-- 4. Données Personnelles -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">04</span>
                        Données personnelles et vie privée
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>Conformément à la <strong>Loi n° 20/017 du 25 novembre 2020</strong> portant sur le numérique en RDC, les données personnelles collectées via l'ERP, les formulaires de contact ou la plateforme de formation font l'objet d'un traitement hautement sécurisé.</p>
                        <p>Ces données sont strictement nécessaires à : la gestion des accès, la facturation, le suivi pédagogique et le support technique.</p>
                        <p>Vous disposez d'un droit d'accès, de rectification, de portabilité et d'effacement de vos données. Pour l'exercer, contactez notre Délégué à la Protection des Données (DPO) à : <a :href="`mailto:${identity.contact_email || 'contact@subnetcongo.cd'}`" class="text-[#003399] font-semibold hover:underline">{{ identity.contact_email || 'contact@subnetcongo.cd' }}</a>.</p>
                        <p class="text-sm bg-slate-100 p-3 rounded">
                            👉 Pour le détail complet, consultez notre 
                            <Link :href="route('confidentialite')" class="text-[#E11D48] font-bold hover:underline">Politique de Confidentialité</Link>.
                        </p>
                    </div>
                </article>

                <!-- 5. Cookies (NOUVEAU) -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">05</span>
                        Gestion des Cookies
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>Le site utilise des cookies (traceurs) pour garantir le bon fonctionnement de la plateforme (cookies de session ERP), améliorer la navigation, et réaliser des statistiques d'audience anonymes.</p>
                        <p>Lors de votre première visite, un bandeau vous informe de la présence de ces cookies et vous invite à indiquer votre choix. Les cookies techniques, indispensables au fonctionnement des portails de connexion, ne requièrent pas de consentement préalable.</p>
                    </div>
                </article>

                <!-- 6. Sécurité (NOUVEAU) -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">06</span>
                        Sécurité des transactions
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>{{ companyName }} s'engage à mettre en œuvre toutes les mesures techniques et organisationnelles appropriées pour garantir la sécurité et la confidentialité de vos données.</p>
                        <p>Les échanges de données entre votre navigateur et nos serveurs (notamment les identifiants de connexion à l'ERP) sont cryptés via le protocole <strong>SSL/TLS (HTTPS)</strong>.</p>
                    </div>
                </article>

                <!-- 7. Responsabilité -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">07</span>
                        Limitation de responsabilité
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>L'utilisation de la plateforme se fait "en l'état". Bien que nous nous efforcions d'assurer une disponibilité de 99,9%, {{ companyName }} décline toute responsabilité en cas :</p>
                        <ul class="list-disc pl-5 space-y-2">
                            <li>D'interruption temporaire du service pour cause de maintenance technique.</li>
                            <li>D'attaques informatiques externes, de défaillance du réseau internet congolais ou de cas de force majeure.</li>
                            <li>De perte de données imputable à une mauvaise manipulation de la part de l'utilisateur.</li>
                        </ul>
                        <p>Les liens hypertextes sortants présents sur ce site ne sauraient engager la responsabilité de l'éditeur quant à leur contenu.</p>
                    </div>
                </article>

                <!-- 8. Juridiction -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">08</span>
                        Droit applicable & Juridiction
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p>Les présentes mentions légales sont soumises au <strong>droit de la République Démocratique du Congo</strong>.</p>
                        <p>En cas de litige relatif à l'interprétation ou à l'exécution de ces dispositions, une résolution à l'amiable sera privilégiée. À défaut d'accord, le litige sera porté exclusivement devant les <strong>tribunaux compétents de Kinshasa/Gombe</strong>.</p>
                    </div>
                </article>

            </div>
            
            <!-- Date de mise à jour -->
            <div class="max-w-4xl mx-auto mt-8 text-center text-sm text-slate-400">
                <p v-if="legalUpdated">Dernière mise à jour : <span class="font-semibold text-slate-500">{{ legalUpdated }}</span><br></p>
                {{ companyName }} se réserve le droit de modifier ces mentions à tout moment.
            </div>

        </section>
    </AppLayout>
</template>