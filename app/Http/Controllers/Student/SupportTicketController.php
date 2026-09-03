<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->tickets()->with('category:id,name,color');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('subject', 'like', "%{$s}%")
                  ->orWhere('message', 'like', "%{$s}%")
                  ->orWhere('ticket_number', 'like', "%{$s}%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($cat = $request->input('category_id')) {
            $query->where('ticket_category_id', $cat);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $categories = \Illuminate\Support\Facades\Schema::hasTable('ticket_categories')
            ? TicketCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color'])
            : collect();

        $stats = [
            'open'     => $user->tickets()->whereIn('status', ['open', 'new'])->count(),
            'pending'  => $user->tickets()->where('status', 'pending')->count(),
            'resolved' => $user->tickets()->whereIn('status', ['resolved', 'closed'])->count(),
            'total'    => $user->tickets()->count(),
        ];

        return Inertia::render('Student/SupportTickets/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->all(['status', 'category_id', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        $categories = TicketCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color']);
        return Inertia::render('Student/SupportTickets/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'        => 'required|string|max:255',
            'message'        => 'required|string|max:5000',
            'description'    => 'nullable|string|max:5000',
            'category_id'    => 'nullable|exists:ticket_categories,id',
            'priority'       => 'required|in:low,medium,high,urgent',
        ]);

        $user = $request->user();
        $ticket = $user->tickets()->create([
            'ticket_number'     => 'TKT-' . strtoupper(uniqid()),
            'subject'           => $data['subject'],
            'message'           => $data['message'],
            'description'       => $data['description'] ?? $data['message'],
            'status'            => 'open',
            'priority'          => $data['priority'],
            'ticket_category_id'=> $data['category_id'] ?? null,
        ]);

        return Redirect::route('student.support.show', $ticket->id)
            ->with('success', 'Ticket créé. Notre équipe vous répondra sous 24h.');
    }

    public function show(Request $request, string $id)
    {
        $ticket = $request->user()->tickets()
            ->with(['category:id,name,color', 'assignedAgent:id,name,avatar'])
            ->findOrFail($id);

        return Inertia::render('Student/SupportTickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, string $id)
    {
        $data = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $ticket = $request->user()->tickets()->findOrFail($id);
        $ticket->update([
            'message' => $ticket->message . "\n\n--- Réponse " . $request->user()->name . " (" . now()->toDateTimeString() . ") ---\n" . $data['message'],
            'status'  => 'pending', // repassé en attente tant qu'un agent ne répond pas
        ]);

        return back()->with('success', 'Réponse envoyée. Votre ticket est repassé en attente.');
    }

    public function close(Request $request, string $id)
    {
        $ticket = $request->user()->tickets()->findOrFail($id);
        $ticket->update(['status' => 'closed']);
        return back()->with('success', 'Ticket fermé. Merci de votre retour.');
    }
}
