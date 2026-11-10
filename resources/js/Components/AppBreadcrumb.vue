<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] }, // [{ label, to?, icon? }]
});

const page = usePage();

// Segments fréquents -> libellés français lisibles (sans IDs ni jargon technique)
const LABELS = {
    admin: 'Administration',
    dashboard: 'Tableau de bord',
    elearning: 'Pédagogie',
    emails: 'Boîte email',
    compose: 'Composer',
    sent: 'Envoyés',
    draft: 'Brouillon',
    drafts: 'Brouillons',
    trash: 'Corbeille',
    folders: 'Dossiers',
    'email-templates': 'Modèles d’emails',
    templates: 'Modèles',
    gmapping: 'Cartographie',
    courses: 'Cours',
    modules: 'Leçons & Modules',
    lessons: 'Leçons',
    'learning-paths': 'Parcours d’apprentissage',
    paths: 'Parcours',
    exams: 'Examens',
    attempts: 'Tentatives',
    results: 'Résultats',
    certificates: 'Certificats',
    grading: 'Corrections',
    enrollments: 'Demandes d’inscription',
    users: 'Utilisateurs',
    students: 'Apprenants',
    instructors: 'Formateurs',
    cohorts: 'Cohortes',
    web: 'Site web',
    pages: 'Pages',
    blog: 'Articles',
    menus: 'Menus & Navigation',
    testimonials: 'Témoignages',
    realizations: 'Réalisations',
    theme: 'Thème',
    widgets: 'Widgets',
    landing: 'Landing page',
    seo: 'SEO',
    assets: 'Médiathèque',
    media: 'Médias',
    system: 'Configuration',
    settings: 'Paramètres',
    video: 'Visioconférence',
    payments: 'Paiements',
    languages: 'Langues',
    translations: 'Traductions',
    logs: 'Journaux',
    roles: 'Rôles',
    permissions: 'Permissions',
    api: 'API',
    maintenance: 'Maintenance',
    server: 'Serveur',
    marketing: 'Marketing',
    coupons: 'Coupons',
    affiliates: 'Affiliation',
    subscriptions: 'Abonnements',
    marketplace: 'Marketplace',
    questions: 'Questions',
    social: 'Communauté',
    forum: 'Forums',
    support: 'Support',
    tickets: 'Tickets',
    'live-sessions': 'Classes virtuelles',
    live: 'Classes virtuelles',
    messages: 'Messages',
    student: 'Espace apprenant',
    profile: 'Mon profil',
    progress: 'Ma progression',
    notifications: 'Notifications',
    reports: 'Rapports',
    sales: 'Ventes',
    billing: 'Facturation',
    legal: 'Identité légale',
    questions_answers: 'Réponses',
    // Verbes usuels -> français métier
    create: 'Créer',
    new: 'Nouveau',
    edit: 'Modifier',
    show: 'Voir',
    view: 'Voir',
    update: 'Modifier',
    delete: 'Supprimer',
    destroy: 'Supprimer',
    invite: 'Inviter',
    share: 'Partager',
    join: 'Rejoindre',
    report: 'Rapport',
    analytics: 'Analytique',
    stats: 'Statistiques',
    dashboard_view: 'Tableau de bord',
};

// Segments techniques à masquer (IDs auto-incrémentés, UUIDs, hashes)
const isIdSegment = (seg) => {
    if (/^\d+$/.test(seg)) return true; // 12, 45…
    if (/^[0-9a-fA-F]{8}(-[0-9a-fA-F]{4}){3}-[0-9a-fA-F]{12}$/.test(seg)) return true; // uuid
    if (/^[0-9a-f]{20,}$/i.test(seg)) return true; // hashes
    return false;
};

// Verbes filtrés (pas de crumb « index/update/delete » vide de sens)
const isSilentSegment = (seg) => ['index', 'update', 'store', 'destroy'].includes(seg);

const labelFor = (seg) => {
    if (Object.prototype.hasOwnProperty.call(LABELS, seg)) return LABELS[seg];
    const prettified = seg.replace(/[-_]/g, ' ').trim();
    return prettified.charAt(0).toUpperCase() + prettified.slice(1);
};

const homeHref = () => {
    try { return route('dashboard'); } catch (_) { return '/dashboard'; }
};

const trail = computed(() => {
    if (props.items && props.items.length > 0) return props.items;

    const path = page.url.split('?')[0];
    const segments = path.split('/').filter(Boolean);
    const crumbs = [{ label: 'Accueil', to: homeHref(), icon: 'pi pi-home' }];

    let url = '';
    for (const seg of segments) {
        url += '/' + seg;
        if (isIdSegment(seg) || isSilentSegment(seg)) continue;
        const label = labelFor(seg);
        if (!label) continue;
        crumbs.push({ label, to: url });
    }

    if (crumbs.length > 0) crumbs[crumbs.length - 1] = { ...crumbs[crumbs.length - 1], to: null };
    return crumbs;
});
</script>

<template>
    <nav class="flex items-center gap-1.5 text-xs font-bold min-w-0" aria-label="Fil d’Ariane">
        <template v-for="(item, idx) in trail" :key="idx">
            <i v-if="item.icon" :class="item.icon" class="text-slate-400 text-xs shrink-0" aria-hidden="true"></i>
            <Link v-if="item.to && idx < trail.length - 1"
                  :href="item.to"
                  class="text-slate-500 hover:text-violet-600 transition-colors truncate">
                {{ item.label }}
            </Link>
            <span v-else class="text-slate-800 truncate" aria-current="page">{{ item.label }}</span>
            <i v-if="idx < trail.length - 1" class="pi pi-angle-right text-slate-300 text-[10px] mx-1 shrink-0" aria-hidden="true"></i>
        </template>
    </nav>
</template>
