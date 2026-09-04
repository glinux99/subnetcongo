<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset; // Modèle supposé pour la médiathèque
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * Affiche la médiathèque avec filtrage et recherche.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Asset::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('original_name', 'like', "%{$search}%");
            })
            ->when($request->input('type'), function ($q, $type) {
                // Ex: image, document, video
                $q->where('mime_type', 'like', "{$type}%");
            })
            ->when($request->input('date'), function ($q, $date) {
                $q->whereDate('created_at', $date);
            });

        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $assets = $query->orderBy($sortField, $sortDirection)
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('Web/Assets', [
            'assets' => $assets,
            'filters' => $request->all(['search', 'type', 'date', 'sort_field', 'sort_direction']),
            'stats' => [
                'total_size' => $this->formatBytes(Asset::sum('size')),
                'count' => Asset::count(),
                'images_count' => Asset::where('mime_type', 'like', 'image%')->count(),
            ]
        ]);
    }

    /**
     * Formulaire d'upload (souvent une modale ou zone dropzone).
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Web/Assets');
    }

    /**
     * Upload d'un ou plusieurs fichiers.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max par fichier
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Stockage
            $path = $file->store('uploads/' . date('Y/m'), 'public');

            $asset = Asset::create([
                'user_id' => Auth::id(),
                'name' => pathinfo($originalName, PATHINFO_FILENAME),
                'original_name' => $originalName,
                'path' => $path,
                'url' => Storage::url($path),
                'mime_type' => $mimeType,
                'extension' => $extension,
                'size' => $size,
                'alt_text' => '',
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'asset_uploaded',
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json($asset);
            }

            return Redirect::route('web.assets.index')->with('success', 'Fichier téléchargé avec succès.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return Redirect::back()->with('error', 'Erreur upload : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour les métadonnées d'un fichier.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $asset->update($validated);

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'asset_updated',
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'new_values' => json_encode($validated),
        ]);

        return Redirect::back()->with('success', 'Détails du fichier mis à jour.');
    }

    /**
     * Supprime un fichier.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $asset = Asset::findOrFail($id);

        // Suppression physique
        if (Storage::disk('public')->exists($asset->path)) {
            Storage::disk('public')->delete($asset->path);
        }

        $asset->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'asset_deleted',
            'auditable_type' => Asset::class,
            'auditable_id' => $id,
        ]);

        return Redirect::back()->with('success', 'Fichier supprimé définitivement.');
    }

    /**
     * Suppression de masse.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $assets = Asset::whereIn('id', $request->ids)->get();
        $count = 0;

        foreach ($assets as $asset) {
            if (Storage::disk('public')->exists($asset->path)) {
                Storage::disk('public')->delete($asset->path);
            }
            $asset->delete();
            $count++;
        }

        return Redirect::back()->with('success', "$count fichiers supprimés.");
    }

    /**
     * Helper pour formater la taille des fichiers.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
