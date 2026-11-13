<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject',
        'content',
        'variables',
        'is_active',
        'bcc_emails',
        'trigger_event',
        'category',
    ];

    protected $casts = [
        'variables' => 'array',
        'bcc_emails' => 'array',
        'is_active' => 'boolean',
    ];

    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_ENROLLMENT = 'enrollment';
    public const CATEGORY_COURSE = 'course';
    public const CATEGORY_EXAM = 'exam';
    public const CATEGORY_WELCOME = 'welcome';
    public const CATEGORY_SUPPORT = 'support';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_LIVE_SESSION = 'live_session';

    public const EVENT_CERTIFICATE_GENERATED = 'certificate_generated';
    public const EVENT_ENROLLMENT_CONFIRMED = 'enrollment_confirmed';
    public const EVENT_COURSE_ADDED = 'course_added';
    public const EVENT_EXAM_PASSED = 'exam_passed';
    public const EVENT_EXAM_FAILED = 'exam_failed';
    public const EVENT_WELCOME = 'welcome';
    public const EVENT_PASSWORD_RESET = 'password_reset';

    /**
     * Gabarit HTML professionnel commun à tous les emails transactionnels.
     * Charte SubnetCongo Academy : bleu #003399, accent rouge #EE1C25.
     * Responsive : large sur desktop (720px), paddings aérés ; compact sur mobile (100%).
     */
    protected static function wrapTemplate(string $innerHtml): string
    {
        return '<!DOCTYPE html>
<html lang="fr" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
<title>SubnetCongo Academy</title>
<!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<style type="text/css">
  @media only screen and (min-width: 620px) {
    .container-inner { padding: 36px 40px !important; }
    .hero { padding: 34px 40px !important; }
    .footer { padding: 26px 40px !important; }
    .mail-body { font-size: 16px !important; line-height: 1.75 !important; }
    .cta-btn { font-size: 15px !important; padding: 16px 40px !important; }
  }
  @media only screen and (max-width: 619px) {
    .container-outer { padding: 12px 8px !important; }
    .container-inner { padding: 24px 18px !important; }
    .hero { padding: 22px 18px !important; }
    .footer { padding: 18px 18px !important; }
    .mail-body { font-size: 14px !important; line-height: 1.6 !important; }
    .cta-btn { display: block !important; width: 100% !important; padding: 14px 16px !important; font-size: 14px !important; box-sizing: border-box !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:#eef2f7;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2f7;">
    <tr>
      <td style="padding:24px 12px;" class="container-outer">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="720" align="center" style="width:100%;max-width:720px;margin:0 auto;border-radius:18px;overflow:hidden;box-shadow:0 18px 40px -12px rgba(15,23,42,.18);">

          <!-- En-tête branded -->
          <tr>
            <td class="hero" style="background:linear-gradient(135deg,#003399 0%,#0a4fd6 60%,#1e64ff 100%);padding:34px 40px;text-align:center;">
              <div style="display:inline-block;background:#EE1C25;color:#ffffff;font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:5px 14px;border-radius:999px;margin-bottom:14px;box-shadow:0 4px 14px -4px rgba(238,28,37,.6);">SubnetCongo Academy</div>
              <h1 style="margin:0;color:#ffffff;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:24px;line-height:1.25;font-weight:800;letter-spacing:-.2px;">Formation &amp; Certification</h1>
              <p style="margin:8px 0 0;color:#cfe0ff;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:13px;letter-spacing:.3px;">Apprenez. Pratiquez. Certifiez-vous.</p>
            </td>
          </tr>

          <!-- Corps -->
          <tr>
            <td class="container-inner" style="background:#ffffff;padding:36px 40px;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:16px;line-height:1.75;color:#334155;">
              <div class="mail-body">
                ' . $innerHtml . '
              </div>
            </td>
          </tr>

          <!-- Pied de page -->
          <tr>
            <td class="footer" style="background:#0f172a;padding:26px 40px;text-align:center;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
              <p style="margin:0 0 8px;color:#cbd5e1;font-size:13px;font-weight:700;letter-spacing:.4px;">SubnetCongo Academy — Plateforme e-learning</p>
              <p style="margin:0;color:#64748b;font-size:11px;line-height:1.6;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.<br>
                Pour toute question : <a href="mailto:academy@subnetcongo.net" style="color:#60a5fa;text-decoration:none;font-weight:600;">academy@subnetcongo.net</a>
              </p>
              <p style="margin:12px 0 0;color:#475569;font-size:10px;letter-spacing:.5px;text-transform:uppercase;">© ' . date('Y') . ' SubnetCongo Sarl · RD Congo</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }

    /**
     * Bouton d'appel à l'action réutilisable.
     * Desktop large, centré ; mobile full-width.
     */
    protected static function cta(string $url, string $label): string
    {
        return '
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:28px 0 8px;">
  <tr>
    <td align="center">
      <a href="' . e($url) . '" class="cta-btn" style="display:inline-block;background:#003399;color:#ffffff;text-decoration:none;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-weight:800;font-size:15px;letter-spacing:.3px;padding:16px 40px;border-radius:12px;box-shadow:0 10px 24px -10px rgba(0,51,153,.55);box-sizing:border-box;">
        ' . e($label) . '&nbsp;&nbsp;→
      </a>
    </td>
  </tr>
</table>';
    }

    public static function getDefaultTemplates(): array
    {
        $t = function (string $key, string $name, string $category, string $trigger, string $subject, string $body, array $variables, array $bcc = []): array {
            return [
                'key' => $key,
                'name' => $name,
                'category' => $category,
                'trigger_event' => $trigger,
                'subject' => $subject,
                'content' => self::wrapTemplate($body),
                'variables' => $variables,
                'is_active' => true,
                'bcc_emails' => $bcc,
            ];
        };

        return [
            // ─────────────────────────── BIENVENUE & COMPTE ───────────────────────────
            $t('welcome', 'Email de bienvenue', self::CATEGORY_WELCOME, self::EVENT_WELCOME,
                'Bienvenue sur SubnetCongo Academy, {{student_name}} !',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous sommes ravis de vous accueillir sur <strong>SubnetCongo Academy</strong>, votre plateforme de formation professionnelle aux compétences certifiantes.</p>
<p>Pour bien démarrer votre parcours :</p>
<ol style="padding-left:20px;">
  <li>Complétez votre profil dans votre espace apprenant.</li>
  <li>Explorez notre catalogue de formations.</li>
  <li>Inscrivez-vous à votre premier cours et progressez à votre rythme.</li>
</ol>
<p>Notre équipe pédagogique reste à votre disposition pour toute question.</p>' .
                self::cta('{{dashboard_url}}', 'Accéder à mon espace'),
                ['student_name', 'student_email', 'dashboard_url', 'welcome_date']),

            $t('password_reset', 'Réinitialisation du mot de passe', self::CATEGORY_SECURITY, self::EVENT_PASSWORD_RESET,
                'Réinitialisation de votre mot de passe — SubnetCongo Academy',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous avons reçu une demande de réinitialisation du mot de passe de votre compte SubnetCongo Academy.</p>
<p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Ce lien est valable pendant une durée limitée.</p>
<p style="color:#b45309;font-size:13px;">Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement cet email : votre mot de passe actuel reste inchangé.</p>' .
                self::cta('{{reset_url}}', 'Réinitialiser mon mot de passe'),
                ['student_name', 'student_email', 'reset_url', 'expiry_minutes']),

            $t('password_changed', 'Mot de passe modifié', self::CATEGORY_SECURITY, 'password_changed',
                'Votre mot de passe a été modifié — SubnetCongo Academy',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Le mot de passe de votre compte SubnetCongo Academy (<strong>{{student_email}}</strong>) vient d\'être modifié le <strong>{{changed_at}}</strong>.</p>
<p>Si vous êtes à l\'origine de cette modification, aucune action n\'est requise.</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <strong style="color:#991b1b;">Si vous n\'avez pas fait cette modification</strong>, contactez immédiatement notre support à <a href="mailto:academy@subnetcongo.net" style="color:#991b1b;">academy@subnetcongo.net</a>.
</div>',
                ['student_name', 'student_email', 'changed_at']),

            $t('email_verification', 'Vérification de votre adresse email', self::CATEGORY_SECURITY, 'email_verification',
                'Confirmez votre adresse email — SubnetCongo Academy',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Merci de votre inscription. Pour activer pleinement votre compte et accéder à toutes les fonctionnalités, veuillez confirmer votre adresse email.</p>' .
                self::cta('{{verification_url}}', 'Vérifier mon adresse'),
                ['student_name', 'student_email', 'verification_url']),

            // ─────────────────────────── INSCRIPTIONS ───────────────────────────
            $t('enrollment_request_received', 'Demande d\'inscription reçue', self::CATEGORY_ENROLLMENT, 'enrollment_request_received',
                'Demande d\'inscription reçue — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous avons bien reçu votre demande d\'inscription au cours <strong>{{course_name}}</strong>.</p>
<div style="background:#eff6ff;border-left:4px solid #003399;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <p style="margin:0;font-size:14px;"><strong>Statut :</strong> En attente de validation</p>
  <p style="margin:4px 0 0;font-size:13px;color:#475569;">Notre équipe vérifie votre paiement{{payment_method}} et validera votre accès sous 24 à 48h. Vous recevrez un email dès que votre inscription sera confirmée.</p>
</div>',
                ['student_name', 'student_email', 'course_name', 'course_url', 'payment_method', 'request_date'],
                ['genesiskikimba@gmail.com', 'Joackimmuholu@gmail.com']
            ),

            $t('enrollment_request_admin', 'Nouvelle demande d\'inscription', self::CATEGORY_ENROLLMENT, 'enrollment_request_admin',
                'Nouvelle demande d\'inscription — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{admin_name}}</strong>,</p>
<p>Une nouvelle demande d\'inscription attend votre validation.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
  <tr><td style="padding:8px 0;color:#64748b;width:130px;">Apprenant</td><td style="padding:8px 0;"><strong>{{student_name}}</strong> ({{student_email}})</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Cours</td><td style="padding:8px 0;"><strong>{{course_name}}</strong></td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Méthode de paiement</td><td style="padding:8px 0;">{{payment_method}}</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Téléphone</td><td style="padding:8px 0;">{{phone_number}}</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">ID transaction</td><td style="padding:8px 0;">{{transaction_id}}</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Note</td><td style="padding:8px 0;">{{note}}</td></tr>
</table>' .
                self::cta('{{admin_url}}', 'Traiter la demande'),
                ['admin_name', 'student_name', 'student_email', 'course_name', 'payment_method', 'phone_number', 'transaction_id', 'note', 'admin_url']),

            $t('enrollment_confirmed', 'Inscription confirmée', self::CATEGORY_ENROLLMENT, self::EVENT_ENROLLMENT_CONFIRMED,
                'Inscription confirmée — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Excellente nouvelle : votre inscription au cours <strong>{{course_name}}</strong> est confirmée !</p>
<p>Vous pouvez dès maintenant accéder à :</p>
<ul style="padding-left:20px;">
  <li>Tous les modules et contenus du cours (vidéos, documents, ressources)</li>
  <li>Les quiz et examens de validation</li>
  <li>Les forums de discussion avec les instructeurs</li>
</ul>' .
                self::cta('{{course_url}}', 'Accéder au cours'),
                ['student_name', 'student_email', 'course_name', 'course_url', 'enrollment_date', 'expiry_date']),

            $t('enrollment_rejected', 'Inscription refusée', self::CATEGORY_ENROLLMENT, 'enrollment_rejected',
                'Inscription non retenue — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous regrettons de vous informer que votre demande d\'inscription au cours <strong>{{course_name}}</strong> n\'a pas pu être acceptée.</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <p style="margin:0;font-size:14px;"><strong>Motif :</strong> {{reason}}</p>
</div>
<p>N\'hésitez pas à nous contacter si vous pensez qu\'il s\'agit d\'une erreur ou si vous souhaitez plus d\'informations.</p>',
                ['student_name', 'student_email', 'course_name', 'course_url', 'reason', 'rejection_date']),

            $t('enrollment_revoked', 'Accès révoqué', self::CATEGORY_ENROLLMENT, 'enrollment_revoked',
                'Accès à un cours révoqué — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous vous informons que votre accès au cours <strong>{{course_name}}</strong> a été révoqué.</p>
<div style="background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <p style="margin:0;font-size:14px;"><strong>Motif :</strong> {{revocation_reason}}</p>
</div>
<p>Si vous pensez qu\'il s\'agit d\'une erreur, contactez notre équipe à <a href="mailto:academy@subnetcongo.net" style="color:#003399;">academy@subnetcongo.net</a>.</p>',
                ['student_name', 'student_email', 'course_name', 'course_url', 'revocation_reason', 'revocation_date']),

            $t('subscription_expiring', 'Abonnement bientôt expiré', self::CATEGORY_ENROLLMENT, 'subscription_expiring',
                'Votre accès expire dans {{days_remaining}} jour(s) — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Votre accès au cours <strong>{{course_name}}</strong> expire le <strong>{{expiry_date}}</strong>.</p>
<p>Il vous reste <strong>{{days_remaining}} jour(s)</strong> pour terminer votre formation.</p>
<p>Ne manquez pas cette opportunité de valider vos compétences avant l\'expiration de votre accès.</p>' .
                self::cta('{{renew_url}}', 'Renouveler mon accès'),
                ['student_name', 'student_email', 'course_name', 'course_url', 'days_remaining', 'expiry_date', 'renew_url']),

            // ─────────────────────────── COURS & FORMATION ───────────────────────────
            $t('course_added', 'Nouveau cours dans votre cursus', self::CATEGORY_COURSE, self::EVENT_COURSE_ADDED,
                'Nouveau cours ajouté — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Un nouveau cours vient d\'être ajouté à votre programme de formation :</p>
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin:16px 0;">
  <p style="margin:0 0 6px;font-size:17px;font-weight:700;color:#0f172a;">{{course_name}}</p>
  <p style="margin:0;color:#475569;font-size:14px;">{{course_description}}</p>
</div>' .
                self::cta('{{course_url}}', 'Découvrir ce cours'),
                ['student_name', 'student_email', 'course_name', 'course_description', 'course_url', 'added_date']),

            $t('course_completed', 'Cours terminé avec succès', self::CATEGORY_COURSE, 'course_completed',
                'Félicitations, vous avez terminé {{course_name}} !',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Félicitations ! Vous avez terminé le cours <strong>{{course_name}}</strong> à <strong>{{progress}}%</strong> de progression.</p>
<p>Si un examen final est disponible, c\'est le moment idéal pour le passer et obtenir votre certificat.</p>' .
                self::cta('{{course_url}}', 'Continuer mon parcours'),
                ['student_name', 'student_email', 'course_name', 'course_url', 'completion_date', 'progress']),

            $t('assignment_graded', 'Devoir corrigé et noté', self::CATEGORY_COURSE, 'assignment_graded',
                'Votre devoir a été corrigé — {{assignment_title}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Votre devoir <strong>{{assignment_title}}</strong> a été corrigé par votre instructeur.</p>
<div style="background:#eff6ff;border-left:4px solid #003399;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <p style="margin:0;font-size:14px;"><strong>Note obtenue :</strong> {{score}} / {{max_points}}</p>
  <p style="margin:4px 0 0;font-size:13px;color:#475569;"><strong>Feedback :</strong> {{feedback}}</p>
</div>' .
                self::cta('{{assignment_url}}', 'Voir ma correction'),
                ['student_name', 'student_email', 'assignment_title', 'score', 'max_points', 'feedback', 'assignment_url']),

            // ─────────────────────────── EXAMENS & CERTIFICATS ───────────────────────────
            $t('exam_passed', 'Examen réussi', self::CATEGORY_EXAM, self::EVENT_EXAM_PASSED,
                'Examen réussi — {{exam_name}} ({{score}}%)',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Félicitations ! Vous avez réussi l\'examen <strong>{{exam_name}}</strong>.</p>
<div style="background:#ecfdf5;border-left:4px solid #059669;padding:12px 16px;border-radius:6px;margin:16px 0;text-align:center;">
  <p style="margin:0;font-size:26px;font-weight:800;color:#047857;">{{score}}%</p>
  <p style="margin:0;font-size:12px;color:#059669;">Score minimum requis : {{passing_score}}%</p>
</div>
<p>{{if_certificate}}</p>' .
                self::cta('{{exam_url}}', 'Voir mon résultat'),
                ['student_name', 'student_email', 'exam_name', 'score', 'passing_score', 'certificate_url', 'if_certificate', 'exam_url']),

            $t('exam_failed', 'Résultat d\'examen', self::CATEGORY_EXAM, self::EVENT_EXAM_FAILED,
                'Résultat de l\'examen {{exam_name}} — {{score}}%',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Vous avez passé l\'examen <strong>{{exam_name}}</strong>. Voici votre résultat :</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin:16px 0;text-align:center;">
  <p style="margin:0;font-size:26px;font-weight:800;color:#b91c1c;">{{score}}%</p>
  <p style="margin:0;font-size:12px;color:#dc2626;">Score requis pour réussir : {{passing_score}}%</p>
</div>
<p>Pas d\'inquiétude : révisez les modules concernés et vous pourrez repasser l\'examen. Chaque tentative vous fait progresser.</p>' .
                self::cta('{{exam_url}}', 'Réviser et repasser'),
                ['student_name', 'student_email', 'exam_name', 'score', 'passing_score', 'exam_url', 'attempt_number']),

            $t('certificate_generated', 'Certificat délivré', self::CATEGORY_CERTIFICATE, self::EVENT_CERTIFICATE_GENERATED,
                'Votre certificat SubnetCongo — {{course_name}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Félicitations ! Vous avez complété avec succès la formation <strong>{{course_name}}</strong> et votre certificat a été généré le <strong>{{completion_date}}</strong>.</p>
<div style="background:#f8fafc;border:1px dashed #003399;border-radius:10px;padding:18px 20px;margin:16px 0;text-align:center;">
  <p style="margin:0 0 4px;font-size:12px;color:#64748b;letter-spacing:1px;text-transform:uppercase;">Code de vérification</p>
  <p style="margin:0;font-size:20px;font-weight:800;color:#003399;letter-spacing:2px;">{{certificate_code}}</p>
</div>
<p>Ce certificat est vérifiable en ligne et atteste de vos compétences acquises.</p>' .
                self::cta('{{certificate_url}}', 'Télécharger mon certificat'),
                ['student_name', 'student_email', 'course_name', 'certificate_url', 'certificate_code', 'completion_date']),

            // ─────────────────────────── SESSIONS LIVE ───────────────────────────
            $t('live_session_started', 'Session live en cours', self::CATEGORY_LIVE_SESSION, 'live_session_started',
                'Session live en cours — {{session_title}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>La session live <strong>{{session_title}}</strong> du cours {{course_name}} vient de commencer !</p>
<p><strong>Heure :</strong> {{start_time}} — <strong>Durée :</strong> {{duration}} min</p>
<p>Rejoignez la salle maintenant pour ne rien manquer.</p>' .
                self::cta('{{session_url}}', 'Rejoindre la session'),
                ['student_name', 'student_email', 'session_title', 'course_name', 'session_url', 'start_time', 'duration']),

            $t('live_session_reminder', 'Rappel de session live', self::CATEGORY_LIVE_SESSION, 'live_session_reminder',
                'Rappel : session live dans {{minutes_until}} min — {{session_title}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Rappel amical : la session live <strong>{{session_title}}</strong> commence dans <strong>{{minutes_until}} minutes</strong>.</p>
<p><strong>Heure :</strong> {{start_time}} — <strong>Cours :</strong> {{course_name}}</p>' .
                self::cta('{{session_url}}', 'Rejoindre la session'),
                ['student_name', 'student_email', 'session_title', 'course_name', 'session_url', 'start_time', 'minutes_until']),

            // ─────────────────────────── PAIEMENTS ───────────────────────────
            $t('payment_received', 'Paiement confirmé', self::CATEGORY_PAYMENT, 'payment_received',
                'Paiement confirmé — Commande #{{order_number}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous avons bien reçu votre paiement. Merci pour votre confiance.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
  <tr><td style="padding:8px 0;color:#64748b;width:130px;">Commande</td><td style="padding:8px 0;"><strong>#{{order_number}}</strong></td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Montant</td><td style="padding:8px 0;"><strong>{{amount}} {{currency}}</strong></td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Date</td><td style="padding:8px 0;">{{payment_date}}</td></tr>
</table>
<p>Vous recevrez prochainement la confirmation de votre inscription au(x) cours concerné(s).</p>' .
                self::cta('{{order_url}}', 'Voir ma commande'),
                ['student_name', 'student_email', 'order_number', 'amount', 'currency', 'order_url', 'payment_date']),

            $t('payment_failed', 'Paiement échoué', self::CATEGORY_PAYMENT, 'payment_failed',
                'Échec du paiement — Commande #{{order_number}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Nous regrettons de vous informer que votre paiement pour la commande <strong>#{{order_number}}</strong> n\'a pas abouti.</p>
<div style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin:16px 0;">
  <p style="margin:0;font-size:14px;"><strong>Montant :</strong> {{amount}} {{currency}} — <strong>Raison :</strong> {{failure_reason}}</p>
</div>
<p>Aucun montant n\'a été débité. Vous pouvez réessayer le paiement en toute sécurité.</p>' .
                self::cta('{{retry_url}}', 'Réessayer le paiement'),
                ['student_name', 'student_email', 'order_number', 'amount', 'currency', 'failure_reason', 'retry_url', 'payment_date']),

            $t('refund_issued', 'Remboursement effectué', self::CATEGORY_PAYMENT, 'refund_issued',
                'Remboursement effectué — Commande #{{order_number}}',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Votre demande de remboursement pour la commande <strong>#{{order_number}}</strong> a été traitée.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
  <tr><td style="padding:8px 0;color:#64748b;width:130px;">Montant remboursé</td><td style="padding:8px 0;"><strong>{{amount}} {{currency}}</strong></td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Date</td><td style="padding:8px 0;">{{refund_date}}</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Motif</td><td style="padding:8px 0;">{{refund_reason}}</td></tr>
</table>
<p>Le remboursement peut prendre quelques jours ouvrés pour apparaître sur votre relevé selon votre banque.</p>' .
                self::cta('{{order_url}}', 'Voir ma commande'),
                ['student_name', 'student_email', 'order_number', 'amount', 'currency', 'refund_date', 'refund_reason', 'order_url']),

            // ─────────────────────────── SUPPORT & CONTACT ───────────────────────────
            $t('support_ticket_created', 'Ticket de support créé', self::CATEGORY_SUPPORT, 'support_ticket_created',
                'Votre demande de support #{{ticket_id}} a été créée',
                '<p style="margin-top:0;">Bonjour <strong>{{student_name}}</strong>,</p>
<p>Votre demande de support a été créée avec succès. Notre équipe vous répondra dans les plus brefs délais.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
  <tr><td style="padding:8px 0;color:#64748b;width:130px;">Sujet</td><td style="padding:8px 0;"><strong>{{ticket_subject}}</strong></td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Numéro</td><td style="padding:8px 0;">#{{ticket_id}}</td></tr>
  <tr><td style="padding:8px 0;color:#64748b;">Statut</td><td style="padding:8px 0;">Ouvert</td></tr>
</table>' .
                self::cta('{{ticket_url}}', 'Suivre mon ticket'),
                ['student_name', 'student_email', 'ticket_id', 'ticket_subject', 'ticket_url', 'created_at']),

            $t('contact_confirmation', 'Message de contact reçu', self::CATEGORY_SUPPORT, 'contact_confirmation',
                'Nous avons bien reçu votre message — SubnetCongo Academy',
                '<p style="margin-top:0;">Bonjour <strong>{{visitor_name}}</strong>,</p>
<p>Merci de nous avoir contactés. Votre message concernant <strong>« {{subject}} »</strong> a bien été enregistré.</p>
<p>Notre équipe vous répondra sous <strong>48 heures ouvrées</strong> à l\'adresse <strong>{{visitor_email}}</strong>.</p>
<p style="font-size:13px;color:#64748b;">Référence de votre demande : <strong>{{ticket_number}}</strong></p>',
                ['visitor_name', 'visitor_email', 'subject', 'ticket_number']),
        ];
    }

    public function replaceVariables(array $data): array
    {
        $subject = $this->subject;
        $content = $this->content;

        foreach ($this->variables as $var) {
            $placeholder = '{{' . $var . '}}';
            $value = $data[$var] ?? '';

            if (is_bool($value)) {
                $value = $value ? 'Oui' : 'Non';
            }

            $subject = str_replace($placeholder, (string) $value, $subject);
            $content = str_replace($placeholder, e((string) $value), $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    public static function seedDefaults()
    {
        $defaults = self::getDefaultTemplates();

        foreach ($defaults as $template) {
            $existing = self::where('key', $template['key'])->first();

            if ($existing) {
                // Ne pas écraser la configuration BCC personnalisée par l'admin
                unset($template['bcc_emails']);
            }

            self::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
