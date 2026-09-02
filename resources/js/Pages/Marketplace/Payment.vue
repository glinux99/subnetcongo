<script setup>
import { onMounted, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';

const props = defineProps({ order: Object, intent: Object, provider: String });
const checking = ref(true);

const checkStatus = async () => {
    await new Promise(r => setTimeout(r, 2500));
    router.reload({ only: ['order'] });
};

onMounted(() => {
    setTimeout(() => { checking.value = false; }, 5000);
});

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
</script>

<template>
    <AppLayout>
        <Head title="Paiement en cours" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans flex items-center justify-center py-12">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-xl p-10 max-w-lg w-full text-center">
                <div class="w-20 h-20 bg-violet-100 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <i class="pi pi-mobile text-violet-600 text-4xl"></i>
                </div>
                <h1 class="text-2xl font-black italic text-slate-800 mb-2">Confirmez sur votre téléphone</h1>
                <p class="text-slate-500 text-sm mb-6">
                    Une demande de paiement de <strong class="text-violet-600">{{ formatPrice(order?.amount) }}</strong> a été envoyée au
                    <strong>{{ order?.phone_number }}</strong>. Saisissez votre code PIN pour valider.
                </p>

                <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left text-sm space-y-2">
                    <div class="flex justify-between"><span class="text-slate-500">Fournisseur</span><span class="font-black uppercase">{{ provider }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Référence</span><span class="font-mono text-xs">{{ intent?.reference ?? '—' }}</span></div>
                </div>

                <Button v-if="checking" label="Vérification..." icon="pi pi-spin pi-spinner" disabled class="w-full font-black" />
                <Link v-else :href="route('marketplace.confirmation', order.id)">
                    <Button label="J'ai confirmé" icon="pi pi-check" @click="checkStatus" class="p-button-primary w-full font-black" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>