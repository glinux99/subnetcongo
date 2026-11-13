<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Source unique de vérité pour les paramètres « Système » de SubnetCongo.
 *
 * Tout est stocké dans la table `web_settings` (groupe), consommé par
 * l'interface /system/settings (Vue) et appliqué à la configuration au boot.
 */
class SystemSettings
{
    private const CACHE_KEY = 'system_settings.all';

    private const TABLE = 'web_settings';

    /**
     * Valeurs par défaut (appliquées si la table est vide / manquante).
     */
    public const DEFAULTS = [
        'site_name'        => 'SubnetCongo',
        'site_tagline'     => 'Centre de formation et d\'excellence numérique en RDC',
        'site_description' => 'SubnetCongo Sarl : Interconnexion, sécurité réseau et formation certifiée en République Démocratique du Congo.',
        'site_logo'        => '/media/lms/logo-subnetcongo.png',
        'site_favicon'     => '/favicon.ico',
        'footer_text'      => 'SubnetCongo Sarl. Tous droits r�serv�s.',
        'contact_email'    => 'admin@subnetcongo.net',
        'contact_phone'    => '+243 973 359 648',
        'address_line'     => 'Goma, République Démocratique du Congo',
        'city'             => 'Goma',
        'primary_color'    => '#E1251B',
        'secondary_color'  => '#2563EB',
        'from_name'        => 'SubnetCongo Academy',

        'general_footer_text'      => 'SubnetCongo Sarl. Tous droits r�serv�s.',
        'general_contact_email'    => 'admin@subnetcongo.net',
        'general_contact_phone'    => '+243 973 359 648',
        'general_address_line'     => 'Goma, République Démocratique du Congo',
        'general_site_name'        => 'SubnetCongo',
        'general_site_slogan'      => 'Centre de formation et d\'excellence numérique en RDC',
        'general_site_description' => 'SubnetCongo Sarl : Interconnexion, sécurité réseau et formation certifiée en République Démocratique du Congo.',

        'mail_driver'       => 'smtp',
        'mail_host'         => '',
        'mail_port'         => '587',
        'mail_encryption'   => 'tls',
        'mail_username'     => 'admin@subnetcongo.net',
        'mail_password'     => '',
        'mail_from_name'    => 'SubnetCongo Academy',
        'mail_from_address' => 'admin@subnetcongo.net',

        'maintenance_mode'      => '0',
        'debug_mode'            => '0',
        'force_2fa'             => '0',
        'enable_registration'   => '1',
        'enable_captcha'        => '0',
        'enable_certificates'   => '1',
        'certificate_prefix'    => 'SC',
        'min_pass_score'        => '60',
        'session_timeout'       => '120',
        'timezone'              => 'Africa/Lubumbashi',
        'locale'                => 'fr',
        'auto_enroll_on_payment' => '0',
        'allow_course_preview'  => '1',
        'video_watermark'       => '0',
        'certificate_terms'     => '',
        'certificate_html_template' => '',
        'ip_whitelist'          => '',
    ];

    public static function get(string $key, $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function all(): array
    {
        if (Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $stored = [];
        try {
            if (Schema::hasTable(self::TABLE)) {
                $stored = \App\Models\WebSetting::all()->pluck('value', 'key')->toArray();
            }
        } catch (\Throwable $e) {
            // DB absente (install) : on retombe sur les défauts.
        }

        $merged = array_merge(self::DEFAULTS, $stored);
        Cache::put(self::CACHE_KEY, $merged, now()->addMinutes(5));
        return $merged;
    }

    public static function set(string $key, mixed $value, ?string $group = null): void
    {
        $group ??= match (true) {
            str_starts_with($key, 'mail_') => 'mail',
            str_starts_with($key, 'maintenance_'), str_starts_with($key, 'debug_') => 'advanced',
            str_starts_with($key, 'auto_'), str_starts_with($key, 'min_'), str_starts_with($key, 'certificate') => 'lms',
            str_starts_with($key, 'enable_') || str_starts_with($key, 'force_') || str_starts_with($key, 'ip_') || str_starts_with($key, 'session_') => 'security',
            str_starts_with($key, 'timezone'), str_starts_with($key, 'locale') => 'localization',
            str_starts_with($key, 'general_') || str_starts_with($key, 'site_') || str_starts_with($key, 'contact_') || str_starts_with($key, 'address_') || str_starts_with($key, 'city') || str_starts_with($key, 'footer_') => 'general',
            str_starts_with($key, 'primary_') || str_starts_with($key, 'secondary_') || str_starts_with($key, 'logo_') || str_starts_with($key, 'favicon') => 'branding',
            default => 'general',
        };

        \App\Models\WebSetting::updateOrCreate(
            ['key' => $key],
            ['value' => self::toString($value), 'group' => $group, 'type' => self::typeOf($value)]
        );
        Cache::forget(self::CACHE_KEY);
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Applique les settings à la configuration Laravel (appelé au boot).
     */
    public static function apply(): void
    {
        $all = self::all();

        // .env override: tous les params mail/crypto depuis la BDD
        if (! empty($all['mail_driver'])) {
            config(['mail.default' => $all['mail_driver']]);
        }
        if (! empty($all['mail_host'])) {
            config(['mail.mailers.smtp.host' => $all['mail_host']]);
        }
        if (! empty($all['mail_port'])) {
            config(['mail.mailers.smtp.port' => (int) $all['mail_port']]);
        }
        if (! empty($all['mail_encryption']) && $all['mail_encryption'] !== 'none') {
            config(['mail.mailers.smtp.encryption' => $all['mail_encryption']]);
        }
        if (! empty($all['mail_username'])) {
            config(['mail.mailers.smtp.username' => $all['mail_username']]);
        }
        if (! empty($all['mail_password'])) {
            config(['mail.mailers.smtp.password' => $all['mail_password']]);
        }
        if (! empty($all['mail_from_name'])) {
            config(['mail.from.name' => $all['mail_from_name']]);
        }
        if (! empty($all['mail_from_address'])) {
            config(['mail.from.address' => $all['mail_from_address']]);
        }

        // Fuseau et langue applicatives
        if (! empty($all['timezone']) && in_array($all['timezone'], timezone_identifiers_list(), true)) {
            config(['app.timezone' => $all['timezone']]);
            date_default_timezone_set($all['timezone']);
        }
        if (! empty($all['locale'])) {
            config(['app.locale' => $all['locale']]);
        }

        // Durée de session (en minutes)
        if (! empty($all['session_timeout']) && (int) $all['session_timeout'] > 0) {
            config(['session.lifetime' => (int) $all['session_timeout']]);
        }

        // Certificats: config gratuite pour contrôleurs
        config(['lms.certificate_prefix' => $all['certificate_prefix'] ?? 'SC']);
        config(['lms.min_pass_score'     => (float) ($all['min_pass_score'] ?? 60)]);
        config(['lms.enable_certificates' => (bool) ($all['enable_certificates'] ?? '1')]);
        config(['lms.auto_enroll_on_payment' => (bool) ($all['auto_enroll_on_payment'] ?? '0')]);
        config(['lms.allow_course_preview' => (bool) ($all['allow_course_preview'] ?? '1')]);
        config(['lms.video_watermark' => (bool) ($all['video_watermark'] ?? '0')]);

        // Maintenance: expose flag for middleware/views
        config(['settings.maintenance_mode' => (bool) ($all['maintenance_mode'] ?? '0')]);
        config(['settings.enable_registration' => (bool) ($all['enable_registration'] ?? '1')]);
        config(['settings.force_2fa' => (bool) ($all['force_2fa'] ?? '0')]);
        config(['settings.ip_whitelist' => $all['ip_whitelist'] ?? '']);

        // Branding general
        config([
            'settings.branding' => [
                'primary_color'    => $all['primary_color']    ?? '#E1251B',
                'secondary_color'  => $all['secondary_color']  ?? '#2563EB',
                'site_name'        => $all['general_site_name'] ?? 'SubnetCongo',
                'site_tagline'     => $all['general_site_slogan'] ?? $all['site_tagline'] ?? '',
                'site_description' => $all['general_site_description'] ?? $all['site_description'] ?? '',
                'site_logo'        => $all['site_logo']        ?? '',
                'site_favicon'     => $all['site_favicon']     ?? '/favicon.ico',
            ],
        ]);
    }

    private static function toString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string) $value;
    }

    private static function typeOf(mixed $value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_array($value) || is_object($value)) return 'json';
        return 'text';
    }
}
