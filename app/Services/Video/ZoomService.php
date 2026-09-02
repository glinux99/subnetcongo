<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ZoomService
{
    protected string $base = 'https://api.zoom.us/v2';
    protected string $oauthBase = 'https://zoom.us/oauth/token';

    /**
     * Récupère un access_token Server-to-Server OAuth.
     * Compte: $ZOOM_ACCOUNT_ID (UUID)
     * Client: $ZOOM_CLIENT_ID + $ZOOM_CLIENT_SECRET
     * Scope: meeting:write
     */
    public function getAccessToken(): string
    {
        return Cache::remember('zoom_access_token', 3500, function () {
            $accountId = config('services.zoom.account_id');
            $clientId = config('services.zoom.client_id');
            $clientSecret = config('services.zoom.client_secret');

            if (!$accountId || !$clientId || !$clientSecret) {
                throw new Exception('Identifiants Zoom manquants (ZOOM_ACCOUNT_ID/ZOOM_CLIENT_ID/ZOOM_CLIENT_SECRET).');
            }

            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($this->oauthBase, [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId,
                ]);

            if ($response->failed()) {
                Log::error('Zoom token error', ['body' => $response->body()]);
                throw new Exception('Impossible d\'obtenir un access_token Zoom.');
            }

            return $response->json('access_token');
        });
    }

    public function createMeeting(array $data): array
    {
        // Mode démo : pas de credentials Zoom configurés → génère un faux meeting_id utilisable.
        if (!config('services.zoom.client_id')) {
            return $this->mockMeeting($data, 'zoom');
        }

        $token = $this->getAccessToken();
        $payload = [
            'topic' => $data['title'],
            'type' => 2, // scheduled
            'start_time' => date('Y-m-d\TH:i:s', strtotime($data['start_at'])),
            'duration' => $data['duration'] ?? 60,
            'timezone' => 'UTC',
            'agenda' => $data['description'] ?? null,
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'waiting_room' => true,
                'audio' => 'both',
                'auto_recording' => ($data['is_recorded'] ?? false) ? 'cloud' : 'none',
                'approval_type' => 2,
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->base}/users/me/meetings", $payload);

        if ($response->failed()) {
            Log::error('Zoom create meeting failed', ['body' => $response->body()]);
            throw new Exception('Création de réunion Zoom échouée.');
        }

        $json = $response->json();
        return [
            'id' => (string) $json['id'],
            'join_url' => $json['join_url'],
            'start_url' => $json['start_url'],
            'password' => $json['password'] ?? null,
            'provider' => 'zoom',
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        if (!config('services.zoom.client_id')) {
            return $this->mockMeeting($data, 'zoom', $meetingId);
        }

        $token = $this->getAccessToken();
        $payload = [
            'topic' => $data['title'],
            'start_time' => date('Y-m-d\TH:i:s', strtotime($data['start_at'])),
            'duration' => $data['duration'] ?? 60,
        ];
        $response = Http::withToken($token)->patch("{$this->base}/meetings/{$meetingId}", $payload);
        if ($response->failed()) {
            throw new Exception('Mise à jour Zoom échouée.');
        }
        $meeting = Http::withToken($token)->get("{$this->base}/meetings/{$meetingId}")->json();
        return [
            'id' => (string) $meeting['id'],
            'join_url' => $meeting['join_url'],
            'start_url' => $meeting['start_url'] ?? null,
            'password' => $meeting['password'] ?? null,
        ];
    }

    public function deleteMeeting(string $meetingId): void
    {
        if (!config('services.zoom.client_id')) {
            return;
        }
        $token = $this->getAccessToken();
        Http::withToken($token)->delete("{$this->base}/meetings/{$meetingId}");
    }

    public function listRecordings(string $meetingId): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)->get("{$this->base}/meetings/{$meetingId}/recordings");
        return $response->json('recording_files') ?? [];
    }

    private function mockMeeting(array $data, string $provider, ?string $id = null): array
    {
        $id ??= (string) (int) (microtime(true) * 1000);
        return [
            'id' => $id,
            'join_url' => "https://{$provider}.com/j/" . $id,
            'start_url' => "https://{$provider}.com/s/" . $id,
            'password' => substr(str_shuffle('0123456789'), 0, 6),
            'provider' => $provider,
            'mock' => true,
        ];
    }
}
