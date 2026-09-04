<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TestimonialController extends Controller
{
    /**
     * Liste des témoignages.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Testimonial::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('author_name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('rating'), function ($q, $rating) {
                $q->where('rating', $rating);
            });

        $testimonials = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Web/Testimonials', [
            'testimonials' => $testimonials,
            'filters' => $request->all(['search', 'status', 'rating']),
        ]);
    }

    /**
     * Formulaire de création.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Web/Testimonials');
    }

    /**
     * Enregistre un témoignage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_role' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'status' => 'required|in:pending,approved,rejected',
            'avatar' => 'nullable|image|max:2048', // 2MB
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('testimonials', 'public');
                $validated['avatar_url'] = asset('storage/' . $path);
            }

            $testimonial = Testimonial::create($validated);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'testimonial_created',
                'auditable_type' => Testimonial::class,
                'auditable_id' => $testimonial->id,
            ]);

            DB::commit();
            return Redirect::route('web.testimonials.index')->with('success', 'Témoignage ajouté.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Affiche un témoignage.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function show(string $id)
    {
        return Inertia::render('Web/Testimonials', [
            'testimonial' => Testimonial::findOrFail($id)
        ]);
    }

    /**
     * Formulaire d'édition.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function edit(string $id)
    {
        return Inertia::render('Web/Testimonials', [
            'testimonial' => Testimonial::findOrFail($id)
        ]);
    }

    /**
     * Met à jour un témoignage.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_role' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'status' => 'required|in:pending,approved,rejected',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar si nécessaire
            // Storage::disk('public')->delete($testimonial->avatar_path);
            $path = $request->file('avatar')->store('testimonials', 'public');
            $validated['avatar_url'] = asset('storage/' . $path);
        }

        $testimonial->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'testimonial_updated',
            'auditable_type' => Testimonial::class,
            'auditable_id' => $testimonial->id,
        ]);

        return Redirect::route('web.testimonials.index')->with('success', 'Témoignage mis à jour.');
    }

    /**
     * Supprime un témoignage.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();

        return Redirect::back()->with('success', 'Témoignage supprimé.');
    }

    /**
     * Change le statut (Approuver/Rejeter).
     *
     * @param string $id
     * @param string $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(string $id, string $status)
    {
        if (!in_array($status, ['approved', 'rejected', 'pending'])) {
            return Redirect::back()->with('error', 'Statut invalide.');
        }

        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update(['status' => $status]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'testimonial_status_changed',
            'auditable_type' => Testimonial::class,
            'auditable_id' => $testimonial->id,
            'new_values' => json_encode(['status' => $status]),
        ]);

        return Redirect::back()->with('success', "Statut changé en : $status");
    }
}
