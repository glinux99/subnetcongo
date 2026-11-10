<?php

namespace App\Services\Video;

use Illuminate\Support\Str;

class JitsiService
{
    public function createMeeting(array $data): array
    {
        $roomName = 'subnetcongo-' . Str::slug($data['title']) . '-' . Str::lower(Str::random(6));
        $base = config('services.jitsi.base_url', 'https://meet.jit.si');
        return [
            'id' => $roomName,
            'join_url' => "{$base}/{$roomName}#config.prejoinPageEnabled=false",
            'start_url' => "{$base}/{$roomName}",
            'password' => null,
            'provider' => 'jitsi',
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        // Jitsi ne conserve pas d'état serveur ; on régénère avec un nom stable
        $data['title'] = $meetingId;
        return $this->createMeeting($data);
    }

    public function deleteMeeting(string $meetingId): void
    {
        // No-op : Jitsi rooms expirent automatiquement
    }

    public function isConfigured(): bool
    {
        return filled(config('services.jitsi.base_url'));
    }

    public function testConnection(): array
    {
        $base = rtrim((string) config('services.jitsi.base_url'), '/');

        if (! $base) {
            return ['success' => false, 'message' => 'URL Jitsi manquante.'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($base);

            return $response->successful()
                ? ['success' => true, 'message' => 'Serveur Jitsi joignable (HTTP ' . $response->status() . ').']
                : ['success' => false, 'message' => 'Jitsi a répondu avec le code ' . $response->status() . '.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Serveur Jitsi injoignable : ' . $e->getMessage()];
        }
    }
}
