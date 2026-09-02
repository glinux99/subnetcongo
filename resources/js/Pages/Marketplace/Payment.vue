<script setup>
import { onMounted, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({ order: Object, intent: Object, provider: String });
const checking = ref(true);
const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
onMounted(() => setTimeout(() => { checking.value = false; }, 5000));
</script>

<template>
    <AppLayout>
        <Head title="Paiement en cours" />
        <div class="min-h-screen bg-slate-50 font-sans flex items-center justify-center py-12 px-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xl p-10 max-w-lg w-full text-center">
                <div class="w-20 h-20 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <i class="pi pi-mobile text-indigo-600 text-4xl"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-800 mb-2">Confirmez sur votre téléphone</h1>
                <p class="text-slate-500 text-sm mb-6">Une demande de paiement de <strong class="text-indigo-600 font-mono">{{ formatPrice(order?.amount) }}</strong> a été envoyée au <strong class="font-black">{{ order?.phone_number }}</strong>.</p>
                <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left text-sm space-y-2">
                    <div class="flex justify-between"><span class="text-slate-500 font-medium">Fournisseur</span><span class="font-black uppercase text-slate-800">{{ provider }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500 font-medium">Référence</span><span class="font-mono text-xs text-slate-800">{{ intent?.reference ?? '—' }}</span></div>
                </div>
                <Button v-if="checking" label="Vérification..." icon="pi pi-spin pi-spinner" disabled class="!bg-slate-200 !text-slate-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                <Link v-else :href="route('marketplace.confirmation', order.id)">
                    <Button label="J'ai confirmé" icon="pi pi-check" class="!bg-indigo-600 hover:!bg-indigo-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>