<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Libellés français explicites pour les codes d'événements connus.
     */
    private const EVENT_TITLES = [
        'ADMIN_DASHBOARD_VIEW'      => 'Consultation du tableau de bord',
        'ARCHIVE'                   => 'Archivage',
        'ASSET_UPLOADED'            => 'Média téléversé',
        'CACHE_PURGED'              => 'Cache vidé',
        'CERTIFICATE_ISSUED'        => 'Certificat délivré',
        'CERTIFICATE_REVOKED'       => 'Certificat révoqué',
        'CERTIFICATE_UPDATED'       => 'Certificat modifié',
        'COHORT_SYNC'               => 'Synchronisation de cohorte',
        'COHORT_CREATED'            => 'Cohorte créée',
        'COHORT_UPDATED'            => 'Cohorte mise à jour',
        'COURSE_CREATED'            => 'Cours créé',
        'COURSE_UPDATED'            => 'Cours mis à jour',
        'CREATE'                    => 'Création',
        'UPDATE'                    => 'Mise à jour',
        'EXAM_CREATED'              => 'Examen créé',
        'EXAM_UPDATED'              => 'Examen mis à jour',
        'MAINTENANCE_OFF'           => 'Maintenance désactivée',
        'MAINTENANCE_ON'            => 'Maintenance activée',
        'QUESTION_ARCHIVED'         => 'Question archivée',
        'QUESTION_CREATED'          => 'Question créée',
        'QUESTION_DELETED'          => 'Question supprimée',
        'QUESTION_DUPLICATED'       => 'Question dupliquée',
        'QUESTION_UPDATED'          => 'Question mise à jour',
        'SETTINGS_UPDATED'          => 'Paramètres mis à jour',
        'STUDENT_ENROLLED'          => 'Étudiant inscrit',
        'STUDENTS_ENROLLED'         => 'Étudiants inscrits',
        'TEMPLATE_DELETED'          => 'Template supprimé',
        'LIVE_SESSION_STARTED'      => 'Session live démarrée',
        'ARTISAN_COMMAND_EXECUTED'  => 'Commande exécutée',
        'SYSTEM_ACTIVITY'           => 'Activité système',
    ];

    /**
     * Verbes et entités traduits pour l'humanisation des codes composés.
     */
    private const VERBS = [
        'created' => 'Création de', 'create' => 'Création de',
        'updated' => 'Mise à jour de', 'update' => 'Mise à jour de',
        'deleted' => 'Suppression de', 'delete' => 'Suppression de',
        'duplicated' => 'Duplication de',
        'uploaded' => 'Téléversement de',
        'revoked' => 'Révocation de',
        'issued' => 'Délivrance de',
        'enrolled' => 'Inscription de',
        'cleared' => 'Effacement de',
        'generated' => 'Génération de',
        'scheduled' => 'Planification de',
        'executed' => 'Exécution de',
        'tested' => 'Test de', 'test' => 'Test de',
        'pruned' => 'Nettoyage de',
        'flushed' => 'Réinitialisation de',
        'refunded' => 'Remboursement de',
        'changed' => 'Modification de',
        'synced' => 'Synchronisation de',
        'viewed' => 'Consultation de',
        'archived' => 'Archivage de',
        'restored' => 'Restauration de',
        'approved' => 'Approbation de',
        'rejected' => 'Rejet de',
        'activated' => 'Activation de',
        'deactivated' => 'Désactivation de',
        'published' => 'Publication de',
        'unpublished' => 'Dépublication de',
        'exported' => 'Export de',
        'imported' => 'Import de',
        'logged' => 'Connexion',
        'started' => 'Démarrage de',
        'stopped' => 'Arrêt de',
        'failed' => 'Échec de',
    ];

    private const ENTITIES = [
        'API_TOKEN' => 'jeton API', 'API_TOKENS' => 'jetons API',
        'API_SETTINGS' => 'paramètres API',
        'ARTISAN_COMMAND' => 'commande',
        'ASSET' => 'média',
        'BACKUP' => 'sauvegarde', 'DATABASE_BACKUP' => 'sauvegarde BDD',
        'BLOG_POST' => 'article de blog',
        'CERTIFICATE' => 'certificat',
        'COHORT' => 'cohorte',
        'COURSE' => 'cours',
        'EXAM' => 'examen',
        'INSTRUCTOR' => 'formateur', 'INSTRUCTORS' => 'formateurs',
        'LEARNING_PATH' => 'parcours',
        'LIVE_SESSION' => 'session live',
        'MAINTENANCE' => 'maintenance',
        'MENU' => 'menu',
        'PAGE' => 'page',
        'PAYMENT_GATEWAY' => 'passerelle de paiement',
        'QUESTION' => 'question',
        'ROBOTS_TXT' => 'fichier robots.txt',
        'SEO_GLOBAL' => 'SEO global', 'SEO_RULE' => 'règle SEO', 'SEO' => 'SEO',
        'SETTINGS' => 'paramètres', 'STATUS' => 'statut',
        'SITEMAP' => 'plan du site',
        'STUDENT' => 'étudiant', 'STUDENTS' => 'étudiants',
        'SYSTEM_LOG' => 'journal système',
        'TEMPLATE' => 'template',
        'TESTIMONIAL' => 'témoignage',
        'TRANSACTION' => 'transaction',
        'USER' => 'utilisateur',
        'VIDEO_PROVIDERS' => 'fournisseurs vidéo',
        'WIDGET' => 'widget',
        'ENROLLMENT' => 'inscription',
        'MODULE' => 'module',
        'LESSON' => 'leçon',
        'FORUM' => 'forum',
        'NOTIFICATION' => 'notification',
        'SUBSCRIPTION' => 'abonnement',
        'CAMPAIGN' => 'campagne',
        'COUPON' => 'coupon',
        'AFFILIATE' => 'affilié',
    ];

    /**
     * Code d'événement exploitable : préfère `event`, sinon `event_name`,
     * jamais la valeur par défaut « new event ».
     */
    public function resolvedEventCode(): string
    {
        $real = fn ($v) => $v !== null && trim((string) $v) !== ''
            && strcasecmp(trim((string) $v), 'new event') !== 0;

        if ($real($this->event)) {
            return trim((string) $this->event);
        }
        if ($real($this->event_name ?? null)) {
            return trim((string) $this->event_name);
        }

        return 'system_activity';
    }

    /**
     * Type technique complet de l'événement (slug conservé tel quel).
     */
    public function eventType(): string
    {
        return $this->resolvedEventCode();
    }

    /**
     * Titre lisible en français de l'événement.
     */
    public function eventTitle(): string
    {
        return static::titleForCode($this->resolvedEventCode());
    }

    /**
     * Titre lisible pour un code d'événement quelconque.
     */
    public static function titleForCode(string $code): string
    {
        $normalized = strtoupper(str_replace(['.', '-', ' '], '_', trim($code)));

        if (isset(self::EVENT_TITLES[$normalized])) {
            return self::EVENT_TITLES[$normalized];
        }

        // Décompose CODE_VERB_ENTITY… ex. PAYMENT_GATEWAY_CREATED
        $parts = explode('_', $normalized);
        $verb  = null;

        // Le verbe est souvent le dernier token (insensible à la casse)
        $last = strtolower((string) end($parts));
        if (isset(self::VERBS[$last])) {
            $verb = self::VERBS[$last];
            array_pop($parts);
        }

        // Token parasite « bulk » (ex. STUDENTS_BULK_DELETED)
        $parts = array_values(array_filter($parts, fn ($p) => strtoupper($p) !== 'BULK'));

        $entity = static::translateEntity($parts);

        if ($verb && $entity) {
            return static::frenchElision($verb . ' ' . $entity);
        }
        if ($verb) {
            return $verb;
        }
        if ($entity) {
            return static::frenchElision(ucfirst($entity));
        }

        // Repli : titre lisible du code brut
        return ucwords(strtolower(str_replace(['_', '.', '-'], ' ', $code)));
    }

    /**
     * Élision française : « de » devant une voyelle devient « d' ».
     */
    private static function frenchElision(string $text): string
    {
        return preg_replace('/\bde ([aeiouyAEIOUY])/', "d'$1", $text);
    }

    private static function translateEntity(array $parts): ?string
    {
        if (! $parts) {
            return null;
        }

        $joined = implode('_', $parts);
        if (isset(self::ENTITIES[$joined])) {
            return self::ENTITIES[$joined];
        }

        // Essaie les préfixes les plus longs (ex. DATABASE_BACKUP_CREATED)
        for ($i = count($parts); $i >= 1; $i--) {
            $candidate = implode('_', array_slice($parts, 0, $i));
            if (isset(self::ENTITIES[$candidate])) {
                $suffix = array_slice($parts, $i);
                $base   = self::ENTITIES[$candidate];
                if ($suffix) {
                    $base .= ' ' . static::translateEntity($suffix);
                }
                return $base;
            }
        }

        // Token inconnu : on le rend lisible
        return ucwords(strtolower(implode(' ', $parts)));
    }
}
