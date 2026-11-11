<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class EmailCampaignController extends Controller
{
    /**
     * Audiences ciblables par une campagne.
     */
    private const AUDIENCES = ['all', 'students', 'instructors'];

    /**
     * Requête visant les destinataires selon l'audience de la campagne.
     */
    private function audienceQuery(?string $audience)
    {
        return match ($audience) {
            'students' => User::role('student')->whereNotNull('email')->where('email', '!=', ''),
            'instructors' => User::role('instructor')->whereNotNull('email')->where('email', '!=', ''),
            default => User::whereNotNull('email')->where('email', '!=', ''),
        };
    }

    /**
     * Envoi synchrone de la campagne (simplifié : pas de queue).
     * Retourne ['total', 'sent', 'failed'].
     */
    private function deliverCampaign(EmailCampaign $campaign): array
    {
        $recipients = $this->audienceQuery($campaign->target_audience)
            ->get(['id', 'name', 'email']);

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new CampaignMail($campaign));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Échec envoi campagne #' . $campaign->id . ' vers ' . $recipient->email . ' : ' . $e->getMessage());
            }
        }

        $campaign->update([
            'total_recipients' => $recipients->count(),
            'sent_count' => $sent,
            'bounces_count' => $campaign->bounces_count + $failed,
            'sent_at' => now(),
            'status' => 'sent',
            'scheduled_at' => null,
        ]);

        return ['total' => $recipients->count(), 'sent' => $sent, 'failed' => $failed];
    }

    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'body' => 'required|string',
            'target_audience' => 'required|in:' . implode(',', self::AUDIENCES),
            'status' => 'required|in:draft,scheduled,queued',
            'scheduled_at' => 'nullable|date|after:now',
        ]);
    }

    /**
     * Liste des campagnes avec statistiques réelles.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $campaigns = EmailCampaign::query()
            ->with('user:id,name')
            ->when($search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => EmailCampaign::count(),
            'active' => EmailCampaign::whereIn('status', ['scheduled', 'queued'])->count(),
            'sent' => EmailCampaign::where('status', 'sent')->count(),
            'draft' => EmailCampaign::where('status', 'draft')->count(),
            'sent_total' => (int) EmailCampaign::sum('sent_count'),
        ];

        return Inertia::render('Marketing/Emails', [
            'mode' => 'index',
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Formulaire de création (même page, mode création).
     */
    public function create()
    {
        return Inertia::render('Marketing/Emails', [
            'mode' => 'create',
            'campaign' => null,
        ]);
    }

    /**
     * Enregistre la campagne. Si status = queued, envoi synchrone (simplifié).
     */
    public function store(Request $request)
    {
        $validated = $this->validateCampaign($request);

        if ($validated['status'] === 'scheduled' && empty($validated['scheduled_at'])) {
            return Redirect::back()
                ->withErrors(['scheduled_at' => 'Une date de programmation est requise pour une campagne programmée.'])
                ->withInput();
        }

        try {
            $campaign = EmailCampaign::create($validated + ['user_id' => Auth::id()]);

            if ($campaign->status === 'queued') {
                $result = $this->deliverCampaign($campaign);

                return Redirect::route('marketing.emails.index')
                    ->with('success', "Campagne envoyée à {$result['sent']}/{$result['total']} destinataire(s)" . ($result['failed'] ? " ({$result['failed']} échec(s))." : '.'));
            }

            return Redirect::route('marketing.emails.index')
                ->with('success', 'Campagne créée avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur création campagne : ' . $e->getMessage());

            return Redirect::back()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Aperçu d'une campagne + destinataires ciblés prévus.
     */
    public function show(EmailCampaign $email)
    {
        $email->load('user:id,name,email');

        $recipientsCount = $this->audienceQuery($email->target_audience)->count();
        $recipientsPreview = $this->audienceQuery($email->target_audience)
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'email']);

        return Inertia::render('Marketing/Emails', [
            'mode' => 'show',
            'campaign' => $email,
            'recipientsCount' => $recipientsCount,
            'recipientsPreview' => $recipientsPreview,
        ]);
    }

    /**
     * Formulaire d'édition (même page, mode édition).
     */
    public function edit(EmailCampaign $email)
    {
        if ($email->status === 'sent') {
            return Redirect::route('marketing.emails.show', $email)
                ->with('error', 'Une campagne envoyée ne peut plus être modifiée.');
        }

        return Inertia::render('Marketing/Emails', [
            'mode' => 'edit',
            'campaign' => $email,
        ]);
    }

    /**
     * Met à jour la campagne. Si status devient queued, envoi synchrone.
     */
    public function update(Request $request, EmailCampaign $email)
    {
        if ($email->status === 'sent') {
            return Redirect::back()->with('error', 'Une campagne envoyée ne peut plus être modifiée.');
        }

        $validated = $this->validateCampaign($request);

        if ($validated['status'] === 'scheduled' && empty($validated['scheduled_at'])) {
            return Redirect::back()
                ->withErrors(['scheduled_at' => 'Une date de programmation est requise.'])
                ->withInput();
        }

        try {
            $email->update($validated);

            if ($email->status === 'queued') {
                $result = $this->deliverCampaign($email);

                return Redirect::route('marketing.emails.index')
                    ->with('success', "Campagne envoyée à {$result['sent']}/{$result['total']} destinataire(s)" . ($result['failed'] ? " ({$result['failed']} échec(s))." : '.'));
            }

            return Redirect::route('marketing.emails.index')
                ->with('success', 'Campagne mise à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour campagne #' . $email->id . ' : ' . $e->getMessage());

            return Redirect::back()
                ->with('error', 'Erreur de mise à jour : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime une campagne (brouillons et programmées uniquement).
     */
    public function destroy(EmailCampaign $email)
    {
        if (! in_array($email->status, ['draft', 'scheduled'], true)) {
            return Redirect::back()->with('error', 'Seuls les brouillons et campagnes programmées peuvent être supprimés.');
        }

        try {
            $email->delete();

            return Redirect::route('marketing.emails.index')
                ->with('success', 'Campagne supprimée.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression campagne #' . $email->id . ' : ' . $e->getMessage());

            return Redirect::back()->with('error', 'Impossible de supprimer la campagne.');
        }
    }

    /**
     * Duplique une campagne en nouveau brouillon.
     */
    public function duplicate(EmailCampaign $email)
    {
        try {
            $clone = $email->replicate()->fill([
                'name' => 'Copie ' . ($email->name ?: $email->subject),
                'status' => 'draft',
                'scheduled_at' => null,
                'sent_at' => null,
                'total_recipients' => 0,
                'sent_count' => 0,
                'opens_count' => 0,
                'clicks_count' => 0,
                'bounces_count' => 0,
                'user_id' => Auth::id(),
            ]);
            $clone->save();

            return Redirect::route('marketing.emails.show', $clone)
                ->with('success', 'Campagne dupliquée en brouillon.');
        } catch (\Throwable $e) {
            Log::error('Erreur duplication campagne #' . $email->id . ' : ' . $e->getMessage());

            return Redirect::back()->with('error', 'Erreur lors de la duplication : ' . $e->getMessage());
        }
    }

    /**
     * Envoie immédiatement un email de test via CampaignMail.
     */
    public function sendTest(Request $request, EmailCampaign $email)
    {
        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        $testEmail = $validated['test_email'];

        try {
            Mail::to($testEmail)->send(new CampaignMail($email, $testEmail));

            return Redirect::back()->with('success', "Email de test envoyé à {$testEmail}.");
        } catch (\Throwable $e) {
            Log::error('Erreur envoi test campagne #' . $email->id . ' : ' . $e->getMessage());

            return Redirect::back()->with('error', "Impossible d'envoyer l'email de test : " . $e->getMessage());
        }
    }
}
