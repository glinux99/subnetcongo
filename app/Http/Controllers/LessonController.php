<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LessonController extends Controller
{
    /**
     * Créer une nouvelle leçon dans un module.
     *
     * @param  Request  $request
     * @param  Module   $module
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Module $module)
    {
        // Vérifier que l'utilisateur a le droit d'éditer le cours parent
        // $this->authorize('update', $module->course);

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'type'               => ['required', Rule::in(['video', 'document', 'quiz', 'assignment'])],
            'content'            => 'nullable|string',
            'video_url'          => 'nullable|url|max:500',
            'video_source'       => ['nullable', Rule::in(['youtube', 'vimeo', 'custom'])],
            'duration_minutes'   => 'nullable|integer|min:0',
            'is_free_preview'    => 'boolean',
            'status'             => ['required', Rule::in(['draft', 'published'])],
            'attachment_paths'   => 'nullable|array',
            'attachment_paths.*' => 'nullable',
            'seo_metadata'       => 'nullable|array',
            'seo_metadata.title' => 'nullable|string|max:70',
            'seo_metadata.description' => 'nullable|string|max:160',
            'seo_metadata.keywords'    => 'nullable|string|max:255',
        ]);

        // Générer un slug unique basé sur le titre
        $slug = $this->generateUniqueSlug($validated['title']);

        // Déterminer l'ordre (dernier +1)
        $maxOrder = $module->lessons()->max('order') ?? 0;

        $attachmentPaths = [];
        $inputPaths = $request->input('attachment_paths');
        foreach (is_array($inputPaths) ? $inputPaths : [] as $item) {
            if (is_string($item)) $attachmentPaths[] = $item;
        }

        $files = $request->file('attachment_paths');
        foreach (is_array($files) ? $files : ($files ? [$files] : []) as $file) {
            $attachmentPaths[] = $file->store('lessons/attachments', 'media');
        }

        $lesson = $module->lessons()->create([
            'title'            => $validated['title'],
            'slug'             => $slug,
            'type'             => $validated['type'],
            'content'          => $validated['content'] ?? null,
            'video_url'        => $validated['video_url'] ?? null,
            'video_source'     => $validated['video_source'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'is_free_preview'  => $validated['is_free_preview'] ?? false,
            'status'           => $validated['status'],
            'order'            => $maxOrder + 1,
            'attachment_paths' => $attachmentPaths,
            'seo_metadata'     => $validated['seo_metadata'] ?? [],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Leçon créée avec succès.',
                'lesson'  => $lesson->load('module'),
            ], 201);
        }

        return back()->with('success', 'Leçon créée avec succès.');
    }

    /**
     * Mettre à jour une leçon existante.
     *
     * @param  Request  $request
     * @param  Lesson   $lesson
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Lesson $lesson)
    {
        // $this->authorize('update', $lesson->module->course);

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'type'               => ['required', Rule::in(['video', 'document', 'quiz', 'assignment'])],
            'content'            => 'nullable|string',
            'video_url'          => 'nullable|url|max:500',
            'video_source'       => ['nullable', Rule::in(['youtube', 'vimeo', 'custom'])],
            'duration_minutes'   => 'nullable|integer|min:0',
            'is_free_preview'    => 'boolean',
            'status'             => ['required', Rule::in(['draft', 'published'])],
            'attachment_paths'   => 'nullable|array',
            'attachment_paths.*' => 'nullable', // Accepte string (existant) ou File (nouveau)
            'seo_metadata'       => 'nullable|array',
            'seo_metadata.title' => 'nullable|string|max:70',
            'seo_metadata.description' => 'nullable|string|max:160',
            'seo_metadata.keywords'    => 'nullable|string|max:255',
        ]);

        // Mettre à jour le slug seulement si le titre a changé
        if ($lesson->title !== $validated['title']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $lesson->id);
        }


      $attachmentPaths = [];

            // Existing string paths from input
            $inputPaths = $request->input('attachment_paths', []);
            foreach ((array) $inputPaths as $item) {
                if (is_string($item)) {
                    $attachmentPaths[] = $item;
                }
            }

            // Uploaded files
            $files = $request->file('attachment_paths');
            if ($files) {
                foreach ((array) $files as $file) {
                    // Ensure it's a valid uploaded file object
                    if ($file && $file->isValid()) {
                        $attachmentPaths[] = $file->store('lessons/attachments', 'media');
                    }
                }
            }

        $lesson->update([
            'title'            => $validated['title'],
            'type'             => $validated['type'],
            'content'          => $validated['content'] ?? $lesson->content,
            'video_url'        => $validated['video_url'] ?? $lesson->video_url,
            'video_source'     => $validated['video_source'] ?? $lesson->video_source,
            'duration_minutes' => $validated['duration_minutes'] ?? $lesson->duration_minutes,
            'is_free_preview'  => $validated['is_free_preview'] ?? $lesson->is_free_preview,
            'status'           => $validated['status'],
            'attachment_paths' => $attachmentPaths,
            'seo_metadata'     => $validated['seo_metadata'] ?? $lesson->seo_metadata,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Leçon mise à jour avec succès.',
                'lesson'  => $lesson->fresh('module'),
            ]);
        }

        return back()->with('success', 'Leçon mise à jour avec succès.');
    }

    /**
     * Supprimer une leçon (soft delete).
     *
     * @param  Lesson  $lesson
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Lesson $lesson)
    {
        // $this->authorize('update', $lesson->module->course);

        $lesson->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Leçon supprimée (archivée).',
            ]);
        }
        return back()->with('success', 'Leçon supprimée.');
    }

    /**
     * Réordonner les leçons d'un module.
     *
     * @param  Request  $request
     * @param  Module   $module
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request, Module $module)
    {
        // $this->authorize('update', $module->course);

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id'    => 'required|exists:lessons,id',
            'orders.*.order' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->orders as $item) {
                Lesson::where('id', $item['id'])
                    ->where('module_id', $module->id)
                    ->update(['order' => $item['order']]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Ordre des leçons mis à jour avec succès.',
            ]);
        }

        return back()->with('success', 'Ordre des leçons mis à jour.');
    }

    /**
     * Générer un slug unique pour une leçon.
     *
     * @param  string   $title
     * @param  int|null $ignoreId  ID de la leçon à ignorer (pour les mises à jour)
     * @return string
     */
    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (Lesson::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
