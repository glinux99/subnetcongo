<script setup>
/**
 * ============================================================================
 * COMPOSANT : BLOG (Blog.vue)
 * DESCRIPTION : Magazine en ligne — même langage visuel que la Landing
 * (hero sombre, cartes premium, révélations au scroll) avec une identité
 * éditoriale propre : featured article, catégories, newsletter, pagination.
 * ============================================================================
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    posts: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    featured: { type: Array, default: () => [] },
    blogCategories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=2070&auto=format&fit=crop';
const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=f1f5f9&color=64748b&name=';

const search = ref(props.filters.search || '');
const activeCategory = ref(props.filters.category || '');

const applyFilters = (cat) => {
    if (cat !== undefined) activeCategory.value = cat;
    router.get(route('blog'), {
        search: search.value || undefined,
        category: activeCategory.value || undefined,
    }, { preserveState: true, replace: true });
};

const postImage = (p) => p.featured_image || p.thumbnail || DEFAULT_IMAGE;
const postCategory = (p) => p.category?.name || t('public.blog.category_fallback');
const postCategorySlug = (p) => p.category?.slug || '';

const formatDate = (date) => {
    if (!date) return '';
    try {
        return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
    } catch (e) { return date; }
};

const readTime = (p) => {
    const text = (p.content || p.excerpt || '').replace(/<[^>]*>/g, '');
    const words = text.split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 200));
};

// Featured : 1 grand + les autres en secondaire
const featuredMain = computed(() => props.featured[0] || null);
const featuredSecondary = computed(() => props.featured.slice(1, 3));

// Newsletter (mock — pas de backend dédié)
const newsletter = useForm({ email: '' });
const newsletterSent = ref(false);
const subscribe = () => {
    newsletterSent.value = true;
    newsletter.email = '';
};

// ============================================================================
// MODAL ARTICLE (lecture complète + partage)
// ============================================================================
const selectedPost = ref(null);
const linkCopied = ref(false);

const openArticle = (post) => { selectedPost.value = post; document.body.style.overflow = 'hidden'; };
const closeArticle = () => { selectedPost.value = null; linkCopied.value = false; document.body.style.overflow = ''; };

const stripHtml = (html) => {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const articleText = (p) => stripHtml(p.content || '');
const articleWords = (text) => text.split(/\s+/).filter(Boolean).length;
const articleReadTime = (p) => Math.max(1, Math.round(articleWords(articleText(p) || p.excerpt || '') / 200));

const shareUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    return `${window.location.origin}/blog`;
});

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        linkCopied.value = true;
        setTimeout(() => { linkCopied.value = false; }, 2500);
    } catch (e) { /* clipboard indisponible */ }
};

const shareTargets = computed(() => [
    { icon: 'pi-facebook',  label: 'Facebook',  href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}` },
    { icon: 'pi-twitter',   label: 'X / Twitter', href: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(selectedPost.value?.title || '')}` },
    { icon: 'pi-linkedin',  label: 'LinkedIn',  href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}` },
    { icon: 'pi-whatsapp',  label: 'WhatsApp',  href: `https://wa.me/?text=${encodeURIComponent((selectedPost.value?.title || '') + ' ' + shareUrl.value)}` },
]);

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
    <Head :title="`${t('public.blog.head_title')} - ${identity.site_name || 'SubnetCongo'}`">
        <meta name="description" :content="t('public.blog.head_desc')">
    </Head>

    <AppLayout>
        <!-- ================================================================== -->
        <!-- HERO : signature sombre Landing, déclinaison éditoriale -->
        <!-- ================================================================== -->
        <header class="relative min-h-[60vh] flex items-end pt-20 overflow-hidden bg-[#020617]">
            <div class="absolute inset-0 z-0 bg-[#020617]">
                <img src="/media/realizations/real-p26-2.jpg" alt="" aria-hidden="true" class="w-full h-full object-cover opacity-30" loading="eager" decoding="async" />
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
                        <span class="text-xs font-bold uppercase tracking-widest text-blue-200">{{ t('public.blog.pill_magazine') }}</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-[1.05] tracking-tight reveal-on-scroll delay-100">
                        {{ t('public.blog.hero_title_1') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-amber-400 italic">{{ t('public.blog.hero_title_2') }}</span>
                    </h1>

                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl font-light leading-relaxed border-l-4 border-red-500 pl-5 reveal-on-scroll delay-200">
                        {{ t('public.blog.hero_sub') }}
                    </p>
                </div>
            </div>
        </header>

        <!-- ================================================================== -->
        <!-- SECTION : ARTICLES À LA UNE (éditorial asymétrique) -->
        <!-- ================================================================== -->
        <section v-if="featuredMain" class="py-20 bg-white">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex items-center justify-between mb-10 reveal-on-scroll">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-1 bg-[#2563EB] rounded-full"></span>
                        <span class="font-bold uppercase tracking-widest text-xs text-[#2563EB]">{{ t('public.blog.featured') }}</span>
                    </div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ posts.total }} {{ t('public.blog.articles_count') }}</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Article principal -->
                    <article class="reveal-on-scroll group relative rounded-[1.5rem] overflow-hidden bg-[#0B1221] min-h-[480px] flex flex-col justify-end cursor-pointer" @click="openArticle(featuredMain)">
                        <img :src="postImage(featuredMain)" loading="eager" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700" :alt="featuredMain.title" />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1221] via-[#0B1221]/40 to-transparent"></div>
                        <div class="relative p-8 lg:p-10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md">{{ postCategory(featuredMain) }}</span>
                                <span class="text-white/60 text-xs font-bold">{{ formatDate(featuredMain.published_at) }}</span>
                            </div>
                            <h2 class="text-2xl lg:text-4xl font-black text-white leading-tight mb-4 group-hover:text-blue-200 transition-colors">
                                {{ featuredMain.title }}
                            </h2>
                            <p class="text-slate-300 text-sm lg:text-base leading-relaxed line-clamp-3 mb-6">{{ featuredMain.excerpt }}</p>
                            <div class="flex items-center gap-3">
                                <img :src="`${DEFAULT_AVATAR}${featuredMain.author?.name || 'A'}`" class="w-10 h-10 rounded-full border-2 border-white/30" :alt="featuredMain.author?.name" />
                                <div>
                                    <p class="text-white text-sm font-bold">{{ featuredMain.author?.name || t('public.blog.redaction') }}</p>
                                    <p class="text-white/50 text-xs">{{ readTime(featuredMain) }} {{ t('public.blog.read_time') }}</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Articles secondaires -->
                    <div class="flex flex-col gap-6">
                        <article v-for="post in featuredSecondary" :key="post.id" @click="openArticle(post)" class="reveal-on-scroll group flex-1 flex flex-col sm:flex-row bg-[#F4F6F9] rounded-[1.25rem] overflow-hidden border border-slate-100 hover:shadow-xl transition-all cursor-pointer">
                            <div class="sm:w-2/5 h-48 sm:h-auto shrink-0 overflow-hidden bg-slate-200">
                                <img :src="postImage(post)" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" :alt="post.title" />
                            </div>
                            <div class="p-6 flex flex-col justify-center">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="bg-[#0B1221] text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded">{{ postCategory(post) }}</span>
                                    <span class="text-slate-400 text-xs font-bold">{{ formatDate(post.published_at) }}</span>
                                </div>
                                <h3 class="text-lg lg:text-xl font-black text-[#0B1221] leading-snug mb-2 line-clamp-2 group-hover:text-[#2563EB] transition-colors">{{ post.title }}</h3>
                                <p class="text-sm text-slate-500 line-clamp-2 mb-3">{{ post.excerpt }}</p>
                                <p class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
                                    <i class="pi pi-clock text-[10px]"></i> {{ readTime(post) }} {{ t('public.blog.read_time') }}
                                </p>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : RECHERCHE & FILTRES CATÉGORIES -->
        <!-- ================================================================== -->
        <section class="py-10 bg-[#F4F6F9] border-b border-slate-100 sticky top-[65px] z-30">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">
                <div class="reveal-on-scroll bg-white rounded-[1.25rem] border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col lg:flex-row gap-4 items-center">
                    <!-- Recherche -->
                    <div class="relative w-full lg:w-80">
                        <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input v-model="search" @keyup.enter="applyFilters()" type="search" :placeholder="t('public.blog.search_placeholder')"
                            class="w-full pl-11 pr-4 py-3 bg-[#F4F6F9] border-none rounded-xl text-sm font-medium text-[#0B1221] focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                    </div>

                    <!-- Pills catégories -->
                    <div class="flex flex-nowrap overflow-x-auto hide-scrollbar gap-2 flex-1">
                        <button @click="applyFilters('')"
                            class="relative px-5 py-2.5 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="!activeCategory ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">{{ t('public.blog.all') }}</span>
                            <div v-if="!activeCategory" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0"></div>
                        </button>
                        <button v-for="cat in blogCategories" :key="cat.id" @click="applyFilters(cat.slug)"
                            class="relative px-5 py-2.5 rounded-lg font-bold text-xs whitespace-nowrap transition-all duration-300"
                            :class="activeCategory === cat.slug ? 'text-white' : 'text-slate-600 hover:text-slate-900 bg-[#F4F6F9]'">
                            <span class="relative z-10">{{ cat.name }} <span class="opacity-60">({{ cat.posts_count }})</span></span>
                            <div v-if="activeCategory === cat.slug" class="absolute inset-0 bg-[#0B1221] rounded-lg shadow z-0"></div>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : GRILLE DES ARTICLES -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#F4F6F9] min-h-[60vh]">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-12">

                <div v-if="posts.data?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <article v-for="(post, idx) in posts.data" :key="post.id" @click="openArticle(post)"
                        class="reveal-on-scroll group flex flex-col bg-white rounded-[1.25rem] overflow-hidden shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-2xl border border-slate-100 transition-all duration-300 h-full cursor-pointer"
                        :style="{ transitionDelay: (idx % 6) * 60 + 'ms' }"
                    >
                        <!-- Image -->
                        <div class="relative h-[220px] w-full shrink-0 overflow-hidden bg-slate-900">
                            <img :src="postImage(post)" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" :alt="post.title" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-60"></div>
                            <div class="absolute top-4 left-4 bg-white/95 backdrop-blur text-[#0B1221] text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full shadow-md">
                                {{ postCategory(post) }}
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-6 md:p-7 flex-1 flex flex-col bg-white">
                            <!-- Méta -->
                            <div class="flex items-center gap-3 mb-3 text-xs font-bold text-slate-400">
                                <span class="flex items-center gap-1.5"><i class="pi pi-calendar text-[10px]"></i> {{ formatDate(post.published_at) }}</span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span class="flex items-center gap-1.5"><i class="pi pi-clock text-[10px]"></i> {{ readTime(post) }} min</span>
                            </div>

                            <!-- Titre -->
                            <h3 class="text-xl font-black text-[#0B1221] leading-snug mb-3 line-clamp-2 group-hover:text-[#2563EB] transition-colors">
                                {{ post.title }}
                            </h3>

                            <!-- Extrait -->
                            <p class="text-sm text-slate-500 leading-relaxed line-clamp-3 mb-5">{{ post.excerpt || t('public.blog.excerpt_fallback') }}</p>

                            <div class="flex-1"></div>

                            <!-- Auteur + CTA -->
                            <div class="flex items-center justify-between pt-5 border-t border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <img :src="`${DEFAULT_AVATAR}${post.author?.name || 'A'}`" class="w-8 h-8 rounded-full object-cover border border-slate-200" :alt="post.author?.name" />
                                    <span class="text-xs text-slate-600 font-bold">{{ post.author?.name || t('public.blog.redaction') }}</span>
                                </div>
                                <span class="flex items-center gap-1.5 text-xs font-bold text-[#2563EB] group-hover:gap-3 transition-all">
                                    {{ t('public.blog.read') }} <i class="pi pi-arrow-right text-[10px]"></i>
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-24 bg-white rounded-[1rem] border border-dashed border-slate-300 w-full reveal-on-scroll">
                    <div class="w-20 h-20 bg-[#F4F6F9] rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-book text-3xl text-slate-300"></i>
                    </div>
                    <p class="text-lg font-bold text-slate-600">{{ t('public.blog.no_articles') }}{{ search ? ` ("${search}")` : '' }} {{ t('public.blog.empty_title') }}</p>
                    <button v-if="search || activeCategory" @click="search = ''; applyFilters('')" class="mt-4 text-[#2563EB] font-bold hover:underline text-sm">{{ t('public.blog.reset_filters') }}</button>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links?.length > 3" class="mt-12 flex justify-center gap-2 flex-wrap">
                    <Link v-for="(l, i) in posts.links" :key="i" :href="l.url || '#'" v-html="l.label"
                        :class="['px-4 py-2 rounded-xl text-sm font-bold transition-colors', l.active ? 'bg-[#0B1221] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-100 hover:bg-slate-50', !l.url && 'opacity-40 pointer-events-none']" />
                </div>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- SECTION : NEWSLETTER (bandeau dark signature Landing) -->
        <!-- ================================================================== -->
        <section class="py-20 bg-[#0B1221] text-white relative overflow-hidden">
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-red-600/10 rounded-full blur-3xl opacity-50"></div>
            <div class="relative max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-12 text-center reveal-on-scroll">
                <div class="inline-flex items-center gap-2 mb-4">
                    <i class="pi pi-send text-[#2563EB]"></i>
                    <span class="font-bold uppercase tracking-widest text-xs text-blue-300">{{ t('public.blog.newsletter_pill') }}</span>
                </div>
                <h2 class="text-3xl md:text-5xl font-black tracking-tight mb-4">{{ t('public.blog.newsletter_title') }}</h2>
                <p class="text-slate-400 text-lg mb-10 max-w-xl mx-auto">{{ t('public.blog.newsletter_desc') }}</p>

                <div v-if="newsletterSent" class="inline-flex items-center gap-3 px-6 py-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold rounded-xl">
                    <i class="pi pi-check-circle"></i> {{ t('public.blog.newsletter_thanks') }}
                </div>
                <form v-else @submit.prevent="subscribe" class="flex flex-col sm:flex-row items-center gap-3 max-w-lg mx-auto">
                    <input v-model="newsletter.email" type="email" required :placeholder="t('public.blog.newsletter_email')"
                        class="flex-1 w-full px-6 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-slate-500 font-medium focus:ring-2 focus:ring-[#2563EB] outline-none transition-all" />
                    <button type="submit" class="w-full sm:w-auto px-8 py-4 bg-[#2563EB] hover:bg-blue-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-blue-500/20">
                        {{ t('public.blog.newsletter_button') }}
                    </button>
                </form>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- MODAL : LECTURE ARTICLE -->
        <!-- ================================================================== -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="selectedPost" class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-8" @click.self="closeArticle">
                    <div class="absolute inset-0 bg-[#020617]/80 backdrop-blur-sm"></div>

                    <div class="relative w-full max-w-3xl max-h-[90vh] bg-white rounded-[1.5rem] shadow-2xl overflow-hidden flex flex-col">
                        <!-- Header image -->
                        <div class="relative h-56 sm:h-72 shrink-0 overflow-hidden bg-slate-900">
                            <img :src="postImage(selectedPost)" class="w-full h-full object-cover" :alt="selectedPost.title" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                            <button @click="closeArticle" class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-colors">
                                <i class="pi pi-times"></i>
                            </button>
                            <div class="absolute bottom-5 left-6 right-6">
                                <span class="inline-block bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md mb-3">{{ postCategory(selectedPost) }}</span>
                                <h2 class="text-xl sm:text-2xl font-black text-white leading-tight">{{ selectedPost.title }}</h2>
                            </div>
                        </div>

                        <!-- Contenu scrollable -->
                        <div class="overflow-y-auto custom-scrollbar p-6 sm:p-8">
                            <!-- Méta auteur -->
                            <div class="flex flex-wrap items-center gap-4 pb-6 mb-6 border-b border-slate-100">
                                <img :src="`${DEFAULT_AVATAR}${selectedPost.author?.name || 'A'}`" class="w-12 h-12 rounded-full object-cover border-2 border-slate-100" :alt="selectedPost.author?.name" />
                                <div class="flex-1">
                                    <p class="text-sm font-black text-[#0B1221]">{{ selectedPost.author?.name || t('public.blog.redaction_subnetcongo') }}</p>
                                    <p class="text-xs text-slate-400 font-bold flex items-center gap-3 mt-0.5">
                                        <span class="flex items-center gap-1"><i class="pi pi-calendar text-[10px]"></i> {{ formatDate(selectedPost.published_at) }}</span>
                                        <span class="flex items-center gap-1"><i class="pi pi-clock text-[10px]"></i> {{ articleReadTime(selectedPost) }} {{ t('public.blog.read_time') }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Corps article -->
                            <div v-if="selectedPost.content" class="prose prose-slate max-w-none article-content" v-html="selectedPost.content"></div>
                            <p v-else class="text-slate-600 leading-relaxed">{{ selectedPost.excerpt }}</p>
                        </div>

                        <!-- Footer partage -->
                        <div class="shrink-0 px-6 sm:px-8 py-5 bg-[#F4F6F9] border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="text-xs text-slate-400 font-medium">{{ t('public.blog.share_article') }}</p>
                            <div class="flex items-center gap-2">
                                <button @click="copyLink" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#2563EB] hover:border-[#2563EB] transition-colors" :title="linkCopied ? t('public.blog.link_copied') : t('public.blog.copy_link')">
                                    <i :class="['pi', linkCopied ? 'pi-check text-emerald-500' : 'pi-link']"></i>
                                </button>
                                <a v-for="s in shareTargets" :key="s.label" :href="s.href" target="_blank" rel="noopener"
                                   class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-white hover:bg-[#0B1221] transition-colors" :title="`${t('public.blog.share_on')} ${s.label}`">
                                    <i :class="['pi', s.icon]"></i>
                                </a>
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

/* Typographie article */
.article-content { font-size: 1rem; line-height: 1.8; color: #475569; }
.article-content h2, .article-content h3 { color: #0B1221; font-weight: 900; margin-top: 1.5em; }
.article-content p { margin-bottom: 1em; }
.article-content img { border-radius: 1rem; margin: 1.5em 0; }
.article-content ul, .article-content ol { padding-left: 1.5em; margin-bottom: 1em; }
.article-content blockquote { border-left: 4px solid #2563EB; padding-left: 1em; font-style: italic; color: #64748b; }
.article-content a { color: #2563EB; font-weight: 700; text-decoration: underline; }
</style>
