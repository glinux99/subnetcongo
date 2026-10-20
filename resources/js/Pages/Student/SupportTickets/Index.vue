<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
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
    open: 'info', new: 'info', pending: 'warn', in_progress: 'info',
    resolved: 'success', closed: 'secondary',
}[s] || 'secondary');

const priorityLabel = (p) => ({ low: 'Basse', medium: 'Moyenne', high: 'Haute', urgent: 'Urgente' }[p] || p);

// Adapté pour les severities natives de PrimeVue (danger, warning, info, success)
const prioritySeverity = (p) => ({ urgent: 'danger', high: 'warn', medium: 'info', low: 'success' }[p] || 'secondary');

const formatDate = (iso) => {
    if (!iso) return '';
    try { return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
    catch (_) { return iso; }
};
</script>

<template>
    <AppLayout>
        <Head title="Mes tickets support" />
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1600px] mx-auto">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Espace Étudiant', icon: 'pi pi-home' }, { label: 'Support & Assistance' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-indigo-900/50 text-indigo-400 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-indigo-950/30 mb-4 uppercase">
                                Help Desk
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                                <span class="text-white">TICKETS</span>
                                <span class="text-[#FF3B3B]">SUPPORT</span>
                            </h1>

                            <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                                Suivez vos demandes d'assistance, échangez avec l'équipe technique ou pédagogique et trouvez des solutions rapides à vos problèmes.
                            </p>
                        </div>

                        <!-- Partie Droite (Boutons) -->
                        <div class="flex items-center gap-3 shrink-0 mt-4 lg:mt-0 w-full lg:w-auto">
                            <Button 
                                label="Actualiser" 
                                icon="pi pi-sync" 
                                @click="apply"
                                class="!bg-transparent hover:!bg-white/10 !border !border-slate-600 !text-white w-full sm:w-auto text-sm font-medium transition-colors" 
                            />
                            <Link :href="route('student.support.create')" class="w-full sm:w-auto">
                                <Button 
                                    label="Nouveau ticket" 
                                    icon="pi pi-plus" 
                                    class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !text-white w-full text-sm font-bold shadow-lg shadow-red-900/50 transition-colors" 
                                />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES KPI (Chevauchement) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- KPI 1 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center">
                            <i class="pi pi-list text-indigo-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Demandes</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.total ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-sky-50 flex items-center justify-center">
                            <i class="pi pi-envelope-open text-sky-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tickets Ouverts</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.open ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-amber-50 flex items-center justify-center">
                            <i class="pi pi-clock text-amber-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">En attente de réponse</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.pending ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center">
                            <i class="pi pi-check-circle text-emerald-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tickets Résolus</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.resolved ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU TABLEAU (Filtres et Liste)                                             -->
                <!-- ============================================================================== -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm relative z-20 mt-6">
                    
                    <!-- Zone des filtres -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <span class="p-input-icon-left flex-1 min-w-[250px]">
                            <i class="pi pi-search text-slate-400"></i>
                            <InputText v-model="search" @keyup.enter="apply" placeholder="Rechercher (sujet, n° ticket...)" class="w-full !rounded-xl border-slate-200" />
                        </span>
                        
                        <Select v-model="status" :options="[
                            { label:'Tous statuts', value:'' },
                            { label:'Ouverts', value:'open' },
                            { label:'En attente', value:'pending' },
                            { label:'En cours', value:'in_progress' },
                            { label:'Résolus', value:'resolved' },
                            { label:'Fermés', value:'closed' },
                        ]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl border-slate-200 min-w-[150px]" />
                        
                        <Select v-model="category" :options="[{ label:'Toutes catégories', value:'' }, ...(categories?.map(c => ({ label:c.name, value:c.id })) || [])]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl border-slate-200 min-w-[180px]" />
                        
                        <Button label="Filtrer" icon="pi pi-filter" @click="apply" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold" />
                    </div>

                    <!-- Tableau natif re-stylisé façon "PrimeVue B2B" -->
                    <div v-if="tickets.data?.length" class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                                    <th class="py-4 px-4 whitespace-nowrap">N° ticket</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Sujet</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Catégorie</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Priorité</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Statut</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Mise à jour</th>
                                    <th class="py-4 px-4 text-right whitespace-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in tickets.data" :key="t.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-4 font-mono text-[11px] font-bold text-slate-500 whitespace-nowrap">#{{ t.ticket_number }}</td>
                                    
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-800 line-clamp-1 min-w-[200px]">{{ t.subject }}</div>
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag v-if="t.category" :value="t.category.name" :style="{ backgroundColor: t.category.color }" class="!text-white !text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                        <span v-else class="text-slate-400 text-xs font-medium">—</span>
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag :value="priorityLabel(t.priority)" :severity="prioritySeverity(t.priority)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag :value="statusLabel(t.status)" :severity="statusSeverity(t.status)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                    </td>
                                    
                                    <td class="py-4 px-4 text-xs font-medium text-slate-500 whitespace-nowrap">
                                        {{ formatDate(t.updated_at || t.created_at) }}
                                    </td>
                                    
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <Link :href="route('student.support.show', t.id)">
                                            <Button label="Ouvrir" icon="pi pi-arrow-right" size="small" class="!rounded-xl !text-[10px] !font-bold !uppercase !tracking-widest !py-2 !bg-indigo-50 !text-indigo-600 !border-none hover:!bg-indigo-100 transition-colors" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-20 text-slate-400 flex flex-col items-center border border-slate-100 rounded-xl bg-slate-50/50">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center border border-slate-200 mb-4 shadow-sm">
                            <i class="pi pi-inbox text-2xl text-slate-300"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-700 mb-1">Aucun ticket trouvé</h3>
                        <p class="text-sm text-slate-500 mb-6">Vous n'avez pas encore de demande d'assistance correspondant à ces critères.</p>
                        <Link :href="route('student.support.create')">
                            <Button label="Créer mon premier ticket" icon="pi pi-plus" class="!bg-[#FF3B3B] hover:!bg-red-600 !border-none !rounded-xl font-bold px-6 shadow-md shadow-red-500/20" />
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tickets.links?.length > 3" class="flex justify-center gap-1 mt-8 flex-wrap">
                        <Link v-for="(l, i) in tickets.links" :key="i" :href="l.url || '#'" v-html="l.label"
                            :class="[
                                'px-3 py-2 rounded-xl text-xs font-black transition-colors', 
                                l.active ? '!bg-indigo-600 !text-white shadow-md' : '!bg-slate-100 !text-slate-600 hover:!bg-slate-200', 
                                !l.url && 'opacity-50 cursor-not-allowed'
                            ]" 
                        />
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Overrides UI Forms PrimeVue */
:deep(.p-inputtext), :deep(.p-select) {
    border-radius: 0.75rem;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-select:not(.p-disabled).p-focus) {
    border-color: #FF3B3B;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
}
</style>