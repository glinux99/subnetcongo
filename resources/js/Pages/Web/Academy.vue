<script setup>
/**
 * ============================================================================
 * COMPOSANT : PAGE ACADÉMIE (Academy.vue)
 * DESCRIPTION : Catalogue complet des cours — même langage visuel que la
 * Landing : hero sombre, filtres premium, cartes cours identiques, CTA final.
 * ============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    courses: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    levels: { type: Object, default: () => ({}) },
});

const DEFAULT_COURSE_IMAGE = 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop';
const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=f1f5f9&color=64748b&name=';

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const level = ref(props.filters.level || '');
const sort = ref(props.filters.sort || 'newest');
const isFiltering = ref(false);

const sortOptions = [
    { label: 'Plus récents', value: 'newest' },
    { label: 'Prix croissant', value: 'price_asc' },
    { label: 'Prix décroissant', value: 'price_desc' },
    { label: 'Titre A → Z', value: 'title' },
];

const applyFilters = () => {
    isFiltering.value = true;
    router.get(route('academy'), {
        search: search.value || undefined,
        category: category.value || undefined,
        level: level.value || undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => { setTimeout(() => { isFiltering.value = false; }, 350); },
    });
};

const resetFilters = () => {
    search.value = '';
    category.value = '';
    level.value = '';
    applyFilters();
};

const isFree = (course) => Number(course.price) === 0;
const hasPromo = (course) => Number(course.promo_price) > 0 && Number(course.promo_price) < Number(course.price);
const isNew = (course) => {
    if (!course.published_at) return false;
    return (Date.now() - new Date(course.published_at).getTime()) < 30 * 24 * 3600 * 1000;
};
const courseImage = (course) => course.image || course.thumbnail_path || DEFAULT_COURSE_IMAGE;
const courseDuration = (course) => {
    const mins = Number(course.duration_minutes || 0);
    if (!mins) return null;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h ? `${h}h${m ? ` ${m}min` : ''}` : `${m}min`;
};
const levelLabel = (course) => props.levels?.[course.level] || course.level || 'Tous niveaux';
const totalCourses = computed(() => props.courses?.total ?? props.courses?.data?.length ?? 0);

// Pills catégories rapides (mêmes valeurs que le select)
const quickCategories = computed(() => (props.categories || []).slice(0, 8));
const pickCategory = (slug) => {
    category.value = category.value === slug ? '' : slug;
    applyFilters();
};

// Stats de la page
const academyStats = computed(() => [
    { value: totalCourses.value, label: 'Formations publiées', icon: 'pi-book', color: '#2563EB' },
    { value: (props.categories || []).length, label: 'Domaines couverts', icon: 'pi-tags', color: '#E1251B' },
    { value: Object.keys(props.levels || {}).filter(k => k !== 'all').length, label: 'Niveaux de progression', icon: 'pi-signal', color: '#10B981' },
    { value: '100%', label: 'Accès depuis partout', icon: 'pi-globe', color: '#F59E0B' },
]);

// Section "Pourquoi nous choisir"
const whyUs = [
    { icon: 'pi-verified', title: 'Certifications reconnues', desc: 'Des cursus alignés sur les standards internationaux : Cisco, AWS, CompTIA et plus.' },
    { icon: 'pi-desktop', title: 'Labs pratiques inclus', desc: 'Chaque formation alterne théorie et exercices réels sur des environnements simulés.' },
    { icon: 'pi-clock', title: 'Rythme flexible', desc: 'Accédez aux contenus 24/7, avancez à votre rythme avec un suivi de progression détaillé.' },
    { icon: 'pi-users', title: 'Communauté d\'experts', desc: 'Des instructeurs certifiés et un support réactif pour répondre à vos questions.' },
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
    <Head :title="`Académie - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" content="Catalogue complet des formations SubnetCongo : réseaux, cybersécurité, cloud et systèmes.">
    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- HERO : signature sombre Landing -->
        <!-- ================================================================== -->
        <header class="relative min-h-[60vh] flex items-end pt-20 overflow-hidden bg-[#020617]">
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <img src="/media/lms/thumbnails/course-10.jpg" alt="" aria-hidden="true" class="w-full h-full object-cover opacity-30" loading="eager" decoding="async" />
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
                        <span class="text-xs font-bold uppercase tracking-widest text-blue-200">Académie IT</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.05] tracking-tight reveal-on-scroll delay-100">
                        Catalogue des <br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">
                            formations
                        </span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5 reveal-on-scroll delay-200">
                        {{ totalCourses }} cours publiés par nos experts — du CCNA à l'architecture cloud.
                    </p>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION : STATS (signature Landing) -->
        <!-- ================================================================== -->
        <section class="py-12 bg-[#F4F6F9] border-b border-slate-100">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(stat, i) in academyStats" :key="i" class="reveal-on-scroll bg-white p-6 lg:p-8 rounded-xl border border-slate-100 shadow-sm flex flex-col items-center text-center">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-3" :style="{ backgroundColor: stat.color + '15', color: stat.color }">
                            <i :class="['pi', stat.icon, 'text-xl']"></i>
                        </div>
                        <span class="text-3xl lg:text-4xl font-black tracking-tighter text-[#0B1221]">{{ stat.value }}</span>
                        <p class="text-slate-500 font-bold uppercase tracking-wider text-[10px] mt-1">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : FILTRES (carte premium façon Landing) -->
        <!-- ================================================================== -->
        <section class="py-10 bg-[#F4F6F9] border-b border-slate-100">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="reveal-on-scroll bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                        <!-- Recherche -->
                        <div class="md:col-span-5 relative">
                            <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input v-model="search" @keyup.enter="applyFilters" type="search" placeholder="Rechercher un cours, un sujet..."
                                class="w-full pl-11 pr-4 py-3.5 bg-[#F4F6F9] border-none rounded-xl text-sm font-medium text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                        </div>

                        <!-- Catégorie -->
                        <div class="md:col-span-4 relative">
                            <i class="pi pi-folder absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <select v-model="category" @change="applyFilters"
                                class="w-full pl-11 pr-4 py-3.5 bg-[#F4F6F9] border-none rounded-xl text-sm font-bold text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all appearance-none cursor-pointer">
                                <option value="">Toutes les catégories</option>
                                <option v-for="c in categories" :key="c.id" :value="c.slug">{{ c.name }}</option>
                            </select>
                        </div>

                        <!-- Niveau + tri + actions -->
                        <div class="md:col-span-3 flex gap-3">
                            <div class="relative flex-1">
                                <i class="pi pi-signal absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select v-model="level" @change="applyFilters"
                                    class="w-full pl-11 pr-4 py-3.5 bg-[#F4F6F9] border-none rounded-xl text-sm font-bold text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">Tous niveaux</option>
                                    <option v-for="(label, value) in levels" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>
                            <button @click="resetFilters" class="px-5 py-3.5 bg-[#0B1221] hover:bg-slate-800 text-white rounded-xl font-bold text-sm transition-colors flex items-center gap-2" title="Réinitialiser">
                                <i class="pi pi-filter-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Compteur + tri -->
                    <div class="mt-5 pt-5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">
                            <span class="text-[#2563EB]">{{ totalCourses }}</span> formation{{ totalCourses > 1 ? 's' : '' }} trouvée{{ totalCourses > 1 ? 's' : '' }}
                        </p>
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                                <i class="pi pi-sort-alt text-[#2563EB]"></i> Trier par
                            </label>
                            <select v-model="sort" @change="applyFilters"
                                class="px-4 py-2.5 bg-[#F4F6F9] border-none rounded-xl text-sm font-bold text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all appearance-none cursor-pointer">
                                <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pills catégories rapides -->
                    <div v-if="quickCategories.length" class="mt-5 pt-5 border-t border-slate-100 flex flex-nowrap overflow-x-auto hide-scrollbar gap-2">
                        <button @click="pickCategory('')"
                            class="relative px-4 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="!category ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">Tous</span>
                            <div v-if="!category" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0"></div>
                        </button>
                        <button v-for="c in quickCategories" :key="c.id" @click="pickCategory(c.slug)"
                            class="relative px-4 py-2 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="category === c.slug ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">{{ c.name }}</span>
                            <div v-if="category === c.slug" class="absolute inset-0 bg-[#2563EB] rounded-lg shadow z-0"></div>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : GRILLE DES COURS (cartes identiques à la Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9] min-h-[60vh]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">

                <div class="transition-all duration-300" :class="{'opacity-0 scale-[0.98]': isFiltering, 'opacity-100 scale-100': !isFiltering}">
                <div v-if="courses.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <Link v-for="(course, idx) in courses.data" :key="course.id" :href="route('marketplace.show', course.slug)"
                        class="reveal-on-scroll group flex flex-col bg-white rounded-[1.25rem] overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-2xl border border-slate-100 transition-all duration-300 h-full w-full"
                        :style="{ transitionDelay: (idx % 6) * 60 + 'ms' }"
                    >
                        <!-- Header Image -->
                        <div class="relative h-[220px] sm:h-[240px] w-full shrink-0 overflow-hidden bg-slate-900">
                            <img :src="courseImage(course)" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" alt="Course cover" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-60"></div>

                            <!-- Badge "Nouveau" (haut gauche) -->
                            <div v-if="isNew(course)" class="absolute top-4 left-4 bg-gradient-to-r from-red-500 to-amber-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                <i class="pi pi-bolt text-[10px]"></i> Nouveau
                            </div>

                            <!-- Badge prix (haut droite) -->
                            <div v-if="isFree(course)" class="absolute top-4 right-4 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-md">
                                <i class="pi pi-gift text-[10px]"></i> Gratuit
                            </div>
                            <div v-else class="absolute top-4 right-4 bg-[#0B1221]/90 backdrop-blur text-white text-[11px] font-black px-3 py-1.5 rounded-full shadow-md flex items-center gap-1.5">
                                <template v-if="hasPromo(course)">
                                    <span class="text-red-400">${{ Number(course.promo_price).toFixed(0) }}</span>
                                    <span class="text-white/50 line-through text-[10px] font-bold">${{ Number(course.price).toFixed(0) }}</span>
                                </template>
                                <template v-else>${{ Number(course.price).toFixed(0) }}</template>
                            </div>

                            <!-- Badge catégorie (bas gauche) -->
                            <div class="absolute bottom-4 left-4 bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md">
                                {{ course.category?.name || 'FORMATION' }}
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-6 md:p-8 flex-1 flex flex-col w-full bg-white">
                            <!-- Auteur -->
                            <div class="flex items-center gap-2.5 mb-3">
                                <img :src="`${DEFAULT_AVATAR}${course.instructor?.name || 'Expert'}`" class="w-6 h-6 rounded-full object-cover border border-slate-200" :alt="course.instructor?.name" />
                                <span class="text-xs text-slate-500 font-bold uppercase">Par {{ course.instructor?.name || 'Expert IT' }}</span>
                            </div>

                            <!-- Titre -->
                            <h3 class="text-[1.35rem] font-bold text-[#0B1221] leading-snug mb-4 line-clamp-2 group-hover:text-[#2563EB] transition-colors">
                                {{ course.title }}
                            </h3>

                            <div class="flex-1"></div>

                            <!-- Meta -->
                            <div class="flex items-center gap-6 mb-6">
                                <div class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                                    <i class="pi pi-signal text-[#2563EB] text-xs"></i> {{ levelLabel(course) }}
                                </div>
                                <div v-if="courseDuration(course)" class="flex items-center gap-1.5 text-sm font-medium text-slate-500">
                                    <i class="pi pi-clock text-slate-400 text-xs"></i> {{ courseDuration(course) }}
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="w-full mt-auto">
                                <div class="w-full bg-[#0B1221] group-hover:bg-[#2563EB] text-white text-sm font-bold py-3.5 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-sm shadow-slate-900/10">
                                    Détails du cours <i class="pi pi-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty state (style Landing) -->
                <div v-else class="text-center py-24 bg-white rounded-[1rem] border border-dashed border-slate-300 w-full reveal-on-scroll">
                    <div class="w-20 h-20 bg-[#F4F6F9] rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-search text-3xl text-slate-300"></i>
                    </div>
                    <p class="text-lg font-bold text-slate-600">Aucun cours trouvé{{ search ? ` pour "${search}"` : '' }}</p>
                    <button @click="resetFilters" class="mt-4 text-[#2563EB] font-bold hover:underline text-sm">Réinitialiser les filtres</button>
                </div>

                <!-- Pagination -->
                <div v-if="courses.links?.length > 3" class="mt-12 flex justify-center gap-2 flex-wrap">
                    <Link v-for="(l, i) in courses.links" :key="i" :href="l.url || '#'" v-html="l.label"
                        :class="['px-4 py-2 rounded-xl text-sm font-bold transition-colors', l.active ? 'bg-[#0B1221] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50', !l.url && 'opacity-40 pointer-events-none']" />
                </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : POURQUOI NOUS CHOISIR -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="text-center max-w-3xl mx-auto mb-12 reveal-on-scroll">
                    <div class="inline-flex items-center gap-2 mb-2 justify-center">
                        <span class="w-6 h-1 bg-red-600 rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-red-600">La différence SubnetCongo</span>
                    </div>
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-[#0B1221] tracking-tighter">Pourquoi apprendre avec nous ?</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="(item, i) in whyUs" :key="i" class="reveal-on-scroll group bg-[#F4F6F9] p-8 rounded-[1.25rem] hover:bg-white hover:shadow-xl border border-transparent hover:border-slate-200 transition-all">
                        <div class="w-14 h-14 rounded-xl bg-white shadow-sm flex items-center justify-center mb-5 text-[#2563EB] group-hover:text-[#E1251B] transition-colors">
                            <i :class="['pi', item.icon, 'text-2xl']"></i>
                        </div>
                        <h3 class="text-lg font-black text-[#0B1221] mb-2">{{ item.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ item.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : CTA FINAL (identique à la Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-white border-t border-slate-100 text-center">
            <div class="max-w-[800px] mx-auto px-4 reveal-on-scroll">
                <h2 class="text-4xl md:text-5xl font-black text-[#0B1221] tracking-tight mb-6">Vous ne trouvez pas votre formation ?</h2>
                <p class="text-lg text-slate-500 mb-10">Contactez-nous : nous créons des parcours sur mesure pour les entreprises.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('contact')" class="w-full sm:w-auto px-10 py-4 bg-[#2563EB] text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/20 text-base">
                        Demander un devis
                    </Link>
                    <Link :href="route('about')" class="w-full sm:w-auto px-10 py-4 bg-[#F4F6F9] text-[#0B1221] font-bold rounded-xl hover:bg-slate-200 transition-colors text-base">
                        Découvrir SubnetCongo
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
