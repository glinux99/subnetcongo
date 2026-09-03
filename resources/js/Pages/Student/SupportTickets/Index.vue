<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { ref, computed } from 'vue';

const props = defineProps({
    tickets:    { type: Object, default: () => ({ data: [], links: [] }) },
    categories: { type: Array,  default: () => [] },
    stats:      { type: Object, default: () => ({}) },
    filters:    { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const category = ref(props.filters.category_id || '');

const apply = () => router.get(route('student.support.index'), {
    search: search.value || undefined,
    status: status.value || undefined,
    category_id: category.value || undefined,
}, { preserveState: true, replace: true });

const statusLabel = (s) => ({
    open: 'Ouvert', new: 'Nouveau', pending: 'En attente', in_progress: 'En cours',
    resolved: 'Résolu', closed: 'Fermé',
}[s] || s);
const statusSeverity = (s) => ({
    open: 'info', new: 'info', pending: 'warning', in_progress: 'info',
    resolved: 'success', closed: 'secondary',
}[s] || 'secondary');
const priorityLabel = (p) => ({ low: 'Basse', medium: 'Moyenne', high: 'Haute', urgent: 'Urgente' }[p] || p);
const priorityColor = (p) => ({ urgent: 'rose', high: 'orange', medium: 'amber', low: 'emerald' }[p] || 'slate');
const formatDate = (iso) => {
    if (!iso) return '';
    try { return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
    catch (_) { return iso; }
};
</script>

<template>
    <Head title="Mes tickets support" />
    <AppLayout>
        <div class="min-h-screen bg-slate-50 font-sans pb-12">
            <div class="bg-[#0f172a] pt-10 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px]"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 mix-blend-overlay"></div>
                <div class="max-w-[1500px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg border border-white/10">
                            <i class="pi pi-question-circle text-white text-3xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-white tracking-tight">Mes tickets support</h1>
                            <p class="text-slate-400 text-sm mt-1">Suivez vos demandes d'assistance et échangez avec l'équipe.</p>
                        </div>
                    </div>
                    <Link :href="route('student.support.create')">
                        <Button label="Nouveau ticket" icon="pi pi-plus" class="!bg-white/10 hover:!bg-white/20 !text-white !border-white/10 !backdrop-blur-md !rounded-xl !font-bold" />
                    </Link>
                </div>
            </div>

            <div class="max-w-[1500px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <!-- KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="s in [
                        { key:'total',    label:'Total',       color:'indigo' },
                        { key:'open',     label:'Ouverts',     color:'sky' },
                        { key:'pending',  label:'En attente',  color:'amber' },
                        { key:'resolved', label:'Résolus',     color:'emerald' },
                    ]" :key="s.key" class="bg-white rounded-2xl p-5 shadow border border-slate-100">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ s.label }}</p>
                        <p :class="['text-3xl font-black mt-1', `text-${s.color}-600`]">{{ stats[s.key] ?? 0 }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
                        <InputText v-model="search" @keyup.enter="apply" placeholder="Rechercher (sujet, n° ticket...)" class="!rounded-xl" />
                        <Select v-model="status" :options="[
                            { label:'Tous statuts', value:'' },
                            { label:'Ouverts', value:'open' },
                            { label:'En attente', value:'pending' },
                            { label:'En cours', value:'in_progress' },
                            { label:'Résolus', value:'resolved' },
                            { label:'Fermés', value:'closed' },
                        ]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl" />
                        <Select v-model="category" :options="[{ label:'Toutes catégories', value:'' }, ...(categories?.map(c => ({ label:c.name, value:c.id })) || [])]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl" />
                        <Button label="Filtrer" icon="pi pi-search" @click="apply" class="!rounded-xl" />
                    </div>

                    <div v-if="tickets.data?.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-500 border-b">
                                    <th class="py-3 px-2">N° ticket</th>
                                    <th class="py-3 px-2">Sujet</th>
                                    <th class="py-3 px-2">Catégorie</th>
                                    <th class="py-3 px-2">Priorité</th>
                                    <th class="py-3 px-2">Statut</th>
                                    <th class="py-3 px-2">Créé le</th>
                                    <th class="py-3 px-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in tickets.data" :key="t.id" class="border-b hover:bg-slate-50 transition">
                                    <td class="py-3 px-2 font-mono text-xs text-slate-500">{{ t.ticket_number }}</td>
                                    <td class="py-3 px-2 font-bold text-slate-800">{{ t.subject }}</td>
                                    <td class="py-3 px-2">
                                        <Tag v-if="t.category" :value="t.category.name" :style="{ backgroundColor: t.category.color }" class="!text-white !text-[10px] !font-black !uppercase" />
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                    <td class="py-3 px-2">
                                        <Tag :value="priorityLabel(t.priority)" :severity="priorityColor(t.priority)" class="!text-[10px] !font-black !uppercase" />
                                    </td>
                                    <td class="py-3 px-2">
                                        <Tag :value="statusLabel(t.status)" :severity="statusSeverity(t.status)" class="!text-[10px] !font-black !uppercase" />
                                    </td>
                                    <td class="py-3 px-2 text-xs text-slate-500">{{ formatDate(t.created_at) }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <Link :href="route('student.support.show', t.id)">
                                            <Button label="Voir" icon="pi pi-arrow-right" size="small" outlined class="!rounded-xl" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-16">
                        <i class="pi pi-inbox text-5xl text-slate-300 mb-3"></i>
                        <p class="text-slate-500 font-bold">Aucun ticket pour le moment.</p>
                        <Link :href="route('student.support.create')">
                            <Button label="Créer mon premier ticket" icon="pi pi-plus" class="!mt-4 !rounded-xl" />
                        </Link>
                    </div>

                    <div v-if="tickets.links?.length > 3" class="flex justify-center gap-1 mt-6 flex-wrap">
                        <Link v-for="(l, i) in tickets.links" :key="i" :href="l.url || '#'" v-html="l.label"
                            :class="['px-3 py-2 rounded-xl text-xs font-black', l.active ? '!bg-indigo-600 !text-white' : '!bg-slate-100 !text-slate-600 hover:!bg-slate-200', !l.url && 'opacity-50']" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
