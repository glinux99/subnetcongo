<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';

defineProps({ order: Object });
const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
</script>

<template>
    <AppLayout>
        <Head title="Commande confirmée" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans flex items-center justify-center py-12">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-xl p-10 max-w-xl w-full text-center">
                <div class="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="pi pi-check-circle text-emerald-500 text-5xl"></i>
                </div>
                <h1 class="text-3xl font-black italic text-slate-800 mb-2">Commande confirmée !</h1>
                <p class="text-slate-500 text-sm mb-6">Votre paiement a été reçu et vos cours sont désormais accessibles.</p>

                <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left">
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-slate-500 font-bold">N° de commande</span>
                        <span class="font-black text-slate-800">{{ order?.order_number }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-slate-500 font-bold">Montant</span>
                        <span class="font-black text-violet-600">{{ formatPrice(order?.amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-slate-500 font-bold">Statut</span>
                        <span class="font-black text-emerald-600 uppercase">{{ order?.status }}</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Link :href="route('student.my-courses')" class="flex-1">
                        <Button label="Mes cours" icon="pi pi-play" class="p-button-primary w-full font-black" />
                    </Link>
                    <Link :href="route('marketplace.index')" class="flex-1">
                        <Button label="Continuer" icon="pi pi-shopping-bag" outlined class="w-full font-black" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>