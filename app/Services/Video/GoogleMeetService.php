<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleMeetService
{
    /**
     * Crée une réunion Google Meet via Calendar API (OAuth2 requis).
     * Si GOOGLE_MEET_CREDENTIALS_PATH n'est pas défini → fallback Jitsi.
     */
    public function createMeeting(array $data): array
    {
        $credentialsPath = config('services.google.credentials_path');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            // Fallback : génère un ID Jitsi-like pour Meet (URL non garantie)
            return [
                'id' => 'meet-' . bin2hex(random_bytes(8)),
                'join_url' => 'https://meet.google.com/lookup/' . bin2hex(random_bytes(5)),
                'start_url' => 'https://meet.google.com/lookup/' . bin2hex(random_bytes(5)),
                'password' => null,
                'provider' => 'meet',
                'mock' => true,
            ];
        }

        $token = $this->getAccessToken();
        $startTime = date('c', strtotime($data['start_at']));
        $endTime = date('c', strtotime($data['start_at']) + ($data['duration'] ?? 60) * 60);

        $response = Http::withToken($token)
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', [
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
            throw new Exception('Création Google Meet échouée.');
        }

        $json = $response->json();
        $entry = collect($json['conferenceData']['entryPoints'] ?? [])->firstWhere('entryPointType', 'video');

        return [
            'id' => $json['id'],
            'join_url' => $entry['uri'] ?? null,
            'start_url' => $entry['uri'] ?? null,
            'password' => null,
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        $token = $this->getAccessToken();
        $startTime = date('c', strtotime($data['start_at']));
        $endTime = date('c', strtotime($data['start_at']) + ($data['duration'] ?? 60) * 60);

        $response = Http::withToken($token)->patch(
            "https://www.googleapis.com/calendar/v3/calendars/primary/events/{$meetingId}",
            [
                'summary' => $data['title'],
                'start' => ['dateTime' => $startTime, 'timeZone' => 'UTC'],
                'end' => ['dateTime' => $endTime, 'timeZone' => 'UTC'],
            ]
        );
        if ($response->failed()) {
            throw new Exception('Mise à jour Google Meet échouée.');
        }
        return $this->createMeeting($data);
    }

    public function deleteMeeting(string $meetingId): void
    {
        $token = $this->getAccessToken();
        Http::withToken($token)->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$meetingId}");
    }

    protected function getAccessToken(): string
    {
        $credentials = json_decode(file_get_contents(config('services.google.credentials_path')), true);
        // Real implementation : use google/apiclient + refresh_token
        // Simplified here for brevity
        return $credentials['access_token'] ?? throw new Exception('Token Google manquant.');
    }
}
