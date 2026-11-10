<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Notifications\BaseAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    /**
     * RÃ´les autorisÃ©s Ã  administrer le centre de support
     * (voir et rÃ©pondre Ã  tous les tickets, assigner, changer les statuts).
     */
    private const STAFF_ROLES = ['superadmin', 'admin', 'support-agent', 'moderator'];

    private function isStaff(): bool
    {
        return Auth::user()?->hasAnyRole(self::STAFF_ROLES) ?? false;
    }

    /**
     * Liste des tickets avec filtres, recherche et pagination.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = $this->buildTicketQuery($request);

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $validSorts = ['subject', 'status', 'priority', 'created_at', 'updated_at', 'ticket_number'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderByRaw("FIELD(priority, 'urgent','high','medium','low')")->orderBy('created_at', 'desc');
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        // Statistiques rÃ©elles
        $base = SupportTicket::query();
        $stats = [
            'total'      => (clone $base)->count(),
            'open'       => (clone $base)->whereIn('status', ['open', 'new', 'in_progress'])->count(),
            'pending'    => (clone $base)->where('status', 'pending')->count(),
            'resolved'   => (clone $base)->whereIn('status', ['resolved', 'closed'])->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to_user_id')->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        $categories = TicketCategory::where('is_active', true)->select('id', 'name', 'slug', 'color')->get();
        $agents = $this->staffUsers();

        // AperÃ§u : dernier message + compteur
        $tickets->getCollection()->transform(function ($ticket) {
            $ticket->last_message = $ticket->messages()->with('user:id,name')->first();
            $ticket->messages_count = $ticket->messages()->count();
            $ticket->user?->append('profile_photo_url');
            $ticket->assignedAgent?->append('profile_photo_url');
            return $ticket;
        });

        return Inertia::render('Social/Support', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status', 'priority', 'category_id', 'assigned_to_user_id', 'unassigned', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
            'categories' => $categories,
            'agents' => $agents,
            'canManage' => $this->isStaff(),
            'auth' => ['user' => Auth::user()],
        ]);
    }

    /**
     * CrÃ©ation : gÃ©rÃ©e par le dialogue de la page index.
     */
    public function create()
    {
        return Redirect::route('social.support.index');
    }

    /**
     * Enregistre un nouveau ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        DB::beginTransaction();
        try {
            $ticket = SupportTicket::create([
                'user_id' => Auth::id(),
                'ticket_category_id' => $validated['ticket_category_id'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'ticket_number' => $this->generateUniqueTicketNumber(),
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $validated['description'],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la crÃ©ation du ticket.')
                ->withInput();
        }

        // Notifier l'Ã©quipe support
        try {
            foreach (User::role(self::STAFF_ROLES)->get() as $agent) {
                if ($agent->id === Auth::id()) continue;
                $agent->notify(new BaseAppNotification(
                    title: 'Nouveau ticket de support',
                    message: "{$ticket->ticket_number} â€” {$ticket->subject} (prioritÃ© : {$ticket->priority})",
                    icon: 'pi-ticket',
                    severity: $ticket->priority === 'urgent' ? 'danger' : 'warning',
                    url: route('social.support.show', $ticket->ticket_number),
                    data: ['ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number],
                ));
            }
        } catch (\Throwable $e) {
            \Log::warning('Support ticket notification failed: ' . $e->getMessage());
        }

        return Redirect::route('social.support.show', $ticket->ticket_number)
            ->with('success', "Ticket {$ticket->ticket_number} crÃ©Ã© avec succÃ¨s. Notre Ã©quipe vous rÃ©pondra bientÃ´t.");
    }

    /**
     * DÃ©tail d'un ticket + fil de messages.
     */
    public function show(SupportTicket $support)
    {
        $support->load([
            'user:id,name,email,profile_photo,avatar',
            'assignedAgent:id,name,email,profile_photo,avatar',
            'category:id,name,slug,color',
        ]);

        $isStaff = $this->isStaff();

        // Un non-staff ne peut voir que ses propres tickets
        abort_unless($isStaff || $support->user_id === Auth::id(), 403);

        // Les messages internes ne sont visibles que par le staff
        $messages = $support->messages()
            ->with('user:id,name,email,profile_photo,avatar')
            ->when(!$isStaff, fn ($q) => $q->where('is_internal', false))
            ->reorder()
            ->oldest()
            ->paginate(50);

        $messages->getCollection()->transform(function ($msg) {
            $msg->user?->append('profile_photo_url');
            return $msg;
        });

        // Le staff "prend en charge" un ticket ouvert quand il le consulte
        if ($isStaff && in_array($support->status, ['open', 'new']) && !$support->assigned_to_user_id) {
            $support->update(['status' => 'in_progress', 'assigned_to_user_id' => Auth::id()]);
        }

        return Inertia::render('Social/Support', [
            'tickets' => null,
            'ticket' => tap($support->fresh(['user', 'assignedAgent', 'category']), function ($t) {
                $t->user?->append('profile_photo_url');
                $t->assignedAgent?->append('profile_photo_url');
            }),
            'messages' => $messages,
            'stats' => $this->quickStats(),
            'categories' => TicketCategory::where('is_active', true)->select('id', 'name', 'slug', 'color')->get(),
            'agents' => $this->staffUsers(),
            'canManage' => $isStaff,
            'auth' => ['user' => Auth::user()],
            'view' => 'detail',
        ]);
    }

    /**
     * Ã‰dition (staff uniquement).
     */
    public function edit(SupportTicket $support)
    {
        abort_unless($this->isStaff(), 403);
        return Redirect::route('social.support.show', $support->ticket_number);
    }

    /**
     * Mise Ã  jour d'un ticket (staff uniquement).
     */
    public function update(Request $request, SupportTicket $support)
    {
        abort_unless($this->isStaff(), 403);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,new,in_progress,pending,resolved,closed',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ]);

        $support->update($validated);

        return Redirect::back()->with('success', 'Ticket mis Ã  jour.');
    }

    /**
     * Suppression (soft delete, staff uniquement).
     */
    public function destroy(SupportTicket $support)
    {
        abort_unless($this->isStaff(), 403);
        $support->delete();
        return Redirect::route('social.support.index')->with('success', 'Ticket supprimÃ©.');
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless($this->isStaff(), 403);
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:support_tickets,id']);

        $count = SupportTicket::whereIn('id', $request->ids)->count();
        SupportTicket::whereIn('id', $request->ids)->delete();

        return Redirect::back()->with('success', "{$count} ticket(s) supprimÃ©(s).");
    }

    /**
     * Ajout d'une rÃ©ponse / note interne.
     */
    public function reply(Request $request, SupportTicket $support)
    {
        $isStaff = $this->isStaff();
        abort_unless($isStaff || $support->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'message' => 'required|string|min:2|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        // Les notes internes sont rÃ©servÃ©es au staff
        $isInternal = $isStaff && $request->boolean('is_internal');

        $msg = SupportMessage::create([
            'support_ticket_id' => $support->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_internal' => $isInternal,
        ]);

        // Statut : le staff qui rÃ©pond passe le ticket en cours ; le client qui rÃ©pond le remet en attente
        if ($isStaff) {
            $support->update(['status' => 'in_progress']);
        } else {
            $support->update(['status' => 'pending']);
        }

        // Notification Ã  l'autre partie
        try {
            $notifiable = $isStaff ? $support->user : ($support->assignedAgent ?? $support->user);
            if ($notifiable && $notifiable->id !== Auth::id()) {
                $notifiable->notify(new BaseAppNotification(
                    title: $isInternal ? 'Note interne sur votre ticket' : 'Nouvelle rÃ©ponse sur votre ticket',
                    message: $isStaff
                        ? "L'Ã©quipe support a rÃ©pondu Ã  Â« {$support->subject} Â» ({$support->ticket_number})."
                        : Auth::user()->name . " a rÃ©pondu Ã  Â« {$support->subject} Â».",
                    icon: 'pi-comments',
                    severity: 'info',
                    url: $isStaff ? route('social.support.show', $support->ticket_number) : route('student.support.show', $support->ticket_number),
                    data: ['ticket_id' => $support->id],
                ));
            }
        } catch (\Throwable $e) {
            \Log::warning('Ticket reply notification failed: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', $isInternal ? 'Note interne enregistrÃ©e.' : 'RÃ©ponse envoyÃ©e.');
    }

    /**
     * Assignation d'un agent (staff uniquement).
     */
    public function assign(Request $request, SupportTicket $support)
    {
        abort_unless($this->isStaff(), 403);

        $validated = $request->validate([
            'assigned_to_user_id' => 'required|exists:users,id',
        ]);

        $support->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'status' => 'in_progress',
        ]);

        try {
            User::find($validated['assigned_to_user_id'])?->notify(new BaseAppNotification(
                title: 'Ticket de support assignÃ©',
                message: "Le ticket {$support->ticket_number} â€” {$support->subject} vous est assignÃ©.",
                icon: 'pi-user-edit',
                severity: 'info',
                url: route('social.support.show', $support->ticket_number),
                data: ['ticket_id' => $support->id],
            ));
        } catch (\Throwable $e) {
            \Log::warning('Ticket assign notification failed: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', 'Ticket assignÃ©.');
    }

    /**
     * Changement rapide de statut (staff uniquement).
     */
    public function changeStatus(Request $request, SupportTicket $support)
    {
        abort_unless($this->isStaff(), 403);

        $validated = $request->validate([
            'status' => 'required|in:open,new,in_progress,pending,resolved,closed',
        ]);

        $support->update(['status' => $validated['status']]);

        try {
            $support->user?->notify(new BaseAppNotification(
                title: 'Statut de votre ticket mis Ã  jour',
                message: "Le ticket {$support->ticket_number} â€” {$support->subject} est maintenant : " . $support->status_label,
                icon: 'pi-sync',
                severity: in_array($validated['status'], ['resolved', 'closed']) ? 'success' : 'info',
                url: route('student.support.show', $support->ticket_number),
                data: ['ticket_id' => $support->id],
            ));
        } catch (\Throwable $e) {
            \Log::warning('Ticket status notification failed: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', 'Statut mis Ã  jour.');
    }

    /**
     * Export CSV des tickets filtrÃ©s.
     */
    public function export(Request $request)
    {
        abort_unless($this->isStaff(), 403);

        $fileName = 'support_tickets_' . date('Y-m-d_His') . '.csv';
        $tickets = $this->buildTicketQuery($request)->with(['user', 'category', 'assignedAgent'])->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel
            fputcsv($file, ['ID', 'NÂ° Ticket', 'Sujet', 'Demandeur', 'Email', 'CatÃ©gorie', 'PrioritÃ©', 'Statut', 'AssignÃ© Ã ', 'Messages', 'CrÃ©Ã© le', 'Mis Ã  jour le'], ';');

            foreach ($tickets as $t) {
                fputcsv($file, [
                    $t->id,
                    $t->ticket_number,
                    $t->subject,
                    $t->user->name ?? 'N/A',
                    $t->user->email ?? 'N/A',
                    $t->category->name ?? 'N/A',
                    $t->priority,
                    $t->status_label,
                    $t->assignedAgent->name ?? 'Non assignÃ©',
                    $t->messages()->count(),
                    $t->created_at?->format('d/m/Y H:i'),
                    $t->updated_at?->format('d/m/Y H:i'),
                ], ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function staffUsers()
    {
        return User::role(self::STAFF_ROLES)
            ->select('id', 'name', 'email', 'profile_photo', 'avatar')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->profile_photo_url,
            ]);
    }

    private function quickStats(): array
    {
        $base = SupportTicket::query();
        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereIn('status', ['open', 'new', 'in_progress'])->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'resolved' => (clone $base)->whereIn('status', ['resolved', 'closed'])->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to_user_id')->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];
    }

    private function generateUniqueTicketNumber(): string
    {
        do {
            $number = 'ticket-' . Str::lower(Str::random(6));
        } while (SupportTicket::where('ticket_number', $number)->exists());
        return $number;
    }

    private function buildTicketQuery(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $categoryId = $request->input('category_id');
        $assignedToUserId = $request->input('assigned_to_user_id');

        $query = SupportTicket::with([
                'user:id,name,email,profile_photo,avatar',
                'assignedAgent:id,name,email,profile_photo,avatar',
                'category:id,name,slug,color',
            ])
            ->withCount('messages');

        // Un utilisateur normal ne voit que ses propres tickets
        if (!$this->isStaff()) {
            $query->where('user_id', Auth::id());
        }

        return $query
            ->when($search, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('subject', 'like', "%{$search}%")
                        ->orWhere('ticket_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($categoryId, fn ($q, $cid) => $q->where('ticket_category_id', $cid))
            ->when($assignedToUserId === 'unassigned', fn ($q) => $q->whereNull('assigned_to_user_id'))
            ->when($assignedToUserId && $assignedToUserId !== 'unassigned', fn ($q, $aid) => $q->where('assigned_to_user_id', $aid))
            ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('assigned_to_user_id')->whereNotIn('status', ['resolved', 'closed']));
    }
}
