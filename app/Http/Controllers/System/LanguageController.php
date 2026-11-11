<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LanguageController extends Controller
{
    /**
     * Noms d'affichage connus (fallback : code en majuscules).
     */
    private const DISPLAY_NAMES = [
        'fr' => 'Français',
        'en' => 'English',
        'de' => 'Deutsch',
        'sw' => 'Kiswahili',
        'ln' => 'Lingála',
    ];

    /**
     * Drapeaux emoji par code (fallback : globe).
     */
    private const FLAGS = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'de' => '🇩🇪',
        'sw' => '🇹🇿',
        'ln' => '🇨🇩',
    ];

    public function index()
    {
        $this->syncDiscoveredLocales();

        $reference = 'fr';
        $referenceKeys = $this->localeKeyCount($reference);

        $languages = Language::orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function (Language $lang) use ($reference, $referenceKeys) {
                $keys = $this->localeKeyCount($lang->code);

                return [
                    'id'         => $lang->id,
                    'name'       => $lang->name,
                    'code'       => $lang->code,
                    'flag'       => self::FLAGS[$lang->code] ?? '🌍',
                    'is_active'  => (bool) $lang->is_active,
                    'is_default' => (bool) $lang->is_default,
                    'has_file'   => $this->localePath($lang->code) !== null,
                    'keys'       => $keys,
                    'progress'   => $referenceKeys > 0 ? min(100, (int) round($keys / $referenceKeys * 100)) : 0,
                ];
            })
            ->values();

        $activeLanguages = $languages->where('is_active', true);
        $completion = $activeLanguages->avg('progress');

        return Inertia::render('System/Languages', [
            'languages' => $languages,
            'stats'     => [
                'total_keys'   => $referenceKeys,
                'active'       => $activeLanguages->count(),
                'installed'    => $languages->count(),
                'completion'   => $completion !== null ? (int) round($completion) : 0,
                'default'      => optional($languages->firstWhere('is_default', true))->name,
            ],
            'current'   => app()->getLocale(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:2', 'max:8', 'unique:languages,code', 'regex:/^[a-z]+$/'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $language = Language::create([
            'code'       => strtolower($validated['code']),
            'name'       => $validated['name'],
            'is_active'  => true,
            'is_default' => false,
        ]);

        // Crée un fichier de traduction vide si absent (actif après rebuild Vite).
        $path = $this->localePath($language->code);
        if ($path === null) {
            $this->writeLocale($language->code, new \stdClass());
        }

        return back()->with('success', "Langue « {$language->name} » ajoutée. Lancez npm run build pour la charger côté front.");
    }

    public function toggle(Request $request)
    {
        $language = Language::findOrFail($request->input('id'));

        if ($language->is_default && $language->is_active) {
            return back()->with('error', 'Impossible de masquer la langue par défaut.');
        }

        $language->update(['is_active' => ! $language->is_active]);

        return back()->with('success', $language->is_active
            ? "« {$language->name} » est maintenant visible."
            : "« {$language->name} » est masquée.");
    }

    public function setDefault(Request $request)
    {
        $language = Language::findOrFail($request->input('id'));
        $language->update(['is_default' => true, 'is_active' => true]);
        Language::where('id', '!=', $language->id)->update(['is_default' => false]);

        return back()->with('success', "« {$language->name} » est la langue par défaut.");
    }

    public function destroy(Request $request, string $id)
    {
        $language = Language::findOrFail($id);

        if ($language->is_default) {
            return back()->with('error', 'Impossible de supprimer la langue par défaut.');
        }

        $language->delete();

        return back()->with('success', 'Langue supprimée.');
    }

    /**
     * Clés de traduction d'une locale (pagination + recherche), avec la référence FR.
     */
    public function keys(Request $request, string $id)
    {
        $language = Language::findOrFail($id);
        $search = mb_strtolower(trim($request->input('search', '')));
        $perPage = 50;
        $page = max(1, (int) $request->input('page', 1));

        $reference = $this->flatten($this->readLocale('fr'));
        $translations = $this->flatten($this->readLocale($language->code));

        $rows = collect($reference)
            ->map(fn ($frValue, $key) => [
                'key'       => $key,
                'fr'        => $frValue,
                'value'     => $translations[$key] ?? null,
                'missing'   => ! array_key_exists($key, $translations),
            ])
            // Clés présentes uniquement dans la locale gérée (orphelines)
            ->merge(collect($translations)
                ->diffKeys($reference)
                ->map(fn ($value, $key) => [
                    'key' => $key, 'fr' => null, 'value' => $value, 'missing' => false,
                ]))
            ->filter(function ($row) use ($search) {
                if ($search === '') return true;

                return str_contains(mb_strtolower($row['key']), $search)
                    || str_contains(mb_strtolower((string) $row['fr']), $search)
                    || str_contains(mb_strtolower((string) $row['value']), $search);
            })
            ->sortBy('key', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $total = $rows->count();
        $missing = $rows->where('missing')->count();
        $data = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return Inertia::render('System/Languages', [
            'editor' => [
                'language' => [
                    'id' => $language->id, 'name' => $language->name,
                    'code' => $language->code, 'flag' => self::FLAGS[$language->code] ?? '🌍',
                ],
                'keys'       => $data,
                'total'      => $total,
                'missing'    => $missing,
                'page'       => $page,
                'per_page'   => $perPage,
                'last_page'  => (int) max(1, ceil($total / $perPage)),
                'search'     => $search,
            ],
        ]);
    }

    /**
     * Met à jour la valeur d'une clé dans le fichier JSON de la locale.
     */
    public function updateKey(Request $request, string $id)
    {
        $validated = $request->validate([
            'key'   => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:10000'],
        ]);

        $language = Language::findOrFail($id);
        $path = $this->localePath($language->code);
        if ($path === null) {
            return back()->with('error', "Aucun fichier de traduction pour « {$language->code} ».");
        }

        $data = $this->readLocale($language->code);
        $flat = $this->flatten($this->readLocale('fr'));
        if (! array_key_exists($validated['key'], $flat) && ! $this->keyExistsIn($data, $validated['key'])) {
            return back()->with('error', 'Clé inconnue : ' . $validated['key']);
        }

        $this->setNestedValue($data, explode('.', $validated['key']), (string) $validated['value']);
        $this->writeLocale($language->code, $data);

        return back()->with('success', 'Clé mise à jour. Rebuild Vite nécessaire pour le front.');
    }

    /**
     * Importe un fichier JSON (remplace le contenu de la locale).
     */
    public function import(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:8'],
            'file' => ['required', 'file', 'mimes:json,txt', 'max:2048'],
        ]);

        $code = strtolower($request->input('code'));
        if (! preg_match('/^[a-z]+$/', $code)) {
            return back()->with('error', 'Code de langue invalide.');
        }

        $content = json_decode($request->file('file')->getContent(), true);
        if (! is_array($content)) {
            return back()->with('error', 'Fichier JSON invalide.');
        }

        $this->writeLocale($code, $content);

        if (! Language::where('code', $code)->exists()) {
            Language::create([
                'code'      => $code,
                'name'      => self::DISPLAY_NAMES[$code] ?? strtoupper($code),
                'is_active' => true,
            ]);
        }

        return back()->with('success', "Traductions « {$code} » importées. Rebuild Vite nécessaire pour le front.");
    }

    /**
     * Télécharge le fichier de traduction d'une locale.
     */
    public function export(string $id)
    {
        $language = Language::findOrFail($id);
        $json = json_encode(
            $this->readLocale($language->code),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return response()->streamDownload(
            fn () => print($json),
            "translations_{$language->code}.json",
            ['Content-Type' => 'application/json']
        );
    }

    /* --------------------------------------------------------------------- */
    /* Helpers fichiers de locales                                            */
    /* --------------------------------------------------------------------- */

    private function syncDiscoveredLocales(): void
    {
        foreach (glob(resource_path('js/locales/*.json')) ?: [] as $file) {
            $code = strtolower(pathinfo($file, PATHINFO_FILENAME));
            if (preg_match('/^[a-z]+$/', $code) && ! Language::where('code', $code)->exists()) {
                Language::create([
                    'code'      => $code,
                    'name'      => self::DISPLAY_NAMES[$code] ?? strtoupper($code),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function localePath(string $code): ?string
    {
        $path = resource_path("js/locales/{$code}.json");

        return is_file($path) ? $path : null;
    }

    private function readLocale(string $code): array
    {
        $path = $this->localePath($code);
        if ($path === null) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeLocale(string $code, $data): void
    {
        file_put_contents(
            resource_path("js/locales/{$code}.json"),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    private function localeKeyCount(string $code): int
    {
        return count($this->flatten($this->readLocale($code)));
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value) && $value !== []) {
                $result += $this->flatten($value, $path);
            } else {
                $result[$path] = is_array($value) ? '' : $value;
            }
        }

        return $result;
    }

    private function keyExistsIn(array $data, string $dotKey): bool
    {
        $node = $data;
        foreach (explode('.', $dotKey) as $segment) {
            if (! is_array($node) || ! array_key_exists($segment, $node)) {
                return false;
            }
            $node = $node[$segment];
        }

        return true;
    }

    private function setNestedValue(array &$data, array $segments, string $value): void
    {
        $node = &$data;
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $node[$segment] = $value;

                return;
            }
            if (! isset($node[$segment]) || ! is_array($node[$segment])) {
                $node[$segment] = [];
            }
            $node = &$node[$segment];
        }
    }
}
