<script setup>
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({ cart: Object });

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(p);
const total = computed(() => props.cart?.total ?? 0);
const hasItems = computed(() => (props.cart?.items?.length ?? 0) > 0);
const remove = (id) => router.delete(route('marketplace.cart.remove', id), { preserveScroll: true });
const checkout = () => router.get(route('marketplace.checkout'));
const getImageUrl = (path) => path?.startsWith('http') ? path : (path ? `/storage/${path}` : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=200');
</script>

<template>
    <AppLayout>
        <Head title="Mon Panier" />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <!-- HEADER -->
            <div class="bg-slate-900 pt-8 pb-16 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-violet-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-pink-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>
                <div class="max-w-[1600px] mx-auto relative z-10 flex items-center gap-5">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 border border-white/10">
                        <i class="pi pi-shopping-cart text-white text-3xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 class="text-3xl font-black text-white tracking-tight">Mon Panier</h1>
                            <Tag :value="`${cart?.items?.length ?? 0} articles`" class="!bg-violet-500/20 !text-violet-300 !border !border-violet-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                        </div>
                        <p class="text-slate-400 font-medium text-sm">Vérifiez vos articles avant de finaliser votre commande.</p>
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

                    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-3">
                            <div v-for="item in cart.items" :key="item.id" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex gap-4 items-center">
                                <img :src="getImageUrl(item.course?.thumbnail_path)" class="w-24 h-16 rounded-2xl object-cover" />
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-black text-sm text-slate-800 truncate tracking-tight">{{ item.course?.title }}</h4>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ item.course?.instructor?.name }}</p>
                                </div>
                                <p class="text-lg font-black text-violet-600 font-mono">{{ formatPrice(item.price) }}</p>
                                <Button icon="pi pi-trash" severity="danger" text rounded @click="remove(item.course?.id)" />
                            </div>
                        </div>

                        <aside class="lg:col-span-1">
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sticky top-6">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Résumé</h3>
                                <div class="space-y-2 pb-4 border-b border-slate-100">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">Sous-total</span>
                                        <span class="font-black text-slate-800 font-mono">{{ formatPrice(total) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-600">Taxes</span>
                                        <span class="font-black text-slate-400">—</span>
                                    </div>
                                </div>
                                <div class="flex justify-between py-4 text-lg">
                                    <span class="font-black">Total</span>
                                    <span class="font-black text-violet-600 font-mono">{{ formatPrice(total) }}</span>
                                </div>
                                <Button label="Passer commande" icon="pi pi-credit-card" @click="checkout" class="!bg-violet-600 hover:!bg-violet-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-xs !py-3 w-full" />
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>