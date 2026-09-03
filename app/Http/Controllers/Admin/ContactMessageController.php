<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('ticket_number', 'like', "%{$s}%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $messages = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total'    => ContactMessage::count(),
            'new'      => ContactMessage::where('status', 'new')->count(),
            'replied'  => ContactMessage::where('status', 'replied')->count(),
            'archived' => ContactMessage::where('status', 'archived')->count(),
        ];

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $messages,
            'stats' => $stats,
            'filters' => $request->all(['search', 'status']),
        ]);
    }

    public function show(string $id)
    {
        $message = ContactMessage::with('repliedBy:id,name')->findOrFail($id);
        if ($message->status === 'new') $message->update(['status' => 'read']);
        return Inertia::render('Admin/ContactMessages/Show', [
            'message' => $message->fresh(['repliedBy:id,name']),
        ]);
    }

    public function reply(Request $request, string $id)
    {
        $data = $request->validate([
            'reply_message' => 'required|string|max:10000',
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->update([
            'reply_message' => $data['reply_message'],
            'replied_by'    => $request->user()->id,
            'replied_at'    => now(),
            'status'        => 'replied',
        ]);

        // Best-effort : envoi email
        try {
            Mail::raw($data['reply_message'], function ($mail) use ($message) {
                $mail->to($message->email, $message->name)
                     ->subject('Re: ' . $message->subject)
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });
        } catch (\Throwable $e) {
            \Log::warning('ContactMessage reply email failed', ['err' => $e->getMessage()]);
        }

        return back()->with('success', 'Réponse enregistrée et email envoyé à ' . $message->email);
    }

    public function destroy(string $id)
    {
        ContactMessage::findOrFail($id)->delete();
        return back()->with('success', 'Message supprimé.');
    }

    public function archive(string $id)
    {
        ContactMessage::findOrFail($id)->update(['status' => 'archived']);
        return back()->with('success', 'Message archivé.');
    }
}
