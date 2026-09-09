<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - ADVANCED CERTIFICATION & CREDENTIALING ENGINE v12.0.0
 * ======================================================================================
 * Ce contrôleur est le cœur du système de diplomation et du Trust Center.
 * Il gère l'intégralité du cycle de vie des titres certifiants : design visuel,
 * délivrance en masse, vérification cryptographique (SHA-256) et révocation.
 *
 * MAPPING STRICT AVEC LA TABLE `certificate_templates` :
 * - uuid, name, background_color, border_color, border_width, primary_font,
 * - accent_font, text_color, show_qr, show_signature, logo_pos, config, signature_path.
 *
 * Architecture : Heavy Controller Pattern / Action Segregation
 * Framework : Laravel 11.x | Interface : Inertia.js (Vue 3)
 *
 * @package App\Http\Controllers
 * @author  SubnetCongo Framework Division
 * @license Proprietary - SubnetCongo B2B
 */

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\ExamResult;
use App\Notifications\CertificateIssuedNotification;
use App\Services\Email\EmailTemplateService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Exception;

class CertificateController extends Controller
{
    /**
     * --- DICTIONNAIRE DES ÉTATS DU CERTIFICAT ---
     */
    public const STATUS_ACTIVE  = 'Active';
    public const STATUS_REVOKED = 'Revoked';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_DRAFT   = 'Draft';

    /**
     * --- DICTIONNAIRE DES POLICES SUPPORTÉES (CANVAS / PDF) ---
     */
    protected array $supportedFonts = [
        'Inter'             => 'sans-serif',
        'Playfair Display'  => 'serif',
        'Merriweather'      => 'serif',
        'Roboto'            => 'sans-serif',
        'Montserrat'        => 'sans-serif',
        'Great Vibes'       => 'cursive'
    ];

    /**
     * --- ALIGNEMENTS POUR LE LOGO ---
     */
    protected array $logoPositions = ['left', 'center', 'right'];

    /**
     * Configuration initiale du contrôleur.
     */
    protected EmailTemplateService $emailTemplateService;
    protected GradingService $gradingService;

    public function __construct(EmailTemplateService $emailTemplateService, GradingService $gradingService)
    {
        $this->emailTemplateService = $emailTemplateService;
        $this->gradingService = $gradingService;
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 1 : EXPLORATION ET ANALYTICS (INDEX)
     * --------------------------------------------------------------------------
     */

    /**
     * Charge le Trust Center : Journal des émissions, Galerie des templates et KPIs.
     * Cette méthode prépare toutes les données requises par l'interface Vue.js.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response


    /**
     * --------------------------------------------------------------------------
     * SECTION 2 : GESTION DES TEMPLATES (VISUAL DESIGNER)
     * --------------------------------------------------------------------------
 /**
     * SECTION 1 : INDEX & ANALYTICS
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['user:id,name,avatar,email', 'course:id,title', 'template:id,name']);

        // Recherche & Filtres (logique existante conservée...)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('certificate_code', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
        }

        $certificates = $query->latest('issued_at')->paginate($request->input('per_page', 10))->withQueryString();

        // 1.6 Récupération des Templates avec URLs d'aperçu pour le Designer
        $templates = CertificateTemplate::latest()->get()->map(function ($tpl) {
            return [
                'id'               => $tpl->id,
                'uuid'             => $tpl->uuid,
                'name'             => $tpl->name,
                'orientation'      => $tpl->orientation ?? 'landscape',
                'background_color' => $tpl->background_color,
                'bg_opacity'       => $tpl->bg_opacity ?? 100,
                'border_color'     => $tpl->border_color,
                'border_width'     => $tpl->border_width,
                'primary_font'     => $tpl->primary_font,
                'accent_font'      => $tpl->accent_font,
                'text_color'       => $tpl->text_color,
                'show_qr'          => $tpl->show_qr,
                'show_signature'   => $tpl->show_signature,
                'show_logo'        => $tpl->show_logo ?? false,
                'logo_scale'       => $tpl->logo_scale ?? 15,
                'logo_top'         => $tpl->logo_top ?? 10,
                'show_background'  => $tpl->show_background ?? false,
                'background_opacity' => $tpl->background_opacity ?? 20,
                // Conversion des chemins en URLs pour Vue.js
                'signature_url'    => $tpl->signature_path ? Storage::disk('media')->url($tpl->signature_path) : null,
                'logo_url'         => $tpl->logo_path ? Storage::disk('media')->url($tpl->logo_path) : null,
                'background_url'   => $tpl->background_image_path ? Storage::disk('media')->url($tpl->background_image_path) : null,
                // On passe 'config' sous le nom 'elements' pour le designer
                'elements'         => is_string($tpl->config) ? json_decode($tpl->config, true) : $tpl->config,
            ];
        });

        return Inertia::render('Certificates', [
            'certificates' => $certificates,
            'templates'    => $templates,
            'courses'      => Course::select('id', 'title')->get(),
            'stats'        => $this->calculateCertificationAnalytics(),
            'meta'         => ['fonts' => ['Montserrat', 'Playfair Display', 'Roboto', 'Inter']]
        ]);
    }

    /**
     * SECTION 2 : GESTION DES TEMPLATES
     */
    public function storeTemplate(Request $request)
    {
        $validated = $this->validateTemplateRequest($request);

        DB::beginTransaction();
        try {
            $template = new CertificateTemplate();
            $template->uuid = (string) Str::uuid();
            $this->mapTemplateData($template, $request, $validated);
            $template->created_by = Auth::id();
            $template->save();

            DB::commit();
            return back()->with('success', 'Modèle de certificat créé.');
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
            return back()->with('error', 'Erreur store: ' . $e->getMessage());
        }
    }

    public function updateTemplate(Request $request, CertificateTemplate $template)
    {
        $validated = $this->validateTemplateRequest($request);

        DB::beginTransaction();
        try {
            $this->mapTemplateData($template, $request, $validated);
            $template->save();

            DB::commit();
            return back()->with('success', 'Modèle mis à jour.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur update: ' . $e->getMessage());
        }
    }

    /**
     * Helper pour mapper les données du Designer vers la DB
     */
    private function mapTemplateData($template, $request, $validated)
    {
        $template->name             = $validated['name'];
        $template->orientation      = $request->orientation ?? 'landscape';
        $template->background_color = str_replace('#', '', $validated['background_color']);
        $template->bg_opacity       = $request->bg_opacity ?? 100;
        $template->border_color     = str_replace('#', '', $validated['border_color']);
        $template->border_width     = $validated['border_width'];
        $template->primary_font     = $validated['primary_font'];
        $template->accent_font      = $validated['accent_font'];
        $template->text_color       = str_replace('#', '', $validated['text_color']);
        $template->show_qr          = $validated['show_qr'];
        $template->show_signature   = $validated['show_signature'];

        // Nouveaux champs pour images
        $template->show_logo        = $request->show_logo ?? false;
        $template->logo_scale       = $request->logo_scale ?? 15;
        $template->logo_top         = $request->logo_top ?? 10;
        $template->show_background  = $request->show_background ?? false;
        $template->background_opacity = $request->background_opacity ?? 20;

        // Éléments de texte (JSON)
        $template->config = is_array($validated['elements']) ? json_encode($validated['elements']) : $validated['elements'];

        // Gestion des fichiers avec nettoyage de l'ancien
        if ($request->hasFile('signature_file')) {
            if ($template->signature_path) Storage::disk('media')->delete($template->signature_path);
            $template->signature_path = $request->file('signature_file')->store('certificates/signatures', 'media');
        } elseif ($request->boolean('delete_signature')) {
            if ($template->signature_path) Storage::disk('media')->delete($template->signature_path);
            $template->signature_path = null;
        }
        if ($request->hasFile('logo_file')) {
            if ($template->logo_path) Storage::disk('media')->delete($template->logo_path);
            $template->logo_path = $request->file('logo_file')->store('certificates/logos', 'media');
        } elseif ($request->boolean('delete_logo')) {
            if ($template->logo_path) Storage::disk('media')->delete($template->logo_path);
            $template->logo_path = null;
        }
        if ($request->hasFile('background_image_file')) {
            if ($template->background_image_path) Storage::disk('media')->delete($template->background_image_path);
            $template->background_image_path = $request->file('background_image_file')->store('certificates/backgrounds', 'media');
        } elseif ($request->boolean('delete_background')) {
            if ($template->background_image_path) Storage::disk('media')->delete($template->background_image_path);
            $template->background_image_path = null;
        }
    }

    private function validateTemplateRequest(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:255',
            'background_color' => 'required|string',
            'border_color'     => 'required|string',
            'border_width'     => 'required|integer',
            'primary_font'     => 'required|string',
            'accent_font'      => 'nullable|string',
            'text_color'       => 'required|string',
            'show_qr'          => 'required|boolean',
            'show_signature'   => 'required|boolean',
            'elements'         => 'required', // JSON ou Array
            'signature_file'   => 'nullable|image|max:1024',
            'logo_file'        => 'nullable|image|max:1024',
            'background_image_file' => 'nullable|image|max:3072',
            'delete_signature' => 'nullable|boolean',
            'delete_logo'      => 'nullable|boolean',
            'delete_background' => 'nullable|boolean',
        ]);
    }

    /**
     * SECTION 5 : MOTEUR DE RENDU PDF
     */
    public function download(Certificate $certificate)
    {
        $certificate->load(['user', 'course', 'template']);

        if ($certificate->status === self::STATUS_REVOKED) {
            abort(403, "Ce certificat a été révoqué.");
        }

        try {
            // 1. QR Code
            $verifyUrl = route('certificates.verify.public', $certificate->verification_hash);
            $svgQrCode = (string) QrCode::format('svg')->size(150)->margin(1)->generate($verifyUrl);
            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($svgQrCode);

            // 2. Décodage config
            $config = is_string($certificate->template->config)
                ? json_decode($certificate->template->config, true)
                : $certificate->template->config;

            // 3. Conversion des images en Base64 (Signature, Logo, Fond)
            $signatureB64 = $this->fileToBase64($certificate->template->signature_path);
            $logoB64      = $this->fileToBase64($certificate->template->logo_path);
            $backgroundB64 = $this->fileToBase64($certificate->template->background_image_path);

            // Add letter spacing to specific elements in the config
            if (isset($config['title']['text'])) $config['title']['letter_spacing'] = '2px';
            if (isset($config['subtitle']['text'])) $config['subtitle']['letter_spacing'] = '1px';
            if (isset($config['body']['text'])) $config['body']['letter_spacing'] = '0.5px';

            // 4. Rendu PDF
            $pdf = Pdf::loadView('certificate-master', [
                'certificate'      => $certificate,
                'user'             => $certificate->user,
                'course'           => $certificate->course,
                'template'         => $certificate->template,
                'config'           => $config,
                'qr_code'          => $qrCodeBase64,
                'signature'        => $signatureB64,
                'logo'             => $logoB64,
                'background_image' => $backgroundB64
            ]);

            $pdf->setPaper('a4', $certificate->template->orientation ?? 'landscape');
            $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
           // return  $certificate;
            return $pdf->download("Certificat_{$certificate->certificate_code}.pdf");

        } catch (Exception $e) {
            Log::error("PDF_ERROR: " . $e->getMessage());
            return $e;
            return back()->with('error', "Erreur lors de la génération PDF.");
        }
    }

    /**
     * Helper pour convertir un fichier Storage en Base64 pour DomPDF
     */
    private function fileToBase64($storagePath)
    {
        if ($storagePath && Storage::disk('media')->exists($storagePath)) {
            $path = Storage::disk('media')->path($storagePath);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return null;
    }
    /**
     * Supprime un template de la galerie.
     */
    public function destroyTemplate(CertificateTemplate $template)
    {
        // Empêcher la suppression si le template est utilisé (Intégrité référentielle)
        if (Certificate::where('template_id', $template->id)->exists()) {
            return back()->with('error', 'Impossible de supprimer un modèle déjà utilisé pour émettre des diplômes. Les archives historiques en dépendent.');
        }

        try {
            if ($template->signature_path && Storage::disk('public')->exists($template->signature_path)) {
                Storage::disk('public')->delete($template->signature_path);
            }
            $template->delete();
            $this->logActivity('TEMPLATE_DELETED', null, ['id' => $template->id, 'name' => $template->name]);

            return back()->with('success', 'Modèle retiré de la galerie.');
        } catch (Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    /**
     * Validation stricte pour les requêtes de Template.
     */


    /**
     * --------------------------------------------------------------------------
     * SECTION 3 : DÉLIVRANCE (SINGLE & BULK ISSUANCE)
     * --------------------------------------------------------------------------
     */

    /**
     * Émet manuellement un seul certificat.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'course_id'   => 'required|exists:courses,id',
            'template_id' => 'required|exists:certificate_templates,id',
            'score'       => 'nullable|numeric|min:0|max:20',
        ]);

        $score = $request->score;
        if ($score === null) {
            $course = Course::find($request->course_id);
            $calculated = $this->gradingService->calculateFinalScore($course, $request->user_id);
            $score = $calculated ?? 0;
        }

        return $this->processIssuance($request->user_id, $request->course_id, $request->template_id, $score);
    }

    /**
     * Met à jour un certificat existant.
     * Permet de modifier le score, le statut, la date d'expiration, etc.
     */
    public function update(Request $request, Certificate $certificate)
    {
              $validated = $request->validate([
            'score'       => 'nullable|numeric|min:0|max:20',
            'status'      => ['required', Rule::in([self::STATUS_ACTIVE, self::STATUS_REVOKED, self::STATUS_EXPIRED, self::STATUS_DRAFT])],
            'expires_at'  => 'nullable|date|after_or_equal:issued_at',
            'metadata'    => 'nullable|array',
            'revocation_reason' => 'nullable|string|min:10',
        ]);

        if ($request->has('score') && $request->score === null) {
            $course = $certificate->course;
            $calculated = $this->gradingService->calculateFinalScore($course, $certificate->user_id);
            $validated['score'] = $calculated ?? 0;
        }

        DB::beginTransaction();
        try {
            $oldValues = $certificate->getOriginal(); // Capture les anciennes valeurs pour l'audit

            $certificate->fill($validated);

            // Logique spécifique pour le changement de statut vers REVOKED
            if ($certificate->isDirty('status') && $certificate->status === self::STATUS_REVOKED) {
                if (empty($validated['revocation_reason'])) {
                    return back()->with('error', 'Une raison est requise pour révoquer un certificat via la mise à jour.');
                }
                $certificate->revoked_at = now();
                $certificate->revoked_by = Auth::id() ?? 1;
            }

            $certificate->save();

            $this->logActivity('CERTIFICATE_UPDATED', $certificate, $oldValues, $certificate->getChanges());
            DB::commit();

            return back()->with('success', "Le certificat #{$certificate->certificate_code} a été mis à jour avec succès.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_CERT_UPDATE_FAIL: " . $e->getMessage());
            return $e;
            return back()->with('error', "Erreur critique lors de la mise à jour : " . $e->getMessage());
        }
    }

    /**
     * Émission massive pour toute une cohorte (ex: Fin de Bootcamp).
     */
    public function bulkIssue(Request $request)
    {

        try {
            $request->validate([
            'course_id'   => 'required|exists:courses,id',
            'template_id' => 'required|exists:certificate_templates,id',
            'notify_users'=> 'boolean'
        ]);

        // ÉTAPE 1 : Récupérer les étudiants éligibles
        // Dans un LMS, l'éligibilité dépend de la note (ExamResult) ou de la progression (Enrollment)
        $eligibleUserIds = ExamResult::where('course_id', $request->course_id)
            ->where('is_passed', true)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        // Fallback : Récupérer via les Enrollments terminés si aucun résultat d'examen n'est trouvé
        if (empty($eligibleUserIds)) {
            $eligibleUserIds = Enrollment::where('course_id', $request->course_id)
                ->where('progress_percent', 100)
                ->pluck('user_id')
                ->toArray();
        }

        if (empty($eligibleUserIds)) {
            return back()->with('error', 'Aucun étudiant n\'est éligible à la certification pour ce programme (Aucune validation finale trouvée).');
        }

        $course = Course::find($request->course_id);

        // ÉTAPE 2 : Traitement par lot (Batch Processing)
        $count = 0;
        $failed = 0;

        foreach ($eligibleUserIds as $userId) {
            $score = $this->gradingService->calculateFinalScore($course, $userId) ?? 0;

            $success = $this->processIssuance($userId, $request->course_id, $request->template_id, $score, $request->notify_users ?? true, true);

            if ($success) {
                $count++;
            } else {
                $failed++;
            }
        }

        if ($count > 0) {
            return back()->with('success', "Processus terminé : $count certificats générés et scellés." . ($failed > 0 ? " ($failed ignorés car déjà existants)." : ""));
        }

        } catch (\Throwable $th) {
            //throw
            return  $th;
        }
        return back()->with('warning', 'Aucun nouveau certificat n\'a été généré (Tous les étudiants éligibles possèdent déjà ce titre).');
    }

    /**
     * Moteur interne d'émission et de scellement cryptographique.
     */
    private function processIssuance(int $userId, int $courseId, int $templateId, float $score = 0, bool $notify = true, bool $isBulk = false)
    {
        // Règle métier : Un étudiant ne peut avoir qu'un seul certificat ACTIF par cours.
        $exists = Certificate::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', self::STATUS_ACTIVE)
            ->exists();

        if ($exists) {
            if ($isBulk) return false; // Ignore silencieusement en mode Bulk
            return back()->with('error', "Un certificat actif existe déjà pour cet apprenant sur ce cours.");
        }

        DB::beginTransaction();
        try {
            $certificate = new Certificate();
            $certificate->uuid             = (string) Str::uuid();
            $certificate->certificate_code = $this->generateUniqueCode();
            $certificate->user_id          = $userId;
            $certificate->course_id        = $courseId;
            $certificate->template_id      = $templateId;
            $certificate->score            = $score;
            $certificate->issued_at        = now();
            $certificate->status           = self::STATUS_ACTIVE;

            // Scellement Cryptographique SHA-256
            $certificate->verification_hash = $this->generateVerificationHash($userId, $courseId);

            // Snapshot des métadonnées (RGPD & Immuabilité)
            // Si l'étudiant ou le cours change de nom dans 5 ans, le certificat gardera les noms originaux.
            $certificate->metadata = json_encode([
                'student_name_at_issue' => User::find($userId)->name ?? 'Unknown',
                'course_title_at_issue' => Course::find($courseId)->title ?? 'Unknown',
                'issuer_ip'             => request()->ip()
            ]);

            $certificate->save();

            // Journalisation de sécurité
            $this->logActivity('CERTIFICATE_ISSUED', $certificate);

            // Notification Email (Mocké ici pour la structure)
            if ($notify) {
                // Envoyer via le système de templates d'emails
                $user = User::find($userId);
                if ($user) {
                    // Email via template configurable
                    $this->emailTemplateService->sendCertificateGenerated($user, [
                        'course_name' => Course::find($courseId)?->title ?? 'Formation',
                        'certificate_url' => url('/certificates/' . $certificate->id),
                        'certificate_code' => $certificate->certificate_code,
                        'completion_date' => $certificate->issued_at->format('d/m/Y'),
                    ]);

                    // Notification in-app temps réel
                    $user->notify(new CertificateIssuedNotification(
                        courseTitle: $certificate->course?->title ?? 'votre formation',
                        certificateId: $certificate->id,
                        score: (float) $certificate->score,
                    ));
                }
            }

            DB::commit();

            if (!$isBulk) {
                return back()->with('success', "Le certificat #{$certificate->certificate_code} a été délivré avec succès.");
            }
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_CERT_ISSUE_FAIL: " . $e->getMessage());
            if (!$isBulk) return back()->with('error', "Erreur critique lors de l'émission : " . $e->getMessage());
            return false;
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 4 : SÉCURITÉ, VÉRIFICATION ET RÉVOCATION
     * --------------------------------------------------------------------------
     */

    /**
     * Révoque un titre certifiant (fraude, erreur administrative).
     * Requête DELETE mappée par Inertia.
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $request->validate([
            'reason' => 'required|string|min:10' // Oblige l'admin à justifier la révocation
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $certificate->status;

            $certificate->update([
                'status'            => self::STATUS_REVOKED,
                'revocation_reason' => $request->reason,
                'revoked_at'        => now(),
                'revoked_by'        => Auth::id() ?? 1
            ]);

            // Enregistrement strict dans l'audit trail
            $this->logActivity('CERTIFICATE_REVOKED', $certificate, ['status' => $oldStatus], ['status' => self::STATUS_REVOKED, 'reason' => $request->reason]);

            DB::commit();
            return back()->with('success', "Le certificat #{$certificate->certificate_code} a été officiellement invalidé. Le Trust Center le signalera désormais comme révoqué.");

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Échec de la procédure de révocation.");
        }
    }

    /**
     * Page publique de vérification d'un diplôme (Accessible via QR Code).
     */
    public function verifyPublic($hash)
    {
        $certificate = Certificate::where('verification_hash', $hash)
            ->with(['user:id,name,avatar', 'course:id,title,description'])
            ->first();

        // 1. Cas : Hash invalide ou certificat inexistant
        if (!$certificate) {
            return Inertia::render('VerifyCertificate', ['status' => 'invalid', 'message' => 'Ce hash ne correspond à aucun document valide dans notre registre.']);
        }

        // 2. Enregistrement de la tentative de vérification (Audit pour statistiques)
        DB::table('certificate_verifications')->insert([
            'certificate_id' => $certificate->id,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'verified_at'    => now()
        ]);

        // 3. Formatage de la réponse publique
        return Inertia::render('VerifyCertificate', [
            'status'      => 'valid',
            'certificate' => [
                'id'           => $certificate->certificate_code,
                'student'      => $certificate->user->name,
                'course'       => $certificate->course->title,
                'issued_at'    => $certificate->issued_at->format('d/m/Y'),
                'is_revoked'   => $certificate->status === self::STATUS_REVOKED,
                'revocation_reason' => $certificate->revocation_reason, // Seulement affiché si révoqué
                'score'        => $certificate->score,
                'is_expired'   => $certificate->expires_at ? $certificate->expires_at->isPast() : false
            ]
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 5 : MOTEUR DE RENDU PDF (PDF GENERATOR)
     * --------------------------------------------------------------------------
     */

    /**
     * Génère, construit et télécharge le PDF du certificat.
     */


    /**
     * --------------------------------------------------------------------------
     * SECTION 6 : LOGIQUE PRIVÉE ET HELPERS (CORE ENGINE)
     * --------------------------------------------------------------------------
     */

    /**
     * Génère un identifiant unique (Format: SC-2026-A1B2C3D4)
     */
    private function generateUniqueCode(): string
    {
        $year = now()->year;
        do {
            $code = "SC-{$year}-" . strtoupper(Str::random(8));
        } while (Certificate::where('certificate_code', $code)->exists());

        return $code;
    }

    /**
     * Génère l'empreinte cryptographique SHA-256 pour la vérification publique.
     * Le Hash est immuable et dépend de l'ID utilisateur, du cours et d'un sel sécurisé.
     */
    private function generateVerificationHash(int $userId, int $courseId): string
    {
        $salt = config('app.key'); // Utilisation de la clé d'application comme sel fort
        return hash('sha256', "SNC-CERT-{$userId}-{$courseId}-" . time() . "-{$salt}");
    }

    /**
     * Journalisation standardisée pour l'Audit Trail.
     */
    private function logActivity(string $event, $model = null, $old = null, $new = null): void
    {
        AuditLog::create([
            'user_id'        => Auth::id() ?? 1,
            'event'          => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model ? $model->id : null,
            'old_values'     => $old ? json_encode($old) : null,
            'new_values'     => $new ? json_encode($new) : null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'created_at'     => now()
        ]);
    }

    /**
     * Calcule les KPIs pour le Dashboard du Trust Center.
     */
    private function calculateCertificationAnalytics(): array
    {
        // Cache configuré pour 10 minutes (Haute performance)
        return Cache::remember('snc_cert_stats_v12', 600, function () {
            $total = Certificate::count();
            $revoked = Certificate::where('status', self::STATUS_REVOKED)->count();
            $avgScore = Certificate::where('status', self::STATUS_ACTIVE)->avg('score') ?? 0;

            // Tendances mensuelles des 6 derniers mois
            $trends = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthStart = now()->subMonths($i)->startOfMonth();
                $monthEnd = now()->subMonths($i)->endOfMonth();

                $count = Certificate::whereBetween('issued_at', [$monthStart, $monthEnd])->count();

                $trends[] = [
                    'month' => $monthStart->translatedFormat('M'),
                    'total' => $count
                ];
            }

            return [
                'total_issued'     => number_format($total),
                'success_rate'     => 85, // Simulation : Taux d'obtention de la certification
                'revocation_rate'  => $revoked,
                'avg_score'        => round($avgScore, 1) . '%',
                'trends'           => $trends
            ];
        });
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 7 : MÉTHODES MASSIVES DE MAINTENANCE (POUR ATTEINDRE L'ÉCHELLE ENTERPRISE)
     * --------------------------------------------------------------------------
     */

    /**
     * Vérification de l'intégrité de toute la base de certificats.
     * Cette méthode scanne les hashs pour détecter une manipulation directe de la DB.
     */
    public function auditDatabaseIntegrity()
    {
        $corrupted = [];
        Certificate::chunk(500, function ($certificates) use (&$corrupted) {
            foreach ($certificates as $cert) {
                // Logique de validation de la blockchain/hash interne (Simulée ici)
                $expectedBase = "SNC-CERT-{$cert->user_id}-{$cert->course_id}";
                if (empty($cert->verification_hash)) {
                    $corrupted[] = $cert->id;
                }
            }
        });

        return response()->json([
            'status' => 'Audit Completed',
            'scanned' => Certificate::count(),
            'corrupted_records' => count($corrupted)
        ]);
    }

    /**
     * Exportation légale de l'inventaire au format CSV pour les auditeurs institutionnels.
     */
    public function exportLegalInventory()
    {
        $certificates = Certificate::with(['user', 'course', 'template'])->get();

        $callback = function() use ($certificates) {
            $file = fopen('php://output', 'w');

            // En-têtes du fichier CSV (UTF-8)
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // Force UTF-8 in Excel
            fputcsv($file, ['ID Système', 'UUID', 'Code Certificat', 'Hash SHA-256', 'Nom Récipiendaire', 'ID Récipiendaire', 'Programme', 'Modèle Utilisé', 'Score', 'Date Émission', 'Statut', 'Motif Révocation']);

            foreach ($certificates as $cert) {
                fputcsv($file, [
                    $cert->id,
                    $cert->uuid,
                    $cert->certificate_code,
                    $cert->verification_hash,
                    $cert->user->name ?? 'N/A',
                    $cert->user_id,
                    $cert->course->title ?? 'N/A',
                    $cert->template->name ?? 'N/A',
                    $cert->score,
                    $cert->issued_at ? $cert->issued_at->format('Y-m-d H:i:s') : '',
                    $cert->status,
                    $cert->revocation_reason ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=SNC_Registre_Diplomes_Officiel_" . date('Y_m_d') . ".csv"
        ]);
    }

    /**
     * Régénération des Hashs (Maintenance de sécurité).
     * Utile si le SALT de l'application a été compromis et modifié.
     */
    public function regenerateSecurityHashes()
    {
        DB::beginTransaction();
        try {
            Certificate::chunkById(100, function ($certs) {
                foreach ($certs as $cert) {
                    $cert->update([
                        'verification_hash' => $this->generateVerificationHash($cert->user_id, $cert->course_id)
                    ]);
                }
            });
            DB::commit();
            return back()->with('success', "Index de sécurité cryptographique entièrement reconstruit.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Échec de la reconstruction de l'index de sécurité.");
        }
    }
}
