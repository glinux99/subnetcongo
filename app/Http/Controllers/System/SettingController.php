<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Payload keys that are accepted (defence in depth) for the settings form.
     */
    private const ALLOWED_KEYS = [
        'general'  => ['site_name', 'site_slogan', 'site_description', 'contact_email', 'phone', 'address', 'city', 'timezone', 'locale'],
        'branding' => ['primary_color', 'secondary_color', 'logo_url', 'favicon_url', 'site_logo'],
        'lms'      => ['enable_certificates', 'certificate_prefix', 'min_pass_score', 'auto_enroll_on_payment', 'allow_course_preview', 'video_watermark'],
        'localization' => ['default_language', 'timezone', 'currency', 'currency_symbol', 'date_format'],
        'mail'     => ['driver', 'host', 'port', 'username', 'password', 'encryption', 'from_name', 'from_address', 'mailgun_domain', 'mailgun_secret'],
        'security' => ['enable_registration', 'force_2fa', 'enable_captcha', 'ip_whitelist', 'session_timeout'],
        'advanced' => ['maintenance_mode', 'debug_mode'],
    ];

    /**
     * Affiche la page principale des paramètres groupés par catégorie.
     */
    public function index()
    {
        $all = SystemSettings::all();

        $settings = [
            'general' => [
                'site_name' => $all['general_site_name'] ?? $all['site_name'] ?? 'SubnetCongo',
                'site_slogan' => $all['general_site_slogan'] ?? $all['site_tagline'] ?? $all['general_site_slogan'] ?? '',
                'site_description' => $all['general_site_description'] ?? $all['site_description'] ?? '',
                'contact_email' => $all['general_contact_email'] ?? $all['contact_email'] ?? 'admin@subnetcongo.net',
                'site_slogan_hidden' => '',
                'phone' => $all['general_contact_phone'] ?? $all['contact_phone'] ?? '+243 973 359 648',
                'address' => $all['general_address_line'] ?? $all['address_line'] ?? 'Goma, République Démocratique du Congo',
                'city' => $all['city'] ?? 'Goma',
                'timezone' => $all['timezone'] ?? 'Africa/Lubumbashi',
                'locale' => $all['locale'] ?? 'fr',
            ],
            'branding' => [
                'primary_color' => $all['primary_color'] ?? '#E1251B',
                'secondary_color' => $all['secondary_color'] ?? '#2563EB',
                'logo_url' => $all['site_logo'] ?? $all['logo_url'] ?? '/media/lms/logo-subnetcongo.png',
                'favicon_url' => $all['site_favicon'] ?? '/favicon.ico',
            ],
            'lms' => [
                'enable_certificates' => (bool) ($all['enable_certificates'] ?? '1'),
                'certificate_prefix' => $all['certificate_prefix'] ?? 'SC',
                'min_pass_score' => (float) ($all['min_pass_score'] ?? 60),
                'auto_enroll_on_payment' => (bool) ($all['auto_enroll_on_payment'] ?? '0'),
                'allow_course_preview' => (bool) ($all['allow_course_preview'] ?? '1'),
                'video_watermark' => (bool) ($all['video_watermark'] ?? '0'),
            ],
            'localization' => [
                'default_language' => $all['default_language'] ?? ($all['locale'] ?? 'fr'),
                'timezone' => $all['timezone'] ?? 'Africa/Lubumbashi',
                'currency' => $all['currency'] ?? 'CDF',
                'currency_symbol' => $all['currency_symbol'] ?? 'FC',
                'date_format' => $all['date_format'] ?? 'd/m/Y',
            ],
            'mail' => [
                'driver' => $all['mail_driver'] ?? 'smtp',
                'host' => $all['mail_host'] ?? '',
                'port' => (int) ($all['mail_port'] ?? 587),
                'username' => $all['mail_username'] ?? 'admin@subnetcongo.net',
                'password' => $all['mail_password'] ?? '',
                'encryption' => $all['mail_encryption'] ?? 'tls',
                'from_name' => $all['mail_from_name'] ?? 'SubnetCongo Academy',
                'from_address' => $all['mail_from_address'] ?? 'admin@subnetcongo.net',
                'mailgun_domain' => $all['mailgun_domain'] ?? '',
                'mailgun_secret' => $all['mailgun_secret'] ?? '',
            ],
            'security' => [
                'enable_registration' => (bool) ($all['enable_registration'] ?? '1'),
                'force_2fa' => (bool) ($all['force_2fa'] ?? '0'),
                'enable_captcha' => (bool) ($all['enable_captcha'] ?? '0'),
                'ip_whitelist' => $all['ip_whitelist'] ?? '',
                'session_timeout' => (int) ($all['session_timeout'] ?? 120),
            ],
            'advanced' => [
                'maintenance_mode' => (bool) ($all['maintenance_mode'] ?? '0'),
                'debug_mode' => (bool) ($all['debug_mode'] ?? '0'),
            ],
        ];

        $lists = [
            'languages' => [
                ['value' => 'fr', 'label' => 'Français'],
                ['value' => 'en', 'label' => 'Anglais (English)'],
            ],
            'timezones' => array_map(fn ($tz) => ['value' => $tz, 'label' => $tz], timezone_identifiers_list()),
            'currencies' => [
                ['value' => 'CDF', 'label' => 'Franc Congolais (CDF)'],
                ['value' => 'USD', 'label' => 'Dollar Américain (USD)'],
                ['value' => 'EUR', 'label' => 'Euro (EUR)'],
            ],
            'mailDrivers' => [
                ['value' => 'smtp', 'label' => 'SMTP (Recommandé)'],
                ['value' => 'mailgun', 'label' => 'Mailgun'],
                ['value' => 'sendmail', 'label' => 'Sendmail'],
                ['value' => 'log', 'label' => 'Log (Développement)'],
            ],
        ];

        $health = 100;
        if (! $settings['security']['force_2fa']) {
            $health -= 15;
        }
        if ($settings['advanced']['debug_mode']) {
            $health -= 25;
        }
        if (! $settings['security']['enable_captcha']) {
            $health -= 10;
        }
        if (empty($settings['mail']['host'])) {
            $health -= 10;
        }

        return Inertia::render('System/Settings', [
            'settings' => $settings,
            'env_type' => config('app.env'),
            'health_score' => $health,
            'dictionaries' => $lists,
        ]);
    }

    /**
     * Enregistre l'ensemble du formulaire (tous les onglets).
     * Écrit directement dans `web_settings`.
     */
    public function update(Request $request)
    {
        $validatedGroups = [];
        foreach (self::ALLOWED_KEYS as $group => $keys) {
            if (! $request->has($group)) {
                continue;
            }
            $data = $request->input($group);
            $clean = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, (array) $data)) {
                    $clean[$key] = $data[$key];
                }
            }
            if (! empty($clean)) {
                $validatedGroups[$group] = $clean;
            }
        }

        if (empty($validatedGroups)) {
            return back()->with('error', 'Aucun paramètre valide reçu.');
        }

        // Un minimum de validation métier
        $mail = $validatedGroups['mail'] ?? [];
        if (! empty($mail)) {
            if (isset($mail['host']) && $mail['host'] === '') {
                // allowed in dev / local
            }
            if (isset($mail['port']) && ! is_numeric($mail['port'])) {
                return back()->withErrors(['mail.port' => 'Le port doit être un nombre.']);
            }
        }

        // Assert: valid certificate_prefix is 1-10 uppercase letters/digits
        if (! empty($validatedGroups['lms']['certificate_prefix'])) {
            $prefix = trim((string) $validatedGroups['lms']['certificate_prefix']);
            if (! preg_match('/^[A-Za-z0-9_-]{1,10}$/', $prefix)) {
                return back()->withErrors(['lms.certificate_prefix' => 'Le préfixe doit être alphanumérique (1-10 caractères).']);
            }
            $validatedGroups['lms']['certificate_prefix'] = $prefix;
        }

        // Persistance
        $flat = [];
        foreach ($validatedGroups as $group => $data) {
            foreach ($data as $key => $value) {
                $storageKey = match ($key) {
                    'site_name' => 'general_site_name',
                    'site_slogan' => 'general_site_slogan',
                    'site_description' => 'general_site_description',
                    'contact_email' => 'general_contact_email',
                    'phone' => 'general_contact_phone',
                    'address' => 'general_address_line',
                    'city' => 'city',
                    'timezone' => 'timezone',
                    'locale' => 'locale',
                    'primary_color' => 'primary_color',
                    'secondary_color' => 'secondary_color',
                    'logo_url' => 'site_logo',
                    'favicon_url' => 'site_favicon',
                    'site_logo' => 'site_logo',
                    default => $key,
                };
                $flat[$storageKey] = $value;
            }
        }
        SystemSettings::setMany($flat);

        Cache::forever('maintenance_mode', (bool) ($validatedGroups['advanced']['maintenance_mode'] ?? false));
        Cache::forever('lms_settings', $validatedGroups['lms'] ?? []);

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'settings_updated',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'new_values' => json_encode($validatedGroups),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Paramètres système enregistrés.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048|mimes:png,jpg,jpeg,svg,webp',
        ]);

        try {
            $file = $request->file('logo');
            $name = 'uploads/branding/logo-' . now()->format('Ymd-His') . '.' . strtolower($file->getClientOriginalExtension());
            $file->storeAs('', $name, 'media');
            $url = '/media/' . $name;

            SystemSettings::setMany(['site_logo' => $url, 'logo_url' => $url]);
            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'logo_uploaded',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Logo mis à jour.');
        } catch (\Throwable $e) {
            Log::error('logo_upload_fail', ['error' => $e->getMessage()]);
            return back()->with('error', 'Échec du téléversement du logo.');
        }
    }

    public function toggleMaintenance(Request $request)
    {
        $toggle = $request->boolean('enable', ! (bool) SystemSettings::get('maintenance_mode', '0'));
        SystemSettings::setMany(['maintenance_mode' => $toggle ? '1' : '0']);

        if ($toggle) {
            Artisan::call('down', ['--render' => 'errors::503']);
        } else {
            Artisan::call('up');
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $toggle ? 'maintenance_on' : 'maintenance_off',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', $toggle ? 'Site mis en maintenance.' : 'Site remis en ligne.');
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'Tous les caches système ont été vidés.');
    }

    /**
     * Commandes artisan pré-définies (pré-validées) + allowedText pour un ajout contrôlé.
     */
    public function artisanCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:cache:clear,config:clear,route:clear,view:clear,live-sessions:sweep,schedule:run,down,up,key:generate,migrate:status,storage:link',
        ]);

        $command = $request->input('command');
        $start = microtime(true);
        $output = '';
        try {
            Artisan::call($command);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            Log::error('artisan_command_fail', ['command' => $command, 'error' => $e->getMessage()]);
            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'artisan_command_failed',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'new_values' => json_encode(['command' => $command, 'error' => $e->getMessage()]),
                'ip_address' => $request->ip(),
            ]);

            return back()->with('error', 'Échec : ' . $e->getMessage());
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'artisan_command_executed',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'new_values' => json_encode([
                'command' => $command,
                'output' => mb_substr($output, 0, 8000),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Commande '$command' exécutée.")->with('output', mb_substr($output, 0, 8000));
    }

    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            \Illuminate\Support\Facades\Mail::raw('Ceci est un email de test du système SubnetCongo.', function ($message) use ($request) {
                $message->to($request->input('email'))
                        ->subject('Test de configuration SMTP');
            });

            return back()->with('success', 'Email de test envoyé à ' . $request->input('email'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec de l\'envoi : ' . $e->getMessage());
        }
    }

    public function video()
    {
        $values = \App\Services\Video\VideoProviderConfig::all();

        $secretKeys = [
            'livekit_api_secret',
            'zoom_client_secret',
            'google_client_secret',
            'google_refresh_token',
            'bbb_secret',
        ];

        $masked = $values;
        $hasSecret = [];

        foreach ($secretKeys as $key) {
            $hasSecret[$key] = filled($values[$key] ?? null);
            $masked[$key] = '';
        }

        return Inertia::render('System/VideoProviders', [
            'settings' => $masked,
            'has_secret' => $hasSecret,
            'configured' => [
                'livekit' => \App\Services\Video\VideoProviderConfig::isConfigured('livekit'),
                'zoom' => \App\Services\Video\VideoProviderConfig::isConfigured('zoom'),
                'meet' => \App\Services\Video\VideoProviderConfig::isConfigured('meet'),
                'bbb' => \App\Services\Video\VideoProviderConfig::isConfigured('bbb'),
                'jitsi' => \App\Services\Video\VideoProviderConfig::isConfigured('jitsi'),
            ],
        ]);
    }

    public function updateVideo(Request $request)
    {
        $data = $request->validate([
            'default_provider' => 'nullable|in:livekit,zoom,meet,bbb,jitsi',
            'livekit_url' => 'nullable|url|max:255',
            'livekit_ws_url' => 'nullable|string|max:255',
            'livekit_api_key' => 'nullable|string|max:255',
            'livekit_api_secret' => 'nullable|string|max:255',
            'zoom_account_id' => 'nullable|string|max:255',
            'zoom_client_id' => 'nullable|string|max:255',
            'zoom_client_secret' => 'nullable|string|max:255',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'google_refresh_token' => 'nullable|string|max:1024',
            'google_calendar_id' => 'nullable|string|max:255',
            'bbb_base_url' => 'nullable|url|max:255',
            'bbb_secret' => 'nullable|string|max:255',
            'jitsi_base_url' => 'nullable|url|max:255',
        ]);

        $payload = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue; // conserve les secrets déjà enregistrés
            }
            $payload[$key] = is_string($value) ? trim($value) : $value;
        }

        \App\Services\Video\VideoProviderConfig::save($payload);

        \Illuminate\Support\Facades\Cache::forget('zoom_access_token');
        \Illuminate\Support\Facades\Cache::forget('google_meet_access_token');

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'video_providers_updated',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'new_values' => json_encode(['keys' => array_keys($payload)]),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Configuration des fournisseurs de visioconférence mise à jour.');
    }

    public function testVideoProvider(string $provider)
    {
        $result = match ($provider) {
            'zoom' => app(\App\Services\Video\ZoomService::class)->testConnection(),
            'meet' => app(\App\Services\Video\GoogleMeetService::class)->testConnection(),
            'bbb' => app(\App\Services\Video\BbbService::class)->testConnection(),
            'jitsi' => app(\App\Services\Video\JitsiService::class)->testConnection(),
            'livekit' => (function () {
                $health = app(\App\Services\LiveKit\LiveKitService::class)->healthCheck();

                return ($health['status'] ?? null) === 'ok'
                    ? ['success' => true, 'message' => 'Connexion LiveKit réussie (' . count($health['rooms'] ?? []) . ' salle(s)).']
                    : ['success' => false, 'message' => $health['message'] ?? 'LiveKit indisponible.'];
            })(),
            default => ['success' => false, 'message' => 'Fournisseur inconnu.'],
        };

        return response()->json($result);
    }
}
