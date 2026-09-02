<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';

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
const language = ref(props.filters?.language ?? null);
const sort = ref(props.filters?.sort ?? 'popular');

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
        language: language.value || undefined,
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
    if (!path) return 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const formatPrice = (price) => {
    if (price == 0 || price == null) return 'Gratuit';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(price);
};
</script>

<template>
    <AppLayout>
        <Head title="Marketplace" />

        <div class="min-h-screen bg-[#F8FAFC] font-sans">
            <!-- Hero -->
            <section class="bg-gradient-to-br from-violet-700 via-violet-600 to-indigo-700 text-white py-16 px-6">
                <div class="max-w-[1400px] mx-auto text-center">
                    <p class="text-[10px] font-black uppercase tracking-widest text-violet-200 mb-3">SUBNETCONGO ACADEMY</p>
                    <h1 class="text-5xl font-black italic tracking-tight mb-4">Explorez le savoir</h1>
                    <p class="text-lg text-violet-100 max-w-2xl mx-auto mb-8">
                        Des centaines de cours dispensés par des experts. Apprenez à votre rythme, obtenez des certificats reconnus.
                    </p>
                    <div class="max-w-2xl mx-auto relative">
                        <i class="pi pi-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                        <InputText v-model="search" @keyup.enter="apply"
                                   placeholder="Que souhaitez-vous apprendre aujourd'hui ?"
                                   class="w-full pl-14 pr-4 py-4 rounded-2xl border-none shadow-2xl text-slate-800 text-sm" />
                    </div>
                </div>
            </section>

            <div class="max-w-[1400px] mx-auto px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Filtres -->
                <aside class="lg:col-span-3">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 sticky top-6">
                        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest mb-4">Filtres</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Catégorie</label>
                                <Select v-model="category" :options="categories" optionLabel="name" optionValue="id"
                                        placeholder="Toutes" class="w-full" />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Niveau</label>
                                <Select v-model="level" :options="levelOptions" optionLabel="label" optionValue="value"
                                        class="w-full" />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 mb-2 block">Prix</label>
                                <Select v-model="priceRange" :options="priceOptions" optionLabel="label" optionValue="value"
                                        class="w-full" />
                            </div>
                            <Button label="Appliquer" icon="pi pi-filter" @click="apply"
                                    class="p-button-primary w-full font-black" />
                        </div>
                    </div>
                </aside>

                <!-- Liste cours -->
                <main class="lg:col-span-9">
                    <div class="flex justify-between items-center mb-5">
                        <p class="text-sm text-slate-600">
                            <strong>{{ courses?.total ?? 0 }}</strong> cours disponibles
                        </p>
                        <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value"
                                @change="apply" class="w-56" />
                    </div>

                    <div v-if="(courses?.data?.length ?? 0) === 0"
                         class="bg-white border-2 border-dashed border-slate-200 rounded-3xl p-16 text-center">
                        <i class="pi pi-search text-5xl text-slate-300 mb-4 block"></i>
                        <h3 class="text-xl font-black text-slate-700 italic">Aucun cours trouvé</h3>
                        <p class="text-slate-400 text-sm mt-2">Essayez d'élargir vos critères de recherche.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <div v-for="course in courses.data" :key="course.id"
                             class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-xl transition-all duration-300 group">
                            <div class="relative h-44 overflow-hidden">
                                <Link :href="route('marketplace.show', course.id)">
                                    <img :src="getImageUrl(course.thumbnail_path || course.image)"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                </Link>
                                <button @click.prevent="toggleWishlist(course)"
                                        class="absolute top-3 right-3 w-9 h-9 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-md transition-all">
                                    <i :class="wishlistIds?.includes(course.id) ? 'pi pi-heart-fill text-rose-500' : 'pi pi-heart text-slate-400'"
                                       class="text-sm"></i>
                                </button>
                                <Tag v-if="enrolledIds?.includes(course.id)" value="Inscrit" severity="success"
                                     class="absolute top-3 left-3 text-[9px] font-black" />
                                <Tag v-else-if="course.level" :value="course.level" severity="info"
                                     class="absolute top-3 left-3 text-[9px] font-black capitalize" />
                            </div>
                            <div class="p-5">
                                <Link :href="route('marketplace.show', course.id)">
                                    <h3 class="font-black text-slate-800 text-sm leading-tight mb-1 line-clamp-2 italic hover:text-violet-600">
                                        {{ course.title }}
                                    </h3>
                                </Link>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                    {{ course.instructor?.name }}
                                </p>
                                <div class="flex justify-between items-center mb-4">
                                    <Rating :modelValue="4.5" readonly :stars="5" class="text-sm" />
                                    <span class="text-lg font-black text-violet-600">{{ formatPrice(course.effective_price ?? course.price) }}</span>
                                </div>
                                <Button v-if="!enrolledIds?.includes(course.id)"
                                        label="Ajouter au panier" icon="pi pi-shopping-cart"
                                        @click="addToCart(course)" outlined size="small"
                                        class="w-full font-bold text-xs" />
                                <Link v-else :href="route('student.my-courses')">
                                    <Button label="Continuer" icon="pi pi-play" size="small"
                                            class="p-button-primary w-full font-black text-xs" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-if="courses?.last_page > 1" class="flex justify-center mt-8 gap-1">
                        <Link v-for="link in courses.links" :key="link.label"
                              :href="link.url" preserve-scroll
                              :class="[
                                  'px-3 py-2 rounded-lg text-xs font-bold',
                                  link.active ? 'bg-violet-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'
                              ]"
                              v-html="link.label" />
                    </div>
                </main>
            </div>
        </div>
    </AppLayout>
</template>