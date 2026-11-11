<script setup>
/**
 * ============================================================================
 * COMPOSANT : CONTACT (Contact.vue)
 * DESCRIPTION : Page contact premium — même langage visuel que la Landing
 * (hero sombre, cartes arrondies, révélations au scroll) avec une identité
 * "support client" : coordonnées, formulaire, infos légales, process, FAQ.
 * ============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    legal: { type: Object, default: () => null },
});

const form = useForm({
    name: '', email: '', phone: '', subject: '', message: '',
});

const submit = () => form.post(route('contact.store'), { onSuccess: () => form.reset() });

const formSection = ref(null);
const quickChannels = [
    { icon: 'pi-graduation-cap', label: 'Formation', desc: 'Inscription, cursus, certification', subject: 'Inscription à une formation', color: '#2563EB' },
    { icon: 'pi-briefcase', label: 'Devis B2B', desc: 'Projet d\'entreprise, audit, infogérance', subject: 'Demande de devis B2B', color: '#E1251B' },
    { icon: 'pi-handshake', label: 'Partenariat', desc: 'Collaboration académique ou commerciale', subject: 'Partenariat', color: '#10B981' },
    { icon: 'pi-headphones', label: 'Support', desc: 'Problème technique, accès plateforme', subject: 'Support technique', color: '#F59E0B' },
];

const prefillSubject = (subject) => {
    form.subject = subject;
    formSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Identité légale réelle : prop passée par le contrôleur (LegalInfo::first()),
// avec repli sur la prop partagée par HandleInertiaRequests.
const legalInertia = computed(() => props.legal ?? page.props.legal ?? null);

// ============================================================================
// DONNÉES STATIQUES
// ============================================================================
const contactCards = computed(() => [
    {
        icon: 'pi-map-marker', color: '#E1251B', bg: 'bg-red-500/10',
        label: 'Adresse', title: 'Siège social',
        lines: [
            legalInertia.value?.address_line || props.identity.contact_address || 'Kinshasa, RDC',
            [legalInertia.value?.city, legalInertia.value?.province, legalInertia.value?.country].filter(Boolean).join(', ') || null,
            legalInertia.value?.po_box ? `BP : ${legalInertia.value.po_box}` : null,
        ].filter(Boolean),
        action: null,
    },
    {
        icon: 'pi-envelope', color: '#2563EB', bg: 'bg-blue-500/10',
        label: 'Email', title: 'Écrivez-nous',
        lines: [props.identity.contact_email || 'contact@subnetcongo.cd'],
        action: { label: 'Envoyer un email', href: `mailto:${props.identity.contact_email}` },
    },
    {
        icon: 'pi-phone', color: '#10B981', bg: 'bg-emerald-500/10',
        label: 'Téléphone', title: 'Appelez-nous',
        lines: [props.identity.contact_phone || '+243 000 000 000'],
        action: { label: 'Appeler maintenant', href: `tel:${props.identity.contact_phone}` },
    },
    {
        icon: 'pi-clock', color: '#F59E0B', bg: 'bg-amber-500/10',
        label: 'Horaires', title: 'Disponibilité',
        lines: ['Lun – Ven : 8h00 – 18h00', 'Sam : 9h00 – 13h00', 'Réponse sous 48h garantie'],
        action: null,
    },
]);

const processSteps = [
    { icon: 'pi-send', title: 'Envoyez votre demande', desc: 'Remplissez le formulaire ou écrivez-nous directement. Décrivez votre besoin en quelques lignes.' },
    { icon: 'pi-comments', title: 'Échange avec un expert', desc: 'Un membre de notre équipe vous recontacte sous 48h pour qualifier votre projet.' },
    { icon: 'pi-check-circle', title: 'Solution & devis', desc: 'Vous recevez une proposition claire : formation, prestation ou partenariat sur mesure.' },
];

const faqs = ref([
    { q: 'Sous quel délai recevez-vous une réponse ?', a: 'Nous garantissons une réponse sous 48 heures ouvrées. Pour les urgences techniques, appelez directement notre ligne téléphonique.', open: false },
    { q: 'Proposez-vous des formations en intra-entreprise ?', a: 'Oui, nous organisons des sessions sur mesure directement dans vos locaux ou en ligne. Contactez-nous avec vos besoins et nous construirons un programme adapté.', open: false },
    { q: 'Comment demander un devis pour un projet B2B ?', a: 'Utilisez le formulaire ci-dessus en précisant "Demande de devis B2B" comme sujet, ou écrivez directement à notre service commercial. Joignez si possible un descriptif de votre infrastructure.', open: false },
    { q: 'Acceptez-vous les demandes de partenariat ?', a: 'Absolument. Nous sommes ouverts aux partenariats académiques, techniques et commerciaux. Présentez-nous votre projet via le formulaire de contact.', open: false },
]);

const toggleFaq = (index) => {
    faqs.value.forEach((faq, i) => { faq.open = i === index ? !faq.open : false; });
};

const subjectOptions = [
    'Demande d\'information',
    'Inscription à une formation',
    'Demande de devis B2B',
    'Partenariat',
    'Support technique',
    'Autre',
];

// ============================================================================
// ANIMATIONS AU SCROLL (Intersection Observer — même que Landing)
// ============================================================================
let observer = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('is-revealed');
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    setTimeout(() => {
        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
    }, 100);
});

onUnmounted(() => { if (observer) observer.disconnect(); });
</script>

<template>
    <Head :title="`Contact - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" content="Contactez SubnetCongo : formations, devis B2B, partenariats et support. Réponse sous 48h.">
    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- HERO : signature sombre Landing -->
        <!-- ================================================================== -->
        <header class="relative min-h-[55vh] flex items-end pt-20 overflow-hidden bg-[#020617]">
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <img src="/media/realizations/real-p22-1.jpg" alt="" aria-hidden="true" class="w-full h-full object-cover opacity-30" loading="eager" decoding="async" />
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-50"></div>
                <div class="absolute -top-[30%] -left-[10%] w-[50%] h-[80%] bg-blue-600/20 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute -bottom-[20%] -right-[10%] w-[40%] h-[60%] bg-red-600/10 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#020617] via-[#020617]/85 to-[#020617]/20"></div>

            <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#E1251B] via-[#F59E0B] to-transparent z-10"></div>
            </div>

            <div class="relative z-20 max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-12 pb-16">
                <div class="max-w-3xl space-y-6">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-white/5 backdrop-blur-md border border-white/10 rounded-full text-white reveal-on-scroll">
                        <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-blue-200">Nous sommes à votre écoute</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.05] tracking-tight reveal-on-scroll delay-100">
                        Parlons de <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">votre projet</span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5 reveal-on-scroll delay-200">
                        Formation, prestation technique ou partenariat : une question, un besoin ? Notre équipe vous répond sous 48h.
                    </p>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION : COORDONNÉES (4 cartes signature Landing) -->
        <!-- ================================================================== -->
        <section class="py-16 bg-[#F4F6F9]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(card, i) in contactCards" :key="i" class="reveal-on-scroll group bg-white p-7 rounded-[1.25rem] border border-slate-100 hover:shadow-2xl transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5 transition-transform group-hover:scale-110" :class="card.bg" :style="{ color: card.color }">
                            <i :class="['pi', card.icon, 'text-2xl']"></i>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ card.label }}</p>
                        <h3 class="text-lg font-black text-[#0B1221] mb-3">{{ card.title }}</h3>
                        <ul class="space-y-1.5 mb-5">
                            <li v-for="(line, j) in card.lines" :key="j" class="text-sm text-slate-500 font-medium">{{ line }}</li>
                        </ul>
                        <a v-if="card.action" :href="card.action.href" class="inline-flex items-center gap-2 text-sm font-bold hover:underline" :style="{ color: card.color }">
                            {{ card.action.label }} <i class="pi pi-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : CANAUX RAPIDES (pré-remplit le sujet du formulaire) -->
        <!-- ================================================================== -->
        <section class="py-10 bg-white border-b border-slate-100">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <button v-for="(ch, i) in quickChannels" :key="i" @click="prefillSubject(ch.subject)"
                        class="reveal-on-scroll group flex items-center gap-4 p-5 bg-[#F4F6F9] rounded-[1.25rem] border border-slate-100 hover:bg-white hover:shadow-xl hover:border-slate-200 transition-all text-left">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                            :style="{ backgroundColor: ch.color + '15', color: ch.color }">
                            <i :class="['pi', ch.icon, 'text-xl']"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-[#0B1221]">{{ ch.label }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ ch.desc }}</p>
                        </div>
                        <i class="pi pi-arrow-right text-xs text-slate-300 group-hover:text-[#2563EB] group-hover:translate-x-1 transition-all ml-auto shrink-0"></i>
                    </button>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : FORMULAIRE + SIDEBAR -->
        <!-- ================================================================== -->
        <section ref="formSection" class="py-20 bg-white scroll-mt-24">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

                    <!-- Colonne formulaire (8/12) -->
                    <div class="lg:col-span-8">
                        <div class="reveal-on-scroll bg-[#F4F6F9] rounded-[1.5rem] border border-slate-100 p-8 lg:p-10">
                            <div class="flex items-center gap-3 mb-8 border-b border-slate-200 pb-6">
                                <div class="w-12 h-12 rounded-xl bg-[#2563EB]/10 text-[#2563EB] flex items-center justify-center">
                                    <i class="pi pi-send text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black text-[#0B1221]">Envoyez-nous un message</h2>
                                    <p class="text-sm text-slate-500">Les champs marqués * sont obligatoires.</p>
                                </div>
                            </div>

                            <div v-if="flash.success" class="mb-6 p-4 bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-bold flex items-center gap-2">
                                <i class="pi pi-check-circle"></i> {{ flash.success }}
                            </div>
                            <div v-if="flash.error" class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-xl text-sm font-bold flex items-center gap-2">
                                <i class="pi pi-exclamation-triangle"></i> {{ flash.error }}
                            </div>

                            <form @submit.prevent="submit" class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Nom complet *</label>
                                        <input v-model="form.name" type="text" placeholder="Jean-Paul Mulumba"
                                            class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" required />
                                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1.5 font-bold">{{ form.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Téléphone</label>
                                        <input v-model="form.phone" type="tel" placeholder="+243 000 000 000"
                                            class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Email *</label>
                                        <input v-model="form.email" type="email" placeholder="exemple@email.com"
                                            class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" required />
                                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1.5 font-bold">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Sujet *</label>
                                        <select v-model="form.subject" required
                                            class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all appearance-none cursor-pointer">
                                            <option value="" disabled>Sélectionnez un sujet...</option>
                                            <option v-for="s in subjectOptions" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                        <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1.5 font-bold">{{ form.errors.subject }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Message *</label>
                                    <textarea v-model="form.message" rows="6" placeholder="Décrivez votre besoin, votre projet ou votre question..."
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all resize-none"></textarea>
                                    <p v-if="form.errors.message" class="text-red-500 text-xs mt-1.5 font-bold">{{ form.errors.message }}</p>
                                </div>

                                <button type="submit" :disabled="form.processing"
                                    class="w-full px-8 py-4 bg-[#0B1221] hover:bg-[#2563EB] text-white font-bold rounded-xl transition-colors shadow-lg shadow-slate-900/10 flex items-center justify-center gap-3 disabled:opacity-50">
                                    <i :class="['pi', form.processing ? 'pi-spin pi-spinner' : 'pi-send']"></i>
                                    {{ form.processing ? 'Envoi en cours...' : 'Envoyer le message' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Colonne sidebar (4/12) -->
                    <div class="lg:col-span-4 space-y-6">
                        <!-- Réseaux sociaux -->
                        <div class="reveal-on-scroll bg-white p-7 rounded-[1.25rem] border border-slate-100 shadow-sm">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 mb-5">Suivez-nous</h3>
                            <div class="grid grid-cols-4 gap-3">
                                <a v-for="soc in [
                                    { icon: 'facebook', link: identity.social_facebook, color: 'hover:bg-blue-600' },
                                    { icon: 'twitter',  link: identity.social_twitter,  color: 'hover:bg-black' },
                                    { icon: 'linkedin', link: identity.social_linkedin, color: 'hover:bg-blue-700' },
                                    { icon: 'youtube',  link: identity.social_youtube,  color: 'hover:bg-red-600' }
                                ]" :key="soc.icon" :href="soc.link && soc.link !== '#' ? soc.link : '#'"
                                   class="aspect-square rounded-xl bg-[#F4F6F9] border border-slate-100 flex items-center justify-center text-slate-500 hover:text-white transition-all duration-300 hover:-translate-y-1"
                                   :class="soc.color">
                                    <i :class="`pi pi-${soc.icon}`"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Infos légales -->
                        <div v-if="legalInertia && (legalInertia.company_name || legalInertia.rccm || legalInertia.ifu || legalInertia.tax_id)" class="reveal-on-scroll bg-[#0B1221] text-white p-7 rounded-[1.25rem] shadow-lg relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-600/20 blur-2xl rounded-full"></div>
                            <div class="relative">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="w-10 h-10 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-id-card"></i>
                                    </div>
                                    <h3 class="font-black">Informations légales</h3>
                                </div>
                                <dl class="space-y-3 text-sm">
                                    <div v-if="legalInertia.company_name">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Raison sociale</dt>
                                        <dd class="font-bold">{{ legalInertia.company_name }}<span v-if="legalInertia.legal_form" class="text-slate-400"> {{ legalInertia.legal_form }}</span></dd>
                                    </div>
                                    <div v-if="legalInertia.rccm">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">RCCM</dt>
                                        <dd class="font-mono">{{ legalInertia.rccm }}</dd>
                                    </div>
                                    <div v-if="legalInertia.ifu">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">IFU</dt>
                                        <dd class="font-mono">{{ legalInertia.ifu }}</dd>
                                    </div>
                                    <div v-if="legalInertia.tax_id">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">N° Contribuable</dt>
                                        <dd class="font-mono">{{ legalInertia.tax_id }}</dd>
                                    </div>
                                    <div v-if="legalInertia.nif">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">NIF</dt>
                                        <dd class="font-mono">{{ legalInertia.nif }}</dd>
                                    </div>
                                    <div v-if="legalInertia.capital_social">
                                        <dt class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Capital social</dt>
                                        <dd class="font-bold">{{ legalInertia.capital_social }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Carte "urgence" -->
                        <div class="reveal-on-scroll bg-gradient-to-br from-red-600 to-[#E1251B] text-white p-7 rounded-[1.25rem] shadow-xl relative overflow-hidden">
                            <div class="absolute -bottom-8 -right-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                            <div class="relative">
                                <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center mb-4">
                                    <i class="pi pi-bolt text-xl"></i>
                                </div>
                                <h3 class="text-xl font-black mb-2">Besoin d'une réponse rapide ?</h3>
                                <p class="text-sm text-red-100 leading-relaxed mb-5">Pour les urgences techniques ou les demandes de devis prioritaires, appelez directement notre ligne.</p>
                                <a :href="`tel:${identity.contact_phone}`" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-red-600 font-bold rounded-xl hover:bg-red-50 transition-colors text-sm">
                                    <i class="pi pi-phone"></i> {{ identity.contact_phone }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : PROCESSUS (3 étapes, style parcours Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center max-w-3xl mx-auto mb-12 reveal-on-scroll">
                    <div class="inline-flex items-center gap-2 mb-2 justify-center">
                        <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">Comment ça marche</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black text-[#0B1221] tracking-tight">De votre message à la solution</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="(step, i) in processSteps" :key="i" class="reveal-on-scroll relative bg-white p-8 rounded-[1.25rem] border border-slate-100 hover:shadow-xl transition-shadow">
                        <div class="absolute top-6 right-6 text-[6rem] font-black text-slate-50 leading-none select-none">0{{ i + 1 }}</div>
                        <div class="relative z-10">
                            <div class="w-14 h-14 rounded-xl bg-[#2563EB]/10 text-[#2563EB] flex items-center justify-center mb-6">
                                <i :class="['pi', step.icon, 'text-2xl']"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#0B1221] mb-3">{{ step.title }}</h3>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : FAQ CONTACT -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#0B1221] text-white">
            <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center mb-12 reveal-on-scroll">
                    <div class="inline-flex items-center gap-2 mb-2 justify-center">
                        <span class="w-6 h-1 bg-red-500 rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-red-400">Avant d'écrire</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-black tracking-tight">Questions fréquentes</h2>
                </div>

                <div class="space-y-3">
                    <div v-for="(f, i) in faqs" :key="i" class="reveal-on-scroll bg-white/5 border border-white/10 rounded-xl overflow-hidden transition-colors hover:bg-white/10">
                        <button @click="toggleFaq(i)" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                            <span class="font-bold text-base pr-4">{{ f.q }}</span>
                            <i :class="['pi', f.open ? 'pi-minus text-red-500' : 'pi-plus text-slate-400', 'transition-transform duration-300']"></i>
                        </button>
                        <div class="transition-all duration-300 ease-in-out px-6" :class="f.open ? 'max-h-48 opacity-100 pb-5' : 'max-h-0 opacity-0 pb-0 overflow-hidden'">
                            <p class="text-slate-300 text-sm leading-relaxed">{{ f.a }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : CTA FINAL -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white border-t border-slate-100 text-center">
            <div class="max-w-[800px] mx-auto px-4 reveal-on-scroll">
                <h2 class="text-4xl md:text-5xl font-black text-[#0B1221] tracking-tight mb-6">Prêt à passer à l'action ?</h2>
                <p class="text-lg text-slate-500 mb-10">Parcourez notre catalogue ou découvrez qui nous sommes.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('academy')" class="w-full sm:w-auto px-10 py-4 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20 text-base inline-flex items-center justify-center gap-2">
                        Explorer le catalogue <i class="pi pi-arrow-right"></i>
                    </Link>
                    <Link :href="route('about')" class="w-full sm:w-auto px-10 py-4 bg-[#F4F6F9] text-[#0B1221] font-bold rounded-xl hover:bg-slate-200 transition-colors text-base">
                        À propos de nous
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.reveal-on-scroll {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}
.reveal-on-scroll.is-revealed {
    opacity: 1; transform: translateY(0);
}
</style>
