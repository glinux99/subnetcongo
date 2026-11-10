<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class BbbService
{
    public function createMeeting(array $data): array
    {
        $base = rtrim((string) config('services.bbb.base_url'), '/');
        $secret = (string) config('services.bbb.secret');

        if (! $base || ! $secret) {
            throw new Exception('BigBlueButton n\'est pas configuré. Renseignez l\'URL du serveur et le secret dans Configuration > Visioconférence.');
        }

        $meetingID = 'snc-' . substr(md5($data['title'] . microtime()), 0, 12);
        $attendeePw = $data['passcode'] ?? substr(md5(uniqid()), 0, 8);
        $moderatorPw = substr(md5(uniqid('mod', true)), 0, 10);

        $params = [
            'meetingID' => $meetingID,
            'name' => $data['title'],
            'attendeePW' => $attendeePw,
            'moderatorPW' => $moderatorPw,
            'record' => ($data['is_recorded'] ?? false) ? 'true' : 'false',
            'maxParticipants' => (string) ($data['max_participants'] ?? 100),
        ];

        $response = Http::timeout(20)->get($base . '/bigbluebutton/api/create', $this->signed($params, $secret));

        if ($response->failed() || ! str_contains($response->body(), '<returncode>SUCCESS</returncode>')) {
            Log::error('BBB create meeting failed', ['body' => $response->body()]);
            throw new Exception('Création BigBlueButton échouée : ' . $response->body());
        }

        return [
            'id' => $meetingID,
            'join_url' => $this->buildJoinUrl($meetingID, 'Participant', 'viewer', $attendeePw),
            'start_url' => $this->buildJoinUrl($meetingID, 'Modérateur', 'moderator', $moderatorPw),
            'password' => $attendeePw,
            'provider' => 'bbb',
            'attendee_password' => $attendeePw,
            'moderator_password' => $moderatorPw,
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        // BBB ne met pas à jour une réunion : on renvoie les URLs de jointure.
        return [
            'id' => $meetingId,
            'join_url' => $this->buildJoinUrl($meetingId, 'Participant', 'viewer', $data['passcode'] ?? null),
            'start_url' => $this->buildJoinUrl($meetingId, 'Modérateur', 'moderator', $data['passcode'] ?? null),
            'password' => $data['passcode'] ?? null,
            'provider' => 'bbb',
        ];
    }

    public function deleteMeeting(string $meetingId): void
    {
        $base = rtrim((string) config('services.bbb.base_url'), '/');
        $secret = (string) config('services.bbb.secret');

        if (! $base || ! $secret) {
            return;
        }

        try {
            Http::timeout(10)->get($base . '/bigbluebutton/api/delete', $this->signed(['meetingID' => $meetingId], $secret));
        } catch (Exception $e) {
            Log::warning('BBB delete failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Construit une URL de jointure signée pour un participant donné.
     */
    public function buildJoinUrl(string $meetingId, string $fullName, string $role = 'viewer', ?string $password = null): string
    {
        $base = rtrim((string) config('services.bbb.base_url'), '/');
        $secret = (string) config('services.bbb.secret');

        $params = [
            'meetingID' => $meetingId,
            'fullName' => $fullName !== '' ? $fullName : 'Participant',
            'role' => $role === 'moderator' ? 'moderator' : 'viewer',
            'redirect' => 'true',
        ];

        if ($password) {
            $params['password'] = $password;
        }

        return $base . '/bigbluebutton/api/join?' . http_build_query($this->signed($params, $secret));
    }

    public function isConfigured(): bool
    {
        return filled(config('services.bbb.base_url')) && filled(config('services.bbb.secret'));
    }

    public function testConnection(): array
    {
        $base = rtrim((string) config('services.bbb.base_url'), '/');
        $secret = (string) config('services.bbb.secret');

        if (! $base || ! $secret) {
            return ['success' => false, 'message' => 'URL ou secret BigBlueButton manquant.'];
        }

        try {
            $response = Http::timeout(15)->get($base . '/bigbluebutton/api', $this->signed(['random' => uniqid()], $secret));

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Le serveur BBB a répondu avec le code ' . $response->status() . '.'];
            }

            return str_contains($response->body(), '<returncode>SUCCESS</returncode>')
                ? ['success' => true, 'message' => 'Connexion BigBlueButton réussie.']
                : ['success' => false, 'message' => 'Réponse BBB inattendue (secret probablement invalide).'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Serveur BBB injoignable : ' . $e->getMessage()];
        }
    }

    protected function signed(array $params, string $secret): array
    {
        $query = http_build_query($params);

        return $params + ['checksum' => sha1($query . $secret)];
    }
}
