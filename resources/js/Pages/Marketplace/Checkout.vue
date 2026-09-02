<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const props = defineProps({
    cart: Object, total: [Number, String], user: Object,
    recommendedProvider: String, providers: Array, countries: Array,
});

const form = ref({
    billing_name: props.user?.name ?? '',
    billing_email: props.user?.email ?? '',
    country_code: props.user?.country_code ?? 'CD',
    provider: props.recommendedProvider ?? 'orange_money',
    phone_number: props.user?.phone ?? '',
});

const formError = ref(null);
const submitting = ref(false);

const providerOptions = computed(() => props.providers?.map(p => ({ label: p.label, value: p.key })) ?? []);
const countryOptions = computed(() => props.countries?.map(c => ({ label: `${c.flag ?? ''} ${c.name} (${c.code})`, value: c.code })) ?? []);

const submit = () => {
    formError.value = null;
    submitting.value = true;
    router.post(route('marketplace.process'), form.value, {
        onError: (e) => { formError.value = e.message ?? 'Une erreur est survenue.'; submitting.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};
const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
</script>

<template>
    <AppLayout>
        <Head title="Paiement" />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1100px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 border border-white/10">
                        <i class="pi pi-credit-card text-white text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">Finaliser la commande</h1>
                            <Tag value="SSL" class="!bg-emerald-500/20 !text-emerald-300 !border !border-emerald-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">Choisissez votre mode de paiement préféré.</p>
                    </div>
                </div>
            </div>

            <div class="max-w-[1100px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">
                    <div v-if="formError" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl text-sm font-bold flex items-center gap-2">
                        <i class="pi pi-exclamation-triangle"></i>{{ formError }}
                    </div>

                    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-5">
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                                <h3 class="font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="pi pi-user text-lg"></i></div>
                                    Informations de facturation
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Nom complet</label>
                                        <InputText v-model="form.billing_name" required class="w-full !rounded-xl" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Email</label>
                                        <InputText v-model="form.billing_email" type="email" required class="w-full !rounded-xl" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Pays</label>
                                        <Select v-model="form.country_code" :options="countryOptions" optionLabel="label" optionValue="value" class="w-full" />
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                                <h3 class="font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center"><i class="pi pi-wallet text-lg"></i></div>
                                    Mode de paiement
                                </h3>
                                <Select v-model="form.provider" :options="providerOptions" optionLabel="label" optionValue="value" class="w-full mb-4" />
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Recommandé pour votre pays</p>
                                <div>
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Numéro de téléphone (Mobile Money)</label>
                                    <InputText v-model="form.phone_number" placeholder="+243 81 234 5678" class="w-full !rounded-xl" />
                                </div>
                                <p class="text-xs text-slate-500 mt-3 leading-relaxed flex items-start gap-2">
                                    <i class="pi pi-info-circle text-indigo-500 mt-0.5"></i>
                                    Vous recevrez une notification sur votre téléphone pour confirmer le paiement.
                                </p>
                            </div>
                        </div>

                        <aside class="lg:col-span-1">
                            <div class="bg-slate-900 rounded-3xl border border-slate-800 shadow-xl p-6 sticky top-6 text-white relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                                <h3 class="font-black text-white uppercase tracking-widest text-sm mb-4 relative z-10">Résumé</h3>
                                <div v-for="item in cart?.items" :key="item.id" class="flex justify-between text-xs py-2 border-b border-white/5 relative z-10">
                                    <span class="truncate text-slate-300 flex-1 pr-2 font-medium">{{ item.course?.title }}</span>
                                    <span class="font-black font-mono">{{ formatPrice(item.price) }}</span>
                                </div>
                                <div class="flex justify-between py-4 text-lg font-black relative z-10">
                                    <span>Total</span>
                                    <span class="text-indigo-400 font-mono">{{ formatPrice(total) }}</span>
                                </div>
                                <Button type="submit" label="Payer maintenant" icon="pi pi-lock" :loading="submitting" class="!bg-indigo-500 hover:!bg-indigo-400 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full relative z-10" />
                                <p class="text-[10px] text-slate-400 text-center mt-3 flex items-center justify-center gap-1.5 font-black uppercase tracking-widest relative z-10">
                                    <i class="pi pi-shield"></i> Paiement sécurisé SSL
                                </p>
                            </div>
                        </aside>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>