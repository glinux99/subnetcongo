<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Lancer les Seeders statiques (configurations de base)
        $this->call([
            CategorySeeder::class,
            RoleSeeder::class,      // Gère les rôles et permissions
            UserSeeder::class,      // Crée l'admin, les profs et les élèves
            SettingSeeder::class,   // Paramètres du site
            LanguageSeeder::class,  // Langues
            PaymentGatewaySettingSeeder::class, // Stripe/PayPal/Orange Money/M-Pesa/Airtel Money
            LandingContentSeeder::class,        // Slides / technologies / stats de la landing
            WebSettingsSeeder::class,           // Paramètres web (identité, contact, social, about, hero, legal, privacy)
            AffiliateSeeder::class,             // Affiliés et parrainages
            EmailTemplateSeeder::class,         // Templates d'emails
        ]);

        $this->command->info('✅ Rôles, Utilisateurs et Configurations créés !');

        // 2. Créer les templates de certificats
        $this->call([
            CertificateTemplateSeeder::class,
        ]);

        $this->command->info('✅ Templates de certificats créés !');

        // 3. Créer les cours professionnels
        $this->call([
            CourseSeeder::class,
            SubnetCongoCoursesSeeder::class,
        ]);

        $this->command->info('✅ Cours professionnels créés !');

        // 3.5. Réalisations SubNet Congo (photos réelles extraites du PDF)
        $this->call([
            RealizationsSeeder::class,
        ]);

        $this->command->info('✅ Réalisations créées !');

        // 3.6. Informations légales SubNet Congo (RCCM, N° d'impôt, CNSS, siège)
        $this->call([
            LegalInfoSeeder::class,
        ]);

        $this->command->info('✅ Informations légales appliquées !');

        // 4. Créer les modules et leçons pour chaque cours
        $this->call([
            ModuleLessonSeeder::class,
        ]);

        $this->command->info('✅ Modules et leçons créés !');

        // 5. Créer les examens et questions pour les cours payants
        $this->call([
            ExamSeeder::class,
        ]);

        $this->command->info('✅ Examens et questions créés !');

        // 6. Inscrire les Étudiants aux cours
        $this->call([
            EnrollmentSeeder::class,
        ]);

        $this->command->info('✅ Inscriptions des Étudiants créées !');

        $this->command->newLine();
        $this->command->info('🎉 Seeding terminé avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->info('  - 7 instructeurs');
        $this->command->info('  - 20 étudiants');
        $this->command->info('  - 28 cours (gratuits et payants)');
        $this->command->info('  - Modules et leçons pour chaque cours');
        $this->command->info('  - Examens avec questions');
        $this->command->info('  - 5 templates de certificats');
        $this->command->info('  - Inscriptions des Étudiants');
        $this->command->info('  - Réalisations extraites du PDF');
        $this->command->info('  - Informations légales SubNet Congo');
    }
}
