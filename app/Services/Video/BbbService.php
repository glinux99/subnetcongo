<?php

namespace App\Services\Video;

class BbbService
{
    public function createMeeting(array $data): array
    {
        $base = config('services.bbb.base_url', 'https://bbb.example.com');
        $secret = config('services.bbb.secret');
        $meetingID = 'snc-' . substr(md5($data['title'] . microtime()), 0, 12);

        if ($secret) {
            $createParams = http_build_query([
                'meetingID' => $meetingID,
                'name' => $data['title'],
                'attendeePW' => $data['passcode'] ?? 'ap',
                'moderatorPW' => 'mp',
            ]);
            $checksum = sha1($createParams . $secret);
            $joinURL = $base . '/bigbluebutton/api/create?' . $createParams . '&checksum=' . $checksum;
            // Appel réel à BBB pour créer la réunion
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
        // API call if needed
    }
}
