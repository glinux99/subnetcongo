<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket; // Modèle à créer
use App\Models\SupportMessage; // Modèle à créer
use App\Models\TicketCategory; // Modèle à créer
use App\Models\User; // Pour les agents et les utilisateurs
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares de permissions.
     */
    public function __construct()
    {
        // Exemple de permissions
        // $this->middleware('can:view-tickets')->only(['index', 'show', 'export']);
        // $this->middleware('can:manage-tickets')->except(['index', 'show', 'export']);
    }

    /**
     * Affiche la liste des tickets de support avec filtres, recherche et pagination.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = $this->buildTicketQuery($request);

        // Tri
        $validSorts = ['subject', 'status', 'priority', 'created_at', 'updated_at'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        // Statistiques pour l'en-tête
        $stats = [
            'total_tickets' => SupportTicket::count(),
            'open_tickets' => SupportTicket::where('status', 'open')->count(),
            'closed_tickets' => SupportTicket::where('status', 'closed')->count(),
            'assigned_tickets' => SupportTicket::whereNotNull('assigned_to_user_id')->count(),
            'unassigned_tickets' => SupportTicket::whereNull('assigned_to_user_id')->where('status', 'open')->count(),
        ];

        $categories = TicketCategory::select('id', 'name')->get();
        $agents = User::whereHas('roles', fn($q) => $q->where('name', 'agent'))->select('id', 'name')->get(); // Supposons un rôle 'agent'

        return Inertia::render('Social/Support', [
            'tickets' => $tickets,
            'filters' => $request->all(['search', 'status', 'priority', 'category_id', 'assigned_to_user_id', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
            'categories' => $categories,
            'agents' => $agents,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau ticket de support.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $categories = TicketCategory::select('id', 'name')->get();
        $priorities = ['low', 'medium', 'high'];

        return Inertia::render('Social/Support', [
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    /**
     * Enregistre un nouveau ticket de support.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,medium,high',
        ]);

        DB::beginTransaction();
        try {
            $ticket = SupportTicket::create([
                'user_id' => Auth::id(),
                'ticket_category_id' => $validated['ticket_category_id'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => 'open', // Par défaut
                'ticket_number' => $this->generateUniqueTicketNumber(),
            ]);

            // Créer le premier message du ticket (la description initiale)
            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $validated['description'],
            ]);

            DB::commit();

            // Envoyer une notification à l'équipe de support
            // Notification::send(User::whereHas('roles', fn($q) => $q->where('name', 'agent'))->get(), new NewTicketNotification($ticket));

            return Redirect::route('social.tickets.show', $ticket->id)
                ->with('success', 'Ticket créé avec succès. Un agent vous répondra bientôt.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la création du ticket: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche un ticket de support spécifique et ses messages.
     *
     * @param SupportTicket $ticket
     * @return \Inertia\Response
     */
    public function show(SupportTicket $ticket)
    {
        $ticket->load(['user:id,name,profile_photo_path', 'assignedAgent:id,name,profile_photo_path', 'category:id,name', 'messages.user:id,name,profile_photo_path']);

        // Marquer le ticket comme "vu" par l'agent si c'est un agent qui le consulte
        if (Auth::user()->hasRole('agent') && $ticket->status === 'open') {
            $ticket->update(['status' => 'pending']);
        }

        return Inertia::render('Social/Support', [
            'ticket' => $ticket,
            'messages' => $ticket->messages()->with('user:id,name,profile_photo_path')->latest()->paginate(10),
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un ticket de support.
     *
     * @param SupportTicket $ticket
     * @return \Inertia\Response
     */
    public function edit(SupportTicket $ticket)
    {
        $this->authorize('update', $ticket); // Exemple de permission

        $categories = TicketCategory::select('id', 'name')->get();
        $priorities = ['low', 'medium', 'high'];
        $agents = User::whereHas('roles', fn($q) => $q->where('name', 'agent'))->select('id', 'name')->get();

        return Inertia::render('Social/Support', [
            'ticket' => $ticket,
            'categories' => $categories,
            'priorities' => $priorities,
            'agents' => $agents,
        ]);
    }

    /**
     * Met à jour un ticket de support.
     *
     * @param Request $request
     * @param SupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket); // Exemple de permission

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10', // Peut-être la description initiale
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:open,pending,closed,resolved',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $ticket->update($validated);

            return Redirect::route('social.tickets.show', $ticket->id)
                ->with('success', 'Ticket mis à jour avec succès.');

        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur lors de la mise à jour du ticket: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un ticket de support (soft delete).
     *
     * @param SupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(SupportTicket $ticket)
    {
        $this->authorize('delete', $ticket); // Exemple de permission

        try {
            $ticket->delete(); // Soft delete
            return Redirect::route('social.tickets.index')
                ->with('success', 'Ticket supprimé.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Impossible de supprimer le ticket: ' . $e->getMessage());
        }
    }

    /**
     * Suppression en masse de tickets de support.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:support_tickets,id']);

        // $this->authorize('bulkDelete', SupportTicket::class); // Exemple de permission

        $count = SupportTicket::whereIn('id', $request->ids)->count();
        SupportTicket::whereIn('id', $request->ids)->delete(); // Soft delete

        return Redirect::back()->with('success', "$count tickets ont été supprimés.");
    }

    /**
     * Ajoute une réponse à un ticket.
     *
     * @param Request $request
     * @param SupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:5',
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
        ]);

        $ticket->update(['status' => 'pending', 'updated_at' => now()]); // Mettre à jour le statut et la date

        // Envoyer une notification à l'utilisateur ou à l'agent assigné
        // $ticket->user->notify(new TicketRepliedNotification($ticket, Auth::user()));

        return Redirect::back()->with('success', 'Votre réponse a été ajoutée.');
    }

    /**
     * Assigne un ticket à un agent.
     *
     * @param Request $request
     * @param SupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $this->authorize('assign', $ticket); // Exemple de permission

        $validated = $request->validate([
            'assigned_to_user_id' => 'required|exists:users,id',
        ]);

        $ticket->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'status' => 'pending', // Changer le statut si assigné
        ]);

        // Envoyer une notification à l'agent assigné
        // User::find($validated['assigned_to_user_id'])->notify(new TicketAssignedNotification($ticket));

        return Redirect::back()->with('success', 'Ticket assigné avec succès.');
    }

    /**
     * Change le statut d'un ticket.
     *
     * @param Request $request
     * @param SupportTicket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorize('changeStatus', $ticket); // Exemple de permission

        $validated = $request->validate([
            'status' => 'required|in:open,pending,closed,resolved',
        ]);

        $ticket->update(['status' => $validated['status']]);

        // Envoyer une notification à l'utilisateur si le statut change
        // $ticket->user->notify(new TicketStatusUpdatedNotification($ticket));

        return Redirect::back()->with('success', 'Statut du ticket mis à jour.');
    }

    /**
     * Exporte les données des tickets de support en CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $fileName = 'support_tickets_export_' . date('Y-m-d') . '.csv';

        $tickets = $this->buildTicketQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Numéro Ticket', 'Sujet', 'Description', 'Utilisateur', 'Catégorie', 'Priorité', 'Statut', 'Assigné à', 'Créé le', 'Dernière Mise à Jour'];

        $callback = function() use($tickets, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($tickets as $ticket) {
                $row['ID'] = $ticket->id;
                $row['Numéro Ticket'] = $ticket->ticket_number;
                $row['Sujet'] = $ticket->subject;
                $row['Description'] = \Str::limit($ticket->description, 100);
                $row['Utilisateur'] = $ticket->user->name ?? 'N/A';
                $row['Catégorie'] = $ticket->category->name ?? 'N/A';
                $row['Priorité'] = $ticket->priority;
                $row['Statut'] = $ticket->status;
                $row['Assigné à'] = $ticket->assignedAgent->name ?? 'Non assigné';
                $row['Créé le'] = $ticket->created_at->format('Y-m-d H:i');
                $row['Dernière Mise à Jour'] = $ticket->updated_at->format('Y-m-d H:i');

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Génère un numéro de ticket unique.
     *
     * @return string
     */
    private function generateUniqueTicketNumber(): string
    {
        do {
            $number = 'TICKET-' . strtoupper(\Str::random(6));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * Construit la requête de base pour les tickets de support avec les filtres communs.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTicketQuery(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $categoryId = $request->input('category_id');
        $assignedToUserId = $request->input('assigned_to_user_id');

        return SupportTicket::with(['user:id,name,profile_photo_path', 'assignedAgent:id,name,profile_photo_path', 'category:id,name'])
            ->when($search, function ($q, $search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%");
                  });
            })
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($priority, function ($q, $priority) {
                $q->where('priority', $priority);
            })
            ->when($categoryId, function ($q, $categoryId) {
                $q->where('ticket_category_id', $categoryId);
            })
            ->when($assignedToUserId, function ($q, $assignedToUserId) {
                $q->where('assigned_to_user_id', $assignedToUserId);
            })
            ->when($request->boolean('unassigned'), function ($q) {
                $q->whereNull('assigned_to_user_id')->where('status', 'open');
            });
    }
}

