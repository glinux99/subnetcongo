<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload d'image pour les écrans administrateurs (réalisations, blog, certificats, etc.).
     * Stocké sur le disque « media » (public/media) — public_path('media').
     * Renvoie le chemin public à utiliser comme valeur du champ.
     */
    public function image(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,avif,svg|max:6144',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $slug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') ?: 'image';
        $name = 'uploads/' . now()->format('Ym') . '/' . Str::lower(uniqid()) . '_' . $slug . '.' . $ext;

        $file->storeAs('', $name, 'media');

        return response()->json([
            'path' => Storage::disk('media')->url($name),
            'url'  => Storage::disk('media')->url($name),
        ]);
    }
}
