<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';

const props = defineProps({ cart: Object });

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
const total = computed(() => props.cart?.total ?? 0);
const hasItems = computed(() => (props.cart?.items?.length ?? 0) > 0);

const remove = (id) => router.delete(route('marketplace.cart.remove', id), { preserveScroll: true });
const checkout = () => router.get(route('marketplace.checkout'));
</script>

<template>
    <AppLayout>
        <Head title="Mon Panier" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans py-10">
            <div class="max-w-[1100px] mx-auto px-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 bg-violet-100 rounded-2xl flex items-center justify-center">
                        <i class="pi pi-shopping-cart text-violet-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black italic text-slate-800">Mon Panier</h1>
                        <p class="text-sm text-slate-500">{{ cart?.items?.length ?? 0 }} article(s)</p>
                    </div>
                </div>

                <div v-if="!hasItems" class="bg-white rounded-3xl border-2 border-dashed border-slate-200 py-20 text-center">
                    <i class="pi pi-shopping-bag text-6xl text-slate-200 mb-4 block"></i>
                    <h3 class="text-xl font-black text-slate-700 italic">Votre panier est vide</h3>
                    <p class="text-slate-400 text-sm mt-2 mb-6">Découvrez nos cours et commencez votre apprentissage.</p>
                    <Link :href="route('marketplace.index')">
                        <Button label="Explorer le marketplace" icon="pi pi-search" class="p-button-primary font-black" />
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-3">
                        <div v-for="item in cart.items" :key="item.id"
                             class="bg-white rounded-2xl border border-slate-100 p-4 flex gap-4 items-center">
                            <img :src="item.course?.thumbnail_path || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=200'"
                                 class="w-24 h-16 rounded-lg object-cover" />
                            <div class="flex-1 min-w-0">
                                <h4 class="font-black text-sm text-slate-800 truncate">{{ item.course?.title }}</h4>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {{ item.course?.instructor?.name }}
                                </p>
                            </div>
                            <p class="text-lg font-black text-violet-600">{{ formatPrice(item.price) }}</p>
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="remove(item.course?.id)" />
                        </div>
                    </div>

                    <aside class="lg:col-span-1">
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-6">
                            <h3 class="font-black text-slate-800 mb-4">Résumé</h3>
                            <div class="space-y-2 pb-4 border-b border-slate-100">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-600">Sous-total</span>
                                    <span class="font-black">{{ formatPrice(total) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-600">Taxes</span>
                                    <span class="font-black text-slate-400">—</span>
                                </div>
                            </div>
                            <div class="flex justify-between py-4 text-lg">
                                <span class="font-black">Total</span>
                                <span class="font-black text-violet-600">{{ formatPrice(total) }}</span>
                            </div>
                            <Button label="Passer commande" icon="pi pi-credit-card"
                                    @click="checkout" class="p-button-primary w-full font-black py-3" />
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </AppLayout>
</template>