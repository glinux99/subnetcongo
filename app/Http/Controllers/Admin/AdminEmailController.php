<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AdminEmailController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $folder = $request->input('folder');
        $search = $request->input('search');

        $data = $search
            ? $this->emailService->search($search, $page, $perPage)
            : $this->emailService->getEmails($page, $perPage, $folder);

        $stats = [
            'total' => $this->emailService->getTotalCount(),
            'unread' => $this->emailService->getUnreadCount(),
            'quota' => $this->emailService->getQuota(),
        ];

        $folders = $this->emailService->getFolders();
        $connectionTest = $this->emailService->testConnection();

        if (!$connectionTest['success']) {
            return Inertia::render('Admin/Emails/Index', [
                'error' => $connectionTest['message'],
                'folders' => $folders,
                'stats' => $stats,
                'emails' => [],
                'pagination' => [
                    'page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'total_pages' => 1,
                ],
                'filters' => $request->all(['search', 'folder']),
                'account' => config('services.imap.username'),
            ]);
        }

        return Inertia::render('Admin/Emails/Index', [
            'emails' => $data['emails'],
            'pagination' => [
                'page' => $data['page'],
                'per_page' => $data['per_page'],
                'total' => $data['total'],
                'total_pages' => $data['total_pages'],
            ],
            'folders' => $folders,
            'stats' => $stats,
            'filters' => $request->all(['search', 'folder']),
            'account' => config('services.imap.username'),
        ]);
    }

    public function show(Request $request, int $messageNumber)
    {
       try {
         $email = $this->emailService->getEmail($messageNumber);

        if (!$email) {
            return Redirect::route('admin.emails.index')
                ->with('error', 'Email non trouvé ou serveur IMAP non accessible.');
        }

        if (!$email['seen']) {
            $this->emailService->markAsRead($messageNumber);
            $email['seen'] = true;
        }

        $folders = $this->emailService->getFolders();

        return Inertia::render('Admin/Emails/Show', [
            'email' => $email,
            'folders' => $folders,
            'messageNumber' => $messageNumber,
        ]);
       } catch (\Throwable $th) {
        //throw
        return $th;
       }
    }

public function reply(Request $request, int $messageNumber)
    {
        // 1. On rend 'to' OBLIGATOIRE au lieu de nullable
        $validated = $request->validate([
            'to' => 'required|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'reply_message' => 'required|string|max:50000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600', // 25 MB max
        ]);

        try {
            // 2. Nettoyage et formatage des adresses emails
            $to = $this->parseEmails($validated['to']);
            $cc = $this->parseEmails($validated['cc'] ?? '');
            $bcc = $this->parseEmails($validated['bcc'] ?? '');

            if (empty($to)) {
                return back()->with('error', 'Aucun destinataire valide trouvé.');
            }

            // 3. Récupérer le sujet de l'email original
            $originalEmail = $this->emailService->getEmail($messageNumber);
            $subject = $originalEmail['subject'] ?? 'Sans objet';

            // Ajouter "Re: " si ce n'est pas déjà présent dans le sujet
            if (!str_starts_with(strtolower($subject), 're:')) {
                $subject = 'Re: ' . $subject;
            }

            // 4. Envoi via Laravel Mail (SMTP) au lieu de imap_mail()
            Mail::html($validated['reply_message'], function ($message) use ($to, $cc, $bcc, $subject, $request) {
                $message->to($to)->subject($subject);

                if (!empty($cc)) {
                    $message->cc($cc);
                }

                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                // Ajout des pièces jointes géré proprement
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType(),
                        ]);
                    }
                }
            });

            // (Optionnel) Si votre service a une méthode pour marquer le message comme "Répondu" sur le serveur IMAP :
            // $this->emailService->markAsAnswered($messageNumber);

            Log::info('Réponse envoyée avec succès', [
                'to' => $to,
                'messageNumber' => $messageNumber,
                'has_attachments' => $request->hasFile('attachments')
            ]);

            return Redirect::route('admin.emails.show', $messageNumber)
                ->with('success', 'Réponse envoyée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réponse à l\'email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $e->getMessage();
            return back()->with('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }

    public function markAsRead(int $messageNumber)
    {
        $this->emailService->markAsRead($messageNumber);
        return response()->json(['success' => true]);
    }

    public function markAsUnread(int $messageNumber)
    {
        $this->emailService->markAsUnread($messageNumber);
        return response()->json(['success' => true]);
    }

    public function markAsFlagged(int $messageNumber)
    {
        $this->emailService->markAsFlagged($messageNumber);
        return response()->json(['success' => true]);
    }

    public function unflag(int $messageNumber)
    {
        $this->emailService->unflag($messageNumber);
        return response()->json(['success' => true]);
    }

    public function moveToTrash(int $messageNumber)
    {
        $this->emailService->moveToTrash($messageNumber);
        return Redirect::route('admin.emails.index')
            ->with('success', 'Email déplacé vers la corbeille.');
    }

    public function permanentlyDelete(int $messageNumber)
    {
        $this->emailService->permanentlyDelete($messageNumber);
        return Redirect::route('admin.emails.index')
            ->with('success', 'Email supprimé définitivement.');
    }

    public function testConnection()
    {
        $result = $this->emailService->testConnection();
        return response()->json($result);
    }

    public function getFolders()
    {
        $folders = $this->emailService->getFolders();
        return response()->json(['folders' => $folders]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $data = $this->emailService->search(
            $validated['query'],
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 20
        );

        return response()->json($data);
    }

    public function compose(Request $request)
    {
        return Inertia::render('Admin/Emails/Compose', [
            'prefill' => [
                'to' => $request->input('to', ''),
                'subject' => $request->input('subject', ''),
                'body' => $request->input('body', ''),
            ],
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|string', // String pour permettre "a@a.com, b@b.com"
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:50000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600', // 25 MB max
        ]);

        try {
            // Conversion des chaînes d'emails en tableaux propres
            $to = $this->parseEmails($validated['to']);
            $cc = $this->parseEmails($validated['cc'] ?? '');
            $bcc = $this->parseEmails($validated['bcc'] ?? '');

            if (empty($to)) {
                return back()->with('error', 'Aucun destinataire valide trouvé.');
            }

            // Utilisation de la Facade Mail de Laravel au lieu de la fonction native mail()
            Mail::html($validated['body'], function ($message) use ($to, $cc, $bcc, $validated, $request) {
                $message->to($to)
                        ->subject($validated['subject']);

                if (!empty($cc)) {
                    $message->cc($cc);
                }

                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }

                // Ajout des pièces jointes
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType(),
                        ]);
                    }
                }
            });

            Log::info('Email envoyé avec succès', [
                'to' => $to,
                'subject' => $validated['subject'],
                'has_attachments' => $request->hasFile('attachments')
            ]);

            return Redirect::route('admin.emails.index')
                ->with('success', 'Email envoyé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erreur lors de l\'envoi : ' . $e->getMessage())->withInput();
        }
    }

    public function saveDraft(Request $request)
    {
        $validated = $request->validate([
            'to' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
        ]);

        Log::info('Brouillon enregistré', $validated);

        return Redirect::back()->with('success', 'Brouillon enregistré avec succès.');
    }

    public function archive(int $messageNumber)
    {
        $this->emailService->archive($messageNumber);
        return Redirect::back()->with('success', 'Email archivé avec succès.');
    }

    public function unarchive(int $messageNumber)
    {
        $this->emailService->unarchive($messageNumber);
        return Redirect::back()->with('success', 'Email désarchivé.');
    }

    public function forward(Request $request, int $messageNumber)
    {
        $originalEmail = $this->emailService->getEmail($messageNumber);
        
        if (!$originalEmail) {
            return Redirect::back()->with('error', 'Email original non trouvé.');
        }

        $subject = $originalEmail['subject'] ?? 'Sans objet';
        if (!str_starts_with(strtolower($subject), 'fwd:')) {
            $subject = 'Fwd: ' . $subject;
        }

        $quotedText = "\n\n--- Message transféré ---\n";
        $quotedText .= "De: " . ($originalEmail['from'][0]['name'] ?? '') . " <" . ($originalEmail['from'][0]['email'] ?? '') . ">\n";
        $quotedText .= "Date: " . ($originalEmail['date'] ?? '') . "\n";
        $quotedText .= "Sujet: " . ($originalEmail['subject'] ?? '') . "\n";
        $quotedText .= "À: " . ($originalEmail['to'][0]['email'] ?? '') . "\n\n";

        $originalBody = strip_tags($originalEmail['text_body'] ?: $originalEmail['body'] ?: '');
        $quotedText .= $originalBody;

        return Inertia::render('Admin/Emails/Compose', [
            'prefill' => [
                'to' => '',
                'subject' => $subject,
                'body' => $quotedText,
            ],
            'forwardedEmail' => $originalEmail,
        ]);
    }

    public function getThread(int $messageNumber)
    {
        $email = $this->emailService->getEmail($messageNumber);
        
        if (!$email) {
            return Redirect::route('admin.emails.index')
                ->with('error', 'Email non trouvé.');
        }

        $thread = $this->emailService->getThread($messageNumber, $email);

        $folders = $this->emailService->getFolders();

        return Inertia::render('Admin/Emails/Thread', [
            'email' => $email,
            'thread' => $thread,
            'folders' => $folders,
            'messageNumber' => $messageNumber,
        ]);
    }

    /**
     * Helper pour convertir une chaîne d'emails séparés par des virgules en tableau valide
     */
    private function parseEmails(?string $emails): array
    {
        if (!$emails) return [];

        $array = explode(',', $emails);
        // Supprime les espaces et filtre les valeurs vides
        return array_filter(array_map('trim', $array));
    }
}
