<script setup>
/**
 * ============================================================================
 * COMPOSANT : PAGE À PROPOS (About.vue)
 * DESCRIPTION : Page institutionnelle — même langage visuel que la Landing.
 * Hero sombre immersif, story, statistiques animées, équipe et CTA final.
 * ============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    page: { type: Object, default: null },
    about: { type: Object, default: () => ({}) },
    team: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    testimonials: { type: Array, default: () => [] },
    technologies: { type: Array, default: () => [] },
});

// ============================================================================
// DONNÉES
// ============================================================================
const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=f1f5f9&color=64748b&name=';

const parseJson = (value, fallback) => {
    if (!value) return fallback;
    if (typeof value !== 'string') return value;
    try { return JSON.parse(value); } catch (e) { return fallback; }
};

const story = computed(() =>
    props.page?.content ||
    props.about?.history ||
    props.identity.site_description ||
    "SubnetCongo est un centre d'excellence numérique dédié à la formation et au déploiement d'infrastructures en République Démocratique du Congo."
);

const mission = computed(() => props.about?.mission || 'Démocratiser l\'accès aux compétences technologiques de pointe pour les professionnels et les entreprises congolaises.');
const vision = computed(() => props.about?.vision || 'Devenir la référence régionale en formation IT certifiante et en ingénierie de réseaux.');

const values = computed(() => parseJson(props.about?.values, [
    { title: 'Excellence', desc: 'Des programmes alignés sur les standards internationaux et animés par des experts certifiés.', icon: 'pi-verified' },
    { title: 'Accessibilité', desc: 'Des formats flexibles, en ligne et en présentiel, pensés pour les contraintes du terrain.', icon: 'pi-users' },
    { title: 'Pratique', desc: 'Des laboratoires virtuels et des projets réels pour transformer le savoir en compétence.', icon: 'pi-desktop' },
    { title: 'Impact', desc: 'Un ancrage fort en RDC : former les talents qui font avancer les infrastructures locales.', icon: 'pi-globe' },
]));

const services = computed(() => parseJson(props.about?.services, [
    { title: 'Infrastructures Réseaux', desc: 'Architecture, déploiement et maintenance de réseaux LAN/WAN (Cisco, MikroTik).', icon: 'pi-sitemap' },
    { title: 'Cybersécurité & Audit', desc: 'Tests d\'intrusion, sécurisation des endpoints et protection périmétrique.', icon: 'pi-shield' },
    { title: 'Solutions Cloud & Data', desc: 'Migration, virtualisation (VMware, Hyper-V) et services AWS/Azure.', icon: 'pi-server' },
    { title: 'Infogérance (NOC/SOC)', desc: 'Surveillance 24/7 de vos infrastructures et réponse aux incidents.', icon: 'pi-desktop' },
]));

const processSteps = [
    { icon: 'pi-search', title: 'Audit & diagnostic', desc: 'Nous analysons votre infrastructure, vos besoins et vos objectifs pour concevoir la bonne approche.' },
    { icon: 'pi-pencil', title: 'Conception du programme', desc: 'Un parcours sur mesure est construit : cours, labs, certification et calendrier adapté à vos équipes.' },
    { icon: 'pi-play', title: 'Formation & accompagnement', desc: 'Sessions en ligne ou sur site, suivi de progression et support continu tout au long du parcours.' },
    { icon: 'pi-chart-line', title: 'Évaluation & certification', desc: 'Quiz, projets réels et examens finaux. Vos équipes repartent avec des compétences mesurables.' },
];

// ============================================================================
// STATISTIQUES ANIMÉES (même mécanique que la Landing)
// ============================================================================
const statsSection = ref(null);
const statsVisible = ref(false);
const displayStats = ref([]);

const buildStats = () => [
    { current: 0, target: parseInt(props.stats?.students ?? 0, 10) || 3500, label: 'Apprenants formés', icon: 'pi-users', color: '#003399' },
    { current: 0, target: parseInt(props.stats?.courses ?? 0, 10) || 120, label: 'Cours & Modules', icon: 'pi-book', color: '#2563EB' },
    { current: 0, target: parseInt(props.stats?.instructors ?? 0, 10) || 25, label: 'Experts Certifiés', icon: 'pi-verified', color: '#10B981' },
    { current: 0, target: parseInt(props.stats?.countries ?? 0, 10) || 1, label: 'Pays desservis', icon: 'pi-globe', color: '#E1251B' },
];

displayStats.value = buildStats();

const animateStats = () => {
    displayStats.value.forEach((stat) => {
        const increment = Math.max(1, Math.ceil(stat.target / 60));
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
// ANIMATIONS AU SCROLL (Intersection Observer — même que Landing)
// ============================================================================
let observer = null;

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                if (entry.target.id === 'about-stats' && !statsVisible.value) {
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

onUnmounted(() => { if (observer) observer.disconnect(); });
</script>

<template>
    <Head :title="`À propos - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" :content="identity.site_description || 'Découvrez SubnetCongo, centre de formation et d\'excellence numérique en RDC.'">
    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- HERO : signature sombre Landing, immersif -->
        <!-- ================================================================== -->
        <header class="relative min-h-[75vh] flex items-center pt-20 overflow-hidden bg-[#020617]">
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-50"></div>
                <div class="absolute -top-[30%] -left-[10%] w-[50%] h-[80%] bg-blue-600/20 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute -bottom-[20%] -right-[10%] w-[40%] h-[60%] bg-red-600/10 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#F4F6F9]"></div>
            </div>

            <div class="relative z-20 max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-12">
                <div class="max-w-3xl space-y-8">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-white/5 backdrop-blur-md border border-white/10 rounded-full text-white reveal-on-scroll">
                        <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-blue-200">À propos de nous</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.05] tracking-tight reveal-on-scroll delay-100">
                        {{ page?.title || identity.site_name || 'SubnetCongo' }} <br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">
                            {{ about.tagline ? about.tagline.split(' ').slice(0, 3).join(' ') : 'Centre d\'excellence' }}
                        </span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5 reveal-on-scroll delay-200">
                        {{ about.tagline || identity.site_tagline || 'Votre partenaire en ingénierie réseau et centre de formation IT de pointe.' }}
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4 reveal-on-scroll delay-300">
                        <Link :href="route('academy')" class="bg-white hover:bg-slate-100 text-[#0B1221] px-8 py-4 rounded-xl font-bold text-base shadow-lg transition-all flex items-center gap-2">
                            Explorer le Catalogue <i class="pi pi-arrow-right"></i>
                        </Link>
                        <Link :href="route('contact')" class="bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white px-8 py-4 rounded-xl font-bold text-base transition-all flex items-center gap-2">
                            Nous contacter <i class="pi pi-briefcase"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION : STACK TECHNIQUE — Bandeau défilant continu -->
        <!-- ================================================================== -->
        <section v-if="technologies.length" class="relative overflow-hidden bg-white border-b border-slate-100">
            <div class="pointer-events-none absolute inset-y-0 left-0 w-20 sm:w-32 bg-gradient-to-r from-white via-white/70 to-transparent z-10"></div>
            <div class="pointer-events-none absolute inset-y-0 right-0 w-20 sm:w-32 bg-gradient-to-l from-white via-white/70 to-transparent z-10"></div>

            <div class="max-w-[1600px] mx-auto px-3 sm:px-6 lg:px-12 flex items-center gap-4 sm:gap-8 lg:gap-12 py-4 sm:py-5">
                <span class="sr-only">Technologies maîtrisées : {{ technologies.map(t => t.name).join(', ') }}</span>

                <div class="flex items-center gap-3 sm:gap-4 shrink-0 pr-4 sm:pr-8 lg:pr-12 border-r border-slate-200">
                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-[#003399] to-[#1e40af] flex items-center justify-center shadow-lg shadow-blue-900/10">
                        <i class="pi pi-cog text-white text-base sm:text-lg"></i>
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm sm:text-base font-black text-slate-900 tracking-tight">Technologies</p>
                        <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 mt-0.5">Maîtrisées</p>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden" aria-hidden="true">
                    <div class="animate-marquee flex items-center gap-6 sm:gap-10 whitespace-nowrap w-max">
                        <div v-for="(tech, i) in [...technologies, ...technologies]" :key="`${tech.id ?? tech.slug}-${i}`"
                             class="flex items-center gap-2">
                            <i :class="['pi', tech.icon || 'pi-cog']" class="text-lg sm:text-xl" :style="{ color: tech.color || '#003399' }"></i>
                            <span class="text-base sm:text-lg font-black tracking-tight" :style="{ color: tech.color || '#0B1221' }">{{ tech.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : NOTRE HISTOIRE / MISSION (Layout bento comme Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white relative overflow-hidden">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex flex-col md:flex-row gap-16 items-start">
                    <!-- Texte sticky -->
                    <div class="w-full md:w-1/3 reveal-on-scroll md:sticky md:top-32">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">Qui sommes-nous</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight leading-tight mb-6">
                            Une vision, <br/>des experts terrain
                        </h2>
                        <Link :href="route('realizations')" class="inline-flex items-center gap-2 text-[#2563EB] font-bold hover:underline">
                            Voir nos réalisations <i class="pi pi-arrow-right text-sm"></i>
                        </Link>
                    </div>

                    <!-- Contenu -->
                    <div class="w-full md:w-2/3 space-y-8">
                        <div class="reveal-on-scroll bg-[#F4F6F9] p-8 lg:p-10 rounded-[1.25rem] border border-slate-100">
                            <p class="text-slate-600 text-lg leading-relaxed whitespace-pre-line">{{ story }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] border border-slate-100">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#2563EB]">
                                    <i class="pi pi-bullseye text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-[#0B1221] mb-2">Notre mission</h4>
                                <p class="text-sm text-slate-500 leading-relaxed">{{ mission }}</p>
                            </div>
                            <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] border border-slate-100">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-red-600">
                                    <i class="pi pi-eye text-xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-[#0B1221] mb-2">Notre vision</h4>
                                <p class="text-sm text-slate-500 leading-relaxed">{{ vision }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : STATISTIQUES (identique à la Landing) -->
        <!-- ================================================================== -->
        <section class="py-16 bg-[#F4F6F9]" id="about-stats" ref="statsSection">
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
        <!-- SECTION : NOS VALEURS -->
        <!-- ================================================================== -->
        <section v-if="values.length" class="py-20 bg-white relative overflow-hidden">
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-100 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>
            <div class="relative max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center mb-12 reveal-on-scroll">
                    <p class="text-xs font-black text-red-500 uppercase tracking-[0.3em] mb-3">Nos engagements</p>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter">Les valeurs qui nous guident</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="(value, i) in values" :key="i" class="reveal-on-scroll group bg-white p-6 rounded-xl border border-slate-100 hover:border-transparent hover:shadow-2xl transition-all duration-300" :style="{ transitionDelay: (i % 4) * 70 + 'ms' }">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110" :style="{ backgroundColor: '#2563EB12', color: '#2563EB' }">
                            <i :class="['pi', value.icon || 'pi-star', 'text-2xl']"></i>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 mb-2">{{ value.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ value.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : NOS SERVICES / EXPERTISES -->
        <!-- ================================================================== -->
        <section v-if="services.length" class="py-20 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center max-w-3xl mx-auto mb-12 reveal-on-scroll">
                    <div class="inline-flex items-center gap-2 mb-2 justify-center">
                        <span class="w-6 h-1 bg-red-600 rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-red-600">Nos expertises</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-[#0B1221] tracking-tighter">Formation & ingénierie, main dans la main</h2>
                    <p class="text-slate-500 mt-4">Au-delà des cours, nous déployons et sécurisons les infrastructures qui comptent.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="(service, i) in services" :key="i" class="reveal-on-scroll group bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-xl border border-transparent hover:border-slate-200 transition-all">
                        <div class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center mb-5 text-[#2563EB] group-hover:text-[#E1251B] transition-colors">
                            <i :class="['pi', service.icon || 'pi-cog', 'text-2xl']"></i>
                        </div>
                        <h3 class="text-lg font-black text-[#0B1221] mb-2">{{ service.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ service.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : PROCESSUS (comment nous travaillons) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex flex-col lg:flex-row gap-16 items-start">
                    <div class="w-full lg:w-1/3 reveal-on-scroll lg:sticky lg:top-32">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">Notre méthode</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight leading-tight mb-6">
                            Une méthode éprouvée, <br/>du besoin au résultat
                        </h2>
                        <p class="text-slate-500 leading-relaxed mb-8">
                            Chaque mission suit le même cycle : comprendre, concevoir, former, mesurer. La qualité avant tout.
                        </p>
                        <Link :href="route('academy')" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-[#0B1221] text-white font-bold rounded-xl hover:bg-[#2563EB] transition-colors">
                            Voir nos parcours <i class="pi pi-arrow-right"></i>
                        </Link>
                    </div>

                    <div class="w-full lg:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-6">
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
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : ÉQUIPE -->
        <!-- ================================================================== -->
        <section v-if="team.length" class="py-20 bg-[#F4F6F9]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-12 reveal-on-scroll">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">Notre équipe</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight">Les experts derrière la formation</h2>
                    </div>
                    <p class="text-slate-500 max-w-md">Des instructeurs et ingénieurs passionnés, au service de votre montée en compétences.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(member, mi) in team" :key="member.id" class="reveal-on-scroll group bg-white p-6 rounded-[1.25rem] border border-slate-100 hover:shadow-2xl transition-all duration-300 text-center" :style="{ transitionDelay: (mi % 4) * 70 + 'ms' }">
                        <div class="relative mx-auto w-24 h-24 mb-5">
                            <img :src="member.avatar || `${DEFAULT_AVATAR}${member.name || 'X'}`" loading="lazy" decoding="async" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md group-hover:scale-105 transition-transform duration-500" :alt="member.name" />
                            <div class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-[#2563EB] text-white flex items-center justify-center shadow-md">
                                <i class="pi pi-graduation-cap text-xs"></i>
                            </div>
                        </div>
                        <h3 class="text-lg font-black text-[#0B1221] mb-1">{{ member.name }}</h3>
                        <p class="text-xs font-bold uppercase tracking-widest text-[#2563EB] mb-3">Instructeur Expert</p>
                        <p class="text-sm text-slate-500 leading-relaxed line-clamp-3">{{ member.bio || 'Formateur certifié, spécialiste des infrastructures et de la cybersécurité.' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : TÉMOIGNAGES -->
        <!-- ================================================================== -->
        <section v-if="testimonials.length" class="py-20 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center max-w-3xl mx-auto mb-12 reveal-on-scroll">
                    <div class="inline-flex items-center gap-2 mb-2 justify-center">
                        <span class="w-6 h-1 bg-red-600 rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-red-600">Ils nous font confiance</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-[#0B1221] tracking-tighter">Ce que disent nos apprenants</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <figure v-for="(t, ti) in testimonials" :key="t.id" class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] border border-slate-100 flex flex-col hover:shadow-xl transition-shadow" :style="{ transitionDelay: (ti % 3) * 80 + 'ms' }">
                        <div class="flex items-center gap-1 mb-4">
                            <i v-for="n in 5" :key="n" :class="['pi', n <= t.rating ? 'pi-star-fill text-amber-400' : 'pi-star text-slate-300', 'text-sm']"></i>
                        </div>
                        <blockquote class="text-slate-600 text-sm leading-relaxed mb-6 flex-1">« {{ t.content }} »</blockquote>
                        <figcaption class="flex items-center gap-3 pt-5 border-t border-slate-200">
                            <img :src="t.avatar || `${DEFAULT_AVATAR}${t.name || 'X'}`" class="w-11 h-11 rounded-full object-cover border-2 border-white shadow" :alt="t.name" />
                            <div>
                                <p class="text-sm font-black text-[#0B1221]">{{ t.name }}</p>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ t.role || 'Apprenant' }}</p>
                            </div>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : CTA FINAL (identique à la Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white border-t border-slate-100 text-center">
            <div class="max-w-[800px] mx-auto px-4 reveal-on-scroll">
                <h2 class="text-4xl md:text-5xl font-black text-[#0B1221] tracking-tight mb-6">Prêt à rejoindre l'aventure ?</h2>
                <p class="text-lg text-slate-500 mb-10">Créez votre compte et accédez dès maintenant à nos formations.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('register')" class="w-full sm:w-auto px-10 py-4 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20 text-base">
                        Créer un compte gratuit
                    </Link>
                    <Link :href="route('academy')" class="w-full sm:w-auto px-10 py-4 bg-[#F4F6F9] text-[#0B1221] font-bold rounded-xl hover:bg-slate-200 transition-colors text-base">
                        Voir le catalogue
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

@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.animate-marquee {
    animation: marquee 32s linear infinite;
    will-change: transform;
}
.animate-marquee:hover { animation-play-state: paused; }
@media (prefers-reduced-motion: reduce) {
    .animate-marquee { animation: none; }
}
</style>
