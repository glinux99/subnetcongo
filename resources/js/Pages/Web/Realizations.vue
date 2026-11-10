<script setup>
/**
 * ============================================================================
 * COMPOSANT : RÉALISATIONS (Realizations.vue)
 * DESCRIPTION : Vitrine projets — même langage visuel que la Landing
 * (hero sombre, cartes premium, révélations au scroll) avec une identité
 * "portfolio terrain" : filtres catégories, fiches projets riches, CTA B2B.
 * ============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    realizations: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    realizationCategories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?q=80&w=2070&auto=format&fit=crop';

const search = ref(props.filters.search || '');
const activeCategory = ref(props.filters.category || '');

const applyFilters = (cat) => {
    if (cat !== undefined) activeCategory.value = cat;
    router.get(route('realizations'), {
        search: search.value || undefined,
        category: activeCategory.value || undefined,
    }, { preserveState: true, replace: true });
};

const realizationImage = (r) => r.image || r.cover_image || DEFAULT_IMAGE;
const realizationSummary = (r) => r.summary || r.description || '';

const formatDate = (date) => {
    if (!date) return '';
    try {
        return new Date(date).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    } catch (e) { return date; }
};

const parseTags = (tags) => {
    if (!tags) return [];
    if (Array.isArray(tags)) return tags;
    try { return JSON.parse(tags); } catch (e) { return []; }
};

// ============================================================================
// MODAL DÉTAIL PROJET
// ============================================================================
const selected = ref(null);
const openProject = (r) => { selected.value = r; document.body.style.overflow = 'hidden'; };
const closeProject = () => { selected.value = null; document.body.style.overflow = ''; };

const stripHtml = (html) => {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const fullDescription = (r) => {
    const raw = r.description || r.summary || '';
    const text = stripHtml(raw).trim();
    return text || realizationSummary(r);
};

// Stats calculées sur le set filtré
const stats = computed(() => ({
    total: props.realizations?.total ?? props.realizations?.data?.length ?? 0,
    categories: props.realizationCategories?.length ?? 0,
    withClient: props.realizations?.data?.filter(r => r.client).length ?? 0,
}));

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
    <Head :title="`Réalisations - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" content="Découvrez nos réalisations : déploiements réseaux, formations et projets infrastructure en RDC.">
    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- HERO : signature sombre Landing -->
        <!-- ================================================================== -->
        <header class="relative min-h-[55vh] flex items-end pt-20 overflow-hidden bg-[#020617]">
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-50"></div>
                <div class="absolute -top-[30%] -left-[10%] w-[50%] h-[80%] bg-blue-600/20 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute -bottom-[20%] -right-[10%] w-[40%] h-[60%] bg-red-600/10 blur-[150px] rounded-full pointer-events-none"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#F4F6F9]"></div>
            </div>

            <div class="relative z-20 max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-12 pb-16">
                <div class="max-w-3xl space-y-6">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-white/5 backdrop-blur-md border border-white/10 rounded-full text-white reveal-on-scroll">
                        <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-blue-200">Portfolio & Projets terrain</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.05] tracking-tight reveal-on-scroll delay-100">
                        Nos <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">réalisations</span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5 reveal-on-scroll delay-200">
                        Déploiements réseaux, infrastructures critiques et programmes de formation menés avec succès en République Démocratique du Congo.
                    </p>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION : STATS RAPIDES -->
        <!-- ================================================================== -->
        <section class="py-12 bg-[#F4F6F9] border-b border-slate-100">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-3 gap-6">
                    <div class="reveal-on-scroll bg-white p-6 lg:p-8 rounded-xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-3" style="backgroundColor: '#2563EB15', color: '#2563EB'">
                            <i class="pi pi-briefcase text-xl"></i>
                        </div>
                        <span class="text-3xl lg:text-4xl font-black tracking-tighter text-[#0B1221]">{{ stats.total }}</span>
                        <p class="text-slate-500 font-bold uppercase tracking-wider text-[10px] mt-1">Projets livrés</p>
                    </div>
                    <div class="reveal-on-scroll bg-white p-6 lg:p-8 rounded-xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-3" style="backgroundColor: '#E1251B15', color: '#E1251B'">
                            <i class="pi pi-tags text-xl"></i>
                        </div>
                        <span class="text-3xl lg:text-4xl font-black tracking-tighter text-[#0B1221]">{{ stats.categories }}</span>
                        <p class="text-slate-500 font-bold uppercase tracking-wider text-[10px] mt-1">Domaines couverts</p>
                    </div>
                    <div class="reveal-on-scroll bg-white p-6 lg:p-8 rounded-xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-3" style="backgroundColor: '#10B98115', color: '#10B981'">
                            <i class="pi pi-building text-xl"></i>
                        </div>
                        <span class="text-3xl lg:text-4xl font-black tracking-tighter text-[#0B1221]">{{ stats.withClient }}</span>
                        <p class="text-slate-500 font-bold uppercase tracking-wider text-[10px] mt-1">Clients accompagnés</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : RECHERCHE & FILTRES -->
        <!-- ================================================================== -->
        <section class="py-10 bg-white border-b border-slate-100 sticky top-[65px] z-30">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="reveal-on-scroll bg-[#F4F6F9] rounded-[1.25rem] border border-slate-100 p-4 sm:p-5 flex flex-col lg:flex-row gap-4 items-center">
                    <!-- Recherche -->
                    <div class="relative w-full lg:w-80">
                        <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input v-model="search" @keyup.enter="applyFilters()" type="search" placeholder="Rechercher un projet..."
                            class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                    </div>

                    <!-- Pills catégories -->
                    <div class="flex flex-nowrap overflow-x-auto hide-scrollbar gap-2 flex-1">
                        <button @click="applyFilters('')"
                            class="relative px-5 py-2.5 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="!activeCategory ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">Tous</span>
                            <div v-if="!activeCategory" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0"></div>
                        </button>
                        <button v-for="cat in realizationCategories" :key="cat" @click="applyFilters(cat)"
                            class="relative px-5 py-2.5 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="activeCategory === cat ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">{{ cat }}</span>
                            <div v-if="activeCategory === cat" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0"></div>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : GRILLE DES PROJETS -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9] min-h-[60vh]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">

                <div v-if="realizations.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <article v-for="(r, idx) in realizations.data" :key="r.id" @click="openProject(r)"
                        class="reveal-on-scroll group flex flex-col bg-white rounded-[1.25rem] overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-2xl border border-slate-100 transition-all duration-300 h-full cursor-pointer"
                        :style="{ transitionDelay: (idx % 6) * 60 + 'ms' }"
                    >
                        <!-- Image -->
                        <div class="relative h-[240px] w-full shrink-0 overflow-hidden bg-slate-900">
                            <img :src="realizationImage(r)" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" :alt="r.title" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-70"></div>

                            <!-- Badge catégorie -->
                            <div v-if="r.category" class="absolute top-4 left-4 bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md">
                                {{ r.category }}
                            </div>

                            <!-- Localisation -->
                            <div v-if="r.location" class="absolute bottom-4 left-4 flex items-center gap-1.5 text-white/90 text-[11px] font-bold bg-black/40 backdrop-blur px-3 py-1.5 rounded-full">
                                <i class="pi pi-map-marker text-[10px] text-red-400"></i> {{ r.location }}
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-6 md:p-7 flex-1 flex flex-col bg-white">
                            <h3 class="text-xl font-black text-[#0B1221] leading-snug mb-2 group-hover:text-[#2563EB] transition-colors">
                                {{ r.title }}
                            </h3>

                            <p v-if="r.client" class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-1.5">
                                <i class="pi pi-building text-[10px]"></i> {{ r.client }}
                            </p>

                            <p class="text-sm text-slate-500 leading-relaxed line-clamp-3 mb-4">{{ realizationSummary(r) }}</p>

                            <div class="flex-1"></div>

                            <!-- Tags + date -->
                            <div class="flex items-center justify-between pt-5 border-t border-slate-100">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="tag in parseTags(r.tags).slice(0, 2)" :key="tag"
                                        class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-[#F4F6F9] px-2.5 py-1 rounded-md">
                                        {{ tag }}
                                    </span>
                                </div>
                                <span class="flex items-center gap-2 text-xs font-bold text-[#2563EB] group-hover:gap-3 transition-all">
                                    Détails <i class="pi pi-arrow-right text-[10px]"></i>
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-24 bg-white rounded-[1rem] border border-dashed border-slate-300 w-full reveal-on-scroll">
                    <div class="w-20 h-20 bg-[#F4F6F9] rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-folder-open text-3xl text-slate-300"></i>
                    </div>
                    <p class="text-lg font-bold text-slate-600">Aucune réalisation{{ search ? ` pour "${search}"` : '' }} pour le moment.</p>
                    <button v-if="search || activeCategory" @click="search = ''; applyFilters('')" class="mt-4 text-[#2563EB] font-bold hover:underline text-sm">Réinitialiser les filtres</button>
                </div>

                <!-- Pagination -->
                <div v-if="realizations.links?.length > 3" class="mt-12 flex justify-center gap-2 flex-wrap">
                    <Link v-for="(l, i) in realizations.links" :key="i" :href="l.url || '#'" v-html="l.label"
                        :class="['px-4 py-2 rounded-xl text-sm font-bold transition-colors', l.active ? 'bg-[#0B1221] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50', !l.url && 'opacity-40 pointer-events-none']" />
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : POURQUOI NOUS (bento façon Landing B2B) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex flex-col md:flex-row gap-16 items-center">
                    <!-- Texte -->
                    <div class="w-full md:w-1/3 reveal-on-scroll">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="w-6 h-1 bg-red-600 rounded-full"></span>
                            <span class="font-bold uppercase tracking-widest text-xs text-red-600">Ingénierie & Terrain</span>
                        </div>
                        <h2 class="text-3xl md:text-5xl font-black text-[#0B1221] tracking-tight leading-tight mb-6">
                            Des projets qui <br/>tiennent la route
                        </h2>
                        <p class="text-slate-500 mb-8 leading-relaxed">
                            Chaque réalisation est le fruit d'une expertise terrain : audit, déploiement, formation et accompagnement long terme.
                        </p>
                        <Link :href="route('contact')" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">
                            Démarrer votre projet
                        </Link>
                    </div>

                    <!-- Grille services -->
                    <div class="w-full md:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-lg border border-transparent hover:border-slate-200 transition-all">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#0B1221]">
                                <i class="pi pi-sitemap text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#0B1221] mb-2">Infrastructures Réseaux</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">Conception et déploiement de LAN/WAN d'entreprise, datacenters et points d'accès critiques.</p>
                        </div>
                        <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-lg border border-transparent hover:border-slate-200 transition-all">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#0B1221]">
                                <i class="pi pi-shield text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#0B1221] mb-2">Cybersécurité</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">Audits, durcissement des infrastructures et déploiement de solutions de supervision SOC/NOC.</p>
                        </div>
                        <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-lg border border-transparent hover:border-slate-200 transition-all">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#0B1221]">
                                <i class="pi pi-cloud text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#0B1221] mb-2">Cloud & Virtualisation</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">Migration vers le cloud, virtualisation VMware/Hyper-V et architectures hybrides.</p>
                        </div>
                        <div class="reveal-on-scroll bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-lg border border-transparent hover:border-slate-200 transition-all">
                            <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center mb-4 text-[#0B1221]">
                                <i class="pi pi-graduation-cap text-xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-[#0B1221] mb-2">Formation Terrain</h4>
                            <p class="text-sm text-slate-500 leading-relaxed">Montée en compétences des équipes internes avec des parcours certifiants et des labs pratiques.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : CTA FINAL -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9] border-t border-slate-100 text-center">
            <div class="max-w-[800px] mx-auto px-4 reveal-on-scroll">
                <h2 class="text-4xl md:text-5xl font-black text-[#0B1221] tracking-tight mb-6">Votre projet pourrait être le prochain</h2>
                <p class="text-lg text-slate-500 mb-10">Discutons de vos besoins : audit gratuit pour les premières demandes.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('contact')" class="w-full sm:w-auto px-10 py-4 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20 text-base">
                        Demander un audit
                    </Link>
                    <Link :href="route('academy')" class="w-full sm:w-auto px-10 py-4 bg-white text-[#0B1221] font-bold rounded-xl hover:bg-slate-100 transition-colors border border-slate-200 text-base">
                        Voir nos formations
                    </Link>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- MODAL : DÉTAIL DU PROJET -->
        <!-- ================================================================== -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="selected" class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-8" @click.self="closeProject">
                    <div class="absolute inset-0 bg-[#020617]/80 backdrop-blur-sm"></div>

                    <div class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-[1.5rem] shadow-2xl overflow-hidden flex flex-col">
                        <!-- Header image -->
                        <div class="relative h-64 sm:h-80 shrink-0 overflow-hidden bg-slate-900">
                            <img :src="realizationImage(selected)" class="w-full h-full object-cover" :alt="selected.title" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                            <button @click="closeProject" class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times"></i>
                            </button>
                            <div class="absolute bottom-5 left-6 right-6">
                                <span v-if="selected.category" class="inline-block bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md mb-3">{{ selected.category }}</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white leading-tight">{{ selected.title }}</h2>
                            </div>
                        </div>

                        <!-- Contenu scrollable -->
                        <div class="overflow-y-auto custom-scrollbar p-6 sm:p-8">
                            <!-- Méta -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                                <div v-if="selected.client" class="bg-[#F4F6F9] rounded-xl p-4">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Client</p>
                                    <p class="text-sm font-bold text-[#0B1221] flex items-center gap-1.5"><i class="pi pi-building text-[#2563EB]"></i> {{ selected.client }}</p>
                                </div>
                                <div v-if="selected.location" class="bg-[#F4F6F9] rounded-xl p-4">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Lieu</p>
                                    <p class="text-sm font-bold text-[#0B1221] flex items-center gap-1.5"><i class="pi pi-map-marker text-[#E1251B]"></i> {{ selected.location }}</p>
                                </div>
                                <div v-if="selected.completed_at" class="bg-[#F4F6F9] rounded-xl p-4">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Réalisé en</p>
                                    <p class="text-sm font-bold text-[#0B1221] flex items-center gap-1.5"><i class="pi pi-calendar text-emerald-600"></i> {{ formatDate(selected.completed_at) }}</p>
                                </div>
                                <div class="bg-[#F4F6F9] rounded-xl p-4">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Technologies</p>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="tag in parseTags(selected.tags).slice(0, 3)" :key="tag" class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-white border border-slate-200 px-2 py-0.5 rounded">{{ tag }}</span>
                                        <span v-if="!parseTags(selected.tags).length" class="text-sm font-bold text-slate-400">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="prose prose-slate max-w-none">
                                <h3 class="text-xs font-black uppercase tracking-widest text-[#2563EB] mb-3">Le projet</h3>
                                <p class="text-slate-600 leading-relaxed whitespace-pre-line">{{ fullDescription(selected) }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="shrink-0 px-6 sm:px-8 py-5 bg-[#F4F6F9] border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="text-xs text-slate-400 font-medium">Un projet similaire ? Parlons-en.</p>
                            <div class="flex gap-3">
                                <button @click="closeProject" class="px-6 py-3 bg-white border border-slate-200 text-[#0B1221] font-bold rounded-xl hover:bg-slate-50 transition-colors text-sm">Fermer</button>
                                <Link :href="route('contact')" class="px-6 py-3 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors text-sm flex items-center gap-2">
                                    Discuter du vôtre <i class="pi pi-arrow-right text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Modal transition */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active > div.relative, .modal-fade-leave-active > div.relative { transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), opacity 0.3s ease; }
.modal-fade-enter-from > div.relative, .modal-fade-leave-to > div.relative { transform: translateY(24px) scale(0.97); opacity: 0; }

/* Scrollbar modal */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
