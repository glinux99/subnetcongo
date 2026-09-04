<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LanguageController extends Controller
{
    public function index()
    {
        $locales = config('app.locales', ['fr', 'en', 'de', 'sw', 'ln']);
        $current = app()->getLocale();

        return Inertia::render('System/Languages', [
            'locales'    => $locales,
            'current'    => $current,
        ]);
    }

    public function create()
    {
        return Inertia::render('System/Languages', [
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:2|unique:languages,code',
            'name' => 'required|string|max:100',
        ]);

        $lang = \App\Models\Language::create([
            'code' => strtolower($request->input('code')),
            'name' => $request->input('name'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Langue ajoutée avec succès.');
    }

    public function show(string $id)
    {
        $lang = \App\Models\Language::findOrFail($id);

        return Inertia::render('System/Languages', [
            'language' => $lang,
            'mode' => 'show',
        ]);
    }

    public function edit(string $id)
    {
        $lang = \App\Models\Language::findOrFail($id);

        return Inertia::render('System/Languages', [
            'language' => $lang,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, string $id)
    {
        $lang = \App\Models\Language::findOrFail($id);

        $request->validate([
            'code' => 'required|string|size:2|unique:languages,code,' . $id,
            'name' => 'required|string|max:100',
        ]);

        $lang->update([
            'code' => strtolower($request->input('code')),
            'name' => $request->input('name'),
        ]);

        return back()->with('success', 'Langue mise à jour.');
    }

    public function destroy(string $id)
    {
        $lang = \App\Models\Language::findOrFail($id);
        $lang->delete();

        return back()->with('success', 'Langue supprimée.');
    }
}
