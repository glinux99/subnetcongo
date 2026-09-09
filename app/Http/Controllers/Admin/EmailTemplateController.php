<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get();

        $grouped = $templates->groupBy('category')->map(function ($group) {
            return $group->map(function ($template) {
                return [
                    'id' => $template->id,
                    'key' => $template->key,
                    'name' => $template->name,
                    'subject' => $template->subject,
                    'trigger_event' => $template->trigger_event,
                    'is_active' => $template->is_active,
                    'variables' => $template->variables,
                ];
            });
        });

        return inertia('Admin/EmailTemplates/Index', [
            'templates' => $templates,
            'grouped' => $grouped,
        ]);
    }

    public function show(string $id)
    {
        $template = EmailTemplate::findOrFail($id);

        return inertia('Admin/EmailTemplates/Show', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return back()->with('success', 'Modèle enregistré avec succès.');
    }

    public function test(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'email' => 'required|email',
        ]);

        $template = EmailTemplate::findOrFail($validated['template_id']);
        
        $user = User::where('email', $validated['email'])->first();
        
        $testData = [
            'student_name' => $user ? $user->name : 'Test User',
            'student_email' => $validated['email'],
            'course_name' => 'Introduction aux Réseaux',
            'course_url' => url('/courses/1'),
            'certificate_url' => url('/certificates/1'),
            'certificate_code' => 'CERT-2026-001',
            'completion_date' => now()->format('d/m/Y'),
            'enrollment_date' => now()->format('d/m/Y'),
            'expiry_date' => now()->addYear()->format('d/m/Y'),
            'exam_name' => 'Quiz Final',
            'score' => '85',
            'passing_score' => '70',
            'dashboard_url' => url('/dashboard'),
            'welcome_date' => now()->format('d/m/Y'),
            'ticket_id' => '12345',
            'ticket_subject' => 'Test de support',
            'ticket_url' => url('/support/12345'),
            'created_at' => now()->format('d/m/Y H:i'),
            'added_date' => now()->format('d/m/Y'),
            'course_description' => 'Un cours passionnant sur les bases du networking.',
            'if_certificate' => 'Votre certificat est en cours de génération !',
        ];

        $replaced = $template->replaceVariables($testData);

        try {
            Mail::html($replaced['content'], function ($message) use ($validated, $replaced) {
                $message->to($validated['email'])
                        ->subject($replaced['subject']);
            });

            return back()->with('success', 'Email de test envoyé à ' . $validated['email']);
        } catch (\Exception $e) {
            Log::error('EmailTemplate test failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    public function seedDefaults()
    {
        EmailTemplate::seedDefaults();
        return back()->with('success', 'Modèles par défaut créés avec succès.');
    }
}
