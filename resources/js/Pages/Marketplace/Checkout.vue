<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';

const props = defineProps({
    cart: Object,
    total: [Number, String],
    user: Object,
    recommendedProvider: String,
    providers: Array,
    countries: Array,
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

const providerOptions = computed(() =>
    props.providers?.map(p => ({ label: p.label, value: p.key })) ?? []
);

const countryOptions = computed(() =>
    props.countries?.map(c => ({ label: `${c.flag ?? ''} ${c.name} (${c.code})`, value: c.code })) ?? []
);

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

        <div class="min-h-screen bg-[#F8FAFC] font-sans py-10">
            <div class="max-w-[1100px] mx-auto px-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-violet-100 rounded-2xl flex items-center justify-center">
                        <i class="pi pi-credit-card text-violet-600 text-xl"></i>
                    </div>
                    <h1 class="text-3xl font-black italic text-slate-800">Finaliser la commande</h1>
                </div>

                <div v-if="formError" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm font-bold">
                    <i class="pi pi-exclamation-triangle mr-2"></i>{{ formError }}
                </div>

                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-5">
                        <div class="bg-white rounded-2xl border border-slate-100 p-6">
                            <h3 class="font-black text-slate-800 mb-4 flex items-center gap-2">
                                <i class="pi pi-user text-violet-500"></i> Informations de facturation
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-500 mb-1 block">Nom complet</label>
                                    <InputText v-model="form.billing_name" required class="w-full" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-500 mb-1 block">Email</label>
                                    <InputText v-model="form.billing_email" type="email" required class="w-full" />
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-500 mb-1 block">Pays</label>
                                    <Select v-model="form.country_code" :options="countryOptions"
                                            optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-100 p-6">
                            <h3 class="font-black text-slate-800 mb-4 flex items-center gap-2">
                                <i class="pi pi-wallet text-violet-500"></i> Mode de paiement
                            </h3>
                            <Select v-model="form.provider" :options="providerOptions"
                                    optionLabel="label" optionValue="value" class="w-full mb-4" />
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Recommandé pour votre pays</p>
                            <div>
                                <label class="text-xs font-bold text-slate-500 mb-1 block">Numéro de téléphone (Mobile Money)</label>
                                <InputText v-model="form.phone_number" placeholder="+243 81 234 5678" class="w-full" />
                            </div>
                            <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                                <i class="pi pi-info-circle text-violet-400"></i>
                                Vous recevrez une notification sur votre téléphone pour confirmer le paiement.
                            </p>
                        </div>
                    </div>

                    <aside class="lg:col-span-1">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-6">
                            <h3 class="font-black text-slate-800 mb-4">Résumé</h3>
                            <div v-for="item in cart?.items" :key="item.id" class="flex justify-between text-xs py-2 border-b border-slate-50">
                                <span class="truncate text-slate-600 flex-1 pr-2">{{ item.course?.title }}</span>
                                <span class="font-black">{{ formatPrice(item.price) }}</span>
                            </div>
                            <div class="flex justify-between py-4 text-lg font-black">
                                <span>Total</span>
                                <span class="text-violet-600">{{ formatPrice(total) }}</span>
                            </div>
                            <Button type="submit" label="Payer maintenant" icon="pi pi-lock"
                                    :loading="submitting" class="p-button-primary w-full font-black py-3" />
                            <p class="text-[10px] text-slate-400 text-center mt-3">
                                <i class="pi pi-shield"></i> Paiement sécurisé SSL
                            </p>
                        </div>
                    </aside>
                </form>
            </div>
        </div>
    </AppLayout>
</template>