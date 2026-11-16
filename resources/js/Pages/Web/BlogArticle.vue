<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    identity: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    menuPages: { type: Array, default: () => [] },
    post: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=2070&auto=format&fit=crop';
const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=f1f5f9&color=64748b&name=';
const SITE_URL = 'https://subnetcongo.net';

const postImage = (p) => p.featured_image || p.thumbnail || DEFAULT_IMAGE;
const image = computed(() => postImage(props.post));
const articleUrl = `${SITE_URL}/blog/${props.post.slug}`;
const siteName = computed(() => props.identity.site_name || 'SubnetCongo');

const stripHtml = (html) => {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
};

const description = computed(() => {
    const base = stripHtml(props.post.excerpt || props.post.content || '') || props.post.title;
    return base.length > 160 ? `${base.slice(0, 157).trimEnd()}…` : base;
});

const formatDate = (date) => {
    if (!date) return '';
    try {
        return new Date(date).toLocaleDateString(locale.value, { day: 'numeric', month: 'long', year: 'numeric' });
    } catch (e) {
        return date;
    }
};

const readTime = computed(() => {
    const text = stripHtml(props.post.content || '');
    const words = text.split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.round(words / 200));
});

const absoluteImage = computed(() => (image.value.startsWith('http') ? image.value : `${SITE_URL}${image.value}`));

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: props.post.title,
    description: description.value,
    image: [absoluteImage.value],
    datePublished: props.post.published_at,
    dateModified: props.post.updated_at || props.post.published_at,
    inLanguage: locale.value,
    author: [{ '@type': 'Person', name: props.post.author?.name || 'Subnet Congo' }],
    publisher: {
        '@type': 'Organization',
        name: 'Subnet Congo',
        logo: { '@type': 'ImageObject', url: `${SITE_URL}/assets/media/logos/logo.png` },
    },
    mainEntityOfPage: { '@type': 'WebPage', '@id': articleUrl },
}));

const breadcrumbJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Accueil', item: SITE_URL },
        { '@type': 'ListItem', position: 2, name: 'Blog', item: `${SITE_URL}/blog` },
        { '@type': 'ListItem', position: 3, name: props.post.title, item: articleUrl },
    ],
}));

const linkCopied = ref(false);
const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(articleUrl);
        linkCopied.value = true;
        setTimeout(() => { linkCopied.value = false; }, 2500);
    } catch (e) { /* clipboard indisponible */ }
};

const shareTargets = computed(() => [
    { icon: 'pi-facebook', label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(articleUrl)}` },
    { icon: 'pi-twitter', label: 'X / Twitter', href: `https://twitter.com/intent/tweet?url=${encodeURIComponent(articleUrl)}&text=${encodeURIComponent(props.post.title)}` },
    { icon: 'pi-linkedin', label: 'LinkedIn', href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(articleUrl)}` },
    { icon: 'pi-whatsapp', label: 'WhatsApp', href: `https://wa.me/?text=${encodeURIComponent(props.post.title + ' ' + articleUrl)}` },
]);
</script>

<template>
    <Head :title="`${post.title} — ${siteName}`">
        <meta name="description" :content="description" />
        <link rel="canonical" :href="articleUrl" />
        <meta property="og:type" content="article" />
        <meta property="og:title" :content="post.title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="absoluteImage" />
        <meta property="og:url" :content="articleUrl" />
        <meta property="og:site_name" :content="siteName" />
        <meta property="article:published_time" :content="post.published_at" />
        <meta property="article:section" :content="post.category?.name" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="post.title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="absoluteImage" />
        <script type="application/ld+json" v-html="JSON.stringify(jsonLd)"></script>
        <script type="application/ld+json" v-html="JSON.stringify(breadcrumbJsonLd)"></script>
    </Head>

    <AppLayout>
        <!-- HÉRO ARTICLE -->
        <header class="relative pt-28 sm:pt-32 pb-10 sm:pb-14 bg-[#020617] text-white overflow-hidden">
            <img :src="image" class="absolute inset-0 w-full h-full object-cover opacity-35" :alt="post.title" loading="eager" decoding="async" />
            <div class="absolute inset-0 bg-gradient-to-t from-[#020617] via-[#020617]/70 to-transparent"></div>
            <div class="relative max-w-4xl mx-auto px-4 sm:px-6">
                <nav class="text-xs text-white/60 font-bold mb-5 flex flex-wrap items-center gap-1.5" aria-label="Fil d'Ariane">
                    <Link href="/" class="hover:text-white transition-colors">Accueil</Link>
                    <i class="pi pi-angle-right text-[10px]" aria-hidden="true"></i>
                    <Link :href="route('blog')" class="hover:text-white transition-colors">Blog</Link>
                    <i class="pi pi-angle-right text-[10px]" aria-hidden="true"></i>
                    <span class="text-white/80 truncate max-w-[16rem]" aria-current="page">{{ post.title }}</span>
                </nav>
                <span v-if="post.category?.name" class="inline-block bg-[#2563EB] text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-md shadow-md mb-4">
                    {{ post.category.name }}
                </span>
                <h1 class="text-3xl sm:text-5xl font-black leading-[1.15] tracking-tight mb-6 text-white">{{ post.title }}</h1>
                <div class="flex flex-wrap items-center gap-4">
                    <img :src="`${DEFAULT_AVATAR}${post.author?.name || 'A'}`" class="w-11 h-11 rounded-full object-cover border-2 border-white/20" :alt="post.author?.name" loading="lazy" decoding="async" />
                    <div>
                        <p class="text-sm font-black text-white">{{ post.author?.name || t('public.blog.redaction_subnetcongo') }}</p>
                        <p class="text-xs text-white/60 font-bold flex flex-wrap items-center gap-3 mt-0.5">
                            <span class="flex items-center gap-1"><i class="pi pi-calendar text-[10px]" aria-hidden="true"></i> {{ formatDate(post.published_at) }}</span>
                            <span class="flex items-center gap-1"><i class="pi pi-clock text-[10px]" aria-hidden="true"></i> {{ readTime }} {{ t('public.blog.read_time') }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENU ARTICLE -->
        <section class="py-10 sm:py-14 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6">
                <article class="prose prose-slate max-w-none article-content" v-if="post.content" v-html="post.content"></article>
                <p v-else class="text-lg text-slate-600 leading-relaxed">{{ post.excerpt }}</p>

                <!-- PARTAGE -->
                <div class="mt-10 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-slate-400 font-medium">{{ t('public.blog.share_article') }}</p>
                    <div class="flex items-center gap-2">
                        <button @click="copyLink" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#2563EB] hover:border-[#2563EB] transition-colors" :title="linkCopied ? t('public.blog.link_copied') : t('public.blog.copy_link')" :aria-label="linkCopied ? t('public.blog.link_copied') : t('public.blog.copy_link')">
                            <i :class="['pi', linkCopied ? 'pi-check text-emerald-500' : 'pi-link']" aria-hidden="true"></i>
                        </button>
                        <a v-for="s in shareTargets" :key="s.label" :href="s.href" target="_blank" rel="noopener" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-white hover:bg-[#0B1221] transition-colors" :title="`${t('public.blog.share_on')} ${s.label}`" :aria-label="`${t('public.blog.share_on')} ${s.label}`">
                            <i :class="['pi', s.icon]" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ARTICLES LIÉS -->
        <section v-if="related.length" class="py-10 sm:py-14 bg-[#F4F6F9] border-t border-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <h2 class="text-lg sm:text-xl font-black text-[#0B1221] mb-6">{{ t('public.blog.related_read') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Link v-for="r in related" :key="r.id" :href="route('blog.show', r.slug)" class="group bg-white rounded-2xl overflow-hidden border border-slate-100 hover:shadow-xl transition-all">
                        <div class="h-40 overflow-hidden bg-slate-900">
                            <img :src="postImage(r)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" :alt="r.title" loading="lazy" decoding="async" />
                        </div>
                        <div class="p-5">
                            <span class="text-[10px] font-black uppercase tracking-wider text-[#2563EB]">{{ r.category?.name || t('public.blog.category_fallback') }}</span>
                            <h3 class="text-base font-black text-[#0B1221] leading-snug mt-2 line-clamp-2">{{ r.title }}</h3>
                            <p class="text-xs text-slate-400 font-bold mt-3">{{ formatDate(r.published_at) }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- RETOUR AU JOURNAL -->
        <div class="py-8 bg-white border-t border-slate-100 text-center">
            <Link :href="route('blog')" class="inline-flex items-center gap-2 text-sm font-black text-[#2563EB] hover:text-blue-800 uppercase tracking-widest transition-colors">
                <i class="pi pi-arrow-left" aria-hidden="true"></i> {{ t('public.blog.back_to_blog') }}
            </Link>
        </div>
    </AppLayout>
</template>

<style scoped>
.prose :where(p, ul, ol) { margin-bottom: 1.25em; }
.article-content { font-size: 1.05rem; line-height: 1.85; color: #334155; }
.article-content h2, .article-content h3 { color: #0B1221; font-weight: 900; margin-top: 1.6em; margin-bottom: 0.6em; line-height: 1.3; }
.article-content p { margin-bottom: 1.1em; }
.article-content img { border-radius: 1rem; margin: 1.5em auto; }
.article-content ul, .article-content ol { padding-left: 1.5em; margin-bottom: 1.25em; }
.article-content li { margin-bottom: 0.35em; }
.article-content blockquote { border-left: 4px solid #2563EB; padding-left: 1.25em; font-style: italic; color: #475569; margin: 1.5em 0; }
.article-content a { color: #2563EB; font-weight: 700; text-decoration: underline; }
.prose :deep(pre) { background: #0B1221; color: #e2e8f0; border-radius: 1rem; padding: 1.25rem; overflow-x: auto; font-size: 0.9em; margin: 1.5em 0; }
.prose :deep(code) { background: #f1f5f9; border-radius: 0.35rem; padding: 0.15em 0.4em; font-size: 0.9em; }
.prose :deep(pre code) { background: transparent; padding: 0; }
</style>
