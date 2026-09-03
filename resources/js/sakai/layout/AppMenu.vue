<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLayout } from '@/sakai/layout/composables/layout';
import AppMenuItem from './AppMenuItem.vue';

const { resetMenu } = useLayout();
const page = usePage();

// Helpers ---------------------------------------------------------------
const r = (name, params = null) => {
    try { return route(name, params); }
    catch (e) { return '#'; }
};

// Roles & permissions helpers ------------------------------------------
const userRoles = computed(() => {
    const direct = page.props.auth?.user?.roles;
    if (Array.isArray(direct) && direct.length) return direct.map(r => (typeof r === 'string' ? r : r.name));
    return (page.props.auth?.roles ?? []).map(r => (typeof r === 'string' ? r : r.name));
});
const isSuper = computed(() => userRoles.value.includes('superadmin'));
const isAdmin = computed(() => isSuper.value || userRoles.value.includes('admin'));
const isInstructor = computed(() => userRoles.value.includes('instructor'));
const isAssistant = computed(() => userRoles.value.includes('assistant') || userRoles.value.includes('cohort-assistant'));
const isFinance = computed(() => userRoles.value.includes('finance-manager'));
const isModerator = computed(() => userRoles.value.includes('moderator'));
const isSupport = computed(() => userRoles.value.includes('support-agent'));
const isStudent = computed(() => userRoles.value.includes('student'));

// Badge counts (live data)
const badges = computed(() => page.props.menu_badges ?? {});

// Menus ---------------------------------------------------------------
const model = computed(() => {
    // STUDENT MENU (simplifié — focus apprenant)
    if (isStudent.value && !isAdmin.value && !isInstructor.value) {
        return [
            {
                label: 'Apprentissage',
                icon: 'pi pi-fw pi-book',
                items: [
                    { label: 'Mon tableau de bord', icon: 'pi pi-fw pi-home', to: r('dashboard') },
                    { label: 'Mes formations', icon: 'pi pi-fw pi-list', to: r('student.my-courses') },
                    { label: 'Mes inscriptions', icon: 'pi pi-fw pi-id-card', to: r('student.my-enrollments'), badge: badges.value.enrollments_pending },
                    { label: 'Bibliothèque médias', icon: 'pi pi-fw pi-video', to: r('media-library.index') },
                    { label: 'Classes virtuelles', icon: 'pi pi-fw pi-desktop', to: r('live-sessions.index') },
                    { label: 'Marketplace', icon: 'pi pi-fw pi-shopping-bag', to: r('marketplace.index') },
                    { label: 'Mon panier', icon: 'pi pi-fw pi-shopping-cart', to: r('marketplace.cart'), badge: badges.value.cart },
                ]
            },
            {
                label: 'Évaluation',
                icon: 'pi pi-fw pi-pencil',
                items: [
                    { label: 'Mes examens', icon: 'pi pi-fw pi-clock', to: r('exams.learner.index'), badge: badges.value.exams_pending },
                    { label: 'Mes certificats', icon: 'pi pi-fw pi-verified', to: r('student.certificates'), badge: badges.value.certificates },
                ]
            },
            {
                label: 'Suivi & Communauté',
                icon: 'pi pi-fw pi-chart-line',
                items: [
                    { label: 'Ma progression', icon: 'pi pi-fw pi-chart-line', to: r('student.progress') },
                    { label: 'Mes questions', icon: 'pi pi-fw pi-question-circle', to: r('student.questions.index') },
                    { label: 'Notifications', icon: 'pi pi-fw pi-bell', to: r('notifications.index'), badge: badges.value.notifications },
                    { label: 'Forums & Discussions', icon: 'pi pi-fw pi-comments', to: r('social.forum.index') },
                    { label: 'Mes tickets support', icon: 'pi pi-fw pi-ticket', to: r('social.support.index') },
                ]
            },
        ];
    }

    // ADMIN / INSTRUCTOR MENU
    const adminMenu = [
        {
            label: 'Tableau de Bord',
            icon: 'pi pi-fw pi-home',
            items: [
                { label: 'Vue d\'ensemble', icon: 'pi pi-fw pi-th-large', to: r('dashboard') },
                { label: 'Analytique apprenants', icon: 'pi pi-fw pi-users', to: r('student-reports.index') },
                { label: 'Rapports de Ventes', icon: 'pi pi-fw pi-wallet', to: r('sales.reports') },
            ]
        },
        {
            label: 'Gestion E-learning',
            icon: 'pi pi-fw pi-book',
            items: [
                {
                    label: 'Contenu Pédagogique',
                    icon: 'pi pi-fw pi-folder',
                    items: [
                        { label: 'Tous les Cours', icon: 'pi pi-fw pi-list', to: r('courses.index'), badge: badges.value.courses },
                        { label: 'Leçons & Modules', icon: 'pi pi-fw pi-file', to: r('modules.index') },
                        { label: 'Parcours (Paths)', icon: 'pi pi-fw pi-map', to: r('learning-paths.index') },
                        { label: 'Bibliothèque Médias', icon: 'pi pi-fw pi-images', to: r('media-library.index') },
                    ]
                },
                {
                    label: 'Examens & Suivi',
                    icon: 'pi pi-fw pi-check-circle',
                    items: [
                        { label: 'Quiz & Tests', icon: 'pi pi-fw pi-pencil', to: r('exams.index') },
                        { label: 'Banque de Questions', icon: 'pi pi-fw pi-database', to: r('questions.index') },
                        { label: 'Corrections', icon: 'pi pi-fw pi-user-edit', to: r('gradings.edit') },
                        { label: 'Certificats', icon: 'pi pi-fw pi-verified', to: r('certificates.index'), badge: badges.value.certificates },
                    ]
                },
                { label: 'Classes Virtuelles', icon: 'pi pi-fw pi-video', to: r('live-sessions.index') },
            ]
        },
        {
            label: 'Administration du Site Web',
            icon: 'pi pi-fw pi-globe',
            items: [
                {
                    label: 'Gestion CMS',
                    icon: 'pi pi-fw pi-desktop',
                    items: [
                        { label: 'Pages Statiques', icon: 'pi pi-fw pi-clone', to: r('web.pages.index') },
                        { label: 'Articles de Blog', icon: 'pi pi-fw pi-align-left', to: r('web.blog.index') },
                        { label: 'Menus & Navigation', icon: 'pi pi-fw pi-bars', to: r('web.menus.index') },
                        { label: 'Témoignages', icon: 'pi pi-fw pi-star', to: r('web.testimonials.index') },
                        { label: 'Réalisations', icon: 'pi pi-fw pi-trophy', to: r('realizations') },
                    ]
                },
                {
                    label: 'Apparence & Design',
                    icon: 'pi pi-fw pi-palette',
                    items: [
                        { label: 'Thèmes & Couleurs', icon: 'pi pi-fw pi-image', to: r('web.theme') },
                        { label: 'Éditeur Widgets', icon: 'pi pi-fw pi-th-large', to: r('web.widgets') },
                        { label: 'SEO & Meta-tags', icon: 'pi pi-fw pi-search-plus', to: r('web.seo') },
                    ]
                },
                { label: 'Fichiers Publics', icon: 'pi pi-fw pi-folder-open', to: r('web.assets') },
            ]
        },
        {
            label: 'Communauté & Étudiants',
            icon: 'pi pi-fw pi-users',
            items: [
                { label: 'Liste des Apprenants', icon: 'pi pi-fw pi-user', to: r('users.students.index') },
                { label: 'Corps Enseignant', icon: 'pi pi-fw pi-user-tie', to: r('users.instructors.index') },
                { label: 'Cohortes / Groupes', icon: 'pi pi-fw pi-tags', to: r('users.cohorts.index') },
                { label: 'Demandes d\'inscription', icon: 'pi pi-fw pi-id-card', to: r('admin.enrollments.index'), badge: badges.value.enrollments_pending },
                { label: 'Forums & Discussions', icon: 'pi pi-fw pi-comments', to: r('social.forum.index'), badge: badges.value.forum_pending },
                { label: 'Support & Tickets', icon: 'pi pi-fw pi-ticket', to: r('social.support.index'), badge: badges.value.tickets_pending },
                { label: 'Messages du site web', icon: 'pi pi-fw pi-inbox', to: r('admin.messages.index') },
            ]
        },
        {
            label: 'Marketing & Ventes',
            icon: 'pi pi-fw pi-megaphone',
            items: [
                { label: 'Campagnes Emailing', icon: 'pi pi-fw pi-send', to: r('marketing.emails.index') },
                { label: 'Coupons de réduction', icon: 'pi pi-fw pi-percentage', to: r('marketing.coupons.index') },
                { label: 'Affiliation', icon: 'pi pi-fw pi-share-alt', to: r('marketing.affiliates.index') },
                { label: 'Abonnements & Plans', icon: 'pi pi-fw pi-credit-card', to: r('billing.subscriptions.index') },
                { label: 'Identité légale (RCCM/IFU)', icon: 'pi pi-fw pi-id-card', to: r('admin.legal.index') },
                { label: 'Éditeur Landing (slides/tech)', icon: 'pi pi-fw pi-sliders-h', to: r('admin.landing.index') },
                { label: 'Marketplace (vue catalogue)', icon: 'pi pi-fw pi-shopping-bag', to: r('marketplace.index') },
            ]
        },
        {
            label: 'Configuration Système',
            icon: 'pi pi-fw pi-cog',
            items: [
                {
                    label: 'Accès & Sécurité',
                    icon: 'pi pi-fw pi-shield',
                    items: [
                        { label: 'Gestion des Rôles', icon: 'pi pi-fw pi-lock', to: r('system.roles.index') },
                        { label: 'Permissions API', icon: 'pi pi-fw pi-key', to: r('system.api.index') },
                        { label: 'Logs d\'audit', icon: 'pi pi-fw pi-history', to: r('system.logs') },
                    ]
                },
                { label: 'Paramètres Généraux', icon: 'pi pi-fw pi-sliders-h', to: r('system.settings') },
                { label: 'Passerelles de Paiement', icon: 'pi pi-fw pi-dollar', to: r('system.payments') },
                { label: 'Traductions (i18n)', icon: 'pi pi-fw pi-globe', to: r('system.languages.index') },
                { label: 'Maintenance Serveur', icon: 'pi pi-fw pi-server', to: r('system.server') },
            ]
        },
    ];

    // Filtrage fin pour les instructeurs non-admin
    if (!isAdmin.value && isInstructor.value) {
        return adminMenu.map(group => {
            if (group.label === 'Tableau de Bord') {
                return {
                    ...group,
                    items: group.items.filter(i => !i.to?.includes('sales-reports'))
                };
            }
            if (group.label === 'Gestion E-learning') {
                return {
                    ...group,
                    items: [
                        {
                            label: 'Mon Contenu',
                            icon: 'pi pi-fw pi-folder',
                            items: [
                                { label: 'Mes Cours', icon: 'pi pi-fw pi-list', to: r('courses.index') },
                                { label: 'Leçons & Modules', icon: 'pi pi-fw pi-file', to: r('modules.index') },
                                { label: 'Bibliothèque Médias', icon: 'pi pi-fw pi-images', to: r('media-library.index') },
                            ]
                        },
                        {
                            label: 'Évaluation',
                            icon: 'pi pi-fw pi-check-circle',
                            items: [
                                { label: 'Mes Examens', icon: 'pi pi-fw pi-pencil', to: r('exams.index') },
                                { label: 'Questions apprenants', icon: 'pi pi-fw pi-inbox', to: r('instructor.questions.index'), badge: badges.value.questions_pending },
                                { label: 'Mes Étudiants', icon: 'pi pi-fw pi-users', to: r('users.students.index') },
                            ]
                        },
                        { label: 'Classes Virtuelles', icon: 'pi pi-fw pi-video', to: r('live-sessions.index') },
                    ]
                };
            }
            // Pas d'accès aux sections admin/marketing/système pour les instructeurs
            return null;
        }).filter(Boolean);
    }

    // Filtrage pour les rôles spécialisés (finance / support / moderator)
    if (!isAdmin.value && (isFinance.value || isSupport.value || isModerator.value)) {
        const allowed = {
            'finance-manager': ['Tableau de Bord', 'Marketing & Ventes', 'Communauté & Étudiants'],
            'support-agent': ['Tableau de Bord', 'Communauté & Étudiants'],
            'moderator': ['Tableau de Bord', 'Communauté & Étudiants'],
        };
        const roleKey = isFinance.value ? 'finance-manager' : (isSupport.value ? 'support-agent' : 'moderator');
        return adminMenu.filter(g => allowed[roleKey].includes(g.label));
    }

    return adminMenu;
});
</script>

<template>
    <ul class="layout-menu">
        <li class="flex justify-end p-3 lg:hidden">
            <button @click="resetMenu" class="flex items-center justify-center w-10 h-10 rounded-full text-gray-400 hover:bg-gray-100 transition-all">
                <i class="pi pi-times text-xl"></i>
            </button>
        </li>

        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item v-if="!item.separator" :item="item" :root="true" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>

        <!-- Badge indicator: current role -->
        <li class="mt-auto p-4 hidden lg:block">
            <div class="bg-gradient-to-br from-violet-50 to-violet-100 border border-violet-200 rounded-xl p-3">
                <p class="text-[9px] font-black text-violet-600 uppercase tracking-widest mb-1">Connecté en tant que</p>
                <p class="text-xs font-black text-slate-800 capitalize truncate">
                    {{ userRoles[0] ?? 'Utilisateur' }}
                </p>
                <p v-if="userRoles.length > 1" class="text-[9px] text-slate-500 mt-1">
                    +{{ userRoles.length - 1 }} rôle(s)
                </p>
            </div>
        </li>
    </ul>
</template>

<style scoped>
.layout-menu {
    display: flex;
    flex-direction: column;
    min-height: 100%;
}

.layout-menu li:last-child:not(.menu-separator) {
    margin-top: auto;
}

/* Active route visual cue */
:deep(.active-route) {
    background: linear-gradient(90deg, rgba(139, 92, 246, 0.12) 0%, rgba(139, 92, 246, 0.04) 100%);
    border-left: 3px solid #8B5CF6;
}

:deep(.layout-menuitem-root-text) {
    letter-spacing: 0.05em;
    font-weight: 800;
}

/* Smooth submenu transition */
:deep(.layout-submenu-enter-active),
:deep(.layout-submenu-leave-active) {
    transition: max-height 0.25s ease, opacity 0.2s ease;
    overflow: hidden;
}
:deep(.layout-submenu-enter-from),
:deep(.layout-submenu-leave-to) {
    max-height: 0;
    opacity: 0;
}
:deep(.layout-submenu-enter-to),
:deep(.layout-submenu-leave-from) {
    max-height: 800px;
    opacity: 1;
}
</style>
