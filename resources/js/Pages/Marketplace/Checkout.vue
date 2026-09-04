<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    cart: Object,
    items: Array,
    subtotal: [Number, String],
    total: [Number, String],
    user: Object,
    recommendedProvider: String,
    providers: Array,
    countries: Array,
    country: Object,
    currency: String,
    symbol: String,
});

const form = ref({
    billing_name: props.user?.name ?? '',
    billing_email: props.user?.email ?? '',
    country_code: props.country?.code ?? props.user?.country_code ?? 'CD',
    provider: props.recommendedProvider ?? 'orange_money',
    phone_number: props.user?.phone ?? '',
});

const formError = ref(null);
const submitting = ref(false);
const requestingValidation = ref(false);

const providerOptions = computed(() => props.providers?.map(p => ({ label: p.label, value: p.key })) ?? []);
const countryOptions  = computed(() => props.countries ?? []);

const selectedCountry = computed(() => props.countries?.find(c => c.value === form.value.country_code) || null);

const formatLocal = (amount) => {
    const currency = props.currency ?? 'USD';
    try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount);
    } catch (_) {
        return `${props.symbol ?? '$'} ${Number(amount).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
};

const changeCountry = (code) => {
    if (! code || code === props.country?.code) return;
    router.post(route('marketplace.country.set'), { country_code: code }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['cart', 'subtotal', 'total', 'currency', 'symbol', 'recommendedProvider'] }),
    });
};

watch(() => props.country?.code, (newCode) => {
    if (newCode) form.value.country_code = newCode;
});

const submit = () => {
    formError.value = null;
    submitting.value = true;
    router.post(route('marketplace.process'), form.value, {
        onError: (e) => { formError.value = e.message ?? e.error ?? 'Une erreur est survenue.'; submitting.value = false; },
        onFinish: () => { submitting.value = false; },
    });
};

const requestAdminValidation = () => {
    formError.value = null;
    requestingValidation.value = true;
    router.post(route('marketplace.request-validation'), {
        note: form.value.billing_name ? 'Demandé par ' + form.value.billing_name : null,
        country_code: form.value.country_code,
        phone_number: form.value.phone_number,
    }, {
        onError: (e) => { formError.value = e.message ?? e.error ?? 'Une erreur est survenue.'; requestingValidation.value = false; },
        onFinish: () => { requestingValidation.value = false; },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Paiement" />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-indigo-600/15 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-emerald-500/10 rounded-full blur-[80px]"></div>
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

                    <form @submit.prevent class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-5">
                            <!-- PAYS & DEVISE -->
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                                <h3 class="font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center"><i class="pi pi-globe text-lg"></i></div>
                                    Pays &amp; devise
                                </h3>
                                <p class="text-xs text-slate-500 mb-4">Les prix sont automatiquement convertis dans la devise de votre pays. Vous pouvez modifier le pays à tout moment.</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Pays (recherche possible)</label>
                                        <Dropdown
                                            v-model="form.country_code"
                                            :options="countryOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Tapez pour rechercher un pays…"
                                            filter
                                            class="w-full"
                                            @change="(e) => changeCountry(e.value)"
                                        >
                                            <template #value="slotProps">
                                                <span v-if="slotProps.value" class="text-sm font-bold">
                                                    {{ selectedCountry?.flag }} {{ selectedCountry?.name }} ({{ selectedCountry?.value }})
                                                </span>
                                            </template>
                                        </Dropdown>
                                    </div>
                                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Devise</p>
                                            <p class="font-black text-slate-800 mt-1">{{ selectedCountry?.currency ?? currency ?? 'USD' }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Symbole</p>
                                            <p class="font-black text-slate-800 mt-1 text-lg">{{ selectedCountry?.symbol ?? symbol ?? '$' }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100">
                                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Indicatif tél.</p>
                                            <p class="font-black text-slate-800 mt-1">{{ selectedCountry?.phone ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BILLING -->
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
                                </div>
                            </div>

                            <!-- PAYMENT -->
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                                <h3 class="font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center"><i class="pi pi-wallet text-lg"></i></div>
                                    Mode de paiement
                                </h3>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Fournisseur</label>
                                <Select v-model="form.provider" :options="providerOptions" optionLabel="label" optionValue="value" class="w-full mb-4" />
                                <p v-if="recommendedProvider" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-2">
                                    <i class="pi pi-star-fill mr-1"></i> Recommandé pour {{ country?.name ?? 'votre pays' }}
                                </p>
                                <div>
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Numéro de téléphone (Mobile Money) <span class="text-slate-400 font-normal normal-case">— préfixe {{ selectedCountry?.phone ?? '+243' }}</span></label>
                                    <InputText v-model="form.phone_number" :placeholder="(selectedCountry?.phone ?? '+243') + ' 81 234 5678'" class="w-full !rounded-xl" />
                                </div>
                                <p class="text-xs text-slate-500 mt-3 leading-relaxed flex items-start gap-2">
                                    <i class="pi pi-info-circle text-indigo-500 mt-0.5"></i>
                                    Vous recevrez une notification sur votre téléphone pour confirmer le paiement. En cas d'échec, un administrateur peut valider manuellement votre inscription.
                                </p>
                            </div>
                        </div>

                        <aside class="lg:col-span-1">
                            <div class="bg-slate-900 rounded-3xl border border-slate-800 shadow-xl p-6 sticky top-6 text-white relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>
                                <div class="flex items-center justify-between mb-4 relative z-10">
                                    <h3 class="font-black text-white uppercase tracking-widest text-sm">Résumé</h3>
                                    <Tag :value="currency ?? 'USD'" severity="info" class="!font-black !text-[10px] !uppercase !tracking-widest" />
                                </div>
                                <div v-for="item in cart?.paid" :key="item.id" class="flex justify-between text-xs py-2 border-b border-white/5 relative z-10">
                                    <span class="truncate text-slate-300 flex-1 pr-2 font-medium">{{ item.course?.title }}</span>
                                    <span class="font-black font-mono">{{ formatLocal(item.price_local) }}</span>
                                </div>
                                <div class="flex justify-between py-2 text-xs text-slate-400 relative z-10">
                                    <span>Sous-total</span>
                                    <span class="font-mono">{{ formatLocal(cart?.subtotal_local) }}</span>
                                </div>
                                <div class="flex justify-between py-2 text-xs text-slate-400 relative z-10">
                                    <span>Taxes</span>
                                    <span class="font-mono">{{ formatLocal(0) }}</span>
                                </div>
                                <div class="flex justify-between py-4 text-lg font-black relative z-10 border-t border-white/10">
                                    <span>Total</span>
                                    <span class="text-indigo-400 font-mono">{{ formatLocal(cart?.total_local) }}</span>
                                </div>

                                <!-- DEUX BOUTONS : Payer maintenant OU Demander validation admin -->
                                <div class="space-y-3 mt-2 relative z-10">
                                    <Button
                                        type="submit"
                                        label="Payer maintenant"
                                        icon="pi pi-lock"
                                        :loading="submitting"
                                        :disabled="requestingValidation"
                                        class="!bg-indigo-500 hover:!bg-indigo-400 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full"
                                    />
                                    <Button
                                        type="button"
                                        label="Demander validation admin"
                                        icon="pi pi-user-edit"
                                        severity="secondary"
                                        outlined
                                        :loading="requestingValidation"
                                        :disabled="submitting"
                                        @click="requestAdminValidation"
                                        class="!bg-white/5 hover:!bg-white/10 !text-white !border-white/20 hover:!border-white/40 !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full"
                                    />
                                </div>

                                <p class="text-[10px] text-slate-400 text-center mt-4 flex items-center justify-center gap-1.5 font-black uppercase tracking-widest relative z-10">
                                    <i class="pi pi-shield"></i> Paiement sécurisé SSL
                                </p>
                                <p class="text-[10px] text-slate-500 text-center mt-2 leading-relaxed relative z-10">
                                    Pas de moyen de paiement en ligne ? Demandez la validation manuelle à un administrateur, qui confirmera votre inscription après vérification.
                                </p>
                            </div>
                        </aside>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
