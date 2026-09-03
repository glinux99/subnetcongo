<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentEnrollmentController extends Controller
{
    /**
     * Liste des inscriptions de l'étudiant (validées, en attente, refusées, révoquées, terminées).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'all');

        $query = Enrollment::with(['course.instructor:id,name,avatar', 'order:id,order_number,status,amount,paid_at', 'reviewer:id,name'])
            ->where('user_id', $user->id)
            ->latest('enrollments.created_at');

        if (in_array($status, ['pending', 'active', 'suspended', 'rejected', 'completed', 'cancelled'], true)) {
            $query->where('enrollments.status', $status);
        }

        $enrollments = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => Enrollment::where('user_id', $user->id)->count(),
            'active' => Enrollment::where('user_id', $user->id)->where('status', Enrollment::STATUS_ACTIVE)->count(),
            'pending' => Enrollment::where('user_id', $user->id)->where('status', Enrollment::STATUS_PENDING)->count(),
            'rejected' => Enrollment::where('user_id', $user->id)->where('status', Enrollment::STATUS_REJECTED)->count(),
            'suspended' => Enrollment::where('user_id', $user->id)->where('status', Enrollment::STATUS_SUSPENDED)->count(),
            'completed' => Enrollment::where('user_id', $user->id)->where('status', Enrollment::STATUS_COMPLETED)->count(),
        ];

        return Inertia::render('Student/MyEnrollments', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'filters' => ['status' => $status],
            'adminContact' => [
                'email' => config('app.support_email', 'support@subnetcongo.com'),
                'phone' => config('app.support_phone', '+243 000 000 000'),
            ],
        ]);
    }

    /**
     * Annuler une demande d'inscription en attente.
     */
    public function cancel(Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === Auth::id(), 403);
        if ($enrollment->status !== Enrollment::STATUS_PENDING) {
            return back()->with('error', 'Seules les inscriptions en attente peuvent être annulées.');
        }
        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);
        return back()->with('success', 'Demande d\'inscription annulée.');
    }
}
