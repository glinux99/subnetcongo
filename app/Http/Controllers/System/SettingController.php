<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting; // Modèle clé-valeur (key, value, group, type)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use DateTimeZone;

class SettingController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:manage-settings');
    }

    /**
     * Affiche la page principale des paramètres groupés par catégorie.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Récupérer tous les paramètres et les grouper
        $settings = Setting::all()->groupBy('group');

        // Structure par défaut si la BDD est vide
        $defaults = [
            'general' => [
                'site_name' => config('app.name'),
                'site_description' => 'Plateforme ERP & E-learning',
                'contact_email' => 'admin@subnetcongo.cd',
                'support_phone' => '+243000000000',
                'timezone' => config('app.timezone', 'Africa/Kinshasa'), // Ajouté pour éviter une erreur Vue
                'language' => config('app.locale', 'fr'), // Ajouté pour éviter une erreur Vue
            ],
            'branding' => [
                'logo_url' => null,
                'favicon_url' => null,
                'primary_color' => '#3b82f6',
            ],
            'security' => [
                'allow_registration' => true,
                'email_verification' => true,
                '2fa_required_admins' => false,
            ],
            'mail' => [
                'driver' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host', 'smtp.mailtrap.io'),
                'port' => config('mail.mailers.smtp.port', 2525),
                'username' => config('mail.mailers.smtp.username', ''),
                'from_address' => config('mail.from.address', 'hello@example.com'),
            ],
            'payment' => [
                'currency' => 'CDF',
                'symbol' => 'FC',
            ]
        ];

        // Fusionner avec les valeurs BDD
        $mergedSettings = [];
        foreach ($defaults as $group => $keys) {
            foreach ($keys as $key => $default) {
                $dbSetting = $settings->get($group)?->where('key', $key)->first();
                $mergedSettings[$group][$key] = $dbSetting ? $this->castValue($dbSetting->value, $dbSetting->type) : $default;
            }
        }

        // --- CORRECTION ICI : GÉNÉRATION DES LISTES POUR LES DROPDOWNS VUE.JS ---
        $lists = [
            'languages' => [
                ['value' => 'fr', 'label' => 'Français'],
                ['value' => 'en', 'label' => 'Anglais (English)'],
            ],
            // Génère la liste de tous les fuseaux horaires mondiaux
            'timezones' => array_map(fn($tz) => ['value' => $tz, 'label' => $tz], DateTimeZone::listIdentifiers()),
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
            ]
        ];

        return Inertia::render('System/Settings', [
            'settings' => $mergedSettings,
            'env_type' => config('app.env'),
            
            // On envoie les listes de deux manières pour couvrir toutes les syntaxes possibles de votre fichier Vue.js
            'dictionaries' => $lists, 
            'languages' => $lists['languages'],
            'timezones' => $lists['timezones'],
            'currencies' => $lists['currencies'],
            'mailDrivers' => $lists['mailDrivers'],
        ]);
    }

    /**
     * Met à jour un groupe de paramètres.
     */
    public function update(Request $request, string $group)
    {
        $rules = match ($group) {
            'general' => [
                'site_name' => 'required|string|max:50',
                'contact_email' => 'required|email',
            ],
            'mail' => [
                'host' => 'required|string',
                'port' => 'required|numeric',
                'from_address' => 'required|email',
            ],
            'security' => [
                'allow_registration' => 'boolean',
            ],
            default => [],
        };

        if (!empty($rules)) {
            $request->validate($rules);
        }

        DB::beginTransaction();
        try {
            foreach ($request->all() as $key => $value) {
                if (in_array($key, ['_method', '_token'])) continue;

                $type = 'string';
                if (is_bool($value)) $type = 'boolean';
                elseif (is_numeric($value)) $type = 'integer';
                elseif (is_array($value)) $type = 'json';

                Setting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value, 'type' => $type]
                );
            }

            Cache::forget('system_settings');

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'settings_updated',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'new_values' => json_encode(['group' => $group]),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return back()->with('success', "Paramètres du groupe '$group' mis à jour.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur de sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Upload du logo ou favicon.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'type' => 'required|in:logo,favicon',
            'file' => 'required|image|max:2048|mimes:png,jpg,ico,svg',
        ]);

        $type = $request->input('type');
        $path = $request->file('file')->store('branding', 'public');
        $url = Storage::url($path);

        Setting::updateOrCreate(
            ['group' => 'branding', 'key' => $type . '_url'],
            ['value' => $url, 'type' => 'string']
        );

        return back()->with('success', ucfirst($type) . ' mis à jour avec succès.');
    }

    /**
     * Envoie un email de test.
     */
    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            Mail::raw('Ceci est un email de test du système SubnetCongo.', function ($message) use ($request) {
                $message->to($request->input('email'))
                        ->subject('Test de configuration SMTP');
            });

            return back()->with('success', 'Email de test envoyé à ' . $request->input('email'));
        } catch (\Exception $e) {
            return back()->with('error', 'Échec de l\'envoi : ' . $e->getMessage());
        }
    }

    /**
     * Exporte la configuration.
     */
    public function backupConfig()
    {
        $settings = Setting::all()->groupBy('group');
        $fileName = 'settings_backup_' . date('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($settings) {
            echo $settings->toJson(JSON_PRETTY_PRINT);
        }, $fileName);
    }

    /**
     * Restaure une configuration.
     */
    public function restoreConfig(Request $request)
    {
        $request->validate(['file' => 'required|mimes:json']);

        try {
            $json = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($json, true);

            if (!is_array($data)) {
                throw new \Exception("Format JSON invalide.");
            }

            DB::beginTransaction();
            foreach ($data as $group => $items) {
                foreach ($items as $item) {
                    Setting::updateOrCreate(
                        ['group' => $group, 'key' => $item['key']],
                        ['value' => $item['value'], 'type' => $item['type']]
                    );
                }
            }
            DB::commit();
            Cache::forget('system_settings');

            return back()->with('success', 'Configuration restaurée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur de restauration : ' . $e->getMessage());
        }
    }

    /**
     * Affiche les variables d'environnement (masquées).
     */
    public function env()
    {
        $allowedKeys = [
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
            'MAIL_MAILER', 'MAIL_HOST',
            'QUEUE_CONNECTION',
        ];

        $envVars = [];
        foreach ($allowedKeys as $key) {
            $val = env($key);
            if (in_array($key, ['DB_PASSWORD', 'MAIL_PASSWORD'])) {
                $val = '********';
            }
            $envVars[$key] = $val;
        }

        return Inertia::render('System/Settings', [
            'env_vars' => $envVars,
        ]);
    }

    /**
     * Vide le cache de l'application.
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'Tous les caches système ont été vidés.');
    }

    /**
     * Helper pour caster les valeurs depuis la BDD.
     */
    private function castValue($value, $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}