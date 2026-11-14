import { computed, reactive, readonly } from 'vue';

const storage = (key, fallback) => {
    try {
        return typeof window !== 'undefined' ? (localStorage.getItem(key) ?? fallback) : fallback;
    } catch (_) {
        return fallback;
    }
};
const persist = (key, value) => {
    try {
        if (typeof window !== 'undefined') localStorage.setItem(key, String(value));
    } catch (_) { /* stockage indisponible */ }
};

const layoutConfig = reactive({
    preset: storage('snc-preset', 'Aura'),
    primary: storage('snc-primary', 'blue'),
    surface: storage('snc-surface', 'slate'),
    darkTheme: storage('snc-dark-mode', '0') === '1',
    menuMode: storage('snc-menu-mode', 'static'),
});

const storedActiveMenuItem = (() => {
    try { return typeof window !== 'undefined' ? sessionStorage.getItem('snc-active-menu') : null; } catch (_) { return null; }
})();

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: storedActiveMenuItem || null
});

export function useLayout() {
    const setPrimary = (value) => {
        layoutConfig.primary = value;
        persist('snc-primary', value);
    };

    const setSurface = (value) => {
        layoutConfig.surface = value;
        persist('snc-surface', value);
    };

    const setPreset = (value) => {
        layoutConfig.preset = value;
        persist('snc-preset', value);
    };

    const setActiveMenuItem = (item) => {
        const value = (item && item.value !== undefined) ? item.value : item;
        layoutState.activeMenuItem = value;
        try {
            if (value) {
                sessionStorage.setItem('snc-active-menu', value);
            } else {
                sessionStorage.removeItem('snc-active-menu');
            }
        } catch (_) { /* stockage indisponible — ignore */ }
    };

    const setMenuMode = (mode) => {
        layoutConfig.menuMode = mode;
        persist('snc-menu-mode', mode);
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle());
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle('app-dark', layoutConfig.darkTheme);
        persist('snc-dark-mode', layoutConfig.darkTheme ? '1' : '0');
    };

    const onMenuToggle = () => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const resetMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);

    const isDarkTheme = computed(() => layoutConfig.darkTheme);

    const getPrimary = computed(() => layoutConfig.primary);

    const getSurface = computed(() => layoutConfig.surface);

    return { layoutConfig: readonly(layoutConfig), layoutState: readonly(layoutState), onMenuToggle, isSidebarActive, isDarkTheme, getPrimary, getSurface, setActiveMenuItem, toggleDarkMode, setPrimary, setSurface, setPreset, resetMenu, setMenuMode };
}
