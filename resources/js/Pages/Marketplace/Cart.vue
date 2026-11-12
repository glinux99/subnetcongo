<script setup>
import { computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import { ref } from 'vue';

const props = defineProps({
    cart: Object,
    items: Array,
    subtotal: [Number, String],
    cartCount: Number,
    country: Object,
    countries: Array,
    coupon: Object,
});

const formatLocal = (amount) => {
    if (amount === null || amount === undefined) return 'â€”';
    const symbol = props.cart?.symbol ?? '$';
    const currency = props.cart?.currency ?? 'USD';
    try {
        return new Intl.NumberFormat(locale.value, { style: 'currency', currency }).format(amount);
    } catch (_) {
        return `${symbol} ${Number(amount).toLocaleString(locale.value, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
};

const getImageUrl = (path) => path?.startsWith('http') ? path : (path ? `/storage/${path}` : '/media/lms/thumbnails/course-1.jpg');

const freeItems  = computed(() => props.cart?.free ?? []);
const paidItems  = computed(() => props.cart?.paid ?? []);
const hasItems   = computed(() => (props.cart?.count ?? 0) > 0);
const hasFree    = computed(() => freeItems.value.length > 0);
const hasPaid    = computed(() => paidItems.value.length > 0);
const couponCode = ref('');
const applyingCoupon = ref(false);
const activeCoupon = computed(() => props.cart?.coupon_code ?? null);
const appliedDiscountLocal = computed(() => props.cart?.discount_local ?? 0);
const taxRatePercent = computed(() => {
    const rate = Number(props.cart?.tax_rate ?? 0) * 100;
    return rate % 1 === 0 ? String(rate) : rate.toFixed(2);
});

const applyCoupon = () => {
    const code = couponCode.value.trim();
    if (!code) return;
    applyingCoupon.value = true;
    router.post(route('marketplace.cart.apply-coupon'), { code }, {
        preserveScroll: true,
        onSuccess: () => { couponCode.value = ''; router.reload({ only: ['cart', 'cartCount'] }); },
        onFinish: () => { applyingCoupon.value = false; },
    });
};
const removeCoupon = () => {
    router.delete(route('marketplace.cart.remove-coupon'), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['cart', 'cartCount'] }),
    });
};

const remove = (slug) => router.delete(route('marketplace.cart.remove', slug), { preserveScroll: true });
const checkout = () => router.get(route('marketplace.checkout'));
const enrollFreeNow = () => router.post(route('marketplace.cart.enroll-free'), {}, { preserveScroll: true });
const changeCountry = (code) => {
    if (! code || code === props.country?.code) return;
    router.post(route('marketplace.country.set'), { country_code: code }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('marketplace.my_cart')" />

        <div class="page-bg min-h-screen font-sans pb-20">

            <PageHeader
                :title="t('marketplace.my_cart')"
                :subtitle="t('marketplace.cart.subtitle')"
                :badge="t('marketplace.cart.items_badge', { count: cart?.count ?? 0 })"
                icon="pi-shopping-cart"
                accent="blue"
            >
                <template #actions>
                    <!-- Sélecteur de pays/devise -->
                    <div class="flex items-center gap-2 bg-slate-800/50 border border-slate-700 rounded-2xl px-3 py-2">
                        <i class="pi pi-globe text-slate-300"></i>
                        <div class="flex flex-col">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ t('marketplace.cart.country_label') }}</p>
                            <Dropdown
                                :modelValue="country?.code"
                                :options="countries"
                                optionLabel="label"
                                optionValue="value"
                                :placeholder="t('marketplace.cart.country_placeholder')"
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
                </template>
            </PageHeader>

            <!-- MAIN -->
            <div class="page-container page-section">
                <div class="bg-white rounded-xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[400px]">

                    <div v-if="!hasItems" class="text-center py-16">
                        <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-shopping-bag text-3xl text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-700">Votre panier est vide</h3>
                        <p class="text-slate-400 text-sm mt-2 mb-6">DÃ©couvrez nos cours et commencez votre apprentissage.</p>
                        <Link :href="route('marketplace.index')">
                            <Button label="Explorer le marketplace" icon="pi pi-search" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !px-5 !py-3" />
                        </Link>
                    </div>

                    <div v-else class="space-y-8">
                        <!-- â•â•â•â•â•â•â•â•â•â•â•â• COURS GRATUITS â•â•â•â•â•â•â•â•â•â•â•â• -->
                        <section v-if="hasFree">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-gift text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-slate-800">Cours gratuits ({{ freeItems.length }})</h2>
                                    <p class="text-xs text-slate-500">Inscription immÃ©diate, sans paiement ni validation.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div v-for="item in freeItems" :key="item.id" class="bg-emerald-50/40 rounded-2xl border border-emerald-100 p-4 flex gap-4 items-center">
                                    <img :src="getImageUrl(item.course?.thumbnail_path)" :alt="item.course?.title" class="w-24 h-16 rounded-2xl object-cover" />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-black text-sm text-slate-800 truncate tracking-tight">{{ item.course?.title }}</h4>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ item.course?.instructor?.name }}</p>
                                    </div>
                                    <Tag value="GRATUIT" severity="success" class="!bg-emerald-500 !text-white !font-black !text-[10px] !uppercase !tracking-widest !px-3 !py-1" />
                                    <p class="text-lg font-black text-emerald-600 font-mono">GRATUIT</p>
                                        <Button icon="pi pi-trash" severity="danger" text rounded :aria-label="`Retirer ${item.course?.title ?? 'ce cours'} du panier`" @click="remove(item.course?.slug)" />
                                </div>
                            </div>
                            <div class="mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-4 flex-wrap">
                                <div class="flex items-center gap-3">
                                    <i class="pi pi-bolt text-emerald-600 text-2xl"></i>
                                    <div>
                                        <p class="font-black text-emerald-800">Inscription immÃ©diate</p>
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

                        <!-- â•â•â•â•â•â•â•â•â•â•â•â• COURS PAYANTS â•â•â•â•â•â•â•â•â•â•â•â• -->
                        <section v-if="hasPaid">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-credit-card text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-black text-slate-800">Cours payants ({{ paidItems.length }})</h2>
                                    <p class="text-xs text-slate-500">Paiement en ligne ou demande d'inscription validÃ©e par un administrateur.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div class="lg:col-span-2 space-y-3">
                                    <div v-for="item in paidItems" :key="item.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex gap-4 items-center">
                                        <img :src="getImageUrl(item.course?.thumbnail_path)" :alt="item.course?.title" class="w-24 h-16 rounded-2xl object-cover" />
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-black text-sm text-slate-800 truncate tracking-tight">{{ item.course?.title }}</h4>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ item.course?.instructor?.name }}</p>
                                        </div>
                                        <p class="text-lg font-black text-blue-600 font-mono">{{ formatLocal(item.price_local) }}</p>
                                    <Button icon="pi pi-trash" severity="danger" text rounded :aria-label="`Retirer ${item.course?.title ?? 'ce cours'} du panier`" @click="remove(item.course?.slug)" />
                                    </div>
                                </div>

                                <aside class="lg:col-span-1">
                                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sticky top-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">RÃ©sumÃ©</h3>
                                            <Tag :value="cart?.currency" severity="info" class="!font-black !text-[10px] !uppercase !tracking-widest" />
                                        </div>
                                        <!-- Code promo -->
                                        <div class="mb-4">
                                            <label for="coupon-input" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Code promo</label>
                                            <div v-if="activeCoupon" class="flex items-center justify-between gap-2 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                                                <span class="text-xs font-black text-emerald-700 font-mono flex items-center gap-2 min-w-0 truncate"><i class="pi pi-tag"></i> {{ activeCoupon }}</span>
                                                <Button label="Retirer" text severity="danger" size="small" class="!text-[10px] !font-black !uppercase !tracking-widest shrink-0" @click="removeCoupon" />
                                            </div>
                                            <div v-else class="flex gap-2">
                                                <InputText id="coupon-input" v-model="couponCode" placeholder="Ex: BIENVENUE10" class="flex-1 !rounded-xl !text-sm uppercase" @keyup.enter="applyCoupon" />
                                                <Button label="Appliquer" icon="pi pi-check" :loading="applyingCoupon" @click="applyCoupon" class="!bg-slate-900 hover:!bg-black !border-none !rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                                            </div>
                                        </div>
                                        <div class="space-y-2 pb-4 border-b border-slate-100">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">Sous-total</span>
                                                <span class="font-black text-slate-800 font-mono">{{ formatLocal(cart?.subtotal_local) }}</span>
                                            </div>
                                            <div v-if="appliedDiscountLocal > 0" class="flex justify-between text-sm">
                                                <span class="text-emerald-700">Remise</span>
                                                <span class="font-black text-emerald-600 font-mono">- {{ formatLocal(appliedDiscountLocal) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-slate-600">TVA ({{ taxRatePercent }}%)</span>
                                                <span class="font-black text-slate-400">{{ formatLocal(cart?.tax_local) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex justify-between py-4 text-lg">
                                            <span class="font-black">Total</span>
                                            <span class="font-black text-blue-600 font-mono">{{ formatLocal(cart?.total_local) }}</span>
                                        </div>
                                        <Button label="Passer commande" icon="pi pi-credit-card" @click="checkout" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                                        <p class="text-[10px] text-slate-400 text-center mt-3 font-black uppercase tracking-widest">
                                            <i class="pi pi-shield text-emerald-500 mr-1"></i> Paiement sÃ©curisÃ© SSL
                                        </p>
                                        <p class="text-[10px] text-slate-500 text-center mt-2 leading-relaxed">
                                            Vous pouvez aussi demander une inscription â€” un administrateur la validera aprÃ¨s vÃ©rification.
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
