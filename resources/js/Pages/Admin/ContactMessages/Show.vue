<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';

const props = defineProps({
    message: { type: Object, required: true },
});

const replyForm = useForm({ reply_message: props.message.reply_message || '' });

const submitReply = () => replyForm.post(route('admin.messages.reply', props.message.id), { preserveScroll: true });
const archive = () => router.post(route('admin.messages.archive', props.message.id));
const destroyMsg = () => {
    if (confirm('Supprimer définitivement ce message ? Cette action est irréversible.')) {
        router.delete(route('admin.messages.destroy', props.message.id));
    }
};

const statusLabel = (s) => ({ new:'Nouveau', read:'Lu', replied:'Répondu', archived:'Archivé', spam:'Spam' }[s] || s);

// Correction pour utiliser les Severities natives de PrimeVue
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
    <Head :title="`Message #${message.ticket_number}`" />
    <AppLayout>
        
        <div class="page-bg min-h-screen font-sans pb-20 bg-slate-50">
            
            <div class="flex flex-col mb-12 px-4 md:px-6 pt-4 max-w-[1200px] mx-auto">
                
                <!-- Lien de retour type Breadcrumb -->
                <div class="flex items-center text-sm px-1 mb-3">
                    <Link :href="route('admin.messages.index')" class="text-slate-500 hover:text-slate-800 text-[11px] font-black uppercase tracking-widest flex items-center gap-2 transition-colors">
                        <i class="pi pi-arrow-left"></i> Retour à la boîte de réception
                    </Link>
                </div>

                <!-- 1. BANNIÈRE SOMBRE -->
                <div class="relative bg-[#16151E] rounded-xl pt-10 pb-24 px-6 md:px-10 shadow-lg overflow-hidden border border-slate-800">
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 relative z-10 w-full">
                        
                        <!-- Partie Gauche (Titres) -->
                        <div class="max-w-3xl">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="inline-block border border-red-900/50 text-red-400 text-[10px] font-bold tracking-widest px-3 py-1 rounded-full bg-red-950/30 uppercase">
                                    Ticket #{{ message.ticket_number }}
                                </span>
                                <Tag :value="statusLabel(message.status)" :severity="statusSeverity(message.status)" class="!text-[9px] !font-bold !uppercase !tracking-wider px-2" />
                            </div>

                            <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter uppercase flex flex-wrap gap-1 md:gap-2 mb-3 leading-none">
                                <span class="text-white">LECTURE DU</span>
                                <span class="text-[#EF4444]">MESSAGE</span>
                            </h1>

                            <p class="text-slate-400 text-sm md:text-base italic leading-relaxed font-bold">
                                Sujet : <span class="text-white">{{ message.subject }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. CARTES KPI / EXPÉDITEUR (Chevauchement) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-2 md:px-4 -mt-16 relative z-20">
                    
                    <!-- Nom -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-32">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center mb-2">
                            <i class="pi pi-user text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Expéditeur</div>
                            <div class="text-lg font-black text-slate-800 truncate">{{ message.name }}</div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-32">
                        <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center mb-2">
                            <i class="pi pi-envelope text-blue-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Adresse E-mail</div>
                            <a :href="`mailto:${message.email}`" class="text-lg font-black text-blue-600 hover:text-blue-800 transition-colors truncate block">{{ message.email }}</a>
                        </div>
                    </div>

                    <!-- Téléphone -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between h-32">
                        <div class="w-8 h-8 rounded bg-red-50 flex items-center justify-center mb-2">
                            <i class="pi pi-phone text-red-500 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Téléphone</div>
                            <div class="text-lg font-black text-slate-800 truncate">{{ message.phone || 'Non renseigné' }}</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================================== -->
                <!-- CONTENU DU MESSAGE ET RÉPONSE                                                  -->
                <!-- ============================================================================== -->
                <div class="mt-6 space-y-6 px-2 md:px-4 relative z-20">
                    
                    <!-- Message Reçu -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="bg-slate-50 border-b border-slate-100 p-4 md:p-6 flex justify-between items-center">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-600 flex items-center gap-2">
                                <i class="pi pi-align-left text-blue-400"></i> Contenu du message
                            </h3>
                            <span class="text-xs font-bold text-slate-400 bg-white px-3 py-1 rounded-md border border-slate-200 shadow-sm">
                                Reçu le {{ formatDate(message.created_at) }}
                            </span>
                        </div>
                        <div class="p-6 md:p-8">
                            <pre class="whitespace-pre-wrap font-sans text-[15px] text-slate-700 leading-relaxed">{{ message.message }}</pre>
                            
                            <!-- Méta-données discrètes -->
                            <div class="mt-8 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                <span><i class="pi pi-wifi mr-1"></i> IP : {{ message.ip_address || '—' }}</span>
                                <span><i class="pi pi-link mr-1"></i> Source : {{ message.source || '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Si déjà répondu -->
                    <div v-if="message.status === 'replied' && message.reply_message" class="bg-blue-50 rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                        <div class="bg-blue-100/50 border-b border-blue-200 p-4 md:p-6 flex justify-between items-center">
                            <h3 class="text-sm font-black uppercase tracking-widest text-blue-800 flex items-center gap-2">
                                <i class="pi pi-reply text-blue-600"></i> Votre Réponse
                            </h3>
                            <span class="text-xs font-bold text-blue-600 bg-white px-3 py-1 rounded-md shadow-sm">
                                Envoyée le {{ formatDate(message.replied_at) }}
                            </span>
                        </div>
                        <div class="p-6 md:p-8">
                            <pre class="whitespace-pre-wrap font-sans text-[15px] text-blue-900 leading-relaxed">{{ message.reply_message }}</pre>
                        </div>
                    </div>

                    <!-- Formulaire de réponse -->
                    <div v-if="message.status !== 'archived' && message.status !== 'replied'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="bg-slate-50 border-b border-slate-100 p-4 md:p-6">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-600 flex items-center gap-2">
                                <i class="pi pi-send text-blue-400"></i> Répondre par e-mail
                            </h3>
                        </div>
                        <div class="p-6 md:p-8">
                            <form @submit.prevent="submitReply">
                                <Textarea 
                                    v-model="replyForm.reply_message" 
                                    rows="8" 
                                    class="w-full !rounded-xl !bg-slate-50 border-slate-200 text-slate-700 text-[15px] leading-relaxed p-4" 
                                    placeholder="Rédigez votre réponse ici. Elle sera envoyée directement à l'adresse e-mail de l'expéditeur et archivée dans le système..." 
                                />
                                <p v-if="replyForm.errors.reply_message" class="text-red-500 text-xs font-bold mt-2"><i class="pi pi-exclamation-triangle"></i> {{ replyForm.errors.reply_message }}</p>
                                
                                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-100">
                                    <div class="flex gap-2 w-full sm:w-auto">
                                        <Button type="button" label="Archiver" icon="pi pi-folder" outlined @click="archive" class="!rounded-xl text-slate-600 border-slate-300 hover:bg-slate-50 w-full sm:w-auto font-bold" />
                                        <Button type="button" label="Supprimer" icon="pi pi-trash" outlined @click="destroyMsg" class="!rounded-xl text-red-500 border-red-200 hover:bg-red-50 w-full sm:w-auto font-bold" />
                                    </div>
                                    <Button type="submit" label="Envoyer la réponse" icon="pi pi-paper-plane" :loading="replyForm.processing" class="!bg-[#EF4444] hover:!bg-red-600 !border-none !rounded-xl font-bold px-8 py-3 w-full sm:w-auto shadow-md shadow-red-500/20" />
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Si archivé, juste l'option supprimer -->
                    <div v-else class="flex justify-end pt-4">
                        <Button label="Supprimer définitivement" icon="pi pi-trash" outlined @click="destroyMsg" class="!rounded-xl text-red-500 border-red-200 hover:bg-red-50 font-bold" />
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.italic { font-style: italic; }

/* Overrides UI Forms PrimeVue */
:deep(.p-textarea) {
    border-radius: 0.75rem;
    transition: all 0.2s;
}
:deep(.p-textarea:enabled:focus) {
    border-color: #EF4444;
    box-shadow: 0 0 0 0.2rem rgba(255, 59, 59, 0.1);
    background-color: #ffffff !important;
}
</style>