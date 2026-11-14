<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';

const visible = ref(false);
const query = ref('');
const searchInput = ref(null);

const items = [
    { group: 'Apprentissage', items: [
        { label: 'Tableau de bord', icon: 'pi pi-home', to: () => 'dashboard' },
        { label: 'Mes formations', icon: 'pi pi-book', to: () => 'student.my-courses' },
        { label: 'Mes examens', icon: 'pi pi-pencil', to: () => 'exams.learner.index' },
        { label: 'Mes certificats', icon: 'pi pi-verified', to: () => 'student.certificates' },
        { label: 'Ma progression', icon: 'pi pi-chart-line', to: () => 'student.progress' },
    ]},
    { group: 'Contenu', items: [
        { label: 'Cours', icon: 'pi pi-list', to: () => 'courses.index' },
        { label: 'Modules', icon: 'pi pi-file', to: () => 'modules.index' },
        { label: 'Parcours', icon: 'pi pi-map', to: () => 'learning-paths.index' },
        { label: 'Médiathèque', icon: 'pi pi-images', to: () => 'media-library.index' },
        { label: 'Examens', icon: 'pi pi-pencil', to: () => 'exams.index' },
        { label: 'Certificats', icon: 'pi pi-verified', to: () => 'certificates.index' },
        { label: 'Classes virtuelles', icon: 'pi pi-video', to: () => 'live-sessions.index' },
    ]},
    { group: 'Administration', items: [
        { label: 'Apprenants', icon: 'pi pi-users', to: () => 'users.students.index' },
        { label: 'Instructeurs', icon: 'pi pi-user-tie', to: () => 'users.instructors.index' },
        { label: 'Cohortes', icon: 'pi pi-tags', to: () => 'users.cohorts.index' },
        { label: 'Forums', icon: 'pi pi-comments', to: () => 'social.forum.index' },
        { label: 'Tickets support', icon: 'pi pi-ticket', to: () => 'social.support.index' },
        { label: 'Campagnes email', icon: 'pi pi-send', to: () => 'marketing.emails.index' },
        { label: 'Coupons', icon: 'pi pi-percentage', to: () => 'marketing.coupons.index' },
        { label: 'Affiliés', icon: 'pi pi-share-alt', to: () => 'marketing.affiliates.index' },
        { label: 'Abonnements', icon: 'pi pi-credit-card', to: () => 'billing.subscriptions.index' },
    ]},
    { group: 'Système', items: [
        { label: 'Rôles', icon: 'pi pi-lock', to: () => 'system.roles.index' },
        { label: 'Permissions API', icon: 'pi pi-key', to: () => 'system.api.index' },
        { label: 'Logs d’audit', icon: 'pi pi-history', to: () => 'system.logs' },
        { label: 'Paramètres', icon: 'pi pi-cog', to: () => 'system.settings' },
        { label: 'Langues', icon: 'pi pi-globe', to: () => 'system.languages.index' },
    ]},
];

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return items.slice(0, 2);
    return items
        .map((g) => ({
            group: g.group,
            items: g.items.filter((i) => i.label.toLowerCase().includes(q)),
        }))
        .filter((g) => g.items.length > 0);
});

const go = (item) => {
    try {
        router.visit(route(item.to()));
        visible.value = false;
        query.value = '';
    } catch (e) {
        // route not defined
    }
};

const openPalette = () => { visible.value = true; };

const handleKey = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        visible.value = !visible.value;
    }
    if (e.key === 'Escape' && visible.value) {
        visible.value = false;
    }
};

watch(visible, (v) => {
    if (v) {
        setTimeout(() => searchInput.value?.$el?.focus(), 100);
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKey);
    window.addEventListener('snc:open-command-palette', openPalette);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKey);
    window.removeEventListener('snc:open-command-palette', openPalette);
});
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :showHeader="false"
        :style="{ width: 'min(640px, calc(100vw - 1rem))', maxWidth: '100vw' }"
        :breakpoints="{ '960px': '100vw' }"
        :dismissableMask="true"
        contentClass="p-0 overflow-hidden rounded-xl dark:bg-slate-900"
    >
        <div class="flex max-h-[80dvh] flex-col border border-slate-100 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 p-3 sm:p-4 dark:border-slate-800 dark:bg-slate-950/60">
                <i class="pi pi-search text-lg text-blue-500 dark:text-blue-400" aria-hidden="true"></i>
                <InputText
                    v-model="query"
                    ref="searchInput"
                    placeholder="Rechercher une page, un cours, un outil..."
                    class="min-w-0 flex-1 border-none bg-transparent text-sm font-medium shadow-none focus:ring-0"
                    inputClass="!bg-transparent !border-none !shadow-none"
                />
                <kbd class="hidden rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-black uppercase tracking-tighter text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 md:inline-flex">Esc</kbd>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <div v-if="results.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500">
                    <i class="pi pi-inbox mb-2 block text-4xl" aria-hidden="true"></i>
                    <p class="text-xs font-bold">Aucun résultat pour « {{ query }} »</p>
                </div>
                <div v-for="group in results" :key="group.group" class="mb-3">
                    <p class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ group.group }}</p>
                    <button
                        v-for="item in group.items"
                        :key="item.label"
                        type="button"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:bg-slate-800"
                        @click="go(item)"
                    >
                        <i :class="item.icon" class="w-5 text-center text-blue-400 transition-colors group-hover:text-blue-600 dark:text-blue-400 dark:group-hover:text-blue-300" aria-hidden="true"></i>
                        <span class="text-sm font-bold text-slate-700 transition-colors group-hover:text-blue-800 dark:text-slate-200 dark:group-hover:text-white">{{ item.label }}</span>
                        <i class="pi pi-arrow-right ml-auto text-xs text-slate-300 transition-colors group-hover:text-blue-400 dark:text-slate-600 dark:group-hover:text-blue-300" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-slate-100 bg-slate-50 px-4 py-2 text-[10px] font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-400">
                <span class="flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-black dark:border-slate-700 dark:bg-slate-800">↑↓</kbd> Naviguer</span>
                <span class="flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-black dark:border-slate-700 dark:bg-slate-800">↵</kbd> Ouvrir</span>
                <span class="ml-auto text-[9px] uppercase tracking-widest">{{ results.reduce((acc, g) => acc + g.items.length, 0) }} résultats</span>
            </div>
        </div>
    </Dialog>
</template>
