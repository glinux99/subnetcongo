<?php

namespace App\Services\LiveKit;

use App\Models\LiveSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Client LiveKit (Cloud ou self-hosted).
 *
 * - generateToken() : token participant avec grants vidéo conformes LiveKit v2
 * - createRoom()/deleteRoom()/listParticipants() : API Twirp /twirp/livekit.RoomService/*
 *
 * Note : LiveKit crée automatiquement une room lorsqu'un premier participant
 * se connecte. createRoom() est donc optionnel et tolérant aux rooms existantes.
 */
class LiveKitService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $url;
    protected string $wsUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.livekit.api_key', '');
        $this->apiSecret = (string) config('services.livekit.api_secret', '');
        $this->url = rtrim((string) config('services.livekit.url', 'http://localhost:7880'), '/');
        $this->wsUrl = rtrim((string) config('services.livekit.ws_url', 'ws://localhost:7880'), '/');

        // LiveKit Cloud expose l'API HTTP sur https:// et le websocket sur wss://
        // sans suffixe de port. On normalise les anciennes configurations (:7880).
        if (str_contains($this->url, '.livekit.cloud') && str_ends_with($this->url, ':7880')) {
            $this->url = preg_replace('/:7880$/', '', $this->url);
        }
        if (str_contains($this->wsUrl, '.livekit.cloud') && str_ends_with($this->wsUrl, ':7880')) {
            $this->wsUrl = preg_replace('/:7880$/', '', $this->wsUrl);
        }
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getWsUrl(): string
    {
        return $this->wsUrl;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiSecret !== '' && $this->url !== '';
    }

    public function healthCheck(): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'error', 'message' => 'LiveKit non configuré'];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->generateAdminToken()])
                ->post($this->url . '/twirp/livekit.RoomService/ListRooms', new \stdClass());

            if ($response->successful()) {
                return ['status' => 'ok', 'url' => $this->url, 'rooms' => $response->json('rooms', [])];
            }

            return ['status' => 'error', 'message' => 'HTTP ' . $response->status() . ' ' . $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Token d'accès d'un participant (instructeur ou apprenant).
     */
    public function generateToken(LiveSession $session, User $user, bool $isInstructor = false): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException("LiveKit n'est pas configuré. Vérifiez LIVEKIT_API_KEY, LIVEKIT_API_SECRET et LIVEKIT_URL dans .env.");
        }

        $roomName = $this->getRoomName($session);
        $now = time();
        $exp = $now + max(60, (int) ($session->duration ?? 120)) * 60 + 3600;

        $video = [
            'roomJoin' => true,
            'room' => $roomName,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'hidden' => false,
        ];

        if ($isInstructor) {
            $video['roomAdmin'] = true;
            $video['roomCreate'] = true;
        }

        $payload = [
            'iss' => $this->apiKey,
            'sub' => (string) $user->id,
            'name' => (string) $user->name,
            'video' => $video,
            'metadata' => json_encode([
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $isInstructor ? 'instructor' : 'student',
            ]),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'jti' => Str::random(16),
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }

    /**
     * Crée la room (idempotent : retourne la room existante si déjà présente).
     */
    public function createRoom(LiveSession $session): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException("LiveKit n'est pas configuré. Impossible de créer une room.");
        }

        $roomName = $this->getRoomName($session);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->generateAdminToken()])
                ->post($this->url . '/twirp/livekit.RoomService/CreateRoom', [
                    'name' => $roomName,
                    'emptyTimeout' => 300,
                    'maxParticipants' => (int) ($session->max_participants ?? 100),
                    'metadata' => json_encode([
                        'session_id' => $session->id,
                        'course_id' => $session->course_id,
                        'title' => $session->title,
                        'provider' => 'livekit',
                    ]),
                ]);

            if ($response->failed() && $response->status() !== 409) {
                Log::warning('LiveKit createRoom failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            // La room sera créée automatiquement au premier join : non bloquant.
            Log::warning('LiveKit createRoom exception', ['error' => $e->getMessage()]);
        }

        return [
            'id' => $roomName,
            'join_url' => $this->wsUrl,
            'start_url' => $this->wsUrl,
        ];
    }

    public function deleteRoom(LiveSession $session): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->generateAdminToken()])
                ->post($this->url . '/twirp/livekit.RoomService/DeleteRoom', [
                    'room' => $this->getRoomName($session),
                ]);
        } catch (\Exception $e) {
            Log::warning('LiveKit deleteRoom failed', ['error' => $e->getMessage()]);
        }
    }

    public function updateRoom(LiveSession $session, array $data = []): array
    {
        // Les rooms LiveKit n'ont pas besoin d'être mises à jour pour le MVP.
        return ['id' => $this->getRoomName($session), 'join_url' => $this->wsUrl, 'start_url' => $this->wsUrl];
    }

    public function createRoomForData(array $data): array
    {
        $session = new LiveSession();
        $session->fill($data);
        $session->provider = 'livekit';

        return $this->createRoom($session);
    }

    public function listParticipants(LiveSession $session): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->generateAdminToken()])
                ->post($this->url . '/twirp/livekit.RoomService/ListParticipants', [
                    'room' => $this->getRoomName($session),
                ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json('participants', []);
        } catch (\Exception $e) {
            Log::warning('LiveKit listParticipants failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getRoomName(LiveSession $session): string
    {
        $slug = Str::slug((string) $session->title);
        $slug = $slug !== '' ? $slug : 'session';

        return 'subnetcongo-' . $session->id . '-' . substr($slug, 0, 40);
    }

    /**
     * Token « serveur » pour appeler l'API RoomService.
     */
    protected function generateAdminToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException("LiveKit n'est pas configuré.");
        }

        $now = time();
        $payload = [
            'iss' => $this->apiKey,
            'sub' => $this->apiKey,
            'name' => 'SubnetCongo Server',
            'video' => [
                'roomList' => true,
                'roomCreate' => true,
                'roomAdmin' => true,
            ],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'jti' => Str::random(16),
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }
}
