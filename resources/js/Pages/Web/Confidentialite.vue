<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="`Politique de Confidentialité - ${identity.site_name || 'SubnetCongo'}`" />
    <AppLayout>
        
        <!-- En-tête de la page -->
        <section class="relative pt-32 pb-32 bg-[#001A4D] text-white">
            <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D] via-[#003399]/80 to-[#001A4D] opacity-90"></div>
            
            <!-- Motif de fond discret -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white via-transparent to-transparent bg-[length:20px_20px]"></div>

            <div class="relative max-w-5xl mx-auto px-6 text-center z-10">
                <span class="inline-flex items-center gap-2 text-red-400 mb-4">
                    <span class="w-8 h-[2px] bg-red-400"></span>
                    <span class="font-bold uppercase tracking-[0.3em] text-xs">Vos données, notre priorité</span>
                    <span class="w-8 h-[2px] bg-red-400"></span>
                </span>
                <h1 class="text-4xl md:text-6xl font-black mt-2 tracking-tight">Politique de <span class="text-[#E11D48]">Confidentialité</span></h1>
                <p class="text-lg text-blue-200 mt-6 max-w-3xl mx-auto font-medium">
                    Découvrez comment {{ identity.site_name || 'SubnetCongo' }} protège vos données personnelles conformément à la législation congolaise en vigueur.
                </p>
            </div>
        </section>

        <!-- Contenu Légal (Style Document) -->
        <section class="pb-20 bg-slate-50 px-4 sm:px-6 relative">
            
            <!-- Le bloc principal remonte sur le header bleu avec une ombre -->
            <div class="max-w-7xl mx-auto bg-white rounded-2xl shadow-xl border border-slate-100 p-8 sm:p-12 -mt-16 relative z-20 space-y-16">

                <!-- 1. Qui sommes-nous ? -->
                <article>
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">01</span>
                        Qui sommes-nous ?
                    </h2>
                    <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                        <p><strong>{{ identity.site_name || 'SubnetCongo' }}</strong> (ci-après « nous ») est la plateforme de formation en ligne et de services technologiques de <strong>SUBNETCONGO SARL</strong>, société immatriculée au RCCM de Kinshasa (CD/KIN/RCCM/25-B-00000), dont le siège social est situé à {{ identity.contact_address || 'Kinshasa, RDC' }}.</p>
                        <p>En tant que responsable de traitement, nous nous engageons à protéger la confidentialité de vos données personnelles et à garantir un niveau de sécurité élevé conformément à la <strong>Loi n° 20/017 du 25 novembre 2020</strong> portant protection des données à caractère personnel en République Démocratique du Congo.</p>
                    </div>
                </article>

                <!-- 2. Données collectées -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">02</span>
                        Quelles données collectons-nous ?
                    </h2>
                    <p class="text-slate-600 mb-6">Nous collectons les catégories de données personnelles suivantes :</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">Données d'identification</h4>
                            <p class="text-sm text-slate-600">Nom, prénom, adresse email, téléphone, pays, ville, genre.</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">Navigation & Connectivité</h4>
                            <p class="text-sm text-slate-600">Adresse IP, type de navigateur, système d'exploitation, identifiants de session.</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">Données pédagogiques</h4>
                            <p class="text-sm text-slate-600">Progression dans les cours, résultats aux examens, certificats obtenus.</p>
                        </div>
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-xs mb-2">Paiement & Facturation</h4>
                            <p class="text-sm text-slate-600">Historique des transactions (les données de cartes bancaires ne sont pas stockées chez nous).</p>
                        </div>
                    </div>
                </article>

                <!-- 3. Finalités -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">03</span>
                        Pourquoi collectons-nous vos données ?
                    </h2>
                    <ul class="space-y-4 text-slate-600">
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">Exécution du contrat :</strong> Création de compte, accès aux formations ERP, délivrance de certificats.</div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">Intérêt légitime :</strong> Amélioration des services, sécurité des systèmes, statistiques.</div>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="pi pi-check-circle text-[#003399] mt-1"></i>
                            <div><strong class="text-slate-800">Obligation légale :</strong> Respect des obligations fiscales et lutte contre la fraude en RDC.</div>
                        </li>
                    </ul>
                </article>

                <!-- 4. Conservation -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">04</span>
                        Durée de conservation
                    </h2>
                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden text-sm">
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">Compte actif & Pédagogie</span>
                            <span class="text-slate-500">Durée de vie du compte + 3 ans</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">Données de facturation</span>
                            <span class="text-slate-500">10 ans (Obligation fiscale)</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">Cookies & Navigation</span>
                            <span class="text-slate-500">13 mois maximum</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between p-4 hover:bg-slate-50 transition-colors">
                            <span class="font-bold text-slate-800">Logs de sécurité</span>
                            <span class="text-slate-500">12 mois</span>
                        </div>
                    </div>
                </article>

                <!-- 5. Partage -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">05</span>
                        Partage et transferts des données
                    </h2>
                    <div class="text-slate-600 leading-relaxed text-justify">
                        <p>Vos données ne sont <strong>jamais vendues</strong>. Elles sont uniquement partagées avec nos prestataires techniques (hébergement, paiements Mobile Money/Cartes, emailing) sous contrats de confidentialité stricts.</p>
                        <p class="mt-4">Les données sont hébergées principalement en RDC et dans l'Union Européenne. Tout transfert hors de ces zones est encadré par des clauses contractuelles garantissant la sécurité de vos informations.</p>
                    </div>
                </article>

                <!-- 6. Vos droits -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">06</span>
                        Vos droits
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">Accès & Portabilité</h4>
                            <p class="text-xs text-slate-500">Obtenir une copie de vos données.</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">Rectification</h4>
                            <p class="text-xs text-slate-500">Mettre à jour vos informations.</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-1">Effacement</h4>
                            <p class="text-xs text-slate-500">Demander la suppression de votre compte.</p>
                        </div>
                    </div>
                    <p class="text-slate-600 text-sm">
                        Pour exercer vos droits, contactez-nous à <a :href="`mailto:${identity.contact_email || 'contact@subnetcongo.cd'}`" class="text-[#003399] font-bold hover:underline">{{ identity.contact_email || 'contact@subnetcongo.cd' }}</a>. Nous répondrons sous 30 jours maximum.
                    </p>
                </article>

                <!-- 7 & 8. Sécurité et Cookies -->
                <article class="border-t border-slate-100 pt-10">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded bg-red-50 text-[#E11D48] flex items-center justify-center text-sm font-bold">07</span>
                        Sécurité & Cookies
                    </h2>
                    <div class="flex flex-col md:flex-row gap-8">
                        <div class="flex-1 text-slate-600 text-justify">
                            <h4 class="font-bold text-slate-900 mb-2">Sécurité des infrastructures</h4>
                            <p class="text-sm mb-4">Nous appliquons le chiffrement TLS/SSL de bout en bout, le hachage des mots de passe (bcrypt) et une surveillance 24/7 de nos serveurs pour empêcher toute violation de données.</p>
                        </div>
                        <div class="flex-1 text-slate-600 text-justify">
                            <h4 class="font-bold text-slate-900 mb-2">Utilisation des traceurs</h4>
                            <p class="text-sm">Nous utilisons des cookies essentiels au fonctionnement de la plateforme. Les cookies analytiques ne sont activés qu'avec votre consentement préalable via notre bandeau dédié.</p>
                        </div>
                    </div>
                </article>
                
            </div>

            <!-- Footer Document Légal -->
            <div class="max-w-4xl mx-auto mt-8 text-center text-sm text-slate-500 space-y-2">
                <p>
                    Pour les aspects juridiques de l'entreprise, veuillez consulter nos 
                    <Link :href="route('mentions-legales')" class="text-[#E11D48] font-bold hover:underline">Mentions Légales</Link>.
                </p>
                <p>
                    Dernière mise à jour : <span class="font-semibold text-slate-600">4 Septembre 2026</span><br>
                    SUBNETCONGO se réserve le droit de modifier cette politique pour se conformer aux évolutions légales en RDC.
                </p>
            </div>

        </section>
    </AppLayout>
</template>