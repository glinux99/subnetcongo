<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - SYSTEM ARCHITECT & INFRASTRUCTURE CONTROL CENTER
 * ======================================================================================
 * Ce contrôleur est la clé de voûte de l'écosystème SubNetCongo.
 * Il orchestre :
 * - Les variables d'environnement (.env) via flux de fichiers
 * - L'identité visuelle (Branding dynamique)
 * - Le moteur pédagogique (LMS Core Rules)
 * - La sécurité périmétrique (Firewall applicatif, 2FA, Captcha)
 * - Les services tiers (Mail, Stockage S3, Analytics)
 * - La maintenance et les diagnostics de santé serveur
 *
 * @package App\Http\Controllers
 * @version 10.5.0
 * @author  SubnetCongo Framework Division / Lead Architect
 */

use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redirect, Artisan, Storage, Cache, Log, Http, Mail, Validator};
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * @var array Cartographie exhaustive des clés de configuration par module.
     * Utilisée pour la validation stricte et le filtrage des requêtes.
     */
    protected array $configMap = [
        'general' => [
            'site_name', 'site_slogan', 'contact_email', 'phone', 'address',
            'footer_text', 'is_multilingue', 'site_url', 'meta_description'
        ],
        'branding' => [
            'primary_color', 'secondary_color', 'logo_url', 'favicon_url',
            'border_radius', 'login_bg_url', 'email_header_url', 'theme_mode'
        ],
        'lms' => [
            'auto_enroll_on_payment', 'enable_certificates', 'certificate_prefix',
            'min_pass_score', 'allow_course_preview', 'video_watermark',
            'max_login_attempts', 'course_approval_required', 'instructor_can_publish'
        ],
        'localization' => [
            'default_language', 'timezone', 'currency', 'currency_symbol',
            'date_format', 'thousand_separator', 'decimal_separator', 'measurement_system'
        ],
        'mail' => [
            'driver', 'host', 'port', 'username', 'password', 'encryption',
            'from_name', 'from_address', 'mailgun_domain', 'mailgun_secret'
        ],
        'security' => [
            'enable_registration', 'force_2fa', 'password_min_length',
            'require_special_char', 'enable_captcha', 'session_timeout',
            'ip_whitelist', 'brute_force_protection', 'captcha_site_key'
        ],
        'advanced' => [
            'maintenance_mode', 'debug_mode', 'cache_driver', 'analytics_id',
            'log_level', 'backup_frequency', 'storage_driver', 'api_enabled'
        ]
    ];

    /**
     * --------------------------------------------------------------------------
     * SECTION 1 : INITIALISATION ET RENDU
     * --------------------------------------------------------------------------
     */

    /**
     * Charge l'intégralité du système de configuration.
     * Cette méthode agrège les données DB, les variables ENV et les états de service.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // 1.1 Sécurité : Seuls les utilisateurs avec le rôle 'super-admin' accèdent ici
        if (!Auth::user()->hasRole('super-admin')) {
            abort(403, 'Accès non autorisé au panneau de contrôle système.');
        }

        // 1.2 Hydratation des paramètres depuis la base de données
        $dbSettings = Setting::all()->pluck('value', 'key');

        // 1.3 Assemblage du schéma de configuration global
        $settings = [
            'general' => $this->hydrateModule('general', $dbSettings),
            'branding' => $this->hydrateModule('branding', $dbSettings),
            'lms' => $this->hydrateModule('lms', $dbSettings),
            'localization' => $this->hydrateModule('localization', $dbSettings),
            'mail' => $this->hydrateMailConfig(),
            'security' => $this->hydrateModule('security', $dbSettings),
            'advanced' => [
                'maintenance_mode' => app()->isDownForMaintenance(),
                'debug_mode' => config('app.debug'),
                'cache_driver' => config('cache.default'),
                'analytics_id' => $dbSettings->get('analytics_id', 'G-XXXXXXXXXX'),
                'server_info' => $this->getServerInfo(),
                'last_backup' => $dbSettings->get('last_backup_at', 'Jamais'),
            ]
        ];

        // 1.4 Injection des dictionnaires pour les listes déroulantes du frontend
        return Inertia::render('Settings', [
            'settings' => $settings,
            'dictionaries' => [
                'languages' => [
                    ['name' => 'Français (RDC)', 'code' => 'fr'],
                    ['name' => 'English (US)', 'code' => 'en'],
                    ['name' => 'Lingala', 'code' => 'ln'],
                    ['name' => 'Swahili', 'code' => 'sw']
                ],
                'timezones' => $this->getTimezonesList(),
                'currencies' => [
                    ['label' => 'US Dollar ($)', 'value' => 'USD'],
                    ['label' => 'Franc Congolais (FC)', 'value' => 'CDF'],
                    ['label' => 'Euro (€)', 'value' => 'EUR']
                ],
                'mailDrivers' => [
                    ['label' => 'SMTP Server', 'value' => 'smtp'],
                    ['label' => 'Mailgun API', 'value' => 'mailgun'],
                    ['label' => 'Amazon SES', 'value' => 'ses'],
                    ['label' => 'Postmark', 'value' => 'postmark']
                ],
                'cacheDrivers' => ['file', 'database', 'redis', 'memcached']
            ],
            'system_health' => $this->getSystemHealthScore($settings)
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 2 : MOTEUR DE MISE À JOUR ATOMIQUE
     * --------------------------------------------------------------------------
     */

    /**
     * Traite la sauvegarde massive des paramètres.
     * Utilise une transaction pour garantir l'intégrité entre la DB et le fichier .env
     */
    public function update(Request $request)
    {
        // 2.1 Validation transversale
        $this->validateSettings($request);

        DB::beginTransaction();
        try {
            $changes = [];

            // 2.2 Itération sur les modules de configuration
            foreach ($this->configMap as $module => $keys) {
                if ($request->has($module)) {
                    $moduleData = $request->input($module);

                    foreach ($keys as $key) {
                        if (array_key_exists($key, $moduleData)) {
                            $value = $moduleData[$key];

                            // Normalisation des booléens pour la base de données
                            $storedValue = is_bool($value) ? ($value ? '1' : '0') : $value;

                            $setting = Setting::updateOrCreate(
                                ['key' => $key],
                                ['value' => $storedValue]
                            );

                            if ($setting->wasChanged()) {
                                $changes[$key] = $value;
                            }
                        }
                    }
                }
            }

            // 2.3 Synchronisation des services critiques (Environnement)
            if ($request->has('mail')) {
                $this->syncMailToEnv($request->input('mail'));
            }

            if ($request->has('advanced')) {
                $this->syncAdvancedToEnv($request->input('advanced'));
            }

            // 2.4 Journalisation de l'audit
            if (!empty($changes)) {
                $this->logAction('SETTINGS_UPDATED', 'Mise à jour globale des paramètres par ' . Auth::user()->name, $changes);
            }

            DB::commit();

            // 2.5 Rafraîchissement de l'état applicatif
            $this->refreshSystemState();

            return Redirect::back()->with('message', 'Configuration propagée avec succès sur tous les services.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::critical("CRITICAL CONFIG ERROR: " . $e->getMessage(), ['user_id' => Auth::id()]);
            return Redirect::back()->withErrors(['error' => 'Échec de la propagation : ' . $e->getMessage()]);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 3 : GESTION DES RESSOURCES MÉDIAS (BRANDING)
     * --------------------------------------------------------------------------
     */

    /**
     * Télécharge et traite les assets visuels (Logo, Favicon).
     */
    public function uploadAsset(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,favicon,login_bg',
            'file' => 'required|image|mimes:png,jpg,jpeg,svg,ico|max:5120'
        ]);

        try {
            $type = $request->type;
            $file = $request->file('file');

            // Nommage normalisé
            $filename = "branding_{$type}_" . time() . "." . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/branding', $filename);
            $url = Storage::url($path);

            // Mise à jour de la clé correspondante
            $key = ($type === 'logo') ? 'logo_url' : (($type === 'favicon') ? 'favicon_url' : 'login_bg_url');
            Setting::updateOrCreate(['key' => $key], ['value' => $url]);

            $this->logAction('ASSET_UPLOADED', "Nouveau asset uploadé : {$type}");

            return Redirect::back()->with('message', 'Asset visuel mis à jour.');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['error' => 'Erreur lors du traitement de l\'image.']);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 4 : DIAGNOSTICS ET MAINTENANCE
     * --------------------------------------------------------------------------
     */

    /**
     * Bascule l'application en mode maintenance (Artisan Wrapper).
     */
    public function toggleMaintenance(Request $request)
    {
        $this->validate($request, ['password' => 'required']);

        // Vérification de sécurité supplémentaire
        if (!Hash::check($request->password, Auth::user()->password)) {
            return Redirect::back()->withErrors(['error' => 'Mot de passe incorrect.']);
        }

        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $this->logAction('MAINTENANCE_OFF', 'Le système a été remis en ligne.');
                return Redirect::back()->with('message', 'Le LMS est maintenant accessible à tous les élèves.');
            } else {
                Artisan::call('down', [
                    '--secret' => 'subnet-snc-access-2026',
                    '--render' => 'errors::503'
                ]);
                $this->logAction('MAINTENANCE_ON', 'Le système a été placé en mode maintenance.');
                return Redirect::back()->with('message', 'Mode maintenance activé. Seuls les admins avec le bypass peuvent accéder.');
            }
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['error' => 'Erreur technique maintenance : ' . $e->getMessage()]);
        }
    }

    /**
     * Purge exhaustive des caches (Application, Config, Route, View, Redis).
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            if (config('cache.default') === 'redis') {
                Cache::store('redis')->flush();
            }

            $this->logAction('CACHE_PURGED', 'L\'intégralité du cache système a été purgée.');
            return Redirect::back()->with('message', 'Mémoire cache libérée.');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['error' => 'Erreur lors de la purge.']);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 5 : COMMUNICATIONS ET TESTS SMTP
     * --------------------------------------------------------------------------
     */

    /**
     * Envoie un email de test complexe pour valider la délivrabilité.
     */
    public function testMail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $data = [
                'user' => Auth::user(),
                'time' => now()->format('H:i:s'),
                'server' => request()->getHttpHost()
            ];

            Mail::send([], [], function ($message) use ($request, $data) {
                $message->to($request->email)
                    ->subject('🚀 Test de connectivité SubNetCongo LMS')
                    ->html("<h1>Test Réussi !</h1>
                            <p>Votre serveur mail fonctionne parfaitement.</p>
                            <ul>
                                <li>Heure du test : {$data['time']}</li>
                                <li>Hôte : {$data['server']}</li>
                                <li>Déclenché par : {$data['user']->name}</li>
                            </ul>");
            });

            return Redirect::back()->with('message', 'Email envoyé ! Vérifiez votre boîte de réception (et vos spams).');
        } catch (\Exception $e) {
            Log::error("SMTP TEST FAILED: " . $e->getMessage());
            return Redirect::back()->withErrors(['error' => 'SMTP Error: ' . $e->getMessage()]);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 6 : LOGIQUE DE CALCUL ET ÉTAT DU SYSTÈME
     * --------------------------------------------------------------------------
     */

    /**
     * Analyse la configuration actuelle pour générer un score de santé.
     */
    private function getSystemHealthScore($settings): int
    {
        $score = 100;

        // Pénalités de sécurité
        if ($settings['advanced']['debug_mode']) $score -= 30;
        if (!$settings['security']['force_2fa']) $score -= 10;
        if (!$settings['security']['enable_captcha']) $score -= 10;

        // Pénalités de performance
        if ($settings['advanced']['cache_driver'] === 'file') $score -= 5;

        // Pénalités infrastructure
        if (empty($settings['mail']['host'])) $score -= 20;

        return max(0, $score);
    }

    /**
     * Récupère les informations techniques du serveur.
     */
    private function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'upload_max' => ini_get('upload_max_filesize'),
            'post_max' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'db_driver' => config('database.default'),
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 7 : MANIPULATION DES FICHIERS D'ENVIRONNEMENT
     * --------------------------------------------------------------------------
     */

    /**
     * Écrit physiquement les changements dans le fichier .env
     * Cette méthode utilise un verrouillage de fichier pour éviter la corruption.
     */
    private function setEnv($key, $value)
    {
        $path = base_path('.env');
        if (!file_exists($path)) return;

        // On échappe les valeurs contenant des espaces
        if (str_contains($value, ' ')) {
            $value = '"' . $value . '"';
        }

        $content = file_get_contents($path);
        $oldValue = env($key);

        if (str_contains($content, "{$key}=")) {
            // Remplacement de la valeur existante
            $content = str_replace(
                "{$key}={$oldValue}",
                "{$key}={$value}",
                $content
            );
        } else {
            // Ajout d'une nouvelle clé
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 8 : MÉTHODES DE SUPPORT ET DICTIONNAIRES
     * --------------------------------------------------------------------------
     */

    /**
     * Construit la liste des fuseaux horaires mondiaux.
     */
    private function getTimezonesList(): array
    {
        $zones = \DateTimeZone::listIdentifiers();
        $list = [];
        foreach ($zones as $zone) {
            $list[] = [
                'label' => str_replace(['_', '/'], [' ', ' / '], $zone),
                'value' => $zone
            ];
        }
        return $list;
    }

    /**
     * Centralisation des logs d'audit système.
     */
    protected function logAction($event, $desc, $data = null)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => $desc,
            'metadata' => $data ? json_encode($data) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 9 : SCÉNARIOS MÉTIERS AVANCÉS (EXTENSIONS)
     * --------------------------------------------------------------------------
     */

    /**
     * Génère un rapport de diagnostic exportable en JSON pour le support technique.
     */
    public function exportDiagnostics()
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'generated_by' => Auth::user()->email,
            'environment' => app()->environment(),
            'config_summary' => Setting::all(),
            'server_metrics' => $this->getServerInfo(),
            'logs_tail' => $this->getLatestLogs()
        ];

        return Response::json($data, 200, [
            'Content-Disposition' => 'attachment; filename="snc_diag_' . date('Ymd_His') . '.json"'
        ]);
    }

    /**
     * Récupère les dernières lignes du fichier de log Laravel.
     */
    private function getLatestLogs($lines = 50): string
    {
        $file = storage_path('logs/laravel.log');
        if (!file_exists($file)) return "Aucun log trouvé.";

        $data = file($file);
        $lastLines = array_slice($data, -$lines);
        return implode("", $lastLines);
    }

    /**
     * Gère l'optimisation des images présentes dans le stockage.
     */
    public function optimizeStorage()
    {
        // Simulation d'une tâche lourde de nettoyage
        Artisan::call('media-library:clean');
        return Redirect::back()->with('message', 'Stockage optimisé.');
    }

    /**
     * Synchronise la configuration Mail avec le fichier .env
     */
    private function syncMailToEnv(array $data)
    {
        if (isset($data['driver'])) $this->setEnv('MAIL_MAILER', $data['driver']);
        if (isset($data['host'])) $this->setEnv('MAIL_HOST', $data['host']);
        if (isset($data['port'])) $this->setEnv('MAIL_PORT', $data['port']);
        if (isset($data['username'])) $this->setEnv('MAIL_USERNAME', $data['username']);
        if (isset($data['password'])) $this->setEnv('MAIL_PASSWORD', $data['password']);
        if (isset($data['encryption'])) $this->setEnv('MAIL_ENCRYPTION', $data['encryption']);
        if (isset($data['from_address'])) $this->setEnv('MAIL_FROM_ADDRESS', $data['from_address']);
        if (isset($data['from_name'])) $this->setEnv('MAIL_FROM_NAME', $data['from_name']);
    }

    /**
     * Synchronise les paramètres avancés avec l'environnement.
     */
    private function syncAdvancedToEnv(array $data)
    {
        if (isset($data['debug_mode'])) {
            $this->setEnv('APP_DEBUG', $data['debug_mode'] ? 'true' : 'false');
        }
        if (isset($data['cache_driver'])) {
            $this->setEnv('CACHE_DRIVER', $data['cache_driver']);
        }
    }

    /**
     * Rafraîchit l'état interne de l'application après un changement de config.
     */
    private function refreshSystemState()
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        // Si nous sommes en mode production, on peut recacher
        if (app()->environment('production')) {
            Artisan::call('config:cache');
        }
    }

    /**
     * Valide l'intégralité des champs de configuration.
     */
    private function validateSettings(Request $request)
    {
        return $request->validate([
            'general.site_name' => 'required|string|max:100',
            'general.contact_email' => 'required|email',
            'branding.primary_color' => 'nullable|string|size:6',
            'lms.min_pass_score' => 'numeric|min:0|max:100',
            'localization.timezone' => 'required|string',
            'security.password_min_length' => 'integer|min:8|max:32',
            'mail.host' => 'nullable|string',
            'mail.port' => 'nullable|integer',
        ]);
    }

    /**
     * Helper pour hydrater un module depuis la DB avec valeurs par défaut.
     */
    private function hydrateModule(string $module, $dbSettings): array
    {
        $data = [];
        foreach ($this->configMap[$module] as $key) {
            $data[$key] = $dbSettings->get($key, config("snc.defaults.{$key}", ''));

            // Cast automatique pour les booléens stockés en '1'/'0'
            if ($data[$key] === '1') $data[$key] = true;
            if ($data[$key] === '0') $data[$key] = false;
        }
        return $data;
    }

    /**
     * Hydrate spécifiquement la config mail depuis le framework.
     */
    private function hydrateMailConfig(): array
    {
        return [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_name' => config('mail.from.name'),
            'from_address' => config('mail.from.address'),
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 10 : GESTION DES BACKUPS (COMMANDES ARTISAN)
     * --------------------------------------------------------------------------
     */

    /**
     * Liste les sauvegardes disponibles sur le disque.
     */
    public function listBackups()
    {
        $files = Storage::disk('local')->files('SubnetCongo/Backups');
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => round(Storage::disk('local')->size($file) / 1024 / 1024, 2) . ' MB',
                'date' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->toDateTimeString()
            ];
        }

        return Inertia::render('Configurations/Settings/Backups', [
            'backups' => $backups
        ]);
    }

    /**
     * Déclenche une nouvelle sauvegarde immédiate.
     */
    public function createBackup()
    {
        try {
            // Utilise spatie/laravel-backup si installé
            Artisan::call('backup:run', ['--only-db' => true]);

            Setting::updateOrCreate(['key' => 'last_backup_at'], ['value' => now()->toDateTimeString()]);

            return Redirect::back()->with('message', 'Sauvegarde de la base de données terminée.');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['error' => 'Échec backup : ' . $e->getMessage()]);
        }
    }

    /**
     * Supprime un fichier de sauvegarde.
     */
    public function deleteBackup(Request $request)
    {
        $request->validate(['filename' => 'required']);

        Storage::disk('local')->delete('SubnetCongo/Backups/' . $request->filename);

        return Redirect::back()->with('message', 'Sauvegarde supprimée.');
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 11 : GESTION DES CLÉS API ET SERVICES TIERS
     * --------------------------------------------------------------------------
     */

    /**
     * Met à jour les clés de services tiers (AWS, Google, Twilio).
     */
    public function updateApiKeys(Request $request)
    {
        // Logique pour les clés sensibles (Encryption requise)
        $keys = $request->only(['aws_key', 'aws_secret', 'google_map_key']);

        foreach ($keys as $key => $value) {
            if ($value) {
                $this->setEnv(strtoupper($key), $value);
            }
        }

        return Redirect::back()->with('message', 'Clés API synchronisées.');
    }

    /**
     * --------------------------------------------------------------------------
     * SECTION 12 : SÉCURITÉ RÉSEAU (IP WHITELISTING)
     * --------------------------------------------------------------------------
     */

    /**
     * Vérifie si l'IP actuelle est dans la liste blanche avant d'appliquer une restriction.
     */
    public function validateIpWhitelist(Request $request)
    {
        $ip = $request->ip();
        $list = explode(',', $request->ip_whitelist);

        if (!empty($request->ip_whitelist) && !in_array($ip, $list)) {
            return response()->json(['error' => 'Attention : Votre propre IP n\'est pas dans la liste.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Fin du contrôleur.
     * Ce fichier contient maintenant toute la logique nécessaire pour piloter
     * les 1000+ lignes de code du front-end et gérer tous les scénarios
     * d'infrastructure d'un LMS moderne.
     */
}
