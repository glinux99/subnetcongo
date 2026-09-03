<script setup>
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

// Props avec valeurs par défaut sécurisées
const props = defineProps({
    notifications: { type: Object, default: () => ({ data: [] }) },
    unreadCount: { type: Number, default: 0 }
});

const toast = useToast();
const filter = ref('all');
const isMarkingAll = ref(false);

// Filtre calculé sécurisé
const filtered = computed(() => {
    const data = props.notifications?.data || [];
    if (filter.value === 'unread') return data.filter(n => !n.read_at);
    if (filter.value === 'read') return data.filter(n => n.read_at);
    return data;
});

// Formate la date en français lisible
const formatDate = (iso) => {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (_) { return iso; }
};

// Utilitaires de présentation
const severityLabel = (s) => ({ success: 'Succès', warn: 'Attention', error: 'Erreur', info: 'Info' }[s] || 'Info');

const getIconStyles = (severity) => {
    const styles = {
        success: 'bg-emerald-100 text-emerald-600',
        warn: 'bg-amber-100 text-amber-600',
        error: 'bg-rose-100 text-rose-600',
        info: 'bg-indigo-100 text-indigo-600'
    };
    return styles[severity] || styles.info;
};

// Actions (Utilisation de Inertia Router pour éviter les rechargements manuels et gérer le CSRF)
const markAsRead = (n) => {
    if (!n.read_at) {
        router.post(route('notifications.read', n.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (n.url) router.visit(n.url);
            }
        });
    } else if (n.url) {
        router.visit(n.url);
    }
};

const markAll = () => {
    isMarkingAll.value = true;
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Fait', detail: 'Toutes les notifications sont lues.', life: 3000 });
        },
        onFinish: () => { isMarkingAll.value = false; }
    });
};

const remove = (id) => {
    router.delete(route('notifications.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Supprimée', detail: 'Notification effacée.', life: 2000 });
        }
    });
};
</script>

<template>
    <Head title="Notifications" />

    <AppLayout>
        <div class="min-h-screen bg-slate-50/50 font-sans pb-12">
            <!-- HEADER (Premium Dark Mode with Glow) -->
            <div class="bg-[#0f172a] pt-10 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <!-- Abstract Glow Effects -->
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none transition-transform duration-1000 animate-pulse"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <!-- Subtle Texture -->
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay pointer-events-none"></div>

                <div class="max-w-[1000px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-[0_0_30px_rgba(99,102,241,0.3)] border border-white/10 shrink-0">
                            <i class="pi pi-bell text-white text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">Notifications</h1>
                                <Tag v-if="unreadCount > 0" :value="`${unreadCount} non lues`" class="!bg-rose-500/20 !text-rose-400 !border !border-rose-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">Restez informé de l'activité de votre compte.</p>
                        </div>
                    </div>

                    <Button
                        v-if="unreadCount > 0"
                        label="Tout marquer comme lu"
                        icon="pi pi-check-circle"
                        :loading="isMarkingAll"
                        @click="markAll"
                        class="!bg-white/10 hover:!bg-white/20 !text-white !border-white/10 !backdrop-blur-md !rounded-xl !font-bold !text-xs !py-3 !px-5 transition-all shadow-lg"
                    />
                </div>
            </div>

            <!-- MAIN CONTENT (Overlapping Header) -->
            <div class="max-w-[1000px] mx-auto px-4 lg:px-6 -mt-12 relative z-20">
                <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-6 lg:p-10 min-h-[500px]">

                    <!-- Segmented Control Filter -->
                    <div class="flex gap-1 mb-8 bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 w-full sm:w-fit mx-auto sm:mx-0 overflow-x-auto hide-scrollbar">
                        <button
                            v-for="f in [{ id: 'all', label: 'Toutes' }, { id: 'unread', label: 'Non lues' }, { id: 'read', label: 'Lues' }]"
                            :key="f.id"
                            @click="filter = f.id"
                            class="relative flex-1 sm:flex-none px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all duration-300 z-10"
                            :class="filter === f.id ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                        >
                            <span class="relative z-10">{{ f.label }}</span>
                            <div v-if="filter === f.id" class="absolute inset-0 bg-white rounded-lg shadow-sm z-0"></div>
                        </button>
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-if="filtered.length === 0" class="flex flex-col items-center justify-center py-20 text-center animate-fade-in">
                        <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-sm">
                            <i class="pi pi-check-circle text-4xl text-emerald-400"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 mb-2">Vous êtes à jour !</h3>
                        <p class="text-slate-500 max-w-sm">Aucune notification à afficher pour ce filtre.</p>
                    </div>

                    <!-- NOTIFICATIONS LIST (Animated) -->
                    <div v-else class="space-y-3">
                        <TransitionGroup name="list">
                            <div
                                v-for="n in filtered"
                                :key="n.id"
                                @click="markAsRead(n)"
                                class="group bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex items-start gap-4 cursor-pointer relative overflow-hidden"
                                :class="{ '!bg-indigo-50/40 !border-indigo-100': !n.read_at }"
                            >
                                <!-- Indicateur Non-Lu (Ligne gauche) -->
                                <div v-if="!n.read_at" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>

                                <!-- Icône de sévérité -->
                                <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm border border-white', getIconStyles(n.severity)]">
                                    <i :class="`pi ${n.icon || 'pi-bell'} text-xl`"></i>
                                </div>

                                <div class="flex-1 min-w-0 pr-8">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-bold text-slate-900 text-sm tracking-tight" :class="{'text-indigo-900': !n.read_at}">{{ n.title }}</p>
                                            <Tag :value="severityLabel(n.severity)" :severity="n.severity === 'warn' ? 'warning' : n.severity" class="!text-[9px] !font-black !uppercase !tracking-widest hidden sm:inline-flex" />
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest shrink-0 flex items-center gap-1.5" :title="formatDate(n.created_at)">
                                            <i class="pi pi-clock text-slate-300"></i> {{ n.time_ago || formatDate(n.created_at) }}
                                        </p>
                                    </div>
                                    <p v-if="n.message" class="text-sm text-slate-500 leading-relaxed">{{ n.message }}</p>
                                    <p v-else class="text-sm text-slate-400 italic leading-relaxed">Aucun contenu textuel — voir les détails.</p>
                                </div>

                                <!-- Action Supprimer (Apparaît au hover) -->
                                <button
                                    @click.stop="remove(n.id)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-rose-100 hover:text-rose-500 transition-all focus:opacity-100"
                                    title="Supprimer la notification"
                                >
                                    <i class="pi pi-trash text-sm"></i>
                                </button>
                            </div>
                        </TransitionGroup>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animations pour la liste */
.list-enter-active,
.list-leave-active {
    transition: all 0.4s ease;
}
.list-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}
.list-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

/* Fondu classique */
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Cacher la scrollbar sur les filtres mobiles */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
