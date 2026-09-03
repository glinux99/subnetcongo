<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { ref } from 'vue';

const props = defineProps({
    messages: { type: Object, default: () => ({ data: [] }) },
    stats:    { type: Object, default: () => ({}) },
    filters:  { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const apply = () => router.get(route('admin.messages.index'), {
    search: search.value || undefined,
    status: status.value || undefined,
}, { preserveState: true, replace: true });

const statusLabel = (s) => ({ new:'Nouveau', read:'Lu', replied:'Répondu', archived:'Archivé', spam:'Spam' }[s] || s);
const statusColor = (s) => ({ new:'rose', read:'amber', replied:'emerald', archived:'secondary', spam:'secondary' }[s] || 'secondary');
const formatDate = (iso) => { if(!iso) return ''; try { return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); } catch(_) { return iso; } };
</script>

<template>
    <Head title="Messages du site web" />
    <AppLayout>
        <div class="min-h-screen bg-slate-50 font-sans pb-12">
            <div class="bg-[#0f172a] pt-10 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-rose-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[1500px] mx-auto relative z-10">
                    <h1 class="text-3xl font-black text-white tracking-tight"><i class="pi pi-inbox text-rose-400 mr-3"></i>Messages du site web</h1>
                    <p class="text-slate-400 text-sm mt-1">Demandes envoyées via le formulaire de contact public.</p>
                </div>
            </div>

            <div class="max-w-[1500px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="s in [
                        { key:'total', label:'Total', color:'indigo' },
                        { key:'new', label:'Nouveaux', color:'rose' },
                        { key:'replied', label:'Répondus', color:'emerald' },
                        { key:'archived', label:'Archivés', color:'slate' },
                    ]" :key="s.key" class="bg-white rounded-2xl p-5 shadow border border-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ s.label }}</p>
                        <p :class="['text-3xl font-black mt-1', `text-${s.color}-600`]">{{ stats[s.key] ?? 0 }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
                        <InputText v-model="search" @keyup.enter="apply" placeholder="Rechercher (nom, email, sujet...)" class="!rounded-xl" />
                        <Select v-model="status" :options="[
                            { label:'Tous statuts', value:'' },
                            { label:'Nouveaux', value:'new' },
                            { label:'Lus', value:'read' },
                            { label:'Répondus', value:'replied' },
                            { label:'Archivés', value:'archived' },
                            { label:'Spam', value:'spam' },
                        ]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" @click="apply" class="!rounded-xl" />
                    </div>

                    <div v-if="messages.data?.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b">
                                    <th class="py-3 px-2">N° message</th>
                                    <th class="py-3 px-2">Nom / Email</th>
                                    <th class="py-3 px-2">Sujet</th>
                                    <th class="py-3 px-2">Statut</th>
                                    <th class="py-3 px-2">Reçu le</th>
                                    <th class="py-3 px-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in messages.data" :key="m.id" class="border-b hover:bg-slate-50 transition">
                                    <td class="py-3 px-2 font-mono text-xs text-slate-500">{{ m.ticket_number }}</td>
                                    <td class="py-3 px-2">
                                        <p class="font-bold text-slate-800">{{ m.name }}</p>
                                        <a :href="`mailto:${m.email}`" class="text-xs text-indigo-600 hover:underline">{{ m.email }}</a>
                                    </td>
                                    <td class="py-3 px-2 text-slate-700">{{ m.subject }}</td>
                                    <td class="py-3 px-2">
                                        <Tag :value="statusLabel(m.status)" :severity="statusColor(m.status)" class="!text-[10px] !font-black !uppercase" />
                                    </td>
                                    <td class="py-3 px-2 text-xs text-slate-500">{{ formatDate(m.created_at) }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <Link :href="route('admin.messages.show', m.id)">
                                            <Button label="Voir / Répondre" icon="pi pi-arrow-right" size="small" outlined class="!rounded-xl" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-16">
                        <i class="pi pi-inbox text-5xl text-slate-300 mb-3"></i>
                        <p class="text-slate-500 font-bold">Aucun message pour le moment.</p>
                    </div>

                    <div v-if="messages.links?.length > 3" class="flex justify-center gap-1 mt-6 flex-wrap">
                        <Link v-for="(l, i) in messages.links" :key="i" :href="l.url || '#'" v-html="l.label"
                            :class="['px-3 py-2 rounded-xl text-xs font-black', l.active ? '!bg-rose-600 !text-white' : '!bg-slate-100 !text-slate-600 hover:!bg-slate-200', !l.url && 'opacity-50']" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
