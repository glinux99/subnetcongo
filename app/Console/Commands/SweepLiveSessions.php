<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\LiveSession;
use App\Models\User;
use App\Notifications\LiveSessionReminderNotification;
use App\Notifications\LiveSessionStartedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Fait vivre automatiquement les sessions live :
 *
 *  1. Scheduled → Live dès que start_at est atteint (date effective d'arrivée).
 *     On notifie en base + broadcast les élèves actifs du cours et l'instructeur.
 *
 *  2. Live → Ended une fois la durée écoulée (start_at + duration minutes).
 *
 *  3. Rappels automatiques : si la session a un remind_before_minutes défini,
 *     on envoie un rappel (mail + base + broadcast) aux inscrits actifs/completed
 *     et aux e-mails invités dès que l'heure de rappel est atteinte. Marquée en
 *     reminder_sent_at pour éviter les doublons.
 *
 * Tâche planifiée Laravel (`schedule:run`) ou cron système.
 */
class SweepLiveSessions extends Command
{
    protected $signature = 'live-sessions:sweep';

    protected $description = 'Applique le cycle de vie des sessions live (Scheduled→Live→Ended) et envoie les rappels automatiques.';

    public function handle(): int
    {
        $now = now();

        // 1) Scheduled → Live quand la date est arrivée (et la session pas trop vieille)
        $starting = LiveSession::query()
            ->where('status', LiveSession::STATUS_SCHEDULED)
            ->where('start_at', '<=', $now)
            ->where('start_at', '>=', $now->copy()->subHours(2))
            ->with(['course:id,title', 'instructor:id,name'])
            ->get();

        foreach ($starting as $session) {
            try {
                $session->forceFill([
                    'status' => LiveSession::STATUS_LIVE,
                    'actual_start_at' => $now,
                ])->save();

                $userIds = $this->recipientIds($session);
                if (! empty($userIds)) {
                    $users = User::whereIn('id', $userIds)->get();
                    Notification::send(
                        $users,
                        new LiveSessionStartedNotification((string) $session->title, (int) $session->id, $session->shareUrl()),
                    );
                }
                $this->info("Session #{$session->id} passée en Live ({$session->title}).");
                Log::info('live_session_auto_started', ['session_id' => $session->id]);
            } catch (\Throwable $e) {
                $this->error("Erreur au passage en Live #{$session->id} : {$e->getMessage()}");
                Log::error('live_session_auto_start_fail', ['session' => $session->id, 'error' => $e->getMessage()]);
            }
        }

        // 2) Live/Scheduled → Ended quand la durée est passée
        $ending = LiveSession::query()
            ->whereIn('status', [LiveSession::STATUS_LIVE, LiveSession::STATUS_SCHEDULED])
            ->whereRaw('DATE_ADD(start_at, INTERVAL duration MINUTE) <= ?', [$now])
            ->get();

        foreach ($ending as $session) {
            try {
                $endedAt = $session->ended_at ?? $now;
                $session->forceFill([
                    'status' => LiveSession::STATUS_ENDED,
                    'ended_at' => $endedAt,
                ])->save();
                $this->info("Session #{$session->id} passée en Ended.");
                Log::info('live_session_auto_ended', ['session_id' => $session->id]);
            } catch (\Throwable $e) {
                $this->error("Erreur au passage en Ended #{$session->id} : {$e->getMessage()}");
                Log::error('live_session_auto_end_fail', ['session' => $session->id, 'error' => $e->getMessage()]);
            }
        }

        // 3) Rappels automatiques (une seule fois, au plus près de l'heure de rappel)
        $reminding = LiveSession::query()
            ->where('status', LiveSession::STATUS_SCHEDULED)
            ->whereNotNull('remind_before_minutes')
            ->whereNull('reminder_sent_at')
            ->where('start_at', '>', $now)
            ->with(['course:id,title'])
            ->get();

        foreach ($reminding as $session) {
            $remindAt = $session->start_at->copy()->subMinutes((int) $session->remind_before_minutes);
            if ($remindAt->gt($now)) {
                continue; // pas encore l'heure du rappel
            }
            try {
                $this->sendReminder($session);
                $session->update(['reminder_sent_at' => $now]);
                $this->info("Rappel envoyé pour la session #{$session->id}.");
                Log::info('live_session_reminder_sent', ['session_id' => $session->id]);
            } catch (\Throwable $e) {
                $this->error("Erreur rappel session #{$session->id} : {$e->getMessage()}");
                Log::error('live_session_reminder_fail', ['session' => $session->id, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Inscrits "actifs/terminés" du cours + instructeur assigné.
     *
     * @return array<int>
     */
    private function recipientIds(LiveSession $session): array
    {
        $ids = [];
        if ($session->course_id) {
            $ids = Enrollment::query()
                ->where('course_id', $session->course_id)
                ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])
                ->pluck('user_id')
                ->all();
        }
        if ($session->instructor_id) {
            $ids[] = (int) $session->instructor_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function sendReminder(LiveSession $session): void
    {
        $session->loadMissing('course:id,title', 'instructor:id,name');

        $tz = is_string($session->settings['timezone'] ?? null) && $session->settings['timezone'] !== ''
            ? $session->settings['timezone']
            : config('live_sessions.timezone', 'Africa/Lubumbashi');

        $local = $session->start_at->copy()->timezone($tz);
        $offset = $local->format('P'); // +02:00
        $startAtLabel = $local->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm').' (UTC'.$offset.')';
        $joinUrl = $session->shareUrl();
        $minutes = (int) $session->remind_before_minutes;
        $courseTitle = $session->course?->title;
        $instructorName = $session->instructor?->name ?? 'Un formateur';

        $notification = new LiveSessionReminderNotification(
            (string) $session->title,
            $courseTitle,
            $instructorName,
            $startAtLabel,
            $joinUrl,
            (int) $session->id,
            $minutes,
        );

        $userIds = $this->recipientIds($session);
        if (! empty($userIds)) {
            $users = User::whereIn('id', $userIds)->get();
            Notification::send($users, $notification);
        }

        // Invités externes sans compte : e-mail uniquement
        foreach ($session->invitedEmails() as $email) {
            if (! User::where('email', $email)->exists()) {
                Notification::route('mail', $email)->notify($notification);
            }
        }
    }
}
