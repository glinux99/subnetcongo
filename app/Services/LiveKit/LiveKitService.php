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

    public function healthCheck(): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'LiveKit non configuré'];
        }

        try {
            $response = Http::timeout(3)->get($this->url);
            if ($response->successful()) {
                return ['status' => 'ok', 'url' => $this->url];
            }
            return ['status' => 'error', 'message' => 'HTTP ' . $response->status()];
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Empty reply from server')) {
                return ['status' => 'error', 'message' => 'Serveur injoignable (vide). Vérifiez docker run -p 7880:7880 -p 7881:7881/udp livekit/livekit-server --dev'];
            }
            if (str_contains($msg, 'Connection refused')) {
                return ['status' => 'error', 'message' => 'Connexion refusée sur ' . $this->url];
            }
            if (str_contains($msg, 'timed out') || str_contains($msg, 'Operation timed out')) {
                return ['status' => 'error', 'message' => 'Timeout. Le serveur LiveKit ne répond pas.'];
            }
            return ['status' => 'error', 'message' => $msg];
        }
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

        try {
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
                $body = $response->body();
                if (empty($body) || $response->getStatusCode() === 0) {
                    throw new \RuntimeException('Serveur LiveKit inaccessible. Vérifiez que le serveur est démarré sur ' . $this->url . ' (docker run -p 7880:7880 -p 7881:7881/udp livekit/livekit-server --dev)');
                }
                throw new Exception('Création de room LiveKit échouée : ' . $body);
            }

            $json = $response->json();
            return [
                'id' => $roomName,
                'join_url' => $this->wsUrl . '?access_token=' . $this->generateToken($session, $session->instructor, true),
                'start_url' => $this->url . '/room/' . $roomName,
            ];
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Empty reply from server') || 
                str_contains($msg, 'Couldn\'t connect to server') || 
                str_contains($msg, 'Connection was aborted') ||
                str_contains($msg, 'Recv failure')) {
                throw new \RuntimeException('Serveur LiveKit inaccessible. Vérifiez : 1) Le serveur est démarré (docker run -p 7880:7880 -p 7881:7881/udp livekit/livekit-server --dev) 2) LIVEKIT_URL=' . $this->url . ' dans .env 3) Pas de firewall bloquant le port 7880');
            }
            throw $e;
        }
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

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->generateAdminToken(),
                'Content-Type' => 'application/json',
            ])->patch($this->url . '/room/' . urlencode($roomName), $data);

            if ($response->failed()) {
                throw new Exception('Mise à jour room LiveKit échouée.');
            }

            return $response->json();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Empty reply from server') || str_contains($e->getMessage(), 'Couldn\'t connect to server')) {
                throw new \RuntimeException('Serveur LiveKit inaccessible. Démarrez-le avec : docker run -p 7880:7880 -p 7881:7881/udp livekit/livekit-server --dev');
            }
            throw $e;
        }
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
