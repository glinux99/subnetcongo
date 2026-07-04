<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Profile; // S'assurer que le modèle Profile est lié
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class InstructorController extends Controller
{
    /**
     * Constructeur pour l'application des middlewares de sécurité.
     */
    public function __construct()
    {
        // $this->middleware('can:manage-instructors');
    }

    /**
     * Affiche la liste des instructeurs (Tableau de bord RH Enterprise).
     * Formatage strict pour alimenter les composants PrimeVue (DataTable, Expansion, Profil 360).
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        // 1. Construction de la requête avec Eager Loading pour éviter le problème N+1
        $query = User::role('instructor')
            ->with(['profile', 'courses' => function ($q) {
                // Charger les cours assignés avec leurs propres statistiques (élèves, avis)
                $q->withCount('students')->withAvg('reviews', 'rating');
            }])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('numero', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('specialty'), function ($q, $specialty) {
                $q->whereHas('profile', function ($p) use ($specialty) {
                    // Recherche JSON (MySQL 5.7+ / PostgreSQL)
                    $p->where('specialties', 'like', "%{$specialty}%");
                });
            })
            ->latest();

        $paginator = $query->paginate($perPage)->withQueryString();

        // 2. Transformation stricte des données pour le front-end (Vue.js / PrimeVue)
        $paginator->getCollection()->transform(function ($user) {

            // Calculs aggrégés pour la ligne d'expansion et les KPIs individuels
            $totalStudents = $user->courses->sum('students_count');
            $avgRating = $user->courses->avg('reviews_avg_rating') ?? 0;

            // Calcul du revenu généré basé sur la commission de l'instructeur
            $commissionRate = $user->profile->commission_rate ?? 70;
            $generatedRevenue = $user->courses->sum(function($course) use ($commissionRate) {
                // (Prix du cours * Nombre d'élèves) * (Commission / 100)
                return ($course->price * $course->students_count) * ($commissionRate / 100);
            });

            return [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'numero'              => $user->numero,
                'status'             => $user->status, // 'Actif', 'En intégration', 'Suspendu'

                // Champs du Profil RH
                'address'            => $user->profile->address ?? '',
                'bio'                => $user->profile->bio ?? '',
                'specialties'        => $user->profile->specialties ?? '[]', // Attendu en JSON string par le front
                'social'             => $user->profile->social ?? '{"linkedin":"","website":""}',
                'commission_rate'    => $commissionRate,
                'avatar_path'        => isset($user->profile->avatar_path) ? asset('storage/' . $user->profile->avatar_path) : null,

                // Statistiques de performance (KPI individuels)
                'assigned_courses_count' => $user->courses->count(),
                'total_students'         => $totalStudents,
                'generated_revenue'      => $generatedRevenue,
                'average_rating'         => round($avgRating, 1),

                // Portfolio détaillé (Row Expansion)
                'assigned_courses' => $user->courses->map(function ($course) use ($commissionRate) {
                    return [
                        'id'             => $course->id,
                        'title'          => $course->title,
                        'status'         => $course->status,
                        'level'          => $course->level ?? 'Général',
                        'thumbnail_path' => $course->thumbnail_path ? asset('storage/' . $course->thumbnail_path) : null,
                        'students_count' => $course->students_count,
                        'rating'         => round($course->reviews_avg_rating ?? 0, 1),
                        'revenue'        => ($course->price * $course->students_count) * ($commissionRate / 100),
                    ];
                }),
            ];
        });

        // 3. Calcul des KPI Globaux (Top Dashboard)
        $baseQuery = User::role('instructor');

        // Simuler ou calculer le total des commissions versées sur la plateforme
        // Dans un vrai cas, ceci viendrait d'une table `payouts` ou `transactions`
        $totalCommissions = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->join('courses', 'users.id', '=', 'courses.instructor_id')
            ->selectRaw('SUM(courses.price * (profiles.commission_rate / 100)) as total')
            ->value('total') ?? 0;

        $stats = [
            'total_instructors' => (clone $baseQuery)->count(),
            'active_courses'    => Course::whereNotNull('instructor_id')->where('status', 'published')->count(),
            'avg_rating'        => number_format(DB::table('reviews')->avg('rating') ?? 0, 1),
            'total_commissions' => $totalCommissions,
        ];

        return Inertia::render('Users/Instructors', [ // Vérifiez le chemin vers votre fichier Vue
            'instructors' => $paginator,
            'statuses'    => ['Actif', 'En intégration', 'Inactif', 'Suspendu'],
            'specialtiesList' => [
                ['label' => 'Développement Web', 'value' => 'Développement Web'],
                ['label' => 'Intelligence Artificielle', 'value' => 'Intelligence Artificielle'],
                ['label' => 'Design UI/UX', 'value' => 'Design UI/UX'],
                ['label' => 'Cybersécurité', 'value' => 'Cybersécurité'],
                ['label' => 'Marketing Digital', 'value' => 'Marketing Digital'],
                ['label' => 'Data Science', 'value' => 'Data Science'],
                ['label' => 'Cloud Computing', 'value' => 'Cloud Computing'],
                ['label' => 'Management & Agile', 'value' => 'Management & Agile']
            ],
            'stats'   => $stats,
            'filters' => $request->only(['search', 'status', 'specialty'])
        ]);
    }

    /**
     * Création d'un nouvel instructeur (Via Workspace Editor).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'numero'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'bio'             => 'nullable|string',
            'specialties'     => 'required|json', // Envoyé via JSON.stringify depuis Vue
            'status'          => 'required|in:Actif,En intégration,Inactif,Suspendu',
            'commission_rate' => 'required|integer|min:0|max:100',
            'social'          => 'nullable|json',
            'avatar'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 1. Création Utilisateur
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'numero'    => $validated['numero'] ?? null,
                'status'   => $validated['status'],
                'password' => Hash::make(str()->random(16)), // MDP aléatoire fort
                'email_verified_at' => now(),
            ]);

            $user->assignRole('instructor');

            // 2. Upload de l'Avatar
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/instructors', 'public');
            }

            // 3. Création du Profil RH
            $user->profile()->create([
                'address'         => $validated['address'] ?? null,
                'bio'             => $validated['bio'] ?? null,
                'specialties'     => $validated['specialties'],
                'social'          => $validated['social'],
                'commission_rate' => $validated['commission_rate'],
                'avatar_path'     => $avatarPath,
            ]);

            // 4. Log système
            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'instructor_created',
                'auditable_type' => User::class,
                'auditable_id'   => $user->id,
            ]);

            DB::commit();

            return Redirect::back()->with('success', 'Le profil de l\'expert a été créé avec succès.');

        } catch (\Exception $e) {
            return $e;
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur critique lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Mise à jour du dossier d'un instructeur (Via Workspace Editor).
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $user = User::with('profile')->findOrFail($id);

      try {
          $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'numero'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'bio'             => 'nullable|string',
            'specialties'     => 'required|json',
            'status'          => 'required|in:Actif,En intégration,Inactif,Suspendu',
            'commission_rate' => 'required|integer|min:0|max:100',
            'social'          => 'nullable|json',
            'avatar'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
      } catch (\Throwable $th) {
        //throw
        return $th;
      }

        DB::beginTransaction();

        try {
            // 1. Maj Utilisateur
            $user->update([
                'name'   => $validated['name'],
                'email'  => $validated['email'],
                'numero'  => $validated['numero'] ?? null,
                'status' => $validated['status'],
            ]);

            // 2. Gestion de la réinitialisation du mot de passe (Toggle Switch de la vue)
            if ($request->boolean('requires_password_reset')) {
                // Envoi du lien natif de réinitialisation de Laravel
                Password::broker()->sendResetLink(['email' => $user->email]);
            }

            // 3. Maj Upload Avatar (Suppression de l'ancien si modifié)
            $avatarPath = $user->profile->avatar_path ?? null;
            if ($request->hasFile('avatar')) {
                if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                    Storage::disk('public')->delete($avatarPath);
                }
                $avatarPath = $request->file('avatar')->store('avatars/instructors', 'public');
            }

            // 4. Maj Profil
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address'         => $validated['address'] ?? null,
                    'bio'             => $validated['bio'] ?? null,
                    'specialties'     => $validated['specialties'],
                    'social'          => $validated['social'],
                    'commission_rate' => $validated['commission_rate'],
                    'avatar_path'     => $avatarPath,
                ]
            );

            // 5. Log système
            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'instructor_updated',
                'auditable_type' => User::class,
                'auditable_id'   => $user->id,
            ]);

            DB::commit();

            return Redirect::back()->with('success', 'Le dossier RH de l\'instructeur a été mis à jour.');

        } catch (\Exception $e) {
            return $e;
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Purge un instructeur individuellement.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // Optionnel : Bloquer la suppression s'il a des cours publiés
        if ($user->courses()->where('status', 'published')->exists()) {
            return Redirect::back()->with('error', 'Action refusée : Cet expert possède des cours en ligne. Veuillez les assigner à un autre formateur ou les archiver d\'abord.');
        }

        try {
            $user->delete(); // Soft Delete
            return Redirect::back()->with('success', 'Le dossier de l\'instructeur a été archivé.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    /**
     * Suppression (Purge) multiple d'instructeurs (Menu actions groupées).
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
            $count = User::whereIn('id', $request->ids)->role('instructor')->delete();

            AuditLog::create([
                'user_id'        => Auth::id(),
                'event'          => 'instructors_bulk_deleted',
                'auditable_type' => User::class,
                'auditable_id'   => 0,
                'new_values'     => json_encode(['count' => $count, 'ids' => $request->ids]),
            ]);

            DB::commit();
            return Redirect::back()->with('success', "$count dossiers formateurs ont été purgés.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la purge de masse.');
        }
    }

    /**
     * Suspension massive d'instructeurs.
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
            $count = User::whereIn('id', $request->ids)
                         ->role('instructor')
                         ->update(['status' => 'Suspendu']);

            return Redirect::back()->with('success', "$count contrats ont été suspendus avec succès.");
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur lors de la suspension des contrats.');
        }
    }
}
