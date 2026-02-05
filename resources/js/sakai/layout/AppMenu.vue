<script setup>
import { ref } from 'vue';
import { useLayout } from '@/sakai/layout/composables/layout';
import AppMenuItem from './AppMenuItem.vue';

const { resetMenu } = useLayout();

const model = ref([
    {
        label: 'Tableau de Bord',
        items: [
            { label: 'Vue d\'ensemble', icon: 'pi pi-fw pi-home', to: '/dashboard' },
            { label: 'Analytique Temps Réel', icon: 'pi pi-fw pi-chart-line', to: '/analytics' },
            { label: 'Rapports de Ventes', icon: 'pi pi-fw pi-wallet', to: '/sales-reports' }
        ]
    },
    {
        label: 'Gestion E-learning',
        items: [
            {
                label: 'Contenu Pédagogique',
                icon: 'pi pi-fw pi-book',
                items: [
                    { label: 'Tous les Cours', icon: 'pi pi-fw pi-list', to: '/courses' },
                    { label: 'Leçons & Modules', icon: 'pi pi-fw pi-file-pdf', to: '/modules' },
                    { label: 'Parcours (Paths)', icon: 'pi pi-fw pi-map', to: '/learning-paths' },
                    { label: 'Bibliothèque Médias', icon: 'pi pi-fw pi-video', to: '/media-library' }
                ]
            },
            {
                label: 'Examens & Suivi',
                icon: 'pi pi-fw pi-check-circle',
                items: [
                    { label: 'Quiz & Tests', icon: 'pi pi-fw pi-pencil', to: '/exams' },
                    { label: 'Banque de Questions', icon: 'pi pi-fw pi-database', to: '/questions' },
                    { label: 'Corrections', icon: 'pi pi-fw pi-user-edit', to: '/grading' },
                    { label: 'Certificats', icon: 'pi pi-fw pi-verified', to: '/certificates' }
                ]
            },
            { label: 'Classes Virtuelles', icon: 'pi pi-fw pi-video', to: '/live-sessions' }
        ]
    },
    {
        label: 'Administration du Site Web',
        items: [
            {
                label: 'Gestion CMS',
                icon: 'pi pi-fw pi-desktop',
                items: [
                    { label: 'Pages Statiques', icon: 'pi pi-fw pi-clone', to: '/web/pages' },
                    { label: 'Articles de Blog', icon: 'pi pi-fw pi-align-left', to: '/web/blog' },
                    { label: 'Menus & Navigation', icon: 'pi pi-fw pi-bars', to: '/web/menus' },
                    { label: 'Témoignages (Reviews)', icon: 'pi pi-fw pi-star', to: '/web/testimonials' }
                ]
            },
            {
                label: 'Apparence & Design',
                icon: 'pi pi-fw pi-palette',
                items: [
                    { label: 'Thèmes & Couleurs', icon: 'pi pi-fw pi-image', to: '/web/theme' },
                    { label: 'Éditeur Widgets', icon: 'pi pi-fw pi-th-large', to: '/web/widgets' },
                    { label: 'SEO & Meta-tags', icon: 'pi pi-fw pi-search-plus', to: '/web/seo' }
                ]
            },
            { label: 'Fichiers Publics', icon: 'pi pi-fw pi-folder-open', to: '/web/assets' }
        ]
    },
    {
        label: 'Communauté & Étudiants',
        items: [
            { label: 'Liste des Apprenants', icon: 'pi pi-fw pi-users', to: '/users/students' },
            { label: 'Corps Enseignant', icon: 'pi pi-fw pi-user-tie', to: '/users/instructors' },
            { label: 'Cohortes / Groupes', icon: 'pi pi-fw pi-tags', to: '/users/cohorts' },
            { label: 'Forums & Discussions', icon: 'pi pi-fw pi-comments', to: '/social/forums' },
            { label: 'Support & Tickets', icon: 'pi pi-fw pi-ticket', to: '/social/support' }
        ]
    },
    {
        label: 'Marketing & Ventes',
        items: [
            { label: 'Campagnes Emailing', icon: 'pi pi-fw pi-send', to: '/marketing/emails' },
            { label: 'Coupons de réduction', icon: 'pi pi-fw pi-percentage', to: '/marketing/coupons' },
            { label: 'Affiliation', icon: 'pi pi-fw pi-share-alt', to: '/marketing/affiliates' },
            { label: 'Abonnements & Plans', icon: 'pi pi-fw pi-credit-card', to: '/billing/subscriptions' }
        ]
    },
    {
        label: 'Configuration Système',
        items: [
            {
                label: 'Accès & Sécurité',
                icon: 'pi pi-fw pi-shield',
                items: [
                    { label: 'Gestion des Rôles', icon: 'pi pi-fw pi-lock', to: '/system/roles' },
                    { label: 'Permissions API', icon: 'pi pi-fw pi-key', to: '/system/api' },
                    { label: 'Logs d\'audit', icon: 'pi pi-fw pi-history', to: '/system/logs' }
                ]
            },
            { label: 'Paramètres Généraux', icon: 'pi pi-fw pi-cog', to: '/system/settings' },
            { label: 'Passerelles de Paiement', icon: 'pi pi-fw pi-dollar', to: '/system/payments' },
            { label: 'Traductions (i18n)', icon: 'pi pi-fw pi-globe', to: '/system/languages' },
            { label: 'Maintenance Serveur', icon: 'pi pi-fw pi-server', to: '/system/server' }
        ]
    }
]);
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
    </ul>
</template>
