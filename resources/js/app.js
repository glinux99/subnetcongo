import './bootstrap';
import '../css/app.css';
import axios from 'axios';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import Aura from '@primevue/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';

import '@/sakai/assets/styles.scss';
import '@/sakai/assets/tailwind.css';
import { createI18n } from 'vue-i18n';
import { definePreset } from '@primevue/themes';

// 1. Importer les fichiers de traduction
import fr from './locales/fr.json';
import en from './locales/en.json';
import de from './locales/de.json';
import sw from './locales/sw.json';
import ln from './locales/ln.json';

const appName = import.meta.env.VITE_APP_NAME || 'Sakai';
const MyCustomPreset = definePreset (Aura, {
    semantic: {
        primary: {
            50: '#eff6ff',
            100: '#dbeafe',
            200: '#bfdbfe',
            300: '#93c5fd',
            400: '#60a5fa',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8',
            800: '#1e40af',
            900: '#1e3a8a',
            950: '#172554'
        },
        // Champs de formulaire (InputText, Textarea, Select, DatePicker, InputNumber…)
        // partagent ces tokens : une seule source de vérité pour toute l'app.
        formField: {
            paddingX: '0.9rem',
            paddingY: '0.65rem',
            borderRadius: '0.65rem'
        }
    },
    colorScheme: {
        light: {
            formField: {
                borderColor: '#E2E8F0',
                hoverBorderColor: '#CBD5E1',
                focusBorderColor: '#3B82F6',
                invalidBorderColor: '#F43F5E',
                placeholderColor: '#94A3B8'
            }
        },
        dark: {
            formField: {
                background: '#0F172A',
                filledBackground: '#0F172A',
                filledFocusBackground: '#1E293B',
                borderColor: 'rgba(51, 65, 85, 0.8)',
                hoverBorderColor: '#475569',
                focusBorderColor: '#60A5FA',
                invalidBorderColor: '#FB7185',
                color: '#F1F5F9',
                placeholderColor: '#64748B'
            }
        }
    }
});
axios.defaults.withCredentials = true; // <- Obligatoire
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios = axios;
// 2. Configurer i18n avec les messages importés
const i18n = createI18n({
    legacy: false, // Important pour le mode Composition API
    locale: 'fr', // Langue par défaut
    fallbackLocale: 'fr',
    messages: {
        fr, // Raccourci pour fr: fr
        en, // Raccourci pour en: en
        de, // Allemand
        sw,
        ln,
    },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                sematic:{

                },
                theme: {
                    preset: MyCustomPreset,
                    options: {
                        darkModeSelector: '.app-dark'
                    }
                }
            })
            .use(i18n)
            .use(ToastService)
            .use(ConfirmationService)
            .directive('tooltip', Tooltip)
            .mixin({
                methods: {
                    can: function (permissions) {
                        var allPermissions = this.$page.props.auth.can;
                        var hasPermission = false;
                        permissions.forEach(function (item) {
                            if (allPermissions[item]) hasPermission = true;
                        });
                        return hasPermission;
                    },
                },
            })
            .mount(el);
    },
    progress: {
        color: '#dc2626',
    },
});
