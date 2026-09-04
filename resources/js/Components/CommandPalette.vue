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
        { label: 'Logs d\'audit', icon: 'pi pi-history', to: () => 'system.logs' },
        { label: 'Paramètres', icon: 'pi pi-cog', to: () => 'system.settings' },
        { label: 'Langues', icon: 'pi pi-globe', to: () => 'system.languages.index' },
    ]},
];

const results = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return items.slice(0, 2);
    return items.map(g => ({
        group: g.group,
        items: g.items.filter(i => i.label.toLowerCase().includes(q)),
    })).filter(g => g.items.length > 0);
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

const handleKey = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        visible.value = true;
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

onMounted(() => window.addEventListener('keydown', handleKey));
onBeforeUnmount(() => window.removeEventListener('keydown', handleKey));
</script>

<template>
    <Dialog v-model:visible="visible" modal :showHeader="false"
            :style="{ width: '640px', maxWidth: '95vw' }"
            :breakpoints="{ '960px': '90vw' }"
            :dismissableMask="true"
            contentClass="p-0 overflow-hidden rounded-xl">
        <div class="flex flex-col max-h-[70vh]">
            <div class="flex items-center gap-3 p-4 border-b border-slate-100 bg-slate-50">
                <i class="pi pi-search text-violet-500 text-lg"></i>
                <InputText v-model="query" ref="searchInput"
                           placeholder="Rechercher une page, un cours, un outil..."
                           class="flex-1 border-none shadow-none focus:ring-0 bg-transparent text-sm font-medium" />
                <kbd class="hidden md:inline-flex items-center px-2 py-1 bg-white border border-slate-200 rounded text-[10px] font-black text-slate-500 uppercase tracking-tighter shadow-sm">Esc</kbd>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <div v-if="results.length === 0" class="text-center py-12 text-slate-400">
                    <i class="pi pi-inbox text-4xl block mb-2"></i>
                    <p class="text-xs font-bold">Aucun résultat pour "{{ query }}"</p>
                </div>
                <div v-for="group in results" :key="group.group" class="mb-3">
                    <p class="px-3 py-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ group.group }}</p>
                    <button v-for="item in group.items" :key="item.label"
                            type="button"
                            @click="go(item)"
                            class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl hover:bg-violet-50 transition-colors text-left group">
                        <i :class="item.icon" class="text-violet-400 group-hover:text-violet-600 w-5 text-center"></i>
                        <span class="text-sm font-bold text-slate-700 group-hover:text-violet-700">{{ item.label }}</span>
                        <i class="pi pi-arrow-right text-slate-300 group-hover:text-violet-400 ml-auto text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="px-4 py-2 border-t border-slate-100 bg-slate-50 flex items-center gap-3 text-[10px] text-slate-500 font-bold">
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-black">↑↓</kbd> Naviguer</span>
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-black">↵</kbd> Ouvrir</span>
                <span class="ml-auto text-[9px] uppercase tracking-widest">{{ results.reduce((acc, g) => acc + g.items.length, 0) }} résultats</span>
            </div>
        </div>
    </Dialog>
</template>