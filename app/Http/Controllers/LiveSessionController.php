<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - VIRTUAL CLASSROOM & LIVE CORE ENGINE v10.5.0
 * ======================================================================================
 * Ce contrôleur orchestre l'intégralité du cycle de vie des sessions en direct.
 * Il gère l'interopérabilité avec les APIs tierces (Zoom, Meet, BBB, Jitsi),
 * le monitoring de l'engagement des apprenants et la gestion automatisée des replays.
 *
 * @package App\Http\Controllers
 * @version 10.5.0
 * @author  SubnetCongo Framework Division
 */

use App\Models\LiveSession;
use App\Models\Course;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\LiveAttendance;
use App\Models\Recording;
use App\Models\Enrollment;
use App\Services\Video\ZoomService;
use App\Services\Video\GoogleMeetService;
use App\Services\Video\BbbService;
use App\Services\Video\JitsiService;
use App\Services\LiveKit\LiveKitService;
use App\Services\Video\VideoProviderConfig;
use App\Notifications\LiveSessionInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redirect, Storage, Log, Cache, Notification, Http, Mail};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;

class LiveSessionController extends Controller
{
    // --------------------------------------------------------------------------
    // CONSTANTES MÉTIER
    // --------------------------------------------------------------------------
    const PROVIDER_ZOOM  = 'zoom';
    const PROVIDER_MEET  = 'meet';
    const PROVIDER_BBB   = 'bbb';
    const PROVIDER_JITSI = 'jitsi';
    const PROVIDER_LIVEKIT = 'livekit';

    const STATUS_SCHEDULED = 'Scheduled';
    const STATUS_LIVE      = 'Live';
    const STATUS_ENDED     = 'Ended';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Initialisation du contrôleur avec injection de middleware de sécurité.
     */
    public function __construct()
    {
        // Middleware activés dans les routes ou selon les besoins
        // $this->middleware(['auth', 'verified']);
        // $this->middleware('can:manage_live_sessions')->except(['join', 'studentIndex']);
    }

    // --------------------------------------------------------------------------
    // 1. NAVIGATION ET FILTRAGE (DASHBOARD)
    // --------------------------------------------------------------------------

    /**
     * Index administratif : Affiche le dashboard de monitoring des sessions.
     */
    public function index(Request $request)
    {
        // 1.1 Initialisation de la requête avec Eager Loading pour performance
        $query = LiveSession::with(['course:id,title', 'instructor:id,name,avatar'])
            ->withCount(['attendees', 'attendances']);

        // 1.2 Moteur de recherche Full-Text
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('meeting_id', 'like', "%{$search}%")
                  ->orWhereHas('course', fn($c) => $c->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('instructor', fn($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        // 1.3 Filtres Facettés
        $query->when($request->status, fn($q, $s) => $q->where('status', $s));
        $query->when($request->provider, fn($q, $p) => $q->where('provider', $p));
        $query->when($request->course_id, fn($q, $id) => $q->where('course_id', $id));

        // 1.4 Filtrage temporel (Calendrier ou Liste)
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('start_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // 1.5 Pagination et Tri
        $sessions = $query->orderBy('start_at', 'desc')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        $sessions->getCollection()->transform(function ($session) {
            $session->pending_requests = collect($session->joinRequests())->where('status', 'pending')->count();
            $session->invited_count = count($session->invitedEmails());
            $session->is_public_session = $session->isPublicSession();
            $session->invited_emails = $session->invitedEmails();
            $session->join_requests = $session->joinRequests();
            $session->share_url = $session->shareUrl();
            return $session;
        });

        // 1.6 Agrégation des KPI pour les widgets PrimeVue
        $kpis = $this->calculateGlobalKPIs();

        return Inertia::render('LiveSessions', [
            'sessions' => $sessions,
            'kpis' => $kpis,
            'filters' => $request->all(),
            'meta' => [
                'providers' => $this->getProvidersMetadata(),
                'configured_providers' => $this->getConfiguredProviders(),
                'courses' => Course::select('id', 'title')->get(),
                'instructors' => User::role('instructor')->select('id', 'name', 'avatar')->get()
            ]
        ]);
    }

    // --------------------------------------------------------------------------
    // 2. CRÉATION ET PLANIFICATION (API SYNC)
    // --------------------------------------------------------------------------

    /**
     * Enregistre une nouvelle session avec synchronisation API vers le fournisseur.
     */
   public function store(Request $request)
{
    $validated = $this->validateSession($request);

    DB::beginTransaction();
    try {
        $apiMeetingData = $this->createMeetingOnProvider($validated);

        $session = new LiveSession();
        $session->fill(collect($validated)->except(['settings', 'visibility', 'invited_emails'])->toArray());
        $session->meeting_id  = $apiMeetingData['id'];
        $session->meeting_url = $apiMeetingData['join_url'];
        $session->host_url    = $apiMeetingData['start_url'] ?? null;
        $session->passcode    = $apiMeetingData['password'] ?? $validated['passcode'] ?? null;
        $session->status      = self::STATUS_SCHEDULED;
        $session->created_by  = Auth::id();

        $settings = $this->normalizeSettings($request->input('settings', []));
        $settings['visibility'] = $validated['visibility'] ?? 'private';
        if (! empty($validated['invited_emails'])) {
            $settings['invited_emails'] = $this->parseEmails($validated['invited_emails']);
        }
        $session->settings    = $settings;
        $session->metadata    = [
            'provider_response' => $apiMeetingData,
        ];
        $session->save();

        if (! empty($settings['invited_emails'])) {
            $this->sendInvitations($session, $settings['invited_emails']);
        }

        $this->scheduleNotifications($session);
        $this->audit('LIVE_SESSION_CREATED', $session);

        DB::commit();
        return Redirect::route('live-sessions.index')->with('success', 'Session planifiée.');
    } catch (Exception $e) {
        DB::rollBack();
        Log::error("SNC_LIVE_STORE_FAIL: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage())->withInput();
    }
}

/**
 * Mise à jour d'une session existante.
 */
    public function update(Request $request, LiveSession $liveSession)
    {
        // Vérifier les droits : instructeur assigné ou admin
        if (Auth::id() !== $liveSession->instructor_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $validated = $this->validateSession($request);

        DB::beginTransaction();
        try {
            // Si le provider a changé ou la date, recréer sur le nouveau provider
            if ($validated['provider'] !== $liveSession->provider ||
                Carbon::parse($validated['start_at'])->ne($liveSession->start_at)) {
                // Supprimer l'ancienne réunion via l'API
                $this->deleteMeetingOnProvider($liveSession);
                $apiMeetingData = $this->createMeetingOnProvider($validated);
            } else {
                $apiMeetingData = $this->updateMeetingOnProvider($liveSession, $validated);
            }

            $liveSession->fill(collect($validated)->except(['settings', 'visibility', 'invited_emails'])->toArray());
            $liveSession->meeting_id  = $apiMeetingData['id'] ?? $liveSession->meeting_id;
            $liveSession->meeting_url = $apiMeetingData['join_url'] ?? $liveSession->meeting_url;
            $liveSession->host_url    = $apiMeetingData['start_url'] ?? $liveSession->host_url;
            $liveSession->passcode    = $apiMeetingData['password'] ?? $validated['passcode'] ?? null;

            $settings = $this->normalizeSettings($request->input('settings', []));
            $settings['visibility'] = $validated['visibility'] ?? ($liveSession->settings['visibility'] ?? 'private');
            if (! empty($validated['invited_emails'])) {
                $settings['invited_emails'] = $this->parseEmails($validated['invited_emails']);
            } elseif (isset($liveSession->settings['invited_emails'])) {
                $settings['invited_emails'] = $liveSession->settings['invited_emails'];
            }
            $liveSession->settings    = $settings;
            $liveSession->metadata    = array_merge($liveSession->metadata ?? [], [
                'provider_response' => $apiMeetingData,
            ]);
            $liveSession->save();

            $this->audit('LIVE_SESSION_UPDATED', $liveSession);
            DB::commit();

            return Redirect::route('live-sessions.index')->with('success', 'Session mise à jour.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_LIVE_UPDATE_FAIL: " . $e->getMessage());
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    // --------------------------------------------------------------------------
    // 3. LOGIQUE DU DIRECT (START / JOIN / HEARTBEAT)
    // --------------------------------------------------------------------------

    /**
     * Déclenche le passage au statut "Live" et prépare l'environnement.
     */
    public function start(LiveSession $liveSession)
    {
        if (Auth::id() !== $liveSession->instructor_id && !Auth::user()->hasRole('admin')) {
            abort(403, "Seul l'instructeur assigné peut démarrer ce live.");
        }

        try {
            $liveSession->update([
                'status' => self::STATUS_LIVE,
                'actual_start_at' => now()
            ]);

            // Notifier les élèves inscrits par Push Temps Réel (Websocket)
            $this->broadcastLiveStart($liveSession);

            $this->audit('LIVE_SESSION_STARTED', $liveSession);

            return response()->json([
                'start_url' => $liveSession->host_url ?? $liveSession->meeting_url,
                'message' => 'Passerelle vidéo activée.'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Échec du démarrage.'], 500);
        }
    }

    /**
     * Gère l'entrée d'un étudiant dans la salle virtuelle.
     */
    public function join(LiveSession $liveSession)
    {
        $user = Auth::user();

        $isPrivileged = $user->id === $liveSession->instructor_id
            || $user->hasAnyRole(['superadmin', 'admin', 'instructor', 'cohort-assistant']);

        if (in_array($liveSession->status, [self::STATUS_CANCELLED, self::STATUS_ENDED], true)) {
            return back()->with('error', "Cette session est terminée ou annulée.");
        }

        if (! $isPrivileged) {
            // 3.1 Vérifier l'inscription ACTIVE au cours (et pas seulement l'existence d'une ligne)
            $enrolled = Enrollment::where('user_id', $user->id)
                ->where('course_id', $liveSession->course_id)
                ->whereIn('status', [\App\Models\Enrollment::STATUS_ACTIVE, \App\Models\Enrollment::STATUS_COMPLETED])
                ->exists();

            $invited = in_array(strtolower((string) $user->email), $liveSession->invitedEmails(), true);
            $approved = $liveSession->hasApprovedAccess($user->id);
            $isPublicSession = $liveSession->isPublicSession();

            if (! $enrolled && ! $invited && ! $approved && ! $isPublicSession) {
                $liveSession->load(['course:id,title', 'instructor:id,name']);

                return Inertia::render('LiveSessions/RequestAccess', [
                    'session' => $liveSession,
                    'requested' => $liveSession->hasPendingAccess($user->id),
                ]);
            }

            $requiresConfirmation = ! $enrolled;
        } else {
            $requiresConfirmation = false;
        }

        // 3.2 Enregistrer la présence initiale
        if ($liveSession->provider === self::PROVIDER_BBB && $liveSession->meeting_id) {
            $liveSession->meeting_url = app(BbbService::class)->buildJoinUrl(
                (string) $liveSession->meeting_id,
                (string) $user->name,
                $isPrivileged ? 'moderator' : 'viewer',
                $isPrivileged
                    ? ($liveSession->metadata['provider_response']['moderator_password'] ?? $liveSession->passcode)
                    : $liveSession->passcode
            );
        }

        LiveAttendance::updateOrCreate(
            ['live_session_id' => $liveSession->id, 'user_id' => $user->id],
            ['joined_at' => now(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]
        );

        // 3.3 Le formateur (ou un admin) qui rejoint une session LiveKit la démarre implicitement
        if ($liveSession->provider === self::PROVIDER_LIVEKIT && $isPrivileged && $liveSession->status !== self::STATUS_LIVE) {
            app(LiveKitService::class)->createRoom($liveSession);
            $liveSession->update([
                'status' => self::STATUS_LIVE,
                'actual_start_at' => $liveSession->actual_start_at ?? now(),
            ]);
        }

        $liveSession->load(['course:id,title', 'instructor:id,name,avatar']);

        return Inertia::render('LiveSessions/Player', [
            'session' => $liveSession,
            'user' => $user->only(['id', 'name', 'avatar', 'profile_photo']),
            'requires_confirmation' => $requiresConfirmation ?? false,
        ]);
    }

    /**
     * Demande d'accès à une session privée (validée par un formateur/admin).
     */
    public function requestAccess(LiveSession $liveSession)
    {
        $user = Auth::user();

        if ($liveSession->hasApprovedAccess($user->id)) {
            return back()->with('info', 'Vous avez déjà accès à cette session.');
        }

        if ($liveSession->hasPendingAccess($user->id)) {
            return back()->with('info', 'Votre demande est déjà en attente de validation.');
        }

        $requests = $liveSession->joinRequests();
        $requests[] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'pending',
            'requested_at' => now()->toIso8601String(),
        ];

        $metadata = $liveSession->metadata ?? [];
        $metadata['join_requests'] = $requests;
        $liveSession->metadata = $metadata;
        $liveSession->save();

        $instructor = User::find($liveSession->instructor_id);
        if ($instructor) {
            $instructor->notify(new \App\Notifications\BaseAppNotification(
                'Nouvelle demande d\'accès',
                $user->name . ' demande à rejoindre la session « ' . $liveSession->title . ' ».',
                'pi-user-plus',
                'warn',
                route('live-sessions.index')
            ));
        }

        return back()->with('success', "Demande envoyée. Un formateur ou un administrateur doit l'approuver.");
    }

    /**
     * Approbation d'une demande d'accès.
     */
    public function approveAccess(Request $request, LiveSession $liveSession)
    {
        $this->authorizeSessionManager($liveSession);

        $userId = (int) $request->input('user_id');
        $this->updateAccessStatus($liveSession, $userId, 'approved');

        $user = User::find($userId);
        if ($user) {
            $this->sendInvitations($liveSession, [$user->email]);
        }

        return back()->with('success', 'Accès approuvé : la personne peut rejoindre la session.');
    }

    /**
     * Refus d'une demande d'accès.
     */
    public function rejectAccess(Request $request, LiveSession $liveSession)
    {
        $this->authorizeSessionManager($liveSession);

        $userId = (int) $request->input('user_id');
        $this->updateAccessStatus($liveSession, $userId, 'rejected');

        return back()->with('success', "Demande d'accès refusée.");
    }

    private function updateAccessStatus(LiveSession $session, int $userId, string $status): void
    {
        $requests = $session->joinRequests();

        foreach ($requests as &$item) {
            if ((int) ($item['user_id'] ?? 0) === $userId) {
                $item['status'] = $status;
                $item['decided_at'] = now()->toIso8601String();
                $item['decided_by'] = Auth::id();
            }
        }
        unset($item);

        $metadata = $session->metadata ?? [];
        $metadata['join_requests'] = $requests;
        $session->metadata = $metadata;
        $session->save();
    }

    /**
     * Ajout d'e-mails invités et réglage public/privé.
     */
    public function invite(Request $request, LiveSession $liveSession)
    {
        $this->authorizeSessionManager($liveSession);

        $data = $request->validate([
            'emails' => 'required|string',
            'visibility' => 'nullable|in:public,private',
        ]);

        $emails = $this->parseEmails($data['emails']);
        $alreadyInvited = $liveSession->invitedEmails();
        $newEmails = array_values(array_diff($emails, $alreadyInvited));

        $settings = $liveSession->settings ?? [];
        $settings['invited_emails'] = array_values(array_unique(array_merge(
            (array) ($settings['invited_emails'] ?? []),
            $emails
        )));

        if (! empty($data['visibility'])) {
            $settings['visibility'] = $data['visibility'];
        }

        $liveSession->settings = $settings;
        $liveSession->save();

        $sent = $this->sendInvitations($liveSession, $newEmails);

        return back()->with('success', sprintf(
            '%d e-mail(s) enregistré(s), %d invitation(s) envoyée(s).',
            count($emails),
            $sent
        ));

    }

    /**
     * Envoie l'invitation (e-mail + notification interne) aux adresses indiquées.
     * Les adresses sans compte reçoivent uniquement l'e-mail.
     */
    private function sendInvitations(LiveSession $session, array $emails): int
    {
        if (empty($emails)) {
            return 0;
        }

        $session->loadMissing(['course:id,title', 'instructor:id,name']);

        $joinUrl = $session->shareUrl();
        $startAtLabel = $session->start_at
            ? $session->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm')
            : 'une date à confirmer';

        $sent = 0;

        foreach ($emails as $email) {
            try {
                $notification = new LiveSessionInvitationNotification(
                    (string) $session->title,
                    $session->course?->title,
                    $session->instructor?->name ?? 'Un formateur',
                    $startAtLabel,
                    $joinUrl,
                    (int) $session->id,
                );

                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->notify($notification);
                } else {
                    Notification::route('mail', $email)->notify($notification);
                }

                $sent++;
            } catch (\Throwable $e) {
                Log::warning('SNC_LIVE_INVITE_FAIL', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * Retourne le lien de partage opaque (jeton) d'une session.
     */
    public function shareLink(LiveSession $liveSession)
    {
        $this->authorizeSessionManager($liveSession);

        return response()->json([
            'url' => $liveSession->shareUrl(),
            'token' => $liveSession->shareToken(),
        ]);
    }

    /**
     * Point d'entrée des liens partagés : /l/{token}.
     * Aucun identifiant de session n'apparaît dans l'URL.
     */
    public function sharedJoin(string $token)
    {
        $session = LiveSession::where('settings->share_token', $token)->first();

        abort_if(! $session, 404);

        if (! Auth::check()) {
            return redirect()->guest(route('login'));
        }

        if (in_array($session->status, [self::STATUS_ENDED, self::STATUS_CANCELLED], true)) {
            return redirect()->route('live-sessions.mine')
                ->with('error', 'Cette session est terminée ou annulée.');
        }

        return redirect()->route('live-sessions.join', $session);
    }

    /**
     * Confirmation des informations à la fin d'une session (participants externes).
     */
    public function confirmInfo(Request $request, LiveSession $liveSession)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $attendance = LiveAttendance::where('live_session_id', $liveSession->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($attendance) {
            $metadata = $attendance->metadata ?? [];
            $metadata['confirmed_identity'] = $data;
            $metadata['confirmed_at'] = now()->toIso8601String();
            $attendance->metadata = $metadata;
            $attendance->save();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Liste publique des sessions ouvertes (webinaires), accessible sans connexion.
     */
    public function publicIndex()
    {
        $sessions = LiveSession::with(['course:id,title,slug', 'instructor:id,name'])
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_LIVE])
            ->where('start_at', '>=', now()->subHours(2))
            ->where('settings->visibility', 'public')
            ->orderByRaw("FIELD(status, 'Live', 'Scheduled')")
            ->orderBy('start_at')
            ->get();

        $sessions->each(function ($session) { $session->share_url = $session->shareUrl(); });

        return Inertia::render('LiveSessions/PublicIndex', [
            'sessions' => $sessions,
        ]);
    }

    private function authorizeSessionManager(LiveSession $session): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ((int) $user->id === (int) $session->instructor_id) {
            return;
        }

        if ($user->hasAnyRole(['superadmin', 'admin', 'instructor', 'cohort-assistant'])) {
            return;
        }

        abort(403);
    }

    /**
     * Espace apprenant : liste des sessions live de ses cours (en direct et à venir).
     */
    public function studentIndex()
    {
        $user = Auth::user();

        $isPrivileged = $user->hasAnyRole(['superadmin', 'admin', 'instructor', 'cohort-assistant']);

        $courseIds = Enrollment::where('user_id', $user->id)
            ->whereIn('status', [\App\Models\Enrollment::STATUS_ACTIVE, \App\Models\Enrollment::STATUS_COMPLETED])
            ->pluck('course_id');

        $query = LiveSession::with(['course:id,title', 'instructor:id,name,avatar'])
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_LIVE])
            ->where('start_at', '>=', now()->subHours(6));

        if (! $isPrivileged) {
            $query->where(function ($q) use ($user, $courseIds) {
                $q->whereIn('course_id', $courseIds)
                    ->orWhere('instructor_id', $user->id);
            });
        }

        $sessions = $query
            ->orderByRaw("FIELD(status, 'Live', 'Scheduled')")
            ->orderBy('start_at')
            ->get();

        $sessions->each(function ($session) { $session->share_url = $session->shareUrl(); });

        return Inertia::render('LiveSessions/StudentIndex', [
            'sessions' => $sessions,
            'courses' => Course::whereIn('id', $courseIds)->select('id', 'title')->get(),
        ]);
    }

    /**
     * Reçoit le "Heartbeat" (battement de coeur) du navigateur étudiant toutes les 60s.
     * Permet de calculer l'engagement réel et le temps passé.
     */
    public function heartbeat(Request $request, LiveSession $liveSession)
    {
        $attendance = LiveAttendance::where('live_session_id', $liveSession->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($attendance) {
            $attendance->increment('seconds_spent', 60);
            $attendance->update(['last_ping_at' => now()]);
        }

        return response()->json(['status' => 'active']);
    }

    // --------------------------------------------------------------------------
    // 4. ANALYTIQUE ET REPORTING (PRIME VUE CHART DATA)
    // --------------------------------------------------------------------------

    /**
     * Génère les données détaillées pour le rapport de session.
     */
    public function report(LiveSession $liveSession)
    {
        $liveSession->load(['course', 'instructor']);

        $attendances = LiveAttendance::with('user')
            ->where('live_session_id', $liveSession->id)
            ->orderBy('joined_at')
            ->get();

        $liveSession->setRelation('attendees', $attendances);

        // 4.1 Calcul du taux d'engagement par tranche de 15 min
        $engagementData = $this->calculateEngagementChart($liveSession);

        // 4.2 Statistiques de présence
        $stats = [
            'total_registered' => Enrollment::where('course_id', $liveSession->course_id)->count(),
            'actual_attendees' => $attendances->count(),
            'average_time' => (int) round($attendances->avg('seconds_spent') ?? 0),
            'peak_participants' => $this->getPeakParticipants($liveSession->id)
        ];

        return Inertia::render('LiveSessions/Report', [
            'session' => $liveSession,
            'stats' => $stats,
            'chartData' => $engagementData
        ]);
    }

    // --------------------------------------------------------------------------
    // 5. ENREGISTREMENTS ET REPLAYS
    // --------------------------------------------------------------------------

    /**
     * Synchronise les enregistrements depuis le cloud du fournisseur.
     */
    public function syncRecordings(LiveSession $liveSession)
    {
        try {
            $recordings = $this->fetchProviderRecordings($liveSession);

            foreach ($recordings as $rec) {
                Recording::updateOrCreate(
                    ['provider_id' => $rec['id']],
                    [
                        'live_session_id' => $liveSession->id,
                        'url' => $rec['download_url'],
                        'file_size' => $rec['file_size'],
                        'duration_seconds' => $rec['duration_sec'],
                        'status' => 'ready'
                    ]
                );
            }

            return back()->with('success', count($recordings) . ' enregistrements synchronisés.');
        } catch (Exception $e) {
            return back()->with('error', 'Impossible de récupérer les replays.');
        }
    }

    // --------------------------------------------------------------------------
    // 6. MÉTHODES PRIVÉES ET SERVICES (CORE LOGIC)
    // --------------------------------------------------------------------------

    /**
     * Validation exhaustive d'une session.
     */
    private function validateSession(Request $request): array
    {
        return $request->validate([
            'title'             => 'required|string|max:255',
            'course_id'         => 'required|exists:courses,id',
            'instructor_id'     => 'required|exists:users,id',
            'start_at'          => 'required|date',
            'duration'          => 'required|integer|min:15|max:480',
            'provider'          => [
                'required',
                Rule::in([self::PROVIDER_ZOOM, self::PROVIDER_MEET, self::PROVIDER_BBB, self::PROVIDER_JITSI, self::PROVIDER_LIVEKIT])
            ],
            'max_participants'  => 'required|integer|min:1',
            'is_recorded'       => 'boolean',
            'passcode'          => 'nullable|string|max:20',
            'settings'          => 'nullable|array',
            'settings.*'        => 'string', // Les valeurs arrivent sous forme de string "1"/"0"
            'visibility'        => 'nullable|in:public,private',
            'invited_emails'    => 'nullable|string',
        ]);
    }

    /**
     * Normalise les strings "1"/"0" en booléens.
     */
    private function normalizeSettings(array $settings): array
    {
        return array_map(function ($value) {
            if ($value === '1') return true;
            if ($value === '0') return false;
            return $value;
        }, $settings);
    }

    /**
     * Transforme une liste d'e-mails séparés par des virgules/espaces en tableau valide.
     */
    private function parseEmails(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Orchestrateur d'API multi-fournisseur.
     */
    private function createMeetingOnProvider(array $data): array
    {
        return match ($data['provider']) {
            self::PROVIDER_ZOOM  => app(ZoomService::class)->createMeeting($data),
            self::PROVIDER_BBB   => app(BbbService::class)->createMeeting($data),
            self::PROVIDER_MEET  => app(GoogleMeetService::class)->createMeeting($data),
            self::PROVIDER_JITSI => app(JitsiService::class)->createMeeting($data),
            // LiveKit : la room est créée automatiquement au premier join.
            self::PROVIDER_LIVEKIT => [
                'id' => null,
                'join_url' => app(LiveKitService::class)->getWsUrl(),
                'start_url' => app(LiveKitService::class)->getWsUrl(),
            ],
            default => throw new Exception("Fournisseur non support� : {$data['provider']}"),
        };
    }

    /**
     * Met à jour une réunion existante chez le fournisseur.
     */
    private function updateMeetingOnProvider(LiveSession $session, array $data): array
    {
        return match ($session->provider) {
            self::PROVIDER_ZOOM  => app(ZoomService::class)->updateMeeting($session->meeting_id, $data),
            self::PROVIDER_BBB   => app(BbbService::class)->updateMeeting($session->meeting_id, $data),
            self::PROVIDER_MEET  => app(GoogleMeetService::class)->updateMeeting($session->meeting_id, $data),
            self::PROVIDER_JITSI => app(JitsiService::class)->updateMeeting($session->meeting_id, $data),
            self::PROVIDER_LIVEKIT => app(LiveKitService::class)->updateRoom($session),
            default => throw new Exception("Fournisseur non supporté."),
        };
    }

    /**
     * Supprime une réunion chez le fournisseur.
     */
    private function deleteMeetingOnProvider(LiveSession $session): void
    {
        try {
            match ($session->provider) {
                self::PROVIDER_ZOOM => app(ZoomService::class)->deleteMeeting($session->meeting_id),
                self::PROVIDER_BBB  => app(BbbService::class)->deleteMeeting($session->meeting_id),
                self::PROVIDER_MEET => app(GoogleMeetService::class)->deleteMeeting($session->meeting_id),
                self::PROVIDER_JITSI => app(JitsiService::class)->deleteMeeting($session->meeting_id),
                self::PROVIDER_LIVEKIT => app(LiveKitService::class)->deleteRoom($session),
                default => null,
            };
        } catch (Exception $e) {
            Log::warning("SNC_LIVE_DELETE_FAIL: " . $e->getMessage());
        }
    }

    /**
     * Calcul des KPIs pour le dashboard.
     */
    private function calculateGlobalKPIs(): array
    {
        return [
            'live_now' => LiveSession::where('status', self::STATUS_LIVE)->count(),
            'scheduled_today' => LiveSession::whereDate('start_at', Carbon::today())->count(),
            'avg_attendance' => (int) (LiveAttendance::groupBy('live_session_id')
                                ->selectRaw('count(user_id) as count')
                                ->get()->avg('count') ?? 0),
            'storage_used' => $this->formatBytes(Recording::sum('file_size'))
        ];
    }

    /**
     * Génère les données de courbes d'engagement (Participants au fil du temps).
     */
    private function calculateEngagementChart(LiveSession $session): array
    {
        $startTime = Carbon::parse($session->actual_start_at ?? $session->start_at);
        $endTime = $session->status === self::STATUS_ENDED ? Carbon::parse($session->ended_at) : now();

        $labels = [];
        $data = [];
        $current = $startTime->copy();

        while ($current <= $endTime) {
            $labels[] = $current->format('H:i');
            $count = DB::table('live_attendances')
                ->where('live_session_id', $session->id)
                ->where('joined_at', '<=', $current)
                ->where('last_ping_at', '>=', $current->copy()->subMinutes(2))
                ->count();
            $data[] = $count;
            $current->addMinutes(5);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Audience en temps réel',
                'data' => $data,
                'borderColor' => '#6366F1',
                'fill' => true,
                'backgroundColor' => 'rgba(99, 102, 241, 0.1)'
            ]]
        ];
    }

    /**
     * Récupère le pic de participants pour une session.
     */
    private function getPeakParticipants($sessionId): int
    {
        // Logique de calcul du pic basée sur les pings ou les logs
        return LiveAttendance::where('live_session_id', $sessionId)->count();
    }

    /**
     * Récupération des enregistrements depuis le fournisseur.
     */
    private function fetchProviderRecordings(LiveSession $session): array
    {
        if ($session->provider === self::PROVIDER_ZOOM && $session->meeting_id) {
            $files = app(ZoomService::class)->listRecordings($session->meeting_id);
            return array_map(fn($f) => [
                'id' => $f['recording_id'] ?? $f['id'],
                'download_url' => $f['download_url'] ?? null,
                'file_size' => $f['file_size'] ?? 0,
                'duration_sec' => (int) ($f['duration'] ?? 0),
            ], $files);
        }
        return [];
    }

    /**
     * Enregistrement des actions administratives pour conformité.
     */
    protected function audit(string $event, $model, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'ip_address' => request()->ip(),
            'new_values' => json_encode(array_merge($model->toArray(), $meta)),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Diffusion du lancement du live aux étudiants (websocket).
     */
    protected function broadcastLiveStart(LiveSession $session): void
    {
        // Exemple avec Pusher ou Laravel Reverb
        // broadcast(new LiveSessionStarted($session))->toOthers();
    }

    /**
     * Programmation des notifications de rappel.
     */
    protected function scheduleNotifications(LiveSession $session): void
    {
        // Envoyer un email 1h avant, 15 min avant, etc.
        // Exemple : $session->enrolledStudents()->each(...);
    }

    // --------------------------------------------------------------------------
    // 7. UTILITAIRES DE FORMATAGE ET CONVERSION
    // --------------------------------------------------------------------------

    private function getProvidersMetadata(): array
    {
        return [
            ['label' => 'Zoom Video', 'value' => 'zoom', 'icon' => 'pi pi-video', 'color' => 'bg-blue-500'],
            ['label' => 'Google Meet', 'value' => 'meet', 'icon' => 'pi pi-google', 'color' => 'bg-emerald-500'],
            ['label' => 'BigBlueButton', 'value' => 'bbb', 'icon' => 'pi pi-users', 'color' => 'bg-indigo-600'],
            ['label' => 'Jitsi Meet', 'value' => 'jitsi', 'icon' => 'pi pi-comments', 'color' => 'bg-slate-700'],
            ['label' => 'LiveKit (In-App)', 'value' => 'livekit', 'icon' => 'pi pi-camera', 'color' => 'bg-rose-500']
        ];
    }

    private function getConfiguredProviders(): array
    {
        return \App\Services\Video\VideoProviderConfig::configuredProviders();
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    // --------------------------------------------------------------------------
    // 8. MÉTHODES SUPPLÉMENTAIRES (GESTION CALENDRIER, CONFLITS, RAPPELS)
    // --------------------------------------------------------------------------

    /**
     * Vérifie les conflits de calendrier pour un instructeur.
     */
    public function checkCalendarConflicts($instructorId, $startTime, $duration): bool
    {
        $endTime = Carbon::parse($startTime)->addMinutes($duration);

        return LiveSession::where('instructor_id', $instructorId)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_at', [$startTime, $endTime])
                  ->orWhereRaw('DATE_ADD(start_at, INTERVAL duration MINUTE) BETWEEN ? AND ?', [$startTime, $endTime]);
            })->exists();
    }

    /**
     * Terminer une session (passage à Ended).
     */
    public function endSession(LiveSession $liveSession)
    {
        DB::beginTransaction();
        try {
            $liveSession->update([
                'status' => self::STATUS_ENDED,
                'ended_at' => now()
            ]);

            // Déclencher le job de synchronisation des enregistrements dans 15 minutes
            // SyncRecordingsJob::dispatch($liveSession)->delay(now()->addMinutes(15));

            $this->audit('LIVE_SESSION_ENDED', $liveSession);
            DB::commit();

            return Redirect::back()->with('success', 'Session fermée. Le replay sera traité prochainement.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la fermeture.');
        }
    }

    /**
     * Annuler une session programmée.
     */
    public function cancel(LiveSession $liveSession)
    {
        if (Auth::id() !== $liveSession->instructor_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $liveSession->update(['status' => self::STATUS_CANCELLED]);
        $this->deleteMeetingOnProvider($liveSession);
        $this->audit('LIVE_SESSION_CANCELLED', $liveSession);

        return Redirect::route('live-sessions.index')->with('success', 'Session annulée.');
    }

    /**
     * Supprimer définitivement une session.
     */
    public function destroy(LiveSession $liveSession)
    {
        $this->deleteMeetingOnProvider($liveSession);
        $liveSession->delete();
        $this->audit('LIVE_SESSION_DELETED', $liveSession);

        return Redirect::route('live-sessions.index')->with('success', 'Session supprimée.');
    }

    // --------------------------------------------------------------------------
    // 9. LIVEKIT WEBSOCKET VIDEO (In-App Video)
    // --------------------------------------------------------------------------

    /**
     * Generate a LiveKit access token for the current user.
     */
    public function livekitToken(LiveSession $liveSession)
    {
        $user = Auth::user();

        if ($liveSession->provider !== self::PROVIDER_LIVEKIT) {
            return response()->json(['error' => 'Cette session n\'utilise pas LiveKit.'], 400);
        }

        $isInstructor = $user->id === $liveSession->instructor_id || $user->hasRole('admin') || $user->hasRole('superadmin');

        try {
            $token = app(LiveKitService::class)->generateToken($liveSession, $user, $isInstructor);

            return response()->json([
                'token' => $token,
                'room' => app(LiveKitService::class)->getRoomName($liveSession),
                'ws_url' => app(LiveKitService::class)->getWsUrl(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ?? $user->profile_photo ?? null,
                ],
                'is_instructor' => $isInstructor,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * List participants in a LiveKit room.
     */
    public function livekitParticipants(LiveSession $liveSession)
    {
        if ($liveSession->provider !== self::PROVIDER_LIVEKIT) {
            return response()->json(['error' => 'Cette session n\'utilise pas LiveKit.'], 400);
        }

        $participants = app(LiveKitService::class)->listParticipants($liveSession);

        return response()->json(['participants' => $participants]);
    }

    /**
     * Start a LiveKit session (marks as Live, creates room).
     */
    public function livekitStart(LiveSession $liveSession)
    {
        if (Auth::id() !== $liveSession->instructor_id && !Auth::user()->hasRole('admin')) {
            abort(403, "Seul l'instructeur assigné peut démarrer ce live.");
        }

        if ($liveSession->provider !== self::PROVIDER_LIVEKIT) {
            return response()->json(['error' => 'Cette session n\'utilise pas LiveKit.'], 400);
        }

        try {
            $roomData = app(LiveKitService::class)->createRoom($liveSession);

            $liveSession->update([
                'status' => self::STATUS_LIVE,
                'actual_start_at' => now(),
                'meeting_url' => $roomData['join_url'],
                'host_url' => $roomData['start_url'],
                'metadata' => array_merge($liveSession->metadata ?? [], [
                    'livekit_room' => $roomData,
                ]),
            ]);

            $this->broadcastLiveStart($liveSession);
            $this->audit('LIVEKIT_SESSION_STARTED', $liveSession);

            return response()->json([
                'room' => $roomData,
                'message' => 'Session LiveKit démarrée.'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Échec du démarrage LiveKit.'], 500);
        }
    }

    /**
     * End a LiveKit session.
     */
    public function livekitEnd(LiveSession $liveSession)
    {
        if (Auth::id() !== $liveSession->instructor_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        if ($liveSession->provider !== self::PROVIDER_LIVEKIT) {
            return response()->json(['error' => 'Cette session n\'utilise pas LiveKit.'], 400);
        }

        try {
            app(LiveKitService::class)->deleteRoom($liveSession);

            $liveSession->update([
                'status' => self::STATUS_ENDED,
                'ended_at' => now(),
            ]);

            $this->audit('LIVEKIT_SESSION_ENDED', $liveSession);

            return response()->json(['message' => 'Session LiveKit terminée.']);
        } catch (Exception $e) {
            return response()->json(['error' => 'Échec de la fermeture.'], 500);
        }
    }

    public function sendChat(LiveSession $liveSession, Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $chatMessage = \App\Models\LiveSessionChatMessage::create([
            'live_session_id' => $liveSession->id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'message' => $request->input('message'),
        ]);

        try {
            broadcast(new \App\Events\LiveSessionChatMessageSent($chatMessage));
        } catch (\Throwable $e) {
            Log::warning('SNC_LIVE_CHAT_BROADCAST_FAIL: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'id' => $chatMessage->id]);
    }

    /**
     * Historique du chat (texte + fichiers) de la session.
     */
    public function chatMessages(LiveSession $liveSession)
    {
        if (! $this->canAccessSession($liveSession, Auth::user())) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $messages = \App\Models\LiveSessionChatMessage::where('live_session_id', $liveSession->id)
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(function ($m) {
                $file = null;
                $decoded = json_decode((string) $m->message, true);
                if (is_array($decoded) && ($decoded['type'] ?? null) === 'file') {
                    $file = $decoded;
                }

                return [
                    'id' => $m->id,
                    'sender' => $m->user_name,
                    'message' => $file ? null : $m->message,
                    'file' => $file,
                    'timestamp' => optional($m->created_at)->format('H:i'),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Partage d'un document dans la session (stockage local privé).
     */
    public function uploadDocument(Request $request, LiveSession $liveSession)
    {
        if (! $this->canAccessSession($liveSession, Auth::user())) {
            return response()->json(['error' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,csv,zip,png,jpg,jpeg,webp,gif,mp4',
        ]);

        $file = $request->file('file');
        $stored = $file->store('live-documents/' . $liveSession->id, 'local');

        if (! $stored) {
            return response()->json(['error' => 'Enregistrement du fichier impossible.'], 500);
        }

        $payload = [
            'type' => 'file',
            'path' => $stored,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'url' => route('live-sessions.documents.download', [$liveSession->id, basename($stored)]),
        ];

        $chatMessage = \App\Models\LiveSessionChatMessage::create([
            'live_session_id' => $liveSession->id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'message' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        try {
            broadcast(new \App\Events\LiveSessionChatMessageSent($chatMessage));
        } catch (\Throwable $e) {
            Log::warning('SNC_LIVE_DOC_BROADCAST_FAIL: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'file' => $payload,
            'id' => $chatMessage->id,
        ]);
    }

    /**
     * Téléchargement sécurisé d'un document partagé.
     */
    public function downloadDocument(LiveSession $liveSession, string $filename)
    {
        if (! $this->canAccessSession($liveSession, Auth::user())) {
            abort(403);
        }

        if ($filename !== basename($filename) || str_contains($filename, '..')) {
            abort(404);
        }

        $path = 'live-documents/' . $liveSession->id . '/' . $filename;

        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
    }

    /**
     * Vérifie qu'un utilisateur peut accéder au contenu de la session.
     */
    private function canAccessSession(LiveSession $session, $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->id === (int) $session->instructor_id) {
            return true;
        }

        if ($user->hasAnyRole(['superadmin', 'admin', 'instructor', 'cohort-assistant'])) {
            return true;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $session->course_id)
            ->whereIn('status', [\App\Models\Enrollment::STATUS_ACTIVE, \App\Models\Enrollment::STATUS_COMPLETED])
            ->exists();
    }

    public function livekitHealth()
    {
        return response()->json(['status' => 'ok', 'message' => 'LiveKit health check']);
    }

    // --------------------------------------------------------------------------
    // FIN DU CONTROLEUR
    // --------------------------------------------------------------------------
}
