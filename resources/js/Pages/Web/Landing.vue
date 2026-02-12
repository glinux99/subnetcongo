<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // Assure-toi que le chemin est correct

/**
 * --- ÉTATS RÉACTIFS ---
 */
const activeSlide = ref(0);
const activeFaq = ref(null);
const selectedCategory = ref('Tous');

/**
 * --- DONNÉES DE LA PAGE ---
 */
const heroSlides = [
    {
        id: 1,
        badge: "Leader Connectivité RDC",
        title: "Désenclaver le",
        highlight: "Congo par le Web.",
        description: "Déploiement stratégique d'antennes VSAT et solutions Starlink pour les zones minières et forestières reculées. Une latence record pour une productivité sans limites.",
        bgImage: "https://images.unsplash.com/photo-1544652478-6653e09f18a2?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1573166364524-d9dbfd8bbf83?q=80&w=2069",
        icon: "pi-map-marker",
        cardTitle: "Omniprésence Nationale",
        cardSub: "Couverture 26 provinces garantie."
    },
    {
        id: 2,
        badge: "Élite Technologique",
        title: "Façonner les",
        highlight: "Experts de Demain.",
        description: "Plongez dans un écosystème d'apprentissage de classe mondiale. De la certification Cisco CCNA à l'Audit de Sécurité, boostez votre carrière internationale.",
        bgImage: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070",
        icon: "pi-book",
        cardTitle: "Certification Cisco Pro",
        cardSub: "92% de taux de réussite aux examens."
    },
    {
        id: 3,
        badge: "Souveraineté Numérique",
        title: "Bâtir les",
        highlight: "Routes du Digital.",
        description: "Maintenance critique, tirage de fibre optique noire et ingénierie de réseaux complexes pour les sièges sociaux et institutions gouvernementales.",
        bgImage: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        mainImage: "https://images.unsplash.com/photo-1551703599-6b3e8379aa8c?q=80&w=2070",
        icon: "pi-cog",
        cardTitle: "Uptime 99.99%",
        cardSub: "Monitoring NOC en temps réel."
    }
];

const ispServices = [
    {
        title: "VSAT & Satellite Elite",
        icon: "pi-globe",
        color: "blue",
        desc: "L'accès ultime pour les sites isolés. Forfaits illimités adaptés aux besoins industriels et humanitaires.",
        features: ["Bandes Ku, Ka & C", "Priorisation du trafic (QoS)", "Installation en moins de 48h"]
    },
    {
        title: "Starlink Business Pro",
        icon: "pi-bolt",
        color: "red",
        desc: "Vitesse révolutionnaire jusqu'à 350 Mbps. Nous sommes votre installateur certifié pour une stabilité maximale.",
        features: ["Kits Haute Performance", "Support technique local", "Facturation RDC simplifiée"]
    },
    {
        title: "Fibre Noire Dédiée",
        icon: "pi-sitemap",
        color: "blue",
        desc: "Connexions symétriques Giga-bit pour les datacenters et entreprises multisites à Goma.",
        features: ["Latence < 10ms", "Monitoring 24/7/365", "Garantie Temps de Rétablissement"]
    }
];

const allCourses = ref([
    {
        id: 1,
        title: "Installation & Optimisation Starlink Business",
        category: "Réseaux",
        instructor: "Eng. Marc L.",
        instructorAvatar: "https://i.pravatar.cc/150?u=marc",
        image: "https://images.unsplash.com/photo-1517433456452-f9633a875f6f?q=80&w=2070",
        rating: 4.9,
        duration: "12h",
        isLive: true,
        liveLink: "https://zoom.us/subnet-live-session-1"
    },
    {
        id: 2,
        title: "Sécurité des SI : Audit et Pentesting",
        category: "Sécurité",
        instructor: "Sarah K.",
        instructorAvatar: "https://i.pravatar.cc/150?u=sarah",
        image: "https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070",
        rating: 4.8,
        duration: "45h",
        isLive: false
    },
    {
        id: 3,
        title: "Administration Cisco CCNA (Module 1)",
        category: "Réseaux",
        instructor: "Jean-Paul M.",
        instructorAvatar: "https://i.pravatar.cc/150?u=jp",
        image: "https://images.unsplash.com/photo-1558494949-ef010911182e?q=80&w=2070",
        rating: 4.7,
        duration: "60h",
        isLive: true,
        liveLink: "https://youtube.com/live/subnet-cisco"
    },
    {
        id: 4,
        title: "Architecture Cloud AWS pour Entreprises",
        category: "Cloud",
        instructor: "Ali B.",
        instructorAvatar: "https://i.pravatar.cc/150?u=ali",
        image: "https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072",
        rating: 5.0,
        duration: "30h",
        isLive: false
    }
]);

const statistics = [
    { value: "15K+", label: "Apprenants certifiés" },
    { value: "850+", label: "Sites VSAT actifs" },
    { value: "24/7", label: "Support Technique" },
    { value: "10+", label: "Années d'Expérience" }
];

const steps = [
    { title: "Consultation", desc: "Analyse de vos besoins spécifiques par nos ingénieurs." },
    { title: "Déploiement", desc: "Installation rigoureuse par nos équipes mobiles partout en RDC." },
    { title: "Formation", desc: "Transfert de compétences pour une autonomie totale." },
    { title: "Maintenance", desc: "Suivi proactif et assistance immédiate en cas d'alerte." }
];

const faqs = [
    { q: "Proposez-vous Starlink partout en RDC ?", a: "Oui, nous couvrons l'intégralité du territoire national, des zones urbaines aux mines les plus isolées." },
    { q: "Les formations sont-elles reconnues ?", a: "Nos formations sont certifiantes et alignées sur les standards internationaux (Cisco, CompTIA, etc.)." },
    { q: "Quels sont vos délais d'intervention ?", a: "Pour les urgences critiques, nos équipes peuvent intervenir sous 4 à 12 heures selon la zone géographique." },
    { q: "Offrez-vous des bourses d'études ?", a: "Chaque année, SubnetCongo offre 50 bourses aux jeunes talents congolais méritants." }
];

/**
 * --- LOGIQUE ---
 */
const filteredCourses = computed(() => {
    if (selectedCategory.value === 'Tous') return allCourses.value;
    return allCourses.value.filter(c => c.category === selectedCategory.value);
});

let carouselTimer;
onMounted(() => {
    carouselTimer = setInterval(() => {
        activeSlide.value = (activeSlide.value + 1) % heroSlides.length;
    }, 8000);
});

onUnmounted(() => {
    clearInterval(carouselTimer);
});
</script>

<template>
    <Head title="Accueil - Connectivité & Formation d'Élite" />

    <AppLayout>
        <!-- --- HERO SECTION --- -->
        <header class="relative min-h-[115vh] flex items-center pt-24 overflow-hidden bg-[#001A4D]">
            <div class="absolute inset-0 z-0">
                <transition-group name="fade-bg">
                    <div v-for="(slide, index) in heroSlides" :key="slide.id" v-show="activeSlide === index" class="absolute inset-0">
                        <div class="absolute inset-0 bg-black/40 z-10"></div>
                        <img :src="slide.bgImage" class="w-full h-full object-cover scale-110 animate-slow-zoom" />
                    </div>
                </transition-group>
                <div class="absolute inset-0 bg-gradient-to-b from-[#001A4D]/80 via-transparent to-[#001A4D]"></div>
            </div>

            <div class="relative z-20 max-w-[1500px] mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-20 items-center">
                <div class="lg:col-span-7 space-y-10">
                    <transition name="slide-fade-content" mode="out-in">
                        <div :key="activeSlide" class="space-y-10">
                            <div class="inline-flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-2xl border border-white/20 rounded-full text-white">
                                <span class="flex h-3 w-3 rounded-full bg-red-500 animate-ping"></span>
                                <span class="text-xs font-black uppercase tracking-[0.3em]">{{ heroSlides[activeSlide].badge }}</span>
                            </div>
                            <h1 class="text-6xl md:text-[7.5rem] font-black text-white leading-[0.95] tracking-tighter">
                                {{ heroSlides[activeSlide].title }} <br/>
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-orange-400 to-yellow-300 italic">
                                    {{ heroSlides[activeSlide].highlight }}
                                </span>
                            </h1>
                            <p class="text-2xl text-blue-100/80 max-w-2xl font-medium leading-relaxed border-l-4 border-red-500 pl-8">
                                {{ heroSlides[activeSlide].description }}
                            </p>
                        </div>
                    </transition>

                    <div class="flex flex-wrap gap-6 pt-6">
                        <button class="group relative bg-white text-blue-900 px-12 py-6 rounded-lg font-black text-xl shadow-2xl overflow-hidden transition-all hover:scale-105">
                            <span class="relative z-10 flex items-center gap-4">Commencer l'Aventure <i class="pi pi-bolt"></i></span>
                            <div class="absolute inset-0 bg-red-500 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                        </button>
                    </div>
                </div>

                <!-- Floating Visual -->
                <div class="lg:col-span-5 relative hidden lg:block">
                    <div class="relative z-20 rounded-lg overflow-hidden border-[15px] border-white/5 shadow-2xl animate-float h-[650px]">
                        <transition name="image-swap" mode="out-in">
                            <img :key="activeSlide" :src="heroSlides[activeSlide].mainImage" class="w-full h-full object-cover" />
                        </transition>
                    </div>
                </div>
            </div>

            <!-- Wave Separator -->
            <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-[0] transform translate-y-[1px]">
                <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="relative block w-full h-[120px] fill-[#FDFFFF]">
                    <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
                </svg>
            </div>
        </header>

        <!-- --- STATISTIQUES --- -->
        <section class="py-20 bg-[#FDFFFF]">
            <div class="max-w-[1500px] mx-auto px-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="stat in statistics" :key="stat.label" class="p-10 rounded-lg bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl transition-all group">
                        <p class="text-5xl font-black text-[#003399] mb-2 group-hover:scale-110 transition-transform inline-block">{{ stat.value }}</p>
                        <p class="text-slate-500 font-black uppercase tracking-widest text-xs">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- --- ISP SERVICES --- -->
        <section class="py-32 px-6 bg-white overflow-hidden" id="solutions">
            <div class="max-w-[1500px] mx-auto">
                <div class="flex flex-col lg:flex-row justify-between items-end mb-24 gap-10">
                    <div class="max-w-3xl space-y-6">
                        <div class="inline-flex items-center gap-2 text-[#EE1C25]">
                            <span class="w-12 h-[2px] bg-[#EE1C25]"></span>
                            <span class="font-black uppercase tracking-[0.5em] text-sm italic">Le Réseau du Futur</span>
                        </div>
                        <h3 class="text-6xl md:text-7xl font-black text-slate-900 tracking-tighter">Nous connectons là où les autres <span class="text-[#003399] underline decoration-red-500 decoration-8 underline-offset-[10px]">s'arrêtent</span>.</h3>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <div v-for="(service, idx) in ispServices" :key="service.title"
                        class="group relative p-12 bg-slate-50 rounded-lg border border-transparent hover:border-red-100 hover:bg-white hover:shadow-2xl transition-all duration-700">
                        <div class="w-24 h-24 rounded-lg flex items-center justify-center mb-10 bg-white shadow-xl group-hover:rotate-[15deg] transition-all">
                            <i :class="`pi ${service.icon} text-4xl text-${service.color}-600` "></i>
                        </div>
                        <h4 class="text-3xl font-black text-slate-900 mb-6">{{ service.title }}</h4>
                        <p class="text-slate-500 mb-10 font-medium italic">{{ service.desc }}</p>
                        <button class="w-full py-6 rounded-3xl bg-slate-900 text-white font-black hover:bg-[#003399] transition-all">
                            Demander un devis <i class="pi pi-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

    <!-- --- [SECTION 5] BENTO GRID ACADEMY --- -->
    <section class="py-32 px-6 bg-[#001A4D] relative rounded-lg mx-4 my-10 shadow-inner overflow-hidden" id="academy">

      <!-- Fond Abstrait Digital -->
      <div class="absolute inset-0 opacity-[0.03] pointer-events-none">
        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(#ffffff_2px,transparent_2px)] [background-size:50px_50px]"></div>
      </div>

      <div class="max-w-[1500px] mx-auto relative z-10">
        <div class="flex flex-col lg:flex-row justify-between items-start mb-24 gap-12">
            <div class="max-w-3xl">
                <h2 class="text-red-500 font-black uppercase tracking-[0.5em] text-sm mb-6 flex items-center gap-4">
                    <span class="w-8 h-8 rounded-full bg-red-500/10 flex items-center justify-center"><i class="pi pi-bolt text-[10px]"></i></span>
                    Subnet Academy
                </h2>
                <h3 class="text-5xl md:text-[5.5rem] font-black text-white tracking-tighter leading-[0.9] mb-8">Apprenez avec des <br/> <span class="italic text-blue-400 underline decoration-red-500 decoration-wavy underline-offset-[15px]">Leaders du Numérique</span>.</h3>
                <p class="text-2xl text-blue-100/60 font-medium max-w-2xl leading-relaxed">Formations intensives basées sur des cas réels africains, certifiées par les géants de la technologie.</p>
            </div>
            <div class="flex flex-col gap-4 w-full lg:w-auto">
                <button class="px-12 py-6 bg-white rounded-3xl font-black text-blue-900 hover:bg-red-500 hover:text-white transition-all shadow-2xl flex items-center justify-center gap-4 group">
                    Explorer le catalogue (450+) <i class="pi pi-search group-hover:scale-125 transition-transform"></i>
                </button>
                <div class="flex items-center gap-4 px-6 py-3 bg-white/5 border border-white/10 rounded-lg justify-center">
                    <i class="pi pi-verified text-green-400"></i>
                    <span class="text-white text-xs font-bold uppercase tracking-widest">Accrédité par le Ministère de l'Éducation Tech</span>
                </div>
            </div>
        </div>

        <!-- BENTO LAYOUT -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Main Course Card -->
            <div class="md:col-span-8 bg-gradient-to-br from-[#003399] to-[#001A4D] rounded-lg p-12 relative overflow-hidden group border border-white/10 shadow-2xl min-h-[500px] flex flex-col justify-between">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-10">
                        <span class="bg-red-600 text-white px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-widest shadow-xl shadow-red-500/20">Bootcamp 2024</span>
                        <span class="bg-blue-400/20 text-blue-200 px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-widest">Hybride (Présentiel/Online)</span>
                    </div>
                    <h4 class="text-5xl md:text-6xl font-black text-white mb-8 max-w-2xl leading-[1.1]">Expert en Ingénierie Réseaux & Installation VSAT Stratégique</h4>
                    <p class="text-blue-200 text-xl mb-12 max-w-lg leading-relaxed opacity-80">Devenez un technicien d'élite capable de déployer des backbones internet en conditions extrêmes.</p>
                </div>

                <div class="relative z-10 flex flex-wrap items-center gap-10 border-t border-white/10 pt-10">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-red-500 shadow-inner">
                            <i class="pi pi-clock text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">180 Heures</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Durée intensive</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-blue-400 shadow-inner">
                            <i class="pi pi-users text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">15 Places</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Par session</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-lg bg-white/5 flex items-center justify-center text-green-400 shadow-inner">
                            <i class="pi pi-briefcase text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-black text-lg">Placement</p>
                            <p class="text-blue-200/50 text-xs font-bold uppercase">Garanti à 85%</p>
                        </div>
                    </div>
                </div>

                <img src="https://images.unsplash.com/photo-1769697705387-591bfddc2861?q=80&w=687&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" class="absolute top-0 right-0 w-full h-full object-cover opacity-20 group-hover:scale-105 transition-transform duration-[5s] pointer-events-none" />
            </div>

            <!-- Side Bento Column -->
            <div class="md:col-span-4 space-y-8">
                <!-- Course Item 1 -->
                <div v-for="c in courses" :key="c.id" class="bg-white/5 backdrop-blur-3xl rounded-lg p-8 border border-white/10 hover:bg-white/10 transition-all cursor-pointer group shadow-2xl relative overflow-hidden">
                    <div class="flex gap-8 items-center relative z-10">
                        <div class="w-20 h-20 rounded-[1.5rem] bg-gradient-to-br from-blue-500/20 to-blue-900/20 flex items-center justify-center text-blue-400 group-hover:bg-red-500 group-hover:text-white group-hover:rotate-12 transition-all duration-500 shadow-2xl">
                            <i :class="`pi ${c.icon} text-3xl` "></i>
                        </div>
                        <div>
                            <h5 class="text-white font-black mb-2 text-xl leading-tight italic">{{ c.title }}</h5>
                            <p class="text-blue-200/40 text-[10px] font-black uppercase tracking-[0.3em]">{{ c.category }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-5 -bottom-5 text-7xl font-black text-white/5 italic opacity-0 group-hover:opacity-100 transition-opacity">IT</div>
                </div>

                <!-- Live Support Card -->
                <div class="bg-gradient-to-r from-[#EE1C25] to-red-700 rounded-lg p-10 text-white flex flex-col justify-between group cursor-pointer hover:scale-[1.02] transition-all shadow-[0_30px_60px_rgba(238,28,37,0.3)] relative overflow-hidden h-[250px]">
                    <div class="relative z-10">
                        <span class="bg-white/20 px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest mb-4 inline-block">Online Now</span>
                        <h6 class="text-3xl font-black leading-tight italic">Prêt à booster votre avenir ?</h6>
                    </div>
                    <div class="relative z-10 flex items-center justify-between">
                        <p class="text-red-100 font-bold">Parler avec un expert</p>
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-red-600 shadow-2xl group-hover:rotate-[360deg] transition-all duration-700">
                            <i class="pi pi-whatsapp text-3xl"></i>
                        </div>
                    </div>
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/asfalt-dark.png')] opacity-10"></div>
                </div>
            </div>
        </div>
      </div>
    </section>

    <!-- --- [SECTION 6] PROCESSUS - COMMENT ÇA MARCHE --- -->
    <section class="py-32 px-6">
        <div class="max-w-[1500px] mx-auto">
            <div class="text-center mb-24 space-y-4">
                <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.5em] text-sm">Le Parcours Subnet</h2>
                <h3 class="text-6xl font-black text-slate-900 tracking-tighter">Étape par étape vers l'excellence.</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 relative">
                <!-- Ligne de connection -->
                <div class="hidden lg:block absolute top-1/2 left-0 w-full h-1 bg-slate-100 -z-10"></div>

                <div v-for="(step, idx) in steps" :key="step.title" class="text-center group">
                    <div class="w-24 h-24 rounded-full bg-white border-8 border-slate-50 flex items-center justify-center mx-auto mb-8 shadow-xl group-hover:border-red-100 transition-all relative">
                        <span class="text-2xl font-black text-slate-900">{{ idx + 1 }}</span>
                        <div class="absolute inset-0 rounded-full border-2 border-dashed border-red-500 animate-spin-slow opacity-0 group-hover:opacity-100"></div>
                    </div>
                    <h5 class="text-xl font-black mb-4 text-slate-800">{{ step.title }}</h5>
                    <p class="text-slate-500 font-medium leading-relaxed">{{ step.desc }}</p>
                </div>
            </div>
        </div>
    </section>
        <!-- --- ACADEMY COURSES --- -->
         <section class="py-32 px-6 bg-slate-50" id="academy-courses">
      <div class="max-w-[1500px] mx-auto">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-8">
          <div class="max-w-2xl">
            <h2 class="text-[#EE1C25] font-black uppercase tracking-[0.4em] text-sm mb-4">Formations en Direct</h2>
            <h3 class="text-5xl font-black text-slate-900 tracking-tighter">Développez vos compétences en <span class="text-[#003399]">Temps Réel</span>.</h3>
          </div>
          <div class="flex gap-2 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
            <button v-for="cat in ['Tous', 'Réseaux', 'Sécurité', 'Cloud']" :key="cat"
                    @click="selectedCategory = cat"
                    :class="['px-6 py-3 rounded-xl font-black text-sm transition-all', selectedCategory === cat ? 'bg-[#003399] text-white' : 'text-slate-500 hover:bg-slate-50']">
              {{ cat }}
            </button>
          </div>
        </div>

        <!-- Grid des Cours -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
          <div v-for="course in filteredCourses" :key="course.id"
               class="group bg-white rounded-lg overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-500 flex flex-col h-full">

            <!-- Image & Badge Live -->
            <div class="relative h-56 overflow-hidden">
              <img :src="course.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

              <!-- INDICATEUR LIVE -->
              <div v-if="course.isLive" class="absolute top-6 left-6 flex items-center gap-2 bg-white/95 backdrop-blur-md px-4 py-2 rounded-full shadow-xl">
                <span class="flex h-2 w-2 rounded-full bg-red-600 animate-pulse"></span>
                <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">En Direct</span>
              </div>

              <div class="absolute bottom-6 left-6">
                <span class="px-3 py-1 bg-blue-600 text-white text-[9px] font-black rounded-lg uppercase tracking-widest">{{ course.category }}</span>
              </div>
            </div>

            <!-- Contenu -->
            <div class="p-8 flex flex-col flex-1">
              <div class="flex items-center gap-2 mb-4">
                <img :src="course.instructorAvatar" class="w-6 h-6 rounded-full" />
                <span class="text-xs font-bold text-slate-400">Par {{ course.instructor }}</span>
              </div>
              <h4 class="text-xl font-black text-slate-900 mb-4 leading-tight group-hover:text-[#003399] transition-colors">{{ course.title }}</h4>

              <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-50">
                <div class="flex items-center gap-4">
                   <div class="flex items-center gap-1 text-amber-400">
                     <i class="pi pi-star-fill text-xs"></i>
                     <span class="text-slate-900 font-black text-xs">{{ course.rating }}</span>
                   </div>
                   <div class="flex items-center gap-1 text-slate-400">
                     <i class="pi pi-clock text-xs"></i>
                     <span class="text-xs font-bold">{{ course.duration }}</span>
                   </div>
                </div>
              </div>

              <!-- BOUTON D'ACTION DYNAMIQUE -->
              <a v-if="course.isLive" :href="course.liveLink" target="_blank"
                 class="mt-6 w-full py-4 bg-red-600 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-red-700 shadow-lg shadow-red-100 transition-all">
                Suivre la session <i class="pi pi-video"></i>
              </a>
              <button v-else class="mt-6 w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-[#003399] transition-all">
                Détails du cours <i class="pi pi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Banner Academy Promo -->
        <div class="mt-20 bg-[#003399] rounded-[3rem] p-12 relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-10">
          <div class="relative z-10 text-center lg:text-left">
            <h4 class="text-3xl md:text-4xl font-black text-white mb-4 italic">Devenez instructeur certifié Subnet</h4>
            <p class="text-blue-100 font-medium text-lg">Partagez votre expertise avec des milliers d'apprenants en RDC.</p>
          </div>
          <button class="relative z-10 px-12 py-5 bg-[#EE1C25] text-white rounded-2xl font-black text-lg shadow-2xl hover:scale-105 transition-all">Publier un cours</button>
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
        </div>

      </div>
    </section>

        <!-- --- FAQ --- -->
        <!-- --- [SECTION 9] FAQ --- -->
    <section class="py-32 px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-20 space-y-4">
                <h2 class="text-red-500 font-black uppercase tracking-widest text-xs">Questions Fréquentes</h2>
                <h3 class="text-5xl font-black text-slate-900 tracking-tighter">Tout ce que vous devez savoir.</h3>
            </div>

            <div class="space-y-6">
                <div v-for="(item, idx) in faqs" :key="idx" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden transition-all">
                    <button @click="activeFaq = activeFaq === idx ? null : idx" class="w-full p-8 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                        <span class="text-xl font-black text-slate-800">{{ item.q }}</span>
                        <i :class="['pi text-[#EE1C25] transition-transform', activeFaq === idx ? 'pi-minus rotate-180' : 'pi-plus']"></i>
                    </button>
                    <div v-show="activeFaq === idx" class="p-8 pt-0 text-slate-500 font-medium leading-relaxed border-t border-slate-50">
                        {{ item.a }}
                    </div>
                </div>
            </div>
        </div>
    </section>
    </AppLayout>
</template>

<style scoped>
/* --- ANIMATIONS CAROUSEL --- */
.fade-bg-enter-active, .fade-bg-leave-active { transition: opacity 2s ease-in-out; }
.fade-bg-enter-from, .fade-bg-leave-to { opacity: 0; }

.slide-fade-content-enter-active, .slide-fade-content-leave-active { transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-fade-content-enter-from { opacity: 0; transform: translateY(40px) scale(0.95); }
.slide-fade-content-leave-to { opacity: 0; transform: translateY(-40px) scale(1.05); }

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(1deg); }
}
.animate-float { animation: float 8s ease-in-out infinite; }

@keyframes slow-zoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.25); }
}
.animate-slow-zoom { animation: slow-zoom 25s linear infinite alternate; }
</style>
