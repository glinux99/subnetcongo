<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cohort;
use App\Models\User;
use App\Models\Course;
use App\Models\AuditLog;
use App\Models\Document;        // Modèle pour les documents
use App\Models\LiveSession;     // Modèle pour les sessions live
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class CohortController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:manage-cohorts');
    }

    /**
     * Tableau de bord principal des cohortes (index).
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Cohort::with([
                'instructor',
                'assistants',
                'courses',
                'prerequisites',
                'documents',
                'liveSessions',
                'auditLogs.user',
                'students' => function ($q) {
                    $q->with('profile')->select('users.id', 'users.name', 'users.email', 'users.created_at', 'users.updated_at');
                }
            ])
            ->withCount('students')
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->instructor_id, fn($q, $v) => $q->where('instructor_id', $v))
            ->latest();

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->getCollection()->transform(function ($cohort) {
            $avgCompletion = 0;
            $avgScore = 0;
            if ($cohort->students_count > 0) {
                $avgCompletion = round($cohort->students->avg('profile.progress') ?? 0);
                $avgScore = round($cohort->students->avg('profile.score') ?? 0);
            }

            return [
                'id'              => $cohort->id,
                'name'            => $cohort->name,
                'description'     => $cohort->description,
                'status'          => $cohort->status,
                'start_date'      => $cohort->start_date,
                'end_date'        => $cohort->end_date,
                'max_students'    => $cohort->max_students,
                'is_private'      => (bool) $cohort->is_private,
                'auto_enrollment' => (bool) $cohort->auto_enrollment,
                'pass_percentage' => $cohort->pass_percentage ?? 70,
                'instructor_id'   => $cohort->instructor_id,
                'instructor'      => $cohort->instructor ? [
                    'id'          => $cohort->instructor->id,
                    'name'        => $cohort->instructor->name,
                    'avatar_path' => optional($cohort->instructor->profile)->avatar_path
                                     ? asset('storage/' . $cohort->instructor->profile->avatar_path) : null,
                ] : null,
                'assistant_ids'   => $cohort->assistants->pluck('id')->toArray(),
                'members_count'   => $cohort->students_count,
                'avg_completion'  => $avgCompletion,
                'avg_score'       => $avgScore,
                'members'         => $cohort->students->map(fn($s) => [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'email'       => $s->email,
                    'avatar_path' => optional($s->profile)->avatar_path
                                     ? asset('storage/' . $s->profile->avatar_path) : null,
                    'pivot'       => [
                        'progress'    => $s->profile->progress ?? 0,
                        'enrolled_at' => $s->updated_at,
                    ]
                ]),
                'assigned_courses' => $cohort->courses->map(fn($c) => [
                    'id'             => $c->id,
                    'title'          => $c->title,
                    'level'          => $c->level ?? 'Général',
                    'thumbnail_path' => $c->thumbnail_path ? asset('storage/' . $c->thumbnail_path) : null,
                    'duration'       => $c->duration_minutes ?? 0,
                ]),
                'prerequisites' => $cohort->prerequisites->pluck('id')->toArray(),
                'documents' => $cohort->documents->map(fn($d) => [
                    'id'   => $d->id,
                    'name' => $d->name,
                    'size' => $d->size,
                    'type' => $d->mime_type,
                ]),
                'live_sessions' => $cohort->liveSessions->map(fn($s) => [
                    'id'            => $s->id,
                    'title'         => $s->title,
                    'start_at'  => $s->start_at,
                    'meeting_url'   => $s->meeting_url,
                    'status'        => $s->status ?? 'à venir',
                    'instructor_id' => $s->instructor_id,
                    'course_id'     => $s->course_id,
                    'duration'      => $s->duration,
                ]),
                'audit_logs' => $cohort->auditLogs->map(fn($log) => [
                    'action' => $log->event,
                    'date'   => $log->created_at->format('d/m/Y H:i'),
                    'user'   => $log->user->name ?? 'Système',
                    'icon'   => 'pi pi-check',
                    'color'  => '#4f46e5',
                ]),
            ];
        });

        $stats = [
            'total_cohorts'             => Cohort::count(),
            'total_enrolled_students'   => DB::table('cohort_user')->distinct('user_id')->count('user_id'),
            'active_courses_in_cohorts' => DB::table('cohort_course')->distinct('course_id')->count('course_id'),
            'avg_cohort_completion'     => round(User::whereHas('cohorts')
                                            ->join('profiles', 'users.id', '=', 'profiles.user_id')
                                            ->avg('profiles.progress') ?? 0)
        ];

        $allStudents = User::role('student')
            ->with('profile')
            ->select('id', 'name', 'email')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'email'       => $u->email,
                'avatar_path' => optional($u->profile)->avatar_path
                                 ? asset('storage/' . $u->profile->avatar_path) : null,
            ]);

        return Inertia::render('Users/Cohorts', [
            'cohorts'         => $paginator,
            'instructorsList' => User::role('instructor')->select('id', 'name')->get(),
            'coursesList'     => Course::select('id', 'title', 'duration_minutes as duration')->where('status', 'published')->get(),
            'allStudentsList' => $allStudents,
            'statuses'        => ['Actif', 'Programmé', 'Terminé', 'Suspendu'],
            'stats'           => $stats,
            'filters'         => $request->only(['search', 'status', 'instructor_id']),
        ]);
    }

    // ---------------------------------------------------------------------
    // CRUD de base (store, update, destroy, etc.)
    // ---------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'status'          => 'required|in:Actif,Programmé,Terminé,Suspendu',
            'instructor_id'   => 'required|exists:users,id',
            'assistant_ids'   => 'nullable|array',
            'assistant_ids.*' => 'exists:users,id',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'max_students'    => 'required|integer|min:1',
            'is_private'      => 'boolean',
            'auto_enrollment' => 'boolean',
            'pass_percentage' => 'nullable|integer|min:0|max:100',
            'courses'         => 'required|array',
            'courses.*'       => 'exists:courses,id',
            'prerequisites'   => 'nullable|array',          // ajout
            'prerequisites.*' => 'exists:courses,id',
        ]);

        DB::beginTransaction();
        try {
            $cohort = Cohort::create([
                'name'            => $validated['name'],
                'description'     => $validated['description'],
                'status'          => $validated['status'],
                'instructor_id'   => $validated['instructor_id'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'max_students'    => $validated['max_students'],
                'is_private'      => $validated['is_private'] ?? false,
                'auto_enrollment' => $validated['auto_enrollment'] ?? true,
                'pass_percentage' => $validated['pass_percentage'] ?? 70,
            ]);

            $cohort->courses()->sync($validated['courses']);
            $cohort->assistants()->sync($validated['assistant_ids'] ?? []);
            $cohort->prerequisites()->sync($validated['prerequisites'] ?? []);  // relation prerequisites

            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'cohort_created',
                'auditable_type' => Cohort::class,
                'auditable_id'   => $cohort->id,
            ]);

            DB::commit();
            return Redirect::back()->with('success', 'La promotion a été configurée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $e;
            return Redirect::back()->with('error', 'Erreur critique : ' . $e->getMessage());
        }
    }

    public function update(Request $request, string $id)
    {
        $cohort = Cohort::findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'status'          => 'required|in:Actif,Programmé,Terminé,Suspendu',
            'instructor_id'   => 'required|exists:users,id',
            'assistant_ids'   => 'nullable|array',
            'assistant_ids.*' => 'exists:users,id',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'max_students'    => 'required|integer|min:' . $cohort->students()->count(),
            'is_private'      => 'boolean',
            'auto_enrollment' => 'boolean',
            'pass_percentage' => 'nullable|integer|min:0|max:100',
            'courses'         => 'required|array',
            'courses.*'       => 'exists:courses,id',
            'prerequisites'   => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id',
        ]);

        DB::beginTransaction();
        try {
            $cohort->update([
                'name'            => $validated['name'],
                'description'     => $validated['description'],
                'status'          => $validated['status'],
                'instructor_id'   => $validated['instructor_id'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'max_students'    => $validated['max_students'],
                'is_private'      => $validated['is_private'] ?? false,
                'auto_enrollment' => $validated['auto_enrollment'] ?? true,
                'pass_percentage' => $validated['pass_percentage'] ?? 70,
            ]);

            $cohort->courses()->sync($validated['courses']);
            $cohort->assistants()->sync($validated['assistant_ids'] ?? []);
            $cohort->prerequisites()->sync($validated['prerequisites'] ?? []);

            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'cohort_updated',
                'auditable_type' => Cohort::class,
                'auditable_id'   => $cohort->id,
            ]);

            DB::commit();
            return Redirect::back()->with('success', 'Les paramètres de la cohorte ont été mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $cohort = Cohort::withCount('students')->findOrFail($id);
        if ($cohort->students_count > 0) {
            return Redirect::back()->with('error', 'Impossible de purger cette promotion. Réassignez d\'abord les apprenants.');
        }
        $cohort->courses()->detach();
        $cohort->assistants()->detach();
        $cohort->prerequisites()->detach();
        $cohort->delete();
        return Redirect::back()->with('success', 'La cohorte a été supprimée définitivement.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:cohorts,id']);
        $cohorts = Cohort::withCount('students')->whereIn('id', $request->ids)->get();
        if ($cohorts->contains(fn($c) => $c->students_count > 0)) {
            return Redirect::back()->with('error', 'Certaines cohortes contiennent encore des étudiants.');
        }
        Cohort::whereIn('id', $request->ids)->delete();
        return Redirect::back()->with('success', 'Les cohortes sélectionnées ont été supprimées.');
    }

    public function destroyBulk(Request $request)
    {
        return $this->bulkDestroy($request);
    }

    public function bulkSuspend(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:cohorts,id']);
        $count = Cohort::whereIn('id', $request->ids)->update(['status' => 'Suspendu']);
        return Redirect::back()->with('success', "$count cohorte(s) suspendue(s).");
    }

    // ---------------------------------------------------------------------
    // Gestion des membres
    // ---------------------------------------------------------------------

    public function addMembers(Request $request, string $id)
    {
        $cohort = Cohort::findOrFail($id);
        $validated = $request->validate([
            'student_ids'     => 'required|array',
            'student_ids.*'   => 'exists:users,id',
            'notify_students' => 'boolean'
        ]);

        $current = $cohort->students()->count();
        $toAdd = count($validated['student_ids']);
        if (($current + $toAdd) > $cohort->max_students) {
            return Redirect::back()->with('error', 'Capacité maximale dépassée.');
        }

        $cohort->students()->syncWithoutDetaching($validated['student_ids']);

        // NOUVEAU : Inscrire automatiquement les étudiants aux cours de la cohorte
        $courseIds = $cohort->courses->pluck('id')->toArray();
        if (!empty($courseIds)) {
            $students = User::whereIn('users.id', $validated['student_ids'])->get();
            foreach ($students as $student) {
                // enrolledCourses() doit être une relation BelongsToMany dans le modèle User
                $student->enrolledCourses()->syncWithoutDetaching($courseIds);
            }
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'event'          => 'students_enrolled',
            'auditable_type' => Cohort::class,
            'auditable_id'   => $cohort->id,
        ]);

        return Redirect::back()->with('success', "$toAdd étudiant(s) enrôlé(s).");
    }

    public function removeMember(Request $request, $cohort_id, $student_id)
    {
        $cohort = Cohort::findOrFail($cohort_id);
        $cohort->students()->detach($student_id);

        // NOUVEAU : Désinscrire l'étudiant des cours de la cohorte
        $student = User::find($student_id);
        if ($student) {
            $courseIds = $cohort->courses->pluck('id')->toArray();
            if (!empty($courseIds)) {
                $student->enrolledCourses()->detach($courseIds);
            }
        }
        return Redirect::back()->with('success', 'Étudiant retiré du groupe.');
    }

    public function transferMember(Request $request)
    {
        $validated = $request->validate([
            'student_id'       => 'required|exists:users,id',
            'to_cohort_id'     => 'required|exists:cohorts,id',
            'transfer_progress' => 'boolean'
        ]);

        $oldCohort = Cohort::whereHas('students', fn($q) => $q->where('id', $validated['student_id']))->first();
        $newCohort = Cohort::findOrFail($validated['to_cohort_id']);

        if ($newCohort->students()->count() >= $newCohort->max_students) {
            return Redirect::back()->with('error', 'La cohorte de destination est pleine.');
        }

        if ($oldCohort) $oldCohort->students()->detach($validated['student_id']);
        $newCohort->students()->attach($validated['student_id']);

        return Redirect::back()->with('success', "Étudiant transféré avec succès.");
    }

    public function suspendMember(Request $request, $cohort_id, $student_id)
    {
        // Implémentez selon votre logique métier (ex: soft delete de la relation, champ status)
        $student = User::findOrFail($student_id);
        // Exemple : $student->cohort_status = 'suspended'; ...
        return Redirect::back()->with('success', "Accès de {$student->name} suspendu.");
    }

    // ---------------------------------------------------------------------
    // API : Détail complet d'une cohorte (Profil 360°)
    // ---------------------------------------------------------------------
    public function show(Cohort $cohort)
    {
        $cohort->load([
            'instructor',
            'assistants',
            'courses',
            'students.profile',
            'prerequisites',          // relation belongsToMany
            'documents',              // relation hasMany
            'liveSessions',           // relation hasMany
            'auditLogs' => function($q) { $q->latest()->take(50); }
        ])->loadCount('students');

        $avgCompletion = $cohort->students_count > 0
            ? round($cohort->students->avg('profile.progress') ?? 0)
            : 0;

        return response()->json([
            'data' => [
                'id'               => $cohort->id,
                'name'             => $cohort->name,
                'description'      => $cohort->description,
                'status'           => $cohort->status,
                'start_date'       => $cohort->start_date,
                'end_date'         => $cohort->end_date,
                'max_students'     => $cohort->max_students,
                'is_private'       => (bool) $cohort->is_private,
                'auto_enrollment'  => (bool) $cohort->auto_enrollment,
                'pass_percentage'  => $cohort->pass_percentage ?? 70,
                'instructor'       => $cohort->instructor ? [
                    'id'          => $cohort->instructor->id,
                    'name'        => $cohort->instructor->name,
                    'avatar_path' => optional($cohort->instructor->profile)->avatar_path
                                     ? asset('storage/' . $cohort->instructor->profile->avatar_path) : null,
                ] : null,
                'assistant_ids'    => $cohort->assistants->pluck('id'),
                'members_count'    => $cohort->students_count,
                'avg_completion'   => $avgCompletion,
                // Données pour le graphique doughnut (à adapter selon votre logique)
                'excellent_count'  => $cohort->students->where('profile.progress', '>=', 80)->count(),
                'average_count'    => $cohort->students->whereBetween('profile.progress', [50, 79])->count(),
                'difficult_count'  => $cohort->students->where('profile.progress', '<', 50)->count(),
                'members'          => $cohort->students->map(fn($s) => [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'email'       => $s->email,
                    'avatar_path' => optional($s->profile)->avatar_path
                                     ? asset('storage/' . $s->profile->avatar_path) : null,
                    'pivot' => [
                        'progress'    => $s->profile->progress ?? 0,
                        'enrolled_at' => $s->updated_at,
                    ]
                ]),
                'assigned_courses' => $cohort->courses->map(fn($c) => [
                    'id'             => $c->id,
                    'title'          => $c->title,
                    'duration'       => $c->duration_minutes ?? 0,
                ]),
                'prerequisites' => $cohort->prerequisites->map(fn($c) => [
                    'id'       => $c->id,
                    'title'    => $c->title,
                    'duration' => $c->duration_minutes ?? 0,
                ]),
                'documents' => $cohort->documents->map(fn($d) => [
                    'id'   => $d->id,
                    'name' => $d->name,
                    'size' => $d->size, // Taille lisible si formatée
                    'type' => $d->mime_type,
                ]),
                'live_sessions' => $cohort->liveSessions->map(fn($s) => [
                    'id'            => $s->id,
                    'title'         => $s->title,
                    'session_date'  => $s->session_date,
                    'platform'      => $s->platform,
                    'link'          => $s->link,
                    'status'        => $s->status ?? 'à venir', // à définir
                ]),
                'audit_logs' => $cohort->auditLogs->map(fn($log) => [
                    'action' => $log->event,
                    'date'   => $log->created_at->format('d/m/Y H:i'),
                    'user'   => $log->user->name ?? 'Système',
                    'icon'   => 'pi pi-check',   // adaptez
                    'color'  => '#4f46e5',
                ]),
            ]
        ]);
    }

    // ---------------------------------------------------------------------
    // API : Documents
    // ---------------------------------------------------------------------
    public function documents(Cohort $cohort)
    {
        return response()->json(['data' => $cohort->documents->map(fn($d) => [
            'id'   => $d->id,
            'name' => $d->name,
            'size' => $d->size,
            'type' => $d->mime_type,
        ])]);
    }

    public function storeDocument(Request $request, Cohort $cohort)
    {
        $request->validate(['file' => 'required|file|max:102400']); // 100 Mo max

        $file = $request->file('file');
        $path = $file->store("cohorts/{$cohort->id}/documents", 'public');

        $document = $cohort->documents()->create([
            'name'      => $file->getClientOriginalName(),
            'path'      => $path,
            'size'      => $this->formatBytes($file->getSize()),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'message' => 'Document uploadé.',
            'data'    => [
                'id'   => $document->id,
                'name' => $document->name,
                'size' => $document->size,
                'type' => $document->mime_type,
            ]
        ]);
    }

    public function deleteDocument(Cohort $cohort, Document $document)
    {
        if ($document->cohort_id !== $cohort->id) {
            abort(403);
        }
        Storage::disk('public')->delete($document->path);
        $document->delete();
        return response()->json(['message' => 'Document supprimé.']);
    }

    public function downloadDocument(Document $document)
    {
        // Vérifiez éventuellement que l'utilisateur a le droit d'accéder
        return Storage::disk('public')->download($document->path, $document->name);
    }

    // ---------------------------------------------------------------------
    // API : Sessions live
    // ---------------------------------------------------------------------
    public function liveSessions(Cohort $cohort)
    {
        return response()->json(['data' => $cohort->liveSessions->map(fn($s) => [
            'id'           => $s->id,
            'title'        => $s->title,
            'session_date' => $s->session_date,
            'platform'     => $s->platform,
            'link'         => $s->link,
            'status'       => $s->status ?? 'à venir',
        ])]);
    }

    public function storeLiveSession(Request $request, Cohort $cohort)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'meeting_url'  => 'nullable|url',
            'start_at'     => 'required|date',
            'duration'     => 'nullable|integer|min:15',
            'instructor_id'=> 'required|exists:users,id',
            'course_id'    => 'required|exists:courses,id',
        ]);

        $cohort->liveSessions()->create([
            'title'        => $validated['title'],
            'instructor_id'=> $validated['instructor_id'],
            'meeting_url'  => $validated['meeting_url'] ?? '',
            'start_at'     => $validated['start_at'],
            'duration'     => $validated['duration'] ?? 60,
            'status'       => 'Scheduled',
            'course_id'    => $validated['course_id'],
        ]);

        return Redirect::back()->with('success', 'La session live a été programmée.');
    }

    public function updateLiveSession(Request $request, Cohort $cohort, LiveSession $liveSession)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'meeting_url'  => 'nullable|url',
            'start_at'     => 'required|date',
            'duration'     => 'nullable|integer|min:15',
            'instructor_id'=> 'required|exists:users,id',
            'course_id'    => 'required|exists:courses,id',
        ]);

        $liveSession->update([
            'title'        => $validated['title'],
            'instructor_id'=> $validated['instructor_id'],
            'meeting_url'  => $validated['meeting_url'] ?? '',
            'start_at'     => $validated['start_at'],
            'duration'     => $validated['duration'] ?? 60,
            'course_id'    => $validated['course_id'],
        ]);

        return Redirect::back()->with('success', 'Session live mise à jour.');
    }

    public function destroyLiveSession(Cohort $cohort, LiveSession $liveSession)
    {
        $liveSession->delete();
        return Redirect::back()->with('success', 'Session live supprimée.');
    }

    // ---------------------------------------------------------------------
    // API : Prérequis
    // ---------------------------------------------------------------------
    public function prerequisites(Cohort $cohort)
    {
        return response()->json(['data' => $cohort->prerequisites->map(fn($c) => [
            'id'    => $c->id,
            'title' => $c->title,
        ])]);
    }

    public function updatePrerequisites(Request $request, Cohort $cohort)
    {
        $request->validate([
            'prerequisites'   => 'nullable|array',
            'prerequisites.*' => 'exists:courses,id'
        ]);

        $cohort->prerequisites()->sync($request->prerequisites ?? []);

        return response()->json(['message' => 'Prérequis mis à jour.']);
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
