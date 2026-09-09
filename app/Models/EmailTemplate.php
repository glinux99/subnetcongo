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
        'trigger_event',
        'category',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_ENROLLMENT = 'enrollment';
    public const CATEGORY_COURSE = 'course';
    public const CATEGORY_EXAM = 'exam';
    public const CATEGORY_WELCOME = 'welcome';
    public const CATEGORY_SUPPORT = 'support';
    public const CATEGORY_MARKETING = 'marketing';

    public const EVENT_CERTIFICATE_GENERATED = 'certificate_generated';
    public const EVENT_ENROLLMENT_CONFIRMED = 'enrollment_confirmed';
    public const EVENT_COURSE_ADDED = 'course_added';
    public const EVENT_EXAM_PASSED = 'exam_passed';
    public const EVENT_EXAM_FAILED = 'exam_failed';
    public const EVENT_WELCOME = 'welcome';
    public const EVENT_PASSWORD_RESET = 'password_reset';

    public static function getDefaultTemplates(): array
    {
        return [
            [
                'key' => 'certificate_generated',
                'name' => 'Certificat généré',
                'category' => self::CATEGORY_CERTIFICATE,
                'trigger_event' => self::EVENT_CERTIFICATE_GENERATED,
                'subject' => 'Votre certificat - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Felicitations ! Vous avez complete avec succes <strong>{{course_name}}</strong>.</p>

<p>Votre certificat est disponible en telechargement. Vous pouvez y acceder via votre espace apprenant ou directement via le lien ci-dessous.</p>

<p><a href="{{certificate_url}}">Telecharger mon certificat</a></p>

<p>Ce certificat est valable indefiniment et peut etre verifie avec le code unique : <strong>{{certificate_code}}</strong>.</p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'certificate_url', 'certificate_code', 'completion_date'],
                'is_active' => true,
            ],
            [
                'key' => 'enrollment_confirmed',
                'name' => 'Inscription confirmée',
                'category' => self::CATEGORY_ENROLLMENT,
                'trigger_event' => self::EVENT_ENROLLMENT_CONFIRMED,
                'subject' => 'Inscription confirmee - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Votre inscription au cours <strong>{{course_name}}</strong> a ete confirmee !</p>

<p>Vous pouvez des maintenant acceder a :</p>
<ul>
<li>Tous les modules du cours</li>
<li>Contenus video et ressources</li>
<li>Quiz et examens</li>
<li>Forums de discussion</li>
</ul>

<p><a href="{{course_url}}">Acceder au cours</a></p>

<p>Bonne formation !<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_url', 'enrollment_date', 'expiry_date'],
                'is_active' => true,
            ],
            [
                'key' => 'course_added',
                'name' => 'Nouveau cours ajouté',
                'category' => self::CATEGORY_COURSE,
                'trigger_event' => self::EVENT_COURSE_ADDED,
                'subject' => 'Nouveau cours ajoute a votre cursus - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Un nouveau cours vient d\'etre ajoute a votre programme de formation :</p>

<p><strong>{{course_name}}</strong></p>

<p>{{course_description}}</p>

<p><a href="{{course_url}}">Decouvrir ce cours</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_description', 'course_url', 'added_date'],
                'is_active' => true,
            ],
            [
                'key' => 'exam_passed',
                'name' => 'Examen réussi',
                'category' => self::CATEGORY_EXAM,
                'trigger_event' => self::EVENT_EXAM_PASSED,
                'subject' => 'Examen reussi - {{exam_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Felicitations ! Vous avez reussi l\'examen <strong>{{exam_name}}</strong>.</p>

<p>Votre score : <strong>{{score}}%</strong> (score minimum requis : {{passing_score}}%)</p>

<p>{{if_certificate}}</p>

<p>Continuez votre progression !<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'exam_name', 'score', 'passing_score', 'certificate_url', 'if_certificate'],
                'is_active' => true,
            ],
            [
                'key' => 'welcome',
                'name' => 'Email de bienvenue',
                'category' => self::CATEGORY_WELCOME,
                'trigger_event' => self::EVENT_WELCOME,
                'subject' => 'Bienvenue sur SubnetCongo Academy !',
                'content' => '<p>Bienvenue {{student_name}},</p>

<p>Nous sommes ravis de vous accueillir sur SubnetCongo Academy, votre plateforme de formation professionnelle.</p>

<p>Pour bien demarrer :</p>
<ol>
<li>Completez votre profil</li>
<li>Explorez nos formations disponibles</li>
<li>Inscrivez-vous a votre premier cours</li>
</ol>

<p><a href="{{dashboard_url}}">Acceder a mon espace</a></p>

<p>Notre equipe est a votre disposition pour toute question.</p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'dashboard_url', 'welcome_date'],
                'is_active' => true,
            ],
            [
                'key' => 'support_ticket_created',
                'name' => 'Ticket de support cree',
                'category' => self::CATEGORY_SUPPORT,
                'trigger_event' => 'support_ticket_created',
                'subject' => 'Ticket de support #{{ticket_id}} cree',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Votre demande de support a été créée avec succès.</p>

<p><strong>Sujet :</strong> {{ticket_subject}}</p>
<p><strong>Numéro :</strong> #{{ticket_id}}</p>
<p><strong>Statut :</strong> Ouvert</p>

<p>Notre équipe vous répondra dans les plus brefs délais.</p>

<p><a href="{{ticket_url}}">Suivre mon ticket</a></p>

<p>Cordialement,<br>Support SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'ticket_id', 'ticket_subject', 'ticket_url', 'created_at'],
                'is_active' => true,
            ],

            // STANDBY TEMPLATES (inactive by default)
            [
                'key' => 'enrollment_rejected',
                'name' => 'Inscription refusée',
                'category' => self::CATEGORY_ENROLLMENT,
                'trigger_event' => 'enrollment_rejected',
                'subject' => 'Inscription refusee - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Nous regrettons de vous informer que votre inscription au cours <strong>{{course_name}}</strong> n\'a pas pu etre acceptee.</p>

<p><strong>Raison :</strong> {{reason}}</p>

<p>N\'hesitez pas a nous contacter pour plus d\'informations.</p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_url', 'reason', 'rejection_date'],
                'is_active' => false,
            ],
            [
                'key' => 'enrollment_revoked',
                'name' => 'Accès révoqué',
                'category' => self::CATEGORY_ENROLLMENT,
                'trigger_event' => 'enrollment_revoked',
                'subject' => 'Acces revoque - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Nous vous informons que votre acces au cours <strong>{{course_name}}</strong> a ete revoque temporairement.</p>

<p><strong>Raison :</strong> {{revocation_reason}}</p>

<p>Pour plus d\'informations, contactez notre equipe.</p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_url', 'revocation_reason', 'revocation_date'],
                'is_active' => false,
            ],
            [
                'key' => 'course_completed',
                'name' => 'Cours terminé',
                'category' => self::CATEGORY_COURSE,
                'trigger_event' => 'course_completed',
                'subject' => 'Cours termine avec succes - {{course_name}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Felicitations ! Vous avez termine le cours <strong>{{course_name}}</strong>.</p>

<p>Votre progression : {{progress}}%</p>

<p>Vous pouvez des maintenant passer votre examen final si disponible.</p>

<p><a href="{{course_url}}">Continuer</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_url', 'completion_date', 'progress'],
                'is_active' => false,
            ],
            [
                'key' => 'live_session_started',
                'name' => 'Session live démarrée',
                'category' => 'live_session',
                'trigger_event' => 'live_session_started',
                'subject' => 'Session live en cours - {{session_title}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>La session live <strong>{{session_title}}</strong> du cours {{course_name}} vient de commencer !</p>

<p><strong>Heure :</strong> {{start_time}}</p>
<p><strong>Durée :</strong> {{duration}} minutes</p>

<p><a href="{{session_url}}">Rejoindre la session</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'session_title', 'course_name', 'session_url', 'start_time', 'duration'],
                'is_active' => false,
            ],
            [
                'key' => 'live_session_reminder',
                'name' => 'Rappel session live',
                'category' => 'live_session',
                'trigger_event' => 'live_session_reminder',
                'subject' => 'Rappel : Session live dans {{minutes_until}} min - {{session_title}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Rappel : La session live <strong>{{session_title}}</strong> commence dans {{minutes_until}} minutes.</p>

<p><strong>Heure :</strong> {{start_time}}</p>
<p><strong> Cours :</strong> {{course_name}}</p>

<p><a href="{{session_url}}">Rejoindre la session</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'session_title', 'course_name', 'session_url', 'start_time', 'minutes_until'],
                'is_active' => false,
            ],
            [
                'key' => 'payment_received',
                'name' => 'Paiement reçu',
                'category' => 'payment',
                'trigger_event' => 'payment_received',
                'subject' => 'Paiement confirme - Commande #{{order_number}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Nous avons bien recu votre paiement.</p>

<p><strong>Commande :</strong> #{{order_number}}</p>
<p><strong>Montant :</strong> {{amount}} {{currency}}</p>
<p><strong>Date :</strong> {{payment_date}}</p>

<p><a href="{{order_url}}">Voir ma commande</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'order_number', 'amount', 'currency', 'order_url', 'payment_date'],
                'is_active' => false,
            ],
            [
                'key' => 'payment_failed',
                'name' => 'Paiement échoué',
                'category' => 'payment',
                'trigger_event' => 'payment_failed',
                'subject' => 'Paiement echoue - Commande #{{order_number}}',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Nous regrettons de vous informer que votre paiement pour la commande #{{order_number}} a echoue.</p>

<p><strong>Montant :</strong> {{amount}} {{currency}}</p>
<p><strong>Raison :</strong> {{failure_reason}}</p>

<p><a href="{{retry_url}}">Reessayer le paiement</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'order_number', 'amount', 'currency', 'failure_reason', 'retry_url', 'payment_date'],
                'is_active' => false,
            ],
            [
                'key' => 'subscription_expiring',
                'name' => 'Abonnement expiré bientôt',
                'category' => self::CATEGORY_ENROLLMENT,
                'trigger_event' => 'subscription_expiring',
                'subject' => 'Votre acces expire dans {{days_remaining}} jours',
                'content' => '<p>Bonjour {{student_name}},</p>

<p>Votre acces au cours <strong>{{course_name}}</strong> expire le {{expiry_date}}.</p>

<p>Il vous reste <strong>{{days_remaining}} jours</strong> pour completer votre formation.</p>

<p><a href="{{renew_url}}">Renouveler mon acces</a></p>

<p>Cordialement,<br>L\'equipe SubnetCongo Academy</p>',
                'variables' => ['student_name', 'student_email', 'course_name', 'course_url', 'days_remaining', 'expiry_date', 'renew_url'],
                'is_active' => false,
            ],
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
            
            $subject = str_replace($placeholder, $value, $subject);
            $content = str_replace($placeholder, $value, $content);
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
            self::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
