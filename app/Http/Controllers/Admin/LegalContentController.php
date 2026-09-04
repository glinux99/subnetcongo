<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LegalContentController extends Controller
{
    public function index()
    {
        $legal = WebSetting::where('group', 'legal')->get()->map(fn ($r) => [
            'key' => $r->key,
            'value' => $r->value,
            'type' => $r->type,
        ])->toArray();

        $privacy = WebSetting::where('group', 'privacy')->get()->map(fn ($r) => [
            'key' => $r->key,
            'value' => $r->value,
            'type' => $r->type,
        ])->toArray();

        return Inertia::render('Admin/LegalContent/Index', [
            'legal' => $legal,
            'privacy' => $privacy,
        ]);
    }

    public function update(Request $request, string $group)
    {
        $request->validate([
            '*.key' => 'required|string',
            '*.value' => 'nullable|string',
        ]);

        $data = $request->all();
        foreach ($data as $item) {
            if (!isset($item['key'])) continue;

            WebSetting::updateOrCreate(
                ['key' => $item['key'], 'group' => $group],
                ['value' => $item['value'] ?? '', 'type' => 'html']
            );
        }

        return Redirect::back()->with('success', 'Contenu ' . ($group === 'legal' ? 'légal' : 'confidentialité') . ' mis à jour.');
    }
}
