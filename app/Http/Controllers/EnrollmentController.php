<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of enrollments.
     */
    public function index(Request $request)
    {
        $query = Enrollment::with(['user', 'course'])
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->course_id, fn($q, $id) => $q->where('course_id', $id))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc');

        $enrollments = $query->paginate($request->input('per_page', 15));

        return Inertia::render('Enrollments', [
            'enrollments' => $enrollments,
            'stats' => Enrollment::getStatistics(),
        ]);
    }

    /**
     * Store a newly created enrollment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'status' => 'nullable|in:pending,active,completed,cancelled,expired',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Check if already enrolled
        $existing = Enrollment::where('user_id', $validated['user_id'])
            ->where('course_id', $validated['course_id'])
            ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_PENDING])
            ->first();

        if ($existing) {
            return back()->with('error', 'User is already enrolled in this course.');
        }

        $enrollment = Enrollment::create([
            'user_id' => $validated['user_id'],
            'course_id' => $validated['course_id'],
            'status' => $validated['status'] ?? Enrollment::STATUS_ACTIVE,
            'progress_percent' => $validated['progress_percent'] ?? 0,
            'enrolled_at' => now(),
        ]);

        return back()->with('success', 'Enrollment created successfully.');
    }

    /**
     * Update the specified enrollment.
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,active,completed,cancelled,expired',
            'progress_percent' => 'nullable|numeric|min:0|max:100',
            'avg_score' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $enrollment->update($validated);

        // If progress reached 100%, mark as completed
        if ($enrollment->progress_percent >= 100 && $enrollment->status !== Enrollment::STATUS_COMPLETED) {
            $enrollment->markAsComplete();
        }

        return back()->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Remove the specified enrollment.
     */
    public function destroy(Enrollment $enrollment)
    {
        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);

        return back()->with('success', 'Enrollment cancelled successfully.');
    }

    /**
     * Enroll the current user in a course.
     */
    public function enrollUser(Course $course)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to enroll in courses.');
        }

        // Check if already enrolled
        if ($user->isEnrolledIn($course)) {
            return back()->with('error', 'You are already enrolled in this course.');
        }

        DB::beginTransaction();
        try {
            // Create enrollment
            $enrollment = $user->enrollIn($course);

            // Initialize progress for each lesson
            $this->initializeProgress($course, $user);

            DB::commit();

            return back()->with('success', 'Successfully enrolled in ' . $course->title);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to enroll in course. Please try again.');
        }
    }

    /**
     * Get user's enrolled courses.
     */
    public function myCourses(Request $request)
    {
        $user = Auth::user();

        $query = $user->enrolledCourses()
            ->with(['instructor', 'category'])
            ->withPivot('progress_percent', 'enrolled_at', 'completed_at')
            ->when($request->input('status'), fn($q, $status) => $q->wherePivot('status', $status))
            ->orderBy('pivot_enrolled_at', 'desc');

        $courses = $query->paginate($request->input('per_page', 12));

        return Inertia::render('MyCourses', [
            'courses' => $courses,
        ]);
    }

    /**
     * Get user's progress for a specific course.
     */
    public function getProgress(Course $course)
    {
        $user = Auth::user();

        if (!$user->isEnrolledIn($course)) {
            return response()->json(['error' => 'Not enrolled'], 403);
        }

        $enrollment = $user->getProgressFor($course);
        $progress = $user->progress()
            ->where('course_id', $course->id)
            ->with('lesson')
            ->get();

        return response()->json([
            'enrollment' => $enrollment,
            'progress' => $progress,
            'completion_rate' => $enrollment->progress_percent,
        ]);
    }

    /**
     * Update progress for a lesson.
     */
    public function updateProgress(Request $request, Course $course)
    {
        $user = Auth::user();

        if (!$user->isEnrolledIn($course)) {
            return response()->json(['error' => 'Not enrolled'], 403);
        }

        $validated = $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'progress_percent' => 'required|numeric|min:0|max:100',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update or create lesson progress
            $userProgress = UserProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $validated['lesson_id'],
                    'course_id' => $course->id,
                ],
                [
                    'progress_percent' => $validated['progress_percent'],
                    'time_spent_seconds' => $validated['time_spent'] ?? 0,
                    'last_accessed_at' => now(),
                ]
            );

            // Recalculate overall course progress
            $overallProgress = $this->calculateOverallProgress($course, $user);

            // Update enrollment progress
            $enrollment = $user->getProgressFor($course);
            $enrollment->updateProgress($overallProgress);

            DB::commit();

            return response()->json([
                'success' => true,
                'progress' => $overallProgress,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update progress'], 500);
        }
    }

    /**
     * Initialize progress records for all lessons in a course.
     */
    protected function initializeProgress(Course $course, User $user)
    {
        $lessons = $course->lessons;

        foreach ($lessons as $lesson) {
            UserProgress::firstOrCreate([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
            ], [
                'progress_percent' => 0,
            ]);
        }
    }

    /**
     * Calculate overall progress for a user in a course.
     */
    protected function calculateOverallProgress(Course $course, User $user): int
    {
        $totalLessons = $course->lessons()->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = UserProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('progress_percent', 100)
            ->count();

        return (int) round(($completedLessons / $totalLessons) * 100);
    }
}
