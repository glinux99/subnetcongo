<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Knob from 'primevue/knob';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const page = usePage();
const props = defineProps({
    courses: Object,
    filters: Object,
    enrolledIds: Array,
    wishlistIds: Array,
    cartCount: Number,
    categories: Array,
});

const search = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? null);
const level = ref(props.filters?.level ?? null);
const priceRange = ref(props.filters?.price_range ?? null);
const sort = ref(props.filters?.sort ?? 'popular');

const stats = computed(() => ({
    total: props.courses?.total ?? 0,
    free: (props.courses?.data ?? []).filter(c => !c.price).length,
    paid: (props.courses?.data ?? []).filter(c => c.price).length,
    avg_rating: 4.6,
}));

const levelOptions = [
    { label: 'Tous niveaux', value: null },
    { label: 'Débutant', value: 'beginner' },
    { label: 'Intermédiaire', value: 'intermediate' },
    { label: 'Avancé', value: 'advanced' },
];

const priceOptions = [
    { label: 'Tous prix', value: null },
    { label: 'Gratuit', value: 'free' },
    { label: 'Payant', value: 'paid' },
];

const sortOptions = [
    { label: 'Populaires', value: 'popular' },
    { label: 'Plus récents', value: 'newest' },
    { label: 'Prix croissant', value: 'price_low' },
    { label: 'Prix décroissant', value: 'price_high' },
];

const apply = () => {
    router.get(route('marketplace.index'), {
        search: search.value || undefined,
        category: category.value || undefined,
        level: level.value || undefined,
        price_range: priceRange.value || undefined,
        sort: sort.value,
    }, { preserveState: true });
};

const addToCart = (course) => {
    router.post(route('marketplace.cart.add', course.id), {}, { preserveScroll: true });
};
const toggleWishlist = (course) => {
    router.post(route('marketplace.wishlist.toggle', course.id), {}, { preserveScroll: true });
};
const getImageUrl = (path) => {
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600';
    return path.startsWith('http') ? path : `/storage/${path}`;
};
const formatPrice = (price) => {
    if (!price || price == 0) return 'Gratuit';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(price);
};
</script>

<template>
    <AppLayout>
        <Head title="Marketplace" />

        <div class="min-h-screen bg-slate-50 font-sans pb-10">

            <!-- HEADER GLOBAL -->
            <div class="bg-slate-900 pt-8 pb-20 px-6 lg:px-12 relative overflow-hidden shadow-xl border-b border-slate-800">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-violet-600/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-20 w-[300px] h-[300px] bg-pink-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 mix-blend-overlay pointer-events-none"></div>

                <div class="max-w-[1600px] mx-auto relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 border border-white/10">
                            <i class="pi pi-shopping-bag text-white text-3xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h1 class="text-3xl font-black text-white tracking-tight">Catalogue Marketplace</h1>
                                <Tag value="SUBNETCONGO" class="!bg-violet-500/20 !text-violet-300 !border !border-violet-500/30 !text-[10px] !font-black !uppercase !tracking-widest" />
                            </div>
                            <p class="text-slate-400 font-medium text-sm">Explorez des centaines de cours dispensés par des experts africains.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <Link :href="route('marketplace.cart')" class="px-4 py-2 bg-slate-800/60 hover:bg-slate-700/80 border border-slate-700/50 rounded-xl text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-colors">
                            <i class="pi pi-shopping-cart text-sm"></i> Mon panier
                            <span v-if="props.cartCount" class="bg-pink-500 text-white text-[9px] font-black rounded-full w-5 h-5 flex items-center justify-center">{{ props.cartCount }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Search bar -->
                <div class="max-w-2xl mx-auto mt-6 relative z-10">
                    <IconField iconPosition="left" class="w-full">
                        <InputIcon class="pi pi-search text-slate-400" />
                        <InputText v-model="search" @keyup.enter="apply" placeholder="Que souhaitez-vous apprendre aujourd'hui ?" class="w-full !bg-white/10 !border !border-white/10 !rounded-2xl !py-4 !pl-12 !text-white placeholder:!text-slate-400 focus:!bg-white/20" />
                    </IconField>
                </div>
            </div>

            <!-- MAIN -->
            <div class="max-w-[1600px] mx-auto px-4 lg:px-6 -mt-10 relative z-20">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 lg:p-8 min-h-[600px]">

                    <!-- KPI strip -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm group hover:border-violet-300 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="w-11 h-11 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-shopping-bag text-lg"></i></div>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cours disponibles</h4>
                            <h2 class="text-3xl font-black text-slate-800 font-mono tabular-nums">{{ stats.total }}</h2>
                        </div>
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm group hover:border-emerald-300 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-gift text-lg"></i></div>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Gratuits</h4>
                            <h2 class="text-3xl font-black text-slate-800 font-mono tabular-nums">{{ stats.free }}</h2>
                        </div>
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm group hover:border-amber-300 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform"><i class="pi pi-credit-card text-lg"></i></div>
                            </div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Premium</h4>
                            <h2 class="text-3xl font-black text-slate-800 font-mono tabular-nums">{{ stats.paid }}</h2>
                        </div>
                        <div class="bg-slate-900 p-5 rounded-3xl border border-slate-800 shadow-lg text-white flex flex-col justify-between relative overflow-hidden">
                            <i class="pi pi-star-fill absolute -right-2 -bottom-2 text-5xl text-white/5"></i>
                            <div class="relative z-10">
                                <div class="w-11 h-11 bg-white/10 text-amber-400 backdrop-blur-md rounded-xl flex items-center justify-center mb-3 border border-white/10"><i class="pi pi-star text-lg"></i></div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Note moyenne</h4>
                                <h2 class="text-3xl font-black text-white font-mono tabular-nums">{{ stats.avg_rating }}<span class="text-base text-slate-500">/5</span></h2>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Filtres -->
                        <aside class="lg:col-span-3">
                            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 sticky top-6">
                                <h3 class="text-[10px] font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="pi pi-filter text-violet-500"></i> Filtres
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Catégorie</label>
                                        <Select v-model="category" :options="props.categories" optionLabel="name" optionValue="id" placeholder="Toutes" class="w-full" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Niveau</label>
                                        <Select v-model="level" :options="levelOptions" optionLabel="label" optionValue="value" class="w-full" />
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Prix</label>
                                        <Select v-model="priceRange" :options="priceOptions" optionLabel="label" optionValue="value" class="w-full" />
                                    </div>
                                    <Button label="Appliquer" icon="pi pi-filter" @click="apply" class="!bg-violet-600 hover:!bg-violet-500 !border-none !rounded-xl !font-black !uppercase !tracking-widest !text-[10px] !py-3 w-full" />
                                </div>
                            </div>
                        </aside>

                        <!-- Liste cours -->
                        <main class="lg:col-span-9">
                            <div class="flex justify-between items-center mb-5">
                                <p class="text-sm text-slate-600">
                                    <strong class="font-black text-slate-800">{{ props.courses?.total ?? 0 }}</strong> cours disponibles
                                </p>
                                <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" @change="apply" class="w-56" />
                            </div>

                            <div v-if="(props.courses?.data?.length ?? 0) === 0" class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-16 text-center">
                                <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="pi pi-search text-3xl text-slate-400"></i>
                                </div>
                                <h3 class="text-xl font-black text-slate-700">Aucun cours trouvé</h3>
                                <p class="text-slate-400 text-sm mt-2">Essayez d'élargir vos critères de recherche.</p>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                                <div v-for="course in props.courses.data" :key="course.id" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-xl hover:border-violet-300 transition-all duration-300 group">
                                    <div class="relative h-44 overflow-hidden">
                                        <Link :href="route('marketplace.show', course.id)">
                                            <img :src="getImageUrl(course.thumbnail_path || course.image)" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        </Link>
                                        <button @click.prevent="toggleWishlist(course)" class="absolute top-3 right-3 w-9 h-9 bg-white/95 hover:bg-white rounded-xl flex items-center justify-center shadow-md transition-all">
                                            <i :class="props.wishlistIds?.includes(course.id) ? 'pi pi-heart-fill text-rose-500' : 'pi pi-heart text-slate-400'" class="text-sm"></i>
                                        </button>
                                        <Tag v-if="props.enrolledIds?.includes(course.id)" value="Inscrit" severity="success" class="absolute top-3 left-3 !text-[9px] !font-black !uppercase !tracking-widest" />
                                        <Tag v-else-if="course.level" :value="course.level" class="!absolute !top-3 !left-3 !bg-slate-900/80 !text-white !border-none !text-[9px] !font-black !uppercase !tracking-widest capitalize" />
                                    </div>
                                    <div class="p-5">
                                        <Link :href="route('marketplace.show', course.id)">
                                            <h3 class="font-black text-slate-800 text-sm leading-tight mb-1 line-clamp-2 hover:text-violet-600 tracking-tight">{{ course.title }}</h3>
                                        </Link>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">{{ course.instructor?.name }}</p>
                                        <div class="flex justify-between items-center mb-4">
                                            <Rating :modelValue="4.5" readonly :stars="5" class="text-xs" />
                                            <span class="text-lg font-black text-violet-600 font-mono">{{ formatPrice(course.effective_price ?? course.price) }}</span>
                                        </div>
                                        <Button v-if="!props.enrolledIds?.includes(course.id)" label="Ajouter au panier" icon="pi pi-shopping-cart" @click="addToCart(course)" outlined size="small" class="w-full !border-slate-200 !text-slate-700 !font-black !uppercase !tracking-widest !text-[10px] !py-2.5" />
                                        <Link v-else :href="route('student.my-courses')">
                                            <Button label="Continuer" icon="pi pi-play" size="small" class="!bg-violet-600 hover:!bg-violet-500 !border-none w-full !font-black !uppercase !tracking-widest !text-[10px] !py-2.5" />
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div v-if="props.courses?.last_page > 1" class="flex justify-center mt-8 gap-1">
                                <Link v-for="link in props.courses.links" :key="link.label" :href="link.url" preserve-scroll :class="['px-3 py-2 rounded-xl text-xs font-black', link.active ? '!bg-violet-600 !text-white' : '!bg-slate-50 !text-slate-600 hover:!bg-slate-100']" v-html="link.label" />
                            </div>
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>