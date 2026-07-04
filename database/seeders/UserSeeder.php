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
            ['email' => 'admin@subnetcongo.net'],['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $superAdmin->assignRole('superadmin'); // Assigne le rôle Spatie

        // 2. Création de quelques Formateurs (Instructors)
        for ($i = 1; $i <= 5; $i++) {
            $instructor = User::firstOrCreate(
                ['email' => "professeur{$i}@subnetcongo.net"],['name' => "Professeur {$i}", 'password' => Hash::make('password')]
            );
            $instructor->assignRole('instructor');
        }

        // 3. Création d'étudiants (Students)
        for ($i = 1; $i <= 10; $i++) {
            $student = User::firstOrCreate(
                ['email' => "etudiant{$i}@subnetcongo.net"],['name' => "Étudiant {$i}", 'password' => Hash::make('password')]
            );
            $student->assignRole('student');
        }
    }
}
