<?php

namespace App\Services\Video;

use Exception;

class BbbService
{
    public function createMeeting(array $data): array
    {
        $base = config('services.bbb.base_url');
        $secret = config('services.bbb.secret');

        if (!$base || !$secret) {
            throw new Exception('BigBlueButton n\'est pas configuré. Définissez BBB_BASE_URL et BBB_SECRET dans le fichier .env.');
        }

        $meetingID = 'snc-' . substr(md5($data['title'] . microtime()), 0, 12);

        $createParams = http_build_query([
            'meetingID' => $meetingID,
            'name' => $data['title'],
            'attendeePW' => $data['passcode'] ?? 'ap',
            'moderatorPW' => 'mp',
        ]);
        $checksum = sha1($createParams . $secret);
        $joinURL = $base . '/bigbluebutton/api/create?' . $createParams . '&checksum=' . $checksum;

        $response = file_get_contents($joinURL);
        if ($response && str_contains($response, '<returncode>FAILED</returncode>')) {
            throw new Exception('Création BBB échouée : ' . $response);
        }

        return [
            'id' => $meetingID,
            'join_url' => $base . '/bigbluebutton/api/join?meetingID=' . $meetingID,
            'start_url' => $base . '/bigbluebutton/api/join?meetingID=' . $meetingID,
            'password' => $data['passcode'] ?? null,
            'provider' => 'bbb',
        ];
    }

    public function updateMeeting(string $meetingId, array $data): array
    {
        return $this->createMeeting($data);
    }

    public function deleteMeeting(string $meetingId): void
    {
        $base = config('services.bbb.base_url');
        $secret = config('services.bbb.secret');
        if (!$base || !$secret) {
            return;
        }
        $params = http_build_query(['meetingID' => $meetingId]);
        $checksum = sha1($params . $secret);
        file_get_contents($base . '/bigbluebutton/api/delete?' . $params . '&checksum=' . $checksum);
    }
}
