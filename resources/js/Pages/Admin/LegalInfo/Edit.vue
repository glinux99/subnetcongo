<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import { useToast } from "primevue/usetoast";
import Toast from 'primevue/toast';
import Message from 'primevue/message';

const props = defineProps({
    legal: { type: Object, default: () => ({}) },
});

const toast = useToast();

const form = useForm({
    company_name:   props.legal.company_name   || 'SubnetCongo',
    legal_form:     props.legal.legal_form     || 'SARL',
    rccm:           props.legal.rccm           || '',
    ifu:            props.legal.ifu            || '',
    tax_id:         props.legal.tax_id         || '',
    nif:            props.legal.nif            || '',
    ai:             props.legal.ai             || '',
    country:        props.legal.country        || 'République Démocratique du Congo',
    province:       props.legal.province       || '',
    city:           props.legal.city           || 'Kinshasa',
    address_line:   props.legal.address_line   || '',
    po_box:         props.legal.po_box         || '',
    website:        props.legal.website        || 'https://subnetcongo.cd',
    capital_social: props.legal.capital_social || '',
    logo_url:       props.legal.logo_url       || '',
});

const submit = () => {
    form.post(route('admin.legal.save'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Mise à jour réussie', detail: 'Les informations légales ont été enregistrées.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez vérifier les champs du formulaire.', life: 4000 });
        }
    });
};
</script>

<template>
    <Head title="Identité Légale | Dashboard Admin" />
    <Toast />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- HEADER ULTIMATE V30 -->
            <div class="bg-[#0F172A] relative overflow-hidden">
                <!-- Mesh Gradient Background -->
                <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
                    <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-red-600 rounded-full blur-[120px]"></div>
                    <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-blue-600 rounded-full blur-[100px]"></div>
                </div>

                <div class="max-w-6xl mx-auto px-6 py-16 relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <span class="bg-red-500/10 text-red-500 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border border-red-500/20">System Config</span>
                            </div>
                            <h1 class="text-4xl font-black text-white tracking-tighter italic uppercase">
                                Identité <span class="text-red-500">Légale</span>
                            </h1>
                            <p class="text-slate-400 text-sm mt-3 max-w-xl leading-relaxed">
                                Gérez les paramètres institutionnels de SubnetCongo. Ces données sont utilisées pour la génération automatique de vos factures, contrats et documents officiels.
                            </p>
                        </div>
                        <div class="hidden lg:block">
                            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center shadow-lg shadow-red-500/20">
                                        <i class="pi pi-shield text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-widest">Statut Compliance</p>
                                        <p class="text-white font-bold tracking-tight">Vérifié & Opérationnel</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM SECTIONS -->
            <div class="max-w-6xl mx-auto px-6 -mt-10 relative z-20">
                <form @submit.prevent="submit" class="space-y-8">

                    <!-- SECTION 1 : SOCIETE -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-12">
                            <div class="lg:col-span-4 bg-slate-50/50 p-8 border-r border-slate-100">
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-building text-red-500"></i> Structure
                                </h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Informations générales sur l'entité juridique et sa forme sociale.</p>
                            </div>
                            <div class="lg:col-span-8 p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-full md:col-span-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Raison sociale <span class="text-red-500">*</span></label>
                                    <InputText v-model="form.company_name" :class="{'p-invalid': form.errors.company_name}" class="w-full !rounded-xl !p-3 !bg-slate-50 focus:!bg-white transition-all" />
                                    <small class="text-red-500" v-if="form.errors.company_name">{{ form.errors.company_name }}</small>
                                </div>
                                <div class="col-span-full md:col-span-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Forme juridique</label>
                                    <InputText v-model="form.legal_form" class="w-full !rounded-xl !p-3 !bg-slate-50 focus:!bg-white" placeholder="SARL, SA, SAS..." />
                                </div>
                                <div class="col-span-full">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Capital social</label>
                                    <InputText v-model="form.capital_social" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2 : FISCALITE -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-12">
                            <div class="lg:col-span-4 bg-slate-50/50 p-8 border-r border-slate-100">
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-percentage text-red-500"></i> Fiscalité & IDs
                                </h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Identifiants uniques d'enregistrement auprès des autorités congolaises.</p>
                            </div>
                            <div class="lg:col-span-8 p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="(label, field) in {rccm: 'RCCM', ifu: 'IFU', tax_id: 'Numéro Contribuable', nif: 'NIF', ai: 'Article d’Imposition (AI)'}" :key="field">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">{{ label }}</label>
                                    <InputText v-model="form[field]" class="w-full !rounded-xl !p-3 font-mono text-sm !bg-slate-50" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3 : LOCALISATION -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-12">
                            <div class="lg:col-span-4 bg-slate-50/50 p-8 border-r border-slate-100">
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-800 flex items-center gap-2">
                                    <i class="pi pi-map-marker text-red-500"></i> Siège Social
                                </h3>
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed">Coordonnées physiques et numériques de l'établissement principal.</p>
                            </div>
                            <div class="lg:col-span-8 p-8 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Pays</label>
                                        <InputText v-model="form.country" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Province</label>
                                        <InputText v-model="form.province" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Ville</label>
                                        <InputText v-model="form.city" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Adresse précise</label>
                                    <InputText v-model="form.address_line" class="w-full !rounded-xl !p-3 !bg-slate-50" placeholder="Avenue, N°, Commune..." />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">URL Site Web</label>
                                        <InputText v-model="form.website" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Logo Institutionnel (URL)</label>
                                        <InputText v-model="form.logo_url" class="w-full !rounded-xl !p-3 !bg-slate-50" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STICKY ACTION BAR -->
                    <div class="sticky bottom-6 bg-[#0F172A]/90 backdrop-blur-xl p-4 rounded-xl border border-white/10 shadow-2xl flex items-center justify-between">
                        <p class="text-xs text-slate-400 px-4 hidden md:block">
                            <i class="pi pi-info-circle text-red-500 mr-2"></i> Les modifications sont définitives après enregistrement.
                        </p>
                        <div class="flex gap-4 w-full md:w-auto">
                            <Button @click="form.reset()" type="button" label="Réinitialiser" class="p-button-text !text-white !text-xs !font-bold flex-1 md:flex-none" />
                            <Button type="submit"
                                    label="Enregistrer les modifications"
                                    icon="pi pi-check-circle"
                                    :loading="form.processing"
                                    class="!bg-red-600 hover:!bg-red-700 !border-none !rounded-xl !px-8 !py-3 !text-xs !font-black !uppercase !tracking-widest flex-1 md:flex-none transition-all active:scale-95" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Focus States Focus Pro Max */
:deep(.p-inputtext:focus) {
    border-color: #EF4444 !important;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
}

:deep(.p-inputtext) {
    border: 1px solid #E2E8F0;
}
</style>
