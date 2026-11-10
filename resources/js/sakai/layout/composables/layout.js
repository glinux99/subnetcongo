import { computed, reactive, readonly } from 'vue';

const layoutConfig = reactive({
    preset: 'Aura',
    primary: 'emerald',
    surface: null,
    darkTheme: typeof window !== 'undefined' && localStorage.getItem('snc-dark-mode') === '1',
    menuMode: 'static'
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
    };

    const setSurface = (value) => {
        layoutConfig.surface = value;
    };

    const setPreset = (value) => {
        layoutConfig.preset = value;
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
        localStorage.setItem('snc-dark-mode', layoutConfig.darkTheme ? '1' : '0');
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
