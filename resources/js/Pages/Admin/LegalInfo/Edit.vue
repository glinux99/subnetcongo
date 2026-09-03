<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

const props = defineProps({
    legal: { type: Object, default: () => ({}) },
});

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

const submit = () => form.post(route('admin.legal.save'));
</script>

<template>
    <Head title="Identité légale" />
    <AppLayout>
        <div class="min-h-screen bg-slate-50 font-sans pb-12">
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-amber-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[900px] mx-auto relative z-10">
                    <h1 class="text-3xl font-black text-white tracking-tight"><i class="pi pi-id-card text-amber-400 mr-3"></i>Identité légale de l'entreprise</h1>
                    <p class="text-slate-400 text-sm mt-1">Renseignez ici les informations qui apparaîtront sur la page Contact, le footer, et les factures.</p>
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <form @submit.prevent="submit" class="bg-white rounded-2xl shadow border border-slate-100 p-8 space-y-6">
                    <h2 class="text-lg font-black uppercase tracking-widest text-slate-700 border-b pb-2">Société</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Raison sociale *</label>
                            <InputText v-model="form.company_name" class="w-full !rounded-xl" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Forme juridique</label>
                            <InputText v-model="form.legal_form" class="w-full !rounded-xl" placeholder="SARL, SA, SAS..." />
                        </div>
                    </div>

                    <h2 class="text-lg font-black uppercase tracking-widest text-slate-700 border-b pb-2 pt-4">Identifiants fiscaux</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">RCCM</label>
                            <InputText v-model="form.rccm" class="w-full !rounded-xl font-mono" placeholder="Ex: CD/KIN/RCCM/22-B-1234" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">IFU (Identifiant Fiscal Unique)</label>
                            <InputText v-model="form.ifu" class="w-full !rounded-xl font-mono" placeholder="Ex: A1234567B" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">N° Contribuable</label>
                            <InputText v-model="form.tax_id" class="w-full !rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">NIF</label>
                            <InputText v-model="form.nif" class="w-full !rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Article d'Imposition (AI)</label>
                            <InputText v-model="form.ai" class="w-full !rounded-xl font-mono" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Capital social</label>
                            <InputText v-model="form.capital_social" class="w-full !rounded-xl" placeholder="Ex: 10 000 000 CDF" />
                        </div>
                    </div>

                    <h2 class="text-lg font-black uppercase tracking-widest text-slate-700 border-b pb-2 pt-4">Adresse</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Pays *</label>
                            <InputText v-model="form.country" class="w-full !rounded-xl" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Province</label>
                            <InputText v-model="form.province" class="w-full !rounded-xl" placeholder="Ex: Kinshasa" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Ville</label>
                            <InputText v-model="form.city" class="w-full !rounded-xl" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Adresse complète</label>
                            <InputText v-model="form.address_line" class="w-full !rounded-xl" placeholder="N°, Avenue, Quartier, Commune" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Boîte postale</label>
                            <InputText v-model="form.po_box" class="w-full !rounded-xl" placeholder="Ex: BP 1234" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Site web</label>
                            <InputText v-model="form.website" class="w-full !rounded-xl" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">URL du logo</label>
                            <InputText v-model="form.logo_url" class="w-full !rounded-xl" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <Button type="submit" label="Enregistrer" icon="pi pi-save" :loading="form.processing" class="!bg-amber-500 hover:!bg-amber-600 !border-none !rounded-xl" />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
