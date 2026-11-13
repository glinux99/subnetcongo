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

    /**
     * Upload d'image ou de vidéo (téléphone/ordinateur) pour les sélecteurs
     * de médias (éditeurs riches, forums…). Disque « media » (public/media).
     */
    public function media(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,avif,svg,mp4,webm,ogg',
                'max:51200',
            ],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $slug = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') ?: 'media';
        $kind = in_array($ext, ['mp4', 'webm', 'ogg']) ? 'video' : 'image';
        $name = 'uploads/' . now()->format('Ym') . '/' . Str::lower(uniqid()) . '_' . $slug . '.' . $ext;

        $file->storeAs('', $name, 'media');
        $url = Storage::disk('media')->url($name);

        return response()->json([
            'url'  => $url,
            'path' => $name,
            'type' => $kind,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }
}
