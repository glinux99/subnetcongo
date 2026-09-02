<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
});

const toast = useToast();
const filter = ref('all');

const filtered = computed(() => {
    if (!props.notifications?.data) return [];
    if (filter.value === 'unread') return props.notifications.data.filter(n => !n.read_at);
    if (filter.value === 'read') return props.notifications.data.filter(n => n.read_at);
    return props.notifications.data;
});

const severityLabel = (s) => ({
    success: 'Succès', warn: 'Attention', error: 'Erreur', info: 'Info',
}[s] || 'Info');

const markAsRead = async (n) => {
    if (!n.read_at) {
        await fetch(route('notifications.read', n.id), {
            method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        });
    }
    if (n.url) router.visit(n.url);
};

const markAll = async () => {
    await fetch(route('notifications.read-all'), {
        method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    });
    router.reload();
    toast.add({ severity: 'success', summary: 'Toutes les notifications marquées comme lues', life: 2500 });
};

const remove = async (id) => {
    await fetch(route('notifications.destroy', id), {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    });
    router.reload();
};
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-[#F8FAFC] py-10 px-6 font-sans">
            <div class="max-w-[900px] mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <p class="text-[10px] font-black text-violet-500 uppercase tracking-widest">Centre de notifications</p>
                        <h1 class="text-3xl font-black text-slate-800 italic tracking-tight mt-1">Notifications</h1>
                        <p class="text-sm text-slate-400 mt-2">
                            {{ unreadCount ?? 0 }} non-lue(s)
                        </p>
                    </div>
                    <Button v-if="unreadCount > 0" label="Tout marquer comme lu" icon="pi pi-check"
                            class="p-button-primary font-black" @click="markAll" />
                </div>

                <div class="flex gap-2 mb-5 bg-white p-1 rounded-xl border border-slate-100 shadow-sm w-fit">
                    <button v-for="f in [
                        { id: 'all', label: 'Toutes' },
                        { id: 'unread', label: 'Non lues' },
                        { id: 'read', label: 'Lues' }
                    ]" :key="f.id"
                            @click="filter = f.id"
                            :class="[
                                'px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-colors',
                                filter === f.id ? 'bg-violet-600 text-white' : 'text-slate-500 hover:bg-slate-50'
                            ]">
                        {{ f.label }}
                    </button>
                </div>

                <div v-if="filtered.length === 0" class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-16 text-center">
                    <i class="pi pi-inbox text-5xl text-slate-300 mb-4 block"></i>
                    <h3 class="text-xl font-black text-slate-700 italic mb-2">Aucune notification</h3>
                </div>

                <div v-else class="space-y-2">
                    <div v-for="n in filtered" :key="n.id"
                         :class="[
                            'bg-white p-4 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-all flex items-start gap-4 cursor-pointer',
                            !n.read_at && 'border-violet-200 bg-violet-50/30'
                         ]"
                         @click="markAsRead(n)">
                        <div :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0',
                            n.severity === 'success' ? 'bg-emerald-100 text-emerald-600' :
                            n.severity === 'warn' ? 'bg-amber-100 text-amber-600' :
                            n.severity === 'error' ? 'bg-rose-100 text-rose-600' :
                            'bg-violet-100 text-violet-600'
                        ]">
                            <i :class="`pi ${n.icon} text-lg`"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-black text-slate-800 text-sm">{{ n.title }}</p>
                                <div class="flex items-center gap-2">
                                    <Tag :value="severityLabel(n.severity)" :severity="n.severity === 'warn' ? 'warning' : n.severity" class="text-[9px]" />
                                    <button @click.stop="remove(n.id)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                        <i class="pi pi-times text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">{{ n.message }}</p>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-2">
                                <i class="pi pi-clock text-violet-400"></i> {{ n.created_at }}
                                <span v-if="!n.read_at" class="ml-2 inline-block px-2 py-0.5 bg-violet-600 text-white rounded-full">NOUVEAU</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>