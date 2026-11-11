<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssetController extends Controller
{
    /**
     * Disques parcourus : « media » (public/media) et « public » (storage/app/public).
     */
    private const DISKS = ['media', 'public'];

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
                ->map(function ($path) use ($diskName) {
                    $full = Storage::disk($diskName)->path($path);
                    $mime = mime_content_type($full) ?: 'application/octet-stream';
                    $size = filesize($full);

                    return [
                        'disk'       => $diskName,
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
                        'url'        => Storage::disk($diskName)->url($path),
                    ];
                });

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
