<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({ notifications: Object, unreadCount: Number });
const toast = useToast();
const filter = ref('all');
const filtered = computed(() => {
    if (!props.notifications?.data) return [];
    if (filter.value === 'unread') return props.notifications.data.filter(n => !n.read_at);
    if (filter.value === 'read') return props.notifications.data.filter(n => n.read_at);
    return props.notifications.data;
});
const severityLabel = (s) => ({ success: 'Succès', warn: 'Attention', error: 'Erreur', info: 'Info' }[s] || 'Info');
const markAsRead = async (n) => {
    if (!n.read_at) {
        await fetch(route('notifications.read', n.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
    }
    if (n.url) router.visit(n.url);
};
const markAll = async () => {
    await fetch(route('notifications.read-all'), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
    router.reload();
    toast.add({ severity: 'success', summary: 'Toutes les notifications marquées comme lues', life: 2500 });
};
const remove = async (id) => {
    await fetch(route('notifications.destroy', id), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
    router.reload();
};
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-slate-50 font-sans pb-10">
            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-rose-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[900px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                            <i class="pi pi-bell text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">Notifications</h1>
                                <Tag :value="`${unreadCount ?? 0} non lues`" class="!bg-rose-500/20 !text-rose-300 !border !border-rose-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">Centre de notifications temps réel.</p>
                        </div>
                    </div>
                    <Button v-if="unreadCount > 0" label="Tout marquer comme lu" icon="pi pi-check" @click="markAll" class="!bg-indigo-500 hover:!bg-indigo-400 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 !px-5" />
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div class="flex gap-2 mb-6 bg-slate-50 p-1 rounded-xl border border-slate-100 w-fit">
                        <button v-for="f in [{ id: 'all', label: 'Toutes' }, { id: 'unread', label: 'Non lues' }, { id: 'read', label: 'Lues' }]" :key="f.id" @click="filter = f.id" :class="['px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-colors', filter === f.id ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100']">
                            {{ f.label }}
                        </button>
                    </div>

                    <div v-if="filtered.length === 0" class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-inbox text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-700">Aucune notification</h3>
                    </div>

                    <div v-else class="space-y-2">
                        <div v-for="n in filtered" :key="n.id" :class="['bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all flex items-start gap-4 cursor-pointer', !n.read_at && '!border-indigo-200 !bg-indigo-50/30']" @click="markAsRead(n)">
                            <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shrink-0', n.severity === 'success' ? 'bg-emerald-100 text-emerald-600' : n.severity === 'warn' ? 'bg-amber-100 text-amber-600' : n.severity === 'error' ? 'bg-rose-100 text-rose-600' : 'bg-indigo-100 text-indigo-600']">
                                <i :class="`pi ${n.icon} text-lg`"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-black text-slate-800 text-sm tracking-tight">{{ n.title }}</p>
                                    <div class="flex items-center gap-2">
                                        <Tag :value="severityLabel(n.severity)" :severity="n.severity === 'warn' ? 'warning' : n.severity" class="!text-[9px] !font-black !uppercase !tracking-widest" />
                                        <button @click.stop="remove(n.id)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                            <i class="pi pi-times text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 font-medium">{{ n.message }}</p>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">
                                    <i class="pi pi-clock text-indigo-400"></i> {{ n.created_at }}
                                    <span v-if="!n.read_at" class="ml-2 inline-block px-2 py-0.5 bg-indigo-600 text-white text-[9px] rounded-full">NOUVEAU</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>