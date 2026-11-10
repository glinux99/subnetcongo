<?php

namespace App\Services\Video;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Centralise la configuration des fournisseurs de visioconférence.
 *
 * Les valeurs sont stockées dans la table `settings` (groupe "video") et
 * prennent le dessus sur le fichier .env, ce qui permet de tout configurer
 * depuis l'interface d'administration sans redéploiement.
 */
class VideoProviderConfig
{
    public const GROUP = 'video';
    public const CACHE_KEY = 'video_provider_settings';

    public const DEFAULTS = [
        'default_provider' => 'livekit',

        'livekit_url' => null,
        'livekit_ws_url' => null,
        'livekit_api_key' => null,
        'livekit_api_secret' => null,

        'zoom_account_id' => null,
        'zoom_client_id' => null,
        'zoom_client_secret' => null,

        'google_client_id' => null,
        'google_client_secret' => null,
        'google_refresh_token' => null,
        'google_calendar_id' => 'primary',

        'bbb_base_url' => null,
        'bbb_secret' => null,

        'jitsi_base_url' => 'https://meet.jit.si',
    ];

    /**
     * Toutes les valeurs effectives (BDD > .env > défauts).
     */
    public static function all(): array
    {
        $stored = self::stored();

        return array_merge(self::DEFAULTS, array_filter(
            $stored,
            fn ($value) => $value !== null && $value !== ''
        ));
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Injecte les valeurs en base dans la configuration Laravel.
     * Appelé au boot : les services existants continuent d'utiliser config().
     */
    public static function apply(): void
    {
        $values = self::all();

        $map = [
            'services.livekit.url' => $values['livekit_url'],
            'services.livekit.ws_url' => $values['livekit_ws_url'],
            'services.livekit.api_key' => $values['livekit_api_key'],
            'services.livekit.api_secret' => $values['livekit_api_secret'],

            'services.zoom.account_id' => $values['zoom_account_id'],
            'services.zoom.client_id' => $values['zoom_client_id'],
            'services.zoom.client_secret' => $values['zoom_client_secret'],

            'services.google.client_id' => $values['google_client_id'],
            'services.google.client_secret' => $values['google_client_secret'],
            'services.google.refresh_token' => $values['google_refresh_token'],
            'services.google.calendar_id' => $values['google_calendar_id'],

            'services.bbb.base_url' => $values['bbb_base_url'],
            'services.bbb.secret' => $values['bbb_secret'],

            'services.jitsi.base_url' => $values['jitsi_base_url'],
        ];

        foreach ($map as $configKey => $value) {
            if ($value !== null && $value !== '') {
                config([$configKey => $value]);
            }
        }
    }

    public static function isConfigured(string $provider): bool
    {
        $v = self::all();

        return match ($provider) {
            'livekit' => filled($v['livekit_api_key'])
                && filled($v['livekit_api_secret'])
                && filled($v['livekit_url']),
            'zoom' => filled($v['zoom_account_id'])
                && filled($v['zoom_client_id'])
                && filled($v['zoom_client_secret']),
            'meet' => filled($v['google_client_id'])
                && filled($v['google_client_secret'])
                && (filled($v['google_refresh_token']) || filled(config('services.google.credentials_path'))),
            'bbb' => filled($v['bbb_base_url']) && filled($v['bbb_secret']),
            'jitsi' => filled($v['jitsi_base_url']),
            default => false,
        };
    }

    /**
     * Liste des fournisseurs effectivement prêts à l'emploi.
     *
     * @return array<int,string>
     */
    public static function configuredProviders(): array
    {
        return array_values(array_filter(
            ['livekit', 'zoom', 'meet', 'bbb', 'jitsi'],
            fn ($provider) => self::isConfigured($provider)
        ));
    }

    public static function defaultProvider(): string
    {
        $default = self::get('default_provider', 'livekit');

        return self::isConfigured($default) ? $default : (self::configuredProviders()[0] ?? 'livekit');
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            Setting::updateOrCreate(
                ['group' => self::GROUP, 'key' => $key],
                ['value' => $value, 'type' => 'string']
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function stored(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return (array) Cache::remember(self::CACHE_KEY, 300, function () {
                return Setting::query()
                    ->where('group', self::GROUP)
                    ->pluck('value', 'key')
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }
}
