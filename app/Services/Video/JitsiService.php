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
}
