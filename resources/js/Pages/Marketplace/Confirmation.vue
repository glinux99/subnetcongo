<script setup>
import { computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({ order: Object });

const isPaid = computed(() => ['paid', 'succeeded'].includes(props.order?.status));
const isFailed = computed(() => ['failed', 'refunded', 'cancelled'].includes(props.order?.status));
const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: props.order?.currency ?? 'USD' }).format(p ?? 0);
const checkStatus = () => router.reload({ only: ['order'] });
</script>

<template>
    <AppLayout>
        <Head :title="isPaid ? 'Commande confirmée' : 'Vérification du paiement'" />
        <div class="page-bg min-h-screen font-sans pb-20 flex items-center justify-center">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xl p-8 sm:p-10 max-w-xl w-full mx-4 text-center">

                <template v-if="isPaid">
                    <div class="w-24 h-24 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-check-circle text-emerald-500 text-5xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-slate-800 mb-2 tracking-tight">Commande confirmée !</h1>
                    <p class="text-slate-500 text-sm mb-6">Votre paiement a été reçu et vos cours sont désormais accessibles.</p>
                </template>
                <template v-else-if="isFailed">
                    <div class="w-24 h-24 bg-rose-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-times-circle text-rose-500 text-5xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-slate-800 mb-2 tracking-tight">Paiement non abouti</h1>
                    <p class="text-slate-500 text-sm mb-6">Le paiement de cette commande a échoué ou a été remboursé. Vous pouvez réessayer ou contacter le support.</p>
                </template>
                <template v-else>
                    <div class="w-24 h-24 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-hourglass text-amber-500 text-5xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-slate-800 mb-2 tracking-tight">Paiement en cours de vérification</h1>
                    <p class="text-slate-500 text-sm mb-6">Nous n'avons pas encore reçu la confirmation de votre paiement. Dès sa réception, vos cours seront débloqués automatiquement.</p>
                </template>

                <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left">
                    <div class="flex justify-between text-sm py-1.5 gap-4">
                        <span class="text-slate-500 font-bold">N° de commande</span>
                        <span class="font-black text-slate-800 font-mono">{{ order?.order_number }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-1.5 gap-4">
                        <span class="text-slate-500 font-bold">Montant</span>
                        <span class="font-black text-blue-600 font-mono">{{ formatPrice(order?.amount) }}</span>
                    </div>
                    <div v-if="order?.discount_amount > 0" class="flex justify-between text-sm py-1.5 gap-4">
                        <span class="text-slate-500 font-bold">Remise{{ order?.coupon_code ? ' (' + order.coupon_code + ')' : '' }}</span>
                        <span class="font-black text-emerald-600 font-mono">- {{ formatPrice(order?.discount_amount) }}</span>
                    </div>
                    <div v-if="order?.tax_amount > 0" class="flex justify-between text-sm py-1.5 gap-4">
                        <span class="text-slate-500 font-bold">TVA</span>
                        <span class="font-black text-slate-600 font-mono">{{ formatPrice(order?.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-1.5 items-center gap-4">
                        <span class="text-slate-500 font-bold">Statut</span>
                        <Tag :value="order?.status"
                            :class="isPaid
                                ? '!bg-emerald-500/10 !text-emerald-700 !border !border-emerald-500/30'
                                : isFailed
                                    ? '!bg-rose-500/10 !text-rose-700 !border !border-rose-500/30'
                                    : '!bg-amber-500/10 !text-amber-700 !border !border-amber-500/30'"
                            class="!text-[10px] !font-black !uppercase !tracking-widest" />
                    </div>
                </div>

                <div v-if="isPaid" class="flex gap-3">
                    <Link :href="route('student.my-courses')" class="flex-1">
                        <Button label="Mes cours" icon="pi pi-play" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                    </Link>
                    <Link :href="route('marketplace.index')" class="flex-1">
                        <Button label="Continuer" icon="pi pi-shopping-bag" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                    </Link>
                </div>
                <div v-else class="flex flex-col sm:flex-row gap-3">
                    <Button label="Vérifier le statut de ma commande" icon="pi pi-refresh" :disabled="isFailed" @click="checkStatus" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 flex-1" />
                    <Link :href="route('marketplace.index')" class="flex-1">
                        <Button label="Continuer mes achats" icon="pi pi-shopping-bag" outlined class="!border-slate-200 !text-slate-700 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
