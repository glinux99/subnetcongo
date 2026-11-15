import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Version applicative globale (APP_VERSION / APP_CHANNEL via .env).
 * Retourne { version, channel, label } où label = "v1.0.0-stable".
 */
export function useAppVersion() {
    const meta = computed(() => usePage().props.app_meta || {});
    const version = computed(() => meta.value.version || '1.0.0');
    const channel = computed(() => meta.value.channel || 'stable');
    const label = computed(() => `v${version.value}-${channel.value}`);

    return { version, channel, label };
}
