<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleMeetService
{
    protected string $apiBase = 'https://www.googleapis.com/calendar/v3';

    public function createMeeting(array $data): array
    {
        $token = $this->getAccessToken();
        $startTime = date('c', strtotime($data['start_at']));
        $endTime = date('c', strtotime($data['start_at']) + ($data['duration'] ?? 60) * 60);

        $response = Http::withToken($token)
            ->post($this->eventsUrl() . '?conferenceDataVersion=1', [
                'summary' => $data['title'],
                'description' => $data['description'] ?? null,
                'start' => ['dateTime' => $startTime, 'timeZone' => 'UTC'],
                'end' => ['dateTime' => $endTime, 'timeZone' => 'UTC'],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => uniqid('meet-'),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Google Meet create failed', ['body' => $response->body()]);
            throw new Exception('Création Google Meet échouée : ' . $response->body());
        }

        $json = $response->json();
        $entry = collect($json['conferenceData']['entryPoints'] ?? [])->firstWhere('entryPointType', 'video');

        return [
            'id' => $json['id'],
            'join_url' => $entry['uri'] ?? null,
            'start_url' => $entry['uri'] ?? null,
            'password' => null,
            'provider' => 'meet',
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        $token = $this->getAccessToken();
        $startTime = date('c', strtotime($data['start_at']));
        $endTime = date('c', strtotime($data['start_at']) + ($data['duration'] ?? 60) * 60);

        $response = Http::withToken($token)->patch(
            $this->eventsUrl() . '/' . $meetingId,
            [
                'summary' => $data['title'],
                'start' => ['dateTime' => $startTime, 'timeZone' => 'UTC'],
                'end' => ['dateTime' => $endTime, 'timeZone' => 'UTC'],
            ]
        );

        if ($response->failed()) {
            throw new Exception('Mise à jour Google Meet échouée.');
        }

        $json = $response->json();
        $entry = collect($json['conferenceData']['entryPoints'] ?? [])->firstWhere('entryPointType', 'video');

        return [
            'id' => $json['id'] ?? $meetingId,
            'join_url' => $entry['uri'] ?? null,
            'start_url' => $entry['uri'] ?? null,
            'password' => null,
            'provider' => 'meet',
        ];
    }

    public function deleteMeeting(string $meetingId): void
    {
        $token = $this->getAccessToken();
        Http::withToken($token)->delete($this->eventsUrl() . '/' . $meetingId);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && (filled(config('services.google.refresh_token')) || filled(config('services.google.credentials_path')));
    }

    /**
     * Teste l'obtention d'un access token (rafraîchissement OAuth).
     */
    public function testConnection(): array
    {
        try {
            $this->getAccessToken();

            return ['success' => true, 'message' => 'Connexion Google réussie.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function eventsUrl(): string
    {
        $calendar = rawurlencode((string) config('services.google.calendar_id', 'primary'));

        return "{$this->apiBase}/calendars/{$calendar}/events";
    }

    protected function getAccessToken(): string
    {
        $clientId = (string) config('services.google.client_id');
        $clientSecret = (string) config('services.google.client_secret');
        $refreshToken = (string) config('services.google.refresh_token');

        if ($clientId && $clientSecret && $refreshToken) {
            return Cache::remember('google_meet_access_token', 3300, function () use ($clientId, $clientSecret, $refreshToken) {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->failed()) {
                    Log::error('Google token refresh failed', ['body' => $response->body()]);
                    throw new Exception('Impossible de rafraîchir le token Google. Vérifiez le client ID, le secret et le refresh token.');
                }

                return (string) $response->json('access_token');
            });
        }

        // Compatibilité avec l'ancien fichier de credentials.
        $credentialsPath = config('services.google.credentials_path');
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            throw new Exception('Google Meet n\'est pas configuré. Renseignez les identifiants OAuth dans Configuration > Visioconférence.');
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (! isset($credentials['access_token'])) {
            throw new Exception('Token Google manquant dans le fichier de credentials.');
        }

        return (string) $credentials['access_token'];
    }
}
