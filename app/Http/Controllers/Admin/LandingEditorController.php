<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSlide;
use App\Models\LandingStat;
use App\Models\LandingTechnology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;

class LandingEditorController extends Controller
{
    public function index()
    {
        return Inertia::render('Web/LandingEditor', [
            'slides'       => LandingSlide::orderBy('position')->orderBy('id')->get(),
            'technologies' => LandingTechnology::orderBy('position')->orderBy('name')->get(),
            'stats'        => LandingStat::orderBy('position')->orderBy('id')->get(),
        ]);
    }

    // ── SLIDES ─────────────────────────────────────────────────
    public function storeSlide(Request $request)
    {
        $data = $this->validateSlide($request);
        $data['position'] = $data['position'] ?? (LandingSlide::max('position') + 1);
        $slide = LandingSlide::create($data);
        return Redirect::back()->with('success', 'Slide ajouté.');
    }

    public function updateSlide(Request $request, LandingSlide $slide)
    {
        $data = $this->validateSlide($request, $slide->id);
        $slide->update($data);
        return Redirect::back()->with('success', 'Slide mis à jour.');
    }

    public function destroySlide(LandingSlide $slide)
    {
        $slide->delete();
        return Redirect::back()->with('success', 'Slide supprimé.');
    }

    public function reorderSlides(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        foreach ($request->ids as $index => $id) {
            LandingSlide::where('id', $id)->update(['position' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }

    public function toggleSlide(LandingSlide $slide)
    {
        $slide->update(['is_active' => ! $slide->is_active]);
        return Redirect::back()->with('success', 'Slide ' . ($slide->is_active ? 'activé' : 'désactivé'));
    }

    private function validateSlide(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'badge'      => 'nullable|string|max:100',
            'title'      => 'required|string|max:255',
            'highlight'  => 'nullable|string|max:255',
            'description'=> 'required|string|max:2000',
            'bg_image'   => 'nullable|string|max:500',
            'main_image' => 'nullable|string|max:500',
            'bg_image_file'   => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:5120',
            'main_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:5120',
            'icon'       => 'nullable|string|max:50',
            'card_title' => 'nullable|string|max:255',
            'card_sub'   => 'nullable|string|max:255',
            'cta_label'  => 'nullable|string|max:100',
            'cta_url'    => 'nullable|string|max:500',
            'position'   => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        // Upload des images locales si fournies
        if ($request->hasFile('bg_image_file')) {
            $data['bg_image'] = $this->uploadSlideImage($request->file('bg_image_file'), 'bg', $id);
        }
        if ($request->hasFile('main_image_file')) {
            $data['main_image'] = $this->uploadSlideImage($request->file('main_image_file'), 'main', $id);
        }

        unset($data['bg_image_file'], $data['main_image_file']);
        return $data;
    }

    private function uploadSlideImage($file, string $prefix, ?int $id = null): string
    {
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = $prefix . '_' . ($id ?? 'new') . '_' . Str::random(8) . '.' . $ext;
        $path = $file->storeAs('public/landing', $name);
        return Storage::url($path);
    }

    // ── TECHNOLOGIES ───────────────────────────────────────────
    public function storeTech(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:100|unique:landing_technologies,slug',
            'icon'        => 'nullable|string|max:100',
            'color'       => 'nullable|string|max:20',
            'category'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'position'    => 'nullable|integer',
            'is_active'   => 'boolean',
        ]);
        if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
        $data['position'] = $data['position'] ?? (LandingTechnology::max('position') + 1);
        LandingTechnology::create($data);
        return Redirect::back()->with('success', 'Technologie ajoutée.');
    }

    public function updateTech(Request $request, LandingTechnology $tech)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:100|unique:landing_technologies,slug,' . $tech->id,
            'icon'        => 'nullable|string|max:100',
            'color'       => 'nullable|string|max:20',
            'category'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'position'    => 'nullable|integer',
            'is_active'   => 'boolean',
        ]);
        if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
        $tech->update($data);
        return Redirect::back()->with('success', 'Technologie mise à jour.');
    }

    public function destroyTech(LandingTechnology $tech)
    {
        $tech->delete();
        return Redirect::back()->with('success', 'Technologie supprimée.');
    }

    public function toggleTech(LandingTechnology $tech)
    {
        $tech->update(['is_active' => ! $tech->is_active]);
        return Redirect::back()->with('success', 'Technologie ' . ($tech->is_active ? 'activée' : 'désactivée'));
    }

    // ── STATS ──────────────────────────────────────────────────
    public function storeStat(Request $request)
    {
        $data = $this->validateStat($request);
        $data['position'] = $data['position'] ?? (LandingStat::max('position') + 1);
        LandingStat::create($data);
        return Redirect::back()->with('success', 'Statistique ajoutée.');
    }

    public function updateStat(Request $request, LandingStat $stat)
    {
        $data = $this->validateStat($request);
        $stat->update($data);
        return Redirect::back()->with('success', 'Statistique mise à jour.');
    }

    public function destroyStat(LandingStat $stat)
    {
        $stat->delete();
        return Redirect::back()->with('success', 'Statistique supprimée.');
    }

    public function toggleStat(LandingStat $stat)
    {
        $stat->update(['is_active' => ! $stat->is_active]);
        return Redirect::back()->with('success', 'Stat ' . ($stat->is_active ? 'activé' : 'désactivé'));
    }

    private function validateStat(Request $request): array
    {
        return $request->validate([
            'label'     => 'required|string|max:100',
            'value'     => 'required|string|max:50',
            'icon'      => 'nullable|string|max:50',
            'color'     => 'nullable|string|max:20',
            'position'  => 'nullable|integer',
            'is_active' => 'boolean',
        ]);
    }
}
