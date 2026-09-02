<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

const page = usePage();
const toast = useToast();
const visible = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

const loadNotifications = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(route('notifications.list'));
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (e) {
        console.error('Erreur chargement notifications', e);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (n) => {
    if (!n.read_at) {
        try {
            await axios.post(route('notifications.read', n.id));
            n.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch (e) {}
    }
    visible.value = false;
    if (n.url) router.visit(n.url);
};

const markAllAsRead = async () => {
    try {
        await axios.post(route('notifications.read-all'));
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
        unreadCount.value = 0;
        toast.add({ severity: 'success', summary: 'Notifications marquées comme lues', life: 2500 });
    } catch (e) {}
};

const severityColor = (s) => {
    return { success: 'success', warn: 'warn', error: 'danger', info: 'info' }[s] || 'info';
};

const toggle = (e) => {
    visible.value = !visible.value;
    if (visible.value) loadNotifications();
};

// Polling fallback (toutes les 60s) si pas de WebSocket
let pollInterval;
onMounted(() => {
    loadNotifications();
    pollInterval = setInterval(loadNotifications, 60000);
});
onBeforeUnmount(() => clearInterval(pollInterval));
</script>

<template>
    <div class="relative">
        <button @click="toggle"
                class="layout-topbar-action relative p-2.5 rounded-xl hover:bg-gray-50 border-none bg-transparent cursor-pointer">
            <i class="pi pi-bell text-gray-600 text-lg"></i>
            <span v-if="unreadCount > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[9px] font-black rounded-full flex items-center justify-center shadow-md ring-2 ring-white animate-pulse">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <Transition name="dropdown">
            <div v-if="visible"
                 class="absolute right-0 mt-3 w-96 bg-white shadow-2xl rounded-2xl border border-slate-100 z-[120] overflow-hidden"
                 @click.stop>
                <!-- Header -->
                <div class="px-4 py-3 bg-gradient-to-br from-slate-50 to-white border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-black text-slate-800">Notifications</p>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest">
                            {{ unreadCount > 0 ? `${unreadCount} non-lue(s)` : 'Tout est à jour' }}
                        </p>
                    </div>
                    <button v-if="unreadCount > 0"
                            @click="markAllAsRead"
                            class="text-[10px] font-black text-violet-600 uppercase tracking-widest hover:underline">
                        Tout marquer
                    </button>
                </div>

                <!-- List -->
                <div class="max-h-[480px] overflow-y-auto">
                    <div v-if="loading && notifications.length === 0" class="p-12 text-center">
                        <i class="pi pi-spin pi-spinner text-2xl text-violet-400"></i>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-12 text-center text-slate-400">
                        <i class="pi pi-inbox text-4xl mb-2 block"></i>
                        <p class="text-xs font-bold">Aucune notification</p>
                    </div>

                    <button v-for="n in notifications" :key="n.id"
                            type="button"
                            @click="markAsRead(n)"
                            :class="[
                                'flex items-start gap-3 w-full p-3 hover:bg-slate-50 transition-colors text-left border-b border-slate-50 last:border-0',
                                !n.read_at && 'bg-violet-50/40'
                            ]">
                        <div :class="[
                            'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0',
                            n.severity === 'success' ? 'bg-emerald-100 text-emerald-600' :
                            n.severity === 'warn' ? 'bg-amber-100 text-amber-600' :
                            n.severity === 'error' ? 'bg-rose-100 text-rose-600' :
                            'bg-violet-100 text-violet-600'
                        ]">
                            <i :class="`pi ${n.icon} text-sm`"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <p class="text-xs font-black text-slate-800 line-clamp-1">{{ n.title }}</p>
                                <span v-if="!n.read_at" class="w-2 h-2 bg-violet-500 rounded-full flex-shrink-0 mt-1"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ n.message }}</p>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-1">{{ n.time_ago }}</p>
                        </div>
                    </button>
                </div>

                <!-- Footer -->
                <Link :href="route('notifications.index')"
                      class="block px-4 py-3 bg-slate-50 border-t border-slate-100 text-center text-[10px] font-black text-violet-600 uppercase tracking-widest hover:bg-violet-50 transition-colors">
                    Voir toutes les notifications →
                </Link>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active {
    transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
}
.dropdown-enter-from, .dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}
</style>