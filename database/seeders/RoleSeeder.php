<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Vider le cache des permissions Spatie (Bonne pratique)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Définition exhaustive de toutes les permissions du système E-learning
        $permissions =[
            // ==========================================
            // SYSTÈME, DASHBOARD & ANALYTICS
            // ==========================================
            'access-dashboard',
            'view-system-analytics',
            'view-sales-analytics',
            'view-course-analytics',
            'manage-general-settings',
            'manage-payment-gateways',
            'manage-email-settings',
            'manage-integrations',
            'view-activity-logs',
            'view-system-backups',
            'create-system-backups',
            'download-system-backups',

            // ==========================================
            // GESTION DES UTILISATEURS ET RÔLES
            // ==========================================
            'view-user',
            'create-user',
            'update-user',
            'delete-user',
            'restore-user',
            'force-delete-user',
            'import-user',
            'export-user',
            'ban-user',
            'unban-user',
            'view-role',
            'create-role',
            'update-role',
            'delete-role',
            'view-permission',
            'create-permission',
            'update-permission',
            'delete-permission',
            'assign-role',
            'assign-permission',

            // ==========================================
            // CATALOGUE & TAXONOMIE (Catégories, Tags...)
            // ==========================================
            'view-category',
            'create-category',
            'update-category',
            'delete-category',
            'view-subcategory',
            'create-subcategory',
            'update-subcategory',
            'delete-subcategory',
            'view-tag',
            'create-tag',
            'update-tag',
            'delete-tag',
            'view-skill',
            'create-skill',
            'update-skill',
            'delete-skill',
            'view-language',
            'create-language',
            'update-language',
            'delete-language',
            'view-level',
            'create-level',
            'update-level',
            'delete-level',

            // ==========================================
            // COURS & PÉDAGOGIE
            // ==========================================
            'view-course',
            'create-course',
            'update-course',
            'delete-course',
            'restore-course',
            'force-delete-course',
            'publish-course',
            'unpublish-course',
            'approve-course',
            'reject-course',
            'duplicate-course',
            'view-chapter',
            'create-chapter',
            'update-chapter',
            'delete-chapter',
            'reorder-chapter',
            'view-lesson',
            'create-lesson',
            'update-lesson',
            'delete-lesson',
            'reorder-lesson',
            'upload-video',
            'delete-video',
            'view-resource',
            'create-resource',
            'update-resource',
            'delete-resource',
            'upload-scorm',
            'view-scorm',
            'delete-scorm',
            'view-live-class',
            'create-live-class',
            'update-live-class',
            'delete-live-class',
            'join-live-class',

            // ==========================================
            // ÉVALUATIONS (Quiz, Devoirs, Questions)
            // ==========================================
            'view-quiz',
            'create-quiz',
            'update-quiz',
            'delete-quiz',
            'attempt-quiz',
            'grade-quiz',
            'view-question',
            'create-question',
            'update-question',
            'delete-question',
            'import-question',
            'view-assignment',
            'create-assignment',
            'update-assignment',
            'delete-assignment',
            'submit-assignment',
            'grade-assignment',

            // ==========================================
            // INSCRIPTIONS & SUIVI APPRENANTS
            // ==========================================
            'view-enrollment',
            'create-enrollment',
            'update-enrollment',
            'delete-enrollment',
            'cancel-enrollment',
            'view-progress',
            'reset-progress',
            'view-wishlist',
            'add-to-wishlist',
            'remove-from-wishlist',

            // ==========================================
            // CERTIFICATS & GAMIFICATION
            // ==========================================
            'view-certificate',
            'create-certificate-template',
            'update-certificate-template',
            'delete-certificate-template',
            'issue-certificate',
            'download-certificate',
            'verify-certificate',
            'view-badge',
            'create-badge',
            'update-badge',
            'delete-badge',
            'award-badge',
            'view-point',
            'award-point',
            'deduct-point',

            // ==========================================
            // E-COMMERCE, VENTES & PAIEMENTS
            // ==========================================
            'view-plan',
            'create-plan',
            'update-plan',
            'delete-plan',
            'view-payment',
            'create-payment',
            'update-payment',
            'delete-payment',
            'export-payment',
            'view-invoice',
            'create-invoice',
            'update-invoice',
            'delete-invoice',
            'download-invoice',
            'view-refund',
            'request-refund',
            'process-refund',
            'reject-refund',
            'view-coupon',
            'create-coupon',
            'update-coupon',
            'delete-coupon',
            'view-payout',
            'request-payout',
            'process-payout',
            'reject-payout',
            'view-affiliate',
            'approve-affiliate',
            'manage-affiliate-commission',

            // ==========================================
            // ENGAGEMENT, AVIS & FORUMS
            // ==========================================
            'view-review',
            'create-review',
            'update-review',
            'delete-review',
            'moderate-review',
            'view-comment',
            'create-comment',
            'update-comment',
            'delete-comment',
            'moderate-comment',
            'view-forum-category',
            'create-forum-category',
            'update-forum-category',
            'delete-forum-category',
            'view-forum-topic',
            'create-forum-topic',
            'update-forum-topic',
            'delete-forum-topic',
            'pin-forum-topic',
            'close-forum-topic',
            'view-forum-post',
            'create-forum-post',
            'update-forum-post',
            'delete-forum-post',
            'moderate-forum-post',
            'view-message',
            'send-message',
            'delete-message',

            // ==========================================
            // SUPPORT CLIENT & TICKETS
            // ==========================================
            'view-ticket',
            'create-ticket',
            'update-ticket',
            'delete-ticket',
            'assign-ticket',
            'resolve-ticket',
            'view-ticket-reply',
            'create-ticket-reply',
            'update-ticket-reply',
            'delete-ticket-reply',
            'view-faq',
            'create-faq',
            'update-faq',
            'delete-faq',

            // ==========================================
            // MARKETING, BLOG & CMS
            // ==========================================
            'view-blog',
            'create-blog',
            'update-blog',
            'delete-blog',
            'publish-blog',
            'view-page',
            'create-page',
            'update-page',
            'delete-page',
            'publish-page',
            'view-media',
            'upload-media',
            'delete-media',
            'view-campaign',
            'create-campaign',
            'send-campaign',
            'delete-campaign',
            'view-notification',
            'send-push-notification',
            'mark-notification-read',
        ];

        // 3. Création des permissions dans la BDD
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ==========================================
        // 4. CRÉATION DES RÔLES ET ASSIGNATION
        // ==========================================

        /**
         * RÔLE 1 : SUPER ADMIN
         * Accès total et absolu au système
         */



        /**
         * RÔLE 2 : ADMIN (Administrateur principal)
         * Gère la plateforme, les utilisateurs, les cours, les ventes.
         * Ne gère pas les paramètres critiques du serveur, rôles ou logs de backup.
         */
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'access-dashboard',
            'view-system-analytics',
            'view-sales-analytics',
            'view-course-analytics',
            // Utilisateurs
            'view-user',
            'create-user',
            'update-user',
            'ban-user',
            'unban-user',
            'export-user',
            // Catalogue
            'view-category',
            'create-category',
            'update-category',
            'delete-category',
            'view-subcategory',
            'create-subcategory',
            'update-subcategory',
            'delete-subcategory',
            'view-tag',
            'create-tag',
            'update-tag',
            'delete-tag',
            'view-skill',
            'create-skill',
            'update-skill',
            'delete-skill',
            'view-language',
            'create-language',
            'update-language',
            'delete-language',
            'view-level',
            'create-level',
            'update-level',
            'delete-level',
            // Cours
            'view-course',
            'update-course',
            'delete-course',
            'publish-course',
            'unpublish-course',
            'approve-course',
            'reject-course',
            'view-chapter',
            'view-lesson',
            'view-resource',
            'view-live-class',
            // Quiz
            'view-quiz',
            'view-question',
            'view-assignment',
            // Inscriptions
            'view-enrollment',
            'create-enrollment',
            'update-enrollment',
            'delete-enrollment',
            'cancel-enrollment',
            'view-progress',
            // Certificats
            'view-certificate',
            'create-certificate-template',
            'update-certificate-template',
            'delete-certificate-template',
            'issue-certificate',
            'verify-certificate',
            // E-commerce
            'view-plan',
            'create-plan',
            'update-plan',
            'delete-plan',
            'view-payment',
            'export-payment',
            'view-invoice',
            'download-invoice',
            'view-refund',
            'process-refund',
            'reject-refund',
            'view-coupon',
            'create-coupon',
            'update-coupon',
            'delete-coupon',
            'view-payout',
            'process-payout',
            'reject-payout',
            // Engagement
            'view-review',
            'moderate-review',
            'view-comment',
            'moderate-comment',
            'view-forum-category',
            'create-forum-category',
            'update-forum-category',
            'delete-forum-category',
            'view-forum-topic',
            'delete-forum-topic',
            'pin-forum-topic',
            'close-forum-topic',
            'view-forum-post',
            'moderate-forum-post',
            // Support
            'view-ticket',
            'assign-ticket',
            'resolve-ticket',
            'view-ticket-reply',
            'view-faq',
            'create-faq',
            'update-faq',
            'delete-faq',
            // CMS
            'view-blog',
            'create-blog',
            'update-blog',
            'delete-blog',
            'publish-blog',
            'view-page',
            'create-page',
            'update-page',
            'delete-page',
            'publish-page',
            'view-media',
            'upload-media',
            'delete-media',
        ]);


        /**
         * RÔLE 3 : INSTRUCTOR (Formateur / Professeur)
         * Crée et gère son propre contenu, voit les statistiques de ses cours, interagit avec ses étudiants.
         */
        $instructor = Role::firstOrCreate(['name' => 'instructor']);
        $instructor->givePermissionTo([
            'access-dashboard',
            'view-course-analytics',
            'view-sales-analytics',
            // Catalogue (Lecture seule)
            'view-category',
            'view-subcategory',
            'view-tag',
            'view-skill',
            'view-language',
            'view-level',
            // Cours
            'view-course',
            'create-course',
            'update-course',
            'delete-course',
            'duplicate-course',
            'view-chapter',
            'create-chapter',
            'update-chapter',
            'delete-chapter',
            'reorder-chapter',
            'view-lesson',
            'create-lesson',
            'update-lesson',
            'delete-lesson',
            'reorder-lesson',
            'upload-video',
            'delete-video',
            'view-resource',
            'create-resource',
            'update-resource',
            'delete-resource',
            'view-live-class',
            'create-live-class',
            'update-live-class',
            'delete-live-class',
            'join-live-class',
            // Quiz & Devoirs
            'view-quiz',
            'create-quiz',
            'update-quiz',
            'delete-quiz',
            'grade-quiz',
            'view-question',
            'create-question',
            'update-question',
            'delete-question',
            'import-question',
            'view-assignment',
            'create-assignment',
            'update-assignment',
            'delete-assignment',
            'grade-assignment',
            // Inscriptions (de ses cours)
            'view-enrollment',
            'view-progress',
            // Certificats (Lecture)
            'view-certificate',
            'verify-certificate',
            // E-commerce (Ses gains)
            'view-payout',
            'request-payout',
            // Engagement
            'view-review',
            'view-comment',
            'create-comment',
            'view-forum-topic',
            'create-forum-topic',
            'view-forum-post',
            'create-forum-post',
            'view-message',
            'send-message',
            // Medias
            'view-media',
            'upload-media',
            'delete-media',
        ]);


        /**
         * RÔLE 4 : STUDENT (Étudiant / Apprenant)
         * Le client final. Consomme le contenu, passe des examens, télécharge des certificats.
         */
        $student = Role::firstOrCreate(['name' => 'student']);
        $student->givePermissionTo([
            'access-dashboard',
            // Catalogue (Lecture)
            'view-category',
            'view-subcategory',
            'view-tag',
            'view-skill',
            'view-language',
            'view-level',
            // Cours (Lecture)
            'view-course',
            'view-chapter',
            'view-lesson',
            'view-resource',
            'view-live-class',
            'join-live-class',
            // Quiz & Devoirs (Action)
            'view-quiz',
            'attempt-quiz',
            'view-question',
            'view-assignment',
            'submit-assignment',
            // Inscriptions & Suivi
            'view-enrollment',
            'view-progress',
            'view-wishlist',
            'add-to-wishlist',
            'remove-from-wishlist',
            // Certificats
            'view-certificate',
            'download-certificate',
            'verify-certificate',
            // Gamification
            'view-badge',
            'view-point',
            // Achats
            'view-payment',
            'view-invoice',
            'download-invoice',
            'request-refund',
            // Engagement
            'view-review',
            'create-review',
            'update-review',
            'delete-review',
            'view-comment',
            'create-comment',
            'update-comment',
            'delete-comment',
            // Forums
            'view-forum-category',
            'view-forum-topic',
            'create-forum-topic',
            'view-forum-post',
            'create-forum-post',
            'update-forum-post',
            // Support
            'view-ticket',
            'create-ticket',
            'update-ticket',
            'view-ticket-reply',
            'create-ticket-reply',
            'view-faq',
            // Messagerie
            'view-message',
            'send-message',
            'view-notification',
            'mark-notification-read',
        ]);


        /**
         * RÔLE 5 : MODERATOR (Modérateur de contenu et communauté)
         * Se concentre sur le bon déroulement des forums, la validation des commentaires, et l'assistance communautaire.
         */
        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'access-dashboard',
            'view-user',
            'ban-user',
            'unban-user',
            'view-course',
            // Engagement & Modération
            'view-review',
            'moderate-review',
            'delete-review',
            'view-comment',
            'moderate-comment',
            'delete-comment',
            'view-forum-category',
            'view-forum-topic',
            'pin-forum-topic',
            'close-forum-topic',
            'delete-forum-topic',
            'view-forum-post',
            'moderate-forum-post',
            'delete-forum-post',
            'view-message',
            'delete-message',
        ]);


        /**
         * RÔLE 6 : SUPPORT AGENT (Agent de support technique/client)
         * Gère exclusivement les tickets de support et les remboursements mineurs.
         */
        $supportAgent = Role::firstOrCreate(['name' => 'support-agent']);
        $supportAgent->givePermissionTo([
            'access-dashboard',
            'view-user',
            'view-enrollment',
            'view-payment',
            'view-invoice',
            // Remboursements
            'view-refund',
            'process-refund',
            'reject-refund',
            // Tickets
            'view-ticket',
            'update-ticket',
            'resolve-ticket',
            'view-ticket-reply',
            'create-ticket-reply',
            'view-faq',
            'create-faq',
            'update-faq',
            // Messages
            'view-message',
            'send-message',
        ]);


        /**
         * RÔLE 7 : FINANCE MANAGER (Gestionnaire financier)
         * Gère les revenus, paiements, factures, commissions des formateurs.
         */
        $financeManager = Role::firstOrCreate(['name' => 'finance-manager']);
        $financeManager->givePermissionTo([
            'access-dashboard',
            'view-sales-analytics',
            'view-course-analytics',
            'view-plan',
            'view-payment',
            'export-payment',
            'view-invoice',
            'download-invoice',
            'view-refund',
            'process-refund',
            'reject-refund',
            'view-coupon',
            'create-coupon',
            'update-coupon',
            'delete-coupon',
            'view-payout',
            'process-payout',
            'reject-payout',
            'view-affiliate',
            'approve-affiliate',
            'manage-affiliate-commission',
        ]);
         $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        // Le superadmin reçoit la totalité des permissions créées
        $superadmin->givePermissionTo(Permission::all());
    }
}
