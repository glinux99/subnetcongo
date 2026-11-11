<script setup>
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({ order: Object, intent: Object, provider: String });
const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: props.order?.currency ?? 'USD' }).format(p ?? 0);
const checkStatus = () => router.get(route('marketplace.confirmation', props.order.id));
</script>

<template>
    <AppLayout>
        <Head title="Paiement en cours" />
        <div class="page-bg min-h-screen font-sans pb-20 flex items-center justify-center">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xl p-8 sm:p-10 max-w-lg w-full mx-4 text-center">
                <div class="w-20 h-20 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <i class="pi pi-mobile text-blue-600 text-4xl"></i>
                </div>
                <Tag value="En attente de confirmation" severity="warn" class="!text-[10px] !font-black !uppercase !tracking-widest !mb-4" />
                <h1 class="text-2xl font-black text-slate-800 mb-2">Confirmez sur votre téléphone</h1>
                <p class="text-slate-500 text-sm mb-6">Une demande de paiement de <strong class="text-blue-600 font-mono">{{ formatPrice(order?.amount) }}</strong> a été envoyée au <strong class="font-black">{{ order?.phone_number ?? 'votre numéro' }}</strong>. Confirmez la transaction, puis vérifiez le statut ci-dessous.</p>
                <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left text-sm space-y-2">
                    <div class="flex justify-between gap-4"><span class="text-slate-500 font-medium">Fournisseur</span><span class="font-black uppercase text-slate-800">{{ provider }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500 font-medium">Référence</span><span class="font-mono text-xs text-slate-800 truncate">{{ intent?.reference ?? '—' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500 font-medium">Commande</span><span class="font-mono text-xs text-slate-800">{{ order?.order_number }}</span></div>
                </div>
                <div class="space-y-3">
                    <Button label="Vérifier le statut de ma commande" icon="pi pi-refresh" @click="checkStatus" class="!bg-blue-600 hover:!bg-blue-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                    <Link :href="route('marketplace.cart')" class="block">
                        <Button label="Retour au panier" icon="pi pi-shopping-cart" outlined class="!border-slate-200 !text-slate-600 !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                    </Link>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-black uppercase tracking-widest">
                    <i class="pi pi-info-circle text-blue-500 mr-1"></i> La validation peut prendre quelques instants
                </p>
            </div>
        </div>
    </AppLayout>
</template>
