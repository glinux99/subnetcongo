<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\UserProgress;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::role('student')->get();
        $courses = Course::published()->get();

        if ($students->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('Aucun étudiant ou cours trouvé. Exécutez d\'abord UserSeeder et CourseSeeder.');
            return;
        }

        $enrollmentCount = 0;

        foreach ($students as $student) {
            // Chaque étudiant s'inscrit à entre 3 et 8 cours aléatoires
            $coursesToEnroll = $courses->random(rand(3, 8));

            foreach ($coursesToEnroll as $course) {
                // Vérifier si déjà inscrit
                $existingEnrollment = Enrollment::where('user_id', $student->id)
                    ->where('course_id', $course->id)
                    ->first();

                if ($existingEnrollment) {
                    continue;
                }

                // Date d'inscription aléatoire dans les 3 derniers mois
                $enrolledAt = Carbon::now()->subDays(rand(1, 90));

                $enrollment = Enrollment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'enrolled_at' => $enrolledAt,
                    'progress_percent' => rand(0, 100),
                    'status' => 'active',
                ]);

                // Simuler la progression pour certains cours
                if ($enrollment->progress_percent > 0) {
                    $this->simulateProgress($student, $course, $enrollment->progress_percent);
                }

                // Marquer comme complété pour certains cours avec 100%
                if ($enrollment->progress_percent == 100) {
                    $enrollment->update([
                        'completed_at' => $enrolledAt->copy()->addDays(rand(7, 60)),
                        'status' => 'completed',
                    ]);
                }

                $enrollmentCount++;
            }
        }

        $this->command->info($enrollmentCount . ' inscriptions créées avec succès !');
    }

    private function simulateProgress(User $student, Course $course, int $progressPercent): void
    {
        $modules = $course->modules;
        $totalLessons = $course->lessons()->count();

        if ($totalLessons === 0) {
            return;
        }

        // Récupérer l'enrollment
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return;
        }

        // Calculer combien de leçons devraient être complétées
        $lessonsToComplete = (int) ceil(($progressPercent / 100) * $totalLessons);
        $completedLessons = $course->lessons()->take($lessonsToComplete)->get();

        foreach ($completedLessons as $lesson) {
            UserProgress::firstOrCreate([
                'user_id' => $student->id,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'progress_percent' => 100,
                'completed_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
