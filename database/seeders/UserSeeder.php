<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Création du SuperAdmin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@subnetcongo.net'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $superAdmin->assignRole('superadmin');

        // 2. Création de Formateurs (Instructors) avec des noms réalistes
        $instructors = [
            ['name' => 'Jean-Pierre Mbemba', 'email' => 'jp.mbemba@subnetcongo.net', 'specialty' => 'Réseaux Cisco'],
            ['name' => 'Marie-Claire N\'Gouaka', 'email' => 'mc.ngouaka@subnetcongo.net', 'specialty' => 'Cybersécurité'],
            ['name' => 'François Makosso', 'email' => 'f.makosso@subnetcongo.net', 'specialty' => 'Télécoms Satellites'],
            ['name' => 'Aminata Diallo', 'email' => 'a.diallo@subnetcongo.net', 'specialty' => 'Cloud & DevOps'],
            ['name' => 'Pierre Massamba', 'email' => 'p.massamba@subnetcongo.net', 'specialty' => 'Développement Web'],
            ['name' => 'Claudette Mouamba', 'email' => 'c.mouamba@subnetcongo.net', 'specialty' => 'Linux & Open Source'],
            ['name' => 'Antoine Ondongo', 'email' => 'a.ondongo@subnetcongo.net', 'specialty' => 'Infrastructure'],
            ['name' => 'Céline Ngouabi', 'email' => 'c.ngouabi@subnetcongo.net', 'specialty' => 'Sécurité Réseau'],
        ];

        foreach ($instructors as $instructorData) {
            $instructor = User::firstOrCreate(
                ['email' => $instructorData['email']],
                [
                    'name' => $instructorData['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $instructor->assignRole('instructor');
        }

        // 3. Création d'étudiants (Students) avec des noms réalistes
        $students = [
            ['name' => 'Emmanuel Boungou', 'email' => 'e.boungou@subnetcongo.net'],
            ['name' => 'Sophie Ndinga', 'email' => 's.ndinga@subnetcongo.net'],
            ['name' => 'Christian Moundi', 'email' => 'c.moundi@subnetcongo.net'],
            ['name' => 'Patricia Louya', 'email' => 'p.louya@subnetcongo.net'],
            ['name' => 'Roger Tati', 'email' => 'r.tati@subnetcongo.net'],
            ['name' => 'Christelle Samba', 'email' => 'c.samba@subnetcongo.net'],
            ['name' => 'Denis Sassou', 'email' => 'd.sassou@subnetcongo.net'],
            ['name' => 'Anne-Marie Malonga', 'email' => 'a.malonga@subnetcongo.net'],
            ['name' => 'Jean-Michel Moka', 'email' => 'jm.moka@subnetcongo.net'],
            ['name' => 'Béatrice Lissouba', 'email' => 'b.lissouba@subnetcongo.net'],
            ['name' => 'Paul Nguesso', 'email' => 'p.nguesso@subnetcongo.net'],
            ['name' => 'Marie-Thérèse Yongo', 'email' => 'mt.yongo@subnetcongo.net'],
            ['name' => 'Charles Bianou', 'email' => 'c.bianou@subnetcongo.net'],
            ['name' => 'Françoise Moungalla', 'email' => 'f.moungalla@subnetcongo.net'],
            ['name' => 'André Goma', 'email' => 'a.goma@subnetcongo.net'],
            ['name' => 'Joséphine Nzita', 'email' => 'j.nzita@subnetcongo.net'],
            ['name' => 'Michel Ombanda', 'email' => 'm.ombanda@subnetcongo.net'],
            ['name' => 'Lucie Itoua', 'email' => 'l.itoua@subnetcongo.net'],
            ['name' => 'Bruno Mbou', 'email' => 'b.mbou@subnetcongo.net'],
            ['name' => 'Claire Ossibi', 'email' => 'c.ossibi@subnetcongo.net'],
        ];

        foreach ($students as $studentData) {
            $student = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $student->assignRole('student');
        }

        $this->command->info('8 instructeurs et 20 étudiants créés avec succès !');
    }
}
