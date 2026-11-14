<script setup>
import { computed } from 'vue';
import { useLayout } from '@/sakai/layout/composables/layout';
import AppConfigurator from './AppConfigurator.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import NavLink from '@/Components/NavLink.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const { t, locale } = useI18n();
const page = usePage();
const user = page.props.auth?.user;

const languages = [
    { name: 'Français', code: 'FR', flag: 'https://flagcdn.com/w40/fr.png' },
    { name: 'English', code: 'EN', flag: 'https://flagcdn.com/w40/gb.png' },
    { name: 'Deutsch', code: 'DE', flag: 'https://flagcdn.com/w40/de.png' },
    { name: 'Kiswahili', code: 'SW', flag: 'https://flagcdn.com/w40/tz.png' },
    { name: 'Lingála', code: 'LN', flag: 'https://flagcdn.com/w40/cd.png' },
];

const currentLang = computed(() => languages.find((l) => l.code.toLowerCase() === locale.value) || languages[0]);

const changeLanguage = (lang) => {
    locale.value = lang.code.toLowerCase();
};

const openCommandPalette = () => {
    window.dispatchEvent(new CustomEvent('snc:open-command-palette'));
};
</script>

<template>
    <header class="layout-topbar" role="banner">
        <div class="flex h-full min-w-0 flex-1 items-center gap-1.5 sm:gap-4">
            <button
                type="button"
                class="group inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-colors hover:bg-blue-600 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-blue-600 dark:hover:text-white"
                :title="t('topbar.toggle_sidebar')"
                :aria-label="t('topbar.toggle_sidebar')"
                @click="onMenuToggle"
            >
                <span class="flex flex-col items-start gap-1.5" aria-hidden="true">
                    <span class="h-0.5 w-5 rounded-full bg-current transition-all"></span>
                    <span class="h-0.5 w-3 rounded-full bg-current opacity-80 transition-all group-hover:w-5"></span>
                    <span class="h-0.5 w-5 rounded-full bg-current transition-all"></span>
                </span>
            </button>

            <NavLink href="/dashboard" class="flex h-11 min-w-0 shrink items-center gap-2 rounded-xl px-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500" aria-label="SubnetCongo — Tableau de bord">
                <img src="/assets/media/logos/logo.png" alt="SubNetCongo" class="h-7 w-auto max-w-36 object-contain transition-opacity hover:opacity-80 sm:h-9" />
            </NavLink>
        </div>

        <div class="hidden min-w-0 flex-1 justify-center px-2 lg:flex lg:max-w-xl xl:px-8">
            <button
                type="button"
                class="group relative w-full text-left"
                :aria-label="t('topbar.search_placeholder')"
                @click="openCommandPalette"
            >
                <i class="pi pi-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition-colors group-hover:text-blue-600 dark:text-slate-500 dark:group-hover:text-blue-300" aria-hidden="true"></i>
                <span class="block w-full rounded-xl border-2 border-transparent bg-slate-100/70 py-2.5 pl-12 pr-20 text-sm font-medium text-slate-400 transition-all group-hover:border-blue-100 group-hover:bg-white dark:bg-slate-800/70 dark:text-slate-400 dark:group-hover:border-slate-700 dark:group-hover:bg-slate-800">
                    {{ t('topbar.search_placeholder') }}
                </span>
                <span class="pointer-events-none absolute right-3 top-1/2 flex -translate-y-1/2 gap-1 opacity-70 transition-opacity group-hover:opacity-100" aria-hidden="true">
                    <kbd class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[9px] font-black uppercase tracking-tighter text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">Ctrl</kbd>
                    <kbd class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[9px] font-black uppercase text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">K</kbd>
                </span>
            </button>
        </div>

        <div class="flex h-full shrink-0 items-center gap-0.5 sm:gap-2">
            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-amber-300"
                :aria-label="isDarkTheme ? t('topbar.themes.light') : t('topbar.themes.dark')"
                :title="isDarkTheme ? t('topbar.themes.light') : t('topbar.themes.dark')"
                @click="toggleDarkMode"
            >
                <i :class="['pi text-lg', isDarkTheme ? 'pi-sun text-amber-400' : 'pi-moon text-blue-500']" aria-hidden="true"></i>
            </button>

            <NotificationBell />

            <div class="relative hidden sm:block">
                <button
                    v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300"
                    :aria-label="t('topbar.themes.accent')"
                    :title="t('topbar.themes.accent')"
                >
                    <i class="pi pi-palette text-lg" aria-hidden="true"></i>
                </button>
                <AppConfigurator />
            </div>

            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-300 dark:hover:bg-slate-800 lg:hidden"
                :aria-label="t('topbar.search_placeholder')"
                :title="t('topbar.search_placeholder')"
                @click="openCommandPalette"
            >
                <i class="pi pi-search text-lg" aria-hidden="true"></i>
            </button>

            <span class="mx-1 hidden h-6 w-px bg-slate-200 dark:bg-slate-800 sm:block" aria-hidden="true"></span>

            <div class="relative hidden sm:block">
                <button
                    v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                    type="button"
                    class="flex h-11 items-center gap-2 rounded-xl px-2 transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:bg-slate-800"
                    :aria-label="currentLang.name"
                >
                    <img :src="currentLang.flag" class="h-4 w-6 rounded object-cover shadow-sm" :alt="currentLang.name" />
                    <span class="text-[11px] font-black uppercase text-slate-600 dark:text-slate-300">{{ currentLang.code }}</span>
                </button>
                <div class="absolute right-0 z-[120] mt-4 hidden w-48 overflow-hidden rounded-xl border border-slate-100 bg-white p-1.5 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    <button
                        v-for="l in languages"
                        :key="l.code"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl p-2.5 text-left transition-colors hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:bg-slate-800"
                        @click="changeLanguage(l)"
                    >
                        <img :src="l.flag" class="h-4 w-6 rounded object-cover" :alt="l.name" />
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ l.name }}</span>
                    </button>
                </div>
            </div>

            <div class="relative">
                <button
                    v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }"
                    type="button"
                    class="flex h-11 items-center gap-2 rounded-xl p-1.5 transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:bg-slate-800 sm:gap-3 sm:px-2"
                    :aria-label="user?.name || t('topbar.userMenu.edit_profile')"
                >
                    <span class="relative inline-flex">
                        <img
                            :src="user?.avatar || user?.profile_photo_url || '/assets/media/avatars/blank.png'"
                            :alt="user?.name || ''"
                            class="h-9 w-9 rounded-xl border border-slate-100 object-cover shadow-sm dark:border-slate-800 sm:h-10 sm:w-10"
                        />
                        <span class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-950" aria-hidden="true"></span>
                    </span>
                    <span class="hidden min-w-0 flex-col text-left xl:flex">
                        <span class="max-w-44 truncate text-[11px] font-black uppercase leading-none text-slate-800 dark:text-slate-100">{{ user?.name }}</span>
                        <span class="mt-1 text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ t('topbar.status.connected') }}</span>
                    </span>
                    <i class="pi pi-chevron-down hidden text-[10px] text-slate-400 dark:text-slate-500 sm:inline" aria-hidden="true"></i>
                </button>

                <div class="absolute right-0 z-[130] mt-4 hidden w-64 max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl border border-slate-100 bg-white text-left shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 text-center dark:border-slate-800 dark:from-slate-900 dark:to-slate-950">
                        <img
                            :src="user?.avatar || user?.profile_photo_url || '/assets/media/avatars/blank.png'"
                            :alt="user?.name || ''"
                            class="mx-auto mb-3 h-16 w-16 rounded-xl border-2 border-white object-cover shadow-xl dark:border-slate-800"
                        />
                        <h3 class="text-sm font-black uppercase tracking-tight text-slate-800 dark:text-slate-100">{{ user?.name }}</h3>
                        <p class="mt-1 truncate text-[10px] font-bold lowercase text-slate-500 dark:text-slate-400">{{ user?.email }}</p>
                    </div>

                    <div class="space-y-1 p-2.5">
                        <NavLink href="/settings" class="group flex items-center gap-3 rounded-xl p-3 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-blue-50 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="pi pi-user-edit text-blue-400 transition-colors group-hover:text-blue-600 dark:text-blue-300 dark:group-hover:text-blue-200" aria-hidden="true"></i>
                            {{ t('topbar.userMenu.edit_profile') }}
                        </NavLink>
                        <NavLink href="/settings" class="group flex items-center gap-3 rounded-xl p-3 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-blue-50 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="pi pi-shield text-slate-400 transition-colors group-hover:text-blue-600 dark:text-slate-500 dark:group-hover:text-blue-300" aria-hidden="true"></i>
                            {{ t('topbar.userMenu.security') }}
                        </NavLink>
                        <div class="mx-3 my-2 border-t border-slate-100 dark:border-slate-800"></div>
                        <DropdownLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-3 rounded-xl border-none bg-transparent p-3 text-[10px] font-black uppercase tracking-wider text-rose-600 transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                        >
                            <i class="pi pi-power-off" aria-hidden="true"></i>
                            {{ t('topbar.userMenu.logout') }}
                        </DropdownLink>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
