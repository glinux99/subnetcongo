<script setup>
/**
 * ============================================================================
 * COMPOSANT : PAGE D'ACCUEIL (Home.vue)
 * DESCRIPTION : Page d'accueil ultra-premium, design SubnetCongo.
 * Fonctionnalités : Hero Slider interactif, Catalogue avec filtres et recherche,
 * Animations au scroll, Compteurs de statistiques, Données structurées SEO.
 * ============================================================================
 */

import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// ============================================================================
// 1. DÉFINITION DES PROPS & VALIDATION STRICTE
// ============================================================================
const props = defineProps({
    identity: {
        type: Object,
        default: () => ({ site_name: 'SubnetCongo', site_tagline: 'Leader en Formation IT', site_description: 'Excellence technologique' })
    },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    heroSource: { type: Object, default: null },
    heroSlides: { type: Array, default: () => [] },
    about: { type: Object, default: () => ({}) },
    courses: { type: Array, default: () => [] },
    testimonials: { type: Array, default: () => [] },
    realizations: { type: Array, default: () => [] },
    recentPosts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    adminStats: { type: Array, default: () => [] },
    technologies: { type: Array, default: () => [] },
    instructors: { type: Array, default: () => [] }
});

// ============================================================================
// 2. CONSTANTES ET DONNÉES DE SECOURS (FALLBACKS RÉALISTES)
// ============================================================================
const DEFAULT_COURSE_IMAGE = 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop';
const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=f1f5f9&color=64748b&name=';

const mockCourses = [
    { id: 1, title: 'Sécurité des SI : Audit et Pentesting', category: 'SÉCURITÉ', is_live: false, is_free: false, price: 150, rating: '4.8', duration: '45h', instructor: 'Sarah K.', image: null },
    { id: 2, title: 'Administration Cisco CCNA (Module 1)', category: 'RÉSEAUX', is_live: true, is_free: false, price: 200, rating: '4.7', duration: '60h', instructor: 'Jean-Paul M.', image: null },
    { id: 3, title: 'Architecture Cloud AWS pour Entreprises', category: 'CLOUD', is_live: false, is_free: false, price: 180, rating: '5.0', duration: '30h', instructor: 'Ali B.', image: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072' },
    { id: 4, title: 'Maîtrise de Linux Enterprise Server', category: 'SYSTÈMES', is_live: false, is_free: true, price: 0, rating: '4.6', duration: '20h', instructor: 'Marc T.', image: null },
    { id: 5, title: 'Déploiement SD-WAN avec Fortinet', category: 'RÉSEAUX', is_live: true, is_free: false, price: 250, rating: '4.9', duration: '50h', instructor: 'Elise D.', image: null },
    { id: 6, title: 'DevSecOps : Intégration continue sécurisée', category: 'SÉCURITÉ', is_live: false, is_free: false, price: 300, rating: '4.8', duration: '40h', instructor: 'Sarah K.', image: null }
];

const learningPaths = [
    { title: 'Ingénieur Réseau Certifié', desc: 'Du CCNA au CCNP, maîtrisez le routage et la commutation.', courses_count: 5, time: '6 mois', icon: 'pi-sitemap' },
    { title: 'Expert Cybersécurité', desc: 'Devenez un hacker éthique certifié et protégez les infrastructures.', courses_count: 7, time: '8 mois', icon: 'pi-shield' },
    { title: 'Architecte Cloud AWS', desc: 'Concevez, déployez et gérez des applications scalables sur AWS.', courses_count: 4, time: '4 mois', icon: 'pi-cloud' }
];

// ============================================================================
// 3. LOGIQUE GLOBALE & RESPONSIVE
// ============================================================================
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200);
const isMobile = computed(() => windowWidth.value < 768);

const updateWidth = () => { windowWidth.value = window.innerWidth; };

// ============================================================================
// 4. LOGIQUE DU HERO SLIDER (Touch, Timer, Keyboard)
// ============================================================================
const activeSlide = ref(0);
const isPaused = ref(false);
const slideProgress = ref(0);
const touchStartX = ref(0);
let progressTimer = null;
const SLIDE_DURATION = 8000;

const slides = computed(() => {
    if (props.heroSlides && props.heroSlides.length) {
        return props.heroSlides.map((s) => ({
            badge: s.badge,
            title: s.title,
            highlight: s.highlight,
            description: s.description,
            bgImage: s.bg_image,
            mainImage: s.main_image,
            icon: s.icon,
            cardTitle: s.card_title,
            cardSub: s.card_sub,
            ctaLabel: s.cta_label,
            ctaUrl: s.cta_url,
        }));
    }
    return [
        { badge: 'Excellence & Innovation', title: 'Propulsez votre Carrière IT', highlight: 'Aujourd\'hui', description: 'Formez-vous aux technologies de demain avec des experts du réseau et de la cybersécurité.', bgImage: 'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=2069', mainImage: 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070' },
        { badge: 'Infrastructures Critiques', title: 'Déployez des Réseaux', highlight: 'Performants', description: 'Solutions d\'ingénierie et d\'infogérance adaptées aux entreprises.', bgImage: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?q=80&w=2034', mainImage: 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=2070' }
    ];
});

const startProgress = () => {
    if (progressTimer) clearInterval(progressTimer);
    slideProgress.value = 0;
    progressTimer = setInterval(() => {
        if (!isPaused.value) {
            slideProgress.value += (100 / (SLIDE_DURATION / 50));
            if (slideProgress.value >= 100) nextSlide();
        }
    }, 50);
};

const nextSlide = () => { activeSlide.value = (activeSlide.value + 1) % slides.value.length; slideProgress.value = 0; };
const prevSlide = () => { activeSlide.value = activeSlide.value === 0 ? slides.value.length - 1 : activeSlide.value - 1; slideProgress.value = 0; };
const goToSlide = (index) => { activeSlide.value = index; slideProgress.value = 0; };

const onTouchStart = (e) => { touchStartX.value = e.changedTouches[0].screenX; isPaused.value = true; };
const onTouchEnd = (e) => {
    const endX = e.changedTouches[0].screenX;
    if (touchStartX.value - endX > 50) nextSlide();
    else if (endX - touchStartX.value > 50) prevSlide();
    isPaused.value = false;
};

// ============================================================================
// 5. CATALOGUE : LOGIQUE DE RECHERCHE ET FILTRAGE AVANCÉ
// ============================================================================
const selectedCategory = ref('Tous');
const searchQuery = ref('');
const isAnimatingCourses = ref(false);

const baseCourses = computed(() => (props.courses.length ? props.courses : mockCourses));
const allCategories = computed(() => ['Tous', ...new Set(baseCourses.value.map(c => c.category).filter(Boolean))]);

const filteredCourses = computed(() => {
    let result = baseCourses.value;

    // Filtre par catégorie
    if (selectedCategory.value !== 'Tous') {
        result = result.filter(c => c.category === selectedCategory.value);
    }

    // Filtre par recherche texte (titre ou instructeur)
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c =>
            c.title.toLowerCase().includes(q) ||
            (c.instructor && c.instructor.toLowerCase().includes(q))
        );
    }

    return result.slice(0, 6); // Limite à 6 sur la page d'accueil
});

const changeCategory = (cat) => {
    if (selectedCategory.value === cat) return;
    isAnimatingCourses.value = true;
    setTimeout(() => {
        selectedCategory.value = cat;
        setTimeout(() => { isAnimatingCourses.value = false; }, 50);
    }, 300);
};

// ============================================================================
// 6. ANIMATIONS ET COMPTEURS (Intersection Observer)
// ============================================================================
const statsSection = ref(null);
const statsVisible = ref(false);
const displayStats = ref([]);

const parseStatValue = (v) => {
    if (v === null || v === undefined) return 0;
    const cleaned = String(v).replace(/[^\d.]/g, '');
    return parseInt(cleaned, 10) || 0;
};

const buildStats = () => {
    if (props.adminStats && props.adminStats.length) {
        return props.adminStats.map((s) => ({
            current: 0,
            target: parseStatValue(s.value),
            label: s.label,
            icon: s.icon || 'pi-chart-bar',
            color: s.color || '#2563EB',
        }));
    }
    return [
        { current: 0, target: props.stats?.students ?? 3500, label: 'Apprenants formés', icon: 'pi-users', color: '#003399' },
        { current: 0, target: props.stats?.courses ?? 120, label: 'Cours & Modules', icon: 'pi-book', color: '#EE1C25' },
        { current: 0, target: props.stats?.instructors ?? 25, label: 'Experts Certifiés', icon: 'pi-verified', color: '#10B981' },
        { current: 0, target: props.stats?.success_rate ?? 98, label: 'Taux de réussite (%)', icon: 'pi-chart-line', color: '#F59E0B' },
    ];
};

displayStats.value = buildStats();

watch(() => props.adminStats, () => { displayStats.value = buildStats(); });

const animateStats = () => {
    displayStats.value.forEach((stat) => {
        const increment = Math.ceil(stat.target / 60);
        const timer = setInterval(() => {
            stat.current += increment;
            if (stat.current >= stat.target) {
                stat.current = stat.target;
                clearInterval(timer);
            }
        }, 25);
    });
};

// ============================================================================
// 7. DONNÉES SERVICES & FAQ
// ============================================================================
const ispServices = computed(() => {
    if (props.about?.services) {
        try { return typeof props.about.services === 'string' ? JSON.parse(props.about.services) : props.about.services; } catch (e) {}
    }
    return [
        { title: 'Infrastructures Réseaux', desc: 'Architecture, déploiement et maintenance de réseaux LAN/WAN (Cisco, MikroTik).', icon: 'pi-sitemap' },
        { title: 'Cybersécurité & Audit', desc: 'Tests d\'intrusion, sécurisation des endpoints et protection périmétrique.', icon: 'pi-shield' },
        { title: 'Solutions Cloud & Data', desc: 'Migration, virtualisation (VMware, Hyper-V) et services AWS/Azure.', icon: 'pi-server' },
        { title: 'Infogérance (NOC/SOC)', desc: 'Surveillance 24/7 de vos infrastructures et réponse aux incidents.', icon: 'pi-desktop' }
    ];
});

const faqs = ref([
    { q: 'Les certifications délivrées sont-elles reconnues ?', a: 'Oui, nos cursus vous préparent aux examens internationaux (Cisco CCNA/CCNP, CompTIA, AWS). Vous passez l\'examen final dans un centre agréé.', open: false },
    { q: 'Comment se déroule la formation en ligne ?', a: 'Via notre plateforme, vous accédez à des vidéos à la demande, des quiz interactifs et des laboratoires virtuels (Labs) pour la pratique.', open: false },
    { q: 'Proposez-vous des formations pour les entreprises (B2B) ?', a: 'Absolument. Nous organisons des sessions intra-entreprise sur mesure pour monter en compétence vos équipes techniques.', open: false },
    { q: 'Quels sont les moyens de paiement acceptés ?', a: 'Nous acceptons les paiements par carte bancaire (Visa/Mastercard), PayPal, et les solutions de Mobile Money locales.', open: false }
]);

const toggleFaq = (index) => {
    faqs.value.forEach((faq, i) => { faq.open = i === index ? !faq.open : false; });
};

// ============================================================================
// 8. CYCLE DE VIE
// ============================================================================
let observer = null;

onMounted(() => {
    window.addEventListener('resize', updateWidth);
    if (slides.value.length > 1) startProgress();

    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                if (entry.target.id === 'stats-section' && !statsVisible.value) {
                    statsVisible.value = true;
                    animateStats();
                }
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    setTimeout(() => {
        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
        if (statsSection.value) observer.observe(statsSection.value);
    }, 100);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateWidth);
    if (progressTimer) clearInterval(progressTimer);
    if (observer) observer.disconnect();
});

// ============================================================================
// 9. SEO - DONNÉES STRUCTURÉES (JSON-LD)
// ============================================================================
const schemaOrgData = computed(() => {
    return {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": props.identity.site_name || 'SubnetCongo',
        "description": props.identity.site_description,
        "url": typeof window !== 'undefined' ? window.location.origin : '',
        "logo": props.identity.logo_url || '',
        "sameAs": [
            props.identity.facebook_url || '',
            props.identity.linkedin_url || ''
        ].filter(Boolean)
    };
});
</script>

<template>
    <Head :title="`Accueil - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" :content="identity.site_description || 'Votre partenaire en ingénierie réseau et centre de formation IT de pointe.'">

    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- SECTION 1 : HERO BANNER (Plein écran, immersif) -->
        <!-- ================================================================== -->
        <header
            class="relative min-h-[90vh] flex items-center pt-20 overflow-hidden bg-[#020617] group/hero"
            @mouseenter="isPaused = true" @mouseleave="isPaused = false"
            @touchstart="onTouchStart" @touchend="onTouchEnd"
        >
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <transition-group name="hero-fade">
                    <div v-for="(slide, index) in slides" :key="index" v-show="activeSlide === index" class="absolute inset-0 w-full h-full">
                        <img :src="slide.bgImage" class="w-full h-full object-cover opacity-40 scale-105 animate-slow-zoom" alt="Background" />
                        <div class="absolute inset-0 bg-gradient-to-r from-[#020617] via-[#020617]/90 to-transparent"></div>
                    </div>
                </transition-group>
                <!-- Effets visuels techniques -->
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-50"></div>
                <div class="absolute -top-[30%] -left-[10%] w-[50%] h-[80%] bg-blue-600/20 blur-[150px] rounded-full pointer-events-none"></div>
            </div>

            <!-- Contenu Hero -->
            <div class="relative z-20 max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-12 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-7 space-y-8">
                    <transition name="slide-up-fade" mode="out-in">
                        <div :key="activeSlide" class="space-y-6">
                            <div class="inline-flex items-center gap-3 px-4 py-2 bg-white/5 backdrop-blur-md border border-white/10 rounded-full text-white">
                                <span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span></span>
                                <span class="text-xs font-bold uppercase tracking-widest text-blue-200">{{ slides[activeSlide].badge }}</span>
                            </div>
                            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.1] tracking-tight">
                                {{ slides[activeSlide].title }} <br />
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">
                                    {{ slides[activeSlide].highlight }}
                                </span>
                            </h1>
                            <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5">
                                {{ slides[activeSlide].description }}
                            </p>
                        </div>
                    </transition>

                    <div class="flex flex-wrap gap-4 pt-4 reveal-on-scroll delay-200">
                        <Link :href="route('academy')" class="bg-white hover:bg-slate-100 text-[#0B1221] px-8 py-4 rounded-xl font-bold text-base shadow-lg transition-all flex items-center gap-2">
                            Explorer le Catalogue <i class="pi pi-arrow-right"></i>
                        </Link>
                        <Link :href="route('contact')" class="bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-xl font-bold text-base transition-all flex items-center gap-2">
                            Services B2B <i class="pi pi-briefcase"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Contrôles du Slider -->
            <div class="absolute bottom-6 left-0 right-0 z-30 px-4 sm:px-6 lg:px-12">
                <div class="max-w-[1600px] mx-auto flex items-center gap-3">
                    <button v-for="(_, index) in slides" :key="index" @click="goToSlide(index)" class="relative h-1.5 rounded-full overflow-hidden transition-all duration-300" :class="activeSlide === index ? 'w-24 bg-white/20' : 'w-10 bg-white/10 hover:bg-white/30'">
                        <div v-if="activeSlide === index" class="absolute top-0 left-0 h-full bg-red-500 transition-all duration-75 ease-linear" :style="{ width: `${slideProgress}%` }"></div>
                    </button>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION 2 : PARTENAIRES / MARQUEE (Moins d'espace vertical) -->
        <!-- ================================================================== -->
        <section class="py-8 bg-white border-b border-slate-100 overflow-hidden relative flex items-center">
            <div class="absolute left-0 top-0 bottom-0 w-24 bg-gradient-to-r from-white to-transparent z-10"></div>
            <div class="absolute right-0 top-0 bottom-0 w-24 bg-gradient-to-l from-white to-transparent z-10"></div>

            <div class="max-w-[1600px] mx-auto w-full px-4 flex items-center gap-12">
                <p class="hidden lg:block text-xs font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap shrink-0">Technologies maîtrisées</p>
                <div class="flex space-x-12 animate-marquee whitespace-nowrap items-center flex-1">
                    <div v-for="tech in (technologies.length ? technologies : [
                        {name:'Cisco',icon:'pi-verified'},{name:'Fortinet',icon:'pi-verified'},{name:'AWS',icon:'pi-verified'},{name:'Linux',icon:'pi-verified'},{name:'VMware',icon:'pi-verified'},{name:'Microsoft',icon:'pi-verified'},{name:'Palo Alto',icon:'pi-verified'}
                    ])" :key="tech.slug || tech.name" class="flex items-center gap-2 text-slate-400 hover:text-slate-800 transition duration-300" :style="{ color: tech.color || '' }">
                        <i :class="['pi', tech.icon || 'pi-verified', 'text-lg']"></i>
                        <span class="text-lg font-black font-sans tracking-tight">{{ tech.name }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 2B : TECHNOLOGIES (Pilotées par l'admin via LandingEditor) -->
        <!-- ================================================================== -->
        <section v-if="technologies.length" class="py-20 bg-white relative overflow-hidden">
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>
            <div class="relative max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center mb-12 reveal-on-scroll">
                    <p class="text-xs font-black text-red-500 uppercase tracking-[0.3em] mb-3">Stack technique</p>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter">Maîtrisez les technologies<br/>qui façonnent l'industrie</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    <div v-for="tech in technologies" :key="tech.id" class="reveal-on-scroll group bg-white p-6 rounded-xl border border-slate-100 hover:border-transparent hover:shadow-2xl transition-all duration-300">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110" :style="{ backgroundColor: (tech.color || '#003399') + '12', color: tech.color || '#003399' }">
                            <i :class="['pi', tech.icon || 'pi-cog', 'text-2xl']"></i>
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">{{ tech.category }}</p>
                        <h3 class="text-lg font-black text-slate-900 mb-2">{{ tech.name }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ tech.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 3 : STATISTIQUES (Espace densifié) -->
        <!-- ================================================================== -->
        <section class="py-16 bg-[#F4F6F9]" id="stats-section" ref="statsSection">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <div v-for="(stat, idx) in displayStats" :key="idx" class="reveal-on-scroll bg-white p-8 rounded-xl border border-slate-100 shadow-sm flex flex-col items-center text-center group">
                        <div class="w-14 h-14 rounded-lg flex items-center justify-center mb-4 transition-colors" :style="{ backgroundColor: (stat.color || '#2563EB') + '15', color: stat.color || '#2563EB' }">
                            <i :class="['pi', stat.icon, 'text-xl']"></i>
                        </div>
                        <div class="flex items-baseline gap-1 text-[#0B1221]">
                            <span class="text-4xl lg:text-5xl font-black tracking-tighter">{{ stat.current }}</span>
                            <span class="text-xl font-black text-red-500">+</span>
                        </div>
                        <p class="text-slate-500 font-bold uppercase tracking-wider text-[10px] mt-2">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 4 : CATALOGUE DE COURS (Design fidèle SubnetCongo) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white relative overflow-hidden" id="courses">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12 relative z-10">

                <!-- En-tête + Recherche & Filtres -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6 mb-12 reveal-on-scroll">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">Académie IT</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight">Formations & Certifications</h2>
                    </div>

                    <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-4">
                        <!-- Barre de recherche -->
                        <div class="relative w-full sm:w-64">
                            <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" v-model="searchQuery" placeholder="Rechercher un cours..." class="w-full pl-11 pr-4 py-3 bg-[#F4F6F9] border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                        </div>

                        <!-- Onglets Catégories -->
                        <div class="flex flex-nowrap overflow-x-auto hide-scrollbar gap-2 bg-[#F4F6F9] p-1.5 rounded-xl">
                            <button v-for="cat in allCategories" :key="cat" @click="changeCategory(cat)"
                                class="relative px-5 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300 z-10"
                                :class="selectedCategory === cat ? 'text-white' : 'text-slate-600 hover:text-slate-900'">
                                <span class="relative z-10">{{ cat }}</span>
                                <div v-if="selectedCategory === cat" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0 transition-all"></div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grille des cours (H-FULL, MT-AUTO, Design précis) -->
                <div class="transition-opacity duration-300 w-full" :class="{'opacity-0 scale-95': isAnimatingCourses, 'opacity-100 scale-100': !isAnimatingCourses}">
                    <div v-if="filteredCourses.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 lg:gap-8 w-full">

                        <Link v-for="(course, idx) in filteredCourses" :key="course.id" :href="route('marketplace.show', course.id)"
                            class="group flex flex-col bg-white rounded-[1.25rem] overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-2xl border border-slate-100 transition-all duration-300 h-full w-full"
                        >
                            <!-- Header Image -->
                            <div class="relative h-[220px] sm:h-[240px] w-full shrink-0 overflow-hidden bg-slate-900">
                                <img :src="course.image || DEFAULT_COURSE_IMAGE" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" alt="Course cover" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-60"></div>

                                <!-- Badges (Haut Gauche et Bas Gauche) -->
                                <div v-if="course.is_live" class="absolute top-4 left-4 bg-white text-red-600 text-[11px] font-black uppercase px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span> EN DIRECT
                                </div>
                                <div class="absolute bottom-4 left-4 bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md">
                                    {{ course.category || 'TECH' }}
                                </div>
                            </div>

                            <!-- Contenu (Flex-1) -->
                            <div class="p-6 md:p-8 flex-1 flex flex-col w-full bg-white">
                                <!-- Auteur -->
                                <div class="flex items-center gap-2.5 mb-3">
                                    <img :src="course.instructor_avatar || `${DEFAULT_AVATAR}${course.instructor || 'X'}`" class="w-6 h-6 rounded-full object-cover border border-slate-200"/>
                                    <span class="text-xs text-slate-500 font-bold uppercase">Par {{ course.instructor || 'Expert IT' }}</span>
                                </div>

                                <!-- Titre -->
                                <h3 class="text-[1.35rem] font-bold text-[#0B1221] leading-snug mb-4 line-clamp-2 group-hover:text-[#2563EB] transition-colors">
                                    {{ course.title }}
                                </h3>

                                <!-- Espace flexible -->
                                <div class="flex-1"></div>

                                <!-- Meta (Notes & Heures) -->
                                <div class="flex items-center gap-6 mb-6">
                                    <div class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                                        <i class="pi pi-star-fill text-amber-400 text-xs"></i> {{ course.rating || '4.8' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-sm font-medium text-slate-500">
                                        <i class="pi pi-clock text-slate-400 text-xs"></i> {{ course.duration || '45h' }}
                                    </div>
                                </div>

                                <!-- Action Button (Même comportement visuel) -->
                                <div class="w-full mt-auto">
                                    <div v-if="course.is_live" class="w-full bg-[#E1251B] hover:bg-red-700 text-white text-sm font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-sm shadow-red-500/20">
                                        Suivre la session <i class="pi pi-video"></i>
                                    </div>
                                    <div v-else class="w-full bg-[#0B1221] hover:bg-slate-800 text-white text-sm font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-sm shadow-slate-900/10">
                                        Détails du cours <i class="pi pi-arrow-right"></i>
                                    </div>
                                </div>
                            </div>
                        </Link>

                    </div>

                    <div v-else class="text-center py-24 bg-[#F4F6F9] rounded-[1rem] border border-dashed border-slate-300 w-full">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <i class="pi pi-search text-3xl text-slate-300"></i>
                        </div>
                        <p class="text-lg font-bold text-slate-600">Aucun cours trouvé pour "{{ searchQuery }}"</p>
                        <button @click="searchQuery = ''; selectedCategory = 'Tous'" class="mt-4 text-[#2563EB] font-bold hover:underline text-sm">Réinitialiser les filtres</button>
                    </div>
                </div>

                <div class="text-center mt-12 reveal-on-scroll">
                    <Link :href="route('academy')" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white border-2 border-[#0B1221] text-[#0B1221] font-bold rounded-xl hover:bg-[#0B1221] hover:text-white transition-all duration-300">
                        Voir tout le catalogue <i class="pi pi-arrow-right text-sm"></i>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 5 : LEARNING PATHS (Parcours de formation) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center max-w-3xl mx-auto mb-12 reveal-on-scroll">
                    <h2 class="text-3xl md:text-4xl font-black text-[#0B1221] tracking-tight mb-4">Parcours Métiers</h2>
                    <p class="text-slate-500">Des programmes structurés de A à Z pour atteindre un poste précis.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="(path, i) in learningPaths" :key="i" class="reveal-on-scroll bg-white p-8 rounded-[1.5rem] border border-slate-100 hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 text-slate-50 opacity-50 group-hover:scale-150 transition-transform duration-700 pointer-events-none">
                            <i :class="['pi', path.icon]" style="font-size: 10rem;"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-[#2563EB]/10 text-[#2563EB] flex items-center justify-center mb-6">
                                <i :class="['pi', path.icon, 'text-xl']"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[#0B1221] mb-2">{{ path.title }}</h3>
                            <p class="text-sm text-slate-500 mb-6 h-10">{{ path.desc }}</p>

                            <div class="flex items-center gap-4 text-xs font-bold text-slate-400 mb-8 border-t border-slate-100 pt-4">
                                <span class="flex items-center gap-1"><i class="pi pi-book"></i> {{ path.courses_count }} Cours</span>
                                <span class="flex items-center gap-1"><i class="pi pi-calendar"></i> ~{{ path.time }}</span>
                            </div>

                            <Link :href="route('academy')" class="text-[#2563EB] font-bold text-sm flex items-center gap-2 hover:underline">
                                Découvrir le parcours <i class="pi pi-arrow-right"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 6 : SOLUTIONS ENTREPRISES (Bento très propre) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white" id="solutions">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex flex-col md:flex-row gap-16 items-center">
                    <!-- Texte -->
                    <div class="w-full md:w-1/3 reveal-on-scroll">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-red-600 rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-red-600">B2B & Ingénierie</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight leading-tight mb-6">
                            Solutions Techniques <br/> sur-mesure
                        </h2>
                        <p class="text-slate-500 mb-8 leading-relaxed">
                            En plus de la formation, nous accompagnons les entreprises dans le déploiement et la sécurisation de leurs infrastructures critiques.
                        </p>
                        <Link :href="route('contact')" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">
                            Demander un Audit
                        </Link>
                    </div>

                    <!-- Grille -->
                    <div class="w-full md:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div v-for="(service, i) in ispServices" :key="i" class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-lg border border-transparent hover:border-slate-200 transition-all">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#0B1221]">
                                <i :class="['pi', service.icon, 'text-xl']"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#0B1221] mb-2">{{ service.title }}</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">{{ service.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION 7 : FAQ (Dense et propre) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#0B1221] text-white">
            <div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center mb-12 reveal-on-scroll">
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
        <!-- SECTION 8 : CTA FINAL -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white border-t border-slate-100 text-center">
            <div class="max-w-[800px] mx-auto px-4 reveal-on-scroll">
                <h2 class="text-4xl md:text-5xl font-black text-[#0B1221] tracking-tight mb-6">Prêt à développer vos compétences ?</h2>
                <p class="text-lg text-slate-500 mb-10">Rejoignez des milliers de professionnels qui nous font confiance.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('register')" class="w-full sm:w-auto px-10 py-4 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20 text-base">
                        Créer un compte gratuit
                    </Link>
                    <Link :href="route('contact')" class="w-full sm:w-auto px-10 py-4 bg-[#F4F6F9] text-[#0B1221] font-bold rounded-xl hover:bg-slate-200 transition-colors text-base">
                        Nous contacter
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
/* Animations existantes conservées pour la fluidité */
@keyframes slow-zoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.08); }
}
.animate-slow-zoom {
    animation: slow-zoom 20s ease-in-out infinite alternate;
}

.hero-fade-enter-active, .hero-fade-leave-active { transition: opacity 1s ease-in-out; }
.hero-fade-enter-from, .hero-fade-leave-to { opacity: 0; }

.slide-up-fade-enter-active, .slide-up-fade-leave-active { transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-up-fade-enter-from { opacity: 0; transform: translateY(20px); }
.slide-up-fade-leave-to { opacity: 0; transform: translateY(-20px); }

@keyframes marquee {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-100%); }
}
.animate-marquee {
    animation: marquee 40s linear infinite;
    display: inline-flex;
    min-width: 100%;
}
.animate-marquee:hover { animation-play-state: paused; }

.reveal-on-scroll {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}
.reveal-on-scroll.is-revealed {
    opacity: 1; transform: translateY(0);
}

/* Scrollbar cachée pour les onglets horizontaux */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
