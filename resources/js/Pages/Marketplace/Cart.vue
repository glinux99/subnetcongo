<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import { ref } from 'vue';

const props = defineProps({
    cart: Object,
    items: Array,
    subtotal: [Number, String],
    cartCount: Number,
    country: Object,
    countries: Array,
});

const showCountryPicker = ref(false);

const formatLocal = (amount) => {
    if (amount === null || amount === undefined) return '—';
    const symbol = props.cart?.symbol ?? '$';
    const currency = props.cart?.currency ?? 'USD';
    try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount);
    } catch (_) {
        return `${symbol} ${Number(amount).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
};

const getImageUrl = (path) => path?.startsWith('http') ? path : (path ? `/storage/${path}` : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=200');

const freeItems  = computed(() => props.cart?.free ?? []);
const paidItems  = computed(() => props.cart?.paid ?? []);
const hasItems   = computed(() => (props.cart?.count ?? 0) > 0);
const hasFree    = computed(() => freeItems.value.length > 0);
const hasPaid    = computed(() => paidItems.value.length > 0);

const remove = (id) => router.delete(route('marketplace.cart.remove', id), { preserveScroll: true });
const checkout = () => router.get(route('marketplace.checkout'));
const enrollFreeNow = () => router.post(route('marketplace.cart.enroll-free'), {}, { preserveScroll: true });
const changeCountry = (code) => {
    if (! code || code === props.country?.code) return;
    router.post(route('marketplace.country.set'), { country_code: code }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Mon Panier" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <!-- HEADER -->
            <div class="page-header">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-violet-600/15 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-pink-500/10 rounded-full blur-[80px]"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex flex-col md:flex-row md:items-center gap-5 justify-between">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 border border-white/10">
                            <i class="pi pi-shopping-cart text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">Mon Panier</h1>
                                <Tag :value="`${cart?.count ?? 0} article(s)`" class="!bg-violet-500/20 !text-violet-300 !border !border-violet-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">Cours gratuits = inscription immédiate · Cours payants = paiement ou validation admin.</p>
                        </div>
                    </div>

                    <!-- Sélecteur de pays/devise -->
                    <div class="flex items-center gap-2 bg-slate-800/50 border border-slate-700 rounded-2xl px-3 py-2">
                        <i class="pi pi-globe text-slate-300"></i>
                        <div class="flex flex-col">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Pays & devise</p>
                            <Dropdown
                                :modelValue="country?.code"
                                :options="countries"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Choisir un pays"
                                class="!bg-transparent !border-none !text-white"
                                @change="(e) => changeCountry(e.value)"
                            >
                                <template #value="slotProps">
                                    <span v-if="slotProps.value" class="text-white text-xs font-black">
                                        {{ country?.flag }} {{ country?.name }} · {{ country?.currency }}
                                    </span>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN -->
            <div class="max-w-[1100px] mx-auto px-4 lg:px-6 -mt-8 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">

                    <div v-if="!hasItems" class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-shopping-bag text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-700">Votre panier est vide</h3>
                        <p class="text-slate-400 text-sm mt-2 mb-6">Découvrez nos cours et commencez votre apprentissage.</p>
                        <Link :href="route('marketplace.index')">
                            <Button label="Explorer le marketplace" icon="pi pi-search" class="!bg-violet-600 hover:!bg-violet-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !px-5 !py-3" />
                        </Link>
                    </div>

                    <div v-else class="space-y-8">
                        <!-- ════════════ COURS GRATUITS ════════════ -->
                        <section v-if="hasFree">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-gift text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-slate-800">Cours gratuits ({{ freeItems.length }})</h2>
                                    <p class="text-xs text-slate-500">Inscription immédiate, sans paiement ni validation.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div v-for="item in freeItems" :key="item.id" class="bg-emerald-50/40 rounded-2xl border border-emerald-100 p-4 flex gap-4 items-center">
                                    <img :src="getImageUrl(item.course?.thumbnail_path)" class="w-24 h-16 rounded-2xl object-cover" />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-black text-sm text-slate-800 truncate tracking-tight">{{ item.course?.title }}</h4>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ item.course?.instructor?.name }}</p>
                                    </div>
                                    <Tag value="GRATUIT" severity="success" class="!bg-emerald-500 !text-white !font-black !text-[10px] !uppercase !tracking-widest !px-3 !py-1" />
                                    <p class="text-lg font-black text-emerald-600 font-mono">GRATUIT</p>
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="remove(item.course?.id)" />
                                </div>
                            </div>
                            <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-4 flex-wrap">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-bolt text-emerald-600 text-2xl"></i>
                                    <div>
                                        <p class="font-black text-emerald-800">Inscription immédiate</p>
                                        <p class="text-xs text-emerald-700">Activez tous vos cours gratuits en un clic, sans validation.</p>
                                    </div>
                                </div>
                                <Button
                                    label="S'inscrire gratuitement"
                                    icon="pi pi-check-circle"
                                    @click="enrollFreeNow"
                                    class="!bg-emerald-600 hover:!bg-emerald-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !px-5 !py-3"
                                />
                            </div>
                        </section>

                        <!-- ════════════ COURS PAYANTS ════════════ -->
                        <section v-if="hasPaid">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-violet-100 text-violet-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-credit-card text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-slate-800">Cours payants ({{ paidItems.length }})</h2>
                                    <p class="text-xs text-slate-500">Paiement en ligne ou demande d'inscription validée par un administrateur.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div class="lg:col-span-2 space-y-3">
                                    <div v-for="item in paidItems" :key="item.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex gap-4 items-center">
                                        <img :src="getImageUrl(item.course?.thumbnail_path)" class="w-24 h-16 rounded-2xl object-cover" />
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-black text-sm text-slate-800 truncate tracking-tight">{{ item.course?.title }}</h4>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ item.course?.instructor?.name }}</p>
                                        </div>
                                        <p class="text-lg font-black text-violet-600 font-mono">{{ formatLocal(item.price_local) }}</p>
                                        <Button icon="pi pi-trash" severity="danger" text rounded @click="remove(item.course?.id)" />
                                    </div>
                                </div>

                                <aside class="lg:col-span-1">
                                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sticky top-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Résumé</h3>
                                            <Tag :value="cart?.currency" severity="info" class="!font-black !text-[10px] !uppercase !tracking-widest" />
                                        </div>
                                        <div class="space-y-2 pb-4 border-b border-slate-100">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Sous-total</span>
                                                <span class="font-black text-slate-800 font-mono">{{ formatLocal(cart?.subtotal_local) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Taxes</span>
                                                <span class="font-black text-slate-400">{{ formatLocal(0) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex justify-between py-4 text-lg">
                                            <span class="font-black">Total</span>
                                            <span class="font-black text-violet-600 font-mono">{{ formatLocal(cart?.total_local) }}</span>
                                        </div>
                                        <Button label="Passer commande" icon="pi pi-credit-card" @click="checkout" class="!bg-violet-600 hover:!bg-violet-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                                        <p class="text-[10px] text-slate-400 text-center mt-3 font-black uppercase tracking-widest">
                                            <i class="pi pi-shield text-emerald-500 mr-1"></i> Paiement sécurisé SSL
                                        </p>
                                        <p class="text-[10px] text-slate-500 text-center mt-2 leading-relaxed">
                                            Vous pouvez aussi demander une inscription — un administrateur la validera après vérification.
                                        </p>
                                    </div>
                                </aside>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
