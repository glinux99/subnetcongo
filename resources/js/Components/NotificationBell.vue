<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const page = usePage();
const toast = useToast();
const visible = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
const realtime = ref(false);
let echoChannel = null;
let pollInterval = null;

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
        notifications.value.forEach((n) => { n.read_at = new Date().toISOString(); });
        unreadCount.value = 0;
        toast.add({ severity: 'success', summary: 'Notifications marquées comme lues', life: 2500 });
    } catch (e) {}
};

const severityColor = (s) => {
    return { success: 'success', warn: 'warn', error: 'danger', info: 'info' }[s] || 'info';
};

const toggle = () => {
    visible.value = !visible.value;
    if (visible.value) loadNotifications();
};

onMounted(() => {
    loadNotifications();

    const userId = page.props.auth?.user?.id;
    if (userId && window.Echo) {
        try {
            const channelName = `App.Models.User.${userId}`;
            echoChannel = window.Echo.private(channelName)
                .notification(() => loadNotifications())
                .error(() => { realtime.value = false; });
            echoChannel.subscribed(() => { realtime.value = true; });
        } catch (e) {
            realtime.value = false;
        }
    }

    pollInterval = window.setInterval(loadNotifications, window.Echo ? 300000 : 60000);
});

onBeforeUnmount(() => {
    if (pollInterval) window.clearInterval(pollInterval);
    const userId = page.props.auth?.user?.id;
    if (userId && window.Echo) {
        window.Echo.leave(`App.Models.User.${userId}`);
    }
});
</script>

<template>
    <div class="relative">
        <button
            type="button"
            @click="toggle"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300"
            :aria-label="`Notifications${unreadCount ? ` (${unreadCount} non lues)` : ''}`"
            :aria-expanded="visible"
        >
            <i class="pi pi-bell text-lg" aria-hidden="true"></i>
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-black text-white shadow-md ring-2 ring-white dark:ring-slate-950"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <Transition name="dropdown">
            <div
                v-if="visible"
                class="fixed left-2 right-2 top-[4.5rem] z-[120] w-auto overflow-hidden rounded-xl border border-slate-100 bg-white text-left shadow-2xl dark:border-slate-800 dark:bg-slate-900 sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-[min(22rem,calc(100vw-2rem))]"
                @click.stop
            >
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-4 py-3 dark:border-slate-800 dark:from-slate-900 dark:to-slate-950">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-slate-800 dark:text-slate-100">Notifications</p>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                            {{ unreadCount > 0 ? `${unreadCount} non-lue(s)` : 'Tout est à jour' }}
                        </p>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black uppercase tracking-widest text-blue-600 transition-colors hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-slate-800"
                        @click="markAllAsRead"
                    >
                        Tout marquer
                    </button>
                </div>

                <div class="max-h-[min(30rem,70dvh)] overflow-y-auto">
                    <div v-if="loading && notifications.length === 0" class="p-10 text-center">
                        <i class="pi pi-spin pi-spinner text-2xl text-blue-500" aria-hidden="true"></i>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-10 text-center text-slate-400 dark:text-slate-500">
                        <i class="pi pi-inbox mb-2 block text-4xl" aria-hidden="true"></i>
                        <p class="text-xs font-bold">Aucune notification</p>
                    </div>

                    <button
                        v-for="n in notifications"
                        :key="n.id"
                        type="button"
                        @click="markAsRead(n)"
                        :class="[
                            'flex w-full items-start gap-3 border-b border-slate-50 p-3 text-left transition-colors last:border-0 hover:bg-slate-50 dark:border-slate-800/70 dark:hover:bg-slate-800/60',
                            !n.read_at && 'bg-blue-50/40 dark:bg-blue-950/20',
                        ]"
                    >
                        <span
                            :class="[
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                n.severity === 'success' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' :
                                n.severity === 'warn' ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400' :
                                n.severity === 'error' ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' :
                                'bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
                            ]"
                            aria-hidden="true"
                        >
                            <i :class="`pi ${n.icon} text-sm`"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="line-clamp-1 text-xs font-black text-slate-800 dark:text-slate-100">{{ n.title }}</span>
                                <span v-if="!n.read_at" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500" aria-hidden="true"></span>
                            </span>
                            <span class="mt-0.5 line-clamp-2 block text-[11px] text-slate-500 dark:text-slate-400">{{ n.message }}</span>
                            <span class="mt-1 block text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ n.time_ago }}</span>
                        </span>
                    </button>
                </div>

                <Link
                    :href="route('notifications.index')"
                    class="block border-t border-slate-100 bg-slate-50 px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-blue-600 transition-colors hover:bg-blue-50 dark:border-slate-800 dark:bg-slate-950/60 dark:text-blue-300 dark:hover:bg-slate-800"
                    @click="visible = false"
                >
                    Voir toutes les notifications
                </Link>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s cubic-bezier(0.25, 1, 0.5, 1);
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.96);
}
</style>
