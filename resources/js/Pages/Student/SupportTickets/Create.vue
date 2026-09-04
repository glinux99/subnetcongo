<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    subject: '',
    message: '',
    category_id: null,
    priority: 'medium',
});

const submit = () => form.post(route('student.support.store'));
</script>

<template>
    <Head title="Nouveau ticket support" />
    <AppLayout>
        <div class="page-bg min-h-screen font-sans pb-20">
            <div class="bg-[#0f172a] pt-10 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-2xl border-b border-slate-800/50">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px]"></div>
                <div class="max-w-[900px] mx-auto relative z-10">
                    <Link :href="route('student.support.index')" class="text-slate-400 hover:text-white text-xs flex items-center gap-2 mb-4">
                        <i class="pi pi-arrow-left"></i> Retour à mes tickets
                    </Link>
                    <h1 class="text-3xl font-black text-white tracking-tight">Nouveau ticket</h1>
                    <p class="text-slate-400 text-sm mt-1">Décrivez votre problème ou votre question, nous vous répondrons sous 24h.</p>
                </div>
            </div>

            <div class="max-w-[900px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <form @submit.prevent="submit" class="bg-white rounded-2xl shadow border border-slate-100 p-8 space-y-5">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Sujet *</label>
                        <InputText v-model="form.subject" class="w-full !rounded-xl" placeholder="Ex : Problème d'accès à mon cours" />
                        <p v-if="form.errors.subject" class="text-rose-500 text-xs mt-1">{{ form.errors.subject }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Catégorie</label>
                            <Select v-model="form.category_id" :options="[{ label:'— Aucune —', value:null }, ...(categories?.map(c => ({ label:c.name, value:c.id })) || [])]" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Priorité *</label>
                            <Select v-model="form.priority" :options="[
                                { label:'Basse', value:'low' },
                                { label:'Moyenne', value:'medium' },
                                { label:'Haute', value:'high' },
                                { label:'Urgente', value:'urgent' },
                            ]" optionLabel="label" optionValue="value" class="w-full" />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1 block">Description *</label>
                        <Textarea v-model="form.message" rows="6" class="w-full !rounded-xl" placeholder="Donnez un maximum de détails pour que nous puissions vous aider rapidement..." />
                        <p v-if="form.errors.message" class="text-rose-500 text-xs mt-1">{{ form.errors.message }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Link :href="route('student.support.index')">
                            <Button label="Annuler" outlined class="!rounded-xl" />
                        </Link>
                        <Button type="submit" label="Envoyer le ticket" icon="pi pi-send" :loading="form.processing" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl" />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
