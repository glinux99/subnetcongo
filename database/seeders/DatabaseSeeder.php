<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Lancer les Seeders statiques
        $this->call([
            CategorySeeder::class,
            RoleSeeder::class,      // Gère les rôles et permissions (créé au début)
            UserSeeder::class,      // Crée l'admin, les profs et les élèves
            SettingSeeder::class,   // Paramètres du site
            LanguageSeeder::class,  // Langues
            PaymentGatewaySettingSeeder::class, // Stripe/PayPal/Orange Money/M-Pesa/Airtel Money
        ]);

        $this->command->info('Rôles, Utilisateurs et Configurations créés !');

        // 2. Récupérer les formateurs pour leur assigner des cours
        $instructors = User::role('instructor')->get();

        if ($instructors->count() > 0) {
            $this->command->info('Génération des cours en cours... (ça peut prendre quelques secondes)');

            // Pour chaque formateur, on crée entre 2 et 4 cours
            foreach ($instructors as $instructor) {

                Course::factory(rand(1, 2))->create([
                    'instructor_id' => $instructor->id
                ])->each(function ($course) {

                    // Pour chaque cours, on crée entre 3 et 5 modules
                    $modules = Module::factory(rand(3, 5))->create([
                        'course_id' => $course->id
                    ]);

                    // Pour chaque module, on crée entre 2 et 6 leçons
                    foreach ($modules as $index => $module) {
                        Lesson::factory(rand(1, 2))->create([
                            'module_id' => $module->id,
                            'order' => $index + 1 // Assure un ordre logique
                        ]);
                    }
                });
            }
            $this->command->info('Cours, Modules et Leçons générés avec succès ! 🚀');
        }
    }
}
