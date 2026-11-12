<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssetController extends Controller
{
    /**
     * Disques parcourus : « media » (public/media) et « public » (storage/app/public).
     */
    private const DISKS = ['media', 'public'];

    /**
     * Tables système à exclure du scan d'utilisation.
     */
    private const USAGE_SKIP_TABLES = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'password_reset_tokens', 'personal_access_tokens',
        'notifications', 'import_batches', 'export_batches',
    ];

    /**
     * Colonnes texte dont le nom suggère un chemin/fichier de média.
     */
    private const USAGE_NAME_PATTERN = '/(^|_)(path|image|photo|avatar|logo|thumbnail|thumb|cover|file|attachment|content|background|signature|icon|banner|slide|document|video|audio|media|pdf|picture)(_|$)/i';

    /**
     * Médiathèque : liste les fichiers réellement présents sur les disques
     * media et public, avec recherche, filtre par type et pagination.
     */
    public function index(Request $request)
    {
        $disk   = in_array($request->input('disk'), self::DISKS) ? $request->input('disk') : 'all';
        $search = mb_strtolower(trim($request->input('search', '')));
        $type   = $request->input('type', 'all'); // image|video|audio|document|other
        $page   = max(1, (int) $request->input('page', 1));
        $perPage = 24;

        $all = [];
        $stats = ['total_size' => 0, 'count' => 0, 'by_disk' => []];

        foreach (self::DISKS as $diskName) {
            if ($disk !== 'all' && $disk !== $diskName) {
                continue;
            }

            $files = collect(Storage::disk($diskName)->allFiles())
                ->reject(fn ($path) => str_starts_with($path, '.'))
                ->map(fn ($path) => $this->assetInfo($diskName, $path));

            $stats['by_disk'][$diskName] = [
                'count' => $files->count(),
                'size'  => $this->formatBytes($files->sum('size')),
            ];
            $stats['total_size'] += $files->sum('size');
            $stats['count'] += $files->count();

            $all = array_merge($all, $files->all());
        }

        $all = collect($all)
            ->when($search !== '', fn ($c) => $c->filter(fn ($f) =>
                str_contains(mb_strtolower($f['name']), $search)
                || str_contains(mb_strtolower($f['folder']), $search)))
            ->when($type !== 'all', fn ($c) => $c->filter(function ($f) use ($type) {
                return match ($type) {
                    'image'    => $f['is_image'],
                    'video'    => $f['is_video'],
                    'audio'    => $f['is_audio'],
                    'document' => in_array($f['extension'], ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv']),
                    'archive'  => in_array($f['extension'], ['zip', 'rar', '7z', 'tar', 'gz']),
                    default    => true,
                };
            }))
            ->sortByDesc('modified')
            ->values();

        $total = $all->count();
        $stats['total_size'] = $this->formatBytes($stats['total_size']);

        return Inertia::render('Web/Assets', [
            'assets' => [
                'data'      => $all->slice(($page - 1) * $perPage, $perPage)->values(),
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
            'filters' => $request->all(['disk', 'search', 'type']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Détecte si des fichiers sont référencés dans la base (colonnes texte
     * candidates, map mise en cache 1 h). Retourne
     * { « disk:path » => [ « table.colonne », ... ] }.
     */
    public function usage(Request $request)
    {
        $keys = collect((array) $request->input('keys', []))
            ->map(fn ($k) => explode(':', (string) $k, 2))
            ->filter(fn ($p) => count($p) === 2 && in_array($p[0], self::DISKS, true) && is_string($p[1]) && $p[1] !== '')
            ->take(60)
            ->values();

        $refs = $this->usageRefs($keys->map(fn ($p) => basename($p[1]))->unique()->values()->all());

        $out = [];
        foreach ($keys as [$disk, $path]) {
            $out[$disk . ':' . $path] = $refs[basename($path)] ?? [];
        }

        return response()->json(['usage' => $out]);
    }

    /**
     * Upload de fichiers sur un disque choisi, dans un dossier optionnel.
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'disk'          => ['required', Rule::in(self::DISKS)],
            'folder'        => ['nullable', 'string', 'max:200'],
            'files'         => ['required', 'array', 'min:1', 'max:10'],
            'files.*'       => ['file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mp3,wav,ogg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,tar,gz,txt,csv'],
        ]);

        $folder = $this->sanitizeFolder($validated['folder'] ?? '');
        $stored = [];

        foreach ($request->file('files') as $file) {
            $name = $this->safeFileName($file->getClientOriginalName());
            $path = $this->uniquePath($validated['disk'], ($folder === '' ? '' : $folder . '/') . $name);
            $dir  = dirname($path) === '.' ? '' : dirname($path);

            Storage::disk($validated['disk'])->putFileAs($dir, $file, basename($path));
            $stored[] = $this->assetInfo($validated['disk'], $path);
        }

        return response()->json(['ok' => true, 'files' => $stored]);
    }

    /**
     * Renomme et/ou déplace un fichier (même disque).
     */
    public function rename(Request $request)
    {
        $validated = $request->validate([
            'disk'   => ['required', Rule::in(self::DISKS)],
            'path'   => ['required', 'string', 'max:500'],
            'name'   => ['nullable', 'string', 'max:150'],
            'folder' => ['nullable', 'string', 'max:300'],
        ]);

        $disk = $validated['disk'];
        $path = $this->safePath($validated['path']);

        if (!Storage::disk($disk)->exists($path)) {
            return response()->json(['ok' => false, 'message' => 'Fichier introuvable.'], 404);
        }

        $oldName = basename($path);
        $oldExt  = pathinfo($oldName, PATHINFO_EXTENSION);

        $newName = isset($validated['name']) ? $this->safeFileName($validated['name']) : $oldName;
        if (pathinfo($newName, PATHINFO_EXTENSION) === '' && $oldExt !== '') {
            $newName .= '.' . $oldExt;
        }

        $dir       = dirname($path) === '.' ? '' : dirname($path);
        $newFolder = $this->sanitizeFolder($validated['folder'] ?? $dir);
        $newPath   = ($newFolder === '' ? '' : $newFolder . '/') . $newName;

        if ($newPath === $path) {
            return response()->json(['ok' => true, 'asset' => $this->assetInfo($disk, $path)]);
        }

        if (Storage::disk($disk)->exists($newPath)) {
            return response()->json(['ok' => false, 'message' => 'Un fichier porte déjà ce nom à cet emplacement.'], 422);
        }

        Storage::disk($disk)->move($path, $newPath);

        return response()->json(['ok' => true, 'asset' => $this->assetInfo($disk, $newPath)]);
    }

    /**
     * Suppression (une ou plusieurs entrées). Bloque par défaut si un fichier
     * est référencé dans la BDD, sauf force=true.
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'items'         => ['required', 'array', 'min:1', 'max:100'],
            'items.*.disk'  => ['required', Rule::in(self::DISKS)],
            'items.*.path'  => ['required', 'string', 'max:500'],
            'force'         => ['nullable', 'boolean'],
        ]);

        if (!($validated['force'] ?? false)) {
            $refs = $this->usageRefs(collect($validated['items'])->map(fn ($i) => basename($i['path']))->unique()->values()->all());

            $blocked = [];
            foreach ($validated['items'] as $item) {
                $usages = $refs[basename($item['path'])] ?? [];
                if ($usages) {
                    $blocked[] = ['path' => $item['path'], 'disk' => $item['disk'], 'usages' => $usages];
                }
            }

            if ($blocked) {
                return response()->json(['ok' => false, 'blocked' => $blocked], 409);
            }
        }

        $removed = 0;
        foreach ($validated['items'] as $item) {
            $path = $this->safePath($item['path']);
            if (Storage::disk($item['disk'])->exists($path) && Storage::disk($item['disk'])->delete($path)) {
                $removed++;
            }
        }

        return response()->json(['ok' => true, 'removed' => $removed]);
    }

    /**
     * Références BDD d'un ensemble de noms de fichiers (basename).
     *
     * @return array<string, list<string>> basename => [ « table.colonne », ... ]
     */
    private function usageRefs(array $names): array
    {
        $names = array_values(array_filter(array_unique($names)));
        $refs  = array_fill_keys($names, []);
        if (!$names) {
            return $refs;
        }

        foreach ($this->usageColumns() as $table => $columns) {
            foreach ($columns as $column) {
                try {
                    $rows = DB::table($table)
                        ->where(function ($q) use ($column, $names) {
                            foreach ($names as $name) {
                                $q->orWhere($column, 'like', '%' . $name . '%');
                            }
                        })
                        ->limit(80)
                        ->pluck($column)
                        ->all();
                } catch (\Throwable) {
                    continue; // table indisponible (vue, erreur...)
                }

                foreach ($rows as $value) {
                    $value = (string) $value;
                    foreach ($names as $name) {
                        if (stripos($value, $name) !== false) {
                            $refs[$name][$table . '.' . $column] = true;
                        }
                    }
                }
            }
        }

        return array_map(fn ($hits) => array_keys($hits), $refs);
    }

    /**
     * Map « table => [colonnes texte candidates] », mise en cache 1 h.
     */
    private function usageColumns(): array
    {
        return Cache::remember('assets_usage_columns', 3600, function () {
            $map = [];
            $tables = collect(DB::select('SHOW TABLES'))->map(fn ($t) => array_values((array) $t)[0]);

            foreach ($tables as $table) {
                if (in_array($table, self::USAGE_SKIP_TABLES, true)) {
                    continue;
                }
                try {
                    $columns = [];
                    foreach (DB::select("SHOW COLUMNS FROM `{$table}`") as $column) {
                        $type = strtolower($column->Type);
                        $name = $column->Field;
                        $isText = str_starts_with($type, 'varchar') || str_starts_with($type, 'char')
                            || str_contains($type, 'text') || str_starts_with($type, 'json');
                        if ($isText && preg_match(self::USAGE_NAME_PATTERN, $name)) {
                            $columns[] = $name;
                        }
                    }
                    if ($columns) {
                        $map[$table] = $columns;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            return $map;
        });
    }

    /**
     * Détail normalisé d'un fichier (utilisé par index/upload/rename).
     */
    private function assetInfo(string $disk, string $path): array
    {
        $full = Storage::disk($disk)->path($path);
        $mime = mime_content_type($full) ?: 'application/octet-stream';
        $size = filesize($full) ?: 0;

        return [
            'disk'       => $disk,
            'path'       => $path,
            'name'       => basename($path),
            'folder'     => dirname($path) === '.' ? '' : dirname($path),
            'extension'  => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'mime_type'  => $mime,
            'size'       => $size,
            'human_size' => $this->formatBytes($size),
            'modified'   => date('d/m/Y H:i', filemtime($full)),
            'is_image'   => str_starts_with($mime, 'image/'),
            'is_video'   => str_starts_with($mime, 'video/'),
            'is_audio'   => str_starts_with($mime, 'audio/'),
            'url'        => Storage::disk($disk)->url($path),
        ];
    }

    /**
     * Nettoie une clé de dossier éventuellement fournie par l'utilisateur :
     * segments « .. » rejetés, caractères restreints, profondeur limitée.
     */
    private function sanitizeFolder(?string $folder): string
    {
        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        if ($folder === '') {
            return '';
        }

        $segments = [];
        foreach (explode('/', $folder) as $segment) {
            $segment = trim(preg_replace('/[^\w\-. ]/u', '', $segment));
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', array_slice($segments, 0, 8));
    }

    /**
     * Nom de fichier sûr (pas de chemins, pas de « . » initial).
     */
    private function safeFileName(string $name): string
    {
        $name = pathinfo(str_replace('\\', '/', $name), PATHINFO_BASENAME);
        $name = preg_replace('/[^\w\-.]+/u', '-', $name);
        $name = trim($name, '.-') ?: 'fichier';

        return mb_substr($name, 0, 120);
    }

    /**
     * Chemin relatif sûr : rejette tout segment « .. ».
     */
    private function safePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                abort(422, 'Chemin invalide.');
            }
        }

        return $path;
    }

    /**
     * -1, -2... si le chemin existe déjà (collision de nom).
     */
    private function uniquePath(string $disk, string $path): string
    {
        if (!Storage::disk($disk)->exists($path)) {
            return $path;
        }

        $dir  = dirname($path) === '.' ? '' : dirname($path) . '/';
        $name = basename($path);
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);

        for ($i = 1; $i < 1000; $i++) {
            $candidate = $dir . $base . '-' . $i . ($ext !== '' ? '.' . $ext : '');
            if (!Storage::disk($disk)->exists($candidate)) {
                return $candidate;
            }
        }

        abort(422, 'Impossible de trouver un nom disponible.');
    }

    private function formatBytes($bytes, $precision = 1)
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
