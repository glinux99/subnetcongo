<?php

namespace App\Http\Controllers;

use App\Models\User; // ou App\Models\Student
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentReportController extends Controller
{
    /**
     * Affiche la liste des étudiants pour la consultation des rapports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = User::query()->whereHas('roles', function ($q) {
            // Assumes a 'student' role exists. Adjust if you use a different role name.
            $q->where('name', 'student');
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->select('id', 'name', 'email', 'avatar') // Select only necessary fields
            ->withCount(['submissions'])
            ->latest()
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('StudentReports', [
            'students' => $students,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    /**
     * Affiche le rapport de performance d'un étudiant.
     *
     * @param  \App\Models\User  $student
     * @return \Inertia\Response
     */
    public function show(User $student)
    {
        // --- 1. Statistiques Globales ---
        $submissionsQuery = Submission::where('user_id', $student->id);
        $gradedSubmissionsQuery = (clone $submissionsQuery)->whereNotNull('grade');

        $averageScore = (clone $gradedSubmissionsQuery)->avg('grade');
        $completedExams = (clone $gradedSubmissionsQuery)->distinct('assignment_id')->count();
        $totalAttempts = (clone $submissionsQuery)->count();

        // Taux de réussite (supposant un seuil de 50% sur un total de 100)
        // Pour un calcul plus précis, il faudrait joindre l'assignment pour avoir max_points.
        $successfulAttempts = (clone $gradedSubmissionsQuery)->where('grade', '>=', 50)->count();
        $totalGradedAttempts = (clone $gradedSubmissionsQuery)->count();
        $successRate = $totalGradedAttempts > 0 ? ($successfulAttempts / $totalGradedAttempts) * 100 : 0;

        $globalStats = [
            'average_score' => round($averageScore, 1),
            'success_rate' => round($successRate),
            'completed_exams' => $completedExams,
            'total_attempts' => $totalAttempts,
        ];

        // --- 2. Performance au fil du temps ---
        $performanceData = Submission::where('user_id', $student->id)
            ->whereNotNull('graded_at')
            ->orderBy('graded_at', 'asc')
            ->select('grade', 'graded_at')
            ->get()
            ->map(function ($submission) {
                return [
                    'score' => $submission->grade,
                    'date' => Carbon::parse($submission->graded_at)->format('d M Y'),
                ];
            });

        $performanceOverTime = [
            'labels' => $performanceData->pluck('date'),
            'scores' => $performanceData->pluck('score'),
        ];

        // --- 3. Données par cours ---
        // On récupère les cours auxquels l'étudiant est inscrit (relation 'courses' sur le modèle User)
        $courses = $student->courses()->with(['assignments'])->get();

        $coursesData = $courses->map(function ($course) use ($student) {
            $examsData = $course->assignments->map(function ($exam) use ($student) {
                // Pour chaque examen, on cherche la meilleure soumission de l'étudiant
                $bestSubmission = Submission::where('user_id', $student->id)
                    ->where('assignment_id', $exam->id)
                    ->whereNotNull('grade')
                    ->orderBy('grade', 'desc')
                    ->first();

                if (!$bestSubmission) {
                    return null; // L'étudiant n'a pas encore passé cet examen
                }

                // Supposons un seuil de réussite de 50
                $status = $bestSubmission->grade >= 50 ? 'Réussi' : 'Échoué';

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'status' => $status,
                    'score' => $bestSubmission->grade,
                    'attempted_at' => Carbon::parse($bestSubmission->submitted_at)->format('d/m/Y'),
                ];
            })->filter(); // ->filter() pour enlever les 'null' (examens non passés)

            return [
                'id' => $course->id,
                'title' => $course->title,
                'exams' => $examsData,
            ];
        });

        return Inertia::render('StudentReport', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'avatar' => $student->avatar, // ou profile_photo_url
            ],
            'globalStats' => $globalStats,
            'coursesData' => $coursesData,
            'performanceOverTime' => $performanceOverTime,
        ]);
    }
}
