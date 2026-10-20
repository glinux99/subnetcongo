<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Breadcrumb from 'primevue/breadcrumb';
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

// Adapté pour les severities natives de PrimeVue
const statusSeverity = (s) => ({ 
    new: 'danger', 
    read: 'info', 
    replied: 'success', 
    archived: 'secondary', 
    spam: 'warning' 
}[s] || 'secondary');

const formatDate = (iso) => { 
    if(!iso) return ''; 
    try { 
        return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }); 
    } catch(_) { return iso; } 
};
</script>

<template>
    <Head title="Messages du site web" />
    <AppLayout>
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1600px] mx-auto">
                
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-2">
                    <Breadcrumb 
                        :model="[{ label: 'Administration', icon: 'pi pi-home' }, { label: 'Messages du site' }]" 
                        class="bg-transparent p-0 m-0 border-none text-xs text-slate-500 font-medium" 
                    />
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <span class="inline-block border border-red-900/50 text-red-400 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 mb-4 uppercase">
                                Contact & Leads
                            </span>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-2 leading-none">
                                <span class="text-white">MESSAGES</span>
                                <span class="text-[#EF4444]">PUBLICS</span>
                            </h1>

                            <p class="text-slate-400 text-xs md:text-sm italic leading-relaxed">
                                Gérez les demandes entrantes envoyées via le formulaire de contact de votre site vitrine. Répondez aux prospects et archivez les échanges.
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
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES KPI (Chevauchement) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- KPI 1 : Total -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center">
                            <i class="pi pi-inbox text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Reçus</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.total ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 2 : Nouveaux -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center">
                            <i class="pi pi-bell text-red-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nouveaux</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.new ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 3 : Répondus -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center">
                            <i class="pi pi-reply text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Répondus</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.replied ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- KPI 4 : Archivés -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-36">
                        <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center">
                            <i class="pi pi-folder text-slate-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Archivés</div>
                            <div class="text-2xl font-black text-slate-800 leading-none">{{ stats?.archived ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- ZONE DU TABLEAU (Filtres et Liste)                                             -->
                <!-- ============================================================================== -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative z-20 mt-6">
                    
                    <!-- Zone des filtres -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <span class="p-input-icon-left flex-1 min-w-[250px]">
                            <i class="pi pi-search text-slate-400"></i>
                            <InputText v-model="search" @keyup.enter="apply" placeholder="Rechercher (nom, email, sujet...)" class="w-full !rounded-xl border-slate-200" />
                        </span>
                        
                        <Select v-model="status" :options="[
                            { label:'Tous statuts', value:'' },
                            { label:'Nouveaux', value:'new' },
                            { label:'Lus', value:'read' },
                            { label:'Répondus', value:'replied' },
                            { label:'Archivés', value:'archived' },
                            { label:'Spam', value:'spam' },
                        ]" optionLabel="label" optionValue="value" @change="apply" class="!rounded-xl border-slate-200 min-w-[180px]" />
                        
                        <Button label="Filtrer" icon="pi pi-filter" @click="apply" class="!rounded-xl !bg-slate-100 !text-slate-700 !border-slate-200 hover:!bg-slate-200 font-bold" />
                    </div>

                    <!-- Tableau natif re-stylisé façon SaaS -->
                    <div v-if="messages.data?.length" class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr class="text-left text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                                    <th class="py-4 px-4 whitespace-nowrap">Réf.</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Expéditeur</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Sujet du message</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Statut</th>
                                    <th class="py-4 px-4 whitespace-nowrap">Reçu le</th>
                                    <th class="py-4 px-4 text-right whitespace-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in messages.data" :key="m.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors" :class="{'bg-red-50/20': m.status === 'new'}">
                                    <td class="py-4 px-4 font-mono text-[11px] font-bold text-slate-400 whitespace-nowrap">
                                        #{{ m.ticket_number }}
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold text-xs uppercase">
                                                {{ m.name ? m.name.substring(0, 2) : '?' }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 text-xs">{{ m.name }}</p>
                                                <a :href="`mailto:${m.email}`" class="text-[10px] font-medium text-blue-500 hover:text-blue-700 transition-colors">{{ m.email }}</a>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-slate-700 line-clamp-1 min-w-[200px]" :class="{'text-slate-900': m.status === 'new'}">
                                            {{ m.subject }}
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-4 whitespace-nowrap">
                                        <Tag :value="statusLabel(m.status)" :severity="statusSeverity(m.status)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                                    </td>
                                    
                                    <td class="py-4 px-4 text-xs font-medium text-slate-500 whitespace-nowrap">
                                        {{ formatDate(m.created_at) }}
                                    </td>
                                    
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <Link :href="route('admin.messages.show', m.id)">
                                            <Button label="Ouvrir" icon="pi pi-arrow-right" size="small" class="!rounded-xl !text-[10px] !font-bold !uppercase !tracking-widest !py-2 !bg-blue-50 !text-blue-600 !border-none hover:!bg-blue-100 transition-colors" />
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
                        <h3 class="text-lg font-bold text-slate-700 mb-1">Aucun message</h3>
                        <p class="text-sm text-slate-500">Votre boîte de réception est vide pour les filtres sélectionnés.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="messages.links?.length > 3" class="flex justify-center gap-1 mt-8 flex-wrap">
                        <Link v-for="(l, i) in messages.links" :key="i" :href="l.url || '#'" v-html="l.label"
                            :class="[
                                'px-3 py-2 rounded-xl text-xs font-black transition-colors', 
                                l.active ? '!bg-red-600 !text-white shadow-md' : '!bg-slate-100 !text-slate-600 hover:!bg-slate-200', 
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
    border-color: #EF4444;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
}
</style>