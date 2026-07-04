<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningPath;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile; // Assurez-vous d'avoir ce modèle
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares et permissions.
     */
    public function __construct()
    {
        // $this->middleware('can:manage-students');
    }

    /**
     * Affiche la liste des étudiants avec le formatage strict exigé par la vue Vue.js Enterprise.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        // 1. Construction de la requête principale avec Eager Loading
        $query = User::role('student')
            ->with(['profile', 'courses', 'learningPaths'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('level'), function ($q, $level) {
                $q->whereHas('profile', function($query) use ($level) {
                    $query->where('level', $level);
                });
            })
            ->latest();

        // 2. Pagination
        $paginator = $query->paginate($perPage)->withQueryString();

        // 3. Transformation des données pour qu'elles matchent EXACTEMENT l'attente du front (sans mapping côté JS)
        $paginator->getCollection()->transform(function ($user) {
            return [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'phone'              => $user->phone ?? '',
                'numero'             => $user->numero ?? '',
                'country'            => $user->country ?? '',
                'fonction'           => $user->fonction ?? '',
                'hourly_rate'        => $user->hourly_rate ?? 0,
                'status'             => $user->status, // ex: 'Actif', 'Suspendu'
                'created_at'         => $user->created_at,
                // Données du Profil
                'address'            => $user->profile->address ?? '',
                'bio'                => $user->profile->bio ?? '',
                'level'              => $user->profile->level ?? 'Débutant',
                'progress'           => $user->profile->progress ?? 0,
                'score'              => $user->profile->score ?? 0,
                'avatar_path'        => isset($user->profile->avatar_path) ? asset('storage/' . $user->profile->avatar_path) : null,
                'certificates'       => $user->profile->certificates ?? 0,
                'average_attendance' => $user->profile->average_attendance ?? 0,
                // Cours Inscrits (Formatage Pivot)
                'enrolled_courses'   => $user->courses->map(function ($course) {
                    return [
                        'id'             => $course->id,
                        'title'          => $course->title,
                        'thumbnail_path' => $course->thumbnail_path ? asset('storage/' . $course->thumbnail_path) : null,
                        'pivot' => [
                            'progress'    => $course->pivot->progress ?? 0,
                            'enrolled_at' => $course->pivot->enrolled_at ?? $course->pivot->created_at,
                        ]
                    ];
                }),
                // Parcours d'apprentissage (Learning Paths)
                'learning_paths'   => $user->learningPaths->map(function ($lp) {
                    return [
                        'id'   => $lp->id,
                        'name' => $lp->name,
                        'pivot' => [
                            'progress'    => $lp->pivot->progress ?? 0,
                            'enrolled_at' => $lp->pivot->enrolled_at ?? $lp->pivot->created_at,
                        ]
                    ];
                }),
            ];
        });

        // 4. Calcul des statistiques dynamiques pour les KPI (Haut de page)
        $baseStudentQuery = User::role('student');
        $totalStudents = (clone $baseStudentQuery)->count();
        $activeStudents = (clone $baseStudentQuery)->where('status', 'Actif')->count();
        $avgProgress = Profile::whereHas('user', fn($q) => $q->role('student'))->avg('progress') ?? 0;
        $certifiedThisYear = Profile::whereHas('user', fn($q) => $q->role('student'))->sum('certificates') ?? 0;

        // 5. Renvoi vers la vue Inertia
        return Inertia::render('Users/Students', [ // <-- Adaptez le chemin de votre composant Vue
            'students' => $paginator,
            'learningPaths' => LearningPath::select('id', 'name')->get(),
            'statuses' => ['Actif', 'Inactif', 'Suspendu', 'Diplômé'],
            'levels' => [
                ['label' => 'Débutant',      'value' => 'Débutant',      'icon' => 'pi pi-star'],
                ['label' => 'Intermédiaire', 'value' => 'Intermédiaire', 'icon' => 'pi pi-star-half'],
                ['label' => 'Avancé',        'value' => 'Avancé',        'icon' => 'pi pi-star-fill'],
                ['label' => 'Expert',        'value' => 'Expert',        'icon' => 'pi pi-bolt']
            ],
            'stats' => [
                'total_students'      => $totalStudents,
                'active_students'     => $activeStudents,
                'avg_progress'        => round($avgProgress),
                'certified_this_year' => $certifiedThisYear,
            ],
            'filters' => $request->only(['search', 'status', 'level']),
            'meta' => [
                'target_progress' => 80 // Objectif de progression global
            ]
        ]);
    }

    /**
     * Enregistre un nouvel étudiant.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'numero'   => 'nullable|string|max:20',
            'country'  => 'nullable|string|max:100',
            'fonction' => 'nullable|string|max:255',
            'address'  => 'nullable|string',
            'bio'      => 'nullable|string|max:1000',
            'status'   => 'required|in:Actif,Inactif,Suspendu,Diplômé',
            'level'    => 'required|string',
            'progress' => 'nullable|integer|min:0|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'password' => 'nullable|string|min:8',
            'score'    => 'nullable|integer|min:0',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 1. Création du User
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'] ?? null,
                'numero'   => $validated['numero'] ?? null,
                'country'  => $validated['country'] ?? null,
                'fonction' => $validated['fonction'] ?? null,
                'hourly_rate' => $validated['hourly_rate'] ?? 0,
                'status'   => $validated['status'],
                'password' => Hash::make($validated['password'] ?? str()->random(12)),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('student');

            // 2. Gestion de l'upload Avatar
            if ($request->boolean('send_credentials')) {
                // Optionnel : Logique pour envoyer un mail de bienvenue avec les accès
                // Mail::to($user->email)->send(new StudentCredentialsMail($user, $validated['password']));
            }

            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/students', 'public');
            }

            // 3. Création du Profil Pédagogique
            $user->profile()->create([
                'address'  => $validated['address'] ?? null,
                'bio'      => $validated['bio'] ?? null,
                'level'    => $validated['level'] ?? 'Débutant',
                'progress' => $validated['progress'] ?? 0,
                'score'    => $validated['score'] ?? 0,
                'avatar_path' => $avatarPath,
            ]);

            // 4. Log
            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'student_created',
                'auditable_type' => User::class,
                'auditable_id'   => $user->id,
            ]);

            DB::commit();

            return Redirect::back()->with('success', 'Étudiant créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur système lors de la création.');
        }
    }

    /**
     * Met à jour les informations d'un étudiant existant.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string',
            'numero'   => 'nullable|string|max:20',
            'country'  => 'nullable|string|max:100',
            'fonction' => 'nullable|string|max:255',
            'bio'      => 'nullable|string|max:1000',
            'status'   => 'required|string',
            'level'    => 'required|string',
            'progress' => 'nullable|integer|min:0|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'score'    => 'nullable|integer|min:0',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'requires_password_reset' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // 1. Maj User
            $user->update($request->only([
                'name',
                'email',
                'phone',
                'numero',
                'country',
                'fonction',
                'hourly_rate',
                'status'
            ]));

            // Si le Toggle de réinitialisation MDP a été activé depuis le front
            if ($request->boolean('requires_password_reset')) {
                Password::broker()->sendResetLink(['email' => $user->email]);
            }

            // 2. Maj Upload Avatar
            $avatarPath = $user->profile->avatar_path ?? null;
            if ($request->hasFile('avatar')) {
                // Suppression de l'ancienne image si elle existe
                if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
                $avatarPath = $request->file('avatar')->store('avatars/students', 'public');
            }

            // 3. Maj Profil
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address'  => $validated['address'] ?? null,
                    'bio'      => $validated['bio'] ?? null,
                    'level'    => $validated['level'],
                    'progress' => $validated['progress'],
                    'score'    => $validated['score'],
                    'avatar_path' => $avatarPath,
                ]
            );

            // 4. Log
            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'student_updated',
                'auditable_type' => User::class,
                'auditable_id'   => $user->id,
            ]);

            DB::commit();

            return Redirect::back()->with('success', 'Dossier étudiant mis à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la mise à jour.');
        }
    }

    /**
     * Supprime définitivement (ou Soft Delete) un étudiant.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        try {
            $user->delete(); // Soft delete recommandé dans un LMS
            return Redirect::back()->with('success', 'Étudiant supprimé et archivé avec succès.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Impossible de supprimer l\'étudiant.');
        }
    }

    /**
     * Suppression multiple en masse depuis la DataTable.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $count = User::whereIn('id', $request->ids)->role('student')->delete();

            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'students_bulk_deleted',
                'auditable_type' => User::class,
                'auditable_id'   => 0,
                'new_values'     => json_encode(['count' => $count, 'ids' => $request->ids]),
            ]);

            DB::commit();
            return Redirect::back()->with('success', "$count dossiers étudiants purgés.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la suppression de masse.');
        }
    }

    /**
     * Suspension en masse depuis le menu des actions de groupe.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkSuspend(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        try {
            $count = User::whereIn('id', $request->ids)->role('student')->update(['status' => 'Suspendu']);

            return Redirect::back()->with('success', "$count comptes étudiants suspendus avec succès.");
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur lors de la suspension de masse.');
        }
    }

    /**
     * Inscrire un étudiant à un cours.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enroll(Request $request, string $id)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        $user = User::findOrFail($id);
        $user->courses()->syncWithoutDetaching([$request->course_id => [
            'enrolled_at' => now(),
        ]]);

        return Redirect::back()->with('success', 'Étudiant inscrit au cours avec succès.');
    }
}
