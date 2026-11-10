<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GradingController extends Controller
{
    /**
     * Affiche l'interface de correction pour une soumission spécifique.
     *
     * @param  \App\Models\Submission  $submission
     * @return \Inertia\Response
     */
    public function edit(Submission $submission)
    {
        // Charger les relations nécessaires pour l'affichage
        $submission->load([
            'student:id,name,avatar', // Assumant que le modèle User est utilisé pour l'étudiant
            'assignment:id,title,max_points',
            'answers' => function ($query) {
                // Charger les questions qui nécessitent une correction manuelle
                $query->with('question:id,question_text,points')
                      ->whereHas('question', function ($q) {
                          // Exemple: ne charger que les réponses ouvertes/courtes
                          $q->where('type', 'short_answer');
                      });
            }
        ]);

        return Inertia::render('Workspace', [
            'submission' => $submission,
            'answers' => $submission->answers,
        ]);
    }

    /**
     * Met à jour la note et le feedback pour une soumission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Submission  $submission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'total_points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
            'question_scores' => 'nullable|array',
            'question_scores.*' => 'nullable|numeric|min:0',
            'publish' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated, $submission) {
            // 1. Mettre à jour les scores des questions individuelles si fournis
            if (!empty($validated['question_scores'])) {
                foreach ($validated['question_scores'] as $questionId => $score) {
                    $submission->answers()
                        ->where('question_id', $questionId)
                        ->update(['score' => $score]);
                }
            }

            // 2. Mettre à jour la soumission principale
            $submission->grade = $validated['total_points'];
            $submission->feedback_text = $validated['feedback'];

            // 3. Mettre à jour le statut de la soumission
            if ($validated['publish']) {
                $submission->status = 'graded'; // Corrigé et publié
                $submission->graded_at = now();
            } elseif ($submission->status === 'pending') {
                $submission->status = 'in_progress'; // Marqué comme 'en cours de correction'
            }

            $submission->save();
        });

        // Email à l'étudiant quand la correction est publiée
        if ($validated['publish']) {
            try {
                $submission->loadMissing(['student', 'assignment']);
                $student = $submission->student;
                if ($student) {
                    $assignment = $submission->assignment;
                    $maxPoints = $assignment?->max_points ?? 0;
                    app(\App\Services\Email\EmailTemplateService::class)->sendTemplatedEmail(
                        'assignment_graded',
                        $student,
                        [
                            'assignment_title' => $assignment?->title ?? 'Devoir',
                            'score'            => $submission->grade ?? 0,
                            'max_points'       => $maxPoints,
                            'feedback'         => $submission->feedback_text ?: 'Aucun commentaire.',
                            'assignment_url'   => url('/student/assignments/' . ($submission->assignment_id ?? $submission->id)),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                \Log::warning('Assignment graded email failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Correction enregistrée avec succès.');
    }
}
