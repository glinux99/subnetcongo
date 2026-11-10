<?php

namespace App\Services;

use App\Models\GreetingMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Service de puisage des messages d'accueil depuis la base de connaissances
 * (table greeting_messages). Chaque connexion pioche un message différent,
 * adapté au rôle et au moment de la journée.
 */
class GreetingService
{
    /**
     * Retourne [greeting, message] pour affichage sur le dashboard.
     */
    public function pick(User $user): array
    {
        $audience = $this->audienceFor($user);
        $period = $this->periodFor();

        $messages = GreetingMessage::where('is_active', true)
            ->where(function ($q) use ($audience) {
                $q->where('audience', $audience)->orWhere('audience', 'all');
            })
            ->where(function ($q) use ($period) {
                $q->where('period', $period)->orWhere('period', 'all');
            })
            ->inRandomOrder()
            ->limit(10)
            ->pluck('id', 'message')
            ->all();

        // Évite de repiocher le même message que la dernière fois (session)
        $lastId = session('greeting_message_id');
        $picked = collect($messages)
            ->reject(fn ($id) => $id === $lastId)
            ->keys()
            ->first() ?? array_key_first($messages);

        if ($picked !== null) {
            $pickedId = $messages[$picked];
            session(['greeting_message_id' => $pickedId]);
        }

        return [
            'greeting' => $this->salutation($period),
            'message' => $picked ?? 'Prêt à continuer votre progression ?',
        ];
    }

    private function audienceFor(User $user): string
    {
        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return GreetingMessage::AUDIENCE_ADMIN;
        }
        if ($user->hasRole('instructor')) {
            return GreetingMessage::AUDIENCE_INSTRUCTOR;
        }
        if ($user->hasAnyRole(['moderator', 'support-agent', 'finance-manager'])) {
            return GreetingMessage::AUDIENCE_STAFF;
        }
        return GreetingMessage::AUDIENCE_STUDENT;
    }

    private function periodFor(): string
    {
        $hour = now()->hour;
        if ($hour < 12) {
            return GreetingMessage::PERIOD_MORNING;
        }
        if ($hour < 18) {
            return GreetingMessage::PERIOD_AFTERNOON;
        }
        return GreetingMessage::PERIOD_EVENING;
    }

    private function salutation(string $period): string
    {
        return match ($period) {
            GreetingMessage::PERIOD_MORNING => 'Bonjour',
            GreetingMessage::PERIOD_AFTERNOON => 'Bon après-midi',
            GreetingMessage::PERIOD_EVENING => 'Bonsoir',
            default => 'Bonjour',
        };
    }
}
