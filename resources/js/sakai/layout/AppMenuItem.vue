<script setup>
import { useLayout } from '@/sakai/layout/composables/layout';
import { onBeforeMount, ref, watch, computed } from 'vue';
import NavLink from "@/Components/NavLink.vue";
import { usePage } from '@inertiajs/vue3';

import { useI18n } from 'vue-i18n';
const { layoutState, setActiveMenuItem, onMenuToggle } = useLayout();
const page = usePage();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});

const userPermissions = computed(() => page.props.auth.can || {});

const hasPermission = (permission) => {
    if (!permission) return true; // Toujours visible si aucune permission n'est requise

    const permissions = userPermissions.value;

    // Handle object-based permissions (e.g., from Spatie)
    if (typeof permissions === 'object' && !Array.isArray(permissions)) {
        if (Array.isArray(permission)) {
            return permission.some(p => permissions[p]);
        }
        return permissions[permission] || false;
    }

    // Handle array-based permissions
    if (Array.isArray(permission)) {
        return permission.some(p => permissions.includes(p));
    }
    return permissions.includes(permission);
};

const isVisible = computed(() => {
    if (props.item.visible === false) return false;

    // Si l'élément a des enfants, on vérifie si au moins un enfant est visible
    if (props.item.items && props.item.items.length > 0) {
        return props.item.items.some(child => hasPermission(child.can));
    }
    return hasPermission(props.item.can);
});

const handleLinkClick = () => {
    if (layoutState.staticMenuMobileActive.value || layoutState.overlayMenuActive.value) {
        onMenuToggle();
    }
    setActiveMenuItem(itemKey.value);
};

const itemClick = (event, item) => { // Cette fonction ne gère plus que les sous-menus
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    // Si c'est un sous-menu, on bascule son état actif
    if (item.items) {
        const foundItemKey = isActiveMenu.value ? props.parentItemKey : itemKey.value;
        setActiveMenuItem(foundItemKey);
    } else if (layoutState.staticMenuMobileActive.value || layoutState.overlayMenuActive.value) {
        // Si c'est un lien simple sur mobile, on ferme le menu
        onMenuToggle();
    }
};
const isActiveMenu = ref(false);
const itemKey = ref(null);
const { t } = useI18n();

// Extrait le path pur (sans host, sans query, sans trailing slash)
const pathOnly = (url) => {
    if (!url) return '';
    try {
        // Si c'est une URL absolue (http://...) on prend seulement le pathname
        if (/^https?:\/\//i.test(url)) {
            return new URL(url).pathname.replace(/\/+$/, '') || '/';
        }
    } catch (_) { /* ignore */ }
    return String(url).split('?')[0].replace(/\/+$/, '') || '/';
};

const currentPath = computed(() => pathOnly(page.url));

// Détecte si l'item courant (ou un de ses descendants) est la route active
const isRouteActive = computed(() => {
    if (!props.item.to) return false;
    return pathOnly(props.item.to) === currentPath.value;
});

// Détecte récursivement si un sous-item correspond à la route active
const hasActiveDescendant = (item) => {
    if (!item || !item.items) return false;
    for (const child of item.items) {
        if (child.to && pathOnly(child.to) === currentPath.value) return true;
        if (hasActiveDescendant(child)) return true;
    }
    return false;
};

const shouldBeActive = computed(() => isRouteActive.value || hasActiveDescendant(props.item));

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? props.parentItemKey + '-' + props.index : String(props.index);

    // Si l'item (ou un enfant) correspond à la route active, on l'ouvre
    if (shouldBeActive.value) {
        isActiveMenu.value = true;
        // On enregistre aussi dans le state pour rester synchro
        setActiveMenuItem(itemKey.value);
    } else {
        const activeItem = layoutState.activeMenuItem;
        isActiveMenu.value = activeItem === itemKey.value || (activeItem ? activeItem.startsWith(itemKey.value + '-') : false);
    }
});

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || (newVal ? newVal.startsWith(itemKey.value + '-') : false);
    }
);

// Ouvre automatiquement le sous-menu quand la route change
watch(currentPath, () => {
    if (shouldBeActive.value) {
        isActiveMenu.value = true;
        setActiveMenuItem(itemKey.value);
    }
});
</script>

<template>
    <li v-if="isVisible"
        :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu, 'active-route': isRouteActive && !item.items, 'has-active-child': !isRouteActive && item.items && shouldBeActive }">
        <div v-if="root" class="layout-menuitem-root-text">{{ t(item.label) }}</div>

        <nav-link v-if="item.to && !item.items"
                  @click="handleLinkClick"
                  :href="item.to" :class="[item.class, { 'active-route': isRouteActive }]"
                  preserve-scroll>
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ t(item.label) }}</span>
            <span v-if="item.badge" class="layout-menuitem-badge ml-auto bg-violet-600 text-white text-[10px] font-black rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center">
                {{ item.badge > 99 ? '99+' : item.badge }}
            </span>
        </nav-link>

        <a v-else-if="!item.to || item.items"
           :href="item.url"
           @click="itemClick($event, item)"
           :class="[item.class, { 'active-route': !item.to && hasActiveDescendant(item) }]"
           :target="item.target"
           tabindex="0">
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ t(item.label) }}</span>
            <span v-if="item.badge" class="layout-menuitem-badge ml-auto bg-violet-600 text-white text-[10px] font-black rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center">
                {{ item.badge > 99 ? '99+' : item.badge }}
            </span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items" :class="{ 'rotate-180': isActiveMenu }"></i>
        </a>

        <Transition v-if="item.items" name="layout-submenu">
            <ul v-show="root ? true : isActiveMenu" class="layout-submenu">
                <app-menu-item v-for="(child, i) in item.items.filter(i => i.visible !== false)"
                               :key="child.label"
                               :index="i"
                               :item="child"
                               :parentItemKey="itemKey"
                               :root="false">
                </app-menu-item>
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped>
/* Item parent qui contient la page active */
.active-route {
    color: #8B5CF6 !important;
    font-weight: 700;
}
.has-active-child > a {
    color: #8B5CF6 !important;
    font-weight: 700;
}
.layout-submenu-toggler {
    transition: transform 0.2s ease;
}
</style>
