<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailCampaign; // Modèle à créer
use App\Models\EmailList; // Modèle à créer
use App\Models\EmailTemplate; // Modèle à créer
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
// use App\Jobs\SendEmailCampaignJob; // Job à créer pour l'envoi en arrière-plan
// use App\Mail\CampaignMail; // Mailable à créer

class EmailCampaignController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares de permissions.
     */
    public function __construct()
    {
        // Exemple de permissions
        // $this->middleware('can:view-campaigns')->only(['index', 'show', 'export']);
        // $this->middleware('can:manage-campaigns')->except(['index', 'show', 'export']);
    }

    /**
     * Affiche la liste des campagnes email.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = EmailCampaign::query()
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            })
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            });

        $validSorts = ['name', 'subject', 'status', 'scheduled_at', 'sent_at', 'created_at'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $campaigns = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => EmailCampaign::count(),
            'draft' => EmailCampaign::where('status', 'draft')->count(),
            'scheduled' => EmailCampaign::where('status', 'scheduled')->count(),
            'sent' => EmailCampaign::where('status', 'sent')->count(),
        ];

        return Inertia::render('Marketing/Emails', [
            'campaigns' => $campaigns,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
        ]);
    }

    /**
     * Affiche le formulaire de création d'une campagne.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // Simule la récupération des listes de diffusion et des templates
        $emailLists = []; // EmailList::get(['id', 'name']);
        $emailTemplates = []; // EmailTemplate::get(['id', 'name']);

        return Inertia::render('Marketing/Emails', [
            'emailLists' => $emailLists,
            'emailTemplates' => $emailTemplates,
        ]);
    }

    /**
     * Enregistre une nouvelle campagne.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'html_content' => 'required|string',
            'email_list_id' => 'nullable|exists:email_lists,id',
            'status' => 'required|in:draft,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validated['status'] === 'scheduled' && empty($validated['scheduled_at'])) {
            return Redirect::back()->withErrors(['scheduled_at' => 'Une date de programmation est requise pour une campagne programmée.'])->withInput();
        }

        try {
            EmailCampaign::create($validated + ['user_id' => Auth::id()]);

            return Redirect::route('marketing.campaigns.index')
                ->with('success', 'Campagne créée avec succès.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur lors de la création: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails et statistiques d'une campagne.
     *
     * @param EmailCampaign $campaign
     * @return \Inertia\Response
     */
    public function show(EmailCampaign $campaign)
    {
        // $campaign->load('emailList');

        // Simulation de données de performance
        $performance_chart = [
            'labels' => ['Ouvertures', 'Clics', 'Désabonnements', 'Rebonds'],
            'data' => [
                $campaign->opens_count ?? 0,
                $campaign->clicks_count ?? 0,
                $campaign->unsubscribes_count ?? 0,
                $campaign->bounces_count ?? 0,
            ],
        ];

        return Inertia::render('Marketing/Emails', [
            'campaign' => $campaign,
            'performance_chart' => $performance_chart,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'une campagne.
     *
     * @param EmailCampaign $campaign
     * @return \Inertia\Response
     */
    public function edit(EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return Redirect::route('marketing.campaigns.show', $campaign)
                ->with('warning', 'Seules les campagnes en brouillon ou programmées peuvent être modifiées.');
        }

        $emailLists = []; // EmailList::get(['id', 'name']);
        $emailTemplates = []; // EmailTemplate::get(['id', 'name']);

        return Inertia::render('Marketing/Emails', [
            'campaign' => $campaign,
            'emailLists' => $emailLists,
            'emailTemplates' => $emailTemplates,
        ]);
    }

    /**
     * Met à jour une campagne.
     *
     * @param Request $request
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return Redirect::back()->with('error', 'Cette campagne ne peut plus être modifiée.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'html_content' => 'required|string',
            'email_list_id' => 'nullable|exists:email_lists,id',
            'status' => 'required|in:draft,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($validated['status'] === 'scheduled' && empty($validated['scheduled_at'])) {
            return Redirect::back()->withErrors(['scheduled_at' => 'Une date de programmation est requise.'])->withInput();
        }

        try {
            $campaign->update($validated);

            return Redirect::route('marketing.campaigns.index')
                ->with('success', 'Campagne mise à jour.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprime une campagne (uniquement si en brouillon).
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(EmailCampaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return Redirect::back()->with('error', 'Seuls les brouillons peuvent être supprimés.');
        }

        try {
            $campaign->delete();
            return Redirect::route('marketing.campaigns.index')
                ->with('success', 'Campagne supprimée.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Impossible de supprimer la campagne.');
        }
    }

    /**
     * Duplique une campagne existante pour en créer un nouveau brouillon.
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(EmailCampaign $campaign)
    {
        try {
            $newCampaign = $campaign->replicate()->fill([
                'name' => $campaign->name . ' (Copie)',
                'status' => 'draft',
                'scheduled_at' => null,
                'sent_at' => null,
                'opens_count' => 0,
                'clicks_count' => 0,
            ]);
            $newCampaign->save();

            return Redirect::route('marketing.campaigns.edit', $newCampaign)
                ->with('success', 'Campagne dupliquée. Vous pouvez maintenant la modifier.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur lors de la duplication: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de test.
     *
     * @param Request $request
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendTest(Request $request, EmailCampaign $campaign)
    {
        $request->validate(['email' => 'required|email']);
        $testEmail = $request->input('email');

        try {
            // On simule la création d'un Mailable
            // Mail::to($testEmail)->send(new CampaignMail($campaign));

            return Redirect::back()->with('success', "Email de test envoyé à {$testEmail}.");
        } catch (\Exception $e) {
            return Redirect::back()->with('error', "Impossible d'envoyer l'email de test: " . $e->getMessage());
        }
    }

    /**
     * Met en file d'attente l'envoi d'une campagne.
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send(EmailCampaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return Redirect::back()->with('warning', 'Cette campagne n\'est pas un brouillon.');
        }
        if (empty($campaign->email_list_id)) {
            return Redirect::back()->with('error', 'Aucune liste de diffusion n\'est associée à cette campagne.');
        }

        try {
            $campaign->update([
                'status' => 'queued',
                'scheduled_at' => now(), // On envoie maintenant
            ]);

            // Déclencher le job d'envoi
            // SendEmailCampaignJob::dispatch($campaign);

            return Redirect::route('marketing.campaigns.show', $campaign)
                ->with('success', 'La campagne a été mise dans la file d\'attente pour envoi.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur lors du lancement de la campagne: ' . $e->getMessage());
        }
    }
}
