/**
 * Chargement des bibliothèques globales.
 */
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Pusher = Pusher;

/**
 * Echo — WebSocket Laravel.
 * Reverb est préféré si VITE_REVERB_APP_KEY est défini, sinon Pusher.
 * En l'absence de configuration, Echo n'est pas instancié : les pages doivent
 * se replier sur un chargement manuel plutôt que sur un rechargement automatique.
 */
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const scheme = import.meta.env.VITE_REVERB_SCHEME || import.meta.env.VITE_PUSHER_SCHEME || 'https';

try {
    if (reverbKey) {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } else if (pusherKey) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1'}.pusher.com`,
            wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }
} catch (error) {
    console.warn('Echo non initialisé :', error);
}
