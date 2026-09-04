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
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
                'site_description' => 'Plateforme E-learning',
                'contact_email' => 'admin@example.com',
                'support_phone' => '',
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
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
            ],
            'payment' => [
                'currency' => 'USD',
                'symbol' => '$',
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

        return Inertia::render('System/Settings', [
            'settings' => $mergedSettings,
            'env_type' => config('app.env'),
        ]);
    }

    /**
     * Met à jour un groupe de paramètres.
     *
     * @param Request $request
     * @param string $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $group)
    {
        // Validation dynamique selon le groupe
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

        $request->validate($rules);

        DB::beginTransaction();
        try {
            foreach ($request->all() as $key => $value) {
                // Ignorer les champs non pertinents
                if (in_array($key, ['_method', '_token'])) continue;

                // Gestion des types
                $type = 'string';
                if (is_bool($value)) $type = 'boolean';
                elseif (is_numeric($value)) $type = 'integer';
                elseif (is_array($value)) $type = 'json';

                Setting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value, 'type' => $type]
                );
            }

            // Vider le cache des settings
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
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
     * Envoie un email de test pour vérifier la configuration SMTP.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            // Utilisation de la config dynamique (si stockée en BDD, il faudrait la charger dans config() avant)
            // Ici on suppose que les settings sont déjà chargés dans le ServiceProvider

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
     * Exporte la configuration actuelle en JSON (Backup).
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
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
     * Restaure une configuration depuis un fichier JSON.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
     *
     * @return \Inertia\Response
     */
    public function env()
    {
        // Liste blanche des clés à afficher
        $allowedKeys = [
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
            'MAIL_MAILER', 'MAIL_HOST',
            'QUEUE_CONNECTION',
        ];

        $envVars = [];
        foreach ($allowedKeys as $key) {
            $val = env($key);
            // Masquer partiellement si sensible
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
     *
     * @return \Illuminate\Http\RedirectResponse
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
