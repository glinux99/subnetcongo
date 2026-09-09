<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Module;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ModuleCompletion;
use Illuminate\Support\Facades\DB;

class GradingService
{
    /**
     * Calculate the final certificate score for a student in a course.
     *
     * @param Course $course
     * @param int $userId
     * @return float|null Score out of 20, or null if not enough data
     */
    public function calculateFinalScore(Course $course, int $userId): ?float
    {
        $modules = $course->modules()->where('has_quiz', true)->get();
        $finalExam = $course->has_final_exam ? Exam::find($course->final_exam_id) : null;

        $totalWeight = 0;
        $weightedScore = 0.0;

        foreach ($modules as $module) {
            if ($module->quiz_weight <= 0) continue;

            $result = $this->getModuleBestResult($module, $userId);
            if ($result === null) continue;

            $scoreOutOf20 = ($result->score_percentage / 100) * 20;
            $weightedScore += $scoreOutOf20 * ($module->quiz_weight / 100);
            $totalWeight += $module->quiz_weight;
        }

        if ($finalExam && $course->final_exam_weight > 0) {
            $result = $this->getExamResult($finalExam, $userId);
            if ($result !== null) {
                $scoreOutOf20 = ($result->score_percentage / 100) * 20;
                $weightedScore += $scoreOutOf20 * ($course->final_exam_weight / 100);
                $totalWeight += $course->final_exam_weight;
            }
        }

        if ($totalWeight <= 0) return null;

        $normalizedScore = $weightedScore / ($totalWeight / 100);
        return round(min(20, max(0, $normalizedScore)), 2);
    }

    /**
     * Get the best exam result for a module quiz.
     */
    private function getModuleBestResult(Module $module, int $userId): ?ExamResult
    {
        if (!$module->quiz_id) return null;

        $exam = Exam::find($module->quiz_id);
        if (!$exam) return null;

        return $this->getExamResult($exam, $userId);
    }

    /**
     * Get the best exam result for a given exam and user.
     */
    private function getExamResult(Exam $exam, int $userId): ?ExamResult
    {
        return ExamResult::whereHas('attempt', function ($q) use ($exam, $userId) {
            $q->where('exam_id', $exam->id)
              ->where('user_id', $userId)
              ->where('status', 'Completed');
        })
        ->orderByDesc('score_percentage')
        ->first();
    }

    /**
     * Check if a student has passed the course.
     */
    public function hasPassedCourse(Course $course, int $userId): bool
    {
        $score = $this->calculateFinalScore($course, $userId);
        if ($score === null) return false;

        $passingScore = $course->passing_score_20 ?? 10;
        return $score >= $passingScore;
    }

    /**
     * Get the breakdown of scores for a course.
     */
    public function getScoreBreakdown(Course $course, int $userId): array
    {
        $breakdown = [
            'modules' => [],
            'final_exam' => null,
            'total_weight' => 0,
            'weighted_score' => 0.0,
            'final_score' => null,
            'passed' => false,
        ];

        $modules = $course->modules()->where('has_quiz', true)->get();
        $finalExam = $course->has_final_exam ? Exam::find($course->final_exam_id) : null;

        foreach ($modules as $module) {
            if ($module->quiz_weight <= 0) continue;

            $result = $this->getModuleBestResult($module, $userId);
            $scoreOutOf20 = $result ? ($result->score_percentage / 100) * 20 : null;

            $breakdown['modules'][] = [
                'module_id' => $module->id,
                'module_title' => $module->title,
                'quiz_id' => $module->quiz_id,
                'weight' => $module->quiz_weight,
                'score_out_of_20' => $scoreOutOf20,
                'score_percentage' => $result ? $result->score_percentage : null,
                'passed' => $scoreOutOf20 !== null && $scoreOutOf20 >= ($module->quiz_passing_score ?? 10),
            ];

            if ($scoreOutOf20 !== null) {
                $breakdown['weighted_score'] += $scoreOutOf20 * ($module->quiz_weight / 100);
                $breakdown['total_weight'] += $module->quiz_weight;
            }
        }

        if ($finalExam && $course->final_exam_weight > 0) {
            $result = $this->getExamResult($finalExam, $userId);
            $scoreOutOf20 = $result ? ($result->score_percentage / 100) * 20 : null;

            $breakdown['final_exam'] = [
                'exam_id' => $finalExam->id,
                'exam_title' => $finalExam->title,
                'weight' => $course->final_exam_weight,
                'score_out_of_20' => $scoreOutOf20,
                'score_percentage' => $result ? $result->score_percentage : null,
                'passed' => $scoreOutOf20 !== null && $scoreOutOf20 >= ($course->passing_score_20 ?? 10),
            ];

            if ($scoreOutOf20 !== null) {
                $breakdown['weighted_score'] += $scoreOutOf20 * ($course->final_exam_weight / 100);
                $breakdown['total_weight'] += $course->final_exam_weight;
            }
        }

        if ($breakdown['total_weight'] > 0) {
            $normalizedScore = $breakdown['weighted_score'] / ($breakdown['total_weight'] / 100);
            $breakdown['final_score'] = round(min(20, max(0, $normalizedScore)), 2);
            $breakdown['passed'] = $breakdown['final_score'] >= ($course->passing_score_20 ?? 10);
        }

        return $breakdown;
    }
}
