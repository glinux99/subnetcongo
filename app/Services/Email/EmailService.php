<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EmailService
{
    protected ?object $connection = null;
    protected string $host;
    protected int $port;
    protected string $encryption;
    protected string $username;
    protected string $password;
    protected string $defaultFolder;
    protected bool $validateCert;
    protected bool $connected = false;

    public function __construct()
    {
        $this->host = config('services.imap.host', env('IMAP_HOST', ''));
        $this->port = (int) config('services.imap.port', env('IMAP_PORT', 993));
        $this->encryption = config('services.imap.encryption', env('IMAP_ENCRYPTION', 'ssl'));
        $this->username = config('services.imap.username', env('IMAP_USERNAME', ''));
        $this->password = config('services.imap.password', env('IMAP_PASSWORD', ''));
        $this->defaultFolder = config('services.imap.default_folder', 'INBOX');
        $this->validateCert = config('services.imap.validate_cert', env('IMAP_VALIDATE_CERT', true));
    }

    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->username) && !empty($this->password);
    }

    protected function getMailboxString(): string
    {
        $enc = $this->encryption === 'ssl' ? '/ssl' : ($this->encryption === 'tls' ? '/tls' : '');
        $novalidate = $this->validateCert ? '' : '/novalidate-cert';
        $secure = $enc . $novalidate;
        
        return "{{$this->host}:{$this->port}/imap{$secure}}{$this->defaultFolder}";
    }

    public function connect(): bool
    {
        if (!$this->isConfigured()) {
            Log::error('EmailService: IMAP not configured');
            return false;
        }

        if ($this->connected && $this->connection !== null) {
            return true;
        }

        $mailbox = $this->getMailboxString();
        
        $this->connection = @imap_open($mailbox, $this->username, $this->password, OP_READONLY, 1);
        
        if ($this->connection === false) {
            $error = imap_last_error();
            Log::error('EmailService: Failed to connect', ['error' => $error, 'mailbox' => $mailbox]);
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        Log::info('EmailService: Connected successfully');
        return true;
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            imap_close($this->connection, CL_EXPUNGE);
            $this->connection = null;
            $this->connected = false;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'IMAP non configuré. Vérifiez IMAP_HOST, IMAP_USERNAME et IMAP_PASSWORD dans .env'
            ];
        }

        $mailbox = $this->getMailboxString();
        
        $connection = @imap_open($mailbox, $this->username, $this->password, OP_READONLY, 1);
        
        if ($connection === false) {
            $error = imap_last_error();
            return [
                'success' => false,
                'message' => 'Connexion échouée: ' . $error
            ];
        }

        imap_close($connection);
        
        return [
            'success' => true,
            'message' => 'Connexion réussie au serveur IMAP'
        ];
    }

    public function getEmails(int $page = 1, int $perPage = 20, ?string $folder = null): array
    {
        if (!$this->connect()) {
            return ['emails' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        if ($folder !== null && $folder !== $this->defaultFolder) {
            $this->switchFolder($folder);
        }

        $total = imap_num_msg($this->connection);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $start = $total - (($page - 1) * $perPage);
        $end = max(1, $start - $perPage + 1);

        $emails = [];
        
        for ($i = $start; $i >= $end && $i > 0; $i--) {
            $header = imap_headerinfo($this->connection, $i);
            if ($header === false) continue;

            $emails[] = $this->parseEmailHeader($header, $i);
        }

        return [
            'emails' => $emails,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    public function getEmail(int $messageNumber): ?array
    {
        if (!$this->connect()) {
            return null;
        }

        $header = imap_headerinfo($this->connection, $messageNumber);
        if ($header === false) {
            return null;
        }

        $structure = imap_fetchstructure($this->connection, $messageNumber);
        $body = $this->getBody($messageNumber, $structure);
        $attachments = $this->getAttachments($messageNumber, $structure);

        return [
            'message_number' => $messageNumber,
            'uid' => imap_uid($this->connection, $messageNumber),
            'subject' => $this->decodeHeader($header->subject ?? ''),
            'from' => $this->parseAddress($header->from ?? []),
            'to' => $this->parseAddress($header->to ?? []),
            'cc' => $this->parseAddress($header->cc ?? []),
            'date' => $header->date ?? null,
            'udate' => $header->udate ?? 0,
            'body' => $body,
            'html_body' => $this->getHtmlBody($messageNumber, $structure),
            'text_body' => $this->getTextBody($messageNumber, $structure),
            'attachments' => $attachments,
            'has_attachments' => !empty($attachments),
            'size' => $header->Size ?? 0,
            'answered' => ($header->Answered ?? '') === 'A',
            'deleted' => ($header->Deleted ?? '') === 'D',
            'seen' => ($header->Unseen ?? '') !== 'U',
            'flagged' => ($header->Flagged ?? '') === 'F',
        ];
    }

    public function getFolders(): array
    {
        if (!$this->connect()) {
            return [];
        }

        $folders = imap_list($this->connection, $this->getBaseMailbox(), '*');
        
        if ($folders === false) {
            return [];
        }

        return array_map(function ($folder) {
            return [
                'name' => $this->decodeFolderName($folder),
                'path' => $folder,
                'short' => str_replace($this->getBaseMailbox(), '', $folder)
            ];
        }, $folders);
    }

    protected function switchFolder(string $folder): bool
    {
        if (!$this->connected) {
            return false;
        }

        $mailbox = $this->getBaseMailbox() . $folder;
        imap_reopen($this->connection, $mailbox);
        return true;
    }

    protected function getBaseMailbox(): string
    {
        $enc = $this->encryption === 'ssl' ? '/ssl' : ($this->encryption === 'tls' ? '/tls' : '');
        $novalidate = $this->validateCert ? '' : '/novalidate-cert';
        return "{{$this->host}:{$this->port}/imap{$enc}{$novalidate}}";
    }

    protected function parseEmailHeader($header, int $messageNumber): array
    {
        return [
            'message_number' => $messageNumber,
            'uid' => imap_uid($this->connection, $messageNumber),
            'subject' => $this->decodeHeader($header->subject ?? '(sans objet)'),
            'from' => $this->parseAddress($header->from ?? []),
            'to' => $this->parseAddress($header->to ?? []),
            'date' => $header->date ?? null,
            'udate' => $header->udate ?? 0,
            'size' => $header->Size ?? 0,
            'answered' => ($header->Answered ?? '') === 'A',
            'deleted' => ($header->Deleted ?? '') === 'D',
            'seen' => ($header->Unseen ?? '') !== 'U',
            'flagged' => ($header->Flagged ?? '') === 'F',
            'recent' => ($header->Recent ?? '') === 'N',
        ];
    }

    protected function parseAddress(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $address) {
            $result[] = [
                'name' => $this->decodeHeader($address->personal ?? ''),
                'email' => ($address->mailbox ?? '') . '@' . ($address->host ?? '')
            ];
        }
        return $result;
    }

    protected function decodeHeader(string $header): string
    {
        if (empty($header)) {
            return '';
        }
        
        $decoded = @imap_mime_header_decode($header);
        
        if ($decoded === false || empty($decoded)) {
            return $header;
        }

        $result = '';
        foreach ($decoded as $part) {
            $charset = $part->charset ?? 'iso-8859-1';
            if (strtolower($charset) === 'default') {
                $charset = 'iso-8859-1';
            }
            $result .= mb_convert_encoding($part->text, 'UTF-8', $charset);
        }

        return trim($result);
    }

    protected function getBody(int $messageNumber, $structure): string
    {
        if ($this->hasHtmlPart($structure)) {
            return $this->getHtmlBody($messageNumber, $structure);
        }
        return $this->getTextBody($messageNumber, $structure);
    }

    protected function getHtmlBody(int $messageNumber, $structure): string
    {
        $body = '';
        
        if ($structure && isset($structure->parts)) {
            foreach ($structure->parts as $partIndex => $part) {
                if ($part->subtype === 'HTML') {
                    $body = $this->decodePart($messageNumber, $partIndex + 1, $part);
                    break;
                }
                if ($part->subtype === 'ALTERNATIVE') {
                    $body = $this->getHtmlFromAlternative($messageNumber, $part);
                    if ($body) break;
                }
            }
        }

        if (empty($body)) {
            $body = imap_body($this->connection, $messageNumber);
        }

        return $body ?: '';
    }

    protected function getTextBody(int $messageNumber, $structure): string
    {
        $body = '';
        
        if ($structure && isset($structure->parts)) {
            foreach ($structure->parts as $partIndex => $part) {
                if ($part->subtype === 'PLAIN') {
                    $body = $this->decodePart($messageNumber, $partIndex + 1, $part);
                    break;
                }
            }
        }

        if (empty($body)) {
            $body = imap_body($this->connection, $messageNumber);
        }

        return $body ?: '';
    }

    protected function getHtmlFromAlternative(int $messageNumber, $part): string
    {
        if (!isset($part->parts)) {
            return '';
        }

        foreach ($part->parts as $subPartIndex => $subPart) {
            if ($subPart->subtype === 'HTML') {
                return $this->decodePart($messageNumber, $subPartIndex + 1, $subPart);
            }
        }

        return '';
    }

    protected function hasHtmlPart($structure): bool
    {
        if (!$structure || !isset($structure->parts)) {
            return false;
        }

        foreach ($structure->parts as $part) {
            if ($part->subtype === 'HTML') {
                return true;
            }
            if ($part->subtype === 'ALTERNATIVE' && $this->hasHtmlPart($part)) {
                return true;
            }
        }

        return false;
    }

    protected function decodePart(int $messageNumber, int $partNumber, $part): string
    {
        $body = imap_fetchbody($this->connection, $messageNumber, $partNumber);
        
        if ($body === false) {
            return '';
        }

        $encoding = $part->encoding ?? 0;
        
        switch ($encoding) {
            case 3:
                $body = base64_decode($body);
                break;
            case 4:
                $body = quoted_printable_decode($body);
                break;
            case 2:
                $body = imap_binary($body);
                break;
        }

        $charset = 'iso-8859-1';
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute ?? '') === 'charset') {
                    $charset = $param->value ?? 'iso-8859-1';
                    break;
                }
            }
        }

        if (strtolower($charset) !== 'utf-8' && strtolower($charset) !== 'utf8') {
            $body = mb_convert_encoding($body, 'UTF-8', $charset);
        }

        return $body;
    }

    protected function getAttachments(int $messageNumber, $structure): array
    {
        $attachments = [];

        if (!$structure || !isset($structure->parts)) {
            return $attachments;
        }

        $this->extractAttachmentsRecursive($this->connection, $messageNumber, $structure->parts, 1, $attachments);

        return $attachments;
    }

    protected function extractAttachmentsRecursive($connection, int $messageNumber, array $parts, int $partNumber, array &$attachments): int
    {
        foreach ($parts as $part) {
            $disposition = strtolower($part->disposition ?? '');
            
            if ($disposition === 'attachment' || $disposition === 'inline') {
                $filename = $this->getPartFilename($part);
                
                $attachments[] = [
                    'part_number' => $partNumber,
                    'filename' => $filename,
                    'type' => $part->type ?? 0,
                    'subtype' => $part->subtype ?? '',
                    'size' => $part->bytes ?? 0,
                    'disposition' => $disposition,
                    'content_id' => $part->id ?? null,
                ];
            }

            if (isset($part->parts)) {
                $partNumber = $this->extractAttachmentsRecursive($connection, $messageNumber, $part->parts, $partNumber, $attachments);
            }

            $partNumber++;
        }

        return $partNumber;
    }

    protected function getPartFilename($part): string
    {
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute ?? '') === 'filename') {
                    return $this->decodeHeader($param->value ?? '');
                }
            }
        }

        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute ?? '') === 'name') {
                    return $this->decodeHeader($param->value ?? '');
                }
            }
        }

        return 'attachment-' . uniqid();
    }

    protected function decodeFolderName(string $folder): string
    {
        $name = preg_replace('/^\{[^}]+\}/', '', $folder);
        return $this->decodeHeader($name);
    }

    public function markAsRead(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return imap_setflag_full($this->connection, $messageNumber, '\\Seen');
    }

    public function markAsUnread(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return imap_clearflag_full($this->connection, $messageNumber, '\\Seen');
    }

    public function markAsFlagged(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return imap_setflag_full($this->connection, $messageNumber, '\\Flagged');
    }

    public function unflag(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        return imap_clearflag_full($this->connection, $messageNumber, '\\Flagged');
    }

    public function moveToTrash(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        $trashFolder = $this->findTrashFolder();
        if ($trashFolder) {
            return imap_mail_move($this->connection, $messageNumber, $trashFolder);
        }

        return imap_delete($this->connection, $messageNumber);
    }

    public function permanentlyDelete(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        imap_delete($this->connection, $messageNumber);
        return imap_expunge($this->connection);
    }

    protected function findTrashFolder(): ?string
    {
        $folders = $this->getFolders();
        
        foreach ($folders as $folder) {
            $name = strtolower($folder['name']);
            if (str_contains($name, 'trash') || str_contains($name, 'corbeille') || str_contains($name, 'deleted')) {
                return $folder['short'];
            }
        }

        return 'INBOX/Drafts';
    }

    public function sendReply(int $messageNumber, string $replyText, ?string $cc = null): array
    {
        if (!$this->connect()) {
            return ['success' => false, 'message' => 'Non connecté au serveur IMAP'];
        }

        $email = $this->getEmail($messageNumber);
        if (!$email) {
            return ['success' => false, 'message' => 'Email non trouvé'];
        }

        $originalFrom = $email['from'][0] ?? null;
        if (!$originalFrom) {
            return ['success' => false, 'message' => 'Expéditeur original non trouvé'];
        }

        $replySubject = preg_match('/^Re:/i', $email['subject']) 
            ? $email['subject'] 
            : 'Re: ' . $email['subject'];

        $replyTo = $originalFrom['email'];
        $replyToName = $originalFrom['name'] ?: null;

        $headers = [
            'From' => config('services.imap.username'),
            'To' => $replyTo . ($replyToName ? " <{$replyToName}>" : ''),
            'Subject' => $replySubject,
            'In-Reply-To' => $email['uid'],
            'References' => $email['uid'],
        ];

        if ($cc) {
            $headers['Cc'] = $cc;
        }

        $quotedText = "\n\n--- Message original ---\n";
        $quotedText .= "De: " . $originalFrom['name'] . " <" . $originalFrom['email'] . ">\n";
        $quotedText .= "Date: " . $email['date'] . "\n";
        $quotedText .= "Sujet: " . $email['subject'] . "\n\n";
        
        $originalBody = strip_tags($email['text_body'] ?: $email['body'] ?: '');
        foreach (explode("\n", $originalBody) as $line) {
            $quotedText .= "> " . $line . "\n";
        }
        
        $fullBody = $replyText . "\n" . $quotedText;

        try {
            imap_mail($replySubject, $fullBody, $this->buildHeaders($headers));
            
            $this->markAsAnswered($messageNumber);

            return [
                'success' => true,
                'message' => 'Réponse envoyée avec succès',
                'to' => $replyTo
            ];
        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send reply', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
            ];
        }
    }

    protected function buildHeaders(array $headers): string
    {
        $result = [];
        foreach ($headers as $key => $value) {
            if ($value !== null) {
                $result[] = "{$key}: {$value}";
            }
        }
        return implode("\r\n", $result);
    }

    protected function markAsAnswered(int $messageNumber): bool
    {
        return imap_setflag_full($this->connection, $messageNumber, '\\Answered');
    }

    public function search(string $query, int $page = 1, int $perPage = 20): array
    {
        if (!$this->connect()) {
            return ['emails' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        $criteria = 'ALL ' . $query;
        
        $messages = imap_search($this->connection, $criteria);
        
        if ($messages === false || empty($messages)) {
            return ['emails' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        rsort($messages);
        
        $total = count($messages);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        
        $offset = ($page - 1) * $perPage;
        $pageMessages = array_slice($messages, $offset, $perPage);

        $emails = [];
        foreach ($pageMessages as $messageNumber) {
            $header = imap_headerinfo($this->connection, $messageNumber);
            if ($header === false) continue;
            $emails[] = $this->parseEmailHeader($header, $messageNumber);
        }

        return [
            'emails' => $emails,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    public function searchByFrom(string $from, int $page = 1, int $perPage = 20): array
    {
        return $this->search("FROM \"{$from}\"", $page, $perPage);
    }

    public function searchBySubject(string $subject, int $page = 1, int $perPage = 20): array
    {
        return $this->search("SUBJECT \"{$subject}\"", $page, $perPage);
    }

    public function getUnreadCount(): int
    {
        if (!$this->connect()) {
            return 0;
        }

        return imap_num_recent($this->connection);
    }

    public function getTotalCount(): int
    {
        if (!$this->connect()) {
            return 0;
        }

        return imap_num_msg($this->connection);
    }

    public function getQuota(): array
    {
        if (!$this->connect()) {
            return ['used' => 0, 'limit' => 0, 'percent' => 0];
        }

        $quota = @imap_get_quota($this->connection, 'INBOX');
        
        if ($quota === false || !isset($quota['STORAGE'])) {
            return ['used' => 0, 'limit' => 0, 'percent' => 0];
        }

        $used = $quota['STORAGE']['usage'] ?? 0;
        $limit = $quota['STORAGE']['limit'] ?? 0;
        $percent = $limit > 0 ? round(($used / $limit) * 100, 1) : 0;

        return [
            'used' => $used,
            'limit' => $limit,
            'percent' => $percent
        ];
    }

    public function archive(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        $archiveFolder = $this->findArchiveFolder();
        if ($archiveFolder) {
            return imap_mail_move($this->connection, $messageNumber, $archiveFolder);
        }

        return imap_setflag_full($this->connection, $messageNumber, '\\Flagged');
    }

    public function unarchive(int $messageNumber): bool
    {
        if (!$this->connect()) {
            return false;
        }

        imap_clearflag_full($this->connection, $messageNumber, '\\Flagged');
        
        $inbox = '{' . $this->host . ':' . $this->port . '/imap/' . ($this->encryption === 'ssl' ? 'ssl' : 'notls') . '}INBOX';
        return imap_mail_move($this->connection, $messageNumber, 'INBOX');
    }

    protected function findArchiveFolder(): ?string
    {
        $folders = $this->getFolders();
        
        foreach ($folders as $folder) {
            $name = strtolower($folder['name']);
            if (str_contains($name, 'archive') || str_contains($name, 'archives')) {
                return $folder['short'];
            }
        }

        return null;
    }

    public function getThread(int $messageNumber, array $currentEmail): array
    {
        $thread = [$currentEmail];
        
        $references = $currentEmail['references'] ?? null;
        $inReplyTo = $currentEmail['in_reply_to'] ?? null;
        
        if (!$references && !$inReplyTo) {
            return $thread;
        }

        if (!$this->connect()) {
            return $thread;
        }

        $searchCriteria = [];
        if ($references) {
            $refs = is_array($references) ? $references : [$references];
            foreach ($refs as $ref) {
                $searchCriteria[] = 'REFERENCES "' . trim($ref) . '"';
            }
        }
        if ($inReplyTo) {
            $searchCriteria[] = 'HEADER Message-ID "' . trim($inReplyTo) . '"';
        }

        if (!empty($searchCriteria)) {
            $criteria = 'OR ' . implode(' ', $searchCriteria);
            $messages = @imap_search($this->connection, $criteria, SE_UID);
            
            if ($messages !== false && !empty($messages)) {
                foreach ($messages as $uid) {
                    if ($uid === $currentEmail['uid']) continue;
                    
                    $msgNum = imap_msgno($this->connection, $uid);
                    $email = $this->getEmail($msgNum);
                    if ($email) {
                        $thread[] = $email;
                    }
                }
                
                usort($thread, function ($a, $b) {
                    return strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0);
                });
            }
        }

        return $thread;
    }

    public function getSentEmails(int $page = 1, int $perPage = 20): array
    {
        if (!$this->connect()) {
            return ['emails' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        $sentFolder = $this->findSentFolder();
        
        if ($sentFolder && $sentFolder !== $this->defaultFolder) {
            $mailbox = $this->getBaseMailbox() . $sentFolder;
            imap_reopen($this->connection, $mailbox);
        }

        $total = imap_num_msg($this->connection);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $start = $total - (($page - 1) * $perPage);
        $end = max(1, $start - $perPage + 1);

        $emails = [];
        
        for ($i = $start; $i >= $end && $i > 0; $i--) {
            $header = imap_headerinfo($this->connection, $i);
            if ($header === false) continue;

            $emails[] = $this->parseEmailHeader($header, $i);
        }

        if ($sentFolder && $sentFolder !== $this->defaultFolder) {
            $inbox = $this->getBaseMailbox() . $this->defaultFolder;
            imap_reopen($this->connection, $inbox);
        }

        return [
            'emails' => $emails,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    protected function findSentFolder(): ?string
    {
        $folders = $this->getFolders();
        
        foreach ($folders as $folder) {
            $name = strtolower($folder['name']);
            if (str_contains($name, 'sent') || str_contains($name, 'envoy')) {
                return $folder['short'];
            }
        }

        return null;
    }
}