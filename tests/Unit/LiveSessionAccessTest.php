<?php

use App\Models\Course;
use App\Models\LiveAttendance;
use App\Models\LiveSession;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\CheckSessionInDatabase::class);
});

function liveTestSession(array $attributes = []): LiveSession
{
    return LiveSession::create(array_merge([
        'course_id' => Course::query()->value('id'),
        'instructor_id' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'admin', 'superadmin']))->value('id'),
        'title' => 'Session de test LiveKit',
        'start_at' => now(),
        'duration' => 60,
        'provider' => LiveSession::PROVIDER_LIVEKIT,
        'status' => LiveSession::STATUS_SCHEDULED,
        'max_participants' => 50,
    ], $attributes));
}

it('expose un token LiveKit avec la bonne URL WebSocket au formateur', function () {
    $session = liveTestSession();

    $response = $this->actingAs(User::findOrFail(1))
        ->getJson(route('live-sessions.livekit-token', $session))
        ->assertOk()
        ->assertJsonStructure(['token', 'room', 'ws_url', 'user' => ['id', 'name'], 'is_instructor'])
        ->assertJsonPath('is_instructor', true);

    expect($response->json('ws_url'))->toStartWith('wss://');
    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('permet à un apprenant inscrit de voir ses lives et de rejoindre', function () {
    $session = liveTestSession();
    $student = User::findOrFail(10);

    $this->actingAs($student)->get(route('live-sessions.mine'))->assertOk();

    $this->actingAs($student)
        ->get(route('live-sessions.join', $session))
        ->assertOk();

    expect(LiveAttendance::where('live_session_id', $session->id)->where('user_id', $student->id)->exists())->toBeTrue();
});

it('refuse un apprenant non inscrit au cours', function () {
    $session = liveTestSession();

    $outsider = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
        ->whereDoesntHave('enrollments', fn ($q) => $q->where('course_id', $session->course_id))
        ->where('id', '!=', $session->instructor_id)
        ->first();

    if (! $outsider) {
        $this->markTestSkipped('Aucun utilisateur non inscrit disponible.');
    }

    $this->actingAs($outsider)
        ->get(route('live-sessions.join', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('LiveSessions/RequestAccess'));
});

it('permet de demander puis approuver un acces', function () {
    $session = liveTestSession();

    $outsider = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
        ->whereDoesntHave('enrollments', fn ($q) => $q->where('course_id', $session->course_id))
        ->first();

    if (! $outsider) {
        $this->markTestSkipped('Aucun utilisateur non inscrit disponible.');
    }

    $this->actingAs($outsider)
        ->from(route('live-sessions.mine'))
        ->post(route('live-sessions.request-access', $session))
        ->assertRedirect(route('live-sessions.mine'));

    $session->refresh();
    expect($session->hasPendingAccess($outsider->id))->toBeTrue();

    $this->actingAs(User::findOrFail(1))
        ->post(route('live-sessions.join-requests.approve', $session), ['user_id' => $outsider->id])
        ->assertRedirect();

    $session->refresh();
    expect($session->hasApprovedAccess($outsider->id))->toBeTrue();

    $this->actingAs($outsider)
        ->get(route('live-sessions.join', $session))
        ->assertOk();
});

it('affiche la liste publique des sessions', function () {
    $session = liveTestSession();
    $settings = $session->settings ?? [];
    $settings['visibility'] = 'public';
    $session->settings = $settings;
    $session->save();

    $this->get(route('live-sessions.public'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('LiveSessions/PublicIndex'));
});

it('bloque l entree dans une session terminee', function () {
    $session = liveTestSession(['status' => LiveSession::STATUS_ENDED]);

    $this->actingAs(User::findOrFail(10))
        ->from(route('live-sessions.mine'))
        ->get(route('live-sessions.join', $session))
        ->assertRedirect(route('live-sessions.mine'))
        ->assertSessionHas('error');
});

it('cree une session LiveKit sans erreur de colonne', function () {
    $course = Course::query()->first();
    $instructor = User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'admin', 'superadmin']))->first();

    $this->actingAs(User::findOrFail(1))
        ->post(route('live-sessions.store'), [
            'title' => 'Cours en direct - test',
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'start_at' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'provider' => LiveSession::PROVIDER_LIVEKIT,
            'max_participants' => 30,
            'is_recorded' => false,
            'settings' => ['allow_chat' => '1'],
        ])
        ->assertRedirect(route('live-sessions.index'))
        ->assertSessionHas('success');

    $created = LiveSession::where('title', 'Cours en direct - test')->first();

    expect($created)->not->toBeNull();
    expect($created->settings)->toMatchArray(['allow_chat' => true]);
});

it('affiche le rapport de presence sans erreur', function () {
    $session = liveTestSession();
    $student = User::findOrFail(10);

    LiveAttendance::create([
        'live_session_id' => $session->id,
        'user_id' => $student->id,
        'joined_at' => now()->subMinutes(10),
        'seconds_spent' => 600,
        'last_ping_at' => now(),
    ]);

    $this->actingAs(User::findOrFail(1))
        ->get(route('live-sessions.report', $session))
        ->assertOk();
});

it('envoie un message de chat et le relit', function () {
    $session = liveTestSession();

    $this->actingAs(User::findOrFail(10))
        ->postJson(route('live-sessions.chat.send', $session), ['message' => 'Bonjour a tous'])
        ->assertOk();

    $this->actingAs(User::findOrFail(10))
        ->getJson(route('live-sessions.chat.messages', $session))
        ->assertOk()
        ->assertJsonPath('messages.0.message', 'Bonjour a tous');
});

it('partage puis telecharge un document', function () {
    \Illuminate\Support\Facades\Storage::fake('local');

    $session = liveTestSession();
    $student = User::findOrFail(10);
    $file = \Illuminate\Http\UploadedFile::fake()->create('support.pdf', 200, 'application/pdf');

    $response = $this->actingAs($student)
        ->post(route('live-sessions.documents.upload', $session), ['file' => $file])
        ->assertOk()
        ->assertJsonPath('file.name', 'support.pdf');

    $this->actingAs($student)
        ->get($response->json('file.url'))
        ->assertOk();
});

it('affiche la liste admin des sessions sans erreur', function () {
    liveTestSession();

    $this->actingAs(User::findOrFail(1))
        ->get(route('live-sessions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('LiveSessions'));
});

it('envoie une invitation email et une notification aux invites', function () {
    \Illuminate\Support\Facades\Notification::fake();

    $session = liveTestSession();
    $student = User::findOrFail(10);

    $this->actingAs(User::findOrFail(1))
        ->post(route('live-sessions.invitations', $session), [
            'emails' => $student->email . ', externe@example.com',
            'visibility' => 'private',
        ])
        ->assertRedirect();

    $session->refresh();
    expect($session->invitedEmails())->toContain(strtolower($student->email));
    expect($session->invitedEmails())->toContain('externe@example.com');

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $student,
        \App\Notifications\LiveSessionInvitationNotification::class
    );

    \Illuminate\Support\Facades\Notification::assertSentOnDemand(
        \App\Notifications\LiveSessionInvitationNotification::class
    );
});

it('genere un lien de partage opaque et le resout', function () {
    $session = liveTestSession();
    $admin = User::findOrFail(1);

    $response = $this->actingAs($admin)
        ->postJson(route('live-sessions.share-link', $session))
        ->assertOk()
        ->assertJsonStructure(['url', 'token']);

    $url = $response->json('url');
    $token = $response->json('token');

    expect($token)->toBeString()->not->toBeEmpty();
    expect($url)->toContain('/l/' . $token);
    expect($url)->not->toContain('/live-sessions/' . $session->id);

    // Invité non connecté : redirection vers la connexion.
    auth()->logout();
    $this->get(route('live-sessions.shared', $token))->assertRedirect(route('login'));

    // Utilisateur autorisé : redirection vers la salle.
    $this->actingAs(User::findOrFail(10))
        ->get(route('live-sessions.shared', $token))
        ->assertRedirect(route('live-sessions.join', $session));

    $this->get(route('live-sessions.shared', 'jeton-inexistant'))->assertNotFound();
});

it('enregistre la configuration des fournisseurs video', function () {
    \App\Services\Video\VideoProviderConfig::save([
        'zoom_account_id' => 'acc-123',
        'zoom_client_id' => 'client-123',
        'zoom_client_secret' => 'secret-123',
        'default_provider' => 'zoom',
    ]);

    \App\Services\Video\VideoProviderConfig::apply();

    expect(\App\Services\Video\VideoProviderConfig::isConfigured('zoom'))->toBeTrue();
    expect(config('services.zoom.client_id'))->toBe('client-123');
    expect(\App\Services\Video\VideoProviderConfig::defaultProvider())->toBe('zoom');

    \App\Services\Video\VideoProviderConfig::flush();
});

it('affiche et met a jour la page de configuration video', function () {
    $admin = User::findOrFail(1);

    $this->actingAs($admin)
        ->get(route('system.settings.video'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('System/VideoProviders'));

    $this->actingAs($admin)
        ->post(route('system.settings.video.update'), [
            'default_provider' => 'livekit',
            'livekit_url' => 'https://demo.livekit.cloud',
            'livekit_ws_url' => 'wss://demo.livekit.cloud',
        ])
        ->assertRedirect();

    expect(\App\Services\Video\VideoProviderConfig::get('livekit_url'))->toBe('https://demo.livekit.cloud');

    \App\Services\Video\VideoProviderConfig::flush();
});

