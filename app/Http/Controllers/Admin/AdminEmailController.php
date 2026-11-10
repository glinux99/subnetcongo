<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Email\EmailService;
use App\Models\SentEmail;
use App\Notifications\EmailSentNotification;
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

        return Inertia::render('Admin/Emails/Gmail', [
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
         $folder = $request->input('folder');
         $email = $this->emailService->getEmail($messageNumber, $folder);

        if (!$email) {
            return Redirect::route('admin.emails.index')
                ->with('error', 'Email non trouvé ou serveur IMAP non accessible.');
        }

        if (!$email['seen']) {
            $this->emailService->markAsRead($messageNumber, $folder);
            $email['seen'] = true;
        }

        $folders = $this->emailService->getFolders();

        return Inertia::render('Admin/Emails/GmailShow', [
            'email' => $email,
            'folders' => $folders,
            'messageNumber' => $messageNumber,
            'folder' => $folder,
        ]);
       } catch (\Throwable $th) {
        //throw
        return $th;
       }
    }

public function reply(Request $request, int $messageNumber)
    {
        $validated = $request->validate([
            'to' => 'nullable|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'reply_message' => 'required|string|max:50000',
            'folder' => 'nullable|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:25600', // 25 MB max
        ]);

        try {
            // 2. Récupérer l'email original (expéditeur + sujet)
            $folder = $validated['folder'] ?? null;
            $originalEmail = $this->emailService->getEmail($messageNumber, $folder);

            // 3. Nettoyage et formatage des adresses emails
            //    Si aucun destinataire explicite, on répond à l'expéditeur d'origine
            $to = $this->parseEmails($validated['to'] ?? '');
            if (empty($to)) {
                $originalSender = $originalEmail['from'][0]['email'] ?? null;
                if ($originalSender) {
                    $to = [$originalSender];
                }
            }
            $cc = $this->parseEmails($validated['cc'] ?? '');
            $bcc = $this->parseEmails($validated['bcc'] ?? '');

            if (empty($to)) {
                return back()->with('error', 'Aucun destinataire valide trouvé.');
            }

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

            // Enregistrer la réponse dans les emails envoyés
            $sentEmail = null;
            try {
                $sentEmail = SentEmail::create([
                    'user_id' => auth()->id(),
                    'to' => $to,
                    'cc' => !empty($cc) ? $cc : null,
                    'bcc' => !empty($bcc) ? $bcc : null,
                    'subject' => $subject,
                    'body' => $validated['reply_message'],
                    'attachments' => $request->hasFile('attachments') ? array_map(function($file) {
                        return [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                        ];
                    }, $request->file('attachments')) : null,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Impossible d\'enregistrer la réponse envoyée', ['error' => $e->getMessage()]);
            }

            $this->notifyEmailSent(array_merge($to, $cc, $bcc), $subject, $sentEmail?->id);

            Log::info('Réponse envoyée avec succès', [
                'to' => $to,
                'messageNumber' => $messageNumber,
                'has_attachments' => $request->hasFile('attachments')
            ]);

            return Redirect::route('admin.emails.show', ['messageNumber' => $messageNumber, 'folder' => $folder])
                ->with('success', 'Réponse envoyée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réponse à l\'email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }
    }

    public function markAsRead(Request $request, int $messageNumber)
    {
        $this->emailService->markAsRead($messageNumber, $request->input('folder'));
        return response()->json(['success' => true]);
    }

    public function markAsUnread(Request $request, int $messageNumber)
    {
        $this->emailService->markAsUnread($messageNumber, $request->input('folder'));
        return response()->json(['success' => true]);
    }

    public function markAsFlagged(Request $request, int $messageNumber)
    {
        $this->emailService->markAsFlagged($messageNumber, $request->input('folder'));
        return response()->json(['success' => true]);
    }

    public function unflag(Request $request, int $messageNumber)
    {
        $this->emailService->unflag($messageNumber, $request->input('folder'));
        return response()->json(['success' => true]);
    }

    public function moveToTrash(Request $request, int $messageNumber)
    {
        $this->emailService->moveToTrash($messageNumber, $request->input('folder'));
        return $this->redirectToFolder($request)
            ->with('success', 'Email déplacé vers la corbeille.');
    }

    public function permanentlyDelete(Request $request, int $messageNumber)
    {
        $this->emailService->permanentlyDelete($messageNumber, $request->input('folder'));
        return $this->redirectToFolder($request)
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
        return Inertia::render('Admin/Emails/GmailCompose', [
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

            // Enregistrer l'email envoyé
            $sentEmail = null;
            try {
                $sentEmail = SentEmail::create([
                    'user_id' => auth()->id(),
                    'to' => $to,
                    'cc' => !empty($cc) ? $cc : null,
                    'bcc' => !empty($bcc) ? $bcc : null,
                    'subject' => $validated['subject'],
                    'body' => $validated['body'],
                    'attachments' => $request->hasFile('attachments') ? array_map(function($file) {
                        return [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                        ];
                    }, $request->file('attachments')) : null,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Impossible d\'enregistrer l\'email envoyé', ['error' => $e->getMessage()]);
            }

            $this->notifyEmailSent(array_merge($to, $cc, $bcc), $validated['subject'], $sentEmail?->id);

            Log::info('Email envoyé avec succès', [
                'to' => $to,
                'subject' => $validated['subject'],
                'has_attachments' => $request->hasFile('attachments')
            ]);

            return Redirect::route('admin.emails.sent')
                ->with('success', 'Email envoyé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                SentEmail::create([
                    'user_id' => auth()->id(),
                    'to' => $to ?? [],
                    'cc' => !empty($cc) ? $cc : null,
                    'bcc' => !empty($bcc) ? $bcc : null,
                    'subject' => $validated['subject'] ?? '',
                    'body' => $validated['body'] ?? '',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            } catch (\Exception $ex) {
                Log::warning('Impossible d\'enregistrer l\'email échoué', ['error' => $ex->getMessage()]);
            }

            return back()->with('error', 'Erreur lors de l\'envoi : ' . $e->getMessage())->withInput();
        }
    }

    public function saveDraft(Request $request)
    {
        $validated = $request->validate([
            'to' => 'nullable|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:50000',
        ]);

        try {
            $to = $this->parseEmails($validated['to'] ?? '');
            $cc = $this->parseEmails($validated['cc'] ?? '');
            $bcc = $this->parseEmails($validated['bcc'] ?? '');

            SentEmail::create([
                'user_id' => auth()->id(),
                'to' => $to,
                'cc' => !empty($cc) ? $cc : null,
                'bcc' => !empty($bcc) ? $bcc : null,
                'subject' => $validated['subject'] ?? '',
                'body' => $validated['body'] ?? '',
                'status' => 'draft',
            ]);

            Log::info('Brouillon enregistré', $validated);

            return Redirect::back()->with('success', 'Brouillon enregistré avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde du brouillon', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de la sauvegarde du brouillon.');
        }
    }

    public function sentEmails(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $status = $request->input('status');

        // Emails enregistrés dans la base (envois via la plateforme)
        $dbQuery = SentEmail::with('user')->orderBy('sent_at', 'desc')->orderBy('created_at', 'desc');
        if ($status && $status !== 'all') {
            $dbQuery->where('status', $status);
        }

        $dbEmails = $dbQuery->get()->map(fn ($e) => [
            'id' => $e->id,
            'imap' => false,
            'to' => $e->to ?? [],
            'cc' => $e->cc ?? [],
            'bcc' => $e->bcc ?? [],
            'subject' => $e->subject,
            'body' => $e->body,
            'attachments' => $e->attachments ?? [],
            'status' => $e->status,
            'error_message' => $e->error_message,
            'sent_at' => $e->sent_at?->toIso8601String(),
            'created_at' => $e->created_at->toIso8601String(),
            'sender_name' => $e->user?->name,
            'sender_photo' => $e->user?->profile_photo_url,
            '_sort' => strtotime($e->sent_at ?? $e->created_at),
        ]);

        // Emails présents dans le dossier IMAP "Sent" (envoyés via webmail/autres clients)
        $imapEmails = collect();
        try {
            $imapData = $this->emailService->getSentEmails(1, 100);
            $imapEmails = collect($imapData['emails'] ?? [])->map(function ($e) {
                $formatted = $this->formatImapSentEmail($e);
                $formatted['body'] = null; // le corps est rechargé à l'ouverture du détail
                return $formatted;
            });
        } catch (\Throwable $e) {
            Log::warning('Impossible de charger le dossier Sent IMAP', ['error' => $e->getMessage()]);
        }

        $all = $dbEmails->merge($imapEmails)
            ->sortByDesc('_sort')
            ->values();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $allDb = SentEmail::query();
        $stats = [
            'total' => $all->count(),
            'sent' => $dbEmails->where('status', 'sent')->count() + $imapEmails->count(),
            'failed' => $allDb->where('status', 'failed')->count(),
            'drafts' => $allDb->where('status', 'draft')->count(),
        ];

        return Inertia::render('Admin/Emails/Sent', [
            'emails' => $paginated->items(),
            'pagination' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'total_pages' => $paginated->lastPage(),
            ],
            'stats' => $stats,
            'filters' => $request->all(['status']),
        ]);
    }

    /**
     * Normalise un email du dossier IMAP "Sent" au format de la page Envoyés.
     */
    private function formatImapSentEmail(array $e): array
    {
        $to = array_map(fn ($a) => $a['email'] ?? '', $e['to'] ?? []);
        $cc = array_map(fn ($a) => $a['email'] ?? '', $e['cc'] ?? []);

        return [
            'id' => null,
            'imap' => true,
            'message_number' => $e['message_number'],
            'to' => array_values(array_filter($to)),
            'cc' => array_values(array_filter($cc)),
            'bcc' => [],
            'subject' => $e['subject'] ?? '(sans objet)',
            'body' => $e['html_body'] ?: nl2br(htmlspecialchars($e['text_body'] ?? $e['body'] ?? '')),
            'attachments' => array_map(fn ($a) => ['name' => $a['filename'] ?? 'pièce jointe', 'size' => $a['size'] ?? 0], $e['attachments'] ?? []),
            'status' => 'sent',
            'error_message' => null,
            'sent_at' => isset($e['udate']) && $e['udate'] ? date('c', $e['udate']) : ($e['date'] ?? null),
            'created_at' => null,
            'sender_name' => null,
            'sender_photo' => null,
            '_sort' => $e['udate'] ?? (strtotime($e['date'] ?? '') ?: 0),
        ];
    }

    public function showSentEmail(int $id)
    {
        $email = SentEmail::with('user')->find($id);

        if (!$email) {
            return Redirect::route('admin.emails.sent')
                ->with('error', 'Email non trouvé.');
        }

        return Inertia::render('Admin/Emails/SentShow', [
            'email' => array_merge($email->toArray(), [
                'imap' => false,
                'sender_name' => $email->user?->name,
                'sender_photo' => $email->user?->profile_photo_url,
            ]),
        ]);
    }

    public function showImapSentEmail(int $messageNumber)
    {
        $email = $this->emailService->getSentEmail($messageNumber);

        if (!$email) {
            return Redirect::route('admin.emails.sent')
                ->with('error', 'Email envoyé introuvable dans le dossier Sent.');
        }

        return Inertia::render('Admin/Emails/SentShow', [
            'email' => $this->formatImapSentEmail($email),
        ]);
    }

    public function deleteSentEmail(int $id)
    {
        $email = SentEmail::find($id);

        if (!$email) {
            return Redirect::route('admin.emails.sent')
                ->with('error', 'Email non trouvé.');
        }

        $email->delete();

        return Redirect::route('admin.emails.sent')
            ->with('success', 'Email supprimé avec succès.');
    }

    public function archive(Request $request, int $messageNumber)
    {
        if (!$this->emailService->archive($messageNumber, $request->input('folder'))) {
            return Redirect::back()->with('error', 'Impossible d\'archiver cet email. Vérifiez la connexion IMAP et les permissions du dossier.');
        }
        return $this->redirectToFolder($request)->with('success', 'Email archivé avec succès.');
    }

    public function unarchive(Request $request, int $messageNumber)
    {
        if (!$this->emailService->unarchive($messageNumber, $request->input('folder'))) {
            return Redirect::back()->with('error', 'Impossible de désarchiver cet email.');
        }
        return $this->redirectToFolder($request)->with('success', 'Email désarchivé.');
    }

    /**
     * Redirige vers la liste des emails du dossier courant après une action.
     */
    private function redirectToFolder(Request $request)
    {
        $folder = $request->input('folder');
        return Redirect::route('admin.emails.index', $folder ? ['folder' => $folder] : []);
    }

    public function forward(Request $request, int $messageNumber)
    {
        $folder = $request->input('folder');
        $originalEmail = $this->emailService->getEmail($messageNumber, $folder);
        
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

        return Inertia::render('Admin/Emails/GmailCompose', [
            'prefill' => [
                'to' => '',
                'subject' => $subject,
                'body' => $quotedText,
            ],
            'forwardedEmail' => $originalEmail,
        ]);
    }

    public function getThread(Request $request, int $messageNumber)
    {
        $folder = $request->input('folder');
        $email = $this->emailService->getEmail($messageNumber, $folder);
        
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
     * Notifie l'expéditeur qu'un email a été envoyé (notification + broadcast).
     */
    private function notifyEmailSent(array $recipients, string $subject, ?int $sentEmailId = null): void
    {
        $recipients = array_values(array_unique(array_filter($recipients)));
        if (empty($recipients) || !auth()->check()) {
            return;
        }

        try {
            auth()->user()->notify(new EmailSentNotification(
                recipients: $recipients,
                subject: $subject,
                sentAt: now()->format('d/m/Y à H:i'),
                sentEmailId: $sentEmailId,
            ));
        } catch (\Throwable $e) {
            Log::warning('EmailSentNotification failed: ' . $e->getMessage());
        }
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
