<?php

namespace App\Services\LiveKit;

use App\Models\LiveSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class LiveKitService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $url;
    protected string $wsUrl;

    public function __construct()
    {
        $this->apiKey = config('services.livekit.api_key', '');
        $this->apiSecret = config('services.livekit.api_secret', '');
        $this->url = rtrim(config('services.livekit.url', 'http://localhost:7880'), '/');
        $this->wsUrl = rtrim(config('services.livekit.ws_url', 'ws://localhost:7880'), '/');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret) && !empty($this->url);
    }

    public function generateToken(LiveSession $session, User $user, bool $isInstructor = false): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré. Vérifiez LIVEKIT_API_KEY, LIVEKIT_API_SECRET et LIVEKIT_URL dans .env.');
        }

        $roomName = $this->getRoomName($session);
        $now = time();
        $exp = $now + ($session->duration ?? 120) * 60 + 3600;

        $payload = [
            'iss' => $this->apiKey,
            'sub' => (string) $user->id,
            'name' => $user->name,
            'video' => true,
            'audio' => true,
            'room' => $roomName,
            'room_admin' => $isInstructor,
            'iat' => $now,
            'exp' => $exp,
            'nbf' => $now,
            'jti' => Str::random(32),
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }

    public function createRoom(LiveSession $session): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré. Impossible de créer une room.');
        }

        $roomName = $this->getRoomName($session);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->generateAdminToken(),
            'Content-Type' => 'application/json',
        ])->post($this->url . '/room', [
            'name' => $roomName,
            'empty_timeout' => 300,
            'max_participants' => $session->max_participants ?? 100,
            'metadata' => json_encode([
                'session_id' => $session->id,
                'course_id' => $session->course_id,
                'title' => $session->title,
                'provider' => 'livekit',
            ]),
        ]);

        if ($response->failed()) {
            Log::error('LiveKit createRoom failed', ['response' => $response->body()]);
            throw new Exception('Création de room LiveKit échouée : ' . $response->body());
        }

        $json = $response->json();
        return [
            'id' => $roomName,
            'join_url' => $this->wsUrl . '?access_token=' . $this->generateToken($session, $session->instructor, true),
            'start_url' => $this->url . '/room/' . $roomName,
        ];
    }

    public function deleteRoom(LiveSession $session): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré.');
        }

        $roomName = $this->getRoomName($session);

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->generateAdminToken(),
            ])->delete($this->url . '/room/' . urlencode($roomName));
        } catch (\Exception $e) {
            Log::warning('LiveKit deleteRoom failed', ['error' => $e->getMessage()]);
        }
    }

    public function updateRoom(LiveSession $session, array $data = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré.');
        }

        $roomName = $this->getRoomName($session);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->generateAdminToken(),
            'Content-Type' => 'application/json',
        ])->patch($this->url . '/room/' . urlencode($roomName), $data);

        if ($response->failed()) {
            throw new Exception('Mise à jour room LiveKit échouée.');
        }

        return $response->json();
    }

    public function createRoomForData(array $data): array
    {
        $session = new \App\Models\LiveSession();
        $session->fill($data);
        $session->provider = 'livekit';

        return $this->createRoom($session);
    }

    public function listParticipants(LiveSession $session): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré.');
        }

        $roomName = $this->getRoomName($session);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->generateAdminToken(),
        ])->get($this->url . '/room/' . urlencode($roomName) . '/participants');

        if ($response->failed()) {
            return [];
        }

        return $response->json('participants', []);
    }

    public function getRoomName(LiveSession $session): string
    {
        return 'subnetcongo-' . $session->id . '-' . ($session->slug ?: Str::slug($session->title));
    }

    protected function generateAdminToken(): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('LiveKit n\'est pas configuré.');
        }

        $now = time();
        $payload = [
            'iss' => $this->apiKey,
            'sub' => '',
            'name' => 'SubnetCongo Admin',
            'video' => true,
            'audio' => true,
            'room_admin' => true,
            'iat' => $now,
            'exp' => $now + 3600,
            'nbf' => $now,
            'jti' => Str::random(32),
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }
}
