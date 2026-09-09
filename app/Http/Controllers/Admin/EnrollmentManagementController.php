<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Events\EnrollmentConfirmed;
use App\Notifications\EnrollmentApprovedNotification;
use App\Notifications\EnrollmentRejectedNotification;
use App\Notifications\EnrollmentRevokedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EnrollmentManagementController extends Controller
{
    /**
     * Liste des inscriptions (admin/finance) avec filtres.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = $request->input('search');
        $courseId = $request->input('course_id');

        $query = Enrollment::with(['user:id,name,email,avatar', 'course:id,title,thumbnail_path,price,instructor_id', 'order:id,order_number,status,amount', 'reviewer:id,name'])
            ->latest('enrollments.created_at');

        if (in_array($status, ['pending', 'active', 'suspended', 'rejected', 'completed', 'cancelled', 'all'], true)) {
            if ($status !== 'all') {
                $query->where('enrollments.status', $status);
            }
        } else {
            $query->where('enrollments.status', 'pending');
        }

        if ($courseId) {
            $query->where('enrollments.course_id', $courseId);
        }
        if ($search) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('course', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $enrollments = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => Enrollment::where('status', Enrollment::STATUS_PENDING)->count(),
            'active' => Enrollment::where('status', Enrollment::STATUS_ACTIVE)->count(),
            'suspended' => Enrollment::where('status', Enrollment::STATUS_SUSPENDED)->count(),
            'rejected' => Enrollment::where('status', Enrollment::STATUS_REJECTED)->count(),
            'completed' => Enrollment::where('status', Enrollment::STATUS_COMPLETED)->count(),
            'today' => Enrollment::whereDate('created_at', today())->count(),
        ];

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'course_id' => $courseId,
            ],
            'courses' => Course::select('id', 'title')->orderBy('title')->limit(200)->get(),
        ]);
    }

    /**
     * Détail d'une inscription.
     */
    public function show(Enrollment $enrollment)
    {
        $enrollment->load(['user', 'course.instructor:id,name', 'order', 'reviewer:id,name', 'lastLesson']);

        return Inertia::render('Admin/Enrollments/Show', [
            'enrollment' => $enrollment,
        ]);
    }

    /**
     * Approuver une inscription en attente.
     */
    public function approve(Request $request, Enrollment $enrollment)
    {
        if (! in_array($enrollment->status, [Enrollment::STATUS_PENDING, Enrollment::STATUS_SUSPENDED, Enrollment::STATUS_REJECTED], true)) {
            return back()->with('error', 'Cette inscription ne peut pas être approuvée dans son état actuel.');
        }

        $data = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $enrollment->update([
            'status' => Enrollment::STATUS_ACTIVE,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'enrolled_at' => $enrollment->enrolled_at ?? now(),
            'admin_notes' => $data['admin_notes'] ?? $enrollment->admin_notes,
            'revoked_at' => null,
            'revoked_reason' => null,
        ]);

        try {
            $enrollment->user?->notify(new EnrollmentApprovedNotification($enrollment));
        } catch (\Throwable $e) {
            \Log::warning('Notify enrollment approved failed: ' . $e->getMessage());
        }

        // Dispatch event for email template (enrollment_confirmed)
        try {
            EnrollmentConfirmed::dispatch($enrollment);
        } catch (\Throwable $e) {
            \Log::warning('EnrollmentConfirmed event dispatch failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Inscription approuvee. L\'etudiant a ete notifie.');
    }

    /**
     * Refuser une inscription.
     */
    public function reject(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'admin_notes' => 'required|string|min:5|max:2000',
        ]);

        $enrollment->update([
            'status' => Enrollment::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $data['admin_notes'],
        ]);

        try {
            $enrollment->user?->notify(new EnrollmentRejectedNotification($enrollment, $data['admin_notes']));
        } catch (\Throwable $e) {
            \Log::warning('Notify enrollment rejected failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Inscription refusée.');
    }

    /**
     * Révoquer (suspendre) une inscription active.
     */
    public function revoke(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'revoked_reason' => 'required|string|min:5|max:500',
        ]);

        $enrollment->update([
            'status' => Enrollment::STATUS_SUSPENDED,
            'revoked_at' => now(),
            'revoked_reason' => $data['revoked_reason'],
            'reviewed_by' => Auth::id(),
        ]);

        try {
            $enrollment->user?->notify(new EnrollmentRevokedNotification($enrollment, $data['revoked_reason']));
        } catch (\Throwable $e) {
            \Log::warning('Notify enrollment revoked failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Accès de l\'étudiant révoqué.');
    }

    /**
     * Réactiver une inscription suspendue / refusée.
     */
    public function reactivate(Enrollment $enrollment)
    {
        if (! in_array($enrollment->status, [Enrollment::STATUS_SUSPENDED, Enrollment::STATUS_REJECTED], true)) {
            return back()->with('error', 'Cette inscription n\'est pas suspendue ou refusée.');
        }

        $enrollment->update([
            'status' => Enrollment::STATUS_ACTIVE,
            'revoked_at' => null,
            'revoked_reason' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Inscription réactivée.');
    }

    /**
     * Supprimer définitivement une inscription.
     */
    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();
        return back()->with('success', 'Inscription supprimée définitivement.');
    }

    /**
     * Actions en masse (approuver / refuser / supprimer).
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:enrollments,id',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $count = 0;
        DB::transaction(function () use ($data, &$count) {
            $enrollments = Enrollment::whereIn('id', $data['ids'])->get();
            foreach ($enrollments as $enrollment) {
                match ($data['action']) {
                    'approve' => $enrollment->update([
                        'status' => Enrollment::STATUS_ACTIVE,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'enrolled_at' => $enrollment->enrolled_at ?? now(),
                        'admin_notes' => $data['admin_notes'] ?? null,
                    ]),
                    'reject' => $enrollment->update([
                        'status' => Enrollment::STATUS_REJECTED,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'admin_notes' => $data['admin_notes'] ?? 'Refus massif',
                    ]),
                    'delete' => $enrollment->delete(),
                };
                $count++;
            }
        });

        return back()->with('success', "Action '{$data['action']}' appliquée à {$count} inscription(s).");
    }
}
